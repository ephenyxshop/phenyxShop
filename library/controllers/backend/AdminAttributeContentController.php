<?php

/**
 * Class AdminAttributeContentControllerCore
 *
 * @since 1.8.1.0
 */
class AdminAttributeContentControllerCore extends AdminController {

	public $php_self = 'adminattributecontent';
	// @codingStandardsIgnoreStart
	/** @var AttributeGroup Cms category instance for navigation */
	protected static $category = null;
	/** @var AdminAttributesController $admin_attribute_group */
	protected $admin_attribute_group;
	/** @var object adminAttribute() instance */
	protected $admin_attribute;

	public $attributeField = [];

	public $attributeCategoryField = [];

	public $attributeGridId = [];

	protected $paragridScript;
	// @codingStandardsIgnoreEnd

	/**
	 * AdminAttributeContentControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->context = Context::getContext();
		/* Get current category */

		$this->table = 'attribute_group';
		$this->className = 'AttributeGroup';
		$this->publicName = $this->l('Géstion des déclinaisons');

		$this->admin_attribute_group = new AdminAttributesGroupsController();
		$this->admin_attribute_group->init();
		$this->admin_attribute = new AdminAttributesController();
		$this->admin_attribute->init();

		parent::__construct();

		if (empty($this->attributeField)) {
			$this->attributeField = EmployeeConfiguration::updateValue('EXPERT_ATTRIBUTESFIELDS', Tools::jsonEncode($this->getAttributesFields()));
		}

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_ATTRIBUTESSCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_ATTRIBUTESSCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_ATTRIBUTESSCRIPT');
		}

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_PS_JS_DIR_ . 'attributeGroup.js',
		]);
	}

	public function ajaxProcessOpenTargetController() {

		$data = $this->createTemplate($this->table . '.tpl');
		$attributeGridId = [];
		$attributeCategories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('a.`id_attribute_group`,  b.`name`, a.`position`')
				->from('attribute_group', 'a')
				->leftJoin('attribute_group_lang', 'b', 'b.`id_attribute_group` = a.`id_attribute_group` AND b.`id_lang` = ' . $this->context->language->id)
				->orderBy('a.`id_attribute_group` ASC, a.`position` ASC')
		);

		foreach ($attributeCategories as $category) {
			$name = $category['name'];
			$attributeGridId[] = [
				'paraGridId'    => 'grid_' . $this->controller_name . $category['id_attribute_group'],
				'paraGridTitle' => $name,
				'paraGridVar'   => 'grid' . $this->className . $category['id_attribute_group'],
				'dataId'        => $category['id_attribute_group'],

			];
		}

		$data->assign([
			'paragridScript'  => $this->generateParaGridScript(),
			'controller'      => $this->controller_name,
			'tableName'       => $this->table,
			'className'       => $this->className,
			'link'            => $this->context->link,
			'extraJs'         => $this->setAjaxMedia(),
			'attributeGridId' => $attributeGridId,
			'days'            => $days,
		]);

		$li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function generateParaGridScript($regenerate = false) {

		$attributeCategories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('a.`id_attribute_group`,  b.`name`, a.`position`')
				->from('attribute_group', 'a')
				->leftJoin('attribute_group_lang', 'b', 'b.`id_attribute_group` = a.`id_attribute_group` AND b.`id_lang` = ' . $this->context->language->id)
				->orderBy('a.`id_attribute_group` ASC, a.`position` ASC')
		);

		$ajaxlinkCms = $this->context->link->getAdminLink($this->controller_name);
		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

		foreach ($attributeCategories as $category) {

			$paragrid->paramGridObj = 'obj' . $this->admin_attribute_group->className . $category['id_attribute_group'];
			$paragrid->paramGridVar = 'grid' . $this->className . $category['id_attribute_group'];
			$paragrid->paramGridId = 'grid_' . $this->controller_name . $category['id_attribute_group'];

			$name = $category['name'];

			$paragrid->paragrid_option['paragrids'][] = [
				'paramGridVar' => $paragrid->paramGridVar,
				'paramGridObj' => $paragrid->paramGridObj,
				'paramGridId'  => $paragrid->paramGridId,
				'builder'      => [
					'height'         => '600',
					'width'          => '\'100%\'',
					'complete'       => 'function(){
					window.dispatchEvent(new Event(\'resize\'));
        		}',
					'rowInit'        => 'function (ui) {
					return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->admin_attribute->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
           			 };
        		}',
					'dataModel'      => [
						'recIndx' => "'$this->identifier'",
						'data'    => 'get' . $this->admin_attribute->className . 'Request(' . $category['id_attribute_group'] . ')',
					],
					'toolbar'        => [
						'items' => [

							[
								'type'     => '\'button\'',
								'label'    => '\'Ajouter une déclinaison\'',
								'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
								'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->admin_attribute->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
							],
							[
								'type'     => '\'button\'',
								'label'    => '\'Ajouter un Groupe de déclinaison \'',
								'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
								'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->admin_attribute_group->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
							],
						],
					],
					'scrollModel'    => [
						'autoFit' => !0,
					],
					'colModel'       => 'getAttributesFields()',
					'numberCell'     => [
						'show' => 0,
					],
					'pageModel'      => [
						'type'       => '\'local\'',
						'rPP'        => 20,
						'rPPOptions' => [10, 20, 40, 50],
					],

					'title'          => "'$name'",
					'showTitle'      => 1,

					'rowBorders'     => 1,
					'selectionModel' => [
						'type' => '\'row\'',
					],
					'collapsible'    => 0,
					'dragModel'      => [
						'on'        => true,
						'diHelper'  => "['position']",
						'clsHandle' => '\'dragHandle\'',
					],
					'dropModel'      => [
						'on' => true,
					],
					'dragColumns'    => [
						'enabled' => 0,
					],
					'moveNode'       => 'function(event, ui) {
				console.log(ui);
                var startIndex = ui.args[0][0].attributePosition;
				var idAttribute = ui.args[0][0].id_attribute;
				var idParent = ui.args[0][0].id_attribute_group;
                var stopIndex = parseInt(ui.args[1]);
                var way = (startIndex < stopIndex) ? 1 : 0;
				processAttributePosition(idAttribute, idParent, way, startIndex, stopIndex)

               }',
				],
				'contextMenu'  => [
					'#' . $paragrid->paramGridId . ' .pq-body-outer .pq-table' => [
						'selector' => '\'.pq-grid-row\'',

						'build'    => 'function($triggerElement, e){
				var rowIndex = $($triggerElement).attr("data-rowIndx");
				var rowData = ' . $paragrid->paramGridVar . '.getRowData( {rowIndx: rowIndex} );
        		return {
            		callback: function(){},
            		items: {
						"add": {
							name: \'' . $this->l('Ajouter une nouvelle Déclinaison') . '\',
							icon: "add",
                			callback: function(itemKey, opt, e) {
								addAjaxObject("' . $this->admin_attribute->controller_name . '");
                			}
							},
                		"edit": {
							name: \'' . $this->l('Modifier la déclinaison : ') . ' :\'+rowData.name,
							icon: "edit",
                			callback: function(itemKey, opt, e) {

								editAjaxObject("' . $this->admin_attribute->controller_name . '", rowData.id_attribute)
                			}
						},
						"sep1": "---------",
           				"delete": {
           					name: \'' . $this->l('Supprimer le déclinaison ') . ' :\'+rowData.name,
           					icon: "delete",
							visible: function(key, opt){
								return !rowData.hasSubmenu;
                            },
           					callback: function(itemKey, opt, e) {

							}
						}
       				},
				};
			}',
					],

				],
			];

		}

		$paragrid->gridFunction = [
			'getAttributesFields()'                  => '
        	var result ;
            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminAttributeContent,
                data: {
                    action: \'getAttributesFields\',
                    ajax: true
                },
                async: false,
                dataType: \'json\',
                success: function success(data) {
                    result = data;
                }
            });
            return result;',
			'getAttributesRequest(idAttributeGroup)' => '
            var result;
            $.ajax({
            type: \'POST\',
            url: AjaxLinkAdminAttributeContent,
            data: {
                action: \'getAttributesRequest\',
				idAttributeGroup: idAttributeGroup,
                ajax: true
            },
            async: false,
            dataType: \'json\',
            success: function (data) {
                result = data;
            }
        });
        return result;',
			'reloadTabGrid(idParentCategory)'        => '
			window["gridAttributeGroup"+idParentCategory].option(\'dataModel.data\', getAttributesRequest(idParentCategory));
			window["gridAttributeGroup"+idParentCategory].refreshDataAndView();
		',
		];

		$option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();

		$this->paragridScript = $script;
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';
	}

	public function builParaGridOption() {}

	public function ajaxProcessinitController() {

		return $this->initGridController();

	}

	public function getAttributesRequest($id_attribute_group) {

		$attributePages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('b.*, a.*, cml.`name` as group_name')
				->from('attribute', 'a')
				->leftJoin('attribute_lang', 'b', 'b.`id_attribute` = a.`id_attribute` AND b.`id_lang` = ' . $this->context->language->id)
				->leftJoin('attribute_group_lang', 'cml', 'cml.`id_attribute_group` = a.`id_attribute_group` AND cml.`id_lang` = ' . $this->context->language->id)
				->where('a.`id_attribute_group` = ' . (int) $id_attribute_group)
				->orderBy('a.`position` ASC')
		);

		$attributeLink = $this->context->link->getAdminLink($this->controller_name);

		foreach ($attributePages as &$page) {

			$page['attributePosition'] = $page['position'];
			$page['position'] = '<div class="dragGroup"><div class="valueAttribute positions" data-id="' . $page['id_attribute'] . '">' . $page['position'] . '</div></div>';

		}

		return $attributePages;

	}

	public function ajaxProcessgetAttributesRequest() {

		$id_attribute_group = Tools::getValue('idAttributeGroup');
		die(Tools::jsonEncode($this->getAttributesRequest($id_attribute_group)));

	}

	public function getAttributesFields() {

		return [

			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'dataIndx'   => 'id_attribute',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => $this->l('Title'),
				'width'      => 200,
				'dataIndx'   => 'name',
				'cls'        => 'name-handle',
				'align'      => 'left',
				'editable'   => false,
				'dataType'   => 'string',
				'hiddenable' => 'no',
			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'attributePosition',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Position'),
				'minWidth' => 100,
				'maxWidth' => 100,
				'dataIndx' => 'position',
				'cls'      => 'pointer dragHandle',
				'dataType' => 'html',
				'align'    => 'center',
			],

		];

	}

	public function ajaxProcessgetAttributesFields() {

		die(EmployeeConfiguration::get('EXPERT_ATTRIBUTESFIELDS'));
	}

	public function ajaxProcessaddAttribute() {

		$attribute = new Attributes();

		foreach ($_POST as $key => $value) {

			if (property_exists($attribute, $key) && $key != 'id_attribute') {
				$attribute->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($attribute));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($attribute->{$field}) || !is_array($attribute->{$field})) {
							$attribute->{$field}

							= [];
						}

						$attribute->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $attribute->add();

		$return = [
			'success' => true,
			'message' => $this->l('La nouvelle déclinaison a été ajoutée avec succès'),
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddAttributeGroup() {

		$file = fopen("testAddAttributeGroup.txt", "w");
		$attribute = new AttributeGroup();

		foreach ($_POST as $key => $value) {

			if (property_exists($attribute, $key) && $key != 'id_attribute_group') {
				$attribute->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($attribute));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($attribute->{$field}) || !is_array($attribute->{$field})) {
							$attribute->{$field}

							= [];
						}

						$attribute->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $attribute->add();

		if ($result) {
			$li = '<li data-class="AttributeContent" data-grid="grid_AdminAttributeContent' . $attribute->id . '" data-category="' . $attribute->id . '" data-catname="' . $attribute->name[$this->context->language->id] . '"><a href="#grid_AdminAttributeContent' . $attribute->id . '">' . $attribute->name[$this->context->language->id] . '</a></li>';
			$html = '<div id="grid_AdminAttributeContent' . $attribute->id . '" class="panel col-lg-12"></div>';
			$return = [
				'success' => true,
				'message' => $this->l('La catégorie Attribute a été ajoutée à jour avec succès'),
				'action'  => 'add',
				'li'      => $li,
				'html'    => $html,
			];
		} else {
			$return = [
				'success' => false,
				'message' => $this->l('L‘ajout de la catégorie de déclinaison à échouée'),
			];
		}

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessUpdateAttribute() {

		$idAttribute = Tools::getValue('id_attribute');

		$attribute = new Attributes($idAttribute);

		foreach ($_POST as $key => $value) {

			if (property_exists($attribute, $key) && $key != 'id_attribute' && $key != 'id_parent') {
				$attribute->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($attribute));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($attribute->{$field}) || !is_array($attribute->{$field})) {
							$attribute->{$field}

							= [];
						}

						$attribute->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $attribute->update();

		$return = [
			'success' => true,
			'message' => $this->l('La Déclinaison ont mis à jour avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessUpdateAttributeGroup() {

		$idAttribute = Tools::getValue('id_attribute_group');

		$attribute = new AttributeGroup($idAttribute);

		foreach ($_POST as $key => $value) {

			if (property_exists($attribute, $key) && $key != 'id_attribute_group') {
				$attribute->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($attribute));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($attribute->{$field}) || !is_array($attribute->{$field})) {
							$attribute->{$field}

							= [];
						}

						$attribute->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $attribute->update();

		$return = [
			'success' => true,
			'message' => $this->l('La catégorie Attribute a été mis à jour avec succès'),
			'action'  => 'update',
		];

		die(Tools::jsonEncode($return));
	}

	/**
	 * Return current category
	 *
	 * @return AttributeGroup
	 *
	 * @since 1.8.1.0
	 */
	public static function getCurrentAttributeGroup() {

		return static::$category;
	}

	/**
	 * Post process
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function postProcess() {

		parent::postProcess();

	}

	/**
	 * Ajax process update attribute positions
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateAttributesPositions() {

		$positions = Tools::getValue('positions');
		$idAttribute = (int) Tools::getValue('idAttribute');
		$idParent = Tools::getValue('idParent');
		$stopIndex = Tools::getValue('stopIndex');
		$stopIndex--;

		$object = new Attributes($idAttribute);

		if (Validate::isLoadedObject($object)) {

			$initPosition = $object->position;

			if ($initPosition > $stopIndex) {

				$objects = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select('id_attribute,  `position` ')
						->from('attribute')
						->where('`id_attribute_group` = ' . (int) $idParent . ' AND `position` >= ' . (int) $stopIndex . ' AND `position` <= ' . (int) $initPosition)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {
					$k = $stopIndex + 1;

					foreach ($objects as $moveObject) {

						if ($moveObject['id_attribute'] == $idAttribute) {
							$result = Db::getInstance()->execute(
								'UPDATE `' . _DB_PREFIX_ . 'attribute`
								SET `position`= ' . (int) $stopIndex . '
								WHERE `id_attribute` =' . (int) $idAttribute);
							continue;
						}

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . 'attribute`
							SET `position`= ' . (int) $k . '
							WHERE `id_attribute` =' . (int) $moveObject['id_attribute']);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with Attribute position update ' . $moveObject['id_attribute']);
						} else {
							$k++;
						}

					}

				}

			} else

			if ($initPosition < $stopIndex) {

				$objects = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select('id_attribute,  `position` ')
						->from('attribute')
						->where('`id_attribute_group` = ' . (int) $idParent . ' AND `position` >= ' . (int) $initPosition . ' AND `position` <= ' . $stopIndex)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {

					$k = $initPosition;

					foreach ($objects as $moveObject) {

						if ($moveObject['id_attribute'] == $idAttribute) {

							$result = Db::getInstance()->execute(
								'UPDATE `' . _DB_PREFIX_ . 'attribute`
							SET `position`= ' . (int) $stopIndex . '
							WHERE `id_attribute` =' . (int) $idAttribute);
							continue;
						}

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . 'attribute`
							SET `position`= ' . (int) $k . '
							WHERE`id_attribute` =' . (int) $moveObject['id_attribute']);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with Attribute position update ' . $moveObject['id_attribute']);
						} else {

							$k++;
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

	/**
	 * Ajax process update AttributeGroup positions
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCmsCategoriesPositions() {

		if ($this->tabAccess['edit'] === '1') {
			$idAttributeGroupToMove = (int) Tools::getValue('id_attribute_group_to_move');
			$idAttributeGroupParent = (int) Tools::getValue('id_attribute_group_parent');
			$way = (int) Tools::getValue('way');
			$positions = Tools::getValue('attribute_group');

			if (is_array($positions)) {

				foreach ($positions as $key => $value) {
					$pos = explode('_', $value);

					if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $idAttributeGroupParent && $pos[2] == $idAttributeGroupToMove)) {
						$position = $key;
						break;
					}

				}

			}

			$attributeCategory = new AttributeGroup($idAttributeGroupToMove);

			if (Validate::isLoadedObject($attributeCategory)) {

				if (isset($position) && $attributeCategory->updatePosition($way, $position)) {
					die(true);
				} else {
					die('{"hasError" : true, "errors" : "Can not update attribute categories position"}');
				}

			} else {
				die('{"hasError" : true, "errors" : "This attribute category can not be loaded"}');
			}

		}

	}

	/**
	 * Ajax process publish Attribute
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessPublishAttribute() {

		if ($this->tabAccess['edit'] === '1') {

			if ($idCms = (int) Tools::getValue('id_attribute')) {
				$boCmsUrl = $this->context->link->getAdminLink('AdminAttribute', true) . '&updateattribute&id_attribute=' . (int) $idCms;

				if (Tools::getValue('redirect')) {
					die($boCmsUrl);
				}

				$attribute = new Attribute((int) (Tools::getValue('id_attribute')));

				if (!Validate::isLoadedObject($attribute)) {
					die('error: invalid id');
				}

				$attribute->active = 1;

				if ($attribute->save()) {
					die($boCmsUrl);
				} else {
					die('error: saving');
				}

			} else {
				die('error: parameters');
			}

		}

	}

}
