<?php

/**
 * @since 1.9.1.0
 */
class BookRecordsCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'book_records',
        'primary' => 'id_book_record',
        'fields'  => [
            'id_book_diary' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'validate'      => ['type' => self::TYPE_BOOL],
            'piece_type'    => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'date_add'      => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'],
            'date_upd'      => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'],

            /* Lang fields */
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 256],
        ],
    ];
    public $id_book_record;
    public $name;
    // @codingStandardsIgnoreEnd
    public $id_book_diary;
    public $validate;
    public $piece_type;
    public $date_add;
    public $date_upd;
    public $diaryName;
    public $diaryCode;

    /**
     * BookRecordsCore constructor.
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

        if ($this->id) {
            $this->diaryName = $this->getDiaryName();
            $this->diaryCode = $this->getDiaryCode();

        }

    }

    public function getDiaryName() {

        $diary = new BookDiary($this->id_book_diary, 1);
        return $diary->name;
    }

    public function getDiaryCode() {

        $diary = new BookDiary($this->id_book_diary, 1);
        return $diary->code;
    }

    public function add($autoDate = false, $nullValues = true) {

        return parent::add($autoDate, $nullValues);
    }

    public static function getRecordDetailsById($idRecord) {

        $idLang = Context::getContext()->language->id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('brd.*, s.account, sl.`name` as accountName')
                ->from('book_record_details', 'brd')
                ->leftJoin('stdaccount', 's', 's.`id_stdaccount` = brd.`id_stdaccount`')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = brd.`id_stdaccount` AND sl.`id_lang` = ' . (int) $idLang)
                ->where('brd.`id_book_record` = ' . (int) $idRecord)
        );
    }

}
