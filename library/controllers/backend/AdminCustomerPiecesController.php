<?php

/**
 * Class AdminCustomerPiecesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCustomerPiecesControllerCore extends AdminController {

	public $php_self = 'admincustomerpieces';
	// @codingStandardsIgnoreStart
	/** @var string $toolbar_title */
	public $toolbar_title;
	/** @var array $statuses_array */
	protected $statuses_array = [];
	// @codingStandardsIgnoreEnd

	public $validateSelector;

	public $paymentSelector;

	public $countryCustomerPiecesSelector;

	public $orderTomerge = [];

	public $orderComplete = [];

	public $pieceTypes = [];

	public $pieceType = [];

	public $configurationDetailField = [];

	static $_customer_eelected;

	static $_pieceDetails = [];

	public $defaultTemplate;

	/**
	 * AdminCustomerPiecesControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'customer_pieces';
		$this->className = 'CustomerPieces';
		$this->publicName = $this->l('Factures Clients');
		$this->lang = false;
		$this->identifier = 'id_customer_piece';
		$this->controller_name = 'AdminCustomerPieces';

		$this->context = Context::getContext();

		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_SCRIPT', $this->generateParaGridScript(true), $this->context->employee->id);
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_SCRIPT', $this->context->employee->id);

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_SCRIPT', $this->generateParaGridScript(true), $this->context->employee->id);
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_SCRIPT', $this->context->employee->id);
		}

		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_FIELDS', Tools::jsonEncode($this->getCustomerPiecesFields()), $this->context->employee->id);
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_FIELDS', $this->context->employee->id), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_FIELDS', Tools::jsonEncode($this->getCustomerPiecesFields()), $this->context->employee->id);
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_FIELDS', $this->context->employee->id), true);
		}

		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS', Tools::jsonEncode($this->getDetailCustomerPiecesFields()));
		$this->configurationDetailField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS', $this->context->employee->id), true);

		if (empty($this->configurationDetailField)) {
			EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS', Tools::jsonEncode($this->getDetailCustomerPiecesFields()));
			$this->configurationDetailField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS'), true);
		}

		$this->pieceTypes = [
			'QUOTATION'    => $this->l('Devis'),
			'ORDER'        => $this->l('Commandes'),
			'DELIVERYFORM' => $this->l('Bons de Livraison'),
			'DOWNPINVOICE' => $this->l('Factures d‘accompte'),
			'INVOICE'      => $this->l('Factures'),
			'ASSET'        => $this->l('Avoirs'),
		];

		$this->pieceType = [
			'QUOTATION'    => $this->l('Devis'),
			'ORDER'        => $this->l('Commande'),
			'DELIVERYFORM' => $this->l('Bon de Livraison'),
			'DOWNPINVOICE' => $this->l('Facture d‘accompte'),
			'INVOICE'      => $this->l('Facture'),
			'ASSET'        => $this->l('Avoir'),
		];

		parent::__construct();

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_PS_JS_DIR_ . 'customerpieces.js',
			_PS_JS_DIR_ . 'toastr.min.js',
			_PS_JS_DIR_ . 'ajaxq.js',
		]);
	}

	public function ajaxProcessOpenTargetController() {

		$data = $this->createTemplate($this->table . '.tpl');
		$extracss = $this->pushCSS([
			_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/customerpieces.css',

		]);

		$rangeMonths = Tools::getExerciceMonthRange();
		$data->assign([
			'pieceTypes'         => $this->pieceTypes,
			'paragridScript'     => $this->generateParaGridScript(),
			'rangeMonths'        => $rangeMonths,
			'pieceTypes'         => $this->pieceTypes,
			'paymentModes'       => PaymentMode::getPaymentModes(),
			'countries'          => Country::getCountries($this->context->language->id, true),
			'manageHeaderFields' => true,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
			'controller'         => $this->controller_name,
			'tableName'          => $this->table,
			'className'          => $this->className,
			'link'               => $this->context->link,
			'extraJs'            => $this->setAjaxMedia(),
			'extracss'           => $extracss,
		]);

		$li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function generateParaGridScript() {

		$ajaxlink = $this->context->link->getAdminLink($this->controller_name);
		$pieceTypes = [
			'QUOTATION'    => $this->l('Devis'),
			'ORDER'        => $this->l('Commande'),
			'DELIVERYFORM' => $this->l('Bon de Livraison'),
			'DOWNPINVOICE' => $this->l('Facture d‘accompte'),
			'INVOICE'      => $this->l('Facture'),
			'ASSET'        => $this->l('Avoir'),
		];
		$pieces = [
			'QUOTATION'    => $this->l('Devis'),
			'ORDER'        => $this->l('Commande'),
			'DELIVERYFORM' => $this->l('Bon de Livraison'),
			'DOWNPINVOICE' => $this->l('Facture d‘accompte'),
			'INVOICE'      => $this->l('Facture'),
			'ASSET'        => $this->l('Avoir'),
		];

		$this->paramExtraFontcion = [
			'


			function proceedBulkUpdate(selector, target) {

			var selectionArray = selector.getSelection();
			var idpieces = [];
			$.each(selectionArray, function(index, value) {
			idpieces.push(value.rowData.id_customer_piece);

			})

			$.ajax({
				type: \'POST\',
				url: AjaxLinkAdminCustomerPieces,
				data: {
					action: \'convertBulkPiece\',
					idPieces: idpieces,
					target: target,
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					if (data.success) {
						showSuccessMessage(data.message);
						gridCustomerPieces.refreshDataAndView();
					} else {
						showErrorMessage(data.message);
					}
				}
				});

			}
			function validateBulkPieces(selector) {


				var idpieces = [];
				$.each(selector, function(index, value) {
					idpieces.push(value.rowData.id_customer_piece);
				})

				$.ajax({
					type: \'POST\',
					url: AjaxLinkAdminCustomerPieces,
					data: {
						action: \'bulkValidate\',
						idPieces: idpieces,
						ajax: true
					},
					async: false,
					dataType: \'json\',
					success: function(data) {
						if (data.success) {
							showSuccessMessage(data.message);
							gridCustomerPieces.refreshDataAndView();
						} else {
							showErrorMessage(data.message);
						}
					}
				});

			}

			function buildStudentPiecesFilter(){

			$("#pieceSessionSelect" ).selectmenu({
				width: 250,
				classes: {
    				"ui-selectmenu-menu": "scrollable"
  				},
	   			"change": function(event, ui) {
		   			gridCustomerPieces.filter({
           			mode: \'AND\',
					rules: [
                		{ dataIndx: \'id_education_session\', condition: \'equal\', value: ui.item.value}
						]
						});
					$("#selectedSessionValue").val(ui.item.value);
					if(ui.item.value >0) {
						$("#export-invoice").slideDown();

						$("#export-excel").slideDown();
					} else {
						$("#export-invoice").slideUp();
						$("#export-excel").slideUp();
					}
	   			}
			});

			$("#pieceMonthSelect" ).selectmenu({
				width:300,

				"change": function(event, ui) {
		   			var values = ui.item.value;
					var res = values.split("|");
					gridCustomerPieces.filter({
           				mode: \'AND\',
						rules: [
                			{ dataIndx: \'date_add\', condition: \'between\', value: res[0], value2:res[1]}
						]
					});

	   			}

			});

            var validateconteneur = $(\'#validateSelector\').parent().parent();
            $(validateconteneur).empty();
            $(validateconteneur).append(\'' . $this->validateSelector . '\');
            $(\'#validateSelect\' ).selectmenu({
                "change": function(event, ui) {

                    grid' . $this->className . '.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx: \'isLocked\', condition: \'equal\', value: ui.item.value}
                        ]
                    });
                }
            });
			}



			',

		];

		$class = 'cls:\'productValidate\'';

		$this->paramPageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];
		$this->windowHeight = '300';

		$this->rowInit = 'function (ui) {
			var applyStyle;
            if(ui.rowData.isLocked) {
            	return {
                attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData' . $this->identifier . '+\' "\', ' . $class . '
                };
            }  else {
                return {
				attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'"  data-object="\' + ui.rowData' . $this->identifier . '+\' "\',
                };
            }
        }';
		$this->paramComplete = 'function(){
		window.dispatchEvent(new Event(\'resize\'));
		buildStudentPiecesFilter();


	 	$("#addNewpieces").selectmenu({
			width: 200,
			"change": function(event, ui) {
				if(ui.item.value != 0) {
					generateNewPiece(ui.item.value);
					$("#addNewpieces").val(0);
					$("#addNewpieces").selectmenu("refresh");
				}
			}
		});
        }';

		$domaine = Configuration::get('PS_SHOP_DOMAIN');

		$this->paramToolbar = [
			'items' => [

				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Gérer les champs affiché') . '\'',
					'cls'   => '\'showFields changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'  => '\'id="page-header-desc-customer_pieces-fields_edit"\'',
				],

				[
					'type'    => '\'select\'',
					'attr'    => '\'id="addNewpieces"\'',
					'cls'     => '\'addnewPiece ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'options' => '[
            			{"0": "Ajouter une pièces"},
						{"QUOTATION": "Devis"},
						{"ORDER": "Commande"},
						{"DELIVERYFORM": "Bon de Livraison"},
						{"DOWNPINVOICE": "Facture d‘accompte"},
						{"INVOICE": "Facture"},
						{"ASSET": "Avoir"},
						]',
				],

			],
		];

		$this->rowDblClick = 'function( event, ui ) {
			editCustomerPiece(ui.rowData.id_customer_piece);
		} ';

		$this->filterModel = [
			'on'          => true,
			'mode'        => '\'AND\'',
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

		$this->paramTitle = '\'' . $this->l('Gestion des pièces client') . '\'';
		$this->summaryData = '[{
                rank: \'Total\',
                summaryRow: true,
                pq_fn: {
                total_products_tax_excl: \'sum(M:M)\',
                total_shipping_tax_excl: \'sum(N:N)\',

				total_products_tax_incl: \'sum(O:O)\',
                total_tax_excl: \'sum(P:P)\',
				total_tax: \'sum(Q:Q)\',
                total: \'sum(R:R)\',
                total_paid:  \'sum(S:S)\',
                balanceDue:  \'sum(T:T)\',
				piece_margin:  \'sum(U:U)\',
            }
            }]';
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
                            name: \'' . $this->l('Transférer la pièce en ') . ' \',
                            icon: "add",
                            items: {

                                "pord": {
                                    name: \'' . $this->l('commande') . ' \',
                                    icon: "edit",
									visible: function(key, opt){
										var selected = selgrid' . $this->className . '.getSelection().length;
                                		if(selected > 1) {
                                    		return false;
                                		}
                                		if(rowData.piece_type == \'QUOTATION\') {
                                    		return true;
                                		}
                                		return false;
									},
                                    callback: function(itemKey, opt, e) {
                                        transfertPiece(rowData.id_customer_piece, "ORDER");
                                    }
                                },
                                "dform": {
                                    name: \'' . $this->l('Un bon de Livraison') . ' \',
                                    icon: "edit",
									visible: function(key, opt){
										var selected = selgrid' . $this->className . '.getSelection().length;
                                		if(selected > 1) {
                                    		return false;
                                		}
                                		if(rowData.piece_type == \'ORDER\') {
                                    		return true;
                                		}
										if(rowData.piece_type == \'QUOTATION\') {
                                    		return true;
                                		}
                                		return false;
									},
                                    callback: function(itemKey, opt, e) {
                                        transfertPiece(rowData.id_customer_piece,"DELIVERYFORM");
                                    }
                                },
                                "inv": {
                                    name: \'' . $this->l('facture') . ' \',
                                    icon: "edit",
									visible: function(key, opt){
										var selected = selgrid' . $this->className . '.getSelection().length;
                                		if(selected > 1) {
                                    		return false;
                                		}
                                		if(rowData.piece_type == \'ORDER\') {
                                    		return true;
                                		}
										if(rowData.piece_type == \'QUOTATION\') {
                                    		return true;
                                		}
										if(rowData.piece_type == \'DELIVERYFORM\') {
                                    		return true;
                                		}
                                		return false;
									},
                                    callback: function(itemKey, opt, e) {
                                        transfertPiece(rowData.id_customer_piece,"INVOICE");
                                    }
                                },
                                "asst": {
                                    name: \'' . $this->l('Un Avoir') . ' \',
                                    icon: "edit",
									visible: function(key, opt){
										var selected = selgrid' . $this->className . '.getSelection().length;
                                		if(selected > 1) {
                                    		return false;
                                		}
                                		if(rowData.piece_type == \'INVOICE\') {
                                    		return true;
                                		}

                                		return false;
									},
                                    callback: function(itemKey, opt, e) {
                                        transfertPiece(rowData.id_customer_piece,"ASSET");
                                    }
                                },
                            }

                            },
                        "edit": {
                            name: \'' . $this->l('Modifier ou visualiser la pièce ') . ' \'+rowData.pieceType+ \' :\'+rowData.pieceNumber,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
								//editCustomerPiece(rowData.id_customer_piece);
								editAjaxObject("' . $this->controller_name . '", rowData.id_customer_piece)
                            }
                        },

                         "validate": {
                            name: \'' . $this->l('Valider la Facture') . ' \ :\'+rowData.pieceNumber,
                            icon: "lock",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.piece_type == \'INVOICE\' && rowData.isLocked ==false) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
								validatePieces(rowData.id_customer_piece);
                            }
                        },
						"bulkvalidate": {
                            name: \'' . $this->l('Valider les Factures sélectionnées') . '\',
                            icon: "lock",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
								var pieceSelected = selgrid' . $this->className . '.getSelection();
							   var allowed = true;
                                if(selected < 2) {
                                    return false;
                                }
								$.each(pieceSelected, function( index, value ) {
  									if(value.rowData.isLocked == true) {
										allowed = false;
									}

  								});
								if(allowed == false) {
									return false;
								}

                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								validateBulkPieces(selgrid' . $this->className . '.getSelection());
                            }
                        },
						"regl": {
                            name: \'' . $this->l('Enregistrer le règlement de la') . ' \'+rowData.pieceType+ \' : \'+rowData.pieceNumber,
                            icon: "pay",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								if(rowData.balanceDue == 0) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								generateReglement(rowData.id_customer_piece);
                            }
                        },
					"reglBulk": {
                            name: \'' . $this->l('Enregistrer le règlement des pièces sélectionnées') . ' \',
                            icon: "edit",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
							   var pieceSelected = selgrid' . $this->className . '.getSelection();
							   var allowed = true;
                                if(selected < 2) {
                                    return false;
                                }
								$.each(pieceSelected, function( index, value ) {
  									if(value.rowData.isLocked == false) {
										allowed = false;
									}
									if(value.rowData.balanceDue == \'0.00\') {
										allowed = false;
									}
									if(value.rowData.isBooked  == 0) {
                                    	allowed = false;
                                	}
  								});
								if(allowed == false) {
									return false;
								}
								return true;
                            },
                            callback: function(itemKey, opt, e) {

								generateBulkReglement(selgrid' . $this->className . '.getSelection());
                            }
                        },

						"book": {
                            name: \'' . $this->l('Comptabiliser la') . ' \'+rowData.pieceType+ \' : \'+rowData.pieceNumber,
                            icon: "book",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.piece_type == \'INVOICE\' && rowData.isBooked  == 0) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
								bookPieces(rowData.id_customer_piece);
                            }
                        },
						"bulkbook": {
                            name: \'' . $this->l('Comptabiliser les Factures sélectionnées') . '\',
                            icon: "book",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
								var pieceSelected = selgrid' . $this->className . '.getSelection();
							  	 var allowed = true;
                                if(selected < 2) {
                                    return false;
                                }
								$.each(pieceSelected, function( index, value ) {
  									if(value.rowData.isLocked == false) {
										allowed = false;
									}
									if(value.rowData.isBooked == true) {
										allowed = false;
									}
  								});
								if(allowed == false) {
									return false;
								}


                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								bookBulkPieces(selgrid' . $this->className . '.getSelection());
                            }
                        },
						"print": {
                            name: \'' . $this->l('Imprimer la') . ' \'+rowData.pieceType+ \' : \'+rowData.pieceNumber,
                            icon: "print",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {

								print(rowData.id_customer_piece);
                            }
                         },
						 "bulkprint": {
                            name: \'' . $this->l('Imprimer les factures sélectionnées') . ' \',
                            icon: "print",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                 if(selected < 2) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {

								bulkPrint(selgrid' . $this->className . '.getSelection());
                            }
                        },
						 "viewbook": {
                            name: \'' . $this->l('Ouvrir l‘écriture') . '\',
                            icon: "book",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								if(rowData.id_book_record  == 0) {
                                    return false;
                                }
                                if(rowData.isBooked  == 1) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
								viewbookPiece(rowData.id_book_record);
                            }
                        },

                        "sep1": "---------",
                        "select": {
                            name: \'' . $this->l('Tous sélectionner') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 2) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                selgrid' . $this->className . '.selectAll();
                            }
                        },
                        "unselect": {
                            name: \'' . $this->l('Tous désélectionner') . '\',
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
                            name: \'' . $this->l('Supprimer la pièce ') . ' \ :\'+rowData.pieceNumber,
                            icon: "list-ul",
                            visible: function(key, opt){
								 var selected = selgrid' . $this->className . '.getSelection().length
                                var dataLenght = ' . 'grid' . $this->className . '.option(\'dataModel.data\').length;
								 if(selected > 2) {
                                    return false;
                                }

                                if(rowData.isLocked ==false) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteCustomerPiece(rowData.id_customer_piece);
                            }
                        },
						 "bulkdelete": {
                            name: \'' . $this->l('Supprimer les pièces sélectionnées ') . ' \',
                            icon: "list-ul",
                            visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length;
								var pieceSelected = selgrid' . $this->className . '.getSelection();
							  	 var allowed = true;
								 if(selected < 2) {
                                    return false;
                                }
								$.each(pieceSelected, function( index, value ) {
  									if(value.rowData.isLocked == true) {
										allowed = false;
									}

  								});

                               if(allowed == false) {
									return false;
								}
								return true;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteSelectedCustomerPiece(selgrid' . $this->className . '.getSelection());
                            }
                        },

                    },
                };
            }',
			]];

		return parent::generateParaGridScript();
	}

	public function ajaxProcessinitController() {

		return $this->initGridController();
	}

	public function manageFieldsVisibility($fields) {

		return parent::manageFieldsVisibility($fields);
	}

	public function ajaxProcessupdateVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_FIELDS', $this->context->employee->id), true);
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
		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_FIELDS', $headerFields, $this->context->employee->id);
		die($headerFields);
	}

	public function ajaxProcessUpdateDetailVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS', $this->context->employee->id), true);
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
		EmployeeConfiguration::updateValue('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS', $headerFields, $this->context->employee->id);
		die($headerFields);
	}

	public function getCustomerPiecesRequest() {

		$orders = CustomerPieces::getRequest();

		$orderLink = $this->context->link->getAdminLink($this->controller_name);

		foreach ($orders as &$order) {

			$order['pieceNumber'] = $this->getStaticPrefix($order['piece_type']) . $order['piece_number'];

			$order['pieceType'] = $this->pieceType[$order['piece_type']];
			$order['osname'] = '<span class="label color_field" style="background-color:' . $order['color'] . '">' . $order['osname'] . '</span>';

			if (empty($order['paymentMode'])) {
				$order['paymentMode'] = $order['module'];
			}

		}

		$orders = $this->removeRequestFields($orders);

		return $orders;

	}

	public function ajaxProcessgetCustomerPiecesRequest() {

		die(Tools::jsonEncode($this->getCustomerPiecesRequest()));

	}

	public function getCustomerPiecesFields() {

		return [
			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'dataIndx'   => 'id_customer_piece',
				'dataType'   => 'integer',
				'editable'   => false,
				'halign'     => 'HORIZONTAL_CENTER',
				'hiddenable' => 'no',
				'align'      => 'center',
				'hidden'     => false,

			],

			[

				'dataIndx'   => 'pieceType',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => ' ',
				'width'      => 50,
				'dataIndx'   => 'piece_type',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [
					'crules'   => [['condition' => "equal"]],
					'listener' => 'function( evt, ui ){
                        console.log(ui);
                    }',

				],
			],
			[
				'title'    => $this->l('N° de pièce'),
				'width'    => 130,
				'exWidth'  => 40,
				'dataIndx' => 'pieceNumber',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_LEFT',
				'editable' => false,
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],
			],
			[
				'title'    => $this->l('Code Client'),
				'width'    => 130,
				'exWidth'  => 25,
				'dataIndx' => 'customer_code',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
				'editable' => false,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
			],

			[
				'title'    => $this->l('Société'),
				'width'    => 150,
				'exWidth'  => 40,
				'dataIndx' => 'company',
				'halign'   => 'HORIZONTAL_LEFT',
				'valign'   => 'center',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],

				],
			],
			[
				'title'    => $this->l('Client'),
				'width'    => 150,
				'exWidth'  => 25,
				'dataIndx' => 'customer',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'string',
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],
			],

			[
				'title'    => $this->l('Adresse de Livraison'),
				'width'    => 150,
				'exWidth'  => 20,
				'dataIndx' => 'address1',
				'align'    => 'left',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'    => $this->l('Ville de livraison'),
				'width'    => 120,
				'exWidth'  => 20,
				'dataIndx' => 'city',
				'align'    => 'left',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'    => $this->l('Code postal de Livraison'),
				'width'    => 100,
				'exWidth'  => 20,
				'dataIndx' => 'postcode',
				'align'    => 'left',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'editable' => false,
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[

				'dataIndx'   => 'id_country',
				'dataType'   => 'string',
				'editable'   => false,
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [
					'crules' => [['condition' => "equal"]],
				],

			],
			[
				'title'    => $this->l('Pays de livraison'),
				'width'    => 120,
				'exWidth'  => 30,
				'dataIndx' => 'country',
				'halign'   => 'HORIZONTAL_LEFT',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => true,

			],
			[
				'title'    => $this->l('Total Produit HT'),
				'width'    => 150,
				'dataIndx' => 'total_products_tax_excl',
				'align'    => 'right',
				'halign'   => 'HORIZONTAL_RIGHT',
				'editable' => false,
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '# ##0,00 € ' . $this->l('HT'),
				'hiddent'  => true,

			],
			[
				'title'    => $this->l('Frais de port HT.'),
				'width'    => 150,
				'dataIndx' => 'total_shipping_tax_excl',
				'align'    => 'right',
				'halign'   => 'HORIZONTAL_RIGHT',
				'editable' => false,
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '# ##0,00 € ' . $this->l('HT.'),
				'hidden'   => true,
			],
			[
				'title'        => $this->l('Total Produit TTC'),
				'width'        => 150,
				'dataIndx'     => 'total_products_tax_incl',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € " . $this->l('TTC'),

				'summary'      => [
					'type' => '\'sum\'',
				],
				'hidden'       => true,
			],
			[
				'title'        => $this->l('Total HT'),
				'width'        => 150,
				'exWidth'      => 20,
				'dataIndx'     => 'total_tax_excl',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € " . $this->l('HT'),

				'summary'      => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'        => $this->l('TVA'),
				'width'        => 150,
				'exWidth'      => 20,
				'dataIndx'     => 'total_tax',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € " . $this->l('HT'),

				'summary'      => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'        => $this->l('Total TTC'),
				'width'        => 150,
				'exWidth'      => 20,
				'dataIndx'     => 'total',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € " . $this->l('TTC'),

				'summary'      => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'        => $this->l('Total Payé'),
				'width'        => 120,
				'exWidth'      => 20,
				'dataIndx'     => 'total_paid',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 €",
				'summary'      => [
					'type' => '\'sum\'',
				],
			],

			[
				'title'        => $this->l('Balance Due'),
				'width'        => 120,
				'exWidth'      => 20,
				'dataIndx'     => 'balanceDue',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € ",
				'summary'      => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'    => $this->l('Marge de la pièce'),
				'width'    => 120,
				'dataIndx' => 'piece_margin',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'editable' => false,
				'format'   => "#.###,00 € ",
				'summary'  => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'    => $this->l('Mode de Payment'),
				'width'    => 170,
				'exWidth'  => 30,
				'dataIndx' => 'paymentMode',
				'halign'   => 'HORIZONTAL_LEFT',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
				'editable' => false,

			],
			[
				'title'    => $this->l('Etat'),
				'width'    => 150,
				'dataIndx' => 'osname',
				'dataType' => 'html',
				'editable' => false,
				'hidden'   => false,

			],
			[
				'title'    => $this->l('Date de la pièce'),
				'minWidth' => 150,
				'exWidth'  => 20,
				'dataIndx' => 'date_add',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => false,
				'filter'   => [
					'crules' => [['condition' => "between"]],
				],
			],

			[
				'title'    => $this->l('Last Transfert'),
				'width'    => 130,
				'exWidth'  => 40,
				'dataIndx' => 'last_transfert',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_LEFT',
				'dataType' => 'string',
				'hidden'   => true,

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'isLocked',
				'dataType'   => 'string',
				'align'      => 'center',
				'valign'     => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [
					'crules' => [['condition' => "equal"]],
				],

			],
			[
				'title'    => $this->l('Validé'),
				'width'    => 100,
				'exWidth'  => 20,
				'dataIndx' => 'validate',
				'editable' => false,
				'align'    => 'center',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_CENTER',
				'cls'      => 'checkValidate',
				'dataType' => 'html',

			],

			[

				'dataIndx'   => 'id_payment_mode',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'isBooked',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'    => $this->l('Comptabilisé'),
				'width'    => 100,
				'exWidth'  => 20,
				'dataIndx' => 'booked',
				'editable' => false,
				'align'    => 'center',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_CENTER',
				'cls'      => 'checkValidate',
				'dataType' => 'html',

			],

			[
				'dataIndx'   => 'id_customer',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'isLocked',
				'dataType'   => 'string',
				'align'      => 'center',
				'valign'     => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [
					'crules' => [['condition' => "equal"]],
				],

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'id_book_record',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],

		];

	}

	public function ajaxProcessgetCustomerPiecesFields() {

		die(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_FIELDS'));
	}

	public function getDetailCustomerPiecesFields() {

		return [
			[
				'dataIndx'   => 'id_customer_piece_detail',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
				'render'     => 'function(ui){
                    if( ui.rowData.summaryRow ){
                        return "<b>"+ui.cellData+"</b>";
                    }
                }',

			],
			[
				'dataIndx'   => 'id_customer_piece',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'id_product',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'id_product_attribute',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'    => $this->l('Réference'),
				'width'    => 120,
				'dataIndx' => 'product_reference',
				'dataType' => 'string',
				'cls'      => '',
				'editable' => true,
				'editor'   => [
					'type' => "textbox",
					'init' => 'autoCompleteProduct',
				],
			],
			[
				'title'    => $this->l('Produit'),
				'width'    => 180,
				'align'    => 'left',
				'dataIndx' => 'product_name',
				'dataType' => 'string',
				'editable' => true,
			],

			[
				'title'    => $this->l('Quantité'),
				'width'    => 80,
				'dataIndx' => 'product_quantity',
				'align'    => 'center',
				'dataType' => 'integer',
				'editable' => true,
			],
			[
				'title'    => $this->l('Prix d‘achat'),
				'width'    => 120,
				'dataIndx' => 'product_wholesale_price',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '# ##0,00 €' . $this->l('HT.'),
				'hidden'   => false,
			],
			[
				'dataIndx'   => 'original_price_tax_excl',
				'hidden'     => true,
				'hiddenable' => 'no',
				'dataType'   => 'float',
			],
			[
				'title'    => $this->l('Prix de Base'),
				'width'    => 120,
				'dataIndx' => 'unit_tax_excl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('HT.'),

			],
			[
				'title'    => $this->l('Réduction %'),
				'width'    => 120,
				'dataIndx' => 'reduction_percent',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.## %",
			],
			[
				'title'    => $this->l('Réduction HT'),
				'width'    => 120,
				'dataIndx' => 'reduction_amount_tax_excl',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('HT.'),
			],
			[
				'title'    => $this->l('Réduction TTC'),
				'width'    => 120,
				'dataIndx' => 'reduction_amount_tax_incl',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('TTC.'),
				'hidden'   => true,
			],
			[
				'title'    => $this->l('Prix TTC.'),
				'width'    => 120,
				'dataIndx' => 'unit_tax_incl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('TTC.'),
			],
			[
				'title'    => $this->l('Eco Tax'),
				'width'    => 100,
				'dataIndx' => 'ecotax',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 €",
				'hidden'   => true,
			],
			[
				'title'    => $this->l('TVA'),
				'width'    => 100,
				'dataIndx' => 'tax_rate',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.## %",
				'align'    => 'center',

			],
			[
				'title'    => $this->l('Total HT.'),
				'width'    => 120,
				'dataIndx' => 'total_tax_excl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '#.###,00 €' . $this->l('HT.'),
				'summary'  => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'    => $this->l('TVA'),
				'width'    => 120,
				'dataIndx' => 'total_tax',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '#.###,00 €' . $this->l('HT.'),
				'summary'  => [
					'type' => '\'sum\'',
				],
			],
			[
				'title'    => $this->l('Total TTC.'),
				'width'    => 120,
				'dataIndx' => 'total_tax_incl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('TTC.'),
				'summary'  => [
					'type' => '\'sum\'',
				],
			],
			[
				'dataIndx'   => 'product_ean13',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'product_upc',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'id_tax_rules_group',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'product_weight',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'origin_total_tax_excl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'wholesale_total_tax_excl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'line_margin_total_tax_excl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'total_tax_excl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'original_price_tax_incl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'original_price_tax_excl',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',

			],

		];
	}

	public function ajaxProcessGetDetailCustomerPiecesFields() {

		die(EmployeeConfiguration::get('EXPERT_CUSTOMERPIECES_DETAIL_FIELDS'));
	}

	public function getDetailCustomerPiecesRequest($idCustomerPiece) {

		$transferts = ['QUOTATION', 'ORDER', 'DELIVERYFORM'];

		$piece = new CustomerPieces($idCustomerPiece);

		if (in_array($piece->piece_type, $transferts) && $piece->last_transfert > 0) {
			$idPiece = CustomerPieces::getPieceIdbyTransfert($piece->last_transfert);
		} else {
			$idPiece = $piece->id;
		}

		$details = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('customer_piece_detail')
				->where('`id_customer_piece` = ' . $idPiece)
				->orderBy('`id_customer_piece_detail` ASC')
		);

		foreach ($details as &$detail) {
			//$detail['tax_rate'] = $detail['tax_rate'] / 100;
		}

		if (!empty($details)) {
			$details = $this->removeDetailRequestFields($details);
		}

		return $details;
	}

	public function ajaxProcessGetDetailCustomerPiecesRequest() {

		$idPiece = Tools::getValue('idPiece');
		die(Tools::jsonEncode($this->getDetailCustomerPiecesRequest($idPiece)));

	}

	public function removeDetailRequestFields($requests) {

		$objects = [];

		$fields = [];
		$gridFields = $this->getDetailCustomerPiecesFields();

		foreach ($gridFields as $grifField) {
			$fields[] = $grifField['dataIndx'];
		}

		foreach ($requests as $key => $object) {

			foreach ($object as $field => $value) {

				if (in_array($field, $fields)) {
					$objects[$key][$field] = $value;
				}

			}

		}

		return $objects;

	}

	public static function setCustomerPiecesCurrency($echo, $tr) {

		$order = new CustomerPieces($tr['id_order']);

		return Tools::displayPrice($echo, (int) $order->id_currency);
	}

	public function ajaxProcessValidateCustomerPieces() {

		$id_customer_piece = Tools::getValue('id_customer_piece');
		$this->object = new $this->className($id_customer_piece);
		$this->object->validate = 1;
		$this->object->update();
		$result = [
			'success' => true,
			'message' => $this->l('La pièce a été validée avec succès '),
		];

		die(Tools::jsonEncode($result));

	}

	public function getInvoicesModels() {

		$templates = new PhenyxShopCollection('InvoiceModel');

		$models = [];

		foreach ($templates as $template) {
			$models[] = [
				'value' => $template->id,
				'name'  => ucfirst(strtolower(str_replace('_', ' ', str_replace('EPH_TEMPLATE_', '', $template->name)))),
			];
		}

		return $models;
	}

	public function ajaxProcessDeleteCustomerPiece() {

		$id_customer_piece = Tools::getValue('id_customer_piece');
		$this->object = new $this->className($id_customer_piece);
		$this->object->delete();

		$result = [
			'success' => true,
			'message' => $this->l('La pièce à été supprimée avec succès'),
		];
		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessBulkDelete() {

		$pieces = Tools::getValue('pieces');

		foreach ($pieces as $key => $id_customer_piece) {
			$this->object = new $this->className($id_customer_piece);
			$this->object->delete();
		}

		$result = [
			'success' => true,
			'message' => $this->l('Les pièces clients ont été supprimées avec succès'),
		];
		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessEditObject() {

		$id_customer_piece = Tools::getValue('idObject');
		$this->object = new $this->className($id_customer_piece);
		$this->object->prefix = $this->getStaticPrefix($this->object->piece_type);
		$this->object->nameType = $this->getStaticPieceName($this->object->piece_type);
		$customerAddress = '';
		$returnAddress = [];

		$order_status = $this->object->current_state;

		$creditcard = null;
		$isCard = false;

		if ($order_status == Configuration::get('OS_EPH_CC')) {

			$creditcard = CustomerPieces::getCreditCardInfo($this->object->id);
			$isCard = true;
		}

		$sepa = null;
		$isSepa = false;

		if ($order_status == Configuration::get('OS_EPH_BANK')) {

			$sepa = CustomerPieces::getSepaInfo($this->object->id);
			$isSepa = true;
		}

		$customer = new Customer($this->object->id_customer);

		$firstAddress = Address::getFirstCustomerAddressId($customer->id);

		if (!empty($firstAddress)) {
			$address = new Address($firstAddress);
			$customerAddress = $address->getAddresRequest($this->context->language->id);
		}

		$address = new Address($this->object->id_address_delivery);

		if (!empty($address)) {
			$this->object->id_address_delivery = $address->getAddresRequest($this->context->language->id);
		} else {
			$this->object->id_address_delivery = $customerAddress;
		}

		$address = new Address($this->object->id_address_invoice);

		if (!empty($address)) {
			$this->object->id_address_invoice = $address->getAddresRequest($this->context->language->id);
		} else {
			$this->object->id_address_invoice = $customerAddress;
		}

		$customerLastAddress = $this->getLastCustomerAddressId((int) $customer->id);
		$addresses = $customer->getAddresses($this->context->language->id);

		foreach ($addresses as $key => $addresse) {
			$returnAddress[$addresse['id_address']] = $addresse;
		}

		$deliveryAddress = new Address($this->object->id_address_delivery);
		$invoiceAddress = new Address($this->object->id_address_invoice);

		$data = $this->createTemplate('controllers/customer_pieces/editCustomerPiece.tpl');

		$this->context->smarty->assign([
			'piece'               => $this->object,
			'creditcard'          => $creditcard,
			'isCard'              => $isCard,
			'isSepa'              => $isSepa,
			'sepa'                => $sepa,
			'nameType'            => $this->pieceType[$this->object->piece_type],
			'customer'            => $customer,
			'taxModes'            => TaxMode::getTaxModes(),
			'currency'            => $context->currency,
			'taxes'               => Tax::getRulesTaxes($this->context->language->id),
			'groups'              => Group::getGroups($this->context->language->id),
			'deliveryAddress'     => $deliveryAddress,
			'invoiceAddress'      => $invoiceAddress,
			'addresses'           => $addresses,
			'states'              => CustomerPieceState::getCustomerPieceStates($this->context->language->id),
			'jsonAddresses'       => Tools::jsonEncode($addresses),
			'customerLastAddress' => $customerLastAddress,
			'customHeaderFields'  => $this->manageFieldsVisibility($this->configurationDetailField),
			'paymentModes'        => PaymentMode::getPaymentModes(),
			'invoiceModels'       => $this->getInvoicesModels(),

		]);

		$li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Modification de la : ' . $this->pieceType[$this->object->piece_type] . ' ' . $this->object->prefix . $this->object->piece_number . '</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'success' => true,
			'li'      => $li,
			'html'    => $html,
		];

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessGenerateNewPiece() {

		$this->controller_name = 'AdminCustomerPieces';
		$type = Tools::getValue('type');
		$piece = new CustomerPieces();
		$prefix = $this->getStaticPrefix($type);
		$increment = CustomerPieces::getIncrementByType($type);
		$piece->piece_number = $increment;

		$data = $this->createTemplate('controllers/customer_pieces/newPiece.tpl');

		$this->context->smarty->assign([
			'type'               => $type,
			'nameType'           => $this->pieceType[$type],
			'piece'              => $piece,
			'piece_number'       => $prefix . $increment,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationDetailField),
			'taxModes'           => TaxMode::getTaxModes(),
			'currency'           => $this->context->currency,
			'taxes'              => Tax::getRulesTaxes($this->context->language->id),
			'groups'             => Group::getGroups($this->context->language->id),
			'paymentModes'       => PaymentMode::getPaymentModes(),
			'link'               => $this->context->link,
			'id_tab'             => $this->identifier_value,
			'formId'             => 'form-customer',
		]);

		$li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ajouter une pièce client de type : ' . $this->pieceType[$type] . '</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcesscreateNewCustomer() {

		$data = $this->createTemplate('controllers/customer_pieces/newCustomer.tpl');

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

		$result = [
			'html' => $data->fetch(),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessUpdateCustomerPiece() {

		$error = false;

		$idPiece = Tools::getValue('id_customer_piece');
		$piece = new CustomerPieces($idPiece);

		$pieceDetails = Tools::jsonDecode(Tools::getValue('details'), true);

		foreach ($_POST as $key => $value) {

			if (property_exists($piece, $key) && $key != 'id_customer_piece') {

				$piece->{$key}

				= $value;
			}

		}

		try {

			$result = $piece->update();

		} catch (Exception $e) {
			fwrite($file, "Error : " . $e->getMessage() . PHP_EOL);

		}

		if ($result) {

			foreach ($pieceDetails as $details) {

				if ($details['id_customer_piece_detail'] > 0) {
					$object = new CustomerPieceDetail($details['id_customer_piece_detail']);

					foreach ($details as $key => $value) {

						if (property_exists($object, $key) && $key != 'id_customer_piece_detail') {

							if ($key == 'tax_rate') {
								$value = $value;
							}

							$object->{$key}

							= $value;
						}

					}

					$result = $object->update();

					if (!$result) {
						$error = true;
					}

				} else {
					$object = new CustomerPieceDetail();
					$object->id_customer_piece = $newPiece->id;
					$object->id_warehouse = 0;

					foreach ($details as $key => $value) {

						if (property_exists($object, $key) && $key != 'id_customer_piece') {

							if ($key == 'tax_rate') {
								$value = $value * 100;
							}

							$object->{$key}

							= $value;
						}

					}

					//$result = $object->add();

					if (!$result) {
						$error = true;
					}

				}

			}

		}

		if ($error) {
			$result = [
				'success' => false,
				'message' => 'Le webmaster à merdé',
			];
		} else {
			$result = [
				'success' => true,
				'message' => 'La pièce commerciale a été mise à jour avec succès',
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessSaveNewPiece() {

		$context = Context::getContext();
		$pieceDetails = Tools::jsonDecode(Tools::getValue('details'), true);

		$newPiece = new CustomerPieces();

		foreach ($_POST as $key => $value) {

			if (property_exists($newPiece, $key) && $key != 'id_customer_piece') {

				$newPiece->{$key}

				= $value;
			}

		}

		$newPiece->id_shop = (int) $context->shop->id;
		$newPiece->id_shop_group = (int) $context->shop->id_shop_group;
		$newPiece->id_lang = $context->language->id;
		$newPiece->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
		$newPiece->round_type = Configuration::get('PS_ROUND_TYPE');
		$newPiece->date_add = date("Y-m-d H:i:s");

		try {

			$result = $newPiece->add();

		} catch (Exception $e) {

		}

		if ($result) {

			foreach ($pieceDetails as $details) {
				$object = new CustomerPieceDetail();
				$object->id_customer_piece = $newPiece->id;
				$object->id_warehouse = 0;

				foreach ($details as $key => $value) {

					if (property_exists($object, $key) && $key != 'id_customer_piece') {
						$object->{$key}

						= $value;
					}

				}

				if (!$object->add()) {
					$this->errors[] = Tools::displayError('An error occurred while creating an object.') . ' <strong> piece detail (' . Db::getInstance()->getMsgError() . ')</strong>';
					Logger::addLog('piece detail (' . Db::getInstance()->getMsgError() . ')', 2, null, $this->className, null, true, (int) $this->context->employee->id);
				}

			}

		} else {
			$response = [
				'success' => false,
				'message' => $this->errors[] = Tools::displayError('An error occurred while creating an object.') . ' <strong>' . $this->table . ' (' . Db::getInstance()->getMsgError() . ')</strong>',
			];
		}

		if (count($this->errors)) {
			$this->errors = array_unique($this->errors);
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {
			$result = [
				'success' => true,
				'message' => $this->l('New pieces successfully added'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessgetAutoCompleteCustomer() {

		$customers = Db::getInstance()->executeS(
			(new DbQuery())
				->select('c.`id_customer`, c.`customer_code`, c.`lastname`, c.`firstname`')
				->from('customer', 'c')
				->join(Shop::addSqlAssociation('customer', 'c'))
				->where('c.`active` = 1')
		);

		die(Tools::jsonEncode($customers));
	}

	public function ajaxProcessGetCustomerInformation() {

		$idCustomer = Tools::getValue('idCustomer');
		$customer = new Customer($idCustomer);
		$customerLastAddress = $this->getLastCustomerAddressId((int) $customer->id);
		$addresses = $customer->getAddresses($this->context->language->id);
		$html = '<option value="0">' . $this->l('Select Address') . '</option>';

		foreach ($addresses as $addresse) {
			$html .= '<option value="' . $addresse['id_address'] . '">' . $addresse['alias'] . '</option>';
		}

		$returnAddress = [];

		foreach ($addresses as $key => $addresse) {
			$returnAddress[$addresse['id_address']] = $addresse;
		}

		$result = [
			'customer'            => (array) $customer,
			'selectAddress'       => $html,
			'addresses'           => $returnAddress,
			'customerLastAddress' => $customerLastAddress,
		];
		die(Tools::jsonEncode($result));
	}

	public function getLastCustomerAddressId($id_customer, $active = true) {

		if (!$id_customer) {
			return false;
		}

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`id_address`')
				->from('address')
				->where('`deleted` = 0 AND `id_customer` = ' . (int) $id_customer)
				->orderBy('`date_add` DESC')
		);
	}

	public function ajaxProcessAutoCompleteProduct() {

		$keyword = Tools::getValue('keyword', false);
		$context = Context::getContext();
		$idCustomer = Tools::getValue('idCustomer');
		$customer = new Customer($idCustomer);

		$items = Db::getInstance()->executeS(
			(new DbQuery())
				->select('p.`id_product`, p.`id_tax_rules_group`, p.`reference`, pl.`name`, p.`wholesale_price`, p.`price`, p.`ecotax`, p.`weight`, p.`ean13`, p.`upc`, t.rate, p.`cache_default_attribute` as `id_product_attribute`')
				->from('product', 'p')
				->join(Shop::addSqlAssociation('product', 'p'))
				->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int) $context->language->id)
				->leftJoin('tax_rules_group', 'tl', 'tl.`id_tax_rules_group` = p.`id_tax_rules_group`')
				->leftJoin('tax', 't', 't.`id_tax` = tl.`id_tax_rules_group`')
				->where('(pl.name LIKE \'%' . pSQL($keyword) . '%\' OR p.reference LIKE \'%' . pSQL($keyword) . '%\') AND p.`active` = 1')
				->groupBy('p.`id_product`')
		);

		if ($items) {

			foreach ($items as &$item) {

				$item['price'] = Product::getPriceStatic($item['id_product'], false, null, 6, null, false, true, 1, false, $idCustomer);
			}

			$results = [];

			foreach ($items as $item) {

				if (Combination::isFeatureActive() && $item['id_product_attribute']) {

					$combinations = Db::getInstance()->executeS(
						(new DbQuery())
							->select('pa.`id_product_attribute`, pa.`reference`, pa.`wholesale_price`, pa.`price`, pa.`ecotax`, ag.`id_attribute_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
                        a.`id_attribute`')
							->from('product_attribute', 'pa')
							->join(Shop::addSqlAssociation('product_attribute', 'pa'))
							->leftJoin('product_attribute_combination', 'pac', 'pac.`id_product_attribute` = pa.`id_product_attribute`')
							->leftJoin('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')
							->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $context->language->id)
							->leftJoin('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')
							->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $context->language->id)
							->where('pa.`id_product` = ' . (int) $item['id_product'])
							->groupBy('pa.`id_product_attribute`, ag.`id_attribute_group`')
							->orderBy('pa.`id_product_attribute`')
					);

					if (!empty($combinations)) {

						foreach ($combinations as $k => $combination) {
							$result = [];
							$result['id_product'] = $item['id_product'];
							$result['id_product_attribute'] = $combination['id_product_attribute'];
							$result['reference'] = $combination['reference'];
							$result['wholesale_price'] = $combination['wholesale_price'] + $item['wholesale_price'];

							$result['price'] = Product::getPriceStatic($item['id_product'], false, $combination['id_product_attribute'], 6, null, false, true, 1, false, $idCustomer);
							$result['ecotax'] = $combination['ecotax'] + $item['ecotax'];
							$result['id_product_attribute'] = $item['id_product_attribute'];
							$result['ean13'] = $item['ean13'];
							$result['upc'] = $item['upc'];
							$result['weight'] = $combination['weight'] + $item['weight'];
							$result['rate'] = $item['rate'];
							$result['name'] = $item['name'] . ' ' . $combination['group_name'] . '-' . $combination['attribute_name'];
							array_push($results, $result);

						}

					} else {
						array_push($results, $item);

					}

				} else {

					array_push($results, $item);
				}

			}

			$results = array_map("unserialize", array_unique(array_map("serialize", $results)));

			$results = Tools::jsonEncode($results, JSON_NUMERIC_CHECK);

			die($results);
		} else {

			$items = Db::getInstance()->executeS(
				(new DbQuery())
					->select('p.`id_formatpack`, p.`id_tax_rules_group`, p.`reference`, pl.`name`, p.`wholesale_price`, p.`price`, t.rate')
					->from('formatpack', 'p')
					->leftJoin('formatpack_lang', 'pl', 'pl.id_formatpack = p.id_formatpack AND pl.id_lang = ' . (int) $context->language->id)
					->leftJoin('tax_rules_group', 'tl', 'tl.`id_tax_rules_group` = p.`id_tax_rules_group`')
					->leftJoin('tax', 't', 't.`id_tax` = tl.`id_tax_rules_group`')
					->where('(pl.name LIKE \'%' . pSQL($keyword) . '%\' OR p.reference LIKE \'%' . pSQL($keyword) . '%\') AND p.`active` = 1')
					->groupBy('p.`id_formatpack`')
			);

			foreach ($items as &$item) {
				$item['rate'] = $item['rate'] / 100;
			}

			$results = [];

			foreach ($items as $item) {
				array_push($results, $item);
			}

			$results = array_map("unserialize", array_unique(array_map("serialize", $results)));

			$results = Tools::jsonEncode($results, JSON_NUMERIC_CHECK);

			die($results);
		}

		if (!$items) {
			json_encode(new stdClass);
		}

	}

	public function getStaticPrefix($pieceType) {

		switch ($pieceType) {

		case 'QUOTATION':
			return $this->l('DE');
			break;
		case 'ORDER':
			return $this->l('CD');
			break;
		case 'DELIVERYFORM':
			return $this->l('BL');
			break;
		case 'DOWNPINVOICE':
			return $this->l('FAC');
			break;
		case 'INVOICE':
			return $this->l('FA');
			break;
		case 'ASSET':
			return $this->l('AV');
			break;
		}

	}

	public function getStaticPieceName($pieceType) {

		switch ($pieceType) {

		case 'QUOTATION':
			return $this->l('Devis');
			break;
		case 'ORDER':
			return $this->l('Commande');
			break;
		case 'DELIVERYFORM':
			return $this->l('Bon de Livraison');
			break;
		case 'DOWNPINVOICE':
			return $this->l('Facture Accompte');
			break;
		case 'INVOICE':
			return $this->l('Facture');
			break;
		case 'ASSET':
			return $this->l('Avoir');
			break;
		}

	}

	public function ajaxProcessMergeOrderTable() {

		$idOrder = Tools::getValue('idOrder');
		$nbOrder = Tools::getValue('numberOrder');

		if (CustomerPieces::mergeOrderTable($idOrder)) {
			$response = [
				'success' => true,
				'message' => $this->l('Order ') . ' ' . $idOrder . ' ' . $this->l(' has been successfully merged'),
			];
		} else {
			$response = [
				'success' => false,
				'message' => $this->l('Order ') . ' ' . $idOrder . ' ' . $this->l(' has not been merged'),
			];
		}

		die(Tools::jsonEncode($response));
	}

	public function ajaxProcessGenerateReglement() {

		$idPiece = Tools::getValue('idPiece');
		$piece = new CustomerPieces($idPiece);
		$customer = new Customer($piece->id_customer);

		$payment = new Payment();
		$payment->payment_number = Payment::getIncrement();

		$data = $this->createTemplate('controllers/payment/payment.tpl');

		$pieces = $customer->getCustomerBalancedPiece();

		$fields = [
			[

				'dataIndx' => 'id_customer_piece',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[

				'dataIndx' => 'piece_type',
				'dataType' => 'string',
				'hidden'   => true,
			],
			[
				'title'    => $this->l('N° de pièce'),
				'width'    => 100,
				'dataIndx' => 'piece_number',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'string',

			],
			[
				'title'    => $this->l('Type pièce'),
				'width'    => 100,
				'dataIndx' => 'type',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'string',

			],

			[
				'title'    => $this->l('Montant'),
				'width'    => 150,
				'dataIndx' => 'total_tax_incl',
				'dataType' => 'float',
				'align'    => 'right',
				'valign'   => 'center',
				'format'   => "#.###,00 € " . $this->l('TTC'),

			],
			[
				'title'    => $this->l('Réglé'),
				'width'    => 150,
				'dataIndx' => 'total_paid',
				'dataType' => 'float',
				'align'    => 'right',
				'valign'   => 'center',
				'format'   => "#.###,00 € " . $this->l('TTC'),

			],
			[
				'title'    => $this->l('Solde dû'),
				'width'    => 150,
				'dataIndx' => 'balance_due',
				'dataType' => 'float',
				'align'    => 'right',
				'valign'   => 'center',
				'format'   => "#.###,00 € " . $this->l('TTC'),

			],

		];

		$data->assign([
			'customer'     => $customer,
			'payment'      => $payment,
			'currency'     => $this->context->currency,
			'sign'         => $this->context->currency->sign,
			'pieces'       => Tools::jsonEncode($pieces),
			'fields'       => Tools::jsonEncode($fields),
			'piece'        => $piece,
			'paymentModes' => PaymentMode::getPaymentModes(),
		]);

		$return = [
			'html' => $data->fetch(),
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddnewPayment() {

		$total_paid = abs(Tools::getValue('total_paid'));
		$id_payment_mode = Tools::getValue('id_payment_mode');
		$paymentMode = new PaymentMode($id_payment_mode);
		$customer = new Customer(Tools::getValue('id_customer'));
		$balance_due = 0;
		$invoices = [];
		$pieceDetails = Tools::jsonDecode(Tools::getValue('details'), true);

		foreach ($pieceDetails as $detail) {

			foreach ($detail['rowData'] as $key => $value) {

				if ($key == 'id_customer_piece') {
					$piece = new CustomerPieces($value);
					$invoices[] = $piece;
					$balance_due = $balance_due + $piece->balance_due;
				}

			}

		}

		$asset_amount = bcsub($total_paid, $balance_due, 2);
		$piecePayment = new Payment();
		$piecePayment->id_currency = $this->context->currency->id;
		$piecePayment->id_customer = $customer->id;
		$piecePayment->amount = $total_paid;
		$piecePayment->id_payment_mode = $paymentMode->id;
		$piecePayment->payment_method = $paymentMode->name[$this->context->language->id];
		$piecePayment->booked = 0;
		$success = $piecePayment->add();

		if ($asset_amount == 0) {

			if ($success) {

				foreach ($invoices as $piece) {
					fwrite($file, $piece->id . PHP_EOL);
					$paymentDetail = new PaymentDetails();
					$paymentDetail->id_payment = $piecePayment->id;
					$paymentDetail->id_customer_piece = $piece->id;
					$paymentDetail->amount = $piece->total_tax_incl;
					$paymentDetail->date_add = $piecePayment->date_add;
					try {
						$success = $paymentDetail->add();
					} catch (Exception $ex) {

					}

					if ($success) {
						$piece->total_paid = $piece->total_tax_incl;
						$piece->update();
					}

				}

			}

		} else
		if ($asset_amount > 0) {

			if ($success) {

				foreach ($invoices as $piece) {
					$paymentDetail = new PaymentDetails();
					$paymentDetail->id_payment = $piecePayment->id;
					$paymentDetail->id_customer_piece = $piece->id;
					$paymentDetail->amount = $piece->total_tax_incl;
					$paymentDetail->date_add = $piecePayment->date_add;
					try {
						$success = $paymentDetail->add();
					} catch (Exception $ex) {

					}

					if ($success) {
						$piece->total_paid = $piece->total_tax_incl;
						$piece->update();
					}

				}

				CustomerPieces::generateFinancialAsset($asset_amount, $customer);

			}

		} else
		if ($asset_amount < 0) {

			if ($success) {

				foreach ($invoices as $piece) {

					if ($total_paid > $piece->total_tax_incl) {
						$amount = $piece->total_tax_incl;
						$total_paid = $total_paid - $amount;
					} else {
						$amount = $total_paid;
					}

					$paymentDetail = new PaymentDetails();
					$paymentDetail->id_payment = $piecePayment->id;
					$paymentDetail->id_customer_piece = $piece->id;
					$paymentDetail->amount = $amount;
					$paymentDetail->date_add = $piecePayment->date_add;
					try {
						$success = $paymentDetail->add();
					} catch (Exception $ex) {

					}

					if ($success) {
						$piece->total_paid = $amount;
						$piece->update();
					}

				}

			}

		}

		$return = [
			'success' => true,
			'message' => 'le réglement & été enregistré avec succès',
		];

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessOldGenerateReglement() {

		$idPiece = Tools::getValue('idPiece');
		$piece = new CustomerPieces($idPiece);
		$piece->generateReglement();
		$piece->total_paid = $piece->total_tax_incl;
		$piece->update();

		$return = [
			'success' => true,
			'message' => 'Le payement a été correctement enregistré',
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessGenerateBulkReglement() {

		$pieces = Tools::getValue('pieces');
		$targetVatAccount = StdAccount::getAccountByName('445717');
		$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));

		foreach ($pieces as $key => $idPiece) {
			$studentPiece = new CustomerPieces($idPiece);
			$paymentMode = new PaymentMode($studentPiece->id_payment_mode);
			$bank = new BankAccount($paymentMode->id_bank_account);
			$bankAccount = new StdAccount($bank->id_stdaccount);
			$student = new Customer($studentPiece->id_customer);

			$dateAdd = date('Y-m-d');
			$account = new StdAccount($student->id_stdaccount);
			$reglement = 'Règlement Client';

			$error = false;

			$piecePayment = new Payment();
			$piecePayment->id_currency = $studentPiece->id_currency;
			$piecePayment->amount = $studentPiece->total_tax_incl;
			$piecePayment->id_payment_mode = $studentPiece->id_payment_mode;
			$piecePayment->booked = 0;
			$piecePayment->date_add = $dateAdd;

			if ($piecePayment->add()) {
				$paymentDetail = new PaymentDetails();
				$paymentDetail->id_payment = $piecePayment->id;
				$paymentDetail->id_student_piece = $studentPiece->id;
				$paymentDetail->id_customer_piece = $studentPiece->id;
				$paymentDetail->amount = $studentPiece->total_tax_incl;
				$paymentDetail->date_add = $piecePayment->date_add;
				$paymentDetail->add();
				$studentPiece->total_paid = $studentPiece->total_tax_incl;
				$studentPiece->update();
				$record = new BookRecords();
				$record->id_book_diary = 2;
				$record->name = 'Réglement de la Facture N° FA' . $studentPiece->piece_number;
				$record->piece_type = $reglement;
				$record->date_add = $dateAdd;
				$success = $record->add();

				if ($success) {
					$piecePayment->id_book_record = $record->id;
					$piecePayment->booked = 1;
					$piecePayment->update();
					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $bankAccount->id;
					$detail->libelle = "Facture " . $studentPiece->prefix . $studentPiece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
					$detail->piece_number = $studentPiece->id;
					$detail->debit = $studentPiece->total_tax_incl;
					$detail->date_add = $record->date_add;
					$detail->add();
					$bankAccount->pointed_solde = $bankAccount->pointed_solde + $studentPiece->total_tax_incl;
					$bankAccount->update();

					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $account->id;
					$detail->libelle = "Facture " . $studentPiece->prefix . $studentPiece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
					$detail->piece_number = $studentPiece->id;
					$detail->credit = $studentPiece->total_tax_incl;
					$detail->date_add = $record->date_add;
					$detail->add();

					$account->pointed_solde = $account->pointed_solde - $studentPiece->total_tax_incl;
					$account->update();

					$record = new BookRecords();
					$record->id_book_diary = 5;
					$record->name = 'Transfert TVA FA' . $studentPiece->piece_number;
					$record->piece_type = 'Opération sur TVA';
					$record->date_add = $dateAdd;
					$success = $record->add();

					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $vatAccount->id;
					$detail->libelle = "Facture " . $studentPiece->prefix . $studentPiece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
					$detail->piece_number = $studentPiece->id;
					$detail->debit = $studentPiece->total_tax;
					$detail->date_add = $record->date_add;
					$detail->add();
					$vatAccount->pointed_solde = $vatAccount->pointed_solde + $studentPiece->total_tax;
					$vatAccount->update();

					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $targetVatAccount->id;
					$detail->libelle = "Facture " . $studentPiece->prefix . $studentPiece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
					$detail->piece_number = $piece->id;
					$detail->credit = $studentPiece->total_tax;
					$detail->date_add = $record->date_add;
					$detail->add();
					$targetVatAccount->pointed_solde = $targetVatAccount->pointed_solde - $studentPiece->total_tax;
					$targetVatAccount->update();
				}

			} else {
				$error = true;
			}

		}

		if (!$error) {
			$return = [
				'success' => true,
				'message' => 'Les payements ont été correctement enregistrés',
			];
		} else {
			$return = [
				'success' => false,
				'message' => 'Jeff a merdé',
			];
		}

		die(Tools::jsonEncode($return));

	}

	public function processBulkUpdateCustomerPiecesStatus() {

		if (Tools::isSubmit('submitUpdateCustomerPiecesStatus') && ($idCustomerPiecesState = (int) Tools::getValue('id_order_state'))) {

			if ($this->tabAccess['edit'] !== '1') {
				$this->errors[] = Tools::displayError('You do not have permission to edit this.');
			} else {
				$orderState = new CustomerPiecesState($idCustomerPiecesState);

				if (!Validate::isLoadedObject($orderState)) {
					$this->errors[] = sprintf(Tools::displayError('CustomerPieces status #%d cannot be loaded'), $idCustomerPiecesState);
				} else {

					foreach (Tools::getValue('orderBox') as $idCustomerPieces) {
						$order = new CustomerPieces((int) $idCustomerPieces);

						if (!Validate::isLoadedObject($order)) {
							$this->errors[] = sprintf(Tools::displayError('CustomerPieces #%d cannot be loaded'), $idCustomerPieces);
						} else {
							$currentCustomerPiecesState = $order->getCurrentCustomerPiecesState();

							if ($currentCustomerPiecesState->id == $orderState->id) {
								$this->errors[] = $this->displayWarning(sprintf('CustomerPieces #%d has already been assigned this status.', $idCustomerPieces));
							} else {
								$history = new CustomerPiecesHistory();
								$history->id_order = $order->id;
								$history->id_employee = (int) $this->context->employee->id;

								// Since we have an order there should already be a payment
								// If there is no payment and the order status is `logable`
								// then the order payment will be generated automatically
								$history->changeIdCustomerPiecesState((int) $orderState->id, $order, !$order->hasInvoice());

								$carrier = new Carrier($order->id_carrier, $order->id_lang);
								$customer = new Customer($order->id_customer);

								if (Validate::isLoadedObject($customer)) {
									$firstname = $customer->firstname;
									$lastname = $customer->lastname;
								} else {
									$firstname = '';
									$lastname = '';
								}

								$templateVars = [
									'{firstname}'        => $firstname,
									'{lastname}'         => $lastname,
									'{id_order}'         => $order->id,
									'{order_name}'       => $order->getUniqReference(),
									'{bankwire_owner}'   => (string) Configuration::get('BANK_WIRE_OWNER'),
									'{bankwire_details}' => (string) nl2br(Configuration::get('BANK_WIRE_DETAILS')),
									'{bankwire_address}' => (string) nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
								];

								if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
									$templateVars = [
										'{followup}'        => str_replace('@', $order->shipping_number, $carrier->url),
										'{shipping_number}' => $order->shipping_number,
									];
								}

								if ($history->addWithemail(true, $templateVars)) {

									if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {

										foreach ($order->getProducts() as $product) {

											if (StockAvailable::dependsOnStock($product['product_id'])) {
												StockAvailable::synchronize($product['product_id'], (int) $product['id_shop']);
											}

										}

									}

								} else {
									$this->errors[] = sprintf(Tools::displayError('Cannot change status for order #%d.'), $idCustomerPieces);
								}

							}

						}

					}

				}

			}

			if (!count($this->errors)) {
				Tools::redirectAdmin(static::$currentIndex . '&conf=4&token=' . $this->token);
			}

		}

	}

	public function ajaxProcessSearchProducts() {

		$this->context->customer = new Customer((int) Tools::getValue('id_customer'));
		$currency = new Currency((int) Tools::getValue('id_currency'));

		if ($products = Product::searchByName((int) $this->context->language->id, pSQL(Tools::getValue('product_search')))) {

			foreach ($products as &$product) {
				// Formatted price
				$product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $currency), $currency);
				// Concret price
				$product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_incl'], $currency), 2);
				$product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), 2);
				$productObj = new Product((int) $product['id_product'], false, (int) $this->context->language->id);
				$combinations = [];
				$attributes = $productObj->getAttributesGroups((int) $this->context->language->id);

				// Tax rate for this customer

				if (Tools::isSubmit('id_address')) {
					$product['tax_rate'] = $productObj->getTaxesRate(new Address(Tools::getValue('id_address')));
				}

				$product['warehouse_list'] = [];

				foreach ($attributes as $attribute) {

					if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
						$combinations[$attribute['id_product_attribute']]['attributes'] = '';
					}

					$combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';
					$combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
					$combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];

					if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
						$priceTaxIncl = Product::getPriceStatic((int) $product['id_product'], true, $attribute['id_product_attribute']);
						$priceTaxExcl = Product::getPriceStatic((int) $product['id_product'], false, $attribute['id_product_attribute']);
						$combinations[$attribute['id_product_attribute']]['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($priceTaxIncl, $currency), 2);
						$combinations[$attribute['id_product_attribute']]['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($priceTaxExcl, $currency), 2);
						$combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($priceTaxExcl, $currency), $currency);
					}

					if (!isset($combinations[$attribute['id_product_attribute']]['qty_in_stock'])) {
						$combinations[$attribute['id_product_attribute']]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct((int) $product['id_product'], $attribute['id_product_attribute'], (int) $this->context->shop->id);
					}

					if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1) {
						$product['warehouse_list'][$attribute['id_product_attribute']] = Warehouse::getProductWarehouseList($product['id_product'], $attribute['id_product_attribute']);
					} else {
						$product['warehouse_list'][$attribute['id_product_attribute']] = [];
					}

					$product['stock'][$attribute['id_product_attribute']] = Product::getRealQuantity($product['id_product'], $attribute['id_product_attribute']);
				}

				if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1) {
					$product['warehouse_list'][0] = Warehouse::getProductWarehouseList($product['id_product']);
				} else {
					$product['warehouse_list'][0] = [];
				}

				$product['stock'][0] = StockAvailable::getQuantityAvailableByProduct((int) $product['id_product'], 0, (int) $this->context->shop->id);

				foreach ($combinations as &$combination) {
					$combination['attributes'] = rtrim($combination['attributes'], ' - ');
				}

				$product['combinations'] = $combinations;

				if ($product['customizable']) {
					$productInstance = new Product((int) $product['id_product']);
					$product['customization_fields'] = $productInstance->getCustomizationFields($this->context->language->id);
				}

			}

			$toReturn = [
				'products' => $products,
				'found'    => true,
			];
		} else {
			$toReturn = ['found' => false];
		}

		$this->content = json_encode($toReturn);
	}

	public function ajaxProcessSendMailValidateCustomerPieces() {

		if ($this->tabAccess['edit'] === '1') {
			$cart = new Cart((int) Tools::getValue('id_cart'));

			if (Validate::isLoadedObject($cart)) {
				$customer = new Customer((int) $cart->id_customer);

				if (Validate::isLoadedObject($customer)) {
					$tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/backoffice_order.tpl');
					$tpl->assign([
						'order_link' => $this->context->link->getFrontPageLink('order', false, (int) $cart->id_lang, 'step=3&recover_cart=' . (int) $cart->id . '&token_cart=' . md5(_COOKIE_KEY_ . 'recover_cart_' . (int) $cart->id)),
						'firstname'  => $customer->firstname,
						'lastname'   => $customer->lastname,
					]);
					$postfields = [
						'sender'      => [
							'name'  => "Sevice Commerciale " . Configuration::get('PS_SHOP_NAME'),
							'email' => 'no-reply@' . Configuration::get('PS_SHOP_URL'),
						],
						'to'          => [
							[
								'name'  => $customer->firstname . ' ' . $customer->lastname,
								'email' => $customer->email,
							],
						],

						'subject'     => $this->l('Process the payment of your order'),
						"htmlContent" => $tpl->fetch(),
					];

					$result = Tools::sendEmail($postfields);
					$this->ajaxDie(json_encode(['errors' => false, 'result' => $this->l('The email was sent to your customer.')]));

				}

			}

			$this->content = json_encode(['errors' => true, 'result' => $this->l('Error in sending the email to your customer.')]);
		}

	}

	public function ajaxProcessAddProductOnCustomerPieces() {

		// Load object
		$order = new CustomerPieces((int) Tools::getValue('id_order'));

		if (!Validate::isLoadedObject($order)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The order object cannot be loaded.'),
					]
				)
			);
		}

		$oldCartRules = $this->context->cart->getCartRules();

		if ($order->hasBeenShipped()) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('You cannot add products to delivered orders. '),
					]
				)
			);
		}

		$productInformations = $_POST['add_product'];

		if (isset($_POST['add_invoice'])) {
			$invoiceInformations = $_POST['add_invoice'];
		} else {
			$invoiceInformations = [];
		}

		$product = new Product($productInformations['product_id'], false, $order->id_lang);

		if (!Validate::isLoadedObject($product)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The product object cannot be loaded.'),
					]
				)
			);
		}

		if (isset($productInformations['product_attribute_id']) && $productInformations['product_attribute_id']) {
			$combination = new Combination($productInformations['product_attribute_id']);

			if (!Validate::isLoadedObject($combination)) {
				$this->ajaxDie(
					json_encode(
						[
							'result' => false,
							'error'  => Tools::displayError('The combination object cannot be loaded.'),
						]
					)
				);
			}

		}

		// Total method
		$totalMethod = Cart::BOTH_WITHOUT_SHIPPING;

		// Create new cart
		$cart = new Cart();
		$cart->id_shop_group = $order->id_shop_group;
		$cart->id_shop = $order->id_shop;
		$cart->id_customer = $order->id_customer;
		$cart->id_carrier = $order->id_carrier;
		$cart->id_address_delivery = $order->id_address_delivery;
		$cart->id_address_invoice = $order->id_address_invoice;
		$cart->id_currency = $order->id_currency;
		$cart->id_lang = $order->id_lang;
		$cart->secure_key = $order->secure_key;

		// Save new cart
		$cart->add();

		// Save context (in order to apply cart rule)
		$this->context->cart = $cart;
		$this->context->customer = new Customer($order->id_customer);

		// always add taxes even if there are not displayed to the customer
		$useTaxes = true;

		$initialProductPriceTaxIncl = Product::getPriceStatic(
			$product->id,
			$useTaxes,
			isset($combination) ? $combination->id : null,
			2,
			null,
			false,
			true,
			1,
			false,
			$order->id_customer,
			$cart->id,
			$order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)}
		);

		// Creating specific price if needed

		if ($productInformations['product_price_tax_incl'] != $initialProductPriceTaxIncl) {
			$specificPrice = new SpecificPrice();
			$specificPrice->id_shop = 0;
			$specificPrice->id_shop_group = 0;
			$specificPrice->id_currency = 0;
			$specificPrice->id_country = 0;
			$specificPrice->id_group = 0;
			$specificPrice->id_customer = $order->id_customer;
			$specificPrice->id_product = $product->id;

			if (isset($combination)) {
				$specificPrice->id_product_attribute = $combination->id;
			} else {
				$specificPrice->id_product_attribute = 0;
			}

			$specificPrice->price = $productInformations['product_price_tax_excl'];
			$specificPrice->from_quantity = 1;
			$specificPrice->reduction = 0;
			$specificPrice->reduction_type = 'amount';
			$specificPrice->reduction_tax = 0;
			$specificPrice->from = '0000-00-00 00:00:00';
			$specificPrice->to = '0000-00-00 00:00:00';
			$specificPrice->add();
		}

		// Add product to cart
		$updateQuantity = $cart->updateQty(
			$productInformations['product_quantity'],
			$product->id,
			isset($productInformations['product_attribute_id']) ? $productInformations['product_attribute_id'] : null,
			isset($combination) ? $combination->id : null,
			'up',
			0,
			new Shop($cart->id_shop)
		);

		if ($updateQuantity < 0) {
			// If product has attribute, minimal quantity is set with minimal quantity of attribute
			$minimalQuantity = ($productInformations['product_attribute_id']) ? Attributes::getAttributeMinimalQty($productInformations['product_attribute_id']) : $product->minimal_quantity;
			$this->ajaxDie(json_encode(['error' => sprintf(Tools::displayError('You must add %d minimum quantity', false), $minimalQuantity)]));
		} else

		if (!$updateQuantity) {
			$this->ajaxDie(json_encode(['error' => Tools::displayError('You already have the maximum quantity available for this product.', false)]));
		}

		// If order is valid, we can create a new invoice or edit an existing invoice

		if ($order->hasInvoice()) {
			$orderInvoice = new CustomerPiecesInvoice($productInformations['invoice']);
			// Create new invoice

			if ($orderInvoice->id == 0) {
				// If we create a new invoice, we calculate shipping cost
				$totalMethod = Cart::BOTH;
				// Create Cart rule in order to make free shipping

				if (isset($invoiceInformations['free_shipping']) && $invoiceInformations['free_shipping']) {
					$cartRule = new CartRule();
					$cartRule->id_customer = $order->id_customer;
					$cartRule->name = [
						Configuration::get('PS_LANG_DEFAULT') => $this->l('[Generated] CartRule for Free Shipping'),
					];
					$cartRule->date_from = date('Y-m-d H:i:s', time());
					$cartRule->date_to = date('Y-m-d H:i:s', time() + 24 * 3600);
					$cartRule->quantity = 1;
					$cartRule->quantity_per_user = 1;
					$cartRule->minimum_amount_currency = $order->id_currency;
					$cartRule->reduction_currency = $order->id_currency;
					$cartRule->free_shipping = true;
					$cartRule->active = 1;
					$cartRule->add();

					// Add cart rule to cart and in order
					$cart->addCartRule($cartRule->id);
					$values = [
						'tax_incl' => $cartRule->getContextualValue(true),
						'tax_excl' => $cartRule->getContextualValue(false),
					];
					$order->addCartRule($cartRule->id, $cartRule->name[Configuration::get('PS_LANG_DEFAULT')], $values);
				}

				$orderInvoice->id_order = $order->id;

				if ($orderInvoice->number) {
					Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $order->id_shop);
				} else {
					$orderInvoice->number = CustomerPieces::getLastInvoiceNumber() + 1;
				}

				$invoiceAddress = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});
				$carrier = new Carrier((int) $order->id_carrier);
				$taxCalculator = $carrier->getTaxCalculator($invoiceAddress);

				$orderInvoice->total_paid_tax_excl = Tools::ps_round((float) $cart->getCustomerPiecesTotal(false, $totalMethod), 2);
				$orderInvoice->total_paid_tax_incl = Tools::ps_round((float) $cart->getCustomerPiecesTotal($useTaxes, $totalMethod), 2);
				$orderInvoice->total_products = (float) $cart->getCustomerPiecesTotal(false, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_products_wt = (float) $cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_shipping_tax_excl = (float) $cart->getTotalShippingCost(null, false);
				$orderInvoice->total_shipping_tax_incl = (float) $cart->getTotalShippingCost();

				$orderInvoice->total_wrapping_tax_excl = abs($cart->getCustomerPiecesTotal(false, Cart::ONLY_WRAPPING));
				$orderInvoice->total_wrapping_tax_incl = abs($cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$orderInvoice->shipping_tax_computation_method = (int) $taxCalculator->computation_method;

				// Update current order field, only shipping because other field is updated later
				$order->total_shipping += $orderInvoice->total_shipping_tax_incl;
				$order->total_shipping_tax_excl += $orderInvoice->total_shipping_tax_excl;
				$order->total_shipping_tax_incl += ($useTaxes) ? $orderInvoice->total_shipping_tax_incl : $orderInvoice->total_shipping_tax_excl;

				$order->total_wrapping += abs($cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$order->total_wrapping_tax_excl += abs($cart->getCustomerPiecesTotal(false, Cart::ONLY_WRAPPING));
				$order->total_wrapping_tax_incl += abs($cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$orderInvoice->add();

				$orderInvoice->saveCarrierTaxCalculator($taxCalculator->getTaxesAmount($orderInvoice->total_shipping_tax_excl));

				$orderCarrier = new CustomerPiecesCarrier();
				$orderCarrier->id_order = (int) $order->id;
				$orderCarrier->id_carrier = (int) $order->id_carrier;
				$orderCarrier->id_order_invoice = (int) $orderInvoice->id;
				$orderCarrier->weight = (float) $cart->getTotalWeight();
				$orderCarrier->shipping_cost_tax_excl = (float) $orderInvoice->total_shipping_tax_excl;
				$orderCarrier->shipping_cost_tax_incl = ($useTaxes) ? (float) $orderInvoice->total_shipping_tax_incl : (float) $orderInvoice->total_shipping_tax_excl;
				$orderCarrier->add();
			}

			// Update current invoice
			else {
				$orderInvoice->total_paid_tax_excl += Tools::ps_round((float) ($cart->getCustomerPiecesTotal(false, $totalMethod)), 2);
				$orderInvoice->total_paid_tax_incl += Tools::ps_round((float) ($cart->getCustomerPiecesTotal($useTaxes, $totalMethod)), 2);
				$orderInvoice->total_products += (float) $cart->getCustomerPiecesTotal(false, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_products_wt += (float) $cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);
				$orderInvoice->update();
			}

		}

		// Create CustomerPieces detail information
		$orderDetail = new CustomerPiecesDetail();
		$orderDetail->createList($order, $cart, $order->getCurrentCustomerPiecesState(), $cart->getProducts(), (isset($orderInvoice) ? $orderInvoice->id : 0), $useTaxes, (int) Tools::getValue('add_product_warehouse'));

		// update totals amount of order
		$order->total_products += (float) $cart->getCustomerPiecesTotal(false, Cart::ONLY_PRODUCTS);
		$order->total_products_wt += (float) $cart->getCustomerPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);

		$order->total_paid += Tools::ps_round((float) ($cart->getCustomerPiecesTotal(true, $totalMethod)), 2);
		$order->total_paid_tax_excl += Tools::ps_round((float) ($cart->getCustomerPiecesTotal(false, $totalMethod)), 2);
		$order->total_paid_tax_incl += Tools::ps_round((float) ($cart->getCustomerPiecesTotal($useTaxes, $totalMethod)), 2);

		if (isset($orderInvoice) && Validate::isLoadedObject($orderInvoice)) {
			$order->total_shipping = $orderInvoice->total_shipping_tax_incl;
			$order->total_shipping_tax_incl = $orderInvoice->total_shipping_tax_incl;
			$order->total_shipping_tax_excl = $orderInvoice->total_shipping_tax_excl;
		}

		StockAvailable::updateQuantity($orderDetail->product_id, $orderDetail->product_attribute_id, ($orderDetail->product_quantity * -1), $order->id_shop);

		// discount
		$order->total_discounts += (float) abs($cart->getCustomerPiecesTotal(true, Cart::ONLY_DISCOUNTS));
		$order->total_discounts_tax_excl += (float) abs($cart->getCustomerPiecesTotal(false, Cart::ONLY_DISCOUNTS));
		$order->total_discounts_tax_incl += (float) abs($cart->getCustomerPiecesTotal(true, Cart::ONLY_DISCOUNTS));

		// Save changes of order
		$order->update();

		// Update weight SUM
		$orderCarrier = new CustomerPiecesCarrier((int) $order->getIdCustomerPiecesCarrier());

		if (Validate::isLoadedObject($orderCarrier)) {
			$orderCarrier->weight = (float) $order->getTotalWeight();

			if ($orderCarrier->update()) {
				$order->weight = sprintf("%.3f " . Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
			}

		}

		// Update Tax lines
		$orderDetail->updateTaxAmount($order);

		// Delete specific price if exists

		if (isset($specificPrice)) {
			$specificPrice->delete();
		}

		$products = $this->getProducts($order);

		// Get the last product
		$product = end($products);
		$product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
		$resume = CustomerPiecesSlip::getProductSlipResume((int) $product['id_order_detail']);
		$product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
		$product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
		$product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
		$product['return_history'] = CustomerPiecesReturn::getProductReturnDetail((int) $product['id_order_detail']);
		$product['refund_history'] = CustomerPiecesSlip::getProductSlipDetail((int) $product['id_order_detail']);

		if ($product['id_warehouse'] != 0) {
			$warehouse = new Warehouse((int) $product['id_warehouse']);
			$product['warehouse_name'] = $warehouse->name;
			$warehouseLocation = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);

			if (!empty($warehouseLocation)) {
				$product['warehouse_location'] = $warehouseLocation;
			} else {
				$product['warehouse_location'] = false;
			}

		} else {
			$product['warehouse_name'] = '--';
			$product['warehouse_location'] = false;
		}

		// Get invoices collection
		$invoiceCollection = $order->getInvoicesCollection();

		$invoiceArray = [];

		foreach ($invoiceCollection as $invoice) {
			/** @var CustomerPiecesInvoice $invoice */
			$invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
			$invoiceArray[] = $invoice;
		}

		// Assign to smarty informations in order to show the new product line
		$this->context->smarty->assign(
			[
				'product'             => $product,
				'order'               => $order,
				'currency'            => new Currency($order->id_currency),
				'can_edit'            => $this->tabAccess['edit'],
				'invoices_collection' => $invoiceCollection,
				'current_id_lang'     => $this->context->language->id,
				'link'                => $this->context->link,
				'current_index'       => static::$currentIndex,
				'display_warehouse'   => (int) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
			]
		);

		$this->sendChangedNotification($order);
		$newCartRules = $this->context->cart->getCartRules();
		sort($oldCartRules);
		sort($newCartRules);
		$result = array_diff($newCartRules, $oldCartRules);
		$refresh = false;

		$res = true;

		foreach ($result as $cartRule) {
			$refresh = true;
			// Create CustomerPiecesCartRule
			$rule = new CartRule($cartRule['id_cart_rule']);
			$values = [
				'tax_incl' => $rule->getContextualValue(true),
				'tax_excl' => $rule->getContextualValue(false),
			];
			$orderCartRule = new CustomerPiecesCartRule();
			$orderCartRule->id_order = $order->id;
			$orderCartRule->id_cart_rule = $cartRule['id_cart_rule'];
			$orderCartRule->id_order_invoice = $orderInvoice->id;
			$orderCartRule->name = $cartRule['name'];
			$orderCartRule->value = $values['tax_incl'];
			$orderCartRule->value_tax_excl = $values['tax_excl'];
			$res &= $orderCartRule->add();

			$order->total_discounts += $orderCartRule->value;
			$order->total_discounts_tax_incl += $orderCartRule->value;
			$order->total_discounts_tax_excl += $orderCartRule->value_tax_excl;
			$order->total_paid -= $orderCartRule->value;
			$order->total_paid_tax_incl -= $orderCartRule->value;
			$order->total_paid_tax_excl -= $orderCartRule->value_tax_excl;
		}

		// Update CustomerPieces
		$order->update();

		$this->ajaxDie(
			json_encode(
				[
					'result'             => true,
					'view'               => $this->createTemplate('_product_line.tpl')->fetch(),
					'can_edit'           => $this->tabAccess['add'],
					'order'              => $order,
					'invoices'           => $invoiceArray,
					'documents_html'     => $this->createTemplate('_documents.tpl')->fetch(),
					'shipping_html'      => $this->createTemplate('_shipping.tpl')->fetch(),
					'discount_form_html' => $this->createTemplate('_discount_form.tpl')->fetch(),
					'refresh'            => $refresh,
				]
			)
		);
	}

	public function sendChangedNotification(CustomerPieces $order = null) {

		if (is_null($order)) {
			$order = new CustomerPieces(Tools::getValue('id_order'));
		}

		Hook::exec('actionCustomerPiecesEdited', ['order' => $order]);
	}

	public function ajaxProcessLoadProductInformation() {

		$orderDetail = new CustomerPiecesDetail(Tools::getValue('id_order_detail'));

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The CustomerPiecesDetail object cannot be loaded.'),
					]
				)
			);
		}

		$product = new Product($orderDetail->product_id);

		if (!Validate::isLoadedObject($product)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The product object cannot be loaded.'),
					]
				)
			);
		}

		$address = new Address(Tools::getValue('id_address'));

		if (!Validate::isLoadedObject($address)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The address object cannot be loaded.'),
					]
				)
			);
		}

		$this->ajaxDie(json_encode([
			'result'            => true,
			'product'           => $product,
			'tax_rate'          => $product->getTaxesRate($address),
			'price_tax_incl'    => Product::getPriceStatic($product->id, true, $orderDetail->product_attribute_id, 2),
			'price_tax_excl'    => Product::getPriceStatic($product->id, false, $orderDetail->product_attribute_id, 2),
			'reduction_percent' => $orderDetail->reduction_percent,
		]));
	}

	public function ajaxProcessEditProductOnCustomerPieces() {

		// Return value
		$res = true;

		$order = new CustomerPieces((int) Tools::getValue('id_order'));
		$orderDetail = new CustomerPiecesDetail((int) Tools::getValue('product_id_order_detail'));

		if (Tools::isSubmit('product_invoice')) {
			$orderInvoice = new CustomerPiecesInvoice((int) Tools::getValue('product_invoice'));
		}

		// If multiple product_quantity, the order details concern a product customized
		$productQuantity = 0;

		if (is_array(Tools::getValue('product_quantity'))) {

			foreach (Tools::getValue('product_quantity') as $idCustomization => $qty) {
				// Update quantity of each customization
				Db::getInstance()->update('customization', ['quantity' => (int) $qty], 'id_customization = ' . (int) $idCustomization);
				// Calculate the real quantity of the product
				$productQuantity += $qty;
			}

		} else {
			$productQuantity = Tools::getValue('product_quantity');
		}

		$this->checkStockAvailable($orderDetail, ($productQuantity - $orderDetail->product_quantity));

		// Check fields validity
		$this->doEditProductValidation($orderDetail, $order, isset($orderInvoice) ? $orderInvoice : null);

		// If multiple product_quantity, the order details concern a product customized
		$productQuantity = 0;

		if (is_array(Tools::getValue('product_quantity'))) {

			foreach (Tools::getValue('product_quantity') as $idCustomization => $qty) {
				// Update quantity of each customization
				Db::getInstance()->update(
					'customization',
					[
						'quantity' => (int) $qty,
					],
					'id_customization = ' . (int) $idCustomization,
					1
				);
				// Calculate the real quantity of the product
				$productQuantity += $qty;
			}

		} else {
			$productQuantity = Tools::getValue('product_quantity');
		}

		$productPriceTaxIncl = Tools::ps_round(Tools::getValue('product_price_tax_incl'), 2);
		$productPriceTaxExcl = Tools::ps_round(Tools::getValue('product_price_tax_excl'), 2);
		$totalProductsTaxIncl = $productPriceTaxIncl * $productQuantity;
		$totalProductsTaxExcl = $productPriceTaxExcl * $productQuantity;

		// Calculate differences of price (Before / After)
		$diffPriceTaxIncl = $totalProductsTaxIncl - $orderDetail->total_price_tax_incl;
		$diffPriceTaxExcl = $totalProductsTaxExcl - $orderDetail->total_price_tax_excl;

		// Apply change on CustomerPiecesInvoice

		if (isset($orderInvoice)) {
			// If CustomerPiecesInvoice to use is different, we update the old invoice and new invoice

			if ($orderDetail->id_order_invoice != $orderInvoice->id) {
				$oldCustomerPiecesInvoice = new CustomerPiecesInvoice($orderDetail->id_order_invoice);
				// We remove cost of products
				$oldCustomerPiecesInvoice->total_products -= $orderDetail->total_price_tax_excl;
				$oldCustomerPiecesInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;

				$oldCustomerPiecesInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
				$oldCustomerPiecesInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;

				$res &= $oldCustomerPiecesInvoice->update();

				$orderInvoice->total_products += $orderDetail->total_price_tax_excl;
				$orderInvoice->total_products_wt += $orderDetail->total_price_tax_incl;

				$orderInvoice->total_paid_tax_excl += $orderDetail->total_price_tax_excl;
				$orderInvoice->total_paid_tax_incl += $orderDetail->total_price_tax_incl;

				$orderDetail->id_order_invoice = $orderInvoice->id;
			}

		}

		if ($diffPriceTaxIncl != 0 && $diffPriceTaxExcl != 0) {
			$orderDetail->unit_price_tax_excl = $productPriceTaxExcl;
			$orderDetail->unit_price_tax_incl = $productPriceTaxIncl;

			$orderDetail->total_price_tax_incl += $diffPriceTaxIncl;
			$orderDetail->total_price_tax_excl += $diffPriceTaxExcl;

			if (isset($orderInvoice)) {
				// Apply changes on CustomerPiecesInvoice
				$orderInvoice->total_products += $diffPriceTaxExcl;
				$orderInvoice->total_products_wt += $diffPriceTaxIncl;

				$orderInvoice->total_paid_tax_excl += $diffPriceTaxExcl;
				$orderInvoice->total_paid_tax_incl += $diffPriceTaxIncl;
			}

			// Apply changes on CustomerPieces
			$order = new CustomerPieces($orderDetail->id_order);
			$order->total_products += $diffPriceTaxExcl;
			$order->total_products_wt += $diffPriceTaxIncl;

			$order->total_paid += $diffPriceTaxIncl;
			$order->total_paid_tax_excl += $diffPriceTaxExcl;
			$order->total_paid_tax_incl += $diffPriceTaxIncl;

			$res &= $order->update();
		}

		$oldQuantity = $orderDetail->product_quantity;

		$orderDetail->product_quantity = $productQuantity;
		$orderDetail->reduction_percent = 0;

		// update taxes
		$res &= $orderDetail->updateTaxAmount($order);

		// Save order detail
		$res &= $orderDetail->update();

		// Update weight SUM
		$orderCarrier = new CustomerPiecesCarrier((int) $order->getIdCustomerPiecesCarrier());

		if (Validate::isLoadedObject($orderCarrier)) {
			$orderCarrier->weight = (float) $order->getTotalWeight();
			$res &= $orderCarrier->update();

			if ($res) {
				$order->weight = sprintf("%.3f " . Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
			}

		}

		// Save order invoice

		if (isset($orderInvoice)) {
			$res &= $orderInvoice->update();
		}

		// Update product available quantity
		StockAvailable::updateQuantity($orderDetail->product_id, $orderDetail->product_attribute_id, ($oldQuantity - $orderDetail->product_quantity), $order->id_shop);

		$products = $this->getProducts($order);
		// Get the last product
		$product = $products[$orderDetail->id];
		$product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
		$resume = CustomerPiecesSlip::getProductSlipResume($orderDetail->id);
		$product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
		$product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
		$product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
		$product['refund_history'] = CustomerPiecesSlip::getProductSlipDetail($orderDetail->id);

		if ($product['id_warehouse'] != 0) {
			$warehouse = new Warehouse((int) $product['id_warehouse']);
			$product['warehouse_name'] = $warehouse->name;
			$warehouseLocation = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);

			if (!empty($warehouseLocation)) {
				$product['warehouse_location'] = $warehouseLocation;
			} else {
				$product['warehouse_location'] = false;
			}

		} else {
			$product['warehouse_name'] = '--';
			$product['warehouse_location'] = false;
		}

		// Get invoices collection
		$invoiceCollection = $order->getInvoicesCollection();

		$invoiceArray = [];

		foreach ($invoiceCollection as $invoice) {
			/** @var CustomerPiecesInvoice $invoice */
			$invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
			$invoiceArray[] = $invoice;
		}

		// Assign to smarty informations in order to show the new product line
		$this->context->smarty->assign(
			[
				'product'             => $product,
				'order'               => $order,
				'currency'            => new Currency($order->id_currency),
				'can_edit'            => $this->tabAccess['edit'],
				'invoices_collection' => $invoiceCollection,
				'current_id_lang'     => $this->context->language->id,
				'link'                => $this->context->link,
				'current_index'       => static::$currentIndex,
				'display_warehouse'   => (int) Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
			]
		);

		if (!$res) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => $res,
						'error'  => Tools::displayError('An error occurred while editing the product line.'),
					]
				)
			);
		}

		if (is_array(Tools::getValue('product_quantity'))) {
			$view = $this->createTemplate('_customized_data.tpl')->fetch();
		} else {
			$view = $this->createTemplate('_product_line.tpl')->fetch();
		}

		$this->sendChangedNotification($order);

		$this->ajaxDie(json_encode([
			'result'              => $res,
			'view'                => $view,
			'can_edit'            => $this->tabAccess['add'],
			'invoices_collection' => $invoiceCollection,
			'order'               => $order,
			'invoices'            => $invoiceArray,
			'documents_html'      => $this->createTemplate('_documents.tpl')->fetch(),
			'shipping_html'       => $this->createTemplate('_shipping.tpl')->fetch(),
			'customized_product'  => is_array(Tools::getValue('product_quantity')),
		]));
	}

	protected function checkStockAvailable($orderDetail, $addQuantity) {

		if ($addQuantity > 0) {
			$stockAvailable = StockAvailable::getQuantityAvailableByProduct($orderDetail->product_id, $orderDetail->product_attribute_id, $orderDetail->id_shop);
			$product = new Product($orderDetail->product_id, true, null, $orderDetail->id_shop);

			if (!Validate::isLoadedObject($product)) {
				$this->ajaxDie(json_encode([
					'result' => false,
					'error'  => Tools::displayError('The Product object could not be loaded.'),
				]));
			} else {

				if (($stockAvailable < $addQuantity) && (!$product->isAvailableWhenOutOfStock((int) $product->out_of_stock))) {
					$this->ajaxDie(json_encode([
						'result' => false,
						'error'  => Tools::displayError('This product is no longer in stock with those attributes '),
					]));

				}

			}

		}

	}

	public function ajaxProcessDeleteProductLine() {

		$res = true;

		$orderDetail = new CustomerPiecesDetail((int) Tools::getValue('id_order_detail'));
		$order = new CustomerPieces((int) Tools::getValue('id_order'));

		$this->doDeleteProductLineValidation($orderDetail, $order);

		// Update CustomerPiecesInvoice of this CustomerPiecesDetail

		if ($orderDetail->id_order_invoice != 0) {
			$orderInvoice = new CustomerPiecesInvoice($orderDetail->id_order_invoice);
			$orderInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
			$orderInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
			$orderInvoice->total_products -= $orderDetail->total_price_tax_excl;
			$orderInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;
			$res &= $orderInvoice->update();
		}

		// Update CustomerPieces
		$order->total_paid -= $orderDetail->total_price_tax_incl;
		$order->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
		$order->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
		$order->total_products -= $orderDetail->total_price_tax_excl;
		$order->total_products_wt -= $orderDetail->total_price_tax_incl;

		$res &= $order->update();

		// Reinject quantity in stock
		$this->reinjectQuantity($orderDetail, $orderDetail->product_quantity, true);

		// Update weight SUM
		$orderCarrier = new CustomerPiecesCarrier((int) $order->getIdCustomerPiecesCarrier());

		if (Validate::isLoadedObject($orderCarrier)) {
			$orderCarrier->weight = (float) $order->getTotalWeight();
			$res &= $orderCarrier->update();

			if ($res) {
				$order->weight = sprintf("%.3f " . Configuration::get('PS_WEIGHT_UNIT'), $orderCarrier->weight);
			}

		}

		if (!$res) {
			$this->ajaxDie(json_encode([
				'result' => $res,
				'error'  => Tools::displayError('An error occurred while attempting to delete the product line.'),
			]));
		}

		// Get invoices collection
		$invoiceCollection = $order->getInvoicesCollection();

		$invoiceArray = [];

		foreach ($invoiceCollection as $invoice) {
			/** @var CustomerPiecesInvoice $invoice */
			$invoice->name = $invoice->getInvoiceNumberFormatted($this->context->language->id, (int) $order->id_shop);
			$invoiceArray[] = $invoice;
		}

		// Assign to smarty informations in order to show the new product line
		$this->context->smarty->assign([
			'order'               => $order,
			'currency'            => new Currency($order->id_currency),
			'invoices_collection' => $invoiceCollection,
			'current_id_lang'     => $this->context->language->id,
			'link'                => $this->context->link,
			'current_index'       => static::$currentIndex,
		]);

		$this->sendChangedNotification($order);

		$this->ajaxDie(json_encode([
			'result'         => $res,
			'order'          => $order,
			'invoices'       => $invoiceArray,
			'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
			'shipping_html'  => $this->createTemplate('_shipping.tpl')->fetch(),
		]));
	}

	public function ajaxProcessChangePaymentMethod() {

		$customer = new Customer(Tools::getValue('id_customer'));
		$modules = Module::getAuthorizedModules($customer->id_default_group);
		$authorizedModules = [];

		if (!Validate::isLoadedObject($customer) || !is_array($modules)) {
			$this->ajaxDie(json_encode(['result' => false]));
		}

		foreach ($modules as $module) {
			$authorizedModules[] = (int) $module['id_module'];
		}

		$paymentModules = [];

		foreach (PaymentModule::getInstalledPaymentModules() as $pModule) {

			if (in_array((int) $pModule['id_module'], $authorizedModules)) {
				$paymentModules[] = Module::getInstanceById((int) $pModule['id_module']);
			}

		}

		$this->context->smarty->assign([
			'payment_modules' => $paymentModules,
		]);

		$this->ajaxDie(json_encode([
			'result' => true,
			'view'   => $this->createTemplate('_select_payment.tpl')->fetch(),
		]));
	}

	protected function applyDiscountOnInvoice($orderInvoice, $valueTaxIncl, $valueTaxExcl) {

		// Update CustomerPiecesInvoice
		$orderInvoice->total_discount_tax_incl += $valueTaxIncl;
		$orderInvoice->total_discount_tax_excl += $valueTaxExcl;
		$orderInvoice->total_paid_tax_incl -= $valueTaxIncl;
		$orderInvoice->total_paid_tax_excl -= $valueTaxExcl;
		$orderInvoice->update();
	}

	protected function doEditProductValidation(CustomerPiecesDetail $orderDetail, CustomerPieces $order, CustomerPiecesInvoice $orderInvoice = null) {

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The CustomerPieces Detail object could not be loaded.'),
			]));
		}

		if (!empty($orderInvoice) && !Validate::isLoadedObject($orderInvoice)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The invoice object cannot be loaded.'),
			]));
		}

		if (!Validate::isLoadedObject($order)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The order object cannot be loaded.'),
			]));
		}

		if ($orderDetail->id_order != $order->id) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('You cannot edit the order detail for this order.'),
			]));
		}

		// We can't edit a delivered order

		if ($order->hasBeenDelivered()) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('You cannot edit a delivered order.'),
			]));
		}

		if (!empty($orderInvoice) && $orderInvoice->id_order != Tools::getValue('id_order')) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('You cannot use this invoice for the order'),
			]));
		}

		// Clean price
		$productPriceTaxIncl = str_replace(',', '.', Tools::getValue('product_price_tax_incl'));
		$productPriceTaxExcl = str_replace(',', '.', Tools::getValue('product_price_tax_excl'));

		if (!Validate::isPrice($productPriceTaxIncl) || !Validate::isPrice($productPriceTaxExcl)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('Invalid price'),
			]));
		}

		if (!is_array(Tools::getValue('product_quantity')) && !Validate::isUnsignedInt(Tools::getValue('product_quantity'))) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('Invalid quantity'),
			]));
		} else

		if (is_array(Tools::getValue('product_quantity'))) {

			foreach (Tools::getValue('product_quantity') as $qty) {

				if (!Validate::isUnsignedInt($qty)) {
					$this->ajaxDie(json_encode([
						'result' => false,
						'error'  => Tools::displayError('Invalid quantity'),
					]));
				}

			}

		}

	}

	protected function doDeleteProductLineValidation(CustomerPiecesDetail $orderDetail, CustomerPieces $order) {

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The CustomerPieces Detail object could not be loaded.'),
			]));
		}

		if (!Validate::isLoadedObject($order)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The order object cannot be loaded.'),
			]));
		}

		if ($orderDetail->id_order != $order->id) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('You cannot delete the order detail.'),
			]));
		}

		// We can't edit a delivered order

		if ($order->hasBeenDelivered()) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('You cannot edit a delivered order.'),
			]));
		}

	}

	protected function reinjectQuantity($orderDetail, $qtyCancelProduct, $delete = false) {

		// Reinject product
		$reinjectableQuantity = (int) $orderDetail->product_quantity - (int) $orderDetail->product_quantity_reinjected;
		$quantityToReinject = $qtyCancelProduct > $reinjectableQuantity ? $reinjectableQuantity : $qtyCancelProduct;
		// @since 1.5.0 : Advanced Stock Management
		// FIXME: this should do something
		// $product_to_inject = new Product($orderDetail->product_id, false, (int) $this->context->language->id, (int) $orderDetail->id_shop);

		$product = new Product($orderDetail->product_id, false, (int) $this->context->language->id, (int) $orderDetail->id_shop);

		if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management && $orderDetail->id_warehouse != 0) {
			$manager = StockManagerFactory::getManager();
			$movements = StockMvt::getNegativeStockMvts(
				$orderDetail->id_order,
				$orderDetail->product_id,
				$orderDetail->product_attribute_id,
				$quantityToReinject
			);
			$leftToReinject = $quantityToReinject;

			foreach ($movements as $movement) {

				if ($leftToReinject > $movement['physical_quantity']) {
					$quantityToReinject = $movement['physical_quantity'];
				}

				$leftToReinject -= $quantityToReinject;

				if (Pack::isPack((int) $product->id)) {
					// Gets items

					if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)) {
						$productsPack = Pack::getItems((int) $product->id, (int) Configuration::get('PS_LANG_DEFAULT'));
						// Foreach item

						foreach ($productsPack as $productPack) {

							if ($productPack->advanced_stock_management == 1) {
								$manager->addProduct(
									$productPack->id,
									$productPack->id_pack_product_attribute,
									new Warehouse($movement['id_warehouse']),
									$productPack->pack_quantity * $quantityToReinject,
									null,
									$movement['price_te'],
									true
								);
							}

						}

					}

					if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
						($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))
					) {
						$manager->addProduct(
							$orderDetail->product_id,
							$orderDetail->product_attribute_id,
							new Warehouse($movement['id_warehouse']),
							$quantityToReinject,
							null,
							$movement['price_te'],
							true
						);
					}

				} else {
					$manager->addProduct(
						$orderDetail->product_id,
						$orderDetail->product_attribute_id,
						new Warehouse($movement['id_warehouse']),
						$quantityToReinject,
						null,
						$movement['price_te'],
						true
					);
				}

			}

			$idProduct = $orderDetail->product_id;

			if ($delete) {
				$orderDetail->delete();
			}

			StockAvailable::synchronize($idProduct);
		} else

		if ($orderDetail->id_warehouse == 0) {
			StockAvailable::updateQuantity(
				$orderDetail->product_id,
				$orderDetail->product_attribute_id,
				$quantityToReinject,
				$orderDetail->id_shop
			);

			if ($delete) {
				$orderDetail->delete();
			}

		} else {
			$this->errors[] = Tools::displayError('This product cannot be re-stocked.');
		}

	}

	protected function getProducts($order) {

		$products = $order->getProducts();

		foreach ($products as &$product) {

			if ($product['image'] != null) {
				$name = 'product_mini_' . (int) $product['product_id'] . (isset($product['product_attribute_id']) ? '_' . (int) $product['product_attribute_id'] : '') . '.jpg';
				// generate image cache, only for back office
				$product['image_tag'] = ImageManager::thumbnail(_PS_IMG_DIR_ . 'p/' . $product['image']->getExistingImgPath() . '.jpg', $name, 45, 'jpg');

				if (file_exists(_PS_TMP_IMG_DIR_ . $name)) {
					$product['image_size'] = getimagesize(_PS_TMP_IMG_DIR_ . $name);
				} else {
					$product['image_size'] = false;
				}

			}

		}

		ksort($products);

		return $products;
	}

	public function ajaxProcessBookCustomerPiece() {

		$id_customer_piece = Tools::getValue('id_customer_piece');
		$piece = new CustomerPieces($id_customer_piece);
		$student = new Customer($piece->id_customer);

		$record = new BookRecords();
		$record->id_book_diary = 7;

		$record->name = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
		$record->piece_type = 'Pièce Client';
		$studentAccount = new StdAccount($student->id_stdaccount);

		$account = new StdAccount(Configuration::get('EPH_PROFIT_DEFAULT_ACCOUNT'));
		$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));

		$record->date_add = $piece->date_add;
		$success = $record->add();

		if ($success) {

			$detail = new BookRecordDetails();
			$detail->id_book_record = $record->id;
			$detail->id_stdaccount = $studentAccount->id;
			$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
			$detail->piece_number = $piece->id;
			$detail->debit = $piece->total_tax_incl;
			$detail->date_add = $record->date_add;
			$detail->add();
			$studentAccount->pointed_solde = $studentAccount->pointed_solde + $piece->total_tax_incl;
			$studentAccount->update();

			$detail = new BookRecordDetails();
			$detail->id_book_record = $record->id;
			$detail->id_stdaccount = $account->id;
			$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
			$detail->piece_number = $piece->id;
			$detail->credit = $piece->total_with_freight_tax_excl;
			$detail->date_add = $record->date_add;
			$detail->add();

			$account->pointed_solde = $account->pointed_solde - $piece->total_with_freight_tax_excl;
			$account->update();

			$detail = new BookRecordDetails();
			$detail->id_book_record = $record->id;
			$detail->id_stdaccount = !empty($account->default_vat) ? $account->default_vat : $vatAccount->id;
			$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
			$detail->piece_number = $piece->id;
			$detail->credit = $piece->total_tax;
			$detail->date_add = $record->date_add;
			$detail->add();
			$vatAccount->pointed_solde = $vatAccount->pointed_solde - $piece->total_tax;
			$vatAccount->update();
		}

		$piece->id_book_record = $record->id;
		$piece->booked = 1;
		$piece->update();
		$return = [
			'success' => true,
			'message' => 'Le Facture ' . $piece->prefix . $piece->piece_number . ' a été comptabilisée avec succès',
		];
		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessBulkBook() {

		$account = new StdAccount(Configuration::get('EPH_PROFIT_DEFAULT_ACCOUNT'));
		$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));
		$idPieces = Tools::getValue('pieces');

		foreach ($idPieces as $id) {
			$piece = new CustomerPieces($id);

			if ($piece->booked == 1) {
				continue;
			}

			$student = new Customer($piece->id_customer);
			$studentAccount = new StdAccount($student->id_stdaccount);
			$record = new BookRecords();
			$record->id_book_diary = 7;

			$record->name = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
			$record->piece_type = 'Pièce Client';
			$studentAccount = new StdAccount($student->id_stdaccount);

			$record->date_add = $piece->date_add;
			$success = $record->add();

			if ($success) {

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $studentAccount->id;
				$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
				$detail->piece_number = $piece->id;
				$detail->debit = $piece->total_tax_incl;
				$detail->date_add = $record->date_add;
				$detail->add();
				$studentAccount->pointed_solde = $studentAccount->pointed_solde + $piece->total_tax_incl;
				$studentAccount->update();

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $account->id;
				$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
				$detail->piece_number = $piece->id;
				$detail->credit = $piece->total_with_freight_tax_excl;
				$detail->date_add = $record->date_add;
				$detail->add();
				$account->pointed_solde = $account->pointed_solde - $piece->total_with_freight_tax_excl;
				$account->update();

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = !empty($account->default_vat) ? $account->default_vat : $vatAccount->id;
				$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
				$detail->piece_number = $piece->id;
				$detail->credit = $piece->total_tax;
				$detail->date_add = $record->date_add;
				$detail->add();
				$vatAccount->pointed_solde = $vatAccount->pointed_solde - $piece->total_tax;
				$vatAccount->update();
			}

			$piece->id_book_record = $record->id;
			$piece->booked = 1;
			$piece->update();

		}

		$result = [
			'success' => true,
			'message' => $this->l('Les pièces ont été comptabilisées avec succès'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessBulkValidate() {

		$idPieces = Tools::getValue('idPieces');

		foreach ($idPieces as $id) {
			$piece = new CustomerPieces($id);

			if (Validate::isLoadedObject($piece)) {
				$piece->piece_type = 'INVOICE';
				$piece->validate = 1;
				$piece->update();

			} else {

				$this->errors[] = Tools::displayError('An error occurred while loading the piece.');
			}

		}

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {
			$result = [
				'success' => true,
				'message' => $this->l('Les pièces ont été mises à jour avec succès'),
			];

		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessPrintPieceToPdf() {

		$template = new InvoiceModel($model);
		$context = Context::getContext();
		$customerPiece = new CustomerPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';
		$customer = new Customer($customerPiece->id_customer);

		$idPiece = Tools::getValue('idPiece');
		$model = Configuration::get('PS_INVOICE_MODEL');
		$address = new Address($customerPiece->id_address_invoice);
		$headerTemplate = 'headertemplate.tpl';
		$bodyTemplate = 'bodytemplate.tpl';

		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		$pathLogo = $this->getLogo();

		$width = 0;
		$height = 0;

		if (!empty($pathLogo)) {
			list($width, $height) = getimagesize($pathLogo);
		}

		// Limit the height of the logo for the PDF render
		$maximumHeight = 150;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$payments = Payment::getByCustomerPieceId($customerPiece->id, $this->context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $headerTemplate);

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $pathLogo,
				'width_logo'     => $width,
				'height_logo'    => $height,
				'piece'          => $customerPiece,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLHeader($data->fetch());

		$data = $this->createTemplate('controllers/customer_pieces/pdf/footertemplate.tpl');

		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $logo_path,
				'piece'            => $customerPiece,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails'   => $productDetails,
				'studentEducation' => $studentEducation,
				'customer'         => $customer,
				'address'          => $address,
			]
		);
		$mpdf->SetHTMLFooter($data->fetch(), 'O');

		$data = $this->createTemplate('controllers/customer_pieces/pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $bodyTemplate);

		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $logo_path,
				'piece'            => $customerPiece,
				'studentEducation' => $studentEducation,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails'   => $productDetails,
				'customer'         => $customer,
				'address'          => $address,
				'fields'           => $template->fields,
			]
		);

		if ($customerPiece->validate == 0 && $customerPiece->piece_type == 'INVOICE') {
			$watermark = $this->l('Provisoire');
			$mpdf->SetWatermarkText($watermark);
		} else

		if ($customerPiece->validate == 1 && $customerPiece->piece_type == 'INVOICE') {
			$mpdf->SetProtection(['copy', 'print'], '', _DB_PASSWD_);
		}

		$filePath = _PS_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'fileExport' . DIRECTORY_SEPARATOR;
		$fileName = $customerPiece->prefix . $customerPiece->piece_number . '.pdf';
		$mpdf->SetTitle($context->company->company_name . " " . $this->getStaticPieceName($customerPiece->piece_type) . " " . $customerPiece->prefix . $customerPiece->piece_number);
		$mpdf->SetAuthor($context->company->company_name);
		$mpdf->showWatermarkText = true;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');

		$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($data->fetch());

		$mpdf->Output($filePath . $fileName, F);
		$fileToUpload = 'fileExport' . DIRECTORY_SEPARATOR . $fileName;
		$link = '<a  target="_blank"  class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="pieceDownloadFile" class="btn btn-default" href="' . $fileToUpload . '">' . $this->l('Voir, imprimer ou télécharger') . '</a>';

	}

	public function ajaxProcessPieceToPdf() {

		$idPiece = Tools::getValue('idPiece');
		$model = Tools::getValue('model');
		$template = new InvoiceModel($model);
		$context = Context::getContext();
		$customerPiece = new CustomerPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';
		$customer = new Customer($customerPiece->id_customer);

		$address = new Address($customerPiece->id_address_invoice);
		$pieceTemplate = 'piecetemplate.tpl';

		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		if (Configuration::get('PS_LOGO_INVOICE', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop);
			$logo_path = _PS_IMG_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop);
		} else

		if (Configuration::get('PS_LOGO', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop);
			$logo_path = _PS_IMG_ . Configuration::get('PS_LOGO', null, null, $idShop);
		}

		$width = 0;
		$height = 0;

		if (!empty($logo_path)) {
			list($width, $height) = getimagesize($logo_path);
		}

		$maximumHeight = 100;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$payments = Payment::getByCustomerPieceId($customerPiece->id, $context->language->id);
		$data = $this->createTemplate('controllers/customer_pieces/' . $pieceTemplate);
		$link = $this->renderPdf($idPiece, $template);
		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $logo_path,
				'piece'            => $customerPiece,
				'studentEducation' => $studentEducation,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails'   => $productDetails,
				'customer'         => $customer,
				'address'          => $address,
				'link'             => $link,
				'template'         => $template,
				'defaultTemplates' => $template->fields,
			]
		);
		$json = [
			'id'  => $customerPiece->id,
			'tpl' => $data->fetch(),
		];
		die(Tools::jsonEncode($json));

	}

	public function renderPdf($idPiece, $template) {

		$idPiece = Tools::getValue('idPiece');
		$context = Context::getContext();
		$customerPiece = new CustomerPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';
		$customer = new Customer($customerPiece->id_customer);

		$address = new Address($customerPiece->id_address_invoice);
		$headerTemplate = 'headertemplate.tpl';
		$bodyTemplate = 'bodytemplate.tpl';

		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		$pathLogo = $this->getLogo();

		$width = 0;
		$height = 0;

		if (!empty($pathLogo)) {
			list($width, $height) = getimagesize($pathLogo);
		}

		// Limit the height of the logo for the PDF render
		$maximumHeight = 150;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$payments = Payment::getByCustomerPieceId($customerPiece->id, $this->context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $headerTemplate);

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $pathLogo,
				'width_logo'     => $width,
				'height_logo'    => $height,
				'piece'          => $customerPiece,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLHeader($data->fetch());

		$data = $this->createTemplate('controllers/customer_pieces/pdf/footertemplate.tpl');

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $logo_path,
				'piece'          => $customerPiece,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLFooter($data->fetch(), 'O');

		$data = $this->createTemplate('controllers/customer_pieces/pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $bodyTemplate);

		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $logo_path,
				'piece'            => $customerPiece,
				'studentEducation' => $studentEducation,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails'   => $productDetails,
				'customer'         => $customer,
				'address'          => $address,
				'fields'           => $template->fields,
			]
		);

		if ($customerPiece->validate == 0 && $customerPiece->piece_type == 'INVOICE') {
			$watermark = $this->l('Provisoire');
			$mpdf->SetWatermarkText($watermark);
		} else

		if ($customerPiece->validate == 1 && $customerPiece->piece_type == 'INVOICE') {
			$mpdf->SetProtection(['copy', 'print'], '', _DB_PASSWD_);
		}

		$filePath = _PS_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
		$fileName = $customerPiece->prefix . $customerPiece->piece_number . '.pdf';
		$mpdf->SetTitle($context->company->company_name . " " . $this->getStaticPieceName($customerPiece->piece_type) . " " . $customerPiece->prefix . $customerPiece->piece_number);
		$mpdf->SetAuthor($context->company->company_name);
		$mpdf->showWatermarkText = true;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');

		$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($data->fetch());

		$mpdf->Output($filePath . $fileName, 'F');
		$fileToUpload = 'invoices' . DIRECTORY_SEPARATOR . $fileName;
		$link = '<a  target="_blank"  class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="pieceDownloadFile" class="btn btn-default" href="' . $fileToUpload . '">' . $this->l('Voir, imprimer ou télécharger') . '</a>';
		return $link;

	}

	public function ajaxProcessPrintPdf() {

		$idPiece = Tools::getValue('idPiece');

		$context = Context::getContext();
		$customerPiece = new CustomerPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';
		$customer = new Customer($customerPiece->id_customer);

		$model = Configuration::get('PS_INVOICE_MODEL');
		$template = new InvoiceModel($model);
		$address = new Address($customerPiece->id_address_invoice);
		$headerTemplate = 'headertemplate.tpl';
		$bodyTemplate = 'bodytemplate.tpl';

		$fileName = "Facture_" . $prefix . $customerPiece->piece_number . '_' . $customer->lastname . '_' . $customer->firstname . '.pdf';

		if (file_exists('invoices' . DIRECTORY_SEPARATOR . $fileName)) {
			$response = [
				'fileExport' => 'invoices' . DIRECTORY_SEPARATOR . $fileName,
			];
			die(Tools::jsonEncode($response));
		}

		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		$pathLogo = $this->getLogo();

		$width = 0;
		$height = 0;

		if (!empty($pathLogo)) {
			list($width, $height) = getimagesize($pathLogo);
		}

		// Limit the height of the logo for the PDF render
		$maximumHeight = 150;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$payments = Payment::getByCustomerPieceId($customerPiece->id, $context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $headerTemplate);

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $pathLogo,
				'width_logo'     => $width,
				'height_logo'    => $height,
				'piece'          => $customerPiece,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLHeader($data->fetch());

		$data = $this->createTemplate('controllers/customer_pieces/pdf/footertemplate.tpl');

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $logo_path,
				'piece'          => $customerPiece,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLFooter($data->fetch(), 'O');

		$data = $this->createTemplate('controllers/customer_pieces/pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $bodyTemplate);

		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $logo_path,
				'piece'            => $customerPiece,
				'studentEducation' => $studentEducation,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
				'productDetails'   => $productDetails,
				'customer'         => $customer,
				'address'          => $address,
				'fields'           => $template->fields,
			]
		);

		if ($customerPiece->validate == 0 && $customerPiece->piece_type == 'INVOICE') {
			$watermark = $this->l('Provisoire');
			$mpdf->SetWatermarkText($watermark);
		} else

		if ($customerPiece->validate == 1 && $customerPiece->piece_type == 'INVOICE') {
			$mpdf->SetProtection(['copy', 'print'], '', _DB_PASSWD_);
		}

		$filePath = _PS_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;

		$mpdf->SetTitle($context->company->company_name . " " . $this->getStaticPieceName($customerPiece->piece_type) . " " . $customerPiece->prefix . $customerPiece->piece_number);
		$mpdf->SetAuthor($context->company->company_name);
		$mpdf->showWatermarkText = true;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');

		$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($data->fetch());

		$mpdf->Output($filePath . $fileName, F);

		$response = [
			'fileExport' => 'invoices' . DIRECTORY_SEPARATOR . $fileName,
		];
		die(Tools::jsonEncode($response));

	}

	public function ajaxProcessgenerateBulkprint() {

		$idPieces = Tools::getValue('pieces');
		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		$pathLogo = $this->getLogo();

		$filePath = 'invoices' . DIRECTORY_SEPARATOR;

		$fileExport = [];

		foreach ($idPieces as $idPiece) {

			$customerPiece = new CustomerPieces($idPiece);
			$prefix = $this->getStaticPrefix($customerPiece->piece_type);
			$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
			$productDetails = $customerPiece->getProductsDetail();
			$studentEducation = '';
			$customer = new Customer($customerPiece->id_customer);

			$model = Configuration::get('PS_INVOICE_MODEL');
			$template = Tools::jsonDecode(EmployeeConfiguration::get($model), true);
			$address = new Address($customerPiece->id_address_invoice);
			$headerTemplate = 'headertemplate.tpl';
			$bodyTemplate = 'bodytemplate.tpl';

			$fileName = "Facture_" . $prefix . $customerPiece->piece_number . '_' . $customer->lastname . '_' . $customer->firstname . '.pdf';

			if (file_exists('invoices' . DIRECTORY_SEPARATOR . $fileName)) {
				$fileExport[] = 'invoices' . DIRECTORY_SEPARATOR . $fileName;
				continue;
			}

			$payments = Payment::getByCustomerPieceId($customerPiece->id, $context->language->id);
			$mpdf = new \Mpdf\Mpdf([
				'margin_left'   => 10,
				'margin_right'  => 10,
				'margin_top'    => 120,
				'margin_bottom' => 75,
				'margin_header' => 10,
				'margin_footer' => 10,
			]);

			$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $headerTemplate);

			$data->assign(
				[
					'company'        => $context->company,
					'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
					'logo_path'      => $pathLogo,
					'width_logo'     => $width,
					'height_logo'    => $height,
					'piece'          => $customerPiece,
					'payments'       => $payments,
					'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
					'productDetails' => $productDetails,
					'customer'       => $customer,
					'address'        => $address,
				]
			);
			$mpdf->SetHTMLHeader($data->fetch());

			$data = $this->createTemplate('controllers/customer_pieces/pdf/footertemplate.tpl');

			$data->assign(
				[
					'company'        => $context->company,
					'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
					'logo_path'      => $logo_path,
					'piece'          => $customerPiece,
					'payments'       => $payments,
					'nameType'       => $this->getStaticPieceName($customerPiece->piece_type),
					'productDetails' => $productDetails,
					'customer'       => $customer,
					'address'        => $address,
				]
			);
			$mpdf->SetHTMLFooter($data->fetch(), 'O');

			$data = $this->createTemplate('controllers/customer_pieces/pdf.css.tpl');
			$data->assign(
				[
					'color' => $template->color,
				]
			);
			$stylesheet = $data->fetch();

			$data = $this->createTemplate('controllers/customer_pieces/pdf/' . $bodyTemplate);

			$data->assign(
				[
					'company'          => $context->company,
					'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
					'logo_path'        => $logo_path,
					'piece'            => $customerPiece,
					'studentEducation' => $studentEducation,
					'payments'         => $payments,
					'nameType'         => $this->getStaticPieceName($customerPiece->piece_type),
					'productDetails'   => $productDetails,
					'customer'         => $customer,
					'address'          => $address,
					'fields'           => $template->fields,
				]
			);

			if ($customerPiece->validate == 0 && $customerPiece->piece_type == 'INVOICE') {
				$watermark = $this->l('Provisoire');
				$mpdf->SetWatermarkText($watermark);
			} else

			if ($customerPiece->validate == 1 && $customerPiece->piece_type == 'INVOICE') {
				$mpdf->SetProtection(['copy', 'print'], '', _DB_PASSWD_);
			}

			$mpdf->SetTitle($context->company->company_name . " " . $this->getStaticPieceName($customerPiece->piece_type) . " " . $customerPiece->prefix . $customerPiece->piece_number);
			$mpdf->SetAuthor($context->company->company_name);
			$mpdf->showWatermarkText = true;
			$mpdf->watermark_font = 'DejaVuSansCondensed';
			$mpdf->watermarkTextAlpha = 0.1;
			$mpdf->SetDisplayMode('fullpage');

			$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
			$mpdf->WriteHTML($data->fetch());

			$mpdf->Output($filePath . $fileName, 'F');

			$fileExport[] = 'invoices' . DIRECTORY_SEPARATOR . $fileName;
		}

		$zip = new ZipArchive;
		$tag = date("H-i-s");

		if ($zip->open(_PS_EXPORT_DIR_ . 'export_facture' . $tag . '.zip', ZipArchive::CREATE) === TRUE) {

			foreach ($fileExport as $invoice) {
				$zip->addFile($invoice, basename($invoice));
			}

			$zip->close();

			$response = [
				'fileExport' => 'fileExport' . DIRECTORY_SEPARATOR . 'export_facture' . $tag . '.zip',
			];
			die(Tools::jsonEncode($response));
		}

	}

	protected function getLogo() {

		$logo = '';
		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		if (Configuration::get('PS_LOGO_INVOICE', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop);
		} else

		if (Configuration::get('PS_LOGO', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop);
		}

		return $logo;
	}

	public function ajaxProcesstransfertPiece() {

		$idPiece = Tools::getValue('idPiece');

		$type = Tools::getValue('type');

		switch ($type) {
		case "ORDER":
			$idCustomerPieceState = 3;
			$message = 'Le devis à été transférer en commande avec succès';
			break;
		case "DELIVERYFORM":
			$idCustomerPieceState = 4;
			$message = 'La pièce à été transférer en bon de livraison avec succès';
			break;
		case "INVOICE":
			$idCustomerPieceState = 19;
			$message = 'La pièce à été transférer en facture avec succès';
			break;
		case "ASSET":
			$idCustomerPieceState = 18;
			break;
		}

		$piece = new CustomerPieces($idPiece);

		if ($piece->piece_type == 'INVOICE' && $type == 'ASSET') {
			$piece = $piece->duplicateObject();
			$message = 'Un avoir pour contre balancer la pièce à été créer avec succès';
		}

		$piece->current_state = $idCustomerPieceState;
		$piece->piece_type = $type;
		$piece->validate = 0;
		$piece->update();

		$response = [
			'success' => true,
			'message' => $message,
		];
		die(Tools::jsonEncode($response));

	}

}
