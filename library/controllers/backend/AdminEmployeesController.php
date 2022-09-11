<?php

/**
 * Class AdminEmployeesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminEmployeesControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    /** @var array profiles list */
    public $php_self = 'adminemployees';
    protected $profiles_array = [];

    /** @var array themes list */
    protected $themes = [];

    /** @var array tabs list */
    protected $tabs_list = [];

    /** @var bool $restrict_edition */
    protected $restrict_edition = false;
    // @codingStandardsIgnoreEnd

    /**
     * AdminEmployeesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'employee';
        $this->className = 'Employee';
        $this->lang = false;
        $this->context = Context::getContext();

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowActionSkipList('delete', [(int) $this->context->employee->id]);

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->default_image_height = 128;
        $this->default_image_width = 128;

        $this->fieldImageSettings = [
            'name' => 'image',
            'dir'  => 'e',
        ];
        /*
                                            check if there are more than one superAdmin
                                            if it's the case then we can delete a superAdmin
        */
        $superAdmin = Employee::countProfile(_PS_ADMIN_PROFILE_, true);

        if ($superAdmin == 1) {
            $superAdminArray = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_, true);
            $superAdminId = [];

            foreach ($superAdminArray as $key => $val) {
                $superAdminId[] = $val['id_employee'];
            }

            $this->addRowActionSkipList('delete', $superAdminId);
        }

        $profiles = Profile::getProfiles($this->context->language->id);

        if (!$profiles) {
            $this->errors[] = Tools::displayError('No profile.');
        } else {

            foreach ($profiles as $profile) {
                $this->profiles_array[$profile['name']] = $profile['name'];
            }

        }

        $this->fields_list = [
            'id_employee' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'firstname'   => ['title' => $this->l('First Name')],
            'lastname'    => ['title' => $this->l('Last Name')],
            'email'       => ['title' => $this->l('Email address')],
            'profile'     => [
                'title'      => $this->l('Profile'), 'type' => 'select', 'list' => $this->profiles_array,
                'filter_key' => 'pl!name', 'class'          => 'fixed-width-lg',
            ],
            'active'      => [
                'title' => $this->l('Active'), 'align' => 'center', 'active' => 'status',
                'type'  => 'bool', 'class'             => 'fixed-width-sm',
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Employee options'),
                'fields' => [
                    'PS_PASSWD_TIME_BACK'            => [
                        'title'      => $this->l('Password regeneration'),
                        'hint'       => $this->l('Security: Minimum time to wait between two password changes.'),
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => ' ' . $this->l('minutes'),
                        'visibility' => Shop::CONTEXT_ALL,
                    ],
                    'PS_BO_ALLOW_EMPLOYEE_FORM_LANG' => [
                        'title'      => $this->l('Memorize the language used in Admin panel forms'),
                        'hint'       => $this->l('Allow employees to select a specific language for the Admin panel form.'),
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'identifier' => 'value',
                        'list'       => [
                            '0' => ['value' => 0, 'name' => $this->l('No')],
                            '1' => [
                                'value' => 1, 'name' => $this->l('Yes'),
                            ],
                        ], 'visibility' => Shop::CONTEXT_ALL,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
        $rtl = $this->context->language->is_rtl ? '_rtl' : '';
        $path = _PS_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

        foreach (scandir($path) as $theme) {

            if ($theme[0] != '.' && is_dir($path . $theme) && (@filemtime($path . $theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-theme.css'))) {
                $this->themes[] = [
                    'id'   => $theme,
                    'name' => ucfirst($theme),
                ];

                // Add all available styles.

            }

        }

        $homeTab = EmployeeMenu::getInstanceFromClassName('AdminDashboard', $this->context->language->id);
        $this->tabs_list[$homeTab->id] = [
            'name'             => $homeTab->name,
            'id_employee_menu' => $homeTab->id,
            'children'         => [
                [
                    'id_employee_menu' => $homeTab->id,
                    'name'             => $homeTab->name,
                ],
            ],
        ];

        foreach (EmployeeMenu::getEmployeeMenus($this->context->language->id, 0) as $tab) {

            if (EmployeeMenu::checkTabRights($tab['id_employee_menu'])) {
                $this->tabs_list[$tab['id_employee_menu']] = $tab;

                foreach (EmployeeMenu::getEmployeeMenus($this->context->language->id, $tab['id_employee_menu']) as $children) {

                    if (EmployeeMenu::checkTabRights($children['id_employee_menu'])) {
                        $this->tabs_list[$tab['id_employee_menu']]['children'][] = $children;
                    }

                }

            }

        }

        parent::__construct();

        // An employee can edit its own profile

        if ($this->context->employee->id == Tools::getValue('id_employee')) {
            $this->tabAccess['view'] = '1';
            $this->restrict_edition = true;
            $this->tabAccess['edit'] = '1';
        }

    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJS(__PS_BASE_URI__ . $this->admin_webpath . '/' . $this->bo_theme . _PS_JS_DIR_ . 'vendor/jquery-passy.js');
        $this->addjQueryPlugin('validate');
        $this->addJS(_PS_JS_DIR_ . 'jquery/plugins/validate/localization/messages_' . $this->context->language->iso_code . '.js');
        $this->addCSS(__PS_BASE_URI__ . _PS_JS_DIR_ . '' . $this->bo_theme . '/css/jquery-ui.css');
        $this->addJquery('3.4.1');
        $this->addJS(__PS_BASE_URI__ . _PS_JS_DIR_ . 'jquery-ui/jquery-ui.js');
    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        parent::initPageHeaderToolbar();

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_employee'] = [
                'href' => static::$currentIndex . '&addemployee&token=' . $this->token,
                'desc' => $this->l('Add new employee', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        if ($this->display == 'edit') {
            $obj = $this->loadObject(true);

            if (Validate::isLoadedObject($obj)) {
                /** @var Employee $obj */
                array_pop($this->toolbar_title);
                $this->toolbar_title[] = sprintf($this->l('Edit: %1$s %2$s'), $obj->lastname, $obj->firstname);
                $this->page_header_toolbar_title = implode(
                    ' ' . Configuration::get('PS_NAVIGATION_PIPE') . ' ',
                    $this->toolbar_title
                );
            }

        }

    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderList() {

        $this->_select = 'pl.`name` AS profile';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'profile` p ON a.`id_profile` = p.`id_profile`
        LEFT JOIN `' . _DB_PREFIX_ . 'profile_lang` pl ON (pl.`id_profile` = p.`id_profile` AND pl.`id_lang` = '
        . (int) $this->context->language->id . ')';
        $this->_use_found_rows = false;

        return parent::renderList();
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        /** @var Employee $obj */

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $availableProfiles = Profile::getProfiles($this->context->language->id);

        if ($obj->id_profile == _PS_ADMIN_PROFILE_ && $this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {
            $this->errors[] = Tools::displayError('You cannot edit the SuperAdmin profile.');

            return parent::renderForm();
        }

        $image = _PS_EMPLOYEE_IMG_DIR_ . $obj->id . '.jpg';

        if (file_exists($image)) {
            $imageUrl = '<img src="../img/e/' . $obj->id . '.jpg" alt="" width="200">';
            $imageSize = filesize($image);
        } else {
            $image = _PS_EMPLOYEE_IMG_DIR_ . 'Unknown.png';
            $imageUrl = '<img src="../img/e/Unknown.png" alt="" width="200">';
            $imageSize = filesize($image);
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Employees'),
                'icon'  => 'icon-user',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'class'    => 'fixed-width-xl',
                    'label'    => $this->l('First Name'),
                    'name'     => 'firstname',
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'class'    => 'fixed-width-xl',
                    'label'    => $this->l('Last Name'),
                    'name'     => 'lastname',
                    'required' => true,
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Image'),
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'name'          => 'image',
                    'col'           => 6,
                    'value'         => true,
                ],
                [
                    'type'         => 'text',
                    'class'        => 'fixed-width-xxl',
                    'prefix'       => '<i class="icon-envelope-o"></i>',
                    'label'        => $this->l('Email address'),
                    'name'         => 'email',
                    'required'     => true,
                    'autocomplete' => false,
                ],
            ],
        ];

        if ($this->restrict_edition) {
            $this->fields_form['input'][] = [
                'type'  => 'change-password',
                'label' => $this->l('Password'),
                'name'  => 'passwd',
            ];
        } else {
            $this->fields_form['input'][] = [
                'type'  => 'password',
                'label' => $this->l('Password'),
                'hint'  => sprintf($this->l('Password should be at least %s characters long.'), Validate::ADMIN_PASSWORD_LENGTH),
                'name'  => 'passwd',
            ];
        }

        $this->fields_form['input'] = array_merge(
            $this->fields_form['input'], [
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Subscribe to ephenyx newsletter'),
                    'name'     => 'optin',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'optin_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'optin_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'hint'     => $this->l('ephenyx can provide you with guidance on a regular basis by sending you tips on how to optimize the management of your store which will help you grow your business. If you do not wish to receive these tips, you can disable this option.'),
                ],
                [
                    'type'    => 'default_tab',
                    'label'   => $this->l('Default page'),
                    'name'    => 'default_tab',
                    'hint'    => $this->l('This page will be displayed just after login.'),
                    'options' => $this->tabs_list,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Language'),
                    'name'    => 'id_lang',
                    //'required' => true,
                    'options' => [
                        'query' => Language::getLanguages(false),
                        'id'    => 'id_lang',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Theme'),
                    'name'    => 'bo_theme',
                    'options' => [
                        'query' => $this->themes,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],

                    'hint'    => $this->l('Back office theme.'),
                ],

            ]
        );

        if ((int) $this->tabAccess['edit'] && !$this->restrict_edition) {
            $this->fields_form['input'][] = [
                'type'     => 'switch',
                'label'    => $this->l('Active'),
                'name'     => 'active',
                'required' => false,
                'is_bool'  => true,
                'values'   => [
                    [
                        'id'    => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
                'hint'     => $this->l('Allow or disallow this employee to log into the Admin panel.'),
            ];

            // if employee is not SuperAdmin (id_profile = 1), don't make it possible to select the admin profile

            if ($this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {

                foreach ($availableProfiles as $i => $profile) {

                    if ($availableProfiles[$i]['id_profile'] == _PS_ADMIN_PROFILE_) {
                        unset($availableProfiles[$i]);
                        break;
                    }

                }

            }

            $this->fields_form['input'][] = [
                'type'     => 'select',
                'label'    => $this->l('Permission profile'),
                'name'     => 'id_profile',
                'required' => true,
                'options'  => [
                    'query'   => $availableProfiles,
                    'id'      => 'id_profile',
                    'name'    => 'name',
                    'default' => [
                        'value' => '',
                        'label' => $this->l('-- Choose --'),
                    ],
                ],
            ];

            if (Shop::isFeatureActive()) {
                $this->context->smarty->assign('_PS_ADMIN_PROFILE_', (int) _PS_ADMIN_PROFILE_);
                $this->fields_form['input'][] = [
                    'type'  => 'shop',
                    'label' => $this->l('Shop association'),
                    'hint'  => $this->l('Select the shops the employee is allowed to access.'),
                    'name'  => 'checkBoxShopAsso',
                ];
            }

        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $this->fields_value['passwd'] = false;
        $this->fields_value['bo_theme_css'] = $obj->bo_theme . '|' . $obj->bo_css;

        if (empty($obj->id)) {
            $this->fields_value['id_lang'] = $this->context->language->id;
        }

        return parent::renderForm();
    }

    /**
     * Process delete
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processDelete() {

        if (!$this->canModifyEmployee()) {
            return false;
        }

        return parent::processDelete();
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function canModifyEmployee() {

        if ($this->restrict_edition) {
            $this->errors[] = Tools::displayError('You cannot disable or delete your own account.');

            return false;
        }

        $employee = new Employee(Tools::getValue('id_employee'));

        if ($employee->isLastAdmin()) {
            $this->errors[] = Tools::displayError('You cannot disable or delete the administrator account.');

            return false;
        }

        // It is not possible to delete an employee if he manages warehouses
        $warehouses = Warehouse::getWarehousesByEmployee((int) Tools::getValue('id_employee'));

        if (Tools::isSubmit('deleteemployee') && count($warehouses) > 0) {
            $this->errors[] = Tools::displayError('You cannot delete this account because it manages warehouses. Check your warehouses first.');

            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function processStatus() {

        if (!$this->canModifyEmployee()) {
            return false;
        }

        parent::processStatus();
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function processSave() {

        $employee = new Employee((int) Tools::getValue('id_employee'));

        // If the employee is editing its own account

        if ($this->restrict_edition) {
            $currentPassword = trim(Tools::getValue('old_passwd'));

            if (Tools::getValue('passwd') && (empty($currentPassword) || !Validate::isPasswdAdmin($currentPassword) || !$employee->getByEmail($employee->email, $currentPassword))) {
                $this->errors[] = Tools::displayError('Your current password is invalid.');
            } else

            if (Tools::getValue('passwd') && (!Tools::getValue('passwd2') || Tools::getValue('passwd') !== Tools::getValue('passwd2'))) {
                $this->errors[] = Tools::displayError('The confirmation password does not match.');
            }

            $_POST['id_profile'] = $_GET['id_profile'] = $employee->id_profile;
            $_POST['active'] = $_GET['active'] = $employee->active;

            // Unset set shops

            foreach ($_POST as $postkey => $postvalue) {

                if (strstr($postkey, 'checkBoxShopAsso_' . $this->table) !== false) {
                    unset($_POST[$postkey]);
                }

            }

            foreach ($_GET as $postkey => $postvalue) {

                if (strstr($postkey, 'checkBoxShopAsso_' . $this->table) !== false) {
                    unset($_GET[$postkey]);
                }

            }

            // Add current shops associated to the employee
            $result = Shop::getShopById((int) $employee->id, $this->identifier, $this->table);

            foreach ($result as $row) {
                $key = 'checkBoxShopAsso_' . $this->table;

                if (!isset($_POST[$key])) {
                    $_POST[$key] = [];
                }

                if (!isset($_GET[$key])) {
                    $_GET[$key] = [];
                }

                $_POST[$key][$row['id_shop']] = 1;
                $_GET[$key][$row['id_shop']] = 1;
            }

        } else {
            $_POST['id_last_student_education'] = $employee->getLastElementsForNotify('student_education');
            $_POST['id_last_student_message'] = $employee->getLastElementsForNotify('student_message');
            $_POST['id_last_student'] = $employee->getLastElementsForNotify('student');
        }

        //if profile is super admin, manually fill checkBoxShopAsso_employee because in the form they are disabled.

        if ($_POST['id_profile'] == _PS_ADMIN_PROFILE_) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_shop`')
                    ->from('shop')
            );

            foreach ($result as $row) {
                $key = 'checkBoxShopAsso_' . $this->table;

                if (!isset($_POST[$key])) {
                    $_POST[$key] = [];
                }

                if (!isset($_GET[$key])) {
                    $_GET[$key] = [];
                }

                $_POST[$key][$row['id_shop']] = 1;
                $_GET[$key][$row['id_shop']] = 1;
            }

        }

        if ($employee->isLastAdmin()) {

            if (Tools::getValue('id_profile') != (int) _PS_ADMIN_PROFILE_) {
                $this->errors[] = Tools::displayError('You should have at least one employee in the administrator group.');

                return false;
            }

            if (Tools::getvalue('active') == 0) {
                $this->errors[] = Tools::displayError('You cannot disable or delete the administrator account.');

                return false;
            }

        }

        if (Tools::getValue('bo_theme_css')) {
            $boTheme = explode('|', Tools::getValue('bo_theme_css'));
            $_POST['bo_theme'] = $boTheme[0];

            if (!in_array($boTheme[0], scandir(_PS_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'themes'))) {
                $this->errors[] = Tools::displayError('Invalid theme');

                return false;
            }

            if (isset($boTheme[1])) {
                $_POST['bo_css'] = $boTheme[1];
            }

        }

        $assos = $this->getSelectedAssoShop($this->table);

        if (!$assos && $this->table = 'employee') {

            if (Shop::isFeatureActive() && _PS_ADMIN_PROFILE_ != $_POST['id_profile']) {
                $this->errors[] = Tools::displayError('The employee must be associated with at least one shop.');
            }

        }

        if (count($this->errors)) {
            return false;
        }

        return parent::processSave();
    }

    /**
     * @param bool $className
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function validateRules($className = false) {

        $employee = new Employee((int) Tools::getValue('id_employee'));

        if (!Validate::isLoadedObject($employee) && !Validate::isPasswd(Tools::getvalue('passwd'), Validate::ADMIN_PASSWORD_LENGTH)) {
            return !($this->errors[] = sprintf(
                Tools::displayError('The password must be at least %s characters long.'),
                Validate::ADMIN_PASSWORD_LENGTH
            ));
        }

        return parent::validateRules($className);
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        if ((Tools::isSubmit('submitBulkdeleteemployee') || Tools::isSubmit('submitBulkdisableSelectionemployee') || Tools::isSubmit('deleteemployee') || Tools::isSubmit('status') || Tools::isSubmit('statusemployee') || Tools::isSubmit('submitAddemployee')) && _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        return parent::postProcess();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initContent() {

        if ($this->context->employee->id == Tools::getValue('id_employee')) {
            $this->display = 'edit';
        }

        parent::initContent();
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @since 1.0.4
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as &$row) {
            $row['email'] = Tools::convertEmailFromIdn($row['email']);
        }

    }

    /**
     * Ajax process get tab by id profile
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessGetTabByIdProfile() {

        $idProfile = Tools::getValue('id_profile');
        $tabs = EmployeeMenu::getTabByIdProfile(0, $idProfile);
        $this->tabs_list = [];

        foreach ($tabs as $tab) {

            if (EmployeeMenu::checkTabRights($tab['id_employee_menu'])) {
                $this->tabs_list[$tab['id_employee_menu']] = $tab;

                foreach (EmployeeMenu::getTabByIdProfile($tab['id_employee_menu'], $idProfile) as $children) {

                    if (EmployeeMenu::checkTabRights($children['id_employee_menu'])) {
                        $this->tabs_list[$tab['id_employee_menu']]['children'][] = $children;
                    }

                }

            }

        }

        $this->ajaxDie(json_encode($this->tabs_list));
    }

    /**
     * Child validation
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function _childValidation() {

        if (!($obj = $this->loadObject(true))) {
            return false;
        }

        if (Tools::getValue('id_profile') == _PS_ADMIN_PROFILE_ && $this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {
            $this->errors[] = Tools::displayError('The provided profile is invalid');
        }

        $email = $this->getFieldValue($obj, 'email');

        if (Validate::isEmail($email) && Employee::employeeExists($email) && (!Tools::getValue('id_employee')
            || ($employee = new Employee((int) Tools::getValue('id_employee'))) && $employee->email != $email)
        ) {
            $this->errors[] = Tools::displayError('An account already exists for this email address:') . ' ' . $email;
        }

    }

    /**
     * Process bulk delete
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function processBulkDelete() {

        if (is_array($this->boxes) && !empty($this->boxes)) {

            foreach ($this->boxes as $idEmployee) {

                if ((int) $this->context->employee->id == (int) $idEmployee) {
                    $this->restrict_edition = true;

                    return $this->canModifyEmployee();
                }

            }

        }

        return parent::processBulkDelete();
    }

    /**
     * @param Employee $object
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function afterUpdate($object) {

        $res = parent::afterUpdate($object);
        // Update cookie if needed

        if (Tools::getValue('id_employee') == $this->context->employee->id && ($passwd = Tools::getValue('passwd'))
            && $object->passwd != $this->context->employee->passwd
        ) {
            $this->context->cookie->passwd = $this->context->employee->passwd = $object->passwd;

            if (Tools::getValue('passwd_send_email')) {
                $tpl = $context->smarty->createTemplate(_PS_MAIL_DIR_ . '/fr/employee_password.tpl');
                $tpl->assign([
                    'email'     => $object->email,
                    'lastname'  => $object->lastname,
                    'firstname' => $object->firstname,
                    'passwd'    => $passwd,

                ]);
                $postfields = [
                    'sender'      => [
                        'name'  => "Service  Administratif " . Configuration::get('PS_SHOP_NAME'),
                        'email' => 'no-reply@' . Configuration::get('PS_SHOP_URL'),
                    ],
                    'to'          => [
                        [
                            'name'  => $object->firstname . ' ' . $object->lastname,
                            'email' => $object->email,
                        ],
                    ],

                    'subject'     => 'Votre nouveau mot de passe',
                    "htmlContent" => $tpl->fetch(),
                ];

                $result = Tools::sendEmail($postfields);
            }

        }

        return $res;
    }

    /**
     * Ajax process form language
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function ajaxProcessFormLanguage() {

        $this->context->cookie->employee_form_lang = (int) Tools::getValue('form_language_id');

        if (!$this->context->cookie->write()) {
            die('Error while updating cookie.');
        }

        die('Form language updated.');
    }

    protected function ajaxProcessToggleMenu() {

        $this->context->cookie->collapse_menu = (int) Tools::getValue('collapse');
        $this->context->cookie->write();
    }

    protected function postImage($id) {

        if (isset($this->fieldImageSettings['name']) && isset($this->fieldImageSettings['dir'])) {

            if (!Validate::isInt(Tools::getValue('img_width')) || !Validate::isInt(Tools::getValue('img_height'))) {
                $this->errors[] = Tools::displayError('Width and height must be numeric values.');
            } else {

                if ((int) Tools::getValue('img_width') > 0 && (int) Tools::getValue('img_height') > 0) {
                    $width = (int) Tools::getValue('img_width');
                    $height = (int) Tools::getValue('img_height');
                } else {
                    $width = null;
                    $height = null;
                }

                return $this->uploadImage($id, $this->fieldImageSettings['name'], $this->fieldImageSettings['dir'] . '/', false, $width, $height);
            }

        }

        return !count($this->errors) ? true : false;
    }

    protected function afterImageUpload() {

        parent::afterImageUpload();

        if (($id_employee = (int) Tools::getValue('id_employy')) &&
            isset($_FILES) && count($_FILES) && file_exists(_PS_EMPLOYEE_IMG_DIR_ . $id_employee . '.jpg')) {
            $current_file = _PS_TMP_IMG_DIR_ . 'emplyee_mini_' . $id_employee . '_' . $this->context->shop->id . '.jpg';

            if (file_exists($current_file)) {
                unlink($current_file);
            }

        }

        return true;
    }

}
