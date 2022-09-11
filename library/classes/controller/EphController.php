<?php

/**
 * Class ControllerCore
 *
 * @since 1.9.1.0
 */
abstract class EphControllerCore {

    // @codingStandardsIgnoreStart
    /** @var array List of PHP errors */
    public static $php_errors = [];
    /** @var array List of CSS files */
    public $css_files = [];

    public $push_css_files = [];
    /** @var array List of JavaScript files footer implemented */
    public $js_footers = [];
    /** @var array List of JavaScript element */
    public $js_shows = [];
    /** @var array List of JavaScript files */
    public $js_files = [];

    public $push_js_files = [];

    public $js_headerfiles = [];
    /** @var bool If AJAX parameter is detected in request, set this flag to true */
    public $ajax = false;

    public $fetchContent = false;

    public $ajaxLayout = false;
    /** @var string Controller type. Possible values: 'front', 'modulefront', 'admin', 'moduleadmin' */
    public $controller_type;
    /** @var string Controller name */
    public $php_self;
	
	public $link_rewrite;
    /** @var Context */
    protected $context;
    /** @var bool Set to true to display page header */
    protected $display_header;
    /** @var bool Set to true to display page header javascript */
    protected $display_header_javascript;
    /** @var string Template filename for the page content */
    protected $template;
    /** @var string Set to true to display page footer */
    protected $display_footer;
    /** @var bool Set to true to only render page content (used to get iframe content) */
    protected $content_only = false;
    /** @var bool If set to true, page content and messages will be encoded to JSON before responding to AJAX request */
    protected $json = false;
    /** @var string JSON response status string */
    protected $status = '';
    /**
     * @see Controller::run()
     * @var string|null Redirect link. If not empty, the user will be redirected after initializing and processing input.
     */
    protected $redirect_after = null;
    // @codingStandardsIgnoreEnd

    /**
     * ControllerCore constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        if (is_null($this->display_header)) {
            $this->display_header = true;
        }

        if (is_null($this->display_header_javascript)) {
            $this->display_header_javascript = true;
        }

        if (is_null($this->display_footer)) {
            $this->display_footer = true;
        }

        $this->context = Context::getContext();
        $this->context->controller = $this;

        // Usage of ajax parameter is deprecated
        $this->ajax = Tools::getValue('ajax') || Tools::isSubmit('ajax');

        if (!headers_sent()
            && isset($_SERVER['HTTP_USER_AGENT'])
            && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
        ) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }

    }

    /**
     * returns a new instance of this controller
     *
     * @param string $className
     * @param bool   $auth
     * @param bool   $ssl
     *
     * @return Controller
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getController($className, $auth = false, $ssl = false) {

        return new $className($auth, $ssl);
    }

    /**
     * Custom error handler
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {

        if (error_reporting() === 0) {
            return false;
        }

        switch ($errno) {
        case E_USER_ERROR:
        case E_ERROR:
            die('Fatal error: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
            break;
        case E_USER_WARNING:
        case E_WARNING:
            $type = 'Warning';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
            $type = 'Notice';
            break;
        default:
            $type = 'Unknown error';
            break;
        }

        // @codingStandardsIgnoreStart
        static::$php_errors[] = [
            'type'    => $type,
            'errline' => (int) $errline,
            'errfile' => str_replace('\\', '\\\\', $errfile), // Hack for Windows paths
            'errno'   => (int) $errno,
            'errstr'  => $errstr,
        ];

        Context::getContext()->smarty->assign('php_errors', static::$php_errors);
        // @codingStandardsIgnoreEnd

        return true;
    }

    /**
     * ephenyx' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for plugins/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     *
     * @return mixed
     *
     * @since 1.0.1
     */
    public function &__get($property) {

        // Property to camelCase for backwards compatibility
        $camelCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (property_exists($this, $camelCaseProperty)) {
            return $this->$camelCaseProperty;
        }

        return $this->$property;
    }

    /**
     * ephenyx' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for plugins/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     *
     * @since 1.0.1
     */
    public function __set($property, $value) {

        $blacklist = [
            '_select',
            '_join',
            '_where',
            '_group',
            '_having',
            '_conf',
            '_lang',
        ];

        // Property to camelCase for backwards compatibility
        $snakeCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (!in_array($property, $blacklist) && property_exists($this, $snakeCaseProperty)) {
            $this->$snakeCaseProperty = $value;
        } else {
            $this->$property = $value;
        }

    }

    /**
     * Starts the controller process
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function run() {

        $this->init();

        if ($this->checkAccess()) {

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->setMedia();
            }

            $this->postProcess();

            if (!empty($this->redirect_after)) {
                $this->redirect();
            }

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->initHeader();
            }

            if ($this->viewAccess()) {
                $this->initContent();
            } else {
                $this->errors[] = Tools::displayError('Access denied.');
            }

            if (!$this->content_only && ($this->display_footer || (isset($this->className) && $this->className))) {
                $this->initFooter();
            }

            if ($this->ajax) {
                $action = Tools::toCamelCase(Tools::getValue('action'), true);

                if (!empty($action) && method_exists($this, 'displayAjax' . $action)) {
                    $this->{'displayAjax' . $action}

                    ();
                } else

                if (method_exists($this, 'displayAjax')) {
                    $this->displayAjax();
                }

            } else

            if ($this->fetchContent) {
                $this->fetchContent();
            } else {
                $this->display();
            }

        } else {
            $this->initCursedPage();

            if (isset($this->layout)) {
                $this->smartyOutputContent($this->layout);
            }

        }

    }

    /**
     * Initialize the page
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function init() {

        if (_PS_MODE_DEV_ && $this->controller_type == 'admin') {
            set_error_handler([__CLASS__, 'myErrorHandler']);
        }

        if (!defined('_PS_BASE_URL_')) {
            define('_PS_BASE_URL_', Tools::getShopDomain(true));
        }

        if (!defined('_PS_BASE_URL_SSL_')) {
            define('_PS_BASE_URL_SSL_', Tools::getShopDomainSsl(true));
        }

    }

    /**
     * Check if the controller is available for the current user/visitor
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function checkAccess();

    /**
     * Sets default media list for this controller
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function setMedia();

    /**
     * Do the page treatment: process input, process AJAX, etc.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function postProcess();

    /**
     * Redirects to $this->redirect_after after the process if there is no error
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract protected function redirect();

    /**
     * Assigns Smarty variables for the page header
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function initHeader();

    /**
     * Check if the current user/visitor has valid view permissions
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function viewAccess();

    /**
     * Assigns Smarty variables for the page main content
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function initContent();

    /**
     * Assigns Smarty variables for the page footer
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function initFooter();

    /**
     * Displays page view
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function display();

    /**
     * Assigns Smarty variables when access is forbidden
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function initCursedPage();

    /**
     * Renders controller templates and generates page content
     *
     * @param array|string $content Template file(s) to be rendered
     *
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function smartyOutputContent($content, $ajax = false) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);

        if (is_array($content)) {

            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }

        } else {
            $html = $this->context->smarty->fetch($content);
        }

        $html = trim($html);

        if (in_array($this->controller_type, ['front', 'modulefront']) && !empty($html) && $this->getLayout()) {
            $liveEditContent = '';

            if (!$this->useMobileTheme() && $this->checkLiveEditAccess()) {
                $liveEditContent = $this->getLiveEditFooter();
            }

            $domAvailable = extension_loaded('dom') ? true : false;
            $defer = (bool) Configuration::get('PS_JS_DEFER');

            if ($defer && $domAvailable) {
                $html = Media::deferInlineScripts($html);
            }

            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";
            $this->context->smarty->assign(
                [
                    $jsTag           => Media::getJsDef(),
                    'js_files'       => $defer ? array_unique($this->js_files) : [],
                    'js_shows'       => $this->js_shows,
                    'js_headerfiles' => array_unique($this->js_headerfiles),
                    'js_inline'      => ($defer && $domAvailable) ? Media::getInlineScript() : [],
                ]
            );
            $javascript = $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($defer && (!isset($this->ajax) || !$this->ajax)) {
                echo $html . $javascript;
            } else {
                echo preg_replace('/(?<!\$)' . $jsTag . '/', $javascript, $html);
            }

            echo $liveEditContent . ((!isset($this->ajax) || !$this->ajax) ? '</body></html>' : '');
        } else {

            if ($this->ajaxLayout) {
                return $html;
            } else {
                echo $html;
            }

        }

    }

    protected function getsmartyOutputContent($content) {

        $this->context->cookie->write();

        $html = '';
        $js_tag = 'js_def';
        $this->context->smarty->assign($js_tag, $js_tag);

        if (is_array($content)) {

            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }

        } else {
            $html = $this->context->smarty->fetch($content);
        }

        $html = trim($html);

        if (in_array($this->controller_type, ['front', 'modulefront']) && !empty($html) && $this->getLayout()) {
            $live_edit_content = '';

            if (!$this->useMobileTheme() && $this->checkLiveEditAccess()) {
                $live_edit_content = $this->getLiveEditFooter();
            }

            $dom_available = extension_loaded('dom') ? true : false;
            $defer = (bool) Configuration::get('PS_JS_DEFER');

            if ($defer && $dom_available) {
                $html = Media::deferInlineScripts($html);
            }

            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";

            $this->context->smarty->assign([
                $js_tag     => Media::getJsDef(),
                'js_files'  => $defer ? array_unique($this->js_files) : [],
                'js_inline' => ($defer && $dom_available) ? Media::getInlineScript() : [],
            ]);

            $javascript = $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($defer && (!isset($this->ajax) || !$this->ajax)) {
                return $html . $javascript;
            } else {
                return preg_replace('/(?<!\$)' . $js_tag . '/', $javascript, $html);
            }

            return $live_edit_content . ((!isset($this->ajax) || !$this->ajax) ? '</body></html>' : '');
        } else {
            return $html;
        }

    }

    /**
     * Sets page header display
     *
     * @param bool $display
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function displayHeader($display = true) {

        $this->display_header = $display;
    }

    /**
     * Sets page header javascript display
     *
     * @param bool $display
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function displayHeaderJavaScript($display = true) {

        $this->display_header_javascript = $display;
    }

    /**
     * Sets page header display
     *
     * @param bool $display
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function displayFooter($display = true) {

        $this->display_footer = $display;
    }

    /**
     * Sets template file for page content output
     *
     * @param string $template
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTemplate($template) {

        $this->template = $template;
    }

    /**
     * Set $this->redirect_after that will be used by redirect() after the process
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setRedirectAfter($url) {

        $this->redirect_after = $url;
    }

    /**
     * Removes CSS stylesheet(s) from the queued stylesheet list
     *
     * @param string|array $cssUri       Path to CSS file or an array like: array(array(uri => media_type), ...)
     * @param string       $cssMediaType
     * @param bool         $checkPath
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function removeCSS($cssUri, $cssMediaType = 'all', $checkPath = true) {

        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => $cssMediaType];
                }

            }

            if ($cssPath && isset($this->css_files[key($cssPath)]) && ($this->css_files[key($cssPath)] == reset($cssPath))) {
                unset($this->css_files[key($cssPath)]);
            }

        }

    }

    /**
     * Removes JS file(s) from the queued JS file list
     *
     * @param string|array $jsUri     Path to JS file or an array like: array(uri, ...)
     * @param bool         $checkPath
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function removeJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsPath = $jsFile;

                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                if ($jsPath && in_array($jsPath, $this->js_files)) {
                    unset($this->js_files[array_search($jsPath, $this->js_files)]);
                }

            }

        } else {
            $jsPath = $jsUri;

            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath) {
                unset($this->js_files[array_search($jsPath, $this->js_files)]);
            }

        }

    }

    /**
     * Adds jQuery library file to queued JS file list
     *
     * @param string|null $version  jQuery library version
     * @param string|null $folder   jQuery file folder
     * @param bool        $minifier If set tot true, a minified version will be included.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addJquery($version = null, $folder = null, $minifier = true) {

        $this->addJS(Media::getJqueryPath($version, $folder, $minifier), false);
    }

    /**
     * Adds a new JavaScript file(s) to the page header.
     *
     * @param string|array $jsUri     Path to JS file or an array like: array(uri, ...)
     * @param bool         $checkPath
     *
     * @return void
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_files)) {
                    $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_files)) {
                $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    public function addFooterJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_footers)) {
                    $this->js_footers[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_footers)) {
                $this->js_footers[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    public function showJS($js_template) {

        $this->js_shows[] = $js_template;
    }

    public function addHeaderJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_headerfiles)) {
                    $this->js_headerfiles[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_headerfiles)) {
                $this->js_headerfiles[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    /**
     * Adds jQuery UI component(s) to queued JS file list
     *
     * @param string|array $component
     * @param string       $theme
     * @param bool         $checkDependencies
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addJqueryUI($component, $theme = 'base', $checkDependencies = true) {

        if (!is_array($component)) {
            $component = [$component];
        }

        foreach ($component as $ui) {
            $uiPath = Media::getJqueryUIPath($ui, $theme, $checkDependencies);
            $this->addCSS($uiPath['css'], 'all', false);
            $this->addJS($uiPath['js'], false);
        }

    }

    /**
     * Adds a new stylesheet(s) to the page header.
     *
     * @param string|array $cssUri       Path to CSS file, or list of css files like this : array(array(uri => media_type), ...)
     * @param string       $cssMediaType
     * @param int|null     $offset
     * @param bool         $checkPath
     *
     * @return true
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {

        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }

            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;

            if ($cssPath && (!isset($this->css_files[$key]) || ($this->css_files[$key] != reset($cssPath)))) {
                $size = count($this->css_files);

                if ($offset === null || $offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->css_files = array_merge(array_slice($this->css_files, 0, $offset), $cssPath, array_slice($this->css_files, $offset));
            }

        }

    }

    public function pushCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {

        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        $result = [];

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = Media::getCSSPath($media, $cssMediaType);
                } else {

                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }

            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;

            if ($cssPath) {
                $size = count($this->push_css_files);

                if ($offset === null || $offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->push_css_files = array_merge(array_slice($this->push_css_files, 0, $offset), $cssPath, array_slice($this->push_css_files, $offset));
            }

        }

        return $this->push_css_files;

    }

    public function pushJS($jsUri, $checkPath = true) {

        $this->push_js_files = [];

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->push_js_files)) {
                    $this->push_js_files[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->push_js_files)) {
                $this->push_js_files[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

        return $this->push_js_files;

    }

    /**
     * Adds jQuery plugin(s) to queued JS file list
     *
     * @param string|array $name
     * @param string|null  $folder
     * @param bool         $css
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addJqueryPlugin($name, $folder = null, $css = true) {

        if (!is_array($name)) {
            $name = [$name];
        }

        if (is_array($name)) {

            foreach ($name as $plugin) {
                $pluginPath = Media::getJqueryPluginPath($plugin, $folder);

                if (!empty($pluginPath['js'])) {
                    $this->addJS($pluginPath['js'], false);
                }

                if ($css && !empty($pluginPath['css'])) {
                    $this->addCSS(key($pluginPath['css']), 'all', null, false);
                }

            }

        }

    }

    /**
     * Checks if the controller has been called from XmlHttpRequest (AJAX)
     *
     * @since   1.5
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function isXmlHttpRequest() {

        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Checks if a template is cached
     *
     * @param string      $template
     * @param string|null $cacheId Cache item ID
     * @param string|null $compileId
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function isCached($template, $cacheId = null, $compileId = null) {

        Tools::enableCache();
        $res = $this->context->smarty->isCached($template, $cacheId, $compileId);
        Tools::restoreCacheSettings();

        return $res;
    }

    /**
     * Dies and echoes output value
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function ajaxDie($value = null, $controller = null, $method = null) {

        if ($controller === null) {
            $controller = get_class($this);
        }

        if ($method === null) {
            $bt = debug_backtrace();
            $method = $bt[1]['function'];
        }

        Hook::exec('actionBeforeAjaxDie', ['controller' => $controller, 'method' => $method, 'value' => $value]);
        Hook::exec('actionBeforeAjaxDie' . $controller . $method, ['value' => $value]);

        die($value);
    }

}
