<?php

/**
 * Class AttributeCore
 *
 * @since   1.8.1.0
 * @version 1.8.5.0
 */
class AttributesCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int Group id which attribute belongs */
    public $id;

    public $id_attribute_group;
    /** @var string Name */
    public $name;
    /** @var string $color */
    public $color;
    /** @var int $position */
    public $position;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'attribute',
        'primary'   => 'id_attribute',
        'multilang' => true,
        'fields'    => [
            'id_attribute_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'color'              => ['type' => self::TYPE_STRING, 'validate' => 'isColor'],
            'position'           => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            /* Lang fields */
            'name'               => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
    ];

    protected $image_dir = _EPH_COL_IMG_DIR_;

    protected $webserviceParameters = [
        'objectsNodeName' => 'product_option_values',
        'objectNodeName'  => 'product_option_value',
        'fields'          => [
            'id_attribute_group' => ['xlink_resource' => 'product_options'],
        ],
    ];

    /**
     * AttributeCore constructor.
     *
     * @param null $id
     * @param null $idLang
     * @param null $idCompany
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct($id = null, $idLang = null) {

        $this->image_dir = _EPH_COL_IMG_DIR_;

        parent::__construct($id, $idLang);
    }

    /**
     * Get all attributes for a given language
     *
     * @param int  $idLang  Language id
     * @param bool $notNull Get only not null fields if true
     *
     * @return array Attributes
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function getAttributes($idLang, $notNull = false) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT ag.*, agl.*, a.`id_attribute`, al.`name`, agl.`name` AS `attribute_group`')
                ->from('attribute_group', 'ag')
                ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $idLang)
                ->leftJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
                ->leftJoin('attribute_lang', 'al', 'al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = ' . (int) $idLang)
                ->where($notNull ? 'a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '')
                ->orderBy('agl.`name` ASC, a.`position` ASC')
        );
    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function delete() {

        
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_product_attribute`')
                    ->from('product_attribute_combination')
                    ->where('`id_attribute` = ' . (int) $this->id)
            );
            $products = [];

            foreach ($result as $row) {
                $combination = new Combination($row['id_product_attribute']);
                $newRequest = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_product`, `default_on`')
                        ->from('product_attribute')
                        ->where('`id_product_attribute` = ' . (int) $row['id_product_attribute'])
                );

                foreach ($newRequest as $value) {

                    if ($value['default_on'] == 1) {
                        $products[] = $value['id_product'];
                    }

                }

                $combination->delete();
            }

            foreach ($products as $product) {
                $idProductAttribute = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('`id_product_attribute`')
                        ->from('product_attribute')
                        ->where('`id_product` = ' . (int) $product)
                );

                if (Validate::isLoadedObject($product = new Product((int) $product))) {
                    $product->deleteDefaultAttributes();
                    $product->setDefaultAttribute($idProductAttribute);
                }

            }

            // Delete associated restrictions on cart rules
            CartRule::cleanProductRuleIntegrity('attributes', $this->id);

            /* Reinitializing position */
            $this->cleanPositions((int) $this->id_attribute_group);
       

        $return = parent::delete();

        if ($return) {
            Hook::exec('actionAttributeDelete', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function update($nullValues = false) {

        $return = parent::update($nullValues);

        if ($return) {
            Hook::exec('actionAttributeSave', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function add($autoDate = true, $nullValues = false) {

        if ($this->position <= 0) {
            $this->position = Attributes::getHigherPosition($this->id_attribute_group) + 1;
        }

        $return = parent::add($autoDate, $nullValues);

        if ($return) {
            Hook::exec('actionAttributeSave', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * @param int    $idAttributeGroup
     * @param string $name
     * @param int    $idLang
     *
     * @return array|bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function isAttribute($idAttributeGroup, $name, $idLang) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('attribute_group', 'ag')
                ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $idLang)
                ->leftJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
                ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $idLang)
                ->where('al.`name` = \'' . pSQL($name) . '\'')
                ->where('ag.`id_attribute_group` = ' . (int) $idAttributeGroup)
                ->orderBy('agl.`name` ASC, a.`position` ASC')
        );

        return ((int) $result > 0);
    }

    /**
     * Get quantity for a given attribute combination
     * Check if quantity is enough to deserve customer
     *
     * @param int       $idProductAttribute Product attribute combination id
     * @param int       $qty                Quantity needed
     *
     * @param Shop|null $shop
     *
     * @return bool Quantity is available or not
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function checkAttributeQty($idProductAttribute, $qty, Shop $shop = null) {

        

        $result = StockAvailable::getQuantityAvailableByProduct(null, (int) $idProductAttribute);

        return ($result && $qty <= $result);
    }

    

    /**
     * Update array with veritable quantity
     *
     * @deprecated since 1.0.0
     *
     * @param array $arr
     *
     * @return bool
     */
    public static function updateQtyProduct(&$arr) {

        Tools::displayAsDeprecated();

        $idProduct = (int) $arr['id_product'];
        $qty = Attributes::getAttributeQty($idProduct);

        if ($qty !== false) {
            $arr['quantity'] = (int) $qty;

            return true;
        }

        return false;
    }

    /**
     * Return true if attribute is color type
     *
     * @acces   public
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function isColorAttribute() {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('ag.`group_type`')
                ->from('attribute_group', 'ag')
                ->innerJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
                ->where('`group_type` = \'color\'')
        );
    }

    /**
     * Get minimal quantity for product with attributes quantity
     *
     * @acces   public static
     *
     * @param int $idProductAttribute
     *
     * @return mixed Minimal Quantity or false
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function getAttributeMinimalQty($idProductAttribute) {

        $minimalQuantity = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`minimal_quantity`')
                ->from('product_attribute', 'pas')
                ->where('`id_product_attribute` = ' . (int) $idProductAttribute)
        );

        if ($minimalQuantity > 1) {
            return (int) $minimalQuantity;
        }

        return false;
    }

    /**
     * Move an attribute inside its group
     *
     * @param bool $way Up (1)  or Down (0)
     * @param int  $position
     *
     * @return bool Update result
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function updatePosition($way, $position) {

        $file = fopen("testAttributeUpdatePosition.txt", "w");

        if (!$idAttributeGroup = (int) Tools::getValue('id_attribute_group')) {
            $idAttributeGroup = (int) $this->id_attribute_group;
        }

        if (!$res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('a.`id_attribute`, a.`position`, a.`id_attribute_group`')
            ->from('attribute', 'a')
            ->where('a.`id_attribute_group` = ' . (int) $idAttributeGroup)
            ->orderBy('a.`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $attribute) {

            if ((int) $attribute['id_attribute'] == (int) $this->id) {
                $movedAttribute = $attribute;
            }

        }

        if (!isset($movedAttribute) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases

        $res1 = Db::getInstance()->update(
            'attribute',
            [
                'position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')],
            ],
            '`position`' . ($way ? '> ' . (int) $movedAttribute['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $movedAttribute['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_attribute_group`=' . (int) $movedAttribute['id_attribute_group']
        );

        $res2 = Db::getInstance()->update(
            'attribute',
            [
                'position' => (int) $position,
            ],
            '`id_attribute` = ' . (int) $movedAttribute['id_attribute'] . ' AND `id_attribute_group`=' . (int) $movedAttribute['id_attribute_group']
        );

        return ($res1 && $res2);
    }

    /**
     * Reorder attribute position in group $id_attribute_group.
     * Call it after deleting an attribute from a group.
     *
     * @param int  $idAttributeGroup
     * @param bool $useLastAttribute
     *
     * @return bool $return
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     * @throws PhenyxShopException
     */
    public function cleanPositions($idAttributeGroup, $useLastAttribute = true) {

        Db::getInstance()->execute('SET @i = -1', false);
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'attribute` SET `position` = @i:=@i+1 WHERE';

        if ($useLastAttribute) {
            $sql .= ' `id_attribute` != ' . (int) $this->id . ' AND';
        }

        $sql .= ' `id_attribute_group` = ' . (int) $idAttributeGroup . ' ORDER BY `position` ASC';

        return Db::getInstance()->execute($sql);
    }

    /**
     * getHigherPosition
     *
     * Get the higher attribute position from a group attribute
     *
     * @param int $idAttributeGroup
     *
     * @return int $position
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public static function getHigherPosition($idAttributeGroup) {

        $position = DB::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('attribute')
                ->where('`id_attribute_group` = ' . (int) $idAttributeGroup)
        );

        return (is_numeric($position)) ? $position : -1;
    }

}
