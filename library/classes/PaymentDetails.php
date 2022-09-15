<?php

/**
 * Class PaymentCore
 *
 * @since 2.1.0.0
 */
class PaymentDetailsCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var string $order_reference */
    public $id_payment;
    /** @var int $id_currency */
    public $id_customer_piece;
    /** @var float $amount */
    public $amount;
    /** @var string $date_add */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'payment_details',
        'primary' => 'id_payment_detail',
        'fields'  => [
            'id_payment'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer_piece' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isNegativePrice', 'required' => true],
            'date_add'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
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
    public function add($autoDate = false, $nullValues = false) {

        if (parent::add($autoDate, $nullValues)) {
            return true;
        }

        return false;
    }
	
	public function delete() {

		$piece = new CustomerPieces($this->id_customer_piece);
		$piece->total_paid = 0;
		$piece->update();
		
		return parent::delete();
	}

    public static function getByCustomerPieceId($idCustomerPiece) {

        return PhenyxObjectModel::hydrateCollection(
            'PaymentDetails',
            Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('payment_details')
                    ->where('`id_customer_piece` = ' . (int) $idCustomerPiece)
            )
        );
    }

}
