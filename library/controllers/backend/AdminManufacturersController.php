<?php

/**
 * Class AdminManufacturersControllerCore
 *
 * @since 1.8.1.0
 */
class AdminManufacturersControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    /** @var bool $bootstrap */
    public $bootstrap = true;
    /** @var array countries list */
    protected $countries_array = [];
    // @codingStandardsIgnoreEnd

    /**
     * AdminManufacturersControllerCore constructor.
     *
     * @since 1.8.1.0
     */
    public function __construct() {

        $this->table = 'manufacturer';
        $this->className = 'Manufacturer';
        $this->publicName = $this->l('Manufacturers');
        $this->context = Context::getContext();

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 'm',
        ];
        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_MANUFACTURER_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_MANUFACTURER_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_MANUFACTURER_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_MANUFACTURER_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_MANUFACTURER_FIELDS', Tools::jsonEncode($this->getManufacturerFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_MANUFACTURER_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_MANUFACTURER_FIELDS', Tools::jsonEncode($this->getManufacturerFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_MANUFACTURER_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
        $this->addJS(__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'manufacturer.js');
    }

    public function initContent() {

        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;

        $ajaxlinkMeta = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Meta List');

        $this->context->smarty->assign([
            'controller'      => Tools::getValue('controller'),
            'gridId'          => 'grid_' . $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'linkController'  => $this->context->link->getAdminLink($this->controller_name),
            'AjaxLink'        => $this->context->link->getAdminLink($this->controller_name),
            'paragridScript'  => $this->generateParaGridScript(),
            'titleBar'        => $this->TitleBar,
            'bo_imgdir'       => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
            'current_id_lang' => $this->context->language->id,
            'idController'    => '',
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
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->complete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        $paragrid->selectionModelType = 'row';
        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'Ajouter une nouvelle marque\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'addBrand',
                ],

            ],
        ];
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Gestion des marques') . '\'';
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
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Voir ou modifier la marque') . ' \'+rowData.name,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {

                                editBrand(rowData.id_manufacturer);
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer la marque ') . ' \ : \'+rowData.name,
                            icon: "trash",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }

                                return true;
                            },

                            callback: function(itemKey, opt, e) {
                                deleteBrand(rowData.id_manufacturer);
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

    public function getManufacturerRequest() {

        $manufacturers = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.`id_manufacturer`, a.`name`, case when a.`active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`, a.`active` as enable, b.*')
                ->from('manufacturer', 'a')
                ->leftJoin('manufacturer_lang', 'b', 'b.`id_manufacturer` = a.`id_manufacturer` AND b.`id_lang` = ' . (int) $this->context->language->id)
                ->groupBy('a.`id_manufacturer`')
                ->orderBy('a.`name` ASC')
        );

        foreach ($manufacturers as &$manufacturer) {

            $manufacturer['city'] = '';
            $manufacturer['country'] = "";

            $id_address = Address::getAddressIdByManufacturerId($manufacturer['id_manufacturer']);

            if (Validate::isUnsignedId($id_address)) {
                $address = New Address($id_address);
                $manufacturer['city'] = $address->city;
                $manufacturer['country'] = Country::getNameById($this->context->language->id, $address->id_country);

            }

        }

        return $manufacturers;

    }

    public function ajaxProcessgetManufacturerRequest() {

        die(Tools::jsonEncode($this->getManufacturerRequest()));

    }

    public function getManufacturerFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 50,
                'dataIndx'   => 'id_manufacturer',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'filter'     => [
                    'crules' => [['condition' => "begin"]],
                ],
                'hiddenable' => 'no',
            ],

            [
                'title'    => $this->l('Name'),
                'width'    => 200,
                'dataIndx' => 'name',
                'dataType' => 'string',
                'editable' => false,
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],

            [
                'title'    => $this->l('Country'),
                'width'    => 200,
                'dataIndx' => 'country',
                'dataType' => 'string',
                'editable' => false,
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'enable',
                'dataType'   => 'string',
                'align'      => 'center',
                'valign'     => 'center',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [

                    'attr'   => "id=\"enableSelecor\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],

                ],

            ],

            [
                'title'    => $this->l('Active'),
                'width'    => 50,
                'dataIndx' => 'active',
                'align'    => 'center',
                'dataType' => 'html',
                'filter'   => [
                    'crules' => [['condition' => "equal"]],
                ],
            ],
        ];

    }

    public function ajaxProcessgetManufacturerFields() {

        die(EmployeeConfiguration::get('EXPERT_MANUFACTURER_FIELDS'));
    }

    public function ajaxProcessEditManufacturer() {

        $id_manufacturer = Tools::getValue('id_manufacturer');
        $this->object = new $this->className($id_manufacturer);

        $data = $this->createTemplate('controllers/manufacturers/editManufacturer.tpl');

        $context = Context::getContext();

        $image = _EPH_MANU_IMG_DIR_ . $this->object->id . '.jpg';

        if (file_exists($image)) {
            $image_url = _THEME_MANU_DIR_ . $this->object->id . '.jpg';
        } else {
            $image_url = _THEME_MANU_DIR_ . 'fr-default-medium_default.jpg';
        }

        $image_size = file_exists($image) ? filesize($image) / 1000 : false;

        $address = null;
        $id_address = Address::getAddressIdByManufacturerId($this->object->id);

        if ($id_address > 0) {
            $address = new Address((int) $id_address);
        }

        $this->context->smarty->assign([
            'manufacturer' => $this->object,
            'languages'    => Language::getLanguages(false),
            'id_lang'      => $this->context->language->id,
            'countries'    => Country::getCountries($this->context->language->id, false),
            'devises'      => Currency::getCurrencies(false, false),
            'address'      => $address,
            'iso'          => file_exists(__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'tiny_mce/langs/' . $iso . '.js') ? $iso : 'en',
            'path_css'     => _THEME_CSS_DIR_,
            'ad'           => __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_),
            'image'        => $image_url,
            'image_size'   => $image_size,
            'link'         => $this->context->link,
            'formId'       => 'form-' . $this->table,

        ]);

        $result = [
            'html' => $data->fetch(),
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessNewManufacturer() {

        $this->object = new $this->className();

        $data = $this->createTemplate('controllers/manufacturers/addManufacturer.tpl');

        $context = Context::getContext();

        $address = null;

        $image_url = _THEME_MANU_DIR_ . 'fr-default-medium_default.jpg';

        $iso = $this->context->language->iso_code;
        $this->context->smarty->assign([
            'manufacturer'    => $this->object,
            'languages'       => Language::getLanguages(false),
            'id_lang'         => $this->context->language->id,
            'taxModes'        => TaxMode::getTaxModes(),
            'currency'        => $context->currency,
            'countries'       => Country::getCountries($this->context->language->id, false),
            'default_country' => Configuration::get('EPH_COUNTRY_DEFAULT'),
            'address'         => $address,
            'iso'             => file_exists(__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'tiny_mce/langs/' . $iso . '.js') ? $iso : 'en',
            'image'           => $image_url,
            'path_css'        => _THEME_CSS_DIR_,
            'ad'              => __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_),
            'link'            => $this->context->link,
            'formId'          => 'form-' . $this->table,
        ]);

        $result = [
            'html' => $data->fetch(),
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessAddNewManufacturer() {

        $this->object = new $this->className();

        $this->copyFromPost($this->object, $this->table);

        $result = $this->object->add();

        if ($result) {

            $address1 = Tools::getValue('address1');

            if (!empty($address1)) {
                $address = new Address();
                $address->alias = Tools::getValue('name', null);
                $address->id_manufacturer = $this->object->id;
                $address->lastname = 'manufacturer'; // skip problem with numeric characters in manufacturer name
                $address->firstname = 'manufacturer'; // skip problem with numeric characters in manufacturer name
                $address->company = Tools::getValue('company');
                $address->address1 = Tools::getValue('address1');
                $address->address2 = Tools::getValue('address2');
                $address->postcode = Tools::getValue('postcode');
                $address->id_country = Tools::getValue('address_country');
                $address->id_state = Tools::getValue('id_state');
                $address->city = Tools::getValue('city');
                $address->phone = Tools::getValue('phone');
                $address->phone_mobile = Tools::getValue('phone_mobile');

                if (!$address->save()) {
                    $this->errors[] = Tools::displayError('We encounter a problem adding the Address.');
                }

            }

            $imageUploader = new HelperImageUploader('logoManUrl');
            $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {
                    $destinationFile = _EPH_MANU_IMG_DIR_ . $this->object->id . '.jpg.';
                    $fileName = $this->object->id . '.jpg.';

                    if (copy($image['save_path'], $destinationFile)) {

                        $imagesTypes = ImageType::getImagesTypes('manufacturers');

                        foreach ($imagesTypes as $k => $imageType) {
                            ImageManager::resize(
                                _EPH_MANU_IMG_DIR_ . $fileName,
                                _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '.jpg',
                                (int) $imageType['width'],
                                (int) $imageType['height']
                            );

                            if (ImageManager::webpSupport()) {
                                ImageManager::resize(
                                    _EPH_MANU_IMG_DIR_ . $fileName,
                                    _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '.webp',
                                    (int) $imageType['width'],
                                    (int) $imageType['height'],
                                    'webp'
                                );
                            }

                            if (ImageManager::retinaSupport()) {
                                ImageManager::resize(
                                    _EPH_MANU_IMG_DIR_ . $fileName,
                                    _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '2x.jpg',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2
                                );

                                if (ImageManager::webpSupport()) {
                                    ImageManager::resize(
                                        _EPH_MANU_IMG_DIR_ . $fileName,
                                        _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '2x.webp',
                                        (int) $imageType['width'] * 2,
                                        (int) $imageType['height'] * 2,
                                        'webp'
                                    );
                                }

                            }

                        }

                        $currentLogoFile = _EPH_TMP_IMG_DIR_ . 'manufacturer_mini_' . $this->object->id . '_' . $this->context->company->id . '.jpg';
                    }

                }

            }

        }

        $this->errors = array_unique($this->errors);

        if (count($this->errors)) {
            $result = [
                'success' => false,
                'message' => implode(PHP_EOL, $this->errors),
            ];
        } else {
            $result = [
                'success'   => true,
                'message'   => $this->l('La nouvelle marque a été ajoutée avec succès'),
                'id_object' => $this->object->id,
                'name'      => $this->object->name,
            ];
        }

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessUpdateManufacturer() {

        $id_manufacturer = Tools::getValue('id_manufacturer');
        $this->object = new $this->className($id_manufacturer);

        $this->copyFromPost($this->object, $this->table);
        $result = $this->object->update();

        if ($this->object->id) {
            $this->updateAssoShop($this->object->id);
        }

        if ($result) {

            $address1 = Tools::getValue('address1');
            $id_address = Tools::getValue('id_address');

            if ($id_address > 0) {
                $address = new Address($id_address);
                $address->company = Tools::getValue('company');
                $address->address1 = Tools::getValue('address1');
                $address->address2 = Tools::getValue('address2');
                $address->postcode = Tools::getValue('postcode');
                $address->phone = Tools::getValue('phone');
                $address->phone_mobile = Tools::getValue('phone_mobile');
                $address->id_country = Tools::getValue('id_country');
                $address->id_state = Tools::getValue('id_state');
                $address->city = Tools::getValue('city');

                if (!$address->update()) {
                    $this->errors[] = Tools::displayError('We encounter a problem updating the Address.');
                }

            } else
            if (!empty($address1)) {
                $address = new Address();
                $address->alias = $this->object->name;
                $address->id_manufacturer = $this->object->id;
                $address->lastname = 'manufacturer'; // skip problem with numeric characters in manufacturer name
                $address->firstname = 'manufacturer'; // skip problem with numeric characters in manufacturer name
                $address->company = Tools::getValue('company');
                $address->address1 = Tools::getValue('address1');
                $address->address2 = Tools::getValue('address2');
                $address->postcode = Tools::getValue('postcode');
                $address->id_country = Tools::getValue('address_country');
                $address->id_state = Tools::getValue('id_state');
                $address->city = Tools::getValue('city');
                $address->phone = Tools::getValue('phone');
                $address->phone_mobile = Tools::getValue('phone_mobile');

                if (!$address->save()) {
                    $this->errors[] = Tools::displayError('We encounter a problem adding the Address.');
                }

            }

        }

        $imageUploader = new HelperImageUploader('logoManUrl');
        $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
        $files = $imageUploader->process();

        if (is_array($files) && count($files)) {

            foreach ($files as $image) {
                $destinationFile = _EPH_MANU_IMG_DIR_ . $this->object->id . '.jpg.';
                $fileName = $this->object->id . '.jpg.';

                if (copy($image['save_path'], $destinationFile)) {

                    $imagesTypes = ImageType::getImagesTypes('manufacturers');

                    foreach ($imagesTypes as $k => $imageType) {
                        ImageManager::resize(
                            _EPH_MANU_IMG_DIR_ . $fileName,
                            _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '.jpg',
                            (int) $imageType['width'],
                            (int) $imageType['height']
                        );

                        if (ImageManager::webpSupport()) {
                            ImageManager::resize(
                                _EPH_MANU_IMG_DIR_ . $fileName,
                                _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            );
                        }

                        if (ImageManager::retinaSupport()) {
                            ImageManager::resize(
                                _EPH_MANU_IMG_DIR_ . $fileName,
                                _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '2x.jpg',
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2
                            );

                            if (ImageManager::webpSupport()) {
                                ImageManager::resize(
                                    _EPH_MANU_IMG_DIR_ . $fileName,
                                    _EPH_MANU_IMG_DIR_ . $this->object->id . '-' . stripslashes($imageType['name']) . '2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    'webp'
                                );
                            }

                        }

                    }

                    $currentLogoFile = _EPH_TMP_IMG_DIR_ . 'manufacturer_mini_' . $this->object->id . '_' . $this->context->company->id . '.jpg';
                }

            }

        }

        $this->errors = array_unique($this->errors);

        if (count($this->errors)) {
            $result = [
                'success' => false,
                'message' => implode(PHP_EOL, $this->errors),
            ];
        } else {
            $result = [
                'success'   => true,
                'message'   => $this->publicName . $this->l(' has been successfully updated'),
                'id_object' => $this->object->id,
            ];
        }

        die(Tools::jsonEncode($result));
    }

    /**
     * @param string $textDelimiter
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function processExport($textDelimiter = '"') {

        if (strtolower($this->table) == 'address') {
            $this->_defaultOrderBy = 'id_manufacturer';
            $this->_where = 'AND a.`id_customer` = 0 AND a.`id_manufacturer` = 0 AND a.`id_warehouse` = 0 AND a.`deleted`= 0';
        }

        parent::processExport($textDelimiter);
    }

    /**
     * Process save
     *
     * @return bool Indicates whether save was successful
     *
     * @since 1.8.1.0
     */
    public function processSave() {

        if (Tools::isSubmit('submitAddaddress')) {
            $this->display = 'editaddresses';
        }

        return parent::processSave();
    }

    /**
     * After image upload
     *
     * @return bool Indicates whether post processing was successful
     *
     * @since 1.8.1.0
     */
    protected function afterImageUpload() {

        $res = true;

        /* Generate image with differents size */

        if (($idManufacturer = (int) Tools::getValue('id_manufacturer')) &&
            isset($_FILES) &&
            count($_FILES) &&
            file_exists(_EPH_MANU_IMG_DIR_ . $idManufacturer . '.jpg')
        ) {
            $imagesTypes = ImageType::getImagesTypes('manufacturers');

            foreach ($imagesTypes as $k => $imageType) {
                $res &= ImageManager::resize(
                    _EPH_MANU_IMG_DIR_ . $idManufacturer . '.jpg',
                    _EPH_MANU_IMG_DIR_ . $idManufacturer . '-' . stripslashes($imageType['name']) . '.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );

                if (ImageManager::webpSupport()) {
                    $res &= ImageManager::resize(
                        _EPH_MANU_IMG_DIR_ . $idManufacturer . '.jpg',
                        _EPH_MANU_IMG_DIR_ . $idManufacturer . '-' . stripslashes($imageType['name']) . '.webp',
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        'webp'
                    );
                }

                if (ImageManager::retinaSupport()) {
                    $res &= ImageManager::resize(
                        _EPH_MANU_IMG_DIR_ . $idManufacturer . '.jpg',
                        _EPH_MANU_IMG_DIR_ . $idManufacturer . '-' . stripslashes($imageType['name']) . '2x.jpg',
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2
                    );

                    if (ImageManager::webpSupport()) {
                        $res &= ImageManager::resize(
                            _EPH_MANU_IMG_DIR_ . $idManufacturer . '.jpg',
                            _EPH_MANU_IMG_DIR_ . $idManufacturer . '-' . stripslashes($imageType['name']) . '2x.webp',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2,
                            'webp'
                        );
                    }

                }

            }

            $currentLogoFile = _EPH_TMP_IMG_DIR_ . 'manufacturer_mini_' . $idManufacturer . '_' . $this->context->company->id . '.jpg';

            if ($res && file_exists($currentLogoFile)) {
                unlink($currentLogoFile);
            }

        }

        if (!$res) {
            $this->errors[] = Tools::displayError('Unable to resize one or more of your pictures.');
        } else {

            if ((int) Configuration::get('EPH_IMAGES_LAST_UPD_MANUFACTURERS') < $idManufacturer) {
                Configuration::updateValue('EPH_IMAGES_LAST_UPD_MANUFACTURERS', $idManufacturer);
            }

        }

        return $res;
    }

    /**
     * Before delete
     *
     * @param ObjectModel $object
     *
     * @return true
     *
     * @since 1.8.1.0
     */
    protected function beforeDelete($object) {

        return true;
    }

}
