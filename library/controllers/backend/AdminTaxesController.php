<?php

/**
 * Class AdminTaxesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminTaxesControllerCore extends AdminController {

    public $php_self = 'admintaxes';
    /**
     * AdminTaxesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'tax';
        $this->className = 'Tax';
        $this->publicName = 'Taxes';
        $this->lang = true;

        $ecotaxDesc = '';

        if (Configuration::get('EPH_USE_ECOTAX')) {
            $ecotaxDesc = $this->l('If you disable the ecotax, the ecotax for all your products will be set to 0.');
        }

        $availableTaxes = Tax::getTaxes((int) Context::getContext()->language->id, false);
        $availableTaxes[] = [
            'name'   => $this->l('None'),
            'id_tax' => 0,
        ];
        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Tax options'),
                'fields' => [
                    'EPH_TAX'                             => [
                        'title' => $this->l('Enable tax'),
                        'desc'  => $this->l('Select whether or not to include tax on purchases.'),
                        'cast'  => 'intval', 'type' => 'bool',
                    ],
                    'EPH_TAX_DISPLAY'                     => [
                        'title' => $this->l('Display tax in the shopping cart'),
                        'desc'  => $this->l('Select whether or not to display tax on a distinct line in the cart.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'EPH_TAX_ADDRESS_TYPE'                => [
                        'title'      => $this->l('Based on'),
                        'cast'       => 'pSQL',
                        'type'       => 'select',
                        'list'       => [
                            [
                                'name' => $this->l('Invoice address'),
                                'id'   => 'id_address_invoice',
                            ],
                            [
                                'name' => $this->l('Delivery address'),
                                'id'   => 'id_address_delivery',
                            ],
                        ],
                        'identifier' => 'id',
                    ],
                    'EPH_DEFAULT_SPECIFIC_PRICE_RULE_TAX' => [
                        'title'      => $this->l('Default tax for specific price rules'),
                        'desc'       => $this->l('This is the default tax that applies to specific price rules. If you enter a specific price rule with tax and the customer can checkout without paying taxes, then this tax will be subtracted from the specific price rule amount. Does not apply to percentage discounts.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $availableTaxes,
                        'identifier' => 'id_tax',
                    ],
                    'EPH_USE_ECOTAX'                      => [
                        'title'      => $this->l('Use ecotax'),
                        'desc'       => $ecotaxDesc,
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        if (Configuration::get('EPH_USE_ECOTAX') || Tools::getValue('EPH_USE_ECOTAX')) {
            $this->fields_options['general']['fields']['EPH_ECOTAX_TAX_RULES_GROUP_ID'] = [
                'title'      => $this->l('Ecotax'),
                'hint'       => $this->l('Define the ecotax (e.g. French ecotax: 19.6%).'),
                'cast'       => 'intval',
                'type'       => 'select',
                'identifier' => 'id_tax_rules_group',
                'list'       => TaxRulesGroup::getTaxRulesGroupsForOptions(),
            ];
        }

        parent::__construct();

        EmployeeConfiguration::updateValue('EXPERT_TAXES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_TAXES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_TAXES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_TAXES_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_TAXES_FIELDS', Tools::jsonEncode($this->getTaxFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_TAXES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_TAXES_FIELDS', Tools::jsonEncode($this->getTaxFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_TAXES_FIELDS'), true);
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

        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Taxes');

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
            'buildTaxFilter() ' => '
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
        buildTaxFilter();
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
        $paragrid->fillHandle = '\'all\'';

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
        $paragrid->gridExtraFunction = $gridExtraFunction;

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

    public function getTaxRequest() {

        $taxes = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('b.*, a.*, case when a.`active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`, a.`active` as `enable`')
                ->from('tax', 'a')
                ->innerJoin('tax_lang', 'b', 'b.`id_tax` = a.`id_tax` AND b.`id_lang` =   ' . (int) $this->context->language->id)
                ->where('a.`deleted` = 0')
                ->orderBy('a.`id_tax` ASC')
        );

        foreach ($taxes as &$taxe) {

            $taxe['openLink'] = $this->context->link->getAdminLink($this->controller_name) . '&id_tax=' . $taxe['id_tax'] . '&id_object=' . $taxe['id_tax'] . '&action=initUpdateController&ajax=true';

        }

        return $taxes;
    }

    public function ajaxProcessgetTaxRequest() {

        die(Tools::jsonEncode($this->getTaxRequest()));

    }

    public function getTaxFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_tax',
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
                'title'      => $this->l('Rate'),
                'width'      => 200,
                'dataIndx'   => 'rate',
                'cls'        => 'name-handle',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'float',
                'format'     => "#.###,00 %",
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

    public function ajaxProcessgetTaxFields() {

        die(EmployeeConfiguration::get('EXPERT_TAXES_FIELDS'));
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initPageHeaderToolbar() {

        $this->page_header_toolbar_btn['new_tax'] = [
            'href'       => static::$currentIndex . '&action=addObject&ajax=true&addtax&token=' . $this->token,
            'desc'       => $this->l('Add new tax', null, null, false),
            'identifier' => 'new',
            'controller' => $this->controller_name,
            'icon'       => 'process-icon-new',
        ];

        parent::initPageHeaderToolbar();
    }

    public function renderOptions() {

        if ($this->fields_options && is_array($this->fields_options)) {
            $this->tpl_option_vars['titleList'] = $this->l('List') . ' ' . $this->toolbar_title[0];
            $this->tpl_option_vars['controller'] = Tools::getValue('controller');

            $helper = new HelperOptions($this);
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
            $helper->isParagrid = true;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }

        return '';
    }

    /**
     * Display delete action link
     *
     * @param string|null $token
     * @param int         $id
     *
     * @return string
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.9.1.0
     */
    public function displayDeleteLink($id, $token = null) {

        if (!array_key_exists('Delete', static::$cache_lang)) {
            static::$cache_lang['Delete'] = $this->l('Delete');
        }

        if (!array_key_exists('DeleteItem', static::$cache_lang)) {
            static::$cache_lang['DeleteItem'] = $this->l('Delete item #', __CLASS__, true, false);
        }

        if (TaxRule::isTaxInUse($id)) {
            $confirm = $this->l('This tax is currently in use as a tax rule. Are you sure you\'d like to continue?', null, true, false);
        }

        $this->context->smarty->assign(
            [
                'href'    => static::$currentIndex . '&' . $this->identifier . '=' . $id . '&delete' . $this->table . '&token=' . ($token != null ? $token : $this->token),
                'confirm' => (isset($confirm) ? '\r' . $confirm : static::$cache_lang['DeleteItem'] . $id . ' ? '),
                'action'  => static::$cache_lang['Delete'],
            ]
        );

        return $this->context->smarty->fetch('helpers/list/list_action_delete.tpl');
    }

    /**
     * Fetch the template for action enable
     *
     * @param string $token
     * @param int    $id
     * @param int    $value      state enabled or not
     * @param string $active     status
     * @param int    $idCategory
     * @param int    $idProduct
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function displayEnableLink($token, $id, $value, $active, $idCategory = null, $idProduct = null) {

        if ($value && TaxRule::isTaxInUse($id)) {
            $confirm = $this->l('This tax is currently in use as a tax rule. If you continue, this tax will be removed from the tax rule. Are you sure you\'d like to continue?', null, true, false);
        }

        $tplEnable = $this->context->smarty->createTemplate('helpers/list/list_action_enable.tpl');
        $tplEnable->assign(
            [
                'enabled'    => (bool) $value,
                'url_enable' => static::$currentIndex . '&' . $this->identifier . '=' . (int) $id . '&' . $active . $this->table . ((int) $idCategory && (int) $idProduct ? '&id_category=' . (int) $idCategory : '') . '&token=' . ($token != null ? $token : $this->token),
                'confirm'    => isset($confirm) ? $confirm : null,
            ]
        );

        return $tplEnable->fetch();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Taxes'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Tax name to display in carts and on invoices (e.g. "VAT").') . ' - ' . $this->l('Invalid characters') . ' <>;=#{}',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Rate'),
                    'name'      => 'rate',
                    'maxlength' => 6,
                    'required'  => true,
                    'hint'      => $this->l('Format: XX.XX or XX.XXX (e.g. 19.60 or 13.925)') . ' - ' . $this->l('Invalid characters') . ' <>;=#{}',
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
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        if ($this->action == 'save') {
            /* Checking fields validity */
            $this->validateRules();

            if (!count($this->errors)) {
                $id = (int) (Tools::getValue('id_' . $this->table));

                /* Object update */

                if (isset($id) && !empty($id)) {
                    /** @var Tax $object */
                    $object = new $this->className($id);

                    if (Validate::isLoadedObject($object)) {
                        $this->copyFromPost($object, $this->table);
                        $result = $object->update(false, false);

                        if (!$result) {
                            $this->errors[] = Tools::displayError('An error occurred while updating an object.') . ' <b>' . $this->table . '</b>';
                        } else
                        if ($this->postImage($object->id)) {
                            Tools::redirectAdmin(static::$currentIndex . '&id_' . $this->table . '=' . $object->id . '&conf=4' . '&token=' . $this->token);
                        }

                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while updating an object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
                    }

                }

                /* Object creation */
                else {
                    /** @var Tax $object */
                    $object = new $this->className();
                    $this->copyFromPost($object, $this->table);

                    if (!$object->add()) {
                        $this->errors[] = Tools::displayError('An error occurred while creating an object.') . ' <b>' . $this->table . '</b>';
                    } else
                    if (($_POST['id_' . $this->table] = $object->id/* voluntary */) && $this->postImage($object->id) && $this->_redirect) {
                        Tools::redirectAdmin(static::$currentIndex . '&id_' . $this->table . '=' . $object->id . '&conf=3' . '&token=' . $this->token);
                    }

                }

            }

        } else {
            parent::postProcess();
        }

    }

    /**
     * @param mixed $value
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsUseEcotax($value) {

        $oldValue = (int) Configuration::get('EPH_USE_ECOTAX');

        if ($oldValue != $value) {
            // Reset ecotax

            if ($value == 0) {
                Product::resetEcoTax();
            }

            Configuration::updateValue('EPH_USE_ECOTAX', (int) $value);
        }

    }

}
