<?php
/**
 * 2007-2016 PhenyxShop
 *
 * ephenyx is an extension to the PhenyxShop e-commerce software developed by PhenyxShop SA
 * Copyright (C) 2017-2018 ephenyx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@ephenyx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PhenyxShop to newer
 * versions in the future. If you wish to customize PhenyxShop for your
 * needs please refer to https://www.ephenyx.com for more information.
 *
 *  @author    ephenyx <contact@ephenyx.com>
 *  @author    PhenyxShop SA <contact@PhenyxShop.com>
 *  @copyright 2017-2020 ephenyx
 *  @copyright 2007-2016 PhenyxShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PhenyxShop is an internationally registered trademark & property of PhenyxShop SA
 */

/**
 * Class OrderCarrierCore
 *
 * @since 1.9.1.0
 */
class OrderCarrierCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_order_carrier;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_carrier;

    /** @var int */
    public $id_order_invoice;

    /** @var float */
    public $weight;

    /** @var float */
    public $shipping_cost_tax_excl;

    /** @var float */
    public $shipping_cost_tax_incl;

    /** @var int */
    public $tracking_number;

    /** @var string Object creation date */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_carrier',
        'primary' => 'id_order_carrier',
        'fields'  => [
            'id_order'               => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_carrier'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',     'required' => true],
            'id_order_invoice'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                        ],
            'weight'                 => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'shipping_cost_tax_excl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'shipping_cost_tax_incl' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                             ],
            'tracking_number'        => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'                    ],
            'date_add'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                              ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_order'   => ['xlink_resource' => 'orders'  ],
            'id_carrier' => ['xlink_resource' => 'carriers'],
        ],
    ];
}
