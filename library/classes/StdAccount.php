<?php

class StdAccount extends PhenyxObjectModel {

	public $id_stdaccount;
	public $account;
	public $id_stdaccount_type;
	public $id_stdaccount_subtype;
	public $vat_exonerate;
	public $default_vat;
	public $counterpart;
	public $pointed_solde;

	public $name;
	public $description;
	public $signsold;

	public $account_vat;
	public $account_counterpart;
	public $nameType;

	public $defaultVatName;
	public $defaultVatCode;

	public $counterPartName;
	public $counterPartCode;

	/**
	 * @see PhenyxObjectModel::$definition
	 */
	public static $definition = [
		'table'     => 'stdaccount',
		'primary'   => 'id_stdaccount',
		'multilang' => true,
		'fields'    => [
			'account'               => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			'id_stdaccount_type'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'id_stdaccount_subtype' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
			'vat_exonerate'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'default_vat'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'counterpart'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'pointed_solde'        => ['type' => self::TYPE_FLOAT],
			/* Lang fields */
			'name'                  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
			'description'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],

		],

	];

	public function __construct($id = null, $id_lang = null) {

		parent::__construct($id, $id_lang);

		if ($this->id) {
			$this->account_vat = $this->getVatAccount();
			$this->account_counterpart = $this->getCounterpartAccount();
			$this->nameType = $this->getAccountNameType();
			$this->defaultVatName = $this->getDefaultVatName();
			$this->defaultVatCode = $this->account_vat;
			$this->counterPartName = $this->getCounterPartName();
			$this->counterPartCode = $this->account_counterpart;
		}

	}

	public function getDefaultVatName() {

		if (!empty($this->default_vat)) {
			$context = Context::getContext();
			$account = new StdAccount($this->default_vat, $context->language->id);
			return $account->name;
		}

	}

	public static function getAccountValueById($idStdaccount) {

		$account = new StdAccount($idStdaccount);
		return $account->account;

	}

	public function getVatAccount() {

		if (!empty($this->default_vat)) {
			$account = new StdAccount($this->default_vat);
			return $account->account;
		}

	}

	public function getCounterPartName() {

		if (!empty($this->counterpart)) {
			$account = new StdAccount($this->counterpart);
			return $account->account;
		}

	}

	public function getCounterpartAccount() {

		if (!empty($this->counterpart)) {
			$context = Context::getContext();
			$account = new StdAccount($this->counterpart, $context->language->id);
			return $account->name;
		}

	}

	public function getAccountNameType() {

		$name = Db::getInstance()->getValue(
			(new DbQuery())
				->select('`name`')
				->from('stdaccount_type_lang')
				->where('`id_stdaccount_type` = ' . (int) $this->id_stdaccount_type)
				->where('`id_lang` = ' . Context::getContext()->language->id)
		);

		return $name;
	}

	public static function getBankStdAccount($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.id_stdaccount, a.account, sl.name, sl.`description`')
				->from('stdaccount', 'a')
				->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $idLang)
				->where('a.`id_stdaccount_type` = 7')
				->orderBy('`account` ASC')
		);
	}

	public static function getExpensesStdAccount($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.id_stdaccount, a.account, sl.name, sl.`description`')
				->from('stdaccount', 'a')
				->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $idLang)
				->where('a.`id_stdaccount_type` = 8')
				->orderBy('`account` ASC')
		);
	}

	public static function getProfitsStdAccount($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.id_stdaccount, a.account, sl.name, sl.`description`')
				->from('stdaccount', 'a')
				->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $idLang)
				->where('a.`id_stdaccount_type` = 9')
				->orderBy('`account` ASC')
		);
	}

	public static function getVATStdAccount($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.id_stdaccount, a.account, sl.name, sl.`description`')
				->from('stdaccount', 'a')
				->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $idLang)
				->where('a.`id_stdaccount_subtype` = 3')
				->orderBy('`account` ASC')
		);
	}

	public static function getAccountType($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('stdaccount_type_lang')
				->where('`id_lang` = ' . $idLang)
				->orderBy('`id_stdaccount_type` ASC')
		);

	}

	public static function getAccountRacineType($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('stdaccount_type_lang')
				->where('`id_lang` = ' . $idLang)
				->orderBy('`racine` ASC')
		);

	}

	public static function getAccountSubType($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('stdaccount_subtype_lang')
				->where('`id_lang` = ' . $idLang)
				->orderBy('`id_stdaccount_subtype` ASC')
		);

	}

	public static function getAccountByidType($idType, $idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.*, sl.*, stl.`name` as `type`')
				->from('stdaccount', 'a')
				->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $idLang)
				->leftJoin('stdaccount_type_lang', 'stl', 'stl.`id_stdaccount_type` = a.`id_stdaccount_type` AND stl.`id_lang`  = ' . (int) $idLang)
				->where('a.`id_stdaccount_type` = ' . (int) $idType)
		);

	}

	public static function getTypeByidType($idType, $idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return Db::getInstance()->getRow(
			(new DbQuery())
				->select('st.*,  stl.*')
				->from('stdaccount_type', 'st')
				->leftJoin('stdaccount_type_lang', 'stl', 'stl.`id_stdaccount_type` = st.`id_stdaccount_type` AND stl.`id_lang`  = ' . (int) $idLang)
				->where('st.`id_stdaccount_type` = ' . (int) $idType)
		);

	}

	public static function generateSupplyerAccount() {

		$type = Configuration::get('EPH_SUPPLIER_AFFECTATION');

	}
	
	public static function getAccountByName($name) {
		
		$idAccount = Db::getInstance()->getValue(
			(new DbQuery())
				->select('`id_stdaccount`')
				->from('stdaccount')
				->where('`account` = \'' . $name.'\'')
		);
		
		if($idAccount > 0) {
			return new StdAccount($idAccount);
		} else {
			return new StdAccount();
		}
		
		

	}

	public static function generateCustomerAccount() {}

}
