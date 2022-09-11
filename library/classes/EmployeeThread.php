<?php
use Defuse\Crypto\Crypto;
use \Curl\Curl;
/**
 * Class CustomerThreadCore
 *
 * @since 1.9.1.0
 */
class EmployeeThreadCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int $id_employee */
    public $subject;
    public $id_license;
    public $id_employee;
    public $message;
    public $status;
    public $thread_priority;
    public $private;
    public $date_add;
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'employee_thread',
        'primary' => 'id_employee_thread',
        'fields'  => [
            'subject'            => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128],
            'id_license'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_employee'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'message'            => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 16777216],
            'status'             => ['type' => self::TYPE_STRING],
            'thread_priority'   =>  ['type' => self::TYPE_STRING],
            'private'            => ['type' => self::TYPE_BOOL],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    
    public function __construct($id = null, $idLang = null, $idShop = null) {

        parent::__construct($id, null, $idShop);
        $this->current_license = Configuration::get('EPH_LICENSE_ID');
        
    }
    
    public function add($autoDate = true, $nullValues = false) {
        
        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('EPH_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'openTicket',
            'object' => $this
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        
		return $curl->response;
    }
    
    
    public static function getEmployeeMessages() {

        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('EPH_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'getEmployeeMessages'
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		$response = $curl->response;
		
		$response = Tools::jsonDecode(Tools::jsonEncode($response), true);
		return $response;
    }
    
    public static function getEmployeeThreadDetails($id_employee_thread) {

        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('EPH_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'getEmployeeThreadDetails',
            'id_employee_thread' => $id_employee_thread
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		$response = $curl->response;
		
		$response = Tools::jsonDecode(Tools::jsonEncode($response), true);
		return $response;
    }

    public static function getEmployeeNotification($idLastElement) {

        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('EPH_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'getEmployeeNotification',
            'idLastElement' => $idLastElement
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		$response = $curl->response;
		
		$response = Tools::jsonDecode(Tools::jsonEncode($response), true);
		return $response;
    }
    
    public static function getMaxMessageId() {

        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('EPH_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'getMaxMessageId',
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		$response = $curl->response;
		
		return $response;
    }
    

    

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getTotalEmployeeThreads($where = null) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('employee_thread')
                ->where(($where ?: '1') . ' ' . Shop::addSqlRestriction())
        );
    }

    /**
     * @param int $idCustomerThread
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getMessageCustomerThreads($idCustomerThread) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('ct.*, cm.*')
                ->from('employee_thread', 'ct')
                ->leftJoin('employee_message', 'cm', 'ct.`id_employee_thread` = cm.`id_employee_thread`')
                ->leftJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee`')
                ->leftJoin('customer', 'c', '(IFNULL(ct.`id_customer`, ct.`email`) = IFNULL(c.`id_customer`, c.`email`))')
                ->where('ct.`id_employee_thread` = ' . (int) $idCustomerThread)
                ->orderBy('cm.`date_add` ASC')
        );
    }

    /**
     * @param int $idCustomerThread
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNextThread($idCustomerThread) {

        $context = Context::getContext();

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_employee_thread`')
                ->from('employee_thread', 'ct')
                ->where('ct.status = "open"')
                ->where('ct.`date_upd` = (SELECT date_add FROM ' . _DB_PREFIX_ . 'employee_message WHERE (id_employee IS NULL OR id_employee = 0) AND id_employee_thread = ' . (int) $idCustomerThread . ' ORDER BY date_add DESC LIMIT 1)')
                ->where($context->cookie->{'employee_threadFilter_cl!id_contact'}
                    ? 'ct.`id_contact` = ' . (int) $context->cookie->{'employee_threadFilter_cl!id_contact'}
                    : '')
                ->where($context->cookie->{'employee_threadFilter_l!id_lang'}
                    ? 'ct.`id_lang` = ' . (int) $context->cookie->{'employee_threadFilter_l!id_lang'}
                    : '')
                ->orderBy('ct.`date_upd` ASC')
        );
    }

   
}
