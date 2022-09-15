<?php

/**
 * Class PhenyxShopLoggerCore
 *
 * @since 1.9.1.0
 */
class PhenyxShopLoggerCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    protected static $is_present = [];
    /** @var int Log id */
    public $id_log;
    /** @var int Log severity */
    public $severity;
    /** @var int Error code */
    public $error_code;
    /** @var string Message */
    public $message;
    /** @var string Object type (eg. Order, Customer...) */
    public $object_type;
    /** @var int Object ID */
    public $object_id;
    /** @var int Object ID */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'log',
        'primary' => 'id_log',
        'fields'  => [
            'severity'    => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'error_code'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'message'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'object_id'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isName'],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * add a log item to the database and send a mail if configured for this $severity
     *
     * @param string $message        the log message
     * @param int    $severity
     * @param int    $errorCode
     * @param string $objectType
     * @param int    $objectId
     * @param bool   $allowDuplicate if set to true, can log several time the same information (not recommended)
     *
     * @param null   $idEmployee
     *
     * @return bool true if succeed
     *
     * @throws EphenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function addLog($message, $severity = 1, $errorCode = null, $objectType = null, $objectId = null, $allowDuplicate = false, $idEmployee = null) {

        $log = new Logger();
        $log->severity = (int) $severity;
        $log->error_code = (int) $errorCode;
        $log->message = pSQL($message);
        $log->date_add = date('Y-m-d H:i:s');
        $log->date_upd = date('Y-m-d H:i:s');

        if ($idEmployee === null && isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee)) {
            $idEmployee = Context::getContext()->employee->id;
        }

        if ($idEmployee !== null) {
            $log->id_employee = (int) $idEmployee;
        }

        if (!empty($objectType) && !empty($objectId)) {
            $log->object_type = pSQL($objectType);
            $log->object_id = (int) $objectId;
        }

        if ($objectType != 'Swift_Message') {
            Logger::sendByMail($log);
        }

        if ($allowDuplicate || !$log->_isPresent()) {
            $res = $log->add();

            if ($res) {
                static::$is_present[$log->getHash()] = isset(static::$is_present[$log->getHash()]) ? static::$is_present[$log->getHash()] + 1 : 1;

                return true;
            }

        }

        return false;
    }

    /**
     * Send e-mail to the shop owner only if the minimal severity level has been reached
     *
     * @param        Logger
     * @param Logger $log
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function sendByMail($log) {

        if ((int) Configuration::get('EPH_LOGS_BY_EMAIL') <= (int) $log->severity) {
            $tpl = $context->smarty->createTemplate(_EPH_MAIL_DIR_ . '/fr/log_alert.tpl');
            $postfields = [
                'sender'      => [
                    'name'  => "Service  Administratif ".Configuration::get('EPH_SHOP_NAME'),
                    'email' => 'no-reply@'.Configuration::get('EPH_SHOP_URL'),
                ],
                'to'          => [
                    [
                        'name'  => "Webmatser",
                        'email' => 'jeff@ephenyx.com',
                    ],
                ],

                'subject'     => 'Log: You have a new alert from your shop',
                "htmlContent" => $tpl->fetch(),
            ];

            $result = Tools::sendEmail($postfields);

        }

    }

    /**
     * check if this log message already exists in database.
     *
     * @return true if exists
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    protected function _isPresent() {

        if (!isset(static::$is_present[md5($this->message)])) {
            static::$is_present[$this->getHash()] = Db::getInstance()->getValue(
                'SELECT COUNT(*)
                FROM `' . _DB_PREFIX_ . 'log`
                WHERE
                    `message` = \'' . $this->message . '\'
                    AND `severity` = \'' . $this->severity . '\'
                    AND `error_code` = \'' . $this->error_code . '\'
                    AND `object_type` = \'' . $this->object_type . '\'
                    AND `object_id` = \'' . $this->object_id . '\'
                '
            );
        }

        return static::$is_present[$this->getHash()];
    }

    /**
     * this function md5($this->message.$this->severity.$this->error_code.$this->object_type.$this->object_id)
     *
     * @return string hash
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getHash() {

        if (empty($this->hash)) {
            $this->hash = md5($this->message . $this->severity . $this->error_code . $this->object_type . $this->object_id);
        }

        return $this->hash;
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function eraseAllLogs() {

        return Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'log');
    }

}
