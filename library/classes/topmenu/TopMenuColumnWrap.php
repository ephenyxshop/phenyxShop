<?php

class TopMenuColumnWrapCore extends PhenyxObjectModel {

    //public $id;

    public $id_topmenu;
    public $internal_name;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $width;
    public $privacy;
    public $position;
    public $value_over;
    public $value_under;
    public $bg_color;
    public $txt_color_column;
    public $txt_color_column_over;
    public $txt_color_element;
    public $txt_color_element_over;
    public $id_menu_depend;

    public static $definition = [
        'table'     => 'topmenu_columns_wrap',
        'primary'   => 'id_topmenu_columns_wrap',
        'multishop' => false,
        'multilang' => true,
        'fields'    => [
            'id_topmenu'             => ['type' => self::TYPE_INT, 'required' => true],
            'id_menu_depend'         => ['type' => self::TYPE_INT],
            'internal_name'          => ['type' => self::TYPE_STRING],
            'bg_color'               => ['type' => self::TYPE_STRING],
            'txt_color_column'       => ['type' => self::TYPE_STRING],
            'txt_color_column_over'  => ['type' => self::TYPE_STRING],
            'txt_color_element'      => ['type' => self::TYPE_STRING],
            'txt_color_element_over' => ['type' => self::TYPE_STRING],
            'position'               => ['type' => self::TYPE_INT],
            'width'                  => ['type' => self::TYPE_INT],
            'custom_class'           => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 255],
            'privacy'                => ['type' => self::TYPE_INT],
            'active'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            'value_over'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
            'value_under'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
        ],
    ];
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
    }

    public function add($autodate = true, $nullValues = false) {

        return parent::add($autodate, $nullValues);
    }

    public function getTranslationsFieldsChild() {

        parent::validateFieldsLang();
        $fields = [];
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {
            $fields[$language['id_lang']]['id_lang'] = $language['id_lang'];
            $fields[$language['id_lang']][$this->identifier] = (int) $this->id;
            $fields[$language['id_lang']]['value_over'] = isset($this->value_over[$language['id_lang']]) ? pSQL($this->value_over[$language['id_lang']], true) : '';
            $fields[$language['id_lang']]['value_under'] = isset($this->value_under[$language['id_lang']]) ? pSQL($this->value_under[$language['id_lang']], true) : '';
        }

        return $fields;
    }

    public function delete() {

        Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'topmenu_columns_wrap` WHERE `id_topmenu_columns_wrap`=' . (int) $this->id);
        Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'topmenu_columns_wrap_lang` WHERE `id_topmenu_columns_wrap`=' . (int) $this->id);
        $columns = TopMenuColumn::getColumnIds((int) $this->id);

        foreach ($columns as $id_topmenu_column) {
            $obj = new TopMenuColumn($id_topmenu_column);
            $obj->delete();
        }

        return true;
    }

    public static function getMenuColumnsWrap($id_topmenu, $id_lang, $active = true) {

        $sql = 'SELECT atmcw.`id_topmenu_columns_wrap` as id_columns_wrap, atmcw.*, atmcwl.*
                FROM `' . _DB_PREFIX_ . 'topmenu_columns_wrap` atmcw
                LEFT JOIN `' . _DB_PREFIX_ . 'topmenu_columns_wrap_lang` atmcwl ON (atmcw.`id_topmenu_columns_wrap` = atmcwl.`id_topmenu_columns_wrap` AND atmcwl.`id_lang` = ' . (int) $id_lang . ')
                WHERE ' . ($active ? ' atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1) AND' : '') . ' atmcw.`id_topmenu` = ' . (int) $id_topmenu . '
                ORDER BY atmcw.`position`';

        $ColumnsWrap = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);

        return $ColumnsWrap;
    }

    public static function getMenusColumnsWrap($menus, $id_lang) {

        $columnWrap = [];

        if (is_array($menus) && count($menus)) {

            foreach ($menus as $menu) {
                $columnWrap[$menu['id_topmenu']] = self::getMenuColumnsWrap($menu['id_topmenu'], $id_lang);
            }

        }

        return $columnWrap;
    }

    public static function getColumnsWrap($id_lang = false, $active = true) {

        $sql = 'SELECT atmcw.* ' . ($id_lang ? ',atmcwl.*' : '') . '
                FROM `' . _DB_PREFIX_ . 'topmenu_columns_wrap` atmcw
                ' . ($id_lang ? 'LEFT JOIN `' . _DB_PREFIX_ . 'topmenu_columns_wrap_lang` atmcwl ON (atmcw.`id_topmenu_columns_wrap` = atmcwl.`id_topmenu_columns_wrap` AND atmcwl.`id_lang` = ' . (int) $id_lang . ')' : '') . '
                WHERE 1 ' . ($active ? 'AND atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1)' : '') . '
                ORDER BY atmcw.`position`';
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($sql);
    }

    public static function getColumnWrapIds($ids_menu) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS('
        SELECT `id_topmenu_columns_wrap`
        FROM ' . _DB_PREFIX_ . 'topmenu_columns_wrap
        WHERE `id_topmenu` IN(' . pSQL($ids_menu) . ')');
        $columnsWrap = [];

        foreach ($result as $row) {
            $columnsWrap[] = $row['id_topmenu_columns_wrap'];
        }

        return $columnsWrap;
    }

    public static function getReferentTopMenuWrap() {

        return Db::getInstance()->executeS(
            'SELECT `id_topmenu_columns_wrap`, `reference`
            FROM `' . _DB_PREFIX_ . 'topmenu_columns_wrap`
            ORDER BY `id_topmenu_columns_wrap`'
        );

    }

    public static function getIdTopMenuColumnWrapByRef($reference) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_topmenu_columns_wrap`')
                ->from('topmenu_columns_wrap')
                ->where('`reference` LIKE \'' . $reference . '\'')
        );
    }

    public static function getIdTopMenuByColumnWrapRef($parentReference) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_topmenu`')
                ->from('topmenu')
                ->where('`reference` LIKE \'' . $parentReference . '\'')
        );
    }

}
