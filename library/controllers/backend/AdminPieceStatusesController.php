<?php

use XhtmlFormatter\Formatter;

/**
 * Class AdminPieceStatusesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminPieceStatusesControllerCore extends AdminController {

	/**
	 * AdminPieceStatusesControllerCore constructor.
	 *
	 * @since 1.9.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'customer_piece_state';
		$this->className = 'CustomerPieceState';
		$this->lang = true;
		$this->publicName = $this->l('Gestion des Statuts des Commandes');
		$this->identifier = 'id_customer_piece_state';
		$this->controller_name = 'AdminPieceStatuses';

		$this->context = Context::getContext();

		EmployeeConfiguration::updateValue('EXPERT_ORDER_STATUS_FIELDS', Tools::jsonEncode($this->getCustomerPieceStateFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ORDER_STATUS_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_ORDER_STATUS_FIELDS', Tools::jsonEncode($this->getCustomerPieceStateFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_ORDER_STATUS_FIELDS'), true);
		}

		EmployeeConfiguration::updateValue('EXPERT_ORDER_STATUS_SCRIPT', $this->generateParaGridScript(true));
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_ORDER_STATUS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_ORDER_STATUS_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_ORDER_STATUS_SCRIPT');
		}

		parent::__construct();
	}

	public function setMedia($isNewTheme = false) {

		parent::setMedia($isNewTheme);

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
			_EPH_JS_DIR_ . 'tinymce.inc.js',
		]);
	}

	public function initContent() {

		$this->displayGrid = true;
		$this->paramGridObj = 'obj' . $this->className;
		$this->paramGridVar = 'grid' . $this->className;
		$this->paramGridId = 'grid_' . $this->controller_name;

		$this->TitleBar = $this->l('Liste des Commandes Clients');

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
			'bo_imgdir'      => _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
			'idController'   => '',
		]);

		parent::initContent();
	}

	public function generateParaGridScript($regenerate = false) {

		$this->paramPageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];

		$this->paramComplete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';

		$this->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.id_order+\'"\',
            };
        }';

		$this->paramTitle = '\'' . $this->l('Gestion des Status des commandes') . '\'';
		$this->paramToolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'' . $this->l('Ajouter une Status') . '\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
				],

			],
		];

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
                            name: \'' . $this->l('Ajouter un Status de Commande') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                 addAjaxObject("' . $this->controller_name . '");
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Editer ce Status de commande ') . '\',
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                             	editOrderStatus(rowData.id_customer_piece_state);
                            }
                        },
						"addLicense": {
                            name : \'' . $this->l('Supprimer ce status de commande ') . '\',
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                             	deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un Statut", "Etes vous sure de vouloir supprimer  ce Statut ?", "Oui", "Annuler",rowData.id_customer_piece_state);
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

	public function getCustomerPieceStateRequest() {

		$orders = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('a.*, b.name, b.template')
				->from('customer_piece_state', 'a')
				->leftJoin('customer_piece_state_lang', 'b', 'b.`id_customer_piece_state` = a.`id_customer_piece_state` AND b.`id_lang` = ' . $this->context->language->id)
		);

		foreach ($orders as &$order) {

			$order['name'] = '<span class="label color_field" style="background-color:' . $order['color'] . '">' . $order['name'] . '</span>';

			if ($order['send_email']) {
				$order['send_email'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['send_email'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['logable']) {
				$order['logable'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['logable'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['delivery']) {
				$order['delivery'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['delivery'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['shipped']) {
				$order['shipped'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['shipped'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['paid']) {
				$order['paid'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['paid'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['invoice']) {
				$order['invoice'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['invoice'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['pdf_invoice']) {
				$order['pdf_invoice'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['pdf_invoice'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

			if ($order['pdf_delivery']) {
				$order['pdf_delivery'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
			} else {
				$order['pdf_delivery'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
			}

		}

		return $orders;

	}

	public function ajaxProcessgetCustomerPieceStateRequest() {

		die(Tools::jsonEncode($this->getCustomerPieceStateRequest()));

	}

	public function getCustomerPieceStateFields() {

		return [
			[
				'title'      => $this->l('ID'),
				'dataIndx'   => 'id_customer_piece_state',
				'dataType'   => 'integer',
				'editable'   => false,
				'hiddenable' => 'no',
				'align'      => 'center',
				'filter'     => [
					'crules' => [['condition' => "begin"]],
				],
			],

			[
				'title'    => $this->l('Nom'),
				'width'    => 100,
				'exWidth'  => 15,
				'dataIndx' => 'name',
				'align'    => 'left',
				'dataType' => 'html',
				'editable' => true,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],

			],

			[
				'title'    => $this->l('Envoyer un email au client'),
				'width'    => 150,
				'dataIndx' => 'send_email',
				'align'    => 'center',
				'dataType' => 'html',
				'editable' => false,
				'hidden'   => false,

			],
			[
				'title'    => $this->l('Livraison'),
				'width'    => 150,
				'dataIndx' => 'delivery',
				'dataType' => 'html',
				'align'    => 'center',
				'editable' => false,
				'hidden'   => false,

			],
			[
				'title'    => $this->l('Facture'),
				'width'    => 150,
				'dataIndx' => 'invoice',
				'dataType' => 'html',
				'align'    => 'center',
				'editable' => false,
				'hidden'   => false,

			],
			[
				'title'    => $this->l('Considérer la Facture Validée'),
				'width'    => 150,
				'dataIndx' => 'logable',
				'dataType' => 'html',
				'align'    => 'center',
				'editable' => false,
				'hidden'   => false,

			],
			[
				'title'    => $this->l('Template de l‘email'),
				'width'    => 150,
				'dataIndx' => 'template',
				'dataType' => 'string',
				'editable' => false,
				'hidden'   => false,

			],

		];

	}

	public function ajaxProcessGetCustomerPieceStateFields() {

		die(Tools::jsonEncode($this->getCustomerPieceStateFields()));
	}

	public function ajaxProcessAddNewOrderStatus() {

		$_GET['addcustomer_piece_state'] = "";

		$html = $this->renderForm();
		$result = [
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcesseditOrderStatus() {

		$idOrderStatus = Tools::getValue('idState');
		$_GET['id_customer_piece_state'] = $idOrderStatus;
		$_GET['updatecustomer_piece_state'] = "";

		$html = $this->renderForm();
		$result = [
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function renderForm() {

		if (!$this->loadObject(true)) {
			return '';
		}

		$this->fields_form = [
			'tinymce' => true,
			'legend'  => [
				'title' => $this->l('Order status'),
				'icon'  => 'icon-time',
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
					'label'    => $this->l('Status name'),
					'name'     => 'name',
					'lang'     => true,
					'required' => true,
					'hint'     => [
						$this->l('Order status (e.g. \'Pending\').'),
						$this->l('Invalid characters: numbers and') . ' !<>,;?=+()@#"{}_$%:',
					],
				],
				[
					'type'  => 'file',
					'label' => $this->l('Icon'),
					'name'  => 'icon',
					'hint'  => $this->l('Upload an icon from your computer (File type: .gif, suggested size: 16x16).'),
				],
				[
					'type'  => 'color',
					'label' => $this->l('Color'),
					'name'  => 'color',
					'hint'  => $this->l('Status will be highlighted in this color. HTML colors only.') . ' "lightblue", "#CC6600")',
				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Consider the associated order as validated.'),
					'name'     => 'logable',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'logable_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'logable_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Allow a customer to download and view PDF versions of his/her invoices.'),
					'name'     => 'invoice',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'invoice_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'invoice_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Hide this status in all customer orders.'),
					'name'     => 'hidden',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'hidden_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'hidden_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Send an email to the customer when his/her order status has changed.'),
					'name'     => 'send_email',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'send_email_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'send_email_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Attach invoice PDF to email.'),
					'name'     => 'pdf_invoice',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'pdf_invoice_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'pdf_invoice_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Attach delivery slip PDF to email.'),
					'name'     => 'pdf_delivery',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'pdf_delivery_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'pdf_delivery_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Set the order as shipped.'),
					'name'     => 'shipped',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'shipped_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'shipped_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Set the order as paid.'),
					'name'     => 'paid',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'paid_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'paid_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],
				[
					'type'     => 'switch',
					'label'    => $this->l('Show delivery PDF.'),
					'name'     => 'delivery',
					'required' => false,
					'is_bool'  => true,
					'values'   => [
						[
							'id'    => 'delivery_on',
							'value' => 1,
							'label' => $this->l('Enabled'),
						],
						[
							'id'    => 'delivery_off',
							'value' => 0,
							'label' => $this->l('Disabled'),
						],
					],

				],

				[
					'type'    => 'select_template',
					'label'   => $this->l('Template'),
					'name'    => 'template',
					'lang'    => true,
					'options' => [
						'query'  => $this->getTemplates(),
						'id'     => 'id',
						'name'   => 'name',
						'folder' => 'folder',
					],

				],
				[
					'type'         => 'textarea',
					'label'        => $this->l('Visualisation du Template'),
					'name'         => 'viewTemplate',
					'autoload_rte' => true,
					'lang'         => true,
					'hint'         => $this->l('Invalid characters:') . ' <>;=#{}',
				],

			],
			'submit'  => [
				'title' => $this->l('Save'),
			],
		];

		$this->fields_value['ajax'] = 1;

		if ($this->object->id > 0) {
			$this->fields_value['action'] = 'updateCustomerPieceState';

			if ($this->object->send_email) {

				foreach (Language::getIDs(false) as $idLang) {

					if (file_exists(_EPH_ROOT_DIR_ . '/mails/fr/' . $this->object->template[$idLang] . '.tpl')) {
						$this->fields_value['viewTemplate'][$idLang] = file_get_contents(_EPH_ROOT_DIR_ . '/mails/fr/' . $this->object->template[$idLang] . '.tpl');
					}

				}

			}

		} else {
			$this->fields_value['action'] = 'addCustomerPieceState';
		}

		return parent::renderForm();
	}

	public function ajaxProcessAddCustomerPieceState() {

		$CustomerPieceState = new CustomerPieceState();

		foreach ($_POST as $key => $value) {

			if (property_exists($CustomerPieceState, $key) && $key != 'id_customer_piece_state') {
				$CustomerPieceState->{$key}
				= $value;
			}

		}

		$classVars = get_class_vars(get_class($CustomerPieceState));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($CustomerPieceState->{$field}) || !is_array($CustomerPieceState->{$field})) {
							$CustomerPieceState->{$field}
							= [];
						}

						$CustomerPieceState->{$field}
						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		$result = $CustomerPieceState->add();

		$return = [
			'success' => true,
			'message' => $this->l('Le status de commande a été ajouté avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	public function ajaxProcessUpdateCustomerPieceState() {

		$idCustomerPieceState = Tools::getValue('id_customer_piece_state');

		$CustomerPieceState = new CustomerPieceState($idCustomerPieceState);

		foreach ($_POST as $key => $value) {

			if (property_exists($CustomerPieceState, $key) && $key != 'id_customer_piece_state') {
				$CustomerPieceState->{$key}
				= $value;
			}

		}

		$classVars = get_class_vars(get_class($CustomerPieceState));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				foreach (Language::getIDs(false) as $idLang) {

					if (Tools::isSubmit($field . '_' . (int) $idLang)) {

						if (!isset($CustomerPieceState->{$field}) || !is_array($CustomerPieceState->{$field})) {
							$CustomerPieceState->{$field}
							= [];
						}

						$CustomerPieceState->{$field}
						[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
					}

				}

			}

		}

		if (!$CustomerPieceState->send_email) {

			foreach (Language::getIDs(false) as $idLang) {
				$CustomerPieceState->template[$idLang] = '';
			}

		}

		$result = $CustomerPieceState->update();

		if ($CustomerPieceState->send_email) {

			foreach (Language::getIDs(false) as $idLang) {

				if (Tools::getValue('viewTemplate_' . $idLang)) {

					$formatter = new Formatter();
					$content = str_replace('&gt;', '>', Tools::getValue('viewTemplate_' . $idLang));
					$output = $formatter->format($content);
					$file = fopen(_EPH_ROOT_DIR_ . "/mails/fr/" . $CustomerPieceState->template[$idLang] . '.tpl', "w");
					fwrite($file, $output);
				}

			}

		}

		$return = [
			'success' => true,
			'message' => $this->l('Le status de commande a été mis à jour avec succès'),
		];

		die(Tools::jsonEncode($return));
	}

	protected function getTemplates() {

		$theme = new Theme($this->context->company->id_theme);
		$defaultPath = '../mails/';
		$themePath = '../themes/' . $theme->directory . '/mails/'; // Mail templates can also be found in the theme folder

		$array = [];

		foreach (Language::getLanguages(false) as $language) {
			$isoCode = $language['iso_code'];

			// If there is no folder for the given iso_code in /mails or in /themes/[theme_name]/mails, we bypass this language

			if (!@filemtime(_EPH_ADMIN_DIR_ . '/' . $defaultPath . $isoCode) && !@filemtime(_EPH_ADMIN_DIR_ . '/' . $themePath . $isoCode)) {
				continue;
			}

			$themeTemplatesDir = _EPH_ADMIN_DIR_ . '/' . $themePath . $isoCode;
			$themeTemplates = is_dir($themeTemplatesDir) ? scandir($themeTemplatesDir) : [];
			// We merge all available emails in one array
			$templates = array_unique(array_merge(scandir(_EPH_ADMIN_DIR_ . '/' . $defaultPath . $isoCode), $themeTemplates));

			foreach ($templates as $key => $template) {

				if (!strncmp(strrev($template), 'lpt.', 4)) {
					$searchResult = array_search($template, $themeTemplates);
					$array[$isoCode][] = [
						'id'     => substr($template, 0, -4),
						'name'   => substr($template, 0, -4),
						'folder' => ((!empty($searchResult) ? $themePath : $defaultPath)),
					];
				}

			}

		}

		return $array;
	}

}
