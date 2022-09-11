<?php

/**
 * @property Group $object
 */
class AdminGroupsControllerCore extends AdminController {

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'group';
        $this->className = 'Group';
        $this->publicName = $this->l('Groups');
        $this->lang = true;
        $this->identifier = 'id_group';
        $this->controller_name = 'AdminGroups';

        $grouEPH_to_keep = [
            Configuration::get('EPH_UNIDENTIFIED_GROUP'),
            Configuration::get('EPH_GUEST_GROUP'),
            Configuration::get('EPH_CUSTOMER_GROUP'),
        ];

        parent::__construct();

        if (Shop::isFeatureActive()) {
            $this->fields_options = [
                'general' => [
                    'title'  => $this->l('Default groups options'),
                    'fields' => [
                        'EPH_UNIDENTIFIED_GROUP' => [
                            'title'      => $this->l('Visitors group'),
                            'desc'       => $this->l('The group defined for your un-identified visitors.'),
                            'cast'       => 'intval',
                            'type'       => 'select',
                            'list'       => $groups,
                            'identifier' => 'id_group',
                        ],
                        'EPH_GUEST_GROUP'        => [
                            'title'      => $this->l('Guests group'),
                            'desc'       => $this->l('The group defined for your identified guest customers (used in guest checkout).'),
                            'cast'       => 'intval',
                            'type'       => 'select',
                            'list'       => $groups,
                            'identifier' => 'id_group',
                        ],
                        'EPH_CUSTOMER_GROUP'     => [
                            'title'      => $this->l('Customers group'),
                            'desc'       => $this->l('The group defined for your identified registered customers.'),
                            'cast'       => 'intval',
                            'type'       => 'select',
                            'list'       => $groups,
                            'identifier' => 'id_group',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }

        EmployeeConfiguration::updateValue('EXPERT_GROUP_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_GROUP_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_GROUP_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_GROUP_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_GROUP_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_GROUP_FIELDS', Tools::jsonEncode($this->getGroupFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_GROUP_FIELDS'), true);
        }

    }

    public function generateParaGridScript($regenerate = false) {

        $showPrice = '<div class="pq-theme"><select id="showPriceSelect"><option value="">' . $this->l('--Select--') . '</option>';
        $showPrice .= '<option value="1">' . $this->l('Yes') . '</option>';
        $showPrice .= '<option value="0">' . $this->l('No') . '</option>';
        $showPrice .= '</select></div>';

        $displayMethod = '<div class="pq-theme"><select id="displayMethodSelect"><option value="">' . $this->l('--Select--') . '</option>';
        $displayMethod .= '<option value="' . EPH_TAX_EXC . '">' . $this->l('Tax excluded') . '</option>';
        $displayMethod .= '<option value="' . EPH_TAX_INC . '">' . $this->l('Tax included') . '</option>';
        $displayMethod .= '</select></div>';

        $gridExtraFunction = ['function buildGroupFilter(){
            var showPriceconteneur = $(\'#showPriceSelector\').parent().parent();
            $(showPriceconteneur).empty();
            $(showPriceconteneur).append(\'' . $showPrice . '\');
            $(\'#showPriceSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'showPrice\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            var displayMethodconteneur = $(\'#displayMethodSelector\').parent().parent();
            $(displayMethodconteneur).empty();
            $(displayMethodconteneur).append(\'' . $displayMethod . '\');
            $(\'#displayMethodSelect\' ).selectmenu({
                "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'displayMethod\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });

            }'];

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->showNumberCell = 0;
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        $paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->complete = 'function(){
        buildGroupFilter();
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'' . $this->l('Ajouter un Groupe') . '\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addNewGroup();
                        }',
                ],

            ],
        ];
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Géstion des groupes Client') . '\'';
        $paragrid->fillHandle = '\'all\'';
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
                return {
                    callback: function(){},
                    items: {

                        "edit": {
                            name : \'' . $this->l('Modifier le groupe : ') . '\'' . '+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editGroup(rowData.id_group)
                            }
                        },
                         "view": {
                            name : \'' . $this->l('Consulter le groupe : ') . '\'' . '+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                viewGroup(rowData.id_group)
                            }
                        },

                        "sep1": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer le groupe :') . '\'' . '+rowData.name,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteGroup(rowData.id_group);
                            }
                        },


                    },
                };
            }',
            ]];

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

        $this->paragridScript = $script;
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getGroupRequest() {

        $groups = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.`id_group`, a.`reduction`, a.`show_prices`, a.`price_display_method`, a.`date_add`, b.`name`')
                ->from('group', 'a')
                ->leftJoin('group_lang', 'b', 'b.`id_group` = a.`id_group` AND b.`id_lang` = ' . (int) $this->context->language->id)
                ->orderBy('a.`id_group` ASC')
        );
        $groupLink = $this->context->link->getAdminLink($this->controller_name);

        foreach ($groups as &$group) {

            $group['nb'] = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('COUNT(cg.`id_customer`)')
                    ->from('customer_group', 'cg')
                    ->leftJoin('customer', 'c', 'c.`id_customer` = cg.`id_customer`')
                    ->where('c.`deleted` != 1 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c') . ' AND cg.`id_group` = ' . (int) $group['id_group'])
            );

            if ($group['show_prices'] == 1) {
                $group['showPrice'] = true;
                $group['show_prices'] = '<div class="p-active"></div>';
            } else {
                $group['showPrice'] = false;
                $group['show_prices'] = '<div class="p-inactive"></div>';
            }

            if ($group['price_display_method'] == EPH_TAX_EXC) {
                $group['displayMethod'] = EPH_TAX_EXC;
                $group['price_display_method'] = $this->l('Tax excluded');
            } else {
                $group['displayMethod'] = EPH_TAX_INC;
                $group['price_display_method'] = $this->l('Tax included');
            }

            $group['addLink'] = $groupLink . '&action=addObject&ajax=true&addaddress';
            $group['openLink'] = $groupLink . '&id_group=' . $group['id_group'] . '&id_object=' . $group['id_group'] . '&updategroup&action=initUpdateController&ajax=true';

        }

        return $groups;

    }

    public function ajaxProcessgetGroupRequest() {

        die(Tools::jsonEncode($this->getGroupRequest()));

    }

    public function getGroupFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 70,
                'exWidth'    => 15,
                'dataIndx'   => 'id_group',
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
                'title'    => $this->l('Group name'),
                'minWidth' => 150,
                'exWidth'  => 40,
                'dataIndx' => 'name',
                'halign'   => 'HORIZONTAL_LEFT',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],

                ],
            ],
            [
                'title'    => $this->l('Discount (%)'),
                'width'    => 150,
                'dataIndx' => 'reduction',
                'dataType' => 'float',
                'format'   => '# ##0,00 %',
                'align'    => 'center',
                'valign'   => 'center',
                'editable' => false,
                'hidden'   => false,

            ],
            [
                'title'    => $this->l('Members'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'nb',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'integer',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'showPrice',
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
                'title'    => $this->l('Show prices'),
                'width'    => 150,
                'dataIndx' => 'show_prices',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"showPriceSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'displayMethod',
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
                'title'    => $this->l('Display prices'),
                'width'    => 100,
                'dataIndx' => 'price_display_method',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'string',
                'editable' => false,
                'filter'   => [
                    'attr'   => "id=\"displayMethodSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],
            ],

            [
                'title'    => $this->l('Creation date'),
                'minWidth' => 150,
                'exWidth'  => 20,
                'cls'      => 'rangeDate',
                'dataIndx' => 'date_add',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,
                'filter'   => [

                    'crules' => [['condition' => "between"]],
                ],
            ],
        ];

    }

    public function ajaxProcessgetGroupFields() {

        die(EmployeeConfiguration::get('EXPERT_GROUP_FIELDS'));
    }

    public function initProcess() {

        $this->id_object = Tools::getValue('id_' . $this->table);

        if (Tools::isSubmit('changeShowPricesVal') && $this->id_object) {
            $this->action = 'change_show_prices_val';
        }

        if (Tools::getIsset('viewgroup')) {
            $this->list_id = 'customer_group';

            if (isset($_POST['submitReset' . $this->list_id])) {
                $this->processResetFilters();
            }

            if (Tools::getIsset('submitFilter' . $this->list_id)) {
                static::$currentIndex .= '&id_group=' . (int) Tools::getValue('id_group') . '&viewgroup';
            }

        } else {
            $this->list_id = 'group';
        }

        parent::initProcess();
    }

    public function ajaxProcessViewGroup() {

        $idGroup = Tools::getValue('idGroup');
        $_GET['id_group'] = $idGroup;
        $_GET['viewgroup'] = "";

        $html = $this->renderView();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function renderView() {

        $this->context = Context::getContext();

        if (!($group = $this->loadObject(true))) {
            return;
        }

        $this->tpl_view_vars = [
            'group'               => $group,
            'language'            => $this->context->language,
            'customerList'        => $this->renderCustomersList($group),
            'categorieReductions' => $this->formatCategoryDiscountList($group->id),
        ];

        return parent::renderView();
    }

    protected function renderCustomersList($group) {

        $genders = [0 => '?'];
        $genders_icon = ['default' => 'unknown.gif'];

        foreach (Gender::getGenders() as $gender) {
            /** @var Gender $gender */
            $genders_icon[$gender->id] = '../genders/' . (int) $gender->id . '.jpg';
            $genders[$gender->id] = $gender->name;
        }

        $this->table = 'customer_group';
        $this->lang = false;
        $this->list_id = 'customer_group';
        $this->actions = [];
        $this->addRowAction('edit');
        $this->identifier = 'id_customer';
        $this->bulk_actions = false;
        $this->list_no_link = true;
        $this->explicitSelect = true;

        $this->fields_list = ([
            'id_customer' => ['title' => $this->l('ID'), 'align' => 'center', 'filter_key' => 'c!id_customer', 'class' => 'fixed-width-xs'],
            'id_gender'   => ['title' => $this->l('Social title'), 'icon' => $genders_icon, 'list' => $genders],
            'firstname'   => ['title' => $this->l('First name')],
            'lastname'    => ['title' => $this->l('Last name')],
            'email'       => ['title' => $this->l('Email address'), 'filter_key' => 'c!email', 'orderby' => true],
            'birthday'    => ['title' => $this->l('Birth date'), 'type' => 'date', 'class' => 'fixed-width-md', 'align' => 'center'],
            'date_add'    => ['title' => $this->l('Registration date'), 'type' => 'date', 'class' => 'fixed-width-md', 'align' => 'center'],
            'active'      => ['title' => $this->l('Enabled'), 'align' => 'center', 'class' => 'fixed-width-sm', 'type' => 'bool', 'search' => false, 'orderby' => false, 'filter_key' => 'c!active', 'callback' => 'printOptinIcon'],
        ]);
        $this->_select = 'c.*, a.id_group';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (a.`id_customer` = c.`id_customer`)';
        $this->_where = 'AND a.`id_group` = ' . (int) $group->id . ' AND c.`deleted` != 1';
        $this->_where .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c');
        self::$currentIndex = self::$currentIndex . '&id_group=' . (int) $group->id . '&viewgroup';

        $this->processFilter();
        return parent::renderList();
    }

    public function ajaxProcessAddNewGroup() {

        $_GET['addgroup'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessEditGroup() {

        $idGroup = Tools::getValue('idGroup');
        $_GET['id_group'] = $idGroup;
        $_GET['updategroup'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function renderForm() {

        $this->displayGrid = false;

        if (!($group = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Customer group'),
                'icon'  => 'icon-group',
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'col'      => 4,
                    'hint'     => $this->l('Forbidden characters:') . ' 0-9!&amp;lt;&amp;gt;,;?=+()@#"�{}_$%:',
                ],
                [
                    'type'   => 'text',
                    'label'  => $this->l('Discount'),
                    'name'   => 'reduction',
                    'suffix' => '%',
                    'col'    => 1,
                    'hint'   => $this->l('Automatically apply this value as a discount on all products for members of this customer group.'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Price display method'),
                    'name'    => 'price_display_method',
                    'col'     => 2,
                    'hint'    => $this->l('How prices are displayed in the order summary for this customer group.'),
                    'options' => [
                        'query' => [
                            [
                                'id_method' => EPH_TAX_EXC,
                                'name'      => $this->l('Tax excluded'),
                            ],
                            [
                                'id_method' => EPH_TAX_INC,
                                'name'      => $this->l('Tax included'),
                            ],
                        ],
                        'id'    => 'id_method',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Show prices'),
                    'name'     => 'show_prices',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'show_prices_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'show_prices_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'     => $this->l('Customers in this group can view prices.'),
                ],
                [
                    'type'   => 'group_discount_category',
                    'label'  => $this->l('Category discount'),
                    'name'   => 'reduction',
                    'values' => ($group->id ? $this->formatCategoryDiscountList((int) $group->id) : [])
                ],
                [
                    'type'   => 'modules',
                    'label'  => $this->l('Modules Authorization'),
                    'name'   => 'auth_modules',
                    'values' => $this->formatModuleListAuth($group->id),
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        if (Tools::getIsset('addgroup')) {
            $this->fields_value['price_display_method'] = Configuration::get('PRICE_DISPLAY_METHOD');
        }

        $this->fields_value['reduction'] = isset($group->reduction) ? $group->reduction : 0;

        $tree = new HelperTreeCategories('categories-tree');
        $this->tpl_form_vars['categoryTreeView'] = $tree->setRootCategory((int) Category::getRootCategory()->id)->render();

        $this->fields_value['ajax'] = 1;

        if ($group->id > 0) {
            $this->fields_value['action'] = 'updateGroup';

        } else {
            $this->fields_value['action'] = 'addGroup';
        }

        return parent::renderForm();
    }

    protected function formatCategoryDiscountList($id_group) {

        $group_reductions = GroupReduction::getGroupReductions((int) $id_group, $this->context->language->id);
        $category_reductions = [];
        $category_reduction = Tools::getValue('category_reduction');

        foreach ($group_reductions as $category) {

            if (is_array($category_reduction) && array_key_exists($category['id_category'], $category_reduction)) {
                $category['reduction'] = $category_reduction[$category['id_category']];
            }

            $category_reductions[(int) $category['id_category']] = [
                'path'        => getPath($this->context->link->getAdminLink('AdminCategories'), (int) $category['id_category']),
                'reduction'   => (float) $category['reduction'] * 100,
                'id_category' => (int) $category['id_category'],
            ];
        }

        if (is_array($category_reduction)) {

            foreach ($category_reduction as $key => $val) {

                if (!array_key_exists($key, $category_reductions)) {
                    $category_reductions[(int) $key] = [
                        'path'        => getPath($this->context->link->getAdminLink('AdminCategories'), $key),
                        'reduction'   => (float) $val * 100,
                        'id_category' => (int) $key,
                    ];
                }

            }

        }

        return $category_reductions;
    }

    public function formatModuleListAuth($id_group) {

        $modules = Module::getModulesInstalled();
        $authorized_modules = '';

        $auth_modules = [];
        $unauth_modules = [];

        if ($id_group) {
            $authorized_modules = Module::getAuthorizedModules($id_group);
        }

        if (is_array($authorized_modules)) {

            foreach ($modules as $module) {
                $authorized = false;

                foreach ($authorized_modules as $auth_module) {

                    if ($module['id_module'] == $auth_module['id_module']) {
                        $authorized = true;
                    }

                }

                if ($authorized) {
                    $auth_modules[] = $module;
                } else {
                    $unauth_modules[] = $module;
                }

            }

        } else {
            $auth_modules = $modules;
        }

        $auth_modules_tmp = [];

        foreach ($auth_modules as $key => $val) {

            if ($module = Module::getInstanceById($val['id_module'])) {
                $auth_modules_tmp[] = $module;
            }

        }

        $auth_modules = $auth_modules_tmp;

        $unauth_modules_tmp = [];

        foreach ($unauth_modules as $key => $val) {

            if (($tmp_obj = Module::getInstanceById($val['id_module']))) {
                $unauth_modules_tmp[] = $tmp_obj;
            }

        }

        $unauth_modules = $unauth_modules_tmp;

        return ['unauth_modules' => $unauth_modules, 'auth_modules' => $auth_modules];
    }

    public function processSave() {

        if (!$this->validateDiscount(Tools::getValue('reduction'))) {
            $this->errors[] = Tools::displayError('The discount value is incorrect (must be a percentage).');
        } else {
            $this->updateCategoryReduction();
            $object = parent::processSave();
            $this->updateRestrictions();
            return $object;
        }

    }

    protected function validateDiscount($reduction) {

        if (!Validate::isPrice($reduction) || $reduction > 100 || $reduction < 0) {
            return false;
        } else {
            return true;
        }

    }

    public function ajaxProcessAddCategoryReduction() {

        $category_reduction = Tools::getValue('category_reduction');
        $id_category = Tools::getValue('id_category'); //no cast validation is done with Validate::isUnsignedId($id_category)

        $result = [];

        if (!Validate::isUnsignedId($id_category)) {
            $result['errors'][] = Tools::displayError('Wrong category ID.');
            $result['hasError'] = true;
        } else
        if (!$this->validateDiscount($category_reduction)) {
            $result['errors'][] = Tools::displayError('The discount value is incorrect (must be a percentage).');
            $result['hasError'] = true;
        } else {
            $result['id_category'] = (int) $id_category;
            $result['catPath'] = getPath(static::$currentIndex . '?tab=AdminCategories', (int) $id_category);
            $result['discount'] = $category_reduction;
            $result['hasError'] = false;
        }

        $this->ajaxDie(json_encode($result));
    }

    /**
     * Update (or create) restrictions for modules by group
     */
    protected function updateRestrictions() {

        $id_group = Tools::getValue('id_group');
        $auth_modules = Tools::getValue('modulesBoxAuth');
        $return = true;

        if ($id_group) {
            Group::truncateModulesRestrictions((int) $id_group);
        }

        $shops = Shop::getShops(true, null, true);

        if (is_array($auth_modules)) {
            $return &= Group::addModulesRestrictions($id_group, $auth_modules, $shops);
        }

        return $return;
    }

    protected function updateCategoryReduction() {

        $category_reduction = Tools::getValue('category_reduction');
        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'group_reduction`
            WHERE `id_group` = ' . (int) Tools::getValue('id_group')
        );
        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'product_group_reduction_cache`
            WHERE `id_group` = ' . (int) Tools::getValue('id_group')
        );

        if (is_array($category_reduction) && count($category_reduction)) {

            if (!Configuration::getGlobalValue('EPH_GROUP_FEATURE_ACTIVE')) {
                Configuration::updateGlobalValue('EPH_GROUP_FEATURE_ACTIVE', 1);
            }

            foreach ($category_reduction as $cat => $reduction) {

                if (!Validate::isUnsignedId($cat) || !$this->validateDiscount($reduction)) {
                    $this->errors[] = Tools::displayError('The discount value is incorrect.');
                } else {
                    $category = new Category((int) $cat);
                    $category->addGroupsIfNoExist((int) Tools::getValue('id_group'));
                    $group_reduction = new GroupReduction();
                    $group_reduction->id_group = (int) Tools::getValue('id_group');
                    $group_reduction->reduction = (float) ($reduction / 100);
                    $group_reduction->id_category = (int) $cat;

                    if (!$group_reduction->save()) {
                        $this->errors[] = Tools::displayError('You cannot save group reductions.');
                    }

                }

            }

        }

    }

    /**
     * Toggle show prices flag
     */
    public function processChangeShowPricesVal() {

        $group = new Group($this->id_object);

        if (!Validate::isLoadedObject($group)) {
            $this->errors[] = Tools::displayError('An error occurred while updating this group.');
        }

        $update = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'group` SET show_prices = ' . ($group->show_prices ? 0 : 1) . ' WHERE `id_group` = ' . (int) $group->id);

        if (!$update) {
            $this->errors[] = Tools::displayError('An error occurred while updating this group.');
        }

        Tools::clearSmartyCache();
        Tools::redirectAdmin(static::$currentIndex . '&token=' . $this->token);
    }

    /**
     * Print enable / disable icon for show prices option
     *
     * @param $id_group integer Group ID
     * @param $tr array Row data
     * @return string HTML link and icon
     */
    public function printShowPricesIcon($id_group, $tr) {

        $group = new Group($tr['id_group']);

        if (!Validate::isLoadedObject($group)) {
            return;
        }

        return '<a class="list-action-enable' . ($group->show_prices ? ' action-enabled' : ' action-disabled') . '" href="index.php?tab=AdminGroups&amp;id_group=' . (int) $group->id . '&amp;changeShowPricesVal&amp;token=' . Tools::getAdminTokenLite('AdminGroups') . '">
                ' . ($group->show_prices ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>') .
            '</a>';
    }

    public function displayEditLink($id, $token = null, $name = null) {

        $tpl = $this->createTemplate('helpers/list/list_action_edit.tpl');

        if (!array_key_exists('Edit', static::$cache_lang)) {
            static::$cache_lang['Edit'] = $this->l('Edit', 'Helper');
        }

        $href = static::$currentIndex . '&' . $this->identifier . '=' . $id . '&update' . $this->table . '&token=' . ($token != null ? $token : $this->token);

        if ($this->display == 'view') {
            $href = $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . (int) $id . '&updatecustomer&back=' . urlencode($href);
        }

        $tpl->assign(
            [
                'href'   => $href,
                'action' => static::$cache_lang['Edit'],
                'id'     => $id,
            ]
        );

        return $tpl->fetch();
    }

}
