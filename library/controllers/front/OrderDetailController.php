<?php

/**
 * Class OrderDetailControllerCore
 *
 * @since 1.8.1.0
 */
class OrderDetailControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'order-detail';
    /** @var bool $auth */
    public $auth = true;
    /** @var string $authRedirection */
    public $authRedirection = 'history';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order detail controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }

    /**
     * Start forms process
     *
     * @see   FrontController::postProcess()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        parent::postProcess();

        if (Tools::isSubmit('msgText') && Tools::isSubmit('id_customer_piece') && Tools::isSubmit('id_product')) {
            $idOrder = (int) Tools::getValue('id_customer_piece');
            $msgText = Tools::getValue('msgText');

            if (!$idOrder || !Validate::isUnsignedId($idOrder)) {
                $this->errors[] = Tools::displayError('The order is no longer valid.');
            } else if (empty($msgText)) {
                $this->errors[] = Tools::displayError('The message cannot be blank.');
            } else if (!Validate::isMessage($msgText)) {
                $this->errors[] = Tools::displayError('This message is invalid (HTML is not allowed).');
            }

            if (!count($this->errors)) {
                $order = new cUSTOMERpIECES($idOrder);

                if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                    //check if a thread already exist
                    $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($this->context->customer->email, $order->id);
                    $idProduct = (int) Tools::getValue('id_product');
                    $cm = new CustomerMessage();

                    if (!$idCustomerThread) {
                        $ct = new CustomerThread();
                        $ct->id_contact = 0;
                        $ct->id_customer = (int) $order->id_customer;
                        $ct->id_shop = (int) $this->context->company->id;

                        if ($idProduct && $order->orderContainProduct($idProduct)) {
                            $ct->id_product = $idProduct;
                        }

                        $ct->id_order = (int) $order->id;
                        $ct->id_lang = (int) $this->context->language->id;
                        $ct->email = $this->context->customer->email;
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    } else {
                        $ct = new CustomerThread((int) $idCustomerThread);
                        $ct->status = 'open';
                        $ct->update();
                    }

                    $cm->id_customer_thread = $ct->id;
                    $cm->message = $msgText;
                    $cm->ip_address = (int) ip2long($_SERVER['REMOTE_ADDR']);
                    $cm->add();

                    if (!Configuration::get('EPH_MAIL_EMAIL_MESSAGE')) {
                        $to = strval(Configuration::get('EPH_SHOP_EMAIL'));
                    } else {
                        $to = new Contact((int) Configuration::get('EPH_MAIL_EMAIL_MESSAGE'));
                        $to = strval($to->email);
                    }

                    $toName = strval(Configuration::get('EPH_SHOP_NAME'));
                    $customer = $this->context->customer;

                    $product = new Product($idProduct);
                    $productName = '';

                    if (Validate::isLoadedObject($product) && isset($product->name[(int) $this->context->language->id])) {
                        $productName = $product->name[(int) $this->context->language->id];
                    }

                    if (Validate::isLoadedObject($customer)) {
                        Mail::Send(
                            $this->context->language->id,
                            'order_customer_comment',
                            Mail::l('Message from a customer'),
                            [
                                '{lastname}'     => $customer->lastname,
                                '{firstname}'    => $customer->firstname,
                                '{email}'        => $customer->email,
                                '{id_order}'     => (int) $order->id,
                                '{order_name}'   => $order->getUniqReference(),
                                '{message}'      => Tools::nl2br($msgText),
                                '{product_name}' => $productName,
                            ],
                            $to,
                            $toName,
                            strval(Configuration::get('EPH_SHOP_EMAIL')),
                            $customer->firstname . ' ' . $customer->lastname,
                            null,
                            null,
                            _EPH_MAIL_DIR_,
                            false,
                            null,
                            null,
                            $customer->email
                        );
                    }

                    if (Tools::getValue('ajax') != 'true') {
                        Tools::redirect('index.php?controller=order-detail&id_order=' . (int) $idOrder);
                    }

                    $this->context->smarty->assign('message_confirmation', true);
                } else {
                    $this->errors[] = Tools::displayError('Order not found');
                }

            }

        }

    }

    /**
     * Handle ajax call
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function displayAjax() {

        $this->display();
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        if (!($idOrder = (int) Tools::getValue('id_customer_piece')) || !Validate::isUnsignedId($idOrder)) {
            $this->errors[] = Tools::displayError('Order ID required');
        } else {
            $order = new CustomerPieces($idOrder);

            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                $idOrderState = (int) $order->piece_type;
                $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
                $addressInvoice = new Address((int) $order->id_address_invoice);
                $addressDelivery = new Address((int) $order->id_address_delivery);

                $invAdrFields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
                $dlvAdrFields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

                $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $invAdrFields);
                $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlvAdrFields);

                if ($order->total_discounts > 0) {
                    $this->context->smarty->assign('total_old', (float) $order->total_paid - $order->total_discounts);
                }

                $products = $order->getProducts();

                /* DEPRECATED: customizedDatas @since 1.5 */
                $customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
                Product::addCustomizationPrice($products, $customizedDatas);

                $customer = new Customer($order->id_customer);
                $this->context->smarty->assign(
                    [
                        'shop_name'                     => strval(Configuration::get('EPH_SHOP_NAME')),
                        'order'                         => $order,
                        'currency'                      => new Currency($order->id_currency),
                        'order_state'                   => (int) $idOrderState,
                        'invoiceAllowed'                => (int) Configuration::get('EPH_INVOICE'),
                        'products'                      => $products,
                        'discounts'                     => $order->getCartRules(),
                        'carrier'                       => $carrier,
                        'address_invoice'               => $addressInvoice,
                        'invoiceState'                  => (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) ? new State($addressInvoice->id_state) : false,
                        'address_delivery'              => $addressDelivery,
                        'inv_adr_fields'                => $invAdrFields,
                        'dlv_adr_fields'                => $dlvAdrFields,
                        'invoiceAddressFormatedValues'  => $invoiceAddressFormatedValues,
                        'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
                        'deliveryState'                 => (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) ? new State($addressDelivery->id_state) : false,
                        'is_guest'                      => false,
                        'CUSTOMIZE_FILE'                => Product::CUSTOMIZE_FILE,
                        'CUSTOMIZE_TEXTFIELD'           => Product::CUSTOMIZE_TEXTFIELD,
                        'isRecyclable'                  => Configuration::get('EPH_RECYCLABLE_PACK'),
                        'use_tax'                       => Configuration::get('EPH_TAX'),
                        'group_use_tax'                 => (Group::getPriceDisplayMethod($customer->id_default_group) == EPH_TAX_INC),
                        /* DEPRECATED: customizedDatas @since 1.5 */
                        'customizedDatas'               => $customizedDatas,
                        /* DEPRECATED: customizedDatas @since 1.5 */
                        'reorderingAllowed'             => !(bool) Configuration::get('EPH_DISALLOW_HISTORY_REORDERING'),
                    ]
                );

                if ($carrier->url && $order->shipping_number) {
                    $this->context->smarty->assign('followup', str_replace('@', $order->shipping_number, $carrier->url));
                }

                $this->context->smarty->assign('HOOK_ORDERDETAILDISPLAYED', Hook::exec('displayOrderDetail', ['order' => $order]));
                Hook::exec('actionOrderDetail', ['carrier' => $carrier, 'order' => $order]);

                unset($carrier, $addressInvoice, $addressDelivery);
            } else {
                $this->errors[] = Tools::displayError('This order cannot be found.');
            }

            unset($order);
        }

        $this->setTemplate(_EPH_THEME_DIR_ . 'order-detail.tpl');
    }

    public function ajaxProcessPrintInvoice() {

        $idOrder = (int) Tools::getValue('id_customer_piece');

        $order = new CustomerPieces($idOrder);

        $file = $order->printPdf();

        $response = [
            'fileExport' => 'invoices' . DIRECTORY_SEPARATOR . $file,
        ];
        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessShowOrder() {

        $tpl = $this->context->smarty->createTemplate(_EPH_THEME_DIR_ . 'order-detail.tpl');
        $idOrder = (int) Tools::getValue('id_customer_piece');

        $order = new CustomerPieces($idOrder);

        if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
            $idOrderState = (int) $order->piece_type;
            $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
            $addressInvoice = new Address((int) $order->id_address_invoice);
            $addressDelivery = new Address((int) $order->id_address_delivery);

            $invAdrFields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
            $dlvAdrFields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

            $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $invAdrFields);
            $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlvAdrFields);

            if ($order->total_discounts > 0) {
                $this->context->smarty->assign('total_old', (float) $order->total_paid - $order->total_discounts);
            }

            $products = $order->getProducts();

            /* DEPRECATED: customizedDatas @since 1.5 */
            $customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
            Product::addCustomizationPrice($products, $customizedDatas);

            $customer = new Customer($order->id_customer);
            $tpl->assign(
                [
                    'shop_name'                     => strval(Configuration::get('EPH_SHOP_NAME')),
                    'order'                         => $order,
                    'currency'                      => new Currency($order->id_currency),
                    'order_state'                   => (int) $idOrderState,
                    'invoiceAllowed'                => (int) Configuration::get('EPH_INVOICE'),
                    'products'                      => $products,
                    'discounts'                     => $order->getCartRules(),
                    'carrier'                       => $carrier,
                    'address_invoice'               => $addressInvoice,
                    'invoiceState'                  => (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) ? new State($addressInvoice->id_state) : false,
                    'address_delivery'              => $addressDelivery,
                    'inv_adr_fields'                => $invAdrFields,
                    'dlv_adr_fields'                => $dlvAdrFields,
                    'invoiceAddressFormatedValues'  => $invoiceAddressFormatedValues,
                    'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
                    'deliveryState'                 => (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) ? new State($addressDelivery->id_state) : false,
                    'is_guest'                      => false,
                    'CUSTOMIZE_FILE'                => Product::CUSTOMIZE_FILE,
                    'CUSTOMIZE_TEXTFIELD'           => Product::CUSTOMIZE_TEXTFIELD,
                    'isRecyclable'                  => Configuration::get('EPH_RECYCLABLE_PACK'),
                    'use_tax'                       => Configuration::get('EPH_TAX'),
                    'group_use_tax'                 => (Group::getPriceDisplayMethod($customer->id_default_group) == EPH_TAX_INC),
                    /* DEPRECATED: customizedDatas @since 1.5 */
                    'customizedDatas'               => $customizedDatas,
                    /* DEPRECATED: customizedDatas @since 1.5 */
                    'reorderingAllowed'             => !(bool) Configuration::get('EPH_DISALLOW_HISTORY_REORDERING'),
                ]
            );

            if ($carrier->url && $order->shipping_number) {
                $this->context->smarty->assign('followup', str_replace('@', $order->shipping_number, $carrier->url));
            }

            $this->context->smarty->assign('HOOK_ORDERDETAILDISPLAYED', Hook::exec('displayOrderDetail', ['order' => $order]));
            Hook::exec('actionOrderDetail', ['carrier' => $carrier, 'order' => $order]);
            unset($carrier, $addressInvoice, $addressDelivery);
        }

        $return = ['html' => $tpl->fetch()];

        die(Tools::jsonEncode($return));
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        if (Tools::getValue('ajax') != 'true') {
            parent::setMedia();
            $this->addCSS(_THEME_CSS_DIR_ . 'history.css');
            $this->addCSS(_THEME_CSS_DIR_ . 'addresses.css');
        }

    }

}
