<?php

/**
 * Class AdminCmsContentControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCmsContentControllerCore extends AdminController {

	public $php_self = 'admincmscontent';
	// @codingStandardsIgnoreStart
	/** @var CMSCategory Cms category instance for navigation */
	protected static $category = null;
	/** @var AdminCmsCategoriesController $admin_cms_categories */
	protected $admin_cms_categories;
	/** @var object adminCMS() instance */
	protected $admin_cms;

	public $cmsField = [];

	public $cmsCategoryField = [];

	public $cmsGridId = [];

	protected $paragridScript;
	// @codingStandardsIgnoreEnd

	/**
	 * AdminCmsContentControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'cmscontent';
		$this->publicName = $this->l('Gestion des Contenu CMS');
		$this->context = Context::getContext();
		/* Get current category */
		$idCmsCategory = (int) Tools::getValue('id_cms_category', Tools::getValue('id_cms_category_parent', 1));
		static::$category = new CMSCategory($idCmsCategory);

		if (!Validate::isLoadedObject(static::$category)) {
			die('Category cannot be loaded');
		}

		$this->table = 'cms';
		$this->className = 'CMS';
		$this->publicName = $this->l('CMS');
		$this->bulk_actions = [
			'delete' => [
				'text'    => $this->l('Delete selected'),
				'confirm' => $this->l('Delete selected items?'),
				'icon'    => 'icon-trash',
			],
		];

		$this->admin_cms_categories = new AdminCmsCategoriesController();
		$this->admin_cms_categories->init();
		$this->admin_cms = new AdminCmsController();
		$this->admin_cms->init();

		parent::__construct();

		if (empty($this->cmsField)) {
			$this->cmsField = EmployeeConfiguration::updateValue('EXPERT_CMS_FIELDS', Tools::jsonEncode($this->getCMSFields()));
		}

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_CMS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_CMS_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_CMS_SCRIPT');
		}

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_PS_JS_DIR_ . 'cmsCategory.js',
			_PS_JS_DIR_ . 'tinymce/tinymce.min.js',
			_PS_JS_DIR_ . 'tinymce.inc.js',
		]);
	}

	public function ajaxProcessOpenTargetController() {

		$this->paragridScript = $this->generateParaGridScript();
		$this->setAjaxMedia();

		$data = $this->createTemplate('cmscontent.tpl');

		$cmsGridId = [];
		$cmsCategories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('a.`id_cms_category`, a.`id_parent`, b.`name`, a.`position`, a.`active`')
				->from('cms_category', 'a')
				->leftJoin('cms_category_lang', 'b', 'b.`id_cms_category` = a.`id_cms_category` AND b.`id_lang` = ' . $this->context->language->id . ' AND b.`id_shop` = ' . $this->context->shop->id)
				->orderBy('a.`id_cms_category` ASC, a.`position` ASC')
		);

		foreach ($cmsCategories as $category) {
			$name = $category['name'];
			$cmsGridId[] = [
				'paraGridId'    => 'grid_AdminCmsContent' . $category['id_cms_category'],
				'paraGridTitle' => $name,
				'paraGridVar'   => 'gridAdminCmsContent' . $category['id_cms_category'],
				'dataId'        => $category['id_cms_category'],

			];
		}

		$data->assign([
			'paragridScript' => $this->paragridScript,
			'controller'     => $this->controller_name,
			'tableName'      => $this->table,
			'className'      => $this->className,
			'link'           => $this->context->link,
			'extraJs'        => $this->push_js_files,
			'cmsGridId'      => $cmsGridId,
		]);

		$li = '<li id="uper' . $this->controller_name . '" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">Gestion des Contenu CMS</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function generateParaGridScript($regenerate = false) {

		$cmsCategories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('a.`id_cms_category`, a.`id_parent`, b.`name`, a.`position`, a.`active`')
				->from('cms_category', 'a')
				->leftJoin('cms_category_lang', 'b', 'b.`id_cms_category` = a.`id_cms_category` AND b.`id_lang` = ' . $this->context->language->id . ' AND b.`id_shop` = ' . $this->context->shop->id)
				->orderBy('a.`id_cms_category` ASC, a.`position` ASC')
		);

		$ajaxlinkCms = $this->context->link->getAdminLink($this->controller_name);
		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

		foreach ($cmsCategories as $category) {

			$paragrid->paramGridObj = 'obj' . $this->admin_cms_categories->className . $category['id_cms_category'];
			$paragrid->paramGridVar = 'grid' . $this->className . $category['id_cms_category'];
			$paragrid->paramGridId = 'grid_' . $this->controller_name . $category['id_cms_category'];

			$name = $category['name'];

			$paragrid->paragrid_option['paragrids'][] = [
				'paramGridVar' => $paragrid->paramGridVar,
				'paramGridObj' => $paragrid->paramGridObj,
				'paramGridId'  => $paragrid->paramGridId,
				'builder'      => [
					'height'         => '\'flex\'',
					'width'          => '\'100%\'',
					'complete'       => 'function(){
					window.dispatchEvent(new Event(\'resize\'));
        		}',
					'rowInit'        => 'function (ui) {
					return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->admin_cms->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
           			 };
        		}',
					'dataModel'      => [
						'recIndx' => "'$this->identifier'",
						'data'    => 'get' . $this->admin_cms->className . 'Request(' . $category['id_cms_category'] . ')',
					],
					'toolbar'        => [
						'items' => [

							[
								'type'     => '\'button\'',
								'label'    => '\'Ajouter une page CMS\'',
								'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
								'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->admin_cms->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
							],
							[
								'type'     => '\'button\'',
								'label'    => '\'Ajouter une famille de CMS \'',
								'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
								'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->admin_cms_categories->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
							],
						],
					],
					'scrollModel'    => [
						'autoFit' => !0,
					],
					'colModel'       => 'getCMSFields()',
					'numberCell'     => [
						'show' => 0,
					],

					'title'          => "'$name'",
					'showTitle'      => 1,
					'filterModel'    => [
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
					],
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
                var startIndex = ui.args[0][0].cmsPosition;
				var idCms = ui.args[0][0].id_cms;
                var idParent = ui.args[0][0].id_cms_category;
                var stopIndex = parseInt(ui.args[1]);
                var way = (startIndex < stopIndex) ? 1 : 0;
                processCmsPosition(idCms, idParent, way, startIndex, stopIndex)}',
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
							name: \'' . $this->l('Ajouter une nouvelle page CMS') . '\',
							icon: "add",
                			callback: function(itemKey, opt, e) {
								addAjaxObject("' . $this->admin_cms->controller_name . '");
                			}
							},
                		"edit": {
							name: \'' . $this->l('Modifier la page ') . ' : \'+rowData.meta_title,
							icon: "edit",
                			callback: function(itemKey, opt, e) {
								editAjaxObject("' . $this->admin_cms->controller_name . '", rowData.id_cms)
                			}
						},
						"open": {
							name: \'' . $this->l('Voir dans une nouvelle fenêtre ') . ' : \'+rowData.meta_title,
							icon: "eye",
                			callback: function(itemKey, opt, e) {
								window.open (rowData.viewLink, "_blank");
                			}
						},
           				"delete": {
           					name: \'' . $this->l('Supprimer la page') . ' : \'+rowData.meta_title,
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
			'getCMSFields()'               => '
        	var result ;
            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminCmsContent,
                data: {
                    action: \'getCMSFields\',
                    ajax: true
                },
                async: false,
                dataType: \'json\',
                success: function success(data) {
                    result = data;
                }
            });
            return result;',
			'getCMSRequest(idCmsCategory)' => '
            var result;
            $.ajax({
            type: \'POST\',
            url: AjaxLinkAdminCmsContent,
            data: {
                action: \'getCMSRequest\',
				idCmsCategory: idCmsCategory,
                ajax: true
            },
            async: false,
            dataType: \'json\',
            success: function (data) {
                result = data;
            }
        });
        return result;',
			'reloadTabGrid(idParentCms)'   => '
			window["gridCMS"+idParentCms].option(\'dataModel.data\', getCMSRequest(idParentCms));
			window["gridCMS"+idParentCms].refreshDataAndView();',
		];

		$option = $paragrid->generateParaGridOption();
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

	public function getCMSRequest($id_cms_category) {

		$cmsPages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('b.*, a.*, cml.`name`')
				->from('cms', 'a')
				->leftJoin('cms_lang', 'b', 'b.`id_cms` = a.`id_cms` AND b.`id_lang` = ' . $this->context->language->id . ' AND b.`id_shop` = ' . $this->context->shop->id)
				->leftJoin('cms_category_lang', 'cml', 'cml.`id_cms_category` = a.`id_cms_category` AND cml.`id_lang` = ' . $this->context->language->id . ' AND cml.`id_shop` = ' . $this->context->shop->id)
				->where('a.`id_cms_category` = ' . (int) $id_cms_category)
				->orderBy('a.`position` ASC')
		);

		$cmsLink = $this->context->link->getAdminLink($this->controller_name);

		foreach ($cmsPages as &$page) {

			if ($page['active'] == 1) {
				$page['active'] = '<div class="i-active"></div>';
			} else {
				$page['active'] = '<div class="i-inactive"></div>';
			}

			$page['viewLink'] = $this->context->link->getFrontCMSLink($page['id_cms']);

			$page['cmsPosition'] = $page['position'];
			$page['position'] = '<div class="dragGroup"><div class="cmsPosition_' . $page['id_cms_category'] . ' positions" data-id="' . $page['id_cms'] . '">' . $page['position'] . '</div></div>';

		}

		return $cmsPages;

	}

	public function ajaxProcessgetCMSRequest() {

		$id_cms_category = Tools::getValue('idCmsCategory');
		die(Tools::jsonEncode($this->getCMSRequest($id_cms_category)));

	}

	public function getCMSFields() {

		return [

			[

				'dataIndx'   => 'viewLink',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => $this->l('ID'),
				'width'      => 50,
				'dataIndx'   => 'id_cms',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => $this->l('Title'),
				'width'      => 200,
				'dataIndx'   => 'meta_title',
				'cls'        => 'name-handle',
				'align'      => 'left',
				'editable'   => false,
				'dataType'   => 'string',
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('URL'),
				'width'    => 200,
				'dataIndx' => 'link_rewrite',
				'cls'      => 'name-handle',
				'align'    => 'left',
				'editable' => false,
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Displayed'),
				'width'    => 200,
				'dataIndx' => 'active',
				'align'    => 'center',
				'editable' => false,
				'dataType' => 'html',
			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'cmsPosition',
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

	public function ajaxProcessgetCMSFields() {

		die(EmployeeConfiguration::get('EXPERT_CMS_FIELDS'));
	}

	public function ajaxProcessUpdateCms() {

		$idCMS = Tools::getValue('id_cms');

		$cms = new CMS($idCMS);

		foreach ($_POST as $key => $value) {

			if (property_exists($cms, $key) && $key != 'id_cms' && $key != 'id_parent') {
				$cms->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($cms));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($cms->{$field}) || !is_array($cms->{$field})) {
							$cms->{$field}

							= [];
						}

						$cms->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		foreach (Language::getIDs(false) as $idLang) {

			if (isset($_POST['meta_keywords_' . $idLang])) {
				$_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
				$cms->keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
			}

		}

		try {
			$result = $cms->update();
		} catch (Exception $e) {
			$file = fopen("testProcessUpdateCms.txt", "w");
			fwrite($file, $e->getMessage());
		}

		$return = [
			'success' => true,
			'message' => $this->l('La page CMS ont mis à jour avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessAddPageCms() {

		$cms = new CMS();

		foreach ($_POST as $key => $value) {

			if (property_exists($cms, $key) && $key != 'id_cms') {
				$cms->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($cms));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($cms->{$field}) || !is_array($cms->{$field})) {
							$cms->{$field}

							= [];
						}

						$cms->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $cms->add();

		if ($result) {
			$return = [
				'success' => true,
				'message' => $this->l('La page CMS a été ajoutée à jour avec succès'),
			];
		} else {
			$return = [
				'success' => false,
				'message' => $this->l('L‘ajout de la catégorie de déclinaison à échouée'),
			];
		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddPageCmsCategorie() {

		$file = fopen("testAddAttributeGroup.txt", "w");
		$attribute = new CMSCategory();

		foreach ($_POST as $key => $value) {

			if (property_exists($attribute, $key) && $key != 'id_cms_category') {
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
			$li = '<li data-class="CmsContent" data-grid="grid_AdminCmsContent' . $attribute->id . '" data-category="' . $attribute->id . '" data-catname="' . $attribute->name[$this->context->language->id] . '"><a href="#grid_AdminCmsContent' . $attribute->id . '">' . $attribute->name[$this->context->language->id] . '</a></li>';
			$html = '<div id="grid_AdminCmsContent' . $attribute->id . '" class="panel col-lg-12"></div>';
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

	public function ajaxProcessupdateCmsCategorie() {

		$idCMS = Tools::getValue('id_cms_category');

		$cms = new CMSCategory($idCMS);

		foreach ($_POST as $key => $value) {

			if (property_exists($cms, $key) && $key != 'id_cms_category') {
				$cms->{$key}

				= $value;
			}

		}

		$classVars = get_class_vars(get_class($cms));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($cms->{$field}) || !is_array($cms->{$field})) {
							$cms->{$field}

							= [];
						}

						$cms->{$field}

						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		foreach (Language::getIDs(false) as $idLang) {

			if (isset($_POST['meta_keywords_' . $idLang])) {
				$_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
				$cms->keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
			}

		}

		$result = $cms->update();

		$return = [
			'success' => true,
			'message' => $this->l('La catégorie CMS a été mis à jour avec succès'),
			'action'  => 'update',
		];

		die(Tools::jsonEncode($return));
	}

	protected function _cleanMetaKeywords($keywords) {

		if (!empty($keywords) && $keywords != '') {
			$out = [];
			$words = explode(',', $keywords);

			foreach ($words as $wordItem) {
				$wordItem = trim($wordItem);

				if (!empty($wordItem) && $wordItem != '') {
					$out[] = $wordItem;
				}

			}

			return ((count($out) > 0) ? implode(',', $out) : '');
		} else {
			return '';
		}

	}

	/**
	 * Return current category
	 *
	 * @return CMSCategory
	 *
	 * @since 1.8.1.0
	 */
	public static function getCurrentCMSCategory() {

		return static::$category;
	}

	/**
	 * Ajax process update cms positions
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCmsPositions() {

		$idCms = (int) Tools::getValue('idCms');
		$idParent = Tools::getValue('idParent');
		$stopIndex = Tools::getValue('stopIndex');
		$stopIndex--;

		$object = new CMS($idCms);

		if (Validate::isLoadedObject($object)) {

			$initPosition = $object->position;

			if ($initPosition > $stopIndex) {

				$objects = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select('id_cms,  `position` ')
						->from('cms')
						->where('`id_cms_category` = ' . (int) $idParent . ' AND `position` >= ' . (int) $stopIndex . ' AND `position` <= ' . (int) $initPosition)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {
					$k = $stopIndex + 1;

					foreach ($objects as $moveObject) {

						if ($moveObject['id_cms'] == $idCms) {
							$result = Db::getInstance()->execute(
								'UPDATE `' . _DB_PREFIX_ . 'cms`
								SET `position`= ' . (int) $stopIndex . '
								WHERE `id_cms` =' . (int) $idCms);
							continue;
						}

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . 'cms`
							SET `position`= ' . (int) $k . '
							WHERE `id_cms` =' . (int) $moveObject['id_cms']);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with Position position update ' . $moveObject['id_cms']);
						} else {
							$k++;
						}

					}

				}

			} else

			if ($initPosition < $stopIndex) {

				$objects = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
					(new DbQuery())
						->select('id_cms,  `position` ')
						->from('cms')
						->where('`id_cms_category` = ' . (int) $idParent . ' AND `position` >= ' . (int) $initPosition . ' AND `position` <= ' . $stopIndex)
						->orderBy('`position` ASC')
				);

				if (!empty($objects)) {

					$k = $initPosition;

					foreach ($objects as $moveObject) {

						if ($moveObject['id_cms'] == $idCms) {

							$result = Db::getInstance()->execute(
								'UPDATE `' . _DB_PREFIX_ . 'cms`
							SET `position`= ' . (int) $stopIndex . '
							WHERE `id_cms` =' . (int) $idCms);
							continue;
						}

						$result = Db::getInstance()->execute(
							'UPDATE `' . _DB_PREFIX_ . 'cms`
							SET `position`= ' . (int) $k . '
							WHERE`id_cms` =' . (int) $moveObject['id_cms']);

						if (!$result) {
							$this->errors[] = Tools::displayError('A problem occur with Cms position update ' . $moveObject['id_cms']);
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
				'message' => $this->l('La position des cms ont été mis à jour avec succès.'),
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
	 * Ajax process update CMSCategory positions
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCmsCategoriesPositions() {

		if ($this->tabAccess['edit'] === '1') {
			$idCmsCategoryToMove = (int) Tools::getValue('id_cms_category_to_move');
			$idCmsCategoryParent = (int) Tools::getValue('id_cms_category_parent');
			$way = (int) Tools::getValue('way');
			$positions = Tools::getValue('cms_category');

			if (is_array($positions)) {

				foreach ($positions as $key => $value) {
					$pos = explode('_', $value);

					if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $idCmsCategoryParent && $pos[2] == $idCmsCategoryToMove)) {
						$position = $key;
						break;
					}

				}

			}

			$cmsCategory = new CMSCategory($idCmsCategoryToMove);

			if (Validate::isLoadedObject($cmsCategory)) {

				if (isset($position) && $cmsCategory->updatePosition($way, $position)) {
					die(true);
				} else {
					die('{"hasError" : true, "errors" : "Can not update cms categories position"}');
				}

			} else {
				die('{"hasError" : true, "errors" : "This cms category can not be loaded"}');
			}

		}

	}

	/**
	 * Ajax process publish CMS
	 *
	 * @return void
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxProcessPublishCMS() {

		if ($this->tabAccess['edit'] === '1') {

			if ($idCms = (int) Tools::getValue('id_cms')) {
				$boCmsUrl = $this->context->link->getAdminLink('AdminCmsContent', true) . '&updatecms&id_cms=' . (int) $idCms;

				if (Tools::getValue('redirect')) {
					die($boCmsUrl);
				}

				$cms = new CMS((int) (Tools::getValue('id_cms')));

				if (!Validate::isLoadedObject($cms)) {
					die('error: invalid id');
				}

				$cms->active = 1;

				if ($cms->save()) {
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
