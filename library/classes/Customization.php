<?php

/**
 * Class CustomizationCore
 *
 * @since 1.9.1.0
 */
class CustomizationCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int $id_product_attribute */
    public $id_product_attribute;
    /** @var int $id_address_delivery */
    public $id_address_delivery;
    /** @var int $id_cart */
    public $id_cart;
    /** @var int $id_product */
    public $id_product;
    /** @var int $quantity */
    public $quantity;
    /** @var int $quantity_refunded */
    public $quantity_refunded;
    /** @var int $quantity_returned */
    public $quantity_returned;
    /** @var bool $in_cart */
    public $in_cart;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customization',
        'primary' => 'id_customization',
        'fields'  => [
            /* Classic fields */
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address_delivery'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity_refunded'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity_returned'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'in_cart'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
        ],
    ];
    protected $webserviceParameters = [
        'fields'       => [
            'id_address_delivery' => [
                'xlink_resource' => [
                    'resourceName' => 'addresses',
                ],
            ],
            'id_cart'             => [
                'xlink_resource' => [
                    'resourceName' => 'carts',
                ],
            ],
            'id_product'          => [
                'xlink_resource' => [
                    'resourceName' => 'products',
                ],
            ],
        ],
        'associations' => [
            'customized_data_text_fields' => [
                'resource' => 'customized_data_text_field', 'virtual_entity' => true, 'fields' => [
                    'id_customization_field' => ['required' => true, 'xlink_resource' => 'product_customization_fields'],
                    'value'                  => [],
                ],
            ],
            'customized_data_images'      => [
                'resource' => 'customized_data_image', 'virtual_entity' => true, 'setter' => false, 'fields' => [
                    'id_customization_field' => ['xlink_resource' => 'product_customization_fields'],
                    'value'                  => [],
                ],
            ],
        ],
    ];

    /**
     * @param int $idOrder
     *
     * @return array|bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getReturnedCustomizations($idOrder) {

        if (($result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('ore.`id_order_return`, ord.`id_order_detail`, ord.`id_customization`, ord.`product_quantity`')
            ->from('order_return', 'ore')
            ->innerJoin('order_return_detail', 'ord', 'ord.`id_order_return` = ore.`id_order_return`')
            ->where('ore.`id_order` = ' . (int) $idOrder)
            ->where('ord.`id_customization` != 0')
        )) === false) {
            return false;
        }

        $customizations = [];

        foreach ($result as $row) {
            $customizations[(int) ($row['id_customization'])] = $row;
        }

        return $customizations;
    }

    /**
     * @param int $idCart
     *
     * @return array|bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getOrderedCustomizations($idCart) {

        if (!$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('`id_customization`, `quantity`')
            ->from('customization')
            ->where('`id_cart` = ' . (int) $idCart)
        )) {
            return false;
        }

        $customizations = [];

        foreach ($result as $row) {
            $customizations[(int) ($row['id_customization'])] = $row;
        }

        return $customizations;
    }

    /**
     * @param array $customizations
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function countCustomizationQuantityByProduct($customizations) {

        $total = [];

        foreach ($customizations as $customization) {
            $total[(int) $customization['id_order_detail']] = !isset($total[(int) $customization['id_order_detail']]) ? (int) $customization['quantity'] : $total[(int) $customization['id_order_detail']] + (int) $customization['quantity'];
        }

        return $total;
    }

    /**
     * @param int      $idCustomization
     * @param int      $idLang
     * @param int|null $idCompany
     *
     * @return bool|false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getLabel($idCustomization, $idLang, $idCompany = null) {

        if (!(int) $idCustomization || !(int) $idLang) {
            return false;
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`name`')
                ->from('customization_field_lang')
                ->where('`id_customization_field` = ' . (int) $idCustomization)
                ->where('`id_lang` = ' . (int) $idLang)
        );

        return $result;
    }

    /**
     * @param array $idsCustomizations
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function retrieveQuantitiesFromIds($idsCustomizations) {

        $quantities = [];

        $inValues = '';

        foreach ($idsCustomizations as $key => $idCustomization) {

            if ($key > 0) {
                $inValues .= ',';
            }

            $inValues .= (int) ($idCustomization);
        }

        if (!empty($inValues)) {
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_customization`, `id_product`, `quantity`, `quantity_refunded`, `quantity_returned`')
                    ->from('customization')
                    ->where('`id_customization` IN (' . $inValues . ')')
            );

            foreach ($results as $row) {
                $quantities[$row['id_customization']] = $row;
            }

        }

        return $quantities;
    }

    /**
     * @param int $idCart
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function countQuantityByCart($idCart) {

        $quantity = [];

        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`, SUM(`quantity`) AS quantity')
                ->from('customization')
                ->where('`id_cart` = ' . (int) $idCart)
                ->groupBy('`id_cart`, `id_product`, `id_product_attribute`')
        );

        foreach ($results as $row) {
            $quantity[$row['id_product']][$row['id_product_attribute']] = $row['quantity'];
        }

        return $quantity;
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isFeatureActive() {

        return Configuration::get('EPH_CUSTOMIZATION_FEATURE_ACTIVE');
    }

    /**
     * This method is allow to know if a Customization entity is currently used
     *
     * @param string|null $table
     * @param bool        $hasActiveColumn
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_customization_field`')
                ->from('customization_field')
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsCustomizedDataTextFields() {

        if (!$results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('`id_customization_field`, `value`')
            ->from('customization_field', 'cf')
            ->leftJoin('customized_data', 'cd', 'cf.`id_customization_field` = cd.`index`')
            ->where('`id_product` = ' . (int) $this->id_product)
            ->where('cf.`type` = 1')
        )) {
            return [];
        }

        return $results;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsCustomizedDataImages() {

        if (!$results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('`id_customization_field`, `value`')
            ->from('customization_field', 'cf')
            ->leftJoin('customized_data', 'cd', 'cf.`id_customization_field` = cd.`index`')
            ->where('`id_product` = ' . (int) $this->id_product)
            ->where('cf.`type` = 0')
        )) {
            return [];
        }

        return $results;
    }

   

}
