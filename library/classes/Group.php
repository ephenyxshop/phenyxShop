<?php

/**
 * Class GroupCore
 *
 * @since 1.9.1.0
 */
class GroupCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    protected static $cache_reduction = [];
    protected static $group_price_display_method = [];
    /** @var string Lastname */
    public $name;
    /** @var string Reduction */
    public $reduction;
    /** @var int Price display method (tax inc/tax exc) */
    public $price_display_method;
    /** @var bool Show prices */
    public $show_prices = 1;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'group',
        'primary'   => 'id_group',
        'multilang' => true,
        'fields'    => [
            'reduction'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'price_display_method' => ['type' => self::TYPE_INT, 'validate' => 'isPriceDisplayMethod', 'required' => true],
            'show_prices'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'name'                 => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    

    /**
     * GroupCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idCompany
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);
        // @codingStandardsIgnoreStart

        if ($this->id && !isset(Group::$group_price_display_method[$this->id])) {
            static::$group_price_display_method[$this->id] = $this->price_display_method;
        }

        // @codingStandardsIgnoreEnd
    }

    /**
     * @param int      $idLang
     * @param int|bool $idCompany
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getGroups($idLang, $idCompany = false) {

        
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT g.`id_group`, g.`reduction`, g.`price_display_method`, gl.`name`')
                ->from('group', 'g')
                ->leftJoin('group_lang', 'gl', 'g.`id_group` = gl.`id_group` AND gl.`id_lang` = ' . (int) $idLang)
                ->orderBy('g.`id_group` ASC')
        );
    }

    /**
     * @param int|null $idCustomer
     *
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getReduction($idCustomer = null) {

        // @codingStandardsIgnoreStart

        if (!isset(static::$cache_reduction['customer'][(int) $idCustomer])) {
            $idGroup = $idCustomer ? Customer::getDefaultGroupId((int) $idCustomer) : (int) Group::getCurrent()->id;
            static::$cache_reduction['customer'][(int) $idCustomer] = Group::getReductionByIdGroup($idGroup);
        }

        return static::$cache_reduction['customer'][(int) $idCustomer];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Return current group object
     * Use context
     *
     * @return Group Group object
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getCurrent() {

        static $groups = [];
        static $psUnidentifiedGroup = null;
        static $psCustomerGroup = null;

        if ($psUnidentifiedGroup === null) {
            $psUnidentifiedGroup = Configuration::get('EPH_UNIDENTIFIED_GROUP');
        }

        if ($psCustomerGroup === null) {
            $psCustomerGroup = Configuration::get('EPH_CUSTOMER_GROUP');
        }

        $customer = Context::getContext()->customer;

        if (Validate::isLoadedObject($customer)) {
            $idGroup = (int) $customer->id_default_group;
        } else {
            $idGroup = (int) $psUnidentifiedGroup;
        }

        if (!isset($groups[$idGroup])) {
            $groups[$idGroup] = new Group($idGroup);
        }

        

        return $groups[$idGroup];
    }

    /**
     * @param int $idGroup
     *
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getReductionByIdGroup($idGroup) {

        // @codingStandardsIgnoreStart

        if (!isset(static::$cache_reduction['group'][$idGroup])) {
            static::$cache_reduction['group'][$idGroup] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`reduction`')
                    ->from('group')
                    ->where('`id_group` = ' . (int) $idGroup)
            );
        }

        return static::$cache_reduction['group'][$idGroup];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getDefaultPriceDisplayMethod() {

        return Group::getPriceDisplayMethod((int) Configuration::get('EPH_CUSTOMER_GROUP'));
    }

    /**
     * @param int $idGroup
     *
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getPriceDisplayMethod($idGroup) {

        // @codingStandardsIgnoreStart

        if (!isset(Group::$group_price_display_method[$idGroup])) {
            static::$group_price_display_method[$idGroup] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`price_display_method`')
                    ->from('group')
                    ->where('`id_group` = ' . (int) $idGroup)
            );
        }

        return static::$group_price_display_method[$idGroup];
        // @codingStandardsIgnoreEnd
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isFeatureActive() {

        static $psGroupFeatureActive = null;

        if ($psGroupFeatureActive === null) {
            $psGroupFeatureActive = Configuration::get('EPH_GROUP_FEATURE_ACTIVE');
        }

        return $psGroupFeatureActive;
    }

    /**
     * This method is allow to know if there are other groups than the default ones
     *
     * @param string $table
     * @param bool   $hasActiveColumn
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false) {

        return (bool) (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue((new DbQuery())->select('COUNT(*)')->from('group')) > 3);
    }

    /**
     * Truncate all restrictions by module
     *
     * @param int $idModule
     *
     * @return bool
     *
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     */
    public static function truncateRestrictionsByModule($idModule) {

        return Db::getInstance()->delete('module_group', '`id_module` = ' . (int) $idModule);
    }

    /**
     * Adding restrictions modules to the group with id $id_group
     *
     * @param int   $idGroup
     * @param array $modules
     * @param array $shops
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function addModulesRestrictions($idGroup, $modules, $shops = [1]) {

        if (!is_array($modules) || !count($modules) || !is_array($shops) || !count($shops)) {
            return false;
        }

        // Delete all record for this group
        Db::getInstance()->delete('module_group', '`id_group` = ' . (int) $idGroup);

        $insert = [];

        foreach ($modules as $module) {

            foreach ($shops as $shop) {
                $insert[] = [
                    'id_module' => (int) $module,
                    'id_shop'   => (int) $shop,
                    'id_group'  => (int) $idGroup,
                ];
            }

        }

        return (bool) Db::getInstance()->insert('module_group', $insert);
    }

    /**
     * Add restrictions for a new module.
     * We authorize every groups to the new module
     *
     * @param int   $idModule
     * @param array $shops
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function addRestrictionsForModule($idModule, $shops = [1]) {

        if (!is_array($shops) || !count($shops)) {
            return false;
        }

        $res = true;

        foreach ($shops as $shop) {
            $res &= Db::getInstance()->execute(
                '
            INSERT INTO `' . _DB_PREFIX_ . 'module_group` (`id_module`, `id_shop`, `id_group`)
            (SELECT ' . (int) $idModule . ', ' . (int) $shop . ', id_group FROM `' . _DB_PREFIX_ . 'group`)'
            );
        }

        return $res;
    }

    /**
     * Light back office search for Group
     *
     * @param string $query Searched string
     *
     * @return array Corresponding groups
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function searchByName($query) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('g.*, gl.*')
                ->from('group', 'g')
                ->leftJoin('group_lang', 'gl', 'g.`id_group` = gl.`id_group`')
                ->where('`name` = \'' . pSQL($query) . '\'')
        );
    }

    /**
     * @param bool $count
     * @param int  $start
     * @param int  $limit
     * @param bool $shopFiltering
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getCustomers($count = false, $start = 0, $limit = 0, $shopFiltering = false) {

        if ($count) {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer', 'c')
                    ->leftJoin('customer', 'c', 'cg.`id_customer` = c.`id_customer`')
                    ->where('cg.`id_group` = ' . (int) $this->id)
                    ->where('c.`deleted` != 1')
            );
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cg.`id_customer`, c.*')
                ->from('customer_group', 'cg')
                ->leftJoin('customer', 'c', 'cg.`id_customer` = c.`id_customer`')
                ->where('cg.`id_group` = ' . (int) $this->id)
                ->where('c.`deleted` != 1')
                ->orderBy('cg.`id_customer` ASC')
                ->limit($limit > 0 ? (int) $limit : 0, $limit ? (int) $start : 0)
        );
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false) {

        Configuration::updateGlobalValue('EPH_GROUP_FEATURE_ACTIVE', '1');

        if (parent::add($autoDate, $nullValues)) {
            Category::setNewGroupForHome((int) $this->id);
            Carrier::assignGroupToAllCarriers((int) $this->id);

            return true;
        }

        return false;
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function update($autodate = true, $nullValues = false) {

        if (!Configuration::getGlobalValue('EPH_GROUP_FEATURE_ACTIVE') && $this->reduction > 0) {
            Configuration::updateGlobalValue('EPH_GROUP_FEATURE_ACTIVE', 1);
        }

        return parent::update($autodate, $nullValues);
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws PhenyxShopDatabaseException
     */
    public function delete() {

        if ($this->id == (int) Configuration::get('EPH_CUSTOMER_GROUP')) {
            return false;
        }

        if (parent::delete()) {
            Db::getInstance()->delete('cart_rule_group', '`id_group` = ' . (int) $this->id);
            Db::getInstance()->delete('customer_group', '`id_group` = ' . (int) $this->id);
            Db::getInstance()->delete('category_group', '`id_group` = ' . (int) $this->id);
            Db::getInstance()->delete('group_reduction', '`id_group` = ' . (int) $this->id);
            Db::getInstance()->delete('product_group_reduction_cache', '`id_group` = ' . (int) $this->id);
            $this->truncateModulesRestrictions($this->id);

            // Add default group (id 3) to customers without groups
            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'customer_group` (
                SELECT c.id_customer, ' . (int) Configuration::get('EPH_CUSTOMER_GROUP') . ' FROM `' . _DB_PREFIX_ . 'customer` c
                LEFT JOIN `' . _DB_PREFIX_ . 'customer_group` cg
                ON cg.id_customer = c.id_customer
                WHERE cg.id_customer IS NULL)'
            );

            // Set to the customer the default group
            // Select the minimal id from customer_group
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'customer` cg
                SET id_default_group =
                    IFNULL((
                        SELECT min(id_group) FROM `' . _DB_PREFIX_ . 'customer_group`
                        WHERE id_customer = cg.id_customer),
                        ' . (int) Configuration::get('EPH_CUSTOMER_GROUP') . ')
                WHERE `id_default_group` = ' . (int) $this->id
            );

            return Db::getInstance()->delete('module_group', '`id_group` = ' . (int) $this->id);
        }

        return false;
    }

    /**
     * Truncate all modules restrictions for the group
     *
     * @param int $idGroup
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     */
    public static function truncateModulesRestrictions($idGroup) {

        return Db::getInstance()->delete(
            'module_group',
            '`id_group` = ' . (int) $idGroup
        );
    }

}
