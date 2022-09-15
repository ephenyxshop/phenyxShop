<?php

class Sepa extends PhenyxObjectModel {

    public $id;

    public $id_order;

    public $id_bank;

    public $export;

    public $step;

    public $date_add;

    public $value_date;

    public $date_transfered;

    public static $definition = [
        'table'   => 'sepa',
        'primary' => 'id_sepa',
        'fields'  => [
            'id_order'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_bank'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'export'          => ['type' => self::TYPE_BOOL],
            'step'            => ['type' => self::TYPE_STRING],
            'date_add'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'value_date'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_transfered' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];

    public function add($autodate = true, $null_values = false) {

        if (!parent::add($autodate, $null_values)) {
            return false;
        }

    }

    public static function getBanks($id_customer, $id_lang) {

        return (Db::getInstance()->ExecuteS('
            SELECT b.*, b.id_bank_account as bank_id, m.*, m.active as mandat_active, cl.name as country_name
            FROM `' . _DB_PREFIX_ . 'bank_account` b
            LEFT JOIN `' . _DB_PREFIX_ . 'mandat` m ON (m.id_bank = b.id_bank_account)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            WHERE b.`id_customer`= ' . (int) $id_customer . ' AND b.active = 1
            ORDER BY b.`id_bank_account`'));
    }

    public static function getBanksbyOrder($id_order, $id_lang) {

        return (Db::getInstance()->getRow('
            SELECT b.*, m.*, cl.name as country_name
             FROM `' . _DB_PREFIX_ . 'sepa` ob
            LEFT JOIN`' . _DB_PREFIX_ . 'bank_account` b ON (b.id_bank_account = ob.id_bank)
            LEFT JOIN`' . _DB_PREFIX_ . 'mandat` m ON (m.id_bank = b.id_bank_account)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            WHERE ob.`id_order`= ' . (int) $id_order));
    }

    public static function getSepa($id_lang, $export = false) {

        $sql = '
            SELECT s.id_sepa, s.value_date as value_date, s.export, s.step as statut, b.*, m.*,
            o.date_add as order_date, cl.name as country_name, o.piece_number as reference, o.total_tax_incl,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`, c.company
            FROM `' . _DB_PREFIX_ . 'sepa` s
            LEFT JOIN `' . _DB_PREFIX_ . 'bank_account` b ON (b.id_bank_account = s.id_bank)
            LEFT JOIN `' . _DB_PREFIX_ . 'mandat` m ON (m.id_bank = b.id_bank_account)
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_pieces` o ON (o.id_customer_piece = s.id_order)
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = b.id_country AND cl.id_lang = ' . (int) $id_lang . ')
            LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (o.id_customer = c.id_customer)';

        if ($export) {
            $sql .= 'WHERE s.`export` =0';
        }

        $sql .= '  ORDER BY s.`id_sepa`';
        return Db::getInstance()->ExecuteS($sql);
    }

    public static function getTotal() {

        $sql = '
            SELECT SUM(o.total_tax_incl)
            FROM `' . _DB_PREFIX_ . 'sepa` s
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_pieces` o ON (o.id_customer_piece = s.id_order)
            WHERE s.`export` =0';

        return Db::getInstance()->getValue($sql);
    }

    public static function getSepaExport() {

        $sql = '
            SELECT s.id_sepa, s.value_date as value_date, b.*, m.*,
            o.total_tax_incl, o.date_add as order_date,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`, c.company
            FROM `' . _DB_PREFIX_ . 'sepa` s
            LEFT JOIN `' . _DB_PREFIX_ . 'bank_account` b ON (b.id_bank_account = s.id_bank)
            LEFT JOIN `' . _DB_PREFIX_ . 'mandat` m ON (m.id_bank = b.id_bank_account)
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_pieces` o ON (o.id_customer_piece = s.id_order)
            LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (o.id_customer = c.id_customer)
            WHERE s.`export` =0
            ORDER BY s.`id_sepa`';

        return Db::getInstance()->ExecuteS($sql);
    }

    public static function getSepaStep($id_bank) {

        $mandat = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'mandat` WHERE `id_bank` = ' . (int) $id_bank);

        if ($mandat['mandat_type'] == 'Unique') {
            return $mandat['step'];
        } else {
            $is_sepa = Db::getInstance()->getValue('SELECT COUNT(id_sepa)
                    FROM `' . _DB_PREFIX_ . 'sepa` WHERE `id_bank` = ' . (int) $id_bank);

            if ($is_sepa != 0) {
                return 'RCUR';
            } else {
                return $mandat['step'];
            }

        }

    }
	
	public static function getSepaByIdBank($id_bank) {

        $sepas = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'sepa` WHERE `id_bank` = ' . (int) $id_bank);

        if(is_array($sepas) && count($sepas)) {
			return false;
		}
		
		return true;

    }

    public static function hasValidSiret($id_customer) {

        if (Validate::isSiret(Db::getInstance()->getValue('SELECT `siret`
                    FROM `' . _DB_PREFIX_ . 'customer` WHERE `id_customer` = ' . (int) $id_customer))) {
            return true;
        }

    }

}
