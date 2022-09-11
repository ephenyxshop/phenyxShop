<?php

class BookDiary extends ObjectModel {

	public $id;

	public $id_diary_type;

	public $name;

	public $code;

	public $diary;

	public static $definition = [
		'table'     => 'book_diary',
		'primary'   => 'id_book_diary',
		'multilang' => true,
		'fields'    => [
			'id_diary_type' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
			/* Lang fields */
			'code'          => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
			'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
		],
	];

	public function __construct($id = null, $id_lang = null) {

		parent::__construct($id, $id_lang);

		if ($this->id) {
			$this->diary = $this->getDiaryName();
		}

	}

	public function getDiaryName() {

		$context = Context::getContext();

		if (!empty($this->id_diary_type)) {
			$diary = new DiaryType($this->id_diary_type, $context->language->id);
			return $diary->name;
		}

	}

	public static function getBookDiary($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		return new PhenyxShopCollection('BookDiary', $idLang);

	}

}
