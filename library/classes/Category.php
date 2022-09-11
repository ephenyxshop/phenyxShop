<?php

/**
 * Class CategoryCore
 *
 * @since 1.8.1.0
 */
class CategoryCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'category',
        'primary'        => 'id_category',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            'nleft'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'nright'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'level_depth'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'display_from_sub' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'id_parent'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_shop_default'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'is_root_category' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position'         => ['type' => self::TYPE_INT],
            'date_add'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            /* Lang fields */
            'name'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
            'link_rewrite'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
            'description'      => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'title_tab'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'size' => 128],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];
    protected static $_links = [];
    /** @var int category ID */
    public $id_category;
    /** @var string Name */
    public $name;
    /** @var bool Status for display */
    public $active = 1;
    /** @var bool Status for displaying subcategory products */
    public $display_from_sub = 1;
    /** @var  int category position */
    public $position;
    /** @var string Description */
    public $description;
    public $title_tab;
    /** @var int Parent category ID */
    public $id_parent;
    /** @var int default Category id */
    public $id_category_default;
    /** @var int Parents number */
    public $level_depth;
    /** @var int Nested tree model "left" value */
    public $nleft;
    /** @var int Nested tree model "right" value */
    public $nright;
    /** @var string string used in rewrited URL */
    public $link_rewrite;
    /** @var string Meta title */
    public $meta_title;
    /** @var string Meta keywords */
    public $meta_keywords;
    /** @var string Meta description */
    public $meta_description;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool is Category Root */
    public $is_root_category;
    /** @var int */
    public $id_shop_default;
    public $groupBox;
    /** @var string id_image is the category ID when an image exists and 'default' otherwise */
    public $id_image = 'default';
    protected $webserviceParameters = [
        'objectsNodeName' => 'categories',
        'hidden_fields'   => ['nleft', 'nright', 'groupBox'],
        'fields'          => [
            'id_parent'             => ['xlink_resource' => 'categories'],
            'level_depth'           => ['setter' => false],
            'nb_products_recursive' => ['getter' => 'getWsNbProductsRecursive', 'setter' => false],
        ],
        'associations'    => [
            'categories' => ['getter' => 'getChildrenWs', 'resource' => 'category'],
            'products'   => ['getter' => 'getProductsWs', 'resource' => 'product'],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * CategoryCore constructor.
     *
     * @param null $idCategory
     * @param null $idLang
     * @param null $idShop
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public function __construct($idCategory = null, $idLang = null, $idShop = null) {

        parent::__construct($idCategory, $idLang, $idShop);
        $this->id_image = ($this->id && file_exists(_EPH_CAT_IMG_DIR_ . (int) $this->id . '.jpg')) ? (int) $this->id : false;
        $this->image_dir = _EPH_CAT_IMG_DIR_;
    }

    /**
     * @param      $categories
     * @param      $current
     * @param null $idCategory
     * @param int  $idSelected
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function recurseCategory($categories, $current, $idCategory = null, $idSelected = 1) {

        if (!$idCategory) {
            $idCategory = (int) Configuration::get('EPH_ROOT_CATEGORY');
        }

        echo '<option value="' . $idCategory . '"' . (($idSelected == $idCategory) ? ' selected="selected"' : '') . '>' .
        str_repeat('&nbsp;', $current['infos']['level_depth'] * 5) . stripslashes($current['infos']['name']) . '</option>';

        if (isset($categories[$idCategory])) {

            foreach (array_keys($categories[$idCategory]) as $key) {
                Category::recurseCategory($categories, $categories[$idCategory][$key], $key, $idSelected);
            }

        }

    }

    /**
     * Return available categories
     *
     * @param bool   $idLang Language ID
     * @param bool   $active return only active categories
     *
     * @param bool   $order
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getCategories($idLang = false, $active = true, $order = true, $sqlFilter = '', $sqlSort = '', $sqlLimit = '') {

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
            SELECT *
            FROM `' . _DB_PREFIX_ . 'category` c
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`
            WHERE 1 ' . $sqlFilter . ' ' . ($idLang ? 'AND `id_lang` = ' . (int) $idLang : '') . '
            ' . ($active ? 'AND `active` = 1' : '') . '
            ' . (!$idLang ? 'GROUP BY c.id_category' : '') . '
            ' . ($sqlSort != '' ? $sqlSort : 'ORDER BY c.`level_depth` ASC, c.`position` ASC') . '
            ' . ($sqlLimit != '' ? $sqlLimit : '')
        );

        if (!$order) {
            return $result;
        }

        $categories = [];

        foreach ($result as $row) {
            $categories[$row['id_parent']][$row['id_category']]['infos'] = $row;
        }

        return $categories;
    }

    /**
     * @param null   $rootCategory
     * @param bool   $idLang
     * @param bool   $active
     * @param null   $groups
     * @param bool   $useShopRestriction
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getAllCategoriesName(
        $rootCategory = null,
        $idLang = false,
        $active = true,
        $groups = null,
        $useShopRestriction = true,
        $sqlFilter = '',
        $sqlSort = '',
        $sqlLimit = ''
    ) {

        if (isset($rootCategory) && !Validate::isInt($rootCategory)) {
            die(Tools::displayError());
        }

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        if (isset($groups) && Group::isFeatureActive() && !is_array($groups)) {
            $groups = (array) $groups;
        }

        $cacheId = 'Category::getAllCategoriesName_' . md5(
            (int) $rootCategory . (int) $idLang . (int) $active . (int) $useShopRestriction
            . (isset($groups) && Group::isFeatureActive() ? implode('', $groups) : '')
        );

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                '
                SELECT c.id_category, cl.name
                FROM `' . _DB_PREFIX_ . 'category` c
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`
                ' . (isset($groups) && Group::isFeatureActive() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`' : '') . '
                ' . (isset($rootCategory) ? 'RIGHT JOIN `' . _DB_PREFIX_ . 'category` c2 ON c2.`id_category` = ' . (int) $rootCategory . ' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`' : '') . '
                WHERE ' . ($sqlFilter ? $sqlFilter : '1') . ' ' . ($idLang ? 'AND `id_lang` = ' . (int) $idLang : '') . '
                ' . ($active ? ' AND c.`active` = 1' : '') . '
                ' . (isset($groups) && Group::isFeatureActive() ? ' AND cg.`id_group` IN (' . implode(',', $groups) . ')' : '') . '
                ' . (!$idLang || (isset($groups) && Group::isFeatureActive()) ? ' GROUP BY c.`id_category`' : '') . '
                ' . ($sqlSort != '' ? $sqlSort : ' ORDER BY c.`level_depth` ASC') . '
                ' . ($sqlSort == '' && $useShopRestriction ? ', c.`position` ASC' : '') . '
                ' . ($sqlLimit != '' ? $sqlLimit : '')
            );

            Cache::store($cacheId, $result);
        } else {
            $result = Cache::retrieve($cacheId);
        }

        return $result;
    }

    /**
     * @param null   $rootCategory
     * @param bool   $idLang
     * @param bool   $active
     * @param null   $groups
     * @param bool   $useShopRestriction
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getNestedCategories(
        $rootCategory = null,
        $idLang = false,
        $active = true,
        $groups = null,
        $useShopRestriction = true,
        $sqlFilter = '',
        $sqlSort = '',
        $sqlLimit = ''
    ) {

        if (isset($rootCategory) && !Validate::isInt($rootCategory)) {
            die(Tools::displayError());
        }

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        if (isset($groups) && Group::isFeatureActive() && !is_array($groups)) {
            $groups = (array) $groups;
        }

        $cacheId = 'Category::getNestedCategories_' . md5(
            (int) $rootCategory . (int) $idLang . (int) $active . (int) $useShopRestriction
            . (isset($groups) && Group::isFeatureActive() ? implode('', $groups) : '')
        );

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                '
                SELECT c.*, cl.*
                FROM `' . _DB_PREFIX_ . 'category` c
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`
                ' . (isset($groups) && Group::isFeatureActive() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`' : '') . '
                ' . (isset($rootCategory) ? 'RIGHT JOIN `' . _DB_PREFIX_ . 'category` c2 ON c2.`id_category` = ' . (int) $rootCategory . ' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`' : '') . '
                WHERE 1 ' . $sqlFilter . ' ' . ($idLang ? 'AND `id_lang` = ' . (int) $idLang : '') . '
                ' . ($active ? ' AND c.`active` = 1' : '') . '
                ' . (isset($groups) && Group::isFeatureActive() ? ' AND cg.`id_group` IN (' . implode(',', $groups) . ')' : '') . '
                ' . (!$idLang || (isset($groups) && Group::isFeatureActive()) ? ' GROUP BY c.`id_category`' : '') . '
                ' . ($sqlSort != '' ? $sqlSort : ' ORDER BY c.`level_depth` ASC') . '
                ' . ($sqlSort == '' && $useShopRestriction ? ', c.`position` ASC' : '') . '
                ' . ($sqlLimit != '' ? $sqlLimit : '')
            );

            $categories = [];
            $buff = [];

            if (!isset($rootCategory)) {
                $rootCategory = Category::getRootCategory()->id;
            }

            foreach ($result as $row) {
                $current = &$buff[$row['id_category']];
                $current = $row;

                if ($row['id_category'] == $rootCategory) {
                    $categories[$row['id_category']] = &$current;
                } else {
                    $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
                }

            }

            Cache::store($cacheId, $categories);
        } else {
            $categories = Cache::retrieve($cacheId);
        }

        return $categories;
    }

    /**
     * @param null      $idLang
     * @param Shop|null $shop
     *
     * @return Category
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getRootCategory($idLang = null, Shop $shop = null) {

        $context = Context::getContext();

        if (is_null($idLang)) {
            $idLang = $context->language->id;
        }

        if (!$shop) {

            if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
                $shop = new Shop(Configuration::get('EPH_SHOP_DEFAULT'));
            } else {
                $shop = $context->shop;
            }

        } else {
            return new Category($shop->getCategory(), $idLang);
        }

        $isMoreThanOneRootCategory = count(Category::getCategoriesWithoutParent()) > 1;

        if (Shop::isFeatureActive() && $isMoreThanOneRootCategory && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $category = Category::getTopCategory($idLang);
        } else {
            $category = new Category($shop->getCategory(), $idLang);
        }

        return $category;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getCategoriesWithoutParent() {

        $cacheId = 'Category::getCategoriesWithoutParent_' . (int) Context::getContext()->language->id;

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                '
            SELECT DISTINCT c.*
            FROM `' . _DB_PREFIX_ . 'category` c
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . (int) Context::getContext()->language->id . ')
            WHERE `level_depth` = 1'
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int|null $idLang
     *
     * @return Category
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function getTopCategory($idLang = null) {

        if (is_null($idLang)) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $cacheId = 'Category::getTopCategory_' . (int) $idLang;

        if (!Cache::isStored($cacheId)) {
            $idCategory = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_category`')
                    ->from('category')
                    ->where('`id_parent` = 0')
            );
            $category = new Category($idCategory, $idLang);
            Cache::store($cacheId, $category);

            return $category;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getSimpleCategories($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_category`, cl.`name`')
                ->from('category', 'c')
                ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ')
                ->where('cl.`id_lang` = ' . (int) $idLang)
                ->where('c.`id_category` != ' . Configuration::get('EPH_ROOT_CATEGORY'))
                ->groupBy('c.`id_category`')
                ->orderBy('c.`id_category`, c.`position`')
        );
    }

    public static function outputCategoriesSelect($idLang) {

        $rootCategoryId = Category::getRootCategory()->id;

        $categoryList = [];

        foreach (Category::getNestedCategories($rootCategoryId, $idLang) as $idCategory => $categoryInformations) {

            if ($rootCategoryId != $idCategory) {
                $categoryList[] = $categoryInformations;
            }

            Category::_getChildrensCategories($categoryList, $categoryInformations, 0);
        }

        return $categoryList;
    }

    public static function _getChildrensCategories(&$categoryList, $categoryInformations, $selected, $levelDepth = false) {

        if (isset($categoryInformations['children']) && Category::_isFilledArray($categoryInformations['children'])) {

            foreach ($categoryInformations['children'] as $categoryInformations) {
                $categoryList[] = $categoryInformations;
                Category::_getChildrensCategories($categoryList, $categoryInformations, $selected, ($levelDepth !== false ? $levelDepth + 1 : $levelDepth));
            }

        }

    }

    public static function _isFilledArray($array) {

        return ($array && is_array($array) && sizeof($array));
    }

    /**
     * Return main categories
     *
     * @param int  $idLang Language ID
     * @param bool $active return only active categories
     *
     * @param bool $idShop
     *
     * @return array categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getHomeCategories($idLang, $active = true, $idShop = false) {

        return static::getChildren(Configuration::get('EPH_HOME_CATEGORY'), $idLang, $active, $idShop);
    }

    /**
     *
     * @param int  $idParent
     * @param int  $idLang
     * @param bool $active
     * @param bool $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getChildren($idParent, $idLang, $active = true, $idShop = false) {

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $cacheId = 'Category::getChildren_' . (int) $idParent . '-' . (int) $idLang . '-' . (bool) $active;

        if (!Cache::isStored($cacheId)) {
            $query = 'SELECT c.`id_category`, cl.`name`, cl.`link_rewrite`
            FROM `' . _DB_PREFIX_ . 'category` c
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category`)
            WHERE `id_lang` = ' . (int) $idLang . '
            AND c.`id_parent` = ' . (int) $idParent . '
            ' . ($active ? 'AND `active` = 1' : '') . '
            GROUP BY c.`id_category`
            ORDER BY c.`position` ASC';
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     *
     * @param int  $idParent
     * @param int  $idLang
     * @param bool $active
     * @param bool $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function hasChildren($idParent, $idLang, $active = true, $idShop = false) {

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $cacheId = 'Category::hasChildren_' . (int) $idParent . '-' . (int) $idLang . '-' . (bool) $active . '-' . (int) $idShop;

        if (!Cache::isStored($cacheId)) {
            $query = 'SELECT c.id_category, "" AS name
            FROM `' . _DB_PREFIX_ . 'category` c
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category`)
            WHERE `id_lang` = ' . (int) $idLang . '
            AND c.`id_parent` = ' . (int) $idParent . '
            ' . ($active ? 'AND `active` = 1' : '') . ' LIMIT 1';
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * This method allow to return children categories with the number of sub children selected for a product
     *
     * @param int   $idParent
     * @param array $selectedCat
     * @param int   $idLang
     * @param Shop  $shop
     * @param bool  $useShopContext
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @internal param int $id_product
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public static function getChildrenWithNbSelectedSubCat($idParent, $selectedCat, $idLang, Shop $shop = null, $useShopContext = true) {

        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        $idShop = $shop->id ? $shop->id : Configuration::get('EPH_SHOP_DEFAULT');
        $selectedCat = explode(',', str_replace(' ', '', $selectedCat));
        $sql = '
        SELECT c.`id_category`, c.`level_depth`, cl.`name`,
        IF((
            SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'category` c2
            WHERE c2.`id_parent` = c.`id_category`
        ) > 0, 1, 0) AS has_children,
        ' . ($selectedCat ? '(
            SELECT count(c3.`id_category`)
            FROM `' . _DB_PREFIX_ . 'category` c3
            WHERE c3.`nleft` > c.`nleft`
            AND c3.`nright` < c.`nright`
            AND c3.`id_category`  IN (' . implode(',', array_map('intval', $selectedCat)) . ')
        )' : '0') . ' AS nbSelectedSubCat
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` )
        WHERE `id_lang` = ' . (int) $idLang . '
        AND c.`id_parent` = ' . (int) $idParent;

        if (!Shop::isFeatureActive() || Shop::getContext() == Shop::CONTEXT_SHOP && $useShopContext) {
            $sql .= ' ORDER BY cs.`position` ASC';
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Copy products from a category to another
     *
     * @param int  $idOld Source category ID
     * @param bool $idNew Destination category ID
     *
     * @return bool Duplication result
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function duplicateProductCategories($idOld, $idNew) {

        $sql = 'SELECT `id_category`
                FROM `' . _DB_PREFIX_ . 'category_product`
                WHERE `id_product` = ' . (int) $idOld;
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        $row = [];

        if ($result) {

            foreach ($result as $i) {
                $row[] = '(' . implode(
                    ', ', [
                        (int) $idNew, $i['id_category'], '(SELECT tmp.max + 1 FROM (
                    SELECT MAX(cp.`position`) AS max
                    FROM `' . _DB_PREFIX_ . 'category_product` cp
                    WHERE cp.`id_category`=' . (int) $i['id_category'] . ') AS tmp)',
                    ]
                ) . ')';
            }

        }

        $flag = Db::getInstance()->execute(
            '
            INSERT IGNORE INTO `' . _DB_PREFIX_ . 'category_product` (`id_product`, `id_category`, `position`)
            VALUES ' . implode(',', $row)
        );

        return $flag;
    }

    /**
     * Check if category can be moved in another one.
     * The category cannot be moved in a child category.
     *
     * @param int $idCategory current category
     * @param int $idParent   Parent candidate
     *
     * @return bool Parent validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function checkBeforeMove($idCategory, $idParent) {

        if ($idCategory == $idParent) {
            return false;
        }

        if ($idParent == Configuration::get('EPH_HOME_CATEGORY')) {
            return true;
        }

        $i = (int) $idParent;

        while (42) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow('SELECT `id_parent` FROM `' . _DB_PREFIX_ . 'category` WHERE `id_category` = ' . (int) $i);

            if (!isset($result['id_parent'])) {
                return false;
            }

            if ($result['id_parent'] == $idCategory) {
                return false;
            }

            if ($result['id_parent'] == Configuration::get('EPH_HOME_CATEGORY')) {
                return true;
            }

            $i = $result['id_parent'];
        }

    }

    /**
     * @param $idCategory
     * @param $idLang
     *
     * @return bool|mixed
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function getLinkRewrite($idCategory, $idLang) {

        if (!Validate::isUnsignedId($idCategory) || !Validate::isUnsignedId($idLang)) {
            return false;
        }

        if (!isset(static::$_links[$idCategory . '-' . $idLang])) {
            static::$_links[$idCategory . '-' . $idLang] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                '
            SELECT cl.`link_rewrite`
            FROM `' . _DB_PREFIX_ . 'category_lang` cl
            WHERE `id_lang` = ' . (int) $idLang . '
            AND cl.`id_category` = ' . (int) $idCategory
            );
        }

        return static::$_links[$idCategory . '-' . $idLang];
    }

    /**
     * Search with Pathes for categories
     *
     * @param int    $idLang           Language ID
     * @param string $path             of category
     * @param bool   $objectToCreate   a category
     *                                 * @param bool $methodToCreate a category
     *
     * @return array Corresponding categories
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function searchByPath($idLang, $path, $objectToCreate = false, $methodToCreate = false) {

        $categories = explode('/', trim($path));
        $category = $idParentCategory = false;

        if (is_array($categories) && count($categories)) {

            foreach ($categories as $categoryName) {

                if ($idParentCategory) {
                    $category = Category::searchByNameAndParentCategoryId($idLang, $categoryName, $idParentCategory);
                } else {
                    $category = Category::searchByName($idLang, $categoryName, true, true);
                }

                if (!$category && $objectToCreate && $methodToCreate) {
                    call_user_func_array([$objectToCreate, $methodToCreate], [$idLang, $categoryName, $idParentCategory]);
                    $category = Category::searchByPath($idLang, $categoryName);
                }

                if (isset($category['id_category']) && $category['id_category']) {
                    $idParentCategory = (int) $category['id_category'];
                }

            }

        }

        return $category;
    }

    /**
     * Retrieve category by name and parent category id
     *
     * @param int    $idLang           Language ID
     * @param string $categoryName     Searched category name
     * @param int    $idParentCategory parent category ID
     *
     * @return array Corresponding category
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function searchByNameAndParentCategoryId($idLang, $categoryName, $idParentCategory) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            '
        SELECT c.*, cl.*
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
            ON (c.`id_category` = cl.`id_category`
            AND `id_lang` = ' . (int) $idLang . ')
        WHERE `name` = \'' . pSQL($categoryName) . '\'
            AND c.`id_category` != ' . (int) Configuration::get('EPH_HOME_CATEGORY') . '
            AND c.`id_parent` = ' . (int) $idParentCategory
        );
    }

    /**
     * Light back office search for categories
     *
     * @param int    $idLang       Language ID
     * @param string $query        Searched string
     * @param bool   $unrestricted allows search without lang and includes first category and exact match
     * @param bool   $skipCache
     *
     * @return array Corresponding categories
     * @throws PrestaShopDatabaseException
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function searchByName($idLang, $query, $unrestricted = false, $skipCache = false) {

        if ($unrestricted === true) {
            $key = 'Category::searchByName_' . $query;

            if ($skipCache || !Cache::isStored($key)) {
                $categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                    (new DbQuery())
                        ->select('c.*, cl.*')
                        ->from('category', 'c')
                        ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ')
                        ->where('`name` = \'' . pSQL($query) . '\'')
                );

                if (!$skipCache) {
                    Cache::store($key, $categories);
                }

                return $categories;
            }

            return Cache::retrieve($key);
        } else {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('c.*, cl.*')
                    ->from('category', 'c')
                    ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $idLang)
                    ->where('`name` LIKE \'%' . pSQL($query) . '%\'')
                    ->where('c.`id_category` != ' . (int) Configuration::get('EPH_HOME_CATEGORY'))
            );
        }

    }

    /**
     * Specify if a category already in base
     *
     * @param int $idCategory Category id
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function categoryExists($idCategory) {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_category`')
                ->from('category', 'c')
                ->where('c.`id_category` = ' . (int) $idCategory)
        );

        return isset($row['id_category']);
    }

    /**
     * @param int $idGroup
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function setNewGroupForHome($idGroup) {

        if (!(int) $idGroup) {
            return false;
        }

        try {
            return Db::getInstance()->insert(
                'category_group',
                [
                    'id_category' => (int) Context::getContext()->shop->getCategory(),
                    'id_group'    => (int) $idGroup,
                ]
            );
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }

    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getUrlRewriteInformations($idCategory) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('l.`id_lang`, c.`link_rewrite`')
                ->from('category_lang', 'c')
                ->leftJoin('lang', 'l', 'c.`id_lang` = l.`id_lang`')
                ->where('c.`id_category` = ' . (int) $idCategory)
                ->where('l.`active` = 1')
        );
    }

    /**
     * @param int       $idCategory
     * @param Shop|null $shop
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function inShopStatic($idCategory, Shop $shop = null) {

        if (!$shop || !is_object($shop)) {
            $shop = Context::getContext()->shop;
        }

        if (!$interval = Category::getInterval($shop->getCategory())) {
            return false;
        }

        try {
            $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`nleft`, `nright`')
                    ->from('category')
                    ->where('`id_category` = ' . (int) $idCategory)
            );
        } catch (PhenyxShopException $e) {
            return false;
        }

        return ($row['nleft'] >= $interval['nleft'] && $row['nright'] <= $interval['nright']);
    }

    /**
     * Return nleft and nright fields for a given category
     *
     * @param int $id
     *
     * @return array
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public static function getInterval($id) {

        $cacheId = 'Category::getInterval_' . (int) $id;

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`nleft`, `nright`, `level_depth`')
                    ->from('category')
                    ->where('`id_category` = ' . (int) $id)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     *
     * @param array $idsCategory
     * @param int   $idLang
     *
     * @return array|false
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public static function getCategoryInformations($idsCategory, $idLang = null) {

        if ($idLang === null) {
            $idLang = Context::getContext()->language->id;
        }

        if (!is_array($idsCategory) || !count($idsCategory)) {
            return false;
        }

        $categories = [];
        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_category`, cl.`name`, cl.`link_rewrite`, cl.`id_lang`')
                ->from('category', 'c')
                ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ')
                ->where('cl.`id_lang` = ' . (int) $idLang)
                ->where('c.`id_category` IN (' . implode(',', array_map('intval', $idsCategory)) . ')')
        );

        foreach ($results as $category) {
            $categories[$category['id_category']] = $category;
        }

        return $categories;
    }

    /**
     * @param int|null $idLang
     * @param bool     $active
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public static function getRootCategories($idLang = null, $active = true) {

        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT(c.`id_category`), cl.`name`')
                ->from('category', 'c')
                ->leftJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category` AND cl.`id_lang`=' . (int) $idLang)
                ->where('`is_root_category` = 1')
                ->where($active ? '`active` = 1' : '')
        );
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public static function getShopsByCategory($idCategory) {

        return Configuration::get('EPH_SHOP_DEFAULT');
    }

    /**
     * @param int      $position
     * @param int|null $idShop
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function addPosition($position, $idShop = null) {

        $return = true;

        return $return;
    }

    /** this function return the number of category + 1 having $id_category_parent as parent.
     *
     * @todo    rename that function to make it understandable (getNewLastPosition for example)
     *
     * @param int $idCategoryParent the parent category
     * @param int $idShop
     *
     * @return int
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function getLastPosition($idCategoryParent, $idShop) {

        if ((int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
                SELECT COUNT(c.`id_category`)
                FROM `' . _DB_PREFIX_ . 'category` c
                WHERE c.`id_parent` = ' . (int) $idCategoryParent
        ) === 1
        ) {
            return 0;
        } else {
            return (1 + (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                '
                SELECT MAX(c.`position`)
                FROM `' . _DB_PREFIX_ . 'category` c
                WHERE c.`id_parent` = ' . (int) $idCategoryParent
            ));
        }

    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public function add($autoDate = true, $nullValues = false) {

        if (!isset($this->level_depth)) {
            $this->level_depth = $this->calcLevelDepth();
        }

        if ($this->is_root_category && ($idRootCategory = (int) Configuration::get('EPH_ROOT_CATEGORY'))) {
            $this->id_parent = $idRootCategory;
        }

        $ret = parent::add($autoDate, $nullValues);

        if (Tools::isSubmit('checkBoxShopAsso_category')) {

            foreach (Tools::getValue('checkBoxShopAsso_category') as $idShop => $value) {
                $position = (int) Category::getLastPosition((int) $this->id_parent, $idShop);
                $this->addPosition($position, $idShop);
            }

        } else {

            foreach (Shop::getShops(true) as $shop) {
                $position = (int) Category::getLastPosition((int) $this->id_parent, $shop['id_shop']);
                $this->addPosition($position, $shop['id_shop']);
            }

        }

        if (!isset($this->doNotRegenerateNTree) || !$this->doNotRegenerateNTree) {
            Category::regenerateEntireNtree();
        }

        $this->updateGroup($this->groupBox);
        Hook::exec('actionCategoryAdd', ['category' => $this]);

        return $ret;
    }

    /**
     * Get the depth level for the category
     *
     * @return int Depth level
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public function calcLevelDepth() {

        /* Root category */

        if (!$this->id_parent) {
            return 0;
        }

        $parentCategory = new Category((int) $this->id_parent);

        if (!Validate::isLoadedObject($parentCategory)) {
            throw new PhenyxShopException('Parent category does not exist');
        }

        return $parentCategory->level_depth + 1;
    }

    /**
     * Re-calculate the values of all branches of the nested tree
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public static function regenerateEntireNtree() {

        $id = Context::getContext()->shop->id;
        $idShop = $id ? $id : Configuration::get('EPH_SHOP_DEFAULT');
        $categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_category`, c.`id_parent`')
                ->from('category', 'c')
                ->orderBy('c.`id_parent`, c.`position` ASC')
        );
        $categoriesArray = [];

        foreach ($categories as $category) {
            $categoriesArray[$category['id_parent']]['subcategories'][] = $category['id_category'];
        }

        $n = 1;

        if (isset($categoriesArray[0]) && $categoriesArray[0]['subcategories']) {
            Category::_subTree($categoriesArray, $categoriesArray[0]['subcategories'][0], $n);
        }

    }

    /**
     * @param $categories
     * @param $idCategory
     * @param $n
     *
     * @deprecated 1.0.0
     * @throws PhenyxShopException
     */
    protected static function _subTree(&$categories, $idCategory, &$n) {

        return static::subTree($categories, $idCategory, $n);
    }

    /**
     * @param $categories
     * @param $idCategory
     * @param $n
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    protected static function subTree(&$categories, $idCategory, &$n) {

        $left = $n++;

        if (isset($categories[(int) $idCategory]['subcategories'])) {

            foreach ($categories[(int) $idCategory]['subcategories'] as $idSubcategory) {
                Category::_subTree($categories, (int) $idSubcategory, $n);
            }

        }

        $right = (int) $n++;

        Db::getInstance()->execute(
            '
        UPDATE ' . _DB_PREFIX_ . 'category
        SET nleft = ' . (int) $left . ', nright = ' . (int) $right . '
        WHERE id_category = ' . (int) $idCategory . ' LIMIT 1'
        );
    }

    /**
     * Update customer groups associated to the object
     *
     * @param array $list groups
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     */
    public function updateGroup($list) {

        $this->cleanGroups();

        if (empty($list)) {
            $list = [Configuration::get('EPH_UNIDENTIFIED_GROUP'), Configuration::get('EPH_GUEST_GROUP'), Configuration::get('EPH_CUSTOMER_GROUP')];
        }

        $this->addGroups($list);
    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PrestaShopDatabaseException
     */
    public function cleanGroups() {

        return Db::getInstance()->delete('category_group', 'id_category = ' . (int) $this->id);
    }

    /**
     * @param array $groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function addGroups($groups) {

        foreach ($groups as $group) {

            if ($group !== false) {
                Db::getInstance()->insert('category_group', ['id_category' => (int) $this->id, 'id_group' => (int) $group]);
            }

        }

    }

    /**
     * update category positions in parent
     *
     * @param mixed $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function update($nullValues = false) {

        if ($this->id_parent == $this->id) {
            throw new PhenyxShopException('a category cannot be its own parent');
        }

        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('category', $this->id);
        }

        if ($this->is_root_category && $this->id_parent != (int) Configuration::get('EPH_ROOT_CATEGORY')) {
            $this->is_root_category = 0;
        }

        // Update group selection
        $this->updateGroup($this->groupBox);

        if ($this->level_depth != $this->calcLevelDepth()) {
            $this->level_depth = $this->calcLevelDepth();
            $changed = true;
        }

        // If the parent category was changed, we don't want to have 2 categories with the same position

        if (!isset($changed)) {
            $changed = $this->getDuplicatePosition();
        }

        if ($changed) {

            if (Tools::isSubmit('checkBoxShopAsso_category')) {

                foreach (Tools::getValue('checkBoxShopAsso_category') as $idAssoObject => $row) {

                    foreach ($row as $idShop => $value) {
                        $this->addPosition((int) Category::getLastPosition((int) $this->id_parent, (int) $idShop), (int) $idShop);
                    }

                }

            } else {

                foreach (Shop::getShops(true) as $shop) {
                    $this->addPosition((int) Category::getLastPosition((int) $this->id_parent, $shop['id_shop']), $shop['id_shop']);
                }

            }

        }

        $ret = parent::update($nullValues);

        if ($changed && (!isset($this->doNotRegenerateNTree) || !$this->doNotRegenerateNTree)) {
            $this->cleanPositions((int) $this->id_parent);
            Category::regenerateEntireNtree();
            $this->recalculateLevelDepth($this->id);
        }

        Hook::exec('actionCategoryUpdate', ['category' => $this]);

        return $ret;
    }

    /**
     * Search for another category with the same parent and the same position
     *
     * @return false|null|string first category found
     *
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getDuplicatePosition() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
        SELECT c.`id_category`
        FROM `' . _DB_PREFIX_ . 'category` c
        WHERE c.`id_parent` = ' . (int) $this->id_parent . '
        AND c.`position` = ' . (int) $this->position . '
        AND c.`id_category` != ' . (int) $this->id
        );
    }

    /**
     * cleanPositions keep order of category in $id_category_parent,
     * but remove duplicate position. Should not be used if positions
     * are clean at the beginning !
     *
     * @param mixed $idCategoryParent
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function cleanPositions($idCategoryParent = null) {

        if ($idCategoryParent === null) {
            return;
        }

        $return = true;
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_category`')
                ->from('category', 'c')
                ->where('c.`id_parent` = ' . (int) $idCategoryParent)
                ->orderBy('c.`position`')
        );
        $count = count($result);

        for ($i = 0; $i < $count; $i++) {
            $return &= Db::getInstance()->execute(
                '
            UPDATE `' . _DB_PREFIX_ . 'category` c
            SET c.`position` = ' . (int) ($i) . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE c.`id_parent` = ' . (int) $idCategoryParent . ' AND c.`id_category` = ' . (int) $result[$i]['id_category']
            );
        }

        return $return;
    }

    /**
     * Updates level_depth for all children of the given id_category
     *
     * @param int $idCategory parent category
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function recalculateLevelDepth($idCategory) {

        if (!is_numeric($idCategory)) {
            throw new PhenyxShopException('id category is not numeric');
        }

        /* Gets all children */
        $categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_category`, `id_parent`, `level_depth`')
                ->from('category')
                ->where('`id_parent` = ' . (int) $idCategory)
        );
        /* Gets level_depth */
        $level = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('level_depth')
                ->from('category')
                ->where('`id_category` = ' . (int) $idCategory)
        );
        /* Updates level_depth for all children */

        foreach ($categories as $subCategory) {
            Db::getInstance()->execute(
                '
                UPDATE ' . _DB_PREFIX_ . 'category
                SET level_depth = ' . (int) ($level['level_depth'] + 1) . '
                WHERE id_category = ' . (int) $subCategory['id_category']
            );
            /* Recursive call */
            $this->recalculateLevelDepth($subCategory['id_category']);
        }

    }

    /**
     * @see     ObjectModel::toggleStatus()
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public function toggleStatus() {

        $result = parent::toggleStatus();
        Hook::exec('actionCategoryUpdate', ['category' => $this]);

        return $result;
    }

    /**
     * Recursive scan of subcategories
     *
     * @param int   $maxDepth         Maximum depth of the tree (i.e. 2 => 3 levels depth)
     * @param int   $currentDepth     specify the current depth in the tree (don't use it, only for rucursivity!)
     * @param int   $idLang           Specify the id of the language used
     * @param array $excludedIdsArray specify a list of ids to exclude of results
     *
     * @return array Subcategories lite tree
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function recurseLiteCategTree($maxDepth = 3, $currentDepth = 0, $idLang = null, $excludedIdsArray = null) {

        $idLang = is_null($idLang) ? Context::getContext()->language->id : (int) $idLang;

        $children = [];
        $subcats = $this->getSubCategories($idLang, true);

        if (($maxDepth == 0 || $currentDepth < $maxDepth) && $subcats && count($subcats)) {

            foreach ($subcats as &$subcat) {

                if (!$subcat['id_category']) {
                    break;
                } else

                if (!is_array($excludedIdsArray) || !in_array($subcat['id_category'], $excludedIdsArray)) {
                    $categ = new Category($subcat['id_category'], $idLang);
                    $children[] = $categ->recurseLiteCategTree($maxDepth, $currentDepth + 1, $idLang, $excludedIdsArray);
                }

            }

        }

        if (is_array($this->description)) {

            foreach ($this->description as $lang => $description) {
                $this->description[$lang] = Category::getDescriptionClean($description);
            }

        } else {
            $this->description = Category::getDescriptionClean($this->description);
        }

        return [
            'id'       => (int) $this->id,
            'link'     => Context::getContext()->link->getCategoryLink($this->id, $this->link_rewrite),
            'name'     => $this->name,
            'desc'     => $this->description,
            'children' => $children,
        ];
    }

    /**
     * Return current category childs
     *
     * @param int  $idLang Language ID
     * @param bool $active return only active categories
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getSubCategories($idLang, $active = true) {

        $sqlGroupsWhere = '';
        $sqlGroupsJoin = '';

        if (Group::isFeatureActive()) {
            $sqlGroupsJoin = 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cg.`id_category` = c.`id_category`)';
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroupsWhere = 'AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id);
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT c.*, cl.id_lang, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $idLang . ')
        ' . $sqlGroupsJoin . '
        WHERE `id_parent` = ' . (int) $this->id . '
        ' . ($active ? 'AND `active` = 1' : '') . '
        ' . $sqlGroupsWhere . '
        GROUP BY c.`id_category`
        ORDER BY `level_depth` ASC, c.`position` ASC'
        );

        foreach ($result as &$row) {
            $row['id_image'] = (file_exists(_EPH_CAT_IMG_DIR_ . (int) $row['id_category'] . '.jpg') || file_exists(_EPH_CAT_IMG_DIR_ . (int) $row['id_category'] . '_thumb.jpg')) ? (int) $row['id_category'] : Language::getIsoById($idLang) . '-default';
            $row['legend'] = 'no picture';
        }

        return $result;
    }

    /**
     * @param string $description
     *
     * @return string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getDescriptionClean($description) {

        return Tools::getDescriptionClean($description);
    }

    /**
     * Delete several categories from database
     *
     * return boolean Deletion result
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @throws PhenyxShopException
     */
    public function deleteSelection($categories) {

        $return = 1;

        foreach ($categories as $idCategory) {
            $category = new Category($idCategory);

            if ($category->isRootCategoryForAShop()) {
                return false;
            } else {
                $return &= $category->delete();
            }

        }

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function isRootCategoryForAShop() {

        return (bool) Configuration::get('EPH_SHOP_DEFAULT');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function delete() {

        if ((int) $this->id === 0 || (int) $this->id === (int) Configuration::get('EPH_ROOT_CATEGORY')) {
            return false;
        }

        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('category', $this->id);
        }

        $this->clearCache();

        $deletedChildren = $allCat = $this->getAllChildren();
        $allCat[] = $this;

        foreach ($allCat as $cat) {
            /** @var Category $cat */
            $cat->deleteLite();

            $cat->deleteImage();
            $cat->cleanGroups();
            $cat->cleanAssoProducts();
            // Delete associated restrictions on cart rules
            CartRule::cleanProductRuleIntegrity('categories', [$cat->id]);
            Category::cleanPositions($cat->id_parent);
            /* Delete Categories in GroupReduction */

            if (GroupReduction::getGroupsReductionByCategoryId((int) $cat->id)) {
                GroupReduction::deleteCategory($cat->id);
            }

        }

        /* Rebuild the nested tree */

        if (!$this->hasMultishopEntries() && (!isset($this->doNotRegenerateNTree) || !$this->doNotRegenerateNTree)) {
            Category::regenerateEntireNtree();
        }

        Hook::exec('actionCategoryDelete', ['category' => $this, 'deleted_children' => $deletedChildren]);

        return true;
    }

    /**
     * Return an array of all children of the current category
     *
     * @param int $idLang
     *
     * @return PrestaShopCollection Collection of Category
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getAllChildren($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $categories = new PrestaShopCollection('Category', $idLang);
        $categories->where('nleft', '>', $this->nleft);
        $categories->where('nright', '<', $this->nright);

        return $categories;
    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function deleteLite() {

        // Directly call the parent of delete, in order to avoid recursion
        return parent::delete();
    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PrestaShopDatabaseException
     */
    public function cleanAssoProducts() {

        return Db::getInstance()->delete('category_product', 'id_category = ' . (int) $this->id);
    }

    /**
     * @return int
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getShopID() {

        return $this->id_shop;
    }

    /**
     * Returns category products
     *
     * @param int          $idLang                 Language ID
     * @param int          $p                      Page number
     * @param int          $n                      Number of products per page
     * @param string|null  $orderBy                ORDER BY column
     * @param string|null  $orderWay               Order way
     * @param bool         $getTotal               If set to true, returns the total number of results only
     * @param bool         $active                 If set to true, finds only active products
     * @param bool         $random                 If true, sets a random filter for returned products
     * @param int          $randomNumberProducts   Number of products to return if random is activated
     * @param bool         $checkAccess            If set tot rue, check if the current customer
     *                                             can see products from this category
     * @param Context|null $context
     *
     * @return array|int|false Products, number of products or false (no access)
     * @throws PrestaShopDatabaseException
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getProducts($idLang, $p, $n, $orderBy = null, $orderWay = null, $getTotal = false, $active = true, $random = false, $randomNumberProducts = 1, $checkAccess = true, Context $context = null) {

        $file = fopen("testgetProducts.txt", "a");

        if (!$context) {
            $context = Context::getContext();
        }

        $id_group = $context->customer->id_default_group;

        if ($checkAccess && !$this->checkAccess($context->customer->id)) {
            return false;
        }

        $front = in_array($context->controller->controller_type, ['front', 'modulefront']);
        $idSupplier = (int) Tools::getValue('id_supplier');
        $id_manufacturer = (int) Tools::getValue('id_manufacturer');

        $subcats = $this->getAllSubcategories();
        $catsToSearchIn = [$this->id];

        if ($subcats && $this->display_from_sub) {

            foreach ($subcats as $scat) {
                $catsToSearchIn[] = $scat['id_category'];
            }

        }

        /** Return only the number of products */

        if ($getTotal) {
            $sql = 'SELECT COUNT(DISTINCT(cp.`id_product`)) AS total
                    FROM `' . _DB_PREFIX_ . 'product` p
                    LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
                    WHERE cp.`id_category` IN (' . implode(',', $catsToSearchIn) . ')' .
                ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') .
                ($active ? ' AND p.`active` = 1' : '') .
                ($id_manufacturer ? ' AND p.`id_manufacturer` = ' . (int) $id_manufacturer : '') .
                ($idSupplier ? 'AND p.id_supplier = ' . (int) $idSupplier : '');
            fwrite($file, $sql . PHP_EOL);
            return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);
        }

        if ($p < 1) {
            $p = 1;
        }

        /** Tools::strtolower is a fix for all modules which are now using lowercase values for 'orderBy' parameter */
        $orderBy = Validate::isOrderBy($orderBy) ? mb_strtolower($orderBy) : 'position';
        $orderWay = Validate::isOrderWay($orderWay) ? mb_strtoupper($orderWay) : 'ASC';

        $orderByPrefix = false;

        if ($orderBy == 'id_product' || $orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderByPrefix = 'p';
        } else

        if ($orderBy == 'name') {
            $orderByPrefix = 'pl';
        } else

        if ($orderBy == 'manufacturer' || $orderBy == 'manufacturer_name') {
            $orderByPrefix = 'm';
            $orderBy = 'name';
        } else

        if ($orderBy == 'position') {
            $orderByPrefix = 'cp';
        }

        if ($orderBy == 'price') {
            $orderBy = 'orderprice';
        }

        $nbDaysNewProduct = Configuration::get('EPH_NB_DAYS_NEW_PRODUCT');

        if (!Validate::isUnsignedInt($nbDaysNewProduct)) {
            $nbDaysNewProduct = 20;
        }

        $sql = 'SELECT p.*, stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity' . (Combination::isFeatureActive() ? ', IFNULL(product_attribute.id_product_attribute, 0) AS id_product_attribute,
                    product_attribute.minimal_quantity AS product_attribute_minimal_quantity' : '') . ', pl.`description`, pl.`description_short`, pl.`available_now`,
                    pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, image.`id_image` id_image,
                    il.`legend` as legend, m.`name` AS manufacturer_name, cl.`name` AS category_default,
                    DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
                    INTERVAL ' . (int) $nbDaysNewProduct . ' DAY)) > 0 AS new, p.price AS orderprice
                FROM `' . _DB_PREFIX_ . 'category_product` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'product` p  ON p.`id_product` = cp.`id_product`
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` product_attribute ON (p.`id_product` = product_attribute.`id_product` AND product_attribute.`default_on` = 1)
                ' . Product::sqlStock('p', 0) . '
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . (int) $idLang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl  ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int) $idLang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image` image  ON (image.`id_product` = p.`id_product` AND image.cover=1 )
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il   ON (image.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $idLang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
                WHERE  cp.`id_category` IN (' . implode(',', $catsToSearchIn) . ')'
            . ($active ? ' AND p.`active` = 1' : '')
            . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '')
            . ($id_manufacturer ? ' AND p.`id_manufacturer` = ' . (int) $id_manufacturer : '')
            . ($idSupplier ? ' AND p.id_supplier = ' . (int) $idSupplier : ''
            . ' GROUP BY cp.id_product');

        if ($random === true) {
            $sql .= ' ORDER BY RAND() LIMIT ' . (int) $randomNumberProducts;
        } else {
            $sql .= ' ORDER BY ' . (!empty($orderByPrefix) ? $orderByPrefix . '.' : '') . '`' . bqSQL($orderBy) . '` ' . pSQL($orderWay) . '
            LIMIT ' . (((int) $p - 1) * (int) $n) . ',' . (int) $n;
        }

        fwrite($file, $sql . PHP_EOL);
        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);

        if (!$results) {
            return [];
        }

        if ($orderBy == 'orderprice') {
            Tools::orderbyPrice($products, $orderWay);
        }

        /** Modify SQL result */
        return Product::getProductsProperties($idLang, $results);
    }

    /**
     * checkAccess return true if id_customer is in a group allowed to see this category.
     *
     * @param mixed $idCustomer
     *
     * @access  public
     * @return bool true if access allowed for customer $id_customer
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function checkAccess($idCustomer) {

        $cacheId = 'Category::checkAccess_' . (int) $this->id . '-' . $idCustomer . (!$idCustomer ? '-' . (int) Group::getCurrent()->id : '');

        if (!Cache::isStored($cacheId)) {

            if (!$idCustomer) {
                $result = (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                    '
                SELECT ctg.`id_group`
                FROM ' . _DB_PREFIX_ . 'category_group ctg
                WHERE ctg.`id_category` = ' . (int) $this->id . ' AND ctg.`id_group` = ' . (int) Group::getCurrent()->id
                );
            } else {
                $result = (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                    '
                SELECT ctg.`id_group`
                FROM ' . _DB_PREFIX_ . 'category_group ctg
                INNER JOIN ' . _DB_PREFIX_ . 'customer_group cg ON (cg.`id_group` = ctg.`id_group` AND cg.`id_customer` = ' . (int) $idCustomer . ')
                WHERE ctg.`id_category` = ' . (int) $this->id
                );
            }

            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return an array of all parents of the current category
     *
     * @param int $idLang
     *
     * @return PrestaShopCollection Collection of Category
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getAllParents($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $categories = new PrestaShopCollection('Category', $idLang);
        $categories->where('nleft', '<', $this->nleft);
        $categories->where('nright', '>', $this->nright);

        return $categories;
    }

    /**
     * @param Link|null $link
     * @param null      $idLang
     *
     * @return string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getLink(Link $link = null, $idLang = null) {

        if (!$link) {
            $link = Context::getContext()->link;
        }

        if (!$idLang && is_array($this->link_rewrite)) {
            $idLang = Context::getContext()->language->id;
        }

        return $link->getCategoryLink(
            $this,
            is_array($this->link_rewrite) ? $this->link_rewrite[$idLang] : $this->link_rewrite,
            $idLang
        );
    }

    /**
     * @param int|null $idLang
     *
     * @return string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getName($idLang = null) {

        if (!$idLang) {

            if (isset($this->name[Context::getContext()->language->id])) {
                $idLang = Context::getContext()->language->id;
            } else {
                $idLang = (int) Configuration::get('EPH_LANG_DEFAULT');
            }

        }

        return isset($this->name[$idLang]) ? $this->name[$idLang] : '';
    }

    /**
     * Get Each parent category of this category until the root category
     *
     * @param int $idLang Language ID
     *
     * @return array Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getParentsCategories($idLang = null) {

        static $parentCategoryCache = [];

        $context = Context::getContext()->cloneContext();
        $context->shop = clone ($context->shop);

        if (is_null($idLang)) {
            $idLang = $context->language->id;
        }

        $categories = null;
        $idCurrent = $this->id;

        if (count(Category::getCategoriesWithoutParent()) > 1 && Configuration::get('EPH_MULTISHOP_FEATURE_ACTIVE') && count(Shop::getShops(true, null, true)) != 1) {
            $context->shop->id_category = (int) Configuration::get('EPH_ROOT_CATEGORY');
        } else

        if (!$context->shop->id) {
            $context->shop = new Shop(Configuration::get('EPH_SHOP_DEFAULT'));
        }

        $idShop = $context->shop->id;

        if (!isset($parentCategoryCache[$idShop][$idLang])) {

            if (!isset($parentCategoryCache[$idShop][$idLang])) {
                $parentCategoryCache[$idShop] = [];
            }

            $parentCategoryCache[$idShop][$idLang] = [];
        }

        while (true) {

            if (!empty($parentCategoryCache[$idShop][$idLang][$idCurrent])) {
                $result = $parentCategoryCache[$idShop][$idLang][$idCurrent];
            } else {
                $sql = (new DbQuery())
                    ->select('c.*, cl.*')
                    ->from('category', 'c')
                    ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category`')
                    ->where('`id_lang` = ' . (int) $idLang);

                $sql->where('c.`id_category` = ' . (int) $idCurrent);

                $rootCategory = Category::getRootCategory();

                if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP
                    && (!Tools::isSubmit('id_category') || (int) Tools::getValue('id_category') == (int) $rootCategory->id || (int) $rootCategory->id == (int) $context->shop->id_category)
                ) {
                    $sql->where('c.`id_parent` != 0');
                }

                $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);
                $parentCategoryCache[$idShop][$idLang][$idCurrent] = $result;
            }

            if ($result) {
                $categories[] = $result;
            } else

            if (!$categories) {
                $categories = [];
            }

            if (!$result || ($result['id_category'] == $context->shop->id_category)) {
                return $categories;
            }

            $idCurrent = $result['id_parent'];
        }

    }

    /**
     * @param $idGroup
     *
     * @return bool|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function addGroupsIfNoExist($idGroup) {

        $groups = $this->getGroups();

        if (!in_array((int) $idGroup, $groups)) {
            return $this->addGroups([(int) $idGroup]);
        }

        return false;
    }

    /**
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getGroups() {

        $cache_id = 'Category::getGrouEPH_' . (int) $this->id;

        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('cg.`id_group`')
                    ->from('category_group', 'cg')
                    ->where('cg.`id_category` = ' . (int) $this->id)
            );
            $groups = [];

            foreach ($result as $group) {
                $groups[] = $group['id_group'];
            }

            Cache::store($cache_id, $groups);

            return $groups;
        }

        return Cache::retrieve($cache_id);
    }

    /**
     * @param $way
     * @param $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('cp.`id_category`, cp.`position`, cp.`id_parent`')
            ->from('category', 'cp')
            ->where('cp.`id_parent` = ' . (int) $this->id_parent)
            ->orderBy('cp.`position` ASC')
        )) {
            return false;
        }

        $moved_category = false;

        foreach ($res as $category) {

            if ((int) $category['id_category'] == (int) $this->id) {
                $moved_category = $category;
            }

        }

        if ($moved_category === false) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = (Db::getInstance()->execute(
            '
            UPDATE `' . _DB_PREFIX_ . 'category` c
            SET c.`position`= c.`position` ' . ($way ? '- 1' : '+ 1') . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE c.`position`
            ' . ($way
                ? '> ' . (int) $moved_category['position'] . ' AND c.`position` <= ' . (int) $position
                : '< ' . (int) $moved_category['position'] . ' AND c.`position` >= ' . (int) $position) . '
            AND c.`id_parent`=' . (int) $moved_category['id_parent']
        )
            && Db::getInstance()->execute(
                '
            UPDATE `' . _DB_PREFIX_ . 'category` c
            SET c.`position` = ' . (int) $position . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE c.`id_parent` = ' . (int) $moved_category['id_parent'] . '
            AND c.`id_category`=' . (int) $moved_category['id_category']
            ));
        Hook::exec('actionCategoryUpdate', ['category' => new Category($moved_category['id_category'])]);

        return $result;
    }

    /**
     * Check if current category is a child of shop root category
     *
     * @since   1.5.0
     *
     * @param Shop $shop
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function inShop(Shop $shop = null) {

        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        if (!$interval = Category::getInterval($shop->getCategory())) {
            return false;
        }

        return ($this->nleft >= $interval['nleft'] && $this->nright <= $interval['nright']);
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getChildrenWs() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT c.`id_category` AS id
        FROM `' . _DB_PREFIX_ . 'category` c
        WHERE c.`id_parent` = ' . (int) $this->id . '
        AND c.`active` = 1
        ORDER BY c.`position` ASC'
        );

        return $result;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getProductsWs() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT cp.`id_product` AS id
        FROM `' . _DB_PREFIX_ . 'category_product` cp
        WHERE cp.`id_category` = ' . (int) $this->id . '
        ORDER BY `position` ASC'
        );

        return $result;
    }

    /**
     * @return false|int|null|string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getWsNbProductsRecursive() {

        $nb_product_recursive = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
            SELECT COUNT(DISTINCT(id_product))
            FROM  `' . _DB_PREFIX_ . 'category_product`
            WHERE id_category = ' . (int) $this->id . ' OR
            EXISTS (
                SELECT 1
                FROM `' . _DB_PREFIX_ . 'category` c2
                WHERE `' . _DB_PREFIX_ . 'category_product`.id_category = c2.id_category
                    AND c2.nleft > ' . (int) $this->nleft . '
                    AND c2.nright < ' . (int) $this->nright . '
                    AND c2.active = 1
            )
        '
        );

        if (!$nb_product_recursive) {
            return -1;
        }

        return $nb_product_recursive;
    }

    /**
     * @param int $idShop
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function isParentCategoryAvailable($idShop) {

        $id = Context::getContext()->shop->id;
        $idShop = $id ? $id : Configuration::get('EPH_SHOP_DEFAULT');

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('c.`id_category`')
                ->from('category', 'c')
                ->where('c.`id_parent` = ' . (int) $this->id_parent)
        );
    }

    /**
     * @param $id_shop
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function existsInShop($id_shop) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_category`')
                ->from('category')
                ->where('`id_category` = ' . (int) $this->id)
        );
    }

    /**
     * Recursively add specified category childs to $to_delete array
     *
     * @param array &$toDelete  Array reference where categories ID will be saved
     * @param int   $idCategory Parent category ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    protected function recursiveDelete(&$toDelete, $idCategory) {

        if (!is_array($toDelete) || !$idCategory) {
            die(Tools::displayError());
        }

        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('category', $this->id);
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_category`')
                ->from('category')
                ->where('`id_parent` = ' . (int) $idCategory)
        );

        foreach ($result as $row) {
            $toDelete[] = (int) $row['id_category'];
            $this->recursiveDelete($toDelete, (int) $row['id_category']);
        }

    }

    /**
     * Get all ids of all subcategories of the current category
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @return array list of ids of the subcategories
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     */
    public function getAllSubcategories() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_category`')
                ->from('category')
                ->where('`nleft` > ' . $this->nleft . ' AND `nright` < ' . $this->nright)
        );
    }

    public function getAgentCategories($id_lang, $id_parent, $active = true) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('
        SELECT c.*, cl.id_lang, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $id_lang . ')
        WHERE `id_parent` = ' . (int) $id_parent . '
        ' . ($active ? 'AND `active` = 1' : '') . '
        GROUP BY c.`id_category`
        ORDER BY `level_depth` ASC, c.`position` ASC');

        $formated_medium = ImageType::getFormatedName('medium');

        foreach ($result as &$row) {
            $row['id_image'] = (Tools::file_exists_cache(_EPH_CAT_IMG_DIR_ . (int) $row['id_category'] . '.jpg') || Tools::file_exists_cache(_EPH_CAT_IMG_DIR_ . (int) $row['id_category'] . '_thumb.jpg')) ? (int) $row['id_category'] : Language::getIsoById($id_lang) . '-default';
            $row['legend'] = 'no picture';
        }

        return $result;
    }

    public function fragmenteProduct($products) {

        $array = array_chunk($products, 6);

        return array_chunk($products, 6);
    }

}
