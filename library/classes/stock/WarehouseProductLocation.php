<?php


/**
 * Class WarehouseProductLocationCore
 *
 * @since 1.9.1.0
 */
class WarehouseProductLocationCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var int product ID
     * */
    public $id_product;

    /**
     * @var int product attribute ID
     * */
    public $id_product_attribute;

    /**
     * @var int warehouse ID
     * */
    public $id_warehouse;

    /**
     * @var string location of the product
     * */
    public $location;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'warehouse_product_location',
        'primary' => 'id_warehouse_product_location',
        'fields'  => [
            'location'             => ['type' => self::TYPE_STRING, 'validate' => 'isReference',  'size' => 64                     ],
            'id_product'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                'required' => true],
            'id_warehouse'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                'required' => true],
        ],
    ];

    /**
     * @see PhenyxObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'        => [
            'id_product'           => ['xlink_resource' => 'products'],
            'id_product_attribute' => ['xlink_resource' => 'combinations'],
            'id_warehouse'         => ['xlink_resource' => 'warehouses'],
        ],
        'hidden_fields' => [],
    ];

    /**
     * For a given product and warehouse, gets the location
     *
     * @param int $idProduct          product ID
     * @param int $idProductAttribute product attribute ID
     * @param int $idWarehouse        warehouse ID
     *
     * @return string $location Location of the product
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getProductLocation($idProduct, $idProductAttribute, $idWarehouse)
    {
        // build query
        $query = new DbQuery();
        $query->select('wpl.location');
        $query->from('warehouse_product_location', 'wpl');
        $query->where(
            'wpl.id_product = '.(int) $idProduct.'
			AND wpl.id_product_attribute = '.(int) $idProductAttribute.'
			AND wpl.id_warehouse = '.(int) $idWarehouse
        );

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given product and warehouse, gets the WarehouseProductLocation corresponding ID
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $id_supplier
     *
     * @return int $id_warehouse_product_location ID of the WarehouseProductLocation
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getIdByProductAndWarehouse($idProduct, $idProductAttribute, $idWarehouse)
    {
        // build query
        $query = new DbQuery();
        $query->select('wpl.id_warehouse_product_location');
        $query->from('warehouse_product_location', 'wpl');
        $query->where(
            'wpl.id_product = '.(int) $idProduct.'
			AND wpl.id_product_attribute = '.(int) $idProductAttribute.'
			AND wpl.id_warehouse = '.(int) $idWarehouse
        );

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given product, gets its warehouses
     *
     * @param int $idProduct
     *
     * @return PhenyxShopCollection The type of the collection is WarehouseProductLocation
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getCollection($idProduct)
    {
        $collection = new PhenyxShopCollection('WarehouseProductLocation');
        $collection->where('id_product', '=', (int) $idProduct);

        return $collection;
    }

    /**
     * @param $idWarehouse
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProducts($idWarehouse)
    {
        return Db::getInstance()->executeS('SELECT DISTINCT id_product FROM '._DB_PREFIX_.'warehouse_product_location WHERE id_warehouse='.(int) $idWarehouse);
    }
}
