<?php

/**
 * Class GuestTrackingControllerCore
 *
 * @since 1.8.1.0
 */
class GuestTrackingControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $ssl */
    public $ssl = true;
    /** @var string $php_self */
    public $php_self = 'guest-tracking';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();

        if ($this->context->customer->isLogged()) {
            Tools::redirect('history.php');
        }

    }

    /**
     * Start forms process
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        if (Tools::isSubmit('submitGuestTracking') || Tools::isSubmit('submitTransformGuestToCustomer')) {
            // These lines are here for retrocompatibility with old theme
            $idOrder = Tools::getValue('id_order');
            $orderCollection = [];

            if ($idOrder) {

                if (is_numeric($idOrder)) {
                    $order = new CustomerPieces((int) $idOrder);

                    if (Validate::isLoadedObject($order)) {
                        $orderCollection = CustomerPieces::getByReference($order->piece_number);
                    }

                } else {
                    $orderCollection = CustomerPieces::getByReference($idOrder);
                }

            }

            // Get order reference, ignore package reference (after the #, on the order reference)
            $orderReference = current(explode('#', Tools::getValue('order_reference')));
            // Ignore $result_number

            if (!empty($orderReference)) {
                $orderCollection = CustomerPieces::getByReference($orderReference);
            }

            $email = Tools::getValue('email');

            if (empty($orderReference) && empty($idOrder)) {
                $this->errors[] = Tools::displayError('Please provide your order\'s reference number.');
            } else if (empty($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } else if (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } else if (!Customer::customerExists($email, false, false)) {
                $this->errors[] = Tools::displayError('There is no account associated with this email address.');
            } else if (Customer::customerExists($email, false, true)) {
                $this->errors[] = Tools::displayError('This page is for guest accounts only. Since your guest account has already been transformed into a customer account, you can no longer view your order here. Please log in to your customer account to view this order');
                $this->context->smarty->assign('show_login_link', true);
            } else if (empty($orderCollection->getResults())) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } else if (!$orderCollection->getFirst()->isAssociatedAtGuest($email)) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } else {
                $this->assignOrderTracking($orderCollection);

                if (isset($order) && Tools::isSubmit('submitTransformGuestToCustomer')) {
                    $customer = new Customer((int) $order->id_customer);

                    if (!Validate::isLoadedObject($customer)) {
                        $this->errors[] = Tools::displayError('Invalid customer');
                    } else if (!Tools::getValue('password')) {
                        $this->errors[] = Tools::displayError('Invalid password.');
                    } else if (!$customer->transformToCustomer($this->context->language->id, Tools::getValue('password'))) {
                        $this->errors[] = Tools::displayError('An error occurred while transforming a guest into a registered customer.');
                    } else {
                        $this->context->smarty->assign('transformSuccess', true);
                    }

                }

            }

        }

    }

    /**
     * Assigns template vars related to order tracking information
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     */
    protected function assignOrderTracking($orderCollection) {

        $customer = new Customer((int) $orderCollection->getFirst()->id_customer);

        $orderCollection = ($orderCollection->getAll());

        $orderList = [];

        foreach ($orderCollection as $order) {
            $orderList[] = $order;
        }

        foreach ($orderList as &$order) {
            /** @var Order $order */
            $order->id_order_state = (int) $order->getCurrentState();
            $order->invoice = (OrderState::invoiceAvailable((int) $order->id_order_state) && $order->invoice_number);
            $order->order_history = $order->getHistory((int) $this->context->language->id, false, true);
            $order->carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
            $order->address_invoice = new Address((int) $order->id_address_invoice);
            $order->address_delivery = new Address((int) $order->id_address_delivery);
            $order->inv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_invoice->id_country);
            $order->dlv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_delivery->id_country);
            $order->invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_invoice, $order->inv_adr_fields);
            $order->deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_delivery, $order->dlv_adr_fields);
            $order->currency = new Currency($order->id_currency);
            $order->discounts = $order->getCartRules();
            $order->invoiceState = (Validate::isLoadedObject($order->address_invoice) && $order->address_invoice->id_state) ? new State((int) $order->address_invoice->id_state) : false;
            $order->deliveryState = (Validate::isLoadedObject($order->address_delivery) && $order->address_delivery->id_state) ? new State((int) $order->address_delivery->id_state) : false;
            $order->products = $order->getProducts();
            $order->customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
            Product::addCustomizationPrice($order->products, $order->customizedDatas);
            $order->total_old = $order->total_discounts > 0 ? (float) $order->total_paid - (float) $order->total_discounts : false;

            if ($order->carrier->url && $order->shipping_number) {
                $order->followup = str_replace('@', $order->shipping_number, $order->carrier->url);
            }

            $order->hook_orderdetaildisplayed = Hook::exec('displayOrderDetail', ['order' => $order]);

            Hook::exec('actionOrderDetail', ['carrier' => $order->carrier, 'order' => $order]);
        }

        $this->context->smarty->assign(
            [
                'shop_name'           => Configuration::get('EPH_SHOP_NAME'),
                'order_collection'    => $orderList,
                'return_allowed'      => false,
                'invoiceAllowed'      => (int) Configuration::get('EPH_INVOICE'),
                'is_guest'            => true,
                'group_use_tax'       => (Group::getPriceDisplayMethod($customer->id_default_group) == EPH_TAX_INC),
                'CUSTOMIZE_FILE'      => Product::CUSTOMIZE_FILE,
                'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                'use_tax'             => Configuration::get('EPH_TAX'),
            ]
        );
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        /* Handle brute force attacks */

        if (count($this->errors)) {
            sleep(1);
        }

        $this->context->smarty->assign(
            [
                'action' => $this->context->link->getPageLink('guest-tracking.php', true),
                'errors' => $this->errors,
            ]
        );
        $this->setTemplate(_EPH_THEME_DIR_ . 'guest-tracking.tpl');
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();

        $this->addCSS(_THEME_CSS_DIR_ . 'history.css');
        $this->addCSS(_THEME_CSS_DIR_ . 'addresses.css');
    }

    protected function processAddressFormat(Address $delivery, Address $invoice) {

        $invAdrFields = AddressFormat::getOrderedAddressFields($invoice->id_country, false, true);
        $dlvAdrFields = AddressFormat::getOrderedAddressFields($delivery->id_country, false, true);

        $this->context->smarty->assign([
            'inv_adr_fields' => $invAdrFields,
            'dlv_adr_fields' => $dlvAdrFields,
        ]);
    }

}
