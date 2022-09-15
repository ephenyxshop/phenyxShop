<?php


/**
 * Represents quantities available
 * It is either synchronized with Stock or manualy set by the seller
 *
 * @since 1.9.1.0
 */
class StockAvailableCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int identifier of the current product */
    public $id_product;
    /** @var int identifier of product attribute if necessary */
    public $id_product_attribute;
   
    /** @var int the quantity available for sale */
    public $quantity = 0;
    /** @var bool determine if the available stock value depends on physical stock */
    public $depends_on_stock = false;
    /** @var bool determine if a product is out of stock - it was previously in Product class */
    public $out_of_stock = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stock_available',
        'primary' => 'id_stock_available',
        'fields'  => [
            'id_product'           => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'quantity'             => ['type' => self::TYPE_INT,  'validate' => 'isInt',        'required' => true],
            'depends_on_stock'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',       'required' => true],
            'out_of_stock'         => ['type' => self::TYPE_INT,  'validate' => 'isInt',        'required' => true],
        ],
    ];

    
    public static function getStockAvailableIdByProductId($idProduct, $idProductAttribute = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('id_stock_available');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);

        if ($idProductAttribute !== null) {
            $query->where('id_product_attribute = '.(int) $idProductAttribute);
        }        

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    public static function synchronize($idProduct, $orderIdShop = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        //if product is pack sync recursivly product in pack
        if (Pack::isPack($idProduct)) {
            if (Validate::isLoadedObject($product = new Product((int) $idProduct))) {
                if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('EPH_PACK_STOCK_TYPE') > 0)) {
                    $productsPack = Pack::getItems($idProduct, (int) Configuration::get('EPH_LANG_DEFAULT'));
                    foreach ($productsPack as $productPack) {
                        static::synchronize($productPack->id);
                    }
                }
            } else {
                return false;
            }
        }

        // gets warehouse ids grouped by shops
        $idsWarehouse = Warehouse::getWarehouses();
        if ($orderIdShop !== null) {
            $orderWarehouses = [];
            $wh = Warehouse::getWarehouses(false, (int) $orderIdShop);
            foreach ($wh as $warehouse) {
                $orderWarehouses[] = $warehouse['id_warehouse'];
            }
        }

        // gets all product attributes ids
        $idsProductAttribute = [];
        foreach (Product::getProductAttributesIds($idProduct) as $idProductAttribute) {
            $idsProductAttribute[] = $idProductAttribute['id_product_attribute'];
        }

        // Allow to order the product when out of stock?
        $outOfStock = static::outOfStock($idProduct);

        $manager = StockManagerFactory::getManager();
        // loops on $ids_warehouse to synchronize quantities
        foreach ($idsWarehouse as $$warehouses) {
            // first, checks if the product depends on stock for the given shop $id_company
            if (static::dependsOnStock($idProduct)) {
                // init quantity
                $productQuantity = 0;

                // if it's a simple product
                if (empty($idsProductAttribute)) {
                    $allowedWarehouseForProduct = WareHouse::getProductWarehouseList((int) $idProduct, 0);
                    $allowedWarehouseForProductClean = [];
                    foreach ($allowedWarehouseForProduct as $warehouse) {
                        $allowedWarehouseForProductClean[] = (int) $warehouse['id_warehouse'];
                    }
                    $allowedWarehouseForProductClean = array_intersect($allowedWarehouseForProductClean, $warehouses);
                    if ($orderIdShop != null && !count(array_intersect($allowedWarehouseForProductClean, $orderWarehouses))) {
                        continue;
                    }

                    $productQuantity = $manager->getProductRealQuantities($idProduct, null, $allowedWarehouseForProductClean, true);

                    Hook::exec(
                        'actionUpdateQuantity',
                        [
                            'id_product'           => $idProduct,
                            'id_product_attribute' => 0,
                            'quantity'             => $productQuantity
                        ]
                    );
                } // else this product has attributes, hence loops on $ids_product_attribute
                else {
                    foreach ($idsProductAttribute as $idProductAttribute) {
                        $allowedWarehouseForCombination = WareHouse::getProductWarehouseList((int) $idProduct, (int) $idProductAttribute);
                        $allowedWarehouseForCombinationClean = [];
                        foreach ($allowedWarehouseForCombination as $warehouse) {
                            $allowedWarehouseForCombinationClean[] = (int) $warehouse['id_warehouse'];
                        }
                        $allowedWarehouseForCombinationClean = array_intersect($allowedWarehouseForCombinationClean, $warehouses);
                        if ($orderIdShop != null && !count(array_intersect($allowedWarehouseForCombinationClean, $orderWarehouses))) {
                            continue;
                        }

                        $quantity = $manager->getProductRealQuantities($idProduct, $idProductAttribute, $allowedWarehouseForCombinationClean, true);

                        $query = new DbQuery();
                        $query->select('COUNT(*)');
                        $query->from('stock_available');
                        $query->where('id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute);

                        if ((int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query)) {
                            $query = [
                                'table' => 'stock_available',
                                'data'  => ['quantity' => $quantity],
                                'where' => 'id_product = '.(int) $idProduct.' AND id_product_attribute = '.(int) $idProductAttribute,
                            ];
                            Db::getInstance()->update($query['table'], $query['data'], $query['where']);
                        } else {
                            $query = [
                                'table' => 'stock_available',
                                'data'  => [
                                    'quantity'             => $quantity,
                                    'depends_on_stock'     => 1,
                                    'out_of_stock'         => $outOfStock,
                                    'id_product'           => (int) $idProduct,
                                    'id_product_attribute' => (int) $idProductAttribute,
                                ],
                            ];
                           
                            Db::getInstance()->insert($query['table'], $query['data']);
                        }

                        $productQuantity += $quantity;

                        Hook::exec(
                            'actionUpdateQuantity',
                            [
                                'id_product'           => $idProduct,
                                'id_product_attribute' => $idProductAttribute,
                                'quantity'             => $quantity,
                            ]
                        );
                    }
                }
                // updates
                // if $id_product has attributes, it also updates the sum for all attributes
                if (($orderIdShop != null && array_intersect($warehouses, $orderWarehouses)) || $orderIdShop == null) {
                    $query = [
                        'table' => 'stock_available',
                        'data'  => ['quantity' => $productQuantity],
                        'where' => 'id_product = '.(int) $idProduct.' AND id_product_attribute = 0',
                    ];
                    Db::getInstance()->update($query['table'], $query['data'], $query['where']);
                }
            }
        }
        // In case there are no warehouses, removes product from StockAvailable
        if (count($idsWarehouse) == 0 && static::dependsOnStock((int) $idProduct)) {
            Db::getInstance()->update('stock_available', ['quantity' => 0], 'id_product = '.(int) $idProduct);
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');
    }

    public static function setProductDependsOnStock($idProduct, $dependsOnStock = true, $idProductAttribute = 0)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $existingId = static::getStockAvailableIdByProductId((int) $idProduct, (int) $idProductAttribute);
        if ($existingId > 0) {
            Db::getInstance()->update(
                'stock_available',
                [
                    'depends_on_stock' => (int) $dependsOnStock,
                ],
                'id_stock_available = '.(int) $existingId
            );
        } else {
            $params = [
                'depends_on_stock'     => (int) $dependsOnStock,
                'id_product'           => (int) $idProduct,
                'id_product_attribute' => (int) $idProductAttribute,
            ];

            

            Db::getInstance()->insert('stock_available', $params);
        }

        // depends on stock.. hence synchronizes
        if ($dependsOnStock) {
            static::synchronize($idProduct);
        }
    }

    public static function setProductOutOfStock($idProduct, $outOfStock = false, $idProductAttribute = 0)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $existingId = (int) static::getStockAvailableIdByProductId((int) $idProduct, (int) $idProductAttribute);

        if ($existingId > 0) {
            Db::getInstance()->update(
                'stock_available',
                ['out_of_stock' => (int) $outOfStock],
                'id_product = '.(int) $idProduct.(($idProductAttribute) ? ' AND id_product_attribute = '.(int) $idProductAttribute : '').static::addSqlShopRestriction(null, $idCompany)
            );
        } else {
            $params = [
                'out_of_stock'         => (int) $outOfStock,
                'id_product'           => (int) $idProduct,
                'id_product_attribute' => (int) $idProductAttribute,
            ];

            
            Db::getInstance()->insert('stock_available', $params, false, true, Db::ON_DUPLICATE_KEY);
        }
    }

    
    public static function getQuantityAvailableByProduct($idProduct = null, $idProductAttribute = null)
    {
        // if null, it's a product without attributes
        if ($idProductAttribute === null) {
            $idProductAttribute = 0;
        }

        $key = 'StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'-'.(int) $idProductAttribute;
        if (!Cache::isStored($key)) {
            $query = new DbQuery();
            $query->select('SUM(quantity)');
            $query->from('stock_available');

            // if null, it's a product without attributes
            if ($idProduct !== null) {
                $query->where('id_product = '.(int) $idProduct);
            }

            $query->where('id_product_attribute = '.(int) $idProductAttribute);
           
            $result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    public function add($autoDate = true, $nullValues = false)
    {
        if (!$result = parent::add($autoDate, $nullValues)) {
            return false;
        }

        $result &= $this->postSave();

        return $result;
    }

    public function update($nullValues = false)
    {
        if (!$result = parent::update($nullValues)) {
            return false;
        }

        $result &= $this->postSave();

        return $result;
    }

    public function postSave()
    {
        if ($this->id_product_attribute == 0) {
            return true;
        }

        

        if (!Configuration::get('EPH_DISP_UNAVAILABLE_ATTR')) {
            $combination = new Combination((int) $this->id_product_attribute);
            if ($colors = $combination->getColorsAttributes()) {
                $product = new Product((int) $this->id_product);
                foreach ($colors as $color) {
                    if ($product->isColorUnavailable((int) $color['id_attribute'])) {
                        Tools::clearColorListCache($product->id);
                        break;
                    }
                }
            }
        }

        $totalQuantity = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`quantity`) AS `quantity`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_product` = '.(int) $this->id_product)
                ->where('`id_product_attribute` <> 0 ')
        );
        $this->setQuantity($this->id_product, 0, $totalQuantity);

        return true;
    }

    public static function updateQuantity($idProduct, $idProductAttribute, $deltaQuantity)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }
        $product = new Product((int) $idProduct);
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        $stockManager = Adapter_ServiceLocator::get('Core_Business_Stock_StockManager');
        $stockManager->updateQuantity($product, $idProductAttribute, $deltaQuantity);

        return true;
    }

    public static function setQuantity($idProduct, $idProductAttribute, $quantity)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $context = Context::getContext();

       

        $dependsOnStock = static::dependsOnStock($idProduct);

        //Try to set available quantity if product does not depend on physical stock
        if (!$dependsOnStock) {
            $idStockAvailable = (int) static::getStockAvailableIdByProductId($idProduct, $idProductAttribute);
            if ($idStockAvailable) {
                $stockAvailable = new StockAvailable($idStockAvailable);
                $stockAvailable->quantity = (int) $quantity;
                $stockAvailable->update();
            } else {
                $outOfStock = static::outOfStock($idProduct, $idCompany);
                $stockAvailable = new StockAvailable();
                $stockAvailable->out_of_stock = (int) $outOfStock;
                $stockAvailable->id_product = (int) $idProduct;
                $stockAvailable->id_product_attribute = (int) $idProductAttribute;
                $stockAvailable->quantity = (int) $quantity;

                $stockAvailable->add();
            }

            Hook::exec(
                'actionUpdateQuantity',
                [
                    'id_product'           => $idProduct,
                    'id_product_attribute' => $idProductAttribute,
                    'quantity'             => $stockAvailable->quantity,
                ]
            );
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');
    }

    
    public static function removeProductFromStockAvailable($idProduct, $idProductAttribute = null)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        

        $res = Db::getInstance()->delete(
            'stock_available',
            '`id_product` = '.(int) $idProduct.($idProductAttribute ? ' AND `id_product_attribute` = '.(int) $idProductAttribute : '')
        );

        if ($idProductAttribute) {
            if ($shop === null || !Validate::isLoadedObject($shop)) {
                $shopDatas = [];        
            } else {
                $idCompany = (int) $shop->id;
            }

            $stockAvailable = new StockAvailable();
            $stockAvailable->id_product = (int) $idProduct;
            $stockAvailable->id_product_attribute = (int) $idProductAttribute;
            $stockAvailable->postSave();
        }

        Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $idProduct.'*');

        return $res;
    }

    public static function dependsOnStock($idProduct)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('depends_on_stock');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = 0');
        

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    public static function outOfStock($idProduct)
    {
        if (!Validate::isUnsignedId($idProduct)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('out_of_stock');
        $query->from('stock_available');
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = 0');

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($query);
    }

    
}
