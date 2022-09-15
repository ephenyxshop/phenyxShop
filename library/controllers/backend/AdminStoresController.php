<?php

/**
 * Class AdminStoresControllerCore
 *
 * @since 1.9.1.0
 */
class AdminStoresControllerCore extends AdminController {

    /**
     * AdminStoresControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'store';
        $this->className = 'Store';
        $this->lang = true;
        $this->toolbar_scroll = false;

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->fieldImageSettings = [
            'name' => 'image',
            'dir'  => 'st',
        ];

        $this->fields_list = [
            'id_store' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'     => ['title' => $this->l('Name'), 'filter_key' => 'a!name'],
            'address1' => ['title' => $this->l('Address'), 'filter_key' => 'a!address1'],
            'city'     => ['title' => $this->l('City')],
            'postcode' => ['title' => $this->l('Zip/postal code')],
            'state'    => ['title' => $this->l('State'), 'filter_key' => 'st!name'],
            'country'  => ['title' => $this->l('Country'), 'filter_key' => 'cl!name'],
            'phone'    => ['title' => $this->l('Phone')],
            'fax'      => ['title' => $this->l('Fax')],
            'active'   => ['title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Parameters'),
                'fields' => [
                    'EPH_STORES_DISPLAY_FOOTER'  => [
                        'title' => $this->l('Display in the footer'),
                        'hint'  => $this->l('Display a link to the store locator in the footer.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'EPH_STORES_DISPLAY_SITEMAP' => [
                        'title' => $this->l('Display in the sitemap page'),
                        'hint'  => $this->l('Display a link to the store locator in the sitemap page.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'EPH_STORES_SIMPLIFIED'      => [
                        'title' => $this->l('Show a simplified store locator'),
                        'hint'  => $this->l('No map, no search, only a store directory.'),
                        'cast'  => 'intval',
                        'type'  => 'bool',
                    ],
                    'EPH_STORES_CENTER_LAT'      => [
                        'title' => $this->l('Default latitude'),
                        'hint'  => $this->l('Used for the initial position of the map.'),
                        'cast'  => 'floatval',
                        'type'  => 'text',
                        'size'  => '10',
                    ],
                    'EPH_STORES_CENTER_LONG'     => [
                        'title' => $this->l('Default longitude'),
                        'hint'  => $this->l('Used for the initial position of the map.'),
                        'cast'  => 'floatval',
                        'type'  => 'text',
                        'size'  => '10',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();

        $this->_buildOrderedFieldsShop($this->_getDefaultFieldsContent());
        EmployeeConfiguration::updateValue('EXPERT_STORES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_STORES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_STORES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_STORES_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_STORES_FIELDS', Tools::jsonEncode($this->getStoreFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STORES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_STORES_FIELDS', Tools::jsonEncode($this->getStoreFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STORES_FIELDS'), true);
        }

    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        Media::addJsDef([
            'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),

        ]);

    }

    protected function _buildOrderedFieldsShop($formFields) {

        $fields = $formFields;
        $this->fields_options['contact'] = [
            'title'  => $this->l('Contact details'),
            'icon'   => 'icon-user',
            'fields' => $fields,
            'submit' => ['title' => $this->l('Save')],
        ];
    }

    protected function _getDefaultFieldsContent() {

        $countryList = [];
        $countryList[] = ['id' => '0', 'name' => $this->l('Choose your country')];

        foreach (Country::getCountries($this->context->language->id) as $country) {
            $countryList[] = ['id' => $country['id_country'], 'name' => $country['name']];
        }

        $stateList = [];
        $stateList[] = ['id' => '0', 'name' => $this->l('Choose your state (if applicable)')];

        foreach (State::getStates($this->context->language->id) as $state) {
            $stateList[] = ['id' => $state['id_state'], 'name' => $state['name']];
        }

        $formFields = [
            'EPH_SHOP_NAME'       => [
                'title'      => $this->l('Shop name'),
                'hint'       => $this->l('Displayed in emails and page titles.'),
                'validation' => 'isGenericName',
                'required'   => true,
                'type'       => 'text',
                'no_escape'  => true,
            ],
            'EPH_SHOP_EMAIL'      => [
                'title'      => $this->l('Shop email'),
                'hint'       => $this->l('Displayed in emails sent to customers.'),
                'validation' => 'isEmail',
                'required'   => true,
                'type'       => 'text',
            ],
            'EPH_SHOP_DETAILS'    => [
                'title'      => $this->l('Registration number'),
                'hint'       => $this->l('Shop registration information (e.g. SIRET or RCS).'),
                'validation' => 'isGenericName',
                'type'       => 'textarea',
                'cols'       => 30,
                'rows'       => 5,
            ],
            'EPH_SHOP_ADDR1'      => [
                'title'      => $this->l('Shop address line 1'),
                'validation' => 'isAddress',
                'type'       => 'text',
            ],
            'EPH_SHOP_ADDR2'      => [
                'title'      => $this->l('Shop address line 2'),
                'validation' => 'isAddress',
                'type'       => 'text',
            ],
            'EPH_SHOP_CODE'       => [
                'title'      => $this->l('Zip/postal code'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'EPH_SHOP_CITY'       => [
                'title'      => $this->l('City'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'EPH_SHOP_COUNTRY_ID' => [
                'title'        => $this->l('Country'),
                'validation'   => 'isInt',
                'type'         => 'select',
                'list'         => $countryList,
                'identifier'   => 'id',
                'cast'         => 'intval',
                'defaultValue' => (int) $this->context->country->id,
            ],
            'EPH_SHOP_STATE_ID'   => [
                'title'      => $this->l('State'),
                'validation' => 'isInt',
                'type'       => 'select',
                'list'       => $stateList,
                'identifier' => 'id',
                'cast'       => 'intval',
            ],
            'EPH_SHOP_PHONE'      => [
                'title'      => $this->l('Phone'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
            'EPH_SHOP_FAX'        => [
                'title'      => $this->l('Fax'),
                'validation' => 'isGenericName',
                'type'       => 'text',
            ],
        ];

        return $formFields;
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderOptions() {

        if ($this->fields_options && is_array($this->fields_options)) {
            //$this->tpl_option_vars['fieldList'] = $this->renderList();
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

    public function initContent() {

        //$this->displayOptionGrid = true;

        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;

        $ajaxlinkMeta = $this->context->link->getAdminLink($this->controller_name);

        $this->TitleBar = $this->l('Stores List');

        $this->context->smarty->assign([
            'manageHeaderFields' => true,
            'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
            'controller'         => Tools::getValue('controller'),
            'tabScript'          => $this->generateTabScript(Tools::getValue('controller')),
            'gridId'             => 'grid_AdminStores',
            'tableName'          => $this->table,
            'className'          => $this->className,
            'linkController'     => $this->context->link->getAdminLink($this->controller_name),
            'AjaxLink'           => $this->context->link->getAdminLink($this->controller_name),
            'paragridScript'     => $this->generateParaGridScript(),
            'titleBar'           => $this->TitleBar,
            'bo_imgdir'          => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
            'current_id_lang'    => $this->context->language->id,
            'idController'       => '',
        ]);

        parent::initContent();
    }

    public function generateParaGridScript($regenerate = false) {

        if (!empty($this->paragridScript) && !$regenerate) {
            return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
        }

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
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
        $paragrid->fillHandle = '\'all\'';

        $paragrid->contextMenuoption = [

            'add'        => [
                'name' => '\'' . $this->l('Add new Store') . '\'',
                'icon' => '"add"',
            ],
            'edit'       => [
                'name' => '\'' . $this->l('Edit the Store: ') . '\'' . '+rowData.name',
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
                'name' => '\'' . $this->l('Delete the selected store: ') . '\'' . '+rowData.name',
                'icon' => '"delete"',
            ],
            'bulkdelete' => [
                'name' => '\'' . $this->l('Delete all the selected stores') . '\'',
                'icon' => '"delete"',
            ],

        ];

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

    public function manageFieldsVisibility($fields) {

        return parent::manageFieldsVisibility($fields);
    }

    public function ajaxProcessupdateVisibility() {

        $headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STORES_FIELDS'), true);
        $visibility = Tools::getValue('visibilities');

        foreach ($headerFields as $key => $headerField) {
            $hidden = '';

            foreach ($headerField as $field => $value) {

                if ($field == 'dataIndx') {

                    if ($visibility[$value] == 1) {
                        $hidden = false;
                    } else

                    if ($visibility[$value] == 0) {
                        $hidden = true;
                    }

                }

            }

            $headerField['hidden'] = $hidden;

            $headerFields[$key] = $headerField;
        }

        $headerFields = Tools::jsonEncode($headerFields);
        EmployeeConfiguration::updateValue('EXPERT_STORES_FIELDS', $headerFields);
        die($headerFields);
    }

    public function getStoreRequest() {

        $stores = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.* , sl.*, cl.`name` as `country`, st.`name` as `state`')
                ->from('store', 'a')
                ->leftJoin('store_lang', 'sl', 'sl.`id_store` = a.`id_store`')
                ->leftJoin('country_lang', 'cl', 'cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . (int) $this->context->language->id)
                ->leftJoin('state', 'st', 'st.`id_state` = a.`id_state`')
                ->orderBy('a.`id_store` ASC')
        );
        $storeLink = $this->context->link->getAdminLink($this->controller_name);

        foreach ($stores as &$store) {

            if ($store['active'] == 1) {
                $store['active'] = '<div class="p-active"></div>';
            } else {
                $store['active'] = '<div class="p-inactive"></div>';
            }

            $store['openLink'] = $storeLink . '&id_store=' . $store['id_store'] . '&id_object=' . $store['id_store'] . '&updatestore&action=initUpdateController&ajax=true';
            $store['addLink'] = $storeLink . '&action=addObject&ajax=true&addstore';

        }

        return $stores;
    }

    public function ajaxProcessgetStoreRequest() {

        die(Tools::jsonEncode($this->getStoreRequest()));

    }

    public function getStoreFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'width'      => 50,
                'dataIndx'   => 'id_store',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'hidden'     => true,
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
                'title'      => $this->l('Email'),
                'width'      => 200,
                'dataIndx'   => 'email',
                'cls'        => 'name-handle',
                'align'      => 'left',
                'editable'   => false,
                'dataType'   => 'string',
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Address'),
                'width'    => 150,
                'dataIndx' => 'address1',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('City'),
                'width'    => 200,
                'dataIndx' => 'city',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Zip/postal code'),
                'width'    => 150,
                'dataIndx' => 'postcode',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('State'),
                'width'    => 200,
                'dataIndx' => 'state',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',
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
                'title'    => $this->l('Phone'),
                'width'    => 200,
                'dataIndx' => 'phone',
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

    public function ajaxProcessgetStoreFields() {

        die(EmployeeConfiguration::get('EXPERT_STORES_FIELDS'));
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initToolbar() {

        parent::initToolbar();

        if ($this->display == 'options') {
            unset($this->toolbar_btn['new']);
        } else
        if ($this->display != 'add' && $this->display != 'edit') {
            unset($this->toolbar_btn['save']);
        }

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
            $this->page_header_toolbar_btn['new_store'] = [
                'href' => static::$currentIndex . '&addstore&token=' . $this->token,
                'desc' => $this->l('Add new store', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Render list
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderList() {

        // Set toolbar options
        $this->display = null;
        $this->initToolbar();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'cl.`name` country, st.`name` state';
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl
                ON (cl.`id_country` = a.`id_country`
                AND cl.`id_lang` = ' . (int) $this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'state` st
                ON (st.`id_state` = a.`id_state`)';

        return parent::renderList();
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

        $image = _EPH_STORE_IMG_DIR_ . $obj->id . '.jpg';
        $imageUrl = ImageManager::thumbnail(
            $image,
            $this->table . '_' . (int) $obj->id . '.' . $this->imageType,
            350,
            $this->imageType,
            true,
            true
        );
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

        $tmpAddr = new Address();
        $res = $tmpAddr->getFieldsRequiredDatabase();
        $requiredFields = [];

        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Stores'),
                'icon'  => 'icon-home',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => false,
                    'hint'     => [
                        $this->l('Store name (e.g. City Center Mall Store).'),
                        $this->l('Allowed characters: letters, spaces and %s'),
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Address'),
                    'name'     => 'address1',
                    'lang'     => true,
                    'required' => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Address (2)'),
                    'name'  => 'address2',
                    'lang'  => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal Code'),
                    'name'     => 'postcode',
                    'required' => in_array('postcode', $requiredFields),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('City'),
                    'name'     => 'city',
                    'required' => true,
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('State'),
                    'name'     => 'id_state',
                    'required' => true,
                    'options'  => [
                        'id'    => 'id_state',
                        'name'  => 'name',
                        'query' => null,
                    ],
                ],
                [
                    'type'      => 'latitude',
                    'label'     => $this->l('Latitude / Longitude'),
                    'name'      => 'latitude',
                    'required'  => true,
                    'maxlength' => 12,
                    'hint'      => $this->l('Store coordinates (e.g. 45.265469/-47.226478).'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Phone'),
                    'name'  => 'phone',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Fax'),
                    'name'  => 'fax',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Email address'),
                    'name'  => 'email',
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Note'),
                    'lang'  => true,
                    'name'  => 'note',
                    'cols'  => 42,
                    'rows'  => 4,
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
                    'hint'     => $this->l('Whether or not to display this store.'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Picture'),
                    'name'          => 'image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'hint'          => $this->l('Storefront picture.'),
                ],
            ],
            'hours'  => [],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        

        $days = [];
        $days[1] = $this->l('Monday');
        $days[2] = $this->l('Tuesday');
        $days[3] = $this->l('Wednesday');
        $days[4] = $this->l('Thursday');
        $days[5] = $this->l('Friday');
        $days[6] = $this->l('Saturday');
        $days[7] = $this->l('Sunday');

        $hours = json_decode($this->getFieldValue($obj, 'hours'), true);

        // Retrocompatibility for ephenyx <= 1.0.4.
        //
        // To get rid of this, introduce a data converter executed by the
        // upgrader over a couple of releases, making this obsolete.

        if (!$hours) {
            $hours = Tools::unSerialize($this->getFieldValue($obj, 'hours'));
        }

        $this->fields_value = [
            'latitude'  => $this->getFieldValue($obj, 'latitude') ? $this->getFieldValue($obj, 'latitude') : Configuration::get('EPH_STORES_CENTER_LAT'),
            'longitude' => $this->getFieldValue($obj, 'longitude') ? $this->getFieldValue($obj, 'longitude') : Configuration::get('EPH_STORES_CENTER_LONG'),
            'days'      => $days,
            'hours'     => isset($hours) ? $hours : false,
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

        if (isset($_POST['submitAdd' . $this->table])) {
            /* Cleaning fields */

            foreach ($_POST as $kp => $vp) {

                if (!in_array($kp, ['checkBoxShopGroupAsso_store', 'checkBoxShopAsso_store']) && !is_array($_POST[$kp])) {
                    $_POST[$kp] = trim($vp);
                }

            }

            /* Rewrite latitude and longitude to 8 digits */
            $_POST['latitude'] = number_format((float) $_POST['latitude'], 8);
            $_POST['longitude'] = number_format((float) $_POST['longitude'], 8);

            /* If the selected country does not contain states */
            $idState = (int) Tools::getValue('id_state');
            $idCountry = (int) Tools::getValue('id_country');
            $country = new Country((int) $idCountry);

            if ($idCountry && $country && !(int) $country->contains_states && $idState) {
                $this->errors[] = Tools::displayError('You\'ve selected a state for a country that does not contain states.');
            }

            /* If the selected country contains states, then a state have to be selected */

            if ((int) $country->contains_states && !$idState) {
                $this->errors[] = Tools::displayError('An address located in a country containing states must have a state selected.');
            }

            $latitude = (float) Tools::getValue('latitude');
            $longitude = (float) Tools::getValue('longitude');

            if (empty($latitude) || empty($longitude)) {
                $this->errors[] = Tools::displayError('Latitude and longitude are required.');
            }

            $postcode = Tools::getValue('postcode');
            /* Check zip code format */

            if ($country->zip_code_format && !$country->checkZipCode($postcode)) {
                $this->errors[] = Tools::displayError('Your Zip/postal code is incorrect.') . '<br />' . Tools::displayError('It must be entered as follows:') . ' ' . str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format)));
            } else
            if (empty($postcode) && $country->need_zip_code) {
                $this->errors[] = Tools::displayError('A Zip/postal code is required.');
            } else
            if ($postcode && !Validate::isPostCode($postcode)) {
                $this->errors[] = Tools::displayError('The Zip/postal code is invalid.');
            }

            /* Store hours */
            $_POST['hours'] = [];

            for ($i = 1; $i < 8; $i++) {
                $_POST['hours'][] .= Tools::getValue('hours_' . (int) $i);
            }

            $_POST['hours'] = json_encode($_POST['hours']);
        }

        if (!count($this->errors)) {
            parent::postProcess();
        } else {
            $this->display = 'add';
        }

    }

    /**
     * Before updating options
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function beforeUpdateOptions() {

        if (isset($_POST['EPH_SHOP_STATE_ID']) && $_POST['EPH_SHOP_STATE_ID'] != '0') {
            $sql = 'SELECT `active` FROM `' . _DB_PREFIX_ . 'state`
                    WHERE `id_country` = ' . (int) Tools::getValue('EPH_SHOP_COUNTRY_ID') . '
                        AND `id_state` = ' . (int) Tools::getValue('EPH_SHOP_STATE_ID');
            $isStateOk = Db::getInstance()->getValue($sql);

            if ($isStateOk != 1) {
                $this->errors[] = Tools::displayError('The specified state is not located in this country.');
            }

        }

    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsShopCountryId($value) {

        if (!$this->errors && $value) {
            $country = new Country($value, $this->context->language->id);

            if ($country->id) {
                Configuration::updateValue('EPH_SHOP_COUNTRY_ID', $value);
                Configuration::updateValue('EPH_SHOP_COUNTRY', $country->name);
            }

        }

    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsShopStateId($value) {

        if (!$this->errors && $value) {
            $state = new State($value);

            if ($state->id) {
                Configuration::updateValue('EPH_SHOP_STATE_ID', $value);
                Configuration::updateValue('EPH_SHOP_STATE', $state->name);
            }

        }

    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function postImage($id) {

        $ret = parent::postImage($id);

        if (($idStore = (int) Tools::getValue('id_store')) && isset($_FILES) && count($_FILES) && file_exists(_EPH_STORE_IMG_DIR_ . $idStore . '.jpg')) {
            $imageTypes = ImageType::getImagesTypes('stores');

            foreach ($imageTypes as $k => $imageType) {
                ImageManager::resize(
                    _EPH_STORE_IMG_DIR_ . $idStore . '.jpg',
                    _EPH_STORE_IMG_DIR_ . $idStore . '-' . stripslashes($imageType['name']) . '.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );

                if (ImageManager::retinaSupport()) {
                    ImageManager::resize(
                        _EPH_STORE_IMG_DIR_ . $idStore . '.jpg',
                        _EPH_STORE_IMG_DIR_ . $idStore . '-' . stripslashes($imageType['name']) . '2x.jpg',
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2
                    );
                }

                if (ImageManager::webpSupport()) {
                    ImageManager::resize(
                        _EPH_STORE_IMG_DIR_ . $idStore . '.jpg',
                        _EPH_STORE_IMG_DIR_ . $idStore . '-' . stripslashes($imageType['name']) . '.webp',
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        'webp'
                    );

                    if (ImageManager::retinaSupport()) {
                        ImageManager::resize(
                            _EPH_STORE_IMG_DIR_ . $idStore . '.jpg',
                            _EPH_STORE_IMG_DIR_ . $idStore . '-' . stripslashes($imageType['name']) . '2x.webp',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2,
                            'webp'
                        );
                    }

                }

                if (Configuration::get('EPH_IMAGE_LAST_UPD_STORES') < $idStore) {
                    Configuration::updateValue('EPH_IMAGE_LAST_UPD_STORES', $idStore);
                }

            }

        }

        return $ret;
    }

}
