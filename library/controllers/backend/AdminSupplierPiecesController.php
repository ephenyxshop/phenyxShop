<?php

/**
 * Class AdminSupplierPiecesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminSupplierPiecesControllerCore extends AdminController {

	public $php_self = 'adminsupplierpieces';
	// @codingStandardsIgnoreStart
	/** @var string $toolbar_title */
	public $toolbar_title;
	/** @var array $statuses_array */
	protected $statuses_array = [];
	// @codingStandardsIgnoreEnd

	public $validateSelector;

	public $paymentSelector;

	public $countrySupplierPiecesSelector;

	public $pieceTypes = [];

	public $pieceType = [];

	public $configurationDetailField = [];

	static $_customer_eelected;

	static $_pieceDetails = [];

	public $defaultTemplate;

	/**
	 * AdminSupplierPiecesControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'supplier_pieces';
		$this->className = 'SupplierPieces';
		$this->publicName = $this->l('Pièces Fournisseur');
		$this->lang = false;
		$this->identifier = 'id_supplier_piece';
		$this->controller_name = 'AdminSupplierPieces';

		$this->context = Context::getContext();

		EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_SCRIPT', $this->generateParaGridScript(true));
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_FIELDS', Tools::jsonEncode($this->getSupplierPiecesFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_FIELDS', Tools::jsonEncode($this->getSupplierPiecesFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_FIELDS'), true);
		}

		EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS', Tools::jsonEncode($this->getDetailSupplierPiecesFields()));
		$this->configurationDetailField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS'), true);

		if (empty($this->configurationDetailField)) {
			EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS', Tools::jsonEncode($this->getDetailSupplierPiecesFields()));
			$this->configurationDetailField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS'), true);
		}

		$this->pieceTypes = [
			'INVOICE' => $this->l('Factures'),
			'ASSET'   => $this->l('Avoirs'),
		];

		$this->pieceType = [
			'INVOICE' => $this->l('Facture'),
			'ASSET'   => $this->l('Avoir'),
		];

		parent::__construct();

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_PS_JS_DIR_ . 'supplierpieces.js',
			_PS_JS_DIR_ . 'multiselect.js',
		]);
	}

	public function ajaxProcessOpenTargetController() {

		$this->paragridScript = $this->generateParaGridScript();
		$this->setAjaxMedia();

		$data = $this->createTemplate($this->table . '.tpl');

		$extracss = $this->pushCSS([
			$this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/supplier_invoice.css',

		]);

		$agentDashboard = [];

		$agents = new PhenyxShopCollection('SaleAgent');

		foreach ($agents as $agent) {

			if ($agent->sale_commission_amount > 0) {
				$agent->due = SaleAgentCommission::getCommissionDueBySaleAgent($agent->id);
				$agentDashboard[] = $agent;
			}

		}

		$data->assign([
			'paragridScript'     => $this->paragridScript,
			'manageHeaderFields' => $this->manageHeaderFields,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
			'controller'         => $this->controller_name,
			'tableName'          => $this->table,
			'className'          => $this->className,
			'link'               => $this->context->link,
			'agents'             => $agentDashboard,
			'extraJs'            => $this->push_js_files,
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

	public function initContent($token = null) {

		$this->displayGrid = true;
		$this->paramGridObj = 'objSupplierPieces';
		$this->paramGridVar = 'gridSupplierPieces';
		$this->paramGridId = 'grid_AdminSupplierPieces';

		$this->toolbar_title = $this->l('Géstion des factures Fournisseur');

		$this->TitleBar = $this->l('Liste des pièces fournisseurs');

		$this->context->smarty->assign([
			'pieceTypes'              => $this->pieceTypes,
			'manageHeaderFields'      => true,
			'customHeaderFields'      => $this->manageFieldsVisibility($this->configurationField),
			'controller'              => $this->controller_name,
			'linkController'          => $this->context->link->getAdminLink($this->controller_name),
			'className'               => 'SupplierPieces',
			'titleBar'                => $this->TitleBar,
			'gridId'                  => $this->paramGridId,
			'tableName'               => $this->table,
			'displayBackOfficeHeader' => $this->displayBackOfficeHeader,
			'displayBackOfficeFooter' => $this->displayBackOfficeFooter,
			'paragridScript'          => $this->generateParaGridScript(),
		]);

		parent::initContent();

	}

	public function generateParaGridScript($regenerate = false) {

		$ajaxlink = $this->context->link->getAdminLink($this->controller_name);
		$pieceTypes = [
			'INVOICE' => $this->l('Facture'),
			'ASSET'   => $this->l('Avoir'),
		];
		$pieces = [
			'INVOICE' => $this->l('Facture'),
			'ASSET'   => $this->l('Avoir'),
		];

		$this->paramExtraFontcion = [
			'


			function proceedBulkUpdate(selector, target) {

			var selectionArray = selector.getSelection();
			var idpieces = [];
			$.each(selectionArray, function(index, value) {
			idpieces.push(value.rowData.id_supplier_piece);

			})

			$.ajax({
				type: \'POST\',
				url: AjaxLinkAdminSupplierPieces,
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
						gridSupplierPieces.refreshDataAndView();
					} else {
						showErrorMessage(data.message);
					}
				}
				});

			}
			function validateBulkPieces(selector) {

				var selectionArray = selector.getSelection();
				var idpieces = [];
				$.each(selectionArray, function(index, value) {
					idpieces.push(value.rowData.id_supplier_piece);
				})

				$.ajax({
					type: \'POST\',
					url: AjaxLinkAdminSupplierPieces,
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
							gridSupplierPieces.refreshDataAndView();
						} else {
							showErrorMessage(data.message);
						}
					}
				});

			}
			function bookBulkPieces(selector) {
				$("html").addClass("csstransitions");
				isAnimating = true;
				var selectionArray = selector.getSelection();

				var idpieces = [];
				$.each(selectionArray, function(index, value) {
					idpieces.push(value.rowData.id_supplier_piece);
				})

				$("#content").addClass("page-is-changing");
				$(".cd-loading-bar").one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {

					proceedbookBulkPieces(idpieces);
					$(".cd-loading-bar").off("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend");
				});
			}
			function proceedbookBulkPieces(idpieces) {

				$.ajax({
					type: \'POST\',
					url: AjaxLinkAdminSupplierPieces,
					data: {
						action: \'bulkBook\',
						idPieces: idpieces,
						ajax: true
					},
					async: false,
					dataType: \'json\',
					success: function(data) {
						if (data.success) {
							showSuccessMessage(data.message);
							gridSupplierPieces.refreshDataAndView();
						} else {
							showErrorMessage(data.message);
						}
					},
					complete: function(data) {
						$("#content").removeClass("page-is-changing");
						$(".cd-loading-bar").one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
							isAnimating = false;
							$(".cd-loading-bar").off("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend");
						});
					}
				});

			}
			function buildStudentPiecesFilter(){

			$("#pieceSessionSelect" ).selectmenu({
				classes: {
    				"ui-selectmenu-menu": "scrollable"
  				},
	   			"change": function(event, ui) {
		   			gridSupplierPieces.filter({
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
					gridSupplierPieces.filter({
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
		$this->windowHeight = '300';
		$this->paramPageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];

		$this->rowInit = 'function (ui) {
			var applyStyle;
            if(ui.rowData.isLocked) {
            	return {
                attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData' . $this->identifier . '+\' "\', ' . $class . '
                };
            }  else {
                return {
				attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData' . $this->identifier . '+\' "\',
                };
            }
        }';
		$this->paramComplete = 'function(){
		window.dispatchEvent(new Event(\'resize\'));
		buildStudentPiecesFilter();
		$("#supplierPieceTypeSelector").selectmenu({
		width: 200,
    	"change": function(event, ui) {
        	gridSupplierPieces.filter({
            	mode: "AND",
                rules: [
                	{ dataIndx:"piece_type", condition: "equal", value: ui.item.value}
                 ]
            });
        }
     });

	 	$("#addNewSupplierpieces").selectmenu({
			width: 200,
			"change": function(event, ui) {
				if(ui.item.value != 0) {
					generateNewSupplierPiece(ui.item.value);
					$("#addNewpieces").val(0);
					$("#addNewpieces").selectmenu("refresh");
				}
			}
		});
        }';

		$this->paramToolbar = [
			'items' => [

				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Gérer les champs affiché') . '\'',
					'cls'   => '\'showFields changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'  => '\'id="page-header-desc-supplier_pieces-fields_edit"\'',
				],
				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Ajouter une facture pour un CEF') . '\'',
					'cls'      => '\'changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'generateCefInvoice',
				],
				[
					'type'    => '\'select\'',
					'icon'    => '\'ui-icon-disk\'',
					'attr'    => '\'id="supplierPieceTypeSelector"\'',
					'options' => '[
            			{"": "Sélectionner le Type"},
						{"INVOICE": "Facture"},
						{"ASSET": "Avoir"},
						]',
				],
				[
					'type'    => '\'select\'',
					'attr'    => '\'id="addNewSupplierpieces"\'',
					'cls'     => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'options' => '[
            			{"0": "Ajouter une pièces"},
						{"INVOICE": "Facture"},
						{"ASSET": "Avoir"},
						]',
				],
			],
		];
		$this->rowDblClick = 'function( event, ui ) {
			editSupplierPiece(ui.rowData.id_supplier_piece);
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

		$this->paramTitle = '\'' . $this->l('Gestion des pièces Fournisseur') . '\'';
		$this->summaryData = '[{
                rank: \'Total\',
                summaryRow: true,
                pq_fn: {
                total_products_tax_excl: \'sum(L:L)\',
                total_shipping_tax_excl: \'sum(M:M)\',
                total_products_tax_incl: \'sum(N:N)\',
                total_tax_excl: \'sum(P:P)\',
                total: \'sum(Q:Q)\',
                total_paid:  \'sum(Q:Q)\',
                balanceDue:  \'sum(R:R)\',
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
                            name: \'' . $this->l('Ajouter') . ' \',
                            icon: "add",
                            items: {

                                "inv": {
                                    name: \'' . $this->l('Une facture') . ' \',
                                    icon: "edit",
                                    callback: function(itemKey, opt, e) {
                                        generateNewSupplierPiece("INVOICE");
                                    }
                                },
                                "asst": {
                                    name: \'' . $this->l('Un Avoir') . ' \',
                                    icon: "edit",
                                    callback: function(itemKey, opt, e) {
                                        generateNewSupplierPiece("ASSET");
                                    }
                                },
                            }

                            },
                        "edit": {
                            name: \'' . $this->l('Modifier ou visualiser la pièce ') . ' \'+rowData.pieceType+ \' :\'+rowData.pieceNumber,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
								editSupplierPiece(rowData.id_supplier_piece);
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
								validatePieces(rowData.id_supplier_piece);
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
								validateBulkPieces(selgridSupplierPieces);
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
								if(rowData.isLocked ==false) {
                                    return false;
                                }
								if(rowData.balanceDue == 0) {
                                    return false;
                                }
								if(rowData.piece_type == \'INVOICE\' && rowData.isBooked  == 0) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {

								generateReglement(rowData.id_supplier_piece);
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
									if(value.rowData.balanceDue == 0) {
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
								bookPieces(rowData.id_supplier_piece);
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
									 if(value.rowData.isBooked  == 1) {
                                    allowed = false;
                                }
  								});
								if(allowed == false) {
									return false;
								}


                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								bookBulkPieces(selgridSupplierPieces);
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
                                deleteSupplierPiece(rowData.id_supplier_piece);
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

	public function manageFieldsVisibility($fields) {

		return parent::manageFieldsVisibility($fields);
	}

	public function ajaxProcessupdateVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_FIELDS'), true);
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
		EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_FIELDS', $headerFields);
		die($headerFields);
	}

	public function ajaxProcessUpdateDetailVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS'), true);
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
		EmployeeConfiguration::updateValue('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS', $headerFields);
		die($headerFields);
	}

	public function getSupplierPiecesRequest() {

		$orders = SupplierPieces::getRequest();

		$orderLink = $this->context->link->getAdminLink($this->controller_name);

		foreach ($orders as &$order) {

			$order['pieceNumber'] = $this->getStaticPrefix($order['piece_type']) . $order['piece_number'];

			$order['pieceType'] = $this->pieceType[$order['piece_type']];

			if (empty($order['paymentMode'])) {
				$order['paymentMode'] = $order['module'];
			}

		}

		$orders = $this->removeRequestFields($orders);

		return $orders;

	}

	public function ajaxProcessgetSupplierPiecesRequest() {

		die(Tools::jsonEncode($this->getSupplierPiecesRequest()));

	}

	public function getSupplierPiecesFields() {

		return [
			[
				'title'    => $this->l('ID'),
				'width'    => 50,
				'dataIndx' => 'id_supplier_piece',
				'dataType' => 'integer',
				'editable' => false,
				'align'    => 'center',

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
				'title'    => $this->l('Fournisseur / CEF'),
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
				'exWidth'  => 20,
				'dataIndx' => 'total_products_tax_excl',
				'align'    => 'right',
				'halign'   => 'HORIZONTAL_RIGHT',
				'editable' => false,
				'valign'   => 'center',
				'dataType' => 'float',
				'hidden'   => true,
				'format'   => '# ##0,00 € ' . $this->l('HT'),

			],
			[
				'title'    => $this->l('Frais de port HT.'),
				'width'    => 150,
				'exWidth'  => 20,
				'dataIndx' => 'total_shipping_tax_excl',
				'align'    => 'right',
				'halign'   => 'HORIZONTAL_RIGHT',
				'editable' => false,
				'valign'   => 'center',
				'hidden'   => true,
				'dataType' => 'float',
				'format'   => '# ##0,00 € ' . $this->l('HT.'),
			],
			[
				'title'        => $this->l('Total Produit TTC'),
				'width'        => 150,
				'exWidth'      => 20,
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
				'title'    => $this->l('Date de la pièce'),
				'minWidth' => 150,
				'exWidth'  => 20,
				'dataIndx' => 'date_add',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'cls'      => 'pq-calendar pq-side-icon',
				'editable' => false,

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
				'dataIndx'   => 'deleteLink',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[

				'dataIndx'   => 'id_payment_mode',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'dataIndx'   => 'id_sale_agent',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'dataIndx'   => 'id_supplier',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'id_education_session',
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
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'isBooked',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[

				'dataIndx'   => 'is_book',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
		];

	}

	public function ajaxProcessgetSupplierPiecesFields() {

		die(EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_FIELDS'));
	}

	public function getDetailSupplierPiecesFields() {

		return [
			[
				'dataIndx'   => 'id_supplier_piece_detail',
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
				'dataIndx'   => 'id_supplier_piece',
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
				'title'    => $this->l('Reference'),
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
				'title'    => $this->l('Produit / Formation'),
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
				'dataIndx'   => 'origin_tax_excl',
				'hidden'     => true,
				'hiddenable' => 'no',
				'dataType'   => 'float',
			],
			[
				'title'    => $this->l('Prix base HT'),
				'width'    => 120,
				'dataIndx' => 'unit_tax_excl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('HT.'),

			],
			[
				'title'    => $this->l('Reduction %'),
				'width'    => 120,
				'dataIndx' => 'reduction_percent',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 %",
			],
			[
				'title'    => $this->l('Reduction HT'),
				'width'    => 120,
				'dataIndx' => 'reduction_amount_tax_excl',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('HT.'),
			],
			[
				'title'    => $this->l('Reduction TTC'),
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
				'title'    => $this->l('Taux TVA'),
				'width'    => 100,
				'dataIndx' => 'tax_rate',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 %",
				'align'    => 'center',

			],
			[
				'title'    => $this->l('Total HT.'),
				'width'    => 120,
				'dataIndx' => 'total_tax_excl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => '# ##0,00 €' . $this->l('HT.'),
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
				'dataIndx'   => 'id_tax_rules_group',
				'dataType'   => 'integer',
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
			[
				'dataIndx'   => 'id_education',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'id_education_attribute',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'dataIndx'   => 'id_sale_agent_commission',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

		];
	}

	public function ajaxProcessGetDetailSupplierPiecesFields() {

		die(Tools::jsonEncode($this->getDetailSupplierPiecesFields()));
	}

	public function getDetailSupplierPiecesRequest($idSupplierPiece) {

		$details = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('supplier_piece_detail')
				->where('`id_supplier_piece` = ' . $idSupplierPiece)
				->orderBy('`id_supplier_piece_detail` ASC')
		);

		if (!empty($details)) {
			$details = $this->removeDetailRequestFields($details);
		}

		return $details;
	}

	public function ajaxProcessGetDetailSupplierPiecesRequest() {

		$idPiece = Tools::getValue('idPiece');
		die(Tools::jsonEncode($this->getDetailSupplierPiecesRequest($idPiece)));

	}

	public function removeDetailRequestFields($requests) {

		$objects = [];

		$fields = [];
		$gridFields = $this->getDetailSupplierPiecesFields();

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

	public static function setSupplierPiecesCurrency($echo, $tr) {

		$order = new SupplierPieces($tr['id_order']);

		return Tools::displayPrice($echo, (int) $order->id_currency);
	}

	public function ajaxProcessValidateSupplierPieces() {

		$id_student_piece = Tools::getValue('id_student_piece');
		$this->object = new $this->className($id_student_piece);
		$this->object->validate = 1;
		$this->object->update();
		//$this->printPdf($this->object-- > id);
		$result = [
			'success' => true,
			'message' => $this->l('La pièce a été validée avec succès '),
		];

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessBulkValidate() {

		$idPieces = Tools::getValue('idPieces');

		foreach ($idPieces as $id) {
			$piece = new $this->className($id);

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
				'message' => $this->l('Les pièces ont été validées avec succès'),
			];

		}

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

	public function ajaxProcessDeleteSupplierPiece() {

		$id_supplier_piece = Tools::getValue('id_supplier_piece');
		$this->object = new $this->className($id_supplier_piece);
		$this->object->delete();

		$result = [
			'success' => true,
			'message' => $this->l('La pièce a été supprimée avec succès'),
		];
		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessEditSupplierPiece() {

		$id_supplier_piece = Tools::getValue('id_supplier_piece');
		$this->object = new $this->className($id_supplier_piece);
		$this->object->prefix = $this->getStaticPrefix($this->object->piece_type);
		$this->object->nameType = $this->getStaticPieceName($this->object->piece_type);
		$customerAddress = '';
		$returnAddress = [];
		$file = fopen("testProcessEditSupplierPiece.txt", "w");

		if ($this->object->id_sale_agent > 0) {
			$saleAgent = new SaleAgent($this->object->id_sale_agent);
			fwrite($file, $saleAgent->id_customer . PHP_EOL);

			$customer = new Customer($saleAgent->id_customer);
			fwrite($file, $customer->firstname . PHP_EOL);

			$customerLastAddress = $this->getLastCustomerAddressId((int) $customer->id);
			$addresses = $customer->getAddresses($this->context->language->id);

			foreach ($addresses as $key => $addresse) {
				$returnAddress[$addresse['id_address']] = $addresse;
			}

			$invoiceAddress = new Address($this->object->id_address_invoice);
			$data = $this->createTemplate('controllers/supplier_pieces/detailSaleAgentPiece.tpl');
		} else {
			$customer = new Supplier($this->object->id_supplier);
			$data = $this->createTemplate('controllers/supplier_pieces/detailStudentPiece.tpl');
		}

		$this->context->smarty->assign([
			'piece'               => $this->object,
			'nameType'            => $this->pieceType[$this->object->piece_type],
			'customer'            => $customer,
			'cust'                => $cust,
			'taxModes'            => TaxMode::getTaxModes(),
			'currency'            => $context->currency,
			'taxes'               => Tax::getRulesTaxes($this->context->language->id),
			'groups'              => Group::getGroups($this->context->language->id),
			'invoiceAddress'      => $invoiceAddress,
			'addresses'           => $addresses,
			'customerLastAddress' => $customerLastAddress,
			'customHeaderFields'  => $this->manageFieldsVisibility($this->configurationDetailField),
			'paymentModes'        => PaymentMode::getPaymentModes(),
			'invoiceModels'       => $this->getInvoicesModels(),

		]);

		$li = '<li id="uperEditAdminSupplierPieces" data-controller="newPieces"><a href="#contentEditAdminSupplierPieces">Modification de la : ' . $this->pieceType[$this->object->piece_type] . ' ' . $this->object->prefix . $this->object->piece_number . '</a><button type="button"     id="closeNewPiece" class="close tabdetail" data-id="uperEditAdminSupplierPieces"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentEditAdminSupplierPieces" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));

	}

	public function generateEditScript($addresses) {

		return '<script type="text/javascript">' . PHP_EOL . '
                    $(document).ready(function(){

                        idCustomer = $(\'#pieceCustomer\').val();

                        if(idCustomer != \'\') {
                            $(\'#pieceCustomer\').prop(\'disabled\', true);
                            idPiece = $("#piece_identifier).val();
                            $("#pieceDatepicker").datepicker( { dateFormat: "dd/mm/yy"});
                            $("#pieceDueDatepicker").datepicker( { dateFormat: "dd/mm/yy"});
                            $("#piecePaymentMode").selectmenu();
                            var validate = $(\'#validate_\').val();
                            var addresses = ' . $addresses . ';
                            formatEditPiece(,' . $addresses . ');
                            var objDetailOrder = buildDetailObject(idPiece);
                            customerPiecesGrid = pq.grid("#gridPiece-", objDetailOrder);
                            if(validate == 1) {
                                customerPiecesGrid.option( "editable", false );
                                $(\'#form-' . $this->table . '- :input\').prop("disabled", true);
                            }
                        }
                        ' . $this->buildPieceCustomMenu() . PHP_EOL . '
                    });' . PHP_EOL . '
                    function buildDetailObject(idPiece) {

                    return {
                        height: \'flex\',
                        width: \'100%\',
                        dataModel: {
                            recIndx: \'id_product\',
                            data: getDetailSupplierPiecesRequest(idPiece),
                        },
                        colModel: ' . EmployeeConfiguration::get('EXPERT_SUPPLIER_PIECES_DETAIL_FIELDS') . ',
                        scrollModel: {
                            autoFit: true,
                        },
                        numberCell: {
                            show: 1,
                        },
                        reactive: true,
                        stripeRows: true,
                        showTitle: 0,
                        collapsible: 0,
                        freezeCols: 1,
                        rowBorders: 1,
                        stripeRows: 1,
                        selectionModel: {
                            type: \'row\',
                        },
                        rowInit: function(ui) {
                            return {
                                cls: \'productLine\'
                            };
                        },
                        toolbar: {
                                items: [
                                {
                                    type: \'button\',
                                    cls: \'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\',
                                    attr: \'id="productAddButton"\',
                                    label: \'' . $this->l('Add a product') . '\',
                                    listener: function() {
                                        var rowData = {
                                            \'product_quantity\': 1,
                                            \'reduction_percent\':0,
                                            \'product_wholesale_price\':0,
                                            \'tax_rate\':0,
                                            \'origin_tax_excl\': 0,
                                            \'unit_tax_excl\': 0,
                                        };
                                        var rowIndx = window[\'customerPiecesGrid\' + identifier].addRow({
                                            rowData: rowData
                                        });
                                        window[\'customerPiecesGrid\' + identifier].goToPage({
                                            rowIndx: rowIndx
                                        });
                                        window[\'customerPiecesGrid\' + identifier].editFirstCellInRow({
                                            rowIndx: rowIndx
                                        });
                                        $("#grid_NewOrder").focus();
                                    }
                                },
                                {
                                    type: \'button\',
                                    cls: \'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\',
                                    label: \'' . $this->l('Delete the product') . '\',
                                    listener: function() {

                                    }
                                },


                        ],
                            },
                            summaryData: [{
                                rank: \'Total\',
                                summaryRow: true,
                                pq_fn: {
                                    total_tax_excl: \'sum(Q:Q)\',
                                    total_tax_incl: \'sum(R:R)\'
                                }
                            }],

                    };
                };' . PHP_EOL . '

                </script>' . PHP_EOL;

	}

	public function ajaxProcessAddNewSupplierPieces() {

		$this->object = new $this->className();
		$this->display = 'add';
		$context = Context::getContext();

		$this->identifier_value = 'new';
		$this->tab_identifier = 'viewAdminSupplierPieces-' . $this->identifier_value;
		$this->tab_link = 'tab-AdminSupplierPieces-' . $this->identifier_value;
		$this->tab_liId = 'view-AdminSupplierPieces-' . $this->identifier_value;
		$this->closeTabButton = '<button type="button" class="closeSupplierPiece tabdetail" data-id="' . $this->tab_liId . '" ><i class="icon-times-circle" aria-hidden="true"></i></button>';
		$this->setMedia();
		$this->ajax_js = '';
		$this->ajax = false;
		$this->display_header = false;
		$this->show_page_header_toolbar = false;
		$this->show_header_script = false;
		$this->show_footer_script = false;
		$this->tableName = $this->className . '-new';

		$type = Tools::getValue('type');
		$prefix = $this->getStaticPrefix($type);
		$increment = SupplierPieces::getIncrementByType($type);

		$data = $this->createTemplate('controllers/supplier_pieces/newPiece.tpl');

		$this->context->smarty->assign([
			'type'               => $type,
			'nameType'           => $this->pieceType[$type],
			'piece_number'       => $prefix . $increment,
			'taxModes'           => TaxMode::getTaxModes(),
			'currency'           => $context->currency,
			'taxes'              => Tax::getRulesTaxes($this->context->language->id),
			'groups'             => Group::getGroups($this->context->language->id),
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationDetailField),
			'paymentModes'       => PaymentMode::getPaymentModes(),
			'toolbar_btn'        => $this->page_header_toolbar_btn,
			'tabTitleBar'        => $this->page_header_toolbar_title,
			'title'              => $this->page_header_toolbar_title,
			'tableName'          => $this->tableName,
			'currentController'  => $this->controller_name,
			'currentTab'         => 'tab-AdminSupplierPieces-' . $this->identifier_value,
			'idController'       => $this->tab_identifier,
			'link'               => $this->context->link,
			'id_tab'             => $this->identifier_value,
			'formId'             => 'form-' . $this->table . '-' . $this->identifier_value,
			'dataId'             => $this->tab_liId,
			'tabScript'          => $this->generatenewPieceScript(),
		]);

		$this->initPageHeaderToolbar();
		$this->tab_name = $this->page_header_toolbar_title;

		$this->tabList = false;
		$_POST['controller'] = $this->controller_name;
		$this->content = $data->fetch();
		$this->ajaxLayout = true;

		$this->ajaxTabDisplay();

	}

	public function ajaxProcessGenerateNewPiece() {

		$type = Tools::getValue('type');
		$prefix = $this->getStaticPrefix($type);

		$data = $this->createTemplate('controllers/supplier_pieces/newPiece.tpl');

		$this->context->smarty->assign([
			'type'               => $type,
			'nameType'           => $this->pieceType[$type],
			'piece'              => $piece,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationDetailField),
			'taxModes'           => TaxMode::getTaxModes(),
			'currency'           => $this->context->currency,
			'taxes'              => Tax::getRulesTaxes($this->context->language->id),
			'groups'             => Group::getGroups($this->context->language->id),
			'paymentModes'       => PaymentMode::getPaymentModes(),
			'link'               => $this->context->link,
			'id_tab'             => $this->identifier_value,
		]);

		$li = '<li id="uperNewSupplierPieces" data-controller="newPieces"><a href="#contentNewSupplierPieces">Ajouter une pièce Fournisseur de type : ' . $this->pieceType[$type] . '</a><button type="button" onclick="closeSupplierPieceForm();" class="close tabdetail" data-id="uperNewSupplierPieces"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentNewSupplierPieces" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcesscreateNewCustomer() {

		$data = $this->createTemplate('controllers/supplier_pieces/newCustomer.tpl');

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

	public function ajaxProcessUpdateSupplierPiece() {

		$id_supplier_piece = Tools::getValue('id_supplier_piece');
		$pieceDetails = Tools::jsonDecode(Tools::getValue('details'), true);
		$result = false;
		$piece = new SupplierPieces($id_supplier_piece);

		foreach ($_POST as $key => $value) {

			if (property_exists($piece, $key) && $key != 'id_supplier_piece') {

				$piece->{$key}
				= $value;
			}

		}

		try {
			$result = $piece->update();
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
		}

		if ($result) {
			$piece->deletePieceDetatil();

			foreach ($pieceDetails as $details) {
				$object = new SupplierPieceDetail();
				$object->id_supplier_piece = $piece->id;
				$object->id_warehouse = 0;

				foreach ($details as $key => $value) {

					if (property_exists($object, $key) && $key != 'id_supplier_piece') {
						$object->{$key}
						= $value;
					}

				}

				try {
					$result = $object->add();
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
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
				'message' => $this->l('La pièce à été mise à jour avec succès'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessSaveNewPiece() {

		$file = fopen("testProcessSaveNewPiece.txt", "w");

		$context = Context::getContext();
		$pieceDetails = Tools::jsonDecode(Tools::getValue('details'), true);
		fwrite($file, print_r($pieceDetails, true));
		$result = false;
		$newPiece = new SupplierPieces();

		foreach ($_POST as $key => $value) {

			if (property_exists($newPiece, $key) && $key != 'id_supplier_piece') {

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
			fwrite($file, "Error : " . $e->getMessage() . PHP_EOL);

		}

		if ($result) {

			foreach ($pieceDetails as $details) {
				$object = new SupplierPieceDetail();
				$object->id_supplier_piece = $newPiece->id;
				$object->id_warehouse = 0;

				foreach ($details as $key => $value) {

					if (property_exists($object, $key) && $key != 'id_supplier_piece') {
						fwrite($file, $key . ' => ' . $value . PHP_EOL);
						$object->{$key}

						= $value;
					}

				}

				fwrite($file, print_r($object, true));

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

	public function ajaxProcessGetAutoCompleteSupplier() {

		$customers = Db::getInstance()->executeS(
			(new DbQuery())
				->select('c.`id_customer`, c.`customer_code`, c.`lastname`, c.`firstname`')
				->from('customer', 'c')
				->where('c.`active` = 1 AND c.is_agent = 1')
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

	public function ajaxProcessPieceToPdf() {

		$idPiece = Tools::getValue('idPiece');
		$model = Tools::getValue('model');

		if (empty($model)) {
			$model = Configuration::get('PS_INVOICE_MODEL');
		}

		$template = new InvoiceModel($model);
		$context = Context::getContext();
		$customerPiece = new SupplierPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';

		if ($customerPiece->id_student > 0) {
			$customer = new Customer($customerPiece->id_customer);
			$studentEducation = new StudentEducation($customerPiece->id_student_education);
			$customer->customer_code = $customer->customer_code;
			$address = new Address();
			$pieceTemplate = 'studentPiecetemplate.tpl';
		} else {
			$customer = new Customer($customerPiece->id_customer);
			$address = new Address($customerPiece->id_address_invoice);
			$pieceTemplate = 'piecetemplate.tpl';
		}

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

		$payments = Payment::getBySupplierPieceId($customerPiece->id, $context->language->id);
		$data = $this->createTemplate('controllers/supplier_pieces/' . $pieceTemplate);
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
		$customerPiece = new SupplierPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';

		if ($customerPiece->id_student > 0) {
			$customer = new Student($customerPiece->id_student);
			$studentEducation = new StudentEducation($customerPiece->id_student_education);
			$customer->customer_code = $customer->student_code;
			$address = new Address();
			$headerTemplate = 'headerStudentTemplate.tpl';
			$bodyTemplate = 'bodyStudentTemplate.tpl';
		} else {
			$customer = new Customer($customerPiece->id_customer);
			$address = new Address($customerPiece->id_address_invoice);
			$headerTemplate = 'headertemplate.tpl';
			$bodyTemplate = 'bodytemplate.tpl';
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

		$payments = Payment::getBySupplierPieceId($customerPiece->id, $this->context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $headerTemplate);

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

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/footertemplate.tpl');

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

		$data = $this->createTemplate('controllers/supplier_pieces/pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $bodyTemplate);

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
		return $link;

	}

	public function ajaxProcessPrintPdf() {

		$idPiece = Tools::getValue('idPiece');

		$model = Configuration::get('PS_INVOICE_MODEL');
		$template = Tools::jsonDecode(EmployeeConfiguration::get($model), true);
		$context = Context::getContext();
		$customerPiece = new SupplierPieces($idPiece);
		$customerPiece->prefix = $this->getStaticPrefix($customerPiece->piece_type);
		$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
		$productDetails = $customerPiece->getProductsDetail();
		$studentEducation = '';

		if ($customerPiece->id_student > 0) {
			$customer = new Student($customerPiece->id_student);
			$studentEducation = new StudentEducation($customerPiece->id_student_education);
			$customer->customer_code = $customer->student_code;
			$address = new Address();
			$headerTemplate = 'headerStudentTemplate.tpl';
			$bodyTemplate = 'bodyStudentTemplate.tpl';
		} else {
			$customer = new Customer($customerPiece->id_customer);
			$address = new Address($customerPiece->id_address_invoice);
			$headerTemplate = 'headertemplate.tpl';
			$bodyTemplate = 'bodytemplate.tpl';
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

		$payments = Payment::getBySupplierPieceId($customerPiece->id, $context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $headerTemplate);

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

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/footertemplate.tpl');

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

		$data = $this->createTemplate('controllers/supplier_pieces/pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $bodyTemplate);

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

		$mpdf->Output($filePath . $fileName, F);

		$response = [
			'fileExport' => 'invoices' . DIRECTORY_SEPARATOR . $fileName,
		];
		die(Tools::jsonEncode($response));

	}

	public function ajaxProcessgenerateBulkprint() {

		$model = Configuration::get('PS_INVOICE_MODEL');
		$template = Tools::jsonDecode(EmployeeConfiguration::get($model), true);
		$idPieces = Tools::getValue('pieces');
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

		$filePath = 'invoices' . DIRECTORY_SEPARATOR;

		$fileExport = [];

		foreach ($idPieces as $idPiece) {

			$customerPiece = new SupplierPieces($idPiece);
			$prefix = $this->getStaticPrefix($customerPiece->piece_type);
			$customerPiece->nameType = $this->getStaticPieceName($customerPiece->piece_type);
			$productDetails = $customerPiece->getProductsDetail();
			$studentEducation = '';

			if ($customerPiece->id_student > 0) {
				$customer = new Student($customerPiece->id_student);
				$studentEducation = new StudentEducation($customerPiece->id_student_education);
				$customer->customer_code = $customer->student_code;
				$address = new Address();
				$headerTemplate = 'headerStudentTemplate.tpl';
				$bodyTemplate = 'bodyStudentTemplate.tpl';
			} else {
				$customer = new Customer($customerPiece->id_customer);
				$address = new Address($customerPiece->id_address_invoice);
				$headerTemplate = 'headertemplate.tpl';
				$bodyTemplate = 'bodytemplate.tpl';
			}

			$fileName = "Facture_" . $prefix . $customerPiecee->piece_number . '_' . $customer->lastname . '_' . $customer->firstname . '.pdf';

			if (file_exists('invoices' . DIRECTORY_SEPARATOR . $fileName)) {
				$fileExport[] = 'invoices' . DIRECTORY_SEPARATOR . $fileName;
				continue;
			}

			$payments = Payment::getBySupplierPieceId($customerPiece->id, $context->language->id);
			$mpdf = new \Mpdf\Mpdf([
				'margin_left'   => 10,
				'margin_right'  => 10,
				'margin_top'    => 120,
				'margin_bottom' => 75,
				'margin_header' => 10,
				'margin_footer' => 10,
			]);

			$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $headerTemplate);

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

			$data = $this->createTemplate('controllers/supplier_pieces/pdf/footertemplate.tpl');

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

			$data = $this->createTemplate('controllers/supplier_pieces/pdf.css.tpl');
			$data->assign(
				[
					'color' => $template->color,
				]
			);
			$stylesheet = $data->fetch();

			$data = $this->createTemplate('controllers/supplier_pieces/pdf/' . $bodyTemplate);

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

		if ($zip->open(_PS_EXPORT_DIR_ . 'export_facture.zip', ZipArchive::CREATE) === TRUE) {

			foreach ($fileExport as $invoice) {
				$zip->addFile($invoice, basename($invoice));
			}

			$zip->close();

			$response = [
				'fileExport' => 'fileExport' . DIRECTORY_SEPARATOR . 'export_facture.zip',
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

	public function ajaxProcessMergeOrderTable() {

		$idOrder = Tools::getValue('idOrder');
		$nbOrder = Tools::getValue('numberOrder');

		if (SupplierPieces::mergeOrderTable($idOrder)) {
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
		$supplierPiece = new SupplierPieces($idPiece);
		$paymentMode = new PaymentMode($supplierPiece->id_payment_mode);
		$bank = new BankAccount($paymentMode->id_bank_account);
		$bankAccount = new StdAccount($bank->id_stdaccount);

		if ($supplierPiece->id_sale_agent > 0) {
			$student = new SaleAgent($supplierPiece->id_sale_agent);
			$name = $student->lastname . ' ' . $student->firstname;

		} else {
			$student = new Supplier($piece->id_supplier);
			$name = $student->name;
		}

		$account = new StdAccount($student->id_stdaccount);
		$error = false;

		$piecePayment = new Payment();
		$piecePayment->id_currency = $supplierPiece->id_currency;
		$piecePayment->amount = $supplierPiece->total_tax_incl;
		$piecePayment->id_payment_mode = $supplierPiece->id_payment_mode;
		$piecePayment->booked = 0;
		$piecePayment->payment_date = $supplierPiece->date_add;
		$piecePayment->date_add = $supplierPiece->date_add;

		if ($piecePayment->add()) {
			$paymentDetail = new PaymentDetails();
			$paymentDetail->id_payment = $piecePayment->id;
			$paymentDetail->id_supplier_piece = $supplierPiece->id;
			$paymentDetail->amount = $supplierPiece->total_tax_incl;
			$paymentDetail->date_add = $piecePayment->date_add;
			$paymentDetail->add();
			$supplierPiece->total_paid = $supplierPiece->total_tax_incl;
			$supplierPiece->update();
			$record = new BookRecords();
			$record->id_book_diary = 2;
			$record->name = 'Réglement de la Factur N° FA' . $supplierPiece->piece_number;
			$record->piece_type = 'Réglement CEF';
			$record->date_add = $supplierPiece->date_add;
			$success = $record->add();

			if ($success) {
				$piecePayment->id_book_record = $record->id;
				$piecePayment->booked = 1;
				$piecePayment->update();
				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $bankAccount->id;
				$detail->libelle = "Facture " . $supplierPiece->piece_number . ' ' . $name;
				$detail->piece_number = $supplierPiece->id;
				$detail->credit = $supplierPiece->total_tax_incl;
				$detail->date_add = $record->date_add;
				$detail->add();
				$bankAccount->pointed_solde = $bankAccount->pointed_solde - $supplierPiece->total_tax_incl;
				$bankAccount->update();

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $account->id;
				$detail->libelle = "Facture " . $supplierPiece->piece_number . ' ' . $name;
				$detail->piece_number = $supplierPiece->id;
				$detail->debit = $supplierPiece->total_tax_incl;
				$detail->date_add = $record->date_add;
				$detail->add();

				$account->pointed_solde = $account->pointed_solde - $supplierPiece->total_tax_incl;
				$account->update();

			}

		}

		if ($supplierPiece->id_sale_agent > 0) {
			$details = $supplierPiece->getPieceDetail();

			foreach ($details as $detail) {

				$commission = new SaleAgentCommission($detail['id_sale_agent_commission']);
				$commission->paid = 1;
				$commission->payment_date = $piecePayment->date_add;
				$commission->update();
			}

		}

		if (!$error) {

			$return = [
				'success' => true,
				'message' => 'Le payement a été correctement enregistré',
			];
		} else {
			$return = [
				'success' => false,
				'message' => 'Jeff a merdé',
			];
		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessGenerateBulkReglement() {

		$pieces = Tools::getValue('pieces');

		foreach ($pieces as $key => $idPiece) {

			$supplierPiece = new SupplierPieces($idPiece);
			$paymentMode = new PaymentMode($supplierPiece->id_payment_mode);
			$bank = new BankAccount($paymentMode->id_bank_account);
			$bankAccount = new StdAccount($bank->id_stdaccount);

			if ($supplierPiece->id_sale_agent > 0) {
				$student = new SaleAgent($supplierPiece->id_sale_agent);
				$name = $student->lastname . ' ' . $student->firstname;

			} else {
				$student = new Supplier($piece->id_supplier);
				$name = $student->name;
			}

			$account = new StdAccount($student->id_stdaccount);
			$error = false;

			$piecePayment = new Payment();
			$piecePayment->id_currency = $supplierPiece->id_currency;
			$piecePayment->amount = $supplierPiece->total_tax_incl;
			$piecePayment->id_payment_mode = $supplierPiece->id_payment_mode;
			$piecePayment->booked = 0;
			$piecePayment->payment_date = $supplierPiece->date_add;
			$piecePayment->date_add = $supplierPiece->date_add;

			if ($piecePayment->add()) {
				$paymentDetail = new PaymentDetails();
				$paymentDetail->id_payment = $piecePayment->id;
				$paymentDetail->id_supplier_piece = $supplierPiece->id;
				$paymentDetail->amount = $supplierPiece->total_tax_incl;
				$paymentDetail->date_add = $piecePayment->date_add;
				$paymentDetail->add();
				$supplierPiece->total_paid = $supplierPiece->total_tax_incl;
				$supplierPiece->update();
				$record = new BookRecords();
				$record->id_book_diary = 2;
				$record->name = 'Réglement de la Factur N° FA' . $supplierPiece->piece_number;
				$record->piece_type = 'Réglement CEF';
				$record->date_add = $supplierPiece->date_add;
				$success = $record->add();

				if ($success) {
					$piecePayment->id_book_record = $record->id;
					$piecePayment->booked = 1;
					$piecePayment->update();
					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $bankAccount->id;
					$detail->libelle = "Facture " . $supplierPiece->piece_number . ' ' . $name;
					$detail->piece_number = $supplierPiece->id;
					$detail->credit = $supplierPiece->total_tax_incl;
					$detail->date_add = $record->date_add;
					$detail->add();
					$bankAccount->pointed_solde = $bankAccount->pointed_solde - $supplierPiece->total_tax_incl;
					$bankAccount->update();

					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $account->id;
					$detail->libelle = "Facture " . $supplierPiece->piece_number . ' ' . $name;
					$detail->piece_number = $supplierPiece->id;
					$detail->debit = $supplierPiece->total_tax_incl;
					$detail->date_add = $record->date_add;
					$detail->add();

					$account->pointed_solde = $account->pointed_solde - $supplierPiece->total_tax_incl;
					$account->update();

				}

			}

			if ($supplierPiece->id_sale_agent > 0) {
				$details = $supplierPiece->getPieceDetail();

				foreach ($details as $detail) {

					$commission = new SaleAgentCommission($detail['id_sale_agent_commission']);
					$commission->paid = 1;
					$commission->payment_date = $piecePayment->date_add;
					$commission->update();
				}

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

	/**
	 * Ajax process search products
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
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

	/**
	 * Ajax process add product on order
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessAddProductOnSupplierPieces() {

		// Load object
		$order = new SupplierPieces((int) Tools::getValue('id_order'));

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
			$orderInvoice = new SupplierPiecesInvoice($productInformations['invoice']);
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
					$orderInvoice->number = SupplierPieces::getLastInvoiceNumber() + 1;
				}

				$invoiceAddress = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});
				$carrier = new Carrier((int) $order->id_carrier);
				$taxCalculator = $carrier->getTaxCalculator($invoiceAddress);

				$orderInvoice->total_paid_tax_excl = Tools::ps_round((float) $cart->getSupplierPiecesTotal(false, $totalMethod), 2);
				$orderInvoice->total_paid_tax_incl = Tools::ps_round((float) $cart->getSupplierPiecesTotal($useTaxes, $totalMethod), 2);
				$orderInvoice->total_products = (float) $cart->getSupplierPiecesTotal(false, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_products_wt = (float) $cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_shipping_tax_excl = (float) $cart->getTotalShippingCost(null, false);
				$orderInvoice->total_shipping_tax_incl = (float) $cart->getTotalShippingCost();

				$orderInvoice->total_wrapping_tax_excl = abs($cart->getSupplierPiecesTotal(false, Cart::ONLY_WRAPPING));
				$orderInvoice->total_wrapping_tax_incl = abs($cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$orderInvoice->shipping_tax_computation_method = (int) $taxCalculator->computation_method;

				// Update current order field, only shipping because other field is updated later
				$order->total_shipping += $orderInvoice->total_shipping_tax_incl;
				$order->total_shipping_tax_excl += $orderInvoice->total_shipping_tax_excl;
				$order->total_shipping_tax_incl += ($useTaxes) ? $orderInvoice->total_shipping_tax_incl : $orderInvoice->total_shipping_tax_excl;

				$order->total_wrapping += abs($cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$order->total_wrapping_tax_excl += abs($cart->getSupplierPiecesTotal(false, Cart::ONLY_WRAPPING));
				$order->total_wrapping_tax_incl += abs($cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_WRAPPING));
				$orderInvoice->add();

				$orderInvoice->saveCarrierTaxCalculator($taxCalculator->getTaxesAmount($orderInvoice->total_shipping_tax_excl));

				$orderCarrier = new SupplierPiecesCarrier();
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
				$orderInvoice->total_paid_tax_excl += Tools::ps_round((float) ($cart->getSupplierPiecesTotal(false, $totalMethod)), 2);
				$orderInvoice->total_paid_tax_incl += Tools::ps_round((float) ($cart->getSupplierPiecesTotal($useTaxes, $totalMethod)), 2);
				$orderInvoice->total_products += (float) $cart->getSupplierPiecesTotal(false, Cart::ONLY_PRODUCTS);
				$orderInvoice->total_products_wt += (float) $cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);
				$orderInvoice->update();
			}

		}

		// Create SupplierPieces detail information
		$orderDetail = new SupplierPiecesDetail();
		$orderDetail->createList($order, $cart, $order->getCurrentSupplierPiecesState(), $cart->getProducts(), (isset($orderInvoice) ? $orderInvoice->id : 0), $useTaxes, (int) Tools::getValue('add_product_warehouse'));

		// update totals amount of order
		$order->total_products += (float) $cart->getSupplierPiecesTotal(false, Cart::ONLY_PRODUCTS);
		$order->total_products_wt += (float) $cart->getSupplierPiecesTotal($useTaxes, Cart::ONLY_PRODUCTS);

		$order->total_paid += Tools::ps_round((float) ($cart->getSupplierPiecesTotal(true, $totalMethod)), 2);
		$order->total_paid_tax_excl += Tools::ps_round((float) ($cart->getSupplierPiecesTotal(false, $totalMethod)), 2);
		$order->total_paid_tax_incl += Tools::ps_round((float) ($cart->getSupplierPiecesTotal($useTaxes, $totalMethod)), 2);

		if (isset($orderInvoice) && Validate::isLoadedObject($orderInvoice)) {
			$order->total_shipping = $orderInvoice->total_shipping_tax_incl;
			$order->total_shipping_tax_incl = $orderInvoice->total_shipping_tax_incl;
			$order->total_shipping_tax_excl = $orderInvoice->total_shipping_tax_excl;
		}

		StockAvailable::updateQuantity($orderDetail->product_id, $orderDetail->product_attribute_id, ($orderDetail->product_quantity * -1), $order->id_shop);

		// discount
		$order->total_discounts += (float) abs($cart->getSupplierPiecesTotal(true, Cart::ONLY_DISCOUNTS));
		$order->total_discounts_tax_excl += (float) abs($cart->getSupplierPiecesTotal(false, Cart::ONLY_DISCOUNTS));
		$order->total_discounts_tax_incl += (float) abs($cart->getSupplierPiecesTotal(true, Cart::ONLY_DISCOUNTS));

		// Save changes of order
		$order->update();

		// Update weight SUM
		$orderCarrier = new SupplierPiecesCarrier((int) $order->getIdSupplierPiecesCarrier());

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
		$resume = SupplierPiecesSlip::getProductSlipResume((int) $product['id_order_detail']);
		$product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
		$product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
		$product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
		$product['return_history'] = SupplierPiecesReturn::getProductReturnDetail((int) $product['id_order_detail']);
		$product['refund_history'] = SupplierPiecesSlip::getProductSlipDetail((int) $product['id_order_detail']);

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
			/** @var SupplierPiecesInvoice $invoice */
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
			// Create SupplierPiecesCartRule
			$rule = new CartRule($cartRule['id_cart_rule']);
			$values = [
				'tax_incl' => $rule->getContextualValue(true),
				'tax_excl' => $rule->getContextualValue(false),
			];
			$orderCartRule = new SupplierPiecesCartRule();
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

		// Update SupplierPieces
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

	/**
	 * Send changed notification
	 *
	 * @param SupplierPieces|null $order
	 *
	 * @since 1.8.1.0
	 */
	public function sendChangedNotification(SupplierPieces $order = null) {

		if (is_null($order)) {
			$order = new SupplierPieces(Tools::getValue('id_order'));
		}

		Hook::exec('actionSupplierPiecesEdited', ['order' => $order]);
	}

	/**
	 * Ajax proces load product information
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessLoadProductInformation() {

		$orderDetail = new SupplierPiecesDetail(Tools::getValue('id_order_detail'));

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(
				json_encode(
					[
						'result' => false,
						'error'  => Tools::displayError('The SupplierPiecesDetail object cannot be loaded.'),
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

	/**
	 * Ajax process edit product on order
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessEditProductOnSupplierPieces() {

		// Return value
		$res = true;

		$order = new SupplierPieces((int) Tools::getValue('id_order'));
		$orderDetail = new SupplierPiecesDetail((int) Tools::getValue('product_id_order_detail'));

		if (Tools::isSubmit('product_invoice')) {
			$orderInvoice = new SupplierPiecesInvoice((int) Tools::getValue('product_invoice'));
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

		// Apply change on SupplierPiecesInvoice

		if (isset($orderInvoice)) {
			// If SupplierPiecesInvoice to use is different, we update the old invoice and new invoice

			if ($orderDetail->id_order_invoice != $orderInvoice->id) {
				$oldSupplierPiecesInvoice = new SupplierPiecesInvoice($orderDetail->id_order_invoice);
				// We remove cost of products
				$oldSupplierPiecesInvoice->total_products -= $orderDetail->total_price_tax_excl;
				$oldSupplierPiecesInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;

				$oldSupplierPiecesInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
				$oldSupplierPiecesInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;

				$res &= $oldSupplierPiecesInvoice->update();

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
				// Apply changes on SupplierPiecesInvoice
				$orderInvoice->total_products += $diffPriceTaxExcl;
				$orderInvoice->total_products_wt += $diffPriceTaxIncl;

				$orderInvoice->total_paid_tax_excl += $diffPriceTaxExcl;
				$orderInvoice->total_paid_tax_incl += $diffPriceTaxIncl;
			}

			// Apply changes on SupplierPieces
			$order = new SupplierPieces($orderDetail->id_order);
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
		$orderCarrier = new SupplierPiecesCarrier((int) $order->getIdSupplierPiecesCarrier());

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
		$resume = SupplierPiecesSlip::getProductSlipResume($orderDetail->id);
		$product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
		$product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
		$product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
		$product['refund_history'] = SupplierPiecesSlip::getProductSlipDetail($orderDetail->id);

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
			/** @var SupplierPiecesInvoice $invoice */
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

	/**
	 * @param SupplierPiecesDetail $orderDetail
	 * @param int         $addQuantity
	 */
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

	/**
	 * Ajax proces delete product line
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessDeleteProductLine() {

		$res = true;

		$orderDetail = new SupplierPiecesDetail((int) Tools::getValue('id_order_detail'));
		$order = new SupplierPieces((int) Tools::getValue('id_order'));

		$this->doDeleteProductLineValidation($orderDetail, $order);

		// Update SupplierPiecesInvoice of this SupplierPiecesDetail

		if ($orderDetail->id_order_invoice != 0) {
			$orderInvoice = new SupplierPiecesInvoice($orderDetail->id_order_invoice);
			$orderInvoice->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
			$orderInvoice->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
			$orderInvoice->total_products -= $orderDetail->total_price_tax_excl;
			$orderInvoice->total_products_wt -= $orderDetail->total_price_tax_incl;
			$res &= $orderInvoice->update();
		}

		// Update SupplierPieces
		$order->total_paid -= $orderDetail->total_price_tax_incl;
		$order->total_paid_tax_incl -= $orderDetail->total_price_tax_incl;
		$order->total_paid_tax_excl -= $orderDetail->total_price_tax_excl;
		$order->total_products -= $orderDetail->total_price_tax_excl;
		$order->total_products_wt -= $orderDetail->total_price_tax_incl;

		$res &= $order->update();

		// Reinject quantity in stock
		$this->reinjectQuantity($orderDetail, $orderDetail->product_quantity, true);

		// Update weight SUM
		$orderCarrier = new SupplierPiecesCarrier((int) $order->getIdSupplierPiecesCarrier());

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
			/** @var SupplierPiecesInvoice $invoice */
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

	/**
	 * Ajax process change payment method
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
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

	/**
	 * Apply discount on invoice
	 *
	 * @param SupplierPiecesInvoice $orderInvoice
	 * @param float        $valueTaxIncl
	 * @param float        $valueTaxExcl
	 *
	 * @return bool Indicates whether the invoice was successfully updated
	 *
	 * @since 1.8.1.0
	 * @since 1.0.1 Return update status bool
	 */
	protected function applyDiscountOnInvoice($orderInvoice, $valueTaxIncl, $valueTaxExcl) {

		// Update SupplierPiecesInvoice
		$orderInvoice->total_discount_tax_incl += $valueTaxIncl;
		$orderInvoice->total_discount_tax_excl += $valueTaxExcl;
		$orderInvoice->total_paid_tax_incl -= $valueTaxIncl;
		$orderInvoice->total_paid_tax_excl -= $valueTaxExcl;
		$orderInvoice->update();
	}

	/**
	 * Edit production validation
	 *
	 * @param SupplierPiecesDetail       $orderDetail
	 * @param SupplierPieces             $order
	 * @param SupplierPiecesInvoice|null $orderInvoice
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	protected function doEditProductValidation(SupplierPiecesDetail $orderDetail, SupplierPieces $order, SupplierPiecesInvoice $orderInvoice = null) {

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The SupplierPieces Detail object could not be loaded.'),
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

	/**
	 * Delete product line validation
	 *
	 * @param SupplierPiecesDetail $orderDetail
	 * @param SupplierPieces       $order
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	protected function doDeleteProductLineValidation(SupplierPiecesDetail $orderDetail, SupplierPieces $order) {

		if (!Validate::isLoadedObject($orderDetail)) {
			$this->ajaxDie(json_encode([
				'result' => false,
				'error'  => Tools::displayError('The SupplierPieces Detail object could not be loaded.'),
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

	/**
	 * @param SupplierPieces $order
	 *
	 * @return array
	 *
	 * @since 1.8.1.0
	 */
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

	public function ajaxProcessGetDetailSaleAgentFields() {

		$fields = [
			[
				'title'      => $this->l('ID'),
				'dataIndx'   => 'id_sale_agent_commission',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'dataIndx'   => 'id_student_education',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'    => $this->l('Etudiant'),
				'width'    => 180,
				'align'    => 'left',
				'dataIndx' => 'student',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Session'),
				'width'    => 180,
				'align'    => 'left',
				'dataIndx' => 'sessionName',
				'dataType' => 'string',
			],

			[
				'title'    => $this->l('Formation'),
				'width'    => 180,
				'align'    => 'left',
				'dataIndx' => 'educationName',
				'dataType' => 'string',
			],

			[
				'title'    => $this->l('Prix base HT'),
				'width'    => 120,
				'dataIndx' => 'amount',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('HT.'),

			],

			[
				'title'    => $this->l('Prix TTC.'),
				'width'    => 120,
				'dataIndx' => 'amount_tax_incl',
				'align'    => 'right',
				'valign'   => 'center',
				'dataType' => 'float',
				'format'   => "#.###,00 € " . $this->l('TTC.'),
			],
			[
				'title'    => $this->l('Supprimer'),
				'align'    => 'center',
				'dataIndx' => 'deleteAction',
				'dataType' => 'html',
			],
			[

				'dataIndx' => 'total_line',
				'dataType' => 'float',
				'format'   => "#.###,00 ",
				'hidden'   => true,
				'cls'      => 'total_line',
			],

		];

		die(Tools::jsonEncode($fields));

	}

	public function ajaxProcessGetDetailSaleAgentRequest() {

		$idAgent = Tools::getValue('idAgent');
		$saleAgent = new SaleAgent($idAgent);
		$rate = 1;
		$commissions = $saleAgent->getCommissionDue();

		if ($saleAgent->is_tax) {
			$rate = 1.2;
		}

		$total = 0;

		foreach ($commissions as &$commission) {

			$studentEducation = new StudentEducation($commission['id_student_education']);
			$commission['id_sale_agent_commission'] = $commission['id_sale_agent_commission'];
			$commission['educationName'] = $studentEducation->name;
			$commission['amount_tax_incl'] = $commission['amount'] * $rate;
			$commission['total_line'] = $commission['amount'] * $rate;
			$commission['sessionName'] = $studentEducation->sessionName;
			$commission['deleteAction'] = '<button classe="ui-button ui-widget ui-corner-all" onClick="deleteCommission(' . $commission['id_sale_agent_commission'] . ', ' . $idAgent . ');">Supprimer</button>';

		}

		die(Tools::jsonEncode($commissions));

	}

	public function ajaxProcessGenerateCefFacture() {

		$idAgent = Tools::getValue('idAgent');
		$invoiceNumber = Tools::getValue('invoiceNumber');
		$rate = 1;
		$tax_rate = 0;
		$saleAgent = new SaleAgent($idAgent);
		$id_address = Address::getFirstCustomerAddressId($saleAgent->id_customer);
		$address = new Address((int) $id_address);

		if ($saleAgent->is_tax) {
			$rate = 1.2;
			$tax_rate = 20;
		}

		$total = 0;
		$file = fopen("testGenerateCefFacture.txt", "w");
		$pieces = Tools::getValue('pieces');

		foreach ($pieces as $piece) {
			$total = $total + $piece['total_line'];
		}

		fwrite($file, print_r($pieces, true));
		$result = false;
		$error = false;
		$piece = new SupplierPieces();
		$piece->piece_type = 'INVOICE';
		$piece->id_shop_group = 1;
		$piece->id_shop = 1;
		$piece->id_lang = 1;
		$piece->id_currency = 1;
		$piece->id_sale_agent = $saleAgent->id;
		$piece->id_student_education = 0;
		$piece->id_education_session = 0;
		$piece->base_tax_excl = $total;
		$piece->total_products_tax_excl = $total;
		$piece->total_products_tax_incl = $total * $rate;
		$piece->total_shipping_tax_excl = 0;
		$piece->total_shipping_tax_incl = 0;
		$piece->total_with_freight_tax_excl = $total;
		$piece->total_tax_excl = $total;
		$piece->total_tax = $total * $rate - $total;
		$piece->total_tax_incl = $total * $rate;
		$piece->balance_due = $total * $rate;
		$piece->id_address_delivery = $address->id;
		$piece->id_address_invoice = $address->id;
		$piece->id_payment_mode = 2;
		$piece->conversion_rate = 1;
		$piece->piece_number = $invoiceNumber;
		$piece->observation = $saleAgent->firstname . ' ' . $saleAgent->lastname;
		$piece->date_add = date("Y-m-d H:i:s");

		try {
			$result = $piece->add();
		} catch (Exception $ex) {

			$error = true;
		}

		if ($result) {

			foreach ($pieces as $invoice) {

				fwrite($file, print_r($invoice, true));
				fwrite($file, $invoice['id_sale_agent_commission'] . PHP_EOL);
				$studentEducation = new StudentEducation($invoice['id_student_education']);
				$detail = new SupplierPieceDetail();
				$detail->id_supplier_piece = $piece->id;
				$detail->id_sale_agent_commission = $invoice['id_sale_agent_commission'];
				$detail->id_education = $studentEducation->id_education;
				$detail->id_education_attribute = $studentEducation->id_education_attribute;
				$detail->product_name = $studentEducation->name;
				$detail->product_quantity = 1;
				$detail->product_reference = $studentEducation->reference;
				$detail->tax_rate = $tax_rate;
				$detail->id_tax_rules_group = 1;
				$detail->original_price_tax_excl = $invoice['amount'];
				$detail->original_price_tax_incl = $invoice['amount'] * $rate;
				$detail->unit_tax_excl = $invoice['amount'];
				$detail->unit_tax_incl = $invoice['amount'] * $rate;
				$detail->total_tax_excl = $invoice['amount'];
				$detail->total_tax = $invoice['amount'] * $rate - $invoice['amount'];
				$detail->total_tax_incl = $invoice['amount'] * $rate;

				try {
					$result = $detail->add();
				} catch (Exception $ex) {

					$error = true;
				}

			}

		}

		if ($error) {
			$return = [
				'success' => false,
				'message' => 'Le webMaster à hentiement merdé',
			];
		} else {

			foreach ($pieces as $invoice) {
				$commission = new SaleAgentCommission($invoice['id_sale_agent_commission']);
				$commission->invoice_number = $invoiceNumber;
				$commission->update();
			}

			$return = [
				'success' => true,
				'idPiece' => $piece->id,
			];
		}

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessBookSupplierPiece() {

		$id_supplier_piece = Tools::getValue('id_supplier_piece');
		$piece = new SupplierPieces($id_supplier_piece);

		if ($piece->id_sale_agent > 0) {
			$student = new SaleAgent($piece->id_sale_agent);
			$counterpart = new StdAccount($student->id_stdaccount);
			$account = new StdAccount($counterpart->counterpart);
			$name = $student->lastname . ' ' . $student->firstname;

			if ($counterpart->default_vat > 0) {
				$vatAccount = new StdAccount($counterpart->default_vat);
			} else {
				$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));
			}

		} else {
			$account = new StdAccount(627);
			$student = new Supplier($piece->id_supplier);
			$name = $student->name;
			$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));
		}

		$record = new BookRecords();
		$record->id_book_diary = 4;

		$record->name = "Facture " . $piece->piece_number . ' de' . $name;
		$record->piece_type = 'Règlement Fournisseur';

		$record->date_add = $piece->date_add;
		$success = $record->add();

		if ($success) {

			$detail = new BookRecordDetails();
			$detail->id_book_record = $record->id;
			$detail->id_stdaccount = $counterpart->id;
			$detail->libelle = "Facture " . $piece->piece_number . ' ' . $name;
			$detail->piece_number = $piece->id;
			$detail->credit = $piece->total_tax_incl;
			$detail->date_add = $record->date_add;
			$detail->add();
			$counterpart->pointed_solde = $counterpart->pointed_solde + $piece->total_tax_incl;
			$counterpart->update();

			$detail = new BookRecordDetails();
			$detail->id_book_record = $record->id;
			$detail->id_stdaccount = $account->id;
			$detail->libelle = "Facture " . $piece->piece_number . ' ' . $name;
			$detail->piece_number = $piece->id;
			$detail->debit = $piece->total_with_freight_tax_excl;
			$detail->date_add = $record->date_add;
			$detail->add();
			$account->pointed_solde = $account->pointed_solde + $piece->total_tax_incl;
			$account->update();

			if ($piece->total_tax_incl > $piece->total_with_freight_tax_excl) {
				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $vatAccount->id;
				$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $name;
				$detail->piece_number = $piece->id;
				$detail->debit = $piece->total_tax;
				$detail->date_add = $record->date_add;
				$detail->add();
				$vatAccount->pointed_solde = $vatAccount->pointed_solde - $piece->total_tax;
				$vatAccount->update();
			}

		}

		$piece->id_book_record = $record->id;
		$piece->is_book = 1;
		$piece->update();
		$return = [
			'success' => true,
			'message' => 'Le Facture ' . $piece->prefix . $piece->piece_number . ' a été comptabilisée avec succès',
		];
		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessBulkBook() {

		$file = fopen("testProcessBulkBook.txt", "w");
		$idPieces = Tools::getValue('idPieces');

		foreach ($idPieces as $id) {
			$piece = new SupplierPieces($id);

			if ($piece->id_sale_agent > 0) {
				$student = new SaleAgent($piece->id_sale_agent);
				fwrite($file, $student->id_stdaccount . PHP_EOL);
				fwrite($file, $student->id_stdaccount . PHP_EOL);
				$counterpart = new StdAccount($student->id_stdaccount);
				fwrite($file, $counterpart->counterpart . PHP_EOL);
				$account = new StdAccount($counterpart->counterpart);
				fwrite($file, $account->id . PHP_EOL);
				$name = $student->lastname . ' ' . $student->firstname;

				if ($counterpart->default_vat > 0) {
					$vatAccount = new StdAccount($counterpart->default_vat);
				} else {
					$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));
				}

			} else {
				$account = new StdAccount(627);
				$student = new Supplier($piece->id_supplier);
				$name = $student->name;
				$vatAccount = new StdAccount(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT'));
			}

			$record = new BookRecords();
			$record->id_book_diary = 4;

			$record->name = "Facture " . $piece->piece_number . ' de' . $name;
			$record->piece_type = 'Règlement Fournisseur';

			$record->date_add = $piece->date_add;
			$success = $record->add();

			if ($success) {

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $counterpart->id;
				$detail->libelle = "Facture " . $piece->piece_number . ' ' . $name;
				$detail->piece_number = $piece->id;
				$detail->credit = $piece->total_tax_incl;
				$detail->date_add = $record->date_add;
				$detail->add();
				$counterpart->pointed_solde = $counterpart->pointed_solde + $piece->total_tax_incl;
				$counterpart->update();

				$detail = new BookRecordDetails();
				$detail->id_book_record = $record->id;
				$detail->id_stdaccount = $account->id;
				$detail->libelle = "Facture " . $piece->piece_number . ' ' . $name;
				$detail->piece_number = $piece->id;
				$detail->debit = $piece->total_with_freight_tax_excl;
				$detail->date_add = $record->date_add;
				$detail->add();
				$account->pointed_solde = $account->pointed_solde + $piece->total_tax_incl;
				$account->update();

				if ($piece->total_tax_incl > $piece->total_with_freight_tax_excl) {
					$detail = new BookRecordDetails();
					$detail->id_book_record = $record->id;
					$detail->id_stdaccount = $vatAccount->id;
					$detail->libelle = "Facture " . $piece->prefix . $piece->piece_number . ' ' . $name;
					$detail->piece_number = $piece->id;
					$detail->debit = $piece->total_tax;
					$detail->date_add = $record->date_add;
					$detail->add();
					$vatAccount->pointed_solde = $vatAccount->pointed_solde - $piece->total_tax;
					$vatAccount->update();
				}

			}

			$piece->id_book_record = $record->id;
			$piece->is_book = 1;
			$piece->update();

		}

		$result = [
			'success' => true,
			'message' => $this->l('Les pièces ont été comptabilisées avec succès'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessDeleteCommission() {

		$idCommisssion = Tools::getValue('idCommisssion');
		$commission = new SaleAgentCommission($idCommisssion);
		$commission->delete();

		$return = [
			'success' => true,
		];

		die(Tools::jsonEncode($return));
	}

}
