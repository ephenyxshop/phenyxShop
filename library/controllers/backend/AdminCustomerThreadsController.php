<?php

/**
 * Class AdminCustomerThreadsControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCustomerThreadsControllerCore extends AdminController {

    public $php_self = 'admincustomerthreads';
    /**
     * AdminCustomerThreadsControllerCore constructor.
     *
     * @since 1.8.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'customer_thread';
        $this->className = 'CustomerThread';
        $this->publicName = $this->l('Relations Clients');
        $this->lang = false;

        $this->fields_options = [
            'contact' => [
                'title'  => $this->l('Contact options'),
                'fields' => [
                    'PS_CUSTOMER_SERVICE_FILE_UPLOAD' => [
                        'title' => $this->l('Allow file uploading'),
                        'hint'  => $this->l('Allow customers to upload files using the contact page.'),
                        'type'  => 'bool',
                    ],
                    'PS_CUSTOMER_SERVICE_SIGNATURE'   => [
                        'title' => $this->l('Default message'),
                        'hint'  => $this->l('Please fill out the message fields that appear by default when you answer a thread on the customer service page.'),
                        'type'  => 'textareaLang',
                        'lang'  => true,
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'general' => [
                'title'  => $this->l('Customer service options'),
                'fields' => [
                    'PS_SAV_IMAP_URL'                 => [
                        'title' => $this->l('IMAP URL'),
                        'hint'  => $this->l('URL for your IMAP server (ie.: mail.server.com).'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_PORT'                => [
                        'title'        => $this->l('IMAP port'),
                        'hint'         => $this->l('Port to use to connect to your IMAP server.'),
                        'type'         => 'text',
                        'defaultValue' => 143,
                    ],
                    'PS_SAV_IMAP_USER'                => [
                        'title' => $this->l('IMAP user'),
                        'hint'  => $this->l('User to use to connect to your IMAP server.'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_PWD'                 => [
                        'title' => $this->l('IMAP password'),
                        'hint'  => $this->l('Password to use to connect your IMAP server.'),
                        'type'  => 'text',
                    ],
                    'PS_SAV_IMAP_DELETE_MSG'          => [
                        'title' => $this->l('Delete messages'),
                        'hint'  => $this->l('Delete messages after synchronization. If you do not enable this option, the synchronization will take more time.'),
                        'type'  => 'bool',
                    ],
                    'PS_SAV_IMAP_CREATE_THREADS'      => [
                        'title' => $this->l('Create new threads'),
                        'hint'  => $this->l('Create new threads for unrecognized emails.'),
                        'type'  => 'bool',
                    ],
                    'PS_SAV_IMAP_OPT_NORSH'           => [
                        'title' => $this->l('IMAP options') . ' (/norsh)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not use RSH or SSH to establish a preauthenticated IMAP sessions.'),
                    ],
                    'PS_SAV_IMAP_OPT_SSL'             => [
                        'title' => $this->l('IMAP options') . ' (/ssl)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Use the Secure Socket Layer (TLS/SSL) to encrypt the session.'),
                    ],
                    'PS_SAV_IMAP_OPT_VALIDATE-CERT'   => [
                        'title' => $this->l('IMAP options') . ' (/validate-cert)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Validate certificates from the TLS/SSL server.'),
                    ],
                    'PS_SAV_IMAP_OPT_NOVALIDATE-CERT' => [
                        'title' => $this->l('IMAP options') . ' (/novalidate-cert)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not validate certificates from the TLS/SSL server. This is only needed if a server uses self-signed certificates.'),
                    ],
                    'PS_SAV_IMAP_OPT_TLS'             => [
                        'title' => $this->l('IMAP options') . ' (/tls)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Force use of start-TLS to encrypt the session, and reject connection to servers that do not support it.'),
                    ],
                    'PS_SAV_IMAP_OPT_NOTLS'           => [
                        'title' => $this->l('IMAP options') . ' (/notls)',
                        'type'  => 'bool',
                        'hint'  => $this->l('Do not use start-TLS to encrypt the session, even with servers that support it.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_CUSTOMERTHREAD_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_CUSTOMERTHREAD_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_CUSTOMERTHREAD_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_CUSTOMERTHREAD_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERTHREAD_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_CUSTOMERTHREAD_FIELDS', Tools::jsonEncode($this->getCustomerThreadFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERTHREAD_FIELDS'), true);
        }

        $this->extracss = $this->pushCSS([_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . 'css/sav.css']);

        $this->ajaxOptions = $this->generateCustomerThreadsTabs();

    }

    public function generateCustomerThreadsTabs() {

        $tabs = [];
        $tabs[$this->l('Contact options')] = [
            'key'     => 'contact',
            'content' => $this->generateOptions('contact'),
        ];
        $tabs[$this->l('Customer service options')] = [
            'key'     => 'general',
            'content' => $this->generateOptions('general'),
        ];
        return $tabs;
    }

    public function generateOptions($tab) {

        $fields_options = [
            $tab => $this->fields_options[$tab],
        ];

        if ($fields_options && is_array($fields_options)) {
            $this->tpl_option_vars['tab_id'] = $tab;
            $this->tpl_option_vars['controller'] = $this->controller_name;
            $this->tpl_option_vars['link'] = $this->context->link;

            if (Configuration::get('PS_SAV_IMAP_URL')
                && Configuration::get('PS_SAV_IMAP_PORT')
                && Configuration::get('PS_SAV_IMAP_USER')
                && Configuration::get('PS_SAV_IMAP_PWD')
            ) {
                $this->tpl_option_vars['use_sync'] = true;
            } else {
                $this->tpl_option_vars['use_sync'] = false;
            }

            $helper = new HelperOptions();
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->toolbar_btn = [
                'save' => [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ],
            ];
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($fields_options);

            return $options;
        }

        return '';
    }

    public function generateParaGridScript() {

        $contacts = '<div class="pq-theme"><select id="contactSelect"><option value="">' . $this->l('--Select--') . '</option>';

        foreach (Contact::getContacts($this->context->language->id) as $contact) {
            $contacts .= '<option value="' . $contact['id_contact'] . '">' . $contact['name'] . '</option>';
        }

        $contacts .= '</select></div>';

        $languages = '<div class="pq-theme"><select id="languageSelect"><option value="">' . $this->l('--Select--') . '</option>';

        foreach (Language::getLanguages() as $language) {
            $languages .= '<option value="' . $language['id_lang'] . '">' . $language['name'] . '</option>';
        }

        $languages .= '</select></div>';

        $showStatus = '<div class="pq-theme"><select id="showStatusSelect"><option value="">' . $this->l('--Select--') . '</option>';
        $showStatus .= '<option value="open" data-content="icon-circle text-success">' . $this->l('Open') . '</option>';
        $showStatus .= '<option value="closed" data-content="icon-circle text-danger">' . $this->l('Closed') . '</option>';
        $showStatus .= '<option value="pending1" data-content="icon-circle text-warning">' . $this->l('Pending 1') . '</option>';
        $showStatus .= '<option value="pending2" data-content="icon-circle text-warning">' . $this->l('Pending 2') . '</option>';
        $showStatus .= '</select></div>';

        $employees = '<div class="pq-theme"><select id="employeeSelect"><option value="">' . $this->l('--Select--') . '</option>';

        foreach (Employee::getEmployees() as $employee) {
            $employees .= '<option value="' . $employee['id_employee'] . '">' . $employee['firstname'] . ' ' . $employee['lastname'] . '</option>';
        }

        $employees .= '</select></div>';

        $showPrivate = '<div class="pq-theme"><select id="showPrivateSelect"><option value="">' . $this->l('--Select--') . '</option>';
        $showPrivate .= '<option value="0" data-content="icon-remove">' . $this->l('No') . '</option>';
        $showPrivate .= '<option value="1" data-content="icon-check">' . $this->l('Yes') . '</option>';
        $showPrivate .= '</select></div>';

        $this->paramExtraFontcion = ['function buildContactFilter(){
            var contactSelect = $(\'#contactSelector\').parent().parent();
            $(contactSelect).empty();
            $(contactSelect).append(\'' . $contacts . '\');
            $(\'#contactSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'id_contact\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            var languagesSelect = $(\'#languageSelector\').parent().parent();
            $(languagesSelect).empty();
            $(languagesSelect).append(\'' . $languages . '\');
            $(\'#languageSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'id_lang\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            var showStatusSelect = $(\'#showStatusSelector\').parent().parent();
            $(showStatusSelect).empty();
            $(showStatusSelect).append(\'' . $showStatus . '\');
            $(\'#showStatusSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'status\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            var employeeSelect = $(\'#employeeSelector\').parent().parent();
            $(employeeSelect).empty();
            $(employeeSelect).append(\'' . $employees . '\');
            $(\'#employeeSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'id_employee\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            var showPrivateSelect = $(\'#showPrivateSelector\').parent().parent();
            $(showPrivateSelect).empty();
            $(showPrivateSelect).append(\'' . $showPrivate . '\');
            $(\'#showPrivateSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'private\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            }', ];

        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

        $this->paramTitle = '\'' . $this->l('Gestion des messages clients') . '\'';

        $this->paramContextMenu = [
            '#grid_' . $this->controller_name => [
                'selector'  => '\'.pq-body-outer .pq-grid-row\'',
                'animation' => [
                    'duration' => 250,
                    'show'     => '\'fadeIn\'',
                    'hide'     => '\'fadeOut\'',
                ],
                'build'     => 'function($triggerElement, e){

                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->className . '.getRowData( {rowIndx: rowIndex} );
                selected = selgrid' . $this->className . '.getSelection().length;
                var dataLenght = grid' . $this->className . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {

                        "edit": {
                            name : \'' . $this->l('Visualiser le message  ') . '\',
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_customer_thread)
                            }
                        },


                        "delete": {
                            name: \'' . $this->l('Supprimer le message :') . '\'' . '+rowData.lastname,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                              deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un message", "Etes vous sure de vouloir supprimer ce message ?", "Oui", "Annuler",rowData.id_customer_thread);
                            }
                        },


                    },
                };
            }',
            ]];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getCustomerThreadRequest() {

        $customerthreads = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, CONCAT(c.`firstname`," ",c.`lastname`) as `customer`, CONCAT(LEFT(e.`firstname`, 1),". ",e.`lastname`) AS `employee`, cl.`name` as `contact`, l.`name` as `language`, group_concat(message) as `messages`, cm.`private`, cm.`id_employee`')
                ->from('customer_thread', 'a')
                ->leftJoin('customer', 'c', 'c.`id_customer` = a.`id_customer`')
                ->leftJoin('customer_message', 'cm', 'cm.`id_customer_thread` = a.`id_customer_thread`')
                ->leftJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee` AND cm.`id_customer_thread` = a.`id_customer_thread`')
                ->leftJoin('lang', 'l', 'l.`id_lang` = a.`id_lang`')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = a.`id_contact` AND cl.`id_lang` = ' . (int) $this->context->language->id)
                ->groupBy('cm.id_customer_thread')
                ->orderBy('a.`date_upd` DESC')
        );
        $customerthreadLink = $this->context->link->getAdminLink($this->controller_name);

        foreach ($customerthreads as &$customerthread) {

            $customerthread['messages'] = mb_substr($customerthread['messages'], 0, 40);

            if (empty($customerthread['customer'])) {
                $customerthread['customer'] = '--';
            }

            if (empty($customerthread['employee'])) {
                $customerthread['employee'] = '--';
            }

            switch ($customerthread['status']) {
            case 'open':
                $customerthread['showStatus'] = '<i class="icon-circle text-success"></i>';
                break;
            case 'closed':
                $customerthread['showStatus'] = '<i class="icon-circle text-danger"></i>';
                break;
            case 'pending1':
            case 'pending2':
                $customerthread['showStatus'] = '<i class="icon-circle text-warning"></i>';
                break;
            }

            switch ($customerthread['private']) {
            case '0':
                $customerthread['showPrivate'] = '<i class="fa fa-trash"></i>';
                break;
            default:
                $customerthread['showStatus'] = '<i class="fa fa-check"></i>';
                break;
            }

        }

        return $customerthreads;

    }

    public function ajaxProcessgetCustomerThreadRequest() {

        die(Tools::jsonEncode($this->getCustomerThreadRequest()));

    }

    public function getCustomerThreadFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'width'      => 50,
                'dataIndx'   => 'id_customer_thread',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 50,
                'dataIndx'   => 'viewLink',
                'dataType'   => 'string',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'addLink',
                'dataType'   => 'string',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Customer'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'customer',
                'halign'   => 'HORIZONTAL_LEFT',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],

                ],
            ],
            [
                'title'    => $this->l('Email'),
                'width'    => 200,
                'dataIndx' => 'email',
                'dataType' => 'string',
                'align'    => 'left',
                'halign'   => 'HORIZONTAL_LEFT',
                'editable' => false,
                'hidden'   => false,
                'filter'   => [

                    'crules' => [['condition' => "begin"]],
                ],

            ],
            [

                'dataIndx'   => 'id_contact',
                'hidden'     => true,
                'hiddenable' => 'no',
                'dataType'   => 'integer',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],

                ],
            ],
            [
                'title'    => $this->l('Type'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'contact',
                'halign'   => 'HORIZONTAL_LEFT',
                'dataType' => 'string',
                'filter'   => [
                    'attr'   => "id=\"contactSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'dataIndx'   => 'id_lang',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'title'    => $this->l('Language'),
                'width'    => 200,
                'dataIndx' => 'language',
                'dataType' => 'string',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"languageSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'dataIndx'   => 'status',
                'dataType'   => 'string',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],
            [
                'title'    => $this->l('Status'),
                'width'    => 150,
                'dataIndx' => 'showStatus',
                'align'    => 'center',
                'dataType' => 'html',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"showStatusSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'hidden'     => true,
                'hiddenable' => 'no',
                'dataIndx'   => 'id_employee',
                'dataType'   => 'integer',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'title'    => $this->l('Employee'),
                'width'    => 200,
                'dataIndx' => 'employee',
                'dataType' => 'string',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"employeeSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'title'    => $this->l('Messages'),
                'width'    => 200,
                'dataIndx' => 'messages',
                'dataType' => 'string',
                'editable' => false,
            ],
            [
                'dataIndx'   => 'private',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],
            ],

            [
                'title'    => $this->l('Private'),
                'width'    => 150,
                'exWidth'  => 20,
                'dataIndx' => 'showPrivate',
                'align'    => 'center',
                'dataType' => 'html',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"showPrivateSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'title'    => $this->l('Last message'),
                'minWidth' => 150,
                'exWidth'  => 20,
                'dataIndx' => 'date_upd',
                'cls'      => 'rangeDate',
                'align'    => 'center',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,
                'filter'   => [
                    'crules' => [['condition' => "between"]],
                ],
            ],
        ];

    }

    public function ajaxProcessgetCustomerThreadFields() {

        die(EmployeeConfiguration::get('EXPERT_CUSTOMERTHREAD_FIELDS'));
    }

    public function ajaxProcessEditObject() {

        if ($this->tabAccess['edit'] == 1) {

            $idObject = Tools::getValue('idObject');

            $_GET[$this->identifier] = $idObject;
            $_GET['view' . $this->table] = "";

            $html = $this->renderView();
            $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Fil de Discussion</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
            $html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $html . '</div>';

            $result = [
                'success' => true,
                'li'      => $li,
                'html'    => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Votre profile administratif ne vous permet pas d‘éditer cette objet',
            ];
        }

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessUpdateCustomerThreads() {

        $languages = Language::getLanguages(false);

        foreach ($this->fields_options as $key => $categoryData) {

            if (!isset($categoryData['fields'])) {

                continue;
            }

            $fields = $categoryData['fields'];

            foreach ($fields as $field => $values) {

                if (isset($values['type']) && $values['type'] == 'selectLang') {

                    foreach ($languages as $lang) {

                        if (Tools::getValue($field . '_' . strtoupper($lang['iso_code']))) {
                            $fields[$field . '_' . strtoupper($lang['iso_code'])] = [
                                'type'       => 'select',
                                'cast'       => 'strval',
                                'identifier' => 'mode',
                                'list'       => $values['list'],
                            ];
                        }

                    }

                }

            }

            // Validate fields

            foreach ($fields as $field => $values) {
                // We don't validate fields with no visibility

                if (!$hideMultishopCheckbox && Shop::isFeatureActive() && isset($values['visibility']) && $values['visibility'] > Shop::getContext()) {
                    continue;
                }

                // Check if field is required

                if ((!Shop::isFeatureActive() && isset($values['required']) && $values['required'])
                    || (Shop::isFeatureActive() && isset($_POST['multishopOverrideOption'][$field]) && isset($values['required']) && $values['required'])
                ) {

                    if (isset($values['type']) && $values['type'] == 'textLang') {

                        foreach ($languages as $language) {

                            if (($value = Tools::getValue($field . '_' . $language['id_lang'])) == false && (string) $value != '0') {
                                $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                            }

                        }

                    } else

                    if (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                        $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $values['title']);
                    }

                }

                // Check field validator

                if (isset($values['type']) && $values['type'] == 'textLang') {

                    foreach ($languages as $language) {

                        if (Tools::getValue($field . '_' . $language['id_lang']) && isset($values['validation'])) {
                            $valuesValidation = $values['validation'];

                            if (!Validate::$valuesValidation(Tools::getValue($field . '_' . $language['id_lang']))) {
                                $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                            }

                        }

                    }

                } else

                if (Tools::getValue($field) && isset($values['validation'])) {
                    $valuesValidation = $values['validation'];

                    if (!Validate::$valuesValidation(Tools::getValue($field))) {
                        $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $values['title']);
                    }

                }

                // Set default value

                if (Tools::getValue($field) === false && isset($values['default'])) {
                    $_POST[$field] = $values['default'];
                }

            }

            if (!count($this->errors)) {

                foreach ($fields as $key => $options) {

                    if (Shop::isFeatureActive() && isset($options['visibility']) && $options['visibility'] > Shop::getContext()) {
                        continue;
                    }

                    if (!$hideMultishopCheckbox && Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && empty($options['no_multishop_checkbox']) && empty($_POST['multishopOverrideOption'][$key])) {
                        Configuration::deleteFromContext($key);
                        continue;
                    }

                    // check if a method updateOptionFieldName is available
                    $methodName = 'updateOption' . Tools::toCamelCase($key, true);

                    if (method_exists($this, $methodName)) {
                        $this->$methodName(Tools::getValue($key));
                    } else

                    if (isset($options['type']) && in_array($options['type'], ['textLang', 'textareaLang'])) {
                        $list = [];

                        foreach ($languages as $language) {
                            $keyLang = Tools::getValue($key . '_' . $language['id_lang']);
                            $val = (isset($options['cast']) ? $options['cast']($keyLang) : $keyLang);

                            if ($this->validateField($val, $options)) {

                                if (Validate::isCleanHtml($val)) {
                                    $list[$language['id_lang']] = $val;
                                } else {
                                    $this->errors[] = Tools::displayError('Can not add configuration ' . $key . ' for lang ' . Language::getIsoById((int) $language['id_lang']));
                                }

                            }

                        }

                        Configuration::updateValue($key, $list, isset($values['validation']) && isset($options['validation']) && $options['validation'] == 'isCleanHtml' ? true : false);
                    } else

                    if (isset($options['json']) && $options['json']) {
                        Configuration::updateValue($key, implode(",", Tools::getValue($key)));
                    } else {
                        $val = (isset($options['cast']) ? $options['cast'](Tools::getValue($key)) : Tools::getValue($key));

                        if ($this->validateField($val, $options)) {

                            if ($options['type'] === 'code') {
                                Configuration::updateValue($key, $val, true);
                            } else

                            if (Validate::isCleanHtml($val)) {
                                Configuration::updateValue($key, $val);
                            } else {
                                $this->errors[] = Tools::displayError('Can not add configuration ' . $key);
                            }

                        }

                    }

                }

            }

        }

        $result = [
            "success" => true,
            "message" => "Les options ont été mises à jour avec succès",
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * Call the IMAP synchronization during the render process.
     */
    public function renderProcessSyncImap() {

        // To avoid an error if the IMAP isn't configured, we check the configuration here, like during
        // the synchronization. All parameters will exists.

        if (!(Configuration::get('PS_SAV_IMAP_URL')
            || Configuration::get('PS_SAV_IMAP_PORT')
            || Configuration::get('PS_SAV_IMAP_USER')
            || Configuration::get('PS_SAV_IMAP_PWD'))
        ) {
            return;
        }

        // Executes the IMAP synchronization.
        $syncErrors = $this->syncImap();

        // Show the errors.

        if (isset($syncErrors['hasError']) && $syncErrors['hasError']) {

            if (isset($syncErrors['errors'])) {

                foreach ($syncErrors['errors'] as &$error) {
                    $this->displayWarning($error);
                }

            }

        }

    }

    /**
     * Imap synchronization method.
     *
     * @return array Errors list.
     */
    public function syncImap() {

        if (!($url = Configuration::get('PS_SAV_IMAP_URL'))
            || !($port = Configuration::get('PS_SAV_IMAP_PORT'))
            || !($user = Configuration::get('PS_SAV_IMAP_USER'))
            || !($password = Configuration::get('PS_SAV_IMAP_PWD'))
        ) {
            return ['hasError' => true, 'errors' => ['IMAP configuration is not correct']];
        }

        $conf = Configuration::getMultiple(
            [
                'PS_SAV_IMAP_OPT_NORSH',
                'PS_SAV_IMAP_OPT_SSL',
                'PS_SAV_IMAP_OPT_VALIDATE-CERT',
                'PS_SAV_IMAP_OPT_NOVALIDATE-CERT',
                'PS_SAV_IMAP_OPT_TLS',
                'PS_SAV_IMAP_OPT_NOTLS',
            ]
        );

        $confStr = '';

        if ($conf['PS_SAV_IMAP_OPT_NORSH']) {
            $confStr .= '/norsh';
        }

        if ($conf['PS_SAV_IMAP_OPT_SSL']) {
            $confStr .= '/ssl';
        }

        if ($conf['PS_SAV_IMAP_OPT_VALIDATE-CERT']) {
            $confStr .= '/validate-cert';
        }

        if ($conf['PS_SAV_IMAP_OPT_NOVALIDATE-CERT']) {
            $confStr .= '/novalidate-cert';
        }

        if ($conf['PS_SAV_IMAP_OPT_TLS']) {
            $confStr .= '/tls';
        }

        if ($conf['PS_SAV_IMAP_OPT_NOTLS']) {
            $confStr .= '/notls';
        }

        if (!function_exists('imap_open')) {
            return ['hasError' => true, 'errors' => ['imap is not installed on this server']];
        }

        $mbox = @imap_open('{' . $url . ':' . $port . $confStr . '}', $user, $password);

        //checks if there is no error when connecting imap server
        $errors = imap_errors();

        if (is_array($errors)) {
            $errors = array_unique($errors);
        }

        $strErrors = '';
        $strErrorDelete = '';

        if (count($errors) && is_array($errors)) {
            $strErrors = '';

            foreach ($errors as $error) {
                $strErrors .= $error . ', ';
            }

            $strErrors = rtrim(trim($strErrors), ',');
        }

        //checks if imap connexion is active

        if (!$mbox) {
            return ['hasError' => true, 'errors' => ['Cannot connect to the mailbox :<br />' . ($strErrors)]];
        }

        //Returns information about the current mailbox. Returns FALSE on failure.
        $check = imap_check($mbox);

        if (!$check) {
            return ['hasError' => true, 'errors' => ['Fail to get information about the current mailbox']];
        }

        if ($check->Nmsgs == 0) {
            return ['hasError' => true, 'errors' => ['NO message to sync']];
        }

        $result = imap_fetch_overview($mbox, "1:{$check->Nmsgs}", 0);

        foreach ($result as $overview) {
            //check if message exist in database

            if (isset($overview->subject)) {
                $subject = $overview->subject;
            } else {
                $subject = '';
            }

            //Creating an md5 to check if message has been allready processed
            $md5 = md5($overview->date . $overview->from . $subject . $overview->msgno);
            $exist = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('`md5_header`')
                    ->from('customer_message_sync_imap')
                    ->where('`md5_header` = \'' . pSQL($md5) . '\'')
            );

            if ($exist) {

                if (Configuration::get('PS_SAV_IMAP_DELETE_MSG')) {

                    if (!imap_delete($mbox, $overview->msgno)) {
                        $strErrorDelete = ', Fail to delete message';
                    }

                }

            } else {
                //check if subject has id_order
                preg_match('/\#ct([0-9]*)/', $subject, $matches1);
                preg_match('/\#tc([0-9-a-z-A-Z]*)/', $subject, $matches2);
                $matchFound = false;

                if (isset($matches1[1]) && isset($matches2[1])) {
                    $matchFound = true;
                }

                $newCt = (Configuration::get('PS_SAV_IMAP_CREATE_THREADS') && !$matchFound && (strpos($subject, '[no_sync]') == false));

                if ($matchFound || $newCt) {

                    if ($newCt) {

                        if (!preg_match('/<(' . Tools::cleanNonUnicodeSupport('[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+') . ')>/', $overview->from, $result)
                            || !Validate::isEmail($from = Tools::convertEmailToIdn($result[1]))
                        ) {
                            continue;
                        }

                        // we want to assign unrecognized mails to the right contact category
                        $contacts = Contact::getContacts($this->context->language->id);

                        if (!$contacts) {
                            continue;
                        }

                        foreach ($contacts as $contact) {

                            if (strpos($overview->to, $contact['email']) !== false) {
                                $idContact = $contact['id_contact'];
                            }

                        }

                        if (!isset($idContact)) {
                            // if not use the default contact category
                            $idContact = $contacts[0]['id_contact'];
                        }

                        $customer = new Customer();
                        $client = $customer->getByEmail($from); //check if we already have a customer with this email
                        $ct = new CustomerThread();

                        if (isset($client->id)) {
                            //if mail is owned by a customer assign to him
                            $ct->id_customer = $client->id;
                        }

                        $ct->email = $from;
                        $ct->id_contact = $idContact;
                        $ct->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
                        $ct->id_shop = $this->context->shop->id; //new customer threads for unrecognized mails are not shown without shop id
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    } else {
                        $ct = new CustomerThread((int) $matches1[1]);
                    }

                    //check if order exist in database

                    if (Validate::isLoadedObject($ct) && ((isset($matches2[1]) && $ct->token == $matches2[1]) || $newCt)) {
                        $message = imap_fetchbody($mbox, $overview->msgno, 1);

                        if (base64_encode(base64_decode($message)) === $message) {
                            $message = base64_decode($message);
                        }

                        $message = quoted_printable_decode($message);
                        $message = utf8_encode($message);
                        $message = quoted_printable_decode($message);
                        $message = nl2br($message);
                        $message = mb_substr($message, 0, (int) CustomerMessage::$definition['fields']['message']['size']);

                        $cm = new CustomerMessage();
                        $cm->id_customer_thread = $ct->id;

                        if (empty($message) || !Validate::isCleanHtml($message)) {
                            $strErrors .= Tools::displayError(sprintf('Invalid Message Content for subject: %1s', $subject));
                        } else {
                            $cm->message = $message;
                            $cm->add();
                        }

                    }

                }

                Db::getInstance()->insert(
                    'customer_message_sync_imap',
                    [
                        'md5_header' => pSQL($md5),
                    ]
                );
            }

        }

        imap_expunge($mbox);
        imap_close($mbox);

        if ($strErrors . $strErrorDelete) {
            return ['hasError' => true, 'errors' => [$strErrors . $strErrorDelete]];
        } else {
            return ['hasError' => false, 'errors' => ''];
        }

    }

    /**
     * @param mixed    $value
     * @param Customer $customer
     *
     * @return string
     *
     * @since 1.8.1.0
     */
    public function printOptinIcon($value, $customer) {

        return ($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>');
    }

    /**
     * @return bool
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        if ($idCustomerThread = (int) Tools::getValue('id_customer_thread')) {

            if (($idContact = (int) Tools::getValue('id_contact'))) {
                Db::getInstance()->execute(
                    '
                    UPDATE ' . _DB_PREFIX_ . 'customer_thread
                    SET id_contact = ' . (int) $idContact . '
                    WHERE id_customer_thread = ' . (int) $idCustomerThread
                );
            }

            if ($idStatus = (int) Tools::getValue('setstatus')) {
                $statusArray = [1 => 'open', 2 => 'closed', 3 => 'pending1', 4 => 'pending2'];
                Db::getInstance()->execute(
                    '
                    UPDATE ' . _DB_PREFIX_ . 'customer_thread
                    SET status = "' . $statusArray[$idStatus] . '"
                    WHERE id_customer_thread = ' . (int) $idCustomerThread . ' LIMIT 1
                '
                );
            }

            if (isset($_POST['id_employee_forward'])) {
                $messages = Db::getInstance()->getRow(
                    '
                    SELECT ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name,
                        CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname
                    FROM ' . _DB_PREFIX_ . 'customer_thread ct
                    LEFT JOIN ' . _DB_PREFIX_ . 'customer_message cm
                        ON (ct.id_customer_thread = cm.id_customer_thread)
                    LEFT JOIN ' . _DB_PREFIX_ . 'contact_lang cl
                        ON (cl.id_contact = ct.id_contact AND cl.id_lang = ' . (int) $this->context->language->id . ')
                    LEFT OUTER JOIN ' . _DB_PREFIX_ . 'employee e
                        ON e.id_employee = cm.id_employee
                    LEFT OUTER JOIN ' . _DB_PREFIX_ . 'customer c
                        ON (c.email = ct.email)
                    WHERE ct.id_customer_thread = ' . (int) Tools::getValue('id_customer_thread') . '
                    ORDER BY cm.date_add DESC
                '
                );
                $output = $this->displayMessage($messages, true, (int) Tools::getValue('id_employee_forward'));
                $cm = new CustomerMessage();
                $cm->id_employee = (int) $this->context->employee->id;
                $cm->id_customer_thread = (int) Tools::getValue('id_customer_thread');
                $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                $currentEmployee = $this->context->employee;
                $idEmployee = (int) Tools::getValue('id_employee_forward');
                $employee = new Employee($idEmployee);
                $email = Tools::convertEmailToIdn(Tools::getValue('email'));
                $message = Tools::getValue('message_forward');

                if (($error = $cm->validateField('message', $message, null, [], true)) !== true) {
                    $this->errors[] = $error;
                } else

                if ($idEmployee && $employee && Validate::isLoadedObject($employee)) {
                    $params = [
                        '{messages}'  => stripslashes($output),
                        '{employee}'  => $currentEmployee->firstname . ' ' . $currentEmployee->lastname,
                        '{comment}'   => stripslashes(Tools::nl2br($_POST['message_forward'])),
                        '{firstname}' => $employee->firstname,
                        '{lastname}'  => $employee->lastname,
                    ];

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params,
                        $employee->email,
                        $employee->firstname . ' ' . $employee->lastname,
                        $currentEmployee->email,
                        $currentEmployee->firstname . ' ' . $currentEmployee->lastname,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true
                    )) {
                        $cm->private = 1;
                        $cm->message = $this->l('Message forwarded to') . ' ' . $employee->firstname . ' ' . $employee->lastname . "\n" . $this->l('Comment:') . ' ' . $message;
                        $cm->add();
                    }

                } else

                if ($email && Validate::isEmail($email)) {
                    $params = [
                        '{messages}'  => Tools::nl2br(stripslashes($output)),
                        '{employee}'  => $currentEmployee->firstname . ' ' . $currentEmployee->lastname,
                        '{comment}'   => stripslashes($_POST['message_forward']),
                        '{firstname}' => '',
                        '{lastname}'  => '',
                    ];

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params,
                        $email,
                        null,
                        $currentEmployee->email,
                        $currentEmployee->firstname . ' ' . $currentEmployee->lastname,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true
                    )) {
                        $cm->message = $this->l('Message forwarded to') . ' ' . Tools::convertEmailFromIdn($email) . "\n" . $this->l('Comment:') . ' ' . $message;
                        $cm->add();
                    }

                } else {
                    $this->errors[] = '<div class="alert error">' . Tools::displayError('The email address is invalid.') . '</div>';
                }

            }

            if (Tools::isSubmit('submitReply')) {
                $ct = new CustomerThread($idCustomerThread);

                ShopUrl::cacheMainDomainForShop((int) $ct->id_shop);

                $cm = new CustomerMessage();
                $cm->id_employee = (int) $this->context->employee->id;
                $cm->id_customer_thread = $ct->id;
                $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                $cm->message = Tools::getValue('reply_message');

                if (($error = $cm->validateField('message', $cm->message, null, [], true)) !== true) {
                    $this->errors[] = $error;
                } else

                if (isset($_FILES) && !empty($_FILES['joinFile']['name']) && $_FILES['joinFile']['error'] != 0) {
                    $this->errors[] = Tools::displayError('An error occurred during the file upload process.');
                } else

                if ($cm->add()) {
                    $fileAttachment = null;

                    if (!empty($_FILES['joinFile']['name'])) {
                        $fileAttachment['content'] = file_get_contents($_FILES['joinFile']['tmp_name']);
                        $fileAttachment['name'] = $_FILES['joinFile']['name'];
                        $fileAttachment['mime'] = $_FILES['joinFile']['type'];
                    }

                    $customer = new Customer($ct->id_customer);
                    $params = [
                        '{reply}'     => Tools::nl2br(Tools::getValue('reply_message')),
                        '{link}'      => Tools::url(
                            $this->context->link->getPageLink('contact', true, null, null, false, $ct->id_shop),
                            'id_customer_thread=' . (int) $ct->id . '&token=' . $ct->token
                        ),
                        '{firstname}' => $customer->firstname,
                        '{lastname}'  => $customer->lastname,
                    ];
                    //#ct == id_customer_thread    #tc == token of thread   <== used in the synchronization imap
                    $contact = new Contact((int) $ct->id_contact, (int) $ct->id_lang);

                    if (Validate::isLoadedObject($contact)) {
                        $fromName = $contact->name;
                        $fromEmail = $contact->email;
                    } else {
                        $fromName = null;
                        $fromEmail = null;
                    }

                    if (Mail::Send(
                        (int) $ct->id_lang,
                        'reply_msg',
                        sprintf(Mail::l('An answer to your message is available #ct%1$s #tc%2$s', $ct->id_lang), $ct->id, $ct->token),
                        $params,
                        Tools::getValue('msg_email'),
                        null,
                        Tools::convertEmailToIdn($fromEmail),
                        $fromName,
                        $fileAttachment,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $ct->id_shop
                    )) {
                        $ct->status = 'closed';
                        $ct->update();
                    }

                    Tools::redirectAdmin(
                        static::$currentIndex . '&id_customer_thread=' . (int) $idCustomerThread . '&viewcustomer_thread&token=' . Tools::getValue('token')
                    );
                } else {
                    $this->errors[] = Tools::displayError('An error occurred. Your message was not sent. Please contact your system administrator.');
                }

            }

        }

        return parent::postProcess();
    }

    /**
     * @param      $message
     * @param bool $email
     * @param null $idEmployee
     *
     * @return string
     *
     * @since 1.0.
     */
    protected function displayMessage($message, $email = false, $idEmployee = null) {

        $tpl = $this->createTemplate('message.tpl');

        $contacts = Contact::getContacts($this->context->language->id);
        $contactArray = [];

        foreach ($contacts as $contact) {
            $contactArray[$contact['id_contact']] = ['id_contact' => $contact['id_contact'], 'name' => $contact['name']];
        }

        $contacts = $contactArray;

        if (!$email) {

            if (!empty($message['id_product']) && empty($message['employee_name'])) {
                $idOrderProduct = CustomerPieces::getIdOrderProduct((int) $message['id_customer'], (int) $message['id_product']);
            }

        }

        $message['date_add'] = Tools::displayDate($message['date_add'], null, true);
        $message['user_agent'] = strip_tags($message['user_agent']);
        $message['message'] = preg_replace(
            '/(https?:\/\/[a-z0-9#%&_=\(\)\.\? \+\-@\/]{6,1000})([\s\n<])/Uui',
            '<a href="\1">\1</a>\2',
            html_entity_decode(
                $message['message'],
                ENT_QUOTES,
                'UTF-8'
            )
        );

        $isValidOrderId = true;
        $order = new CustomerPieces((int) $message['id_order']);

        if (!Validate::isLoadedObject($order)) {
            $isValidOrderId = false;
        }

        $tpl->assign(
            [
                'thread_url'        => Tools::getAdminUrl(basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminCustomerThreads') . '&amp;id_customer_thread=' . (int) $message['id_customer_thread'] . '&amp;viewcustomer_thread=1'),
                'link'              => $this->context->link,
                'current'           => static::$currentIndex,
                'token'             => $this->token,
                'message'           => $message,
                'id_order_product'  => isset($idOrderProduct) ? $idOrderProduct : null,
                'email'             => Tools::convertEmailFromIdn($email),
                'id_employee'       => $idEmployee,
                'PS_SHOP_NAME'      => Configuration::get('PS_SHOP_NAME'),
                'file_name'         => file_exists(_PS_UPLOAD_DIR_ . $message['file_name']),
                'contacts'          => $contacts,
                'is_valid_order_id' => $isValidOrderId,
            ]
        );

        return $tpl->fetch();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @since 1.8.1.0
     */

    public function renderView() {

        if (!$idCustomerThread = (int) Tools::getValue('id_customer_thread')) {
            return '';
        }

        if (!($thread = $this->loadObject())) {
            return '';
        }

        $this->context->cookie->{'customer_threadFilter_cl!id_contact'}

        = $thread->id_contact;

        $employees = Employee::getEmployees();

        $messages = CustomerThread::getMessageCustomerThreads($idCustomerThread);

        foreach ($messages as $key => &$mess) {

            if ($mess['id_employee']) {
                $employee = new Employee($mess['id_employee']);
                $messages[$key]['employee_image'] = $employee->getImage();
            }

            if (isset($mess['file_name']) && $mess['file_name'] != '') {

                if (file_exists(_PS_UPLOAD_DIR_ . $mess['file_name'])) {
                    $ext = pathinfo(_PS_UPLOAD_DIR_ . $mess['file_name'], PATHINFO_EXTENSION);

                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $mess['image'] = $this->context->link->getBaseFrontLink() . 'upload/' . $mess['file_name'];
                    }

                }

                $messages[$key]['file_name'] = $this->context->link->getBaseFrontLink() . 'upload/' . $mess['file_name'];
            } else {
                unset($messages[$key]['file_name']);
            }

            if ($mess['id_product']) {
                $product = new Product((int) $mess['id_product'], false, $this->context->language->id);

                if (Validate::isLoadedObject($product)) {
                    $messages[$key]['product_name'] = $product->name[$this->context->language->id];
                    $messages[$key]['product_link'] = 'editAjaxObject("AdminProducts", ' . (int) $product->id . ')';
                }

            }

        }

        $nextThread = CustomerThread::getNextThread((int) $thread->id);

        $contacts = Contact::getContacts($this->context->language->id);

        $actions = [];

        if ($nextThread) {
            $nextThread = [
                'href' => static::$currentIndex . '&id_customer_thread=' . (int) $nextThread . '&viewcustomer_thread&token=' . $this->token,
                'name' => $this->l('Reply to the next unanswered message in this thread'),
            ];
        }

        if ($thread->status != 'closed') {
            $actions['closed'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=2&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Mark as "handled"'),
                'name'  => 'setstatus',
                'value' => 2,
            ];
        } else {
            $actions['open'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=1&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Re-open'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->status != 'pending1') {
            $actions['pending1'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=3&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Mark as "pending 1" (will be answered later)'),
                'name'  => 'setstatus',
                'value' => 3,
            ];
        } else {
            $actions['pending1'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=1&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Disable pending status'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->status != 'pending2') {
            $actions['pending2'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=4&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Mark as "pending 2" (will be answered later)'),
                'name'  => 'setstatus',
                'value' => 4,
            ];
        } else {
            $actions['pending2'] = [
                'href'  => static::$currentIndex . '&viewcustomer_thread&setstatus=1&id_customer_thread=' . (int) Tools::getValue('id_customer_thread') . '&viewmsg&token=' . $this->token,
                'label' => $this->l('Disable pending status'),
                'name'  => 'setstatus',
                'value' => 1,
            ];
        }

        if ($thread->id_customer) {
            $customer = new Customer($thread->id_customer);
            $orders = CustomerPieces::getCustomerOrders($customer->id);

            if ($orders && count($orders)) {
                $totalOk = 0;
                $ordersOk = [];

                foreach ($orders as $key => $order) {

                    if ($order['validate']) {
                        $ordersOk[] = $order;
                        $totalOk += $order['total_tax_incl'] / $order['conversion_rate'];
                    }

                    $orders[$key]['date_add'] = Tools::displayDate($order['date_add']);
                    $orders[$key]['total_tax_incl'] = Tools::displayPrice($order['total_tax_incl'], new Currency((int) $order['id_currency']));
                }

            }

            $products = $customer->getBoughtProducts();

            if ($products && count($products)) {

                foreach ($products as $key => $product) {
                    $products[$key]['date_add'] = Tools::displayDate($product['date_add'], null, true);
                }

            }

        }

        $timelineItems = $this->getTimeline($messages, $thread->id_order);
        $firstMessage = $messages[0];

        if (!$messages[0]['id_employee']) {
            unset($messages[0]);
        }

        $contact = '';

        foreach ($contacts as $c) {

            if ($c['id_contact'] == $thread->id_contact) {
                $contact = $c['name'];
            }

        }

        $this->tpl_view_vars = [
            'id_customer_thread'            => $idCustomerThread,
            'thread'                        => $thread,
            'actions'                       => $actions,
            'employees'                     => $employees,
            'current_employee'              => $this->context->employee,
            'messages'                      => $messages,
            'first_message'                 => $firstMessage,
            'contact'                       => $contact,
            'next_thread'                   => $nextThread,
            'orders'                        => isset($orders) ? $orders : false,
            'customer'                      => isset($customer) ? $customer : false,
            'products'                      => isset($products) ? $products : false,
            'total_ok'                      => isset($totalOk) ? Tools::displayPrice($totalOk, $this->context->currency) : false,
            'orders_ok'                     => isset($ordersOk) ? $ordersOk : false,
            'count_ok'                      => isset($ordersOk) ? count($ordersOk) : false,
            'PS_CUSTOMER_SERVICE_SIGNATURE' => str_replace('\r\n', "\n", Configuration::get('PS_CUSTOMER_SERVICE_SIGNATURE', (int) $thread->id_lang)),
            'timeline_items'                => $timelineItems,
            'AjaxBackLink'                  => $this->context->link->getAdminLink($this->controller_name),
        ];

        if ($nextThread) {
            $this->tpl_view_vars['next_thread'] = $nextThread;
        }

        return parent::renderView();
    }

    /**
     * Get timeline
     *
     * @param $messages
     * @param $idOrder
     *
     * @return array
     *
     * @since 1.8.1.0
     */
    public function getTimeline($messages, $idOrder) {

        $timeline = [];

        foreach ($messages as $message) {
            $product = new Product((int) $message['id_product'], false, $this->context->language->id);

            $content = '';

            if (!$message['private']) {
                $content .= $this->l('Message to: ') . ' <span class="badge">' . (!$message['id_employee'] ? $message['subject'] : $message['customer_name']) . '</span><br/>';
            }

            if (Validate::isLoadedObject($product)) {
                $content .= '<br/>' . $this->l('Product: ') . '<span class="label label-info">' . $product->name . '</span><br/><br/>';
            }

            $content .= Tools::safeOutput($message['message']);

            $timeline[$message['date_add']][] = [
                'arrow'            => 'left',
                'background_color' => '',
                'icon'             => 'fa fa-envelope',
                'content'          => $content,
                'date'             => $message['date_add'],
            ];
        }

        $order = new CustomerPieces((int) $idOrder);

        if (Validate::isLoadedObject($order)) {
            $orderHistory = $order->getHistory($this->context->language->id);

            foreach ($orderHistory as $history) {
                $linkOrder = $this->context->link->getAdminLink('AdminOrders') . '&vieworder&id_order=' . (int) $order->id;

                $content = '<a class="badge" target="_blank" href="' . Tools::safeOutput($linkOrder) . '">' . $this->l('Order') . ' #' . (int) $order->id . '</a><br/><br/>';

                $content .= '<span>' . $this->l('Status:') . ' ' . $history['ostate_name'] . '</span>';

                $timeline[$history['date_add']][] = [
                    'arrow'            => 'right',
                    'alt'              => true,
                    'background_color' => $history['color'],
                    'icon'             => 'fa fa-credit-card',
                    'content'          => $content,
                    'date'             => $history['date_add'],
                    'see_more_link'    => $linkOrder,
                ];
            }

        }

        krsort($timeline);

        return $timeline;
    }

    /**
     * @param $value
     *
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     */
    public function updateOptionPsSavImapOpt($value) {

        if ($this->tabAccess['edit'] != '1') {
            throw new PhenyxShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (!$this->errors && $value) {
            Configuration::updateValue('PS_SAV_IMAP_OPT', implode('', $value));
        }

    }

    /**
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     */
    public function ajaxProcessMarkAsRead() {

        if ($this->tabAccess['edit'] != '1') {
            throw new PhenyxShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        $idThread = Tools::getValue('id_thread');
        $messages = CustomerThread::getMessageCustomerThreads($idThread);

        if (count($messages)) {
            Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'customer_message` set `read` = 1 WHERE `id_employee` = ' . (int) $this->context->employee->id . ' AND `id_customer_thread` = ' . (int) $idThread);
        }

    }

    /**
     * Call the IMAP synchronization during an AJAX process.
     *
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     */
    public function ajaxProcessSyncImap() {

        if ($this->tabAccess['edit'] != '1') {
            throw new PhenyxShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (Tools::isSubmit('syncImapMail')) {
            $this->ajaxDie(json_encode($this->syncImap()));
        }

    }

    protected function openUploadedFile() {

        $filename = $_GET['filename'];

        $extensions = [
            '.txt'  => 'text/plain',
            '.rtf'  => 'application/rtf',
            '.doc'  => 'application/msword',
            '.docx' => 'application/msword',
            '.pdf'  => 'application/pdf',
            '.zip'  => 'multipart/x-zip',
            '.png'  => 'image/png',
            '.jpeg' => 'image/jpeg',
            '.gif'  => 'image/gif',
            '.jpg'  => 'image/jpeg',
        ];

        $extension = false;

        foreach ($extensions as $key => $val) {

            if (substr(mb_strtolower($filename), -4) == $key || substr(mb_strtolower($filename), -5) == $key) {
                $extension = $val;
                break;
            }

        }

        if (!$extension || !Validate::isFileName($filename)) {
            die(Tools::displayError());
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $extension);
        header('Content-Disposition:attachment;filename="' . $filename . '"');
        readfile(_PS_UPLOAD_DIR_ . $filename);
        die;
    }

    /**
     * @param $content
     *
     * @return string
     *
     * @since 1.8.1.0
     */
    protected function displayButton($content) {

        return '<div><p>' . $content . '</p></div>';
    }

}
