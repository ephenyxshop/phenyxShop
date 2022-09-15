<?php

/**
 * Class AdminCountriesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminCountriesControllerCore extends AdminController {

    /**
     * AdminCountriesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'country';
        $this->className = 'Country';
        $this->publicName = $this->l('Countries');
        $this->lang = true;

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 'st',
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('Country options'),
                'fields' => [
                    'EPH_RESTRICT_DELIVERED_COUNTRIES' => [
                        'title'   => $this->l('Restrict country selections in front office to those covered by active carriers'),
                        'cast'    => 'intval',
                        'type'    => 'bool',
                        'default' => '0',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_COUNTRIES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_COUNTRIES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_COUNTRIES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_COUNTRIES_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_COUNTRIES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_COUNTRIES_FIELDS', Tools::jsonEncode($this->getCountryFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_COUNTRIES_FIELDS'), true);
        }

    }

    public function generateParaGridScript($regenerate = false) {

        if (!empty($this->paragridScript) && !$regenerate) {
            return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
        }

        $context = Context::getContext();
        $controllerLink = $context->link->getAdminLink($this->controller_name);
        $activeSelector = '<div class="pq-theme"><select id="activeSelect"><option value="">' . $this->l('--Select--') . '</option><option value="0">' . $this->l('Disable') . '</option><option value="1">' . $this->l('Enable') . '</option></select></div>';
        $zoneSelector = '<div class="pq-theme"><select id="zoneSelect"><option value="0">' . $this->l('--Select--') . '</option>';

        foreach (Zone::getZones(true) as $zone) {
            $zoneSelector .= '<option value="' . $zone['id_zone'] . '">' . $zone['name'] . '</option>';
        }

        $zoneSelector .= '</select></div>';
        $gridExtraFunction = [
            'buildCountryFilter() ' => '
            var conteneur = $(\'#activeSelecor\').parent().parent();
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
                var zoneconteneur = $(\'#zoneSelector\').parent().parent();
                $(zoneconteneur).empty();
                $(zoneconteneur).append(\'' . $zoneSelector . '\');
                $(\'#zoneSelect\' ).selectmenu({
                    "change": function(event, ui) {
                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx:\'idZone\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
                });
        ', ];

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = 700;
        $paragrid->colModel = EmployeeConfiguration::get('EXPERT_COUNTRIES_FIELDS');
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
        buildCountryFilter();
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
        $paragrid->fillHandle = '\'all\'';
        $paragrid->rowDblClick = 'function( event, ui ) {

            var identifierlink = ui.rowData.' . $this->identifier . ';
            var datalink = \'' . $controllerLink . '&' . $this->identifier . '=\'+identifierlink+\'&id_object=\'+identifierlink+\'&update' . $this->table . '&action=initUpdateController&ajax=true\';
            openAjaxGridLink(datalink, identifierlink, \'' . $this->controller_name . '\', \'View' . $this->controller_name . '\');
        } ';
        $paragrid->contextMenuoption = [
            'add'  => [
                'name' => '\'' . $this->l('Add a new Country') . '\'',
                'icon' => '"add"',
            ],
            'edit' => [
                'name' => '\'' . $this->l('Edit the Country') . ' :\'' . '+rowData.name',
                'icon' => '"edit"',
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
        $paragrid->gridFunction = $gridExtraFunction;

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

    public function getCountryRequest() {

        if (is_object($this->_controller_request) && $this->_controller_request->exists('countryGridRequest')) {

            return $this->_controller_request->get('countryGridRequest');
        }

        $countries = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.`id_country`, b.`name` AS `name`, `iso_code`, a.`id_zone` as `idZone`, `call_prefix`, z.`id_zone` AS `zone`, z.`name` AS zone, case when a.`active` =1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as `active`,  a.`active` as `enable`')
                ->from('country', 'a')
                ->leftJoin('country_lang', 'b', 'b.`id_country` = a.`id_country` AND b.`id_lang` = ' . (int) $this->context->language->id)
                ->leftJoin('zone', 'z', 'z.`id_zone` = a.`id_zone`')
                ->orderBy('b.`name` ASC')
        );

        foreach ($countries as &$country) {

            $country['openLink'] = $this->context->link->getAdminLink($this->controller_name) . '&id_country=' . $country['id_country'] . '&id_object=' . $country['id_country'] . '&updatecountry&action=initUpdateController&ajax=true';

        }

        if (is_object($this->_controller_request)) {
            $this->_controller_request->set('countryGridRequest', $countries);
        }

        return $countries;

    }

    public function ajaxProcessgetCountryRequest() {

        die(Tools::jsonEncode($this->getCountryRequest()));

    }

    public function getCountryFields() {

        $zonesArray = [];
        $this->zones = Zone::getZones();

        foreach ($this->zones as $zone) {
            $zonesArray[$zone['id_zone']] = $zone['name'];
        }

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_country',
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
                'title'    => $this->l('Call prefix'),
                'width'    => 150,
                'dataIndx' => 'call_prefix',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',

            ],
            [
                'title'      => $this->l('Id Zone'),
                'minWidth'   => 150,
                'dataIndx'   => 'idZone',
                'dataType'   => 'integer',
                'editable'   => false,
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],
            [
                'title'    => $this->l('Zone'),
                'width'    => 200,
                'dataIndx' => 'zone',
                'align'    => 'left',
                'editable' => false,
                'dataType' => 'string',
                'filter'   => [
                    'attr'   => "id=\"zoneSelector\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],

                ],
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
                'title'    => $this->l('Enabled'),
                'minWidth' => 100,
                'dataIndx' => 'active',
                'align'    => 'center',
                'valign'   => 'center',
                'halign'   => 'HORIZONTAL_CENTER',
                'dataType' => 'html',
                'filter'   => [
                    'attr'   => "id=\"activeSelecor\", placeholder=" . $this->l('--Select--') . " readonly",
                    'crules' => [['condition' => "equal"]],
                ],

            ],

        ];
    }

    public function ajaxProcessgetCountryFields() {

        die(EmployeeConfiguration::get('EXPERT_COUNTRIES_FIELDS'));
    }

    /**
     * Display call prefix
     *
     * @param string $prefix
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public static function displayCallPrefix($prefix) {

        return ((int) $prefix ? '+' . $prefix : '-');
    }

    public function renderOptions() {

        // If friendly url is not active, do not display custom routes form

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

        $addressLayout = AddressFormat::getAddressCountryFormat($obj->id);

        if ($value = Tools::getValue('address_layout')) {
            $addressLayout = $value;
        }

        $defaultLayout = '';

        $defaultLayoutTab = [
            ['firstname', 'lastname'],
            ['company'],
            ['vat_number'],
            ['address1'],
            ['address2'],
            ['postcode', 'city'],
            ['Country:name'],
            ['phone'],
            ['phone_mobile'],
        ];

        foreach ($defaultLayoutTab as $line) {
            $defaultLayout .= implode(' ', $line) . "\r\n";
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Countries'),
                'icon'  => 'icon-globe',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Country'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Country name') . ' - ' . $this->l('Invalid characters:') . ' &lt;&gt;;=#{} ',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'maxlength' => 3,
                    'class'     => 'uppercase',
                    'required'  => true,
                    'hint'      => $this->l('Two -- or three -- letter ISO code (e.g. "us for United States).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Call prefix'),
                    'name'      => 'call_prefix',
                    'maxlength' => 3,
                    'class'     => 'uppercase',
                    'required'  => true,
                    'hint'      => $this->l('International call prefix, (e.g. 1 for United States).'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Default currency'),
                    'name'    => 'id_currency',
                    'options' => [
                        'query'   => Currency::getCurrencies(false, true, true),
                        'id'      => 'id_currency',
                        'name'    => 'name',
                        'default' => [
                            'label' => $this->l('Default store currency'),
                            'value' => 0,
                        ],
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Zone'),
                    'name'    => 'id_zone',
                    'options' => [
                        'query' => Zone::getZones(),
                        'id'    => 'id_zone',
                        'name'  => 'name',
                    ],
                    'hint'    => $this->l('Geographical region.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Does it need Zip/postal code?'),
                    'name'     => 'need_zip_code',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'need_zip_code_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'need_zip_code_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Zip/postal code format'),
                    'name'     => 'zip_code_format',
                    'required' => true,
                    'desc'     => $this->l('Indicate the format of the postal code: use L for a letter, N for a number, and C for the country\'s ISO 3166-1 alpha-2 code. For example, NNNNN for the United States, France, Poland and many other; LNNNNLLL for Argentina, etc. If you do not want ephenyx to verify the postal code for this country, leave it blank.'),
                ],
                [
                    'type'                    => 'address_layout',
                    'label'                   => $this->l('Address format'),
                    'name'                    => 'address_layout',
                    'address_layout'          => $addressLayout,
                    'encoding_address_layout' => urlencode($addressLayout),
                    'encoding_default_layout' => urlencode($defaultLayout),
                    'display_valid_fields'    => $this->displayValidFields(),
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
                    'hint'     => $this->l('Display this country to your customers (the selected country will always be displayed in the Back Office).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Contains states'),
                    'name'     => 'contains_states',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'contains_states_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="' . $this->l('Yes') . '" title="' . $this->l('Yes') . '" />' . $this->l('Yes'),
                        ],
                        [
                            'id'    => 'contains_states_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="' . $this->l('No') . '" title="' . $this->l('No') . '" />' . $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Do you need a tax identification number?'),
                    'name'     => 'need_identification_number',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'need_identification_number_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="' . $this->l('Yes') . '" title="' . $this->l('Yes') . '" />' . $this->l('Yes'),
                        ],
                        [
                            'id'    => 'need_identification_number_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="' . $this->l('No') . '" title="' . $this->l('No') . '" />' . $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Display tax label (e.g. "Tax incl.")'),
                    'name'     => 'display_tax_label',
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'display_tax_label_on',
                            'value' => 1,
                            'label' => '<img src="../img/admin/enabled.gif" alt="' . $this->l('Yes') . '" title="' . $this->l('Yes') . '" />' . $this->l('Yes'),
                        ],
                        [
                            'id'    => 'display_tax_label_off',
                            'value' => 0,
                            'label' => '<img src="../img/admin/disabled.gif" alt="' . $this->l('No') . '" title="' . $this->l('No') . '" />' . $this->l('No'),
                        ],
                    ],
                ],
            ],

        ];

       

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        return parent::renderForm();
    }

    /**
     * Display valid fields
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    protected function displayValidFields() {

        /* The following translations are needed later - don't remove the comments!
                                            $this->l('Customer');
                                            $this->l('Warehouse');
                                            $this->l('Country');
                                            $this->l('State');
                                            $this->l('Address');
        */

        $htmlTabnav = '<ul class="nav nav-tabs" id="custom-address-fields">';
        $htmlTabcontent = '<div class="tab-content" >';

        $objectList = AddressFormat::getLiableClass('Address');
        $objectList['Address'] = null;

        // Get the available properties for each class
        $i = 0;
        $classTabActive = 'active';

        foreach ($objectList as $className => &$object) {

            if ($i != 0) {
                $classTabActive = '';
            }

            $fields = [];
            $htmlTabnav .= '<li' . ($classTabActive ? ' class="' . $classTabActive . '"' : '') . '>
                <a href="#availableListFieldsFor_' . $className . '"><i class="icon-caret-down"></i>&nbsp;' . Translate::getAdminTranslation($className, 'AdminCountries') . '</a></li>';

            foreach (AddressFormat::getValidateFields($className) as $name) {
                $fields[] = '<a href="javascript:void(0);" class="addPattern btn btn-default btn-xs" id="' . ($className == 'Address' ? $name : $className . ':' . $name) . '">
                    <i class="icon-plus-sign"></i>&nbsp;' . PhenyxObjectModel::displayFieldName($name, $className) . '</a>';
            }

            $htmlTabcontent .= '
                <div class="tab-pane availableFieldsList panel ' . $classTabActive . '" id="availableListFieldsFor_' . $className . '">
                ' . implode(' ', $fields) . '</div>';
            unset($object);
            $i++;
        }

        $htmlTabnav .= '</ul>';
        $htmlTabcontent .= '</div>';

        return $html = $htmlTabnav . $htmlTabcontent;
    }

    /**
     * Process update
     *
     * @return false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processUpdate() {

        /** @var Country $country */
        $country = $this->loadObject();

        if (Validate::isLoadedObject($country) && Tools::getValue('id_zone')) {
            $oldIdZone = $country->id_zone;
            $sql = new DbQuery();
            $sql->select('id_state');
            $sql->from('state');
            $sql->where('`id_country` = ' . (int) $country->id . ' AND `id_zone` = ' . (int) $oldIdZone);
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

            if ($results && count($results)) {
                $ids = [];

                foreach ($results as $res) {
                    $ids[] = (int) $res['id_state'];
                }

                if (count($ids)) {
                    Db::getInstance()->update(
                        'state',
                        [
                            'id_zone' => (int) Tools::getValue('id_zone'),
                        ],
                        '`id_state` IN (' . implode(',', $ids) . ')'
                    );
                }

            }

        }

        return parent::processUpdate();
    }

    /**
     * Post process
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        if (!Tools::getValue('id_' . $this->table)) {

            if (Validate::isLanguageIsoCode(Tools::getValue('iso_code')) && (int) Country::getByIso(Tools::getValue('iso_code'))) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }

        } else

        if (Validate::isLanguageIsoCode(Tools::getValue('iso_code'))) {
            $idCountry = (int) Country::getByIso(Tools::getValue('iso_code'));

            if (!is_null($idCountry) && $idCountry != Tools::getValue('id_' . $this->table)) {
                $this->errors[] = Tools::displayError('This ISO code already exists.You cannot create two countries with the same ISO code.');
            }

        }

        return parent::postProcess();
    }

    /**
     * Process save
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function processSave() {

        if (!$this->id_object) {
            $tmpAddrFormat = new AddressFormat();
        } else {
            $tmpAddrFormat = new AddressFormat($this->id_object);
        }

        $tmpAddrFormat->format = Tools::getValue('address_layout');

        if (!$tmpAddrFormat->checkFormatFields()) {
            $errorList = $tmpAddrFormat->getErrorList();

            foreach ($errorList as $numError => $error) {
                $this->errors[] = $error;
            }

        }

        if (strlen($tmpAddrFormat->format) <= 0) {
            $this->errors[] = $this->l('Address format invalid');
        }

        $country = parent::processSave();

        if (!count($this->errors) && $country instanceof Country) {

            if (is_null($tmpAddrFormat->id_country)) {
                $tmpAddrFormat->id_country = $country->id;
            }

            if (!$tmpAddrFormat->save()) {
                $this->errors[] = Tools::displayError('Invalid address layout ' . Db::getInstance()->getMsgError());
            }

        }

        return $country;
    }

    /**
     * Process status
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function processStatus() {

        parent::processStatus();

        /** @var Country $object */

        if (Validate::isLoadedObject($object = $this->loadObject()) && $object->active == 1) {
            return Country::addModuleRestrictions([], [['id_country' => $object->id]], []);
        }

        return false;
    }

    /**
     * Process bulk status selection
     *
     * @param bool $way
     *
     * @return bool|void
     *
     * @since 1.9.1.0
     */
    public function processBulkStatusSelection($way) {

        if (is_array($this->boxes) && !empty($this->boxes)) {
            $countriesIds = [];

            foreach ($this->boxes as $id) {
                $countriesIds[] = ['id_country' => $id];
            }

            if (count($countriesIds)) {
                Country::addModuleRestrictions([], $countriesIds, []);
            }

        }

        parent::processBulkStatusSelection($way);
    }

}
