<?php

/**
 * Class OrderReturnControllerCore
 *
 * @since 1.8.1.0
 */
class OrderReturnControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'order-return';
    /** @var string $authRedirection */
    public $authRedirection = 'order-follow';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize order return controller
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

        $idOrderReturn = (int) Tools::getValue('id_order_return');

        if (!isset($idOrderReturn) || !Validate::isUnsignedId($idOrderReturn)) {
            $this->errors[] = Tools::displayError('Order ID required');
        } else {
            $orderReturn = new OrderReturn((int) $idOrderReturn);

            if (Validate::isLoadedObject($orderReturn) && $orderReturn->id_customer == $this->context->cookie->id_customer) {
                $order = new CustomerPieces((int) ($orderReturn->id_order));

                if (Validate::isLoadedObject($order)) {
                    $state = new OrderReturnState((int) $orderReturn->state);
                    $this->context->smarty->assign(
                        [
                            'orderRet'               => $orderReturn,
                            'order'                  => $order,
                            'state_name'             => $state->name[(int) $this->context->language->id],
                            'return_allowed'         => false,
                            'products'               => OrderReturn::getOrdersReturnProducts((int) $orderReturn->id, $order),
                            'returnedCustomizations' => OrderReturn::getReturnedCustomizedProducts((int) $orderReturn->id_order),
                            'customizedDatas'        => Product::getAllCustomizedDatas((int) $order->id_cart),
                        ]
                    );
                } else {
                    $this->errors[] = Tools::displayError('Cannot find the order return.');
                }

            } else {
                $this->errors[] = Tools::displayError('Cannot find the order return.');
            }

        }

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

        $this->context->smarty->assign(
            [
                'errors'       => $this->errors,
                'nbdaysreturn' => (int) Configuration::get('EPH_ORDER_RETURN_NB_DAYS'),
            ]
        );
        $this->setTemplate(_EPH_THEME_DIR_ . 'order-return.tpl');
    }

    /**
     * Process ajax call
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function displayAjax() {

        $this->smartyOutputContent($this->template);
    }

}
