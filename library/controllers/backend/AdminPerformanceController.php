<?php

/**
 * Class AdminPerformanceControllerCore
 *
 * @since 1.9.1.0
 */
class AdminPerformanceControllerCore extends AdminController {

    public $php_self = 'adminperformance';

    const DEBUG_MODE_SUCCEEDED = 0;
    const DEBUG_MODE_ERROR_NO_READ_ACCESS = 1;
    const DEBUG_MODE_ERROR_NO_READ_ACCESS_CUSTOM = 2;
    const DEBUG_MODE_ERROR_NO_WRITE_ACCESS = 3;
    const DEBUG_MODE_ERROR_NO_WRITE_ACCESS_CUSTOM = 4;
    const DEBUG_MODE_ERROR_NO_DEFINITION_FOUND = 5;

    const PROFILING_SUCCEEDED = 0;
    const PROFILING_ERROR_NO_READ_ACCESS = 1;
    const PROFILING_ERROR_NO_READ_ACCESS_CUSTOM = 2;
    const PROFILING_ERROR_NO_WRITE_ACCESS = 3;
    const PROFILING_ERROR_NO_WRITE_ACCESS_CUSTOM = 4;
    const PROFILING_ERROR_NO_DEFINITION_FOUND = 5;

    /**
     * AdminPerformanceControllerCore constructor.
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'performance';
        $this->publicName = $this->l('Mise en Cache et compression');

        $this->context = Context::getContext();
        parent::__construct();

        $this->ajaxOptions = $this->generatePerformanceTab();
    }

    public function generatePerformanceTab() {

        $tabs = [];
        $tabs['Smarty'] = [
            'key'     => 'smarty',
            'content' => $this->initSmarty(),
        ];
        $tabs['Mode débug'] = [
            'key'     => 'debugMode',
            'content' => $this->initDebug(),
        ];
        $tabs['Compression'] = [
            'key'     => 'compression',
            'content' => $this->initCompression(),
        ];
        $tabs['Chiffrement'] = [
            'key'     => 'encrtyp',
            'content' => $this->initEncrypt(),
        ];
        $tabs['Antémémoire côté serveur'] = [
            'key'     => 'servercaching',
            'content' => $this->initServerCaching(),
        ];
        $tabs['Page Front Office pleine cache'] = [
            'key'     => 'fullPageCache',
            'content' => $this->initFullPageCache(),
        ];

        return $tabs;
    }

    public function initSmarty() {

        $data = $this->createTemplate('controllers/performance/smarty.tpl');

        $data->assign([
            'smarty_force_compile' => Configuration::get('EPH_SMARTY_FORCE_COMPILE'),
            'smarty_cache'         => Configuration::get('EPH_SMARTY_CACHE'),
            'smarty_caching_type'  => Configuration::get('EPH_SMARTY_CACHING_TYPE'),
            'smarty_clear_cache'   => Configuration::get('EPH_SMARTY_CLEAR_CACHE'),
            'smarty_console'       => Configuration::get('EPH_SMARTY_CONSOLE'),
            'smarty_console_key'   => Configuration::get('EPH_SMARTY_CONSOLE_KEY'),
        ]);

        return $data->fetch();
    }

    public function initDebug() {

        $data = $this->createTemplate('controllers/performance/debug.tpl');

        $data->assign([
            'debug_mode' => $this->isDebugModeEnabled(),
            'profiling'  => $this->isProfilingEnabled(),
        ]);

        return $data->fetch();
    }

    public function initCompression() {

        $data = $this->createTemplate('controllers/performance/compression.tpl');

        $data->assign([
            'EPH_CSS_THEME_CACHE'                => Configuration::get('EPH_CSS_THEME_CACHE'),
            'EPH_JS_THEME_CACHE'                 => Configuration::get('EPH_JS_THEME_CACHE'),
            'EPH_JS_HTML_THEME_COMPRESSION'      => Configuration::get('EPH_JS_HTML_THEME_COMPRESSION'),
            'EPH_HTACCESS_CACHE_CONTROL'         => Configuration::get('EPH_HTACCESS_CACHE_CONTROL'),
            'EPH_JS_DEFER'                       => Configuration::get('EPH_JS_DEFER'),
            'EPH_CSS_BACKOFFICE_CACHE'           => Configuration::get('EPH_CSS_BACKOFFICE_CACHE'),
            'EPH_JS_BACKOFFICE_CACHE'            => Configuration::get('EPH_JS_BACKOFFICE_CACHE'),
            'EPH_JS_HTML_BACKOFFICE_COMPRESSION' => Configuration::get('EPH_JS_HTML_BACKOFFICE_COMPRESSION'),
            'EPH_JS_BACKOFFICE_DEFER'            => Configuration::get('EPH_JS_BACKOFFICE_DEFER'),
            'EPH_KEEP_CCC_FILES'                => Configuration::get('EPH_KEEP_CCC_FILES'),
            'ccc_up'                            => 1,
        ]);

        return $data->fetch();
    }

    public function initEncrypt() {

        $data = $this->createTemplate('controllers/performance/encrypt.tpl');

        $data->assign([
            'EPH_CIPHER_ALGORITHM' => Configuration::get('EPH_CIPHER_ALGORITHM'),
        ]);

        return $data->fetch();
    }

    public function initServerCaching() {

        $phpdocLangs = ['en', 'zh', 'fr', 'de', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'];
        $phpLang = in_array($this->context->language->iso_code, $phpdocLangs) ? $this->context->language->iso_code : 'en';
        $warningMemcache = ' ' . $this->l('(you must install the [a]Memcache PECL extension[/a])');
        $warningMemcache = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/memcache.installation.php" target="_blank">', $warningMemcache);
        $warningMemcache = str_replace('[/a]', '</a>', $warningMemcache);
        $warningMemcached = ' ' . $this->l('(you must install the [a]Memcached PECL extension[/a])');
        $warningMemcached = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/memcached.installation.php" target="_blank">', $warningMemcached);
        $warningMemcached = str_replace('[/a]', '</a>', $warningMemcached);
        $warningApc = ' ' . $this->l('(you must install the [a]APC PECL extension[/a])');
        $warningApc = str_replace('[a]', '<a href="http://php.net/manual/' . substr($phpLang, 0, 2) . '/apc.installation.php" target="_blank">', $warningApc);
        $warningApc = str_replace('[/a]', '</a>', $warningApc);
        $warningRedis = ' ' . $this->l('(you must install the [a]redis extension[/a])');
        $warningRedis = str_replace('[a]', '<a href="https://pecl.php.net/package/redis" target="_blank">', $warningRedis);
        $warningRedis = str_replace('[/a]', '</a>', $warningRedis);

        $warningFs = ' ' . sprintf($this->l('(the directory %s must be writable)'), realpath(_EPH_CACHEFS_DIRECTORY_));

        $data = $this->createTemplate('controllers/performance/servercaching.tpl');
        $depth = Configuration::get('EPH_CACHEFS_DIRECTORY_DEPTH');
        $data->assign([
            'EPH_CACHE_ENABLED'           => Configuration::get('EPH_CACHE_ENABLED'),
            'EPH_CACHE_SYSTEM'            => Configuration::get('EPH_CACHE_SYSTEM') ?: 'CacheFs',
            'EPH_cache_fs_directory_depth' => $depth ? $depth : 1,
            'memcached_servers'           => CacheMemcache::getMemcachedServers(),
            'redis_servers'               => CacheRedis::getRedisServers(),
            'cacheDisabled'               => !Cache::isEnabled(),
            'warningMemcache'             => $warningMemcache,
            'warningMemcached'            => $warningMemcached,
            'warningApc'                  => $warningApc,
            'warningRedis'                => $warningRedis,
            'warningFs'                   => $warningFs,
            'isWritable'                  => is_writable(_EPH_CACHEFS_DIRECTORY_),
            'memcache'                    => extension_loaded('memcache'),
            'memcached'                   => extension_loaded('memcached'),
            'apcu'                        => extension_loaded('apcu'),
            'redis'                       => extension_loaded('redis'),
        ]);

        return $data->fetch();
    }

    public function initFullPageCache() {

        Cache::clean('hook_module_list');
        $hooks = Hook::getHookModuleList();
        $hookSettings = PageCache::getCachedHooks();
        $moduleSettings = [];

        foreach ($hooks as $hook) {

            foreach ($hook as &$hookInfo) {
                $idModule = (int) $hookInfo['id_module'];
                $idHook = (int) $hookInfo['id_hook'];
                $moduleName = $hookInfo['name'];
                $moduleDisplayName = Module::getModuleName($moduleName);
                $hookName = Hook::getNameById($idHook);
                // We only want display hooks

                if (strpos($hookName, 'action') === 0
                    || strpos($hookName, 'displayAdmin') === 0
                    || strpos($hookName, 'dashboard') === 0
                    || strpos($hookName, 'BackOffice') !== false
                ) {
                    continue;
                }

                if (!isset($moduleSettings[$idModule])) {
                    $moduleSettings[$idModule] = [
                        'name'        => $moduleName,
                        'displayName' => $moduleDisplayName,
                        'hooks'       => [],
                    ];
                }

                $moduleSettings[$hookInfo['id_module']]['hooks'][$hookName] = isset($hookSettings[$idModule][$idHook]);
            }

        }

        $data = $this->createTemplate('controllers/performance/fullPage.tpl');

        $controllerList = $this->displayControllerList(json_decode(Configuration::get('EPH_PAGE_CACHE_CONTROLLERS'), true), $this->context->shop->id);

        $data->assign([
            'controllerList'              => $controllerList,
            'moduleSettings'              => $moduleSettings,
            'EPH_PAGE_CACHE_ENABLED'      => PageCache::isEnabled(),
            'EPH_PAGE_CACHE_DEBUG'        => (bool) Configuration::get('EPH_PAGE_CACHE_DEBUG'),
            'EPH_PAGE_CACHE_IGNOREPARAMS' => (bool) Configuration::get('EPH_PAGE_CACHE_IGNOREPARAMS'),
        ]);

        return $data->fetch();
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function renderForm() {

        $this->initFieldsetSmarty();
        $this->initFieldsetDebugMode();
        $this->initFieldsetCCC();

        $this->initFieldsetCiphering();

        $this->initFieldsetCaching();
        $this->initFieldsetFullPageCache();

        // Reindex fields
        $this->fields_form = array_values($this->fields_form);

        // Activate multiple fieldset
        $this->multiple_fieldsets = true;

        return parent::renderForm();
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetSmarty() {

        $this->fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Smarty'),
                'icon'  => 'icon-briefcase',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'smarty_up',
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Template compilation'),
                    'name'   => 'smarty_force_compile',
                    'values' => [
                        [
                            'id'    => 'smarty_force_compile_' . _EPH_SMARTY_NO_COMPILE_,
                            'value' => _EPH_SMARTY_NO_COMPILE_,
                            'label' => $this->l('Never recompile template files'),
                            'hint'  => $this->l('This option should be used in a production environment.'),
                        ],
                        [
                            'id'    => 'smarty_force_compile_' . _EPH_SMARTY_CHECK_COMPILE_,
                            'value' => _EPH_SMARTY_CHECK_COMPILE_,
                            'label' => $this->l('Recompile templates if the files have been updated'),
                            'hint'  => $this->l('Templates are recompiled when they are updated. If you experience compilation troubles when you update your template files, you should use Force Compile instead of this option. It should never be used in a production environment.'),
                        ],
                        [
                            'id'    => 'smarty_force_compile_' . _EPH_SMARTY_FORCE_COMPILE_,
                            'value' => _EPH_SMARTY_FORCE_COMPILE_,
                            'label' => $this->l('Force compilation'),
                            'hint'  => $this->l('This forces Smarty to (re)compile templates on every invocation. This is handy for development and debugging. Note: This should never be used in a production environment.'),
                        ],
                    ],
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Cache'),
                    'name'    => 'smarty_cache',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'smarty_cache_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'smarty_cache_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'    => $this->l('Should be enabled except for debugging.'),
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Caching type'),
                    'name'   => 'smarty_caching_type',
                    'values' => [
                        [
                            'id'    => 'smarty_caching_type_filesystem',
                            'value' => 'filesystem',
                            'label' => $this->l('File System') . (is_writable(_EPH_CACHE_DIR_ . 'smarty/cache') ? '' : ' ' . sprintf($this->l('(the directory %s must be writable)'), realpath(_EPH_CACHE_DIR_ . 'smarty/cache'))),
                        ],
                        [
                            'id'    => 'smarty_caching_type_mysql',
                            'value' => 'mysql',
                            'label' => $this->l('MySQL'),
                        ],

                    ],
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Clear cache'),
                    'name'   => 'smarty_clear_cache',
                    'values' => [
                        [
                            'id'    => 'smarty_clear_cache_never',
                            'value' => 'never',
                            'label' => $this->l('Never clear cache files'),
                        ],
                        [
                            'id'    => 'smarty_clear_cache_everytime',
                            'value' => 'everytime',
                            'label' => $this->l('Clear cache everytime something has been modified'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['smarty_force_compile'] = Configuration::get('EPH_SMARTY_FORCE_COMPILE');
        $this->fields_value['smarty_cache'] = Configuration::get('EPH_SMARTY_CACHE');
        $this->fields_value['smarty_caching_type'] = Configuration::get('EPH_SMARTY_CACHING_TYPE');
        $this->fields_value['smarty_clear_cache'] = Configuration::get('EPH_SMARTY_CLEAR_CACHE');
        $this->fields_value['smarty_console'] = Configuration::get('EPH_SMARTY_CONSOLE');
        $this->fields_value['smarty_console_key'] = Configuration::get('EPH_SMARTY_CONSOLE_KEY');
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetDebugMode() {

        $this->fields_form[1]['form'] = [
            'legend' => [
                'title' => $this->l('Debug mode'),
                'icon'  => 'icon-bug',
            ],
            'input'  => [
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Disable non ephenyx modules'),
                    'name'    => 'native_module',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'native_module_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'native_module_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable non ephenyx modules.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Disable all overrides'),
                    'name'    => 'overrides',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'overrides_module_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'overrides_module_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable all classes and controllers overrides.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Debug mode'),
                    'name'    => 'debug_mode',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'debug_mode_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'debug_mode_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable debug mode.'),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Profiling'),
                    'name'    => 'profiling',
                    'class'   => 't',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'profiling_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'profiling_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'    => $this->l('Enable or disable profiling.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['native_module'] = Configuration::get('EPH_DISABLE_NON_NATIVE_MODULE');
        $this->fields_value['overrides'] = Configuration::get('EPH_DISABLE_OVERRIDES');
        $this->fields_value['debug_mode'] = $this->isDebugModeEnabled();
        $this->fields_value['profiling'] = $this->isProfilingEnabled();
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetFeaturesDetachables() {

        $this->fields_form[2]['form'] = [
            'legend'      => [
                'title' => $this->l('Optional features'),
                'icon'  => 'icon-puzzle-piece',
            ],
            'description' => $this->l('Some features can be disabled in order to improve performance.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'features_detachables_up',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Combinations'),
                    'name'     => 'combination',
                    'is_bool'  => true,
                    'disabled' => Combination::isCurrentlyUsed(),
                    'values'   => [
                        [
                            'id'    => 'combination_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'combination_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'     => $this->l('Choose "No" to disable Product Combinations.'),
                    'desc'     => Combination::isCurrentlyUsed() ? $this->l('You cannot set this parameter to No when combinations are already used by some of your products') : null,
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Features'),
                    'name'    => 'feature',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'feature_1',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'feature_0',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'    => $this->l('Choose "No" to disable Product Features.'),
                ],

            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['combination'] = Combination::isFeatureActive();
        $this->fields_value['feature'] = Feature::isFeatureActive();
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetCCC() {

        $this->fields_form[3]['form'] = [
            'legend'      => [
                'title' => $this->l('Compression'),
                'icon'  => 'icon-fullscreen',
            ],
            'description' => $this->l('CCC allows you to reduce the loading time of your page. With these settings you will gain performance without even touching the code of your theme. Make sure, however, that your theme is compatible with ephenyx 1.0.x. Otherwise, CCC will cause problems.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'ccc_up',
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Smart cache for CSS'),
                    'name'   => 'EPH_CSS_THEME_CACHE',
                    'values' => [
                        [
                            'id'    => 'EPH_CSS_THEME_CACHE_1',
                            'value' => 1,
                            'label' => $this->l('Use CCC for CSS'),
                        ],
                        [
                            'id'    => 'EPH_CSS_THEME_CACHE_0',
                            'value' => 0,
                            'label' => $this->l('Keep CSS as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Smart cache for JavaScript'),
                    'name'   => 'EPH_JS_THEME_CACHE',
                    'values' => [
                        [
                            'id'    => 'EPH_JS_THEME_CACHE_1',
                            'value' => 1,
                            'label' => $this->l('Use CCC for JavaScript'),
                        ],
                        [
                            'id'    => 'EPH_JS_THEME_CACHE_0',
                            'value' => 0,
                            'label' => $this->l('Keep JavaScript as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Compress inline JavaScript in HTML'),
                    'name'   => 'EPH_JS_HTML_THEME_COMPRESSION',
                    'values' => [
                        [
                            'id'    => 'EPH_JS_HTML_THEME_COMPRESSION_1',
                            'value' => 1,
                            'label' => $this->l('Compress inline JavaScript in HTML after "Smarty compile" execution'),
                        ],
                        [
                            'id'    => 'EPH_JS_HTML_THEME_COMPRESSION_0',
                            'value' => 0,
                            'label' => $this->l('Keep inline JavaScript in HTML as original'),
                        ],
                    ],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Move JavaScript to the end'),
                    'name'   => 'EPH_JS_DEFER',
                    'values' => [
                        [
                            'id'    => 'EPH_JS_DEFER_1',
                            'value' => 1,
                            'label' => $this->l('Move JavaScript to the end of the HTML document'),
                        ],
                        [
                            'id'    => 'EPH_JS_DEFER_0',
                            'value' => 0,
                            'label' => $this->l('Keep JavaScript in HTML at its original position'),
                        ],
                    ],
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];
        $this->fields_form[3]['form']['input'][] = [
            'type'   => 'switch',
            'label'  => $this->l('Apache optimization'),
            'name'   => 'EPH_HTACCESS_CACHE_CONTROL',
            'hint'   => $this->l('This will add directives to your .htaccess file, which should improve caching and compression.'),
            'values' => [
                [
                    'id'    => 'EPH_HTACCESS_CACHE_CONTROL_1',
                    'value' => 1,
                    'label' => $this->l('Yes'),
                ],
                [
                    'id'    => 'EPH_HTACCESS_CACHE_CONTROL_0',
                    'value' => 0,
                    'label' => $this->l('No'),
                ],
            ],
        ];

        $this->fields_form[3]['form']['input'][] = [
            'type'   => 'switch',
            'label'  => $this->l('Keep JS and CSS files'),
            'desc'   => $this->l('Keep old JS and CSS files on the server, to make sure e.g. Google\'s cache still renders correctly (improves SEO).'),
            'name'   => 'EPH_KEEP_CCC_FILES',
            'values' => [
                [
                    'id'    => 'EPH_KEEP_CCC_FILES_1',
                    'value' => 1,
                ],
                [
                    'id'    => 'EPH_KEEP_CCC_FILES_0',
                    'value' => 0,
                ],
            ],
        ];

        $this->fields_value['EPH_CSS_THEME_CACHE'] = Configuration::get('EPH_CSS_THEME_CACHE');
        $this->fields_value['EPH_JS_THEME_CACHE'] = Configuration::get('EPH_JS_THEME_CACHE');
        $this->fields_value['EPH_JS_HTML_THEME_COMPRESSION'] = Configuration::get('EPH_JS_HTML_THEME_COMPRESSION');
        $this->fields_value['EPH_HTACCESS_CACHE_CONTROL'] = Configuration::get('EPH_HTACCESS_CACHE_CONTROL');
        $this->fields_value['EPH_JS_DEFER'] = Configuration::get('EPH_JS_DEFER');
        $this->fields_value['EPH_KEEP_CCC_FILES'] = Configuration::get('EPH_KEEP_CCC_FILES');
        $this->fields_value['ccc_up'] = 1;
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetMediaServer() {

        $this->fields_form[4]['form'] = [
            'legend'      => [
                'title' => $this->l('Media servers (use only with CCC)'),
                'icon'  => 'icon-link',
            ],
            'description' => $this->l('You must enter another domain, or subdomain, in order to use cookieless static content.'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'media_server_up',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #1'),
                    'name'  => '_MEDIA_SERVER_1_',
                    'hint'  => $this->l('Name of the second domain of your shop, (e.g. myshop-media-server-1.com). If you do not have another domain, leave this field blank.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #2'),
                    'name'  => '_MEDIA_SERVER_2_',
                    'hint'  => $this->l('Name of the third domain of your shop, (e.g. myshop-media-server-2.com). If you do not have another domain, leave this field blank.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Media server #3'),
                    'name'  => '_MEDIA_SERVER_3_',
                    'hint'  => $this->l('Name of the fourth domain of your shop, (e.g. myshop-media-server-3.com). If you do not have another domain, leave this field blank.'),
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['_MEDIA_SERVER_1_'] = Configuration::get('EPH_MEDIA_SERVER_1');
        $this->fields_value['_MEDIA_SERVER_2_'] = Configuration::get('EPH_MEDIA_SERVER_2');
        $this->fields_value['_MEDIA_SERVER_3_'] = Configuration::get('EPH_MEDIA_SERVER_3');
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initFieldsetCiphering() {

        $phpdocLangs = ['en', 'zh', 'fr', 'de', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'];
        $phpLang = in_array($this->context->language->iso_code, $phpdocLangs) ? $this->context->language->iso_code : 'en';

        $warningMcrypt = ' ' . $this->l('(You must install the [a]Mcrypt extension[/a])');
        $warningMcrypt = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/book.mcrypt.php" target="_blank">', $warningMcrypt);
        $warningMcrypt = str_replace('[/a]', '</a>', $warningMcrypt);

        $warningOpenssl = ' ' . $this->l('(You must install the [a]openssl extension[/a])');
        $warningOpenssl = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/book.openssl.php" target="_blank">', $warningOpenssl);
        $warningOpenssl = str_replace('[/a]', '</a>', $warningOpenssl);

        $usePhpEncryptionWith = (extension_loaded('libsodium') || version_compare(phpversion(), '7.2.0', '>=')) ? 'libsodium' : (function_exists('openssl_encrypt') ? 'openssl' : 'libsodium/openssl');
        $useRijndaelWith = extension_loaded('openssl') ? 'openssl' : (function_exists('mcrypt_encrypt') ? 'mcrypt' : 'mcrypt/openssl');

        $this->fields_form[5]['form'] = [

            'legend'      => [
                'title' => $this->l('Ciphering'),
                'icon'  => 'icon-desktop',
            ],
            'description' => $this->l('Keep in mind that changing this setting will log everyone out!'),
            'input'       => [
                [
                    'type' => 'hidden',
                    'name' => 'ciphering_up',
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Algorithm'),
                    'name'   => 'EPH_CIPHER_ALGORITHM',
                    'hint'   => $this->l('The Rijndael is faster than our custom BlowFish class, but requires the "mcrypt" or "openssl" PHP extension. If you change this configuration option, all cookies will be reset.'),
                    'values' => [
                        [
                            'id'    => 'EPH_CIPHER_ALGORITHM_2',
                            'value' => 2,
                            'label' => sprintf($this->l('Use the PHP Encryption library with the %s extension (highest security).'), $usePhpEncryptionWith) . (extension_loaded('openssl') ? '' : $warningOpenssl),
                        ],
                        [
                            'id'    => 'EPH_CIPHER_ALGORITHM_1',
                            'value' => 1,
                            'label' => sprintf($this->l('Use Rijndael with the %s extension.'), $useRijndaelWith) . (!extension_loaded('openssl') && !function_exists('mcrypt_encrypt') ? $warningOpenssl : (!extension_loaded('openssl') ? (!function_exists('mcrypt_encrypt') ? $warningMcrypt : '') : '')),
                        ],
                        [
                            'id'    => 'EPH_CIPHER_ALGORITHM_0',
                            'value' => 0,
                            'label' => $this->l('Use the custom BlowFish class.'),
                        ],
                    ],
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['EPH_CIPHER_ALGORITHM'] = Configuration::get('EPH_CIPHER_ALGORITHM');
    }

    /**
     * @since 1.9.1.0
     */
    public function initFieldsetCaching() {

        $phpdocLangs = ['en', 'zh', 'fr', 'de', 'ja', 'pl', 'ro', 'ru', 'fa', 'es', 'tr'];
        $phpLang = in_array($this->context->language->iso_code, $phpdocLangs) ? $this->context->language->iso_code : 'en';
        $warningMemcache = ' ' . $this->l('(you must install the [a]Memcache PECL extension[/a])');
        $warningMemcache = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/memcache.installation.php" target="_blank">', $warningMemcache);
        $warningMemcache = str_replace('[/a]', '</a>', $warningMemcache);
        $warningMemcached = ' ' . $this->l('(you must install the [a]Memcached PECL extension[/a])');
        $warningMemcached = str_replace('[a]', '<a href="http://www.php.net/manual/' . substr($phpLang, 0, 2) . '/memcached.installation.php" target="_blank">', $warningMemcached);
        $warningMemcached = str_replace('[/a]', '</a>', $warningMemcached);
        $warningApc = ' ' . $this->l('(you must install the [a]APC PECL extension[/a])');
        $warningApc = str_replace('[a]', '<a href="http://php.net/manual/' . substr($phpLang, 0, 2) . '/apc.installation.php" target="_blank">', $warningApc);
        $warningApc = str_replace('[/a]', '</a>', $warningApc);
        $warningRedis = ' ' . $this->l('(you must install the [a]redis extension[/a])');
        $warningRedis = str_replace('[a]', '<a href="https://pecl.php.net/package/redis" target="_blank">', $warningRedis);
        $warningRedis = str_replace('[/a]', '</a>', $warningRedis);

        $warningFs = ' ' . sprintf($this->l('(the directory %s must be writable)'), realpath(_EPH_CACHEFS_DIRECTORY_));
        $this->fields_form[6]['form'] = [
            'legend'           => [
                'title' => $this->l('Server Side Caching'),
                'icon'  => 'icon-desktop',
            ],
            'input'            => [
                [
                    'type' => 'hidden',
                    'name' => 'cache_up',
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Use cache'),
                    'name'    => 'EPH_CACHE_ENABLED',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'EPH_CACHE_ENABLED_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'EPH_CACHE_ENABLED_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Caching system'),
                    'name'   => 'EPH_CACHE_SYSTEM',
                    'hint'   => $this->l('The CacheFS system should be used only when the infrastructure contains one front-end server. If you are not sure, ask your hosting company.'),
                    'values' => [
                        [
                            'id'    => 'CacheFs',
                            'value' => 'CacheFs',
                            'label' => $this->l('File System') . (is_writable(_EPH_CACHEFS_DIRECTORY_) ? '' : $warningFs),
                        ],
                        [
                            'id'    => 'CacheMemcache',
                            'value' => 'CacheMemcache',
                            'label' => $this->l('Memcache via PHP::Memcache') . (extension_loaded('memcache') ? '' : $warningMemcache),
                        ],
                        [
                            'id'    => 'CacheMemcached',
                            'value' => 'CacheMemcached',
                            'label' => $this->l('Memcached via PHP::Memcached') . (extension_loaded('memcached') ? '' : $warningMemcached),
                        ],
                        [
                            'id'    => 'CacheApc',
                            'value' => 'CacheApcu',
                            'label' => $this->l('APC') . (extension_loaded('apcu') ? '' : $warningApc),
                        ],
                        [
                            'id'    => 'CacheRedis',
                            'value' => 'CacheRedis',
                            'label' => $this->l('redis') . (extension_loaded('redis') ? '' : $warningRedis),
                        ],
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Directory depth'),
                    'name'  => 'EPH_cache_fs_directory_depth',
                ],
            ],
            'submit'           => [
                'title' => $this->l('Save'),
            ],
            'memcachedServers' => true,
            'redisServers'     => true,
        ];
        $depth = Configuration::get('EPH_CACHEFS_DIRECTORY_DEPTH');
        $this->fields_value['EPH_CACHE_ENABLED'] = Configuration::get('EPH_CACHE_ENABLED');
        $this->fields_value['EPH_CACHE_SYSTEM'] = Configuration::get('EPH_CACHE_SYSTEM') ?: 'CacheFs';
        $this->fields_value['EPH_cache_fs_directory_depth'] = $depth ? $depth : 1;
        $this->tpl_form_vars['memcached_servers'] = CacheMemcache::getMemcachedServers();
        $this->tpl_form_vars['redis_servers'] = CacheRedis::getRedisServers();
        $this->tpl_form_vars['cacheDisabled'] = !Cache::isEnabled();
    }

    /**
     * @since 1.9.1.0
     */
    public function initFieldsetFullPageCache() {

        Cache::clean('hook_module_list');
        $hooks = Hook::getHookModuleList();
        $hookSettings = PageCache::getCachedHooks();
        $moduleSettings = [];

        foreach ($hooks as $hook) {

            foreach ($hook as &$hookInfo) {
                $idModule = (int) $hookInfo['id_module'];
                $idHook = (int) $hookInfo['id_hook'];
                $moduleName = $hookInfo['name'];
                $moduleDisplayName = Module::getModuleName($moduleName);
                $hookName = Hook::getNameById($idHook);
                // We only want display hooks

                if (strpos($hookName, 'action') === 0
                    || strpos($hookName, 'displayAdmin') === 0
                    || strpos($hookName, 'dashboard') === 0
                    || strpos($hookName, 'BackOffice') !== false
                ) {
                    continue;
                }

                if (!isset($moduleSettings[$idModule])) {
                    $moduleSettings[$idModule] = [
                        'name'        => $moduleName,
                        'displayName' => $moduleDisplayName,
                        'hooks'       => [],
                    ];
                }

                $moduleSettings[$hookInfo['id_module']]['hooks'][$hookName] = isset($hookSettings[$idModule][$idHook]);
            }

        }

        $this->fields_form[7]['form'] = [
            'legend'         => [
                'title' => $this->l('Full page cache'),
                'icon'  => 'icon-rocket',
            ],
            'description'    => $this->l('Before enabling the full page cache, make sure you have chosen a caching system in the panel above.'),
            'input'          => [
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Use full page cache'),
                    'name'     => 'EPH_PAGE_CACHE_ENABLED',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'EPH_PAGE_CACHE_ENABLED_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'EPH_PAGE_CACHE_ENABLED_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'disabled' => !Cache::isEnabled(),
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Debug mode'),
                    'hint'    => $this->l('Enable this option to see the "X-ephenyx-PageCache" debug header'),
                    'name'    => 'EPH_PAGE_CACHE_DEBUG',
                    'is_bool' => true,
                    'values'  => [
                        [
                            'id'    => 'EPH_PAGE_CACHE_DEBUG_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'EPH_PAGE_CACHE_DEBUG_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'       => 'tags',
                    'label'      => $this->l('Ignore query parameters'),
                    'name'       => 'EPH_PAGE_CACHE_IGNOREPARAMS',
                    'hint'       => [
                        $this->l('To add parameters click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                    ],
                    'tagPrompt'  => $this->l('Add param'),
                    'delimiters' => '13,44,32,59',
                ],
            ],
            'submit'         => [
                'title' => $this->l('Save'),
            ],
            'controllerList' => true,
            'dynamicHooks'   => true,
        ];

        $controllerList = $this->displayControllerList(json_decode(Configuration::get('EPH_PAGE_CACHE_CONTROLLERS'), true), $this->context->shop->id);

        $this->tpl_form_vars['controllerList'] = $controllerList;
        $this->tpl_form_vars['moduleSettings'] = $moduleSettings;
        $this->fields_value['EPH_PAGE_CACHE_ENABLED'] = PageCache::isEnabled();
        $this->fields_value['EPH_PAGE_CACHE_DEBUG'] = (bool) Configuration::get('EPH_PAGE_CACHE_DEBUG');
        $this->fields_value['EPH_PAGE_CACHE_IGNOREPARAMS'] = Configuration::get('EPH_PAGE_CACHE_IGNOREPARAMS');
    }

    public function ajaxProcessSavePerformance() {

        Configuration::updateValue('EPH_SMARTY_FORCE_COMPILE', Tools::getValue('smarty_force_compile', _EPH_SMARTY_NO_COMPILE_));

        if (Configuration::get('EPH_SMARTY_CACHE') != Tools::getValue('smarty_cache') || Configuration::get('EPH_SMARTY_CACHING_TYPE') != Tools::getValue('smarty_caching_type')) {
            Tools::clearSmartyCache();
        }

        Configuration::updateValue('EPH_SMARTY_CACHE', Tools::getValue('smarty_cache', 0));
        Configuration::updateValue('EPH_SMARTY_CACHING_TYPE', Tools::getValue('smarty_caching_type'));
        Configuration::updateValue('EPH_SMARTY_CLEAR_CACHE', Tools::getValue('smarty_clear_cache'));

        $debugMode = (bool) Tools::getValue('debug_mode');

        if ($debugMode) {
            $debugModeStatus = $this->enableDebugMode();
        } else {
            $debugModeStatus = $this->disableDebugMode();
        }

        $profiling = (bool) Tools::getValue('profiling');

        if ($profiling) {
            $profilingStatus = $this->enableProfiling();
        } else {
            $profilingStatus = $this->disableProfiling();
        }

        $themeCacheDirectory = _EPH_ALL_THEMES_DIR_ . $this->context->shop->theme_directory . '/cache/';

        if (((bool) Tools::getValue('EPH_CSS_THEME_CACHE') || (bool) Tools::getValue('EPH_JS_THEME_CACHE')) && !is_writable($themeCacheDirectory)) {

            if (@file_exists($themeCacheDirectory) || !@mkdir($themeCacheDirectory, 0777, true)) {
                $this->errors[] = sprintf(Tools::displayError('To use Smart Cache directory %s must be writable.'), realpath($themeCacheDirectory));
            }

        }

        if ($tmp = (int) Tools::getValue('EPH_CSS_THEME_CACHE')) {
            $version = (int) Configuration::get('EPH_CCCCSS_VERSION');

            if (Configuration::get('EPH_CSS_THEME_CACHE') != $tmp) {
                Configuration::updateValue('EPH_CCCCSS_VERSION', ++$version);
            }

        }

        if ($tmp = (int) Tools::getValue('EPH_JS_THEME_CACHE')) {
            $version = (int) Configuration::get('EPH_CCCJS_VERSION');

            if (Configuration::get('EPH_JS_THEME_CACHE') != $tmp) {
                Configuration::updateValue('EPH_CCCJS_VERSION', ++$version);
            }

        }

        Configuration::updateValue('EPH_CSS_BACKOFFICE_CACHE', (int) Tools::getValue('EPH_CSS_BACKOFFICE_CACHE'));
        Configuration::updateValue('EPH_JS_BACKOFFICE_CACHE', (int) Tools::getValue('EPH_JS_BACKOFFICE_CACHE'));
        Configuration::updateValue('EPH_JS_HTML_BACKOFFICE_COMPRESSION', (int) Tools::getValue('EPH_JS_HTML_BACKOFFICE_COMPRESSION'));
        Configuration::updateValue('EPH_JS_BACKOFFICE_DEFER', (int) Tools::getValue('EPH_JS_BACKOFFICE_DEFER'));

        if (!Configuration::updateValue('EPH_CSS_THEME_CACHE', (int) Tools::getValue('EPH_CSS_THEME_CACHE')) ||
            !Configuration::updateValue('EPH_JS_THEME_CACHE', (int) Tools::getValue('EPH_JS_THEME_CACHE')) ||
            !Configuration::updateValue('EPH_JS_HTML_THEME_COMPRESSION', (int) Tools::getValue('EPH_JS_HTML_THEME_COMPRESSION')) ||
            !Configuration::updateValue('EPH_JS_DEFER', (int) Tools::getValue('EPH_JS_DEFER')) ||
            !Configuration::updateValue('EPH_KEEP_CCC_FILES', (int) Tools::getValue('EPH_KEEP_CCC_FILES')) ||
            !Configuration::updateValue('EPH_HTACCESS_CACHE_CONTROL', (int) Tools::getValue('EPH_HTACCESS_CACHE_CONTROL'))
        ) {
            $this->errors[] = Tools::displayError('Unknown error.');
        } else {

            if (Configuration::get('EPH_HTACCESS_CACHE_CONTROL')) {

                if (is_writable(_EPH_ROOT_DIR_ . '/.htaccess')) {
                    Tools::generateHtaccess();
                } else {
                    $message = $this->l('Before being able to use this tool, you need to:');
                    $message .= '<br />- ' . $this->l('Create a blank .htaccess in your root directory.');
                    $message .= '<br />- ' . $this->l('Give it write permissions (CHMOD 666 on Unix system).');
                    $this->errors[] = Tools::displayError($message, false);
                    Configuration::updateValue('EPH_HTACCESS_CACHE_CONTROL', false);
                }

            }

        }

        $cacheActive = (bool) Tools::getValue('EPH_CACHE_ENABLED');

        if ($cachingSystem = preg_replace('[^a-zA-Z0-9]', '', Tools::getValue('EPH_CACHE_SYSTEM'))) {
            Configuration::updateGlobalValue('EPH_CACHE_SYSTEM', $cachingSystem);
        }

        Configuration::updateGlobalValue('EPH_CACHE_ENABLED', $cacheActive);

        if ($cacheActive) {

            if ($cachingSystem == 'CacheMemcache' && !extension_loaded('memcache')) {
                $this->errors[] = Tools::displayError('To use Memcached, you must install the Memcache PECL extension on your server.') . '
                            <a href="http://www.php.net/manual/en/memcache.installation.php">http://www.php.net/manual/en/memcache.installation.php</a>';
            } else

            if ($cachingSystem == 'CacheMemcached' && !extension_loaded('memcached')) {
                $this->errors[] = Tools::displayError('To use Memcached, you must install the Memcached PECL extension on your server.') . '
                            <a href="http://www.php.net/manual/en/memcached.installation.php">http://www.php.net/manual/en/memcached.installation.php</a>';
            } else

            if ($cachingSystem == 'CacheApc' && !extension_loaded('apc') && !extension_loaded('apcu')) {
                $this->errors[] = Tools::displayError('To use APC cache, you must install the APC PECL extension on your server.') . '
                            <a href="http://fr.php.net/manual/fr/apc.installation.php">http://fr.php.net/manual/fr/apc.installation.php</a>';
            } else

            if ($cachingSystem == 'CacheXcache' && !extension_loaded('xcache')) {
                $this->errors[] = Tools::displayError('To use Xcache, you must install the Xcache extension on your server.') . '
                            <a href="http://xcache.lighttpd.net">http://xcache.lighttpd.net</a>';
            } else

            if ($cachingSystem == 'CacheRedis' && !extension_loaded('redis')) {
                $this->errors[] = Tools::displayError('To use Redis, you must install the Redis extension on your server.') . '
                            <a href="https://pecl.php.net/package/redis">https://pecl.php.net/package/redis</a>';
            } else

            if ($cachingSystem == 'CacheXcache' && !ini_get('xcache.var_size')) {
                $this->errors[] = Tools::displayError('To use Xcache, you must configure "xcache.var_size" for the Xcache extension (recommended value 16M to 64M).') . '
                            <a href="http://xcache.lighttpd.net/wiki/XcacheIni">http://xcache.lighttpd.net/wiki/XcacheIni</a>';
            } else

            if ($cachingSystem == 'CacheFs') {

                if (!is_dir(_EPH_CACHEFS_DIRECTORY_)) {
                    @mkdir(_EPH_CACHEFS_DIRECTORY_, 0777, true);
                } else

                if (!is_writable(_EPH_CACHEFS_DIRECTORY_)) {
                    $this->errors[] = sprintf(Tools::displayError('To use CacheFS, the directory %s must be writable.'), realpath(_EPH_CACHEFS_DIRECTORY_));
                }

            }

            $cacheEnabled = Cache::isEnabled();
            $cacheSystem = Configuration::get('EPH_CACHE_SYSTEM');

            if ($cachingSystem == 'CacheFs') {

                if (!($depth = Tools::getValue('EPH_cache_fs_directory_depth'))) {
                    $this->errors[] = Tools::displayError('Please set a directory depth.');
                }

                if (!count($this->errors)) {
                    CacheFs::deleteCacheDirectory();
                    CacheFs::createCacheDirectories((int) $depth);
                    Configuration::updateValue('EPH_CACHEFS_DIRECTORY_DEPTH', (int) $depth);
                }

            } else

            if ($cachingSystem == 'CacheMemcache' && !$cacheEnabled && $cacheSystem == 'CacheMemcache') {
                Cache::getInstance()->flush();
            } else

            if ($cachingSystem == 'CacheMemcached' && !$cacheEnabled && $cachingSystem == 'CacheMemcached') {
                Cache::getInstance()->flush();
            } else

            if ($cachingSystem == 'CacheRedis' && !$cacheEnabled && $cacheSystem == 'CacheRedis') {
                Cache::getInstance()->flush();
            }

        }

        Configuration::updateValue('EPH_PAGE_CACHE_ENABLED', (bool) Tools::getValue('EPH_PAGE_CACHE_ENABLED'));
        Configuration::updateValue('EPH_PAGE_CACHE_DEBUG', (bool) Tools::getValue('EPH_PAGE_CACHE_DEBUG'));
        Configuration::updateValue('EPH_PAGE_CACHE_IGNOREPARAMS', Tools::getValue('EPH_PAGE_CACHE_IGNOREPARAMS'));
        Configuration::updateValue('EPH_PAGE_CACHE_CONTROLLERS', json_encode(array_map('trim', explode(',', Tools::getValue('EPH_PAGE_CACHE_CONTROLLERS')))));

        if (count($this->errors)) {
            $result = [
                'success' => false,
                'message' => implode(PHP_EOL, $this->errors),
            ];
        } else {
            $result = [
                'success' => true,
                'message' => 'Les paramètres de performances ont été mis à jour avec succès',
            ];
        }

        die(Tools::jsonEncode($result));
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function postProcess() {

        /* PhenyxShop demo mode */

        parent::postProcess();

    }

    /**
     * @param $fileList
     * @param $idShop
     *
     * @return string
     */
    public function displayControllerList($fileList, $idShop) {

        if (!is_array($fileList)) {
            $fileList = ($fileList) ? [$fileList] : [];
        }

        $content = '<p><input type="text" name="EPH_PAGE_CACHE_CONTROLLERS" value="' . implode(', ', $fileList) . '" id="em_text_' . $idShop . '" placeholder="' . $this->l('E.g. address, addresses, attachment') . '"/></p>';

        if ($idShop) {
            $shop = new Shop($idShop);
            $content .= ' (' . $shop->name . ')';
        }

        $content .= '<p>
                    <select size="25" id="em_list_' . $idShop . '" multiple="multiple">
                    <option disabled="disabled">' . $this->l('___________ CUSTOM ___________') . '</option>';

        // @todo do something better with controllers
        $controllers = Performer::getControllers(_EPH_FRONT_CONTROLLER_DIR_);
        ksort($controllers);

        foreach ($fileList as $k => $v) {

            if (!array_key_exists($v, $controllers)) {
                $content .= '<option value="' . $v . '">' . $v . '</option>';
            }

        }

        $content .= '<option disabled="disabled">' . $this->l('____________ CORE ____________') . '</option>';

        foreach ($controllers as $k => $v) {
            $content .= '<option value="' . $k . '">' . $k . '</option>';
        }

        $modulesControllersType = ['admin' => $this->l('Admin modules controller'), 'front' => $this->l('Front modules controller')];

        foreach ($modulesControllersType as $type => $label) {
            $content .= '<option disabled="disabled">____________ ' . $label . ' ____________</option>';
            $allModulesControllers = Performer::getModuleControllers($type);

            foreach ($allModulesControllers as $module => $modulesControllers) {

                foreach ($modulesControllers as $cont) {
                    $content .= '<option value="module-' . $module . '-' . $cont . '">module-' . $module . '-' . $cont . '</option>';
                }

            }

        }

        $content .= '</select>
                    </p>';

        return $content;
    }

    /**
     * Is Debug Mode enabled?
     *
     * @return bool Whether debug mode is enabled
     */
    public function isDebugModeEnabled() {

        // Always try the custom defines file first
        $definesClean = '';

        $m = [];

        if (!preg_match('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean, $m)) {
            $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

            if (!preg_match('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean, $m)) {
                return false;
            }

        }

        if (mb_strtolower($m[1]) === 'true') {
            return true;
        }

        return false;
    }

    /**
     * Is profiling enabled?
     *
     * @return bool Whether profiling is enabled
     */
    public function isProfilingEnabled() {

        // Always try the custom defines file first
        $definesClean = '';

        $m = [];

        if (!preg_match('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', $definesClean, $m)) {
            $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

            if (!preg_match('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', $definesClean, $m)) {
                return false;
            }

        }

        if (mb_strtolower($m[1]) === 'true') {
            return true;
        }

        return false;
    }

    /**
     * Check read permission on defines.inc.php
     *
     * @param bool $custom Whether the custom defines file should be used
     *
     * @return bool Whether the file can be read
     */
    public function isDefinesReadable() {

        return is_readable(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
    }

    /**
     * Enable debug mode
     *
     * @return int Whether changing debug mode succeeded or error code
     */
    public function enableDebugMode() {

        // Check custom defines file first

        if (!$this->isDefinesReadable()) {
            return static::DEBUG_MODE_ERROR_NO_READ_ACCESS;
        }

        $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        $defines = file_get_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

        if (!preg_match('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean)) {
            return static::DEBUG_MODE_ERROR_NO_DEFINITION_FOUND;
        }

        $defines = preg_replace('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', 'define(\'_EPH_MODE_DEV_\', true);', $defines);

        if (!@file_put_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php', $defines)) {
            return static::DEBUG_MODE_ERROR_NO_WRITE_ACCESS;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        }

        return static::DEBUG_MODE_SUCCEEDED;
    }

    /**
     * Disable debug mode
     *
     * @return int Whether changing debug mode succeeded or error code
     */
    public function disableDebugMode() {

        // Check custom defines file first

        if (!$this->isDefinesReadable()) {
            return static::DEBUG_MODE_ERROR_NO_READ_ACCESS;
        }

        $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        $defines = file_get_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

        if (!preg_match('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean)) {
            return static::DEBUG_MODE_ERROR_NO_DEFINITION_FOUND;
        }

        $defines = preg_replace('/define\(\'_EPH_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', 'define(\'_EPH_MODE_DEV_\', false);', $defines);

        if (!@file_put_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php', $defines)) {
            return static::DEBUG_MODE_ERROR_NO_WRITE_ACCESS;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        }

        return static::DEBUG_MODE_SUCCEEDED;
    }

    /**
     * Enable profiling
     *
     * @return int Whether changing profiling succeeded or error code
     */
    public function enableProfiling() {

        // Check custom defines file first

        if (!$this->isDefinesReadable()) {
            return static::PROFILING_ERROR_NO_READ_ACCESS;
        }

        $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        $defines = file_get_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

        if (!preg_match('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', $definesClean)) {
            return static::PROFILING_ERROR_NO_DEFINITION_FOUND;
        }

        $defines = preg_replace('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', 'define(\'_EPH_DEBUG_PROFILING_\', true);', $defines);

        if (!@file_put_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php', $defines)) {
            return static::PROFILING_ERROR_NO_WRITE_ACCESS;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        }

        return static::PROFILING_SUCCEEDED;
    }

    /**
     * Disable profiling
     *
     * @return int Whether changing profiling succeeded or error code
     */
    public function disableProfiling() {

        // Check custom defines file first

        if (!$this->isDefinesReadable()) {
            return static::PROFILING_ERROR_NO_READ_ACCESS;
        }

        $definesClean = php_strip_whitespace(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        $defines = file_get_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php');

        if (!preg_match('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', $definesClean)) {
            return static::PROFILING_ERROR_NO_DEFINITION_FOUND;
        }

        $defines = preg_replace('/define\(\'_EPH_DEBUG_PROFILING_\', ([a-zA-Z]+)\);/Ui', 'define(\'_EPH_DEBUG_PROFILING_\', false);', $defines);

        if (!@file_put_contents(_EPH_ROOT_DIR_ . '/app/defines.inc.php', $defines)) {
            return static::PROFILING_ERROR_NO_WRITE_ACCESS;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_EPH_ROOT_DIR_ . '/app/defines.inc.php');
        }

        return static::PROFILING_SUCCEEDED;
    }

    /**
     * @since 1.9.1.0
     */
    public function displayAjaxTestMemcachedServer() {

        if (_EPH_MODE_DEMO_) {
            die(Tools::displayError('This functionality has been disabled.'));
        }

        if (Tools::isSubmit('action') && Tools::getValue('action') == 'test_memcached_server') {
            $host = pSQL(Tools::getValue('sHost', ''));
            $port = (int) Tools::getValue('sPort', 0);
            $type = Tools::getValue('type', '');

            if ($host != '' && $port != 0) {
                $res = 0;

                if ($type == 'memcached') {

                    if (extension_loaded('memcached') &&
                        @fsockopen($host, $port)
                    ) {
                        $memcache = new Memcached();
                        $memcache->addServer($host, $port);
                        $res = in_array('255.255.255', $memcache->getVersion(), true) === false;
                    }

                } else {

                    if (function_exists('memcache_get_server_status') &&
                        function_exists('memcache_connect') &&
                        @fsockopen($host, $port)
                    ) {
                        $memcache = @memcache_connect($host, $port);
                        $res = @memcache_get_server_status($memcache, $host, $port);
                    }

                }

                $this->ajaxDie(json_encode([$res]));
            }

        }

        die;
    }

    public function ajaxProcessAddRedisServer() {

        $host = pSQL(Tools::getValue('sHost', ''));
        $port = (int) Tools::getValue('sPort', 0);
        $auth = pSQL(Tools::getValue('sAuth', ''));
        $db = (int) Tools::getValue('sDb', 0);

        CacheRedis::addServer(pSQL($host), (int) $port, pSQL($auth), (int) $db);

        $result = [
            'success' => true,
            'message' => 'Le serveur Rédis a été ajouté avec succès',
            'idRedis' => CacheRedis::getLastRedisServer(),
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessDeleteRedisServer() {

        CacheRedis::deleteServer((int) Tools::getValue('idRedis'));
        die(true);
    }

    /**
     * Perform a short test to see if Redis is enabled
     * and return the result through ajax
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessTestRedisServer() {

        if (_EPH_MODE_DEMO_) {
            die(Tools::displayError('This functionality has been disabled.'));
        }

        $host = pSQL(Tools::getValue('sHost', ''));
        $port = (int) Tools::getValue('sPort', 0);
        $auth = pSQL(Tools::getValue('sAuth', ''));
        $db = (int) Tools::getValue('sDb', 0);

        if ($host != '' && $port != 0) {

            $res = 0;

            if (extension_loaded('redis')) {
                try {
                    $redis = new Redis();

                    if ($redis->connect($host, $port)) {
                        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

                        if (!empty($auth)) {

                            if (!($redis->auth($auth))) {
                                $this->ajaxDie(json_encode([0]));
                            }

                        }

                        $redis->select($db);

                        $res = $redis->ping() ? 1 : 0;
                    }

                } catch (Exception $e) {
                    $this->ajaxDie(json_encode([0]));
                }

            }

            $this->ajaxDie(json_encode([$res]));

        }

        die;
    }

    /**
     * Process dynamic hook setting
     */
    public function displayAjaxUpdateDynamicHooks() {

        $idModule = (int) Tools::getValue('idModule');
        $status = Tools::getValue('status') === 'true';
        $hookName = Tools::getValue('hookName');
        $idHook = Hook::getIdByName($hookName);
        $this->ajaxDie(json_encode([
            'success' => PageCache::setHookCacheStatus($idModule, $idHook, $status),
        ]));
    }

}
