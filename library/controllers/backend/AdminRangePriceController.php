<?php

/**
 * Class AdminRangePriceControllerCore
 *
 * @since 1.9.1.0
 */
class AdminRangePriceControllerCore extends AdminController {

    /**
     * AdminRangePriceControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'range_price';
        $this->className = 'RangePrice';
        $this->lang = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')]];

        $this->fields_list = [
            'id_range_price' => ['title' => $this->l('ID'), 'align' => 'center', 'width' => 25],
            'carrier_name'   => ['title' => $this->l('Carrier'), 'align' => 'left', 'width' => 'auto', 'filter_key' => 'ca!name'],
            'delimiter1'     => ['title' => $this->l('From'), 'width' => 86, 'type' => 'price', 'align' => 'right'],
            'delimiter2'     => ['title' => $this->l('To'), 'width' => 86, 'type' => 'price', 'align' => 'right'],
        ];

        $this->_join = 'LEFT JOIN ' . _DB_PREFIX_ . 'carrier ca ON (ca.`id_carrier` = a.`id_carrier`)';
        $this->_select = 'ca.`name` AS carrier_name';
        $this->_where = 'AND ca.`deleted` = 0';
        $this->_use_found_rows = false;

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        $this->page_header_toolbar_title = $this->l('Price ranges');
        $this->page_header_toolbar_btn['new_price_range'] = [
            'href' => static::$currentIndex . '&addrange_price&token=' . $this->token,
            'desc' => $this->l('Add new price range', null, null, false),
            'icon' => 'process-icon-new',
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        $currency = $this->context->currency;
        $carriers = Carrier::getCarriers((int) Configuration::get('EPH_LANG_DEFAULT'), true, false, false, null, Carrier::EPH_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

        foreach ($carriers as $key => $carrier) {

            if ($carrier['is_free']) {
                unset($carriers[$key]);
            }

        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Price ranges'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'          => 'select',
                    'label'         => $this->l('Carrier'),
                    'name'          => 'id_carrier',
                    'required'      => false,
                    'hint'          => $this->l('You can apply this range to a different carrier by selecting its name.'),
                    'options'       => [
                        'query' => $carriers,
                        'id'    => 'id_carrier',
                        'name'  => 'name',
                    ],
                    'empty_message' => '<p class="alert alert-block">' . $this->l('There is no carrier available for this price range.') . '</p>',
                ],
                [
                    'type'          => 'text',
                    'label'         => $this->l('From'),
                    'name'          => 'delimiter1',
                    'required'      => true,
                    'suffix'        => $currency->getSign('right') . ' ' . $this->l('(Tax Incl.)'),
                    'hint'          => $this->l('Start range (included).'),
                    'string_format' => '%.2f',
                ],
                [
                    'type'          => 'text',
                    'label'         => $this->l('To'),
                    'name'          => 'delimiter2',
                    'required'      => true,
                    'suffix'        => $currency->getSign('right') . ' ' . $this->l('(Tax Incl.)'),
                    'hint'          => $this->l('End range (excluded).'),
                    'string_format' => '%.2f',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default',
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Get list
     *
     * @param int         $idLang
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool $idLangShop
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false) {

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if ($this->_list && is_array($this->_list)) {

            foreach ($this->_list as $key => $list) {

                if ($list['carrier_name'] == '0') {
                    $this->_list[$key]['carrier_name'] = Carrier::getCarrierNameFromShopName();
                }

            }

        }

    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        $id = (int) Tools::getValue('id_' . $this->table);

        if (Tools::getValue('submitAdd' . $this->table)) {

            if (Tools::getValue('delimiter1') >= Tools::getValue('delimiter2')) {
                $this->errors[] = Tools::displayError('Invalid range');
            } else if (!$id && RangePrice::rangeExist((int) Tools::getValue('id_carrier'), (float) Tools::getValue('delimiter1'), (float) Tools::getValue('delimiter2'))) {
                $this->errors[] = Tools::displayError('The range already exists');
            } else if (RangePrice::isOverlapping((int) Tools::getValue('id_carrier'), (float) Tools::getValue('delimiter1'), (float) Tools::getValue('delimiter2'), ($id ? (int) $id : null))) {
                $this->errors[] = Tools::displayError('Error: Ranges are overlapping');
            } else if (!count($this->errors)) {
                parent::postProcess();
            }

        } else {
            parent::postProcess();
        }

    }

}
