<?php

/**
 * Class AdminTaxRulesGroupControllerCore
 *
 * @since 1.9.1.0
 */
class AdminTaxRulesGroupControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    public $tax_rule;
    public $selected_countries = [];
    public $selected_states = [];
    public $errors_tax_rule;
    // @codingStandardsIgnoreEnd

    /**
     * AdminTaxRulesGroupControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'tax_rules_group';
        $this->className = 'TaxRulesGroup';
        $this->publicName = 'Taxe rules';
        $this->lang = false;

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_TAXERULESGROUP_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_TAXERULESGROUP_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_TAXERULESGROUP_FIELDS', Tools::jsonEncode($this->getTaxRulesGroupFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);

        Media::addJsDef([
            'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
        ]);

        $this->addJS([
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/pqSelect/pqselect.min.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/pqgrid.min.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/localize/pq-localize-fr.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/pqTouch/pqtouch.min.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/jsZip-2.5.0/jszip.min.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/FileSaver.js',
            __EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pgrid/javascript-detect-element-resize/jquery.resize.js',

        ]);

    }

    public function initContent() {

        $this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Taxe rules Group');

        $this->context->smarty->assign([
            'controller'     => Tools::getValue('controller'),
            'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
            'gridId'         => 'grid_' . $this->controller_name,
            'tableName'      => $this->table,
            'className'      => $this->className,
            'linkController' => $this->context->link->getAdminLink($this->controller_name),
            'AjaxLink'       => $this->context->link->getAdminLink($this->controller_name),
            'paragridScript' => $this->generateParaGridScript(),
            'titleBar'       => $this->TitleBar,
            'bo_imgdir'      => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
            'idController'   => '',
        ]);

        parent::initContent();

    }

    public function generateParaGridScript($regenerate = false) {

        if (!empty($this->paragridScript) && !$regenerate) {
            return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
        }

        $context = Context::getContext();
        $controllerLink = $context->link->getAdminLink($this->controller_name);
        $activeSelector = '<div class="pq-theme"><select id="activeSelect"><option value="">' . $this->l('--Select--') . '</option><option value="0">' . $this->l('Disable') . '</option><option value="1">' . $this->l('Enable') . '</option></select></div>';

        $gridExtraFunction = [
            'buildTaxRulesGroupFilter() ' => '
            var conteneur = $(\'#activeSelector\').parent().parent();
                $(conteneur).empty();
                $(conteneur).append(\'' . $activeSelector . '\');
                $(\'#activeSelect\' ).selectmenu({
                    "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'enable\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }

                });
        '];

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->colModel = EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_FIELDS');
        $paragrid->showNumberCell = 0;
        $paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->complete = 'function(){
        buildTaxRulesGroupFilter();
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
        $paragrid->fillHandle = '\'all\'';
        $paragrid->rowDblClick = 'function( event, ui ) {
            var identifierlink = ui.rowData.' . $this->identifier . ';
            var datalink = \'' . $controllerLink . '&' . $this->identifier . '=\'+identifierlink+\'&id_object=\'+identifierlink+\'&update' . $this->table . '&action=initUpdateController&ajax=true\';
            openAjaxGridLink(datalink, identifierlink, \'' . $this->controller_name . '\', \'View' . $this->controller_name . '\');
        } ';

        $paragrid->contextMenuoption = [

            'add'        => [
                'name' => '\'' . $this->l('Add new ') . $this->publicName . '\'',
                'icon' => '"add"',
            ],
            'edit'       => [
                'name' => '\'' . $this->l('Edit the ') . $this->publicName . ' :\'' . '+rowData.name',
                'icon' => '"edit"',
            ],
            'sep'        => [
                'sep1' => "---------",
            ],
            'select'     => [
                'name' => '\'' . $this->l('Select all item') . '\'',
                'icon' => '"list-ul"',
            ],
            'unselect'   => [
                'name' => '\'' . $this->l('Unselect all item') . '\'',
                'icon' => '"list-ul"',
            ],
            'sep'        => [
                'sep2' => "---------",
            ],
            'delete'     => [
                'name' => '\'' . $this->l('Delete the ') . $this->publicName . ' :\'' . '+rowData.name',
                'icon' => '"delete"',
            ],
            'bulkdelete' => [
                'name' => '\'' . $this->l('Delete the selected ') . $this->publicName . '\'',
                'icon' => '"delete"',
            ],

        ];
        $paragrid->filterModel = [
            'on'          => true,
            'mode'        => '\'OR\'',
            'header'      => true,
            'menuIcon'    => 0,
            'gridOptions' => [
                'numberCell' => [
                    'show' => 0,
                ],
                'width'      => '\'flex\'',
                'flex'       => [
                    'one' => true,
                ],
            ],
        ];

        $paragrid->gridFunction = $gridExtraFunction;

        $option = $paragrid->generateParaGridOption();
        $script = $paragrid->generateParagridScript();

        if ($regenerate) {
            return $script;
        }

        $this->paragridScript = $script;
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getTaxRulesGroupRequest() {

        $taxes = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, case when a.`active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`, a.`active` as `enable`')
                ->from('tax_rules_group', 'a')
                ->where('a.`deleted` = 0')
                ->orderBy('a.`id_tax_rules_group` ASC')
        );

        foreach ($taxes as &$taxe) {

            $taxe['openLink'] = $this->context->link->getAdminLink($this->controller_name) . '&id_tax_rules_group=' . $taxe['id_tax_rules_group'] . '&id_object=' . $taxe['id_tax_rules_group'] . '&updatetax_rules_groupe&action=initUpdateController&ajax=true';

        }

        return $taxes;

    }

    public function ajaxProcessgetTaxRulesGroupRequest() {

        die(Tools::jsonEncode($this->getTaxRulesGroupRequest()));

    }

    public function getTaxRulesGroupFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_tax_rules_group',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 50,
                'dataIndx'   => 'openLink',
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
                'title'      => ' ',
                'width'      => 50,
                'dataIndx'   => 'deleteLink',
                'dataType'   => 'string',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
            [
                'title'      => $this->l('Name'),
                'width'      => 200,
                'dataIndx'   => 'name',
                'cls'        => 'name-handle',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
            ],
            [

                'dataIndx'   => 'enable',
                'dataType'   => 'integer',
                'align'      => 'center',
                'valign'     => 'center',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],
            [
                'title'    => $this->l('Active'),
                'minWidth' => 100,
                'dataIndx' => 'active',
                'align'    => 'center',
                'valign'   => 'center',
                'halign'   => 'HORIZONTAL_CENTER',
                'dataType' => 'html',
                'filter'   => [
                    'attr'   => "id=\"activeSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],

            ],

        ];
    }

    public function ajaxProcessgetTaxRulesGroupFields() {

        die(EmployeeConfiguration::get('EXPERT_TAXERULESGROUP_FIELDS'));
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_tax_rules_group'] = [
                'href'       => static::$currentIndex . '&action=addObject&ajax=true&addtax_rules_group&token=' . $this->token,
                'identifier' => 'new',
                'controller' => $this->controller_name,
                'desc'       => $this->l('Add new tax rules group', null, null, false),
                'icon'       => 'process-icon-new',
            ];
        }

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

        $this->displayGrid = false;
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Tax Rules'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Enable'),
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
                ],
            ],
            'submit' => [
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        if (!isset($obj->id)) {
            $this->no_back = false;
            $content = parent::renderForm();
        } else {
            $this->no_back = true;
            $this->page_header_toolbar_btn['new'] = [
                'href' => '#',
                'desc' => $this->l('Add a new tax rule'),
            ];
            $content = parent::renderForm();
            $this->tpl_folder = 'tax_rules/';
            $content .= $this->initRuleForm();

            // We change the variable $ tpl_folder to avoid the overhead calling the file in list_action_edit.tpl in intList ();

            $content .= $this->initRulesList((int) $obj->id);
        }

        return $content;
    }

    /**
     * Initialize rule form
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function initRuleForm() {

        $this->fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('New tax rule'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'    => 'select',
                    'label'   => $this->l('Country'),
                    'name'    => 'country',
                    'id'      => 'country',
                    'options' => [
                        'query'   => Country::getCountries($this->context->language->id),
                        'id'      => 'id_country',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('All', 'AdminTaxRulesGroupController'),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'states[]',
                    'id'       => 'states',
                    'multiple' => true,
                    'options'  => [
                        'query'   => [],
                        'id'      => 'id_state',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('All', 'AdminTaxRulesGroupController'),
                        ],
                    ],
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal code range'),
                    'name'     => 'zipcode',
                    'required' => false,
                    'hint'     => $this->l('You can define a range of Zip/postal codes (e.g., 75000-75015) or simply use one Zip/postal code.'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Behavior'),
                    'name'     => 'behavior',
                    'required' => false,
                    'options'  => [
                        'query' => [
                            [
                                'id'   => 0,
                                'name' => $this->l('This tax only'),
                            ],
                            [
                                'id'   => 1,
                                'name' => $this->l('Combine'),
                            ],
                            [
                                'id'   => 2,
                                'name' => $this->l('One after another'),
                            ],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'hint'     => [
                        $this->l('You must define the behavior if an address matches multiple rules:') . '<br>',
                        $this->l('- This tax only: Will apply only this tax') . '<br>',
                        $this->l('- Combine: Combine taxes (e.g.: 10% + 5% = 15%)') . '<br>',
                        $this->l('- One after another: Apply taxes one after another (e.g.: 0 + 10% = 0 + 5% = 5.5)'),
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Tax'),
                    'name'     => 'id_tax',
                    'required' => false,
                    'options'  => [
                        'query'   => Tax::getTaxes((int) $this->context->language->id),
                        'id'      => 'id_tax',
                        'name'    => 'name',
                        'default' => [
                            'value' => 0,
                            'label' => $this->l('No Tax'),
                        ],
                    ],
                    'hint'     => sprintf($this->l('(Total tax: %s)'), '9%'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_value = [
            'action'             => 'create_rule',
            'id_tax_rules_group' => $obj->id,
            'id_tax_rule'        => '',
        ];

        $this->getlanguages();
        $helper = new HelperForm();
        $helper->override_folder = $this->tpl_folder;
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = 'tax_rule';
        $helper->identifier = 'id_tax_rule';
        $helper->id = $obj->id;
        $helper->toolbar_scroll = true;
        $helper->show_toolbar = true;
        $helper->languages = $this->_languages;
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->fields_value = $this->getFieldsValue($this->object);
        $helper->toolbar_btn['save_new_rule'] = [
            'href'  => static::$currentIndex . '&amp;id_tax_rules_group=' . $obj->id . '&amp;action=create_rule&amp;token=' . $this->token,
            'desc'  => 'Save tax rule',
            'class' => 'process-icon-save',
        ];
        $helper->submit_action = 'create_rule';

        return $helper->generateForm($this->fields_form);
    }

    /**
     * Initialize rules list
     *
     * @param int $idGroup
     *
     * @return false|string
     *
     * @since 1.9.1.0
     */
    public function initRulesList($idGroup) {

        $this->table = 'tax_rule';
        $this->list_id = 'tax_rule';
        $this->identifier = 'id_tax_rule';
        $this->className = 'TaxRule';
        $this->lang = false;
        $this->list_simple_header = false;
        $this->toolbar_btn = null;
        $this->list_no_link = true;

        $this->bulk_actions = [
            'delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?'), 'icon' => 'icon-trash'],
        ];

        $this->fields_list = [
            'country_name' => [
                'title' => $this->l('Country'),
            ],
            'state_name'   => [
                'title' => $this->l('State'),
            ],
            'zipcode'      => [
                'title' => $this->l('Zip/Postal code'),
                'class' => 'fixed-width-md',
            ],
            'behavior'     => [
                'title' => $this->l('Behavior'),
            ],
            'rate'         => [
                'title' => $this->l('Tax'),
                'class' => 'fixed-width-sm',
            ],
            'description'  => [
                'title' => $this->l('Description'),
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = '
            c.`name` AS country_name,
            s.`name` AS state_name,
            CONCAT_WS(" - ", a.`zipcode_from`, a.`zipcode_to`) AS zipcode,
            t.rate';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` c
                ON (a.`id_country` = c.`id_country` AND id_lang = ' . (int) $this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'state` s
                ON (a.`id_state` = s.`id_state`)
            LEFT JOIN `' . _DB_PREFIX_ . 'tax` t
                ON (a.`id_tax` = t.`id_tax`)';
        $this->_where = 'AND `id_tax_rules_group` = ' . (int) $idGroup;
        $this->_use_found_rows = false;

        $this->show_toolbar = false;
        $this->tpl_list_vars = ['id_tax_rules_group' => (int) $idGroup];

        $this->_filter = false;

        return parent::renderList();
    }

    /**
     * Initialize processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initProcess() {

        if (Tools::isSubmit('deletetax_rule')) {

            if ($this->tabAccess['delete'] === '1') {
                $this->action = 'delete_tax_rule';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }

        } else
        if (Tools::isSubmit('submitBulkdeletetax_rule')) {

            if ($this->tabAccess['delete'] === '1') {
                $this->action = 'bulk_delete_tax_rules';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }

        } else
        if (Tools::getValue('action') == 'create_rule') {

            if ($this->tabAccess['add'] === '1') {
                $this->action = 'create_rule';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }

        } else {
            parent::initProcess();
        }

    }

    /**
     * Process rule create
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function processCreateRule() {

        $zipCode = Tools::getValue('zipcode');
        $idRule = (int) Tools::getValue('id_tax_rule');
        $idTax = (int) Tools::getValue('id_tax');
        $idTaxRulesGroup = (int) Tools::getValue('id_tax_rules_group');
        $behavior = (int) Tools::getValue('behavior');
        $description = pSQL(Tools::getValue('description'));

        if ((int) ($idCountry = Tools::getValue('country')) == 0) {
            $countries = Country::getCountries($this->context->language->id);
            $this->selected_countries = [];

            foreach ($countries as $country) {
                $this->selected_countries[] = (int) $country['id_country'];
            }

        } else {
            $this->selected_countries = [$idCountry];
        }

        $this->selected_states = Tools::getValue('states');

        if (empty($this->selected_states) || count($this->selected_states) == 0) {
            $this->selected_states = [0];
        }

        $taxRulesGroup = new TaxRulesGroup((int) $idTaxRulesGroup);

        foreach ($this->selected_countries as $idCountry) {
            $first = true;

            foreach ($this->selected_states as $idState) {

                if ($taxRulesGroup->hasUniqueTaxRuleForCountry($idCountry, $idState, $idRule)) {
                    $this->errors[] = Tools::displayError('A tax rule already exists for this country/state with tax only behavior.');
                    continue;
                }

                $tr = new TaxRule();

                // update or creation?

                if (isset($idRule) && $first) {
                    $tr->id = $idRule;
                    $first = false;
                }

                $tr->id_tax = $idTax;
                $taxRulesGroup = new TaxRulesGroup((int) $idTaxRulesGroup);
                $tr->id_tax_rules_group = (int) $taxRulesGroup->id;
                $tr->id_country = (int) $idCountry;
                $tr->id_state = (int) $idState;
                list($tr->zipcode_from, $tr->zipcode_to) = $tr->breakDownZipCode($zipCode);

                // Construct Object Country
                $country = new Country((int) $idCountry, (int) $this->context->language->id);

                if ($zipCode && $country->need_zip_code) {

                    if ($country->zip_code_format) {

                        foreach ([$tr->zipcode_from, $tr->zipcode_to] as $zipCode) {

                            if ($zipCode) {

                                if (!$country->checkZipCode($zipCode)) {
                                    $this->errors[] = sprintf(
                                        Tools::displayError('The Zip/postal code is invalid. It must be typed as follows: %s for %s.'),
                                        str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format))), $country->name
                                    );
                                }

                            }

                        }

                    }

                }

                $tr->behavior = (int) $behavior;
                $tr->description = $description;
                $this->tax_rule = $tr;
                $_POST['id_state'] = $tr->id_state;

                $this->errors = array_merge($this->errors, $this->validateTaxRule($tr));

                if (count($this->errors) == 0) {
                    $taxRulesGroup = $this->updateTaxRulesGroup($taxRulesGroup);
                    $tr->id = (int) $taxRulesGroup->getIdTaxRuleGroupFromHistorizedId((int) $tr->id);
                    $tr->id_tax_rules_group = (int) $taxRulesGroup->id;

                    if (!$tr->save()) {
                        $this->errors[] = Tools::displayError('An error has occurred: Cannot save the current tax rule.');
                    }

                }

            }

        }

        if (count($this->errors) == 0) {
            Tools::redirectAdmin(
                static::$currentIndex . '&' . $this->identifier . '=' . (int) $taxRulesGroup->id . '&conf=4&update' . $this->table . '&token=' . $this->token
            );
        } else {
            $this->display = 'edit';
        }

    }

    /**
     * Check if the tax rule could be added in the database
     *
     * @param TaxRule $tr
     *
     * @return array
     *
     * @since 1.9.1.0
     */
    protected function validateTaxRule(TaxRule $tr) {

        // @TODO: check if the rule already exists
        return $tr->validateController();
    }

    /**
     * @param TaxRulesGroup $object
     *
     * @return TaxRulesGroup
     *
     * @since 1.9.1.0
     */
    protected function updateTaxRulesGroup($object) {

        static $taxRulesGroup = null;

        if ($taxRulesGroup === null) {
            $object->update();
            $taxRulesGroup = $object;
        }

        return $taxRulesGroup;
    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function processBulkDeleteTaxRules() {

        $this->deleteTaxRule(Tools::getValue('tax_ruleBox'));
    }

    /**
     * Delete Tax Rule
     *
     * @param array $idTaxRuleList
     *
     * @since 1.9.1.0
     */
    protected function deleteTaxRule(array $idTaxRuleList) {

        $result = true;

        foreach ($idTaxRuleList as $idTaxRule) {
            $taxRule = new TaxRule((int) $idTaxRule);

            if (Validate::isLoadedObject($taxRule)) {
                $taxRulesGroup = new TaxRulesGroup((int) $taxRule->id_tax_rules_group);
                $taxRulesGroup = $this->updateTaxRulesGroup($taxRulesGroup);
                $taxRule = new TaxRule($taxRulesGroup->getIdTaxRuleGroupFromHistorizedId((int) $idTaxRule));

                if (Validate::isLoadedObject($taxRule)) {
                    $result &= $taxRule->delete();
                }

            }

        }

        Tools::redirectAdmin(
            static::$currentIndex . '&' . $this->identifier . '=' . (int) $taxRulesGroup->id . '&conf=4&update' . $this->table . '&token=' . $this->token
        );
    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function processDeleteTaxRule() {

        $this->deleteTaxRule([Tools::getValue('id_tax_rule')]);
    }

    /**
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function displayAjaxUpdateTaxRule() {

        if ($this->tabAccess['view'] === '1') {
            $idTaxRule = Tools::getValue('id_tax_rule');
            $taxRules = new TaxRule((int) $idTaxRule);
            $output = [];

            foreach ($taxRules as $key => $result) {
                $output[$key] = $result;
            }

            $this->ajaxDie(json_encode($output));
        }

    }

}
