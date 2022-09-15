<?php

class DiaryType extends PhenyxObjectModel {

    public $id;

    public $name;

    public static $definition = [
        'table'     => 'diary_type',
        'primary'   => 'id_diary_type',
        'multilang' => true,
        'fields'    => [
            /* Lang fields */

            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);

    }

    public static function getDiaryType($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.`id_diary_type`, b.`name`')
                ->from('diary_type', 'a')
                ->leftJoin('diary_type_lang', 'b', 'b.`id_diary_type` = a.`id_diary_type` AND b.`id_lang`  = ' . (int) $idLang)
                ->orderBy('`id_diary_type` ASC')
        );
    }

}
