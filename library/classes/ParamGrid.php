<?php

/**
 * Class ParamGrid
 *
 * @since 2.1.0.0
 */
class ParamGridCore {

	public $paramClass;

	public $paramTable;

	public $paramController;

	public $paramIdentifier;

	public $paramGridObj = [];

	public $paramGridId;

	public $paramGridVar;

	public $height;

	public $width = '100%';

	public $recIndx;

	public $data;

	public $dataModel = [];

	public $autoFit = true;

	public $resizable = true;

	public $scrollModel = [];

	public $animOn = true;

	public $animDuration = 400;

	public $animModel = [];

	public $complete;

	public $wrap = true;

	public $autofill = true;

	public $colModel;

	public $showNumberCell = 1;

	public $numberCell = [];

	public $pageModel = [];

	public $create;

	public $rowInit;

	public $change;

	public $cellSave;

	public $cellClick;

	public $cellDblClick;

	public $cellKeyDown;

	public $showTitle = 0;

	public $showTop = 1;

	public $showHeader = 1;

	public $title = '\'\'';

	public $collapsible = 0;
	public $freezeCols = 0;
	public $rowBorders = 1;
	public $columnBorders = 0;
	public $stripeRows = 1;
	public $selectionModelType;
	public $selectionModel = [];

	public $paragrid_option = [];

	public $editModel;

	public $controllerName;

	public $paragridScript;

	public $contextMenuoption;

	public $dragOn = 0;

	public $dragdiHelper;

	public $dragclsHandle;

	public $dragModel = [];

	public $dropOn = 0;

	public $dropModel = [];

	public $moveNode;

	public $toolbar;

	public $groupModel;

	public $filterModel;

	public $fillHandle;

	public $beforeRowExpand;

	public $contextMenu;

	public $gridFunction = [];

	public $gridExtraFunction;

	public $gridAfterLoadFunction;

	public $summaryData;

	public $detailModel;

	public $subDetailModel;

	public $detailContextMenu;

	public $treeModel;

	public $otherFunction;

	public $requestModel;
	
	public $requestComplementaryModel;

	public $heightModel;

	public $ajaxUrl;

	public $rowDblClick;

	public $summaryTitle;

	public $autoRowHead = true;
	
	public $refresh;
	
	public $editorBlur;
	
	public $sortModel;
	
	public $beforeSort;
	
	public $beforeFilter;
	
	public $beforeTableView;
	
	public $uppervar;
	
	public $editorBegin;
	
	public $editorEnd;
	
	public $editorFocus;
	
	public $postRenderInterval;
	
	public $needRequestModel = true;
	
	public $check;
	
	public $onlyObject = false;

	
	

	public function __construct($paramClass, $paramController, $paramTable, $paramIdentifier) {

		$this->paramClass = $paramClass;
		$this->paramController = $paramController;
		$this->paramTable = $paramTable;
		$this->paramIdentifier = $paramIdentifier;

	}

	public function generateParaGridOption() {

		$this->paramGridObj = (!empty($this->paramGridObj)) ? $this->paramGridObj : 'obj' . $this->paramClass;
		$this->paramGridVar = (!empty($this->paramGridVar)) ? $this->paramGridVar : 'grid' . $this->paramClass;
		$this->paramGridId = (!empty($this->paramGridId)) ? $this->paramGridId : 'grid_' . $this->paramController;

		if (!empty($this->recIndx)) {
			$this->dataModel['recIndx'] = '\'' . $this->recIndx . '\'';
		}
		if($this->needRequestModel) {
			$this->requestModel = (!empty($this->requestModel)) ? $this->requestModel : '{
            	location: "remote",
				dataType: "json",
            	method: "GET",
				recIndx: "' . $this->paramIdentifier . '",
				url: AjaxLink' . $this->paramController . ',
				postData: function () {
                	return {
                    	action: "get' . $this->paramClass . 'Request",
                    	ajax: 1
					};
            	},
            	getData: function (dataJSON) {
					return { data: dataJSON };
            	}
        	}';
		
		}
		$this->heightModel = (!empty($this->heightModel)) ? $this->heightModel : 'getHeightModel() {
			return screenHeight = $(window).height() -300;
		}';

		$this->dataModel = (!empty($this->dataModel)) ? $this->dataModel : $this->paramController . 'Model';

		$this->colModel = (!empty($this->colModel)) ? $this->colModel : 'get' . $this->paramClass . 'Fields()';

		$this->scrollModel = [
			'autoFit' => $this->autoFit,
		];

		$this->numberCell = [
			'show' => $this->showNumberCell,
		];

		$this->selectionModel = [
			'type' => '\'' . $this->selectionModelType . '\'',
		];

		$this->paragrid_option['paragrids'][] = (!empty($this->paragrid_option)) ? $this->paragrid_option : [
			'paramGridVar'          => $this->paramGridVar,
			'paramGridId'           => $this->paramGridId,
			'paramGridObj'          => $this->paramGridObj,
			'requestModel'          => $this->requestModel,
			'requestComplementaryModel' => $this->requestComplementaryModel,

			'builder'               => [
				'height'         => empty($this->height) ? 'getHeightModel()' : $this->height,
				'width'          => '\'' . $this->width . '\'',
				'dataModel'      => $this->dataModel,
				'scrollModel'    => $this->scrollModel,
				'animModel'      => $this->animModel,
				'wrap'           => $this->wrap,
				'autofill'       => $this->autofill,
				'colModel'       => $this->colModel,
				'numberCell'     => $this->numberCell,
				'showTitle'      => $this->showTitle,
				'showHeader'     => $this->showHeader,
				'showTop'        => $this->showTop,
				'title'          => $this->title,
				'resizable'      => $this->resizable,
				'columnBorders'  => $this->columnBorders,
				'collapsible'    => $this->collapsible,
				'freezeCols'     => $this->freezeCols,
				'rowBorders'     => $this->rowBorders,
				'stripeRows'     => $this->stripeRows,
				'selectionModel' => $this->selectionModel,
			],
			'gridAfterLoadFunction' => $this->gridAfterLoadFunction,

		];

		foreach ($this->paragrid_option['paragrids'] as &$values) {

			if (!empty($this->pageModel)) {
				$values['builder']['pageModel'] = $this->pageModel;
			}

			if (!empty($this->fillHandle)) {
				$values['builder']['fillHandle'] = $this->fillHandle;
			}

			if (!empty($this->rowDblClick)) {
				$values['builder']['rowDblClick'] = $this->rowDblClick;
			}

			if (!empty($this->filterModel)) {
				$values['builder']['filterModel'] = $this->filterModel;

			}
			if (!empty($this->sortModel)) {
				$values['builder']['sortModel'] = $this->sortModel;

			}
			if (!empty($this->beforeSort)) {
				$values['builder']['beforeSort'] = $this->beforeSort;

			}
			if (!empty($this->beforeFilter)) {
				$values['builder']['beforeFilter'] = $this->beforeFilter;

			}
			if (!empty($this->editorBegin)) {
				$values['builder']['editorBegin'] = $this->editorBegin;

			}
			if (!empty($this->editorBlur)) {
				$values['builder']['editorBlur'] = $this->editorBlur;

			}
			if (!empty($this->editorEnd)) {
				$values['builder']['editorEnd'] = $this->editorEnd;

			}
			if (!empty($this->editorFocus)) {
				$values['builder']['editorFocus'] = $this->editorFocus;

			}
			if (!empty($this->beforeTableView)) {
				$values['builder']['beforeTableView'] = $this->beforeTableView;

			}

			if (!empty($this->autoRowHead)) {
				$values['builder']['autoRowHead'] = $this->autoRowHead;
			}

			if (!empty($this->groupModel)) {
				$values['builder']['groupModel'] = $this->groupModel;
			}

			if (!empty($this->toolbar)) {
				$values['builder']['toolbar'] = $this->toolbar;
			}

			if (!empty($this->complete)) {
				$values['builder']['complete'] = $this->complete;
			}

			if (!empty($this->rowInit)) {
				$values['builder']['rowInit'] = $this->rowInit;
			}

			if (!empty($this->create)) {
				$values['builder']['create'] = $this->create;
			}

			if (!empty($this->change)) {
				$values['builder']['change'] = $this->change;
			}
			if (!empty($this->check)) {
				$values['builder']['check'] = $this->check;
			}

			if (!empty($this->cellSave)) {
				$values['builder']['cellSave'] = $this->cellSave;
			}

			if (!empty($this->cellClick)) {
				$values['builder']['cellClick'] = $this->cellClick;
			}

			if (!empty($this->cellDblClick)) {
				$values['builder']['cellDblClick'] = $this->cellDblClick;
			}

			if (!empty($this->cellKeyDown)) {
				$values['builder']['cellKeyDown'] = $this->cellKeyDown;
			}

			if (!empty($this->editModel)) {
				$values['builder']['editModel'] = $this->editModel;
			}

			if (!empty($this->summaryData)) {
				$values['builder']['summaryData'] = $this->summaryData;
			}

			if ($this->dragOn == 1) {
				$this->dragModel = [
					'on'        => $this->dragOn,
					'diHelper'  => $this->dragdiHelper,
					'clsHandle' => $this->dragclsHandle,
				];
				$values['builder']['dragModel'] = $this->dragModel;
			}

			if ($this->dropOn == 1) {
				$this->dropModel = [
					'on' => $this->dropOn,
				];
				$values['builder']['dropModel'] = $this->dropModel;
			}

			if (!empty($this->moveNode)) {
				$values['builder']['moveNode'] = $this->moveNode;
			}

			if (!empty($this->dragModel)) {
				$values['builder']['dragModel'] = $this->dragModel;
			}

			if (!empty($this->summaryTitle)) {
				$values['builder']['summaryTitle'] = $this->summaryTitle;
			}

			if (!empty($this->dropModel)) {
				$values['builder']['dropModel'] = $this->dropModel;
			}

			if (!empty($this->contextMenu)) {
				$values['contextMenu'] = $this->contextMenu;
			}

			if (!empty($this->detailModel)) {
				$values['builder']['detailModel'] = $this->detailModel;
			}

			if (!empty($this->treeModel)) {
				$values['builder']['treeModel'] = $this->treeModel;
			}

			if (!empty($this->subDetailModel)) {
				$values['subDetailModel'] = $this->subDetailModel;
			}
			if (!empty($this->refresh)) {
				$values['builder']['refresh'] = $this->refresh;
			}
			if (!empty($this->postRenderInterval)) {
				$values['builder']['postRenderInterval'] = $this->postRenderInterval;
			}
			
			
			

		}

		if (!empty($this->gridFunction)) {
			$this->paragrid_option['gridFunction'] = $this->gridFunction;
		}

		if (!empty($this->detailContextMenu)) {
			$this->paragrid_option['detailContextMenu'] = $this->detailContextMenu;
		}

		if (!empty($this->contextMenuoption)) {

			$option = $this->buildOptionContextMenu();

			foreach ($this->paragrid_option['paragrids'] as &$values) {
				$values['contextMenu'] = [
					'#grid_' . $this->paramController => [
						'selector'  => '\'.pq-body-outer .pq-grid-row\'',
						'animation' => [
							'duration' => 250,
							'show'     => '\'fadeIn\'',
							'hide'     => '\'fadeOut\'',
						],
						'build'     => 'function($triggerElement, e){

                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . $this->paramGridVar . '.getRowData( {rowIndx: rowIndex} );
                selected = selgrid' . $this->paramClass . '.getSelection().length;
                var dataLenght = ' . $this->paramGridVar . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {' . PHP_EOL . '     ' . $option . PHP_EOL . '}
                };
                }',
					],
				];
			}

		}

		if (!empty($this->gridExtraFunction)) {

			foreach ($this->gridExtraFunction as $function) {
				$this->paragrid_option['extraFunction'][] = $function;
			}

		}

		if (!empty($this->otherFunction)) {
			$this->paragrid_option['otherFunction'] = $this->otherFunction;
		}

	}

	public function buildOptionContextMenu() {

		$context = Context::getContext();
		$controllerLink = $context->link->getAdminLink($this->paramController);

		$oneSelected = 'visible: function(key, opt){
                           if(selected == 1) {
                                return true;
                            }
                            return false;
                    },' . PHP_EOL;
		$multiSelected = 'visible: function(key, opt){
                            if(selected < 2) {
                                return false;
                            }
                            return true;
                    },' . PHP_EOL;
		$allSelected = 'visible: function(key, opt){
                            if(dataLenght == selected) {
                                return false;
                            }
                            return true;
                    },' . PHP_EOL;
		$option = '';

		foreach ($this->contextMenuoption as $key => $contextMenuoptions) {

			switch ($key) {
			case 'add':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= '                    callback: function(itemKey, opt, e) {

						var datalink = \'' . $controllerLink . '&add' . $this->paramTable . '\';
                        openAjaxLink(datalink);
                        }' . PHP_EOL;
				$option .= '                            },' . PHP_EOL;
				break;
			case 'edit':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $oneSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        var datalink = rowData.openLink;
                        openAjaxLink(datalink);
                    }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'view':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $oneSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        var datalink = rowData.viewLink;
                        openAjaxLink(datalink, rowData.' . $this->paramIdentifier . ', \'' . $this->paramController . '\', \'View' . $this->paramController . '\');
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'details':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $oneSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        var datalink = rowData.detailLink;
                        openAjaxLink(datalink, rowData.' . $this->paramIdentifier . ', \'' . $this->paramController . '\', \'View' . $this->paramController . '\');
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'duplicate':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $oneSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        var datalink = rowData.duplicateLink;
                        openAjaxLink(datalink, rowData.' . $this->paramIdentifier . ', \'' . $this->paramController . '\', \'View' . $this->paramController . '\');
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'select':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $allSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        selgrid' . $this->paramClass . '.selectAll({ all: true });
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'unselect':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $multiSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        ' . $this->paramGridVar . '.setSelection( null );
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
			case 'delete':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $oneSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
                        var identifierlink = rowData.' . $this->paramIdentifier . ';
						var datalink = \'' . $controllerLink . '&' . $this->paramIdentifier . '=\'+identifierlink+\'&id_object=\'+identifierlink+\'&delete' . $this->paramTable . '&action=eleteObject&ajax=true\';
                        deleteAjaxGridLink(datalink);
                        ' . $this->paramGridVar . '.deleteRow({ rowIndx: rowIndex } );
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'bulkdelete':
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= $multiSelected;
				$option .= '                    callback: function(itemKey, opt, e) {
						proceedBulkDelete(selgrid' . $this->paramClass . ');
                        }' . PHP_EOL;
				$option .= '                },' . PHP_EOL;
				break;
			case 'sep':

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '            "' . $subKey . '" :"' . $value . '",' . PHP_EOL;
				}

				break;
			default:
				$option .= '                "' . $key . '"' . ' :{' . PHP_EOL;

				foreach ($contextMenuoptions as $subKey => $value) {
					$option .= '                    ' . $subKey . ': ' . $value . ',' . PHP_EOL;
				}

				$option .= '                },' . PHP_EOL;
				break;
			}

		}

		return $option;

	}

	public function generateParagridScript() {

		$is_function = false;
		$context = Context::getContext();

		$paramGridVar = '';
		$jsScript = '';

		foreach ($this->paragrid_option['paragrids'] as $key => $value) {

			if (isset($value['paramGridVar'])) {
				$paramGridVar = $value['paramGridVar'];
				$jsScript .= 'var ' . $value['paramGridVar'] . ';' . PHP_EOL;
				$jsScript .= '  var ' . $this->paramGridObj . ';' . PHP_EOL;
			}

		}
		if(!empty($this->uppervar)) {
			$jsScript .= $this->uppervar;
		}
 		if(!$this->onlyObject) {
			$jsScript .= '$(document).ready(function(){' . PHP_EOL;
		}
		

		foreach ($this->paragrid_option['paragrids'] as $key => $value) {

			if (empty($this->recIndx)) {

				if (!empty($this->ajaxUrl)) {
					$jsScript .= 'ajax' . $this->paramController . ' = ' . $this->ajaxUrl . ';' . PHP_EOL;
				}
				if(!empty($this->requestComplementaryModel)) {
					$jsScript .= 'var ' .$this->paramController .'ComplementaryModel = ' .$this->requestComplementaryModel .' ;' .PHP_EOL;
				}
				if(!$this->onlyObject) {
					$jsScript .= 'var totalRecords;'. PHP_EOL;
					$jsScript .= 'var hasFilters;'. PHP_EOL;
					$jsScript .= 'var pq_data = [];'. PHP_EOL;
					if($this->needRequestModel) {
						$jsScript .= 'var ' . $this->paramController . 'Model = ' . $this->requestModel . ';' . PHP_EOL;
					}
				
					$jsScript .= 'function ' . $this->heightModel . ';' . PHP_EOL;
				}
			}

		}

		foreach ($this->paragrid_option as $key => $value) {

			if ($key == 'paragrids') {

				foreach ($this->paragrid_option[$key] as $element => $values) {

					if (empty($values['paramGridVar'])) {
						continue;
					}

					$this->paramGridVar = $values['paramGridVar'];
					$this->paramGridId = $values['paramGridId'];
					$this->paramGridObj = $values['paramGridObj'];

					$jsScript .= $this->paramGridObj . ' = {' . PHP_EOL;

					foreach ($values['builder'] as $option => $value) {

						if (is_array($value)) {
							$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
						} else {
							$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
						}

					}

					$jsScript .= '  };' . PHP_EOL;
					if(!empty($this->requestComplementaryModel)) {
						$jsScript .= $this->paramGridObj .'.dataModel = '.$this->paramController .'ComplementaryModel;'. PHP_EOL;
					}
					if(!$this->onlyObject) {
					$jsScript .= '  ' . $this->paramGridVar . ' = pq.grid(\'#' . $this->paramGridId . '\', ' . $this->paramGridObj . ');' . PHP_EOL;
					
					$jsScript .= '  var sel' . $this->paramGridVar . ' = ' . $this->paramGridVar . '.SelectRow();' . PHP_EOL;
					$jsScript .= ' $(\'#' . $this->paramGridId . '\').pqGrid("refresh");' . PHP_EOL;

					if (isset($this->gridAfterLoadFunction)) {
						$jsScript .= $this->gridAfterLoadFunction . PHP_EOL;
					}

					if (isset($values['contextMenu'])) {

						foreach ($values['contextMenu'] as $contextMenu => $value) {
							$jsScript .= '  $("' . $contextMenu . '").contextMenu({' . PHP_EOL;

							foreach ($value as $option => $value) {

								if (is_array($value)) {
									$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
								} else {
									$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
								}

							}

							$jsScript .= '  });' . PHP_EOL;
						}

					}

					if (isset($values['subDetailModel'])) {

						foreach ($values['subDetailModel'] as $detailModel => $value) {
							$jsScript .= '  var ' . $detailModel . ' = function( data ) {' . PHP_EOL;
							$jsScript .= '      return {' . PHP_EOL;

							foreach ($value as $option => $value) {

								if (is_array($value)) {
									$jsScript .= '      ' . $this->deployArrayScript($option, $value) . PHP_EOL;
								} else

								if (empty($option)) {
									$jsScript .= '      ' . $value . ',' . PHP_EOL;
								} else {
									$jsScript .= '      ' . $option . ': ' . $value . ',' . PHP_EOL;
								}

							}

							$jsScript .= '      };' . PHP_EOL;
							$jsScript .= '  };' . PHP_EOL;
						}

					}
					}
				}

			}

			if ($key == 'detailContextMenu') {

				foreach ($this->paragrid_option[$key] as $detailMenu => $value) {
					$jsScript .= 'function ' . $detailMenu . '(evt, ui) {' . PHP_EOL;
					$jsScript .= '  return [' . PHP_EOL;

					foreach ($value as $menus) {

						if (is_array($menus)) {
							$jsScript .= '      {' . PHP_EOL;

							foreach ($menus as $suboption => $value) {
								$jsScript .= '      ' . $suboption . ': ' . $value . ',' . PHP_EOL;

							}

							$jsScript .= '      },' . PHP_EOL;
						} else {
							$jsScript .= '      ' . $menus . ',' . PHP_EOL;
						}

					}

					$jsScript .= '  ];' . PHP_EOL;
					$jsScript .= '};' . PHP_EOL;
				}

			}

			if ($key == 'extraFunction') {

				foreach ($this->paragrid_option[$key] as $function) {
					$jsScript .= $function;

				}

			}

		}
		if(!$this->onlyObject) {
			$jsScript .= '});' . PHP_EOL . PHP_EOL;
		}
		if(!$this->onlyObject) {
			foreach ($this->paragrid_option as $key => $value) {

			if ($key == 'gridFunction') {
				$is_function = true;

				foreach ($this->paragrid_option[$key] as $function => $value) {
					$jsScript .= 'function ' . $function . ' {' . PHP_EOL;
					$jsScript .= $value . PHP_EOL;
					$jsScript .= '}' . PHP_EOL;
				}

			}

			if ($key == 'otherFunction') {

				foreach ($this->paragrid_option[$key] as $function => $value) {
					$jsScript .= 'function ' . $function . ' {' . PHP_EOL;
					$jsScript .= $value . PHP_EOL;
					$jsScript .= '}' . PHP_EOL;
				}

			}

		}

			if ($is_function == false) {
			$jsScript .= 'function get' . $this->paramClass . 'Fields() {' . PHP_EOL;
			$jsScript .= '  var result;' . PHP_EOL;
			$jsScript .= '  $.ajax({' . PHP_EOL;
			$jsScript .= '      type: \'POST\',' . PHP_EOL;
			$jsScript .= '      url: AjaxLink' . $this->paramController . ',' . PHP_EOL;
			$jsScript .= '      data: {' . PHP_EOL;
			$jsScript .= '          action: \'get' . $this->paramClass . 'Fields\',' . PHP_EOL;
			$jsScript .= '          ajax: true' . PHP_EOL;
			$jsScript .= '      },' . PHP_EOL;
			$jsScript .= '      async: false,' . PHP_EOL;
			$jsScript .= '      dataType: \'json\',' . PHP_EOL;
			$jsScript .= '      success: function (data) {' . PHP_EOL;
			$jsScript .= '          result = data;' . PHP_EOL;
			$jsScript .= '      }' . PHP_EOL;
			$jsScript .= '  });' . PHP_EOL;
			$jsScript .= '  return result;' . PHP_EOL;
			$jsScript .= '}' . PHP_EOL . PHP_EOL;
			$jsScript .= 'function get' . $this->paramClass . 'Request() {' . PHP_EOL;
			$jsScript .= '  var result;' . PHP_EOL;
			$jsScript .= '  $.ajax({' . PHP_EOL;
			$jsScript .= '      type: \'POST\',' . PHP_EOL;
			$jsScript .= '      url: AjaxLink' . $this->paramController . ',' . PHP_EOL;
			$jsScript .= '      data: {' . PHP_EOL;
			$jsScript .= '          action: \'get' . $this->paramClass . 'Request\',' . PHP_EOL;
			$jsScript .= '          ajax: true' . PHP_EOL;
			$jsScript .= '      },' . PHP_EOL;
			$jsScript .= '      async: false,' . PHP_EOL;
			$jsScript .= '      dataType: \'json\',' . PHP_EOL;
			$jsScript .= '      success: function (data) {' . PHP_EOL;
			$jsScript .= '          result = data;' . PHP_EOL;
			$jsScript .= '      }' . PHP_EOL;
			$jsScript .= '  });' . PHP_EOL;
			$jsScript .= '  return result;' . PHP_EOL;
			$jsScript .= '}' . PHP_EOL;
			$jsScript .= 'function reload' . $this->paramClass . 'Grid() {' . PHP_EOL;
			$jsScript .= '  ' . $this->paramGridVar . '.option(\'dataModel.data\', get' . $this->paramClass . 'Request());' . PHP_EOL;
			$jsScript .= '  ' . $this->paramGridVar . '.refreshDataAndView();' . PHP_EOL;
			$jsScript .= '}' . PHP_EOL;

		}
		}
		return $jsScript;

	}

	public function deployArrayScript($option, $value, $sub = false) {

		if ($sub) {

			if (is_string($option) && is_array($value) && !Tools::is_assoc($value)) {
				$jsScript = $option . ': [' . PHP_EOL;

				foreach ($value as $suboption => $value) {

					if (is_array($value)) {
						$jsScript .= '          ' . $this->deployArrayScript($suboption, $value, true);
					} else

					if (is_string($suboption)) {
						$jsScript .= '          ' . $suboption . ': ' . $value . ',' . PHP_EOL;
					} else {
						$jsScript .= '          ' . $value . ',' . PHP_EOL;
					}

				}

				$jsScript .= '          ],' . PHP_EOL;
				return $jsScript;

			} else {

				if (is_string($option)) {
					$jsScript = $option . ': {' . PHP_EOL;
				} else {
					$jsScript = ' {' . PHP_EOL;
				}

			}

		} else {

			if (is_string($option)) {
				$jsScript = $option . ': {' . PHP_EOL;
			} else {
				$jsScript = ' {' . PHP_EOL;
			}

		}

		foreach ($value as $suboption => $value) {

			if (is_array($value)) {
				$jsScript .= '          ' . $this->deployArrayScript($suboption, $value, true);
			} else

			if (is_string($suboption)) {
				$jsScript .= '          ' . $suboption . ': ' . $value . ',' . PHP_EOL;
			} else {
				$jsScript .= '          ' . $value . ',' . PHP_EOL;
			}

		}

		if ($sub) {
			$jsScript .= '          },' . PHP_EOL;
		} else {
			$jsScript .= '      },' . PHP_EOL;
		}

		return $jsScript;

	}

	protected function l($string, $class = 'ParamGrid', $addslashes = false, $htmlentities = true) {

		// if the class is extended by a module, use modules/[module_name]/xx.php lang file
		$currentClass = get_class($this);

		if (Module::getModuleNameFromClass($currentClass)) {
			$string = str_replace('\'', '\\\'', $string);

			return Translate::getModuleTranslation(Module::$classInModule[$currentClass], $string, $currentClass);
		}

		global $_LANGADM;

		if ($class == __CLASS__) {
			$class = 'ParamGrid';
		}

		$key = md5(str_replace('\'', '\\\'', $string));
		$str = (array_key_exists(get_class($this) . $key, $_LANGADM)) ? $_LANGADM[get_class($this) . $key] : ((array_key_exists($class . $key, $_LANGADM)) ? $_LANGADM[$class . $key] : $string);
		$str = $htmlentities ? htmlentities($str, ENT_QUOTES, 'utf-8') : $str;

		return str_replace('"', '&quot;', ($addslashes ? addslashes($str) : stripslashes($str)));
	}

}
