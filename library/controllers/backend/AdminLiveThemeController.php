<?php

/**
 * Class AdminLiveThemeControllerCore
 *
 * @since 1.9.1.0
 */
class AdminLiveThemeControllerCore extends AdminController {

    public $php_self = 'adminlivetheme';

    
    // @codingStandardsIgnoreEnd

    /**
     * AdminThemesControllerCore constructor.
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'hook';
        $this->className = 'Hook';
        $this->publicName = $this->l('Front Theme Manager');
        $this->context = Context::getContext();

        parent::__construct();

        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/live_themes.css', 
            _EPH_JS_DIR_ . 'colorpicker/jquery.colorpicker.css', 
        ]);
        
        $this->ajaxOptions = $this->generateLiveThemeConfigurator();


    }
    
    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_JS_DIR_ . 'hook.js',
        ]);
    }
    
    public function generateLiveThemeConfigurator() {

        $tabs = [];
        
        $tabs[$this->l('Meta Manager')] = [
			'key'     => 'meta',
			'content' => $this->renderMeta(),
        ];
        $tabs[$this->l('Home Theme')] = [
			'key'     => 'home_theme',
			'content' => $this->renderHomeTheme(),
        ];       

        return $tabs;

    }
    
    public function generateParaGridScript($regenerate = false) {

        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 100,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
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
        $this->paramContextMenu = [
            '#grid_' . $this->controller_name => [
                'selector'  => '\'.pq-grid-row\'',
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
                            name: \'' . $this->l('Edit or Update the Hook:') . '\'+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '",rowData.id_hook);
                            }
                        },
                        "sep1": "---------",

                        "delete": {
                            name: \'' . $this->l('Delete the Hook:') . '\'+rowData.name,
                            icon: "delete",
                            visible: function(key, opt){
                                return !rowData.hasSubmenu;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un Hook", "Etes vous sure de vouloir supprimer le Hook"+rowData.name+"  ?", "Oui", "Annuler",rowData.id_hook, rowIndex);

                            }
                        }
                    },
                };
            }',
            ]];

        $this->paramTitle = '\'' . $this->l('Management of live Front Office Hooks') . '\'';
        
        

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getHookRequest() {

                
        $hooks = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('h.id_hook, h.name, h.target, h.position, hl.title, hl.description')
                ->from('hook', 'h')
                ->leftJoin('hook_lang', 'hl', 'hl.`id_hook` = h.`id_hook` AND hl.`id_lang`  = ' . (int) $this->context->language->id)
                ->orderBy('h.`position` ASC')
        );
        
        foreach ($hooks as &$hook) {
            
            $hook['hookPosition'] = $hook['position'];    
            $hook['position'] = '<div class="dragGroup"><div class="hookPosition_' . $hook['id_hook'] . ' positions" data-id="' . $hook['id_hook']  . '" data-position="' . $hook['position'] . '">' . $hook['position'] . '</div></div>';            

        }

        

        return $hooks;

    }

    public function ajaxProcessgetHookRequest() {
        
        $result = Tools::jsonEncode($this->getHookRequest());
        die($result);

    }

    public function getHookFields() {

        return [
            
            [
               
                'dataIndx'   => 'id_hook',
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
                'dataIndx'   => 'hookPosition',
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

    public function ajaxProcessgetHookFields() {

        die(Tools::jsonEncode($this->getHookFields()));
    }
    
    
    
    public function renderMeta() {

        
        $theme = new Theme((int) $this->context->company->id_theme);

        $themeMetasQuery = (new DbQuery())
            ->select('ml.`title`, m.`page`, tm.`left_column` as `left`, tm.`right_column` as `right`, m.`id_meta`, tm.`id_theme_meta`')
            ->from('theme_meta', 'tm')
            ->innerJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
            ->leftJoin('meta_lang', 'ml', 'ml.`id_meta` = m.`id_meta` AND ml.`id_lang` = ' . (int) $this->context->language->id . ' AND ml.`id_shop` = ' . (int) $this->context->company->id)
            ->where('tm.`id_theme` = ' . (int) $theme->id);

        $themeMetas = Db::getInstance()->executeS($themeMetasQuery);

        // if no theme_meta are found, we must create them

        if (empty($themeMetas)) {
            $metas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_meta`')
                    ->from('meta')
            );
            $metasDefault = [];

            foreach ($metas as $meta) {
                $tmpMeta['id_meta'] = (int) $meta['id_meta'];
                $tmpMeta['left'] = 1;
                $tmpMeta['right'] = 1;
                $metasDefault[] = $tmpMeta;
            }

            $theme->updateMetas($metasDefault);
            $themeMetas = Db::getInstance()->executeS($themeMetasQuery);
        }        

        foreach ($themeMetas as $key => &$meta) {

            if (!isset($meta['title']) || !$meta['title'] || $meta['title'] == '') {
                $meta['title'] = $meta['page'];
            }

        }

        $formatedMetas = $themeMetas;

        $fieldsList = [
            'title' => [
                'title' => $this->l('Meta'),
                'align' => 'center',
                'width' => 'auto',
            ],
            'left'  => [
                'title'  => $this->l('Left column'),
                'active' => 'left',
                'type'   => 'bool',
                'ajax'   => true,
            ],
            'right' => [
                'title'  => $this->l('Right column'),
                'active' => 'right',
                'type'   => 'bool',
                'ajax'   => true,
            ],
        ];
        $helperList = new HelperList();
        $helperList->tpl_vars = ['icon' => 'icon-columns'];
        $helperList->title = $this->l('Appearance of columns');
        $helperList->no_link = true;
        $helperList->shopLinkType = '';
        $helperList->identifier = 'id_theme_meta';
        $helperList->table = 'meta';
        $helperList->tpl_vars['show_filters'] = false;
        $helperList->currentIndex = $this->context->link->getAdminLink('AdminLiveTheme', false);

        return $helperList->generateList($formatedMetas, $fieldsList);

    }
    
    public function renderHomeTheme() {
        
       
        $data = $this->createTemplate('controllers/live_theme/home.tpl');      
        
        
        $data->assign([
            'headerHooks'       => Hook::getHeaderHooks('index'),
            'leftHooks'       => Hook::getLeftColumnHooks(),
            'homeHooks'       => Hook::getHomeHooks(),
            'rightHooks'       => Hook::getRightColumnHooks(),
            'footerHooks'       => Hook::getFooterHooks(),
            'controller'  => 'AdminLiveTheme',
            'link'        => $this->context->link,
        ]);

        return $data->fetch();
    }
    
    public function ajaxProcessLeftMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'left_column' => ['type' => 'sql', 'value' => 'NOT `left_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }

    }
    
    public function ajaxProcessRightMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'right_column' => ['type' => 'sql', 'value' => 'NOT `right_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );


        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }

    }
    
    public function ajaxProcessUnHookModule() {

        $idModule = Tools::getValue('idModule');
        $id_hook = Tools::getValue('id_hook');

        $module = Module::getInstanceById((int) $idModule);
        $hook = new Hook((int) $id_hook);

        if ($module->unregisterHook((int) $id_hook) || !$module->unregisterExceptions((int) $id_hook)) {

            $return = [
                'success' => true,
                'message' => $this->l('The Hook has been ungraft successfully.'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Something wrong happen.'),
            ];
        }

        die(Tools::jsonEncode($return));
    }
    
    public function ajaxProcessHookPlugin() {
        
        $file = fopen("testProcessHookPlugin.txt","w");
        $idModule = Tools::getValue('idPlugin');
        $id_hook = Tools::getValue('idHook');
        fwrite($file,$idModule.PHP_EOL);
        fwrite($file,$id_hook.PHP_EOL);
        $tmpInstance = Module::getInstanceById($idModule);
        fwrite($file,$tmpInstance->name.PHP_EOL);
        
        $hook = new Hook($id_hook);
        fwrite($file,$hook->name.PHP_EOL);
        $tmpInstance->registerHook($hook->name);
        $position =  Db::getInstance()->getValue(
            (new DbQuery())
	        ->select('position')
            ->from('hook_module')
            ->where('`id_hook` ='.$hook->id)
            ->where('`id_module` ='.$tmpInstance->id)
        );
        $return = [
            'success' => true,
            'position' => $position,
            'displayName' => $tmpInstance->displayName,
            'message' => $this->l('The Hook has been graft successfully.'),
        ];
        
        die(Tools::jsonEncode($return));
    }
    
    public function ajaxProcessUpdatePositions() {

        if ($this->tabAccess['edit'] === '1') {
            $idHook = (int) (Tools::getValue('idHook'));
            $orderHooks = (int) (Tools::getValue('orderHooks'));
            foreach($orderHooks as $idModule) {
                
            }
            $module = Module::getInstanceById($idModule);

            if (Validate::isLoadedObject($module)) {

                if ($module->updatePosition($idHook, $way, $position)) {
                    die(true);
                } else {
                    die('{"hasError" : true, "errors" : "Cannot update module position."}');
                }

            } else {
                die('{"hasError" : true, "errors" : "This module cannot be loaded."}');
            }

        }

    }
    
    public function renderForm() {

        if (!($obj = $this->loadObject(true))) {
            return '';
        }
        $file = fopen("testHookRenferForm.txt","w");
        fwrite($file, print_r($obj, true));
        $metas = Meta::getFrontMetas();
        
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
                    'name' => 'position',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
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
                    'lang'         => true,
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
                    'form_group_class' => 'hook_tag',
                    'label'    => $this->l('Tag Name'),
                    'name'     => 'tag_value',
                    'lang'     => true,
                ],
                [
                    'type'   => 'meta',
                    'label'  => $this->l('Pages'),
                    'name'   => 'metas',
                    'values' => $metas,
                    'hint'   => $this->l('The Pages in which this hook will be used.'),
                ],
               
            ],

        ];

        

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];
        
         if ($this->object->id > 0) {
            $this->form_action = 'updateHook';
            $this->editObject = $this->l('Edit and update the front Hook ') . $obj->title[$this->context->language->id];
        } else {
            $this->form_action = 'addHook';
            $this->editObject = $this->l('Add new Front Hook');
        }

        $this->form_ajax = 1;
        return parent::renderForm();
    }
    
    public function ajaxProcessUpdateHook() {
        
        $idHook = Tools::getValue('id_hook');
        $hook = new Hook($idHook);
        
        foreach ($_POST as $key => $value) {

            if (property_exists($hook, $key) && $key != 'id_hook') {
                $hook->{$key} = $value;
            }

        }
        $classVars = get_class_vars(get_class($hook));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($hook->{$field}) || !is_array($hook->{$field})) {
                            $hook->{$field} = [];
                        }

                        $hook->{$field}[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                    }
                }
            }
        }
        $metas = Tools::getValue('metas');
        $hook->metas = Tools::jsonEncode($metas);
        
        $result = $hook->update();
        if($result) {
            $return = [
                'success' => true,
                'message' => $this->l('Hook parameters has been update with success'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Something go wrong'),
            ];
        }
        

        die(Tools::jsonEncode($return));
    }
    
    public function ajaxProcessAddHook() {
        
    }




}
