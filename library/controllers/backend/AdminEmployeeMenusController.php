<?php

/**
 * Class AdminEmployeeMenusControllerCore
 *
 * @since 1.8.1.0
 */
class AdminEmployeeMenusControllerCore extends AdminController {

	// @codingStandardsIgnoreStart
	/** @var string $position_identifier */
	public $php_self = 'adminemployeemenus';
	protected $position_identifier = 'id_employee_menu';

	/**
	 * AdminEmployeeMenusControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->context = Context::getContext();
		$this->table = 'employee_menu';
		$this->className = 'EmployeeMenu';
		$this->publicName = 'Onglets Back Office';
		$this->context = Context::getContext();
		$this->lang = true;

		parent::__construct();

		$this->cleanTabPositions();

		//$this->extracss = $this->pushCSS([_EPH_THEMES_ . $this->bo_theme . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/employee_menus.css']);

	}

	public function generateParaGridScript() {

		$this->windowHeight = '300';
		$this->paramPageModel = [];
		$this->paramCreate = 'function(){
            var arr = this.pageData().filter(function(rd){
                return (rd.pq_level>-1);
            })
            this.Tree().collapseNodes(arr);
        }';
		$this->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
		$this->paramComplete = 'function(){
		window.dispatchEvent(new Event(\'resize\'));
		$(\'.pq-grid-bottom\').slideUp();
        }';

		$this->paramToolbar = [
			'items' => [
				[
					'type'     => '\'button\'',
					'label'    => '\'Ajouter un Onglet\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'function () {' . PHP_EOL . '
                    	addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                    }' . PHP_EOL,
				],

			],
		];
		$this->dragModel = [
			'on'          => true,
			'diHelper'    => "['position']",
			'clsHandle'   => '\'dragHandle\'',
			'dragNodes'   => 'function(rd, evt){
                var checkNodes = this.Tree().getCheckedNodes();
                return (checkNodes.length && checkNodes.indexOf(rd)>-1 )? checkNodes: [ rd ];
            }',
			'isDraggable' => 'function(ui){
                return !(ui.rowData.pq_gsummary || ui.rowData.pq_level == 0);
            }',
		];
		$this->dropModel = [
			'on'          => true,
			'isDroppable' => 'function(evt, uiDrop){

                var Drag = uiDrop.helper.data(\'Drag\'),
                    uiDrag = Drag.getUI(),
                    rdDrag = uiDrag.rowData,
                    rdDrop = uiDrop.rowData,
                    Tree = this.Tree(),
                    denyDrop = (
                        rdDrop == rdDrag ||
                        rdDrop.pq_gsummary ||
                        Tree.isAncestor( rdDrop,  rdDrag)
                    );

                return !denyDrop;
            }',
		];
		$this->moveNode = 'function(event, ui) {
			 		var grid = this,
        			Tree = grid.Tree();
					var Object;
					console.log(ui);
					var stopIndex;
					var startIndex = ui.args[0][0].employee_menuPosition;
					var level = ui.args[0][0].pq_level;
					var idTab = parseInt(ui.args[0][0].id_employee_menu);
                    var idParent = ui.args[0][0].id_parent;
					if(idParent == 0) {
						stopIndex = ui.args[2];
					} else {
						stopIndex = ui.args[1].employee_menuPosition;
					}


                    processEmployeeMenusPosition(idTab, idParent, startIndex, stopIndex, \'employee_menuPosition_\'+idParent, \'' . $this->context->link->getAdminLink($this->controller_name) . '\');

                    grid.option(\'dataModel.data\', get' . $this->className . 'Request());
                    grid.refreshDataAndView();

					Tree.collapseAll();
					Tree.expandTo(Tree.getNode(idTab));

                }';
		$this->filterModel = [
			'on'     => true,
			'mode'   => '\'AND\'',
			'header' => true,
		];
		$this->treeModel = [
			'dataIndx'     => '\'name\'',
			'id'           => '\'id_employee_menu\'',
			'checkbox'     => 0,
			'checkboxHead' => 0,
			'icons'        => 0,
			'iconCollapse' => ['\'toggeCategoryClose\'', '\'toggeCategoryOpen\''],
		];
		$this->treeExpand = 'function(event, ui) {
			 	var height = $(".pq-table-right.pq-table.pq-td-border-top.pq-td-border-right").outerHeight()+1000;
				setTimeout( function(){
					$(".pq-table-right.pq-table.pq-td-border-top.pq-td-border-right").css("height", height);
				 },1000);
                }';

		$this->paramTitle = '\'' . $this->l('Gestion du Menu Back Office') . '\'';

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
							name: \'' . $this->l('Edit the menu: ') . '\'+rowData.name,
							icon: "edit",
                			callback: function(itemKey, opt, e) {
								editAjaxObject("' . $this->controller_name . '", rowData.id_employee_menu)
                			}
						},
						"sep1": "---------",
				 		"select": {
           					name: \'' . $this->l('Select all item') . '\',
                			icon: "list-ul",
                			visible: function(key, opt){
								var selected = selgrid' . $this->className . '.getSelection().length
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
           					name: \'' . $this->l('Unselect all item') . '\',
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
           					name: \'' . $this->l('Delete the selected menu:') . '\'+rowData.name,
           					icon: "delete",
							visible: function(key, opt){
								return !rowData.hasSubmenu;
                            },
           					callback: function(itemKey, opt, e) {
								deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un Onglet", "Etes vous sure de vouloir supprimer "+rowData.name+ " ?", "Oui", "Annuler",rowData.id_employee_menu);


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

	public function ajaxProcessinitController() {

		return $this->initGridController();
	}

	public function getEmployeeMenuRequest($idParent = 0) {

		$employee_menus = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('b.name, a.id_employee_menu, a.class_name, a.module, a.id_parent, a.id_employee_menu as id,  a.function, a.active, a.position')
				->from('employee_menu', 'a')
				->leftJoin('employee_menu_lang', 'b', 'b.`id_employee_menu` = a.`id_employee_menu` AND b.`id_lang` = ' . $this->context->language->id)
				->where('a.`id_parent` = ' . $idParent)
				->orderBy('a.`position` ASC')
		);

		foreach ($employee_menus as &$employee_menu) {

			if ($employee_menu['active'] == 1) {
				$employee_menu['active'] = '<div class="fa fa-check" style="color: green" onClick="menuInactive(' . $employee_menu['id_employee_menu'] . ')"></div>';
				$employee_menu['enable'] = true;
			} else {
				$employee_menu['active'] = '<div class="fa fa-times" style="color:red" onClick="menuActive(' . $employee_menu['id_employee_menu'] . ')"></div>';
				$employee_menu['enable'] = false;
			}

			$employee_menu['employee_menuPosition'] = $employee_menu['position'];

			$employee_menu['position'] = '<div class="dragGroup"><div class="employee_menuPosition_' . $employee_menu['id_parent'] . ' positions" data-id="' . $employee_menu['id_employee_menu'] . '" data-parent="' . $employee_menu['id_parent'] . '" data-position="' . $employee_menu['position'] . '">' . $employee_menu['position'] . '</div></div>';

			$children = $this->getEmployeeMenuRequest($employee_menu['id_employee_menu']);

			if (is_array($children) && count($children)) {
				$employee_menu['hasSubmenu'] = true;
				$employee_menu['children'] = $this->getEmployeeMenuRequest($employee_menu['id_employee_menu']);
			} else {
				$employee_menu['hasSubmenu'] = false;
			}

		}

		return $employee_menus;

	}

	public function ajaxProcessgetEmployeeMenuRequest() {

		die(Tools::jsonEncode($this->getEmployeeMenuRequest()));

	}

	public function getEmployeeMenuFields() {

		return [

			[
				'title'      => ' ',
				'width'      => 50,
				'dataIndx'   => 'hasSubmenu',
				'dataType'   => 'bool',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'      => '',
				'width'      => 0,
				'dataIndx'   => 'id_parent',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'    => $this->l('ID'),
				'width'    => 50,
				'dataIndx' => 'id_employee_menu',
				'dataType' => 'integer',
				'editable' => false,
				'align'    => 'center',
				'hidden'   => true,
			],

			[
				'title'      => $this->l('Name'),
				'minWidth'   => 200,
				'dataIndx'   => 'name',
				'cls'        => 'name-handle',
				'align'      => 'left',
				'valign'     => 'center',
				'editable'   => false,
				'dataType'   => 'string',
				'hiddenable' => 'no',
			],
			[
				'title'      => $this->l('Controlleur'),
				'minWidth'   => 100,
				'dataIndx'   => 'class_name',
				'align'      => 'left',
				'valign'     => 'center',
				'editable'   => false,
				'dataType'   => 'string',
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Fonction'),
				'width'    => 100,
				'dataIndx' => 'function',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Module'),
				'width'    => 100,
				'dataIndx' => 'module',
				'align'    => 'left',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'string',
			],

			[
				'title'    => $this->l('Actif'),
				'width'    => 50,
				'dataIndx' => 'active',
				'editable' => false,
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'html',

			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'employee_menuPosition',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Position'),
				'minWidth' => 100,
				'maxWidth' => 100,
				'dataIndx' => 'position',
				'valign'   => 'center',
				'editable' => false,
				'cls'      => 'pointer dragHandle',
				'dataType' => 'html',
				'align'    => 'center',
			],
		];
	}

	public function ajaxProcessgetEmployeeMenuFields() {

		die(Tools::jsonEncode($this->getEmployeeMenuFields()));
	}

	public function getTabName($id_employee_menu) {

		$employee_menu = new EmployeeMenu($id_employee_menu);
		return $employee_menu->name[$this->context->language->id];
	}

	public function ajaxProcessUpdateEmployeeMenu() {

		$id_employee_menu = Tools::getValue('id_employee_menu');

		$employeeMenu = new EmployeeMenu($id_employee_menu);

		foreach ($_POST as $key => $value) {

			if (property_exists($employeeMenu, $key) && $key != 'id_employee_menu') {
				$employeeMenu->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($employeeMenu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($employeeMenu->{$field}) || !is_array($employeeMenu->{$field})) {
							$employeeMenu->{$field}

							= [];
						}

						$employeeMenu->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $employeeMenu->update();

		if ($result) {

			$return = [
				'success' => true,
				'message' => $this->l('Le  menu a été mis à jour avec succès'),
			];
		} else {
			$return = [
				'success' => false,
				'message' => $this->l('Il y a eu un problème lors de la mise à jour du menu'),
			];
		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessNewEmployeeMenu() {

		$employeeMenu = new EmployeeMenu();

		foreach ($_POST as $key => $value) {

			if (property_exists($employeeMenu, $key) && $key != 'id_employee_menu') {
				$employeeMenu->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($employeeMenu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($employeeMenu->{$field}) || !is_array($employeeMenu->{$field})) {
							$employeeMenu->{$field}

							= [];
						}

						$employeeMenu->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		try {
			$result = $employeeMenu->add();
		} catch (Exception $ex) {
			$file = fopen("testEmployeeMenu.txt", "w");
			fwrite($file, $ex->getMessage());
		}

		if ($result) {

			$return = [
				'success' => true,
				'message' => $this->l('Le nouveau menu a été crée avec succès'),
			];
		} else {

			$return = [
				'success' => false,
				'message' => $this->l('Il y a eu un problème lors de la création du nouveau menu'),
			];
		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessEditObject() {

		if ($this->tabAccess['edit'] == 1) {
			$data = $this->createTemplate('controllers/employee_menus/editEmployeeMenu.tpl');
			$idEmployeeMenu = Tools::getValue('idObject');

			$object = new EmployeeMenu($idEmployeeMenu, true);

			$employee_menus = EmployeeMenu::getEmployeeMenus($this->context->language->id, 0);

			$selectEmployeeMenu = EmployeeMenu::getEmployeeMenuSelects($this->context->language->id, $object->id_parent);

			foreach ($employee_menus as $key => $employee_menu) {

				if ($employee_menu['id_employee_menu'] == $this->identifier) {
					unset($employee_menus[$key]);
				}

			}

			$employee_menuZero = [
				'id_employee_menu' => 0,
				'name'             => $this->l('Home'),
			];
			array_unshift($employee_menus, $employee_menuZero);
			$data->assign([
				'employee_menu'      => $object,
				'employee_menus'     => $employee_menus,
				'languages'          => Language::getLanguages(false),
				'selectEmployeeMenu' => $selectEmployeeMenu,

			]);

			$li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Edition du menu ' . $object->name[$this->context->language->id] . '</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
			$html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

			$result = [
				'success' => true,
				'li'      => $li,

				'html'    => $html,
			];
		} else {
			$result = [
				'success' => false,
				'message' => 'Votre profile administratif ne vous permet pas d‘éditer les menus administratif',
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddObject() {

		if ($this->tabAccess['add'] == 1) {
			$employee_menu = new EmployeeMenu();

			$idParent = Tools::getValue('idParent');
			$data = $this->createTemplate('controllers/employee_menus/newEmployeeMenu.tpl');

			$employee_menus = EmployeeMenu::getEmployeeMenus($this->context->language->id, 0);
			$selectEmployeeMenu = EmployeeMenu::getEmployeeMenuSelects($this->context->language->id, $idParent);

			$data->assign([
				'employee_menu'      => $employee_menu,
				'employee_menus'     => $employee_menus,
				'idParent'           => $idParent,
				'languages'          => Language::getLanguages(false),
				'selectEmployeeMenu' => $selectEmployeeMenu,

			]);

			$li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Ajouter un nouvel Onglet </a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
			$html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';
			$result = [
				'success' => true,
				'li'      => $li,
				'html'    => $html,
			];
		} else {
			$result = [
				'success' => false,
				'message' => 'Votre profile administratif ne vous permet pas de créer un ongelt administratif',
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessMenuInactive() {

		$idEmployeeMenu = Tools::getValue('idEmployeeMenu');
		$object = new EmployeeMenu($idEmployeeMenu);
		$object->active = 0;
		$object->update();

		$result = [
			'success' => true,
			'message' => 'Le menu a été désactivé avec succès',
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessMenuActive() {

		$idEmployeeMenu = Tools::getValue('idEmployeeMenu');
		$object = new EmployeeMenu($idEmployeeMenu);
		$object->active = 1;
		$object->update();

		$result = [
			'success' => true,
			'message' => 'Le menu a été activé avec succès',
		];

		die(Tools::jsonEncode($result));
	}

	/**
	 * Post processing
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function postProcess() {

		parent::postProcess();

	}

	public function movePosition($idParent, $idTab, $k) {

		$result = Db::getInstance()->execute(
			'UPDATE `' . _DB_PREFIX_ . 'employee_menu`
            SET `position`= ' . $k . '
            WHERE `id_parent` =' . (int) $idParent . ' AND `id_employee_menu` = ' . $idTab);

		if (!$result) {
			return false;
		}

		return true;

	}

	public function cleanTabPositions() {

		$employee_menus = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('`id_employee_menu`')
				->from('employee_menu')
				->where('`id_parent` = 0')
				->orderBy('`position` ASC'));

		$k = 1;

		foreach ($employee_menus as $employee_menu) {
			$this->movePosition(0, $employee_menu['id_employee_menu'], $k);
			$k++;
		}

		foreach ($employee_menus as $employee_menu) {

			$childs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('`id_employee_menu`, `position`')
					->from('employee_menu')
					->where('`id_parent` = ' . (int) $employee_menu['id_employee_menu'])
					->orderBy('`position` ASC'));

			$k = 1;

			foreach ($childs as $child) {

				$this->movePosition($employee_menu['id_employee_menu'], $child['id_employee_menu'], $k);
				$k++;

			}

		}

	}

	/**
	 * Ajax process update positions
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdatePositions() {

		$positions = Tools::getValue('positions');
		$idObject = Tools::getvalue('idObject');
		$idParent = Tools::getValue('idParent');
		$stopIndex = Tools::getValue('stopIndex');

		$object = new $this->className($idObject);

		if (Validate::isLoadedObject($object)) {
			$initPosition = $object->position;

			if ($initPosition > $stopIndex) {

				$objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select($this->identifier . ',  `position` ')
						->from($this->table)
						->where('`id_parent` = ' . (int) $idParent . ' AND `position` >= ' . (int) $stopIndex . ' AND `position` < ' . (int) $initPosition)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {
					$k = $stopIndex + 1;

					foreach ($objects as $moveObject) {

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . $this->table . '`
							SET `position`= ' . (int) $k . '
							WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $moveObject[$this->identifier]);
						} else {
							$k++;
						}

					}

					if (empty($this->errors)) {
						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . $this->table . '`
							SET `position`= ' . (int) $stopIndex . '
							WHERE `' . $this->identifier . '` =' . (int) $idObject);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $idObject);
						}

					}

				}

			} else

			if ($initPosition < $stopIndex) {

				$objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select($this->identifier . ',  `position` ')
						->from($this->table)
						->where('`id_parent` = ' . (int) $idParent . ' AND `position` >= ' . (int) $initPosition)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {
					$k = $initPosition - 1;

					foreach ($objects as $moveObject) {

						if ($moveObject[$this->identifier] == $idObject) {
							$k++;
							continue;
						}

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . $this->table . '`
							SET `position`= ' . (int) $k . '
							WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $moveObject[$this->identifier]);
						} else {
							$k++;
						}

					}

					if (empty($this->errors)) {
						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . $this->table . '`
							SET `position`= ' . (int) $stopIndex . '
							WHERE `' . $this->identifier . '` =' . (int) $idObject);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $idObject);
						}

					}

				}

			}

		}

		if (empty($this->errors)) {
			$result = [
				'success' => true,
				'message' => $this->l('Tab position has been successfully updated.'),
			];
		} else {
			$this->errors = array_unique($this->errors);
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];

		}

		die(Tools::jsonEncode($result));

	}

}
