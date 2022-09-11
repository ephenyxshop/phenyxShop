<?php

/**
 * Class CmsControllerCore
 *
 * @since 1.8.1.0
 */
class CmsControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'cms';
    public $assignCase;
    /** @var CMS $cms */
    public $cms;
    /** @var CMSCategory */
    public $cms_category;
    /** @var bool $ssl */
    public $ssl = false;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize CMS controller
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        if ($idCms = (int) Tools::getValue('id_cms')) {
            $this->cms = new CMS($idCms, $this->context->language->id, $this->context->shop->id);
        } else if ($idCmsCategory = (int) Tools::getValue('id_cms_category')) {
            $this->cms_category = new CMSCategory($idCmsCategory, $this->context->language->id, $this->context->shop->id);
        }

        if (Configuration::get('PS_SSL_ENABLED') && Tools::getValue('content_only') && $idCms && Validate::isLoadedObject($this->cms)
            && in_array($idCms, [(int) Configuration::get('PS_CONDITIONS_CMS_ID'), (int) Configuration::get('LEGAL_CMS_ID_REVOCATION')])
        ) {
            $this->ssl = true;
        }

        parent::init();

        $this->canonicalRedirection();

        // assignCase (1 = CMS page, 2 = CMS category)

        if (Validate::isLoadedObject($this->cms)) {
            $adtoken = Tools::getAdminToken('AdminCmsContent' . (int) Tab::getIdFromClassName('AdminCmsContent') . (int) Tools::getValue('id_employee'));

            if (!$this->cms->isAssociatedToShop() || !$this->cms->active && Tools::getValue('adtoken') != $adtoken) {
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
            } else {
                $this->assignCase = 1;
            }

        } else if (Validate::isLoadedObject($this->cms_category) && $this->cms_category->active) {
            $this->assignCase = 2;
        } else {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }

    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalUrl
     *
     * @since 1.8.1.0
     */
    public function canonicalRedirection($canonicalUrl = '') {

        if (Tools::getValue('live_edit')) {
            return;
        }

        if (Validate::isLoadedObject($this->cms) && ($canonicalUrl = $this->context->link->getCMSLink($this->cms, $this->cms->link_rewrite, $this->ssl))) {
            parent::canonicalRedirection($canonicalUrl);
        } else if (Validate::isLoadedObject($this->cms_category) && ($canonicalUrl = $this->context->link->getCMSCategoryLink($this->cms_category))) {
            parent::canonicalRedirection($canonicalUrl);
        }

    }

    public function setMedia() {

        parent::setMedia();

        if ($this->assignCase == 1) {
            $this->addJS(_THEME_JS_DIR_ . 'cms.js');
        }

        $this->addCSS(_THEME_CSS_DIR_ . 'cms.css');
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     */
    public function initContent() {

        parent::initContent();

        $parentCat = new CMSCategory(1, $this->context->language->id);
        $this->context->smarty->assign('id_current_lang', $this->context->language->id);
        $this->context->smarty->assign('home_title', $parentCat->name);
        $this->context->smarty->assign('cgv_id', Configuration::get('PS_CONDITIONS_CMS_ID'));

        if ($this->assignCase == 1) {

            if (isset($this->cms->id_cms_category) && $this->cms->id_cms_category) {
                $path = Tools::getFullPath($this->cms->id_cms_category, $this->cms->meta_title, 'CMS');
            } else if (isset($this->cms_category->meta_title)) {
                $path = Tools::getFullPath(1, $this->cms_category->meta_title, 'CMS');
            }

            $this->context->smarty->assign(
                [
                    'cms'          => $this->cms,
                    'content_only' => (int) Tools::getValue('content_only'),
                    'path'         => isset($path) ? $path : '',
                    'body_classes' => [$this->php_self . '-' . $this->cms->id, $this->php_self . '-' . $this->cms->link_rewrite],
                ]
            );

            if ($this->cms->indexation == 0) {
                $this->context->smarty->assign('nobots', true);
            }

        } else if ($this->assignCase == 2) {
            $this->context->smarty->assign(
                [
                    'category'     => $this->cms_category, //for backward compatibility
                    'cms_category' => $this->cms_category,
                    'sub_category' => $this->cms_category->getSubCategories($this->context->language->id),
                    'cms_pages'    => CMS::getCMSPages($this->context->language->id, (int) $this->cms_category->id, true, (int) $this->context->shop->id),
                    'path'         => ($this->cms_category->id !== 1) ? Tools::getPath($this->cms_category->id, $this->cms_category->name, false, 'CMS') : '',
                    'body_classes' => [$this->php_self . '-' . $this->cms_category->id, $this->php_self . '-' . $this->cms_category->link_rewrite],
                ]
            );
        }

        $this->setTemplate(_PS_THEME_DIR_ . 'cms.tpl');
    }

}
