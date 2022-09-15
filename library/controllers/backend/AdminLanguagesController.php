<?php

/**
 * Class AdminLanguagesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminLanguagesControllerCore extends AdminController {

    /**
     * AdminLanguagesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'lang';
        $this->className = 'Language';
        $this->publicName = $this->l('Languages');
        $this->lang = false;
        $this->deleted = false;
        

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_LANGUAGES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_LANGUAGES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_LANGUAGES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_LANGUAGES_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_LANGUAGES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_LANGUAGES_FIELDS', Tools::jsonEncode($this->getLanguageFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_LANGUAGES_FIELDS'), true);
        }

    }

    public function generateParaGridScript($regenerate = false) {

        if (!empty($this->paragridScript) && !$regenerate) {
            return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
        }

        $context = Context::getContext();
        $controllerLink = $context->link->getAdminLink($this->controller_name);

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->showNumberCell = 0;
        $paragrid->colModel = EmployeeConfiguration::get('EXPERT_LANGUAGES_FIELDS');
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
        $paragrid->rowDblClick = 'function( event, ui ) {
            var identifierlink = ui.rowData.' . $this->identifier . ';
            var datalink = \'' . $controllerLink . '&' . $this->identifier . '=\'+identifierlink+\'&id_object=\'+identifierlink+\'&update' . $this->table . '&action=initUpdateController&ajax=true\';
            openAjaxGridLink(datalink, identifierlink, \'' . $this->controller_name . '\', \'View' . $this->controller_name . '\');
        } ';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
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
                        "add": {
                            name: \'' . $this->l('Add new Language') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.addLink;
                                openAjaxGridLink(datalink);
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Edit the Customer: ') . '\'' . '+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.openLink;
                                openAjaxGridLink(datalink, rowData.id_customer, \'' . $this->controller_name . '\', \'View' . $this->controller_name . '\');
                            }
                        },

                        "sep1": "---------",
                        "select": {
                            name: \'' . $this->l('Select all item') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                var dataLenght = ' . 'grid' . $this->className . '.option(\'dataModel.data\').length;
                                if(dataLenght == selected) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                selgrid' . $this->className . '.selectAll();
                            }
                        },
                        "unselect": {
                            name: \'' . $this->l('Unselect all item') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 2) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                ' . 'grid' . $this->className . '.setSelection( null );
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Delete the customer:') . '\'' . '+rowData.lastname,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                var idCustomer = rowData.id_customer;
                                deleteCustomert(idCustomer, rowIndex);
                            }
                        },


                    },
                };
            }',
            ]];

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

    public function getLanguageRequest() {

        $languages = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*, case when `active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`')
                ->from('lang')
                ->orderBy('`id_lang` ASC')
        );

        foreach ($languages as &$lang) {

            if (file_exists(_EPH_ROOT_DIR_ . '/img/l/' . $lang['id_lang'] . '.jpg')) {
                $flag = '/img/l/' . $lang['id_lang'] . '.jpg';
            } else {
                $flag = '/img/l/none.jpg';
            }

            $lang['flag'] = '<img src="' . $flag . '" class="imgflag img-thumbnail">';

            $lang['openLink'] = $this->context->link->getAdminLink($this->controller_name) . '&id_lang=' . $lang['id_lang'] . '&id_object=' . $lang['id_lang'] . '&updatelang&action=initUpdateController&ajax=true';
            $lang['addLink'] = $this->context->link->getAdminLink($this->controller_name) . '&action=addObject&ajax=true&addlang';

        }

        return $languages;
    }

    public function ajaxProcessgetLanguageRequest() {

        die(Tools::jsonEncode($this->getLanguageRequest()));

    }

    public function getLanguageFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_lang',
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
                'title'    => $this->l('Flag'),
                'width'    => 50,
                'dataIndx' => 'flag',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',
                'editable' => false,

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
                'title'      => $this->l('ISO code'),
                'width'      => 200,
                'dataIndx'   => 'iso_code',
                'cls'        => 'name-handle',
                'align'      => 'center',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Language code'),
                'width'    => 150,
                'dataIndx' => 'language_code',
                'align'    => 'center',
                'editable' => false,
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Date format'),
                'minWidth' => 150,
                'dataIndx' => 'date_format_lite',
                'dataType' => 'string',
                'editable' => false,

            ],
            [
                'title'    => $this->l('Date format (full)'),
                'minWidth' => 150,
                'dataIndx' => 'date_format_full',
                'dataType' => 'string',
                'editable' => false,

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

    public function ajaxProcessgetLanguageFields() {

        die(EmployeeConfiguration::get('EXPERT_LANGUAGES_FIELDS'));
    }

    /**
     * Initialize page header toolbar
     *
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        $this->page_header_toolbar_btn['new_language'] = [
            'href'       => static::$currentIndex . '&action=addObject&ajax=true&addlang&token=' . $this->token,
            'desc'       => $this->l('Add new language', null, null, false),
            'identifier' => 'new',
            'controller' => $this->controller_name,
            'icon'       => 'process-icon-new',
        ];

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

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Languages'),
                'icon'  => 'icon-globe',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'ps_version',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 32,
                    'required'  => true,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'required'  => true,
                    'maxlength' => 2,
                    'hint'      => $this->l('Two-letter ISO code (e.g. FR, EN, DE).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Language code'),
                    'name'      => 'language_code',
                    'required'  => true,
                    'maxlength' => 5,
                    'hint'      => $this->l('IETF language tag (e.g. en-US, pt-BR).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Date format'),
                    'name'     => 'date_format_lite',
                    'required' => true,
                    'hint'     => sprintf($this->l('Short date format (e.g., %s).'), 'Y-m-d'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Date format (full)'),
                    'name'     => 'date_format_full',
                    'required' => true,
                    'hint'     => sprintf($this->l('Full date format (e.g., %s).'), 'Y-m-d H:i:s'),
                ],
                [
                    'type'     => 'file',
                    'label'    => $this->l('Flag'),
                    'name'     => 'flag',
                    'required' => false,
                    'hint'     => $this->l('Upload the country flag from your computer.'),
                ],
                [
                    'type'     => 'file',
                    'label'    => $this->l('"No-picture" image'),
                    'name'     => 'no_picture',
                    'hint'     => $this->l('Image is displayed when "no picture is found".'),
                    'required' => false,
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Is RTL language'),
                    'name'     => 'is_rtl',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'is_rtl_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'     => $this->l('Enable if this language is read from right to left.') . ' ' . $this->l('(Experimental: your theme must be compliant with RTL languages).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
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
                    'hint'     => $this->l('Activate this language.'),
                ],
            ],
        ];

        

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        /** @var Language $obj */

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        if ($obj->id && !$obj->checkFiles()) {
            $this->fields_form['new'] = [
                'legend'     => [
                    'title' => $this->l('Warning'),
                    'image' => '../img/admin/warning.gif',
                ],
                'list_files' => [
                    [
                        'label' => $this->l('Translation files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'tr', true),
                    ],
                    [
                        'label' => $this->l('Theme files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'theme', true),
                    ],
                    [
                        'label' => $this->l('Mail files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'mail', true),
                    ],
                ],
            ];
        }

        $this->fields_value = ['ps_version' => _EPH_VERSION_];

        return parent::renderForm();
    }

    /**
     * Process delete
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processDelete() {

        $object = $this->loadObject();

        if (!$this->checkDeletion($object)) {
            return false;
        }

        if (!$this->deleteNoPictureImages((int) $object->id)) {
            $this->errors[] = Tools::displayError('An error occurred while deleting the object.') . ' <b>' . $this->table . '</b> ';
        }

        return parent::processDelete();
    }

    /**
     * Check deletion
     *
     * @param $object
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function checkDeletion($object) {

        if (_EPH_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if (Validate::isLoadedObject($object)) {

            if ($object->id == Configuration::get('EPH_LANG_DEFAULT')) {
                $this->errors[] = $this->l('You cannot delete the default language.');
            } else
            if ($object->id == $this->context->language->id) {
                $this->errors[] = $this->l('You cannot delete the language currently in use. Please select a different language.');
            } else {
                return true;
            }

        } else {
            $this->errors[] = Tools::displayError('(cannot load object)');
        }

        return false;
    }

    /**
     * deleteNoPictureImages will delete all default image created for the language id_language
     *
     * @param string $idLanguage
     *
     * @return bool true if no error
     *
     * @since 1.9.1.0
     */
    protected function deleteNoPictureImages($idLanguage) {

        $language = Language::getIsoById($idLanguage);
        $imageTypes = ImageType::getImagesTypes('products');
        $dirs = [_EPH_PROD_IMG_DIR_, _EPH_CAT_IMG_DIR_, _EPH_MANU_IMG_DIR_, _EPH_SUPP_IMG_DIR_, _EPH_MANU_IMG_DIR_];

        foreach ($dirs as $dir) {

            foreach ($imageTypes as $k => $imageType) {

                if (file_exists($dir . $language . '-default-' . stripslashes($imageType['name']) . '.jpg')) {

                    if (!unlink($dir . $language . '-default-' . stripslashes($imageType['name']) . '.jpg')) {
                        $this->errors[] = Tools::displayError('An error occurred during image deletion process.');
                    }

                }

            }

            if (file_exists($dir . $language . '.jpg')) {

                if (!unlink($dir . $language . '.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred during image deletion process.');
                }

            }

        }

        return !count($this->errors) ? true : false;
    }

    /**
     * Process status
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processStatus() {

        $object = $this->loadObject();

        if ($this->checkDisableStatus($object)) {
            $this->checkEmployeeIdLang($object->id);

            return parent::processStatus();
        }

        return false;
    }

    /**
     * Check disable status
     *
     * @param $object
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function checkDisableStatus($object) {

        if (_EPH_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if (!Validate::isLoadedObject($object)) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
        } else {

            if ($object->id == (int) Configuration::get('EPH_LANG_DEFAULT')) {
                $this->errors[] = Tools::displayError('You cannot change the status of the default language.');
            } else {
                return true;
            }

        }

        return false;
    }

    /**
     * Check employee language id
     *
     * @param $currentIdLang
     *
     * @since 1.9.1.0
     */
    protected function checkEmployeeIdLang($currentIdLang) {

        //update employee lang if current id lang is disabled
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'employee` set `id_lang`=' . (int) Configuration::get('EPH_LANG_DEFAULT') . ' WHERE `id_lang`=' . (int) $currentIdLang);
    }

    /**
     * Process add
     *
     * @return false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processAdd() {

        if (_EPH_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if (isset($_POST['iso_code']) && !empty($_POST['iso_code']) && Validate::isLanguageIsoCode(Tools::getValue('iso_code')) && Language::getIdByIso($_POST['iso_code'])) {
            $this->errors[] = Tools::displayError('This ISO code is already linked to another language.');
        }

        if ((!empty($_FILES['no_picture']['tmp_name']) || !empty($_FILES['flag']['tmp_name'])) && Validate::isLanguageIsoCode(Tools::getValue('iso_code'))) {

            if ($_FILES['no_picture']['error'] == UPLOAD_ERR_OK) {
                $this->copyNoPictureImage(strtolower(Tools::getValue('iso_code')));
            }

            unset($_FILES['no_picture']);
        }

        $success = parent::processAdd();

        if (empty($_FILES['flag']['tmp_name'])) {
            Language::_copyNoneFlag($this->object->id, $_POST['iso_code']);
        }

        return $success;
    }

    /**
     * Copy a no-product image
     *
     * @param string $language Language iso_code for no_picture image filename
     *
     * @return void
     */
    public function copyNoPictureImage($language) {

        if (isset($_FILES['no_picture']) && $_FILES['no_picture']['error'] === 0) {

            if ($error = ImageManager::validateUpload($_FILES['no_picture'], Tools::getMaxUploadSize())) {
                $this->errors[] = $error;
            } else {

                if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['no_picture']['tmp_name'], $tmpName)) {
                    return;
                }

                if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'p/' . $language . '.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No Picture" image to your product folder.');
                }

                if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'c/' . $language . '.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No picture" image to your category folder.');
                }

                if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'm/' . $language . '.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No picture" image to your manufacturer folder.');
                } else {
                    $imageTypes = ImageType::getImagesTypes('products');

                    foreach ($imageTypes as $k => $imageType) {

                        if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'p/' . $language . '-default-' . stripslashes($imageType['name']) . '.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your product directory.');
                        }

                        if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'c/' . $language . '-default-' . stripslashes($imageType['name']) . '.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your category directory.');
                        }

                        if (!ImageManager::resize($tmpName, _EPH_IMG_DIR_ . 'm/' . $language . '-default-' . stripslashes($imageType['name']) . '.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your manufacturer directory.');
                        }

                    }

                }

                unlink($tmpName);
            }

        }

    }

    /**
     * Process update
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processUpdate() {

        if (_EPH_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if ((isset($_FILES['no_picture']) && !$_FILES['no_picture']['error'] || isset($_FILES['flag']) && !$_FILES['flag']['error'])
            && Validate::isLanguageIsoCode(Tools::getValue('iso_code'))
        ) {

            if ($_FILES['no_picture']['error'] == UPLOAD_ERR_OK) {
                $this->copyNoPictureImage(strtolower(Tools::getValue('iso_code')));
            }

            // class AdminTab deal with every $_FILES content, don't do that for no_picture
            unset($_FILES['no_picture']);
        }

        /** @var Language $object */
        $object = $this->loadObject();

        if (Tools::getValue('active') != (int) $object->active) {

            if (!$this->checkDisableStatus($object)) {
                return false;
            }

        }

        $this->checkEmployeeIdLang($object->id);

        return parent::processUpdate();
    }

    /**
     * Ajax process check language pack
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessCheckLangPack() {

        $this->errors[] = $this->l('Our apologies. Language packs aren\'t in the first few ephenyx releases.');
    }

    /**
     * Process bulk delete
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function processBulkDelete() {

        if (is_array($this->boxes) && !empty($this->boxes)) {

            foreach ($this->boxes as $idLang) {
                $object = new Language((int) $idLang);

                if (!$this->checkDeletion($object)) {
                    return false;
                }

                if (!$this->deleteNoPictureImages((int) $object->id)) {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.') . ' <b>' . $this->table . '</b> ';

                    return false;
                }

            }

        }

        return parent::processBulkDelete();
    }

    /**
     * Process bulk disable selection
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function processBulkDisableSelection() {

        if (is_array($this->boxes) && !empty($this->boxes)) {

            foreach ($this->boxes as $idLang) {
                $object = new Language((int) $idLang);

                if (!$this->checkDisableStatus($object)) {
                    return false;
                }

                $this->checkEmployeeIdLang($object->id);
            }

        }

        return parent::processBulkDisableSelection();
    }

    /**
     * @param Language $object
     * @param string   $table
     *
     * @since 1.9.1.0
     */
    protected function copyFromPost(&$object, $table) {

        if ($object->id && ($object->iso_code != $_POST['iso_code'])) {

            if (Validate::isLanguageIsoCode($_POST['iso_code'])) {
                $object->moveToIso($_POST['iso_code']);
            }

        }

        parent::copyFromPost($object, $table);
    }

    /**
     * After image upload
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function afterImageUpload() {

        parent::afterImageUpload();

        if (($idLang = (int) Tools::getValue('id_lang')) && isset($_FILES) && count($_FILES) && file_exists(_EPH_LANG_IMG_DIR_ . $idLang . '.jpg')) {
            $currentFile = _EPH_TMP_IMG_DIR_ . 'lang_mini_' . $idLang . '_' . $this->context->company->id . '.jpg';

            if (file_exists($currentFile)) {
                unlink($currentFile);
            }

        }

        return true;
    }

}
