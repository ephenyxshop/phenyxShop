<?php

/**
 * Class ModuleFrontControllerCore
 *
 * @since 1.9.1.0
 */
class ModuleFrontControllerCore extends FrontController {

    /** @var Module $module */
    public $module;

    /**
     * ModuleFrontControllerCore constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        $this->module = Module::getInstanceByName(Tools::getValue('module'));

        if (!$this->module->active) {
            Tools::redirect('index');
        }

        $this->page_name = 'module-' . $this->module->name . '-' . Performer::getInstance()->getController();

        parent::__construct();

        $this->controller_type = 'modulefront';

        $inBase = isset($this->page_name) && is_object($this->context->theme) && $this->context->theme->hasColumnsSettings($this->page_name);

        $tmp = isset($this->display_column_left) ? (bool) $this->display_column_left : true;
        $this->display_column_left = $inBase ? $this->context->theme->hasLeftColumn($this->page_name) : $tmp;

        $tmp = isset($this->display_column_right) ? (bool) $this->display_column_right : true;
        $this->display_column_right = $inBase ? $this->context->theme->hasRightColumn($this->page_name) : $tmp;
    }

    /**
     * Assigns module template for page content
     *
     * @param string $template Template filename
     *
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTemplate($template) {

        if (!$path = $this->getTemplatePath($template)) {
            throw new PhenyxShopException("Template '$template' not found");
        }

        $this->template = $path;
    }

    /**
     * Finds and returns module front template that take the highest precedence
     *
     * @param string $template Template filename
     *
     * @return string|false
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTemplatePath($template) {

        if (file_exists(_EPH_THEME_DIR_ . 'modules/' . $this->module->name . '/' . $template)) {
            return _EPH_THEME_DIR_ . 'modules/' . $this->module->name . '/' . $template;
        } else if (file_exists(_EPH_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/front/' . $template)) {
            return _EPH_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/front/' . $template;
        } else if (file_exists(_EPH_MODULE_DIR_ . $this->module->name . '/views/templates/front/' . $template)) {
            return _EPH_MODULE_DIR_ . $this->module->name . '/views/templates/front/' . $template;
        }

        return false;
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function initContent() {

        if (Tools::isSubmit('module') && Tools::getValue('controller') == 'payment') {
            $currency = Currency::getCurrency((int) $this->context->cart->id_currency);
            $minimalPurchase = Tools::convertPrice((float) Configuration::get('EPH_PURCHASE_MINIMUM'), $currency);

            if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimalPurchase) {
                Tools::redirect('index.php?controller=order&step=1');
            }

        }

        parent::initContent();
    }

}
