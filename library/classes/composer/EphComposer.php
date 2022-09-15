<?php

use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class EphComposerCore extends PhenyxObjectModel {

    protected static $_base_map = [];
    protected static $sc = [];
    protected static $carousel_index = 1;
	protected static $_cache_user_shortcodes;
	protected static $_get_shortcodes;
    protected $shortcodes = [];
    public $mapsbaseItems = [];
    public $hookContent = [];
    public $composer_settings;
    public $postCustomCss = [];
    public $customUserTemplatesDir = false;
    public $front_js = [];
    public $front_css = [];
    protected $default_templates = false;
    public static $_url;
    public static $instance;
    public static $shortcode_tags = [];
    public static $staticShortcodeTags = [];
    public static $staticShortcodeHandler;
    public static $sds_current_hook;
    public static $sds_action_hooks = [];
    public static $front_content_scripts = [];
    public static $backOfficeCalledFor = 0;
    public static $EPHBackofficeShortcodesAction = [];
    public static $vc_translations = [];
    public $vccawobj;
    public $vctcbj;
    public $vcmain;
    public $vc_base;
    public $vc_mapper;
    public $vc_map;
    public $vc_automapper;

    public static $registeredCSS = [];
    public static $registeredJS = [];
    public static $front_editor_actions = [];
    public $ajaxController;
    public $image_sizes = [];
    public $image_sizes_dropdown = [];
    public $mode = 'admin_page';
    public $factory = [];
    public $brand_url = 'http://vc.wpbakery.com/?utm_campaign=VCplugin_header&utm_source=vc_user&utm_medium=backend_editor';
    public $css_class = 'vc_navbar';
    public $controls_filter_name = 'vc_nav_controls';
    public $smarty;
    private static $isEphAdminCustomController;
    private static $vcBackofficePageIndenfiers;
    public static $vcCustomPageType;
    public static $vcCustomPageId;
    public static $vc_mode_name;
    public $configuration;

    public $vc_row_layouts = [];

    public static $modules_list = [];

    public $seetings_maps = [];

    public $support_hooks = [];

    public $shortcodeHandler;

    public $base;
    public $id_composer_category;
    public $class;
    public $content_element;
    public $type;
    public $is_container;
    public $weight;
    public $show_settings_on_create;
    public $wrapper_class;
    public $allowed_container_element;
    public $controls;
    public $custom_markup;
    public $default_content;
    public $js_view;
    public $active;
    public $name;
    public $description;
    public $params;

    public static $definition = [
        'table'     => 'composer_map',
        'primary'   => 'id_composer_map',
        'multilang' => true,
        'fields'    => [
            'base'                      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'id_composer_category'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'class'                     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'type'                      => ['type' => self::TYPE_STRING],
            'content_element'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'is_container'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'icon'                      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'weight'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'show_settings_on_create'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'wrapper_class'             => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'allowed_container_element' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'controls'                  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'custom_markup'             => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'default_content'           => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'js_view'                   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'active'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            /* Lang fields */
            'name'                      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
            'description'               => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
        ],
    ];

    public function __construct($id = null, $idLang = null) {

        global $globalShortcodeHandler;
		
		parent::__construct($id, $idLang);

        if ($this->id) {

            $this->params = $this->getShortCodeParam();
        }

        $this->vc_row_layouts = [
            ['cells' => '11', 'mask' => '12', 'title' => '1/1', 'icon_class' => 'l_11'],
            ['cells' => '12_12', 'mask' => '26', 'title' => '1/2 + 1/2', 'icon_class' => 'l_12_12'],
            ['cells' => '23_13', 'mask' => '29', 'title' => '2/3 + 1/3', 'icon_class' => 'l_23_13'],
            ['cells' => '13_13_13', 'mask' => '312', 'title' => '1/3 + 1/3 + 1/3', 'icon_class' => 'l_13_13_13'],
            ['cells' => '14_14_14_14', 'mask' => '420', 'title' => '1/4 + 1/4 + 1/4 + 1/4', 'icon_class' => 'l_14_14_14_14'],
            ['cells' => '14_34', 'mask' => '212', 'title' => '1/4 + 3/4', 'icon_class' => 'l_14_34'],
            ['cells' => '14_12_14', 'mask' => '313', 'title' => '1/4 + 1/2 + 1/4', 'icon_class' => 'l_14_12_14'],
            ['cells' => '56_16', 'mask' => '218', 'title' => '5/6 + 1/6', 'icon_class' => 'l_56_16'],
            ['cells' => '16_16_16_16_16_16', 'mask' => '642', 'title' => '1/6 + 1/6 + 1/6 + 1/6 + 1/6 + 1/6', 'icon_class' => 'l_16_16_16_16_16_16'],
            ['cells' => '16_23_16', 'mask' => '319', 'title' => '1/6 + 4/6 + 1/6', 'icon_class' => 'l_16_46_16'],
            ['cells' => '16_16_16_12', 'mask' => '424', 'title' => '1/6 + 1/6 + 1/6 + 1/2', 'icon_class' => 'l_16_16_16_12'],
        ];

        $this->modules_list = [
            'blockbanner',
            'blockbestsellers',
            'blockcart',
            'blockcategories',
            'blockcms',
            'blockcmsinfo',
            'blockcontact',
            'blockcontactinfos',
            'blockcurrencies',
            'blockfacebook',
            'blocklanguages',
            'blocklayered',
            'blockmanufacturer',
            'blockmyaccount',
            'blockmyaccountfooter',
            'blocknewproducts',
            'blocknewsletter',
            'blockpaymentlogo',
            'blocksearch',
            'blocksocial',
            'blockspecials',
            'blockstore',
            'blocksupplier',
            'blocktags',
            'blocktopmenu',
            'blockuserinfo',
            'blockviewed',
            'blockwishlist',
            'graphnvd3',
            'gridhtml',
            'homefeatured',
            'homeslider',
            'productcomments',
            'productpaymentlogos',
            'sendtoafriend',
            'socialsharing',
        ];

        $this->support_hooks = ['Footer', 'displayFooterProduct', 'Home', 'LeftColumn', 'displayLeftColumnProduct', 'MyAccountBlock', 'RightColumn', 'displayRightColumnProduct', 'Top', 'displayBanner', 'displayFooter', 'displayFooterProduct', 'displayHome', 'HomeTabContent', 'displayLeftColumn', 'displayLeftColumnProduct', 'displayMaintenance', 'displayMyAccountBlock', 'displayMyAccountBlockfooter', 'displayNav', 'displayRightColumn', 'displayRightColumnProduct', 'displayTop', 'displayProductContent', 'displaySmartBlogLeft', 'displaySidearea', 'displayFooterTop', 'displayTopColumn', 'displaySmartBlogRight'];

       
       

        $this->configuration = [
            'cmscontent'        => [
                'type'                     => 'core',
                'shortname'                => 'cms',
                'controller'               => 'AdminCmsContent',
                'context_controller'       => 'cms',
                'dbtable'                  => 'cms',
                'identifier'               => 'id_cms',
                'field'                    => 'content',
                'composer_frontend_status' => 1,
                'composer_backend_status'  => 1,
                'composer_frontend_enable' => 1,
            ],
            'categories'        => [
                'type'                     => 'core',
                'shortname'                => '',
                'controller'               => 'AdminCategories',
                'context_controller'       => 'category',
                'dbtable'                  => 'category',
                'identifier'               => 'id_category',
                'field'                    => 'description',
                'composer_frontend_status' => 1,
                'composer_backend_status'  => 1,
                'composer_frontend_enable' => 1,
            ],
            'products'          => [
                'type'                     => 'core',
                'shortname'                => 'vctc',
                'controller'               => 'AdminProducts',
                'context_controller'       => 'product',
                'dbtable'                  => 'product',
                'identifier'               => 'id_product',
                'field'                    => 'description',
                'composer_frontend_status' => 1,
                'composer_backend_status'  => 1,
                'composer_frontend_enable' => 1,
            ],
            'manufacturers'     => [
                'type'                     => 'core',
                'shortname'                => '',
                'controller'               => 'AdminManufacturers',
                'context_controller'       => 'manufacturer',
                'dbtable'                  => 'manufacturer',
                'identifier'               => 'id_manufacturer',
                'field'                    => 'description',
                'composer_frontend_status' => 1,
                'composer_backend_status'  => 1,
                'composer_frontend_enable' => 1,
            ],
            'vccontentanywhere' => [
                'type'                     => 'core',
                'shortname'                => 'vccaw',
                'controller'               => 'AdminContentanywhere',
                'dbtable'                  => 'vccontentanywhere',
                'identifier'               => 'id_vccontentanywhere',
                'field'                    => 'content',
                'composer_frontend_status' => 1, 0,
                'composer_backend_status'  => 1,
                'composer_frontend_enable' => 0,
            ],
        ];

        $this->seetings_maps = $this->builMaps();

        $globalShortcodeHandler = self::$staticShortcodeHandler = $this->shortcodeHandler = $this->buildHandler();
        $this->buildShortCodeTag();

    }
	
	public function getUserShortCodes() {
		
		if (!isset(static::$_cache_user_shortcodes)) {
			$seetingsMaps = $this->seetings_maps;
		
			$cleanArray = ['values', 'tpl'];
			foreach($seetingsMaps as $key => &$seetings) {
			
				unset($seetings['type']);
				foreach($seetings['params'] as &$params) {
					foreach($params as $key => $value) {
						if(in_array($key, $cleanArray)) {
							unset($params[$key]);
						}
					}
				}
			}
			static::$_cache_user_shortcodes = Tools::jsonEncode($seetingsMaps);
			return static::$_cache_user_shortcodes;
		}
		return static::$_cache_user_shortcodes;
		
	}
	
	public function getShortCodes() {
		
		if (!isset(static::$_get_shortcodes)) {
			$seetingsMaps = $this->seetings_maps;
			$cleanArray = ['values', 'tpl'];
			foreach($seetingsMaps as $key => &$seetings) {
			
				$seetings['_category_ids'] = md5($seetings['category']);
				unset($seetings['type']);
				foreach($seetings['params'] as &$params) {
					foreach($params as $key => $value) {
						if(in_array($key, $cleanArray)) {
							unset($params[$key]);
						}
					}
				}
			}
			static::$_get_shortcodes = Tools::jsonEncode($seetingsMaps);
			return static::$_get_shortcodes;
		}
		return static::$_get_shortcodes;
		
	}

    public static function getElementBase($base) {

        $context = Context::getContext();
		
        $seetings = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('cm.*, cml.*, ccl.`name` as `category`')
                ->from('composer_map', 'cm')
                ->leftJoin('composer_map_lang', 'cml', 'cml.`id_composer_map` = cm.`id_composer_map` AND cml.`id_lang` = ' . $context->language->id)
                ->leftJoin('composer_category_lang', 'ccl', 'ccl.`id_composer_category` = cm.`id_composer_category` AND ccl.`id_lang` = ' . $context->language->id)
                ->where('cm.`base` LIKE \'' . $base . '\'')
        );

        foreach ($seetings as $key => $value) {

            if (empty($value)) {
                unset($seetings[$key]);
            }

        }

        $params = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cpt.`value`as `type`, cml.heading, cmp.*, cml.description, cml.param_group as `group` ')
                ->from('composer_map_params', 'cmp')
                ->leftJoin('composer_map_params_lang', 'cml', 'cml.`id_composer_map_params` = cmp.`id_composer_map_params` AND cml.`id_lang` = ' . $context->language->id)
                ->leftJoin('composer_param_type', 'cpt', 'cpt.`id_composer_param_type` = cmp.`id_type`')
                ->where('cmp.`id_composer_map` = ' . $seetings['id_composer_map'])
        );

        foreach ($params as &$param) {

            foreach ($param as $key => $value) {

                if (empty($value)) {
                    unset($param[$key]);
                }
				unset($param['id_type']);

            }
			
			if($param['param_name'] == 'img_size') {
				$param['values'] = EphComposer::getComposerImageTypes();
			} else {
				$values = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
					->select('cv.`value_key`, cvl.`name`')
					->from('composer_value', 'cv')
                	->leftJoin('composer_value_lang', 'cvl', 'cvl.`id_composer_value` = cv.`id_composer_value` AND cvl.`id_lang` = ' . $context->language->id)
                	->where('cv.`id_composer_map_params` = ' . $param['id_composer_map_params'])
				);
				$param['values'] = $values;
				
			}
			

        }

        if (!empty($params)) {

            foreach ($params as &$param) {

                if (!empty($param['dependency'])) {
                    $param['dependency'] = Tools::jsonDecode($param['dependency'], true);
                }

                if (!empty($param['settings'])) {
                    $param['settings'] = Tools::jsonDecode($param['settings'], true);
                }

                if (!empty($param['value']) && $param['param_name'] != 'content') {
                    $param['value'] = Tools::jsonDecode($param['value'], true);
                }

                unset($param['id_composer_map']);
                unset($param['id_composer_map_params']);
                unset($param['position']);

            }

        }

        unset($seetings['id_composer_map']);
        unset($seetings['id_lang']);
        $seetings['params'] = $params;

        return $seetings;

    }
	
	
	public static function getComposerImageTypes() {
		
		 $images_types = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('vc_image_type')
                ->orderBy('`name` ASC')
        );
		$values = [];
		$values[] = [
			'value_key' => '',
			'name' => ''
		];
		
		foreach($images_types as $type) {
			$values[] = [
				'value_key' => $type['name'],
				'name' =>  $type['name']
			];
			
		}

		return $values;
	}

    public static function getMapsbaseItems() {

        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`base`')
                ->from('composer_map')
        );
        $base = [];

        foreach ($db_results as $key => $value) {
            $base[] = $value['base'];
        }

        return $base;

    }

    public static function getDefaultTemplates($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('ct.`image_path`, ct.`content`, ctl.`name`')
                ->from('composer_template', 'ct')
                ->leftJoin('composer_template_lang', 'ctl', 'ctl.`id_composer_template` = ct.`id_composer_template` AND ctl.`id_lang` = ' . (int) $idLang)
        );

    }

    public static function getMapsItems($idLang) {

        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`base`, c.`icon`, c.`is_container`, cml.`name`, ccl.`name` as `category`, cml.`description`')
                ->from('composer_map', 'c')
                ->leftJoin('composer_map_lang', 'cml', 'cml.`id_composer_map` = c.`id_composer_map` AND cml.`id_lang` = ' . (int) $idLang)
                ->leftJoin('composer_category_lang', 'ccl', 'ccl.`id_composer_category` = c.`id_composer_category` AND ccl.`id_lang` = ' . (int) $idLang)
                ->where('c.`content_element` = 1')
        );

        $items = [];

        foreach ($db_results as $key => $value) {

            $result = [];
            $result['name'] = $value['name'];
            $result['base'] = $value['base'];
            $result['category'] = $value['category'];
            $result['is_container'] = $value['is_container'];
            $result['icon'] = $value['icon'];
            $result['description'] = $value['description'];
            $items[] = $result;

        }

        return $items;
    }

    public static function getMapsModules($items) {

        $seetingModules = EphComposer::getAllModules();
        $context = Context::getcontext();

        foreach ($seetingModules as $module) {

            if (Module::isInstalled($module) && Module::isEnabled($module)) {

                $instance = Module::getInstanceByName($module);
                $result = [];
                $result['name'] = $instance->name;
                $result['base'] = 'vc_' . $module;
                $result['category'] = 'Modules';
                $result['icon'] = $context->company->getBaseURL() . 'plugins/' . $module . '/logo.png';
                $result['description'] = '';
                $items[] = $result;
            }

        }

        return $items;
    }

    public static function getAllModules() {

        $modules_list = self::$modules_list;

        $includes = Configuration::get('vc_include_modules');
        $excludes = Configuration::get('vc_exclude_modules');

        if (!empty($includes)) {
            $includes = explode("\n", $includes);

            foreach ($includes as $include) {
                $include = trim($include);

                if (Validate::isModuleName($include) && !in_array($include, $modules_list)) {
                    $modules_list[] = $include;
                }

            }

        }

        if (!empty($excludes)) {
            $excludes = explode("\n", $excludes);

            foreach ($excludes as $exclude) {
                $exclude = trim($exclude);

                if (Validate::isModuleName($exclude) && ($index = array_search($exclude, $modules_list)) !== FALSE) {
                    unset($modules_list[$index]);
                }

            }

        }

        return $modules_list;
    }

    public function getAllFilterModules() {

        $results = [];

        $AllModules = $this->GetAllModules();

        if (isset($AllModules) && !empty($AllModules)) {
            $i = 0;

            foreach ($AllModules as $mod) {

                if ($this->getModuleHooks($mod)) {
                    $results[$i]['id'] = $mod;
                    $results[$i]['name'] = Module::getModuleName($mod);
                    $i++;
                }

            }

        }

        return $results;
    }

    public function getShortCodeParam() {

        $collections = [];
        $params = Db::getInstance()->executeS('SELECT `id_composer_map_params` FROM `' . _DB_PREFIX_ . 'composer_map_params` WHERE `id_composer_map` = ' . (int) $this->id);

        foreach ($params as $param) {
            $collections[] = new EphParamMap($param['id_composer_map_params']);
        }

        return $collections;
    }

    public function buildHandler() {

        $arrayExclude = ['vc_single_image', 'vc_gallery', 'vc_images_carousel'];

        $seetings = $this->builMaps();
        $handlers = new HandlerContainer();

        foreach ($seetings as $key => $value) {

            if (in_array($key, $arrayExclude)) {
                continue;
            }

            $handlers->add($key, function (ShortcodeInterface $s) {

                $args = [
                    'full_width',
                    'gap',
                    'columns_placement',
                    'full_height',
                    'equal_height',
                    'content_placement',
                    'parallax',
                    'font_color',

                ];

                $key = $s->getName();
                $value = $this->seetings_maps[$key];

                $class = $key . ' wb_' . $value['type'];

                if ($value['type'] == 'row') {
                    $class .= ' vc_row-fluid';
                }

                if ($value['type'] == 'column') {
                    $width = $this->translateColumnWidthToSpan($s->getParameter('width'));
                    $class .= ' ' . $width;
                }

                $el_id = $s->getParameter('el_id');

                if (!empty($el_id)) {
                    $el_id = 'id="' . $el_id . '" ';
                }

                $el_class = $s->getParameter('el_class');

                if (!empty($el_id)) {
                    $class .= ' ' . $el_class;
                }

                $css = $s->getParameter('css');

                if (!empty($css)) {
                    $classCss = explode('{', $css);
                    $css = '<style>' . $css . '</style>';
                    $class .= ' ' . $classCss[0];
                }

                $css_animation = $s->getParameter('css_animation');

                if (!empty($css_animation)) {
                    $css_animation = 'wpb_animate_when_almost_visible wpb_' . $css_animation;
                    $class .= ' ' . $css_animation;
                }

                $attribute = '';
                $option = [];

                foreach ($args as $arg) {
                    $option[$arg] = $s->getParameter($arg);

                    if (!empty($option[$arg])) {
                        $attribute .= 'data-vc-' . $arg . '="' . $option[$arg] . '" ';

                        if ($key == 'vc_row') {
                            $flex_row = false;
                            $full_height = false;

                            switch ($arg) {
                            case 'full_width':
                                $class .= ' data-vc-full-width="true" data-vc-full-width-init="false"';

                                if ('stretch_row_content' === $option[$arg]) {
                                    $attribute .= 'data-vc-stretch-content="true"';
                                } else

                                if ('stretch_row_content_no_spaces' === $option[$arg]) {
                                    $attribute .= 'data-vc-stretch-content="true"';
                                    $class .= ' vc_row-no-padding';
                                }

                                break;
                            case 'full_height':
                                $full_height = true;
                                $class .= ' vc_row-o-full-height';

                                break;
                            case 'equal_height':
                                $flex_row = true;
                                $class .= ' vc_row-o-equal-height';
                                break;
                            case 'content_placement':
                                $flex_row = true;
                                $class .= ' vc_row-o-content-' . $option[$arg];
                                break;
                            case 'columns_placement':
                                $classToAdd = ' vc_row-o-content-' . $option[$arg];
                                break;

                            }

                            if ($full_height && !empty($classToAdd)) {
                                $class .= $classToAdd;
                            }

                        }

                    }

                }

                $output = $css;
                $output .= '<div ' . $el_id . ' class="' . $class . '" ' . $attribute . '>';

                if ($key != 'vc_row') {
                    $output .= '<div class="wpb_wrapper">';
                }

                $output .= $s->getContent();

                if ($key != 'vc_row') {
                    $output .= '</div>';
                }

                $output .= '</div>';
                return $output;
            });

        }

        $handlers->add('vc_single_image', function (ShortcodeInterface $s) {

            $args = [
                'full_width',
                'gap',
                'columns_placement',
                'full_height',
                'equal_height',
                'content_placement',
                'parallax',
                'font_color',

            ];

            $class = 'wpb_single_image wpb_content_element';

            $el_id = $s->getParameter('el_id');

            if (!empty($el_id)) {
                $el_id = 'id="' . $el_id . '" ';
            }

            $el_class = $s->getParameter('el_class');

            if (!empty($el_id)) {
                $class .= ' ' . $el_class;
            }

            $css = $s->getParameter('css');

            if (!empty($css)) {
                $classCss = explode('{', $css);
                $css = '<style>' . $css . '</style>';
                $class .= ' ' . $classCss[0];
            }

            $css_animation = $s->getParameter('css_animation');

            if (!empty($css_animation)) {
                $css_animation = 'wpb_animate_when_almost_visible wpb_' . $css_animation;
                $class .= ' ' . $css_animation;
            }

            $attribute = '';
            $option = [];

            foreach ($args as $arg) {
                $option[$arg] = $s->getParameter($arg);

                if (!empty($option[$arg])) {
                    $attribute .= 'data-vc-' . $arg . '="' . $option[$arg] . '" ';
                }

            }

            $alignement = 'vc_align_left';
            $align_key = $s->getParameter('alignment');

            if (!empty($align_key)) {
                $alignement = 'vc_align_' . $align_key;

            }

            $class .= ' ' . $alignement;

            $image = $s->getParameter('image');

            if (is_string($image)) {
                $image = [$image];
            }

            $imageLinks = EphComposer::fieldAttachedImages($image);
            $borderClass = 'vc_box_border_grey';
            $border_color = $s->getParameter('border_color');

            if (!empty($border_color)) {
                $borderClass = 'vc_box_border_' . $border_color;
            }

            $output = $css;
            $output .= '<div ' . $el_id . ' class="' . $class . '" ' . $attribute . '><div class="wpb_wrapper">';

            foreach ($imageLinks as $src) {
                $output .= '<img class="' . $borderClass . '" alt="" src="' . $src . '">';
            }

            $output .= '</div></div>';

            return $output;

        });

        $handlers->add('vc_gallery', function (ShortcodeInterface $s) {

            $args = [
                'full_width',
                'gap',
                'columns_placement',
                'full_height',
                'equal_height',
                'content_placement',
                'parallax',
                'font_color',

            ];

            $class = 'wpb_gallery wpb_content_element vc_clearfix';

            $el_id = $s->getParameter('el_id');

            if (!empty($el_id)) {
                $el_id = 'id="' . $el_id . '" ';
            }

            $el_class = $s->getParameter('el_class');

            if (!empty($el_id)) {
                $class .= ' ' . $el_class;
            }

            $css = $s->getParameter('css');

            if (!empty($css)) {
                $classCss = explode('{', $css);
                $css = '<style>' . $css . '</style>';
                $class .= ' ' . $classCss[0];
            }

            $attribute = '';
            $option = [];

            foreach ($args as $arg) {
                $option[$arg] = $s->getParameter($arg);

                if (!empty($option[$arg])) {
                    $attribute .= 'data-vc-' . $arg . '="' . $option[$arg] . '" ';
                }

            }

            $img_size = $s->getParameter('img_size');

            $image = $s->getParameter('image');

            if (is_string($image)) {
                $image = [$image];
            }

            $imageLinks = EphComposer::fieldAttachedImages($image);

            $type = $s->getParameter('type');
            $interval = $s->getParameter('interval');

            $custom_links_target = $s->getParameter('custom_links_target');
            $eventclick = $s->getParameter('eventclick');

            $output = $css;
            $output .= '<div ' . $el_id . ' class="' . $class . '" ' . $attribute . '><div class="wpb_wrapper">';
            $output .= '<div class="wpb_gallery_slides wpb_flexslider ' . $type . ' flexslider" data-interval="' . $interval . '" data-flex_fx="fade">
                <ul class="slides">';

            foreach ($imageLinks as $src) {
                $output .= '<li>
                        <a class="prettyphoto" href="/plugins/jscomposer/uploads/Artistic-Putty-One-Colour-Application---YouTube-1080p-00_01_41_19-Still010.jpg" rel="prettyPhoto[rel-2064136127]">
                            <img class="" alt="" src="' . $src . '">
                        </a>
                    </li>';
                $output .= '<img class="" alt="" src="' . $src . '">';
            }

            $output .= '</ul></div>';
            $output .= '</div></div>';

            return $output;

        });

        $handlers->add('vc_images_carousel', function (ShortcodeInterface $s) {

            $class = 'vc_slide vc_images_carousel';
            $el_class = $s->getParameter('el_class');

            if (!empty($el_class)) {
                $class .= ' ' . $el_class;
            }

            $dataInterval = 'data-interval="' . $s->getParameter('speed') . '"';
            $wrapSize = 'data-wrap="false" style="width: 100%;"';
            $imgSize = null;
            $img_size = $s->getParameter('img_size');
            $tagSlideline = 'style="width: 400px;"';
            $tagvc_item = 'style="width: 50%; height: 205px;"';

            if ($img_size != 'default') {
                $imgSize = $img_size;
                $sliderWidth = $this->getSliderWidth($img_size);
                $wrapSize = 'data-wrap="true" style="width: ' . $sliderWidth . ';"';

            }

            $images = explode(",", $s->getParameter('images'));

            $imageLinks = EphComposer::fieldAttachedImages($images, $imgSize);

            $custom_links_target = 'target="' . $s->getParameter('custom_links_target') . '"';
            $aClass = 'class="prettyphoto"';
            $custom_links = $s->getParameter('custom_links');

            if (!empty($custom_links)) {
                $aClass = '';
            }

            $eventclick = $s->getParameter('eventclick');

            $slidesPerView = $s->getParameter('slides_per_view');

            $slides_per_view = 'data-per-view="' . $slidesPerView . '"';

            if ($slidesPerView > 1) {
                $class .= ' vc_per-view-more vc_per-view-' . $slidesPerView;
            }

            $dataMode = $s->getParameter('mode');

            if ($dataMode == 'vertical') {
                $class .= ' vc_carousel_' . $dataMode;

                if ($img_size != 'default') {
                    $sliderHeight = $this->getSliderHeight($img_size) + 2;
                    $tagvc_item = 'style="height: ' . $sliderHeight . 'px;"';
                    $heightSlideline = (count($images) + 1) * $sliderHeight;
                    $tagSlideline = 'style="height: ' . $heightSlideline . 'px;"';

                }

            }

            $dataMode = 'data-mode="' . $dataMode . '"';
            $class .= ' vc_build';

            $Idcarousel = 'vc_images-carousel-' . EphComposer::getCarouselIndex();

            $tag_autoplay = '';
            $tag_autoHigh = 'data-hide-on-end="true"';
            $autoplay = $s->getParameter('autoplay');

            if (!empty($autoplay) && $autoplay == 'yes') {
                $tag_autoplay = 'data-auto-height="yes"';
                $tag_autoHigh = 'data-hide-on-end="true"';
            }

            $partialView = 'data-partial="false"';
            $partial_view = $s->getParameter('partial_view');

            if (!empty($partial_view) && $partial_view == 'true') {
                $partialView = 'data-partial="true"';
            }

            $hide_pagination_control = $s->getParameter('hide_pagination_control');
            $hide_prev_next_buttons = $s->getParameter('hide_prev_next_buttons');

            $output = '<div class="wpb_images_carousel wpb_content_element vc_clearfix">';
            $output .= '<div class="wpb_wrapper">';
            $output .= '<div id="' . $Idcarousel . '" class="' . $class . '" data-ride="vc_carousel" ' . $wrapSize . ' ' . $dataInterval . ' ' . $tag_autoplay . ' ' . $dataMode . ' ' . $partialView . ' ' . $slides_per_view . ' ' . $tag_autoHigh . '>';

            if ($hide_pagination_control !== 'yes') {
                $output .= '<ol class="vc_carousel-indicators">';

                for ($z = 0; $z < count($imageLinks); $z++) {
                    $output .= '<li data-target="#' . $Idcarousel . '" data-slide-to="' . $z . '"></li>';
                }

                $output .= '</ol>';

            }

            $output .= '<div class="vc_carousel-inner">';
            $output .= '<div class="vc_carousel-slideline" ' . $tagSlideline . '>';
            $output .= '<div class="vc_carousel-slideline-inner">';

            foreach ($imageLinks as $src) {
                $output .= '<div class="vc_item" ' . $tagvc_item . '>';
                $output .= '<div class="vc_inner">';
                $output .= '<a ' . $aClass . ' href="' . $src . '" rel="prettyPhoto[rel-2064136127]" ' . $custom_links_target . '>';
                $output .= '<img src="' . $src . '">';
                $output .= '</a>';
                $output .= '</div>';
                $output .= '</div>';
            }

            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';

            if ($hide_prev_next_buttons !== 'yes') {
                $output .= '<a class="vc_left vc_carousel-control" href="#vc_images-carousel-2-1581579735" data-slide="prev">';
                $output .= '<span class="icon-prev"></span>';
                $output .= '</a>';
                $output .= '<a class="vc_right vc_carousel-control" href="#vc_images-carousel-2-1581579735" data-slide="next">';
                $output .= '<span class="icon-next"></span>';
                $output .= '</a>';
            }

            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';

            return $output;

        });

        return $handlers;

    }

    protected function getSliderWidth($size) {

        $width = '100%';
        $types = EphImageType::getImageTypeByName($size);

        if (isset($types)) {
            $width = $types['width'] . 'px';
        }

        return $width;
    }

    protected function getSliderHeight($size) {

        $width = '100%';
        $types = EphImageType::getImageTypeByName($size);

        if (isset($types)) {
            $width = $types['height'];
        }

        return $width;
    }

    public static function getCarouselIndex() {

        return self::$carousel_index++ . '-' . time();
    }

    protected function translateColumnWidthToSpan($width) {

        if (preg_match('/^(\d{1,2})\/12$/', $width, $match)) {
            $w = 'vc_col-sm-' . $match[1];
        } else {
            $w = 'vc_col-sm-';

            switch ($width) {
            case "1/6":
                $w .= '2';
                break;
            case "1/4":
                $w .= '3';
                break;
            case "1/3":
                $w .= '4';
                break;
            case "1/2":
                $w .= '6';
                break;
            case "2/3":
                $w .= '8';
                break;
            case "3/4":
                $w .= '9';
                break;
            case "5/6":
                $w .= '10';
                break;
            case "1/1":
                $w .= '12';
                break;
            default:
                $w = $width;
            }

        }

        return $w;
    }

    public static function vc_get_image_sizes_string() {

        $types = EphImageType::getImagesTypes();
        $return = '';

        if (!empty($types)) {

            foreach ($types as $k => $imageType) {

                if ($k > 0) {
                    $return .= ', ';
                }

                $return .= "\"{$imageType['name']}\"";
            }

        }

        return $return;
    }

    public static function recurseShortCode($content) {

        $handlers = self::$staticShortcodeHandler;
        $processor = new Processor(new RegularParser(), $handlers);
        return $processor->process($content);

    }

    public function builMaps() {

		$file = fopen("testbuilMaps.txt","w");
        $context = Context::getContext();
        

        $map_seeting = [];
		
        $seetings = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cml.name, c.*, ccl.`name` as `category`, cml.`description`')
                ->from('composer_map', 'c')
                ->leftJoin('composer_map_lang', 'cml', 'cml.`id_composer_map` = c.`id_composer_map` AND cml.`id_lang` = ' . $context->language->id)
                ->leftJoin('composer_category_lang', 'ccl', 'ccl.`id_composer_category` = c.`id_composer_category` AND ccl.`id_lang` = ' . $context->language->id)
        );
		$excludeField = ['show_settings_on_create', 'content_element', 'is_container'];
		
		foreach ($seetings as &$seeting) {
			foreach ($seeting as $key => $value) {
				if($key == 'show_settings_on_create') {
					if($value == 2) {
						$seeting['show_settings_on_create'] = false;
					} else if($value == 1) {
						$seeting['show_settings_on_create'] = true;
					} else if(empty($value)) {
						unset($seeting['show_settings_on_create']);
					} 
				}
				if($key == 'content_element') {
					if($value == 0) {
						$seeting['content_element'] = false;
					} else if($value == 1) {
						unset($seeting['content_element']);
					} 
				}
				if($key == 'is_container') {
					if($value == 1) {
						$seeting['is_container'] = true;
					} else  {
						unset($seeting['is_container']);
					} 
				}
			}
			
		}

        foreach ($seetings as &$seeting) {
			
			
            foreach ($seeting as $key => $value) {
				if(in_array($key, $excludeField)) {
					continue;
				}
				if (empty($value)) {
					unset($seeting[$key]);
                    
                }
            }
			unset($seeting['id_composer_category']);
			unset($seeting['active']);
			


        }

        foreach ($seetings as &$seeting) {
			
			
            $params = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('cpt.`value`as `type`, cmpl.heading, cmp.*, cmpl.description, cmpl.param_group as `group`')
                    ->from('composer_map_params', 'cmp')
                    ->leftJoin('composer_map_params_lang', 'cmpl', 'cmpl.`id_composer_map_params` = cmp.`id_composer_map_params` AND cmpl.`id_lang` = ' . $context->language->id)
                    ->leftJoin('composer_param_type', 'cpt', 'cpt.`id_composer_param_type` = cmp.`id_type`')
                    ->where('cmp.`id_composer_map` = ' . $seeting['id_composer_map'])
            );

            foreach ($params as &$param) {

                unset($param['id_type']);
				foreach ($param as $key => $value) {

                    if (empty($value)) {
                        unset($param[$key]);
                    }
					

                }
				if(!empty($param['value'])  && $param['param_name'] != 'content') {
					$param['value'] = Tools::jsonDecode($param['value'], true);
				}
				if($param['param_name'] == 'img_size') {
					$param['values'] = EphComposer::getComposerImageTypes();
				} else {
					$values = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
						(new DbQuery())
						->select('cv.`value_key`, cvl.`name`')
						->from('composer_value', 'cv')
                		->leftJoin('composer_value_lang', 'cvl', 'cvl.`id_composer_value` = cv.`id_composer_value` AND cvl.`id_lang` = ' . $context->language->id)
                		->where('cv.`id_composer_map_params` = ' . $param['id_composer_map_params'])
					);
					$param['values'] = $values;
				
				}

            }

            if (!empty($params)) {

                foreach ($params as &$param) {

                    if (!empty($param['dependency'])) {
                        $param['dependency'] = Tools::jsonDecode($param['dependency'], true);
                    }

                    if (!empty($param['settings'])) {
                        $param['settings'] = Tools::jsonDecode($param['settings'], true);
                    }

                    unset($param['id_composer_map']);
                    unset($param['id_composer_map_params']);
                    unset($param['position']);

                }

            }

            unset($seeting['id_composer_map']);
            unset($seeting['id_lang']);
            $seeting['params'] = $params;
            $map_seeting[$seeting['base']] = $seeting;
        }
		
		fwrite($file, Tools::jsonEncode($map_seeting));
		

        return $map_seeting;

    }

    public function vc_manager() {

        return Module::getInstanceByName('ephcomposer');

    }

    public function visual_composer() {

        return vc_manager()->vc();
    }

    public static function vc_mapper() {

        return vc_manager()->mapper();
    }

    public function vc_settings() {

        return vc_manager()->settings();
    }

    public function vc_backend_editor() {

        return vc_manager()->backendEditor();
    }

    public function buildShortCodeTag() {

        $this->vcallmod();
        $this->vc_base = new EphBase();

        foreach ($this->seetings_maps as $tag => $attributes) {

            if (isset(self::$sc[$tag])) {
                continue;
            }

            self::$sc[$tag] = $attributes;
            self::$sc[$tag]['params'] = [];

            if (!empty($attributes['params'])) {

                foreach ($attributes['params'] as $attribute) {

                    self::$sc[$tag]['params'][] = $attribute;
                }

            }

            $this->vc_base->addShortCode(self::$sc[$tag]);

        }

    }

    public function vcallmod() {

        $GetAllmodules_list = [];

        if (defined('_EPH_ROOT_DIR_')) {
            $GetAllmodules_list = $this->getAllFilterModules();
        } else {
            $GetAllmodules_list = $this->getAllModules();
        }

        if (!empty($GetAllmodules_list)) {

            foreach ($GetAllmodules_list as &$value) {

                if (!isset($value['id']) || !isset($value['name'])) {
                    $value = ['id' => $value, 'name' => $value];
                }

                EphComposer::addShortcode('vc_' . $value['id'], [$this, 'vcallmodcode']);

                if ($this->is_admin()) {
                    $this->vcmaps_init('vc_' . $value['id'], $value['name']);
                }

            }

        }

    }

    public function vcmaps_init($base = '', $module_name = null) {

        $hooks = [];

        $this->vc_mapper = new EphMapper;
        $mod_name = str_replace('vc_', '', $base);

        if (empty($module_name)) {
            $module_name = $mod_name;
        }

        $allhooks = $this->getModuleHookbyedit($mod_name);

        if (isset($allhooks) && !empty($allhooks)) {

            foreach ($allhooks as $hook) {
                $hooks[$hook['name']] = $hook['name'];
            }

        }

        $icon_url = context::getcontext()->shop->getBaseURL() . 'plugins/' . $mod_name . '/logo.png';

        $brands_params = [
            'name'     => $module_name,
            'base'     => $base,
            'icon'     => $icon_url,
            'category' => 'Modules',
            'params'   => [
                [
                    "type"       => "dropdown",
                    "heading"    => "Executed Hook",
                    "param_name" => "execute_hook",
                    "value"      => $hooks,
                ], [
                    "type"       => "vc_hidden_field",
                    "param_name" => "execute_module",
                    "def_value"  => $mod_name,
                    "value"      => $mod_name,
                ],
            ],
        ];

        $this->vc_mapper->addActivity('mapper', 'map', [
            'tag'        => $brands_params['base'],
            'attributes' => $brands_params,
        ]);

    }

    public function getModuleHookbyedit($module = '') {

        $reslt = [];
        $support_hooks = $this->support_hooks;
        $module_Ins = Module::getInstanceByName($module);
        $hooks = [];

        if (isset($support_hooks) && !empty($support_hooks)) {

            foreach ($support_hooks as $support_hook) {
                $support_retro_hook = Hook::getRetroHookName($support_hook);

                if (is_callable([
                    $module_Ins,
                    'hook' . $support_hook,
                ]) || is_callable([
                    $module_Ins,
                    'hook' . $support_retro_hook,
                ])) {

                    if (empty($support_retro_hook)) {
                        $support_retro_hook = $support_hook;
                    }

                    $hooks[] = [
                        'id'   => $support_retro_hook,
                        'name' => $support_retro_hook,
                    ];
                }

            }

        }

        return $hooks;

    }

    public function vcallmodcode($atts, $content = null) {

        extract(EphComposer::shortcodeAtts([
            'execute_hook'   => '',
            'execute_module' => '',
        ], $atts));

        $results = $this->ModHookExec($execute_module, $execute_hook);
        return $results;
    }

    public function ModHookExec($mod_name = '', $hook_name = '') {

        $results = '';

        if (Module::isInstalled($mod_name) && Module::isEnabled($mod_name)) {
            $mod_ins = Module::getInstanceByName($mod_name);

            if (Validate::isLoadedObject($mod_ins)) {
                $context = Context::getContext();
                $retro_hook_name = Hook::getRetroHookName($hook_name);
                $params = [
                    'cookie' => $context->cookie,
                    'cart'   => $context->cart,
                ];

                if (is_callable([
                    $mod_ins,
                    'hook' . $hook_name,
                ])) {
                    $mod_method = 'hook' . $hook_name;
                    $results = $mod_ins->$mod_method($params);
                } else

                if (is_callable([
                    $mod_ins,
                    'hook' . $retro_hook_name,
                ])) {
                    $mod_retro_method = 'hook' . $retro_hook_name;
                    $results = $mod_ins->$mod_retro_method($params);
                }

            } else {
                $results = '<strong>' . $mod_name . '</strong> is not install. Please Install <strong>' . $mod_name . '</strong> Module.';
            }

        }

        return $results;
    }

    public function ephBeforeInit() {

        $this->generateImageSizesArray();

        return true;
    }

    protected function setMode() {

        if ($this->is_admin()) {

            if ($this->vc_action() === 'vc_inline') {
                $this->mode = 'admin_frontend_editor';
            } else {
                $this->mode = 'admin_page';
            }

        } else

        if (Tools::getValue('vc_editable') === 'true') {
            $this->mode = 'page_editable';
        }

    }

    public function vc() {

        if (!isset($this->factory['vc'])) {

            $vc = new EphBase();
            $this->factory['vc'] = $vc;
        }

        return $this->factory['vc'];
    }

    public function getModuleHooks($module = '') {

        $support_hooks = $this->support_hooks;
        $module_Ins = Module::getInstanceByName($module);
        $hooks = [];

        if (isset($support_hooks) && !empty($support_hooks)) {

            foreach ($support_hooks as $support_hook) {
                $support_retro_hook = Hook::getRetroHookName($support_hook);

                if (is_callable([
                    $module_Ins,
                    'hook' . $support_hook,
                ]) || is_callable([
                    $module_Ins,
                    'hook' . $support_retro_hook,
                ])) {

                    if (empty($support_retro_hook)) {
                        $support_retro_hook = $support_hook;
                    }

                    $hooks[] = [
                        'id'   => $support_retro_hook,
                        'name' => $support_retro_hook,
                    ];
                }

            }

        }

        return $hooks;
    }

    public function getAllHooks() {

        $results = [];
        $support_hooks = [];

        if (isset($this->support_hooks) && !empty($this->support_hooks)) {
            $i = 0;

            foreach ($this->support_hooks as $value) {

                if (!empty($value)) {
                    $results[$i]['id'] = Hook::getRetroHookName($value);
                    $results[$i]['name'] = Hook::getRetroHookName($value);
                    $i++;
                }

            }

        }

        return $results;
    }

    public static function getMaps($element) {

        return EphComposer::getMapsSeetings($element);
    }

    public static function getMapsSeetings($element) {

        if (isset(static::$maps[$element])) {
            return static::$maps[$element];
        }

        return false;
    }

    public function genereateTabId($id) {

        return time() . '-' . $id . '-' . rand(0, 100);
    }

    public function composerControllerRegistration() {

        Configuration::updateValue('VC_ENQUEUED_CONTROLLERS', Tools::jsonEncode($this->configuration));

    }

    public static function getJsControllerValues($tableName, $failed_name, $identifier, $valIdentifier, $id_lang) {

        $db = Db::getInstance();

        $db_results = Db::getInstance()->getRow(
            (new DbQuery())
                ->select('*')
                ->from($tableName, 'ltable')
                ->leftJoin($tableName . '_lang', 'rtable', 'ltable.`' . $identifier . '` = rtable.`' . $identifier . '`')
                ->where('`rtable.' . $identifier . '` = ' . $valIdentifier)
                ->where('rtable.`id_lang` = ' . $id_lang)
        );

        $tmp = $db_results[$failed_name];
        unset($db_results[$failed_name]);
        $db_results[$failed_name][$id_lang] = $tmp;

        return (object) $db_results;
    }

    public static function getComposersConfiguration() {

        $setComposerConfiguration = Tools::jsonDecode(Configuration::get('VC_ENQUEUED_CONTROLLERS'));

        if (!is_array($setComposerConfiguration)) {
            $setComposerConfiguration = Tools::jsonDecode(Configuration::get('VC_ENQUEUED_CONTROLLERS'));
        } else {
            return $setComposerConfiguration;
        }

        return $setComposerConfiguration;

    }

    public static function condition() {

        $composerConfiguration = EphComposer::getComposersConfiguration();

        $controller = Tools::getValue('controller');

        $module_type = '';
        $module_controller = '';
        $module_table = '';
        $module_identifier = '';
        $module_field = '';
        $module_status = '';
        $module_frontend_status = '';
        $module_backend_status = '';

        $current_url = [];

        foreach ($_GET as $key => $value) {
            $ck_process_type_add = substr($key, 0, 3); // add 3
            $ck_process_type_update = substr($key, 0, 6); // update 6

            if (($ck_process_type_add == 'add' || $ck_process_type_update == 'update') && $value == '' && is_object($composerConfiguration)) {

                foreach ($composerConfiguration as $key => $value) {

                    if ($value->controller == $controller) {
                        return true;
                    }

                }

            }

        }

        if ($controller == 'VC_frontend') {
            return true;
        }

    }

    public static function getInstance() {

        return new Ephcomposer();
    }

    public static function ModifyImageUrl($img_src = '') {

        $httpprefix = '//';
        $img_pathinfo = pathinfo($img_src);
        $mainstr = $img_pathinfo['basename'];
        $static_url = $img_pathinfo['dirname'] . '/' . $mainstr;
        return $httpprefix . Tools::getMediaServer($static_url) . $static_url;
    }

    public static function removeShortcode($tag) {

        unset(self::$staticShortcodeTags[$tag]);
    }

    public static function addShortcode($tag, $func) {

        self::$staticShortcodeTags[$tag] = $func;
    }

    public static function vc_remove_element($tag) {

        EphMap::dropShortcode($tag);
    }

    public static function addShortcodeParam($name, $form_field_callback, $script_url = null) {

        return EPHShortcodeParams::addField($name, $form_field_callback, $script_url);
    }

    public static function getControllerEditorConfiguration($controller, $config_name) {

        $controllerConfiguration = Ephcomposer::getComposersConfiguration();

        foreach ($controllerConfiguration as $key => $value) {
            $arr_value = (array) $value;

            if (isset($value->controller)) {

                if ($value->controller == $controller) {
                    return $arr_value[$config_name];
                }

            }

        }

    }

    public static function doShortcode($content, $hook_name = '') {

        $shortcode_tags = self::$staticShortcodeTags;

        if (empty($shortcode_tags) || !is_array($shortcode_tags)) {
            return $content;
        }

       

        $pattern = EphComposer::getShortcodeRegex();

        self::$sds_current_hook = $hook_name;
        return preg_replace_callback("/$pattern/s", [__CLASS__, 'doShortcodeTag'], $content);
    }

    public static function doShortcodeTag($m) {

       
        $EphBase = new EphBase();
        $shortcode_tags = self::$staticShortcodeTags;

        if ($m[1] == '[' && $m[6] == ']') {
            return Tools::substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = self::shortcodeParseAtts($m[3]);

        if (isset($m[5])) {
            return $m[1] . call_user_func($shortcode_tags[$tag], $attr, $m[5], $tag, self::$sds_current_hook) . $m[6];
        } else {

            return $m[1] . call_user_func($shortcode_tags[$tag], $attr, null, $tag, self::$sds_current_hook) . $m[6];
        }

    }

    public static function getShortcodeRegex() {

        $shortcode_tags = self::$staticShortcodeTags;
        $tagnames = array_keys($shortcode_tags);
        $tagregexp = join('|', array_map('preg_quote', $tagnames));
        return
        '\\[' // Opening bracket
         . '(\\[?)' // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
         . "($tagregexp)"// 2: Shortcode name
         . '(?![\\w-])' // Not followed by word character or hyphen
         . '(' // 3: Unroll the loop: Inside the opening shortcode tag
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . '(?:'
        . '\\/(?!\\])' // A forward slash not followed by a closing bracket
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . ')*?'
        . ')'
        . '(?:'
        . '(\\/)' // 4: Self closing tag ...
         . '\\]' // ... and closing bracket
         . '|'
        . '\\]' // Closing bracket
         . '(?:'
        . '(' // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
         . '[^\\[]*+' // Not an opening bracket
         . '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
         . '[^\\[]*+' // Not an opening bracket
         . ')*+'
        . ')'
        . '\\[\\/\\2\\]' // Closing shortcode tag
         . ')?'
            . ')'
            . '(\\]?)'; // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    public static function shortcodeParseAtts($text) {

        $atts = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {

            foreach ($match as $m) {

                if (!empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } else

                if (!empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } else

                if (!empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } else

                if (isset($m[7]) and strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } else

                if (isset($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                }

            }

        } else {
            $atts = ltrim($text);
        }

        return $atts;
    }

    public static function adminShortcodeAtts($pairs, $atts, $shortcode = '') {

        $out = EphComposer::shortcodeAtts($pairs, $atts, $shortcode);

        if (isset($atts['content'])) {
            $out['content'] = $atts['content'];
        }

        return $out;
    }

    public static function shortcodeAtts($pairs, $atts, $shortcode = '') {

        $atts = (array) $atts;

        $out = [];

        foreach ($pairs as $name => $default) {

            if (isset($atts[$name])) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }

        }

        return $out;
    }

    public static function stripShortcodes($content) {

        $shortcode_tags = $this->staticShortcodeTags;

        if (empty($shortcode_tags) || !is_array($shortcode_tags)) {
            return $content;
        }

        $pattern = Ephcomposer::getShortcodeRegex();
        return preg_replace_callback("/$pattern/s", [__CLASS__, 'stripShortcodeTag'], $content);
    }

    public static function stripShortcodeTag($m) {

        if ($m[1] == '[' && $m[6] == ']') {
            return Tools::substr($m[0], 1, -1);
        }

        return $m[1] . $m[6];
    }

    public static function wpautop($pee, $br = true) {

        $pre_tags = [];

        if (trim($pee) === '') {
            return '';
        }

        $pee = $pee . "\n";

        if (Tools::strpos($pee, '<pre') !== false) {
            $pee_parts = explode('</pre>', $pee);
            $last_pee = array_pop($pee_parts);
            $pee = '';
            $i = 0;

            foreach ($pee_parts as $pee_part) {
                $start = Tools::strpos($pee_part, '<pre');

                if ($start === false) {
                    $pee .= $pee_part;
                    continue;
                }

                $name = "<pre wp-pre-tag-$i></pre>";
                $pre_tags[$name] = Tools::substr($pee_part, $start) . '</pre>';
                $pee .= Tools::substr($pee_part, 0, $start) . $name;
                $i++;
            }

            $pee .= $last_pee;
        }

        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|details|menu|summary)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(["\r\n", "\r"], "\n", $pee); // cross-platform newlines

        if (Tools::strpos($pee, '<option') !== false) {
            $pee = preg_replace('|\s*<option|', '<option', $pee);
            $pee = preg_replace('|</option>\s*|', '</option>', $pee);
        }

        if (Tools::strpos($pee, '</object>') !== false) {
            // no P/BR around param and embed
            $pee = preg_replace('|(<object[^>]*>)\s*|', '$1', $pee);
            $pee = preg_replace('|\s*</object>|', '</object>', $pee);
            $pee = preg_replace('%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee);
        }

        if (Tools::strpos($pee, '<source') !== false || Tools::strpos($pee, '<track') !== false) {
            // no P/BR around source and track
            $pee = preg_replace('%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee);
            $pee = preg_replace('%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee);
            $pee = preg_replace('%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee);
        }

        $pee = preg_replace("/\n\n+/", "\n\n", $pee);
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';

        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }

        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', [__CLASS__, '_autopNewlinePreservationHelper'], $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }

        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        if (!empty($pre_tags)) {
            $pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);
        }

        return $pee;
    }

    public static function _autopNewlinePreservationHelper($matches) {

        return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
    }

    public static function shortcode_unautop($pee) {

        $shortcode_tags = $this->staticShortcodeTags;

        if (empty($shortcode_tags) || !is_array($shortcode_tags)) {
            return $pee;
        }

        $tagregexp = join('|', array_map('preg_quote', array_keys($shortcode_tags)));
        $pattern = '/'
        . '<p>' // Opening paragraph
         . '\\s*+' // Optional leading whitespace
         . '(' // 1: The shortcode
         . '\\[' // Opening bracket
         . "($tagregexp)"// 2: Shortcode name
         . '(?![\\w-])' // Not followed by word character or hyphen
        // Unroll the loop: Inside the opening shortcode tag
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . '(?:'
        . '\\/(?!\\])' // A forward slash not followed by a closing bracket
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . ')*?'
        . '(?:'
        . '\\/\\]' // Self closing tag and closing bracket
         . '|'
        . '\\]' // Closing bracket
         . '(?:' // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
         . '[^\\[]*+' // Not an opening bracket
         . '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
         . '[^\\[]*+' // Not an opening bracket
         . ')*+'
        . '\\[\\/\\2\\]' // Closing shortcode tag
         . ')?'
        . ')'
        . ')'
        . '\\s*+' // optional trailing whitespace
         . '<\\/p>' // closing paragraph
         . '/s';

        return preg_replace($pattern, '$1', $pee);
    }

    public function is_admin() {

        if (defined('_EPH_ROOT_DIR_')) {
            return true;
        }

        return false;
    }

    protected function asAdmin() {

        $this->backendEditor()->addHooksSettings();
    }

    public function vc_loop_include_templates() {

        require_once $this->vc_path_dir('TEMPLATES_DIR', 'params/loop/templates.html');
    }

    public function mode() {

        return $this->mode;
    }

    public static function getAdminUpdateCMSLink($params = []) {

        $link = Context::getContext()->link->getAdminLink('AdminCmsContent');

        if (!empty($params) && is_array($params)) {
            $params = http_build_query($params);
            $params = htmlspecialchars_decode($params);
            $link .= "&{$params}";
        }

        return $link;
    }

    public static function backToAdminLink() {

        $return_url = Tools::getValue('return_url');
        $return_url_array = @unserialize(urldecode($return_url));
        $return_url = '';

        foreach ($return_url_array AS $key => $value) {
            $return_url .= '&' . $key . '=' . $value;
        }

        $return_url = substr($return_url, 1);
        return strtok($_SERVER["REQUEST_URI"], '?') . '?' . $return_url;

        // $link = Context::getContext()->link->getAdminLink('Admin'.$controller);
        // if (!empty($params) && is_array($params)) {
        //     $params = http_build_query($params);
        //     $params = htmlspecialchars_decode($params);
        //     $link .= "&{$params}";
        // }
        // return $link;
    }

    public static function getCMSLink($id, $alias = null, $ssl = null, $id_lang = null, $id_company = null) {

        $link = new Link;
        $cms = new CMS($id);
        return $link->getCMSLink($cms, $alias, $ssl, $id_lang, $id_company);
    }

    public static function getExitVcLink() {

        $return_url = Tools::getValue('return_url');
        $return_url_array = @unserialize(urldecode($return_url));
        $return_url = '';

        foreach ($return_url_array AS $key => $value) {
            $return_url .= '&' . $key . '=' . $value;
        }

        $return_url = substr($return_url, 1);
        return strtok($_SERVER["REQUEST_URI"], '?') . '?' . $return_url;

    }

    public static function getCategoryLink($id, $alias = null, $ssl = null, $id_lang = null, $id_company = null) {

        $link = new Link;
        // $category = new Category($id);
        return $link->getCategoryLink($id);
        // return $link->getCategoryLink($category, $alias, $ssl, $id_lang, $id_company);
    }

    public static function getVccontentanywhereLink($id, $alias = null, $ssl = null, $id_lang = null, $id_company = null) {

        $link = new Link;
        $url = $link->getModuleLink('jscomposer', 'vc_contentanywhere', ['val_identifier' => Tools::getValue('val_identifier'), 'frontend_module_name' => Tools::getValue('frontend_module_name'), Configuration::get('EPH_SSL_ENABLED')]);

        return $url;

    }

    public static function getProductLinks($id_product, $alias = null, $ssl = null, $id_lang = null, $id_company = null) {

        $link = new Link();
        $product = new Product((int) $id_product, false, $id_lang);

        return $link->getProductLink($product);
    }

    public function backendEditor() {

        if (!isset($this->factory['backend_editor'])) {
            $this->factory['backend_editor'] = new EphBackendEditor();
        }

        return $this->factory['backend_editor'];
    }

    public function mapper() {

        if (!isset($this->factory['mapper'])) {
            $this->factory['mapper'] = new EphMapper();
        }

        return $this->factory['mapper'];
    }

    public function automapper() {

        if (!isset($this->factory['automapper'])) {
            $this->factory['automapper'] = new EphAutomapper();
        }

        return $this->factory['automapper'];
    }

    public function settings() {

        if (!isset($this->factory['settings'])) {
            $this->factory['settings'] = new EphSettings();
        }

        return $this->factory['settings'];
    }

    public function vc_action() {

        if ($vc_action = Tools::getValue('vc_action')) {
            return $vc_action;
        }

        return null;
    }

    public function vc_post_param($param, $default = null) {

        return Tools::getValue($param) ? Tools::getValue($param) : $default;
    }

    public function addDefaultTemplates($data) {

        vc_add_default_templates($data);
    }

    public function loadDefaultTemplates() {

        return vc_load_default_templates();
    }

    public function path($name, $file = '') {

        return $this->vc_path_dir($name, $file);
    }

    public function vc_path_dir($name, $file = '') {

        return $this->composer_settings[$name] . '/' . $file;
    }

    public function vc_include_template($file, $args) {

        extract($args);
        require $this->vc_path_dir('TEMPLATES_DIR', $file);
    }

    public function assetUrl($file) {

        return ($this->vc_asset_url($file));
    }

    public function vc_asset_url($url) {

        return $this->_path . 'assets/' . $url;
    }

    public function esc_attr_e($string, $textdomain = '') {

        echo $this->esc_attr($string);
    }

    public function esc_attr($string) {

        return Tools::htmlentitiesUTF8($string);
    }

    public function esc_attr__($string) {

        return $this->esc_attr($this->l($string));
    }

    public function lcfirst($str) {

        $str[0] = mb_strtolower($str[0]);
        return $str;
    }

    public function vc_studly($value) {

        $value = Tools::ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    public function vc_camel_case($value) {

        return $this->lcfirst($this->vc_studly($value));
    }

    public function getControls() {

        $list = [];

        foreach ($this->controls as $control) {
            $method = $this->vc_camel_case('get_control_' . $control);

            if (method_exists($this, $method)) {
                $list[] = [$control, $this->method() . "\n"];
            }

        }

        return $list;
    }

    public function renderEditor($post = null) {

        if (!$this->isLoadEphComposer()) {
            return '';
        }

        $post_id = $page_type = '';

        switch (Tools::getValue('controller')) {
        case 'AdminCmsContent':
        case 'VC_frontend':

            if (Tools::getValue('vc_action') == 'vc_inline' && Tools::getValue('id_cms')) {
                $post_id = Tools::getValue('id_cms');
                $page_type = 'cms';
            } else

            if (Tools::getValue('vc_action') == 'vc_inline' && Tools::getValue('id_category')) {
                $post_id = Tools::getValue('id_category');
                $page_type = 'category';
            } else

            if (Tools::getValue('vc_action') == 'vc_inline' && Tools::getValue('id_vccontentanywhere')) {
                $post_id = Tools::getValue('id_vccontentanywhere');
                $page_type = 'vccontentanywhere';
            }

            break;
        }

        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $optname = "_wpb_{$page_type}_{$post_id}_{$lang['id_lang']}_css";
            $this->postCustomCss["{$lang['id_lang']}"] = Configuration::get($optname);
        }

        $tags = [
            'idObject' => Tools::getValue('id_object'),
            'action'   => Tools::getValue('action'),
        ];
        ob_start();
        $tags = 'coucou';
        $this->vc_include_template('editors/backend_editor.tpl.php', [
            'editor'    => $this,
            'arguments' => $tags,
        ]);
        $content = ob_get_clean();
        $content .= $this->renderEditorFooter();
        return $content;
    }

    public function renderEditorFooter() {

        ob_start();
        $this->init();
        $this->vc_include_template('editors/partials/backend_editor_footer.tpl.php', [
            'editor' => $this,
        ]);
        return ob_get_clean();
    }

    public function getLogo() {

        $output = '<a id="vc_logo" class="vc_navbar-brand" title="' . $this->esc_attr('Visual Composer', 'js_composer')
        . '" href="' . $this->esc_attr($this->brand_url) . '" target="_blank">'
        . $this->l('Visual Composer') . '</a>';
        return $output;
    }

    public function getControlCustomCss() {

        return '<li class="vc_pull-right"><a id="vc_post-settings-button" class="vc_icon-btn vc_post-settings" title="'
        . $this->esc_attr('Page settings', 'js_composer') . '">'
        . '<span id="vc_post-css-badge" class="vc_badge vc_badge-custom-css" style="display: none;">' . $this->l('CSS') . '</span></a>'
            . '</li>';
    }

    public function getControlAddElement() {

        return '<li class="vc_show-mobile">'
        . ' <a href="javascript:;" class="vc_icon-btn vc_element-button" data-model-id="vc_element" id="vc_add-new-element" title="'
        . '' . $this->l('Add new element') . '">'
            . ' </a>'
            . '</li>';
    }

    public function getControlTemplates() {

        return '<li><a href="javascript:;" class="vc_icon-btn vc_templates-button vc_navbar-border-right"  id="vc_templates-editor-button" title="'
        . $this->l('Templates') . '"></a></li>';
    }

    public function getControlFrontend() {

        if (!function_exists('vc_enabled_frontend')) {
            return false;
        }

        return '<li class="vc_pull-right">'
        . '<a href="' . vc_frontend_editor()->getInlineUrl() . '" class="vc_btn vc_btn-primary vc_btn-sm vc_navbar-btn" id="wpb-edit-inline">' . __('Frontend', "js_composer") . '</a>'
            . '</li>';
    }

    public function getControlPreview() {

        return '';
    }

    public function getControlSaveBackend() {

        return '<li class="vc_pull-right vc_save-backend">'
        . '<a href="javascript:;" class="vc_btn vc_btn-grey vc_btn-sm vc_navbar-btn vc_control-preview">' . $this->l('Preview') . '</a>'
        . '<a class="vc_btn vc_btn-sm vc_navbar-btn vc_btn-primary vc_control-save" id="wpb-save-post">' . $this->l('Update') . '</a>'
            . '</li>';
    }

    public function frontendEditor() {

        if (!isset($this->factory['frontend_editor'])) {
            require_once $this->path('EDITORS_DIR', 'class-vc-frontend-editor.php');
            $this->factory['frontend_editor'] = new Vc_Frontend_Editor();
        }

        return $this->factory['frontend_editor'];
    }

    public function setCustomUserShortcodesTemplateDir($dir) {

        preg_replace('/\/$/', '', $dir);
        $this->customUserTemplatesDir = $dir;
    }

    public function getDefaultShortcodesTemplatesDir() {

        return vc_path_dir('TEMPLATES_DIR', 'shortcodes');
    }

    public function getShortcodesTemplateDir($template) {

        return '';
    }

    public static function controller_upload_url($link = '') {

        $hash = vc_manager()->secure_key;

        $url = '//' . Tools::getHttpHost(false) . __EPH_BASE_URI__ . Context::getContext()->controller->admin_webpath . '/';
        $url .= Context::getContext()->link->getAdminLink('VC_upload') . '&security_key=' . $hash;

        if ($link != '') {
            $url = "{$url}&{$link}";
        }

        return $url;
    }

    public static function getMediaUploaderUrl() {

        return '//' . Tools::getShopDomain(false) . _MODULE_DIR_ . 'jscomposer/views/';
    }

    public static function delete_uploaded_file() {

        require_once dirname(__FILE__) . '/views/lang/en.php';

        $db = Db::getInstance();

        $tablename = _DB_PREFIX_ . 'vc_media';

        $imgdir = _EPH_COMPOSER_IMG_DIR_;

        $data = $_POST;

        if (!isset($data['img'])) {
            die('-1');
        }

        $filename = $db->escape($data['img']);
        $subdir = $db->getValue("SELECT subdir FROM {$tablename} WHERE file_name='{$data['img']}'");

        if (file_exists("{$imgdir}{$subdir}{$data['img']}")) {

            $images = [$filename];

            $types = EphImageType::getImagesTypes();

            foreach ($images as $image) {

                if (!empty($types)) {
                    $filerealname = Tools::substr($image, 0, Tools::strrpos($image, '.'));
                    $ext = substr($image, Tools::strrpos($image, '.'));

                    foreach ($types as $imageType) {
                        $newfilename = "{$filerealname}-{$imageType['name']}";

                        if (file_exists("{$imgdir}{$newfilename}{$ext}")) {
                            Tools::deleteFile("{$imgdir}{$newfilename}{$ext}");
                        }

                        if (file_exists("{$imgdir}{$subdir}{$newfilename}{$ext}")) {
                            Tools::deleteFile("{$imgdir}{$subdir}{$newfilename}{$ext}");
                        }

                    }

                }

                Tools::deleteFile("{$imgdir}{$image}");

                if (file_exists("{$imgdir}{$subdir}{$image}")) {
                    Tools::deleteFile("{$imgdir}{$subdir}{$image}");
                }

            }

            if ($db->query("DELETE FROM {$tablename} WHERE file_name='{$filename}'")) {
                echo Tools::jsonEncode([
                    'success' => '1',
                    'output'  => $this->get_uploaded_files_markup($this->get_uploaded_files_result(30, 0, $subdir)),
                ]);
            }

            die();
        }

    }

    public static function get_uploaded_files_result($per_page = 20, $start = 0, $subdir = '') {

        if (!empty($subdir) && $subdir != '/') {
            $subdir = "subdir='{$subdir}'";
        } else {
            $subdir = "subdir IS NULL OR subdir=''";
        }

        $sql = (new DbQuery())
            ->select('*')
            ->from('vc_media')
            ->where($subdir)
            ->orderBy('`id_vc_media` DESC')
            ->limit($start, $per_page);
        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
        $results = [];

        if (!empty($db_results)) {

            foreach ($db_results as $dres) {
                $dres = (object) $dres;

                if ($dres->subdir == '/') {
                    $dres->subdir = '';
                }

                if (isset($dres->file_name) && !empty($dres->file_name) && file_exists(_EPH_COMPOSER_IMG_DIR_ . $dres->subdir . $dres->file_name)
                ) {

                    $results["{$dres->id_vc_media}"] = $dres->file_name;
                }

            }

        }

        return $results;
    }

    public static function get_uploaded_files_markup($results = [], $path = '') {

        $upload_dir = _EPH_COMPOSER_IMG_DIR_;
        $current_path = vc_manager()->composer_settings['UPLOADS_DIR'];

        if (!empty($path)) {
            $current_path .= "{$path}";
            $upload_dir .= "{$path}";
        }

        ob_start();

        if (!empty($results)):

            $num = 0;

            foreach ($results as $id => $filename):
                $filerealname = Tools::substr($filename, 0, Tools::strrpos($filename, '.'));
                $file_path = $current_path . $filename;

                $img = $filename;
                $date = filemtime($file_path);
                $size = filesize($file_path);

                $file_infos = pathinfo($file_path);
                $file_ext = $file_infos['extension'];
                $extension_lower = strtolower($file_ext);

                $is_img = true;

                list($img_width, $img_height, $img_type, $attr) = getimagesize($file_path);

                $ext = substr($filename, strrpos($filename, '.'));
                $thumbimg = "{$filerealname}-vc_media_thumbnail{$ext}";

                ?>
                                                                                                                                                                        <li data-image-folder="<?php echo $path ?>" data-image="<?php echo $filename ?>" data-id="<?php echo $id ?>" class="ff-item-type-2 file">
                                                                                                                                                                            <figure data-type="img" data-name="<?php echo $filename ?>">
                                                                                                                                                                                <a data-function="apply" data-field_id="<?php echo $id ?>" href="#" data-file="<?php echo $img ?>" class="link-img">
                                                                                                                                                                                    <div class="img-precontainer">
                                                                                                                                                                                        <div class="img-container">
                                                                                                                                                                                            <span></span>
                                                                                                                                                                                            <img alt="<?php echo $img ?>" data-id="<?php echo $id ?>" src="<?php echo $upload_dir . $thumbimg ?>"  class="original "  >
                                                                                                                                                                                        </div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div class="img-precontainer-mini original-thumb">
                                                                                                                                                                                        <div class="filetype png hide"><?php echo $img_type ?></div>
                                                                                                                                                                                        <div class="img-container-mini">

                                                                                                                                                                                            <img src="<?php echo $upload_dir . $thumbimg ?>" class=" " alt="<?php echo $filerealname ?> thumbnails" />
                                                                                                                                                                                        </div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                </a>

                                                                                                                                                                                <div class="box">
                                                                                                                                                                                    <h4 class="ellipsis">
                                                                                                                                                                                        <a data-function="apply" data-field_id="" data-file-id="<?php echo $id ?>" data-file="<?php echo $img ?>" class="link" href="javascript:void('')">
                                                                                                                                                                                <?php echo $img ?></a></h4>
                                                                                                                                                                                </div>
                                                                                                                                                                        <?php $date = filemtime($current_path . $img);?>
                                                                                                                                                                                <input type="hidden" class="date" value="<?php echo $date; ?>"/>
                                                                                                                                                                                <input type="hidden" class="size" value="<?php echo $size ?>"/>
                                                                                                                                                                                <input type="hidden" class="extension" value="<?php echo $extension_lower; ?>"/>
                                                                                                                                                                                <input type="hidden" class="name" value="<?php echo $filerealname ?>"/>
                                                                                                                                                                                <input type="hidden" class="id" value="<?php echo $id ?>"/>

                                                                                                                                                                                <div class="file-date"><?php echo date(lang_Date_type, $date); ?></div>
                                                                                                                                                                                <div class="file-size"><?php echo $size; ?></div>
                                                                                                                                                                                <div class='img-dimension'><?php

                if ($is_img) {
                    echo $img_width . "x" . $img_height;
                }

                ?></div>
                                                                                                                                                                                <div class='file-extension'><?php echo Tools::safeOutput($extension_lower); ?></div>

                                                                                                                                                                                <figcaption>

                                                                                                                                                                                </figcaption>
                                                                                                                                                                            </figure>
                                                                                                                                                                        </li>
                                                                                                                                                                        <?php
    endforeach;

        endif;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function wpb_single_image_src() {

        if (Tools::getValue('content') && is_numeric(Tools::getValue('content'))) {
            $image_src = $this->_path . 'uploads/' . $this->get_media_thumbnail_url(Tools::getValue('content'));
            echo EphComposer::ModifyImageUrl($image_src);
            die();
        }

    }

    public static function get_media_thumbnail_url($id = '') {

        if (isset($id) && !empty($id)) {
            $db = Db::getInstance();
            $tablename = _DB_PREFIX_ . 'vc_media';

            $db_results = $db->executeS("SELECT `file_name`, `subdir` FROM {$tablename} WHERE id_vc_media={$id}", true, false);
            $url = isset($db_results[0]['subdir']) && !empty($db_results[0]['subdir']) ? $db_results[0]['subdir'] . '/' : '';
            return $url .= isset($db_results[0]['file_name']) ? $db_results[0]['file_name'] : '';
        } else {
            return '';
        }

    }

    public static function get_media_alt($id = '') {

        if (isset($id) && !empty($id)) {
            $db = Db::getInstance();
            $context = Context::getContext();
            $id_lang = (int) Context::getContext()->language->id;

            $tablename = _DB_PREFIX_ . 'vc_media';

            $db_results = $db->getRow("SELECT `legend`  FROM {$tablename}  INNER JOIN `{$tablename}_lang` ON `{$tablename}`.`id_vc_media` = `{$tablename}_lang`.`id_vc_media`  WHERE {$tablename}.id_vc_media={$id} AND `{$tablename}_lang`.id_lang = " . $id_lang, true, false);
            return isset($db_results['legend']) ? $db_results['legend'] : '';
        } else {
            return '';
        }

    }

    public static function import_media_img($file_url, $folder, $filename, $imgSubDir = '') {

        $db = Db::getInstance();
        $tablename = _DB_PREFIX_ . 'vc_media';

        $tempname = substr($filename, 0, strrpos($filename, '.'));
        $extension = substr($filename, strrpos($filename, '.'));

        $found = $db->getValue("SELECT COUNT(*) AS found FROM {$tablename} WHERE file_name LIKE '{$tempname}%' AND subdir='{$imgSubDir}'");

        if ($found && $found > 0) {
            $filename = $tempname . '-' . (++$found) . $extension;
        }

        if (!empty($imgSubDir) && $imgSubDir != '/') {
            $imgSubDir = "'{$imgSubDir}'";
        } else {
            $imgSubDir = 'NULL';
        }

        $db->execute("INSERT INTO {$tablename}(file_name,subdir) VALUES('{$filename}',{$imgSubDir})");
        $imgid = $db->Insert_ID();

        if (!empty($imgid) && is_numeric($imgid)) {
            //new fixing
            Tools::copy($file_url, $folder . $filename);
            $dir = $folder;
            $filerealname = Tools::substr($filename, 0, Tools::strrpos($filename, '.'));
            $ext = substr($filename, strrpos($filename, '.'));
            $type = EphImageType::getImagesTypes('active');

            if (!empty($type)) {

                foreach ($type as $imageType) {
                    $newfilename = "{$filerealname}-{$imageType['name']}";

                    if (!file_exists($dir . $newfilename . $ext)) {
                        ImageManager::resize($dir . $filename, $dir . $newfilename . $ext, (int) $imageType['width'], (int) $imageType['height']);
                    }

                }

            }

            return ["id" => $imgid, "path" => 'uploads/' . $filename];
        }

    }

    public static function getgenerateImageSizesArray() {

        EphComposer::generateImageSizesArray();
        return $this->image_sizes_dropdown;
    }

    public function generateImageSizesArray() {

        $sizes = array_merge([['name' => 'default']], EphImageType::getImagesTypes());

        if (!empty($sizes)) {

            foreach ($sizes as $size) {

                if (isset($size['width'])) {
                    $this->image_sizes[$size['name']] = "{$size['width']}x{$size['height']}";
                }

                $this->image_sizes_dropdown[$size['name']] = $size['name'];
            }

        }

    }

    public function getImageSize($name) {

        if (isset($this->image_sizes[$name]) && !empty($this->image_sizes[$name])) {
            return $this->image_sizes[$name];
        }

        return false;
    }

    public function update_content_frontend() {

        $composerConfiguration = EphComposer::getModulesConfiguration();

        $controller_name = Tools::isSubmit('controller') ? Tools::getValue('controller') : '';
        $controller_name = Tools::isSubmit('frontend_module_name') ? Tools::getValue('frontend_module_name') : $controller_name;

        $module_type = '';
        $module_controller = '';
        $module_table = '';
        $module_identifier = '';
        $module_field = '';
        $module_status = '';
        $module_frontend_status = '';
        $module_backend_status = '';

        foreach ($composerConfiguration as $key => $value) {

            if ($value->controller == $controller_name) {

                $module_type = (isset($value->type)) ? $value->type : '';
                $module_controller = $value->controller;
                $field_identifier = $value->identifier;
                $field_content = $value->field;
                $db_table = (isset($value->dbtable)) ? _DB_PREFIX_ . $value->dbtable : '';
                $module_status = $value->module_status;
                $module_frontend_status = $value->module_frontend_status;
                $module_backend_status = $value->module_backend_status;

                $val_content = Tools::getValue('content');
                $val_content = addcslashes($val_content, "'");

                $val_identifier = Tools::isSubmit('controller') ? Tools::getValue($field_identifier) : '';
                $val_identifier = Tools::isSubmit('frontend_val_identifier') ? Tools::getValue('frontend_val_identifier') : $val_identifier;

                $id_lang = Tools::getValue('id_lang');

                $db = Db::getInstance();
                $sql = "UPDATE {$db_table}_lang SET {$field_content}='{$val_content}' WHERE {$field_identifier}={$val_identifier} AND id_lang={$id_lang}";
                $stat = $db->execute($sql, false);
                echo intval($stat);

            }

        }

    }

    public function update_cms_frontend() {

        $id_lang = Tools::getValue('id_lang');
        $id_cms = Tools::getValue('post_id');
        $content = Tools::getValue('content');

        if (!empty($id_cms) && !empty($id_lang)) {
            $db = Db::getInstance();
            $table = _DB_PREFIX_ . 'cms_lang';

            $content = addcslashes($content, "'");
            $sql = "UPDATE {$table} SET content='{$content}' WHERE id_cms={$id_cms} AND id_lang={$id_lang}";
            $stat = $db->execute($sql, false);
            echo intval($stat);
        }

    }

    public static function getSmartBlogPostsThumbSizes() {

        $dbvc = Db::getInstance();
        $thumb_sizes = $dbvc->executeS("SELECT type_name FROM " . _DB_PREFIX_ . "smart_blog_imagetype WHERE type='post'", true, false);
        $nthumbs = [];

        if (!empty($thumb_sizes)) {

            foreach ($thumb_sizes as $tsize) {
                $tsize = $tsize['type_name'];
                $nthumbs["{$tsize}"] = $tsize;
            }

        }

        return $nthumbs;
    }

    public function getPreviewLink() {

        $id = intval(Tools::getValue('post_id'));
        $link = new Link;
        $id_lang = Tools::getValue('id_lang') ? Tools::getValue('id_lang') : null;
        $id_company = Tools::getValue('id_shop') ? Tools::getValue('id_shop') : null;
        $ssl = Tools::getValue('ssl') ? Tools::getValue('ssl') : null;
        $type = Tools::getValue('type');

        if (!empty($id) && is_numeric($id)) {

            switch ($type) {
            case 'cms':
                echo $link->getCMSLink($id, null, $ssl, $id_lang, $id_company);
                break;
            case 'cat':
                echo $link->getCategoryLink($id, null, $ssl, $id_lang, $id_company);
                break;
            case 'man':
                echo $link->getManufacturerLink($id, null, $ssl, $id_lang, $id_company);
                break;
            case 'sup':
                echo $link->getSupplierLink($id, null, $ssl, $id_lang, $id_company);
                break;
            case 'prd':
                echo $link->getProductLink($id, null, $ssl, $id_lang, $id_company);
                break;
            case 'smartblog':

                if (class_exists('SmartBlogPost')) {
                    //smartblog link generator...
                    $blog = new SmartBlogPost($id);

                    if (!empty($blog->id_smart_blog_post)) {
                        $options = ['id_post' => $blog->id_smart_blog_post, 'slug' => $blog->link_rewrite[intval($id_lang)]];
                        echo smartblog::GetSmartBlogLink('smartblog_post', $options);
                    }

                }

                break;
            }

        }

        die();
    }

    public static function getTPLPath($template = '', $module_name = 'jscomposer') {

        if (Tools::file_exists_cache(_EPH_THEME_DIR_ . 'plugins/' . $module_name . '/' . $template)) {
            return _EPH_THEME_DIR_ . 'plugins/' . $module_name . '/' . $template;
        } else

        if (Tools::file_exists_cache(_EPH_THEME_DIR_ . 'plugins/' . $module_name . '/views/templates/front/' . $template)) {
            return _EPH_THEME_DIR_ . 'plugins/' . $module_name . '/views/templates/front/' . $template;
        } else

        if (Tools::file_exists_cache(_EPH_MODULE_DIR_ . $module_name . '/views/templates/front/' . $template)) {
            return _EPH_MODULE_DIR_ . $module_name . '/views/templates/front/' . $template;
        }

        return false;
    }

    public static function asign_smarty_object() {

        $smarty = new smarty();
        EphComposer::fronted_smarty_asign($smarty);
        return $smarty;
    }

    public static function loadFrontendEditorHead($shortcode) {

        if (strpos($shortcode, 'nivo') !== false) {

            if (Configuration::get('vc_load_nivo_js') != 'no') {
                Context::getContext()->controller->addJS(vc_asset_url('lib/nivoslider/jquery.nivo.slider.pack.js'));
            }

            if (Configuration::get('vc_load_nivo_css') != 'no') {
                Context::getContext()->controller->addCSS(vc_asset_url('lib/nivoslider/nivo-slider.css'));
            }

            Context::getContext()->controller->addCSS(vc_asset_url('lib/nivoslider/themes/default/default.css'));
        }

        if (strpos($shortcode, 'flexslider_fade') !== false OR strpos($shortcode, 'flexslider_slide') !== false) {

            if (Configuration::get('vc_load_flex_css') != 'no') {
                Context::getContext()->controller->addCSS(vc_asset_url('lib/flexslider/flexslider.css'));
            }

            if (Configuration::get('vc_load_flex_js') != 'no') {
                Context::getContext()->controller->addJS(vc_asset_url('lib/flexslider/jquery.flexslider-min.js'));
            }

        }

        if (strpos($shortcode, 'image_grid') !== false) {
            Context::getContext()->controller->addJS(vc_asset_url('lib/isotope/dist/isotope.pkgd.min.js'));
        }

        if (strpos($shortcode, 'vc_images_carousel') !== false) {
            Context::getContext()->controller->addCSS(vc_asset_url('lib/vc_carousel/css/vc_carousel.css'));
            Context::getContext()->controller->addJS(vc_asset_url('lib/vc_carousel/js/transition.js'));
            Context::getContext()->controller->addJS(vc_asset_url('lib/vc_carousel/js/vc_carousel.js'));
        }

        if (strpos($shortcode, 'link_image') !== false) {
            Context::getContext()->controller->addCSS(vc_asset_url('lib/prettyphoto/css/prettyPhoto.css'));
            Context::getContext()->controller->addJS(vc_asset_url('lib/prettyphoto/js/jquery.prettyPhoto.js'));
        }

    }

    public static function fronted_smarty_asign($smarty) {

        smartyRegisterFunction($smarty, 'modifier', 'truncate', 'smarty_modifier_truncate');
        smartyRegisterFunction($smarty, 'modifier', 'secureReferrer', ['Tools', 'secureReferrer']);
        smartyRegisterFunction($smarty, 'function', 't', 'smartyTruncate'); // unused
        smartyRegisterFunction($smarty, 'function', 'm', 'smartyMaxWords'); // unused
        smartyRegisterFunction($smarty, 'function', 'p', 'smartyShowObject'); // Debug only
        smartyRegisterFunction($smarty, 'function', 'd', 'smartyDieObject'); // Debug only
        smartyRegisterFunction($smarty, 'function', 'l', 'smartyTranslate', false);
        smartyRegisterFunction($smarty, 'function', 'hook', 'smartyHook');
        smartyRegisterFunction($smarty, 'function', 'toolsConvertPrice', 'toolsConvertPrice');
        smartyRegisterFunction($smarty, 'modifier', 'json_encode', ['Tools', 'jsonEncode']);
        smartyRegisterFunction($smarty, 'modifier', 'json_decode', ['Tools', 'jsonDecode']);
        smartyRegisterFunction($smarty, 'function', 'dateFormat', ['Tools', 'dateFormat']);
        smartyRegisterFunction($smarty, 'function', 'convertPrice', ['Product', 'convertPrice']);
        smartyRegisterFunction($smarty, 'function', 'convertPriceWithCurrency', ['Product', 'convertPriceWithCurrency']);
        smartyRegisterFunction($smarty, 'function', 'displayWtPrice', ['Product', 'displayWtPrice']);
        smartyRegisterFunction($smarty, 'function', 'displayWtPriceWithCurrency', ['Product', 'displayWtPriceWithCurrency']);
        smartyRegisterFunction($smarty, 'function', 'displayPrice', ['Tools', 'displayPriceSmarty']);
        smartyRegisterFunction($smarty, 'modifier', 'convertAndFormatPrice', ['Product', 'convertAndFormatPrice']); // used twice
        smartyRegisterFunction($smarty, 'function', 'getAdminToken', ['Tools', 'getAdminTokenLiteSmarty']);
        smartyRegisterFunction($smarty, 'function', 'displayAddressDetail', ['AddressFormat', 'generateAddressSmarty']);
        smartyRegisterFunction($smarty, 'function', 'getWidthSize', ['Image', 'getWidth']);
        smartyRegisterFunction($smarty, 'function', 'getHeightSize', ['Image', 'getHeight']);
        smartyRegisterFunction($smarty, 'function', 'addJsDef', ['MediaAdmin', 'addJsDef']);
        smartyRegisterFunction($smarty, 'block', 'addJsDefL', ['MediaAdmin', 'addJsDefL']);
        smartyRegisterFunction($smarty, 'modifier', 'boolval', ['Tools', 'boolval']);
        $compared_products = [];

        if (Configuration::get('EPH_COMPARATOR_MAX_ITEM') && isset(Context::getcontext()->cookie->id_compare)) {
            $compared_products = CompareProduct::getCompareProducts(Context::getcontext()->cookie->id_compare);
        }

        if (Configuration::get('EPH_SSL_ENABLED') && Configuration::get('EPH_SSL_ENABLED_EVERYWHERE')) {
            $link_ssl = true;
        } else {
            $link_ssl = false;
        }

        $protocol_link = (Configuration::get('EPH_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $useSSL = ((isset($link_ssl) && $link_ssl && Configuration::get('EPH_SSL_ENABLED')) || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        $link = new Link($protocol_link, $protocol_content);
        $currency = Tools::setCurrency(Context::getcontext()->cookie);

        if ((int) Context::getcontext()->cookie->id_cart) {
            $cart = new Cart(Context::getcontext()->cookie->id_cart);

            if ($cart->OrderExists()) {
                unset($this->context->cookie->id_cart, $cart, Context::getcontext()->cookie->checkedTOS);
                Context::getcontext()->cookie->check_cgv = false;
            } else

            if (intval(Configuration::get('EPH_GEOLOCATION_ENABLED')) &&
                !in_array(strtoupper(Context::getcontext()->cookie->iso_code_country), explode(';', Configuration::get('EPH_ALLOWED_COUNTRIES'))) &&
                $cart->nbProducts() && intval(Configuration::get('EPH_GEOLOCATION_NA_BEHAVIOR')) != -1 &&
                !in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
                unset(Context::getcontext()->cookie->id_cart, $cart);
            } else

            if (Context::getcontext()->cookie->id_customer != $cart->id_customer || Context::getcontext()->cookie->id_lang != $cart->id_lang || $currency->id != $cart->id_currency) {

                if (Context::getcontext()->cookie->id_customer) {
                    $cart->id_customer = (int) (Context::getcontext()->cookie->id_customer);
                }

                $cart->id_lang = (int) (Context::getcontext()->cookie->id_lang);
                $cart->id_currency = (int) $currency->id;
                $cart->update();
            }

            if (isset($cart) && (!isset($cart->id_address_delivery) || $cart->id_address_delivery == 0 ||
                !isset($cart->id_address_invoice) || $cart->id_address_invoice == 0) && Context::getcontext()->cookie->id_customer) {
                $to_update = false;

                if (!isset($cart->id_address_delivery) || $cart->id_address_delivery == 0) {
                    $to_update = true;
                    $cart->id_address_delivery = (int) Address::getFirstCustomerAddressId($cart->id_customer);
                }

                if (!isset($cart->id_address_invoice) || $cart->id_address_invoice == 0) {
                    $to_update = true;
                    $cart->id_address_invoice = (int) Address::getFirstCustomerAddressId($cart->id_customer);
                }

                if ($to_update) {
                    $cart->update();
                }

            }

        }

        if (!isset($cart) || !$cart->id) {
            $cart = new Cart();
            $cart->id_lang = (int) (Context::getcontext()->cookie->id_lang);
            $cart->id_currency = (int) (Context::getcontext()->cookie->id_currency);
            $cart->id_guest = (int) (Context::getcontext()->cookie->id_guest);
            $cart->id_shop_group = (int) Context::getcontext()->shop->id_shop_group;
            $cart->id_shop = Context::getcontext()->shop->id;

            if (Context::getcontext()->cookie->id_customer) {
                $cart->id_customer = (int) (Context::getcontext()->cookie->id_customer);
                $cart->id_address_delivery = (int) (Address::getFirstCustomerAddressId($cart->id_customer));
                $cart->id_address_invoice = $cart->id_address_delivery;
            } else {
                $cart->id_address_delivery = 0;
                $cart->id_address_invoice = 0;
            }

            Context::getcontext()->cart = $cart;
            CartRule::autoAddToCart(Context::getcontext());
        } else {
            Context::getcontext()->cart = $cart;
        }

        $smarty->assign(
            [
                'page_name'           => Context::getcontext()->controller->php_self,
                'add_prod_display'    => (int) Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                'link'                => $link,
                'cart'                => $cart,
                'currency'            => $currency,
                'cookie'              => Context::getcontext()->cookie,
                'tpl_dir'             => _EPH_THEME_DIR_,
                'EPH_CATALOG_MODE'     => Configuration::get('EPH_CATALOG_MODE'),
                'EPH_STOCK_MANAGEMENT' => Configuration::get('EPH_STOCK_MANAGEMENT'),
                'priceDisplay'        => Product::getTaxCalculationMethod((int) Context::getcontext()->cookie->id_customer),
                'compared_products'   => is_array($compared_products) ? $compared_products : [],
                'comparator_max_item' => (int) Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
            ]
        );
    }

    public function contenthookvalue($hook = '') {

        if (!$this->vcHookContentCount($hook)) {
            return false;
        }

        $context = $this->context;
        $page = $context->controller->php_self;

        if (!is_object($this->vccawobj)) {
            $this->vccawobj = Contentanywhere::GetInstance();
        }

        $vcaw = $this->vccawobj;

        $id_page_value = '';

        if ($id_cms = Tools::getValue('id_cms')) {
            $id_page_value = $id_cms;
        } else

        if ($id_category = Tools::getValue('id_category')) {
            $id_page_value = $id_category;
        } else

        if ($id_product = Tools::getValue('id_product')) {
            $id_page_value = $id_product;
        }

        $cacheId = 'vccc' . $page . $hook . $id_page_value;

        if (!$this->isCached('jscomposer.tpl', $this->getCacheId(), $cacheId)) {
            $results = $vcaw->GetVcContentAnyWhereByHookPageFilter($hook, $page, $id_page_value);
            $this->context->smarty->assign([
                'results' => $results,
            ]);
        }

        return $this->display(__FILE__, 'views/templates/front/jscomposer.tpl', $this->getCacheId(), $cacheId);
    }

    public static function vc_map($attributes) {

        if (!isset($attributes['base'])) {
            trigger_error(__("Wrong wpb_map object. Base attribute is required", 'js_composer'), E_USER_ERROR);
            die();
        }

        EphMap::map($attributes['base'], $attributes);
    }

    public function GenerateModuleIcon() {

        $output = '<style>';

        if (!is_object($this->vccawobj)) {
            $this->vccawobj = new Contentanywhere();
        }

        $vccaw = $this->vccawobj;
        $GetAllmodules_list = $vccaw->getAllFilterModules();

        foreach ($GetAllmodules_list as $value) {
            $icon_url = context::getcontext()->shop->getBaseURL() . 'plugins/' . $value['id'] . '/logo.png';
            $output .= "
                .vc_el-container #vc_" . $value['id'] . " .vc_element-icon,
                .wpb_vc_" . $value['id'] . " .wpb_element_title .vc_element-icon {
                    background-image: url(" . $icon_url . ");
                    background-image: url(" . $icon_url . ");
                    -webkit-background-size: contain;
                    -moz-background-size: contain;
                    -ms-background-size: contain;
                    -o-background-size: contain;
                    background-size: contain;
                }
            ";
        }

        $output .= '</style>';
        echo $output;
    }

    public function vc_hidden_fields_func($settings, $value) {

        $outputcontent = '<input type="hidden" name="' . $settings['param_name'] . '" value="' . $settings['def_value'] . '" class="wpb_vc_param_value wpb-textinput ' . $settings['param_name'] . ' ' . $settings['type'] . '_field">';
        return $outputcontent;
    }

    public function vc_product_fileds_func($settings, $value) {

        $_html = '<div class="' . $settings['param_name'] . '">
        <select name="vc_product_fileds_' . $settings['param_name'] . '" class=" fixed-width-xl" id="vc_product_fileds_' . $settings['param_name'] . '" multiple="true">';
        $allproducts = $this->GetAllProductS();

        foreach ($allproducts as $allprd) {

            if (isset($value)) {
                $settings_def_value = explode(",", $value);

                if (in_array($allprd['id_product'], $settings_def_value)) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

            } else {
                $selected = '';
            }

            $_html .= '<option ' . $selected . ' value="' . $allprd['id_product'] . '">' . $allprd['name'] . '</option>';
        }

        $_html .= '</select>
        <input type="hidden" name="' . $settings['param_name'] . '" id="' . $settings['param_name'] . '" value="' . $value . '" class="wpb_vc_param_value wpb-textinput ' . $settings['param_name'] . ' ' . $settings['type'] . '_field">
        <script type="text/javascript">
            $(function(){
                var defVal = $("input#' . $settings['param_name'] . '").val();
                if(defVal.length){
                    var ValArr = defVal.split(\',\');
                    for(var n in ValArr){
                        $( "select#vc_product_fileds_' . $settings['param_name'] . '" ).children(\'option[value="\'+ValArr[n]+\'"]\').attr(\'selected\',\'selected\');
                    }
                }
                $( "select#vc_product_fileds_' . $settings['param_name'] . '" ).select2( { placeholder: "Select Products", width: 200, tokenSeparators: [\',\', \' \'] } ).on(\'change\',function(){
                    var data = $(this).select2(\'data\');
                    var select = $(this);
                    var field = select.next("input#' . $settings['param_name'] . '");
                    var saved = \'\';
                    select.children(\'option\').attr(\'selected\',null);
                    if(data.length)
                        $.each(data, function(k,v){
                            var selected = v.id;
                            select.children(\'option[value="\'+selected+\'"]\').attr(\'selected\',\'selected\');
                            if(k > 0)
                                saved += \',\';
                            saved += selected;
                        });
                     field.val(saved);
                });
            });
        </script>
        </div>';
        return $_html;
    }

    public function vc_brands_fileds_func($settings, $value) {

        $_html = '<div class="' . $settings['param_name'] . '">
        <select name="vc_brand_fileds_' . $settings['param_name'] . '" class=" fixed-width-xl" id="vc_brand_fileds_' . $settings['param_name'] . '" multiple="true">';
        $allbrands = $this->GetAllBrandS();

        foreach ($allbrands as $allbrnd) {

            if (isset($value)) {
                $settings_def_value = explode(",", $value);

                if (in_array($allbrnd['id_manufacturer'], $settings_def_value)) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

            } else {
                $selected = '';
            }

            $_html .= '<option ' . $selected . ' value="' . $allbrnd['id_manufacturer'] . '">' . $allbrnd['name'] . '</option>';
        }

        $_html .= '</select>
        <input type="hidden" name="' . $settings['param_name'] . '" id="' . $settings['param_name'] . '" value="' . $value . '" class="wpb_vc_param_value wpb-textinput ' . $settings['param_name'] . ' ' . $settings['type'] . '_field">
        <script type="text/javascript">
            $(function(){
                var defVal = $("input#' . $settings['param_name'] . '").val();
                if(defVal.length){
                    var ValArr = defVal.split(\',\');
                    for(var n in ValArr){
                        $( "select#vc_brand_fileds_' . $settings['param_name'] . '" ).children(\'option[value="\'+ValArr[n]+\'"]\').attr(\'selected\',\'selected\');
                    }
                }
                $( "select#vc_brand_fileds_' . $settings['param_name'] . '" ).select2( { placeholder: "Select Brands", width: 200, tokenSeparators: [\',\', \' \'] } ).on(\'change\',function(){
                    var data = $(this).select2(\'data\');
                    var select = $(this);
                    var field = select.next("input#' . $settings['param_name'] . '");
                    var saved = \'\';
                    select.children(\'option\').attr(\'selected\',null);
                    if(data.length)
                        $.each(data, function(k,v){
                            var selected = v.id;
                            select.children(\'option[value="\'+selected+\'"]\').attr(\'selected\',\'selected\');
                            if(k > 0)
                                saved += \',\';
                            saved += selected;
                        });
                     field.val(saved);
                });
            });
        </script>
        </div>';
        return $_html;
    }

    public function vc_category_fileds_func($settings, $value) {

        $_html = '<div class="' . $settings['param_name'] . '">
        <select name="vc_category_fileds_' . $settings['param_name'] . '" class=" fixed-width-xl" id="vc_category_fileds_' . $settings['param_name'] . '" multiple="true">';
        $allcategories = $this->GetAllCategorieS();

        foreach ($allcategories as $allprd) {

            if (isset($value)) {
                $settings_def_value = explode(",", $value);

                if (in_array($allprd['id_category'], $settings_def_value)) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

            } else {
                $selected = '';
            }

            $_html .= '<option ' . $selected . ' value="' . $allprd['id_category'] . '">' . $allprd['name'] . '</option>';
        }

        $_html .= '</select>
        <input type="hidden" name="' . $settings['param_name'] . '" id="' . $settings['param_name'] . '" value="' . $value . '" class="wpb_vc_param_value wpb-textinput ' . $settings['param_name'] . ' ' . $settings['type'] . '_field">
        <script type="text/javascript">
            $(function(){

                var defVal = $("input#' . $settings['param_name'] . '").val();
                if(defVal.length){
                    var ValArr = defVal.split(\',\');
                    for(var n in ValArr){
                        $( "select#vc_category_fileds_' . $settings['param_name'] . '" ).children(\'option[value="\'+ValArr[n]+\'"]\').attr(\'selected\',\'selected\');
                    }
                }
                $( "select#vc_category_fileds_' . $settings['param_name'] . '" ).select2( { placeholder: "Select Categories", width: 200, tokenSeparators: [\',\', \' \'] } ).on(\'change\',function(){
                    var data = $(this).select2(\'data\');
                    var select = $(this);
                    var field = select.next("input#' . $settings['param_name'] . '");
                    var saved = \'\';
                    select.children(\'option\').attr(\'selected\',null);
                    if(data.length)
                        $.each(data, function(k,v){
                            var selected = v.id;
                            select.children(\'option[value="\'+selected+\'"]\').attr(\'selected\',\'selected\');
                            if(k > 0)
                                saved += \',\';
                            saved += selected;
                        });
                     field.val(saved);
                });
            });
        </script>
        </div>';
        return $_html;
    }

    public function vc_supplier_fileds_func($settings, $value) {

        $_html = '<div class="' . $settings['param_name'] . '">
        <select name="vc_supplier_fileds_' . $settings['param_name'] . '" class=" fixed-width-xl" id="vc_supplier_fileds_' . $settings['param_name'] . '" multiple="true">';
        $allsuppliers = $this->GetAllSupplierS();

        foreach ($allsuppliers as $allsplr) {

            if (isset($value)) {
                $settings_def_value = explode(",", $value);

                if (in_array($allsplr['id_supplier'], $settings_def_value)) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

            } else {
                $selected = '';
            }

            $_html .= '<option ' . $selected . ' value="' . $allsplr['id_supplier'] . '">' . $allsplr['name'] . '</option>';
        }

        $_html .= '</select>
        <input type="hidden" name="' . $settings['param_name'] . '" id="' . $settings['param_name'] . '" value="' . $value . '" class="wpb_vc_param_value wpb-textinput ' . $settings['param_name'] . ' ' . $settings['type'] . '_field">
        <script type="text/javascript">
            $(function(){
                var defVal = $("input#' . $settings['param_name'] . '").val();
                if(defVal.length){
                    var ValArr = defVal.split(\',\');
                    for(var n in ValArr){
                        $( "select#vc_supplier_fileds_' . $settings['param_name'] . '" ).children(\'option[value="\'+ValArr[n]+\'"]\').attr(\'selected\',\'selected\');
                    }
                }
                $( "select#vc_supplier_fileds_' . $settings['param_name'] . '" ).select2( { placeholder: "Select Supplier", width: 200, tokenSeparators: [\',\', \' \'] } ).on(\'change\',function(){
                    var data = $(this).select2(\'data\');
                    var select = $(this);
                    var field = select.next("input#' . $settings['param_name'] . '");
                    var saved = \'\';
                    select.children(\'option\').attr(\'selected\',null);
                    if(data.length)
                        $.each(data, function(k,v){
                            var selected = v.id;
                            select.children(\'option[value="\'+selected+\'"]\').attr(\'selected\',\'selected\');
                            if(k > 0)
                                saved += \',\';
                            saved += selected;
                        });
                     field.val(saved);
                });
            });
        </script>
        </div>';
        return $_html;
    }

    public static function vc_content_filter($content = '') {

        $content = EphComposer::doShortcode($content);

        if ((bool) Module::isEnabled('smartshortcode')) {
            $smartshortcode = Module::getInstanceByName('smartshortcode');
            $content = $smartshortcode->parse($content);
        }

        return $content;
    }

    public function GetSimpleProductS() {

        $context = Context::getContext();
        $id_lang = (int) Context::getContext()->language->id;
        $front = true;
        $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` ' . ')
                WHERE pl.`id_lang` = ' . (int) $id_lang . '
                ' . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') . '
                ORDER BY pl.`name`';
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function GetAllProductS() {

        $rs = [];
        $rslt = [];
        $rs = $this->getSimpleProducts();
        $i = 0;

        foreach ($rs as $r) {
            $rslt[$i]['id_product'] = $r['id_product'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function GetAllBrandS() {

        $rs = [];
        $rslt = [];
        $rs = Manufacturer::getManufacturers();
        $i = 0;

        foreach ($rs as $r) {
            $rslt[$i]['id_manufacturer'] = $r['id_manufacturer'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function GetAllSupplierS() {

        $rs = [];
        $rslt = [];
        $rs = Supplier::getSuppliers();
        $i = 0;

        foreach ($rs as $r) {
            $rslt[$i]['id_supplier'] = $r['id_supplier'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function GetAllCategorieS() {

        $rs = [];
        $rslt = [];
        $id_lang = Context::getContext()->language->id;
        $rs = Category::getCategories($id_lang, true, false);
        $i = 0;

        foreach ($rs as $r) {
            $rslt[$i]['id_category'] = $r['id_category'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function hookvcBeforeInit() {

        $this->generateImageSizesArray();

        return true;
    }

    public function moduleFrontendEnable() {

        if (Tools::getValue('controller') == 'VC_frontend') {
            return true;
        }

        $composerConfiguration = EphComposer::getModulesConfiguration();

        $current_context_controller = (isset($this->context->controller->php_self)) ? $this->context->controller->php_self : '';

        $composerConfiguration = EphComposer::getModulesConfiguration();

        foreach ($composerConfiguration as $key => $value) {

            if (isset($value->context_controller)) {

                if ($value->context_controller == $current_context_controller && $value->type == 'core') {
                    $module_frontend_enable = (isset($value->module_frontend_enable)) ? $value->module_frontend_enable : 0;

                    if ($module_frontend_enable == 0) {
                        return false;
                    }

                }

            }

        }

        return true;
    }

    public function isLoadEphComposer($loade_for = false) {

        $current_controller = Tools::getValue('controller');

        if ($current_controller == 'VC_frontend') {
            $return_url = Tools::getValue('return_url');
            $return_url_array = @unserialize(urldecode($return_url));

            $current_controller = $return_url_array['controller'];
        }

        $composerConfiguration = EphComposer::getModulesConfiguration();

        $composerConfiguration_found = false;

        if (is_object($composerConfiguration)) {

            foreach ($composerConfiguration as $key => $value) {

                if ($value->controller == $current_controller) {

                    if (isset($value->module_status)) {

                        if ($value->module_status == 0) {
                            return false;
                        } else

                        if ($loade_for == 'frontend' && $value->module_frontend_status == 0) {
                            return false;
                        } else

                        if ($loade_for == 'backend' && $value->module_backend_status == 0) {
                            return false;
                        } else

                        if ($value->module_frontend_status == 0 && $value->module_backend_status == 0) {
                            return false;
                        }

                    }

                    $composerConfiguration_found = true;
                }

            }

        }

        return $composerConfiguration_found ? true : false;
    }

    public function cachedHookContent() {

        $sql = ' SELECT  `hook_name`, count(`id_vccontentanywhere`) as contentCount   FROM `' . _DB_PREFIX_ . 'vccontentanywhere` GROUP BY `hook_name` ';

        $results = Db::getInstance()->executeS($sql);

        foreach ($results as $key => $value) {

            if (isset($value['hook_name']) && !empty($value['hook_name'])) {
                $hook_retro_name = Hook::getRetroHookName($value['hook_name']);

                $this->hookContent[$hook_retro_name] = $value['contentCount'];
            }

            $this->hookContent[$value['hook_name']] = $value['contentCount'];
        }

    }

    public function vcHookContentCount($hook) {

        if (isset($this->hookContent[$hook]) && ($this->hookContent[$hook] > 0)) {
            return true;
        }

    }

    public static function GetLinkobj() {

        if (Tools::usingSecureMode()) {
            $useSSL = true;
        } else {
            $useSSL = false;
        }

        $protocol_link = (Configuration::get('EPH_SSL_ENABLED')) ? 'https://' : 'http://';
        $protocol_content = (isset($useSSL) AND $useSSL AND Configuration::get('EPH_SSL_ENABLED')) ? 'https://' : 'http://';
        $link = new Link($protocol_link, $protocol_content);
        return $link;
    }

    public function getProductsList() {

        $vcc = new Contentanywhere();
        echo $vcc->getProductsByName();
    }

    public static function getPsImgSizesOption() {

        $db = Db::getInstance();
        $tablename = _DB_PREFIX_ . 'image_type';
        $sizes = $db->executeS("SELECT name FROM {$tablename} ORDER BY name ASC");
        $options = ['Default' => ''];

        if (!empty($sizes)) {

            foreach ($sizes as $size) {
                $options[$size['name']] = $size['name'];
            }

        }

        return $options;
    }

    private static function productCategoryWalaker($children, &$options) {

        foreach ($children as $cat) {
            $options[$cat['name']] = $cat['id_category'];

            if (isset($cat['children']) && !empty($cat['children'])) {
                $this->productCategoryWalaker($cat['children'], $options);
            }

        }

    }

    public static function getCategoriesOption() {

        $categories = Category::getNestedCategories();

        $options = ['Default' => ''];

        if (!empty($categories)) {

            foreach ($categories as $cat) {
                $options[$cat['name']] = $cat['id_category'];

                if (isset($cat['children']) && !empty($cat['children'])) {
                    $this->productCategoryWalaker($cat['children'], $options);
                }

            }

        }

        return $options;
    }

    public function vc_get_autocomplete_suggestion() {

        $q = Tools::getValue('q');
        $type = Tools::getValue('vc_catalog_type');
        $limit = Tools::getValue('limit');
        $query = [
            'keyword' => $q,
            'type'    => $type,
            'limit'   => $limit,
        ];

        $this->vc_render_suggestion($query);
    }

    private function vc_render_suggestion($query) {

        $this->productIdAutocompleteSuggester($query);
        die('');
    }

    private function productIdAutocompleteSuggester($query) {

        switch ($query['type']) {
        case 'product':
            $this->getProductsList();
            break;
        case 'category':
            $vcc = new Contentanywhere();
            echo $vcc->getCatsByName();
            break;
        case 'manufacturer':
            $vcc = new Contentanywhere();
            echo $vcc->getManufacturersByName();
            break;
        case 'supplier':
            $vcc = new Contentanywhere();
            echo $vcc->getSuppliersByName();
            break;
        }

    }

    public static function productIdAutocompleteRender($query) {

        if (!empty($query['value'])) {
            $elemid = $elemName = '';
            $context = Context::getContext();

            switch ($query['type']) {
            case 'product':
                $product = new Product((int) $query['value']);

                if (!empty($product) && isset($product->name)) {
                    $elemid = (int) $query['value'];
                    $elemName = $product->name[$context->language->id];
                }

                break;
            case 'category':
                $cat = new Category((int) $query['value']);

                if (!empty($cat) && isset($cat->name)) {
                    $elemid = (int) $query['value'];
                    $elemName = $cat->name[$context->language->id];
                }

                break;
            case 'manufacturer':
                $man = new Manufacturer((int) $query['value']);

                if (!empty($man) && isset($man->name)) {
                    $elemid = (int) $query['value'];
                    $elemName = $man->name;
                }

                break;
            case 'supplier':
                $sup = new Supplier((int) $query['value']);

                if (!empty($sup) && isset($sup->name)) {
                    $elemid = (int) $query['value'];
                    $elemName = $sup->name;
                }

                break;
            }

            if (!empty($elemid)) {
                return [$elemid, $elemName];
            }

        }

        return false;
    }

    public function hookVcShortcodesCssClass($params) {}

    public static function AddVcExternalControllers($attribs) {

        if (!empty($attribs)) {

            $controllers = Configuration::get('VC_ENQUEUED_CONTROLLERS');
            $controllers = Tools::jsonDecode($controllers, true);

            if (empty($controllers)) {
                $controllers = [];
            }

            foreach ($attribs as $id => $attr) {

                if (isset($controllers[$id])) {
                    $attr['type'] = isset($attr['type']) ? $attr['type'] : 'custom';
                    $attr['shortname'] = isset($attr['shortname']) ? $attr['shortname'] : '';
                    $attr['module_status'] = isset($attr['module_status']) ? $attr['module_status'] : 1;
                    $attr['module_frontend_status'] = isset($attr['module_frontend_status']) ? $attr['module_frontend_status'] : 1;
                    $attr['module_backend_status'] = isset($attr['module_backend_status']) ? $attr['module_backend_status'] : 1;
                    $attr['module_frontend_enable'] = isset($attr['module_frontend_enable']) ? $attr['module_frontend_enable'] : 1;

                    $controllers[$id] = $attr;
                }

            }

            Configuration::updateValue('VC_ENQUEUED_CONTROLLERS', Tools::jsonEncode($controllers));
            return true;
        }

        return false;
    }

    public static function getVcShared($asset = '') {

        switch ($asset) {
        case 'colors':
            return EphSharedLibrary::getColors();
            break;

        case 'icons':
            return EphSharedLibrary::getIcons();
            break;

        case 'sizes':
            return EphSharedLibrary::getSizes();
            break;

        case 'button styles':
        case 'alert styles':
            return EphSharedLibrary::getButtonStyles();
            break;

        case 'cta styles':
            return EphSharedLibrary::getCtaStyles();
            break;

        case 'text align':
            return EphSharedLibrary::getTextAlign();
            break;

        case 'cta widths':
        case 'separator widths':
            return EphSharedLibrary::getElementWidths();
            break;

        case 'separator styles':
            return EphSharedLibrary::getSeparatorStyles();
            break;

        case 'single image styles':
            return EphSharedLibrary::getBoxStyles();
            break;

        default:
            # code...
            break;
        }

    }

    public static function RemoveVcExternalControllers($attribs) {

        if (!empty($attribs)) {
            $controllers = Configuration::get('VC_ENQUEUED_CONTROLLERS');
            $controllers = Tools::jsonDecode($controllers, true);

            if (empty($controllers)) {
                $controllers = [];
            }

            foreach ($attribs as $id) {

                if (isset($controllers[$id])) {
                    unset($controllers[$id]);
                }

            }

            Configuration::updateValue('VC_ENQUEUED_CONTROLLERS', Tools::jsonEncode($controllers));
            return true;
        }

        return false;
    }

    public static function getFullImageUrl($img_id) {

        $link_to = $this->_url . 'uploads/' . $this->get_media_thumbnail_url($img_id);
        $link_to = $this->ModifyImageUrl($link_to);
        return $link_to;
    }

    public function __call($function, $args) {

        $hook = substr($function, 0, 4);

        if ($hook == 'hook') {
            $hook_name = substr($function, 4);

            return $this->contenthookvalue($hook_name);
        } else {
            return false;
        }

    }

    public static function vcTinymcePluginAdd($name) {

        $old_vc_tinymce_plugins = unserialize(Configuration::get('VC_TINYMCE_PLUGIN'));

        if (isset($old_vc_tinymce_plugins) && ($old_vc_tinymce_plugins == '')) {
            $old_vc_tinymce_plugins = [];
        }

        if (in_array($name, $old_vc_tinymce_plugins)) {
            $name = '';
        } else {
            $old_vc_tinymce_plugins[] = $name;
        }

        $updated_hook_list = serialize($old_vc_tinymce_plugins);
        Configuration::updateValue('VC_TINYMCE_PLUGIN', $updated_hook_list);
    }

    public static function vcTinymcePluginCssAdd($name) {

        $old_vc_tinymce_plugins = unserialize(Configuration::get('VC_TINYMCE_PLUGIN_CSS'));

        if (isset($old_vc_tinymce_plugins) && ($old_vc_tinymce_plugins == '')) {
            $old_vc_tinymce_plugins = [];
        }

        if (in_array($name, $old_vc_tinymce_plugins)) {
            $name = '';
        } else {
            $old_vc_tinymce_plugins[] = $name;
        }

        $updated_hook_list = serialize($old_vc_tinymce_plugins);
        Configuration::updateValue('VC_TINYMCE_PLUGIN_CSS', $updated_hook_list);
    }

    public static function vcTinymcePluginRemove($name) {

        if ($name != '') {

            $old_vc_tinymce_plugins = unserialize(Configuration::get('VC_TINYMCE_PLUGIN'));

            if ($old_vc_tinymce_plugins == '') {
                $old_vc_tinymce_plugins = [];
            }

            $key = array_search($name, $old_vc_tinymce_plugins);

            unset($old_vc_tinymce_plugins[$key]);

            $updated_vc_tinymce_plugins = serialize($old_vc_tinymce_plugins);
            Configuration::updateValue('VC_TINYMCE_PLUGIN', $updated_vc_tinymce_plugins);
        }

    }

    public static function vcTinymcePluginCssRemove($name) {

        if ($name != '') {

            $old_vc_tinymce_plugins = unserialize(Configuration::get('VC_TINYMCE_PLUGIN_CSS'));

            if ($old_vc_tinymce_plugins == '') {
                $old_vc_tinymce_plugins = [];
            }

            $key = array_search($name, $old_vc_tinymce_plugins);

            unset($old_vc_tinymce_plugins[$key]);

            $updated_vc_tinymce_plugins = serialize($old_vc_tinymce_plugins);
            Configuration::updateValue('VC_TINYMCE_PLUGIN_CSS', $updated_vc_tinymce_plugins);
        }

    }

    public function vccClearCache() {

        $this->_clearCache('jscomposer.tpl');
    }

    public function vcProTabClearCache() {

        $this->_clearCache('vc_prd_tab_title.tpl');
        $this->_clearCache('vc_prd_tab_content.tpl');
    }

    public function hookVcAllowedImgAttrs($params) {}

    public function vcTranslate($key) {

        if (isset($this->vc_translations[$key])) {
            return $this->vc_translations[$key];
        }

        return $key;
    }

    public static function generateDependenciesAttributes($settings) {

        return '';
    }

    public static function wpb_map($attributes) {

        EphComposer::vc_map($attributes);
    }

    public static function wpb_remove($shortcode) {

        EphComposer::vc_remove_element($shortcode);
    }

    public static function vc_add_param($shortcode, $attributes) {

        EphMap::addParam($shortcode, $attributes);
    }

    public static function vc_add_params($shortcode, $attributes) {

        foreach ($attributes as $attr) {
            EphMap::vc_add_param($shortcode, $attr);
        }

    }

    public static function wpb_add_param($shortcode, $attributes) {

        EphMap::vc_add_param($shortcode, $attributes);
    }

    public static function vc_map_update($name = '', $setting = '', $value = '') {

        return EphMap::modify($name, $setting, $value);
    }

    public static function vc_update_shortcode_param($name, $attribute = []) {

        return EphMap::mutateParam($name, $attribute);
    }

    public static function vc_remove_param($name = '', $attribute_name = '') {

        return EphMap::dropParam($name, $attribute_name);
    }

    public static function vc_mode() {

        return EphComposer::mode();
    }

    public static function vc_set_shortcodes_templates_dir($dir) {

        EphComposer::setCustomUserShortcodesTemplateDir($dir);
    }

    public static function vc_shortcodes_theme_templates_dir($template) {

        return EphComposer::getShortcodesTemplateDir($template);
    }

    public static function vc_enabled_frontend() {

        return vc_frontend_editor()->inlineEnabled();
    }

    public static function vc_add_default_templates($data) {

        return visual_composer()->templatesEditor()->addDefaultTemplates($data);
    }

    public static function vc_load_default_templates() {

        return visual_composer()->templatesEditor()->loadDefaultTemplates();
    }

    public static function vc_get_shortcode($tag) {

        return EphMap::getShortCode($tag);
    }

    public static function vc_map_get_defaults($tag) {

        $shortcode = EphComposer::vc_get_shortcode($tag);
        $params = [];

        if (is_array($shortcode) && isset($shortcode['params']) && !empty($shortcode['params'])) {

            foreach ($shortcode['params'] as $param) {

                if (isset($param['param_name']) && 'content' !== $param['param_name']) {
                    $value = '';

                    if (isset($param['std'])) {
                        $value = $param['std'];
                    } else

                    if (isset($param['value']) && 'checkbox' !== $param['type']) {

                        if (is_array($param['value'])) {
                            $value = current($param['value']);

                            if (is_array($value)) {
                                $value = current($value);
                            }

                        } else {
                            $value = $param['value'];
                        }

                    }

                    $params[$param['param_name']] = $value;
                }

            }

        }

        return $params;
    }

    public static function vc_map_get_attributes($tag, $atts = []) {

        return EphComposer::shortcodeAtts(EphComposer::vc_map_get_defaults($tag), $atts, $tag);
    }

    public static function fieldAttachedImages($att_ids = [], $imageSize = null) {

        $links = [];

        foreach ($att_ids as $th_id) {

            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('vc_media')
                    ->where('`id_vc_media` = ' . (int) $th_id)
            );
			if(isset($result['base_64']) && !empty($result['base_64'])) {
				$links[$th_id] = $result['base_64'];
				
			} else if (isset($result['file_name']) && !empty($result['file_name'])) {
                $thumb_src = _COMPOSER_IMG_DIR_;

                if (!empty($result['subdir'])) {
                    $thumb_src .= $result['subdir'];
                }

                $thumb_src .= $result['file_name'];

                if (!empty($imageSize)) {
                    $path_parts = pathinfo($thumb_src);
                    $thumb_src = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'] . '-' . $imageSize . '.' . $path_parts['extension'];

                }
				if(Tools::isImagickCompatible() && empty($result['base_64'])) {
					$extension = pathinfo($thumb_src, PATHINFO_EXTENSION);
					$img = new Imagick(_EPH_ROOT_DIR_.$thumb_src);
					$imgBuff = $img->getimageblob();
					$img->clear(); 
					$img = base64_encode($imgBuff);
					$base64 = 'data:image/'.$extension.';base64,'.$img;
					$imageType = new EphMedia($result['id_vc_media']);
					$imageType->file_name = $result['file_name'];
					$imageType->base_64 = $base64;
					$imageType->subdir = $result['subdir'];
					foreach (Language::getIDs(false) as $idLang) {
						$imageType->legend[$idLang] = pathinfo($thumb_src, PATHINFO_FILENAME);
					}
					if($imageType->update()) {
						$thumb_src = $base64;
					}

					
				}

                $links[$th_id] = $thumb_src;
            }

        }

        return $links;
    }

}
