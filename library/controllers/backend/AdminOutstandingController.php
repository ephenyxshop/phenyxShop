<?php

/**
 * Class AdminOutstandingControllerCore
 *
 * @since 1.9.1.0
 */
class AdminOutstandingControllerCore extends AdminController {

    /**
     * AdminOutstandingControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'order_invoice';
        $this->className = 'OrderInvoice';
        $this->addRowAction('view');

        $this->context = Context::getContext();

        $this->_select = '`id_order_invoice` AS `id_invoice`,
        `id_order_invoice` AS `outstanding`,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        c.`outstanding_allow_amount`,
        r.`color`,
        rl.`name` AS `risk`';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = o.`id_customer`)
        LEFT JOIN `' . _DB_PREFIX_ . 'risk` r ON (r.`id_risk` = c.`id_risk`)
        LEFT JOIN `' . _DB_PREFIX_ . 'risk_lang` rl ON (r.`id_risk` = rl.`id_risk` AND rl.`id_lang` = ' . (int) $this->context->language->id . ')';
        $this->_where = 'AND number > 0';
        $this->_use_found_rows = false;

        $risks = [];

        foreach (Risk::getRisks() as $risk) {
            /** @var Risk $risk */
            $risks[$risk->id] = $risk->name;
        }

        $this->fields_list = [
            'number'                   => [
                'title' => $this->l('Invoice'),
            ],
            'date_add'                 => [
                'title'      => $this->l('Date'),
                'type'       => 'date',
                'align'      => 'right',
                'filter_key' => 'a!date_add',
            ],
            'customer'                 => [
                'title'          => $this->l('Customer'),
                'filter_key'     => 'customer',
                'tmpTableFilter' => true,
            ],
            'company'                  => [
                'title' => $this->l('Company'),
                'align' => 'center',
            ],
            'risk'                     => [
                'title'       => $this->l('Risk'),
                'align'       => 'center',
                'orderby'     => false,
                'type'        => 'select',
                'color'       => 'color',
                'list'        => $risks,
                'filter_key'  => 'r!id_risk',
                'filter_type' => 'int',
            ],
            'outstanding_allow_amount' => [
                'title'  => $this->l('Outstanding Allowance'),
                'align'  => 'center',
                'prefix' => '<b>',
                'suffix' => '</b>',
                'type'   => 'price',
            ],
            'outstanding'              => [
                'title'    => $this->l('Current Outstanding'),
                'align'    => 'center',
                'callback' => 'printOutstandingCalculation',
                'orderby'  => false,
                'search'   => false,
            ],
            'id_invoice'               => [
                'title'    => $this->l('Invoice'),
                'align'    => 'center',
                'callback' => 'printPDFIcons',
                'orderby'  => false,
                'search'   => false,
            ],
        ];

        parent::__construct();
    }

    /**
     * Column callback for print PDF incon
     *
     * @param int   $idInvoice Invoice ID
     * @param array $tr        Row data
     *
     * @return string HTML content
     */
    public function printPDFIcons($idInvoice, $tr) {

        $this->context->smarty->assign(
            [
                'id_invoice' => $idInvoice,
            ]
        );

        return $this->createTemplate('_print_pdf_icon.tpl')->fetch();
    }

    /**
     * Print outstanding calculation
     *
     * @param int   $idInvoice
     * @param array $tr
     *
     * @return string
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     */
    public function printOutstandingCalculation($idInvoice, $tr) {

        $orderInvoice = new OrderInvoice($idInvoice);

        if (!Validate::isLoadedObject($orderInvoice)) {
            throw new PhenyxShopException('object OrderInvoice cannot be loaded');
        }

        $order = new Order($orderInvoice->id_order);

        if (!Validate::isLoadedObject($order)) {
            throw new PhenyxShopException('object Order cannot be loaded');
        }

        $customer = new Customer((int) $order->id_customer);

        if (!Validate::isLoadedObject($orderInvoice)) {
            throw new PhenyxShopException('object Customer cannot be loaded');
        }

        return '<b>' . Tools::displayPrice($customer->getOutstanding(), $this->context->currency) . '</b>';
    }

    /**
     * View render
     *
     * @throws PhenyxShopException Invalid objects
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderView() {

        $orderInvoice = new OrderInvoice((int) Tools::getValue('id_order_invoice'));

        if (!Validate::isLoadedObject($orderInvoice)) {
            throw new PhenyxShopException('object OrderInvoice cannot be loaded');
        }

        $order = new Order($orderInvoice->id_order);

        if (!Validate::isLoadedObject($order)) {
            throw new PhenyxShopException('object Order cannot be loaded');
        }

        $link = $this->context->link->getAdminLink('AdminOrders');
        $link .= '&vieworder&id_order=' . $order->id;
        $this->redirect_after = $link;
        $this->redirect();

        return '';
    }

}
