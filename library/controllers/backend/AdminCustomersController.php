<?php

/**
 * Class AdminCustomersControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCustomersControllerCore extends AdminController {

	// @codingStandardsIgnoreStart

	public $php_self = 'admincustomers';
	protected static $meaning_status = [];
	protected $delete_mode;
	protected $_defaultOrderBy = 'date_add';
	protected $_defaultOrderWay = 'DESC';
	protected $can_add_customer = true;
	protected $tarifs_array = [];

	public $genderSelector;
	public $groupSelector;
	public $countrySelector;
	// @codingStandardsIgnoreEnd

	/**
	 * AdminCustomersControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->required_fields = ['newsletter', 'optin'];
		$this->table = 'customer';
		$this->className = 'Customer';
		$this->publicName = $this->l('Liste des Clients / Etudiants');
		$this->lang = false;
		$this->identifier = 'id_customer';
		$this->controller_name = 'AdminCustomers';
		$this->context = Context::getContext();

		$this->default_form_language = $this->context->language->id;
		//EmployeeConfiguration::updateValue('EXPERT_CUSTOMERS_FIELDS', Tools::jsonEncode($this->getCustomerFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERS_FIELDS', $this->context->employee->id), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_CUSTOMERS_FIELDS', Tools::jsonEncode($this->getCustomerFields()), $this->context->employee->id);
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERS_FIELDS', $this->context->employee->id), true);
		}

		//EmployeeConfiguration::updateValue('EXPERT_CUSTOMERS_SCRIPT', $this->generateParaGridScript(true), $this->context->employee->id);
		$this->paragridScript = $this->generateParaGridScript();

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_CUSTOMERS_SCRIPT', $this->generateParaGridScript(true), $this->context->employee->id);
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_CUSTOMERS_SCRIPT', $this->context->employee->id);
		}

		parent::__construct();

		$this->extracss = $this->pushCSS([_EPH_ADMIN_THEME_DIR_ .   $this->bo_theme . 'css/student.css']);
		$this->manageHeaderFields = true;

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_PS_JS_DIR_ . 'customer.js',
			_PS_JS_DIR_ . 'bank.js',
		]);
	}

	public function generateParaGridScript($regenerate = false) {

		$this->uppervar = 'var pqVS = {

            rpp: 100,
            init: function () {
                this.totalRecords = 0;
                this.requestPage = 1;
                this.data = [];
				this.needInstance = 1;
				this.hasFilter = 0;
				this.isSort = 0;
            }
        };' . PHP_EOL . '

		pqVS.init();' . PHP_EOL;

		$this->requestModel = '{
			beforeSend: function( jqXHR, settings ){

				if(pqVS.hasFilter == 0 && pqVS.isSort == 0) {
				var grid = this;
				var init = (pqVS.requestPage - 1) * pqVS.rpp;

				if(init > pqVS.totalRecords) {
					init = pqVS.totalRecords;
				}
				var datalen = pq_data.length;

				var totalForCache = init+datalen;

				if(init < datalen) {

					if(totalForCache == pqVS.totalRecords) {
						var end = totalForCache;
					} else {
						var end = init + pqVS.rpp;
					}

					var nextSet = [];
					for (var i = init; i < end; i++) {
						nextSet.push(pq_data[i]);
					}
					grid.hideLoading( );
					pqVS.needInstance = false;


					return { totalRecords: totalRecords, data: nextSet };
					jqXHR.abort();
				}
				}

 			},
            location: "remote",
            dataType: "json",
            method: "POST",
			recIndx: "id_customer",
			url: AjaxLinkAdminCustomers,
			postData: function () {
                return {
                    action: "getCustomerRequest",
                    ajax: 1,
					pq_data: JSON.stringify(pq_data),
					pq_curpage: pqVS.requestPage,
                    pq_rpp: pqVS.rpp,
					needInstance: pqVS.needInstance,
					totalRecords: pqVS.totalRecords

                };
            },
            getData: function (response) {

				var data = response.data;
				var len = data.length;

				var datalen = pq_data.length;
				var init = (response.curPage - 1) * pqVS.rpp;
				var totalForCache = init+len;
				pqVS.totalRecords = response.totalRecords;
				var nextSet = [];

				if(pqVS.hasFilter == 0 && pqVS.isSort == 0) {
					if(totalForCache == pqVS.totalRecords) {
						for (var i = 0; i < len; i++) {
							pq_data.push(data[i]);
						}
						console.log("case1")
						var end = totalForCache;
					} else 	if(init == datalen) {
						console.log("case2")
						var end = init + pqVS.rpp;
						for (var i = 0; i < len; i++) {
							pq_data.push(data[i]);
						}

					} else {
						console.log("case3")
						var end = init +pqVS.rpp;
					}




					var nextSet = [];
					for (var i = init; i < end; i++) {
						nextSet.push(pq_data[i]);
					}
				} else {
					nextSet = response.data;
				}





                return { totalRecords: response.totalRecords, data: nextSet }
            }
        }';

		$this->windowHeight = '350';
		$this->paramPageModel = [
			'type' => '\'remote\'',
			'rPP'  => 100,
		];

		$this->paramChange = 'function(evt, ui) {
            var grid = this;
            var updateData = ui.updateList[0];
            var newRow = updateData.newRow;
            var dataField = Object.keys(newRow)[0].toString();
            var dataValue = newRow[dataField];
            var dataEducation = updateData.rowData.id_customer;
            $.ajax({
                type: "POST",
                url: AjaxLinkAdminCustomers,
                data: {
                    action: "updateByVal",
                    idCustomer: dataEducation,
                    field: dataField,
                    fieldValue: dataValue,
                    ajax: true
                },
                async: true,
                dataType: "json",
                success: function(data) {
                    if (data.success) {
                        showSuccessMessage(data.message);
                     } else {
                        showErrorMessage(data.message);
                    }
                }
            })
        }';

		$this->sortModel = [
			'cancel' => true,
			'type'   => '\'remote\'',
		];
		$this->beforeSort = '
		function (evt) {
			console.log(evt);
        	if (evt.originalEvent) {
            	pqVS.init();
				pqVS.isSort = true;
            }
            }
		';

		$this->filterModel = [
			'on'          => true,
			'mode'        => '\'AND\'',
			'header'      => true,
			'type'        => '\'remote\'',
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
		$this->beforeFilter = 'function(event, ui ){
			console.log(ui);
			var value = ui.rules[0].value;
			if(value == "") {
				console.log("empty value")
			}
			if(typeof value !== "undefined" && !$.isNumeric(value) && value != ""  && value.length < 3) {
				return false;
			}

            pqVS.init();
			pqVS.hasFilter = true;
        }';
		$this->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

		$this->gridAfterLoadFunction = 'gridCustomer.pager().on("change", function(evt, ui){

			pqVS.requestPage = ui.curPage;
    });
';
		$this->paramComplete = 'function(event, ui){

		$("#isCefSelector").selectmenu({
		width: 200,
    	"change": function(event, ui) {
        	gridCustomer.filter({
            	mode: "AND",
                rules: [
                	{ dataIndx:"is_agent", condition: "equal", value: ui.item.value}
                 ]
            });
        }
     });
        }';

		$this->paramToolbar = [
			'items' => [
				[
					'type'     => '\'button\'',
					'label'    => '\'Ajouter un Client\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'function () {' . PHP_EOL . '
                    	addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                    }' . PHP_EOL,
				],
				[
					'type'    => '\'select\'',
					'icon'    => '\'ui-icon-disk\'',
					'attr'    => '\'id="isCefSelector"\'',
					'options' => '[
            			{"0": "Seulement les CEF"},
						{"0": "Non"},
						{"1": "Oui"},
						]',
				],
				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Gérer les champs affiché') . '\'',
					'cls'   => '\'showCategory changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'  => '\'id="page-header-desc-customer-fields_edit"\'',
				],
			],
		];

		$this->paramTitle = '\'' . $this->l('Gérer mes clients') . '\'';

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
                selected = selgrid' . $this->className . '.getSelection().length;
                var dataLenght = grid' . $this->className . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                        "add": {
                            name: \'' . $this->l('Ajouter un nouveau client') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addAjaxObject("' . $this->controller_name . '");
                            }
                        },

						"edit": {
                            name : \'' . $this->l('Modifier  ') . '\'' . '+rowData.firstname+ " "+rowData.lastname,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_customer)
                            }
                        },

						"turnAdmin": {
                            name : \'' . $this->l('Convertir en Employee ') . '\'' . '+rowData.firstname+" "+rowData.lastname,
                            icon: "ass",
							visible: function(key, opt) {
                                if (rowData.is_admin == 1) {
                                    return false;
                                }
                                return true;
                            },
							callback: function(itemKey, opt, e) {
                             	turnEmployee(rowData.id_customer);
                            }
                        },

                        "sep1": "---------",
                        "select": {
                            name: \'' . $this->l('Tous sélectionner') . '\',
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
                            name: \'' . $this->l('Tous déselectionner') . '\',
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
                            name: \'' . $this->l('Supprimer le client :') . '\'' . '+rowData.lastname,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                              deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un client", "Etes vous sure de vouloir supprimer "+rowData.firstname+" "+ rowData.lastname+ " ?", "Oui", "Annuler",rowData.id_customer, rowIndex);
                            }
                        },
                        "bulkdelete": {
                            name: \'' . $this->l('Delete the selected customer') . '\',
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected < 2) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								proceedBulkDelete(selgrid' . $this->className . ');
                            }
                        },

                    },
                };
            }',
			]];

		return parent::generateParaGridScript();
	}

	public function generateParaGridOption() {

		return true;

	}

	public function ajaxProcessinitController() {

		return $this->initGridController();
	}

	public function getCustomerRequest($pq_curpage, $pq_rpp, $pq_filter, $pq_sort) {

		$nbRecords = Db::getInstance()->getValue(
			(new DbQuery())
				->select('count( * )')
				->from($this->table)
		);

		$hasFilter = false;

		$query = new DbQuery();
		$query->select('a.`id_customer`, a.`customer_code`, a.`firstname`, a.`lastname`, a.`email`, a.`company`, a.`is_admin`, a.`id_gender`, a.`active` AS `active`, a.`id_default_group`, a.birthday,  grl.name AS `tarif`, a.date_add, gl.name as title,  case when a.active = 1 then \'<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>\' else \'<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>\' end as active, case when a.active = 1 then 1 else 0 end as enable');
		$query->from('customer', 'a');
		$query->leftJoin('gender_lang', 'gl', 'a.`id_gender` = gl.`id_gender` AND gl.`id_lang` = ' . $this->context->language->id);
		$query->leftJoin('group', 'gr', 'gr.`id_group` = a.`id_default_group`');
		$query->leftJoin('group_lang', 'grl', 'grl.`id_group` = gr.`id_group` AND grl.`id_lang` = ' . $this->context->language->id);

		if (is_array($pq_filter) && count($pq_filter)) {

			$hasFilter = true;
			$mode = $pq_filter['mode'];
			$filter = $pq_filter['data'];

			foreach ($filter as $key => $value) {

				if ($value['condition'] == 'equal') {
					$operator = ' = ' . $value['value'];
				} else

				if ($value['condition'] == 'contain') {
					$operator = ' LIKE \'%' . $value['value'] . '%\'';
				} else

				if ($value['condition'] == 'begin') {
					$operator = ' LIKE \'' . $value['value'] . '%\'';
				}

				$query->where('a.' . $value['dataIndx'] . $operator);
			}

		} else {

			if ($pq_curpage > 1) {

				$query->limit($pq_rpp, $pq_rpp * ($pq_curpage - 1));
			} else {

				$query->limit($pq_rpp);
			}

		}

		if (is_array($pq_sort) && count($pq_sort)) {

			foreach ($pq_sort as $key => $value) {

				if ($value['dir'] == 'up') {
					$arg = 'ASC';
				} else {
					$arg = 'DESC';
				}

				$query->orderBy('a.' . $value['dataIndx'] . ' ' . $arg);
			}

		} else {
			$query->orderBy('a.`id_customer` DESC');
		}

		$customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

		if ($hasFilter && is_array($customers) && count($customers)) {
			$nbRecords = count($customers);

		}

		foreach ($customers as &$customer) {

			$id_address = Address::getFirstCustomerAddressId($customer['id_customer']);

			if ($id_address > 0) {
				$address = new Address((int) $id_address);
				$customer['phone'] = $address->phone;
				$customer['phone_mobile'] = $address->phone_mobile;
				$customer['address1'] = $address->address1;
				$customer['postcode'] = $address->postcode;
				$customer['city'] = $address->city;

			}

		}

		return [
			'data'         => $customers,
			'curPage'      => $pq_curpage,
			'totalRecords' => $nbRecords,
		];

	}

	public function ajaxProcessgetCustomerRequest() {

		$needInstance = Tools::getValue('needInstance');
		$pq_data = Tools::getValue('pq_data');
		$pq_data = Tools::jsonDecode($pq_data, true);
		$totalRecords = Tools::getValue('totalRecords');
		$pq_curpage = Tools::getValue('pq_curpage');

		if ($needInstance) {

			$pq_rpp = Tools::getValue('pq_rpp');
			$pq_filter = Tools::getValue('pq_filter');
			$pq_filter = Tools::jsonDecode($pq_filter, true);
			$pq_sort = Tools::getValue('pq_sort');
			$pq_sort = Tools::jsonDecode($pq_sort, true);
			header("Content-type: application/json");

			$return = Tools::jsonEncode($this->getCustomerRequest($pq_curpage, $pq_rpp, $pq_filter, $pq_sort));
			die($return);
		} else {
			return [
				'data'         => $pq_data,
				'curPage'      => $pq_curpage,
				'totalRecords' => $totalRecords,
			];
			die(Tools::jsonEncode($return));
		}

	}

	public function ajaxProcessUpdateByVal() {

		$idCustomer = (int) Tools::getValue('idCustomer');
		$field = Tools::getValue('field');
		$fieldValue = Tools::getValue('fieldValue');

		if ($field == 'birthday') {
			$date = DateTime::createFromFormat('m/d/Y', $fieldValue);

			$fieldValue = $date->format('Y-m-d');
		}

		$education = new Customer($idCustomer);
		$classVars = get_class_vars(get_class($education));

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		if (Validate::isLoadedObject($education)) {

			$education->$field = $fieldValue;
			$result = $education->update();

			if (!isset($result) || !$result) {
				$result = [
					'success' => false,
					'message' => Tools::displayError('An error occurred while updating the field.'),
				];
				$this->errors[] = Tools::displayError('An error occurred while updating the product.');
			} else {
				$result = [
					'success' => true,
					'message' => $this->l('Update successful'),
				];
			}

		} else {

			$this->errors[] = Tools::displayError('An error occurred while loading the product.');
		}

		$this->errors = array_unique($this->errors);

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function getCustomerFields() {

		$genders = [
			[
				'title'     => 'Monsieur',
				'id_gender' => 1,
			],
			[
				'title'     => 'Madame',
				'id_gender' => 2,
			],
		];

		// Tous les champs seront automatiquement
		// 'valign'      => 'center',
		// grâce à une règle css dans admin-theme.css

		return [
			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'exWidth'    => 15,
				'dataIndx'   => 'id_customer',
				'dataType'   => 'integer',
				'editable'   => false,
				'halign'     => 'HORIZONTAL_CENTER',
				'hiddenable' => 'no',
				'align'      => 'center',
				'valign'     => 'center',
				'filter'     => [
					'crules' => [['condition' => "contain"]],
				],
			],

			[
				'title'      => $this->l('Customer Code'),
				'width'      => 100,
				'exWidth'    => 25,
				'dataIndx'   => 'customer_code',
				'dataType'   => 'string',
				'align'      => 'left',
				'halign'     => 'HORIZONTAL_LEFT',
				'editable'   => false,
				'hidden'     => false,
				'hiddenable' => 'yes',
				'valign'     => 'center',
				'filter'     => [

					'crules' => [['condition' => "contain"]],
				],

			],

			[
				'title'    => $this->l('Social title'),
				'width'    => 75,
				'dataIndx' => 'title',
				'align'    => 'center',
				'dataType' => 'string',
				'cls'      => 'pq-dropdown',
				'editor'   => [
					'type'      => "select",
					'valueIndx' => "id_gender",
					'labelIndx' => "title",
					'options'   => $genders,

				],

			],
			[
				'title'    => $this->l('Prénom'),
				'width'    => 150,
				'exWidth'  => 25,
				'dataIndx' => 'firstname',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => true,
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
				'editable' => true,
			],
			[
				'title'    => $this->l('Nom'),
				'width'    => 150,
				'exWidth'  => 25,
				'dataIndx' => 'lastname',
				'dataType' => 'string',
				'halign'   => 'HORIZONTAL_LEFT',
				'editable' => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
				'editable' => true,
			],
			[
				'title'    => $this->l('Email'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'email',
				'dataType' => 'string',
				'cls'      => 'jsCopyClipBoard ',
				'editable' => false,
				'hidden'   => false,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],

			],
			[
				'title'    => $this->l('Société'),
				'width'    => 100,
				'exWidth'  => 40,
				'dataIndx' => 'company',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],
			],

			[
				'title'     => $this->l('Groupe Client'),
				'width'     => 100,
				'exWidth'   => 30,
				'dataIndx'  => 'tarif',
				'labelIndx' => 'id_default_group',
				'halign'    => 'HORIZONTAL_LEFT',
				'dataType'  => 'string',

			],
			[
				'title'    => $this->l('Téléphone'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'phone_mobile',
				'cls'      => 'jsCopyClipBoard telephone',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],

			],

			[
				'title'    => $this->l('Adresse'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'address1',
				'align'    => 'left',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
			],
			[
				'title'    => $this->l('Adresse (suite)'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'address2',
				'align'    => 'left',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
			],

			[
				'title'    => $this->l('Code postale'),
				'width'    => 150,
				'exWidth'  => 20,
				'dataIndx' => 'postcode',
				'align'    => 'left',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,

			],
			[
				'title'    => $this->l('Ville'),
				'width'    => 150,
				'exWidth'  => 30,
				'dataIndx' => 'city',
				'halign'   => 'HORIZONTAL_LEFT',
				'align'    => 'left',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
			],
			[
				'title'    => $this->l('Date de naissance'),

				'dataIndx' => 'birthday',
				'minWidth' => 150,
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'session'  => false,
				'vdi'      => true,
				'editable' => true,
				'hidden'   => true,
				'cls'      => 'pq-calendar pq-side-icon',
				'editor'   => [
					'type'    => "textbox",
					'init'    => 'dateEditor',
					'getData' => 'getDataDate',
				],
				'render'   => 'renderBirthDate',
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
				'title'    => $this->l('Date de création'),
				'width'    => 150,
				'exWidth'  => 20,
				'dataIndx' => 'date_add',
				'halign'   => 'HORIZONTAL_CENTER',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => false,

			],

			[

				'dataIndx'   => 'id_gender',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[

				'dataIndx'   => 'id_admin',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
		];

	}

	public function ajaxProcessgetCustomerFields() {

		die(EmployeeConfiguration::get('EXPERT_CUSTOMERS_FIELDS', $this->context->employee->id));
	}

	public function manageFieldsVisibility($fields) {

		return parent::manageFieldsVisibility($fields);
	}

	public function ajaxProcessUpdateVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERS_FIELDS', $this->context->employee->id), true);
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
		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERS_FIELDS', $headerFields, $this->context->employee->id);
		$response = [
			'headerFields' => $headerFields,
		];

		die($headerFields);
	}

	public function ajaxProcessupdateJsonVisibility() {

		$visibility = Tools::getValue('visibilities');
	}

	public function ajaxProcessAddObject() {

		$data = $this->createTemplate('controllers/customers/newCustomer.tpl');

		$groups = Group::getGroups($this->default_form_language, true);

		$allgroups = Group::getGroups($this->default_form_language, true);

		$data->assign([

			'taxModes'        => TaxMode::getTaxModes(),
			'currency'        => $this->context->currency,
			'countries'       => Country::getCountries($this->context->language->id, false),
			'default_country' => Configuration::get('PS_COUNTRY_DEFAULT'),
			'taxes'           => Tax::getRulesTaxes($this->context->language->id),
			'tarifs'          => Customer::getTarifs(),
			'genders'         => Gender::getGenders(),
			'paymentModes'    => PaymentMode::getPaymentModes(),
			'groups'          => $groups,
			'allgroups'       => $allgroups,
			'link'            => $this->context->link,
			'id_tab'          => $this->identifier_value,
			'formId'          => 'form-' . $this->table,

		]);

		$li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ajouter un nouveau client</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessEditObject() {

		if ($this->tabAccess['edit'] == 1) {

			$id_customer = Tools::getValue('idObject');
			$this->object = new $this->className($id_customer);
			$context = Context::getContext();

			$data = $this->createTemplate('controllers/customers/editCustomer.tpl');

			$id_address = Address::getFirstCustomerAddressId($this->object->id);

			if ($id_address > 0) {
				$address = new Address((int) $id_address);
			}

			$pusjJs = $this->pushJS([_PS_JS_DIR_ . 'bank.js',
			]);

			$genders = Gender::getGenders();

			$tarifs = Customer::getTarifs();

			$groups = Group::getGroups($this->default_form_language, true);

			$this->context->customer = $this->object;

			$customerStats = $this->object->getStats();

			if ($total_customer = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
				->select('SUM(`total_paid`)')
				->from('customer_pieces')
				->where('`id_customer` = ' . (int) $this->object->id)
				->where('`validate` = 1')
			)) {
				Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
					(new DbQuery())
						->select('SQL_CALC_FOUND_ROWS COUNT(*)')
						->from('customer_pieces')
						->where('`validate` = 1')
						->where('`id_customer` != ' . (int) $this->object->id)
						->groupBy('id_customer')
						->having('SUM(`total_paid`) > ' . (int) $total_customer)
				);
				$countBetterCustomers = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()') + 1;
			} else {
				$countBetterCustomers = '-';
			}

			$orders = CustomerPieces::getOrdersbyIdCustomer($this->object->id);
			$totalOrders = count($orders);

			$customerTurnover = CustomerPieces::getOrderTotalbyIdCustomer($this->object->id);

			$products = $this->object->getBoughtProducts();

			$groups = $this->object->getGroups();
			$customerGroups = [];

			foreach ($groups as $group) {

				$customerGroups[] = $group;
			}

			$totalGroups = count($groups);

			for ($i = 0; $i < $totalGroups; $i++) {
				$group = new Group($groups[$i]);
				$groups[$i] = [];
				$groups[$i]['id_group'] = $group->id;
				$groups[$i]['name'] = $group->name[$this->default_form_language];
			}

			$allgroups = Group::getGroups($this->default_form_language, true);
			$data->assign('banks', Customer::getBankAccount($this->object->id));

			if ($this->object->is_admin) {
				$employee = new Employee($this->object->id_employee);
				$availableProfiles = Profile::getProfiles($this->context->language->id);
				$image = _PS_EMPLOYEE_IMG_DIR_ . $employee->id . '.jpg';

				if (file_exists($image)) {
					$imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/' . $employee->id . '.jpg';
				} else {
					$imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/Unknown.png';
				}

				$themes = [];
				$path = _SHOP_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

				foreach (scandir($path) as $theme) {

					if ($theme[0] != '.' && is_dir($path . $theme) && (@filemtime($path . $theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-theme.css'))) {
						$themes[] = [
							'id'   => $theme,
							'name' => ucfirst($theme),
						];
					}

				}

				$data->assign([
					'employee'          => $employee,
					'imageUrl'          => $imageUrl,
					'availableProfiles' => $availableProfiles,
					'themes'            => $themes,
					'currentProfileId'  => $this->context->employee->id_profile,
				]);
			}

			$data->assign([
				'customer'               => $this->object,
				'pusjJs'                 => $pusjJs,
				'taxModes'               => TaxMode::getTaxModes(),
				'currency'               => $context->currency,
				'countries'              => Country::getCountries($this->context->language->id, false),
				'default_country'        => Configuration::get('PS_COUNTRY_DEFAULT'),
				'taxes'                  => Tax::getRulesTaxes($this->context->language->id),
				'tarifs'                 => Customer::getTarifs(),
				'genders'                => Gender::getGenders(),
				'paymentModes'           => PaymentMode::getPaymentModes(),
				'addresses'              => [$address],
				'registration_date'      => Tools::displayDate($customer->date_add, null, true),
				'customer_stats'         => $customerStats,
				'last_visit'             => Tools::displayDate($customerStats['last_visit'], null, true),
				'count_better_customers' => $countBetterCustomers,
				'groups'                 => $groups,
				'allgroups'              => $allgroups,
				'customerGroups'         => $customerGroups,
				'orders'                 => $orders,
				'totalOrders'            => $totalOrders,
				'customerTurnover'       => $customerTurnover,
				'products'               => $products,
				'link'                   => $this->context->link,
				'id_tab'                 => $this->identifier_value,
				'controller'             => $this->controller_name,
				'table'                  => $this->table,

			]);

			$li = '<li id="uperEdit' . $this->controller_name . '" data-self="' . $this->php_self . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Voir ou éditer ' . $this->object->firstname . ' ' . $this->object->lastname . '</a><button type="button" class="close tabdetail" onClick="cancelViewCustomer();" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
			$html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

			$result = [
				'success' => true,
				'li'      => $li,
				'html'    => $html,
			];
		} else {
			$result = [
				'success' => false,
				'message' => 'Votre profile administratif ne vous permet pas d‘éditer les clients',
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessBulkDelete() {

		$customers = Tools::getValue('customers');

		foreach ($customers as $customer) {
			$object = new Customer($customer);

			if (!$object->delete()) {
				$this->errors[] = Tools::displayError('An error occurred while deleting the customer ' . $object->firstname);
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
				'success' => true,
				'message' => $this->l('Selection of customers has been properly deleted'),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessDeleteCustomer() {

		$idCustomer = Tools::getValue('idCustomer');
		$customer = new Customer($idCustomer);
		$customer->delete();

		$result = [
			'success' => true,
			'message' => 'Le client a été supprimé avec succès de la base de données.',
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddNewAjaxObject() {

		if (!$address1 = Tools::getValue('address1')) {
			$return = [
				'success' => false,
				'message' => 'L’adresse est obligatoire, merci de renseigner le champs.',
			];
			die(Tools::jsonEncode($return));
		}

		$email = Tools::getValue('email');

		if (!Customer::checkEmail($email)) {

			$return = [
				'success' => false,
				'message' => 'L’adresse e-mail existe déjà dans la base de données !',
			];
			die(Tools::jsonEncode($return));
		}

		$customer = new Customer();

		foreach ($_POST as $key => $value) {

			if (property_exists($customer, $key) && $key != 'id_customer') {

				$customer->{$key}

				= $value;
			}

		}

		$password = Tools::generateStrongPassword();

		$customer->passwd = Tools::hash($password);
		$customer->password = $password;
		$customer->ip_registration_newsletter = pSQL(Tools::getRemoteAddr());
		$customer->newsletter_date_add = pSQL(date('Y-m-d H:i:s'));
		$customer->newsletter = 1;
		$idCountry = Tools::getValue('id_country');

		if (!$idCountry) {
			$idCountry = 8;
		}

		$customer->customer_code = Customer::generateCustomerCode($idCountry, Tools::getValue('postcode'));
		$customer->id_stdaccount = Customer::generateCustomerAccount($customer, Tools::getValue('postcode'));

		try {
			$result = $customer->add();
		} catch (Exception $ex) {
			$file = fopen("testAddCustomer.txt", "w");
			fwrite($file, $ex->getMessage());
		}

		if ($result) {
			$tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/admin_account.tpl');
			$address = new Address();
			$address->id_customer = $customer->id;
			$address->id_country = Tools::getValue('id_country');
			$address->alias = 'Facturation';
			$address->company = Tools::getValue('company');
			$address->lastname = $customer->lastname;
			$address->firstname = $customer->firstname;
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

			$result = $address->add();

			$suivie = new StudentSuivie();
			$suivie->id_customer = $customer->id;
			$suivie->id_employee = $this->context->employee->id;
			$suivie->id_employee = 0;
			$suivie->content = 'Inscription de ' . $customer->firstname . ' ' . $customer->lastname . ' par ' . $this->context->employee->firstname . ' ' . $this->context->employee->lastname;
			$suivie->add();
			$tpl->assign([
				'firstname'    => $customer->firstname,
				'lastname'     => $customer->lastname,
				'email'        => $customer->email,
				'student_code' => $customer->customer_code,
				'passwd'       => $customer->password,
			]);
			$postfields = [
				'sender'      => [
					'name'  => "Service  Administratif " . Configuration::get('PS_SHOP_NAME'),
					'email' => 'no-reply@' . Configuration::get('PS_SHOP_URL'),
				],
				'to'          => [
					[
						'name'  => $customer->firstname . ' ' . $customer->lastname,
						'email' => $customer->email,
					],
				],
				'subject'     => $customer->firstname . ' ! Bienvenue sur ' . Configuration::get('PS_SHOP_NAME'),
				"htmlContent" => $tpl->fetch(),
			];
			$result = Tools::sendEmail($postfields);

			$return = [
				'success' => true,
				'message' => 'Le client a été ajouté avec succès à la base de données.',
			];

		} else {
			$return = [
				'success' => false,
				'message' => 'Le webmaster a fait une bourde visiblement.',
			];

		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddCustomerBankAccount() {

		$bank = new BankAccount();

		foreach ($_POST as $key => $value) {

			if (property_exists($bank, $key) && $key != 'id_bank_account') {

				$bank->{$key}
				= $value;

			}

		}

		$result = $bank->add();

		if ($result) {
			$html = '<tr id="bank_' . $bank->id . '"><td>' . $bank->bank_name . '</td><td>' . $bank->iban . '</td><td>' . $bank->swift . '</td></tr>';

			$return = [
				'success' => true,
				'message' => 'Le compte bancaire a été ajouté avec succès',
				'html'    => $html,
			];
		} else {
			$return = [
				'success' => false,
				'message' => 'Un problème a été rencontré lors de la création du compte bancaire',
			];
		}

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessCheckEmail() {

		$email = Tools::getValue('email');
		$checkExist = Db::getInstance()->getValue(
			(new DbQuery())
				->select('`id_customer`')
				->from('customer')
				->where('`email` LIKE \'' . $email . '\'')
		);

		if (isset($checkExist) && $checkExist > 0) {
			$return = [
				'success' => false,
			];
		} else {
			$return = [
				'success' => true,
			];
		}

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessUpdateObject() {

		$object = new Customer(Tools::getValue('id_customer'));
		$oldPasswd = $object->passwd;

		foreach ($_POST as $key => $value) {

			if (property_exists($object, $key) && $key != 'id_customer') {

				if ($key == 'passwd' && empty($value)) {
					continue;
				}

				if ($key == 'passwd' && !empty($value)) {
					$newPasswd = Tools::hash(Tools::getValue('passwd'));

					if ($newPasswd == $oldPasswd) {
						continue;
					}

					$value = $newPasswd;
					$object->password = Tools::getValue('passwd');
				}

				$object->{$key}

				= $value;
			}

		}

		try {
			$result = $object->update();
		} catch (Exception $ex) {
			$file = fopen("testUpdateCustomer.txt", "w");
			fwrite($file, $ex->getMessage());
		}

		if (!isset($result) || !$result) {
			$this->errors[] = Tools::displayError('An error occurred while updating an object.') . ' <b>' . $this->table . '</b> (' . Db::getInstance()->getMsgError() . ')';
		} else {

			if (Tools::getValue('address1')) {

				if ($idAddress = Tools::getValue('id_address')) {
					$address = new Address($idAddress);
				} else {
					$address = new Address();
				}

				$address->id_customer = $object->id;
				$address->id_country = Tools::getValue('id_country');
				$address->alias = 'Facturation';
				$address->company = Tools::getValue('company');
				$address->lastname = $object->lastname;
				$address->firstname = $object->firstname;
				$address->address1 = Tools::getValue('address1');
				$address->address2 = Tools::getValue('address2');
				$address->postcode = Tools::getValue('postcode');
				$address->city = Tools::getValue('city');
				$address->phone = Tools::getValue('phone');
				$address->phone_mobile = Tools::getValue('phone_mobile');

				if ($idAddress > 0) {

					$result = $address->update();
				} else {

					$result = $address->add();
				}

			}

			if ($object->is_admin) {
				$imageUploader = new HelperImageUploader('employeeUrl');
				$imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
				$files = $imageUploader->process();

				if (is_array($files) && count($files)) {

					foreach ($files as $image) {
						$destinationFile = _PS_EMPLOYEE_IMG_DIR_ . $object->id . '.jpg';
						$fileName = $object->id . '.jpg';
						copy($image['save_path'], $destinationFile);
					}

				}

			}

			$result = [
				'success' => true,
				'message' => $this->l('Le client a été mis à jour avec succès'),
			];

		}

		$this->errors = array_unique($this->errors);

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessEditCustomerAddress() {

		$idAddress = Tools::getValue('idAddress');

		$data = $this->createTemplate('controllers/customers/editAddress.tpl');

		$data->assign([
			'countries'       => Country::getCountries($this->context->language->id, false),
			'default_country' => Configuration::get('PS_COUNTRY_DEFAULT'),
			'address'         => new Address($idAddress),

		]);

		$result = [
			'success' => true,
			'html'    => $data->fetch(),
		];

		die(Tools::jsonEncode($result));

	}

	public function processChangeNewsletterVal() {

		$customer = new Customer($this->id_object);

		if (!Validate::isLoadedObject($customer)) {
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		}

		$customer->newsletter = $customer->newsletter ? 0 : 1;

		if (!$customer->update()) {
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		}

		Tools::redirectAdmin(static::$currentIndex . '&token=' . $this->token);
	}

	/**
	 * Toggle newsletter optin flag
	 */
	public function processChangeOptinVal() {

		$customer = new Customer($this->id_object);

		if (!Validate::isLoadedObject($customer)) {
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		}

		$customer->optin = $customer->optin ? 0 : 1;

		if (!$customer->update()) {
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		}

		Tools::redirectAdmin(static::$currentIndex . '&token=' . $this->token);
	}

	/**
	 * add to $this->content the result of Customer::SearchByName
	 * (encoded in json)
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessSearchCustomers() {

		$searches = explode(' ', Tools::getValue('customer_search'));
		$customers = [];
		$searches = array_unique($searches);

		foreach ($searches as $search) {

			if (!empty($search) && $results = Customer::searchByName($search, 50)) {

				foreach ($results as $result) {

					if ($result['active']) {
						$customers[$result['id_customer']] = $result;
					}

				}

			}

		}

		if (count($customers)) {
			$toReturn = [
				'customers' => $customers,
				'found'     => true,
			];
		} else {
			$toReturn = ['found' => false];
		}

		$this->content = json_encode($toReturn);
	}

	/**
	 * Uodate the customer note
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCustomerNote() {

		if ($this->tabAccess['edit'] === '1') {
			$note = Tools::htmlentitiesDecodeUTF8(Tools::getValue('note'));
			$customer = new Customer((int) Tools::getValue('id_customer'));

			if (!Validate::isLoadedObject($customer)) {
				die('error:update');
			}

			if (!empty($note) && !Validate::isCleanHtml($note)) {
				die('error:validation');
			}

			$customer->note = $note;

			if (!$customer->update()) {
				die('error:update');
			}

			die('ok');
		}

	}

	/**
	 * After delete
	 *
	 * @param ObjectModel $object
	 * @param int         $oldId
	 *
	 * @return bool
	 *
	 * @since 1.8.1.0
	 */
	protected function afterDelete($object, $oldId) {

		$customer = new Customer($oldId);
		$addresses = $customer->getAddresses($this->default_form_language);

		foreach ($addresses as $k => $v) {
			$address = new Address($v['id_address']);
			$address->id_customer = $object->id;
			$address->save();
		}

		return true;
	}

	public function ajaxProcessTurnEmployee() {

		$id_customer = Tools::getValue('id_customer');
		$customer = new Customer($id_customer);
		$availableProfiles = Profile::getProfiles($this->context->language->id);
		$extracss = $this->pushCSS([
			_PS_JS_DIR_ . 'trumbowyg/ui/trumbowyg.min.css',
			_PS_JS_DIR_ . 'jquery-ui/general.min.css',

		]);
		$pusjJs = $this->pushJS([
			_PS_JS_DIR_ . 'employee.js',
			_PS_JS_DIR_ . 'trumbowyg/trumbowyg.min.js',
			_PS_JS_DIR_ . 'jquery-jeditable/jquery.jeditable.min.js',
			_PS_JS_DIR_ . 'jquery-ui/jquery-ui-timepicker-addon.min.js',
			_PS_JS_DIR_ . 'moment/moment.min.js',
			_PS_JS_DIR_ . 'moment/moment-timezone-with-data.min.js',
			_PS_JS_DIR_ . 'calendar/working_plan_exceptions_modal.min.js',
			_PS_JS_DIR_ . 'datejs/date.min.js',

		]);
		$themes = [];
		$path = _SHOP_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

		foreach (scandir($path) as $theme) {

			if ($theme[0] != '.' && is_dir($path . $theme) && (@filemtime($path . $theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-theme.css'))) {
				$themes[] = [
					'id'   => $theme,
					'name' => ucfirst($theme),
				];
			}

		}

		$employee = new Employee();
		$imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/Unknown.png';

		$company = new Company(Configuration::get('EPH_COMPANY_ID'));

		$data = $this->createTemplate('controllers/back_users/newwemployee.tpl');
		$data->assign('genders', Gender::getGenders());
		$data->assign('countries', Country::getCountries($this->context->language->id, true));
		$data->assign('customer', $customer);
		$data->assign([
			'link'                    => $this->context->link,
			'employee'                => $employee,
			'imageUrl'                => $imageUrl,
			'availableProfiles'       => $availableProfiles,
			'themes'                  => $themes,
			'currentProfileId'        => $this->context->employee->id_profile,
			'EALang'                  => Tools::jsonEncode($this->getEaLang()),
			'pusjJs'                  => $pusjJs,
			'extracss'                => $extracss,
			'workin_plan'             => Tools::jsonEncode($employee->workin_plan),
			'workin_break'            => Tools::jsonEncode($employee->workin_break),
			'working_plan_exceptions' => Tools::jsonEncode($employee->working_plan_exceptions),
		]);

		$li = '<li id="uperAddAdminBackUsers" data-controller="AdminDashboard"><a href="#contentAddAdminBackUsers">Transformer ' . $student->firstname . ' en CEF</a><button type="button" class="close tabdetail" data-id="uperAddAdminBackUsers"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentAddAdminBackUsers" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessNewAgent() {

		$agent = new SaleAgent();
		$id_student = Tools::getValue('id_customer');
		$student = new Customer($id_student);

		foreach ($_POST as $key => $value) {

			if (property_exists($agent, $key) && $key != 'id_sale_agent') {
				$agent->{$key}

				= $value;

			}

		}

		$agent->active = 1;

		$result = $agent->add();

		if ($result) {

			$student->is_agent = 1;
			$student->id_default_group = 5;
			$student->update();
			$return = [
				'success' => true,
				'message' => 'L\'étudiant a été transformé en agent avec succès.',
			];
		} else {
			$return = [
				'success' => false,
				'message' => 'Jeff a merdé somewhere over the rainbow.',
			];
		}

		die(Tools::jsonEncode($return));
	}

	public static function ajaxProcessGetAutoCompleteEducation() {

		$context = Context::getContext();
		$results = [];
		$query = Tools::getValue('search');
		$sql = 'SELECT p.`id_education`, pl.`link_rewrite`, p.`reference`, p.`id_formatpack`, pl.`name`, p.`days`, image_education.`id_image_education` id_image, il.`legend`, p.`cache_default_attribute`
		FROM `' . _DB_PREFIX_ . 'education` p
		LEFT JOIN `' . _DB_PREFIX_ . 'education_lang` pl ON (pl.id_education = p.id_education AND pl.id_lang = ' . (int) $context->language->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_education` image_education
			ON (image_education.`id_education` = p.`id_education` AND image_education.cover=1)
		LEFT JOIN `' . _DB_PREFIX_ . 'image_education_lang` il ON (image_education.`id_image_education` = il.`id_image_education` AND il.`id_lang` = ' . (int) $context->language->id . ')
		WHERE (pl.name LIKE \'%' . pSQL($query) . '%\' OR p.reference LIKE \'%' . pSQL($query) . '%\')' .
			' GROUP BY p.id_education';

		$items = Db::getInstance()->executeS($sql);

		if ($items) {

			foreach ($items as $item) {

				if ($item['cache_default_attribute']) {
					$sql = 'SELECT pa.`id_education_attribute`, pa.`reference`, pa.`id_formatpack`, pa.`days`, ag.`id_attribute_group`, pai.`id_image`, agl.`name` AS group_name, al.`name` AS attribute_name,
						a.`id_attribute`
					FROM `' . _DB_PREFIX_ . 'education_attribute` pa
					' . Shop::addSqlAssociation('education_attribute', 'pa') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'education_attribute_combination` pac ON pac.`id_education_attribute` = pa.`id_education_attribute`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $context->language->id . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $context->language->id . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'education_attribute_image` pai ON pai.`id_education_attribute` = pa.`id_education_attribute`
					WHERE pa.`id_education` = ' . (int) $item['id_education'] . '
					GROUP BY pa.`id_education_attribute`, ag.`id_attribute_group`
					ORDER BY pa.`id_education_attribute`';

					$combinations = Db::getInstance()->executeS($sql);

					if (!empty($combinations)) {

						foreach ($combinations as $k => $combination) {
							$results[$combination['id_education_attribute']]['id_education'] = $item['id_education'];
							$results[$combination['id_education_attribute']]['id_education_attribute'] = $combination['id_education_attribute'];
							$results[$combination['id_education_attribute']]['days'] = $combination['days'];
							!empty($results[$combination['id_education_attribute']]['name']) ? $results[$combination['id_education_attribute']]['name'] .= ' ' . $combination['group_name'] . '-' . $combination['attribute_name']
							: $results[$combination['id_education_attribute']]['name'] = $item['name'] . ' ' . $combination['group_name'] . '-' . $combination['attribute_name'];

							if (!empty($combination['reference'])) {
								$results[$combination['id_education_attribute']]['ref'] = $combination['reference'];
							} else {
								$results[$combination['id_education_attribute']]['ref'] = !empty($item['reference']) ? $item['reference'] : '';
							}

							$results[$combination['id_education_attribute']]['id_formatpack'] = $combination['id_formatpack'];

						}

					} else {
						$education = [
							'id_education'           => (int) ($item['id_education']),
							'id_education_attribute' => 0,
							'name'                   => $item['name'],
							'days'                   => $item['days'],
							'ref'                    => (!empty($item['reference']) ? $item['reference'] : ''),
							'id_formatpack'          => $item['id_formatpack'],

						];
					}

				} else {
					$education = [
						'id_education'           => (int) ($item['id_education']),
						'id_education_attribute' => 0,
						'name'                   => $item['name'],
						'days'                   => $item['days'],
						'ref'                    => (!empty($item['reference']) ? $item['reference'] : ''),
						'id_formatpack'          => $item['id_formatpack'],
					];
					array_push($results, $education);
				}

			}

			$results = array_values($results);
		}

		die(Tools::jsonEncode($results));

	}

	public function ajaxProcessGetEducationDetails() {

		$id_education = Tools::getValue('id_education');
		$id_education_attribute = Tools::getValue('id_education_attribute');

		$education = Education::getEducationDetails($id_education, $id_education_attribute);

		die(Tools::jsonEncode(['education' => $education]));
	}

	public function ajaxProcessAddLicense() {

		$idFiliale = Tools::getValue('idCustomer');

		$studentCompany = new customer($idFiliale);

		$titleTab = '';

		$data = $this->createTemplate('controllers/customers/addLicense.tpl');
		$data->assign('studentCompany', $studentCompany);
		$data->assign('purchase_key', License::generateLicenceKey());

		$result = [
			'html'     => $data->fetch(),
			'titleTab' => $titleTab,
		];
		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddNewLicence() {

		$license = new License();

		foreach ($_POST as $key => $value) {

			if (property_exists($license, $key) && $key != 'id_license') {

				$license->{$key}

				= $value;
			}

		}

		$license->add();

		$result = [
			'success' => true,
			'message' => 'La société a été ajouté avec succès à la base de donnée.',
		];

		die(Tools::jsonEncode($result));
	}

}
