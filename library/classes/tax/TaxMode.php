<?php
/**
 * 2007-2016 PhenyxShop
 *
 * ephenyx is an extension to the PhenyxShop e-commerce software developed by PhenyxShop SA
 * Copyright (C) 2017-2019 ephenyx
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
 * @author    ephenyx <contact@ephenyx.com>
 * @author    PhenyxShop SA <contact@PhenyxShop.com>
 * @copyright 2017-2019 ephenyx
 * @copyright 2007-2016 PhenyxShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PhenyxShop is an internationally registered trademark & property of PhenyxShop SA
 */

/**
 * @since 2.1.0.0
 */
class TaxModeCore extends ObjectModel {

	// @codingStandardsIgnoreStart
	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = [
		'table'     => 'tax_mode',
		'primary'   => 'id_tax_mode',
		'multilang' => true,
		'fields'    => [
			'id_sell_account' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'id_buy_account' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'sell_account' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			'buy_account' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			/* Lang fields */
			'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 64],
		],
	];
	public $id_tax_mode;
	public $id_sell_account;
	public $id_buy_account;
	public $name;
	
	public $sell_account;
	public $buy_account;
	
	/**
	 * GenderCore constructor.
	 *
	 * @param int|null $id
	 * @param int|null $idLang
	 * @param int|null $idShop
	 *
	 * @since 2.1.0.0
	 */
	public function __construct($id = null, $idLang = null, $idShop = null) {

		parent::__construct($id, $idLang, $idShop);
	}

	/**
	 * @param null $idLang
	 *
	 * @return PhenyxShopCollection
	 *
	 * @since 2.1.0.0
	 */
	public static function getTaxModes($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		$taxModes = new PhenyxShopCollection('TaxMode', $idLang);

		return $taxModes;
	}

	

}
