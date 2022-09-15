<?php


/**
 * Class WarehouseCore
 *
 * @since 1.9.1.0
 */
class WarehouseCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int identifier of the warehouse */
    public $id;

    /** @var int Id of the address associated to the warehouse */
    public $id_address;

    /** @var string Reference of the warehouse */
    public $reference;

    /** @var string Name of the warehouse */
    public $name;

    /** @var int Id of the employee who manages the warehouse */
    public $id_employee;

    /** @var int Id of the valuation currency of the warehouse */
    public $id_currency;

    /** @var bool True if warehouse has been deleted (hence, no deletion in DB) */
    public $deleted = 0;

    /**
     * Describes the way a Warehouse is managed
     *
     * @var string enum WA|LIFO|FIFO
     */
    public $management_type;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'warehouse',
        'primary' => 'id_warehouse',
        'fields'  => [
            'id_address'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'reference'       => ['type' => self::TYPE_STRING, 'validate' => 'isString',          'required' => true, 'size' => 45],
            'name'            => ['type' => self::TYPE_STRING, 'validate' => 'isString',          'required' => true, 'size' => 45],
            'id_employee'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'management_type' => ['type' => self::TYPE_STRING, 'validate' => 'isStockManagement', 'required' => true              ],
            'id_currency'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',      'required' => true              ],
            'deleted'         => ['type' => self::TYPE_BOOL],
        ],
    ];

    /**
     * @see PhenyxObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'fields'       => [
            'id_address'  => ['xlink_resource' => 'addresses'],
            'id_employee' => ['xlink_resource' => 'employees'],
            'id_currency' => ['xlink_resource' => 'currencies'],
            'valuation'   => ['getter' => 'getWsStockValue', 'setter' => false],
            'deleted'     => [],
        ],
        'associations' => [
            'stocks'   => [
                'resource' => 'stock',
                'fields'   => [
                    'id' => [],
                ],
            ],
            'carriers' => [
                'resource' => 'carrier',
                'fields'   => [
                    'id' => [],
                ],
            ],
            'shops'    => [
                'resource' => 'shop',
                'fields'   => [
                    'id'   => [],
                    'name' => [],
                ],
            ],
        ],
    ];

    /**
     * Gets the shops associated to the current warehouse
     *
     * @return array Shops (id, name)
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getShops()
    {
        $query = new DbQuery();
        $query->select('ws.id_shop, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->leftJoin('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);

        return $res;
    }

    /**
     * Gets the carriers associated to the current warehouse
     *
     * @return array Ids of the associated carriers
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getCarriers($returnReference = false)
    {
        $idsCarrier = [];

        $query = new DbQuery();
        if ($returnReference) {
            $query->select('wc.id_carrier');
        } else {
            $query->select('c.id_carrier');
        }
        $query->from('warehouse_carrier', 'wc');
        $query->innerJoin('carrier', 'c', 'c.id_reference = wc.id_carrier');
        $query->where($this->def['primary'].' = '.(int) $this->id);
        $query->where('c.deleted = 0');
        $res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);

        if (!is_array($res)) {
            return $idsCarrier;
        }

        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $idsCarrier[$carrier] = $carrier;
            }
        }

        return $idsCarrier;
    }

    /**
     * Sets the carriers associated to the current warehouse
     *
     * @param array $idsCarriers
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setCarriers($idsCarriers)
    {
        if (!is_array($idsCarriers)) {
            $idsCarriers = [];
        }

        $rowToInsert = [];
        foreach ($idsCarriers as $idCarrier) {
            $rowToInsert[] = [$this->def['primary'] => $this->id, 'id_carrier' => (int) $idCarrier];
        }

        Db::getInstance()->execute(
            '
			DELETE FROM '._DB_PREFIX_.'warehouse_carrier
			WHERE '.$this->def['primary'].' = '.(int) $this->id
        );

        if ($rowToInsert) {
            Db::getInstance()->insert('warehouse_carrier', $rowToInsert);
        }
    }

    /**
     * For a given carrier, removes it from the warehouse/carrier association
     * If $id_warehouse is set, it only removes the carrier for this warehouse
     *
     * @param int $idCarrier   Id of the carrier to remove
     * @param int $idWarehouse optional Id of the warehouse to filter
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function removeCarrier($idCarrier, $idWarehouse = null)
    {
        Db::getInstance()->execute(
            '
			DELETE FROM '._DB_PREFIX_.'warehouse_carrier
			WHERE id_carrier = '.(int) $idCarrier.
            ($idWarehouse ? ' AND id_warehouse = '.(int) $idWarehouse : '')
        );
    }

    /**
     * Checks if a warehouse is empty - i.e. has no stock
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function isEmpty()
    {
        $query = new DbQuery();
        $query->select('SUM(s.physical_quantity)');
        $query->from('stock', 's');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query) == 0);
    }

    /**
     * Checks if the given warehouse exists
     *
     * @param int $idWarehouse
     *
     * @return bool Exists/Does not exist
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function exists($idWarehouse)
    {
        $query = new DbQuery();
        $query->select('id_warehouse');
        $query->from('warehouse');
        $query->where('id_warehouse = '.(int) $idWarehouse);
        $query->where('deleted = 0');

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query));
    }

    /**
     * For a given {product, product attribute} sets its location in the given warehouse
     * First, for the given parameters, it cleans the database before updating
     *
     * @param int    $idProduct          ID of the product
     * @param int    $idProductAttribute Use 0 if this product does not have attributes
     * @param int    $idWarehouse        ID of the warehouse
     * @param string $location           Describes the location (no lang id required)
     *
     * @return bool Success/Failure
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function setProductLocation($idProduct, $idProductAttribute, $idWarehouse, $location)
    {
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'warehouse_product_location`
			WHERE `id_product` = '.(int) $idProduct.'
			AND `id_product_attribute` = '.(int) $idProductAttribute.'
			AND `id_warehouse` = '.(int) $idWarehouse
        );

        $rowToInsert = [
            'id_product'           => (int) $idProduct,
            'id_product_attribute' => (int) $idProductAttribute,
            'id_warehouse'         => (int) $idWarehouse,
            'location'             => pSQL($location),
        ];

        return Db::getInstance()->insert('warehouse_product_location', $rowToInsert);
    }

    /**
     * Resets all product locations for this warehouse
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function resetProductsLocations()
    {
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'warehouse_product_location`
			WHERE `id_warehouse` = '.(int) $this->id
        );
    }

    /**
     * For a given {product, product attribute} gets its location in the given warehouse
     *
     * @param int $idProduct          ID of the product
     * @param int $idProductAttribute Use 0 if this product does not have attributes
     * @param int $idWarehouse        ID of the warehouse
     *
     * @return string Location of the product
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getProductLocation($idProduct, $idProductAttribute, $idWarehouse)
    {
        $query = new DbQuery();
        $query->select('location');
        $query->from('warehouse_product_location');
        $query->where('id_warehouse = '.(int) $idWarehouse);
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = '.(int) $idProductAttribute);

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query));
    }

    /**
     * For a given {product, product attribute} gets warehouse list
     *
     * @param int $idProduct          ID of the product
     * @param int $idProductAttribute Optional, uses 0 if this product does not have attributes
     * @param int $idCompany             Optional, ID of the shop. Uses the context shop id (@see Context::shop)
     *
     * @return array Warehouses (ID, reference/name concatenated)
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProductWarehouseList($idProduct, $idProductAttribute = 0)
    {
        // if it's a pack, returns warehouses if and only if some products use the advanced stock management
        
        $query = new DbQuery();
        $query->select('wpl.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse_product_location', 'wpl');
        $query->innerJoin('warehouse_shop', 'ws', 'ws.id_warehouse = wpl.id_warehouse');
        $query->innerJoin('warehouse', 'w', 'ws.id_warehouse = w.id_warehouse');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = '.(int) $idProductAttribute);
        $query->where('w.deleted = 0');
        $query->groupBy('wpl.id_warehouse');

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query));
    }

   
    public static function getWarehouses($ignoreShop = false)
    {
        

        $query = new DbQuery();
        $query->select('w.id_warehouse, CONCAT(reference, \' - \', name) as name');
        $query->from('warehouse', 'w');
        $query->where('deleted = 0');
        $query->orderBy('reference ASC');
        

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }

    

    /**
     * Gets the number of products in the current warehouse
     *
     * @return int Number of different id_stock
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getNumberOfProducts()
    {
        $query = '
			SELECT COUNT(t.id_stock)
			FROM
				(
					SELECT s.id_stock
				 	FROM '._DB_PREFIX_.'stock s
				 	WHERE s.id_warehouse = '.(int) $this->id.'
				 	GROUP BY s.id_product, s.id_product_attribute
				 ) as t';

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * Gets the number of quantities - for all products - in the current warehouse
     *
     * @return int Total Quantity
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getQuantitiesOfProducts()
    {
        $query = '
			SELECT SUM(s.physical_quantity)
			FROM '._DB_PREFIX_.'stock s
			WHERE s.id_warehouse = '.(int) $this->id;

        $res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);

        return ($res ? $res : 0);
    }

    /**
     * Gets the value of the stock in the current warehouse
     *
     * @return int Value of the stock
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getStockValue()
    {
        $query = new DbQuery();
        $query->select('SUM(s.`price_te` * s.`physical_quantity`)');
        $query->from('stock', 's');
        $query->where('s.`id_warehouse` = '.(int) $this->id);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given employee, gets the warehouse(s) he/she manages
     *
     * @param int $idEmployee Manager ID
     *
     * @return array ids_warehouse Ids of the warehouses
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getWarehousesByEmployee($idEmployee)
    {
        $query = new DbQuery();
        $query->select('w.id_warehouse');
        $query->from('warehouse', 'w');
        $query->where('w.id_employee = '.(int) $idEmployee);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * For a given product, returns the warehouses it is stored in
     *
     * @param int $idProduct          Product Id
     * @param int $idProductAttribute Optional, Product Attribute Id - 0 by default (no attribues)
     *
     * @return array Warehouses Ids and names
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getWarehousesByProductId($idProduct, $idProductAttribute = 0)
    {
        if (!$idProduct && !$idProductAttribute) {
            return [];
        }

        $query = new DbQuery();
        $query->select('DISTINCT w.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse', 'w');
        $query->leftJoin('warehouse_product_location', 'wpl', 'wpl.id_warehouse = w.id_warehouse');
        if ($idProduct) {
            $query->where('wpl.id_product = '.(int) $idProduct);
        }
        if ($idProductAttribute) {
            $query->where('wpl.id_product_attribute = '.(int) $idProductAttribute);
        }
        $query->orderBy('w.reference ASC');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * For a given $id_warehouse, returns its name
     *
     * @param int $idWarehouse Warehouse Id
     *
     * @return string Name
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getWarehouseNameById($idWarehouse)
    {
        $query = new DbQuery();
        $query->select('name');
        $query->from('warehouse');
        $query->where('id_warehouse = '.(int) $idWarehouse);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * For a given pack, returns the warehouse it can be shipped from
     *
     * @param int  $idProduct
     *
     * @param null $idCompany
     *
     * @return array|bool id_warehouse or false
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getPackWarehouses($idProduct, $idCompany = null)
    {
        if (!Pack::isPack($idProduct)) {
            return false;
        }

        if (is_null($idCompany)) {
            $idCompany = Context::getContext()->shop->id;
        }

        // warehouses of the pack
        $packWarehouses = WarehouseProductLocation::getCollection((int) $idProduct);
        // products in the pack
        $products = Pack::getItems((int) $idProduct, Configuration::get('EPH_LANG_DEFAULT'));

        // array with all warehouses id to check
        $list = [];

        // fills $list
        foreach ($packWarehouses as $pack_warehouse) {
            /** @var WarehouseProductLocation $pack_warehouse */
            $list['pack_warehouses'][] = (int) $pack_warehouse->id_warehouse;
        }

        // for each products in the pack
        foreach ($products as $product) {
            if ($product->advanced_stock_management) {
                // gets the warehouses of one product
                $productWarehouses = Warehouse::getProductWarehouseList((int) $product->id, (int) $product->cache_default_attribute, (int) $idCompany);
                $list[(int) $product->id] = [];
                // fills array with warehouses for this product
                foreach ($productWarehouses as $productWarehouse) {
                    $list[(int) $product->id][] = $productWarehouse['id_warehouse'];
                }
            }
        }

        $res = false;
        // returns final list
        if (count($list) > 1) {
            $res = call_user_func_array('array_intersect', $list);
        }

        return $res;
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function resetStockAvailable()
    {
        $products = WarehouseProductLocation::getProducts((int) $this->id);
        foreach ($products as $product) {
            StockAvailable::synchronize((int) $product['id_product']);
        }
    }

    /*********************************\
     *
     * Webservices Specific Methods
     *
     *********************************/

    /**
     * Webservice : gets the value of the warehouse
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getWsStockValue()
    {
        return $this->getStockValue();
    }

    /**
     * Webservice : gets the ids stock associated to this warehouse
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsStocks()
    {
        $query = new DbQuery();
        $query->select('s.id_stock as id');
        $query->from('stock', 's');
        $query->where('s.id_warehouse ='.(int) $this->id);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Webservice : gets the ids shops associated to this warehouse
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsShops()
    {
        $query = new DbQuery();
        $query->select('ws.id_shop as id, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->leftJoin('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);

        return $res;
    }

    /**
     * Webservice : gets the ids carriers associated to this warehouse
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsCarriers()
    {
        $idsCarrier = [];

        $query = new DbQuery();
        $query->select('wc.id_carrier as id');
        $query->from('warehouse_carrier', 'wc');
        $query->where($this->def['primary'].' = '.(int) $this->id);

        $res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);

        if (!is_array($res)) {
            return $idsCarrier;
        }

        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $idsCarrier[] = $carrier;
            }
        }

        return $idsCarrier;
    }
}
