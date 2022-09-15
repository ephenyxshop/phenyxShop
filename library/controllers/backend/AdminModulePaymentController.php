<?php

/**
 * Class AdminPaymentControllerCore
 *
 * @since 1.8.1.0
 */
class AdminModulePaymentControllerCore extends AdminController {

    public $php_self = 'adminmodulepayment';
    // @codingStandardsIgnoreStart
    /** @var array $payment_modules */
    public $payment_modules = [];

    public $submit_name;

    // @codingStandardsIgnoreEnd

    /**
     * AdminPaymentControllerCore constructor.
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        $this->bootstrap = true;
        parent::__construct();
        $this->publicName = $this->l('Payment Modules Management');

        $idCompany = $this->context->company->id;

        /* Get all modules then select only payment ones */
        $modules = Module::getModulesOnDisk(true);

        foreach ($modules as $module) {

            if ($module->tab == 'payments_gateways') {

                if ($module->id) {

                    if (!get_class($module) == 'SimpleXMLElement') {
                        $module->country = [];
                    }

                    $sql = new DbQuery();
                    $sql->select('`id_country`');
                    $sql->from('module_country');
                    $sql->where('`id_module` = ' . (int) $module->id);
                    $sql->where('`id_shop` = ' . (int) $idCompany);

                    $countries = DB::getInstance()->executeS($sql);

                    foreach ($countries as $country) {
                        $module->country[] = $country['id_country'];
                    }

                    if (!get_class($module) == 'SimpleXMLElement') {
                        $module->currency = [];
                    }

                    $sql = new DbQuery();
                    $sql->select('`id_currency`');
                    $sql->from('module_currency');
                    $sql->where('`id_module` = ' . (int) $module->id);
                    $sql->where('`id_shop` = ' . (int) $idCompany);

                    $currencies = DB::getInstance()->executeS($sql);

                    foreach ($currencies as $currency) {
                        $module->currency[] = $currency['id_currency'];
                    }

                    if (!get_class($module) == 'SimpleXMLElement') {
                        $module->group = [];
                    }

                    $sql = new DbQuery();
                    $sql->select('`id_group`');
                    $sql->from('module_group');
                    $sql->where('`id_module` = ' . (int) $module->id);
                    $sql->where('`id_shop` = ' . (int) $idCompany);

                    $groups = DB::getInstance()->executeS($sql);

                    foreach ($groups as $group) {
                        $module->group[] = $group['id_group'];
                    }

                    if (!get_class($module) == 'SimpleXMLElement') {
                        $module->reference = [];
                    }

                    $sql = new DbQuery();
                    $sql->select('`id_reference`');
                    $sql->from('module_carrier');
                    $sql->where('`id_module` = ' . (int) $module->id);
                    $sql->where('`id_shop` = ' . (int) $idCompany);

                    $carriers = Db::getInstance()->executeS($sql);

                    foreach ($carriers as $carrier) {
                        $module->reference[] = $carrier['id_reference'];
                    }

                } else {
                    $module->country = null;
                    $module->currency = null;
                    $module->group = null;
                    $module->reference = null;
                }

                $this->payment_modules[] = $module;
            }

        }

    }

    public function ajaxProcessOpenTargetController() {

        $this->controller_name = 'AdminModulePayment';

        $html = $this->renderView();

        $li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $html . '</div>';

        $result = [
            'li'   => $li,
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function initProcess() {

        if ($this->tabAccess['edit'] === '1') {

            if (Tools::isSubmit('submitModulecountry')) {
                $this->action = 'country';
            } else
            if (Tools::isSubmit('submitModulecurrency')) {
                $this->action = 'currency';
            } else
            if (Tools::isSubmit('submitModulegroup')) {
                $this->action = 'group';
            } else
            if (Tools::isSubmit('submitModulereference')) {
                $this->action = 'carrier';
            }

        } else {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }

    }

    public function ajaxProcessModulecurrency() {

        $this->submit_name = $this->l('Currency');
        $this->saveRestrictions('currency');
    }

    public function ajaxProcessModulecountry() {

        $this->submit_name = $this->l('Country');
        $this->saveRestrictions('country');
    }

    public function ajaxProcessModulegroup() {

        $this->submit_name = $this->l('Group');
        $this->saveRestrictions('group');
    }

    public function ajaxProcessModulereference() {

        $this->submit_name = $this->l('Carrier');
        $this->saveRestrictions('carrier');
    }

    /**
     * @param $type
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    protected function saveRestrictions($type) {

        // Delete type restrictions for active module.
        $modules = [];

        foreach ($this->payment_modules as $module) {

            if ($module->active) {
                $modules[] = (int) $module->id;
            }

        }

        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'module_' . bqSQL($type) . '`
            WHERE id_shop = ' . $this->context->company->id . '
            AND `id_module` IN (' . implode(', ', $modules) . ')'
        );

        if ($type === 'carrier') {
            // Fill the new restriction selection for active module.
            $values = [];

            foreach ($this->payment_modules as $module) {

                if ($module->active && isset($_POST[$module->name . '_reference'])) {

                    foreach ($_POST[$module->name . '_reference'] as $selected) {
                        $values[] = '(' . (int) $module->id . ', ' . (int) $this->context->company->id . ', ' . (int) $selected . ')';
                    }

                }

            }

            if (count($values)) {
                Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'module_carrier`
                (`id_module`, `id_shop`, `id_reference`)
                VALUES ' . implode(',', $values));
            }

        } else {
            // Fill the new restriction selection for active module.
            $values = [];

            foreach ($this->payment_modules as $module) {

                if ($module->active && isset($_POST[$module->name . '_' . $type . ''])) {

                    foreach ($_POST[$module->name . '_' . $type . ''] as $selected) {
                        $values[] = '(' . (int) $module->id . ', ' . (int) $this->context->company->id . ', ' . (int) $selected . ')';
                    }

                }

            }

            if (count($values)) {
                Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'module_' . bqSQL($type) . '`
                (`id_module`, `id_shop`, `id_' . bqSQL($type) . '`)
                VALUES ' . implode(',', $values));
            }

        }

        $result = [
            'success' => true,
            'message' => $this->l('Payment Module Seetings for') . ' ' . $this->submit_name . ' ' . $this->l('has been updated with success'),
        ];

        die(Tools::jsonEncode($result));

    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function renderView() {

        $this->toolbar_title = $this->l('Payment');
        unset($this->toolbar_btn['back']);

        

        $displayRestrictions = false;

        foreach ($this->payment_modules as $module) {

            if ($module->active) {
                $displayRestrictions = true;
                break;
            }

        }

        $lists = [
            [
                'items'      => Currency::getCurrencies(),
                'title'      => $this->l('Currency restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the currency, or currencies, for which you want the payment module(s) to be available.'),
                'name_id'    => 'currency',
                'identifier' => 'id_currency',
                'icon'       => 'icon-money',
            ],
            [
                'items'      => Group::getGroups($this->context->language->id),
                'title'      => $this->l('Group restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the customer group(s), for which you want the payment module(s) to be available.'),
                'name_id'    => 'group',
                'identifier' => 'id_group',
                'icon'       => 'icon-group',
            ],
            [
                'items'      => Country::getCountries($this->context->language->id),
                'title'      => $this->l('Country restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the country, or countries, for which you want the payment module(s) to be available.'),
                'name_id'    => 'country',
                'identifier' => 'id_country',
                'icon'       => 'icon-globe',
            ],
            [
                'items'      => Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS),
                'title'      => $this->l('Carrier restrictions'),
                'desc'       => $this->l('Please mark each checkbox for the carrier, or carrier, for which you want the payment module(s) to be available.'),
                'name_id'    => 'reference',
                'identifier' => 'id_reference',
                'icon'       => 'icon-truck',
            ],
        ];

        foreach ($lists as $keyList => $list) {
            $list['check_list'] = [];

            foreach ($list['items'] as $keyItem => $item) {
                $nameId = $list['name_id'];

                if ($nameId === 'currency'
                    && mb_strpos($list['items'][$keyItem]['name'], '(' . $list['items'][$keyItem]['iso_code'] . ')') === false) {
                    $list['items'][$keyItem]['name'] = sprintf($this->l('%1$s (%2$s)'), $list['items'][$keyItem]['name'], $list['items'][$keyItem]['iso_code']);
                }

                foreach ($this->payment_modules as $keyModule => $module) {

                    if (isset($module->$nameId) && in_array($item['id_' . $nameId], $module->$nameId)) {
                        $list['items'][$keyItem]['check_list'][$keyModule] = 'checked';
                    } else {
                        $list['items'][$keyItem]['check_list'][$keyModule] = 'unchecked';
                    }

                    if (!isset($module->$nameId)) {
                        $module->$nameId = [];
                    }

                    if (!isset($module->currencies_mode)) {
                        $module->currencies_mode = '';
                    }

                    if (!isset($module->currencies)) {
                        $module->currencies = '';
                    }

                    // If is a country list and the country is limited, remove it from list

                    if ($nameId == 'country'
                        && isset($module->limited_countries)
                        && !empty($module->limited_countries)
                        && is_array($module->limited_countries)
                        && !(in_array(strtoupper($item['iso_code']), array_map('strtoupper', $module->limited_countries)))) {
                        $list['items'][$keyItem]['check_list'][$keyModule] = null;
                    }

                }

            }

            // update list
            $lists[$keyList] = $list;
        }

        $this->tpl_view_vars = [
            'modules_list'         => $this->renderModulesList(),
            'display_restrictions' => $displayRestrictions,
            'lists'                => $lists,
            'ps_base_uri'          => __EPH_BASE_URI__,
            'payment_modules'      => $this->payment_modules,
            'url_submit'           => static::$currentIndex . '&token=' . $this->token,
            'shop_context'         => $shopContext,
            'controller'           => Tools::getValue('controller'),
            'tabScript'            => $this->generateTabScript(Tools::getValue('controller')),
        ];

        return parent::renderView();
    }

    /**
     * @return mixed|string
     *
     * @since   1.0.0
     * @version 1.8.1.0 Initial version
     */
    public function renderModulesList() {

        if ($this->getModulesList($this->filter_modules_list)) {
            $activeList = [];
            $unactiveList = [];

            foreach ($this->modules_list as $key => $module) {

                if (isset($module->description_full) && trim($module->description_full) != '') {
                    $module->show_quick_view = true;
                }

                if ($module->active) {
                    $activeList[] = $module;
                } else {
                    $unactiveList[] = $module;
                }

            }

            $helper = new Helper();
            $fetch = '';

            if (isset($activeList)) {
                $this->context->smarty->assign('panel_title', $this->l('Active payment'));
                $fetch = $helper->renderModulesList($activeList);
            }

            $this->context->smarty->assign(
                [
                    'panel_title' => $this->l('Recommended payment gateways'),
                    'view_all'    => true,
                ]
            );
            $fetch .= $helper->renderModulesList($unactiveList);

            return $fetch;
        }

    }

}
