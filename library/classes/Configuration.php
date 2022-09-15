<?php

/**
 * Class ConfigurationCore
 *
 * @since 1.9.1.0
 */
class ConfigurationCore extends PhenyxObjectModel {

    // Default configuration consts
    // @since 1.0.1
    const SEARCH_INDEXATION = 'EPH_SEARCH_INDEXATION';
    const ONE_PHONE_AT_LEAST = 'EPH_ONE_PHONE_AT_LEAST';
    const GROUP_FEATURE_ACTIVE = 'EPH_GROUP_FEATURE_ACTIVE';
    const CARRIER_DEFAULT = 'EPH_CARRIER_DEFAULT';
    const CURRENCY_DEFAULT = 'EPH_CURRENCY_DEFAULT';
    const COUNTRY_DEFAULT = 'EPH_COUNTRY_DEFAULT';
    const REWRITING_SETTINGS = 'EPH_REWRITING_SETTINGS';
    const ORDER_OUT_OF_STOCK = 'EPH_ORDER_OUT_OF_STOCK';
    const LAST_QTIES = 'EPH_LAST_QTIES';
    const CART_REDIRECT = 'EPH_CART_REDIRECT';
    const CONDITIONS = 'EPH_CONDITIONS';
    const RECYCLABLE_PACK = 'EPH_RECYCLABLE_PACK';
    const GIFT_WRAPPING = 'EPH_GIFT_WRAPPING';
    const GIFT_WRAPPING_PRICE = 'EPH_GIFT_WRAPPING_PRICE';
    const STOCK_MANAGEMENT = 'EPH_STOCK_MANAGEMENT';
    const NAVIGATION_PIPE = 'EPH_NAVIGATION_PIPE';
    const PRODUCTS_PER_PAGE = 'EPH_PRODUCTS_PER_PAGE';
    const PURCHASE_MINIMUM = 'EPH_PURCHASE_MINIMUM';
    const PRODUCTS_ORDER_WAY = 'EPH_PRODUCTS_ORDER_WAY';
    const PRODUCTS_ORDER_BY = 'EPH_PRODUCTS_ORDER_BY';
    const SHIPPING_HANDLING = 'EPH_SHIPPING_HANDLING';
    const SHIPPING_FREE_PRICE = 'EPH_SHIPPING_FREE_PRICE';
    const SHIPPING_FREE_WEIGHT = 'EPH_SHIPPING_FREE_WEIGHT';
    const SHIPPING_METHOD = 'EPH_SHIPPING_METHOD';
    const TAX = 'EPH_TAX';
    const SHOP_ENABLE = 'EPH_SHOP_ENABLE';
    const NB_DAYS_NEW_PRODUCT = 'EPH_NB_DAYS_NEW_PRODUCT';
    const SSL_ENABLED = 'EPH_SSL_ENABLED';
    const WEIGHT_UNIT = 'EPH_WEIGHT_UNIT';
    const BLOCK_CART_AJAX = 'EPH_BLOCK_CART_AJAX';
    const ORDER_RETURN = 'EPH_ORDER_RETURN';
    const ORDER_RETURN_NB_DAYS = 'EPH_ORDER_RETURN_NB_DAYS';
    const MAIL_TYPE = 'EPH_MAIL_TYPE';
    const PRODUCT_PICTURE_MAX_SIZE = 'EPH_PRODUCT_PICTURE_MAX_SIZE';
    const PRODUCT_PICTURE_WIDTH = 'EPH_PRODUCT_PICTURE_WIDTH';
    const PRODUCT_PICTURE_HEIGHT = 'EPH_PRODUCT_PICTURE_HEIGHT';
    const INVOICE_PREFIX = 'EPH_INVOICE_PREFIX';
    const INVCE_INVOICE_ADDR_RULES = 'EPH_INVCE_INVOICE_ADDR_RULES';
    const INVCE_DELIVERY_ADDR_RULES = 'EPH_INVCE_DELIVERY_ADDR_RULES';
    const DELIVERY_PREFIX = 'EPH_DELIVERY_PREFIX';
    const DELIVERY_NUMBER = 'EPH_DELIVERY_NUMBER';
    const RETURN_PREFIX = 'EPH_RETURN_PREFIX';
    const INVOICE = 'EPH_INVOICE';
    const PASSWD_TIME_BACK = 'EPH_PASSWD_TIME_BACK';
    const PASSWD_TIME_FRONT = 'EPH_PASSWD_TIME_FRONT';
    const DISP_UNAVAILABLE_ATTR = 'EPH_DISP_UNAVAILABLE_ATTR';
    const SEARCH_MINWORDLEN = 'EPH_SEARCH_MINWORDLEN';
    const SEARCH_BLACKLIST = 'EPH_SEARCH_BLACKLIST';
    const SEARCH_WEIGHT_PNAME = 'EPH_SEARCH_WEIGHT_PNAME';
    const SEARCH_WEIGHT_REF = 'EPH_SEARCH_WEIGHT_REF';
    const SEARCH_WEIGHT_SHORTDESC = 'EPH_SEARCH_WEIGHT_SHORTDESC';
    const SEARCH_WEIGHT_DESC = 'EPH_SEARCH_WEIGHT_DESC';
    const SEARCH_WEIGHT_CNAME = 'EPH_SEARCH_WEIGHT_CNAME';
    const SEARCH_WEIGHT_MNAME = 'EPH_SEARCH_WEIGHT_MNAME';
    const SEARCH_WEIGHT_TAG = 'EPH_SEARCH_WEIGHT_TAG';
    const SEARCH_WEIGHT_ATTRIBUTE = 'EPH_SEARCH_WEIGHT_ATTRIBUTE';
    const SEARCH_WEIGHT_FEATURE = 'EPH_SEARCH_WEIGHT_FEATURE';
    const SEARCH_AJAX = 'EPH_SEARCH_AJAX';
    const TIMEZONE = 'EPH_TIMEZONE';
    const THEME_V11 = 'EPH_THEME_V11';
    const TIN_ACTIVE = 'EPH_TIN_ACTIVE';
    const SHOW_ALL_MODULES = 'EPH_SHOW_ALL_MODULES';
    const BACKUP_ALL = 'EPH_BACKUP_ALL';
    const PRICE_ROUND_MODE = 'EPH_PRICE_ROUND_MODE';
    const CONDITIONS_CMS_ID = 'EPH_CONDITIONS_CMS_ID';
    const TRACKING_DIRECT_TRAFFIC = 'TRACKING_DIRECT_TRAFFIC';
    const META_KEYWORDS = 'EPH_META_KEYWORDS';
    const DISPLAY_JQZOOM = 'EPH_DISPLAY_JQZOOM';
    const VOLUME_UNIT = 'EPH_VOLUME_UNIT';
    const CIPHER_ALGORITHM = 'EPH_CIPHER_ALGORITHM';
    const ATTRIBUTE_CATEGORY_DISPLAY = 'EPH_ATTRIBUTE_CATEGORY_DISPLAY';
    const CUSTOMER_SERVICE_FILE_UPLOAD = 'EPH_CUSTOMER_SERVICE_FILE_UPLOAD';
    const CUSTOMER_SERVICE_SIGNATURE = 'EPH_CUSTOMER_SERVICE_SIGNATURE';

    const BLOCK_BESTSELLERS_DISPLAY = 'EPH_BLOCK_BESTSELLERS_DISPLAY';
    const BLOCK_NEWPRODUCTS_DISPLAY = 'EPH_BLOCK_NEWPRODUCTS_DISPLAY';
    const BLOCK_SPECIALS_DISPLAY = 'EPH_BLOCK_SPECIALS_DISPLAY';
    const STOCK_MVT_REASON_DEFAULT = 'EPH_STOCK_MVT_REASON_DEFAULT';
    const COMPARATOR_MAX_ITEM = 'EPH_COMPARATOR_MAX_ITEM';
    const ORDER_PROCESS_TYPE = 'EPH_ORDER_PROCESS_TYPE';
    const SPECIFIC_PRICE_PRIORITIES = 'EPH_SPECIFIC_PRICE_PRIORITIES';
    const TAX_DISPLAY = 'EPH_TAX_DISPLAY';
    const SMARTY_FORCE_COMPILE = 'EPH_SMARTY_FORCE_COMPILE';
    const DISTANCE_UNIT = 'EPH_DISTANCE_UNIT';
    const STORES_DISPLAY_CMS = 'EPH_STORES_DISPLAY_CMS';
    const STORES_DISPLAY_FOOTER = 'EPH_STORES_DISPLAY_FOOTER';
    const STORES_SIMPLIFIED = 'EPH_STORES_SIMPLIFIED';
    const SHOP_LOGO_WIDTH = 'SHOP_LOGO_WIDTH';
    const SHOP_LOGO_HEIGHT = 'SHOP_LOGO_HEIGHT';
    const EDITORIAL_IMAGE_WIDTH = 'EDITORIAL_IMAGE_WIDTH';
    const EDITORIAL_IMAGE_HEIGHT = 'EDITORIAL_IMAGE_HEIGHT';
    const STATSDATA_CUSTOMER_PAGESVIEWS = 'EPH_STATSDATA_CUSTOMER_PAGESVIEWS';
    const STATSDATA_PAGESVIEWS = 'EPH_STATSDATA_PAGESVIEWS';
    const STATSDATA_PLUGINS = 'EPH_STATSDATA_PLUGINS';
    const GEOLOCATION_ENABLED = 'EPH_GEOLOCATION_ENABLED';
    const ALLOWED_COUNTRIES = 'EPH_ALLOWED_COUNTRIES';
    const GEOLOCATION_BEHAVIOR = 'EPH_GEOLOCATION_BEHAVIOR';
    const LOCALE_LANGUAGE = 'EPH_LOCALE_LANGUAGE';
    const LOCALE_COUNTRY = 'EPH_LOCALE_COUNTRY';
    const ATTACHMENT_MAXIMUM_SIZE = 'EPH_ATTACHMENT_MAXIMUM_SIZE';
    const SMARTY_CACHE = 'EPH_SMARTY_CACHE';
    const DIMENSION_UNIT = 'EPH_DIMENSION_UNIT';
    const GUEST_CHECKOUT_ENABLED = 'EPH_GUEST_CHECKOUT_ENABLED';
    const DISPLAY_SUPPLIERS = 'EPH_DISPLAY_SUPPLIERS';
    const DISPLAY_BEST_SELLERS = 'EPH_DISPLAY_BEST_SELLERS';
    const CATALOG_MODE = 'EPH_CATALOG_MODE';
    const GEOLOCATION_WHITELIST = 'EPH_GEOLOCATION_WHITELIST';
    const LOGS_BY_EMAIL = 'EPH_LOGS_BY_EMAIL';
    const COOKIE_CHECKIP = 'EPH_COOKIE_CHECKIP';
    const STORES_CENTER_LAT = 'EPH_STORES_CENTER_LAT';
    const STORES_CENTER_LONG = 'EPH_STORES_CENTER_LONG';
    const USE_ECOTAX = 'EPH_USE_ECOTAX';
    const CANONICAL_REDIRECT = 'EPH_CANONICAL_REDIRECT';
    const IMG_UPDATE_TIME = 'EPH_IMG_UPDATE_TIME';
    const BACKUP_DROP_TABLE = 'EPH_BACKUP_DROP_TABLE';
    const OS_CHEQUE = 'EPH_OS_CHEQUE';
    const OS_PAYMENT = 'EPH_OS_PAYMENT';
    const OS_PREPARATION = 'EPH_OS_PREPARATION';
    const OS_SHIPPING = 'EPH_OS_SHIPPING';
    const OS_DELIVERED = 'EPH_OS_DELIVERED';
    const OS_CANCELED = 'EPH_OS_CANCELED';
    const OS_REFUND = 'EPH_OS_REFUND';
    const OS_ERROR = 'EPH_OS_ERROR';
    const OS_OUTOFSTOCK = 'EPH_OS_OUTOFSTOCK';
    const OS_BANKWIRE = 'EPH_OS_BANKWIRE';
    const OS_PAYPAL = 'EPH_OS_PAYPAL';
    const OS_WS_PAYMENT = 'EPH_OS_WS_PAYMENT';
    const OS_OUTOFSTOCK_PAID = 'EPH_OS_OUTOFSTOCK_PAID';
    const OS_OUTOFSTOCK_UNPAID = 'EPH_OS_OUTOFSTOCK_UNPAID';
    const OS_COD_VALIDATION = 'EPH_OS_COD_VALIDATION';
    const LEGACY_IMAGES = 'EPH_LEGACY_IMAGES';
    const IMAGE_QUALITY = 'EPH_IMAGE_QUALITY';
    const PNG_QUALITY = 'EPH_PNG_QUALITY';
    const JPEG_QUALITY = 'EPH_JPEG_QUALITY';
    const COOKIE_LIFETIME_FO = 'EPH_COOKIE_LIFETIME_FO';
    const COOKIE_LIFETIME_BO = 'EPH_COOKIE_LIFETIME_BO';
    const RESTRICT_DELIVERED_COUNTRIES = 'EPH_RESTRICT_DELIVERED_COUNTRIES';
    const SHOW_NEW_ORDERS = 'EPH_SHOW_NEW_ORDERS';
    const SHOW_NEW_CUSTOMERS = 'EPH_SHOW_NEW_CUSTOMERS';
    const SHOW_NEW_MESSAGES = 'EPH_SHOW_NEW_MESSAGES';
    const FEATURE_FEATURE_ACTIVE = 'EPH_FEATURE_FEATURE_ACTIVE';
    const COMBINATION_FEATURE_ACTIVE = 'EPH_COMBINATION_FEATURE_ACTIVE';
    const SPECIFIC_PRICE_FEATURE_ACTIVE = 'EPH_SPECIFIC_PRICE_FEATURE_ACTIVE';
    const SCENE_FEATURE_ACTIVE = 'EPH_SCENE_FEATURE_ACTIVE';
    const VIRTUAL_PROD_FEATURE_ACTIVE = 'EPH_VIRTUAL_PROD_FEATURE_ACTIVE';
    const CUSTOMIZATION_FEATURE_ACTIVE = 'EPH_CUSTOMIZATION_FEATURE_ACTIVE';
    const CART_RULE_FEATURE_ACTIVE = 'EPH_CART_RULE_FEATURE_ACTIVE';
    const PACK_FEATURE_ACTIVE = 'EPH_PACK_FEATURE_ACTIVE';
    const ALIAS_FEATURE_ACTIVE = 'EPH_ALIAS_FEATURE_ACTIVE';
    const TAX_ADDRESS_TYPE = 'EPH_TAX_ADDRESS_TYPE';
    const SHOP_DEFAULT = 'EPH_SHOP_DEFAULT';
    const CARRIER_DEFAULT_SORT = 'EPH_CARRIER_DEFAULT_SORT';
    const STOCK_MVT_INC_REASON_DEFAULT = 'EPH_STOCK_MVT_INC_REASON_DEFAULT';
    const STOCK_MVT_DEC_REASON_DEFAULT = 'EPH_STOCK_MVT_DEC_REASON_DEFAULT';
    const ADVANCED_STOCK_MANAGEMENT = 'EPH_ADVANCED_STOCK_MANAGEMENT';
    const ADMINREFRESH_NOTIFICATION = 'EPH_ADMINREFRESH_NOTIFICATION';
    const STOCK_MVT_TRANSFER_TO = 'EPH_STOCK_MVT_TRANSFER_TO';
    const STOCK_MVT_TRANSFER_FROM = 'EPH_STOCK_MVT_TRANSFER_FROM';
    const CARRIER_DEFAULT_ORDER = 'EPH_CARRIER_DEFAULT_ORDER';
    const STOCK_MVT_SUPPLY_ORDER = 'EPH_STOCK_MVT_SUPPLY_ORDER';
    const STOCK_CUSTOMER_ORDER_REASON = 'EPH_STOCK_CUSTOMER_ORDER_REASON';
    const UNIDENTIFIED_GROUP = 'EPH_UNIDENTIFIED_GROUP';
    const GUEST_GROUP = 'EPH_GUEST_GROUP';
    const CUSTOMER_GROUP = 'EPH_CUSTOMER_GROUP';
    const SMARTY_CONSOLE = 'EPH_SMARTY_CONSOLE';
    const INVOICE_MODEL = 'EPH_INVOICE_MODEL';
    const LIMIT_UPLOAD_IMAGE_VALUE = 'EPH_LIMIT_UPLOAD_IMAGE_VALUE';
    const LIMIT_UPLOAD_FILE_VALUE = 'EPH_LIMIT_UPLOAD_FILE_VALUE';
    const TOKEN_ENABLE = 'EPH_TOKEN_ENABLE';
    const STATS_RENDER = 'EPH_STATS_RENDER';
    const STATS_OLD_CONNECT_AUTO_CLEAN = 'EPH_STATS_OLD_CONNECT_AUTO_CLEAN';
    const STATS_GRID_RENDER = 'EPH_STATS_GRID_RENDER';
    const BASE_DISTANCE_UNIT = 'EPH_BASE_DISTANCE_UNIT';
    const SHOP_DOMAIN = 'EPH_SHOP_DOMAIN';
    const SHOP_DOMAIN_SSL = 'EPH_SHOP_DOMAIN_SSL';
    const SHOP_NAME = 'EPH_SHOP_NAME';
    const SHOP_EMAIL = 'EPH_SHOP_EMAIL';
    const MAIL_METHOD = 'EPH_MAIL_METHOD';
    const SHOP_ACTIVITY = 'EPH_SHOP_ACTIVITY';
    const LOGO = 'EPH_LOGO';
    const FAVICON = 'EPH_FAVICON';
    const STORES_ICON = 'EPH_STORES_ICON';
    const ROOT_CATEGORY = 'EPH_ROOT_CATEGORY';
    const HOME_CATEGORY = 'EPH_HOME_CATEGORY';
    const CONFIGURATION_AGREMENT = 'EPH_CONFIGURATION_AGREMENT';
    const MAIL_SERVER = 'EPH_MAIL_SERVER';
    const MAIL_USER = 'EPH_MAIL_USER';
    const MAIL_PASSWD = 'EPH_MAIL_PASSWD';
    const MAIL_SMTP_ENCRYPTION = 'EPH_MAIL_SMTP_ENCRYPTION';
    const MAIL_SMTP_PORT = 'EPH_MAIL_SMTP_PORT';
    const MAIL_COLOR = 'EPH_MAIL_COLOR';
    const PAYMENT_LOGO_CMS_ID = 'EPH_PAYMENT_LOGO_CMS_ID';
    const ALLOW_MOBILE_DEVICE = 'EPH_ALLOW_MOBILE_DEVICE';
    const CUSTOMER_CREATION_EMAIL = 'EPH_CUSTOMER_CREATION_EMAIL';
    const SMARTY_CONSOLE_KEY = 'EPH_SMARTY_CONSOLE_KEY';
    const DASHBOARD_USE_PUSH = 'EPH_DASHBOARD_USE_PUSH';
    const ATTRIBUTE_ANCHOR_SEPARATOR = 'EPH_ATTRIBUTE_ANCHOR_SEPARATOR';
    const DASHBOARD_SIMULATION = 'EPH_DASHBOARD_SIMULATION';
    const QUICK_VIEW = 'EPH_QUICK_VIEW';
    const USE_HTMLPURIFIER = 'EPH_USE_HTMLPURIFIER';
    const SMARTY_CACHING_TYPE = 'EPH_SMARTY_CACHING_TYPE';
    const SMARTY_CLEAR_CACHE = 'EPH_SMARTY_CLEAR_CACHE';
    const DETECT_LANG = 'EPH_DETECT_LANG';
    const DETECT_COUNTRY = 'EPH_DETECT_COUNTRY';
    const ROUND_TYPE = 'EPH_ROUND_TYPE';
    const PRICE_DISPLAY_PRECISION = 'EPH_PRICE_DISPLAY_PRECISION';
    const LOG_EMAILS = 'EPH_LOG_EMAILS';
    const CUSTOMER_NWSL = 'EPH_CUSTOMER_NWSL';
    const CUSTOMER_OPTIN = 'EPH_CUSTOMER_OPTIN';
    const PACK_STOCK_TYPE = 'EPH_PACK_STOCK_TYPE';
    const LOG_MODULE_PERFS_MODULO = 'EPH_LOG_MODULE_PERFS_MODULO';
    const DISALLOW_HISTORY_REORDERING = 'EPH_DISALLOW_HISTORY_REORDERING';
    const DISPLAY_PRODUCT_WEIGHT = 'EPH_DISPLAY_PRODUCT_WEIGHT';
    const PRODUCT_WEIGHT_PRECISION = 'EPH_PRODUCT_WEIGHT_PRECISION';
    const ADVANCED_PAYMENT_API = 'EPH_ADVANCED_PAYMENT_API';
    const PAGE_CACHE_CONTROLLERS = 'EPH_PAGE_CACHE_CONTROLLERS';
    const PAGE_CACHE_IGNOREPARAMS = 'EPH_ADVANCED_PAYMENT_API';
    const ROUTE_CATEGORY_RULE = 'EPH_ROUTE_category_rule';
    const ROUTE_SUPPLIER_RULE = 'EPH_ROUTE_supplier_rule';
    const ROUTE_CMS_RULE = 'EPH_ROUTE_cms_rule';
    const ROUTE_CMS_CATEGORY_RULE = 'EPH_ROUTE_cms_category_rule';
    const ROUTE_EDUCATION_TYPE_RULE = 'EPH_ROUTE_education_type_rule';
    const DISABLE_OVERRIDES = 'EPH_DISABLE_OVERRIDES';
    const DISABLE_NON_NATIVE_MODULE = 'EPH_DISABLE_NON_NATIVE_MODULE';
    const CUSTOMCODE_METAS = 'EPH_CUSTOMCODE_METAS';
    const CUSTOMCODE_CSS = 'EPH_CUSTOMCODE_CSS';
    const CUSTOMCODE_JS = 'EPH_CUSTOMCODE_JS';
    const CUSTOMCODE_ORDERCONF_JS = 'EPH_CUSTOMCODE_ORDERCONF_JS';
    const STORE_REGISTERED = 'EPH_STORE_REGISTERED';
    const EPHENYX_LICENSE_KEY = '_EPHENYX_LICENSE_KEY_';
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'configuration',
        'primary'   => 'id_configuration',
        'multilang' => true,
        'fields'    => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 254],
            'value'         => ['type' => self::TYPE_NOTHING],
            'date_add'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    /** @var array Configuration cache */
    protected static $_cache = [];
    /** @var array Vars types */
    protected static $types = [];
    /** @var string Key */
    public $name;
    /** @var string Value */
    public $value;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    protected $webserviceParameters = [
        'fields' => [
            'value' => [],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @return bool|null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function configurationIsLoaded() {

        return isset(static::$_cache[static::$definition['table']])
        && is_array(static::$_cache[static::$definition['table']])
        && count(static::$_cache[static::$definition['table']]);
    }

    /**
     * WARNING: For testing only. Do NOT rely on this method, it may be removed at any time.
     *
     * @todo    Delegate static calls from Configuration to an instance of a class to be created.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function clearConfigurationCacheForTesting() {

        static::$_cache = [];
    }

    /**
     * @param string   $key
     * @param int|null $idLang
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getGlobalValue($key, $idLang = null) {

        return Configuration::get($key, $idLang);
    }

    /**
     * Get a single configuration value (in one language only)
     *
     * @param string   $key    Key wanted
     * @param int      $idLang Language ID
     * @param int|null $idCompanyGroup
     * @param int|null $idCompany
     *
     * @return string Value
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function get($key, $idLang = null) {

        if (defined('_EPH_DO_NOT_LOAD_CONFIGURATION_') && _EPH_DO_NOT_LOAD_CONFIGURATION_) {
            return false;
        }

        static::validateKey($key);
		

        if (!static::configurationIsLoaded()) {
            Configuration::loadConfiguration();
        }

        $idLang = (int) $idLang;

       
        if (!isset(static::$_cache[static::$definition['table']][$idLang])) {
            $idLang = 0;
        }

        
        if (Configuration::hasKey($key, $idLang) && isset(static::$_cache[static::$definition['table']][$idLang]['global'][$key])) {
			
            return static::$_cache[static::$definition['table']][$idLang]['global'][$key];
        } else {
            $value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`value`')
                    ->from(static::$definition['table'])
                    ->where('`name` LIKE \'' . $key . '\'')
            );
            static::$_cache[static::$definition['table']][$idLang]['global'][$key] = $value;
            return $value;
        }

        return false;
    }

    /**
     * Load all configuration data
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function loadConfiguration() {

        return static::loadConfigurationFromDB(Db::getInstance(_EPH_USE_SQL_SLAVE_));
    }

    /**
     * Load all configuration data, using an existing database connection.
     *
     * @param Db $connection Database connection to be used for data retrieval.
     *
     * @since   1.0.7
     * @version 1.0.7 Initial version
     */
    public static function loadConfigurationFromDB($connection) {

        static::$_cache[static::$definition['table']] = [];

        $rows = $connection->executeS(
            (new DbQuery())
                ->select('c.`name`, cl.`id_lang`, IFNULL(cl.`value`, c.`value`) AS `value`')
                ->from(static::$definition['table'], 'c')
                ->leftJoin(static::$definition['table'] . '_lang', 'cl', 'c.`' . static::$definition['primary'] . '` = cl.`' . static::$definition['primary'] . '`')
        );

        if (!is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $lang = ($row['id_lang']) ? $row['id_lang'] : 0;
            static::$types[$row['name']] = ($lang) ? 'lang' : 'normal';

            if (!isset(static::$_cache[static::$definition['table']][$lang])) {
                static::$_cache[static::$definition['table']][$lang] = [
                    'global' => [],
                ];
            }

            static::$_cache[static::$definition['table']][$lang]['global'][$row['name']] = $row['value'];

        }

    }

    /**
     * Check if key exists in configuration
     *
     * @param string $key
     * @param int    $idLang
     * @param int    $idCompanyGroup
     * @param int    $idCompany
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function hasKey($key, $idLang = null) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_configuration`')
                ->from('configuration')
                ->where('`name` = \''.$key.'\'')
        );
    }

    public static function getInt($key) {

        $resultsArray = [];

        foreach (Language::getIDs() as $idLang) {
            $resultsArray[$idLang] = Configuration::get($key, $idLang);
        }

        return $resultsArray;
    }

    public static function getMultiShopValues($key, $idLang = null) {        

        return Configuration::get($key, $idLang, null);
    }

    public static function getMultiple($keys, $idLang = null) {

        if (!is_array($keys)) {
            throw new PhenyxShopException('keys var is not an array');
        }

        $idLang = (int) $idLang;

       
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = Configuration::get($key, $idLang);
        }

        return $results;
    }

    public static function updateGlobalValue($key, $values, $html = false) {

        return Configuration::updateValue($key, $values, $html, 0, 0);
    }
    
    public static function updateValue($key, $values, $html = false, $script = false) {

        $file = fopen("testupdateValue.txt", "w");

        static::validateKey($key);

        fwrite($file, $key . ' => ' . $values . PHP_EOL);

        

        if (!is_array($values)) {
            $values = [$values];
        }

        if ($html) {

            foreach ($values as &$value) {
                $value = Tools::purifyHTML($value);
            }

            unset($value);
        }

        if(!$script) {
            foreach ($values as &$value) {
                $value = pSQL($value, $html);
            }
        }

        $result = true;

        foreach ($values as $lang => $value) {

            if (Configuration::hasKey($key, $lang)) {
                // If key exists already, update value.
                fwrite($file, 'Configuration has Key' . PHP_EOL);
                fwrite($file, $lang . PHP_EOL);

                if (!$lang) {
                    // Update config not linked to lang
                    $result &= Db::getInstance()->update(
                        static::$definition['table'],
                        [
                            'value'    => $value,
                            'date_upd' => date('Y-m-d H:i:s'),
                        ],
                        '`name` = \'' . $key . '\'',
                        1,
                        true
                    );
                } else {
                    // Update multi lang
                    $sql = 'UPDATE `' . _DB_PREFIX_ . static::$definition['table'] . '_lang` cl
                            SET cl.value = \'' . $value . '\',
                                cl.date_upd = NOW()
                            WHERE cl.id_lang = ' . (int) $lang . '
                                AND cl.`' . static::$definition['primary'] . '` = (
                                    SELECT c.`' . static::$definition['primary'] . '`
                                    FROM `' . _DB_PREFIX_ . static::$definition['table'] . '` c
                                    WHERE c.name = \'' . $key . '\''
                        . ')';
                    $result &= Db::getInstance()->execute($sql);
                }

            } else {
                // If key doesn't exist, create it.
                fwrite($file, 'No Key' . PHP_EOL);

                if (!$configID = Configuration::getIdByName($key)) {
                    $data = [
                        'name'          => $key,
                        'value'         => $lang ? null : $value,
                        'date_add'      => ['type' => 'sql', 'value' => 'NOW()'],
                        'date_upd'      => ['type' => 'sql', 'value' => 'NOW()'],
                    ];
                    $result &= Db::getInstance()->insert(static::$definition['table'], $data, true);
                    $configID = Db::getInstance()->Insert_ID();
                }

                if ($lang) {
                    $result &= Db::getInstance()->insert(
                        static::$definition['table'] . '_lang',
                        [
                            static::$definition['primary'] => $configID,
                            'id_lang'                      => (int) $lang,
                            'value'                        => $value,
                            'date_upd'                     => date('Y-m-d H:i:s'),
                        ]
                    );
                }

            }

        }

        Configuration::set($key, $values);

        return $result;
    }
   
    public static function getIdByName($key) {

        static::validateKey($key);

        

        $sql = 'SELECT `' . static::$definition['primary'] . '`
                FROM `' . _DB_PREFIX_ . static::$definition['table'] . '`
                WHERE name = \'' . $key . '\'';

        return (int) Db::getInstance()->getValue($sql);
    }

    public static function set($key, $values) {

        static::validateKey($key);

        

        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $lang => $value) {

            static::$_cache[static::$definition['table']][$lang]['global'][$key] = $value;

        }

    }

    public static function deleteByName($key) {

        static::validateKey($key);

        $result = Db::getInstance()->execute(
            '
        DELETE FROM `' . _DB_PREFIX_ . static::$definition['table'] . '_lang`
        WHERE `' . static::$definition['primary'] . '` IN (
            SELECT `' . static::$definition['primary'] . '`
            FROM `' . _DB_PREFIX_ . static::$definition['table'] . '`
            WHERE `name` = "' . $key . '"
        )'
        );

        $result2 = Db::getInstance()->delete(static::$definition['table'], '`name` = "' . $key . '"');

        static::$_cache[static::$definition['table']] = null;

        return ($result && $result2);
    }

    public static function deleteFromContext($key) {


        $id = Configuration::getIdByName($key);
        Db::getInstance()->delete(
            static::$definition['table'],
            '`' . static::$definition['primary'] . '` = ' . (int) $id
        );
        Db::getInstance()->delete(
            static::$definition['table'] . '_lang',
            '`' . static::$definition['primary'] . '` = ' . (int) $id
        );

        static::$_cache[static::$definition['table']] = null;
    }

    public static function isLangKey($key) {

        static::validateKey($key);

        return (isset(static::$types[$key]) && static::$types[$key] == 'lang') ? true : false;
    }

    public function getFieldsLang() {

        if (!is_array($this->value)) {
            return true;
        }

        return parent::getFieldsLang();
    }

    protected static function validateKey($key) {

        if (!Validate::isConfigName($key)) {
            $e = new PhenyxShopException(sprintf(
                Tools::displayError('[%s] is not a valid configuration key'),
                Tools::htmlentitiesUTF8($key)
            ));
            die($e->displayMessage());
        }

    }

}
