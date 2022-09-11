<?php

class TopMenuCore extends ObjectModel {

    protected static $_forceCompile;
    protected static $_caching;
    protected static $_compileCheck;

    public $id;
    public $reference;
    public $id_category;
    public $id_cms;
    public $id_cms_category;
    public $id_specific_page;
	public $custom_hook;
    public $id_shop;
    public $name;
    public $link;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $type;
	public $image_hash;
	public $image_hover;
    public $privacy;
    public $have_icon;
    public $image_type;
    public $image_legend;
    public $image_class;
    public $value_over;
    public $value_under;
    public $target;
    public $txt_color_menu_tab;
    public $txt_color_menu_tab_hover;
    public $fnd_color_menu_tab;
    public $fnd_color_menu_tab_over;
    public $width_submenu;
    public $minheight_submenu;
    public $position_submenu;
    public $fnd_color_submenu;
    public $border_color_submenu;
    public $border_color_tab;
    public $border_size_tab;
    public $border_size_submenu;
    public $custom_class;
    public $outPutName;

    public static $definition = [
        'table'     => 'topmenu',
        'primary'   => 'id_topmenu',
        'multilang' => true,
        'fields'    => [
            'id_category'              => ['type' => self::TYPE_INT],
            'id_cms'                   => ['type' => self::TYPE_INT],
            'id_cms_category'          => ['type' => self::TYPE_INT],
            'id_specific_page'         => ['type' => self::TYPE_INT],
			'custom_hook'             => ['type' => self::TYPE_STRING],
            'id_shop'                  => ['type' => self::TYPE_INT],
            'position'                 => ['type' => self::TYPE_INT],
            'txt_color_menu_tab'       => ['type' => self::TYPE_STRING],
            'txt_color_menu_tab_hover' => ['type' => self::TYPE_STRING],
            'fnd_color_menu_tab'       => ['type' => self::TYPE_STRING],
            'fnd_color_menu_tab_over'  => ['type' => self::TYPE_STRING],
            'border_size_tab'          => ['type' => self::TYPE_STRING],
            'width_submenu'            => ['type' => self::TYPE_STRING],
            'minheight_submenu'        => ['type' => self::TYPE_STRING],
            'position_submenu'         => ['type' => self::TYPE_STRING],
            'fnd_color_submenu'        => ['type' => self::TYPE_STRING],
            'border_color_submenu'     => ['type' => self::TYPE_STRING],
            'border_size_submenu'      => ['type' => self::TYPE_STRING],
            'privacy'                  => ['type' => self::TYPE_STRING],
            'active'                   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_desktop'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'target'                   => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'type'                     => ['type' => self::TYPE_INT],
            'custom_class'             => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 255],
			'image_hash'             => ['type' => self::TYPE_STRING],
			'image_hover'             => ['type' => self::TYPE_STRING],


            'name'                     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => false, 'size' => 255],
            'link'                     => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => false, 'size' => 255],
            'value_over'               => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false],
            'value_under'              => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false],
            'have_icon'                => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isBool', 'required' => false],
            'image_type'               => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false],
            'image_legend'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => false, 'size' => 255],
            'image_class'              => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false, 'size' => 255],
        ],
    ];

    public function __construct($id_topmenu = null, $id_lang = null, $id_shop = null) {

       
        parent::__construct($id_topmenu, $id_lang, $id_shop);

        if ($this->id) {
            $this->outPutName = $this->getNameValue();
        }

    }

    public function getNameValue() {
		
		if($this->type == 9) {
			
			$meta = new Meta($this->id_specific_page);
            $name = $meta->title[$this->context->language->id];
			return $name;
		}

        $context = Context::getContext();
        $sql = 'SELECT atp.*, atpl.*,
                cl.meta_title,
                etl.name as category_name,
                ccl.name as cms_category_name
                FROM `' . _DB_PREFIX_ . 'topmenu` atp
                LEFT JOIN `' . _DB_PREFIX_ . 'topmenu_lang` atpl ON (atp.`id_topmenu` = atpl.`id_topmenu` AND atpl.`id_lang` = ' . (int) $context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'category_lang etl ON (etl.id_category = atp.`id_category` AND etl.`id_lang` = ' . (int) $context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms c ON (c.id_cms = atp.`id_cms`)
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category cc ON (cc.id_cms_category = atp.`id_cms_category`)
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category_lang ccl ON (cc.id_cms_category = ccl.id_cms_category AND ccl.id_lang = ' . (int) $context->language->id . ')
                WHERE atp.`id_topmenu` = ' . $this->id;

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);

        switch ($result['type']) {
        case 1:
            $name = htmlentities($result['meta_title'], ENT_COMPAT, 'UTF-8');
            break;
        case 2:
            $name = htmlentities($result['name'], ENT_COMPAT, 'UTF-8');
            break;
        case 3:
            $name = htmlentities($result['category_name'], ENT_COMPAT, 'UTF-8');
            break;
        case 7:

            if (trim($result['name'])) {
                $name = htmlentities($result['name'], ENT_COMPAT, 'UTF-8');
            } else
            if (trim($result['image_legend'])) {
                $name = htmlentities($result['image_legend'], ENT_COMPAT, 'UTF-8');
            } else {
                $name = 'Image ou Icone';
            }

            break;
        case 9:

            if (!trim($result['name'])) {
                $page = Meta::getMetaById($result['id_specific_page'], (int) $context->language->id);
                $name = (!empty($page['title']) ? $page['title'] : $page['page']);
            } else {
                $name = htmlentities($result['name'], ENT_COMPAT, 'UTF-8');
            }

            break;
        case 13:
            $name = htmlentities($result['cms_category_name'], ENT_COMPAT, 'UTF-8');
            break;
        }

        return $name;
    }

    public function getTranslationsFieldsChild() {

        parent::validateFieldsLang();
        $fieldsArray = [
            'name', 'link', 'have_icon', 'image_type', 'image_legend', 'image_class',
        ];
        $fields = [];
        $languages = Language::getLanguages(false);
        $defaultLanguage = Configuration::get('EPH_LANG_DEFAULT');

        foreach ($languages as $language) {
            $fields[$language['id_lang']]['id_lang'] = $language['id_lang'];
            $fields[$language['id_lang']][$this->identifier] = (int) $this->id;
            $fields[$language['id_lang']]['value_over'] = isset($this->value_over[$language['id_lang']]) ? pSQL($this->value_over[$language['id_lang']], true) : '';
            $fields[$language['id_lang']]['value_under'] = isset($this->value_under[$language['id_lang']]) ? pSQL($this->value_under[$language['id_lang']], true) : '';

            foreach ($fieldsArray as $field) {

                if (!Validate::isTableOrIdentifier($field)) {
                    die(Tools::displayError());
                }

                if (isset($this->{$field}

                    [$language['id_lang']]) and !empty($this->{$field}

                    [$language['id_lang']])) {
                    $fields[$language['id_lang']][$field] = pSQL($this->{$field}

                        [$language['id_lang']]);
                } else

                if (in_array($field, $this->fieldsRequiredLang)) {
                    $fields[$language['id_lang']][$field] = pSQL($this->{$field}

                        [$defaultLanguage]);
                } else {
                    $fields[$language['id_lang']][$field] = '';
                }

            }

        }

        return $fields;
    }

    public function add($autodate = true, $nullValues = false) {

		$this->position = Topmenu::getHigherPosition() + 1;
        return parent::add($autodate, $nullValues);
    }
	
	public static function getHigherPosition() {

        $position = DB::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('topmenu')
        );

        return (is_numeric($position)) ? $position : -1;
    }

    public function update($nullValues = false) {

        $this->id_shop = Context::getContext()->shop->id;
        return parent::update($nullValues);
    }

    public function delete() {

        if (!isset($this->id) || !$this->id) {
            return;
        }

        $columnsWrap = TopMenuColumnWrap::getColumnWrapIds($this->id);

        foreach ($columnsWrap as $id_topmenu_columns_wrap) {
            $obj = new TopMenuColumnWrap($id_topmenu_columns_wrap);
            $obj->delete();
        }

        return parent::delete();
    }

    public static function menuHaveDepend($id_topmenu) {

        $sql = 'SELECT `id_topmenu_column`
                FROM `' . _DB_PREFIX_ . 'topmenu_columns`
                WHERE `id_topmenu_depend` = ' . (int) $id_topmenu;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getMenusId() {

        $sql = 'SELECT atp.`id_topmenu`
        FROM `' . _DB_PREFIX_ . 'topmenu` atp';
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getMenusFromIdCms($idCms) {

        $sql = 'SELECT atp.`id_topmenu`
        FROM `' . _DB_PREFIX_ . 'topmenu` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 1
        AND atp.`id_cms` = ' . (int) $idCms;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getMenusFromIdCmsCategory($idCmsCategory) {

        $sql = 'SELECT atp.`id_topmenu`
        FROM `' . _DB_PREFIX_ . 'topmenu` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 10
        AND atp.`id_cms_category` = ' . (int) $idCmsCategory;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function disableById($idMenu) {

        return Db::getInstance()->update('topmenu', [
            'active' => 0,
        ], 'id_topmenu = ' . (int) $idMenu);
    }

    public static function getMenus($id_lang, $active = true, $get_from_all_shops = false, $groupRestrict = false) {

        $sql_grouEPH_join = '';
        $sql_grouEPH_where = '';

        if ($groupRestrict && Group::isFeatureActive()) {
            $groups = TopMenu::getCustomerGroups();

            if (count($groups)) {
                $sql_grouEPH_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cg.`id_category` = ca.`id_category`)';
                $sql_grouEPH_where = 'AND IF (atp.`id_category` IS NULL OR atp.`id_category` = 0, 1, cg.`id_group` IN (' . implode(',', array_map('intval', $groups)) . '))';
            }

        }
		
        $sql = 'SELECT atp.*, atpl.*,
                cl.link_rewrite, cl.meta_title,
                cal.link_rewrite as category_link_rewrite, cal.name as category_name,
                m.name as manufacturer_name,
                s.name as supplier_name,
                ccl.name as cms_category_name
                FROM `' . _DB_PREFIX_ . 'topmenu` atp                
                LEFT JOIN `' . _DB_PREFIX_ . 'topmenu_lang` atpl ON (atp.`id_topmenu` = atpl.`id_topmenu` AND atpl.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms c ON (c.id_cms = atp.`id_cms`)
                ' . Shop::addSqlAssociation('cms', 'c', false, true, null, ($get_from_all_shops ? 'all' : false)) . '
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category cc ON (cc.id_cms_category = atp.`id_cms_category`)
              
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category_lang ccl ON (cc.id_cms_category = ccl.id_cms_category AND ccl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'category ca ON (ca.id_category = atp.`id_category`)
                ' . $sql_grouEPH_join . '
                LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cal ON (ca.id_category = cal.id_category AND cal.id_lang = ' . (int) $id_lang .  ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (atp.`id_manufacturer` = m.`id_manufacturer`)
                LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (atp.`id_supplier` = s.`id_supplier`)'
					. ($active ? '
                WHERE atp.`active` = 1 AND (atp.`active_desktop` = 1 || atp.`active_mobile` = 1)
                AND ((atp.`id_manufacturer` = 0 AND atp.`id_supplier` = 0 AND atp.`id_category` = 0 AND atp.`id_cms` = 0 AND atp.`id_cms_category` = 0)
                OR c.id_cms IS NOT NULL OR cc.id_cms_category IS NOT NULL OR m.id_manufacturer IS NOT NULL OR ca.id_category IS NOT NULL OR s.`id_supplier` IS NOT NULL)
                ' . $sql_grouEPH_where : '') . '
                GROUP BY atp.`id_topmenu`
                ORDER BY atp.`position`';
		
        $menus = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);

        if (is_array($menus) && count($menus)) {

            foreach ($menus as &$menu) {

                $menu['columnsWrap'] = TopMenuColumnWrap::getMenuColumnsWrap($menu['id_topmenu'], $id_lang, false);
                $menu['outPutName'] = self::getAdminOutputNameValue($menu, false);
                $menu['outPutNameValue'] = self::getAdminOutputNameValue($menu, true, 'menu', $menu['id_topmenu']);

                if (count($menu['columnsWrap'])) {

                    foreach ($menu['columnsWrap'] as &$columnWrap) {

                        //$columnWrap['outPutName'] = self::getAdminOutputNameValue($columnWrap, true, 'column', $columnWrap['id_columns_wrap']);

                        $columnWrap['columns'] = TopMenuColumn::getMenuColums($columnWrap['id_columns_wrap'], $id_lang, false);

                        if (count($columnWrap['columns'])) {

                            foreach ($columnWrap['columns'] as &$column) {
                                $column['columnElements'] = TopMenuElements::getMenuColumnElements($column['id_column'], $id_lang, false);

                                $column['outPutName'] = self::getAdminOutputNameValue($column, true, 'column');

                            }

                        }

                    }

                }

            }

        }


        return $menus;
    }

    public static function getCustomerGroups() {

        $groups = [];

        if (Group::isFeatureActive()) {

            if (Validate::isLoadedObject(Context::getContext()->customer)) {
                $groups = FrontController::getCurrentCustomerGroups();
            } else {
                $groups = [(int) Configuration::get('EPH_UNIDENTIFIED_GROUP')];
            }

        }

        sort($groups);
        return $groups;
    }

    public function outputMenuContent() {

        $this->context = Context::getContext();
        $menus = TopMenu::getMenus($this->context->language->id, true, false, true);

        $columnsWrap = TopMenuColumnWrap::getMenusColumnsWrap($menus, $this->context->language->id);
        $columns = TopMenuColumn::getMenusColums($columnsWrap, $this->context->language->id, true);
        $elements = TopMenuElements::getMenuColumnsElements($columns, $this->context->language->id, true, true);
        $advtmThemeCompatibility = (bool) Configuration::get('EPHTM_THEME_COMPATIBILITY_MODE') && ((bool) Configuration::get('EPHTM_MENU_CONT_HOOK') == 'top');
        $advtmResponsiveMode = ((bool) Configuration::get('EPHTM_RESPONSIVE_MODE') && (int) Configuration::get('EPHTM_RESPONSIVE_THRESHOLD') > 0);
        $advtmResponsiveToggleText = (Configuration::get('EPHTM_RESP_TOGGLE_TEXT', $this->context->language->id) !== false && Configuration::get('EPHTM_RESP_TOGGLE_TEXT', $this->context->language->id) != '' ? Configuration::get('EPHTM_RESP_TOGGLE_TEXT', $this->context->language->id) : 'Menu');
        $advtmResponsiveContainerClasses = trim(Configuration::get('EPHTM_RESP_CONT_CLASSES'));
        $advtmContainerClasses = trim(Configuration::get('EPHTM_CONT_CLASSES'));
        $advtmInnerClasses = trim(Configuration::get('EPHTM_INNER_CLASSES'));
        $advtmIsSticky = (Configuration::get('EPHTM_MENU_CONT_POSITION') == 'sticky');
        $advtmOpenMethod = (int) Configuration::get('EPHTM_SUBMENU_OPEN_METHOD');

        if ($advtmOpenMethod == 2) {
            $advtmInnerClasses .= ' phtm_open_on_click';
        } else {
            $advtmInnerClasses .= ' phtm_open_on_hover';
        }

        $advtmInnerClasses = trim($advtmInnerClasses);
        $customerGroups = TopMenu::getCustomerGroups();

        foreach ($menus as &$menu) {
            $menuHaveSub = count($columnsWrap[$menu['id_topmenu']]);
            $menu['link_output_value'] = $this->getLinkOutputValue($menu, 'menu', true, $menuHaveSub, true);

            foreach ($columnsWrap[$menu['id_topmenu']] as &$columnWrap) {
                $menu['link_output_value'] = $this->getLinkOutputValue($menu, 'menu', true, $menuHaveSub, true);

                foreach ($columns[$columnWrap['id_topmenu_columns_wrap']] as &$column) {
                    $column['link_output_value'] = $this->getLinkOutputValue($column, 'column', true);

                    foreach ($elements[$column['id_topmenu_column']] as &$element) {
                        $element['link_output_value'] = $this->getLinkOutputValue($element, 'element', true);
                    }

                }

            }

        }

        return [
            'advtmIsSticky'                   => $advtmIsSticky,
            'advtmOpenMethod'                 => $advtmOpenMethod,
            'advtmInnerClasses'               => $advtmInnerClasses,
            'advtmContainerClasses'           => $advtmContainerClasses,
            'advtmResponsiveContainerClasses' => $advtmResponsiveContainerClasses,
            'advtmResponsiveToggleText'       => $advtmResponsiveToggleText,
            'advtmResponsiveMode'             => $advtmResponsiveMode,
            'advtmThemeCompatibility'         => $advtmThemeCompatibility,
            'phtm_menus'                      => $menus,
            'phtm_columns_wrap'               => $columnsWrap,
            'phtm_columns'                    => $columns,
            'phtm_elements'                   => $elements,
            'customerGroups'                  => $customerGroups,
        ];

    }

    public function clearMenuCache() {

        $this->context->smarty->clearCompiledTemplate(_THEMES_DIR_ . 'menu/ephtopmenu.tpl');
        return $this->context->smarty->clearCache(null, 'ADTM');
    }

    public function getLinkOutputValue($row, $type, $withExtra = true, $haveSub = false, $first_level = false) {

        $link = $this->context->link;
        $_iso_lang = Language::getIsoById($this->context->cookie->id_lang);
        $return = false;
        $name = false;
        $image_legend = false;
        $icone = false;
        $url = false;
        $linkNotClickable = false;

        if (trim($row['link']) == '#') {
            $linkNotClickable = true;
        }

        $data_type = [
            'type' => null,
            'id'   => null,
        ];

        if ($type == 'menu') {
            $tag = 'id_topmenu';
        }

        if ($type == 'column') {
            $tag = 'id_topmenu_column';
        }

        if ($row['type'] == 1) {

            if (trim($row['name'])) {
                $name .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row['meta_title'], ENT_COMPAT, 'UTF-8');
            }

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

            $url .= $link->getCMSLink((int) $row['id_cms'], $row['link_rewrite']);
            $data_type['type'] = 'cms';
            $data_type['id'] = (int) $row['id_cms'];
        } else

        if ($row['type'] == 2) {

            if (trim($row['name'])) {
                $name .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            }

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

            if (trim($row['link'])) {
                $url .= htmlentities($row['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

        } else

        if ($row['type'] == 3) {

            if (trim($row['name'])) {
                $name .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row['category_name'], ENT_COMPAT, 'UTF-8');
            }

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

            $url .= $link->getCategoryLink((int) $row['id_category'], $row['category_link_rewrite']);
            $data_type['type'] = 'category';
            $data_type['id'] = (int) $row['id_category'];
        } else

        if ($row['type'] == 4) {

            if (trim($row['name'])) {
                $name .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row['manufacturer_name'], ENT_COMPAT, 'UTF-8') . '';
            }

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

            if ((int) $row['id_manufacturer']) {
                $data_type['type'] = 'brands';
                $data_type['id'] = (int) $row['id_manufacturer'];
                $url .= $link->getManufacturerLink((int) $row['id_manufacturer'], Tools::link_rewrite($row['manufacturer_name']));
            } else {
                $data_type['type'] = 'custom';
                $data_type['id'] = 'manufacturer';
                $url .= $link->getPageLink('manufacturer.php');
            }

        } else

        if ($row['type'] == 6) {
            $currentSearchQuery = trim(Tools::getValue('search_query', Tools::getValue('s')));
            $this->context->smarty->assign([
                'atm_form_action_link'       => $link->getPageLink('search'),
                'atm_search_id'              => 'search_query_atm_' . $type . '_' . $row[$tag],
                'atm_have_icon'              => trim($row['have_icon']),
                'atm_withExtra'              => $withExtra,
                'atm_icon_image_source'      => _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg'),
                'atm_search_value'           => (Tools::strlen($currentSearchQuery) ? $currentSearchQuery : trim(htmlentities($row['name'], ENT_COMPAT, 'UTF-8'))),
                'atm_is_autocomplete_search' => Configuration::get('EPHTM_AUTOCOMPLET_SEARCH') && version_compare(_EPH_VERSION_, '1.7.0.0', '<'),
                'atm_cookie_id_lang'         => $this->context->cookie->id_lang,
                'atm_pagelink_search'        => $link->getPageLink('search'),
            ]);
            $cache = Configuration::get('EPHTM_CACHE');

            if (!Configuration::get('EPH_SMARTY_CACHE')) {
                $cache = false;
            }

            if ($cache) {
                $adtmCacheId = sprintf('ADTM|%d|%s|%d|%s', $this->context->cookie->id_lang, (Validate::isLoadedObject($this->context->customer) && $this->context->customer->isLogged()), (Shop::isFeatureActive() ? $this->context->shop->id : 0));
                return $this->display(_THEMES_DIR_ . 'menu/ephtopmenu_search.tpl', $adtmCacheId);
            }

            return $this->display(_THEMES_DIR_ . 'menu/ephtopmenu_search.tpl');
        } else

        if ($row['type'] == 7) {
            $name = '';

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

            if (trim($row['link'])) {
                $url .= htmlentities($row['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }

        } else

        if ($row['type'] == 9) {
            $page = Meta::getMetaByPage($row['id_specific_page'], (int) $this->context->cookie->id_lang);
            $name = (!empty($page['title']) ? $page['title'] : $page['page']);

            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9]+)$#i', $page['page'], $m)) {
                $url = $link->getModuleLink($m[1], $m[2]);
                $data_type['id'] = '';
            } else {
                $url = $link->getPageLink($page['page']);
                $data_type['id'] = $page['page'];
            }

            $data_type['type'] = 'custom';

            if (trim($row['name'])) {
                $name = htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            }

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

        } else
        if ($row['type'] == 10) {

            return ['tpl' => './ephtopmenu_hook.tpl'];

        } else
        if ($row['type'] == 11) {

            return ['tpl' => './ephtopmenu_search_hook.tpl'];

        } else
        if ($row['type'] == 12) {

            $this->context->smarty->assign([
                //   'atm_form_custom_hook' => $row ['custom_hook'],
            ]);

            return $this->display(_THEMES_DIR_ . 'menu/ephtopmenu_custom_hook.tpl');

        } else

        if ($row['type'] == 13) {

            if (trim($row['name'])) {
                $name = htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $cmsCategory = new CMSCategory($row['id_cms_category']);
                $cmsCategoryName = $cmsCategory->getName((int) $this->context->cookie->id_lang);
                $name = htmlentities($cmsCategoryName, ENT_COMPAT, 'UTF-8');
            }

            $data_type['type'] = 'cms-category';
            $data_type['id'] = (int) $row['id_cms_category'];
            $url .= $link->getCMSCategoryLink((int) $row['id_cms_category'], $row['link_rewrite']);

            if ($withExtra && trim($row['have_icon'])) {
                $icone .= _EPH_IMG_ . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');
            }

        }

        $linkSettings = [
            'tag'           => 'a',
            'linkAttribute' => 'href',
            'url'           => ($linkNotClickable ? '#' : $url),
        ];

        if (!$first_level && Configuration::get('EPHTM_OBFUSCATE_LINK')) {
            $linkSettings['tag'] = 'span';
            $linkSettings['linkAttribute'] = 'data-href';
            $linkSettings['url'] = ($linkNotClickable ? '#' : self::getDataSerialized($url));
        }

        $return .= '<' . $linkSettings['tag'] . ' ' . $linkSettings['linkAttribute'] . '="' . $linkSettings['url'] . '" title="' . $name . '" ' . ($row['target'] ? 'target="' . htmlentities($row['target'], ENT_COMPAT, 'UTF-8') . '"' : '') . ' class="' . ($linkNotClickable ? 'ephtm_unclickable' : '') . (strpos($name, "\n") !== false ? ' a-multiline' : '') . ($first_level ? ' a-niveau1' : '') . '" ' . (!empty($data_type['type']) ? ' data-type="' . $data_type['type'] . '"' : '') . (isset($data_type['id']) && $data_type['id'] ? ' data-id="' . $data_type['id'] . '"' : '') . '>';

        if ($type == 'menu') {
            $return .= '<span class="phtm_menu_span phtm_menu_span_' . (int) $row['id_topmenu'] . '">';
        }

        if ($icone) {

            if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {
                $icone = '';

                if ($row['image_type'] == 'i-mi') {
                    $row['image_class'] = 'zmdi ' . $row['image_class'];
                }

                $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
            } else {
                $iconWidth = $iconHeight = false;
                $iconPath = dirname(__FILE__) . '/' . $type . '_icons/' . $row[$tag] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg');

                if (file_exists($iconPath) && is_readable($iconPath)) {
                    list($iconWidth, $iconHeight) = getimagesize($iconPath);
                }

                $icone = $link->getMediaLink($icone);
                $icone = str_replace('https://', '//', $icone);
                $icone = str_replace('http://', '//', $icone);

                if (trim($row['image_legend'])) {
                    $image_legend = htmlentities($row['image_legend'], ENT_COMPAT, 'UTF-8');
                } else {
                    $image_legend = $name;
                }

                $return .= '<img src="' . $icone . '" alt="' . $image_legend . '" title="' . $image_legend . '" ' . ((int) $iconWidth > 0 ? 'width="' . (int) $iconWidth . '" ' : '') . ((int) $iconHeight > 0 ? 'height="' . (int) $iconHeight . '" ' : '') . 'class="ephtm_menu_icon img-responsive img-fluid" />';
            }

        }

        $return .= nl2br($name);

        if ($type == 'menu') {
            $return .= '</span>';
        }

        $return .= '</' . $linkSettings['tag'] . '>';
        return $return;
    }

    public static function getAdminOutputNameValue($row, $withExtra = true, $type = false, $id = null) {

        $typeRow = 0;

        if (isset($row['type'])) {
            $typeRow = $row['type'];
        }

        $return = '';
        $context = Context::getContext();
        $_iso_lang = Language::getIsoById($context->cookie->id_lang);

        if ($id > 0) {

            if ($withExtra && trim($row['have_icon'])) {
                $icone = $row['image_hash'];
				if (!empty($row['image_hover'])) {
                	$icone_overlay = 'data-overlay="'.$row['image_hover'].'"';
            	}
				$return .= '<img src="' . $icone .'" style="width:64px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }

            if (isset($row['name']) && trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else

            if (isset($row['meta_title']) && trim($row['meta_title'])) {
                $return .= htmlentities($row['meta_title'], ENT_COMPAT, 'UTF-8');
            }

            return $return;
        }

        if ($row['type'] == 1) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= htmlentities($row['meta_title'], ENT_COMPAT, 'UTF-8');
            }

        } else

        if ($row['type'] == 2) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= 'No label';
            }

        } else

        if ($row['type'] == 3) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= htmlentities($row['category_name'], ENT_COMPAT, 'UTF-8');
            }

            return $return;

        } else

        if ($row['type'] == 4) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else

            if (!$row['id_manufacturer'] && !trim($row['name'])) {
                $return .= $this->l('No label');
            } else {
                $return .= htmlentities($row['manufacturer_name'], ENT_COMPAT, 'UTF-8') . '';
            }

        } else

        if ($row['type'] == 5) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            }

        } else

        if ($row['type'] == 6) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:32px;display:inline; background:white;margin-right:20px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
                }

            }

            if (trim($row['name'])) {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('No label');
            }

        } else

        if ($row['type'] == 7) {

            if ($withExtra && trim($row['have_icon'])) {

                if (in_array($row['image_type'], ['i-fa', 'i-mi'])) {

                    if ($row['image_type'] == 'i-mi') {
                        $row['image_class'] = 'zmdi ' . $row['image_class'];
                    }

                    $return .= '<i class="pmAtmIcon ' . $row['image_class'] . '"></i>';
                } else {
                    $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_topmenu_' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:64px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" class="iconOnly" />';
                }

            }

            if (trim($row['image_legend'])) {
                $return .= htmlentities($row['image_legend'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= 'Onglet Image';
            }

        } else

        if ($row['type'] == 9) {

            if (!trim($row['name'])) {
                $page = Meta::getMetaById($row['id_specific_page'], (int) $context->cookie->id_lang);
                $row['name'] = (!empty($page['title']) ? $page['title'] : $page['page']);
            }

            if ($withExtra && trim($row['have_icon'])) {
                $return .= '<img src="' . _EPH_IMG_ . $type . '_icons/' . $row['id_top' . $type] . '-' . $_iso_lang . '.' . ($row['image_type'] ?: 'jpg') . '" style="width:64px;" alt="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row['name'], ENT_COMPAT, 'UTF-8') . '" />';
            } else {
                $return .= htmlentities($row['name'], ENT_COMPAT, 'UTF-8');
            }

        } else

        if ($row['type'] == 10) {

            $return .= 'Zone Panier';

        }
		if ($row['type'] == 12) {

            $return .= 'Custom Hook';

        }

        if ($row['type'] == 11) {

            $return .= 'Zone de Recherche';

        }

        return $return;
    }

    public static function addSqlAssociation($table, $alias, $identifier, $inner_join = true, $on = null, $shops = false) {

        if (Shop::isFeatureActive()) {

            if ($shops == 'all') {
                $ids_shop = array_values(Shop::getCompleteListOfShopsID());
            } else

            if (is_array($shops) && count($shops)) {
                $ids_shop = array_values($shops);
            } else

            if (is_numeric($shops)) {
                $ids_shop = [$shops];
            } else {
                $ids_shop = array_values(Shop::getContextListShopID());
            }

            $table_alias = $alias . '_shop';

            if (strpos($table, '.') !== false) {
                list($table_alias, $table) = explode('.', $table);
            }

            return ($inner_join ? ' INNER' : ' LEFT') . ' JOIN `' . _DB_PREFIX_ . $table . '_shop` ' . $table_alias . '
                        ON ' . $table_alias . '.' . $identifier . ' = ' . $alias . '.' . $identifier . '
                        AND ' . $table_alias . '.id_shop IN (' . implode(', ', array_map('intval', $ids_shop)) . ') '
                . ($on ? ' AND ' . $on : '');
        }

        return '';
    }

    public function getType($type) {

        if ($type == 1) {
            return $this->l('CMS');
        } else

        if ($type == 2) {
            return $this->l('Link');
        } else

        if ($type == 3) {
            return $this->l('Category');
        } else

        if ($type == 6) {
            return $this->l('Search');
        } else

        if ($type == 7) {
            return $this->l('Only image or icon');
        } else

        if ($type == 9) {
            return $this->l('Specific page');
        } else

        if ($type == 10) {
            return $this->l('Hook Cart');
        } else

        if ($type == 11) {
            return $this->l('Hook Search');
        } else

        if ($type == 12) {
            return $this->l('Custom Hook');
        } else

        if ($type == 13) {
            return $this->l('CMS category');
        }

    }

    public static function displayMenuForm() {

        $context = Context::getContext();
        $menus = TopMenu::getMenus($context->cookie->id_lang, false);

        if (is_array($menus) && count($menus)) {

            foreach ($menus as &$menu) {
                $menu['columnsWrap'] = TopMenuColumnWrap::getMenuColumnsWrap($menu['id_topmenu'], $context->cookie->id_lang, false);

                if (count($menu['columnsWrap'])) {

                    foreach ($menu['columnsWrap'] as &$columnWrap) {
                        $columnWrap['columns'] = TopMenuColumn::getMenuColums($columnWrap['id_topmenu_columns_wrap'], $context->cookie->id_lang, false);

                        if (count($columnWrap['columns'])) {

                            foreach ($columnWrap['columns'] as &$column) {
                                $column['columnElements'] = TopMenuElements::getMenuColumnElements($column['id_topmenu_column'], $context->cookie->id_lang, false);

                            }

                        }

                    }

                }

            }

        }

        $cms = CMS::listCms((int) $context->cookie->id_lang);
        $cmsNestedCategories = TopMenu::getNestedCmsCategories((int) $context->cookie->id_lang);

        $cmsCategories = [];

        foreach ($cmsNestedCategories as $cmsCategory) {
            $cmsCategory['level_depth'] = (int) $cmsCategory['level_depth'];
            $cmsCategories[] = $cmsCategory;
            TopMenu::getChildrenCmsCategories($cmsCategories, $cmsCategory, null);
        }

        $alreadyDefinedCurrentIdMenu = $context->smarty->getTemplateVars('current_id_topmenu');

        if (empty($alreadyDefinedCurrentIdMenu)) {
            $currentIdMenu = Tools::getValue('id_topmenu', false);
        } else {
            $currentIdMenu = $alreadyDefinedCurrentIdMenu;
        }

        $ObjEphenyxTopMenuClass = false;
        $ObjEphenyxTopMenuColumnWrapClass = false;
        $ObjEphenyxTopMenuColumnClass = false;
        $ObjEphenyxTopMenuElementsClass = false;

        if (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editMenu') && Tools::getValue('id_topmenu')) {
                $ObjEphenyxTopMenuClass = new TopMenu(Tools::getValue('id_topmenu'));
            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editColumnWrap') && Tools::getValue('id_topmenu_columns_wrap')) {
                $ObjEphenyxTopMenuColumnWrapClass = new TopMenuColumnWrap(Tools::getValue('id_topmenu_columns_wrap'));
            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')) {

            if (Tools::getValue('editColumn') && Tools::getValue('id_topmenu_column')) {
                $ObjEphenyxTopMenuColumnClass = new TopMenuColumn(Tools::getValue('id_topmenu_column'));

            }

        }

        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')) {

            if (Tools::getValue('editElement') && Tools::getValue('id_topmenu_element')) {
                $ObjEphenyxTopMenuElementsClass = new TopMenuElements(Tools::getValue('id_topmenu_element'));
            }

        }

        $vars = [
            'menus'                => $menus,
            'current_id_topmenu'   => $currentIdMenu,
            'displayTabElement'    => (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayColumnElement' => (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayGroupElement'  => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')),
            'displayItemElement'   => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')),
            'editMenu'             => (Tools::getValue('editMenu') && Tools::getValue('id_topmenu')),
            'editColumn'           => (Tools::getValue('editColumnWrap') && Tools::getValue('id_topmenu_columns_wrap')),
            'editGroup'            => (Tools::getValue('editColumn') && Tools::getValue('id_topmenu_column')),
            'editElement'          => (Tools::getValue('editElement') && Tools::getValue('id_topmenu_element')),
            'cms'                  => $cms,
            'cmsCategories'        => $cmsCategories,
            'manufacturer'         => $manufacturer,
            'linkTopMenu'          => $context->link->getAdminLink('AdminTopMenu'),
            'ObjTopMenu'           => $ObjEphenyxTopMenuClass,
            'ObjTopMenuColumnWrap' => $ObjEphenyxTopMenuColumnWrapClass,
            'ObjTopMenuColumn'     => $ObjEphenyxTopMenuColumnClass,
            'ObjTopMenuElements'   => $ObjEphenyxTopMenuElementsClass,
        ];

        return self::fetchTemplate('tabs/display_form.tpl', $vars);
    }

    private static function getNestedCmsCategories($id_lang) {

        $nestedArray = [];
        $cmsCategories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            'SELECT cc.*, ccl.*
            FROM `' . _DB_PREFIX_ . 'cms_category` cc
            ' . Shop::addSqlAssociation('cms_category', 'cc') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` ccl ON cc.`id_cms_category` = ccl.`id_cms_category`
            WHERE ccl.`id_lang` = ' . (int) $id_lang . '
            AND cc.`id_parent` != 0
            ORDER BY cc.`level_depth` ASC, cc.`position` ASC'
        );
        $buff = [];

        foreach ($cmsCategories as $row) {
            $current = &$buff[$row['id_cms_category']];
            $current = $row;

            if (!$row['active']) {
                $current['name'] .= ' ' . '(disabled)';
            }

            if ((int) $row['id_parent'] == 1) {
                $nestedArray[$row['id_cms_category']] = &$current;
            } else {
                $buff[$row['id_parent']]['children'][$row['id_cms_category']] = &$current;
            }

        }

        return $nestedArray;
    }

    private static function getChildrenCmsCategories(&$cmsList, $cmsCategory, $levelDepth = false) {

        if (isset($cmsCategory['children']) && self::isFilledArray($cmsCategory['children'])) {

            foreach ($cmsCategory['children'] as $cmsInformation) {
                $cmsInformation['level_depth'] = (int) $cmsInformation['level_depth'];
                $cmsList[] = $cmsInformation;
                $this->getChildrenCmsCategories($cmsList, $cmsInformation, ($levelDepth !== false ? $levelDepth + 1 : $levelDepth));
            }

        }

    }

    public static function isFilledArray($array) {

        return $array && is_array($array) && count($array);
    }

    public static function fetchTemplate($tpl, $customVars = [], $configOptions = []) {

        //$data = $this->createTemplate('controllers/top_menu/' . $tpl);
        $context = Context::getContext();
        $admin_webpath = str_ireplace(_SHOP_CORE_DIR_, '', _EPH_ROOT_DIR_);
        $admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $admin_webpath);

        $tpl = $context->smarty->createTemplate('controllers/top_menu/' . $tpl, $context->smarty);
        $tpl->assign(
            [
                'linkTopMenu'       => $context->link->getAdminLink('AdminTopMenu'),
                'topMenu_img_dir'   => _EPH_MENU_DIR_,
                'menu_img_dir'      => __EPH_BASE_URI__ . $admin_webpath . '/themes/default/img/topmenu/',
                'current_iso_lang'  => Language::getIsoById($context->cookie->id_lang),
                'current_id_lang'   => (int) $context->language->id,
                'default_language'  => (int) Configuration::get('EPH_LANG_DEFAULT'),
                'languages'         => Language::getLanguages(false),
                'options'           => $configOptions,
                'shopFeatureActive' => Shop::isFeatureActive(),
            ]
        );

        if (is_array($customVars) && count($customVars)) {
            $tpl->assign($customVars);
        }

        return $tpl->fetch();

        //return $context->smarty->fetch('controllers/top_menu/' . $tpl);
    }

    public static function getReferentTopMenu() {

        return Db::getInstance()->executeS(
            'SELECT `id_topmenu`, `reference`
            FROM `' . _DB_PREFIX_ . 'topmenu`
            ORDER BY `id_topmenu`'
        );

    }

    public static function getIdTopMenuByRef($reference) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_topmenu`')
                ->from('topmenu')
                ->where('`reference` LIKE \'' . $reference . '\'')
        );
    }

}
