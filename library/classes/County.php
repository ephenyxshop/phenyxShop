<?php

/**
 * @deprecated 1.0.0
 */
class CountyCore extends PhenyxObjectModel {

	const USE_BOTH_TAX = 0;
	const USE_COUNTY_TAX = 1;
	const USE_STATE_TAX = 2;

	// @codingStandardsIgnoreStart
	protected static $_cache_get_counties = [];
	protected static $_cache_county_zipcode = [];
	public $id;
	public $name;
	public $id_state;
	public $active;
	// @codingStandardsIgnoreEnd

	/**
	 * @see PhenyxObjectModel::$definition
	 */
	public static $definition = [
		'table'   => 'county',
		'primary' => 'id_county',
		'fields'  => [
			'name'     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
			'id_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'active'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
		],
	];

	protected $webserviceParameters = [
		'fields' => [
			'id_state' => ['xlink_resource' => 'states'],
		],
	];

	/**
	 * @deprecated 1.0.0
	 */
	public static function getCounties($id_state) {

		Tools::displayAsDeprecated();

		return false;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public static function getIdCountyByZipCode($id_state, $zip_code) {

		Tools::displayAsDeprecated();

		return false;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public static function deleteZipCodeByIdCounty($id_county) {

		Tools::displayAsDeprecated();

		return true;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public static function getIdCountyByNameAndIdState($name, $idState) {

		Tools::displayAsDeprecated();

		return false;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function delete() {

		return true;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function getZipCodes() {

		Tools::displayAsDeprecated();

		return false;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function addZipCodes($zipCodes) {

		Tools::displayAsDeprecated();

		return true;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function removeZipCodes($zipCodes) {

		Tools::displayAsDeprecated();

		return true;
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function breakDownZipCode($zipCodes) {

		Tools::displayAsDeprecated();

		return [0, 0];
	}

	/**
	 * @deprecated 1.0.0
	 */
	public function isZipCodeRangePresent($zipCodes) {

		Tools::displayAsDeprecated();

		return false;
	}

	/**
	 * @deprecated since 1.0.0
	 */
	public function isZipCodePresent($zipCode) {

		Tools::displayAsDeprecated();

		return false;
	}
}
