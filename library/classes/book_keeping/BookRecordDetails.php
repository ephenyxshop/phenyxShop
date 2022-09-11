<?php

/**
 * @since 1.9.1.0
 */
class BookRecordDetailsCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'book_record_details',
        'primary' => 'id_book_record_detail',
        'fields'  => [
            'id_book_record' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_stdaccount'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'libelle'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 256],
            'piece_number'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'debit'          => ['type' => self::TYPE_FLOAT],
            'credit'         => ['type' => self::TYPE_FLOAT],
            'date_add'       => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'],
            /* Lang fields */

        ],
    ];
    public $id_book_record_detail;
    public $id_book_record;
    public $id_stdaccount;
    public $libelle;
    public $piece_number;
    public $debit;
    public $credit;
    public $date_add;

    /**
     * BookRecordDetailsCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

    }

    public function add($autoDate = false, $nullValues = true) {

        return parent::add($autoDate, $nullValues);
    }

}
