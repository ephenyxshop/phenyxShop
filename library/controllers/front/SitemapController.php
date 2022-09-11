<?php

/**
 * Class SitemapControllerCore
 *
 * @since 1.8.1.0
 */
class SitemapControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'sitemap';
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
        $this->addCSS(_THEME_CSS_DIR_ . 'sitemap.css');
        $this->addJS(_THEME_JS_DIR_ . 'tools/treeManagement.js');
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

        $this->context->smarty->assign('categoriesTree', Category::getRootCategory()->recurseLiteCategTree(0));
        $this->context->smarty->assign('categoriescmsTree', CMSCategory::getRecurseCategory($this->context->language->id, 1, 1, 1));
        $this->context->smarty->assign('voucherAllowed', (int) CartRule::isFeatureActive());

        if (Module::isInstalled('blockmanufacturer') && Module::isEnabled('blockmanufacturer')) {
            $blockmanufacturer = Module::getInstanceByName('blockmanufacturer');
            $this->context->smarty->assign('display_manufacturer_link', isset($blockmanufacturer->active) ? (bool) $blockmanufacturer->active : false);
        } else {
            $this->context->smarty->assign('display_manufacturer_link', 0);
        }

        if (Module::isInstalled('blocksupplier') && Module::isEnabled('blocksupplier')) {
            $blocksupplier = Module::getInstanceByName('blocksupplier');
            $this->context->smarty->assign('display_supplier_link', isset($blocksupplier->active) ? (bool) $blocksupplier->active : false);
        } else {
            $this->context->smarty->assign('display_supplier_link', 0);
        }

        $this->context->smarty->assign('PS_DISPLAY_SUPPLIERS', Configuration::get('PS_DISPLAY_SUPPLIERS'));
        $this->context->smarty->assign('PS_DISPLAY_BEST_SELLERS', Configuration::get('PS_DISPLAY_BEST_SELLERS'));
        $this->context->smarty->assign('display_store', Configuration::get('PS_STORES_DISPLAY_SITEMAP'));

        $this->setTemplate(_PS_THEME_DIR_ . 'sitemap.tpl');
    }

}
