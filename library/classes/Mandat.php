<?php

class Mandat extends PhenyxObjectModel {

    public $id;

    public $id_bank;

    public $mandat_sepa;

    public $mandat_type;

    public $step;

    public $IP_Registration;

    public $date_add;

    public $date_execution;

    public $active = true;

    public static $definition = [
        'table'   => 'mandat',
        'primary' => 'id_mandat',
        'fields'  => [
            'id_bank'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'required' => true, 'copy_post' => false],
            'mandat_sepa'     => ['type' => self::TYPE_STRING, 'required' => true],
            'mandat_type'     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'step'            => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'IP_Registration' => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'date_add'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_execution'  => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'copy_post' => false],
            'active'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
        ],
    ];

    public function add($autodate = true, $null_values = false) {

        if (!parent::add($autodate, $null_values)) {
            return false;
        }

    }

    public function update($null_values = false) {

        return parent::update($null_values);
    }

    public static function getMandatIdbyBankId($id_bank) {

        return (Db::getInstance()->getValue('
            SELECT `id_mandat`
            FROM `' . _DB_PREFIX_ . 'mandat`
            WHERE `id_bank`= ' . (int) $id_bank));
    }

}
