<?php

/**
 * @property BookRecords $object
 */
class AdminBookingsControllerCore extends AdminController {

	public $php_self = 'adminbookings';

	public function __construct() {

		$this->bootstrap = true;

		$this->context = Context::getContext();

		parent::__construct();

	}

	public function setMedia($isNewTheme = false) {

		parent::setMedia($isNewTheme);

		$this->addCss(_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/bookings.css', 'all');
		$this->addJS(_PS_JS_DIR_ . 'bookings.js');

		Media::addJsDef([
			'AjaxLink' . $this->controller_name => $this->context->link->getAdminLink($this->controller_name),
		]);

	}

	public function initContent() {

		$this->context->smarty->assign([
			'controller' => Tools::getValue('controller'),
		]);

		parent::initContent();

	}

	public function ajaxProcessOpenSociety() {

		$idCompany = Configuration::get('EPH_COMPANY_ID');

		if (!($company = new Company($idCompany))) {
			return '';
		}

		$country = Country::getCountries($this->context->language->id, true);
		$data = $this->createTemplate('controllers/bookings/society.tpl');

		$data->assign('company', $company);
		$data->assign('country', $country);

		$li = '<li id="uperSociety" data-controller="' . $this->controller_name . '"><a href="#contentSociety">Identité de l‘entreprise</a><button type="button" class="close tabdetail" data-id="uperSociety"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentSociety" class="panel col-lg-12">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenBookingParam() {

		$idCompany = Configuration::get('EPH_COMPANY_ID');

		if (!($company = new Company($idCompany))) {
			return '';
		}

		$data = $this->createTemplate('controllers/bookings/booking.tpl');

		$data->assign('company', $company);
		$data->assign('EPH_FIRST_ACCOUNT_START', Configuration::get('EPH_FIRST_ACCOUNT_START'));
		$data->assign('EPH_FIRST_ACCOUNT_END', Configuration::get('EPH_FIRST_ACCOUNT_END'));
		$data->assign('EPH_N-1_ACCOUNT_START', Configuration::get('EPH_N-1_ACCOUNT_START'));
		$data->assign('EPH_N-1_ACCOUNT_END', Configuration::get('EPH_N-1_ACCOUNT_END'));
		$data->assign('EPH_N_ACCOUNT_START', Configuration::get('EPH_N_ACCOUNT_START'));
		$data->assign('EPH_N_ACCOUNT_END', Configuration::get('EPH_N_ACCOUNT_END'));
		$data->assign('EPH_N1_ACCOUNT_START', Configuration::get('EPH_N1_ACCOUNT_START'));
		$data->assign('EPH_N1_ACCOUNT_END', Configuration::get('EPH_N1_ACCOUNT_END'));
		$data->assign('EPH_POST_ACCOUNT_START', Configuration::get('EPH_POST_ACCOUNT_START'));
		$data->assign('EPH_POST_ACCOUNT_END', Configuration::get('EPH_POST_ACCOUNT_END'));

		$data->assign('EPH_STUDENT_AFFECTATION', Configuration::get('EPH_STUDENT_AFFECTATION'));
		$data->assign('EPH_STUDENT_AFFECTATION_1_TYPE', Configuration::get('EPH_STUDENT_AFFECTATION_1_TYPE'));
		$data->assign('EPH_STUDENT_COMMON_ACCOUNT', Configuration::get('EPH_STUDENT_COMMON_ACCOUNT'));
		$data->assign('EPH_STUDENT_COMMON_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_STUDENT_COMMON_ACCOUNT')));

		$data->assign('EPH_SUPPLIER_AFFECTATION', Configuration::get('EPH_SUPPLIER_AFFECTATION'));
		$data->assign('EPH_SUPPLIER_AFFECTATION_1_TYPE', Configuration::get('EPH_STUDENT_AFFECTATION_1_TYPE'));
		$data->assign('EPH_SUPPLIER_COMMON_ACCOUNT', Configuration::get('EPH_SUPPLIER_COMMON_ACCOUNT'));
		$data->assign('EPH_SUPPLIER_COMMON_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_SUPPLIER_COMMON_ACCOUNT')));

		$data->assign('EPH_PROFIT_DEFAULT_ACCOUNT', Configuration::get('EPH_PROFIT_DEFAULT_ACCOUNT'));
		$data->assign('EPH_STUDENT_DEFAULT_ACCOUNT', Configuration::get('EPH_STUDENT_DEFAULT_ACCOUNT'));
		$data->assign('EPH_SUPPLIER_DEFAULT_ACCOUNT', Configuration::get('EPH_SUPPLIER_DEFAULT_ACCOUNT'));
		$data->assign('EPH_PURCHASE_DEFAULT_ACCOUNT', Configuration::get('EPH_PURCHASE_DEFAULT_ACCOUNT'));
		$data->assign('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT', StdAccount::getAccountValueById(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT')));
		$data->assign('EPH_DEDUCTIBLE_VAT_DEFAULT_ACCOUNT', StdAccount::getAccountValueById(Configuration::get('EPH_DEDUCTIBLE_VAT_DEFAULT_ACCOUNT')));

		$data->assign('EPH_PROFIT_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_PROFIT_DEFAULT_ACCOUNT')));
		$data->assign('EPH_STUDENT_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_STUDENT_DEFAULT_ACCOUNT')));
		$data->assign('EPH_SUPPLIER_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_SUPPLIER_DEFAULT_ACCOUNT')));
		$data->assign('EPH_PURCHASE_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_PURCHASE_DEFAULT_ACCOUNT')));
		$data->assign('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_COLLECTED_VAT_DEFAULT_ACCOUNT')));
		$data->assign('EPH_DEDUCTIBLE_VAT_DEFAULT_ACCOUNT_VALUE', StdAccount::getAccountValueById(Configuration::get('EPH_DEDUCTIBLE_VAT_DEFAULT_ACCOUNT')));

		$li = '<li id="uperBookingParam" data-controller="' . $this->controller_name . '"><a href="#contentBookingParam">Paramètre comptable</a><button type="button" class="close tabdetail" data-id="uperBookingParam"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentBookingParam" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenSaisieK() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_SCRIPT');
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminNewBookRecordsController();
			EmployeeConfiguration::updateValue('EXPERT_NEW_BOOK_RECORDS_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_NEW_BOOK_RECORDS_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/saisiek.tpl');
		$today = date("Y-m-d");
		$diaries = BookDiary::getBookDiary();
		$data->assign([
			'paragridScript' => $this->paragridScript,
			'diaries'        => $diaries,
			'today'          => $today,
		]);

		$li = '<li id="uperSaisieK" data-controller="' . $this->controller_name . '"><a href="#contentSaisieK">Paramètre comptable</a><button type="button" class="close tabdetail" data-id="uperSaisieK"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentSaisieK" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenSaisieListe() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_SCRIPT');
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminBookRecordsController();
			EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/saisieListe.tpl');
		$today = date("Y-m-d");
		$diaries = BookDiary::getBookDiary();
		$data->assign([
			'paragridScript' => $this->paragridScript,
			'diaries'        => $diaries,
			'today'          => $today,
			'company'        => $this->context->company,
		]);

		$li = '<li id="uperSaisieListe" data-controller="' . $this->controller_name . '"><a href="#contentSaisieListe">Liste des écritures</a><button type="button" class="close tabdetail" data-id="uperSaisieListe"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentSaisieListe" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenStudent() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_STUDENTS_SCRIPT', $this->context->employee->id);
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STUDENTS_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminStudentsController();
			EmployeeConfiguration::updateValue('EXPERT_STUDENTS_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_STUDENTS_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_STUDENTS_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STUDENTS_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/student.tpl');
		$today = date("Y-m-d");
		$diaries = BookDiary::getBookDiary();
		$data->assign([
			'paragridScript'     => $this->paragridScript,
			'manageHeaderFields' => true,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
			'company'            => $this->context->company,
		]);

		$li = '<li id="uperStudent" data-controller="' . $this->controller_name . '"><a href="#contentStudent">Liste des étudiants</a><button type="button" class="close tabdetail" data-id="uperStudent"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentStudent" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenStudentSessions() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_STUDENTEDUCATION_SCRIPT');
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STUDENTEDUCATION_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminStudentEducationsController();
			EmployeeConfiguration::updateValue('EXPERT_STUDENTEDUCATION_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_STUDENTEDUCATION_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_STUDENTEDUCATION_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_STUDENTEDUCATION_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/studentEducation.tpl');
		$sessions = EducationSession::getFilledEducationSession();
		$steps = StudentEducationStep::getEducationStep();
		$lastEducatinOpen = EducationSession::getLastEducatinOpen();
		$data->assign([
			'paragridScript'     => $this->paragridScript,
			'manageHeaderFields' => true,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
			'sessions'           => $sessions,
			'lastEducatinOpen'   => $lastEducatinOpen['id_education_session'],
			'saleAgents'         => SaleAgent::getSaleAgents(),
			'steps'              => $steps,
		]);

		$li = '<li id="uperStudentSessions" data-controller="' . $this->controller_name . '"><a href="#contentStudentSessions">Géstion des inscriptions</a><button type="button" class="close tabdetail" data-id="uperStudentSessions"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentStudentSessions" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenAgentCommerciaux() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_SALEAGENTS_SCRIPT');
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SALEAGENTS_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminSaleAgentController();
			EmployeeConfiguration::updateValue('EXPERT_SALEAGENTS_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_SALEAGENTS_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_SALEAGENTS_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_SALEAGENTS_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/saleagent.tpl');

		$data->assign([
			'paragridScript'     => $this->paragridScript,
			'manageHeaderFields' => false,
		]);

		$li = '<li id="uperAgentCommerciaux" data-controller="' . $this->controller_name . '"><a href="#contentAgentCommerciaux">Conseillers en Formation</a><button type="button" class="close tabdetail" data-id="uperAgentCommerciaux"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentAgentCommerciaux" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenEducations() {

		$this->paragridScript = EmployeeConfiguration::get('EXPERT_EDUCATIONS_SCRIPT');
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EDUCATIONS_FIELDS'), true);

		if (empty($this->paragridScript)) {
			$controller = new AdminEducationsController();
			EmployeeConfiguration::updateValue('EXPERT_EDUCATIONS_SCRIPT', $controller->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_EDUCATIONS_SCRIPT');
			EmployeeConfiguration::updateValue('EXPERT_EDUCATIONS_FIELDS', Tools::jsonEncode($controller->getBookRecordsFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EDUCATIONS_FIELDS'), true);
		}

		$data = $this->createTemplate('controllers/bookings/educations.tpl');

		$data->assign([
			'paragridScript'     => $this->paragridScript,
			'manageHeaderFields' => false,
			'id_lang_default'    => Configuration::get('PS_LANG_DEFAULT'),
		]);

		$li = '<li id="uperEducations" data-controller="' . $this->controller_name . '"><a href="#contentEducations">Nos Formations</a><button type="button" class="close tabdetail" data-id="uperEducations"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentEducations" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

}
