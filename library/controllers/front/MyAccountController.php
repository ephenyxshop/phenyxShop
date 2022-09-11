<?php

/**
 * Class MyAccountControllerCore
 *
 * @since 1.8.1.0
 */
class MyAccountControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'my-account';
    /** @var string $authRedirection */
    public $authRedirection = 'my-account';
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
        $this->addCSS(_THEME_CSS_DIR_ . 'my-account.css');
    }

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        $hasAddress = $this->context->customer->getAddresses($this->context->language->id);
        $this->context->smarty->assign(
            [
                'has_customer_an_address' => empty($hasAddress),
                'voucherAllowed'          => (int) CartRule::isFeatureActive(),
                'returnAllowed'           => (int) Configuration::get('EPH_ORDER_RETURN'),
				'useSepa'				=> (int) Configuration::get('_EPHENYX_USE_SEPA_'),
            ]
        );
        $this->context->smarty->assign('HOOK_CUSTOMER_ACCOUNT', Hook::exec('displayCustomerAccount'));

        $this->setTemplate(_EPH_THEME_DIR_ . 'my-account.tpl');
    }
}
