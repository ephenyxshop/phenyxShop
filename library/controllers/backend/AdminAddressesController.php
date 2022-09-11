<?php

/**
 * Class AdminAddressesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminAddressesControllerCore extends AdminController {

	public $php_self = 'adminaddresses';
	/** @var array countries list */
	protected $countries_array = [];

	public $countryAddressSelector;

	/**
	 * AdminAddressesControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'address';
		$this->className = 'Address';
		$this->publicName = $this->l('Customer Address');
		$this->lang = false;
		$this->identifier = 'id_address';
		$this->controller_name = 'AdminAdresses';
		$this->context = Context::getContext();

		parent::__construct();
		EmployeeConfiguration::updateValue('EXPERT_ADDRESS_SCRIPT', $this->generateParaGridScript(true));
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_ADDRESS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_ADDRESS_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_ADDRESS_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_ADDRESS_FIELDS', Tools::jsonEncode($this->getAddressFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ADDRESS_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_ADDRESS_FIELDS', Tools::jsonEncode($this->getAddressFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ADDRESS_FIELDS'), true);
		}

	}

	public function generateParaGridScript($regenerate = false) {

		$this->countryAddressSelector = '<div class="pq-theme"><select id="countryAddressSelect"><option value="">' . $this->l('--Select--') . '</option>';

		foreach (Country::getCountries($this->context->language->id, true) as $country) {
			$this->countryAddressSelector .= '<option value="' . $country['id_country'] . '">' . $country['name'] . '</option>';
		}

		$this->countryAddressSelector .= '</select></div>';

		$gridExtraFunction = ['function buildAddressFilter(){
           	var countryconteneur = $(\'#countryAddressSelector\').parent().parent();
			$(countryconteneur).empty();
			$(countryconteneur).append(\'' . $this->countryAddressSelector . '\');
			$(\'#countryAddressSelect\' ).selectmenu({
        		"change": function(event, ui) {
					grid' . $this->className . '.filter({
    					mode: \'AND\',
    					rules: [
        					{ dataIndx:\'id_country\', condition: \'equal\', value: ui.item.value}
    					]
        			});
    			}
			});

        	}'];

		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);
		$paragrid->height = 700;
		$paragrid->showNumberCell = 0;
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
		buildAddressFilter();
        }';

		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Ajouter une nouvelle adresse') . '\'',
					'cls'      => '\'buttonCleanProductPosition changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only pull-right\'',
					'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
				],

				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Gérer les champs affiché') . '\'',
					'cls'   => '\'showCategory changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'  => '\'id="page-header-desc-address-fields_edit"\'',
				],
			],
		];
		$paragrid->selectionModelType = 'row';
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
                selected = selgridAddress.getSelection().length;
                var dataLenght = gridAddress.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                        "add": {
                            name: \'' . $this->l('Ajouter une nouvelle adresse') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addAjaxObject("' . $this->controller_name . '");
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Visualiser ou modifier : ') . '\'' . '+rowData.alias,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                //editAddress(rowData.id_address);
								editAjaxObject("' . $this->controller_name . '", rowData.id_address)
                            }
                        },

                        "sep1": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer :') . '\'' . '+rowData.alias,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteAddress(rowData.id_address, rowIndex);
                            }
                        },

                    },
                };
            }',
			]];
		$paragrid->gridExtraFunction = $gridExtraFunction;

		$option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();

		$this->paragridScript = $script;
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';

	}

	public function getAddressRequest() {

		$addresses = Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.`id_address`, a.`alias`, a.`company`, a.`firstname` AS `firstname`, a.`lastname` AS `lastname`, a.`address1`, a.`postcode`, a.`city`,a.`id_country` , c.`customer_code`, cl.`name` as `country`')
				->from('address', 'a')
				->leftJoin('country_lang', 'cl', 'cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . (int) $this->context->language->id)
				->leftJoin('customer', 'c', 'a.`id_customer` = c.`id_customer`')
				->where('a.`id_customer` != 0 AND a.`deleted` = 0 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c'))
				->orderBy('a.`id_address` ASC')
		);

		return $addresses;

	}

	public function ajaxProcessgetAddressRequest() {

		die(Tools::jsonEncode($this->getAddressRequest()));

	}

	public function getAddressFields() {

		return [
			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'dataIndx'   => 'id_address',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'filter'     => [
					'crules' => [['condition' => "begin"]],
				],
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Alias'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'alias',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "begin"]],

				],
			],
			[
				'title'    => $this->l('Customer Code'),
				'width'    => 150,
				'dataIndx' => 'customer_code',
				'dataType' => 'string',
				'align'    => 'left',
				'halign'   => 'HORIZONTAL_LEFT',
				'editable' => false,
				'hidden'   => false,
				'filter'   => [

					'crules' => [['condition' => "begin"]],
				],

			],
			[
				'title'    => $this->l('Company'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'company',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "begin"]],

				],
			],

			[
				'title'    => $this->l('First Name'),
				'width'    => 200,
				'dataIndx' => 'firstname',
				'dataType' => 'string',
				'editable' => false,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'    => $this->l('Last Name'),
				'width'    => 200,
				'dataIndx' => 'lastname',
				'dataType' => 'string',
				'editable' => false,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
				'hidden'   => false,
			],
			[
				'title'    => $this->l('Address'),
				'width'    => 200,
				'dataIndx' => 'address1',
				'dataType' => 'string',
				'editable' => false,
			],
			[
				'title'    => $this->l('Address (follow)'),
				'width'    => 200,
				'dataIndx' => 'address2',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
			],

			[
				'title'    => $this->l('Post Code'),
				'width'    => 150,
				'exWidth'  => 20,
				'dataIndx' => 'postcode',
				'align'    => 'left',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'    => $this->l('City'),
				'width'    => 150,
				'exWidth'  => 30,
				'dataIndx' => 'city',
				'halign'   => 'HORIZONTAL_LEFT',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'id_country',
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
				'title'    => $this->l('Country'),
				'width'    => 150,
				'exWidth'  => 30,
				'dataIndx' => 'country',
				'halign'   => 'HORIZONTAL_LEFT',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => false,
				'filter'   => [
					'attr'   => "id=\"countryAddressSelector\", placeholder=" . $this->l('--Select--') . " readonly",
					'crules' => [['condition' => "equal"]],
				],
			],
		];

	}

	public function ajaxProcessgetAddressFields() {

		die(EmployeeConfiguration::get('EXPERT_ADDRESS_FIELDS'));
	}

	public function manageFieldsVisibility($fields) {

		return parent::manageFieldsVisibility($fields);
	}

	public function ajaxProcessUpdateVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ADDRESS_FIELDS'), true);
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
		EmployeeConfiguration::updateValue('EXPERT_ADDRESS_FIELDS', $headerFields);
		$response = [
			'headerFields' => $headerFields,
		];

		die($headerFields);
	}

	/**
	 * @return string
	 *
	 * @since 1.8.1.0
	 */
	public function renderForm() {

		if (!$this->loadObject(true)) {
			return '';
		}

		$this->displayGrid = false;
		$this->fields_form = [
			'legend' => [
				'title' => $this->l('Addresses'),
				'icon'  => 'icon-envelope-alt',
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
					'type'     => 'text_customer',
					'label'    => $this->l('Customer'),
					'name'     => 'id_customer',
					'required' => false,
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Identification Number'),
					'name'     => 'dni',
					'required' => false,
					'col'      => '4',
					'hint'     => $this->l('DNI / NIF / NIE'),
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Address alias'),
					'name'     => 'alias',
					'required' => true,
					'col'      => '4',
					'hint'     => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
				],
				[
					'type'     => 'textarea',
					'label'    => $this->l('Other'),
					'name'     => 'other',
					'required' => false,
					'cols'     => 15,
					'rows'     => 3,
					'hint'     => $this->l('Forbidden characters:') . ' &lt;&gt;;=#{}',
				],
				[
					'type' => 'hidden',
					'name' => 'id_order',
				],
				[
					'type' => 'hidden',
					'name' => 'address_type',
				],
				[
					'type' => 'hidden',
					'name' => 'back',
				],
			],
			'submit' => [
				'title' => $this->l('Save'),
			],
		];

		$this->fields_value['address_type'] = (int) Tools::getValue('address_type', 1);

		$idCustomer = (int) Tools::getValue('id_customer');

		if (!$idCustomer && Validate::isLoadedObject($this->object)) {
			$idCustomer = $this->object->id_customer;
		}

		if ($idCustomer) {
			$customer = new Customer((int) $idCustomer);
			$token_customer = Tools::getAdminToken('AdminCustomers' . (int) (EmployeeMenu::getIdFromClassName('AdminCustomers')) . (int) $this->context->employee->id);
		}

		$this->fields_value['ajax'] = 1;

		if ($this->object->id > 0) {
			$this->fields_value['action'] = 'updateAddress';
			$this->editObject = 'Edition d‘une adresse';
		} else {
			$this->fields_value['action'] = 'addAddress';
			$this->editObject = 'Ajouter une nouvelle adresse';
		}

		$this->tpl_form_vars = [
			'customer'      => isset($customer) ? $customer : null,
			'tokenCustomer' => isset($token_customer) ? $token_customer : null,
			'back_url'      => urldecode(Tools::getValue('back')),
		];

		// Order address fields depending on country format
		$addressesFields = $this->processAddressFormat();
		// we use  delivery address
		$addressesFields = $addressesFields['dlv_all_fields'];

		// get required field
		$requiredFields = AddressFormat::getFieldsRequired();

		// Merge with field required
		$addressesFields = array_unique(array_merge($addressesFields, $requiredFields));

		$tempFields = [];

		foreach ($addressesFields as $addrFieldItem) {

			if ($addrFieldItem == 'company') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Company'),
					'name'     => 'company',
					'required' => in_array('company', $requiredFields),
					'col'      => '4',
					'hint'     => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
				];
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('VAT number'),
					'col'      => '2',
					'name'     => 'vat_number',
					'required' => in_array('vat_number', $requiredFields),
				];
			} else
			if ($addrFieldItem == 'lastname') {

				if (isset($customer) &&
					!Tools::isSubmit('submit' . strtoupper($this->table)) &&
					Validate::isLoadedObject($customer) &&
					!Validate::isLoadedObject($this->object)
				) {
					$defaultValue = $customer->lastname;
				} else {
					$defaultValue = '';
				}

				$tempFields[] = [
					'type'          => 'text',
					'label'         => $this->l('Last Name'),
					'name'          => 'lastname',
					'required'      => true,
					'col'           => '4',
					'hint'          => $this->l('Invalid characters:') . ' 0-9!&amp;lt;&amp;gt;,;?=+()@#"�{}_$%:',
					'default_value' => $defaultValue,
				];
			} else
			if ($addrFieldItem == 'firstname') {

				if (isset($customer) &&
					!Tools::isSubmit('submit' . strtoupper($this->table)) &&
					Validate::isLoadedObject($customer) &&
					!Validate::isLoadedObject($this->object)
				) {
					$defaultValue = $customer->firstname;
				} else {
					$defaultValue = '';
				}

				$tempFields[] = [
					'type'          => 'text',
					'label'         => $this->l('First Name'),
					'name'          => 'firstname',
					'required'      => true,
					'col'           => '4',
					'hint'          => $this->l('Invalid characters:') . ' 0-9!&amp;lt;&amp;gt;,;?=+()@#"�{}_$%:',
					'default_value' => $defaultValue,
				];
			} else
			if ($addrFieldItem == 'address1') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Address'),
					'name'     => 'address1',
					'col'      => '6',
					'required' => true,
				];
			} else
			if ($addrFieldItem == 'address2') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Address') . ' (2)',
					'name'     => 'address2',
					'col'      => '6',
					'required' => in_array('address2', $requiredFields),
				];
			} else
			if ($addrFieldItem == 'postcode') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Zip/Postal Code'),
					'name'     => 'postcode',
					'col'      => '2',
					'required' => true,
				];
			} else
			if ($addrFieldItem == 'city') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('City'),
					'name'     => 'city',
					'col'      => '4',
					'required' => true,
				];
			} else
			if ($addrFieldItem == 'country' || $addrFieldItem == 'Country:name') {
				$tempFields[] = [
					'type'          => 'select',
					'label'         => $this->l('Country'),
					'name'          => 'id_country',
					'required'      => in_array('Country:name', $requiredFields) || in_array('country', $requiredFields),
					'col'           => '4',
					'default_value' => (int) $this->context->country->id,
					'options'       => [
						'query' => Country::getCountries($this->context->language->id),
						'id'    => 'id_country',
						'name'  => 'name',
					],
				];
				$tempFields[] = [
					'type'     => 'select',
					'label'    => $this->l('State'),
					'name'     => 'id_state',
					'required' => false,
					'col'      => '4',
					'options'  => [
						'query' => [],
						'id'    => 'id_state',
						'name'  => 'name',
					],
				];
			} else
			if ($addrFieldItem == 'phone') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Home phone'),
					'name'     => 'phone',
					'required' => in_array('phone', $requiredFields) || Configuration::get('EPH_ONE_PHONE_AT_LEAST'),
					'col'      => '4',
					'hint'     => Configuration::get('EPH_ONE_PHONE_AT_LEAST') ? sprintf($this->l('You must register at least one phone number.')) : '',
				];
			} else
			if ($addrFieldItem == 'phone_mobile') {
				$tempFields[] = [
					'type'     => 'text',
					'label'    => $this->l('Mobile phone'),
					'name'     => 'phone_mobile',
					'required' => in_array('phone_mobile', $requiredFields) || Configuration::get('EPH_ONE_PHONE_AT_LEAST'),
					'col'      => '4',
					'hint'     => Configuration::get('EPH_ONE_PHONE_AT_LEAST') ? sprintf($this->l('You must register at least one phone number.')) : '',
				];
			}

		}

		// merge address format with the rest of the form
		array_splice($this->fields_form['input'], 3, 0, $tempFields);

		return parent::renderForm();
	}

	public function ajaxProcessUpdateAddress() {

		$idAddress = Tools::getValue('id_address');

		$address = new Address($idAddress);

		foreach ($_POST as $key => $value) {

			if (property_exists($address, $key) && $key != 'id_address') {
				$address->{$key}

				= $value;
			}

		}

		$result = $address->update();

		$return = [
			'success' => true,
			'message' => $this->l('L‘adresse a mis à jour avec succès'),
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddAddress() {

		$customer = new Customer();
		$customer->getByEmail(Tools::getValue('email'), null, false);

		$address = new Address();

		foreach ($_POST as $key => $value) {

			if (property_exists($address, $key) && $key != 'id_address') {
				$address->{$key}

				= $value;
			}

		}

		$address->id_customer = $customer->id;

		$result = $address->add();

		$return = [
			'success' => true,
			'message' => $this->l('L‘adresse a été ajoutée avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	/**
	 * Get Address formats used by the country where the address id retrieved from POST/GET is.
	 *
	 * @return array address formats
	 */
	protected function processAddressFormat() {

		$tmpAddr = new Address((int) Tools::getValue('id_address'));

		$selectedCountry = ($tmpAddr && $tmpAddr->id_country) ? $tmpAddr->id_country : (int) Configuration::get('EPH_COUNTRY_DEFAULT');

		$invAdrFields = AddressFormat::getOrderedAddressFields($selectedCountry, false, true);
		$dlvAdrFields = AddressFormat::getOrderedAddressFields($selectedCountry, false, true);

		$invAllFields = [];
		$dlvAllFields = [];

		$out = [];

		foreach (['inv', 'dlv'] as $adrType) {

			foreach (${$adrType . 'AdrFields'} as $fieldsLine) {

				foreach (explode(' ', $fieldsLine) as $fieldItem) {
					${$adrType . 'AllFields'}

					[] = trim($fieldItem);
				}

			}

			$out[$adrType . '_adr_fields'] = ${$adrType . 'AdrFields'};
			$out[$adrType . '_all_fields'] = ${$adrType . 'AllFields'};
		}

		return $out;
	}

	/**
	 * @return bool|false|ObjectModel|null
	 *
	 * @since 1.8.1.0
	 */
	public function processSave() {

		if (Tools::getValue('submitFormAjax')) {
			$this->redirect_after = false;
		}

		// Transform e-mail in id_customer for parent processing

		if (Validate::isEmail(Tools::getValue('email'))) {
			$customer = new Customer();
			$customer->getByEmail(Tools::getValue('email'), null, false);

			if (Validate::isLoadedObject($customer)) {
				$_POST['id_customer'] = $customer->id;
			} else {
				$this->errors[] = Tools::displayError('This email address is not registered.');
			}

		} else
		if ($idCustomer = Tools::getValue('id_customer')) {
			$customer = new Customer((int) $idCustomer);

			if (Validate::isLoadedObject($customer)) {
				$_POST['id_customer'] = $customer->id;
			} else {
				$this->errors[] = Tools::displayError('This customer ID is not recognized.');
			}

		} else {
			$this->errors[] = Tools::displayError('This email address is not valid. Please use an address like bob@example.com.');
		}

		if (Country::isNeedDniByCountryId(Tools::getValue('id_country')) && !Tools::getValue('dni')) {
			$this->errors[] = Tools::displayError('The identification number is incorrect or has already been used.');
		}

		/* If the selected country does not contain states */
		$idState = (int) Tools::getValue('id_state');
		$idCountry = (int) Tools::getValue('id_country');
		$country = new Country((int) $idCountry);

		if ($country && !(int) $country->contains_states && $idState) {
			$this->errors[] = Tools::displayError('You have selected a state for a country that does not contain states.');
		}

		/* If the selected country contains states, then a state have to be selected */

		if ((int) $country->contains_states && !$idState) {
			$this->errors[] = Tools::displayError('An address located in a country containing states must have a state selected.');
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

		if (Configuration::get('EPH_ONE_PHONE_AT_LEAST') && !Tools::getValue('phone') && !Tools::getValue('phone_mobile')) {
			$this->errors[] = Tools::displayError('You must register at least one phone number.');
		}

		/* If this address come from order's edition and is the same as the other one (invoice or delivery one)
        ** we delete its id_address to force the creation of a new one */

		if ((int) Tools::getValue('id_order')) {
			$this->_redirect = false;

			if (isset($_POST['address_type'])) {
				$_POST['id_address'] = '';
				$this->id_object = null;
			}

		}

		// Check the requires fields which are settings in the BO
		$address = new Address();
		$this->errors = array_merge($this->errors, $address->validateFieldsRequiredDatabase());

		$return = false;

		if (empty($this->errors)) {
			$return = parent::processSave();
		} else {
			// if we have errors, we stay on the form instead of going back to the list
			$this->display = 'edit';
		}

		/* Reassignation of the order's new (invoice or delivery) address */
		$addressType = (int) Tools::getValue('address_type') == 2 ? 'invoice' : 'delivery';

		if ($this->action == 'save' && ($idOrder = (int) Tools::getValue('id_order')) && !count($this->errors) && !empty($addressType)) {

			if (!Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'orders SET `id_address_' . bqSQL($addressType) . '` = ' . (int) $this->object->id . ' WHERE `id_order` = ' . (int) $idOrder)) {
				$this->errors[] = Tools::displayError('An error occurred while linking this address to its order.');
			} else {
				Tools::redirectAdmin(urldecode(Tools::getValue('back')) . '&conf=4');
			}

		}

		return $return;
	}

	/**
	 * @return false|ObjectModel
	 *
	 * @since 1.8.1.0
	 */
	public function processAdd() {

		if (Tools::getValue('submitFormAjax')) {
			$this->redirect_after = false;
		}

		return parent::processAdd();
	}

	/**
	 * Method called when an ajax request is made
	 *
	 * @see AdminController::postProcess()
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcess() {

		if (Tools::isSubmit('email')) {
			$email = pSQL(Tools::getValue('email'));
			$customer = Customer::searchByName($email);

			if (!empty($customer)) {
				$customer = $customer['0'];
				$this->ajaxDie(json_encode(['infos' => pSQL($customer['firstname']) . '_' . pSQL($customer['lastname']) . '_' . pSQL($customer['company'])]));
			}

		}

		die;
	}

	/**
	 * @return false|ObjectModel
	 *
	 * @since 1.8.1.0
	 */
	public function processDelete() {

		if (Validate::isLoadedObject($object = $this->loadObject())) {
			/** @var Address $object */

			if (!$object->isUsed()) {
				$this->deleted = false;
			}

		}

		$res = parent::processDelete();

		if ($back = Tools::getValue('back')) {
			$this->redirect_after = urldecode($back) . '&conf=1';
		}

		return $res;
	}

	/**
	 * Delete multiple items
	 *
	 * @return bool true if succcess
	 */
	protected function processBulkDelete() {

		if (is_array($this->boxes) && !empty($this->boxes)) {
			$deleted = false;

			foreach ($this->boxes as $id) {
				$toDelete = new Address((int) $id);

				if ($toDelete->isUsed()) {
					$deleted = true;
					break;
				}

			}

			$this->deleted = $deleted;
		}

		return parent::processBulkDelete();
	}

}
