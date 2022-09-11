<?php

/**
 * @property PaymentMode $object
 */
class AdminStdAccountsControllerCore extends AdminController {

    public $subForm = false;

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'stdaccount';
        $this->className = 'StdAccount';
        $this->publicName = $this->l('Book keepings Accounts');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();

        EmployeeConfiguration::updateValue('EXPERT_BOOKACCOUNTS_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOKACCOUNTS_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_BOOKACCOUNTS_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOKACCOUNTS_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_BOOKACCOUNTS_FIELDS', Tools::jsonEncode($this->getStdAccountFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOKACCOUNTS_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_BOOKACCOUNTS_FIELDS', Tools::jsonEncode($this->getStdAccountFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOKACCOUNTS_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        Media::addJsDef([
            'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
        ]);
        $this->addCSS(__PS_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/account.css', 'all');
        $this->addJS(__PS_BASE_URI__ . _PS_JS_DIR_ . 'stdaccount.js');

    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _PS_JS_DIR_ . 'stdaccount.js',
        ]);
    }

    public function ajaxProcessOpenTargetController() {

        $this->paragridScript = $this->generateParaGridScript();
        $this->setAjaxMedia();

        $data = $this->createTemplate($this->table . '.tpl');

        $extracss = $this->pushCSS([
            $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/account.css',

        ]);

        $data->assign([
            'paragridScript' => $this->paragridScript,
            'controller'     => $this->controller_name,
            'tableName'      => $this->table,
            'className'      => $this->className,
            'stdTypes'       => StdAccount::getAccountType($this->context->language->id),
            'link'           => $this->context->link,
            'extraJs'        => $this->push_js_files,
            'extracss'       => $extracss,
        ]);

        $li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,

            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function initContent() {

        $this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->context->smarty->assign([
            'stdTypes' => StdAccount::getAccountType($this->context->language->id),
        ]);

        $this->TitleBar = $this->l('Book account List');

        $this->context->smarty->assign([
            'controller'     => Tools::getValue('controller'),
            'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
            'gridId'         => 'grid_AdminStdAccounts',
            'tableName'      => $this->table,
            'className'      => $this->className,
            'linkController' => $this->context->link->getAdminLink($this->controller_name),
            'AjaxLink'       => $this->context->link->getAdminLink($this->controller_name),
            'paragridScript' => $this->generateParaGridScript(),
            'titleBar'       => $this->TitleBar,
            'bo_imgdir'      => __PS_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
            'idController'   => '',
        ]);

        parent::initContent();

    }

    public function generateParaGridScript($regenerate = false) {

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->showNumberCell = 0;
        $paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->complete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Gestion des plan de comptes') . '\'';
        $paragrid->fillHandle = '\'all\'';
        $paragrid->filterModel = [
            'on'     => true,
            'mode'   => '\'AND\'',
            'header' => true,
        ];
        $paragrid->contextMenu = [
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
                var today = new Date();
                var dd = String(today.getDate()).padStart(2, "0");
                var mm = String(today.getMonth() + 1).padStart(2, "0"); //January is 0!
                var yyyy = today.getFullYear();
                today = yyyy + mm + dd;
                return {
                    callback: function(){},
                    items: {

                        "add": {
                            name : \'' . $this->l('Ajouter un nouveau compte ') . '\',
                            icon: "add",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                addNewAccount();
                            }
                        },
                         "edit": {
                            name : \'' . $this->l('Modifier le compte  ') . '\'' . '+rowData.account,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                editAccount(rowData.id_stdaccount);
                            }
                        },
                         "delete": {
                            name : \'' . $this->l('Supprimer le compte  ') . '\'' . '+rowData.account,
                            icon: "delete",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteAccount(rowData.id_stdaccount);
                            }
                        },


                    },
                };
            }',
            ]];

        $option = $paragrid->generateParaGridOption();
        $script = $paragrid->generateParagridScript();

        $this->paragridScript = $script;
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getStdAccountRequest() {

        $stdaccounts = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, sl.*, stl.`name` as `type`, stl.`racine`, a2.`account` as `defaultVat`, a3.`account` as `counter`')
                ->from('stdaccount', 'a')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $this->context->language->id)
                ->leftJoin('stdaccount_type_lang', 'stl', 'stl.`id_stdaccount_type` = a.`id_stdaccount_type` AND stl.`id_lang`  = ' . (int) $this->context->language->id)
                ->leftJoin('stdaccount', 'a2', 'a2.`id_stdaccount` = a.`default_vat`')
                ->leftJoin('stdaccount', 'a3', 'a3.`id_stdaccount` = a.`counterpart`')
                ->orderBy('a.`id_stdaccount_type` ASC, a.`account` ASC')
        );
        $stdaccountLink = $this->context->link->getAdminLink($this->controller_name);

        if (!empty($stdaccounts)) {

            foreach ($stdaccounts as &$stdaccount) {

                if ($stdaccount['vat_exonerate'] == 1) {
                    $stdaccount['vat_exonerate'] = true;
                } else {
                    $stdaccount['vat_exonerate'] = false;
                }

            }

        }

        return $stdaccounts;

    }

    public function ajaxProcessgetStdAccountRequest() {

        die(Tools::jsonEncode($this->getStdAccountRequest()));

    }

    public function getStdAccountFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'dataIndx' => 'id_stdaccount',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => true,
            ],

            [
                'title'    => $this->l('Numéro de Compte'),
                'width'    => 200,
                'dataIndx' => 'account',
                'valign'   => 'center',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'    => $this->l('Nom'),
                'width'    => 200,
                'dataIndx' => 'name',
                'valign'   => 'center',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'    => $this->l('Description'),
                'width'    => 200,
                'exWidth'  => 40,
                'dataIndx' => 'description',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Exonéré de TVA'),
                'type'     => 'checkbox',
                'width'    => 150,
                'exWidth'  => 40,
                'align'    => 'center',
                'valign'   => 'center',
                'dataIndx' => 'vat_exonerate',
                'dataType' => 'bool',
            ],
            [
                'title'    => $this->l('Compte de TVA'),
                'width'    => 150,
                'dataIndx' => 'defaultVat',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Compte de contrepartie'),
                'width'    => 150,
                'dataIndx' => 'counter',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Solde signéé'),
                'width'    => 150,
                'dataIndx' => 'pointed_solde',
                'align'    => 'right',
                'valign'   => 'center',
                'dataType' => 'float',
                'format'   => "#.###,00",

            ],
            [

                'dataIndx' => 'id_stdaccount_type',
                'dataType' => 'integer',
                'hidden'   => true,
                'filter'   => [
                    'crules' => [['condition' => "equal"]],
                ],
            ],
        ];
    }

    public function ajaxProcessgetStdAccountFields() {

        die(EmployeeConfiguration::get('EXPERT_BOOKACCOUNTS_FIELDS'));
    }

    public function getShortStdAccountFields() {

        return [
            [

                'dataIndx' => 'id_stdaccount',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => true,
            ],

            [
                'title'    => $this->l('Numéro de Compte'),
                'width'    => 100,
                'dataIndx' => 'account',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'    => $this->l('Nom'),
                'width'    => 150,
                'dataIndx' => 'name',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Déscription'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'description',
                'dataType' => 'string',
            ],

        ];

    }

    public function ajaxProcessGetShortStdAccountFields() {

        die(Tools::jsonEncode($this->getShortStdAccountFields()));
    }

    public function initPageHeaderToolbar() {

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_stdaccount_account'] = [
                'href'       => 'javascript:void(0)',
                'desc'       => $this->l('Ajouter un nouveau compte', null, null, false),
                'identifier' => 'new',
                'controller' => $this->controller_name,
                'icon'       => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderForm() {

        $this->displayGrid = false;
        $obj = $this->loadObject(true);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Book Account'),
                'icon'  => 'icon-university',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Book account number'),
                    'name'     => 'account',
                    'col'      => 2,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Book account name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                ],

                [
                    'type'  => 'text',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                    'lang'  => true,
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],

        ];

        $this->tpl_form_vars['stdAccount'] = $obj;

        $this->tpl_form_vars['formId'] = 'form-stdaccount-new';
        $this->tpl_form_vars['renderCreate'] = true;

        return parent::renderForm();
    }

    public function renderObjectForm() {

        $this->tpl_form_vars['subCreate'] = true;
        $this->tpl_form_vars['formId'] = 'form-stdaccount-new-sub';
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Book Account'),
                'icon'  => 'icon-university',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Book account number'),
                    'name'     => 'account',
                    'col'      => 2,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Book account name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                ],

                [
                    'type'  => 'text',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                    'lang'  => true,
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],

        ];

        $return = parent::renderForm();
        $this->tpl_form_vars['subCreate'] = false;
        return $return;
    }

    public function ajaxProcessProcessSave() {

        $account = new StdAccount();

        foreach ($_POST as $key => $value) {

            if (property_exists($account, $key) && $key != 'id_stdaccount') {
                $account->{$key}
                = $value;
            }

        }

        $classVars = get_class_vars(get_class($account));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($account->{$field}) || !is_array($account->{$field})) {
                            $account->{$field}

                            = [];
                        }

                        $account->{$field}

                        [(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                    }

                }

            }

        }

        $result = $account->add();

        if ($result) {
            $return = [
                'success'    => true,
                'id_object'  => $account->id,
                'stdaccount' => $account->account,
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('L‘ajout du compte à échouée'),
            ];
        }

        die(Tools::jsonEncode($return));

    }

    public function postProcess() {

        parent::postProcess();
    }

    public function ajaxProcessCheckAccount() {

        $account = Tools::getValue('account');

        $searchAccount = StdAccount::getAccountByName($account);

        if ($searchAccount->id > 0) {

            $return = [
                'success' => false,
            ];
        } else {
            $return = [
                'success' => true,
            ];
        }

        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessGetAccountTypeRequest() {

        $type = Tools::getValue('type');

        switch ($type) {
        case 'Banks':
            die(Tools::jsonEncode(StdAccount::getBankStdAccount()));
            break;
        case 'Profits':
            die(Tools::jsonEncode(StdAccount::getProfitsStdAccount()));
            break;
        case 'Expenses':
            die(Tools::jsonEncode(StdAccount::getExpensesStdAccount()));
            break;
        case 'VAT':
            die(Tools::jsonEncode(StdAccount::getVATStdAccount()));
            break;
        case 'Supplier':
            die(Tools::jsonEncode(StdAccount::getAccountByidType(4)));
            break;
        case 'Customer':
            die(Tools::jsonEncode(StdAccount::getAccountByidType(5)));
            break;
        }

    }

    public function ajaxProcessAutoCompleteAccount() {

        $keyword = Tools::getValue('keyword', false);
        $context = Context::getContext();

        $items = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('s.*, sl.`name`, sl2.`name` as defaultVatName, s2.account as defaultVatCode, sl3.`name` as counterPartName, s3.account as counterPartCode')
                ->from('stdaccount', 's')
                ->leftJoin('stdaccount', 's2', 's2.id_stdaccount = s.default_vat')
                ->leftJoin('stdaccount', 's3', 's3.id_stdaccount = s.counterpart')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.id_stdaccount = s.id_stdaccount AND sl.id_lang = ' . (int) $context->language->id)
                ->leftJoin('stdaccount_lang', 'sl2', 'sl2.id_stdaccount = s.default_vat AND sl2.id_lang = ' . (int) $context->language->id)
                ->leftJoin('stdaccount_lang', 'sl3', 'sl3.id_stdaccount = s.counterpart AND sl2.id_lang = ' . (int) $context->language->id)
                ->where('s.account LIKE \'' . pSQL($keyword) . '%\'')
        );

        if ($items) {

            foreach ($items as &$item) {

                if (empty($item['default_vat']) && $item['id_stdaccount_type'] == 5) {
                    $item['default_vat'] = Configuration::get('EPH_DEDUCTIBLE_VAT_DEFAULT_ACCOUNT');
                    $stdAccount = new StdAccount($item['default_vat'], (int) $context->language->id);
                    $item['defaultVatCode'] = $stdAccount->account;
                    $item['defaultVatName'] = $stdAccount->name;
                }

                if (empty($item['counterpart']) && $item['id_stdaccount_type'] == 4) {
                    $item['counterpart'] = Configuration::get('EPH_PURCHASE_DEFAULT_ACCOUNT');
                    $stdAccount = new StdAccount($item['counterpart'], (int) $context->language->id);
                    $item['counterPartCode'] = $stdAccount->account;
                    $item['counterPartName'] = $stdAccount->name;
                }

            }

            $results = Tools::jsonEncode($items, JSON_NUMERIC_CHECK);

            die($results);
        } else {
            json_encode(new stdClass);
        }

    }

}
