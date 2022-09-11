<?php

class AdminFrontParallaxControllerCore extends AdminController {

    public $php_self = 'adminfrontparallax';

    public $hooks = [];

    public function __construct() {

        $this->table = 'xprtparrallaxblocktbl';
        $this->className = 'FrontParallax';
        $this->publicName = $this->l('Front Parallax Blocks Management');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_PARALLAX_FIELDS', Tools::jsonEncode($this->getFrontParallaxFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PARALLAX_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_PARALLAX_FIELDS', Tools::jsonEncode($this->getFrontParallaxFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PARALLAX_FIELDS'), true);
        }

        EmployeeConfiguration::updateValue('EXPERT_PARALLAX_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_PARALLAX_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_PARALLAX_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_PARALLAX_SCRIPT');
        }

        $this->hooks = [
            'displayTopColumn',
            'displayFooterTop',
            'displayFooterBottom',
            'displayHome',
            'displayhomefullwidthmiddle',
            'displayhomefullwidthmiddletop',
            'displayMaintenance',
        ];

    }

    public function ajaxProcessOpenTargetController() {

        $targetController = $this->targetController;

        $this->paragridScript = $this->generateParaGridScript();

        $this->setAjaxMedia();

        $data = $this->createTemplate($this->table . '.tpl');

        foreach ($this->extra_vars as $key => $value) {
            $data->assign($key, $value);
        }

        $isInstalled = false;

        if (Module::isInstalled('xprtparrallaxblock') && (bool) Module::isEnabled('xprtparrallaxblock')) {
            $isInstalled = true;
        }

        $data->assign([
            'isInstalled'     => $isInstalled,
            'paragridScript'  => $this->paragridScript,
            'controller'      => $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'link'            => $this->context->link,
            'id_lang_default' => Configuration::get('EPH_LANG_DEFAULT'),
            'extraJs'         => $this->push_js_files,
            'extracss'        => $this->extracss,
        ]);

        $li = '<li id="uper' . $targetController . '" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '" data-controller="AdminDashboard"><a href="#content' . $targetController . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $targetController . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $targetController . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';
        $result = [
            'li'         => $li,
            'html'       => $html,
            'page_title' => $this->page_title,
        ];

        die(Tools::jsonEncode($result));

    }

    public function setAjaxMedia() {

        return $this->pushJS([_EPH_JS_DIR_ . 'tinymce.min.js', _EPH_JS_DIR_ . 'tinymce.inc.js', _EPH_JS_DIR_ . 'themeuploadify.min.js',
        ]);
    }

    public function initContent() {

        $this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Book account List');

        $this->context->smarty->assign([
            'controller'     => Tools::getValue('controller'),
            'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
            'gridId'         => 'grid_AdminFrontParallax',
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

    public function generateParaGridScript() {

        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'Ajouter un block\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                        addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                    }' . PHP_EOL,
                ],

            ],
        ];

        $this->paramTitle = '\'' . $this->l('Gestion des blocs Parallax') . '\'';

        $this->dragModel = [
            'on'          => true,
            'diHelper'    => "['position']",
            'clsHandle'   => '\'dragHandle\'',
            'dragNodes'   => 'function(rd, evt){
                var checkNodes = this.Tree().getCheckedNodes();
                return (checkNodes.length && checkNodes.indexOf(rd)>-1 )? checkNodes: [ rd ];
            }',
            'isDraggable' => 'function(ui){
                return !(ui.rowData.pq_gsummary || ui.rowData.pq_level == 0);
            }',
        ];
        $this->dropModel = [
            'on'          => true,
            'isDroppable' => 'function(evt, uiDrop){

                var Drag = uiDrop.helper.data(\'Drag\'),
                    uiDrag = Drag.getUI(),
                    rdDrag = uiDrag.rowData,
                    rdDrop = uiDrop.rowData,
                    Tree = this.Tree(),
                    denyDrop = (
                        rdDrop == rdDrag ||
                        rdDrop.pq_gsummary ||
                        Tree.isAncestor( rdDrop,  rdDrag)
                    );

                return !denyDrop;
            }',
        ];
        $this->moveNode = 'function(event, ui) {
            var startIndex = ui.args[0][0].blocklinkPosition;
            var idBlockParallax = ui.args[0][0].id_id_xprtparrallaxblocktbl;
            var stopIndex = parseInt(ui.args[1]);
            processBlockParallaxPosition(idBlockParallax, startIndex, stopIndex)

         }';

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
                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Voir ou éditer ce bloc ') . ' \'+rowData.title,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_xprtparrallaxblocktbl)
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer la bloc') . ' \ : \'+rowData.title,
                            icon: "trash",

                            callback: function(itemKey, opt, e) {
                                 deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un Block Parallax", "Etes vous sure de vouloir supprimer "+rowData.title+ " ?", "Oui", "Annuler",rowData.id_xprtparrallaxblocktbl);
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

    public function getFrontParallaxRequest() {

        $parallaxblocks = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, sl.`title`')
                ->from('xprtparrallaxblocktbl', 'a')
                ->leftJoin('xprtparrallaxblocktbl_lang', 'sl', 'sl.`id_xprtparrallaxblocktbl` = a.`id_xprtparrallaxblocktbl` AND sl.`id_lang`  = ' . (int) $this->context->language->id)
                ->orderBy('a.`id_xprtparrallaxblocktbl` ASC')
        );

        if (!empty($parallaxblocks)) {

            foreach ($parallaxblocks as &$parallaxblock) {

                if ($parallaxblock['active'] == 1) {
                    $parallaxblock['active'] = '<div class="fa fa-check" style="color: green"></div>';
                } else {
                    $parallaxblock['active'] = '<div class="fa fa-times" style="color:red"></div>';
                }

                if ($parallaxblock['is_video'] == 1) {
                    $parallaxblock['is_video'] = '<div class="fa fa-check" style="color: green"></div>';
                } else {
                    $parallaxblock['is_video'] = '<div class="fa fa-times" style="color:red"></div>';
                }

                $parallaxblock['blocklinkPosition'] = $parallaxblock['position'];
                $parallaxblock['position'] = '<div class="dragGroup"><div class="blocklinkPosition_' . $parallaxblock['id_xprtparrallaxblocktbl'] . ' positions" data-id="' . $parallaxblock['id_xprtparrallaxblocktbl'] . '">' . $parallaxblock['position'] . '</div></div>';

            }

        }

        return $parallaxblocks;

    }

    public function ajaxProcessgetFrontParallaxRequest() {

        die(Tools::jsonEncode($this->getFrontParallaxRequest()));

    }

    public function getFrontParallaxFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'maxWidth' => 50,
                'dataIndx' => 'id_xprtparrallaxblocktbl',
                'dataType' => 'integer',
                'editable' => false,
            ],
            [
                'title'    => $this->l('Titre '),
                'minWidth' => 100,
                'dataIndx' => 'title',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Hook'),
                'minWidth' => 100,
                'dataIndx' => 'hook',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'string',

            ],

            [
                'title'    => $this->l('Video'),
                'minWidth' => 100,
                'dataIndx' => 'is_video',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],
            [
                'title'    => $this->l('Active'),
                'minWidth' => 100,
                'dataIndx' => 'active',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],

            [

                'dataIndx'   => 'blocklinkPosition',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],

            [
                'title'    => $this->l('Position'),
                'minWidth' => 100,
                'maxWidth' => 100,
                'dataIndx' => 'position',
                'cls'      => 'pointer dragHandle',
                'dataType' => 'html',
                'align'    => 'center',
            ],

        ];

    }

    public function ajaxProcessgetFrontParallaxFields() {

        die(EmployeeConfiguration::get('EXPERT_PARALLAX_FIELDS'));
    }

    public function hook_val() {

        $allhooks = [];
        $hook_val = $this->hooks;

        if (isset($hook_val)) {
            $i = 0;

            foreach ($hook_val as $hok) {
                $allhooks[$i]['id'] = $hok;
                $allhooks[$i]['name'] = ucwords($hok);
                $i++;
            }

        }

        return $allhooks;
    }

    public function renderForm() {

        $obj = $this->loadObject(true);
        $image = _EPH_PARALLAX_IMG_DIR_ . $obj->image;
        $image_url = _PARALLAX_IMG_DIR . $obj->image;
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;
        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Ephenyx Parrallax Block'),
            ],
            'input'   => [

                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Is this a video'),
                    'name'     => 'is_video',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'is_video',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'is_video',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'             => 'image_parallax',
                    'label'            => $this->l('Parrallax Image'),
                    'name'             => 'image',
                    'display_image'    => true,
                    'image'            => $image_url ? $image_url : false,
                    'size'             => $image_size,
                    'form_group_class' => 'hidden',
                    'form_group_id'    => 'image_parallax',
                ],

                [
                    'type'             => 'text',
                    'label'            => $this->l('Video Link'),
                    'name'             => 'video_link',
                    'form_group_class' => 'hidden',
                    'form_group_id'    => 'link_video',

                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Title'),
                    'name'  => 'title',
                    'desc'  => $this->l('Enter Your Parrallax Title'),
                    'lang'  => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Sous Titre'),
                    'name'  => 'subtitle',
                    'desc'  => $this->l('Enter Your Parrallax Subtitle'),
                    'lang'  => true,
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Content'),
                    'name'         => 'content',
                    'desc'         => $this->l('Enter Your Parrallax Content'),
                    'lang'         => true,
                    'cols'         => 40,
                    'rows'         => 10,
                    'class'        => 'rte',
                    'autoload_rte' => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Content position'),
                    'name'    => 'contentposition',
                    'options' => [
                        'query' => [
                            [
                                'id'   => 'top_left',
                                'name' => 'Top left',
                            ],
                            [
                                'id'   => 'top_middle',
                                'name' => 'Top middle',
                            ],
                            [
                                'id'   => 'top_right',
                                'name' => 'Top right',
                            ],
                            [
                                'id'   => 'center_left',
                                'name' => 'Center left',
                            ],
                            [
                                'id'   => 'center_middle',
                                'name' => 'Center middle',
                            ],
                            [
                                'id'   => 'center_right',
                                'name' => 'Center right',
                            ],
                            [
                                'id'   => 'bottom_left',
                                'name' => 'Bottom left',
                            ],
                            [
                                'id'   => 'bottom_middle',
                                'name' => 'Bottom middle',
                            ],
                            [
                                'id'   => 'bottom_right',
                                'name' => 'Bottom right',
                            ],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Button Label'),
                    'name'  => 'btntext',
                    'desc'  => $this->l('Enter Your Parrallax Button Label Text'),
                    'lang'  => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Button Link'),
                    'name'  => 'btnurl',
                    'desc'  => $this->l('Enter Your Parrallax Button URl Link'),
                    'lang'  => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Button Link Target'),
                    'name'    => 'btntarget',
                    'options' => [
                        'query' => [
                            [
                                'id'   => '_blank',
                                'name' => 'New Window or Tab',
                            ],
                            [
                                'id'   => '_self',
                                'name' => 'Same Frame as it Was Clicked',
                            ],
                            [
                                'id'   => '_parent',
                                'name' => 'Parent Frame',
                            ],
                            [
                                'id'   => '_top',
                                'name' => 'Full Body of The Window',
                            ],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Height'),
                    'name'  => 'height',
                    'desc'  => $this->l('Renseigner la hauteur de la parallax'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Padding'),
                    'name'  => 'padding',
                    'desc'  => $this->l('Enter Your Parrallax Padding(Format: 0px 0px 0px 0px)'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Margin'),
                    'name'  => 'margin',
                    'desc'  => $this->l('Enter Your Parrallax Margin(Format: 0px 0px 0px 0px)'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Where You Want To Display'),
                    'name'    => 'hook',
                    'options' => [
                        'query' => $this->hook_val(),
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Force Full Width'),
                    'name'     => 'fullwidth',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'fullwidth',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'fullwidth',
                            'value' => 0,
                            'label' => $this->l('Désactivé'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Disabled'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'active',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $this->fields_form['submit'] = [
            'title' => $this->l('Save   '),
            'class' => 'button',
        ];

        $this->fields_value['ajax'] = 1;

        if ($obj->id > 0) {
            $this->fields_value['action'] = 'updateParallaxBlock';
        } else {
            $this->fields_value['action'] = 'addParallaxBlock';
        }

        $this->tpl_vars = [
            'is_video' => $obj->is_video,
        ];
        return parent::renderForm();
    }

    public function ajaxProcessUpdateParallaxBlock() {

        $idParallax = Tools::getValue('id_xprtparrallaxblocktbl');
        $file = fopen("testProcessUpdateCms.txt", "w");
        $parallax = new FrontParallax($idParallax);

        foreach ($_POST as $key => $value) {

            if (property_exists($parallax, $key) && $key != 'id_xprtparrallaxblocktbl') {
                $parallax->{$key}
                = $value;
            }

        }

        $classVars = get_class_vars(get_class($parallax));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($parallax->{$field}) || !is_array($parallax->{$field})) {
                            $parallax->{$field}
                            = [];
                        }

                        $parallax->{$field}
                        [(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                    }

                }

            }

        }

        fwrite($file, print_r($parallax, true));

        if (!$parallax->is_video) {
            fwrite($file, 'no video');
            $image_name = $parallax->image;
            $imageUploader = new HelperImageUploader('image');
            $imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
            $files = $imageUploader->process();
            fwrite($file, print_r($files, true));

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {

                    if ($image['error'] && $image['size'] > 0) {
                        $return = [
                            'success' => false,
                            'message' => $image['error'],
                        ];
                        die(Tools::jsonEncode($return));
                    }

                    if ($image['size'] == 0) {
                        continue;
                    }

                    $image_name = $image['name'];
                    copy($image['save_path'], _EPH_PARALLAX_IMG_DIR_ . $image_name);
                }

            }

            fwrite($file, $image_name);
            $parallax->image = $image_name;
        } else {
            $parallax->image = '';
        }

        try {
            $result = $parallax->update();
        } catch (Exception $e) {

            fwrite($file, $e->getMessage());
        }

        if ($result) {

            $return = [
                'success' => true,
                'message' => $this->l('Le block Parallax a été mis à jour avec succès'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Bug merde add'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessAddParallaxBlock() {

        $parallax = new FrontParallax();

        foreach ($_POST as $key => $value) {

            if (property_exists($parallax, $key) && $key != 'id_xprtparrallaxblocktbl') {
                $parallax->{$key}
                = $value;
            }

        }

        $classVars = get_class_vars(get_class($parallax));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($parallax->{$field}) || !is_array($parallax->{$field})) {
                            $parallax->{$field}
                            = [];
                        }

                        $parallax->{$field}
                        [(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                    }

                }

            }

        }

        $image_name = '';
        $imageUploader = new HelperImageUploader('image');
        $imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
        $files = $imageUploader->process();

        if (is_array($files) && count($files)) {

            foreach ($files as $image) {

                if ($image['error']) {
                    $return = [
                        'success' => false,
                        'message' => $image['error'],
                    ];
                    die(Tools::jsonEncode($return));
                }

                $image_name = $image['name'];
                copy($image['save_path'], _EPH_PARALLAX_IMG_DIR_ . $image_name);
            }

        }

        $parallax->image = $image_name;

        try {
            $result = $parallax->add();
        } catch (Exception $e) {
            $file = fopen("testProcessUpdateCms.txt", "w");
            fwrite($file, $e->getMessage());
        }

        if ($result) {

            $return = [
                'success' => true,
                'message' => $this->l('Le block Parallax a été ajouté avec succès'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Bug merde add'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessBlockParallaxPosition() {

        $idBlockParallax = Tools::getValue('idBlockParallax');
        $startIndex = Tools::getValue('startIndex');
        $stopIndex = Tools::getValue('stopIndex');

        $object = new FrontParallax($idBlockParallax);

        if (Validate::isLoadedObject($object)) {
            $initPosition = $object->position;

            if ($initPosition > $stopIndex) {

                $objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('id_xprtparrallaxblocktbl,  `position` ')
                        ->from('xprtparrallaxblocktbl')
                        ->where('`position` >= ' . (int) $stopIndex . ' AND `position` < ' . (int) $initPosition)
                        ->orderBy('`position` ASC')
                );

                if (!empty($objects)) {

                    $k = $stopIndex + 1;

                    foreach ($objects as $moveObject) {
                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
                            SET `position`= ' . (int) $k . '
                            WHERE `id_xprtparrallaxblocktbl` =' . (int) $moveObject['id_xprtparrallaxblocktbl']);
                        $k++;

                    }

                }

            } else {

                $objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('id_xprtparrallaxblocktbl,  `position` ')
                        ->from('xprtparrallaxblocktbl')
                        ->where('`position` > ' . (int) $initPosition . ' AND `position` <= ' . $stopIndex)
                        ->orderBy('`position` ASC')
                );

                if (!empty($objects)) {

                    $k = $initPosition;

                    foreach ($objects as $moveObject) {
                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
                            SET `position`= ' . (int) $k . '
                            WHERE `id_xprtparrallaxblocktbl` =' . (int) $moveObject['id_xprtparrallaxblocktbl']);
                        $k++;

                    }

                }

            }

            $result = Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
                SET `position`= ' . (int) $stopIndex . '
                WHERE `id_xprtparrallaxblocktbl` =' . (int) $object->id);

            $result = [
                'success' => true,
                'message' => $this->l('La position des blocks ont été mis à jour avec succès.'),
            ];

        } else {
            $result = [
                'success' => false,
                'message' => 'Un problème est apparu lors du chargement du bloc',
            ];
        }

        die(Tools::jsonEncode($result));
    }

}
