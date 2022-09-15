<?php

class LCR extends PhenyxObjectModel {

    public $id;

    public $id_order;

    public $id_bank;

    public $date_add;

    public $value_date;

    public $paid;

    public static $definition = [
        'table'   => 'lcr',
        'primary' => 'id_lcr',
        'fields'  => [
            'id_order'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_bank'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'date_add'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'value_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'paid'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
        ],
    ];

    public function add($autodate = true, $null_values = false) {

        if (!parent::add($autodate, $null_values)) {
            return false;
        }

    }

    public static function getLcrs($id_customer, $id_lang) {

        return (Db::getInstance()->ExecuteS('
            SELECT b.*, b.id_bank as bank_id, m.*, m.active as mandat_active, cl.name as country_name
            FROM `' . _DB_PREFIX_ . 'bank` b
            LEFT JOIN `' . _DB_PREFIX_ . 'mandat` m ON (m.id_bank = b.id_bank)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            WHERE b.`id_customer`= ' . (int) $id_customer . ' AND b.`deleted` =0
            ORDER BY b.`id_bank`'));
    }

    public static function getBanksbyOrder($id_order, $id_lang) {

        return (Db::getInstance()->getRow('
            SELECT b.*, l.value_date, cl.name as country_name
            FROM `' . _DB_PREFIX_ . 'lcr` l
            LEFT JOIN`' . _DB_PREFIX_ . 'bank` b ON (b.id_bank = l.id_bank)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            WHERE l.`id_order`= ' . (int) $id_order));
    }

    public static function getLCR($id_lang) {

        $sql = '
            SELECT l.id_lcr, l.value_date as value_date, l.paid, b.*,
            o.date_add as order_date, cl.name as country_name, o.reference, o.total_paid_tax_incl,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`, c.company
            FROM `' . _DB_PREFIX_ . 'lcr` l
            LEFT JOIN `' . _DB_PREFIX_ . 'bank` b ON (b.id_bank = l.id_bank)
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = l.id_order)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (o.id_customer = c.id_customer)
            ORDER BY l.`id_lcr`';

        return Db::getInstance()->ExecuteS($sql);
    }

    public static function getTotal() {

        $sql = '
            SELECT SUM(o.total_paid_tax_incl)
            FROM `' . _DB_PREFIX_ . 'lcr` l
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = l.id_order)
            WHERE l.paid = 0';

        return Db::getInstance()->getValue($sql);
    }

    public static function isCustomerAllowed($id_customer) {

        return Db::getInstance()->getValue('SELECT `id_customer` FROM `' . _DB_PREFIX_ . 'customer_lcr` WHERE `id_customer` = ' . (int) $id_customer);
    }

    public static function isBtoBCustomer($id_customer) {

        $isbusiness = false;
        $customer = new Customer($id_customer);
        $btob_mode = unserialize(Configuration::get('BTOB_MODE'));

        if (in_array("groups", $btob_mode)) {
            $btob_groups = unserialize(Configuration::get('BTOB_GROUPS'));
            $custmer_groups = $customer->getGroups();

            foreach ($custmer_groups as $custmer_group) {

                if (in_array($custmer_group, $btob_groups)) {
                    $isbusiness = true;
                }

            }

        }

        if (in_array("siret", $btob_mode)) {
            $isbusiness = Sepa::hasValidSiret($customer->id);
        }

        return $isbusiness;
    }

}
