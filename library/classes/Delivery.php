<?php

/**
 * Class DeliveryCore
 *
 * @since 1.9.1.0
 */
class DeliveryCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_delivery;
    /** @var int * */
    public $id_company;
    /** @var int * */
    public $id_company_group;
    /** @var int */
    public $id_carrier;
    /** @var int */
    public $id_range_price;
    /** @var int */
    public $id_range_weight;
    /** @var int */
    public $id_zone;
    /** @var float */
    public $price;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'delivery',
        'primary' => 'id_delivery',
        'fields'  => [
            'id_carrier'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_range_price'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_range_weight' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_zone'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_shop'         => ['type' => self::TYPE_INT],
            'id_shop_group'   => ['type' => self::TYPE_INT],
            'price'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
        ],
    ];
    protected $webserviceParameters = [
        'objectsNodeName' => 'deliveries',
        'fields'          => [
            'id_carrier'      => ['xlink_resource' => 'carriers'],
            'id_range_price'  => ['xlink_resource' => 'price_ranges'],
            'id_range_weight' => ['xlink_resource' => 'weight_ranges'],
            'id_zone'         => ['xlink_resource' => 'zones'],
        ],
    ];

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getFields() {

        $fields = parent::getFields();

        // @todo add null management in definitions

        if ($this->id_shop) {
            $fields['id_shop'] = (int) $this->id_shop;
        } else {
            $fields['id_shop'] = null;
        }

        if ($this->id_shop_group) {
            $fields['id_shop_group'] = (int) $this->id_shop_group;
        } else {
            $fields['id_shop_group'] = null;
        }

        return $fields;
    }

}
