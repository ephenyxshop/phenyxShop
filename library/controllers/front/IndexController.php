<?php

/**
 * Class IndexControllerCore
 *
 * @since 1.8.1.0
 */
class IndexControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'index';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();
        $this->addJS(_THEME_JS_DIR_ . 'index.js');

        $this->context->smarty->assign(
            [
                'HOOK_HOME'             => Hook::exec('displayHome'),
                'HOOK_HOME_TAB'         => Hook::exec('displayHomeTab'),
                'HOOK_HOME_TAB_CONTENT' => Hook::exec('displayHomeTabContent'),
                'homeVideo'             => Configuration::get('EPH_HOME_VIDEO_ACTIVE'),
                'videoLink'             => Configuration::get('EPH_HOME_VIDEO_LINK'),
                'homeParallax'          => Configuration::get('EPH_HOME_PARALLAX_ACTIVE'),
                'parallaxImage'         => Configuration::get('EPH_HOME_PARALLAX_FILE'),
            ]
        );
        $this->setTemplate(_EPH_THEME_DIR_ . 'index.tpl');
    }
}
