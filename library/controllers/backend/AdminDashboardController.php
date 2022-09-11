<?php

header('Access-Control-Allow-Origin: https://ephenyx.shop/');

/**
 * Class AdminDashboardControllerCore
 *
 * @since 1.9.1.0
 */
class AdminDashboardControllerCore extends AdminController {

	public $php_self = 'admindashboard';

	public $currentExerciceStart;

	public $currentExerciceEnd;

	/**
	 * AdminDashboardControllerCore constructor.
	 *
	 * @since 1.9.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;

		parent::__construct();

		$this->currentExerciceStart = Configuration::get('EPH_N_ACCOUNT_START');

		$this->currentExerciceEnd = Configuration::get('EPH_N_ACCOUNT_END');

	}

	/**
	 * @since 1.9.1.0
	 */
	public function setMedia($isNewTheme = false) {

		parent::setMedia($isNewTheme);

		$this->addCSS('https://cdn.ephenyxapi.com/shop/dashboard.css');

		$this->addJS(
			[
				_EPH_JS_DIR_ . 'nav.js',
				'https://cdn.ephenyxapi.com/shop/dashactivity.js',
				_EPH_JS_DIR_ . 'datejs/date.min.js',
				_EPH_JS_DIR_ . 'tools.js',
				_EPH_JS_DIR_ . 'dashtrends.js',
				'https://cdn.ephenyxapi.com/vendor/d3.v7.min.js',
				_EPH_JS_DIR_ . 'jquery.datetimepicker.full.js',
				_EPH_JS_DIR_ . 'colorpicker/colorpicker.js',
				_EPH_JS_DIR_ . 'dashboard.js',
				_EPH_JS_DIR_ . 'tabs.js',

			]
		);
		$this->addCSS(
			[
				'https://cdn.ephenyxapi.com/vendor/nv.d3.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/colorpicker/colorpicker.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/jquery.datetimepicker.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/graph.css',
			]
		);
		Media::addJsDef([
			'AjaxLinkAdminDashboard' => $this->context->link->getAdminLink('admindashboard'),
			'imageLink'              => $this->context->link->getEmployeeImageLink(),
			'read_more'              => '',
			'currentProfileId'       => $this->context->employee->id_profile,
		]);

	}

	public function initContent() {

		$newCompany = false;
		$initCompany = null;
		$this->idCompany = Configuration::get('EPH_COMPANY_ID');

		if ($this->idCompany == 0) {
			$newCompany = true;

			$company = new Company();
			$countries = Country::getCountries($this->context->language->id, true);
			$data = $this->createTemplate('paramSociety.tpl');
			$data->assign('company', $company);
			$data->assign('countries', $countries);
			$initCompany = $data->fetch();

		}

		$testStatsDateUpdate = $this->context->cookie->__get('stats_date_update');

		if (!empty($testStatsDateUpdate) && $this->context->cookie->__get('stats_date_update') < strtotime(date('Y-m-d'))) {

			switch ($this->context->employee->preselect_date_range) {
			case 'day':
				$dateFrom = date('Y-m-d');
				$dateTo = date('Y-m-d');
				break;
			case 'prev-day':
				$dateFrom = date('Y-m-d', strtotime('-1 day'));
				$dateTo = date('Y-m-d', strtotime('-1 day'));
				break;
			case 'month':
			default:
				$dateFrom = date('Y-m-01');
				$dateTo = date('Y-m-d');
				break;
			case 'prev-month':
				$dateFrom = date('Y-m-01', strtotime('-1 month'));
				$dateTo = date('Y-m-t', strtotime('-1 month'));
				break;
			case 'year':
				$dateFrom = date('Y-01-01');
				$dateTo = date('Y-m-d');
				break;
			case 'prev-year':
				$dateFrom = date('Y-m-01', strtotime('-1 year'));
				$dateTo = date('Y-12-t', strtotime('-1 year'));
				break;
			case 'exercice':
				$dateFrom = $this->currentExerciceStart;
				$dateTo = $this->currentExerciceEnd;
				break;
			case 'prev-exercice':
				$dateFrom = Configuration::get('EPH_N-1_ACCOUNT_START');
				$dateTo = Configuration::get('EPH_N-1_ACCOUNT_END');
				break;
			}

			$this->context->employee->stats_date_from = $dateFrom;
			$this->context->employee->stats_date_to = $dateTo;
			$this->context->employee->update();
			$this->context->cookie->__set('stats_date_update', strtotime(date('Y-m-d')));
			$this->context->cookie->write();
		}

		$calendarHelper = new HelperCalendar();

		$calendarHelper->setDateFrom(Tools::getValue('date_from', $this->context->employee->stats_date_from));
		$calendarHelper->setDateTo(Tools::getValue('date_to', $this->context->employee->stats_date_to));

		$statsCompareFrom = $this->context->employee->stats_compare_from;
		$statsCompareTo = $this->context->employee->stats_compare_to;

		if (is_null($statsCompareFrom) || $statsCompareFrom == '0000-00-00') {
			$statsCompareFrom = null;
		}

		if (is_null($statsCompareTo) || $statsCompareTo == '0000-00-00') {
			$statsCompareTo = null;
		}

		$calendarHelper->setCompareDateFrom($statsCompareFrom);
		$calendarHelper->setCompareDateTo($statsCompareTo);
		$calendarHelper->setCompareOption(Tools::getValue('compare_date_option', $this->context->employee->stats_compare_option));

		$params = [
			'date_from' => $this->context->employee->stats_date_from,
			'date_to'   => $this->context->employee->stats_date_to,
		];
		$licence = Configuration::get('EPH_LICENCE_SHOP');

		$this->context->smarty->assign([
			'date_from'               => $this->context->employee->stats_date_from,
			'date_to'                 => $this->context->employee->stats_date_to,
			'hookDashboardZoneOne'    => Hook::exec('dashboardZoneOne', $params),
			'hookDashboardZoneTwo'    => Hook::exec('dashboardZoneTwo', $params),
			'action'                  => '#',
			'new_version_url'         => isset($phenyxCurl['tpl']) ? $phenyxCurl['tpl'] : '',
			'dashboard_use_push'      => Configuration::get('EPH_DASHBOARD_USE_PUSH'),
			'calendar'                => $calendarHelper->generate(),
			'EPH_DASHBOARD_SIMULATION' => Configuration::get('EPH_DASHBOARD_SIMULATION'),
			'datepickerFrom'          => Tools::getValue('datepickerFrom', $this->context->employee->stats_date_from),
			'datepickerTo'            => Tools::getValue('datepickerTo', $this->context->employee->stats_date_to),
			'preselect_date_range'    => Tools::getValue('preselectDateRange', $this->context->employee->preselect_date_range),
			'newCompany'              => $newCompany,
			'initCompany'             => $initCompany,
		]);

		return parent::initContent();
	}

	public function ajaxProcessRefreshDashboard() {

		$idModule = null;

		if ($module = Tools::getValue('module')) {
			$moduleObj = Module::getInstanceByName($module);

			if (Validate::isLoadedObject($moduleObj)) {
				$idModule = $moduleObj->id;
			}

		}

		$params = [
			'date_from'          => $this->context->employee->stats_date_from,
			'date_to'            => $this->context->employee->stats_date_to,
			'compare_from'       => $this->context->employee->stats_compare_from,
			'compare_to'         => $this->context->employee->stats_compare_to,
			'dashboard_use_push' => (int) Tools::getValue('dashboard_use_push'),
			'extra'              => (int) Tools::getValue('extra'),
		];

		$this->ajaxDie(json_encode(Hook::exec('dashboardData', $params, $idModule, true, true, (int) Tools::getValue('dashboard_use_push'))));
	}

	public function ajaxProcessSetMainboardDateRange() {

		$value = Tools::getValue('item');

		switch ($value) {
		case 'submitDateDay':
			$from = date('Y-m-d');
			$to = date('Y-m-d');
			$this->context->employee->preselect_date_range = 'day';
			break;
		case 'submitDateDayPrev':
			$yesterday = time() - 60 * 60 * 24;
			$from = date('Y-m-d', $yesterday);
			$to = date('Y-m-d', $yesterday);
			$this->context->employee->preselect_date_range = 'prev-day';
			break;
		case 'submitDateMonth':
			$from = date('Y-m-01');
			$to = date('Y-m-t');
			$this->context->employee->preselect_date_range = 'month';
			break;
		case 'submitDateMonthPrev':
			$m = (date('m') == 1 ? 12 : date('m') - 1);
			$y = ($m == 12 ? date('Y') - 1 : date('Y'));
			$from = $y . '-' . $m . '-01';
			$to = $y . '-' . $m . date('-t', mktime(12, 0, 0, $m, 15, $y));
			$this->context->employee->preselect_date_range = 'prev-month';
			break;
		case 'submitDateYear':
			$from = date('Y-01-01');
			$to = date('Y-12-31');
			$this->context->employee->preselect_date_range = 'year';
			break;
		case 'submitDateYearPrev':
			$from = (date('Y') - 1) . date('-01-01');
			$to = (date('Y') - 1) . date('-12-31');
			$this->context->employee->preselect_date_range = 'prev-year';
			break;
		case 'submitDateExercice':
			$from = Configuration::get('EPH_N_ACCOUNT_START');
			$from = str_replace('/', '-', $from);
			$from = date('Y-m-d', strtotime($from));
			$to = Configuration::get('EPH_N_ACCOUNT_END');
			$to = str_replace('/', '-', $to);
			$to = date('Y-m-d', strtotime($to));
			$this->context->employee->preselect_date_range = 'exercice';
			break;
		case 'submitDateExercicePrev':
			$from = Configuration::get('EPH_N-1_ACCOUNT_START');
			$from = Configuration::get('EPH_N_ACCOUNT_START');
			$from = str_replace('/', '-', $from);
			$from = date('Y-m-d', strtotime($from));
			$to = Configuration::get('EPH_N-1_ACCOUNT_END');
			$to = str_replace('/', '-', $to);
			$to = date('Y-m-d', strtotime($to));
			$this->context->employee->preselect_date_range = 'prev-exercice';
			break;
		}

		if (isset($from) && isset($to) && !count($this->errors)) {

			$this->context->employee->stats_date_from = $from;
			$this->context->employee->stats_date_to = $to;
			$this->context->employee->update();
			$return = [
				'from' => $from,
				'to'   => $to,
			];
			die(Tools::jsonEncode($return));
		}

	}

	public function ajaxProcessSaveDashConfig() {

		$return = ['has_errors' => false, 'errors' => []];
		$module = Tools::getValue('module');
		$hook = Tools::getValue('hook');
		$configs = Tools::getValue('configs');

		$params = [
			'date_from' => $this->context->employee->stats_date_from,
			'date_to'   => $this->context->employee->stats_date_to,
		];

		if (Validate::isModuleName($module) && $moduleObj = Module::getInstanceByName($module)) {

			if (Validate::isLoadedObject($moduleObj) && method_exists($moduleObj, 'validateDashConfig')) {
				$return['errors'] = $moduleObj->validateDashConfig($configs);
			}

			if (!count($return['errors'])) {

				if (Validate::isLoadedObject($moduleObj) && method_exists($moduleObj, 'saveDashConfig')) {
					$return['has_errors'] = $moduleObj->saveDashConfig($configs);
				} else

				if (is_array($configs) && count($configs)) {

					foreach ($configs as $name => $value) {

						if (Validate::isConfigName($name)) {
							Configuration::updateValue($name, $value);
						}

					}

				}

			} else {
				$return['has_errors'] = true;
			}

		}

		if (Validate::isHookName($hook) && method_exists($moduleObj, $hook)) {
			$return['widget_html'] = $moduleObj->$hook($params);
		}

		$this->ajaxDie(json_encode($return));
	}

	public function ajaxProcessSetSimulationMode() {

		Configuration::updateValue('EPH_DASHBOARD_SIMULATION', (int) Tools::getValue('EPH_DASHBOARD_SIMULATION'));
		$this->ajaxDie('k' . Configuration::get('EPH_DASHBOARD_SIMULATION') . 'k');
	}

	public function ajaxProcessGetBlogRss() {

		$return = ['has_errors' => false, 'rss' => []];

		if (!$this->isFresh('/app/xml/blog-' . $this->context->language->iso_code . '.xml', 86400)) {

			if (!$this->refresh('/app/xml/blog-' . $this->context->language->iso_code . '.xml', 'https://ephenyx.com/feed/')) {
				$return['has_errors'] = true;
			}

		}

		if (!$return['has_errors']) {
			$rss = @simplexml_load_file(_EPH_ROOT_DIR_ . '/app/xml/blog-' . $this->context->language->iso_code . '.xml');

			if (!$rss) {
				$return['has_errors'] = true;
			}

			$articlesLimit = 2;

			if ($rss) {

				foreach ($rss->channel->item as $item) {

					if ($articlesLimit > 0 && Validate::isCleanHtml((string) $item->title) && Validate::isCleanHtml((string) $item->description)
						&& isset($item->link) && isset($item->title)
					) {

						if (in_array($this->context->mode, [Context::MODE_HOST, Context::MODE_HOST_CONTRIB])) {
							$utmContent = 'cloud';
						} else {
							$utmContent = 'download';
						}

						$shopDefaultCountryId = (int) Configuration::get('EPH_COUNTRY_DEFAULT');
						$shopDefaultIsoCountry = (string) mb_strtoupper(Country::getIsoById($shopDefaultCountryId));
						$analyticsParams = [
							'utm_source'   => 'back-office',
							'utm_medium'   => 'rss',
							'utm_campaign' => 'back-office-' . $shopDefaultIsoCountry,
							'utm_content'  => $utmContent,
						];
						$urlQuery = parse_url($item->link, PHP_URL_QUERY);
						parse_str($urlQuery, $linkQueryParams);

						if ($linkQueryParams) {
							$fullUrlParams = array_merge($linkQueryParams, $analyticsParams);
							$baseUrl = explode('?', (string) $item->link);
							$baseUrl = (string) $baseUrl[0];
							$articleLink = $baseUrl . '?' . http_build_query($fullUrlParams);
						} else {
							$articleLink = (string) $item->link . '?' . http_build_query($analyticsParams);
						}

						$return['rss'][] = [
							'date'       => Tools::displayDate(date('Y-m-d', strtotime((string) $item->pubDate))),
							'title'      => (string) Tools::htmlentitiesUTF8($item->title),
							'short_desc' => Tools::truncateString(strip_tags((string) $item->description), 150),
							'link'       => (string) $articleLink,
						];
					} else {
						break;
					}

					$articlesLimit--;
				}

			}

		}

		$this->ajaxDie(json_encode($return));
	}

	public function ajaxProcessOpenSociety() {

		$idCompany = Configuration::get('EPH_COMPANY_ID');

		if (!($company = new Company($idCompany))) {
			return '';
		}

		$extracss = $this->pushCSS([
			_EPH_JS_DIR_ . 'trumbowyg/ui/trumbowyg.min.css',
			_EPH_JS_DIR_ . 'jquery-ui/general.min.css',

		]);
		$pusjJs = $this->pushJS([
			_EPH_JS_DIR_ . 'society.js',
			_EPH_JS_DIR_ . 'trumbowyg/trumbowyg.min.js',
			_EPH_JS_DIR_ . 'jquery-jeditable/jquery.jeditable.min.js',
			_EPH_JS_DIR_ . 'jquery-ui/jquery-ui-timepicker-addon.min.js',
			_EPH_JS_DIR_ . 'moment/moment.min.js',
			_EPH_JS_DIR_ . 'moment/moment-timezone-with-data.min.js',
			_EPH_JS_DIR_ . 'calendar/working_plan_exceptions_modal.min.js',

		]);

		$country = Country::getCountries($this->context->language->id, true);
		$data = $this->createTemplate('society.tpl');

		$data->assign('company', $company);
		$data->assign('countries', $country);
		$data->assign([
			'EALang'      => Tools::jsonEncode($this->getEaLang()),
			'pusjJs'      => $pusjJs,
			'extracss'    => $extracss,
			'workin_plan' => Tools::jsonEncode($company->working_plan),

		]);

		$li = '<li id="uperEditSociety" data-controller="' . $this->controller_name . '"><a href="#contentEditSociety">Identité de l‘entreprise</a><button type="button" class="close tabdetail" data-id="uperEditSociety"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentEditSociety" class="panel col-lg-12">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessUpdateCompany() {

		$idCompany = Tools::getValue('id_company');

		if ($idCompany > 0) {
			$company = new Company($idCompany);
		} else {
			$company = new Company();
		}

		foreach ($_POST as $key => $value) {

			if (property_exists($company, $key) && $key != 'id_company') {

				$company->{$key}

				= $value;

			}

		}

		if ($idCompany > 0) {
			$result = $company->update();
			$return = [
				'success' => true,
				'message' => 'Votre société a été mis à jour avec succès',
			];
		} else {
			$result = $company->add();
			$return = [
				'success' => true,
				'message' => 'Votre société vient d‘être créer avec succès',
			];
		}

		Configuration::updateValue('EPH_SHOP_EMAIL', $company->company_email);
		Configuration::updateValue('EPH_SHOP_ADMIN_EMAIL', $company->administratif_email);
		Configuration::updateValue('EPH_SHOP_NAME', $company->company_name);
		Configuration::updateValue('EPH_SHOP_URL', $company->company_url);

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessOpenBookingParam() {

		$idCompany = Configuration::get('EPH_COMPANY_ID');

		if (!($company = new Company($idCompany))) {
			return '';
		}

		$data = $this->createTemplate('controllers/booking.tpl');

		$data->assign('company', $company);
		$data->assign('EPH_FIRST_ACCOUNT_START', Configuration::get('EPH_FIRST_ACCOUNT_START'));
		$data->assign('EPH_FIRST_ACCOUNT_END', Configuration::get('EPH_FIRST_ACCOUNT_END'));
		$data->assign('EPH_N_ACCOUNT_START', Configuration::get('EPH_N_ACCOUNT_START'));
		$data->assign('EPH_N_ACCOUNT_END', Configuration::get('EPH_N_ACCOUNT_END'));
		$data->assign('EPH_N1_ACCOUNT_END', Configuration::get('EPH_N1_ACCOUNT_END'));
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
		$data->assign([

			'link'       => $this->context->link,
			'controller' => 'AdminCompany',
		]);

		$li = '<li id="uperBookingParam" data-controller="' . $this->controller_name . '"><a href="#contentBookingParam">Paramètre comptable</a><button type="button" class="close tabdetail" data-id="uperBookingParam"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentBookingParam" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenSaisieK() {

		$controller = new AdminNewBookRecordsController();
		$this->paragridScript = $controller->generateParaGridScript();

		$data = $this->createTemplate('controllers/saisiek.tpl');
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

	public function ajaxProcessUploadProfilPicture() {

		$id_employee = $this->context->employee->id;
		$dir = _EPH_EMPLOYEE_IMG_DIR_;
		$name = 'PicProfil';
		$type == 'profil';

		if ($croped_image = Tools::getValue($name)) {
			list($type, $croped_image) = explode(';', $croped_image);
			list(, $croped_image) = explode(',', $croped_image);
			$croped_image = base64_decode($croped_image);
			$uploadfile = $dir . basename($this->context->employee->id . '.jpg');
			file_put_contents($uploadfile, $croped_image);
			ImageManager::resize($uploadfile, $uploadfile);
			die($this->context->employee->id);
		}

	}

	public function ajaxProcessGetAdminLinkController() {

		$controller = Tools::getValue('sourceController');
		$link = $this->context->link->getAdminLink($controller);
		$return = [
			'link' => $link,
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessEraseCache() {

		Tools::clearSmartyCache();
		Tools::cleanFrontCache();
		Tools::cleanThemeDirectory();
		Tools::generateIndex();
		PageCache::flush();

		if (function_exists('opcache_reset')) {
			opcache_reset();
		}

		$result = [
			'success' => true,
			'message' => 'Le cache a été vidé avec succès',
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessgetRevolutionSliderTab() {

		$sliderRevvolution = new AdminRevsliderSlidersController();
		$li = '<li id="uperAdminRevsliderSliders" data-self="' . $sliderRevvolution->link_rewrite . '?page=revslider" data-name="' . $sliderRevvolution->page_title . '" data-controller="AdminDashboard"><a href="#contentAdminRevsliderSliders">' . $sliderRevvolution->publicName . '</a><button type="button" class="close tabdetail" data-id="uperAdminRevsliderSliders"><i class="icon icon-times-circle"></i></button></li>';
		$result = [
			'li'         => $li,
			'page_title' => $sliderRevvolution->page_title,
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessOpenRevolutionSlider() {

		$_GET['page'] = 'revslider';
		$sliderRevvolution = new AdminRevsliderSlidersController();
		require_once _EPH_MODULE_DIR_ . 'revslider/rev-loader.php';
		$data = $this->createTemplate('controllers/revslider_sliders/revslider.tpl');

		$rsaf = new RevSliderFunctionsAdmin();
		$system_config = $rsaf->get_system_requirements();
		$overview_data = $rsaf->get_slider_overview();
		$rsa = $rsaf->get_short_library();

		$rsupd = new RevSliderPluginUpdate();
		$mediamanagerurl = $this->context->link->getAdminLink('adminlayersliderMedia');
		$jsDef = [
			'slide_id'         => 0,
			'rev_ajaxurl'      => RevLoader::getAjaxUrl(),
			'custom_admin_url' => RevLoader::getCustomAdminRUL(),
			'custom_base_url'  => RevLoader::customBaseURL(),
			'project_url'      => RevLoader::url(),
			'mediamanagerurl'  => $mediamanagerurl,
			'RVS_LANG'         => $sliderRevvolution->get_javascript_multilanguage(),
		];

		$rsaddon = new RevSliderAddons();
		$rs_addon_update = $rsaddon->check_addon_version();

		$rs_addons = $rsaddon->get_addon_list();

		$rs_wp_date_format = RevLoader::get_option('date_format');
		$rs_wp_time_format = RevLoader::get_option('time_format');
		$rs_added_image_sizes = $rsaf->get_all_image_sizes();

		$rs_slider_update_needed = $rsupd->slider_need_update_checks();
		$rs_global_settings = $rsaf->get_global_settings();
		$rs_notices = $rsaf->add_notices();

		$rs_compression = $rsaf->compression_settings();
		$rs_backend_fonts = $rsaf->get_font_familys();
		$rs_new_addon_counter = RevLoader::get_option('rs-addons-counter', false);
		$rs_new_addon_counter = ($rs_new_addon_counter === false) ? count($rs_addons) : $rs_new_addon_counter;
		$rs_new_temp_counter = RevLoader::get_option('rs-templates-counter', false);

		if ($rs_new_temp_counter === false) {
			$_rs_tmplts = RevLoader::get_option('rs-templates', []);
			$rs_new_temp_counter = (isset($_rs_tmplts['slider'])) ? count($_rs_tmplts['slider']) : $rs_new_temp_counter;
		}

		$rs_global_sizes = [
			'd' => $rsaf->get_val($rs_global_settings, ['size', 'desktop'], '1240'),
			'n' => $rsaf->get_val($rs_global_settings, ['size', 'notebook'], '1024'),
			't' => $rsaf->get_val($rs_global_settings, ['size', 'tablet'], '778'),
			'm' => $rsaf->get_val($rs_global_settings, ['size', 'mobile'], '480'),
		];
		$rs_show_updated = RevLoader::get_option('rs_cache_overlay', RS_REVISION);

		if (version_compare(RS_REVISION, $rs_show_updated, '>')) {
			RevLoader::update_option('rs_cache_overlay', RS_REVISION);
		}

		$time = date('H');
		$timezone = date('e'); /* Set the $timezone variable to become the current timezone */
		$hi = $this->l('Good Evening');
		$selling = $rsaf->get_addition('selling');

		if ($time < '12') {
			$hi = $this->l('Good Morning');
		} else
		if ($time >= '12' && $time < '17') {
			$hi = $this->l('Good Afternoon');
		}

		$rs_color_picker_presets = RSColorpicker::get_color_presets();

		$current_user = $this->context->employee->firstname;

		$context = Context::getContext();
		$base_link = $context->link->getBaseAdminLink();
		$AdminControllerUrl = str_replace($base_link, '', $context->link->getAdminLink('AdminRevsliderSliders'));

		$datas = [
			'link'                       => $this->context->link,
			'rsaf'                       => $rsaf,
			'RS_DO_SILENT_SLIDER_UPDATE' => ($rsupd->slider_need_update_checks() == true) ? 'true' : 'false',
			'current_user'               => $current_user,
			'hi'                         => $hi,
			'controller'                 => $sliderRevvolution->controller_name,
			'AdminControllerUrl'         => $AdminControllerUrl,
			'jsDef'                      => $jsDef,
			'link'                       => $this->context->link,
			'id_lang_default'            => Configuration::get('EPH_LANG_DEFAULT'),
			'extracss'                   => $sliderRevvolution->extracss,
			'obj'                        => $rsaf->json_encode_client_side($rsa),
			'addOns_to_update'           => (!empty($rs_addon_update)) ? $rsaf->json_encode_client_side($rs_addon_update) : '',
			'sliders'                    => Tools::jsonEncode(RevSliderSlider::get_sliders_short_list()),
			'rs_color_picker_presets'    => (!empty($rs_color_picker_presets)) ? $rsaf->json_encode_client_side($rs_color_picker_presets) : '',
			'rs_addons'                  => $rsaf->json_encode_client_side($rs_addons),
			'activated'                  => RevLoader::get_option('revslider-valid', 'false'),
			'nonce'                      => RevLoader::wp_create_nonce("revslider_actions"),
			'RS_PLUGIN_SLUG_PATH'        => RS_PLUGIN_SLUG_PATH,
			'RS_PLUGIN_SLUG'             => RS_PLUGIN_SLUG,
			'RS_PLUGIN_URL'              => RS_PLUGIN_URL,
			'RS_REVISION'                => RS_REVISION,
			'updated'                    => (version_compare(RS_REVISION, $rs_show_updated, '>')) ? 'true' : 'false',
			'latest_version'             => RevLoader::get_option('revslider-latest-version', RS_REVISION),
			'stable_version'             => RevLoader::get_option('revslider-stable-version', '4.2'),
			'output_compress'            => $rsaf->json_encode_client_side($rs_compression),
			'rs_wp_date_format'          => $rs_wp_date_format,
			'rs_wp_time_format'          => RevLoader::get_option('time_format'),
			'tomorrow'                   => date($rs_wp_date_format, strtotime(date($rs_wp_date_format) . ' +1 day')),
			'last_week'                  => date($rs_wp_date_format, strtotime(date($rs_wp_date_format) . ' -7 day')),
			'glb_slizes'                 => $rsaf->json_encode_client_side($rs_global_sizes),
			'img_sizes'                  => $rsaf->json_encode_client_side($rs_added_image_sizes),
			'rs_image_meta_todo'         => !empty(RevLoader::get_option('rs_image_meta_todo', [])) ? 'true' : 'false',
			'notices'                    => (!empty($rs_notices)) ? $rsaf->json_encode_client_side($rs_notices) : '',
			'selling'                    => ($rsaf->get_addition('selling') === true) ? 'true' : 'false',
			'rs_new_addon_counter'       => $rs_new_addon_counter,
			'rs_new_temp_counter'        => $rs_new_temp_counter,
			'overview_data'              => $rsaf->get_slider_overview(),
			'sliderLibrary'              => $rsaf->json_encode_client_side(['sliders' => $rsaf->get_slider_overview()]),
			'system_config'              => $rsaf->json_encode_client_side($system_config),
			'code'                       => RevLoader::get_option('revslider-code', ''),
			'selling'                    => $rsaf->get_addition('selling'),
			'release_log.html'           => file_get_contents(RS_PLUGIN_PATH . 'release_log.html'),
			'registered_p_c'             => $rsaf->get_addition('selling'),
			'registered_p_c_url'         => $rsaf->get_addition('selling'),
		];

		foreach ($datas as $key => $value) {
			$data->assign($key, $value);
		}

		$html = '<div id="contentAdminRevsliderSliders" class="panel col-lg-12" style="display: flow-root;">' . $data->fetch() . '</div>';

		die($html);
	}

	public function ajaxProcessEditRevolutionSlider() {

		$sliderRevvolution = new AdminRevsliderSlidersController();
		$idSlider = Tools::getValue('idSlider');
		$_GET['page'] = 'revslider';
		$_GET['view'] = 'slide';
		$_GET['id'] = $idSlider;

		require_once _EPH_MODULE_DIR_ . 'revslider/rev-loader.php';

		$rs_data = new RevSliderData();
		$rs_f = new RevSliderFunctions();
		$slider = new RevSliderSlider();
		$slide = new RevSliderSlide();
		$rs_nav = new RevSliderNavigation();
		$wpml = new RevSliderWpml();
		$slide_id = $idSlider;
		$slide_alias = RevSliderFunctions::esc_attr_deep($rs_f->get_get_var('alias'));
		$_GET['id'] = $idSlider;
		$font_familys = $rs_f->get_font_familys();

		$json_font_familys = $rs_f->json_encode_client_side($font_familys);

		$arr_navigations = $rs_nav->get_all_navigations_builder();

		$animationsRaw = $rs_data->get_layer_animations(true);

		$rs_color_picker_presets = RSColorpicker::get_color_presets();

		$post_types_with_categories = [
			'post' => [],
			'page' => [],
		];
		$json_tax_with_cats = $rs_f->json_encode_client_side($post_types_with_categories);

		$gethooks = RevLoader::getHooks();
		$shoEPH_arr = Shop::getShops();
		$uslider = new RevSliderSlider();
		$pop_posts = $uslider->get_popular_posts(15);
		$rec_posts = $uslider->get_latest_posts(15);
		$recent = [];
		$popular = [];

		if (is_array($pop_posts)) {

			foreach ($pop_posts as $p_post) {
				$popular[] = $p_post['ID'];
			}

		}

		if (is_array($rec_posts)) {

			foreach ($rec_posts as $r_post) {
				$recent[] = $r_post['ID'];
			}

		}

		$post_sortby = '';

		if (RevSliderEventsManager::isEventsExists()) {
			$arrEMSortBy = RevSliderEventsManager::getArrSortBy();

			if (!empty($arrEMSortBy)) {

				foreach ($arrEMSortBy as $event_handle => $event_name) {
					$post_sortby .= '<option value="' . $event_handle . '">' . $event_name . '</option>';
				}

			}

		}

		$data = $this->createTemplate('controllers/revslider_sliders/editRevslider.tpl');

		$rsaf = new RevSliderFunctionsAdmin();
		$overview_data = $rsaf->get_slider_overview();
		$rsa = $rsaf->get_short_library();
		$mediamanagerurl = $this->context->link->getAdminLink('adminlayersliderMedia');
		$rsupd = new RevSliderPluginUpdate();
		$jsDef = [
			'slide_id'         => $idSlider,
			'rev_ajaxurl'      => RevLoader::getAjaxUrl(),
			'custom_admin_url' => RevLoader::getCustomAdminRUL(),
			'custom_base_url'  => RevLoader::customBaseURL(),
			'project_url'      => RevLoader::url(),
			'mediamanagerurl'  => $mediamanagerurl,
			'RVS_LANG'         => $sliderRevvolution->get_javascript_multilanguage(),

		];

		$rsaddon = new RevSliderAddons();
		$rs_addon_update = $rsaddon->check_addon_version();

		$rs_addons = $rsaddon->get_addon_list();

		$rs_wp_date_format = RevLoader::get_option('date_format');
		$rs_wp_time_format = RevLoader::get_option('time_format');
		$rs_added_image_sizes = $rsaf->get_all_image_sizes();

		$rs_slider_update_needed = $rsupd->slider_need_update_checks();
		$rs_global_settings = $rsaf->get_global_settings();
		$rs_notices = $rsaf->add_notices();

		$rs_compression = $rsaf->compression_settings();
		$rs_backend_fonts = $rsaf->get_font_familys();
		$rs_new_addon_counter = RevLoader::get_option('rs-addons-counter', false);
		$rs_new_addon_counter = ($rs_new_addon_counter === false) ? count($rs_addons) : $rs_new_addon_counter;
		$rs_new_temp_counter = RevLoader::get_option('rs-templates-counter', false);

		if ($rs_new_temp_counter === false) {
			$_rs_tmplts = RevLoader::get_option('rs-templates', []);
			$rs_new_temp_counter = (isset($_rs_tmplts['slider'])) ? count($_rs_tmplts['slider']) : $rs_new_temp_counter;
		}

		$rs_global_sizes = [
			'd' => $rsaf->get_val($rs_global_settings, ['size', 'desktop'], '1240'),
			'n' => $rsaf->get_val($rs_global_settings, ['size', 'notebook'], '1024'),
			't' => $rsaf->get_val($rs_global_settings, ['size', 'tablet'], '778'),
			'm' => $rsaf->get_val($rs_global_settings, ['size', 'mobile'], '480'),
		];
		$rs_show_updated = RevLoader::get_option('rs_cache_overlay', RS_REVISION);

		if (version_compare(RS_REVISION, $rs_show_updated, '>')) {
			RevLoader::update_option('rs_cache_overlay', RS_REVISION);
		}

		$context = Context::getContext();
		$base_link = $context->link->getBaseAdminLink();
		$AdminControllerUrl = str_replace($base_link, '', $context->link->getAdminLink('AdminRevsliderSliders'));

		$datas = [
			'rsaf'                     => $rsaf,
			'gethooks'                 => $gethooks,
			'shoEPH_arr'                => $shoEPH_arr,
			'json_tax_with_cats'       => $json_tax_with_cats,
			'idSlider'                 => $idSlider,
			'slide_alias'              => $slide_alias,
			'popular'                  => implode(',', $popular),
			'recent'                   => implode(',', $recent),
			'post_sortby'              => $post_sortby,
			'controller'               => $sliderRevvolution->php_self,
			'AdminControllerUrl'       => $AdminControllerUrl,
			'jsDef'                    => $jsDef,
			'link'                     => $this->context->link,
			'id_lang_default'          => Configuration::get('EPH_LANG_DEFAULT'),
			'extracss'                 => $sliderRevvolution->extracss,
			'obj'                      => $rsaf->json_encode_client_side($rsa),
			'addOns_to_update'         => (!empty($rs_addon_update)) ? $rsaf->json_encode_client_side($rs_addon_update) : '',
			'sliders'                  => Tools::jsonEncode(RevSliderSlider::get_sliders_short_list()),
			'rs_color_picker_presets'  => (!empty($rs_color_picker_presets)) ? $rsaf->json_encode_client_side($rs_color_picker_presets) : '',
			'rs_addons'                => $rsaf->json_encode_client_side($rs_addons),
			'activated'                => RevLoader::get_option('revslider-valid', 'false'),
			'nonce'                    => RevLoader::wp_create_nonce("revslider_actions"),
			'RS_PLUGIN_SLUG_PATH'      => RS_PLUGIN_SLUG_PATH,
			'RS_PLUGIN_SLUG'           => RS_PLUGIN_SLUG,
			'RS_PLUGIN_URL'            => RS_PLUGIN_URL,
			'RS_REVISION'              => RS_REVISION,
			'updated'                  => (version_compare(RS_REVISION, $rs_show_updated, '>')) ? 'true' : 'false',
			'latest_version'           => RevLoader::get_option('revslider-latest-version', RS_REVISION),
			'stable_version'           => RevLoader::get_option('revslider-stable-version', '4.2'),
			'output_compress'          => $rsaf->json_encode_client_side($rs_compression),
			'rs_wp_date_format'        => $rs_wp_date_format,
			'rs_wp_time_format'        => RevLoader::get_option('time_format'),
			'tomorrow'                 => date($rs_wp_date_format, strtotime(date($rs_wp_date_format) . ' +1 day')),
			'last_week'                => date($rs_wp_date_format, strtotime(date($rs_wp_date_format) . ' -7 day')),
			'glb_slizes'               => $rsaf->json_encode_client_side($rs_global_sizes),
			'img_sizes'                => $rsaf->json_encode_client_side($rs_added_image_sizes),
			'rs_image_meta_todo'       => !empty(RevLoader::get_option('rs_image_meta_todo', [])) ? 'true' : 'false',
			'notices'                  => (!empty($rs_notices)) ? $rsaf->json_encode_client_side($rs_notices) : '',
			'selling'                  => ($rsaf->get_addition('selling') === true) ? 'true' : 'false',
			'rs_new_addon_counter'     => $rs_new_addon_counter,
			'rs_new_temp_counter'      => $rs_new_temp_counter,
			'revslider_header_content' => RevLoader::do_action('revslider_header_content', $rsaf),
			'overview_data'            => $rsaf->get_slider_overview(),
			'sliderLibrary'            => $rsaf->json_encode_client_side(['sliders' => $rsaf->get_slider_overview()]),
			'system_config'            => $rsaf->json_encode_client_side($rsaf->get_system_requirements()),
			'code'                     => RevLoader::get_option('revslider-code', ''),
			'selling'                  => $rsaf->get_addition('selling'),
			'release_log.html'         => file_get_contents(RS_PLUGIN_PATH . 'release_log.html'),
			'registered_p_c'           => $rsaf->get_addition('selling'),
			'registered_p_c_url'       => $rsaf->get_addition('selling'),
			'migrateNavigation'        => $rs_f->json_encode_client_side($arr_navigations),
			'json_font_familys'        => $json_font_familys,
			'mediamanagerurl'          => $mediamanagerurl,
		];

		foreach ($datas as $key => $value) {
			$data->assign($key, $value);
		}

		$file = fopen("testEditSlider.html", "w");
		$html = $data->fetch();
		fwrite($file, $html);

		die($html);
	}

}
