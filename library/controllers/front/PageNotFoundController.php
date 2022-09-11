<?php

/**
 * Class PageNotFoundControllerCore
 *
 * @since 1.8.1.0
 */
class PageNotFoundControllerCore extends FrontController {

    // @codingStandardsIgnoreSta
    /** @var string $php_self */
    public $php_self = 'pagenotfound';
    /** @var string $page_name */
    public $page_name = 'pagenotfound';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

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

        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');

        if (preg_match('/\.(gif|jpe?g|png|ico)$/i', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            $this->context->cookie->disallowWriting();

            // First preg_match() matches friendly URLs, second one plain URLs.

            if (preg_match('@^' . __EPH_BASE_URI__
                . '([0-9]+)\-([_a-zA-Z-]+)(/[_a-zA-Z-]+)?\.(png|jpe?g|gif)$@',
                $_SERVER['REQUEST_URI'], $matches)
                || preg_match('@^' . _EPH_PROD_IMG_
                    . '[0-9/]+/([0-9]+)\-([_a-zA-Z]+)(\.)(png|jpe?g|gif)$@',
                    $_SERVER['REQUEST_URI'], $matches)) {
                $imageType = ImageType::getByNameNType($matches[2], 'products');

                if ($imageType && count($imageType)) {
                    $root = _EPH_PROD_IMG_DIR_;
                    $folder = Image::getImgFolderStatic($matches[1]);
                    $file = $matches[1];
                    $ext = '.' . $matches[4];

                    if (file_exists($root . $folder . $file . $ext)) {

                        if (ImageManager::resize($root . $folder . $file . $ext, $root . $folder . $file . '-' . $matches[2] . $ext, (int) $imageType['width'], (int) $imageType['height'])) {
                            header('HTTP/1.1 200 Found');
                            header('Status: 200 Found');
                            header('Content-Type: image/jpg');
                            readfile($root . $folder . $file . '-' . $matches[2] . $ext);
                            exit;
                        }

                    }

                }

            } else if (preg_match('@^' . __EPH_BASE_URI__
                . 'c/([0-9]+)\-([_a-zA-Z-]+)(/[_a-zA-Z0-9-]+)?\.(png|jpe?g|gif)$@',
                $_SERVER['REQUEST_URI'], $matches)
                || preg_match('@^' . _THEME_CAT_DIR_
                    . '([0-9]+)\-([_a-zA-Z-]+)(\.)(png|jpe?g|gif)$@',
                    $_SERVER['REQUEST_URI'], $matches)) {
                $imageType = ImageType::getByNameNType($matches[2], 'categories');

                if ($imageType && count($imageType)) {
                    $root = _EPH_CAT_IMG_DIR_;
                    $file = $matches[1];
                    $ext = '.' . $matches[4];

                    if (file_exists($root . $file . $ext)) {

                        if (ImageManager::resize($root . $file . $ext, $root . $file . '-' . $matches[2] . $ext, (int) $imageType['width'], (int) $imageType['height'])) {
                            header('HTTP/1.1 200 Found');
                            header('Status: 200 Found');
                            header('Content-Type: image/jpg');
                            readfile($root . $file . '-' . $matches[2] . $ext);
                            exit;
                        }

                    }

                }

            }

            header('Content-Type: image/gif');
            readfile(_EPH_IMG_DIR_ . '404.gif');
            exit;
        } else if (in_array(mb_strtolower(substr($_SERVER['REQUEST_URI'], -3)), ['.js', 'css'])) {
            $this->context->cookie->disallowWriting();
            exit;
        }

        parent::initContent();

        $this->setTemplate(_EPH_THEME_DIR_ . '404.tpl');
    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalUrl
     *
     * @deprecated 1.0.1
     */
    protected function canonicalRedirection($canonicalUrl = '') {

        // 404 - no need to redirect to the canonical url
    }

    /**
     * SSL redirection
     *
     * @deprecated 1.0.1
     */
    protected function sslRedirection() {

        // 404 - no need to redirect
    }

}
