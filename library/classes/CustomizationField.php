<?php

/**
 * Class CustomizationFieldCore
 *
 * @since 1.9.1.0
 */
class CustomizationFieldCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_product;
    /** @var int Customization type (0 File, 1 Textfield) (See Product class) */
    public $type;
    /** @var bool Field is required */
    public $required;
    /** @var string Label for customized field */
    public $name;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'customization_field',
        'primary'        => 'id_customization_field',
        'multilang'      => true,        
        'fields'         => [
            /* Classic fields */
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'type'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'required'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],

            /* Lang fields */
            'name'       => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'size' => 255],
        ],
    ];
    protected $webserviceParameters = [
        'fields' => [
            'id_product' => [
                'xlink_resource' => [
                    'resourceName' => 'products',
                ],
            ],
        ],
    ];
}
