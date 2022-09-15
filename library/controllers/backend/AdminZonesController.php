<?php

/**
 * Class AdminZonesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminZonesControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    /** @var string $asso_type */
    public $asso_type = 'shop';
    // @codingStandardsIgnoreEnd

    /**
     * AdminZonesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'zone';
        $this->className = 'Zone';
        $this->publicName = $this->l('Zone');
        $this->lang = false;
        $this->identifier = 'id_zone';

        parent::__construct();

        $this->paragridScript = EmployeeConfiguration::get('EXPERT_ZONES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_ZONES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_ZONES_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ZONES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_ZONES_FIELDS', Tools::jsonEncode($this->getZoneFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ZONES_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);

        Media::addJsDef([
            'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
        ]);

    }

    public function initContent() {

        $this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Zone');

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
        $controllerLink = $context->link->getAdminLink($this->controller_name);

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->colModel = EmployeeConfiguration::get('EXPERT_ZONES_FIELDS');
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
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
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'' . $this->l('Ajouter une zone') . '\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addNewZone();
                        }',
                ],

            ],
        ];
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Géstion des zones') . '\'';
        $paragrid->fillHandle = '\'all\'';
        $paragrid->rowDblClick = 'function( event, ui ) {
            editZones(ui.rowData.id_zone);
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
                            name: \'' . $this->l('Ajouter une nouvelle zone') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addNewZone();
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Voir ou éditer la zone : ') . '\'' . '+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editZones(rowData.id_zone)
                            }
                        },


                        "sep1": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer la zone :') . '\'' . '+rowData.name,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteZone(rowData.id_zone);
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

    public function getZoneRequest() {

        if (is_object($this->_controller_request) && $this->_controller_request->exists('zoneGridRequest')) {

            return $this->_controller_request->get('zoneGridRequest');
        }

        $zones = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*, case when `active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`')
                ->from('zone')
                ->orderBy('`id_zone` ASC')
        );

        if (is_object($this->_controller_request)) {
            $this->_controller_request->set('zoneGridRequest', $zones);
        }

        return $zones;
    }

    public function ajaxProcessgetZoneRequest() {

        die(Tools::jsonEncode($this->getZoneRequest()));

    }

    public function getZoneFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_zone',
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

    public function ajaxProcessgetZoneFields() {

        die(EmployeeConfiguration::get('EXPERT_ZONES_FIELDS'));
    }

    public function ajaxProcessAddNewZone() {

        $_GET['addzone'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcesseditZones() {

        $idZone = Tools::getValue('idZone');
        $_GET['id_zone'] = $idZone;
        $_GET['updatezone'] = "";

        $html = $this->renderForm();
        $result = [
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        if (!$this->loadObject(true)) {
            return '';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Zones'),
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
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Zone name (e.g. Africa, West Coast, Neighboring Countries).'),
                ],
                [
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
                    'hint'     => $this->l('Allow or disallow shipping to this zone.'),
                ],
            ],
        ];

        

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $this->fields_value['ajax'] = 1;

        if ($this->object->id > 0) {
            $this->fields_value['action'] = 'updateZone';

        } else {
            $this->fields_value['action'] = 'addZone';
        }

        return parent::renderForm();
    }

    public function ajaxProcessUpdateZone() {

        $id_zone = Tools::getValue('id_zone');
        $zone = new Zone($id_zone);

        foreach ($_POST as $key => $value) {

            if (property_exists($zone, $key) && $key != 'id_zone') {
                $zone->{$key}
                = $value;
            }

        }

        $result = $zone->update();

        $return = [
            'success' => true,
            'message' => $this->l('La zone a été misà jour avec succès'),
        ];

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessAddZone() {

        $zone = new Zone();

        foreach ($_POST as $key => $value) {

            if (property_exists($zone, $key) && $key != 'id_zone') {
                $zone->{$key}
                = $value;
            }

        }

        $result = $zone->add();

        $return = [
            'success' => true,
            'message' => $this->l('La zone a été ajouté avec succès'),
        ];

        die(Tools::jsonEncode($return));
    }

}
