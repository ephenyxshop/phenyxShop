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
 * Class OrderMessageCore
 *
 * @since 1.9.1.0
 */
class OrderMessageCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string name name */
    public $name;

    /** @var string message content */
    public $message;

    /** @var string Object creation date */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'order_message',
        'primary'   => 'id_order_message',
        'multilang' => true,
        'fields'    => [
            'date_add' => ['type' => self::TYPE_DATE,                   'validate' => 'isDate'                                           ],
            'name'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128 ],
            'message'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isMessage',     'required' => true, 'size' => 1200],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id'       => ['sqlId' => 'id_discount_type', 'xlink_resource' => 'order_message_lang'],
            'date_add' => ['sqlId' => 'date_add'],
        ],
    ];

    /**
     * @param $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getOrderMessages($idLang)
    {
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('
		SELECT om.id_order_message, oml.name, oml.message
		FROM '._DB_PREFIX_.'order_message om
		LEFT JOIN '._DB_PREFIX_.'order_message_lang oml ON (oml.id_order_message = om.id_order_message)
		WHERE oml.id_lang = '.(int) $idLang.'
		ORDER BY name ASC');
    }
}
