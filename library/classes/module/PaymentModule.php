<?php

abstract class PaymentModuleCore extends Module {

    /** @var int Current order's id */
    public $currentOrder;
    public $currencies = true;
    public $currencies_mode = 'checkbox';

    const DEBUG_MODE = false;

    public function install() {

        if (!parent::install()) {
            return false;
        }

        // Insert currencies availability

        if ($this->currencies_mode == 'checkbox') {

            if (!$this->addCheckboxCurrencyRestrictionsForModule()) {
                return false;
            }

        } else

        if ($this->currencies_mode == 'radio') {

            if (!$this->addRadioCurrencyRestrictionsForModule()) {
                return false;
            }

        } else {
            Tools::displayError('No currency mode for payment module');
        }

        // Insert countries availability
        $return = $this->addCheckboxCountryRestrictionsForModule();

        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_FIXED')) {
            Configuration::updateValue('CONF_' . strtoupper($this->name) . '_FIXED', '0.2');
        }

        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_VAR')) {
            Configuration::updateValue('CONF_' . strtoupper($this->name) . '_VAR', '2');
        }

        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_FIXED_FOREIGN')) {
            Configuration::updateValue('CONF_' . strtoupper($this->name) . '_FIXED_FOREIGN', '0.2');
        }

        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_VAR_FOREIGN')) {
            Configuration::updateValue('CONF_' . strtoupper($this->name) . '_VAR_FOREIGN', '2');
        }

        return $return;
    }

    public function uninstall() {

        if (!Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'module_country` WHERE id_module = ' . (int) $this->id)
            || !Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'module_currency` WHERE id_module = ' . (int) $this->id)
            || !Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'module_group` WHERE id_module = ' . (int) $this->id)) {
            return false;
        }

        return parent::uninstall();
    }

    public function addCheckboxCurrencyRestrictionsForModule(array $shops = []) {

        if (!$shops) {
            $shops = Shop::getShops(true, null, true);
        }

        foreach ($shops as $s) {

            if (!Db::getInstance()->execute('
                    INSERT INTO `' . _DB_PREFIX_ . 'module_currency` (`id_module`, `id_shop`, `id_currency`)
                    SELECT ' . (int) $this->id . ', "' . (int) $s . '", `id_currency` FROM `' . _DB_PREFIX_ . 'currency` WHERE deleted = 0')) {
                return false;
            }

        }

        return true;
    }

    public function addRadioCurrencyRestrictionsForModule(array $shops = []) {

        if (!$shops) {
            $shops = Shop::getShops(true, null, true);
        }

        foreach ($shops as $s) {

            if (!Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'module_currency` (`id_module`, `id_shop`, `id_currency`)
                VALUES (' . (int) $this->id . ', "' . (int) $s . '", -2)')) {
                return false;
            }

        }

        return true;
    }

    public function addCheckboxCountryRestrictionsForModule(array $shops = []) {

        $countries = Country::getCountries((int) Context::getContext()->language->id, true); //get only active country
        $country_ids = [];

        foreach ($countries as $country) {
            $country_ids[] = $country['id_country'];
        }

        return Country::addModuleRestrictions($shops, $countries, [['id_module' => (int) $this->id]]);
    }

    public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown',
        $message = null, $extra_vars = [], $currency_special = null, $dont_touch_amount = false,
        $secure_key = false, Shop $shop = null) {

        if (self::DEBUG_MODE) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Function called', 1, null, 'Cart', (int) $id_cart, true);
        }

        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }

        $this->context->cart = new Cart((int) $id_cart);
        $this->context->customer = new Customer((int) $this->context->cart->id_customer);
        // The tax cart is loaded before the customer so re-cache the tax calculation method
        $this->context->cart->setTaxCalculationMethod();

        $this->context->language = new Language((int) $this->context->cart->id_lang);
        $this->context->shop = ($shop ? $shop : new Shop((int) $this->context->cart->id_shop));
        ShopUrl::resetMainDomainCache();
        $id_currency = $currency_special ? (int) $currency_special : (int) $this->context->cart->id_currency;
        $this->context->currency = new Currency((int) $id_currency, null, (int) $this->context->shop->id);

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $this->context->country;
        }

        $order_status = new CustomerPieceState((int) $id_order_state, (int) $this->context->language->id);

        if (!Validate::isLoadedObject($order_status)) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status cannot be loaded', 3, null, 'Cart', (int) $id_cart, true);
            throw new PhenyxShopException('Can\'t load Order status');
        }

        if (!$this->active) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Module is not active', 3, null, 'Cart', (int) $id_cart, true);
            die(Tools::displayError());
        }

        // Does order already exists ?

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {

            if ($secure_key !== false && $secure_key != $this->context->cart->secure_key) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Secure key does not match', 3, null, 'Cart', (int) $id_cart, true);
                die(Tools::displayError());
            }

            // For each package, generate an order
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            $package_list = $this->context->cart->getPackageList();
            $cart_delivery_option = $this->context->cart->getDeliveryOption();

            // If some delivery options are not defined, or not valid, use the first valid option

            foreach ($delivery_option_list as $id_address => $package) {

                if (!isset($cart_delivery_option[$id_address]) || !array_key_exists($cart_delivery_option[$id_address], $package)) {

                    foreach ($package as $key => $val) {
                        $cart_delivery_option[$id_address] = $key;
                        break;
                    }

                }

            }

            $order_list = [];
            $order_detail_list = [];

           

            $order_creation_failed = false;
            $cart_total_paid = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH), 2);

            foreach ($cart_delivery_option as $id_address => $key_carriers) {

                foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {

                    foreach ($data['package_list'] as $id_package) {
                        // Rewrite the id_warehouse
                        $package_list[$id_address][$id_package]['id_warehouse'] = (int) $this->context->cart->getPackageIdWarehouse($package_list[$id_address][$id_package], (int) $id_carrier);
                        $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                    }

                }

            }

            // Make sure CartRule caches are empty
            CartRule::cleanCache();
            $cart_rules = $this->context->cart->getCartRules();

            foreach ($cart_rules as $cart_rule) {

                if (($rule = new CartRule((int) $cart_rule['obj']->id)) && Validate::isLoadedObject($rule)) {

                    if ($error = $rule->checkValidity($this->context, true, true)) {
                        $this->context->cart->removeCartRule((int) $rule->id);

                        if (isset($this->context->cookie) && isset($this->context->cookie->id_customer) && $this->context->cookie->id_customer && !empty($rule->code)) {

                            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                                Tools::redirect('index.php?controller=order-opc&submitAddDiscount=1&discount_name=' . urlencode($rule->code));
                            }

                            Tools::redirect('index.php?controller=order&submitAddDiscount=1&discount_name=' . urlencode($rule->code));
                        } else {
                            $rule_name = isset($rule->name[(int) $this->context->cart->id_lang]) ? $rule->name[(int) $this->context->cart->id_lang] : $rule->code;
                            $error = sprintf(Tools::displayError('CartRule ID %1s (%2s) used in this cart is not valid and has been withdrawn from cart'), (int) $rule->id, $rule_name);
                            PrestaShopLogger::addLog($error, 3, '0000002', 'Cart', (int) $this->context->cart->id);
                        }

                    }

                }

            }

            foreach ($package_list as $id_address => $packageByAddress) {

                foreach ($packageByAddress as $id_package => $package) {
                    /** @var Order $order */
                    $piece = new CustomerPieces();

                    if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                        $address = new Address((int) $id_address);
                        $this->context->country = new Country((int) $address->id_country, (int) $this->context->cart->id_lang);

                        if (!$this->context->country->active) {
                            throw new PhenyxShopException('The delivery address country is not active.');
                        }

                    }

                    $carrier = null;

                    if (!$this->context->cart->isVirtualCart() && isset($package['id_carrier'])) {
                        $carrier = new Carrier((int) $package['id_carrier'], (int) $this->context->cart->id_lang);
                        $piece->id_carrier = (int) $carrier->id;
                        $id_carrier = (int) $carrier->id;
                    } else {
                        $piece->id_carrier = 0;
                        $id_carrier = 0;
                    }

                    $piece = new CustomerPieces();
                    $pieceNumber = $piece->generateCartNumber($this->context->cart);
                    $piece->id_piece_origine = 0;
                    $piece->piece_type = 'ORDER';
                    $piece->id_cart = $this->context->cart->id;
                    $piece->id_shop_group = $this->context->cart->id_shop_group;
                    $piece->id_shop = $this->context->cart->id_shop;
                    $piece->id_lang = $this->context->cart->id_lang;
                    $piece->id_currency = $this->context->cart->id_currency;
                    $piece->id_customer = $this->context->cart->id_customer;
                    $piece->current_state = $id_order_state;
                    $piece->payment = $payment_method;
                    $piece->base_tax_excl = (float) $this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $package['product_list'], $id_carrier) + (float) abs($this->context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $package['product_list'], $id_carrier));
                    $piece->total_products = (float) $this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $package['product_list'], $id_carrier);
                    $piece->total_products_tax_excl = (float) $this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $package['product_list'], $id_carrier);
                    $piece->total_products_tax_incl = (float) $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $package['product_list'], $id_carrier);
                    $piece->total_shipping_tax_excl = (float) $this->context->cart->getPackageShippingCost((int) $id_carrier, false, null, $package['product_list']);
                    $piece->shipping_tax_subject = $piece->total_shipping_tax_excl;
                    $piece->total_with_freight_tax_excl = $piece->total_products + $piece->total_shipping_tax_excl;
                    $piece->total_shipping_tax_incl = (float) $this->context->cart->getPackageShippingCost((int) $id_carrier, true, null, $package['product_list']);
                    $piece->total_discounts_tax_excl = (float) abs($this->context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $package['product_list'], $id_carrier));
                    $piece->total_discounts_tax_incl = (float) abs($this->context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $package['product_list'], $id_carrier));
                    $piece->total_wrapping_tax_excl = (float) abs($this->context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $package['product_list'], $id_carrier));
                    $piece->total_wrapping_tax_incl = (float) abs($this->context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $package['product_list'], $id_carrier));
                    $piece->total_tax_excl = $piece->total_products + $piece->total_shipping_tax_excl + $piece->total_wrapping_tax_excl;
                    $piece->total_tax_incl = (float) $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $package['product_list'], $id_carrier) + $piece->total_shipping_tax_incl + $piece->total_wrapping_tax_incl;
                    $piece->total_tax = $piece->total_tax_incl - $piece->total_tax_excl;

                    if (isset($this->name)) {
                        $piece->id_payment_mode = CustomerPieces::getPaymentModeByModule($this->name);
                    }

                    $piece->module = $this->name;

                    $piece->total_paid = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH, $package['product_list'], $id_carrier), _PS_PRICE_COMPUTE_PRECISION_);
                    $piece->id_carrier = $id_carrier;
                    $piece->id_address_delivery = (int) $id_address;
                    $piece->id_address_invoice = (int) $this->context->cart->id_address_invoice;
                    $piece->conversion_rate = $this->context->currency->conversion_rate;
                    $piece->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
                    $piece->round_type = Configuration::get('PS_ROUND_TYPE');
                    $piece->piece_number = $pieceNumber;
                    $piece->validate = 0;

                    $result = $piece->add();

                    if (!$result) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', (int) $id_cart, true);
                        throw new PhenyxShopException('Can\'t save Order');
                    }

                    $piece->generateReglement();

                    $order_list[] = $package['product_list'];

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderDetail is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }

                    // Insert new Order detail list using cart for the current order
                    $order_detail = new CustomerPieceDetail(null, null, $this->context);
                    $order_detail->createList($piece, $this->context->cart, $package['product_list'], 0, true, $package_list[$id_address][$id_package]['id_warehouse']);
                    $order_detail_list[] = $order_detail;

                    if (!is_null($carrier)) {
                        $orderCarrier = new OrderCarrier();
                        $orderCarrier->id_order = (int) $piece->id;
                        $orderCarrier->id_carrier = (int) $id_carrier;
                        $orderCarrier->weight = (float) $piece->getTotalWeight();
                        $orderCarrier->shipping_cost_tax_excl = (float) $piece->total_shipping_tax_excl;
                        $orderCarrier->shipping_cost_tax_incl = (float) $piece->total_shipping_tax_incl;
                        $orderCarrier->add();
                    }

                }

            }

            // Register Payment only if the order status validate the order

            // Next !
            $only_one_gift = false;
            $cart_rule_used = [];
            $products = $this->context->cart->getProducts();

            // Make sure CartRule caches are empty
            CartRule::cleanCache();

            foreach ($order_detail_list as $key => $order_detail) {
                /** @var OrderDetail $order_detail */

                $order = $order_list[$key];

                if (isset($piece->id)) {

                    // Optional message to attach to this order

                    if (isset($message) & !empty($message)) {
                        $msg = new Message();
                        $message = strip_tags($message, '<br>');

                        if (Validate::isCleanHtml($message)) {

                            if (self::DEBUG_MODE) {
                                PrestaShopLogger::addLog('PaymentModule::validateOrder - Message is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                            }

                            $msg->message = $message;
                            $msg->id_cart = (int) $id_cart;
                            $msg->id_customer = (int) ($piece->id_customer);
                            $msg->id_order = (int) $piece->id;
                            $msg->private = 1;
                            $msg->add();
                        }

                    }

                    $products_list = '';
                    $virtual_product = true;

                    $product_var_tpl_list = [];

                    foreach ($package['product_list'] as $product) {
                        $price = Product::getPriceStatic((int) $product['id_product'], false, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 6, null, false, true, $product['cart_quantity'], false, (int) $piece->id_customer, (int) $piece->id_cart, (int) $piece->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                        $price_wt = Product::getPriceStatic((int) $product['id_product'], true, ($product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null), 2, null, false, true, $product['cart_quantity'], false, (int) $piece->id_customer, (int) $piece->id_cart, (int) $piece->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

                        $product_price = Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt;

                        $product_var_tpl = [
                            'reference'     => $product['reference'],
                            'name'          => $product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''),
                            'unit_price'    => Tools::displayPrice($product_price, $this->context->currency, false),
                            'price'         => Tools::displayPrice($product_price * $product['quantity'], $this->context->currency, false),
                            'quantity'      => $product['quantity'],
                            'customization' => [],
                        ];

                        $customized_datas = Product::getAllCustomizedDatas((int) $piece->id_cart);

                        if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']])) {
                            $product_var_tpl['customization'] = [];

                            foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']][$piece->id_address_delivery] as $customization) {
                                $customization_text = '';

                                if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD])) {

                                    foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text) {
                                        $customization_text .= $text['name'] . ': ' . $text['value'] . '<br />';
                                    }

                                }

                                if (isset($customization['datas'][Product::CUSTOMIZE_FILE])) {
                                    $customization_text .= sprintf(Tools::displayError('%d image(s)'), count($customization['datas'][Product::CUSTOMIZE_FILE])) . '<br />';
                                }

                                $customization_quantity = (int) $product['customization_quantity'];

                                $product_var_tpl['customization'][] = [
                                    'customization_text'     => $customization_text,
                                    'customization_quantity' => $customization_quantity,
                                    'quantity'               => Tools::displayPrice($customization_quantity * $product_price, $this->context->currency, false),
                                ];
                            }

                        }

                        $product_var_tpl_list[] = $product_var_tpl;
                        // Check if is not a virutal product for the displaying of shipping

                        if (!$product['is_virtual']) {
                            $virtual_product &= false;
                        }

                    }

                    // end foreach ($products)

                    $product_list_txt = '';
                    $product_list_html = '';

                    if (count($product_var_tpl_list) > 0) {

                        $product_list_html = $this->getEmailTemplateContent('order_conf_product_list.tpl', $product_var_tpl_list);
                    }

                    $cart_rules_list = [];
                    $total_reduction_value_ti = 0;
                    $total_reduction_value_tex = 0;

                    foreach ($cart_rules as $cart_rule) {
                        $package = ['id_carrier' => $piece->id_carrier, 'id_address' => $piece->id_address_delivery, 'products' => $package['product_list']];
                        $values = [
                            'tax_incl' => $cart_rule['obj']->getContextualValue(true, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package),
                            'tax_excl' => $cart_rule['obj']->getContextualValue(false, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package),
                        ];

                        if (!$values['tax_excl']) {
                            continue;
                        }

                        if (count($order_list) == 1 && $values['tax_incl'] > ($piece->total_products_wt - $total_reduction_value_ti) && $cart_rule['obj']->partial_use == 1 && $cart_rule['obj']->reduction_amount > 0) {
                            // Create a new voucher from the original
                            $voucher = new CartRule((int) $cart_rule['obj']->id); // We need to instantiate the CartRule without lang parameter to allow saving it
                            unset($voucher->id);

                            // Set a new voucher code
                            $voucher->code = empty($voucher->code) ? substr(md5($piece->id . '-' . $piece->id_customer . '-' . $cart_rule['obj']->id), 0, 16) : $voucher->code . '-2';

                            if (preg_match('/\-([0-9]{1,2})\-([0-9]{1,2})$/', $voucher->code, $matches) && $matches[1] == $matches[2]) {
                                $voucher->code = preg_replace('/' . $matches[0] . '$/', '-' . (intval($matches[1]) + 1), $voucher->code);
                            }

                            // Set the new voucher value

                            if ($voucher->reduction_tax) {
                                $voucher->reduction_amount = ($total_reduction_value_ti + $values['tax_incl']) - $piece->total_products_wt;

                                // Add total shipping amout only if reduction amount > total shipping

                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $piece->total_shipping_tax_incl) {
                                    $voucher->reduction_amount -= $piece->total_shipping_tax_incl;
                                }

                            } else {
                                $voucher->reduction_amount = ($total_reduction_value_tex + $values['tax_excl']) - $piece->total_products;

                                // Add total shipping amout only if reduction amount > total shipping

                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $piece->total_shipping_tax_excl) {
                                    $voucher->reduction_amount -= $piece->total_shipping_tax_excl;
                                }

                            }

                            if ($voucher->reduction_amount <= 0) {
                                continue;
                            }

                            if ($this->context->customer->isGuest()) {
                                $voucher->id_customer = 0;
                            } else {
                                $voucher->id_customer = $piece->id_customer;
                            }

                            $voucher->quantity = 1;
                            $voucher->reduction_currency = $piece->id_currency;
                            $voucher->quantity_per_user = 1;
                            $voucher->free_shipping = 0;

                            if ($voucher->add()) {
                                // If the voucher has conditions, they are now copied to the new voucher
                                CartRule::copyConditions($cart_rule['obj']->id, $voucher->id);
                                $tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/voucher.tpl');
                                $tpl->assign([
                                    'voucher_amount' => Tools::displayPrice($voucher->reduction_amount, $this->context->currency, false),
                                    'voucher_num'    => $voucher->code,
                                    'firstname'      => $this->context->customer->firstname,
                                    'lastname'       => $this->context->customer->lastname,
                                    'id_order'       => $piece->id,
                                    'order_name'     => $piece->prefix . $piece->piece_number,
                                ]);
                                $postfields = [
                                    'sender'      => [
                                        'name'  => "Sevice Commerciale " . Configuration::get('PS_SHOP_NAME'),
                                        'email' => 'no-reply@' . Configuration::get('PS_SHOP_URL'),
                                    ],
                                    'to'          => [
                                        [
                                            'name'  => $customer->firstname . ' ' . $customer->lastname,
                                            'email' => $customer->email,
                                        ],
                                    ],

                                    'subject'     => $this->l('New voucher for your order %s', $piece->prefix . $piece->piece_number),
                                    "htmlContent" => $tpl->fetch(),
                                ];

                                $result = Tools::sendEmail($postfields);

                            }

                            $values['tax_incl'] = $piece->total_products_wt - $total_reduction_value_ti;
                            $values['tax_excl'] = $piece->total_products - $total_reduction_value_tex;
                        }

                        $total_reduction_value_ti += $values['tax_incl'];
                        $total_reduction_value_tex += $values['tax_excl'];

                        $piece->addCartRule($cart_rule['obj']->id, $cart_rule['obj']->name, $values, 0, $cart_rule['obj']->free_shipping);

                        if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && !in_array($cart_rule['obj']->id, $cart_rule_used)) {
                            $cart_rule_used[] = $cart_rule['obj']->id;

                            // Create a new instance of Cart Rule without id_lang, in order to update its quantity
                            $cart_rule_to_update = new CartRule((int) $cart_rule['obj']->id);
                            $cart_rule_to_update->quantity = max(0, $cart_rule_to_update->quantity - 1);
                            $cart_rule_to_update->update();
                        }

                        $cart_rules_list[] = [
                            'voucher_name'      => $cart_rule['obj']->name,
                            'voucher_reduction' => ($values['tax_incl'] != 0.00 ? '-' : '') . Tools::displayPrice($values['tax_incl'], $this->context->currency, false),
                        ];
                    }

                    $cart_rules_list_txt = '';
                    $cart_rules_list_html = '';

                    if (count($cart_rules_list) > 0) {

                        $cart_rules_list_html = $this->getEmailTemplateContent('order_conf_cart_rules.tpl', $cart_rules_list);
                    }

                    // Specify order id for message
                    $old_message = Message::getMessageByCartId((int) $this->context->cart->id);

                    if ($old_message && !$old_message['private']) {
                        $update_message = new Message((int) $old_message['id_message']);
                        $update_message->id_order = (int) $piece->id;
                        $update_message->update();

                        // Add this message in the customer thread
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int) $piece->id_customer;
                        $customer_thread->id_shop = (int) $this->context->shop->id;
                        $customer_thread->id_order = (int) $piece->id;
                        $customer_thread->id_lang = (int) $this->context->language->id;
                        $customer_thread->email = $this->context->customer->email;
                        $customer_thread->status = 'open';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();

                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $update_message->message;
                        $customer_message->private = 0;

                        if (!$customer_message->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while saving message');
                        }

                    }

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Hook validateOrder is about to be called', 1, null, 'Cart', (int) $id_cart, true);
                    }

                    // Hook validate order
                    Hook::exec('actionValidateOrder', [
                        'cart'        => $this->context->cart,
                        'order'       => $piece,
                        'customer'    => $this->context->customer,
                        'currency'    => $this->context->currency,
                        'orderStatus' => $order_status,
                    ]);

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status is about to be added', 1, null, 'Cart', (int) $id_cart, true);
                    }

                    $newHistory = new CustomerPiecesHistory();
                    $newHistory->id_customer_piece = (int) $piece->id;
                    $newHistory->changeIdOrderState((int) $id_order_state, $piece, true);
                    $newHistory->add();

                    // Switch to back order if needed

                    if (Configuration::get('PS_STOCK_MANAGEMENT') && ($orderDetail->getStockState() || $orderDetail->product_quantity_in_stock <= 0)) {
                        $history = new CustomerPiecesHistory();
                        $history->id_customer_piece = (int) $piece->id;
                        $history->changeIdOrderState(Configuration::get($piece->validate ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'), $piece, true);
                        $history->add();
                    }

                    unset($order_detail);

                    $piece = new CustomerPieces((int) $piece->id);

                    $invoice = new Address((int) $piece->id_address_invoice);
                    $delivery = new Address((int) $piece->id_address_delivery);
                    $delivery_state = $delivery->id_state ? new State((int) $delivery->id_state) : false;
                    $invoice_state = $invoice->id_state ? new State((int) $invoice->id_state) : false;

                    $data = [
                        'firstname'            => $this->context->customer->firstname,
                        'lastname'             => $this->context->customer->lastname,
                        'email'                => $this->context->customer->email,
                        'delivery_block_txt'   => $this->_getFormatedAddress($delivery, "\n"),
                        'invoice_block_txt'    => $this->_getFormatedAddress($invoice, "\n"),
                        'delivery_block_html'  => $this->_getFormatedAddress($delivery, '<br />', [
                            'firstname' => '<span style="font-weight:bold;">%s</span>',
                            'lastname'  => '<span style="font-weight:bold;">%s</span>',
                        ]),
                        'invoice_block_html'   => $this->_getFormatedAddress($invoice, '<br />', [
                            'firstname' => '<span style="font-weight:bold;">%s</span>',
                            'lastname'  => '<span style="font-weight:bold;">%s</span>',
                        ]),
                        'delivery_company'     => $delivery->company,
                        'delivery_firstname'   => $delivery->firstname,
                        'delivery_lastname'    => $delivery->lastname,
                        'delivery_address1'    => $delivery->address1,
                        'delivery_address2'    => $delivery->address2,
                        'delivery_city'        => $delivery->city,
                        'delivery_postal_code' => $delivery->postcode,
                        'delivery_country'     => $delivery->country,
                        'delivery_state'       => $delivery->id_state ? $delivery_state->name : '',
                        'delivery_phone'       => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
                        'delivery_other'       => $delivery->other,
                        'invoice_company'      => $invoice->company,
                        'invoice_vat_number'   => $invoice->vat_number,
                        'invoice_firstname'    => $invoice->firstname,
                        'invoice_lastname'     => $invoice->lastname,
                        'invoice_address2'     => $invoice->address2,
                        'invoice_address1'     => $invoice->address1,
                        'invoice_city'         => $invoice->city,
                        'invoice_postal_code'  => $invoice->postcode,
                        'invoice_country'      => $invoice->country,
                        'invoice_state'        => $invoice->id_state ? $invoice_state->name : '',
                        'invoice_phone'        => ($invoice->phone) ? $invoice->phone : $invoice->phone_mobile,
                        'invoice_other'        => $invoice->other,
                        'order_name'           => $piece->prefix . $piece->piece_number,
                        'date'                 => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
                        'carrier'              => ($virtual_product || !isset($carrier->name)) ? Tools::displayError('No carrier') : $carrier->name,
                        'payment'              => $payment_method,
                        'products'             => $product_list_html,
                        'products_txt'         => $product_list_txt,
                        'discounts'            => $cart_rules_list_html,
                        'discounts_txt'        => $cart_rules_list_txt,
                        'total_paid'           => Tools::displayPrice($piece->total_paid, $this->context->currency, false),
                        'total_products'       => Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $piece->total_products : $piece->total_products_wt, $this->context->currency, false),
                        'total_discounts'      => Tools::displayPrice($piece->total_discounts, $this->context->currency, false),
                        'total_shipping'       => Tools::displayPrice($piece->total_shipping, $this->context->currency, false),
                        'total_wrapping'       => Tools::displayPrice($piece->total_wrapping, $this->context->currency, false),
                        'total_tax_paid'       => Tools::displayPrice(($piece->total_products_wt - $piece->total_products) + ($piece->total_shipping_tax_incl - $piece->total_shipping_tax_excl), $this->context->currency, false)];

                    if (is_array($extra_vars)) {
                        $data = array_merge($data, $extra_vars);
                    }
                    
                    $fileName = $piece->printPdf();
                    $fileAttachement[] = [
                        'content' => chunk_split(base64_encode(file_get_contents(_PS_INVOICE_DIR_ . $fileName))),
                        'name'    => $fileName,
                    ];

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Mail is about to be sent', 1, null, 'Cart', (int) $id_cart, true);
                    }

                    $template = PaymentModule::getMailTemplate($id_order_state, $piece->id_lang);

                    if (Validate::isEmail($this->context->customer->email)) {
                        $tpl = Context::getContext()->smarty->createTemplate(_PS_MAIL_DIR_ . '/' . $template . '.tpl');

                        foreach ($data as $key => $value) {
                            $tpl->assign($key, $value);
                        }

                        $postfields = [
                            'sender'      => [
                                'name'  => "Service Commerciale " . Configuration::get('PS_SHOP_NAME'),
                                'email' => Configuration::get('PS_SHOP_EMAIL'),
                            ],
                            'to'          => [
                                [
                                    'name'  => $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                                    'email' => $this->context->customer->email,
                                ],
                            ],
                            'cc'          => [
                                [
                                    'name'  => "Sevice Commercial " . Configuration::get('PS_SHOP_NAME'),
                                    'email' => Configuration::get('PS_SHOP_EMAIL'),
                                ],
                            ],
                            'subject'     => 'Confirmation de commande',
                            "htmlContent" => $tpl->fetch(),
                            'attachment'  => $fileAttachement,
                        ];
                        Tools::sendEmail($postfields);

                    }

                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_list = $piece->getProducts();

                        foreach ($product_list as $product) {
                            // if the available quantities depends on the physical stock

                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                // synchronizes
                                StockAvailable::synchronize($product['product_id'], $piece->id_shop);
                            }

                        }

                    }

                } else {
                    $error = Tools::displayError('Order creation failed');
                    PrestaShopLogger::addLog($error, 4, '0000002', 'Cart', intval($piece->id_cart));
                    die($error);
                }

            }

            // End foreach $order_detail_list

            // Use the last order as currentOrder

            if (isset($piece) && $piece->id) {
                $this->currentOrder = (int) $piece->id;
            }

            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - End of validateOrder', 1, null, 'Cart', (int) $id_cart, true);
            }

            return true;
        } else {
            $error = Tools::displayError('Cart cannot be loaded or an order has already been placed using this cart');
            PrestaShopLogger::addLog($error, 4, '0000001', 'Cart', intval($this->context->cart->id));
            die($error);
        }

    }

    public static function getMailTemplate($id_order_state, $idLang) {

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`template`')
                ->from('customer_piece_state_lang')
                ->where('`id_customer_piece_state` = ' . (int) $id_order_state . ' AND `id_lang` = ' . $idLang)
        );

        if (!empty($result)) {
            return $result;
        }

        return 'order_conf';
    }

    public function formatProductAndVoucherForEmail($content) {

        Tools::displayAsDeprecated();
        return $content;
    }

    protected function _getTxtFormatedAddress($the_address) {

        $adr_fields = AddressFormat::getOrderedAddressFields($the_address->id_country, false, true);
        $r_values = [];

        foreach ($adr_fields as $fields_line) {
            $tmp_values = [];

            foreach (explode(' ', $fields_line) as $field_item) {
                $field_item = trim($field_item);
                $tmp_values[] = $the_address->{$field_item};
            }

            $r_values[] = implode(' ', $tmp_values);
        }

        $out = implode("\n", $r_values);
        return $out;
    }

    protected function _getFormatedAddress(Address $the_address, $line_sep, $fields_style = []) {

        return AddressFormat::generateAddress($the_address, ['avoid' => []], $line_sep, ' ', $fields_style);
    }

    public function getCurrency($current_id_currency = null) {

        if (!(int) $current_id_currency) {
            $current_id_currency = Context::getContext()->currency->id;
        }

        if (!$this->currencies) {
            return false;
        }

        if ($this->currencies_mode == 'checkbox') {
            $currencies = Currency::getPaymentCurrencies($this->id);
            return $currencies;
        } else

        if ($this->currencies_mode == 'radio') {
            $currencies = Currency::getPaymentCurrenciesSpecial($this->id);
            $currency = $currencies['id_currency'];

            if ($currency == -1) {
                $id_currency = (int) $current_id_currency;
            } else

            if ($currency == -2) {
                $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
            } else {
                $id_currency = $currency;
            }

        }

        if (!isset($id_currency) || empty($id_currency)) {
            return false;
        }

        $currency = new Currency((int) $id_currency);
        return $currency;
    }

    public static function addCurrencyPermissions($id_currency, array $id_module_list = []) {

        $values = '';

        if (count($id_module_list) == 0) {
            // fetch all installed module ids
            $modules = PaymentModuleCore::getInstalledPaymentModules();

            foreach ($modules as $module) {
                $id_module_list[] = $module['id_module'];
            }

        }

        foreach ($id_module_list as $id_module) {
            $values .= '(' . (int) $id_module . ',' . (int) $id_currency . '),';
        }

        if (!empty($values)) {
            return Db::getInstance()->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'module_currency` (`id_module`, `id_currency`)
            VALUES ' . rtrim($values, ','));
        }

        return true;
    }

    public static function getInstalledPaymentModules() {

        $hook_payment = 'Payment';

        if (Db::getInstance()->getValue('SELECT `id_hook` FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = \'displayPayment\'')) {
            $hook_payment = 'displayPayment';
        }

        return Db::getInstance()->executeS('
        SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
        FROM `' . _DB_PREFIX_ . 'module` m
        LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module`'
            . Shop::addSqlRestriction(false, 'hm') . '
        LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook`
        INNER JOIN `' . _DB_PREFIX_ . 'module_shop` ms ON (m.`id_module` = ms.`id_module` AND ms.id_shop=' . (int) Context::getContext()->shop->id . ')
        WHERE h.`name` = \'' . pSQL($hook_payment) . '\'');
    }

    public static function preCall($module_name) {

        if (!parent::preCall($module_name)) {
            return false;
        }

        if (($module_instance = Module::getInstanceByName($module_name))) {
            /** @var PaymentModule $module_instance */

            if (!$module_instance->currencies || ($module_instance->currencies && count(Currency::checkPaymentCurrencies($module_instance->id)))) {
                return true;
            }

        }

        return false;
    }

    protected function getEmailTemplateContent($template_name, $var) {

        $email_configuration = Configuration::get('PS_MAIL_TYPE');

        $theme_template_path = _PS_THEME_DIR_ . 'mails' . DIRECTORY_SEPARATOR . $template_name;
        $default_mail_template_path = _PS_MAIL_DIR_ . $template_name;

        if (Tools::file_exists_cache($theme_template_path)) {
            $default_mail_template_path = $theme_template_path;
        }

        if (Tools::file_exists_cache($default_mail_template_path)) {
            $this->context->smarty->assign('list', $var);
            return $this->context->smarty->fetch($default_mail_template_path);
        }

        return '';
    }

}
