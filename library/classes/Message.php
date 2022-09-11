<?php

/**
 * Class MessageCore
 *
 * @since 1.9.1.0
 */
class MessageCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /** @var string message content */
    public $message;

    /** @var int Order ID (if applicable) */
    public $id_student_education;
    /** @var int Customer ID (if applicable) */
    public $id_student;
    /** @var int Employee ID (if applicable) */
    public $id_employee;
    /** @var bool Message is not displayed to the customer */
    public $private;
    /** @var string Object creation date */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'message',
        'primary' => 'id_message',
        'fields'  => [
            'message'              => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 1600],
            'id_student_education' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_student'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'private'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Return the last message from cart
     *
     * @param int $idCart Cart ID
     *
     * @return array Message
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMessageByCartId($idCart) {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('message')
                ->where('`id_cart` = ' . (int) $idCart)
        );
    }

    /**
     * Return messages from Order ID
     *
     * @param int          $idOrder Order ID
     * @param bool         $private return WITH private messages
     * @param Context|null $context
     *
     * @return array Messages
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMessagesByOrderId($idOrder, $private = false, Context $context = null) {

        if (!Validate::isBool($private)) {
            die(Tools::displayError());
        }

        if (!$context) {
            $context = Context::getContext();
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*')
                ->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')
                ->select('(COUNT(mr.`id_message`) = 0 AND m.`id_student` != 0) AS `is_new_for_me`')
                ->from('message', 'm')
                ->leftJoin('student', 'c', 'm.`id_student` = c.`id_student`')
                ->leftJoin('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = m.`id_employee`')
                ->where('mr.`id_employee` = ' . (isset($context->employee) ? (int) $context->employee->id : '\'\''))
                ->where('`id_order` = ' . (int) $idOrder)
                ->where($private ? 'm.`private` = 0' : '')
                ->groupBy('m.`id_message`')
                ->orderBy('m.`date_add` DESC')
        );
    }

    /**
     * Return messages from Cart ID
     *
     * @param int          $idCart
     * @param bool         $private return WITH private messages
     * @param Context|null $context
     *
     * @return array Messages
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public static function getMessagesByCartId($idCart, $private = false, Context $context = null) {

        if (!Validate::isBool($private)) {
            die(Tools::displayError());
        }

        if (!$context) {
            $context = Context::getContext();
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.*')
                ->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')
                ->from('message', 'm')
                ->leftJoin('customer', 'c', 'm.`id_customer` = c.`id_customer`')
                ->leftJoin('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = m.`id_employee`')
                ->where('mr.`id_employee` = ' . (int) $context->employee->id)
                ->where('`id_cart` = ' . (int) $idCart)
                ->where(!$private ? 'm.`private` = 0' : '')
                ->groupBy('m.`id_message`')
                ->orderBy('m.`date_add` DESC')
        );
    }

    /**
     * Registered a message 'readed'
     *
     * @param int $idMessage  Message ID
     * @param int $idEmployee Employee ID
     *
     * @return bool
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function markAsReaded($idMessage, $idEmployee) {

        if (!Validate::isUnsignedId($idMessage) || !Validate::isUnsignedId($idEmployee)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance()->insert(
            'message_readed',
            [
                'id_message'  => (int) $idMessage,
                'id_employee' => (int) $idEmployee,
                'date_add'    => ['type' => 'sql', 'value' => 'NOW()'],
            ]
        );

        return $result;
    }

}
