<?php

/**
 * Class CompanyCore
 *
 * @since 2.1.0.0
 */
class CompanyCore extends ObjectModel {

	public $tax_system;
	public $tax_payment;
	public $start_date;
	public $first_accounting_end;
	public $accounting_period_start;
	public $accounting_period_end;
	/** @var int Country id */
	public $id_country_registration = 0;
	/** @var int State id */
	public $id_state;
	/** @var string Country name */
	public $country;
	/** @var string Alias (eg. Home, Work...) */
	public $company;
	/** @var string Company */
	public $company_name;
	
	public $company_url;
	
	public $activity_number;

	public $company_email;
	
	public $administratif_email;
	/** @var string Lastname */
	public $lastname;
	/** @var string Firstname */
	public $firstname;
	/** @var string Company first line */
	public $address1;
	/** @var string Company second line (optional) */
	public $address2;
	/** @var string Postal code */
	public $postcode;
	/** @var string City */
	public $city;
	/** @var string Phone number */
	public $phone;
	/** @var string Mobile phone number */
	public $phone_mobile;
	/** @var string SIRET */
	public $siret;
	
	public $register_city;
	/** @var string APE */
	public $ape;
	/** @var string VAT number */
	public $vat_number;
	/** @var string DNI number */
	public $dni;
	/** @var string Object creation date */
	public $date_add;
	/** @var string Object last modification date */
	public $date_upd;
	/** @var bool True if company has been deleted (staying in database as deleted) */
	public $deleted = 0;
	protected static $_idZones = [];
	protected static $_idCountries = [];
	protected $_includeContainer = false;
	// @codingStandardsIgnoreEnd

	public $next_accounting_start;
	public $next_accounting_end;
	public $saisie_end;
	
	public $rcs;
	
	public $working_plan = '{"monday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"tuesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"wednesday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"thursday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"friday":{"start":"09:00","end":"20:00","breaks":[{"start":"14:30","end":"15:00"}]},"saturday":null,"sunday":null}';

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = [
		'table'   => 'company',
		'primary' => 'id_company',
		'fields'  => [

			'id_country_registration' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
			'id_state'                => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
			'tax_system'              => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'tax_payment'             => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			'start_date'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'first_accounting_end'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'accounting_period_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'accounting_period_end'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'company'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
			'company_name'            => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
			'company_url'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'required' => true, 'size' => 255],
			'activity_number'         => ['type' => self::TYPE_STRING,  'size' => 64],
			'company_email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
			'administratif_email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
			'lastname'                => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32],
			'firstname'               => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32],
			'vat_number'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'address1'                => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
			'address2'                => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
			'postcode'                => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12],
			'city'                    => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64],
			'phone'                   => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
			'phone_mobile'            => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
			'dni'                     => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16],
			'siret'                   => ['type' => self::TYPE_STRING],
			'register_city'           => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 64],
			'ape'                     => ['type' => self::TYPE_STRING, 'validate' => 'isApe'],
			'deleted'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'working_plan'			  => ['type' => self::TYPE_STRING],
			'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
		],
	];

	/**
	 * Build an company
	 *
	 * @param int      $idCompany Existing company id in order to load object (optional)
	 * @param int|null $idLang
	 *
	 * @since 2.1.0.0
	 * @throws PhenyxShopException
	 */
	public function __construct($idCompany = null, $idLang = null) {

		parent::__construct($idCompany);

		/* Get and cache company country name */

		if ($this->id) {
			$this->country = Country::getNameById($idLang ? $idLang : Configuration::get('PS_LANG_DEFAULT'), $this->id_country_registration);
			$date = new DateTime($this->accounting_period_start);
			$date->modify('+1 year');
			$this->next_accounting_start = $date->format('Y-m-d');
			$date = new DateTime($this->accounting_period_end);
			$date->modify('+1 year');
			$this->next_accounting_end = $date->format('Y-m-d');
			$date = new DateTime($this->accounting_period_end);
			$date->modify('+2 year');
			$this->saisie_end = $date->format('Y-m-d');
			$this->rcs = $this->formatRcs();
			$this->workin_plan = Tools::jsonDecode($this->working_plan, true);
		}

	}
	
	public function formatRcs() {
		
		if(!empty($this->siret)) {
			return substr($this->siret, 0, -5);
		}
		
		return null;
	}

	/**
	 * @see     ObjectModel::add()
	 *
	 * @since 2.1.0.0
	 *
	 * @param bool $autoDate
	 * @param bool $nullValues
	 *
	 * @return bool
	 */
	public function add($autoDate = true, $nullValues = false) {

		if (!parent::add($autoDate, $nullValues)) {
			return false;
		}

		Configuration::updateValue('EPH_COMPANY_ID', $this->id);
		return true;
	}

	/**
	 * @param bool $nullValues
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function update($nullValues = false) {

		// Empty related caches

		if (isset(static::$_idCountries[$this->id])) {
			unset(static::$_idCountries[$this->id]);
		}

		if (isset(static::$_idZones[$this->id])) {
			unset(static::$_idZones[$this->id]);
		}

		return parent::update($nullValues);
	}

	/**
	 * @see     ObjectModel::delete()
	 *
	 * @since 2.1.0.0
	 *
	 * @return bool
	 * @throws PhenyxShopException
	 */
	public function delete() {

		return $this->update();

	}

}
