<?php

/**
 *
 * @deprecated 1.5.0.1
 * @see OrderPaymentCore
 *
 */
class PaymentCCCore extends OrderPayment {

    // @codingStandardsIgnoreStart
    /** @var int $id_order */
    public $id_order;
    /** @var int $id_currency */
    public $id_currency;
    /** @var float $amount */
    public $amount;
    /** @var string $transaction_id */
    public $transaction_id;
    /** @var string $card_number */
    public $card_number;
    /** @var string $card_brand */
    public $card_brand;
    /** @var string $card_expiration */
    public $card_expiration;
    /** @var string $card_holder*/
    public $card_holder;
    /** @var string $date_add */
    public $date_add;
    protected $fieldsRequired = ['id_currency', 'amount'];
    protected $fieldsSize = ['transaction_id' => 254, 'card_number' => 254, 'card_brand' => 254, 'card_expiration' => 254, 'card_holder' => 254];
    protected $fieldsValidate = [
        'id_order'       => 'isUnsignedId', 'id_currency' => 'isUnsignedId', 'amount'   => 'isPrice',
        'transaction_id' => 'isAnything', 'card_number'   => 'isAnything', 'card_brand' => 'isAnything', 'card_expiration' => 'isAnything', 'card_holder' => 'isAnything',
    ];
    public static $definition = [
        'table'   => 'payment_cc',
        'primary' => 'id_payment_cc',
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @deprecated 1.5.0.2
     * @see        OrderPaymentCore
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     */
    public function add($autoDate = true, $nullValues = false) {

        Tools::displayAsDeprecated();

        return parent::add($autoDate, $nullValues);
    }

    /**
     * Get the detailed payment of an order
     *
     * @param int $idOrder
     *
     * @return array
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @deprecated 1.5.0.1
     * @see        OrderPaymentCore
     */
    public static function getByOrderId($idOrder) {

        Tools::displayAsDeprecated();
        $order = new Order($idOrder);

        return OrderPayment::getByOrderReference($order->reference);
    }
}
