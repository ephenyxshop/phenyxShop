<?php

/**
 * Class CustomerThreadCore
 *
 * @since 1.9.1.0
 */
class CustomerThreadCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int $id_contact */
    public $id_contact;
    /** @var int $id_customer */
    public $id_customer;
    /** @var int $id_order */
    public $id_order;
    /** @var int $id_product */
    public $id_product;
    /** @var bool $status */
    public $status;
    /** @var string $email */
    public $email;
    /** @var string $token */
    public $token;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_thread',
        'primary' => 'id_customer_thread',
        'fields'  => [
            'id_lang'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_contact'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'email'       => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 254],
            'token'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'status'      => ['type' => self::TYPE_STRING],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    
    /**
     * @param int      $idCustomer
     * @param int|null $read
     * @param int|null $idOrder
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCustomerMessages($idCustomer, $read = null, $idOrder = null) {

        $sql = (new DbQuery())
            ->select('*')
            ->from('customer_thread', 'ct')
            ->leftJoin('customer_message', 'cm', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
            ->where('`id_customer` = ' . (int) $idCustomer);

        if ($read !== null) {
            $sql->where('cm.`read` = ' . (int) $read);
        }

        if ($idOrder !== null) {
            $sql->where('ct.`id_order` = ' . (int) $idOrder);
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param string $email
     * @param int    $idOrder
     *
     * @return false|null|string
     *
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getIdCustomerThreadByEmailAndIdOrder($email, $idOrder) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('cm.`id_customer_thread`')
                ->from('customer_thread', 'cm')
                ->where('cm.`email` = \'' . pSQL($email) . '\'')
                ->where('cm.`id_order` = ' . (int) $idOrder)
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getContacts() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cl.*, COUNT(*) as `total`')
                ->select('(SELECT `id_customer_thread` FROM `' . _DB_PREFIX_ . 'customer_thread` ct2 WHERE status = "open" AND ct.`id_contact` = ct2.`id_contact`  ORDER BY `date_upd` ASC LIMIT 1) AS `id_customer_thread`')
                ->from('customer_thread', 'ct')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = ct.`id_contact` AND cl.`id_lang` = ' . (int) Context::getContext()->language->id)
                ->where('ct.`status` = "open"')
                ->where('ct.`id_contact` IS NOT NULL')
                ->where('cl.`id_contact` IS NOT NULL')
                ->groupBy('ct.`id_contact`')
                ->having('COUNT(*) > 0')
        );
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
    public static function getTotalCustomerThreads($where = null) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('customer_thread')
                ->where(($where ?: '1'))
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
                ->select('ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name')
                ->select('CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname')
                ->from('customer_thread', 'ct')
                ->leftJoin('customer_message', 'cm', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = ct.`id_contact` AND cl.`id_lang` = ' . (int) Context::getContext()->language->id)
                ->leftJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee`')
                ->leftJoin('customer', 'c', '(IFNULL(ct.`id_customer`, ct.`email`) = IFNULL(c.`id_customer`, c.`email`))')
                ->where('ct.`id_customer_thread` = ' . (int) $idCustomerThread)
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
                ->select('`id_customer_thread`')
                ->from('customer_thread', 'ct')
                ->where('ct.status = "open"')
                ->where('ct.`date_upd` = (SELECT date_add FROM ' . _DB_PREFIX_ . 'customer_message WHERE (id_employee IS NULL OR id_employee = 0) AND id_customer_thread = ' . (int) $idCustomerThread . ' ORDER BY date_add DESC LIMIT 1)')
                ->where($context->cookie->{'customer_threadFilter_cl!id_contact'}
                    ? 'ct.`id_contact` = ' . (int) $context->cookie->{'customer_threadFilter_cl!id_contact'}
                    : '')
                ->where($context->cookie->{'customer_threadFilter_l!id_lang'}
                    ? 'ct.`id_lang` = ' . (int) $context->cookie->{'customer_threadFilter_l!id_lang'}
                    : '')
                ->orderBy('ct.`date_upd` ASC')
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getWsCustomerMessages() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_customer_message` AS `id`')
                ->from('customer_message')
                ->where('`id_customer_thread` = ' . (int) $this->id)
        );
    }

    /**
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function delete() {

        if (!Validate::isUnsignedId($this->id)) {
            return false;
        }

        $return = true;
        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_customer_message`')
                ->from('customer_message')
                ->where('`id_customer_thread` = ' . (int) $this->id)
        );

        if (count($result)) {

            foreach ($result as $res) {
                $message = new CustomerMessage((int) $res['id_customer_message']);

                if (!Validate::isLoadedObject($message)) {
                    $return = false;
                } else {
                    $return &= $message->delete();
                }

            }

        }

        $return &= parent::delete();

        return $return;
    }

}
