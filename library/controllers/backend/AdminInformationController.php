<?php

/**
 * Class AdminInformationControllerCore
 *
 * @since 1.9.1.0
 */
class AdminInformationControllerCore extends AdminController {

    public $php_self = 'admininformation';
    /**
     * @var array $fileList
     *
     * @since 1.9.1.0
     */
    public $fileList = [];

    /**
     * @var string $excludeRegexp
     *
     * @since 1.9.1.0
     */
    protected $excludeRegexp = '^/(install(-dev|-new)?|vendor|themes|tools|cache|docs|download|img|localization|log|mails|translations|upload|modules|override/(.*|index\.php)$)';

    /**
     * AdminInformationControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'informations';
        $this->className = 'Information';
        $this->publicName = $this->l('Information Environnement');
        parent::__construct();
    }

    public function ajaxProcessOpenTargetController() {

        $data = $this->createTemplate($this->table . '.tpl');

        $informations = $this->renderView();

        $data->assign([
            'informations' => $informations,
            'controller'   => $this->controller_name,
            'tableName'    => $this->table,
            'className'    => $this->className,
            'link'         => $this->context->link,
        ]);

        $li = '<li id="uper' . $this->controller_name . '" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display: flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'         => $li,
            'html'       => $html,
            'page_title' => $this->page_title,
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * @since 1.9.1.0
     */
    public function initContent() {

        $this->show_toolbar = false;

        if (!$this->ajax) {
            $this->display = 'view';
        }

        parent::initContent();
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderView() {

        $vars = [
            'version'         => [
                'php'                => phpversion(),
                'server'             => $_SERVER['SERVER_SOFTWARE'],
                'memory_limit'       => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'database'        => [
                'version' => Db::getInstance()->getVersion(),
                'server'  => _DB_SERVER_,
                'name'    => _DB_NAME_,
                'user'    => _DB_USER_,
                'prefix'  => _DB_PREFIX_,
                'engine'  => _MYSQL_ENGINE_,
                'driver'  => Db::getClass(),
            ],
            'uname'           => function_exists('php_uname') ? php_uname('s') . ' ' . php_uname('v') . ' ' . php_uname('m') : '',
            'apache_instaweb' => Tools::apacheModExists('mod_instaweb'),
            'shop'            => [
                'ps'    => _EPH_VERSION_,
                'url'   => $this->context->shop->getBaseURL(),
                'theme' => $this->context->shop->theme_name,
            ],
            'mail'            => Configuration::get('EPH_MAIL_METHOD') == 1,
            'smtp'            => [
                'server'     => Configuration::get('EPH_MAIL_SERVER'),
                'user'       => Configuration::get('EPH_MAIL_USER'),
                'password'   => Configuration::get('EPH_MAIL_PASSWD'),
                'encryption' => Configuration::get('EPH_MAIL_SMTP_ENCRYPTION'),
                'port'       => Configuration::get('EPH_MAIL_SMTP_PORT'),
            ],
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'],
        ];

        $this->tpl_view_vars = array_merge($this->getTestResult(), array_merge($vars));

        return parent::renderView();
    }

    /**
     * get all tests
     *
     * @return array of test results
     */
    public function getTestResult() {

        $testsErrors = [
            'PhpVersion'              => $this->l('Update your PHP version.'),
            'Upload'                  => $this->l('Configure your server to allow file uploads.'),
            'System'                  => $this->l('At least one of these PHP functions is missing: fopen(), fclose(), fread(), fwrite(), rename(), file_exists(), unlink(), rmdir(), mkdir(), getcwd(), chdir() and/or chmod().'),
            'Gd'                      => $this->l('Enable the GD library on your server.'),
            'ConfigDir'               => $this->l('Set write permissions for the "config" folder.'),
            'CacheDir'                => $this->l('Set write permissions for the "cache" folder.'),
            'ImgDir'                  => $this->l('Set write permissions for the "img" folder and subfolders.'),
            'LogDir'                  => $this->l('Set write permissions for the "log" folder and subfolders.'),
            'MailsDir'                => $this->l('Set write permissions for the "mails" folder and subfolders.'),
            'ModuleDir'               => $this->l('Set write permissions for the "modules" folder and subfolders.'),
            'ThemeLangDir'            => sprintf($this->l('Set the write permissions for the "themes%s/lang/" folder and subfolders, recursively.'), _THEME_NAME_),
            'ThemePdfLangDir'         => sprintf($this->l('Set the write permissions for the "themes%s/pdf/lang/" folder and subfolders, recursively.'), _THEME_NAME_),
            'ThemeCacheDir'           => sprintf($this->l('Set the write permissions for the "themes%s/cache/" folder and subfolders, recursively.'), _THEME_NAME_),
            'TranslationsDir'         => $this->l('Set write permissions for the "translations" folder and subfolders.'),
            'CustomizableProductsDir' => $this->l('Set write permissions for the "upload" folder and subfolders.'),
            'VirtualProductsDir'      => $this->l('Set write permissions for the "download" folder and subfolders.'),
            'Fopen'                   => $this->l('Allow PHP fopen() on your server to open remote files/URLs.'),
            'Gz'                      => $this->l('Enable GZIP compression on your server.'),
            'Files'                   => $this->l('Some ephenyx files are missing from your server.'),
            'MaxExecutionTime'        => $this->l('Set PHP `max_execution_time` to at least 30 seconds.'),
            'PdoMysql'                => $this->l('Install the PHP extension for MySQL with PDO support on your server.'),
            'MysqlVersion'            => $this->l('Update your database server to at least MySQL v5.5.3 or MariaDB v5.5.'),
            'Bcmath'                  => $this->l('Install the `bcmath` PHP extension on your server.'),
            'Xml'                     => $this->l('Install the `xml` PHP extension on your server.'),
            'Json'                    => $this->l('Install the `json` PHP extension on your server.'),
            'Zip'                     => $this->l('Install the `zip` PHP extension on your server.'),
            'Tlsv12'                  => $this->l('Install TLS v1.2 support on your server.'),
            'NewPhpVersion'           => sprintf($this->l('You are using PHP %s version. Soon, the oldest PHP version supported by ephenyx will be PHP 5.6. To make sure you’re ready for the future, we recommend you to upgrade to PHP 5.6 now!'), phpversion()),
            'Mbstring'                => $this->l('The `mbstring` extension has not been installed/enabled. This has a severe impact on the store\'s performance.'),
        ];

        // Functions list to test with 'test_system'
        // Test to execute (function/args): lets uses the default test
        $paramsRequiredResults = ConfigurationTest::check(ConfigurationTest::getDefaultTests());
        $paramsOptionalResults = ConfigurationTest::check(ConfigurationTest::getDefaultTestsOp());

        $failRequired = false;

        foreach ($paramsRequiredResults as $key => $result) {

            if ($result !== 'ok') {
                $failRequired = true;
                $testsErrors[$key] .= '<br/>' . sprintf($this->l('Test result: %s'), $result);
                // Establish retrocompatibility with templates.
                $paramsRequiredResults[$key] = 'fail';
            }

        }

        $failOptional = false;

        foreach ($paramsOptionalResults as $key => $result) {

            if ($result !== 'ok') {
                $failOptional = true;
                $testsErrors[$key] .= '<br/>' . sprintf($this->l('Test result: %s'), $result);
                // Establish retrocompatibility with templates.
                $paramsOptionalResults[$key] = 'fail';
            }

        }

        if ($failRequired && $paramsRequiredResults['Files'] !== 'ok') {
            $tmp = ConfigurationTest::testFiles(true);

            if (is_array($tmp) && count($tmp)) {
                $testsErrors['Files'] = $testsErrors['Files'] . '<br/>(' . implode(', ', $tmp) . ')';
            }

        }

        $results = [
            'failRequired'  => $failRequired,
            'testsRequired' => $paramsRequiredResults,
            'failOptional'  => $failOptional,
            'testsOptional' => $paramsOptionalResults,
            'testsErrors'   => $testsErrors,
            'tlsVersion'    => $this->testTlsv12(),
        ];

        return $results;
    }

    public function testTlsv12() {

        $ch = curl_init('https://www.howsmyssl.com/a/check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($data);

        return $json->tls_version;
    }

    /**
     * @since 1.9.1.0
     *
     * @fixme: remove API call
     */
    public function displayAjaxCheckFiles() {

        $this->fileList = [
            'listMissing'   => false,
            'isDevelopment' => false,
            'missing'       => [],
            'updated'       => [],
            'obsolete'      => [],
        ];
        $filesFile = _EPH_CONFIG_DIR_ . 'json/files.json';

        if (file_exists($filesFile)) {
            $files = json_decode(file_get_contents($filesFile), true);
            $this->getListOfUpdatedFiles($files);
        } else {
            $this->fileList['listMissing'] = $filesFile;
        }

        if (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/admin-dev/')) {
            $this->fileList['isDevelopment'] = true;
        }

        $this->ajaxDie(json_encode($this->fileList));
    }

    /**
     * Get the list of files to be checked and save it in
     * config/json/files.json. This can't be done from back office, but
     * is done automatically when building a distribution package.
     *
     * @return array md5 list
     */
    public static function generateMd5List() {

        $md5List = [];
        $adminDir = str_replace(_EPH_ROOT_ADMIN_DIR_, '', _EPH_ADMIN_DIR_);

        $iterator = static::getCheckFileIterator();

        foreach ($iterator as $file) {
            /** @var DirectoryIterator $file */
            $filePath = $file->getPathname();
            $filePath = str_replace(_EPH_ROOT_ADMIN_DIR_, '', $filePath);

            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }

            if (strpos($filePath, $adminDir) !== false) {
                $filePath = str_replace($adminDir, '/admin', $filePath);
            }

            if (is_dir($file->getPathname())) {
                continue;
            }

            $md5List[$filePath] = md5_file($file->getPathname());
        }

        file_put_contents(
            _EPH_CONFIG_DIR_ . 'json/files.json',
            json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $md5List;
    }

    /**
     * Generate a list of files to be checked.
     *
     * @return AppendIterator Iterator of all files to be checked.
     */
    protected static function getCheckFileIterator() {

        $iterator = new AppendIterator();
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_CLASS_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_CONTROLLER_DIR_)));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_ADMIN_DIR_ . '/Core')));
        $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_ADMIN_DIR_ . '/Adapter')));
        // if() for retrocompatibility, only. Make it unconditional after 1.0.8.

        if (!defined('_EPH_VERSION_')
            || version_compare(_EPH_VERSION_, '1.0.7', '>')) {
            $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_ADMIN_DIR_ . '/vendor')));
        }

        $iterator->append(new DirectoryIterator(_EPH_ADMIN_DIR_));

        return $iterator;
    }

    /**
     * @param array       $md5List
     * @param string|null $basePath
     *
     * @since 1.9.1.0
     */
    public function getListOfUpdatedFiles(array $md5List, $basePath = null) {

        $adminDir = str_replace(_EPH_ROOT_ADMIN_DIR_, '', _EPH_ADMIN_DIR_);

        if (is_null($basePath)) {
            $basePath = rtrim(_EPH_ROOT_ADMIN_DIR_, DIRECTORY_SEPARATOR);
        }

        foreach ($md5List as $file => $md5) {

            if (strpos($file, '/admin/') === 0) {
                $file = str_replace('/admin/', $adminDir . '/', $file);
            }

            if (!file_exists($basePath . $file)) {
                $this->fileList['missing'][] = ltrim($file, '/');
                continue;
            }

            if (md5_file($basePath . $file) != $md5) {
                $this->fileList['updated'][] = ltrim($file, '/');
                continue;
            }

        }

        $fileList = array_keys($md5List);

        $iterator = static::getCheckFileIterator();

        foreach ($iterator as $file) {

            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }

            if (is_dir($file->getPathname())) {
                continue;
            }

            $path = str_replace($basePath, '', $file->getPathname());
            $path = str_replace($adminDir, '/admin', $path);

            if (!in_array($path, $fileList)) {
                $this->fileList['obsolete'][] = ltrim($path, '/');
            }

        }

    }

}
