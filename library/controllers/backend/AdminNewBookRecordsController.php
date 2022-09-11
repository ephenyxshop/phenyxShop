<?php

/**
 * @property BookRecords $object
 */
class AdminNewBookRecordsControllerCore extends AdminController {

	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'saisiek';
		$this->className = 'NewBookRecords';
		$this->publicName = $this->l('Enregistrer une écriture');
		$this->lang = true;

		$this->context = Context::getContext();

		parent::__construct();

		EmployeeConfiguration::updateValue('EXPERT_NEW_BOOK_RECORDS_SCRIPT', $this->generateParaGridScript());
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_NEW_BOOK_RECORDS_SCRIPT', $this->generateParaGridScript());
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_NEW_BOOK_RECORDS_FIELDS', Tools::jsonEncode($this->getNewBookRecordsFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_NEW_BOOK_RECORDS_FIELDS', Tools::jsonEncode($this->getNewBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_FIELDS'), true);
		}

		$this->extra_vars = [
			'diaries' => BookDiary::getBookDiary(),
			'today'   => date("Y-m-d"),
		];

	}

	public function generateParaGridScript() {

		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

		$paragrid->selectionModelType = 'row';
		$paragrid->height = '350';
		$paragrid->requestModel = '{
			location: "remote",
            dataType: "json",
            method: "GET",
			recIndx: "id_book_record_details",
			url: AjaxLink' . $this->controller_name . '+"&action=getNewBookRecordsRequest&ajax=1",
            getData: function (dataJSON) {
                return { data: dataJSON };
			}


        }';
		$paragrid->heightModel = 'getHeightModel() {
			var offset = $("#tableNewRecord").height()+$("#headerActionRow").height()+280;
			return screenHeight = $(window).height()-offset;
		};';
		$paragrid->columnBorders = 1;

		$paragrid->showNumberCell = 1;
		$paragrid->change = 'function(evt, ui) {
			//proceedListChange(evt, ui);

        }';

		$paragrid->cellDblClick = 'function(evt, ui) {

			if(ui.column.dataIndx == "date_add" && ui.rowData.date_add == "" && ui.rowData.account == "") {
				rowData =  addEmptyRecordsLine();
				window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: ui.rowIndx});
				jumpGridCell(ui.rowIndx, "account");
			}
		}';
		$paragrid->cellSave = 'function(evt, ui) {
			console.log(ui);
			var indexRow = ui.rowIndx;
			console.log(indexRow);

			var idDiary = $("#DiarySelect" ).val();
			var Recordslibelle = $("#recordLibelle").val();
			var keys = [];
			if(ui.dataIndx == "libelle") {
				if(ui.rowData.id_stdaccount_type == 5 && idDiary == 7) {

					jumpGridCell(ui.rowIndx, "debit");
					window[\'gridNewBookRecords\'].updateRow({
						rowIndx: ui.rowIndx,
    					newRow: { "debit": "" }
					});
				}
				if(ui.rowData.id_stdaccount_type == 4 && idDiary == 4) {

					jumpGridCell(ui.rowIndx, "credit");
					window[\'gridNewBookRecords\'].updateRow({
						rowIndx: ui.rowIndx,
    					newRow: { "credit": "" }
					});
				}
			}

			if(ui.dataIndx == "debit" && ui.rowData.id_stdaccount_type == 4) {
					window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "credit": 0 }
					});
					amount = ui.rowData.debit;
					debit = amount/(12/10);
					solde = amount - debit;
					keys.push(indexRow+1);
					keys.push(indexRow+2);
					var rowIndex = ui.rowIndx+1;
					var resolve = ui.rowData.resolve;
					if(resolve != 0) {
						resolve = resolve.split(",");

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[0],
    						newRow: {
								"credit": solde,
								"debit" :0
							}
						});

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[1],
    						newRow: {
								"credit": debit ,
								"debit" :0
							}
						});
					} else {
						var rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.default_vat, ui.rowData.defaultVatCode, ui.rowData.defaultVatName, debit, credit, ui.rowData.libelle);
						var rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});

						rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.counterpart, ui.rowData.counterPartCode, ui.rowData.counterPartName, solde, credit, ui.rowData.libelle);
						rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "resolve": keys.toString() }
						});

						this.refresh();
						if(ui.rowData.counterpart == 0) {
							jumpGridCell(rowIndx, "account");
						}
                    	window[\'gridNewBookRecords\'].goToPage({rowIndx: rowIndx});
					}

				} else
			if(ui.dataIndx == "credit" && ui.rowData.id_stdaccount_type == 4) {
					window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "debit": 0 }
					});
					amount = ui.rowData.credit;
					debit = amount/(12/10);
					solde = amount - debit;

					keys.push(indexRow+1);
					keys.push(indexRow+2);
					var rowIndex = ui.rowIndx+1;
					var resolve = ui.rowData.resolve;
					if(resolve != 0) {
						resolve = resolve.split(",");

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[0],
    						newRow: {
								"debit": solde,
								"credit" :0
							}
						});

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[1],
    						newRow: {
								"debit": debit ,
								"credit" :0
							}
						});
					} else {

						var rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.default_vat, ui.rowData.defaultVatCode, ui.rowData.defaultVatName, debit, 0, ui.rowData.libelle);
						var rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});

						rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.counterpart, ui.rowData.counterPartCode, ui.rowData.counterPartName, solde, 0, ui.rowData.libelle);
						rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "resolve": keys.toString() }
						});

						this.refresh();
						if(ui.rowData.counterpart == 0) {
							jumpGridCell(rowIndx, "account");
						}
                    	window[\'gridNewBookRecords\'].goToPage({rowIndx: rowIndx});

				}
			} else
				if(ui.dataIndx == "debit" && ui.rowData.id_stdaccount_type == 5) {
					window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "credit": 0 }
					});
					amount = ui.rowData.debit;
					credit = amount/(12/10);
					solde = amount - credit;
					keys.push(indexRow+1);
					keys.push(indexRow+2);
					var rowIndex = ui.rowIndx+1;
					var resolve = ui.rowData.resolve;
					if(resolve != 0) {
						resolve = resolve.split(",");

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[0],
    						newRow: {
								"credit": solde,
								"debit" :0
							}
						});

						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[1],
    						newRow: {
								"credit": credit ,
								"debit" :0
							}
						});
					} else {
						var rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.default_vat, ui.rowData.defaultVatCode, ui.rowData.defaultVatName, 0, credit, ui.rowData.libelle);
						var rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});

						rowData = addNewRecordsLine(ui.rowData.date_add,ui.rowData.counterpart, ui.rowData.counterPartCode, ui.rowData.counterPartName, 0, solde,  ui.rowData.libelle);
						rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "resolve": keys.toString() }
						});

						this.refresh();
						if(ui.rowData.counterpart == 0) {
							jumpGridCell(rowIndx, "account");
						}
                    	window[\'gridNewBookRecords\'].goToPage({rowIndx: rowIndx});
				}
			} else
				if(ui.dataIndx == "credit" && ui.rowData.id_stdaccount_type == 5) {
					window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "debit": 0 }
					});
					amount = ui.rowData.credit;
					debit = amount/(12/10);
					solde = amount - debit;
					var resolve = ui.rowData.resolve;
					if(resolve != 0) {
						resolve = resolve.split(",");
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[0],
    						newRow: {
								"debit": debit,
								"credit" :0
							}
						});
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: resolve[1],
    						newRow: {
								"debit": solde,
								"credit" :0
							}
						});
					} else {


						var rowData = addNewRecordsLine(ui.rowData.date_add, ui.rowData.default_vat, ui.rowData.defaultVatCode, ui.rowData.defaultVatName, debit, 0, ui.rowData.libelle);

						var rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});


						//rowIndex = rowIndex +1;
						rowData = addNewRecordsLine(ui.rowData.date_add, ui.rowData.counterpart, ui.rowData.counterPartCode, ui.rowData.counterPartName,  solde,0, ui.rowData.libelle);
						rowIndx = window[\'gridNewBookRecords\'].addRow({newRow: rowData, rowIndx: rowIndex});

						this.refresh();
						if(ui.rowData.counterpart == 0) {
							jumpGridCell(rowIndx, "account")
						}
                    	window[\'gridNewBookRecords\'].goToPage({rowIndx: rowIndx});
						window[\'gridNewBookRecords\'].updateRow({
							rowIndx: ui.rowIndx,
    						newRow: { "resolve": keys.toString() }
						});
					}
				}

			this.refresh();

        }';
		$paragrid->complete = 'function(){

		//window[\'gridNewBookRecords\'].editCell( { rowIndx: 0, dataIndx: "account" } );

		$("#DiarySelect").focus();


        }';

		$paragrid->groupModel = [
			'on'           => true,
			'grandSummary' => true,
			'header'       => 0,
		];

		$paragrid->summaryTitle = [
			'sum' => '"{0}"',
		];
		$paragrid->showTop = 0;
		$paragrid->title = '""';
		$paragrid->fillHandle = '\'all\'';

		$option = $paragrid->generateParaGridOption();
		$this->paragridScript = $paragrid->generateParagridScript();

		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';

	}

	public function getNewBookRecordsRequest() {

		$details[] = [
			'date_add'    => date("Y-m-d"),
			'account'     => '',
			'libelle'     => '',
			'debit'       => 0,
			'credit'      => 0,
			'accountName' => '',
			'resolve'     => 0,
		];

		for ($i = 1; $i < 28; $i++) {
			$details[] = [
				'date_add'    => '',
				'account'     => '',
				'libelle'     => '',
				'debit'       => 0,
				'credit'      => 0,
				'accountName' => '',
				'resolve'     => 0,
			];
		}

		return $details;

	}

	public function ajaxProcessgetNewBookRecordsRequest() {

		die(Tools::jsonEncode($this->getNewBookRecordsRequest()));

	}

	public function getNewBookRecordsFields() {

		return [

			[
				'title'    => $this->l('Date'),
				'maxWidth' => 150,
				'dataIndx' => 'date_add',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => true,
				'editor'   => [
					'type' => "textbox",
					'init' => 'dateEditor',
				],
			],

			[
				'title'    => $this->l('N° de Compte'),
				'maxWidth' => 150,
				'dataIndx' => 'account',
				'dataType' => 'string',
				'editable' => true,
				'editor'   => [
					'type' => "textbox",
					'init' => 'autoCompleteAccount',
					'cls'  => 'detailAccount',
				],
				'style'    => '{"min-height": "40px"}',
			],
			[
				'title'    => $this->l('Libellé'),
				'minWidth' => 300,
				'dataIndx' => 'libelle',
				'dataType' => 'string',
				'editable' => true,
			],
			[
				'title'    => $this->l('Montant Débit'),
				'maxWidth' => 200,
				'dataIndx' => 'debit',
				'dataType' => 'float',
				'format'   => '# ##0,00',
				'editable' => true,
				'summary'  => [
					'type' => 'sum',
				],
			],
			[
				'title'    => $this->l('Montant Crédit'),
				'maxWidth' => 200,
				'dataIndx' => 'credit',
				'dataType' => 'float',
				'format'   => '# ##0,00',
				'editable' => true,
				'summary'  => [
					'type' => 'sum',
				],
			],
			[
				'title'    => $this->l('Intitulé du compte'),
				'width'    => 150,
				'dataIndx' => 'accountName',
				'dataType' => 'string',
			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'id_stdaccount',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'id_stdaccount_type',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'vat_exonerate',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'default_vat',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'defaultVatName',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'counterpart',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'counterPartName',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'defaultVatCode',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'counterPartCode',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],

			[
				'title'      => ' ',
				'dataIndx'   => 'resolve',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',

			],

		];
	}

	public function ajaxProcessGetNewBookRecordsFields() {

		die(EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_FIELDS'));
	}

}
