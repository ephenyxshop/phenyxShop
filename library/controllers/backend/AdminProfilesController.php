<?php

/**
 * Class AdminProfilesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminProfilesControllerCore extends AdminController {

	/**
	 * AdminProfilesControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->context = Context::getContext();
		$this->table = 'profile';
		$this->className = 'Profile';
		$this->publicName = $this->l('Employee profiles');
		$this->lang = true;

		$this->identifier = 'id_profile';

		foreach (Profile::getProfiles($this->context->language->id) as $profile) {
			$listProfile[] = ['value' => $profile['id_profile'], 'name' => $profile['name']];
		}

		parent::__construct();

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_PROFILES_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_PROFILES_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_PROFILES_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_PROFILES_FIELDS', Tools::jsonEncode($this->getProfileFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PROFILES_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_PROFILES_FIELDS', Tools::jsonEncode($this->getProfileFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PROFILES_FIELDS'), true);
		}

		$this->extracss = $this->pushCSS([
			$this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/confirm-box.css',

		]);
	}

	public function setMedia($isNewTheme = false) {

		parent::setMedia($isNewTheme);

		Media::addJsDef([
			'AjaxLinkAdminProfiles' => $this->context->link->getAdminLink('AdminProfiles'),

		]);

	}

	public function initContent() {

		$this->displayGrid = true;
		$this->paramGridObj = 'obj' . $this->className;
		$this->paramGridVar = 'grid' . $this->className;
		$this->paramGridId = 'grid_' . $this->controller_name;

		$this->TitleBar = $this->l('Liste des agents commerciaux');

		$this->context->smarty->assign([
			'controller'     => Tools::getValue('controller'),
			'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
			'gridId'         => 'grid_AdminProfiles',
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

		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

		$paragrid->height = 700;
		$paragrid->showNumberCell = 0;
		$paragrid->create = 'function (evt, ui) {
			buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
		$paragrid->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];
		$paragrid->complete = 'function(){
		window.dispatchEvent(new Event(\'resize\'));
        }';
		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'' . $this->l('Ajouter un Profile') . '\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'function () {' . PHP_EOL . '
                           addNewProfile();
						}',
				],
				[
					'type'     => '\'button\'',
					'label'    => '\'' . $this->l('Supprimer ce Profile') . '\'',
					'attr'     => '\'id="deleteProfile"\'',
					'style'    => '\'display:none;\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'deleteProfile',
				],

			],
		];
		$paragrid->change = 'function(evt, ui) {
			if(ui.source == "add") {
				return false;
			}
            var grid = this;
            var updateData = ui.updateList[0];
            var newRow = updateData.newRow;
            var dataField = Object.keys(newRow)[0].toString();
            var dataValue = newRow[dataField];
            var dataSponsor = updateData.rowData.id_profile;
            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminProfiles,
                data: {
                    action: \'updateByVal\',
                    idSponsor: dataSponsor,
                    field: dataField,
                    fieldValue: dataValue,
                    ajax: true
                },
                async: true,
                dataType: \'json\',
                success: function(data) {
                    if (data.success) {
                        showSuccessMessage(data.message);
						gridprofile.refreshDataAndView();
                     } else {
                        showErrorMessage(data.message);
                    }
                }
            })
        }';
		$paragrid->selectionModelType = 'row';
		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Gestion des profils utilisateurs du back office') . '\'';
		$paragrid->fillHandle = '\'all\'';

		$paragrid->rowSelect = 'function( event, ui ) {

			$("#idProfile").val(ui.addList[0].rowData.id_profile);
			$("#deleteProfile").slideDown();

        } ';

		$paragrid->selectEnd = 'function( event, ui ) {

			$("#idProfile").val(0);
			$("#deleteProfile").slideUp();

        } ';

		$option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();

		$this->paragridScript = $script;
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';

	}

	public function getProfileRequest() {

		$profiles = Db::getInstance()->executeS(
			(new DbQuery())
				->select('b.*, a.*')
				->from('profile', 'a')
				->leftJoin('profile_lang', 'b', 'b.`id_profile` = a.`id_profile` AND b.`id_lang` = ' . (int) $this->context->language->id)
				->orderBy('a.`id_profile` ASC')
		);

		return $profiles;

	}

	public function ajaxProcessgetProfileRequest() {

		die(Tools::jsonEncode($this->getProfileRequest()));

	}

	public function getProfileFields() {

		return [
			[
				'title'      => $this->l('ID'),
				'minWidth'   => 50,
				'maxWidth'   => 50,
				'dataIndx'   => 'id_profile',
				'dataType'   => 'integer',
				'editable'   => false,
				'align'      => 'center',
				'filter'     => [
					'crules' => [['condition' => "begin"]],
				],
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Nom du profil'),
				'width'    => 100,
				'dataIndx' => 'name',
				'align'    => 'left',
				'editable' => true,
				'dataType' => 'string',
			],
		];
	}

	public function ajaxProcessgetProfileFields() {

		die(EmployeeConfiguration::get('EXPERT_PROFILES_FIELDS'));
	}

	public function ajaxProcessAddNewProfile() {

		$profile = new Profile();
		$profile->name[Configuration::get('EPH_LANG_DEFAULT')] = 'Nouveau Profile';
		$profile->add();

		$result = [
			'profile' => $profile,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessUpdateByVal() {

		$idSponsor = (int) Tools::getValue('idSponsor');
		$field = Tools::getValue('field');
		$fieldValue = Tools::getValue('fieldValue');
		$sponsor = new Profile($idSponsor);
		$classVars = get_class_vars(get_class($sponsor));

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		if (Validate::isLoadedObject($sponsor)) {

			if (array_key_exists('lang', $fields[$field]) && $fields[$field]['lang']) {
				$idLang = Context::getContext()->language->id;
				$sponsor->{$field}

				[(int) $idLang] = $fieldValue;

			} else {
				$sponsor->$field = $fieldValue;
			}

			$result = $sponsor->update();

			if (!isset($result) || !$result) {
				$this->errors[] = Tools::displayError('An error occurred while updating the product.');
			} else {
				$result = [
					'success' => true,
					'message' => $this->l('Le champ a été mis à jour avec succès'),
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

	public function ajaxProcessDeleteProfile() {

		$profile = new Profile(Tools::getValue('idProfile'));

		if ($profile->id == 1) {

			$result = [
				'success' => false,
				'message' => 'Vous ne pouvez pas supprimer le profile Maître',
			];
		} else {
			$employees = Employee::getEmployeesByProfile($profile->id);

			if (is_array($employees) && Count($employees)) {
				$result = [
					'success' => false,
					'message' => 'Vous ne pouvez pas supprimer un profil utilisé',
				];

			} else {
				$profile->delete();
				$result = [
					'success' => true,
					'message' => 'Le profil a été supprimé avec succès',
				];
			}

		}

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

		/* PhenyxShop demo mode */

		if (_EPH_MODE_DEMO_) {
			$this->errors[] = Tools::displayError('This functionality has been disabled.');

			return;
		}

		/* PhenyxShop demo mode*/

		if (isset($_GET['delete' . $this->table]) && $_GET[$this->identifier] == (int) (_EPH_ADMIN_PROFILE_)) {
			$this->errors[] = $this->l('For security reasons, you cannot delete the Administrator\'s profile.');
		} else {
			parent::postProcess();
		}

		parent::postProcess();
	}

}
