<?php

/**
 * Class AdminHookPositionsControllerCore
 *
 * @since 1.9.1.0
 */
class AdminHookPositionsControllerCore extends AdminController {

    public $php_self = 'adminhookpositions';
    
    public $hooks;
    /**
     * AdminCountriesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'hook_position';
        $this->className = 'HookPosition';
        $this->publicName = $this->l('Front Office Hooks');
        $this->lang = true;

        parent::__construct();
        $this->hooks = HookPosition::getHooks();
        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/hook.css',

        ]);
        $this->extra_vars = [
            'hooks'     => $this->hooks,
        ];

    }
    
    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_JS_DIR_ . 'hook.js',
        ]);
    }    

    public function generateParaGridScript($regenerate = false) {

        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        
        $this->requestModel = '{
			location: "remote",
            dataType: "json",
            method: "GET",
			recIndx: "id",
			url: AjaxLink' . $this->controller_name . ',
            postData: function () {
                return {
                    action: "getHookPositionRequest",
                    ajax: 1
                };
            },
            getData: function (dataJSON) {
                if (dataJSON && dataJSON.length) {
                    $.each(dataJSON, function( index, value ) {
                        if(value.modules.length)
                        dataJSON[index][\'pq_detail\'] = { \'show\': true };
                    });
                    
                }
				return { data: dataJSON };
            }


        }';

        $this->paramComplete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\''.$this->l('Declare a new Hook').'\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
                ],

            ],
        ];

        $this->paramTitle = '\'' . $this->l('Management of live Front Office Hooks') . '\'';
        
        $this->detailModel = [
            'cache'=> true,
            'collapseIcon'=> '\'ui-icon-triangle-1-e\'',
            'expandIcon'=> '\'ui-icon-triangle-1-se\'',
            'init'         => 'function (ui) {
            	var rowData = ui.rowData;
                var model = sub' . $this->className . 'Model(rowData.modules);
                var $grid = $(\'<div id="sub' . $this->className . '"></div>\').pqGrid(model);
                rowData[ \'pq_detail\' ][ \'show\' ] == true;
               
                return $grid;
            }',
        ];
        
        $this->subDetailModel = [
            'sub' . $this->className . 'Model' => [
                'dataModel'      => [
                    'recIndx' => '\'id_module\'',
                    'data'    => 'data',
                ],
                'colModel'       => "getHookModulesFields()",
                'rowInit'        => 'function (ui) {
                    return {' . PHP_EOL . '
                        attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="Module" data-rowIndx="\' + ui.rowIndx+\'"  data-object="\' + ui.rowData.id_module+\' "\',
                    };
                }',
                'scrollModel'    => [
                    'autoFit' => true,
                ],
                'numberCell'     => [
                    'show' => 0,
                ],
                'height'         => '\'flex\'',
                'showTitle'      => 0,
                'showToolbar' => 0,
                'showTop' => 0,
                'showBottom' => 0,
                'collapsible'    => 0,
                'freezeCols'     => 0,
                'rowBorders'     => 0,
                'selectionModel' => [
                    'type' => '\'row\'',
                ],	
                'dragModel'      => [
                    'on'        => true,
                        'diHelper'  => "['position']",
                        'clsHandle' => '\'dragHandle\'',
                    ],
                'dropModel'      => [
                    'on' => true,
                ],
                'moveNode'       => 'function(event, ui) {
                    var startIndex = ui.args[0][0].modulePosition;
                    var idModule = ui.args[0][0].id_module;
                    var stopIndex = parseInt(ui.args[1]);
                    var way = (startIndex < stopIndex) ? 1 : 0;
                    processModulePosition(way, startIndex, stopIndex, idModule);
                    grid'.$this->className . '.refreshDataAndView();
                }',
            ],
        ];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getHookPositionRequest() {

        $hooks = $this->hooks;
        
        foreach ($hooks as &$hook) {

           $hook->action = '<a id="dropDownAction_'.$hook->id.'" class="btn btn-default fg-button fg-button-icon-right ui-widget ui-state-default ui-corner-all" href="#detail_'.$hook->id.'" title="">'.$this->l('Action').'<span class="ui-icon ui-icon-triangle-1-s" style="margin: 0px 8px"></span></a><div id="detail_'.$hook->id.'" class="hidden"><ul><li><a class="btn btn-default" href="javascript:void(0)"  onClick="editAjaxObject(\'' . $this->controller_name . '\', '.$hook->id.')">'.$this->l('Edit Hook').' '.$hook->title.'</a></li><li><a class="btn btn-default" href="javascript:void(0)" onClick="deleteObject(\'' . $this->controller_name . '\', \'' . $this->className . '\', \''.$this->l('Deelete Hook').'\', \''.$this->l('Are you sure you want to delete this Hook?').'\', \''.$this->l('Yes').'\', \''.$this->l('Cancel').'\','.$hook->id.')">'.$this->l('Deelete Hook').'</a></li></ul></div><script type="text/javascript">$("#dropDownAction_'.$hook->id.'").dropdownmenu({content: $("#dropDownAction_'.$hook->id.'").next().html(),showSpeed: 400,width: 250});</script>';

        }

        

        return $hooks;

    }

    public function ajaxProcessgetHookPositionRequest() {
        
        $result = Tools::jsonEncode($this->getHookPositionRequest());
        die($result);

    }

    public function getHookPositionFields() {

        return [
            [
                'title'     => '',
                'minWidth'  => 27,
                'maxWidth'  => 27,
                'type'      => 'detail',
                'resizable' => false,
                'editable'  => false,
                'sortable'  => false,
                'hidden'    => false,
                'show'      => true
            ],
            [
               
                'dataIndx'   => 'id',
                'dataType'   => 'integer',
                'hidden'    => true,
                'hiddenable' => 'no',
            ],
            
            [
                'title'      => $this->l('Hook Name'),
                'width'      => 200,
                'dataIndx'   => 'name',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'      => $this->l('Hook Target'),
                'width'      => 100,
                'dataIndx'   => 'target',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'      => $this->l('Hook Title'),
                'width'      => 100,
                'dataIndx'   => 'title',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Hook Description'),
                'width'    => 200,
                'dataIndx' => 'description',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',

            ],            
            [
                'title'    => $this->l('Action'),
                'width'    => 250,
                'dataIndx' => 'action',
                'cls'        => 'thread-line',
                'align'    => 'center',
                'dataType' => 'html',
                'editable' => false,
            ],

        ];
    }

    public function ajaxProcessgetHookPositionFields() {

        die(Tools::jsonEncode($this->getHookPositionFields()));
    }
    
    public function getHookModulesFields() {

        return [
             [
                
                'dataIndx'   => 'id_module',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
			

            [
                'title'    => $this->l('Module'),
                'width'    => 350,
                'dataIndx' => 'module',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Name'),
                'width'    => 200,
                'dataIndx' => 'displayName',
                'dataType' => 'string',
                'align'    => 'left',
                'editable' => false,
                'hidden'   => false,

            ],
            [
                'dataIndx'   => 'modulePosition',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Position'),
                'width'    => 120,
                'dataIndx' => 'position',
                'cls'      => 'pointer dragHandle',
                'editable' => false,
                'dataType' => 'html',
                'align'    => 'center',
                'valign'   => 'center',
            ],

        ];

    }

    public function ajaxProcessGetHookModulesFields() {

        die(Tools::jsonEncode($this->getHookModulesFields()));

    }
   

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        if (!($obj = $this->loadObject(true))) {
            return '';
        }
        
        $target[] = [
            'id'   => 'generic',
            'name' => $this->l('Generic target'),
        ];
        $target[] = [
            'id'   => 'header',
            'name' => $this->l('Target Header of site'),
        ];
        $target[] = [
            'id'   => 'index',
            'name' => $this->l('Target Home Page of site'),
        ];
        $target[] = [
            'id'   => 'content',
            'name' => $this->l('Target Content Page of site'),
        ];
        $target[] = [
            'id'   => 'footer',
            'name' => $this->l('Target Footer of site'),
        ];
        $target[] = [
            'id'   => 'product',
            'name' => $this->l('Target Product Page of site'),
        ];

        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => $this->l('Front Office Hook'),
                'icon'  => 'fa fa-anchor',
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
                    'type' => 'hidden',
                    'name' => 'id_hook',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'title',
                    'lang'     => true,
                    'required' => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Front Office Target'),
                    'name'    => 'target',
                    'options' => [
                        'query'   => $target,
                        'id'      => 'id',
                        'name'    => 'name',
                    ],
                ],                
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'hint'         => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('This Hook is a Tag'),
                    'name'     => 'is_tag',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'is_tag_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'is_tag_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Tag Name'),
                    'name'     => 'tag_value',
                    'lang'     => true,
                ],
               
            ],

        ];

        

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];
        
         if ($this->object->id > 0) {
            $this->form_action = 'updateHookPosition';
            $this->editObject = $this->l('Edit and update the front Hook ') . $obj->title[$this->context->language->id];
        } else {
            $this->form_action = 'addHookPosition';
            $this->editObject = $this->l('Add new Front Hook');
        }

        $this->form_ajax = 1;
        return parent::renderForm();
    }
    
    public function ajaxProcessUpdateHookPosition() {
        
    }
    
    public function ajaxProcessAddHookPosition() {
        
    }

    

}
