<?php
use Defuse\Crypto\Crypto;
use \Curl\Curl;
/**
 * Class EmployeeMessageCore
 *
 * @since 1.8.1.0
 */
class EmployeeMessageCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int $id_customer_thread */
    public $subject;
    public $id_license;
    public $id_employee_thread;
    /** @var int $id_employee */
    public $id_employee;
    /** @var string $message */
    public $message;
    
    public $date_add;
    /** @var string $date_upd*/
    public $date_upd;
    /** @var bool $read */
    public $read;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'employee_message',
        'primary' => 'id_employee_message',
        'fields'  => [
            'subject'  => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128],
            'id_license'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_employee'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee_thread' => ['type' => self::TYPE_INT],
            'message'            => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 16777216],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'read'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
    
    public function add($autoDate = true, $nullValues = false) {
        
        $url = 'https://ephenyx.io/ticket';
		$string = Configuration::get('_EPHENYX_LICENSE_KEY_').'/'.Configuration::get('PS_SHOP_DOMAIN');
		$crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);
		
		$data_array = [
			'crypto_key' => $crypto_key,
            'action' => 'addAnswer',
            'object' => $this
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        
		return $curl->response;
    }

   
    /**
     * @param int  $idOrder
     * @param bool $private
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.8.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMessagesByOrderId($idOrder, $private = true) {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cm.*')
                ->select('c.`firstname` AS `cfirstname`')
                ->select('c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`')
                ->select('e.`lastname` AS `elastname`')
                ->select('(COUNT(cm.id_customer_message) = 0 AND ct.id_customer != 0) AS is_new_for_me')
                ->from('customer_message', 'cm')
                ->leftJoin('customer_thread', 'ct', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
                ->leftJoin('customer', 'c', 'ct.`id_customer` = c.`id_customer`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee`')
                ->where('ct.`id_order` = ' . (int) $idOrder)
                ->where($private ? 'cm.`private` = 0' : '')
                ->groupBy('cm.`id_customer_message`')
                ->orderBy('cm.`date_add` DESC')
        );
    }

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @since   1.8.1.0
     * @version 1.8.1.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function getTotalCustomerMessages($where = null) {

        if (is_null($where)) {
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where('1 ' . Shop::addSqlRestriction())
            );
        } else {
            return (int) Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message', 'cm')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where($where . Shop::addSqlRestriction())
            );
        }

    }

    
}
