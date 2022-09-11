<?php

/**
 * @since 2.1.0.0
 */
class PaymentTypeCore extends ObjectModel {

	// @codingStandardsIgnoreStart
	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = [
		'table'     => 'payment_type',
		'primary'   => 'id_payment_type',
		'multilang' => true,
		'fields'    => [

			/* Lang fields */
			'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 64],
		],
	];
	public $id_payment_type;
	public $name;

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
	public static function getPaymentTypes($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		$paymentTypes = new PhenyxShopCollection('PaymentType', $idLang);

		return $paymentTypes;
	}

}
