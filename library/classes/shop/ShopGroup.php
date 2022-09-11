<?php


/**
 * Class ShopGroupCore
 *
 * @since 1.9.1.0
 * @version 1.8.1.0 Initial version
 */
class ShopGroupCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $name;
    public $active = true;
    public $share_customer;
    public $share_stock;
    public $share_order;
    public $deleted;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'shop_group',
        'primary' => 'id_shop_group',
        'fields'  => [
            'name'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'share_customer' => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'share_order'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'share_stock'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'active'         => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'deleted'        => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
        ],
    ];

    /**
     * @see     ObjectModel::getFields()
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getFields()
    {
        if (!$this->share_customer || !$this->share_stock) {
            $this->share_order = false;
        }

        return parent::getFields();
    }

    /**
     * @param bool $active
     *
     * @return PhenyxShopCollection
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getShopGroups($active = true)
    {
        $groups = new PhenyxShopCollection('ShopGroup');
        $groups->where('deleted', '=', false);
        if ($active) {
            $groups->where('active', '=', true);
        }

        return $groups;
    }

    /**
     * @param bool $active
     *
     * @return int Total of shop groups
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getTotalShopGroup($active = true)
    {
        return count(ShopGroup::getShopGroups($active));
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function haveShops()
    {
        return (bool) $this->getTotalShops();
    }

    /**
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getTotalShops()
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('shop', 's')
                ->where('`id_shop_group` = '.(int) $this->id)
        );
    }

    /**
     * @param int $idGroup
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getShopsFromGroup($idGroup)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('s.`id_shop`')
                ->from('shop', 's')
                ->where('`id_shop_group` = '.(int) $idGroup)
        );
    }

    /**
     * Return a group shop ID from group shop name
     *
     * @param string $name
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getIdByName($name)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_shop_group`')
                ->from('shop_group')
                ->where('`name` = \''.pSQL($name).'\'')
        );
    }

    /**
     * Detect dependency with customer or orders
     *
     * @param int    $idShopGroup
     * @param string $check all|customer|order
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function hasDependency($idShopGroup, $check = 'all')
    {
        $listShops = Shop::getShops(false, $idShopGroup, true);
        if (!$listShops) {
            return false;
        }

        if ($check == 'all' || $check == 'customer') {
            $totalCustomer = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer')
                    ->where('`id_shop` IN ('.implode(', ', $listShops).')')
            );
            if ($totalCustomer) {
                return true;
            }
        }

        if ($check == 'all' || $check == 'order') {
            $totalOrder = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('orders')
                    ->where('`id_shop` IN ('.implode(', ', $listShops).')')
            );
            if ($totalOrder) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @param bool   $idShop
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function shopNameExists($name, $idShop = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_shop`')
                ->from('shop')
                ->where('`name` = \''.pSQL($name).'\'')
                ->where('`id_shop_group` = '.(int) $this->id)
                ->where($idShop ? 'id_shop != '.(int) $idShop : '')
        );
    }
}
