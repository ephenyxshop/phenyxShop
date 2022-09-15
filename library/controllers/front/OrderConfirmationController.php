<?php

/**
 * Class OrderConfirmationControllerCore
 *
 * @since 1.8.1.0
 */
class OrderConfirmationControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $ssl */
    public $ssl = true;
    /** @var string $php_self */
    public $php_self = 'order-confirmation';
    /** @var int $id_cart */
    public $id_cart;
    /** @var int $id_module */
    public $id_module;
    /** @var int $id_order */
    public $id_order;
    /** @var string $reference */
    public $reference;
    /** @var string $secure_key */
    public $secure_key;
    
    public function init() {

        parent::init();

        $this->id_cart = (int) Tools::getValue('id_cart', 0);
        $isGuest = false;

        /* check if the cart has been made by a Guest customer, for redirect link */

        if (Cart::isGuestCartByCartId($this->id_cart)) {
            $isGuest = true;
            $redirectLink = 'index.php?controller=guest-tracking';
        } else {
            $redirectLink = 'index.php?controller=history';
        }

        $this->id_module = (int) (Tools::getValue('id_module', 0));
        $this->id_order = CustomerPieces::getOrderByCartId((int) ($this->id_cart));
        $this->secure_key = Tools::getValue('key', false);
        $order = new CustomerPieces((int) ($this->id_order));

        if ($isGuest) {
            $customer = new Customer((int) $order->id_customer);
            $redirectLink .= '&id_customer_piece=' . $order->piece_number . '&email=' . urlencode($customer->email);
        }

        if (!$this->id_order || !$this->id_module || !$this->secure_key || empty($this->secure_key)) {
            Tools::redirect($redirectLink . (Tools::isSubmit('slowvalidation') ? '&slowvalidation' : ''));
        }

        $this->reference = $order->piece_number;

        if (!Validate::isLoadedObject($order) || $order->id_customer != $this->context->customer->id) {
            Tools::redirect($redirectLink);
        }

        $module = Module::getInstanceById((int) ($this->id_module));

        if ($order->module != $module->name) {
            Tools::redirect($redirectLink);
        }

    }

    public function initContent() {

        parent::initContent();

       

        $idCart = (int) Tools::getValue('id_cart');
        $idOrder = CustomerPieces::getOrderByCartId($idCart);
        $order = new CustomerPieces($idOrder);
        $varProducts = [];

        if (Validate::isLoadedObject($order)) {
            $products = $order->getProducts();

            if ($products) {

                foreach ($products as $product) {
                    $varProducts[] = [
                        'id_product' => $product['id_product'],
                        'name'       => $product['product_name'],
                        'price'      => $product['unit_tax_incl'],
                        'quantity'   => $product['product_quantity'],
                    ];
                }

            }

        }

        Media::AddJsDef(
            [
                'bought_products'          => $varProducts,
                'total_products_tax_incl'  => $order->total_products_wt,
                'total_products_tax_excl'  => $order->total_products,
                'total_shipping_tax_incl'  => $order->total_shipping_tax_incl,
                'total_shipping_tax_excl'  => $order->total_shipping_tax_excl,
                'total_discounts_tax_incl' => $order->total_discounts_tax_incl,
                'total_discounts_tax_excl' => $order->total_discounts_tax_excl,
                'total_paid_tax_incl'      => $order->total_paid_tax_incl,
                'total_paid_tax_excl'      => $order->total_paid_tax_excl,
                'id_customer'              => $this->context->customer->id,
            ]
        );

        $this->context->smarty->assign(
            [
                'is_guest'                => $this->context->customer->is_guest,
                'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
                'HOOK_PAYMENT_RETURN'     => $this->displayPaymentReturn(),
            ]
        );

        if ($this->context->customer->is_guest) {
            $this->context->smarty->assign(
                [
                    'id_order'           => $this->id_order,
                    'reference_order'    => $this->reference,
                    'id_order_formatted' => sprintf('#%06d', $this->id_order),
                    'email'              => $this->context->customer->email,
                ]
            );
            /* If guest we clear the cookie for security reason */
            $this->context->customer->mylogout();
        }

        $this->setTemplate(_EPH_THEME_DIR_ . 'order-confirmation.tpl');
    }

    /**
     * Execute the hook displayOrderConfirmation
     *
     * @return string|array|false
     *
     * @since 1.8.1.0
     */
    public function displayOrderConfirmation() {

        if (Validate::isUnsignedId($this->id_order)) {
            $params = [];
            $order = new CustomerPieces($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayOrderConfirmation', $params);
            }

        }

        return false;
    }

    /**
     * Execute the hook displayPaymentReturn
     *
     * @return string|array|false
     *
     * @since 1.8.1.0
     */
    public function displayPaymentReturn() {

        if (Validate::isUnsignedId($this->id_order) && Validate::isUnsignedId($this->id_module)) {
            $params = [];
            $order = new CustomerPieces($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayPaymentReturn', $params, $this->id_module);
            }

        }

        return false;
    }

}
