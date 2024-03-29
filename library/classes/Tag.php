<?php

/**
 * Class TagCore
 *
 * @since 1.9.1.0
 */
class TagCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tag',
        'primary' => 'id_tag',
        'fields'  => [
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'name'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];
    /** @var int Language id */
    public $id_lang;
    /** @var string Name */
    public $name;
    protected $webserviceParameters = [
        'fields' => [
            'id_lang' => ['xlink_resource' => 'languages'],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * TagCore constructor.
     *
     * @param int|null    $id
     * @param string|null $name
     * @param int|null    $idLang
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.0.0
     */
    public function __construct($id = null, $name = null, $idLang = null) {

        $this->def = Tag::getDefinition($this);
        $this->setDefinitionRetrocompatibility();

        if ($id) {
            parent::__construct($id);
        } else if ($name && Validate::isGenericName($name) && $idLang && Validate::isUnsignedId($idLang)) {
            $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('tag', 't')
                    ->where('`name` = \'' . pSQL($name) . '\'')
                    ->where('`id_lang` = ' . (int) $idLang)
            );

            if ($row) {
                $this->id = (int) $row['id_tag'];
                $this->id_lang = (int) $row['id_lang'];
                $this->name = $row['name'];
            }

        }

    }

    /**
     * Add several tags in database and link it to a product
     *
     * @param int          $idLang    Language id
     * @param int          $idProduct Product id to link tags with
     * @param string|array $tagList   List of tags, as array or as a string with comas
     * @param string       $separator Separator to split a given string inot an array.
     *                                Not needed if $tagList is an array already.
     *
     * @return bool Operation success
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.0.0
     */
    public static function addTags($idLang, $idProduct, $tagList, $separator = ',') {

        if (!Validate::isUnsignedId($idLang)) {
            return false;
        }

        if (!is_array($tagList)) {
            $tagList = explode($separator, $tagList);
        }

        $list = [];

        if (is_array($tagList)) {

            foreach ($tagList as $tag) {

                if (!Validate::isGenericName($tag)) {
                    return false;
                }

                $tag = trim(mb_substr($tag, 0, static::$definition['fields']['name']['size']));
                $tagObj = new Tag(null, $tag, (int) $idLang);

                /* Tag does not exist in database */

                if (!Validate::isLoadedObject($tagObj)) {
                    $tagObj->name = $tag;
                    $tagObj->id_lang = (int) $idLang;
                    $tagObj->add();
                }

                if (!in_array($tagObj->id, $list)) {
                    $list[] = $tagObj->id;
                }

            }

        }

        $insert = [];

        foreach ($list as $tag) {
            $insert[] = [
                'id_tag'     => (int) $tag,
                'id_product' => (int) $idProduct,
                'id_lang'    => (int) $idLang,
            ];
        }

        $result = Db::getInstance()->insert('product_tag', $insert);

        if ($list != []) {
            static::updateTagCount($list);
        }

        return $result;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.0.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function add($autoDate = true, $nullValues = false) {

        if (!parent::add($autoDate, $nullValues)) {
            return false;
        } else if (isset($_POST['products'])) {
            return $this->setProducts(Tools::getValue('products'));
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.0.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function setProducts($array) {

        $result = Db::getInstance()->delete('product_tag', '`id_tag` = ' . (int) $this->id);

        if (is_array($array)) {
            $array = array_map('intval', $array);
            $result &= PhenyxObjectModel::updateMultishopTable('Product', ['indexed' => 0], 'a.id_product IN (' . implode(',', $array) . ')');
            $ids = [];

            foreach ($array as $idProduct) {
                $ids[] = [
                    'id_product' => (int) $idProduct,
                    'id_tag'     => (int) $this->id,
                    'id_lang'    => (int) $this->id_lang,
                ];
            }

            if ($result) {
                $result &= Db::getInstance()->insert('product_tag', $ids);

                if (Configuration::get('EPH_SEARCH_INDEXATION')) {
                    $result &= Search::indexation(false);
                }

            }

        }

        static::updateTagCount([(int) $this->id]);

        return $result;
    }

    /**
     * @param array|null $tagList
     *
     * @since 1.9.1.0
     * @version 1.0.0
     * @throws PhenyxShopException
     */
    public static function updateTagCount($tagList = null) {

        if (!Module::getBatchMode()) {

            if ($tagList != null) {
                $tagListQuery = ' AND pt.id_tag IN (' . implode(',', $tagList) . ')';
                Db::getInstance()->execute('DELETE pt FROM `' . _DB_PREFIX_ . 'tag_count` pt WHERE 1=1 ' . $tagListQuery);
            } else {
                $tagListQuery = '';
            }

            Db::getInstance()->execute(
                'REPLACE INTO `' . _DB_PREFIX_ . 'tag_count` (id_group, id_tag, id_lang,  counter)
            SELECT cg.id_group, pt.id_tag, pt.id_lang,  COUNT(pt.id_tag) AS times
                FROM `' . _DB_PREFIX_ . 'product_tag` pt
                INNER JOIN `' . _DB_PREFIX_ . 'product` p
                    USING (id_product)
                JOIN (SELECT DISTINCT id_group FROM `' . _DB_PREFIX_ . 'category_group`) cg
                WHERE p.`active` = 1
                AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
                                LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cgo ON (cp.`id_category` = cgo.`id_category`)
                                WHERE cgo.`id_group` = cg.id_group AND p.`id_product` = cp.`id_product`)
                ' . $tagListQuery . '
                GROUP BY pt.id_tag, pt.id_lang, cg.id_group, id_shop ORDER BY NULL'
            );
            Db::getInstance()->execute(
                'REPLACE INTO `' . _DB_PREFIX_ . 'tag_count` (id_group, id_tag, id_lang, counter)
            SELECT 0, pt.id_tag, pt.id_lang,  COUNT(pt.id_tag) AS times
                FROM `' . _DB_PREFIX_ . 'product_tag` pt
                INNER JOIN `' . _DB_PREFIX_ . 'product` p
                    USING (id_product)
                WHERE p.`active` = 1
                ' . $tagListQuery . '
                GROUP BY pt.id_tag, pt.id_lang ORDER BY NULL'
            );
        }

    }

    /**
     * @param int $idLang
     * @param int $nb
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.9.1.0
     * @version 1.0.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getMainTags($idLang, $nb = 10) {

        $context = Context::getContext();

        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();

            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.`name`, `counter` AS `times`')
                    ->from('tag_count', 'pt')
                    ->leftJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                    ->where('pt.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1'))
                    ->where('pt.`id_lang` = ' . (int) $idLang)
                    ->where('pt.`id_shop` = ' . (int) $context->company->id)
                    ->orderBy('`times` DESC')
                    ->limit((int) $nb)
            );
        } else {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.`name`, `counter` AS `times`')
                    ->from('tag_count', 'pt')
                    ->leftJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                    ->where('pt.`id_group` = 0')
                    ->where('pt.`id_lang` = ' . (int) $idLang)
                    ->where('pt.`id_shop` = ' . (int) $context->company->id)
                    ->orderBy('`times` DESC')
                    ->limit((int) $nb)
            );
        }

    }

    /**
     * @param int $idProduct
     *
     * @return array|bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.0.0
     */
    public static function getProductTags($idProduct) {

        if (!$tmp = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('t.`id_lang`, t.`name`')
            ->from('tag', 't')
            ->leftJoin('product_tag', 'pt', 'pt.`id_tag` = t.`id_tag`')
            ->where('pt.`id_product` = ' . (int) $idProduct)

        )) {
            return false;
        }

        $result = [];

        foreach ($tmp as $tag) {
            $result[$tag['id_lang']][] = $tag['name'];
        }

        return $result;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.0.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function deleteTagsForProduct($idProduct) {

        $tagsRemoved = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_tag`')
                ->from('product_tag')
                ->where('`id_product` = ' . (int) $idProduct)
        );
        $result = Db::getInstance()->delete('product_tag', 'id_product = ' . (int) $idProduct);
        Db::getInstance()->delete('tag', 'NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'product_tag WHERE ' . _DB_PREFIX_ . 'product_tag.id_tag = ' . _DB_PREFIX_ . 'tag.id_tag)');
        $tagList = [];

        foreach ($tagsRemoved as $tagRemoved) {
            $tagList[] = $tagRemoved['id_tag'];
        }

        if ($tagList != []) {
            static::updateTagCount($tagList);
        }

        return $result;
    }

    /**
     * @param bool         $associated
     * @param Context|null $context
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.0.0
     */
    public function getProducts($associated = true, Context $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        $idLang = $this->id_lang ? $this->id_lang : $context->language->id;

        if (!$this->id && $associated) {
            return [];
        }

        $in = $associated ? 'IN' : 'NOT IN';

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('pl.`name`, pl.`id_product`')
                ->from('product', 'p')
                ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int) $idLang)
                ->where('p.`active` = 1')
                ->where($this->id ? ('p.`id_product` ' . $in . ' (SELECT pt.`id_product` FROM `' . _DB_PREFIX_ . 'product_tag` pt WHERE pt.`id_tag` = ' . (int) $this->id . ')') : '')
                ->orderBy('pl.`name`')
        );
    }

}
