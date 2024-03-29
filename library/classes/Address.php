<?php

/**
 * Class AddressCore
 *
 * @since 1.9.1.0
 */
class AddressCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int Customer id which address belongs to */
    public $id_customer = null;	

	
    /** @var int Manufacturer id which address belongs to */
    public $id_manufacturer = null;
    /** @var int Supplier id which address belongs to */
    public $id_supplier = null;
    /**
     * @since 1.5.0
     * @var int Warehouse id which address belongs to
     */
    public $id_warehouse = null;
    /** @var int Country id */
    public $id_country;
    /** @var int State id */
    public $id_state;
    /** @var string Country name */
    public $country;
    /** @var string Alias (eg. Home, Work...) */
    public $alias;
    /** @var string Company (optional) */
    public $company;
    /** @var string Lastname */
    public $lastname;
    /** @var string Firstname */
    public $firstname;
    /** @var string Address first line */
    public $address1;
    /** @var string Address second line (optional) */
    public $address2;
    /** @var string Postal code */
    public $postcode;
    /** @var string City */
    public $city;
    /** @var string Any other useful information */
    public $other;
    /** @var string Phone number */
    public $phone;
    /** @var string Mobile phone number */
    public $phone_mobile;
    /** @var string VAT number */
    public $vat_number;
    /** @var string DNI number */
    public $dni;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool True if address has been deleted (staying in database as deleted) */
    public $deleted = 0;
    protected static $_idZones = [];
    protected static $_idCountries = [];
    protected $_includeContainer = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'address',
        'primary' => 'id_address',
        'fields'  => [
            'id_customer'     => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],			
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_supplier'     => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_warehouse'    => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'id_country'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_state'        => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'alias'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'company'         => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'lastname'        => ['type' => self::TYPE_STRING, 'validate' => 'isName',  'size' => 32],
            'firstname'       => ['type' => self::TYPE_STRING, 'validate' => 'isName',  'size' => 32],
            'vat_number'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'address1'        => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
            'address2'        => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
            'postcode'        => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12],
            'city'            => ['type' => self::TYPE_STRING,  'required' => true, 'size' => 64],
            'other'           => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 300],
            'phone'           => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
            'phone_mobile'    => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32],
            'dni'             => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16],
            'deleted'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
            'date_add'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'addresses',
        'fields'          => [
            'id_customer'     => ['xlink_resource' => 'customers'],
            'id_manufacturer' => ['xlink_resource' => 'manufacturers'],
            'id_supplier'     => ['xlink_resource' => 'suppliers'],
            'id_warehouse'    => ['xlink_resource' => 'warehouse'],
            'id_country'      => ['xlink_resource' => 'countries'],
            'id_state'        => ['xlink_resource' => 'states'],
        ],
    ];

    /**
     * Build an address
     *
     * @param int      $idAddress Existing address id in order to load object (optional)
     * @param int|null $idLang
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function __construct($idAddress = null, $idLang = null) {

        parent::__construct($idAddress);

        /* Get and cache address country name */

        if ($this->id) {
            $this->country = Country::getNameById($idLang ? $idLang : Configuration::get('EPH_LANG_DEFAULT'), $this->id_country);
        }

    }

    /**
     * @see     PhenyxObjectModel::add()
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
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

        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function update($nullValues = false) {

        // Empty related caches

        if (isset(static::$_idCountries[$this->id])) {
            unset(static::$_idCountries[$this->id]);
        }

        if (isset(static::$_idZones[$this->id])) {
            unset(static::$_idZones[$this->id]);
        }

        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        return parent::update($nullValues);
    }

    /**
     * @see     PhenyxObjectModel::delete()
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @return bool
     * @throws PhenyxShopException
     */
    public function delete() {

        if (Validate::isUnsignedId($this->id_customer)) {
            Customer::resetAddressCache($this->id_customer, $this->id);
        }

        if (!$this->isUsed()) {
            return parent::delete();
        } else {
            $this->deleted = true;

            return $this->update();
        }

    }

    /**
     * Returns fields required for an address in an array hash
     *
     * @return array hash values.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getFieldsValidate() {

        $tmpAddr = new Address();
        $out = $tmpAddr->fieldsValidate;

        unset($tmpAddr);

        return $out;
    }

    /**
     * @see     PhenyxObjectModel::validateController()
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @version 1.0.6 Use VatNumber::validateNumber(), if present.
     *
     * @param bool $htmlEntities
     *
     * @return array
     * @throws PhenyxShopException
     */
    public function validateController($htmlEntities = true) {

        $errors = parent::validateController($htmlEntities);

        if (Module::isInstalled('vatnumber')
            && Module::isEnabled('vatnumber')
            && file_exists(_EPH_MODULE_DIR_ . 'vatnumber/vatnumber.php')) {
            include_once _EPH_MODULE_DIR_ . 'vatnumber/vatnumber.php';

            if (method_exists('VatNumber', 'validateNumber')) {
                /*
                                     * Even better would be to execute such stuff as a hook, but
                                     * the current hook mechanism is designed with front office
                                     * display stuff in mind, so not really suitable here. A better
                                     * hook mechanism would allow to give arbitrary parameters, not
                                     * just arrays. And it would return results unchanged, not
                                     * casted to a string and results of all hooks concatenated.
                                     * --Traumflug, 2018-07-12
                */
                $result = VatNumber::validateNumber($this);

                if (is_string($result)) {
                    $errors[] = $result;
                }

            } else {
                // Retrocompatibility for module version < 2.1.0 (07/2018).

                if (Configuration::get('VATNUMBER_MANAGEMENT')
                    && Configuration::get('VATNUMBER_CHECKING')) {
                    $errors = array_merge($errors, VatNumber::WebServiceCheck($this->vat_number));
                }

            }

        }

        return $errors;
    }

    /**
     * Get zone id for a given address
     *
     * @param int $idAddress Address id for which we want to get zone id
     *
     * @return int Zone id
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getZoneById($idAddress) {

        if (!isset($idAddress) || empty($idAddress)) {
            return false;
        }

        if (isset(static::$_idZones[$idAddress])) {
            return static::$_idZones[$idAddress];
        }

        $idZone = Hook::exec('actionGetIDZoneByAddressID', ['id_address' => $idAddress]);

        if (is_numeric($idZone)) {
            static::$_idZones[$idAddress] = (int) $idZone;

            return static::$_idZones[$idAddress];
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('s.`id_zone` AS `id_zone_state`, c.`id_zone`')
                ->from('address', 'a')
                ->leftJoin('country', 'c', 'c.`id_country` = a.`id_country`')
                ->leftJoin('state', 's', 's.`id_state` = a.`id_state` AND c.`contains_states` = 1')
                ->where('a.`id_address` = ' . (int) $idAddress)
        );

        static::$_idZones[$idAddress] = (int) ((int) $result['id_zone_state'] ? $result['id_zone_state'] : $result['id_zone']);

        return static::$_idZones[$idAddress];
    }

    /**
     * Check if country is active for a given address
     *
     * @param int $idAddress Address id for which we want to get country status
     *
     * @return int Country status
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isCountryActiveById($idAddress) {

        if (!isset($idAddress) || empty($idAddress)) {
            return false;
        }

        $cacheId = 'Address::isCountryActiveById_' . (int) $idAddress;

        if (!Cache::isStored($cacheId)) {
            $result = (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getvalue(
                (new DbQuery())
                    ->select('c.`active`')
                    ->from('address', 'a')
                    ->leftJoin('country', 'c', 'c.`id_country` = a.`id_country`')
                    ->where('a.`id_address` = ' . (int) $idAddress)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Check if the address is deleted in the database
     *
     * @param int $idAddress
     *
     * @return bool
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     *
     * @since 1.0.4
     */
    public static function isDeleted($idAddress) {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('a.`deleted`')
                ->from(bqSQL(Address::$definition['table']), 'a')
                ->where('`id_address` = ' . (int) $idAddress)
        );

        if (!is_array($row) || !isset($row['deleted'])) {
            return true;
        }

        return (bool) $row['deleted'];
    }

    /**
     * Check if address is used (at least one order placed)
     *
     * @return int Order count for this address
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function isUsed() {

        $result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('orders')
                ->where('`id_address_delivery` = ' . (int) $this->id . ' OR `id_address_invoice` = ' . (int) $this->id)
        );

        return $result > 0 ? (int) $result : false;
    }

    /**
     * @param $idAddress
     *
     * @return array|bool|mixed|null|object
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCountryAndState($idAddress) {

        if (isset(static::$_idCountries[$idAddress])) {
            return static::$_idCountries[$idAddress];
        }

        if ($idAddress) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`id_country`, `id_state`, `vat_number`, `postcode`')
                    ->from('address')
                    ->where('`id_address` = ' . (int) $idAddress)
            );
        } else {
            $result = false;
        }

        static::$_idCountries[$idAddress] = $result;

        return $result;
    }

    /**
     * Specify if an address is already in base
     *
     * @param int $idAddress Address id
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function addressExists($idAddress) {

        $key = 'address_exists_' . (int) $idAddress;

        if (!Cache::isStored($key)) {
            $idAddress = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('a.`id_address`')
                    ->from('address', 'a')
                    ->where('a.`id_address` = ' . (int) $idAddress)
            );
            Cache::store($key, (bool) $idAddress);

            return (bool) $idAddress;
        }

        return Cache::retrieve($key);
    }

    /**
     * @param int  $idCustomer
     * @param bool $active
     *
     * @return bool|int|null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getFirstCustomerAddressId($idCustomer, $active = true) {

        if (!$idCustomer) {
            return false;
        }

        $cacheId = 'Address::getFirstCustomerAddressId_' . (int) $idCustomer . '-' . (bool) $active;

        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_address`')
                    ->from('address')
                    ->where('`id_customer` = ' . (int) $idCustomer)
                    ->where('`deleted` = 0')
                    ->where($active ? '`active` = 1' : '')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }
	
	public static function getFirstAgentAddressId($idAgent, $active = true) {

        if (!$idAgent) {
            return false;
        }

        $cacheId = 'Address::getFirstAgentAddressId_' . (int) $idAgent . '-' . (bool) $active;

        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_address`')
                    ->from('address')
                    ->where('`id_agent` = ' . (int) $idAgent)
                    ->where('`deleted` = 0')
                    ->where($active ? '`active` = 1' : '')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }
	
	public static function getFirstStudentAddressId($idStudent, $active = true) {

        if (!$idStudent) {
            return false;
        }

        $cacheId = 'Address::getFirstStudentAddressId_' . (int) $idStudent . '-' . (bool) $active;

        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_address`')
                    ->from('address')
                    ->where('`id_student` = ' . (int) $idStudent)
                    ->where('`deleted` = 0')
                    ->where($active ? '`active` = 1' : '')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Initiliaze an address corresponding to the specified id address or if empty to the
     * default shop configuration
     *
     * @param int  $idAddress
     * @param bool $withGeoLocation
     *
     * @return Address address
     *
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function initialize($idAddress = null, $withGeoLocation = false) {

        $context = Context::getContext();
        $exists = (int) $idAddress && (bool) Address::addressExists($idAddress);

        if ($exists) {
            $contextHash = (int) $idAddress;
        } else if ($withGeoLocation && isset($context->customer->geoloc_id_country)) {
            $contextHash = md5(
                (int) $context->customer->geoloc_id_country . '-' . (int) $context->customer->id_state . '-' . $context->customer->postcode
            );
        } else {
            $contextHash = md5((int) $context->country->id);
        }

        $cacheId = 'Address::initialize_' . $contextHash;

        if (!Cache::isStored($cacheId)) {
            // if an id_address has been specified retrieve the address

            if ($exists) {
                $address = new Address((int) $idAddress);

                if (!Validate::isLoadedObject($address)) {
                    throw new PhenyxShopException('Invalid address #' . (int) $idAddress);
                }

            } else if ($withGeoLocation && isset($context->customer->geoloc_id_country)) {
                $address = new Address();
                $address->id_country = (int) $context->customer->geoloc_id_country;
                $address->id_state = (int) $context->customer->id_state;
                $address->postcode = $context->customer->postcode;
            } else {
                // set the default address
                $address = new Address();
                $address->id_country = (int) $context->country->id;
                $address->id_state = 0;
                $address->postcode = 0;
            }

            Cache::store($cacheId, $address);

            return $address;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Returns id_address for a given id_supplier
     *
     * @param int $idSupplier
     *
     * @return int $id_address
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getAddressIdBySupplierId($idSupplier) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_address')
                ->from('address')
                ->where('id_supplier = ' . (int) $idSupplier)
                ->where('deleted = 0')
                ->where('id_customer = 0')
                ->where('id_manufacturer = 0')
                ->where('id_warehouse = 0')
        );
    }

    /**
     * @param string $alias
     * @param int    $idAddress
     * @param int    $idCustomer
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function aliasExist($alias, $idAddress, $idCustomer) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('count(*)')
                ->from('address')
                ->where('alias = \'' . pSQL($alias) . '\'')
                ->where('id_address != ' . (int) $idAddress)
                ->where('id_customer = ' . (int) $idCustomer)
                ->where('deleted = 0')
        );
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     */
    public function getFieldsRequiredDB() {

        $this->cacheFieldsRequiredDatabase(false);

        if (isset(static::$fieldsRequiredDatabase['Address'])) {
            return static::$fieldsRequiredDatabase['Address'];
        }

        return [];
    }

    public function getAddresRequest($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('a.`id_country`, a.`company`, a.`address1`, a.`postcode`, a.`city`, cl.`name` AS country, s.name AS state, s.iso_code AS state_iso')
                ->from('address', 'a')
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . $idLang)
                ->leftJoin('state', 's', 's.`id_state` = a.`id_state`')
                ->where('a.`id_address` = ' . (int) $this->id)
        );
    }

    public static function getBAddressesByCustomerId($idCustomer) {

        $idLang = Context::getContext()->language->id;
        $collection = new PhenyxShopCollection('Address');
        $collection->where('id_customer', '=', (int) $idCustomer);

        foreach ($collection as $address) {
            $address->country = Country::getNameById($idLang, $address->id_country);
        }

        return $collection;
    }
	
	public static function getBAddressesByStudentId($idStudent) {

        $idLang = Context::getContext()->language->id;
        $collection = new PhenyxShopCollection('Address');
        $collection->where('id_student', '=', (int) $idStudent);

        foreach ($collection as $address) {
            $address->country = Country::getNameById($idLang, $address->id_country);
        }

        return $collection;
    }

    public static function getAutoCompleteCity($query) {

        $results = [];

        $sql = 'SELECT `post_code`, `city`
        FROM `' . _DB_PREFIX_ . 'post_code`
        WHERE (`post_code` LIKE \'' . pSQL($query) . '%\' )' .
            ' ORDER BY city';

        $items = Db::getInstance()->executeS($sql);

        if ($items) {

            foreach ($items as $item) {
                $education = [
                    'address_zipcode' => $item['post_code'],
                    'address_city'    => $item['city'],
                ];
                array_push($results, $education);
            }

            $results = array_values($results);
        }

        return $results;

    }

}
