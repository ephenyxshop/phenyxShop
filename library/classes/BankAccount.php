<?php

class BankAccountCore extends ObjectModel {

	public $id;

	public $id_country;

	public $company_bank = 0;

	public $id_supplier = null;
	
	public $id_customer = null;

	public $id_stdaccount = null;

	public $code;

	public $owner;

	public $bank_name;

	public $iban;

	public $swift;

	public $bban;

	public $ics;

	public $active;

	public $stdaccount;

	public $explodeIban;

	public $iban_cases;

	public $iban_lenghts;

	public static $definition = [
		'table'   => 'bank_account',
		'primary' => 'id_bank_account',
		'fields'  => [
			'id_country'    => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'company_bank'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'id_supplier'   => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'id_customer'   => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'id_stdaccount' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'code'          => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'owner'         => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			'bank_name'     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			'iban'          => ['type' => self::TYPE_STRING],
			'bban'          => ['type' => self::TYPE_STRING],
			'swift'         => ['type' => self::TYPE_STRING, 'required' => true],
			'ics'           => ['type' => self::TYPE_STRING],
			'active'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
		],
	];

	public function __construct($id = null, $id_lang = null) {

		parent::__construct($id, $id_lang);

		if ($this->id) {
			$this->stdaccount = $this->getStdAccount();
			$this->explodeIban = Tools::str_rsplit($this->iban, 4);
			$this->iban_cases = count($this->explodeIban);
			$this->iban_lenghts = strlen($this->iban);
		}

	}
	
	public function delete() {

		if($notExist = Sepa::getSepaByIdBank($this->id)) {
			Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'mandat` WHERE `id_bank` = ' . (int) $this->id);
			return parent::delete();
		} else {
			Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'mandat` SET active = 0 WHERE `id_bank` = ' . (int) $this->id);
			$this->active = 0;
			$this->update();
			return true;
		}
		
	}

	public function getStdAccount() {

		if (!empty($this->id_stdaccount)) {
			$account = new StdAccount($this->id_stdaccount);
			return $account->account;
		}

	}

	public function add($autodate = true, $null_values = false) {

		if (!parent::add($autodate, $null_values)) {
			return false;
		}

		return true;

	}

	public static function getBankAccounts($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		$bankAccounts = new PhenyxShopCollection('BankAccount', $idLang);

		return $bankAccounts;
	}

	public static function getCountries($id_lang, $sepa = false) {

		$sql = 'SELECT cl.*, c.*
        FROM `' . _DB_PREFIX_ . 'bank_iban` c
        LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' . (int) $id_lang . ') ';

		if ($sepa) {
			$sql .= 'WHERE c.`sepa` = 1 ';
		}

		$sql .= 'ORDER BY c.iban DESC, cl.name ASC';
		return Db::getInstance()->ExecuteS($sql);
	}

	public static function hasIban($id_country) {

		return (Db::getInstance()->getRow('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'bank_iban`
            WHERE `id_country` =' . (int) $id_country));
	}

	public static function countryByIso($iso) {

		return (Db::getInstance()->getRow('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'bank_iban`
            WHERE `iso_iban` = \'' . pSQL($iso) . '\''));
	}

	public static function getBankIdBySupplierId($idSupplier) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('id_bank_account')
				->from('bank_account')
				->where('id_supplier = ' . (int) $idSupplier)
		);
	}

	public static function getBanksBySupplierId($idSupplier) {

		$collection = new PhenyxShopCollection('BankAccount');
		$collection->where('id_supplier', '=', (int) $idSupplier);

		return $collection;
	}

	public static function getBanksByCustomerId($idCustomer) {

		$collection = new PhenyxShopCollection('BankAccount');
		$collection->where('id_student', '=', (int) $idCustomer);

		return $collection;
	}
	
	public static function getCompanyBanks() {

		$collection = new PhenyxShopCollection('BankAccount');
		$collection->where('company_bank', '=', 1);

		return $collection;
	}

}
