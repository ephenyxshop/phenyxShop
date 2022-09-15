<?php


/**
 * Class SupplyOrderReceiptHistoryCore
 *
 * @since 1.9.1.0
 */
class SupplyOrderReceiptHistoryCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var int Detail of the supply order (i.e. One particular product)
     */
    public $id_supply_order_detail;

    /**
     * @var int Employee
     */
    public $id_employee;

    /**
     * @var string The first name of the employee responsible of the movement
     */
    public $employee_firstname;

    /**
     * @var string The last name of the employee responsible of the movement
     */
    public $employee_lastname;

    /**
     * @var int State
     */
    public $id_supply_order_state;

    /**
     * @var int Quantity delivered
     */
    public $quantity;

    /**
     * @var string Date of delivery
     */
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supply_order_receipt_history',
        'primary' => 'id_supply_order_receipt_history',
        'fields'  => [
            'id_supply_order_detail' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_supply_order_state'  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'id_employee'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true],
            'employee_firstname'     => ['type' => self::TYPE_STRING, 'validate' => 'isName'                           ],
            'employee_lastname'      => ['type' => self::TYPE_STRING, 'validate' => 'isName'                           ],
            'quantity'               => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true],
            'date_add'               => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                           ],
        ],
    ];

    /**
     * @see PhenyxObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'supply_order_receipt_histories',
        'objectNodeName'  => 'supply_order_receipt_history',
        'fields'          => [
            'id_supply_order_detail' => ['xlink_resource' => 'supply_order_details'],
            'id_employee'            => ['xlink_resource' => 'employees'],
            'id_supply_order_state'  => ['xlink_resource' => 'supply_order_states'],
        ],
    ];
}
