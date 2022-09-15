<?php

/**
 * Class CompareProductCore
 *
 * @since 1.9.1.0
 */
class CompareProductCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'compare',
        'primary' => 'id_compare',
        'fields'  => [
            'id_compare'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
    ];
    /** @var int $id_compare */
    public $id_compare;
    /** @var int $id_customer */
    public $id_customer;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * Get all compare products of the customer
     *
     * @param int $idCompare
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCompareProducts($idCompare) {

        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT `id_product`')
                ->from('compare', 'c')
                ->leftJoin('compare_product', 'cp', 'cp.`id_compare` = c.`id_compare`')
                ->where('cp.`id_compare` = ' . (int) $idCompare)
        );

        $compareProducts = null;

        if ($results) {

            foreach ($results as $result) {
                $compareProducts[] = (int) $result['id_product'];
            }

        }

        return $compareProducts;
    }

    /**
     * Add a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws PhenyxShopException
     */
    public static function addCompareProduct($idCompare, $idProduct) {

        // Check if compare row exists
        $idCompare = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_compare`')
                ->from('compare')
                ->where('`id_compare` = ' . (int) $idCompare)
        );

        if (!$idCompare) {
            $idCustomer = false;

            if (Context::getContext()->customer) {
                $idCustomer = Context::getContext()->customer->id;
            }

            $sql = Db::getInstance()->insert(
                'compare',
                [
                    'id_compare'  => ['type' => 'sql', 'value' => 'NULL'],
                    'id_customer' => (int) $idCustomer,
                ],
                true
            );

            if ($sql) {
                $idCompare = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('MAX(`id_compare`)')
                        ->from('compare')
                );
                Context::getContext()->cookie->id_compare = $idCompare;
            }

        }

        return Db::getInstance()->insert(
            'compare_product',
            [
                'id_compare' => (int) $idCompare,
                'id_product' => (int) $idProduct,
                'date_add'   => ['type' => 'sql', 'value' => 'NOW()'],
                'date_upd'   => ['type' => 'sql', 'value' => 'NOW()'],
            ]
        );
    }

    /**
     * Remove a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function removeCompareProduct($idCompare, $idProduct) {

        return Db::getInstance()->execute('
            DELETE cp FROM `' . _DB_PREFIX_ . 'compare_product` cp, `' . _DB_PREFIX_ . 'compare` c
            WHERE cp.`id_compare`=c.`id_compare`
            AND cp.`id_product` = ' . (int) $idProduct . '
            AND c.`id_compare` = ' . (int) $idCompare);
    }

    /**
     * Get the number of compare products of the customer
     *
     * @param int $idCompare
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNumberProducts($idCompare) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(`id_compare`)')
                ->from('compare_product')
                ->where('`id_compare` = ' . (int) $idCompare)
        );
    }

    /**
     * Clean entries which are older than the period
     *
     * @param string $period
     *
     * @return void
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function cleanCompareProducts($period = null) {

        if ($period !== null) {
            Tools::displayParameterAsDeprecated('period');
        }

        Db::getInstance()->execute(
            '
        DELETE cp, c FROM `' . _DB_PREFIX_ . 'compare_product` cp, `' . _DB_PREFIX_ . 'compare` c
        WHERE cp.date_upd < DATE_SUB(NOW(), INTERVAL 1 WEEK) AND c.`id_compare`=cp.`id_compare`'
        );
    }

    /**
     * Get the id_compare by id_customer
     *
     * @param int $idCustomer
     *
     * @return int $id_compare
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getIdCompareByIdCustomer($idCustomer) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_compare`')
                ->from('compare')
                ->where('`id_customer` = ' . (int) $idCustomer)
        );
    }

}
