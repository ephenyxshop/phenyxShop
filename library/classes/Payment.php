<?php

/**
 * Class PaymentCore
 *
 * @since 2.1.0.0
 */
class PaymentCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
	public $payment_number;
    /** @var int $id_currency */
    public $id_currency;
	
	public $id_customer;
    /** @var float $amount */
    public $amount;
    /** @var integer $id_payment_mode */
    public $id_payment_mode;
    /** @var string $payment_method */
    public $payment_method;
    /** @var float $conversion_rate */
    public $conversion_rate = 1;
    /** @var bool $book */
    public $booked;
    /** @var string $date_add */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'payment',
        'primary' => 'id_payment',
        'fields'  => [
			'payment_number'                => ['type' => self::TYPE_INT],
            'id_currency'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
			'id_customer'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount'          => ['type' => self::TYPE_FLOAT, 'validate' => 'isNegativePrice', 'required' => true],
            'id_payment_mode' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'payment_method'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'booked'          => ['type' => self::TYPE_BOOL],
            'date_add'        => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 2.1.0.0
     */
    public function add($autoDate = true, $nullValues = false) {

        
		$this->payment_number = $this->generatePaymentNumber();
		if (parent::add($autoDate, $nullValues)) {
            return true;
        }

        return false;
    }
	
	public function delete() {

		$details = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('id_payment_detail')
                ->from('payment_details')
                ->where('`id_payment` = ' . (int) $this->id)
        );
		if(is_array($details) && count($details)) {
			foreach($details as $detail) {
				$pdetail = new PaymentDetails($detail['id_payment_detail']);
				$pdetail->delete();
				
			}

		}
		
		return parent::delete();
	}


    public static function getByCustomerPieceId($idCustomerPiece, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('pd.*, p.`id_payment_mode`, p.`payment_method`, pm.`name`')
                ->from('payment_details', 'pd')
                ->leftJoin('payment', 'p', 'p.`id_payment` = pd.`id_payment`')
                ->leftJoin('payment_mode_lang', 'pm', 'pm.`id_payment_mode` = p.`id_payment_mode` AND pm.`id_lang` = ' . (int) $idLang)
                ->where('pd.`id_customer_piece` = ' . (int) $idCustomerPiece)
        );
    }
	
	public function generatePaymentNumber() {
	
		$year = date('Y');
		$lastValue = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
			->select('`payment_number`')
			->from('payment')
			->orderBy('`id_payment` DESC')
		);
		
		if(empty($lastValue)) {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		$test = substr($lastValue, 0, 4);
		if($test == $year) {
			return $lastValue+1;
		} else {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		
		
	}
	
	public static function getIncrement() {
		
		
		
		$year = date('Y');
		$lastValue = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
				->select('`id_payment`')
				->from('payment')
				->orderBy('`id_payment` DESC')
		);
		
		if(empty($lastValue)) {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		$test = substr($lastValue, 0, 4);
		if($test == $year) {
			return $lastValue+1;
		} else {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		};
		
	}

}
