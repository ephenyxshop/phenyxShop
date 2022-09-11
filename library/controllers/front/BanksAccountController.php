<?php

/**
 * Class BanksAccountControllerCore
 *
 * @since 1.8.1.0
 */
class BanksAccountControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'banks-account';
    /** @var string $authRedirection */
    public $authRedirection = 'banks-account';
    /** @var bool $ssl */
    public $ssl = true;
    /** @var Customer */
    protected $customer;
    // @codingStandardsIgnoreEnd
    public $display_column_left = false;
    public $display_column_right = false;
    /**
     * Initialize controller
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();
        $this->customer = $this->context->customer;
    }

    /**
     * Start forms process
     *
     * @return Customer
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        parent::postProcess();

        $_POST = array_map('stripslashes', $this->customer->getFields());

        return $this->customer;
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        $countries = Country::getCountries($this->context->language->id, true);
        $banks = Customer::getBankAccount($this->customer->id);
        /* Generate years, months and days */
        $this->context->smarty->assign(
            [
                'customer'  => $this->customer,
                'countries' => $countries,
                'banks'     => $banks,
                'errors'    => $this->errors,
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'banks.tpl');
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
        $this->addCSS(_THEME_CSS_DIR_ . 'bank.css');
        $this->addJS(_THEME_JS_DIR_ . 'banks.js');
        Media::addJsDef([
            'AjaxLinkBankAccount' => $this->context->link->getPageLink('bank_account', true),

        ]);
    }

    public function ajaxProcessHasIban() {

        $id_country = Tools::getValue('id_country');
        $iban = BankAccount::hasIban($id_country);

        if ($iban) {
            die(Tools::jsonEncode($iban));
        } else {
            die(false);
        }

    }

}
