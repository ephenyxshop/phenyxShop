<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminPFGController extends AdminController {

	private $field_controller;
	private $submission_controller;

	/**
	 * Create the necessary elements for rendering the forms
	 * using a HelperList from PhenyxShop :)
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'pfg';
		$this->className = 'PFGModel';
		$this->publicName = $this->l('Gestion de formulaire');

		parent::__construct();
		$this->context = Context::getContext();
		EmployeeConfiguration::updateValue('EXPERT_PFG_SCRIPT', $this->generateParaGridScript());
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_PFG_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_PFG_SCRIPT', $this->generateParaGridScript());
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_PFG_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_PFG_FIELDS', Tools::jsonEncode($this->getPFGModelFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PFG_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_PFG_FIELDS', Tools::jsonEncode($this->getPFGModelFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PFG_FIELDS'), true);
		}

	}

	/**
	 * Add some JS to improve user experience
	 */
	public function setMedia($isNewTheme = false) {

		parent::setMedia($isNewTheme);
		$this->addJS(__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'pfg.js');
		Media::addJsDef([
			'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
		]);
	}

	public function setAjaxMedia() {

		return $this->pushJS([
			__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
			_EPH_JS_DIR_ . 'admin/tinymce.inc.js',
		]);
	}

	public function initContent() {

		$this->displayGrid = true;
		$this->paramGridObj = 'obj' . $this->className;
		$this->paramGridVar = 'grid' . $this->className;
		$this->paramGridId = 'grid_' . $this->controller_name;
		$ajaxlink = $this->context->link->getAdminLink($this->controller_name);

		$this->TitleBar = $this->l('Liste du formulaires');

		$this->context->smarty->assign([
			'controller'     => Tools::getValue('controller'),
			'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
			'gridId'         => 'grid_' . $this->controller_name,
			'tableName'      => $this->table,
			'className'      => $this->className,
			'linkController' => $this->context->link->getAdminLink($this->controller_name),
			'AjaxLink'       => $this->context->link->getAdminLink($this->controller_name),
			'paragridScript' => $this->generateParaGridScript(),
			'titleBar'       => $this->TitleBar,
			'bo_imgdir'      => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
			'idController'   => '',
		]);

		parent::initContent();

	}

	public function generateParaGridScript() {

		$gridExtraFunction = [
			'
			function addNewFormulaire() {

			$.ajax({
				type: \'GET\',
				url: AjaxLinkAdminPFG,
				data: {
					action: \'addNewFormulaire\',
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFormulaire").html(data.html);
					$("body").addClass("edit");
					$("#paragrid_' . $this->controller_name . '").slideUp();
					$("#detailFormulaire").slideDown();
				}
				});

			}
			function editFormulaire(idFormulaire) {

			$.ajax({
				type: \'POST\',
				url: AjaxLinkAdminPFG,
				data: {
					action: \'editFormulaire\',
					idFormulaire: idFormulaire,
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFormulaire").html(data.html);
					$("body").addClass("edit");
					$("#paragrid_' . $this->controller_name . '").slideUp();
					$("#detailFormulaire").slideDown();
				}
				});

			}

			function manageFormulaire(idFormulaire) {

				$.ajax({
				type: \'GET\',
				url: AjaxLinkAdminPFG,
				data: {
					action: \'manageFormulaire\',
					idFormulaire: idFormulaire,
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFormulaire").html(data.html);
					$("body").addClass("edit");
					$("#paragrid_' . $this->controller_name . '").slideUp();
					$("#detailFormulaire").slideDown();
				}
				});
			}
			function deleteActualite(idActualite) {


				$.ajax({
					type: \'POST\',
					url: AjaxLinkAdminActualites,
					data: {
						action: \'deleteActualite\',
						idActualite: idActualite,
						ajax: true
					},
					async: false,
					dataType: \'json\',
					success: function(data) {
						if (data.success) {
							showSuccessMessage(data.message);
							gridActualite.refreshDataAndView();
						} else {
							showErrorMessage(data.message);
						}
					}
				});

			}

			function viewSubmissions(idFormulaire) {

				$.ajax({
				type: \'GET\',
				url: AjaxLinkAdminPFG,
				data: {
					action: \'viewSubmissions\',
					idFormulaire: idFormulaire,
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFormulaire").html(data.html);
					$("body").addClass("edit");
					$("#paragrid_' . $this->controller_name . '").slideUp();
					$("#detailFormulaire").slideDown();
				}
				});
			}


		',

		];

		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);
		$paragrid->paramTable = $this->table;
		$paragrid->paramController = $this->controller_name;
		$paragrid->height = "550";
		$paragrid->showNumberCell = 0;
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];
		$paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
		$paragrid->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
		$paragrid->complete = 'function(){
		//adjustActualiteGridHeight();
		grid' . $this->className . '.refreshView();
		window.dispatchEvent(new Event(\'resize\'));

        }';
		$paragrid->selectionModelType = 'row';
		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'Ajouter un nouveau formulaire\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'addNewFormulaire',
				],

			],
		];

		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Gestion des formulaires') . '\'';
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
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Modifier ') . ' \'+rowData.title,
                            icon: "edit",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								return true;
                            },
                            callback: function(itemKey, opt, e) {

								editFormulaire(rowData.id_pfg);
                            }
                        },

						"manage": {
                            name: \'' . $this->l('Configurer les champs de  ') . ' \'+rowData.title,
                            icon: "config",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								return true;
                            },
                            callback: function(itemKey, opt, e) {

								manageFormulaire(rowData.id_pfg);
                            }
                        },
						"detail": {
                            name: \'' . $this->l('Voir les  Soummissions de ') . ' \'+rowData.title,
                            icon: "view",
							visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								if(rowData.answers == 0) {
                                    return false;
                                }
								return true;
                            },
                            callback: function(itemKey, opt, e) {

								viewSubmissions(rowData.id_pfg);
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer ') . ' \ : \'+rowData.title,
                            icon: "trash",

                            callback: function(itemKey, opt, e) {
                                deleteActualite(rowData.id_actualite);
                            }
                        },

                    },
                };
            }',
			]];

		$paragrid->gridExtraFunction = $gridExtraFunction;

		$option = $paragrid->generateParaGridOption();
		$this->paragridScript = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';

	}

	public function getPFGModelRequest() {

		$formulaires = Db::getInstance()->executeS(
			(new DbQuery())
				->select('et.*, etl.*')
				->from('pfg', 'et')
				->leftJoin('pfg_lang', 'etl', 'etl.`id_pfg` = et.`id_pfg` AND etl.`id_lang` = ' . (int) $this->context->language->id)
				->orderBy('et.`id_pfg` ASC')
		);
		$shop = new Shop($this->context->shop->id);
		$url = 'https://' . $shop->domain_ssl;

		foreach ($formulaires as &$formulaire) {

			if ($formulaire['active'] == 1) {
				$formulaire['active'] = '<div class="p-active"></div>';
				$formulaire['enable'] = true;
			} else {
				$formulaire['active'] = '<div class="p-inactive"></div>';
				$formulaire['enable'] = false;
			}

			$formulaire['form_link'] = $url . '/formulaire/' . $formulaire['id_pfg'] . '-' . $formulaire['link_rewrite'];

			$formulaire['answers'] = PFGModel::getFormsbyId($formulaire['id_pfg']);

		}

		return $formulaires;

	}

	public function ajaxProcessgetPFGModelRequest() {

		die(Tools::jsonEncode($this->getPFGModelRequest()));

	}

	public function getPFGModelFields() {

		return [
			[
				'title'      => $this->l('ID'),
				'maxWidth'   => 100,
				'dataIndx'   => 'id_pfg',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'filter'     => [
					'crules' => [['condition' => "begin"]],
				],
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Titre'),
				'minWidth' => 150,
				'exWidth'  => 20,
				'dataIndx' => 'title',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',

			],
			[
				'title'    => $this->l('Lien Front Office'),
				'minWidth' => 250,
				'exWidth'  => 20,
				'dataIndx' => 'form_link',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',

			],
			[
				'title'    => $this->l('Template Front Office'),
				'minWidth' => 100,
				'exWidth'  => 20,
				'dataIndx' => 'template',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',

			],

			[
				'title'      => $this->l('Active'),
				'width'      => 50,
				'dataIndx'   => 'enable',
				'dataType'   => 'bool',
				'editable'   => false,
				'align'      => 'left',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'    => $this->l('Enabled'),
				'width'    => 100,
				'editable' => false,
				'dataIndx' => 'active',
				'align'    => 'center',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'html',
			],
			[
				'title'      => $this->l('Réponses'),
				'minWidth'   => 100,
				'dataIndx'   => 'answers',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'hiddenable' => 'no',
			],

		];

	}

	public function ajaxProcessgetPFGModelFields() {

		die(EmployeeConfiguration::get('EXPERT_PFG_FIELDS'));
	}

	public function ajaxProcessAddNewFormulaire() {

		$_GET['addpfg'] = "";

		$html = $this->renderForm();
		$result = [
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcesseditFormulaire() {

		$idFormulaire = Tools::getValue('idFormulaire');

		$this->identifier = 'id_pfg';
		$_GET['id_pfg'] = $idFormulaire;
		$_GET['updatepfg'] = "";

		$html = $this->renderForm();
		$result = [
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddPFG() {

		$pgfield = new PFGModel();

		foreach ($_POST as $key => $value) {

			if (property_exists($pgfield, $key) && $key != 'id_pfg') {
				$pgfield->{$key}
				= $value;
			}

		}

		$classVars = get_class_vars(get_class($pgfield));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($pgfield->{$field}) || !is_array($pgfield->{$field})) {
							$pgfield->{$field}
							= [];
						}

						$pgfield->{$field}
						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $pgfield->add();

		$return = [
			'success' => true,
			'message' => $this->l('Le Formulaire a été ajouté avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessUpdatePFG() {

		$id_pfg = Tools::getValue('id_pfg');

		$pgfield = new PFGModel($id_pfg);

		foreach ($_POST as $key => $value) {

			if (property_exists($pgfield, $key) && $key != 'id_pfg') {
				$pgfield->{$key}
				= $value;
			}

		}

		$classVars = get_class_vars(get_class($pgfield));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($pgfield->{$field}) || !is_array($pgfield->{$field})) {
							$pgfield->{$field}
							= [];
						}

						$pgfield->{$field}
						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $pgfield->update();

		$return = [
			'success' => true,
			'message' => $this->l('Le formulaire a été mis à jour avec succès'),
		];

		die(Tools::jsonEncode($return));

	}

	public function renderForm() {

		if (!$this->loadObject(true)) {
			return;
		}

		if (Validate::isLoadedObject($this->object)) {
			$this->display = 'edit';
		} else {
			$this->display = 'add';
		}

		$context = Context::getContext();
		$context->controller->addJS([
			__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
			_EPH_JS_DIR_ . 'tinymce.inc.js',
		]);

		$actions = [
			['value' => 'form', 'name' => $this->l('Send the form')],
			['value' => 'message', 'name' => $this->l('Send a specific message')],
			['value' => null, 'name' => $this->l('Do nothing')],
		];

		$this->fields_form = [
			'tinymce' => false,
			'legend'  => [
				'title' => $this->l('Powerful Form Generator'),
			],
			'input'   => [

				[
					'type' => 'hidden',
					'name' => 'ajax',
				],
				[
					'type' => 'hidden',
					'name' => 'action',
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Nom du formulaire :'),
					'name'     => 'title',
					'lang'     => true,
					'required' => true,
					'class'    => 'fixed-width-xl',
					'size'     => 50,
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Friendly URL'),
					'name'     => 'link_rewrite',
					'required' => true,
					'lang'     => true,
					'hint'     => $this->l('Only letters and the hyphen (-) character are allowed.'),
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Template Front Office'),
					'name'     => 'template',
					'required' => true,
					'hint'     => $this->l('Fichier utilisé dans le repertoir du thème.'),
				],
				[
					'type'    => 'select',
					'label'   => $this->l('Type of email for the sender'),
					'name'    => 'action_sender',
					'options' => [
						'query' => $actions,
						'id'    => 'value',
						'name'  => 'name',
					],
					'desc'    => $this->l('What kind of email to send to the sender after the form has been successfully submitted.'),
					'class'   => 'fixed-width-xl',
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Send form to :'),
					'name'     => 'send_mail_to',
					'required' => true,
					'class'    => 'fixed-width-xl',
					'desc'     => $this->l('List of the admins emails, separated by comma (",").<br />Ex: email@example.com,second@example.com'),
					'size'     => 50,
				],
				[
					'type'    => 'select',
					'label'   => $this->l('Type of email for the admin(s)'),
					'name'    => 'action_admin',
					'options' => [
						'query' => $actions,
						'id'    => 'value',
						'name'  => 'name',
					],
					'desc'    => $this->l('What kind of email to send to the admin(s) after the form has been successfully submitted.'),
					'class'   => 'fixed-width-xl',
				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Active:'),
					'name'     => 'active',
					'required' => false,
					'is_bool'  => true,
					'class'    => 't',
					'values'   => [
						[
							'id'    => 'active_on',
							'value' => 1,
							'label' => $this->l('Yes'),
						],
						[
							'id'    => 'active_off',
							'value' => 0,
							'label' => $this->l('Désactivé'),
						],
					],
				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Restreint à un seul envoi'),
					'name'     => 'one_submission_only',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'one_submission_only_on',
							'value' => 1,
							'label' => $this->l('Yes'),
						],
						[
							'id'    => 'one_submission_only_off',
							'value' => 0,
							'label' => $this->l('No'),
						],
					],
				],
				[
					'type'     => 'select',
					'label'    => $this->l('Allow only to :'),
					'name'     => 'is_only_connected',
					'required' => false,
					'options'  => [
						'query' => [
							['value' => '0', 'name' => $this->l('Everybody')],
							['value' => '1', 'name' => $this->l('Connected user only.')],
						],
						'id'    => 'value',
						'name'  => 'name',
					],
					'class'    => 'fixed-width-xl',
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Redirect URL :'),
					'name'     => 'unauth_redirect_url',
					'lang'     => true,
					'required' => false,
					'desc'     => $this->l('Redirect URL for non authenticated users. Leave empty to show a 404 page instead.'),
					'class'    => 'fixed-width-xxl redirect_url',
					'size'     => 98,
				],
				[
					'type'     => 'select',
					'label'    => $this->l('Accessible via :'),
					'name'     => 'accessible',
					'required' => false,
					'options'  => [
						'query' => [
							['value' => '1', 'name' => $this->l('Via URL only.')],
							['value' => '2', 'name' => $this->l('Via HOOK only.')],
							['value' => '0', 'name' => $this->l('Both URL and HOOK.')],
						],
						'id'    => 'value',
						'name'  => 'name',
					],
					'class'    => 'fixed-width-xl',
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Default subject (sender) :'),
					'name'     => 'subject_sender',
					'lang'     => true,
					'required' => false,
					'desc'     => html_entity_decode($this->l('Default subject for the email sent to the sender if no subject field is defined.<br /><strong>You can use variables from the fields you configured in this form.</strong> Like this : <code>&lcub;$firstname&rcub;</code> for a field with the name "firstname".')),
					'class'    => 'fixed-width-xxl',
					'size'     => 98,
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Default subject (admin) :'),
					'name'     => 'subject_admin',
					'lang'     => true,
					'required' => false,
					'desc'     => html_entity_decode($this->l('Default subject for the email sent to the admin if no subject field is defined.<br /><strong>You can use variables from the fields you configured in this form.</strong> Like this : <code>&lcub;$firstname&rcub;</code> for a field with the name "firstname".')),
					'class'    => 'fixed-width-xxl',
					'size'     => 98,
				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Header message :'),
					'name'         => 'header',
					'autoload_rte' => true,
					'lang'         => true,
					'required'     => false,
					'rows'         => '5',
					'cols'         => '48',
					'desc'         => $this->l('Message to display before the form.'),
				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Footer message :'),
					'name'         => 'footer',
					'autoload_rte' => true,
					'lang'         => true,
					'required'     => false,
					'rows'         => '5',
					'cols'         => '48',
					'desc'         => $this->l('Message to display after the form.'),
				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Success message :'),
					'name'         => 'success',
					'autoload_rte' => true,
					'lang'         => true,
					'required'     => false,
					'rows'         => '5',
					'cols'         => '48',
					'desc'         => html_entity_decode($this->l('Message to display after the form has been submitted. If your message starts with http, the user will be redirected to that address.<br /><strong>You can use variables from the fields you configured in this form.</strong> Like this : <code>&lcub;$firstname&rcub;</code> for a field with the name "firstname".')),
				],
				[
					'type'     => 'text',
					'label'    => $this->l('Send button value :'),
					'name'     => 'send_label',
					'lang'     => true,
					'required' => false,
					'desc'     => $this->l('The value to show in the "send" button.'),
					'class'    => 'fixed-width-xxl',
					'size'     => 98,
				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Message to send to the sender :'),
					'name'         => 'message_sender',
					'autoload_rte' => true,
					'lang'         => true,
					'required'     => false,
					'rows'         => '5',
					'cols'         => '48',
					'desc'         => html_entity_decode($this->l('Message to send to the sender.<br /><strong>You can use variables from the fields you configured in this form.</strong> Like this : <code>&lcub;$firstname&rcub;</code> for a field with the name "firstname".')),
					'class'        => 'message_senders',
				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Message to send to the admins :'),
					'name'         => 'message_admin',
					'autoload_rte' => true,
					'lang'         => true,
					'required'     => false,
					'rows'         => '5',
					'cols'         => '48',
					'desc'         => html_entity_decode($this->l('Message to send to the admins.<br /><strong>You can use variables from the fields you configured in this form.</strong> Like this : <code>&lcub;$firstname&rcub;</code> for a field with the name "firstname".')),
					'class'        => 'message_admins',
				],
			],
			'submit'  => [
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right',
			],
		];

		$this->fields_value['active'] = true;
		$this->fields_value['ajax'] = 1;

		if ($this->object->id > 0) {
			$this->fields_value['action'] = 'updatePFG';
		} else {
			$this->fields_value['action'] = 'addPFG';
			$this->fields_value['template'] = 'formulaire.tpl';
		}

		$this->tpl_form_vars = [
			'active' => $this->object->active,
			'EPH_ALLOW_ACCENTED_CHARS_URL', (int) Configuration::get('EPH_ALLOW_ACCENTED_CHARS_URL'),
		];

		$languages = Language::getLanguages(true);

		if (count($languages) > 1) {
			$this->warnings[] = $this->l('You use more than one language on your shop. Don\t forget to mention a value for each language before submitting this form.');
		}

		return parent::renderForm();
	}

	public function ajaxProcessManageFormulaire() {

		$idFormulaire = Tools::getValue('idFormulaire');

		$controller = new AdminPFGFieldsController();
		$controller->id_pfg = $idFormulaire;

		$data = $this->createTemplate('formulaire_fields.tpl');

		$data->assign([
			'paragridScript' => $this->buildPFGFieldsScript($idFormulaire),
			'controller'     => $controller->controller_name,
			'tableName'      => $controller->table,
			'className'      => $controller->className,
			'link'           => $this->context->link,
			'idFormulaire'   => $idFormulaire,
		]);

		$result = [
			'html' => $data->fetch(),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessViewSubmissions() {

		$idFormulaire = Tools::getValue('idFormulaire');

		$data = $this->createTemplate('formulaire_submissions.tpl');

		$data->assign([
			'paragridScript' => $this->buildPFGSubmissionScript($idFormulaire),
			'controller'     => "AdminPFGSubmissions",
			'tableName'      => 'pfg_submissions',
			'className'      => 'PFGSubmissionModel',
			'link'           => $this->context->link,
			'idFormulaire'   => $idFormulaire,
		]);

		$result = [
			'html' => $data->fetch(),
		];

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessGetPFGSubmissionModelFields() {

		$idFormulaire = Tools::getValue('idFormulaire');
		$pfg_model = new PFGModel($idFormulaire);
		$fields = PFGFieldModel::findFields($pfg_model->id);
		$colModel = $this->setFieldsList($fields);

		die(Tools::jsonEncode($colModel));
	}

	public function ajaxProcessGetPFGFieldModelSubmissions() {

		$id_pfg = Tools::getValue('id_pfg');
		$pfg_model = new PFGModel($id_pfg);
		$fields = PFGFieldModel::findFields($pfg_model->id);
		$select = $this->setSelectColumns($fields);

		$entries = Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('pfg_submissions')
				->where('`id_pfg` =' . $id_pfg)
		);

		foreach ($entries as &$entrie) {
			$entry = Tools::jsonDecode($entrie['entry'], true);

			foreach ($entry as $key => $field) {

				if (in_array($key, $select)) {
					$entrie[$key] = $field;
				}

			}

		}

		die(Tools::jsonEncode($entries));
	}

	public function buildPFGSubmissionScript($idFormulaire) {

		$className = 'PFGSubmissionModel';
		$table = 'pfg_submissions';
		$controller_name = "AdminPFGSubmissions";
		$identifier = 'id_submission';

		$gridExtraFunction = [
			'
			function backFormList() {
				$("#paragrid_AdminPFG").slideDown();
				$("#detailFormulaire").slideUp();
				$("#detailFormulaire").html("");

			}

			function exportEntries() {
				$.ajax({
				type: \'GET\',
				url: AjaxLinkAdminPFG,
				data: {
					action: \'exportEntries\',
					id_pfg: ' . $idFormulaire . ',
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					 window.location.href = data.fileExport;
				}
				});

			}
		',

		];

		$paragrid = new ParamGrid($className, $controller_name, $table, $identifier);
		$paragrid->paramTable = $table;
		$paragrid->paramController = $controller_name;
		$paragrid->requestModel = '{
            location: "remote",
            dataType: "json",
            method: "GET",
            recIndx: "id_pfg_fields",
            url: AjaxLinkAdminPFG+"&action=getPFGFieldModelSubmissions&id_pfg=' . $idFormulaire . '&ajax=1",
            getData: function (dataJSON) {
                return { data: dataJSON };
            }


        }';

		$paragrid->height = '700';

		//$paragrid->ajaxUrl = 'AjaxLinkAdminEducations + "&action=getProductRequest&ajax=1&idCategory="+getURLParameter(\'idCategory\')';
		$paragrid->showNumberCell = 0;
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];

		$paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $controller_name . '+\'" data-class="' . $className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $identifier . '+\' "\',
            };
        }';

		$paragrid->complete = 'function(){
		//adjustActualiteGridHeight();
		grid' . $this->className . '.refreshView();
		window.dispatchEvent(new Event(\'resize\'));

        }';
		$paragrid->selectionModelType = 'row';
		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'Exporter les entrées du formulaire\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'exportEntries',
				],

				[
					'type'     => '\'button\'',
					'label'    => '\'Retour à la liste des formulaires\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'backFormList',
				],

			],
		];

		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Liste des entrées de formulaire') . '\'';
		$paragrid->fillHandle = '\'all\'';

		$paragrid->gridFunction = [
			'getPFGSubmissionModelFields()'  => '
        	var result ;
            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminPFG,
                data: {
                    action: \'getPFGSubmissionModelFields\',
					idFormulaire: ' . $idFormulaire . ',
                    ajax: true
                },
                async: false,
                dataType: \'json\',
                success: function success(data) {
                    result = data;
                }
            });
            return result;',

			'getPFGSubmissionModelRequest()' => '
            var result;
            $.ajax({
            type: \'POST\',
            url: AjaxLinkAdminPFG,
            data: {
                action: \'getPFGFieldModelSubmissions\',
				idFormulaire: ' . $idFormulaire . ',
                ajax: true
            },
            async: false,
            dataType: \'json\',
            success: function (data) {
                result = data;
            }
        });
        return result;',

		];

		$paragrid->gridExtraFunction = $gridExtraFunction;

		$option = $paragrid->generateParaGridOption();
		$this->paragridScript = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function setSelectColumns($fields) {

		if (count($fields) === 0) {
			return;
		}

		$select = [];

		foreach ($fields as $field) {
			$select[] = $field['name'];
		}

		return $select;
	}

	public function setFieldsList($fields) {

		$fieldList[] = [
			'title'    => $this->l('ID'),
			'maxWidth' => 100,
			'dataIndx' => 'id_submission',
			'dataType' => 'integer',
			'editable' => false,
			'align'    => 'center',
		];

		foreach ($fields as $i => $field) {

			$fieldList[] = [
				'title'    => $field['label'],
				'dataIndx' => $field['name'],
				'dataType' => 'string',
				'editable' => false,
				'align'    => 'left',
			];
		}

		$fieldList[] = [
			'title'    => $this->l('Date de démarrage'),
			'maxWidth' => 200,
			'dataIndx' => 'date_add',
			'align'    => 'center',
			'valign'   => 'center',
			'dataType' => 'date',
			'format'   => 'dd/mm/yy',
			'editable' => false,
		];

		return $fieldList;
	}

	public function buildPFGFieldsScript($idFormulaire) {

		$className = 'PFGFieldModel';
		$table = 'pfg_fields';
		$controller_name = "AdminPFGFields";
		$identifier = 'id_pfg_fields';

		$gridExtraFunction = [
			'
			function backFormList() {
				$("#paragrid_AdminPFG").slideDown();
				$("#detailFormulaire").slideUp();
				$("#detailFormulaire").html("");

			}

			function addNewField() {

			$.ajax({
				type: \'GET\',
				url: AjaxLinkAdminPFGFields,
				data: {
					action: \'addNewField\',
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFieldForm").html(data.html);
					$("body").addClass("edit");
					$("#paragrid_' . $controller_name . '").slideUp();
					$("#detailFieldForm").slideDown();
				}
				});

			}
			function editField(idField) {

			$.ajax({
				type: \'POST\',
				url: AjaxLinkAdminPFGFields,
				data: {
					action: \'editField\',
					idField: idField,
					ajax: true
				},
				async: false,
				dataType: \'json\',
				success: function(data) {
					$("#detailFieldForm").html(data.html);
					$("#paragrid_' . $controller_name . '").slideUp();
					$("body").addClass("edit");
					$("#detailFieldForm").slideDown();
				}
				});

			}
			function deleteActualite(idActualite) {


				$.ajax({
					type: \'POST\',
					url: AjaxLinkAdminPFGFields,
					data: {
						action: \'deleteActualite\',
						idActualite: idActualite,
						ajax: true
					},
					async: false,
					dataType: \'json\',
					success: function(data) {
						if (data.success) {
							showSuccessMessage(data.message);
							gridActualite.refreshDataAndView();
						} else {
							showErrorMessage(data.message);
						}
					}
				});

			}


		',

		];

		$paragrid = new ParamGrid($className, $controller_name, $table, $identifier);
		$paragrid->paramTable = $table;
		$paragrid->paramController = $controller_name;
		$paragrid->requestModel = '{
            location: "remote",
            dataType: "json",
            method: "GET",
            recIndx: "id_pfg_fields",
            url: AjaxLinkAdminPFGFields+"&action=getPFGFieldModelRequest&id_pfg=' . $idFormulaire . '&ajax=1",
            getData: function (dataJSON) {
                return { data: dataJSON };
                }


        }';
		$paragrid->height = '700';

		//$paragrid->ajaxUrl = 'AjaxLinkAdminEducations + "&action=getProductRequest&ajax=1&idCategory="+getURLParameter(\'idCategory\')';
		$paragrid->showNumberCell = 0;
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];

		$paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $controller_name . '+\'" data-class="' . $className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $identifier . '+\' "\',
            };
        }';

		$paragrid->complete = 'function(){
		//adjustActualiteGridHeight();
		grid' . $this->className . '.refreshView();
		window.dispatchEvent(new Event(\'resize\'));

        }';
		$paragrid->selectionModelType = 'row';
		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'Ajouter un nouveau champ de formulaire\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'addNewField',
				],
				[
					'type'     => '\'button\'',
					'label'    => '\'Retour à la liste des formulaires\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'backFormList',
				],

			],
		];
		$paragrid->dragModel = [
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
		$paragrid->dropModel = [
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
		$paragrid->moveNode = 'function(event, ui) {
			 		console.log(ui);
                var startIndex = ui.args[0][0].fieldPosition;
				var idField = ui.args[0][0].id_pfg_field;
				var idForm = ui.args[0][0].id_pfg;
                var stopIndex = parseInt(ui.args[1]);
                var way = (startIndex < stopIndex) ? 1 : 0;
				processFieldFormPosition(idField, idForm, way, startIndex, stopIndex)

                }';

		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Gestion des formulaires') . '\'';
		$paragrid->fillHandle = '\'all\'';
		$paragrid->contextMenu = [
			'#grid_' . $controller_name => [
				'selector'  => '\'.pq-body-outer .pq-grid-row\'',
				'animation' => [
					'duration' => 250,
					'show'     => '\'fadeIn\'',
					'hide'     => '\'fadeOut\'',
				],
				'build'     => 'function($triggerElement, e){
                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $className . '.getRowData( {rowIndx: rowIndex} );
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Modifier le champ ') . ' \'+rowData.name,
                            icon: "edit",
							visible: function(key, opt){
                               var selected = selgrid' . $className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
								return true;
                            },
                            callback: function(itemKey, opt, e) {
								editField(rowData.id_pfg_field);
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer le champ ') . ' \ : \'+rowData.name,
                            icon: "trash",

                            callback: function(itemKey, opt, e) {
                                deleteField(rowData.id_pfg_field);
                            }
                        },

                    },
                };
            }',
			]];

		$paragrid->gridExtraFunction = $gridExtraFunction;

		$option = $paragrid->generateParaGridOption();
		$this->paragridScript = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function ajaxProcessExportEntries() {

		$id_pfg = Tools::getValue('id_pfg');

		$pfg_model = new PFGModel($id_pfg);
		$fields = PFGFieldModel::findFields($pfg_model->id, $this->context->language->id);
		$select = $this->setSelectColumns($fields);
		$titles = [];

		foreach ($fields as $field) {
			$titles[] = $field['label'];
		}

		$column = chr(64 + count($titles));

		$entries = Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('pfg_submissions')
				->where('`id_pfg` =' . $id_pfg)
		);

		foreach ($entries as &$entrie) {
			$entry = Tools::jsonDecode($entrie['entry'], true);

			foreach ($entry as $key => $field) {

				if (in_array($key, $select)) {
					$entrie[$key] = $field;
				}

			}

		}

		$titleStyle = [
			'font'    => [
				'bold' => true,
			],
			'borders' => [
				'bottom' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
				],
			],
		];
		$corpStyle = [
			'font' => [
				'bold' => false,
			],

		];

		$spreadsheet = new Spreadsheet();

		$sessions = StudentEducation::getFilledSession();
		$spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);

		foreach ($titles as $key => $value) {
			$key++;
			$letter = chr(64 + $key);

			$spreadsheet->setActiveSheetIndex(0)
				->setCellValue($letter . '1', $value);

		}

		$spreadsheet->getActiveSheet()->getStyle('A1:' . $column . '1')->getAlignment()->setVertical(Alignment::HORIZONTAL_CENTER);
		$spreadsheet->getActiveSheet()->getStyle('A1:' . $column . '1')->applyFromArray($titleStyle);
		$spreadsheet->getActiveSheet()->getStyle('A1:' . $column . '1')->getFont()->setSize(12);
		$i = 2;

		foreach ($entries as $entrie) {

			foreach ($select as $k => $title) {

				if (array_key_exists($title, $entrie)) {
					$k++;
					$letter = chr(64 + $k);

					$spreadsheet->setActiveSheetIndex(0)
						->setCellValue($letter . $i, $entrie[$title]);

					$spreadsheet->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true);
					$spreadsheet->getActiveSheet()->getStyle($letter . $i)->applyFromArray($corpStyle);

				}

			}

			$i++;
		}

		$fileSave = new Xlsx($spreadsheet);
		$fileSave->save(_EPH_EXPORT_DIR_ . 'formulaire_' . $pfg_model->title[$this->context->language->id] . '.xlsx');
		$response = [
			'fileExport' => 'fileExport' . DIRECTORY_SEPARATOR . 'formulaire_' . $pfg_model->title[$this->context->language->id] . '.xlsx',
		];
		die(Tools::jsonEncode($response));
	}

	/**
	 * Process to the validation of the submitted HelperForm
	 */
	protected function _childValidation() {

		$languages = Language::getLanguages(true);

		// if action == 'message', related message required

		if (Tools::getValue('action_sender') === 'message') {

			foreach ($languages as $language) {
				$value = Tools::getValue('message_sender_' . $language['id_lang']);

				if (empty($value)) {
					$this->errors[] = $this->l('Please indicate a message to send to the sender.');
				}

			}

		}

		// if action == 'message', related message required

		if (Tools::getValue('action_admin') === 'message') {

			foreach ($languages as $language) {
				$value = Tools::getValue('message_admin_' . $language['id_lang']);

				if (empty($value)) {
					$this->errors[] = $this->l('Please indicate the message to send to the admin(s).');
				}

			}

		}

		// check every admin email,
		$send_mail_to = Tools::getValue('send_mail_to');

		if (empty($send_mail_to)) {
			$this->errors[] = $this->l('"Send form to" field is required.');
		} else {
			$emails = explode(',', Tools::getValue('send_mail_to'));

			foreach ($emails as $email) {
				$email = trim($email);

				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$this->errors[] = $this->l('Invalid email provided in "Send form to". (Please separate emails with a comma)');
				}

			}

		}

		$fields = [];

		foreach (PFGFieldModel::findFields(Tools::getValue('id_pfg')) as $field) {
			$fields[] = $field['name'];
		}

		foreach (['subject_sender', 'subject_admin', 'success', 'message_sender', 'message_admin'] as $variable_name) {

			foreach ($languages as $language) {
				$matches = [];

				preg_match_all('/(\{\$([a-z0-9_]+)(\[\])?\})/', Tools::getValue($variable_name . '_' . $language['id_lang']), $matches, PREG_SET_ORDER);

				if (count($matches) > 0) {
					$matches = $this->pregMatchReorder($matches);

					foreach ($matches as $match) {

						if (!in_array($match, $fields)) {
							$this->errors[] = sprintf($this->l('Invalid variable "%s". This name does not exists. (You need to create the field first)'), $match);
							return;
						}

					}

				}

			}

		}

	}

	private function pregMatchReorder($matches) {

		$result = [];

		foreach ($matches as $match) {
			$result[] = $match[2];
		}

		return $result;
	}

}
