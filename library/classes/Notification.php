<?php

/**
 * Class NotificationCore
 *
 * @since 1.9.1.0
 */
class NotificationCore {

    public $types;

    /**
     * NotificationCore constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        $this->types = ['customer_piece', 'customer_message', 'customer'];
    }

    /**
     * getLastElements return all the notifications (new order, new customer registration, and new customer message)
     * Get all the notifications
     *
     * @return array containing the notifications
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getLastElements() {

        $notifications = [];
        $employeeInfos = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_last_customer_piece`, `id_last_customer_message`, `id_last_customer`')
                ->from('employee')
                ->where('`id_employee` = ' . (int) Context::getContext()->cookie->id_employee)
        );

        foreach ($this->types as $type) {
            $notifications[$type] = Notification::getLastElementsIdsByType($type, $employeeInfos['id_last_' . $type]);
        }

        return $notifications;
    }

    /**
     * getLastElementsIdsByType return all the element ids to show (order, customer registration, and customer message)
     * Get all the element ids
     *
     * @param string $type          contains the field name of the Employee table
     * @param int    $idLastElement contains the id of the last seen element
     *
     * @return array containing the notifications
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getLastElementsIdsByType($type, $idLastElement) {
		
        switch ($type) {
        case 'customer_piece':
            $sql = (new DbQuery())
                ->select('SQL_CALC_FOUND_ROWS o.`id_customer_piece`, o.`id_customer`, o.`total_tax_incl`')
                ->select('o.`id_currency`, o.`date_upd`, c.`firstname`, c.`lastname`')
                ->from('customer_pieces', 'o')
                ->leftJoin('customer', 'c', 'c.`id_customer` = o.`id_customer`')
                ->where('`id_customer_piece` > ' . (int) $idLastElement . ' ' . Shop::addSqlRestriction(false, 'o'))
                ->orderBy('`id_customer_piece` DESC')
                ->limit(5);
            break;

        case 'customer_message':
            $sql = (new DbQuery())
                ->select('SQL_CALC_FOUND_ROWS c.`id_customer_message`, ct.`id_customer`, ct.`id_customer_thread`')
                ->select('ct.`email`, c.`date_add` AS `date_upd`')
                ->from('customer_message', 'c')
                ->leftJoin('customer_thread', 'ct', 'c.`id_customer_thread` = ct.`id_customer_thread`')
                ->where('c.`id_customer_message` > ' . (int) $idLastElement)
                ->where('c.`id_employee` = 0')
                ->where('ct.`id_shop` IN (' . implode(', ', Shop::getContextListShopID()) . ')')
                ->orderBy('c.`id_customer_message` DESC')
                ->limit(5);
            break;
        default:
            $sql = (new DbQuery())
                ->select('SQL_CALC_FOUND_ROWS t.`id_' . bqSQL($type) . '`, t.*')
                ->from(bqSQL($type), 't')
                ->where('t.`deleted` = 0')
                ->where('t.`id_' . bqSQL($type) . '` > ' . (int) $idLastElement . ' ' . Shop::addSqlRestriction(false, 't'))
                ->orderBy('t.`id_' . bqSQL($type) . '` DESC')
                ->limit(5);
            break;
        }
		
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $total = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()', false);
        $json = ['total' => $total, 'results' => []];

        foreach ($result as $value) {
            $customerName = '';

            if (isset($value['firstname']) && isset($value['lastname'])) {
                $customerName = Tools::safeOutput($value['firstname'] . ' ' . $value['lastname']);
            } else if (isset($value['email'])) {
                $customerName = Tools::safeOutput($value['email']);
            }

            $json['results'][] = [
                'id_customer_piece'            => ((!empty($value['id_customer_piece'])) ? (int) $value['id_customer_piece'] : 0),
                'id_customer'         => ((!empty($value['id_customer'])) ? (int) $value['id_customer'] : 0),
                'id_customer_message' => ((!empty($value['id_customer_message'])) ? (int) $value['id_customer_message'] : 0),
                'id_customer_thread'  => ((!empty($value['id_customer_thread'])) ? (int) $value['id_customer_thread'] : 0),
                'total_paid'          => ((!empty($value['total_tax_incl'])) ? Tools::displayPrice((float) $value['total_tax_incl'], (int) $value['id_currency'], false) : 0),
                'customer_name'       => $customerName,
                // x1000 because of moment.js (see: http://momentjs.com/docs/#/parsing/unix-timestamp/)
                'update_date'         => isset($value['date_upd']) ? (int) strtotime($value['date_upd']) * 1000 : 0,
            ];
        }

        return $json;
    }

    /**
     * updateEmployeeLastElement return 0 if the field doesn't exists in Employee table.
     * Updates the last seen element by the employee
     *
     * @param string $type contains the field name of the Employee table
     *
     * @return bool if type exists or not
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function updateEmployeeLastElement($type) {

        global $cookie;

        if (in_array($type, $this->types)) {
            // We update the last item viewed
            return Db::getInstance()->update(
                'employee',
                [
                    'id_last_' . bqSQL($type) => ['type' => 'sql', 'value' => '(SELECT IFNULL(MAX(`id_' . $type . '`), 0) FROM `' . _DB_PREFIX_ . (($type == 'customer_piece') ? bqSQL($type) . 's' : bqSQL($type)) . '`)'],
                ],
                '`id_employee` = ' . (int) $cookie->id_employee
            );
        }

        return false;
    }

}
