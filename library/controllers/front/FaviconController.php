<?php

/**
 * Class FaviconControllerCore
 *
 * @since 1.0.4
 */
class FaviconControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'favicon';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.4
     */
    public function init() {

        if (Tools::getValue('icon') === 'apple-touch-icon') {

            if (Tools::getIsset('width') && Tools::getIsset('height')) {
                $width = Tools::getValue('width');
                $height = Tools::getValue('height');

                header('Content-Type: image/png');
                readfile(_EPH_IMG_DIR_ . "favicon/favicon_{$this->context->company->id}_{$width}_{$height}.png");
                exit;
            }

            header('Content-Type: image/png');
            readfile(_EPH_IMG_DIR_ . "favicon/favicon_{$this->context->company->id}_180_180.png");
            exit;
        }

        header('Content-Type: image/x-icon');
        readfile(_EPH_IMG_DIR_ . "favicon.ico");
        exit;
    }

}
