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
 * Class OrderCartRuleCore
 *
 * @since 1.9.1.0
 */
class OrderCartRuleCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_order_cart_rule;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_cart_rule;

    /** @var int */
    public $id_order_invoice;

    /** @var string */
    public $name;

    /** @var float value (tax incl.) of voucher */
    public $value;

    /** @var float value (tax excl.) of voucher */
    public $value_tax_excl;

    /** @var bool value : voucher gives free shipping or not */
    public $free_shipping;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_cart_rule',
        'primary' => 'id_order_cart_rule',
        'fields'  => [
            'id_order'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_cart_rule'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_order_invoice' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                    ],
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',  'required' => true],
            'value'            => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'value_tax_excl'   => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true],
            'free_shipping'    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                          ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_order' => ['xlink_resource' => 'orders'],
        ],
    ];
}
