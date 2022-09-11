<?php

/**
 * Class AdminSearchControllerCore
 *
 * @since 1.9.1.0
 */
class AdminSearchControllerCore extends AdminController {

    /**
     * AdminSearchControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        $this->query = trim(Tools::getValue('bo_query'));
        $searchType = (int) Tools::getValue('bo_search_type');
        /* Handle empty search field */

        if (!empty($this->query)) {

            if (!$searchType && strlen($this->query) > 1) {
                $this->searchFeatures();
            }

            /* Product research */

            if (!$searchType || $searchType == 1) {
                /* Handle product ID */

                if ($searchType == 1 && (int) $this->query && Validate::isUnsignedInt((int) $this->query)) {

                    if (($product = new Product($this->query)) && Validate::isLoadedObject($product)) {
                        Tools::redirectAdmin('index.php?tab=AdminProducts&id_product=' . (int) ($product->id) . '&token=' . Tools::getAdminTokenLite('AdminProducts'));
                    }

                }

                /* Normal catalog search */
                $this->searchCatalog();
            }

            /* Customer */

            if (!$searchType || $searchType == 2 || $searchType == 6) {

                if (!$searchType || $searchType == 2) {
                    /* Handle customer ID */

                    if ($searchType && (int) $this->query && Validate::isUnsignedInt((int) $this->query)) {

                        if (($customer = new Customer($this->query)) && Validate::isLoadedObject($customer)) {
                            Tools::redirectAdmin('index.php?tab=AdminCustomers&id_customer=' . (int) $customer->id . '&viewcustomer' . '&token=' . Tools::getAdminToken('AdminCustomers' . (int) EmployeeMenu::getIdFromClassName('AdminCustomers') . (int) $this->context->employee->id));
                        }

                    }

                    /* Normal customer search */
                    $this->searchCustomer();
                }

                if ($searchType == 6) {
                    $this->searchIP();
                }

            }

            /* Order */

            if (!$searchType || $searchType == 3) {

                if (Validate::isUnsignedInt(trim($this->query)) && (int) $this->query && ($order = new CustomerPieces((int) $this->query)) && Validate::isLoadedObject($order)) {

                    if ($searchType == 3) {
                        Tools::redirectAdmin('index.php?tab=AdminCustomerPieces&id_customer_piece=' . (int) $order->id . '&vieworder');
                    } else {
                        $row = get_object_vars($order);
                        $row['id_customer_piece'] = $row['id'];
                        $customer = $order->getCustomer();
                        $row['customer'] = $customer->firstname . ' ' . $customer->lastname;
                        $orderState = $order->getCurrentOrderState();
                        $row['osname'] = $orderState->name[$this->context->language->id];
                        $this->_list['orders'] = [$row];
                    }

                } else {
                    $orders = CustomerPieces::getByReference($this->query);
                    $nbOrders = count($orders);

                    if ($nbOrders == 1 && $searchType == 3) {
                        Tools::redirectAdmin('index.php?tab=AdminCustomerPieces&id_customer_piece=' . (int) $orders[0]->id . '&vieworder');
                    } else
                    if ($nbOrders) {
                        $this->_list['orders'] = [];

                        foreach ($orders as $order) {
                            /** @var Order $order */
                            $row = get_object_vars($order);
                            $row['id_customer_piece'] = $row['id'];
                            $customer = $order->getCustomer();
                            $row['customer'] = $customer->firstname . ' ' . $customer->lastname;
                            $orderState = $order->getCurrentOrderState();
                            $row['osname'] = $orderState->name[$this->context->language->id];
                            $this->_list['orders'][] = $row;
                        }

                    } else
                    if ($searchType == 3) {
                        $this->errors[] = Tools::displayError('No order was found with this ID:') . ' ' . Tools::htmlentitiesUTF8($this->query);
                    }

                }

            }

            /* Invoices */

            if ($searchType == 4) {

                if (Validate::isOrderInvoiceNumber($this->query) && ($invoice = OrderInvoice::getInvoiceByNumber($this->query))) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf') . '&submitAction=generateInvoicePDF&id_order=' . (int) ($invoice->id_order));
                }

                $this->errors[] = Tools::displayError('No invoice was found with this ID:') . ' ' . Tools::htmlentitiesUTF8($this->query);
            }

            /* Cart */

            if ($searchType == 5) {

                if ((int) $this->query && Validate::isUnsignedInt((int) $this->query) && ($cart = new Cart($this->query)) && Validate::isLoadedObject($cart)) {
                    Tools::redirectAdmin('index.php?tab=AdminCarts&id_cart=' . (int) ($cart->id) . '&viewcart' . '&token=' . Tools::getAdminToken('AdminCarts' . (int) (EmployeeMenu::getIdFromClassName('AdminCarts')) . (int) $this->context->employee->id));
                }

                $this->errors[] = Tools::displayError('No cart was found with this ID:') . ' ' . Tools::htmlentitiesUTF8($this->query);
            }

            /* IP */
            // 6 - but it is included in the customer block

            /* Module search */

            if (!$searchType || $searchType == 7) {
                /* Handle module name */

                if ($searchType == 7 && Validate::isModuleName($this->query) and ($module = Module::getInstanceByName($this->query)) && Validate::isLoadedObject($module)) {
                    Tools::redirectAdmin('index.php?tab=AdminModules&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst($module->name) . '&token=' . Tools::getAdminTokenLite('AdminModules'));
                }

                /* Normal catalog search */
                $this->searchModule();
            }

        }

        $this->display = 'view';
    }

    /**
     * Search a feature in all store
     *
     * @params string $query String to find in the catalog
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function searchFeatures() {

        $this->_list['features'] = [];

        global $_LANGADM;

        if ($_LANGADM === null) {
            return;
        }

        $tabs = [];
        $keyMatch = [];
        $result = Db::getInstance()->executeS(
            '
        SELECT class_name, name
        FROM ' . _DB_PREFIX_ . 'tab t
        INNER JOIN ' . _DB_PREFIX_ . 'tab_lang tl ON (t.id_tab = tl.id_tab AND tl.id_lang = ' . (int) $this->context->employee->id_lang . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'access a ON (a.id_tab = t.id_tab AND a.id_profile = ' . (int) $this->context->employee->id_profile . ')
        WHERE active = 1
        ' . ($this->context->employee->id_profile != 1 ? 'AND view = 1' : '') .
            (defined('_PS_HOST_MODE_') ? ' AND t.`hide_host_mode` = 0' : '')
        );

        foreach ($result as $row) {
            $tabs[strtolower($row['class_name'])] = $row['name'];
            $keyMatch[strtolower($row['class_name'])] = $row['class_name'];
        }

        foreach (AdminEmployeeMenu::$tabParenting as $key => $value) {
            $value = stripslashes($value);

            if (!isset($tabs[strtolower($key)]) || !isset($tabs[strtolower($value)])) {
                continue;
            }

            $tabs[strtolower($key)] = $tabs[strtolower($value)];
            $keyMatch[strtolower($key)] = $key;
        }

        $this->_list['features'] = [];

        foreach ($_LANGADM as $key => $value) {

            if (stripos($value, $this->query) !== false) {
                $value = stripslashes($value);
                $key = strtolower(substr($key, 0, -32));

                if (in_array($key, ['AdminTab', 'index'])) {
                    continue;
                }

                // if class name doesn't exists, just ignore it

                if (!isset($tabs[$key])) {
                    continue;
                }

                if (!isset($this->_list['features'][$tabs[$key]])) {
                    $this->_list['features'][$tabs[$key]] = [];
                }

                $this->_list['features'][$tabs[$key]][] = ['link' => $this->context->link->getAdminLink($keyMatch[$key]), 'value' => Tools::safeOutput($value)];
            }

        }

    }

    /**
     * Search a specific string in the products and categories
     *
     * @params string $query String to find in the catalog
     *
     *
     * @return void
     * @since 1.9.1.0
     *
     *
     */
    public function searchCatalog() {

        $this->_list['products'] = Product::searchByName($this->context->language->id, $this->query);
        $this->_list['categories'] = Category::searchByName($this->context->language->id, $this->query);
    }

    /**
     * Search a specific name in the customers
     *
     * @params string $query String to find in the catalog
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function searchCustomer() {

        $this->_list['customers'] = Customer::searchByName($this->query);
    }

    /**
     * Search by ip
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function searchIP() {

        if (!ip2long(trim($this->query))) {
            $this->errors[] = Tools::displayError('This is not a valid IP address:') . ' ' . Tools::htmlentitiesUTF8($this->query);

            return;
        }

        $this->_list['customers'] = Customer::searchByIp($this->query);
    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    public function searchModule() {

        $this->_list['modules'] = [];
        $allModules = Module::getModulesOnDisk(true, true, $this->context->employee->id);

        foreach ($allModules as $module) {

            if (stripos($module->name, $this->query) !== false || stripos($module->displayName, $this->query) !== false || stripos($module->description, $this->query) !== false) {
                $module->linkto = 'index.php?tab=AdminModules&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst($module->name) . '&token=' . Tools::getAdminTokenLite('AdminModules');
                $this->_list['modules'][] = $module;
            }

        }

    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJqueryPlugin('highlight');
        $this->addCSS(__PS_BASE_URI__ . _PS_JS_DIR_ . '' . $this->bo_theme . '/css/jquery-ui.css');
        $this->addJquery('3.4.1');
        $this->addJS(__PS_BASE_URI__ . _PS_JS_DIR_ . 'jquery-ui/jquery-ui.js');
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initToolbar() {}

    /**
     * Initialize toolbar title
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initToolbarTitle() {

        $this->toolbar_title = $this->l('Search results', null, null, false);
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderView() {

        $this->tpl_view_vars['query'] = Tools::safeOutput($this->query);
        $this->tpl_view_vars['show_toolbar'] = true;

        if (count($this->errors)) {
            return parent::renderView();
        } else {
            $nbResults = 0;

            foreach ($this->_list as $list) {

                if ($list != false) {
                    $nbResults += count($list);
                }

            }

            $this->tpl_view_vars['nb_results'] = $nbResults;

            if (isset($this->_list['features']) && count($this->_list['features'])) {
                $this->tpl_view_vars['features'] = $this->_list['features'];
            }

            if (isset($this->_list['categories']) && count($this->_list['categories'])) {
                $categories = [];

                foreach ($this->_list['categories'] as $category) {
                    $categories[] = getPath($this->context->link->getAdminLink('AdminCategories', false), $category['id_category']);
                }

                $this->tpl_view_vars['categories'] = $categories;
            }

            if (isset($this->_list['products']) && count($this->_list['products'])) {
                $view = '';
                $this->initProductList();

                $helper = new HelperList();
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->identifier = 'id_product';
                $helper->actions = ['edit'];
                $helper->show_toolbar = false;
                $helper->table = 'product';
                $helper->currentIndex = $this->context->link->getAdminLink('AdminProducts', false);

                $query = trim(Tools::getValue('bo_query'));
                $searchType = (int) Tools::getValue('bo_search_type');

                if ($query) {
                    $helper->currentIndex .= '&bo_query=' . $query . '&bo_search_type=' . $searchType;
                }

                $helper->token = Tools::getAdminTokenLite('AdminProducts');

                if ($this->_list['products']) {
                    $view = $helper->generateList($this->_list['products'], $this->fields_list['products']);
                }

                $this->tpl_view_vars['products'] = $view;
            }

            if (isset($this->_list['customers']) && count($this->_list['customers'])) {
                $view = '';
                $this->initCustomerList();

                $helper = new HelperList();
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->identifier = 'id_customer';
                $helper->actions = ['edit', 'view'];
                $helper->show_toolbar = false;
                $helper->table = 'customer';
                $helper->currentIndex = $this->context->link->getAdminLink('AdminCustomers', false);
                $helper->token = Tools::getAdminTokenLite('AdminCustomers');

                if ($this->_list['customers']) {

                    foreach ($this->_list['customers'] as $key => $val) {
                        $this->_list['customers'][$key]['orders'] = CustomerPieces::getCustomerNbOrders((int) $val['id_customer']);
                    }

                    $view = $helper->generateList($this->_list['customers'], $this->fields_list['customers']);
                }

                $this->tpl_view_vars['customers'] = $view;
            }

            if (isset($this->_list['orders']) && count($this->_list['orders'])) {
                $view = '';
                $this->initOrderList();

                $helper = new HelperList();
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->identifier = 'id_order';
                $helper->actions = ['view'];
                $helper->show_toolbar = false;
                $helper->table = 'order';
                $helper->currentIndex = $this->context->link->getAdminLink('AdminOrders', false);
                $helper->token = Tools::getAdminTokenLite('AdminOrders');

                if ($this->_list['orders']) {
                    $view = $helper->generateList($this->_list['orders'], $this->fields_list['orders']);
                }

                $this->tpl_view_vars['orders'] = $view;
            }

            if (isset($this->_list['modules']) && count($this->_list['modules'])) {
                $this->tpl_view_vars['modules'] = $this->_list['modules'];
            }

            if (isset($this->_list['addons']) && count($this->_list['addons'])) {
                $this->tpl_view_vars['addons'] = $this->_list['addons'];
            }

            return parent::renderView();
        }

    }

    /**
     * Extend this to remove buttons
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function initProductList() {

        $this->show_toolbar = false;
        $this->fields_list['products'] = [
            'id_product'        => ['title' => $this->l('ID'), 'width' => 25],
            'manufacturer_name' => ['title' => $this->l('Manufacturer'), 'align' => 'center', 'width' => 200],
            'reference'         => ['title' => $this->l('Reference'), 'align' => 'center', 'width' => 150],
            'name'              => ['title' => $this->l('Name'), 'width' => 'auto'],
            'price_tax_excl'    => ['title' => $this->l('Price (tax excl.)'), 'align' => 'right', 'type' => 'price', 'width' => 60],
            'price_tax_incl'    => ['title' => $this->l('Price (tax incl.)'), 'align' => 'right', 'type' => 'price', 'width' => 60],
            'active'            => ['title' => $this->l('Active'), 'width' => 70, 'active' => 'status', 'align' => 'center', 'type' => 'bool'],
        ];
    }

    /**
     * Initialize customer list
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function initCustomerList() {

        $gendersIcon = ['default' => 'unknown.gif'];
        $genders = [0 => $this->l('?')];

        foreach (Gender::getGenders() as $gender) {
            /** @var Gender $gender */
            $gendersIcon[$gender->id] = '../genders/' . (int) $gender->id . '.jpg';
            $genders[$gender->id] = $gender->name;
        }

        $this->fields_list['customers'] = ([
            'id_customer' => ['title' => $this->l('ID'), 'align' => 'center', 'width' => 25],
            'id_gender'   => ['title' => $this->l('Social title'), 'align' => 'center', 'icon' => $gendersIcon, 'list' => $genders, 'width' => 25],
            'firstname'   => ['title' => $this->l('First Name'), 'align' => 'left', 'width' => 150],
            'lastname'    => ['title' => $this->l('Name'), 'align' => 'left', 'width' => 'auto'],
            'email'       => ['title' => $this->l('Email address'), 'align' => 'left', 'width' => 250],
            'birthday'    => ['title' => $this->l('Birth date'), 'align' => 'center', 'type' => 'date', 'width' => 75],
            'date_add'    => ['title' => $this->l('Registration date'), 'align' => 'center', 'type' => 'date', 'width' => 75],
            'orders'      => ['title' => $this->l('Orders'), 'align' => 'center', 'width' => 50],
            'active'      => ['title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'width' => 25],
        ]);
    }

    /**
     * Initialize order list
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function initOrderList() {

        $this->fields_list['orders'] = [
            'reference'           => ['title' => $this->l('Reference'), 'align' => 'center', 'width' => 65],
            'id_order'            => ['title' => $this->l('ID'), 'align' => 'center', 'width' => 25],
            'customer'            => ['title' => $this->l('Customer')],
            'total_paid_tax_incl' => ['title' => $this->l('Total'), 'width' => 70, 'align' => 'right', 'type' => 'price', 'currency' => true],
            'payment'             => ['title' => $this->l('Payment'), 'width' => 100],
            'osname'              => ['title' => $this->l('Status'), 'width' => 280],
            'date_add'            => ['title' => $this->l('Date'), 'width' => 130, 'align' => 'right', 'type' => 'datetime'],
        ];
    }

}
