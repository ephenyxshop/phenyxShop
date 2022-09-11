<?php

/**
 * @since 2.1.0.0
 */
class PaymentModeCore extends ObjectModel {

	// @codingStandardsIgnoreStart
	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = [
		'table'     => 'payment_mode',
		'primary'   => 'id_payment_mode',
		'multilang' => true,
		'fields'    => [
			'id_payment_type' => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedId'],
			'id_book_diary'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_bank_account' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'code'            => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 34],
			'id_module'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'active'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],

			/* Lang fields */

			'name'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 128],
		],
	];
	public $id_payment_type;
	public $id_book_diary;
	public $id_bank_account;
	public $id_module;
	public $name;
	public $code;
	public $active;

	public $payment_type;
	public $bank_account;
	public $book_diary;
	public $bookcode;

	/**
	 * GenderCore constructor.
	 *
	 * @param int|null $id
	 * @param int|null $idLang
	 * @param int|null $idShop
	 *
	 * @since 2.1.0.0
	 */
	public function __construct($id = null, $idLang = null, $idShop = null) {

		parent::__construct($id, $idLang, $idShop);

		if ($this->id) {
			$this->payment_type = $this->getPaymentType();
			$this->bank_account = $this->getBankAccount();
			$this->book_diary = $this->getDiaryName();
			$this->bookcode = $this->getDiaryCode();
		}

	}

	public function getPaymentType() {

		$context = Context::getContext();

		if (!empty($this->id_payment_type)) {
			$type = new PaymentType($this->id_payment_type, $context->language->id);
			return $type->name;
		}

	}

	public function getBankAccount() {

		if (!empty($this->id_bank_account)) {
			$bank = new BankAccount($this->id_bank_account);
			return $bank->name;
		}

	}

	public function getDiaryName() {

		$context = Context::getContext();

		if (!empty($this->id_book_diary)) {
			$diary = new BookDiary($this->id_book_diary, $context->language->id);
			return $diary->name;
		}

	}

	public function getDiaryCode() {

		$context = Context::getContext();

		if (!empty($this->id_book_diary)) {
			$diary = new BookDiary($this->id_book_diary, $context->language->id);
			return $diary->code;
		}

	}

	/**
	 * @param null $idLang
	 *
	 * @return PhenyxShopCollection
	 *
	 * @since 2.1.0.0
	 */
	public static function getPaymentModes($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}

		$paymentTypes = new PhenyxShopCollection('PaymentMode', $idLang);

		return $paymentTypes;
	}

	public static function getPaymentModeByModuleId($idModule, $moduleName) {

		$id_payment_mode = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`id_payment_mode`')
				->from('payment_mode')
				->where('`id_module` = ' . (int) $idModule)
		);

		if (!empty($id_payment_mode)) {
			return $id_payment_mode;
		} else {
			$idLang = Context::getContext()->language->id;

			$search = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
					->select('`id_payment_mode`')
					->from('payment_mode_lang')
					->where('`name` LIKE \'%' . $moduleName . '%\'')
					->where('`id_lang` = ' . (int) $idLang)
			);

			if (!empty($search)) {
				return $search;
			} else {
				return 6;
			}

		}

	}

	public static function getPaymentModeNameById($idPaymentMode) {

		$idLang = Context::getContext()->language->id;

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`name`')
				->from('payment_mode_lang')
				->where('`id_payment_mode` = ' . (int) $idPaymentMode)
				->where('`id_lang` = ' . (int) $idLang)
		);

	}

}
