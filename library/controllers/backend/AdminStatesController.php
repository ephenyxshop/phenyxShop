<?php

/**
 * Class AdminStatesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminStatesControllerCore extends AdminController {

    public $php_self = 'adminstates';
    /**
     * AdminStatesControllerCore constructor.
     *
     * @since 1.8.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'state';
        $this->className = 'State';
        $this->publicName = $this->l('Etats');
        $this->lang = false;

        parent::__construct();

        $this->paragridScript = EmployeeConfiguration::get('EXPERT_STATES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_STATES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_STATES_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STATES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_STATES_FIELDS', Tools::jsonEncode($this->getStateFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STATES_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);

        Media::addJsDef([
            'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
        ]);

    }

    public function initContent() {

        //$this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Country');

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

        $context = Context::getContext();

        $zoneSelector = '{"": "Trier par zones"},';

        foreach (Zone::getZones(true) as $zone) {
            $zoneSelector .= '{"' . $zone['id_zone'] . '": "' . $zone['name'] . '"},';
        }

        $countrySelector = '{"": "Trier par pays"},';
        $countries = Country::getCountries($this->context->language->id, false, true, false);

        foreach ($countries as $country) {
            $countrySelector .= '{"' . $country['id_country'] . '": "' . $country['name'] . '"},';
        }

        $controllerLink = $context->link->getAdminLink($this->controller_name);

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->showNumberCell = 0;
        $paragrid->colModel = EmployeeConfiguration::get('EXPERT_STATES_FIELDS');
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
        $("#countrySelector").selectmenu({
                width: 200,
                "change": function(event, ui) {
                    gridState.filter({
                        mode: "AND",
                        rules: [
                            { dataIndx:"id_country", condition: "equal", value: ui.item.value}
                        ]
                    });
                }
            });
            $("#zoneSelector").selectmenu({
                width: 200,
                "change": function(event, ui) {
                    gridState.filter({
                        mode: "AND",
                        rules: [
                            { dataIndx:"id_zone", condition: "equal", value: ui.item.value}
                        ]
                    });
                }
            });
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Gestion des états') . '\'';
        $paragrid->fillHandle = '\'all\'';
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
        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'' . $this->l('Ajouter un Etat') . '\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addNewState();
                        }',
                ],
                [
                    'type'    => '\'select\'',
                    'icon'    => '\'ui-icon-disk\'',
                    'attr'    => '\'id="countrySelector"\'',
                    'options' => '[' . $countrySelector . ']',
                ],
                [
                    'type'    => '\'select\'',
                    'icon'    => '\'ui-icon-disk\'',
                    'attr'    => '\'id="zoneSelector"\'',
                    'options' => '[
                        ' . $zoneSelector . '
                        ]',
                ],

            ],
        ];
        $paragrid->rowDblClick = 'function( event, ui ) {

        } ';
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
                        "add": {
                            name: \'' . $this->l('Ajouter un nouvel Etat') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addNewState();
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Voir ou éditer l‘état : ') . '\'' . '+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editState(rowData.id_state)
                            }
                        },


                        "sep1": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer le Pays :') . '\'' . '+rowData.name,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteState(rowData.id_state);
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

    public function getStateRequest() {

        if (is_object($this->_controller_request) && $this->_controller_request->exists('stateGridRequest')) {

            return $this->_controller_request->get('stateGridRequest');
        }

        $states = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.* , z.`name` AS zone, cl.`name` AS country ')
                ->from('state', 'a')
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = a.`id_country` AND cl.id_lang = ' . (int) $this->context->language->id)
                ->leftJoin('zone', 'z', 'z.`id_zone` = a.`id_zone`')
                ->orderBy('a.`id_state` ASC')
        );
        $stateLink = $this->context->link->getAdminLink($this->controller_name);

        foreach ($states as &$state) {

            if ($state['active'] == 1) {
                $state['active'] = '<div class="p-active"></div>';
            } else {
                $state['active'] = '<div class="p-inactive"></div>';
            }

        }

        if (is_object($this->_controller_request)) {
            $this->_controller_request->set('stateGridRequest', $zones);
        }

        return $states;

    }

    public function ajaxProcessgetStateRequest() {

        die(Tools::jsonEncode($this->getStateRequest()));

    }

    public function getStateFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_state',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
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
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'      => $this->l('ISO code'),
                'width'      => 200,
                'dataIndx'   => 'iso_code',
                'cls'        => 'name-handle',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'      => $this->l('Id Country'),
                'minWidth'   => 150,
                'dataIndx'   => 'id_country',
                'dataType'   => 'integer',
                'editable'   => false,
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Country'),
                'width'    => 200,
                'dataIndx' => 'country',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',

            ],
            [
                'title'      => $this->l('Id Zone'),
                'dataIndx'   => 'id_zone',
                'dataType'   => 'integer',
                'editable'   => false,
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Zone'),
                'width'    => 200,
                'dataIndx' => 'zone',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Enabled'),
                'width'    => 200,
                'dataIndx' => 'active',
                'align'    => 'center',
                'editable' => false,
                'dataType' => 'html',

            ],

        ];
    }

    public function ajaxProcessgetStateFields() {

        die(EmployeeConfiguration::get('EXPERT_STATES_FIELDS'));
    }

    public function ajaxProcessAddNewState() {

        $_GET['addstate'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessEditState() {

        $idState = Tools::getValue('idState');
        $_GET['id_state'] = $idState;
        $_GET['updatestate'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.8.1.0
     */
    public function renderForm() {

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('States'),
                'icon'  => 'icon-globe',
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
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 32,
                    'required'  => true,
                    'hint'      => $this->l('Provide the State name to be display in addresses and on invoices.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'maxlength' => 7,
                    'required'  => true,
                    'class'     => 'uppercase',
                    'hint'      => $this->l('1 to 4 letter ISO code.') . ' ' . $this->l('You can prefix it with the country ISO code if needed.'),
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id, false, true),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                    'hint'          => $this->l('Country where the state is located.') . ' ' . $this->l('Only the countries with the option "contains states" enabled are displayed.'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Zone'),
                    'name'     => 'id_zone',
                    'required' => true,
                    'options'  => [
                        'query' => Zone::getZones(),
                        'id'    => 'id_zone',
                        'name'  => 'name',
                    ],
                    'hint'     => [
                        $this->l('Geographical region where this state is located.'),
                        $this->l('Used for shipping'),
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="' . $this->l('Enabled') . '" title="' . $this->l('Enabled') . '" />',
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" />',
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['ajax'] = 1;

        if ($this->obj->id > 0) {
            $this->fields_value['action'] = 'updateState';

        } else {
            $this->fields_value['action'] = 'addState';
        }

        return parent::renderForm();
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        if (Tools::isSubmit($this->table . 'Orderby') || Tools::isSubmit($this->table . 'Orderway')) {
            $this->filter = true;
        }

        // Idiot-proof controls

        if (!Tools::getValue('id_' . $this->table)) {

            if (Validate::isStateIsoCode(Tools::getValue('iso_code')) && State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }

        } else

        if (Validate::isStateIsoCode(Tools::getValue('iso_code'))) {
            $idState = State::getIdByIso(Tools::getValue('iso_code'), Tools::getValue('id_country'));

            if ($idState && $idState != Tools::getValue('id_' . $this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists. You cannot create two states with the same ISO code.');
            }

        }

        /* Delete state */

        if (Tools::isSubmit('delete' . $this->table)) {

            if ($this->tabAccess['delete'] === '1') {

                if (Validate::isLoadedObject($object = $this->loadObject())) {
                    /** @var State $object */

                    if (!$object->isUsed()) {

                        if ($object->delete()) {
                            Tools::redirectAdmin(static::$currentIndex . '&conf=1&token=' . (Tools::getValue('token') ? Tools::getValue('token') : $this->token));
                        }

                        $this->errors[] = Tools::displayError('An error occurred during deletion.');
                    } else {
                        $this->errors[] = Tools::displayError('This state was used in at least one address. It cannot be removed.');
                    }

                } else {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
                }

            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }

        }

        if (!count($this->errors)) {
            parent::postProcess();
        }

    }

    /**
     * Display ajax states
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function displayAjaxStates() {

        $states = Db::getInstance()->executeS(
            '
        SELECT s.id_state, s.name
        FROM ' . _DB_PREFIX_ . 'state s
        LEFT JOIN ' . _DB_PREFIX_ . 'country c ON (s.`id_country` = c.`id_country`)
        WHERE s.id_country = ' . (int) (Tools::getValue('id_country')) . ' AND s.active = 1 AND c.`contains_states` = 1
        ORDER BY s.`name` ASC'
        );

        if (is_array($states) and !empty($states)) {
            $list = '';

            if ((bool) Tools::getValue('no_empty') != true) {
                $emptyValue = (Tools::isSubmit('empty_value')) ? Tools::getValue('empty_value') : '-';
                $list = '<option value="0">' . Tools::htmlentitiesUTF8($emptyValue) . '</option>' . "\n";
            }

            foreach ($states as $state) {
                $list .= '<option value="' . (int) ($state['id_state']) . '"' . ((isset($_GET['id_state']) and $_GET['id_state'] == $state['id_state']) ? ' selected="selected"' : '') . '>' . $state['name'] . '</option>' . "\n";
            }

        } else {
            $list = 'false';
        }

        die($list);
    }

}
