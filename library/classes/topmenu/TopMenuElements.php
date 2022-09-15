<?php

class TopMenuElementsCore extends PhenyxObjectModel {

    public $id;
    public $link;
    public $name;
    public $id_topmenu_column;
    public $id_cms;
    public $id_cms_category;
    public $id_specific_page;
    public $have_icon;
    public $image_type;
    public $image_legend;
    public $image_class;
    public $privacy;
    public $type;
    public $id_column_depend;
    public $position = 0;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $target;

    public static $definition = [
        'table'     => 'topmenu_elements',
        'primary'   => 'id_topmenu_elements',
        'multishop' => false,
        'multilang' => true,
        'fields'    => [
            'id_topmenu_column' => ['type' => self::TYPE_INT, 'required' => true],
            'id_cms'            => ['type' => self::TYPE_INT],
            'id_cms_category'   => ['type' => self::TYPE_INT],
            'id_specific_page'  => ['type' => self::TYPE_INT],
            'id_column_depend'  => ['type' => self::TYPE_INT],
            'position'          => ['type' => self::TYPE_INT],
            'privacy'           => ['type' => self::TYPE_INT],
            'target'            => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'active'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'type'              => ['type' => self::TYPE_INT, 'required' => true],
            'custom_class'      => ['type' => self::TYPE_STRING, 'size' => 255],

            'name'              => ['type' => TYPE_STRING, 'validate' => 'isCatalogName', 'lang' => true, 'size' => 255],
            'link'              => ['type' => TYPE_STRING, 'validate' => 'isUrl', 'lang' => true, 'size' => 255],
            'have_icon'         => ['type' => TYPE_STRING, 'validate' => 'isBool', 'lang' => true],
            'image_type'        => ['type' => TYPE_STRING, 'validate' => 'isString', 'lang' => true],
            'image_legend'      => ['type' => TYPE_STRING, 'validate' => 'isCatalogName', 'lang' => true, 'size' => 255],
            'image_class'       => ['type' => TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
        ],
    ];
    public function __construct($id_topmenu_elements = null, $id_lang = null) {

        parent::__construct($id_topmenu_elements, $id_lang);
        $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
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

    public function delete() {

        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {

            if (file_exists(_EPH_ROOT_DIR_ . '/plugins/topmenu/element_icons/' . (int) $this->id . '-' . $language['iso_code'] . '.' . (isset($this->image_type[$language['id_lang']]) && !preg_match('/^i-(fa|mi)$/', $this->image_type[$language['id_lang']]) ? $this->image_type[$language['id_lang']] : 'jpg'))) {
                @unlink(_EPH_ROOT_DIR_ . '/plugins/topmenu/element_icons/' . (int) $this->id . '-' . $language['iso_code'] . '.' . (isset($this->image_type[$language['id_lang']]) && !preg_match('/^i-(fa|mi)$/', $this->image_type[$language['id_lang']]) ? $this->image_type[$language['id_lang']] : 'jpg'));
            }

        }

        Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'topmenu_elements` WHERE `id_topmenu_elements`=' . (int) $this->id);
        Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'topmenu_elements_lang` WHERE `id_topmenu_elements`=' . (int) $this->id);
        return true;
    }

    public static function getMenuColumnElements($id_topmenu_column, $id_lang, $active = true, $groupRestrict = false) {

        $sql_groups_join = '';
        $sql_groups_where = '';
		$file = fopen("testgetMenuColumnElements.txt","w");
        $sql = 'SELECT ate.*, atel.*,
        cl.link_rewrite, cl.meta_title
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` ate
                LEFT JOIN `' . _DB_PREFIX_ . 'topmenu_elements_lang` atel ON (ate.`id_topmenu_elements` = atel.`id_topmenu_elements` AND atel.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms c ON (c.id_cms = ate.`id_cms`)
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON (c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category cc ON (cc.id_cms_category = ate.`id_cms_category`)
                LEFT JOIN ' . _DB_PREFIX_ . 'cms_category_lang ccl ON (cc.id_cms_category = ccl.id_cms_category AND ccl.id_lang = ' . (int) $id_lang . ')
                WHERE ' . ($active ? ' ate.`active` = 1 AND (ate.`active_desktop` = 1 || ate.`active_mobile` = 1) AND' : '') . ' ate.`id_topmenu_column` = ' . (int) $id_topmenu_column . '
                ' . ($active ? 'AND ((ate.`id_cms` = 0 AND ate.`id_cms_category` = 0)
                OR c.id_cms IS NOT NULL OR cc.id_cms_category IS NOT NULL )' : '') . '
                GROUP BY ate.`id_topmenu_elements`
                ORDER BY ate.`position`';
		fwrite($file,$sql);
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getMenuColumnsElements($menus, $id_lang, $active = true, $groupRestrict = false) {

        $elements = [];

        if (is_array($menus) && count($menus)) {

            foreach ($menus as $columns) {

                if (is_array($columns) && count($columns)) {

                    foreach ($columns as $column) {
                        $elements[$column['id_topmenu_column']] = self::getMenuColumnElements($column['id_topmenu_column'], $id_lang, $active, $groupRestrict);
                    }

                }

            }

        }

        return $elements;
    }

    public static function getElementIds($ids_column) {

        if (!is_array($ids_column)) {
            $ids_column = [(int) $ids_column];
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS('
        SELECT `id_topmenu_elements`
        FROM ' . _DB_PREFIX_ . 'topmenu_elements
        WHERE `id_topmenu_column` IN(' . implode(',', array_map('intval', $ids_column)) . ')');
        $elements = [];

        foreach ($result as $row) {
            $elements[] = $row['id_topmenu_elements'];
        }

        return $elements;
    }

    public static function getElementsFromIdCategory($idCategory) {

        $sql = 'SELECT atp.`id_topmenu_elements`
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 3
        AND atp.`id_category` = ' . (int) $idCategory;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getElementsFromIdCms($idCms) {

        $sql = 'SELECT atp.`id_topmenu_elements`
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 1
        AND atp.`id_cms` = ' . (int) $idCms;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getElementsFromIdCmsCategory($idCmsCategory) {

        $sql = 'SELECT atp.`id_topmenu_elements`
        FROM `' . _DB_PREFIX_ . 'topmenu_elements` atp
        WHERE atp.`active` = 1
        AND atp.`type` = 10
        AND atp.`id_cms_category` = ' . (int) $idCmsCategory;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function disableById($idElement) {

        return Db::getInstance()->update('topmenu_elements', [
            'active' => 0,
        ], 'id_topmenu_elements = ' . (int) $idElement);
    }

    public static function getIdElementCategoryDepend($id_topmenu_column, $id_category) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue('SELECT `id_topmenu_elements`
                FROM `' . _DB_PREFIX_ . 'topmenu_elements`
                WHERE `id_column_depend` = ' . (int) $id_topmenu_column . ' AND `id_category` = ' . (int) $id_category);
    }

    public static function getIdElementCmsDepend($idColumn, $idCms) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue('SELECT `id_topmenu_elements`
                FROM `' . _DB_PREFIX_ . 'topmenu_elements`
                WHERE `id_column_depend` = ' . (int) $idColumn . ' AND `id_cms` = ' . (int) $idCms);
    }

}
