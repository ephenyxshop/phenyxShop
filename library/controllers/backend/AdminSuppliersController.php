<?php

/**
 * @property Supplier $object
 */
class AdminSuppliersControllerCore extends AdminController {

    public $php_self = 'adminsuppliers';
    public $bootstrap = true;

    public function __construct() {

        $this->table = 'supplier';
        $this->className = 'Supplier';
        $this->publicName = $this->l('Suppliers');

        $this->fieldImageSettings = ['name' => 'logo', 'dir' => 'su'];

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_SUPPLIER_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_SUPPLIER_SCRIPT');
        }

        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_FIELDS', Tools::jsonEncode($this->getSupplierFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_FIELDS'), true);
        }

    }

    public function setAjaxMedia() {

        return $this->pushJS([
            __PS_BASE_URI__ . _PS_JS_DIR_ . 'tinymce/tinymce.min.js',
            _PS_JS_DIR_ . 'admin/tinymce.inc.js',
        ]);
    }

    public function generateParaGridScript($regenerate = false) {

        $this->paramExtraFontcion = ['

            function addNewSupplier() {
                $.ajax({
                    type: "GET",
                    url: AjaxLinkAdminSuppliers,
                    data: {
                        action: "addNewSupplier",
                        ajax: !0
                    },
                    async: !1,
                    dataType: "json",
                    success: function(data) {
                        if (data.success) {
                            $("#editSupplier").html(data.html);
                            $("#paragrid_AdminSuppliers").slideUp();
                            $("body").addClass("edit");
                            $("#editSupplier").slideDown();
                        }
                    }
                })
            }

            function editSupplier(idSupplier) {
                $.ajax({
                    type: "GET",
                    url: AjaxLinkAdminSuppliers,
                    data: {
                        action: "editSupplier",
                        idSupplier: idSupplier,
                        ajax: !0
                    },
                    async: !1,
                    dataType: "json",
                    success: function(data) {
                        if (data.success) {
                            $("#editSupplier").html(data.html);
                            $("#paragrid_AdminSuppliers").slideUp();
                            $("body").addClass("edit");
                            $("#editSupplier").slideDown();
                        }
                    }
                })

            }



            '];

        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $this->paramComplete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 100,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'icon'     => '\'ui-icon-disk\'',
                    'label'    => '\'' . $this->l('Ajouter un Fournisseur') . '\'',
                    'cls'      => '\'buttonCleanProductPosition changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only pull-right\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
                ],

            ],
        ];

        $this->paramTitle = '\'' . $this->l('Liste des fournisseurs') . '\'';
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
                            name: \'' . $this->l('Editer ou modifier le fournisseur  ') . '\'+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_supplier)
                            }
                        },
                        "sep1": "---------",

                        "delete": {
                            name: \'' . $this->l('Supprimer le fournisseur ') . '\'+rowData.name,
                            icon: "delete",
                            visible: function(key, opt){
                                return !rowData.hasSubmenu;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteSupplier(rowData.id_supplier);


                            }
                        }
                    },
                };
            }',
            ]];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getSupplierRequest() {

        $suppliers = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.* ')
                ->from('supplier', 'a')
                ->leftJoin('supplier_lang', 'cl', 'cl.`id_supplier` = a.`id_supplier` AND cl.`id_lang` = ' . (int) $this->context->language->id)
        );

        foreach ($suppliers as &$supplier) {

            if ($supplier['active'] == 1) {
                $supplier['active'] = '<div class="p-active"></div>';
            } else {
                $supplier['active'] = '<div class="p-inactive"></div>';
            }

            $supplier['city'] = '';
            $supplier['country'] = "";

            $id_address = Address::getAddressIdBySupplierId($supplier['id_supplier']);

            if (Validate::isUnsignedId($id_address)) {
                $address = New Address($id_address);
                $supplier['city'] = $address->city;
                $supplier['country'] = Country::getNameById($this->context->language->id, $address->id_country);

            }

        }

        return $suppliers;

    }

    public function ajaxProcessgetSupplierRequest() {

        die(Tools::jsonEncode($this->getSupplierRequest()));

    }

    public function getSupplierFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 50,
                'dataIndx'   => 'id_supplier',
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
                'title'    => $this->l('Email address'),
                'width'    => 200,
                'dataIndx' => 'email',
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

        return $fields_list;

    }

    public function ajaxProcessgetSupplierFields() {

        die(EmployeeConfiguration::get('EXPERT_SUPPLIER_FIELDS'));
    }

    public function ajaxProcessAddNewSupplier() {

        $_GET['addsupplier'] = "";
        $_GET['id_supplier'] = "";

        $html = $this->renderForm();
        $result = [
            'success' => true,
            'html'    => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessEditSupplier() {

        $idSupplier = Tools::getValue('idSupplier');
        $this->identifier = 'id_supplier';
        $_GET['id_supplier'] = $idSupplier;
        $_GET['updatesupplier'] = "";

        $html = $this->renderForm();
        $result = [
            'success' => true,
            'html'    => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function renderForm() {

        // loads current warehouse

        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $image = _PS_SUPP_IMG_DIR_ . $obj->id . '.jpg';
        $imageUrl = ImageManager::thumbnail($image, $this->table . '_' . (int) $obj->id . '.' . $this->imageType, 350, $this->imageType, true, true);
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

        $tmpAddr = new Address();
        $res = $tmpAddr->getFieldsRequiredDatabase();
        $requiredFields = [];

        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Suppliers'),
                'icon'  => 'icon-truck',
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
                    'name' => 'id_address',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Code Fournisseur'),
                    'name'     => 'supplier_code',
                    'required' => true,
                    'col'      => 4,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'col'      => 4,
                    'hint'     => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Adresse email'),
                    'name'     => 'email',
                    'required' => true,
                    'col'      => 4,
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('SIRET'),
                    'name'  => 'siret',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('APE'),
                    'name'  => 'ape',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('N° TVA Intracommunautaire'),
                    'name'  => 'vat_number',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Compte Comptable'),
                    'name'     => 'stdAccount',
                    'required' => true,
                    'col'      => 4,
                ],
                [
                    'type'      => in_array('company', $requiredFields) ? 'text' : 'hidden',
                    'label'     => $this->l('Company'),
                    'name'      => 'company',
                    'display'   => in_array('company', $requiredFields),
                    'required'  => in_array('company', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Company name for this supplier'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'lang'         => true,
                    'hint'         => [
                        $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                        $this->l('Will appear in the list of suppliers.'),
                    ],
                    'autoload_rte' => 'rte', //Enable TinyMCE editor for short description
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Phone'),
                    'name'      => 'phone',
                    'required'  => in_array('phone', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Phone number for this supplier'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Mobile phone'),
                    'name'      => 'phone_mobile',
                    'required'  => in_array('phone_mobile', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Mobile phone number for this supplier.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address'),
                    'name'      => 'address',
                    'maxlength' => 128,
                    'col'       => 6,
                    'required'  => true,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address') . ' (2)',
                    'name'      => 'address2',
                    'required'  => in_array('address2', $requiredFields),
                    'col'       => 6,
                    'maxlength' => 128,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Zip/postal code'),
                    'name'      => 'postcode',
                    'required'  => in_array('postcode', $requiredFields),
                    'maxlength' => 12,
                    'col'       => 2,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('City'),
                    'name'      => 'city',
                    'maxlength' => 32,
                    'col'       => 4,
                    'required'  => true,
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'col'           => 4,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id, false),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('State'),
                    'name'    => 'id_state',
                    'col'     => 4,
                    'options' => [
                        'id'    => 'id_state',
                        'query' => [],
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'          => 'image',
                    'label'         => $this->l('Logo'),
                    'name'          => 'logo',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'hint'          => $this->l('Upload a supplier logo from your computer.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta title'),
                    'name'  => 'meta_title',
                    'lang'  => true,
                    'col'   => 4,
                    'hint'  => $this->l('Forbidden characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta description'),
                    'name'  => 'meta_description',
                    'lang'  => true,
                    'col'   => 6,
                    'hint'  => $this->l('Forbidden characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'col'   => 6,
                    'hint'  => [
                        $this->l('To add "tags" click in the field, write something and then press "Enter".'),
                        $this->l('Forbidden characters:') . ' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Enable'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
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
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),

            ],
        ];

        // loads current address for this supplier - if possible
        $address = null;

        if (isset($obj->id)) {
            $idAddress = Address::getAddressIdBySupplierId($obj->id);

            if ($idAddress > 0) {
                $address = new Address((int) $idAddress);
            }

        }

        // force specific fields values (address)

        if ($address != null) {
            $this->fields_value = [
                'id_address'   => $address->id,
                'phone'        => $address->phone,
                'phone_mobile' => $address->phone_mobile,
                'address'      => $address->address1,
                'address2'     => $address->address2,
                'postcode'     => $address->postcode,
                'city'         => $address->city,
                'id_country'   => $address->id_country,
                'id_state'     => $address->id_state,
            ];
        } else {
            $this->fields_value = [
                'id_address' => 0,
                'id_country' => Configuration::get('PS_COUNTRY_DEFAULT'),
            ];
        }

        $this->fields_value['ajax'] = 1;
        $this->fields_value['id_stdaccount'] = $obj->id_stdaccount;

        if ($obj->id > 0) {
            $this->fields_value['action'] = 'updateSupplier';
            $this->editObject = 'Mettre à jour un forunisseur';
        } else {
            $this->fields_value['action'] = 'addSupplier';
            $this->editObject = 'Ajouter un fournisseur';
        }

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        return parent::renderForm();
    }

    public function ajaxProcessaddSupplier() {

        $supplier = new Supplier();

        foreach ($_POST as $key => $value) {

            if (property_exists($supplier, $key) && $key != 'id_supplier') {

                $supplier->{$key}
                = $value;
            }

        }

        try {
            $result = $supplier->add();
        } catch (Exception $ex) {
            $file = fopen("testAddCustomer.txt", "w");
            fwrite($file, $ex->getMessage());
        }

        if ($result && Tools::getValue('address')) {

            $address = new Address();
            $address->id_supplier = $supplier->id;
            $address->id_country = Tools::getValue('id_country');
            $address->alias = 'Facturation';
            $address->company = Tools::getValue('company');
            $address->address1 = Tools::getValue('address1');
            $address->address2 = Tools::getValue('address2');
            $address->postcode = Tools::getValue('postcode');
            $address->city = Tools::getValue('city');
            $address->phone = Tools::getValue('phone');
            $address->phone_mobile = Tools::getValue('phone_mobile');

            $mobile = str_replace(' ', '', $address->phone_mobile);

            if (strlen($mobile) == 10) {
                $mobile = '+33' . substr($mobile, 1);
                $address->phone_mobile = $mobile;
            }

            try {
                $result = $address->add();
            } catch (Exception $ex) {
                $file = fopen("testAddSupplierAddress.txt", "w");
                fwrite($file, $ex->getMessage());
            }

        }

        if ($result) {
            $return = [
                'success' => true,
                'message' => 'Le Fournisseur a été ajouté avec succès à la base de donnée.',
            ];
        } else {
            $return = [
                'success' => false,
                'message' => 'Un truc a un peu merdé.',
            ];
        }

        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessupdateSupplier() {

        $idSupplier = Tools::getValue('id_supplier');
        $supplier = new Supplier($idSupplier);

        foreach ($_POST as $key => $value) {

            if (property_exists($supplier, $key) && $key != 'id_supplier') {

                $supplier->{$key}
                = $value;
            }

        }

        try {
            $result = $supplier->update();
        } catch (Exception $ex) {
            $file = fopen("testAddCustomer.txt", "w");
            fwrite($file, $ex->getMessage());
        }

        if ($result && Tools::getValue('address')) {

            if ($idAddress = Tools::getValue('id_address')) {
                $address = new Address($idAddress);
            } else {
                $address = new Address();
            }

            $address->id_supplier = $supplier->id;
            $address->id_country = Tools::getValue('id_country');
            $address->alias = 'Facturation';
            $address->company = Tools::getValue('name');
            $address->address1 = Tools::getValue('address1');
            $address->address2 = Tools::getValue('address2');
            $address->postcode = Tools::getValue('postcode');
            $address->city = Tools::getValue('city');
            $address->phone = Tools::getValue('phone');
            $address->phone_mobile = Tools::getValue('phone_mobile');

            $mobile = str_replace(' ', '', $address->phone_mobile);

            if (strlen($mobile) == 10) {
                $mobile = '+33' . substr($mobile, 1);
                $address->phone_mobile = $mobile;
            }

            if ($idAddress > 0) {
                $result = $address->update();
            } else {
                $result = $address->add();
            }

        }

        if ($result) {
            $return = [
                'success' => true,
                'message' => 'Le Fournisseur a été ajouté avec succès à la base de donnée.',
            ];
        } else {
            $return = [
                'success' => false,
                'message' => 'Un truc a un peu merdé.',
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function generateEditScript() {

        return '<script type="text/javascript">' . PHP_EOL . '
                    $(document).ready(function(){
                        $("#supplier_tabs_' . $this->identifier_value . '").tabs({
                            show: { effect: "blind", duration: 800 }
                        });' . PHP_EOL . '
                        $("#id_tax-mode_' . $this->identifier_value . '").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        $("#id_currency_' . $this->identifier_value . '").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        $("#id_payment_mode_' . $this->identifier_value . '").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        $("#selectCountry").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                            "change": function(event, ui) {
                                var iso = $(ui.item.element).attr("id");
                                var idCountry = ui.item.value;
                                getBankFormat(iso, idCountry);

                            }
                        });' . PHP_EOL . '
                        $("#address_country_' . $this->identifier_value . '").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                            "change": function(event, ui) {
                                ajaxNexSupplierStates(ui.item.value);
                                $("#id_country_' . $this->identifier_value . '").val(ui.item.value);
                                checkTaxZone(ui.item.value);
                            }
                        });' . PHP_EOL . '
                        var idCountry = $("#id_country_' . $this->identifier_value . '").val();
                        ajaxSupplierStates(idCountry);

                    });' . PHP_EOL . '
                    function ajaxSupplierStates(id_country) {
                        var id_state_selected = $("#id_state_' . $this->identifier_value . '").val();
                        console.log(id_country);
                        console.log(id_state_selected);
                        $.ajax({
                            url: AjaxLinkAdminStates,
                            cache: false,
                            data: {
                                action: \'states\',
                                id_country: id_country,
                                id_state: id_state_selected,
                                ajax: true
                            },
                            success: function(html) {
                                if (html == \'false\') {
                                    $("#contains_states").slideUp();
                                    $("#address_state_' . $this->identifier_value . ' option[value=0]").attr("selected", "selected");
                                } else {
                                    $("#address_state_' . $this->identifier_value . '").html(html);
                                    $("#contains_states").slideDown();
                                    $("#address_state_' . $this->identifier_value . '").selectmenu({
                                        width: 200,
                                        classes: {
                                            "ui-selectmenu-menu": "scrollable"
                                        },
                                        "change": function(event, ui) {
                                            $("#id_state_' . $this->identifier_value . '").val(ui.item.value);
                                        }
                                    });
                                    $("#address_state_' . $this->identifier_value . '").val(id_state_selected);
                                    $("#address_state_' . $this->identifier_value . '").selectmenu("refresh");
                                }
                            }
                        });
                    }' . PHP_EOL . '
                    function checkTaxZone(id_country) {
            $.ajax({
                url: AjaxLinkAdminSuppliers,
                data: {
                    action: \'checkTaxZone\',
                    id_country: id_country,
                    ajax: true
                },
                success: function(data) {
                    $("#id_tax-mode_' . $this->identifier_value . '").val(data);
                    $("#id_tax-mode_' . $this->identifier_value . '").selectmenu("refresh");
                }
            });
        }' . PHP_EOL . '
                    function getSupplierCurrency(id_country) {
            $.ajax({
                url: AjaxLinkAdminSuppliers,
                data: {
                    action: \'getSupplierCurrency\',
                    id_country: id_country,
                    ajax: true
                },
                success: function(data) {
                    console.log(data);
                    $("#id_currency_' . $this->identifier_value . '").val(data);
                    $("#id_currency_' . $this->identifier_value . '").selectmenu("refresh");
                }
            });
        }' . PHP_EOL . '

                </script>' . PHP_EOL;

    }

    public function generateAddScript() {

        $method = Tools::jsonEncode(Supplier::getSupplierAccountMethod());
        $script = '<script type="text/javascript">' . PHP_EOL . '
                    var action, target, accounttype, accountValue, objewSupplierAccount;
                    var method = ' . $method . ';
                    $(document).ready(function(){


                        if(method.method == 1) {
                            $("#bookForm").slideUp();
                            action = \'stNewSupplieraccount\';
                            target = \'stdAccount_new\';
                            $("#subCreateType").val("Supplier");
                            objewSupplierAccount = generateObjStdAccount("Supplier", stNewSupplieraccount.name);

                            if(method.value == \'401+SUPPLIER_CODE\') {
                                accounttype = 1;
                            } else {
                                accounttype = 2;
                            }
                        } else {
                            $("#stNewSupplieraccount").html(method.account+\'<i class="icon icon-bars" aria-hidden="true"></i>\');
                            $("#stdAccount_new").val(method.value);
                            accounttype =0;
                        }
                        $("#supplier_tabs_' . $this->identifier_value . '").tabs({
                            show: { effect: "blind", duration: 800 }
                        });' . PHP_EOL . '
                        $("#id_tax-mode_new").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        $("#id_currency_new").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        var idCountry = $("#id_country_new").val();' . PHP_EOL . '

                        checkTaxZone(idCountry);' . PHP_EOL . '
                        getSupplierCurrency(idCountry);' . PHP_EOL . '
                        $("#id_payment_mode_new").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                        });' . PHP_EOL . '
                        $("#selectCountry").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                            "change": function(event, ui) {
                                var iso = $(ui.item.element).attr("id");
                                var idCountry = ui.item.value;
                                getBankFormat(iso, idCountry);

                            }
                        });' . PHP_EOL . '
                        $("#address_country_new").selectmenu({
                            width: 200,
                            classes: {
                                "ui-selectmenu-menu": "scrollable"
                            },
                            "change": function(event, ui) {
                                ajaxNexSupplierStates(ui.item.value);
                                $("#id_country_new").val(ui.item.value);
                                checkTaxZone(ui.item.value);
                            }
                        });' . PHP_EOL;

        $script .= '$(document).on("click", "#stNewSupplieraccount", function() {
                        $.fancybox.open($("#newSupplierPopup"), {
                                touch: false,
                                clickContent: false,
                                clickSlide:false,
                                afterLoad: function( instance, current ) {
                                    gridAccount = pq.grid("#gridnewSupplierStdaccount", objewSupplierAccount);
                                    selgridAccount = gridAccount.SelectRow();
                                    $("#account_sub").val(accountValue);
                                    $("#createSupplierAccount").trigger("click");
                                },
                                afterClose:function( instance, current ) {
                                    gridAccount.destroy();
                                }
                            });
                        });' . PHP_EOL;

        $script .= '});' . PHP_EOL . '
        function ajaxNexSupplierStates(id_country) {
                        var id_state_selected = $("#id_state").val();
                        $.ajax({
                            url: AjaxLinkAdminStates,
                            cache: false,
                            data: {
                                action: \'states\',
                                id_country: id_country,
                                id_state: id_state_selected,
                                ajax: true
                            },
                            success: function(html) {
                                if (html == \'false\') {
                                    $("#contains_states").slideUp();
                                    $("#id_state option[value=0]").attr("selected", "selected");
                                } else {
                                    $("#id_state").html(html);
                                    $("#contains_states").slideDown();
                                    $("#id_state option[value=\' + id_state_selected + \']").attr("selected", "selected");
                                    $("#id_state_new").selectmenu({
                                        width: 200,
                                        classes: {
                                            "ui-selectmenu-menu": "scrollable"
                                        },
                                    });
                                }
                            }
                        });
                    }' . PHP_EOL . '
        function checkTaxZone(id_country) {
            $.ajax({
                url: AjaxLinkAdminSuppliers,
                data: {
                    action: \'checkTaxZone\',
                    id_country: id_country,
                    ajax: true
                },
                success: function(data) {
                    $("#id_tax-mode_new").val(data);
                    $("#id_tax-mode_new").selectmenu("refresh");
                }
            });
        }' . PHP_EOL . '
        function getSupplierCurrency(id_country) {
            $.ajax({
                url: AjaxLinkAdminSuppliers,
                data: {
                    action: \'getSupplierCurrency\',
                    id_country: id_country,
                    ajax: true
                },
                success: function(data) {
                    console.log(data);
                    $("#id_currency_new").val(data);
                    $("#id_currency_new").selectmenu("refresh");
                }
            });
        }' . PHP_EOL . '
        function processUpper() {
                        var x=document.getElementById("supplier_code_new");
                        x.value= x.value.replace(/\s/g, \'\').toUpperCase();
                        $("#code_bank").val(\'BQ\'+x.value);
                        if(accounttype == 1) {
                            var supCode = x.value;
                            $("#bookForm").slideDown();
                            accountValue = \'401\'+supCode;
                            $("#stNewSupplieraccount").html(accountValue+\'<i class="icon icon-bars" aria-hidden="true"></i>\');
                            $("#stNewSupplieraccount").trigger("click");
                        }
                    }' . PHP_EOL . '
        function processNameAccount() {

                        if(accounttype == 2) {
                            var x=document.getElementById("name_new");
                            var supCode = x.value.replace(/\s/g, \'\').toUpperCase();
                            $("#bookForm").slideDown();
                            accountValue = \'401\'+supCode;
                            $("#stNewSupplieraccount").html(accountValue+\'<i class="icon icon-bars" aria-hidden="true"></i>\');
                            $("#stNewSupplieraccount").trigger("click");
                        }
                    }' . PHP_EOL . '

        </script>' . PHP_EOL;

        return $script;

    }

    public function ajaxProcessCheckTaxZone() {

        $idCountry = Tools::getValue('id_country');
        $country = new Country($idCountry);
        $zone = new Zone($country->id_zone);
        die($zone->id_tax_mode);
    }

    public function ajaxProcessGetSupplierCurrency() {

        $idCountry = Tools::getValue('id_country');
        $country = new Country($idCountry);
        die($country->id_currency);
    }

    public static function getSupplierAccountMethod() {

        $type = Configuration::get('EPH_SUPPLIER_AFFECTATION');

        switch ($type) {
        case 0:
            return [
                'method' => 0,
                'value'  => Configuration::get('EPH_SUPPLIER_DEFAULT_ACCOUNT'),
            ];
            break;
        case 1:
            return [
                'method' => 1,
                'value'  => Configuration::get('EPH_CUSTOMER_AFFECTATION_1_TYPE'),
            ];
            break;
        case 2:
            return [
                'method' => 2,
                'value'  => Configuration::get('EPH_SUPPLIER_COMMON_ACCOUNT'),
            ];
            break;
        }

    }

}
