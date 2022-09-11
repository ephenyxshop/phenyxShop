<?php

/**
 * Class HistoryControllerCore
 *
 * @since 1.8.1.0
 */
class HistoryControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'history';
    /** @var string $authRedirection */
    public $authRedirection = 'history';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();
        $this->addCSS(
            [
                _THEME_CSS_DIR_ . 'history.css',
                _THEME_CSS_DIR_ . 'addresses.css',
            ]
        );
        $this->addJS(
            [
                _THEME_JS_DIR_ . 'history.js',
                _THEME_JS_DIR_ . 'tools.js', // retro compat themes 1.5
            ]
        );
        $this->addJqueryPlugin(['scrollTo', 'footable', 'footable-sort']);

        Media::addJsDef([
            'AjaxLinkOrderDetail' => $this->context->link->getPageLink('order-detail', true),

        ]);
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

        if ($orders = CustomerPieces::getCustomerOrders($this->context->customer->id)) {

            foreach ($orders as &$order) {
				foreach($order['orders'] as $key => $value) {
					$myOrder = new CustomerPieces((int) $value['id_customer_piece']);
					if (Validate::isLoadedObject($myOrder)) {
                    	$order['virtual'] = $myOrder->isVirtual(false);
                	}
				}
               

            }

        }

        $this->context->smarty->assign(
            [
                'orders'            => $orders,
                'invoiceAllowed'    => (int) Configuration::get('EPH_INVOICE'),
                'reorderingAllowed' => !(bool) Configuration::get('EPH_DISALLOW_HISTORY_REORDERING'),
                'slowValidation'    => Tools::isSubmit('slowvalidation'),
                'link'              => $this->context->link,
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'history.tpl');
    }

}
