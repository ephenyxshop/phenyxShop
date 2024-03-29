<?php

/**
 * Class ManufacturerCore
 *
 * @since 1.9.1.0
 */
class ManufacturerCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * Return name from id
     *
     * @param int $id_manufacturer Manufacturer ID
     *
     * @return string name
     */
    protected static $cacheName = [];
    public $id;
    /** @var int manufacturer ID //FIXME is it really usefull...? */
    public $id_manufacturer;
    /** @var string Name */
    public $name;
    /** @var string A description */
    public $description;
    /** @var string A short description */
    public $short_description;
    /** @var int Address */
    public $id_address;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Friendly URL */
    public $link_rewrite;
    /** @var string Meta title */
    public $meta_title;
    /** @var string Meta keywords */
    public $meta_keywords;
    /** @var string Meta description */
    public $meta_description;
    /** @var bool active */
    public $active;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'manufacturer',
        'primary'   => 'id_manufacturer',
        'multilang' => true,
        'fields'    => [
            'name'              => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
            'active'            => ['type' => self::TYPE_BOOL],
            'date_add'          => ['type' => self::TYPE_DATE],
            'date_upd'          => ['type' => self::TYPE_DATE],

            /* Lang fields */
            'description'       => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'short_description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'meta_description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName'],
        ],
    ];
    

    /**
     * ManufacturerCore constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        $this->link_rewrite = $this->getLink();
        $this->image_dir = _EPH_MANU_IMG_DIR_;
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getLink() {

        return Tools::link_rewrite($this->name);
    }

    /**
     * Return manufacturers
     *
     * @param bool     $getNbProducts [optional] return products numbers for each
     * @param int      $idLang
     * @param bool     $active
     * @param bool|int $p
     * @param bool|int $n
     * @param bool     $allGroup
     *
     * @param bool     $groupBy
     *
     * @return false|array Manufacturers
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @version 1.0.5 Set $groupBy to true by default and deprecate it.
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getManufacturers($getNbProducts = false, $idLang = 0, $active = true, $p = false, $n = false, $allGroup = false, $groupBy = true) {

        if (!$groupBy) {
            Tools::displayParameterAsDeprecated('$groupBy');
        }

        if (!$idLang) {
            $idLang = (int) Configuration::get('EPH_LANG_DEFAULT');
        }

        if (!Group::isFeatureActive()) {
            $allGroup = true;
        }

        $manufacturers = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*, ml.`description`, ml.`short_description`')
                ->from('manufacturer', 'm')
                ->innerJoin('manufacturer_lang', 'ml', 'm.`id_manufacturer` = ml.`id_manufacturer`')
                ->where('ml.`id_lang` = ' . (int) $idLang)
                ->where($active ? 'm.`active` = 1' : '')
                ->groupBy($groupBy ? 'm.`id_manufacturer`' : '')
                ->orderBy('m.`name` ASC')
                ->limit($p ? (int) $n : 0, $p ? ((int) $p - 1) * (int) $n : 0)
        );

        if ($manufacturers === false) {
            return false;
        }

        if ($getNbProducts) {
            $sqlGroups = '';

            if (!$allGroup) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sqlGroups = (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1');
            }

            $categoryGroupSql = (new DbQuery())
                ->select('1')
                ->from('category_group', 'cg')
                ->leftJoin('category_product', 'cp', 'cp.`id_category` = cg.`id_category`')
                ->where('p.`id_product` = cp.`id_product`')
                ->where('cg.`id_group` ' . $sqlGroups);
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('p.`id_manufacturer`, COUNT(DISTINCT p.`id_product`) AS `nb_products`')
                    ->from('product', 'p')
                    ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
                    ->where('p.`id_manufacturer` != 0')
                    ->where('p.`visibility` NOT IN ("none")')
                    ->where($active ? 'p.`active` = 1' : '')
                    ->where(Group::isFeatureActive() && $allGroup ? '' : 'EXISTS (' . $categoryGroupSql->build() . ')')
                    ->groupBy('p.`id_manufacturer`')
            );

            $counts = [];

            if (is_array($results) && !empty($results)) {

                foreach ($results as $result) {
                    $counts[(int) $result['id_manufacturer']] = (int) $result['nb_products'];
                }

            }

            if (count($counts)) {

                foreach ($manufacturers as $key => $manufacturer) {

                    if (array_key_exists((int) $manufacturer['id_manufacturer'], $counts)) {
                        $manufacturers[$key]['nb_products'] = $counts[(int) $manufacturer['id_manufacturer']];
                    } else {
                        $manufacturers[$key]['nb_products'] = 0;
                    }

                }

            }

        }

        $totalManufacturers = count($manufacturers);
        $rewriteSettings = (int) Configuration::get('EPH_REWRITING_SETTINGS');

        for ($i = 0; $i < $totalManufacturers; $i++) {
            $manufacturers[$i]['link_rewrite'] = ($rewriteSettings ? Tools::link_rewrite($manufacturers[$i]['name']) : 0);
        }

        return $manufacturers;
    }

    /**
     * @param int $idManufacturer
     *
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNameById($idManufacturer) {

        if (!isset(static::$cacheName[$idManufacturer])) {
            static::$cacheName[$idManufacturer] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('name')
                    ->from('manufacturer')
                    ->where('`id_manufacturer` = ' . (int) $idManufacturer)
                    ->where('`active` = 1')
            );
        }

        return static::$cacheName[$idManufacturer];
    }

    /**
     * @param string $name
     *
     * @return bool|int
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getIdByName($name) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_manufacturer`')
                ->from('manufacturer')
                ->where('`name` = \'' . pSQL($name) . '\'')
        );

        if (isset($result['id_manufacturer'])) {
            return (int) $result['id_manufacturer'];
        }

        return false;
    }

    /**
     * @param int          $idManufacturer
     * @param int          $idLang
     * @param int          $p
     * @param int          $n
     * @param string|null  $orderBy
     * @param string|null  $orderWay
     * @param bool         $getTotal
     * @param bool         $active
     * @param bool         $activeCategory
     * @param Context|null $context
     *
     * @return array|bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getProducts(
        $idManufacturer,
        $idLang,
        $p,
        $n,
        $orderBy = null,
        $orderWay = null,
        $getTotal = false,
        $active = true,
        $activeCategory = true,
        Context $context = null
    ) {

        if (!$context) {
            $context = Context::getContext();
        }

        $front = true;

        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($p < 1) {
            $p = 1;
        }

        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'name';
        }

        if (empty($orderWay)) {
            $orderWay = 'ASC';
        }

        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }

        $groups = FrontController::getCurrentCustomerGroups();
        $sqlGroups = count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1';

        /* Return only the number of products */

        if ($getTotal) {
            $categoryGroupSql = (new DbQuery())
                ->select('1')
                ->from('category_group', 'cg')
                ->leftJoin('category_product', 'cp', 'cp.`id_category` = cg.`id_category`')
                ->join($activeCategory ? 'INNER JOIN `' . _DB_PREFIX_ . 'category` ca ON (cp.`id_category` = ca.`id_category` AND ca.`active` = 1)' : '')
                ->where('p.`id_product` = cp.`id_product`')
                ->where('cg.`id_group` ' . $sqlGroups);
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('p.`id_product`')
                    ->from('product', 'p')
                    ->where('p.`id_manufacturer` = ' . (int) $idManufacturer)
                    ->where($active ? 'p.`active` = 1' : '')
                    ->where($front ? 'p.`visibility` IN ("both", "catalog")' : '')
                    ->where('EXISTS (' . $categoryGroupSql->build() . ')')
            );

            return (int) count($result);
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderBy = pSQL($orderBy[0]) . '.`' . pSQL($orderBy[1]) . '`';
        }

        if ($orderBy == 'price') {
            $aliasWithDot = 'p.';
        } else if ($orderBy == 'name') {
            $aliasWithDot = 'pl.';
        } else if ($orderBy == 'manufacturer_name') {
            $orderBy = 'name';
            $aliasWithDot = 'm.';
        } else if ($orderBy == 'quantity') {
            $aliasWithDot = 'stock.';
        } else {
            $aliasWithDot = 'p.';
        }

        $sql = (new DbQuery())
            ->select('p.*, stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')
            ->select(Combination::isFeatureActive() ? 'product_attribute_shop.`minimal_quantity` AS `product_attribute_minimal_quantity`, IFNULL(product_attribute_shop.`id_product_attribute`,0) AS `id_product_attribute`' : '')
            ->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`')
            ->select('pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` AS `id_image`, il.`legend`, m.`name` AS `manufacturer_name`')
            ->select('DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00", INTERVAL ' . (Validate::isUnsignedInt(Configuration::get('EPH_NB_DAYS_NEW_PRODUCT')) ? (int) Configuration::get('EPH_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY)) > 0 AS `new`')
            ->from('product', 'p')
            ->join(Combination::isFeatureActive() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.`id_shop` = ' . (int) $context->company->id . ')' : '')
            ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')
            ->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->company->id)
            ->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $idLang)
            ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->join(Product::sqlStock('p', 0))
            ->where('pl.`id_lang` = ' . (int) $idLang );

        if (Group::isFeatureActive() || $activeCategory) {
            $sql->innerJoin('category_product', 'cp', 'p.`id_product` = cp.`id_product`');

            if (Group::isFeatureActive()) {
                $sql->innerJoin('category_group', 'cg', 'cp.`id_category` = cg.`id_category`');
                $sql->where('cg.`id_group` ' . $sqlGroups);
            }

            if ($activeCategory) {
                $sql->innerJoin('category', 'ca', 'cp.`id_category` = ca.`id_category`');
                $sql->where('ca.`active` = 1');
            }

        }

        $sql->where('p.`id_manufacturer` = ' . (int) $idManufacturer);
        $sql->where($active ? '`p`.`active` = 1' : '');
        $sql->where($front ? '`p`.`visibility` IN ("both", "catalog")' : '');
        $sql->groupBy('p.`id_product`');
        $sql->orderBy($aliasWithDot . '`' . bqSQL($orderBy) . '` ' . pSQL($orderWay));
        $sql->limit((int) $n, ((int) $p - 1) * (int) $n);
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * Specify if a manufacturer already in base
     *
     * @param int $idManufacturer Manufacturer id
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function manufacturerExists($idManufacturer) {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_manufacturer`')
                ->from('manufacturer', 'm')
                ->where('m.`id_manufacturer` = ' . (int) $idManufacturer)
        );

        return isset($row['id_manufacturer']);
    }

    /**
     * Delete several objects from database
     *
     * return boolean Deletion result
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @param array $selection
     *
     * @return bool
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function deleteSelection($selection) {

        if (!is_array($selection)) {
            die(Tools::displayError());
        }

        $result = true;

        foreach ($selection as $id) {
            $this->id = (int) $id;
            $this->id_address = Manufacturer::getManufacturerAddress();
            $result = $result && $this->delete();
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function delete() {

        $address = new Address($this->id_address);

        if (Validate::isLoadedObject($address) && !$address->delete()) {
            return false;
        }

        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('manufacturer', $this->id);
        }

        if (parent::delete()) {
            CartRule::cleanProductRuleIntegrity('manufacturers', $this->id);

            return $this->deleteImage();
        }

        return false;
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getProductsLite($idLang) {

        $context = Context::getContext();
        $front = true;

        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('p.`id_product`, pl.`name`')
                ->from('product', 'p')
                ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')
                ->where('pl.`id_lang` = ' . (int) $idLang . $context->company->addSqlRestrictionOnLang('pl'))
                ->where('p.`id_manufacturer` = ' . (int) $this->id)
                ->where($front ? 'p.`visibility` IN ("both", "catalog")' : '')
        );
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAddresses($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('a.*, cl.`name` AS `country`, s.`name` AS `state`')
                ->from('address', 'a')
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = a.`id_country`')
                ->leftJoin('state', 's', 's.`id_state` = a.`id_state`')
                ->where('cl.`id_lang` = ' . (int) $idLang)
                ->where('`id_manufacturer` = ' . (int) $this->id)
                ->where('a.`deleted` = 0')
        );
    }

    

    

    /**
     * @param bool $nullValues
     *
     * @return bool Indicates whether updating succeeded
     * @throws PhenyxShopDatabaseException
     */
    public function update($nullValues = null) {

        if ('EPH_PAGE_CACHE_ENABLED') {
            PageCache::invalidateEntity('manufacturer', $this->id);
        }

        return parent::update($nullValues);
    }

    /**
     * @return bool|false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    protected function getManufacturerAddress() {

        if (!(int) $this->id) {
            return false;
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_address`')
                ->from('address')
                ->where('`id_manufacturer` = ' . (int) $this->id)
        );
    }

}
