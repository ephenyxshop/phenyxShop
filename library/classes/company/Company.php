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
	public $id_theme;
	public $active;
	public $deleted = 0;
	protected static $_idZones = [];
	protected static $_idCountries = [];
	protected $_includeContainer = false;
	// @codingStandardsIgnoreEnd

	public $next_accounting_start;
	public $next_accounting_end;
	public $saisie_end;

	public $rcs;

	const CONTEXT_COMPANY = 1;    
    
    public $theme_name;
    
    public $theme_directory;    
    
    public $physical_uri;

    public $virtual_uri;

    public $domain;

    public $domain_ssl;
    
    protected static $companies;

	protected static $context_id_company;
    
    protected static $context;

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
			'company_url'             => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255],
			'activity_number'         => ['type' => self::TYPE_STRING, 'size' => 64],
			'company_email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
			'administratif_email'     => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
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
			'working_plan'            => ['type' => self::TYPE_STRING],
			'id_theme'                => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
			'active'                  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'deleted'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
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
            $this->setUrl();
			$this->country = Country::getNameById($idLang ? $idLang : Configuration::get('EPH_LANG_DEFAULT'), $this->id_country_registration);
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
    
    public function setUrl() {
        $cacheId = 'Company::setUrl_'.(int) $this->id;
        if (!Cache::isStored($cacheId)) {
            $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('cu.physical_uri, cu.virtual_uri, cu.domain, cu.domain_ssl, t.id_theme, t.name, t.directory')
                    ->from('company', 'c')
                    ->leftJoin('company_url', 'cu', 'c.`id_company` = cu.`id_company`')
                    ->leftJoin('theme', 't', 't.`id_theme` = c.`id_theme`')
                    ->where('c.`id_company` = '.(int) $this->id)
                    ->where('c.`active` = 1')
                    ->where('c.`deleted` = 0')
                    ->where('cu.`main` = 1')
            );
            Cache::store($cacheId, $row);
        } else {
            $row = Cache::retrieve($cacheId);
        }
        if (!$row) {
            return false;
        }

        $this->id_theme = $row['id_theme'];
        $this->theme_name = $row['name'];
        $this->theme_directory = $row['directory'];
        $this->physical_uri = $row['physical_uri'];
        $this->virtual_uri = $row['virtual_uri'];
		$this->domain_ssl = $row['domain_ssl'];

        return true;
    }

	public static function initialize() {

		if (!($idCompany = Tools::getValue('id_company')) || defined('_EPH_ROOT_DIR_')) {
			$foundUri = '';
			$isMainUri = false;
			$host = Tools::getHttpHost();
			$requestUri = rawurldecode($_SERVER['REQUEST_URI']);

			$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('c.`id_company`, CONCAT(cu.`physical_uri`, cu.`virtual_uri`) AS `uri`, cu.`domain`, cu.`main`')
					->from('company_url', 'cu')
					->leftJoin('company', 'c', 'c.`id_company` = cu.`id_company`')
					->where('cu.domain = \'' . pSQL($host) . '\' OR cu.domain_ssl = \'' . pSQL($host) . '\'')
					->where('c.`active` = 1')
					->where('c.`deleted` = 0')
					->orderBy('LENGTH(CONCAT(cu.`physical_uri`, cu.`virtual_uri`)) DESC')
			);

			$through = false;

			foreach ($result as $row) {
				// An URL matching current shop was found

				if (preg_match('#^' . preg_quote($row['uri'], '#') . '#i', $requestUri)) {
					$through = true;
					$idCompany = $row['id_company'];
					$foundUri = $row['uri'];

					if ($row['main']) {
						$isMainUri = true;
					}

					break;
				}

			}

			// If an URL was found but is not the main URL, redirect to main URL

			if ($through && $idCompany && !$isMainUri) {

				foreach ($result as $row) {

					if ($row['id_company'] == $idCompany && $row['main']) {
						$requestUri = substr($requestUri, strlen($foundUri));
						$url = str_replace('//', '/', $row['domain'] . $row['uri'] . $requestUri);
						$redirectType = Configuration::get('EPH_CANONICAL_REDIRECT');
						$redirectCode = ($redirectType == 1 ? '302' : '301');
						$redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
						header('HTTP/1.0 ' . $redirectCode . ' ' . $redirectHeader);
						header('Cache-Control: no-cache');
						header('Location: ' . Tools::getShopProtocol() . $url);
						exit;
					}

				}

			}

		}

		$httpHost = Tools::getHttpHost();

		if ((!$idCompany && defined('_EPH_ROOT_DIR_')) || Tools::isPHPCLI()) {
			// If in admin, we can access to the shop without right URL

			if ((!$idCompany && Tools::isPHPCLI()) || defined('_EPH_ROOT_DIR_')) {
				$idCompany = (int) Configuration::get('EPH_COMPANY_ID');
			}

			$company = new Company((int) $idCompany);

			if (!Validate::isLoadedObject($company)) {
				$company = new Company((int) Configuration::get('EPH_COMPANY_ID'));
			}

			$company->virtual_uri = '';

			if (!isset($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_HOST'])) {
				$_SERVER['HTTP_HOST'] = $company->domain;
			}

			if (!isset($_SERVER['SERVER_NAME']) || empty($_SERVER['SERVER_NAME'])) {
				$_SERVER['SERVER_NAME'] = $company->domain;
			}

			if (!isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR'])) {
				$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			}

		} else {
			$company = new Company($idCompany);

			if (!Validate::isLoadedObject($company) || !$company->active) {
				$defaultCompany = new Company(Configuration::get('EPH_COMPANY_ID'));


				if (!Validate::isLoadedObject($defaultShop)) {
					throw new PhenyxShopException('Shop not found');
				}

				$params = $_GET;
				unset($params['id_company']);
				$url = $defaultShop->domain;

				if (!Configuration::get('EPH_REWRITING_SETTINGS')) {
					$url .= $defaultShop->getBaseURI() . 'index.php?' . http_build_query($params);
				} else {
					

					if (strpos($url, 'www.') === 0 && 'www.' . $_SERVER['HTTP_HOST'] === $url || $_SERVER['HTTP_HOST'] === 'www.' . $url) {
						$url .= $_SERVER['REQUEST_URI'];
					} else {
						$url .= $defaultShop->getBaseURI();
					}

					if (count($params)) {
						$url .= '?' . http_build_query($params);
					}

				}

				$redirectType = Configuration::get('EPH_CANONICAL_REDIRECT');
				$redirectCode = ($redirectType == 1 ? '302' : '301');
				$redirectHeader = ($redirectType == 1 ? 'Found' : 'Moved Permanently');
				header('HTTP/1.0 ' . $redirectCode . ' ' . $redirectHeader);
				header('Location: ' . Tools::getShopProtocol() . $url);
				exit;
			} else if (defined('_EPH_ROOT_DIR_') && empty($company->physical_uri)) {
				$shopDefault = new Company((int) Configuration::get('EPH_COMPANY_ID'));
				$company->physical_uri = $shopDefault->physical_uri;
				$company->virtual_uri = $shopDefault->virtual_uri;
			}

		}

		static::$context_id_company = $company->id;
		static::$context = static::CONTEXT_COMPANY;

		return $company;
	}

	public function getBaseURI() {

		return $this->physical_uri . $this->virtual_uri;
	}
    
    public function getBaseURL($autoSecureMode = false, $addBaseUri = true) {
        
        $url = [];
        $url['protocol'] = 'https://';
		$url['domain'] = $autoSecureMode && Tools::usingSecureMode() ? $this->domain_ssl : $this->domain;

        if ($addBaseUri) {
            $url['base_uri'] = $this->getBaseURI();
        }

        return implode('', $url);
    }
    
    public function getCategory()  {
        
        return Configuration::get('EPH_ROOT_CATEGORY');
    }

	public function formatRcs() {

		if (!empty($this->siret)) {
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
        $langs = Language::getLanguages(false, $this->id, true);
        
		Configuration::updateValue('EPH_COMPANY_ID', $this->id);
        Configuration::updateValue('EPH_ROUTE_product_rule', array_map(function() {return '{category:/}{id}-{rewrite}';}, $langs));
        Configuration::updateValue('EPH_ROUTE_category_rule', array_map(function() {return '{id}-{rewrite}';}, $langs));
        Configuration::updateValue('EPH_ROUTE_supplier_rule', array_map(function() {return 'supplier/{id}-{rewrite}';}, $langs));
        Configuration::updateValue('EPH_ROUTE_manufacturer_rule', array_map(function() {return 'brand/{id}-{rewrite}';}, $langs));
        Configuration::updateValue('EPH_ROUTE_cms_rule', array_map(function() {return 'content/{id}-{rewrite}';}, $langs));
        Configuration::updateValue('EPH_ROUTE_cms_category_rule', array_map(function() {return 'content/category/{id}-{rewrite}';}, $langs));
        
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
