<?php

/**
 * Class CustomerMessageCore
 *
 * @since 1.8.1.0
 */
class CustomerMessageCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int $id_customer_thread */
    public $id_customer_thread;
    /** @var int $id_employee */
    public $id_employee;
    /** @var string $message */
    public $message;
    /** @var string $file_name */
    public $file_name;
    /** @var string $ip_address */
    public $ip_address;
    /** @var string $user_agent */
    public $user_agent;
    /** @var int $private */
    public $private;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd*/
    public $date_upd;
    /** @var bool $read */
    public $read;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_message',
        'primary' => 'id_customer_message',
        'fields'  => [
            'id_employee'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer_thread' => ['type' => self::TYPE_INT],
            'ip_address'         => ['type' => self::TYPE_STRING, 'validate' => 'isIp2Long', 'size' => 15],
            'message'            => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 16777216],
            'file_name'          => ['type' => self::TYPE_STRING],
            'user_agent'         => ['type' => self::TYPE_STRING],
            'private'            => ['type' => self::TYPE_INT],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'read'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_employee'        => [
                'xlink_resource' => 'employees',
            ],
            'id_customer_thread' => [
                'xlink_resource' => 'customer_threads',
            ],
        ],
    ];

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

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
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
            return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where('1 ')
            );
        } else {
            return (int) Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message', 'cm')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where($where )
            );
        }

    }

    /**
     * @return bool
     *
     * @since   1.8.1.0
     * @version 1.8.1.0 Initial version
     */
    public function delete() {

        if (!empty($this->file_name)) {
            @unlink(_EPH_UPLOAD_DIR_ . $this->file_name);
        }

        return parent::delete();
    }

}
