<?php

/**
 * Class AdminLoginControllerCore
 *
 * @since 1.9.1.0
 */
class AdminLoginControllerCore extends AdminController {

    public $php_self = 'adminlogin';
	/**
     * AdminLoginControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->errors = [];
        $this->display_header = false;
        $this->display_footer = false;
        $this->meta_title = $this->l('Administration panel');
        $this->css_files = [];
        parent::__construct();
        if(Validate::isLoadedObject($this->context->employee)) {
            $url = $this->context->link->getAdminLink('admindashboard');
            Tools::redirectAdmin($url);
        }
        $this->layout = _SHOP_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'login' . DIRECTORY_SEPARATOR . 'layout.tpl';

        if (!headers_sent()) {
            header('Login: true');
        }

    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initContent() {
		
        if (!Tools::usingSecureMode() && Configuration::get('EPH_SSL_ENABLED')) {
            // You can uncomment these lines if you want to force https even from localhost and automatically redirect
            // header('HTTP/1.1 301 Moved Permanently');
            // header('Location: '.Tools::getShopDomainSsl(true).$_SERVER['REQUEST_URI']);
            // exit();
            $clientIsMaintenanceOrLocal = in_array(Tools::getRemoteAddr(), array_merge(['127.0.0.1'], explode(',', Configuration::get('EPH_MAINTENANCE_IP'))));
            // If ssl is enabled, https protocol is required. Exception for maintenance and local (127.0.0.1) IP

            if ($clientIsMaintenanceOrLocal) {
                $warningSslMessage = Tools::displayError('SSL is activated. However, your IP is allowed to enter unsecure mode for maintenance or local IP issues.');
            } else {
                $url = 'https://' . Tools::safeOutput(Tools::getServerName()) . Tools::safeOutput($_SERVER['REQUEST_URI']);
                $warningSslMessage = sprintf(
                    Translate::ppTags(
                        Tools::displayError('SSL is activated. Please connect using the following link to [1]log into secure mode (https://)[/1]', false),
                        ['<a href="%s">']
                    ),
                    $url
                );
            }

            $this->context->smarty->assign('warningSslMessage', $warningSslMessage);
        }

        

        $rand = basename(_EPH_ROOT_DIR_) . '/';

        $this->context->smarty->assign(
            [
                'randomNb' => $rand,
                'adminUrl' => Tools::getCurrentUrlProtocolPrefix() . Tools::getShopDomain() . __EPH_BASE_URI__ . $rand,
				'link'            			=> $this->context->link,
            ]
        );
		
		$this->context->smarty->assign($this->initLogoAndFavicon());

        // Redirect to admin panel

        if (Tools::isSubmit('redirect') && Validate::isControllerName(Tools::getValue('redirect'))) {
            $this->context->smarty->assign('redirect', Tools::getValue('redirect'));
        } else {
            $tab = new EmployeeMenu((int) $this->context->employee->default_tab);
            $this->context->smarty->assign('redirect', $this->context->link->getAdminLink($tab->class_name));
        }

        if ($nbErrors = count($this->errors)) {
            $this->context->smarty->assign(
                [
                    'errors'                    => $this->errors,
                    'nbErrors'                  => $nbErrors,
                    'shop_name'                 => Tools::safeOutput(Configuration::get('EPH_SHOP_NAME')),
                    'disableDefaultErrorOutPut' => true,
					
                ]
            );
        }

        if ($email = Tools::getValue('email')) {
            $this->context->smarty->assign('email', $email);
        }

        if ($password = Tools::getValue('password')) {
            $this->context->smarty->assign('password', $password);
        }

        $this->setMedia();
        $this->initHeader();
        parent::initContent();
        $this->initFooter();

        //force to disable modals
        $this->context->smarty->assign('modals', null);
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function setMedia($isNewTheme = false) {
		
		

        $this->addJS( 'https://code.jquery.com/jquery-3.6.0.min.js');
        $this->addjqueryPlugin('validate');
        $this->addJS(_EPH_JS_DIR_.'jquery/plugins/validate/localization/messages_' . $this->context->language->iso_code . '.js');
		$this->addCSS(_EPH_ADMIN_THEME_DIR_. $this->bo_theme . '/css/admin-theme.css', 'all', 0);
        $this->addCSS(_EPH_ADMIN_THEME_DIR_. $this->bo_theme . '/css/overrides.css', 'all', PHP_INT_MAX);
        
        $this->addJS('https://cdn.ephenyxapi.com/vendor/spin.js');
        $this->addJS('https://cdn.ephenyxapi.com/vendor/ladda.js');
        MediaAdmin::addJsDef(['img_dir' => _EPH_IMG_]);
        MediaAdmin::addJsDefL('one_error', $this->l('There is one error.', null, true, false));
        MediaAdmin::addJsDefL('more_errors', $this->l('There are several errors.', null, true, false));

        Hook::exec('actionAdminLoginControllerSetMedia');
    }
	
	public function initLogoAndFavicon() {

        $logo = $this->context->link->getBaseFrontLink() .DIRECTORY_SEPARATOR . 'content' .DIRECTORY_SEPARATOR . 'img' .DIRECTORY_SEPARATOR . Configuration::get('EPH_LOGO');

        return [
            'favicon_url'       => $this->context->link->getBaseFrontLink() .DIRECTORY_SEPARATOR . 'content'.DIRECTORY_SEPARATOR . 'img' .DIRECTORY_SEPARATOR .Configuration::get('EPH_FAVICON'),
            'logo_image_width'  => Configuration::get('SHOP_LOGO_WIDTH'),
            'logo_image_height' => Configuration::get('SHOP_LOGO_HEIGHT'),
            'logo_url'          => $logo,
        ];
    }

    /**
     * Check token
     *
     * Always true to make this page publicly accessible
     *
     * @return bool
     */
    public function checkToken() {

        return true;
    }

    /**
     * All BO users can access the login page
     *
     * Always returns true to make this page publicly accessible
     *
     * @param bool $disable Not used
     *
     * @return bool
     */
    public function viewAccess($disable = false) {

        return true;
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function postProcess() {

        if (Tools::isSubmit('submitLogin')) {
            $this->processLogin();
        } else if ($action = Tools::getValue('action') && $action = 'passswordForgot') {
            $this->ajaxProcessPassswordForgot();
        }

    }

    /**
     * Process login
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function processLogin() {

        /* Check fields validity */
        $passwd = trim(Tools::getValue('passwd'));
        $email = trim(Tools::getValue('email'));

        if (!Validate::isEmail($email)) {
            $this->errors[] = Tools::displayError('Invalid email address.');
        }

        if (!Validate::isPasswdAdmin($passwd)) {
            $this->errors[] = Tools::displayError('Invalid password.');
        }

        if (!count($this->errors)) {
            // Find employee
            $this->context->employee = new Employee();
            $isEmployeeLoaded = $this->context->employee->getByEmail($email, $passwd);

            if (!$isEmployeeLoaded) {
                $this->errors[] = Tools::displayError('The employee does not exist, or the password provided is incorrect.');
                $this->context->employee->logout();
            } else {
               
                $this->context->employee->log_in = 1;
                $this->context->employee->last_timestamp = time();
                $this->context->employee->update();

                $this->context->employee->remote_addr = (int) ip2long(Tools::getRemoteAddr());
                
                $customer = new Customer($this->context->employee->id_customer);
                $this->context->customer = $customer;
                $this->context->cookie->is_admin = 1;
                $this->context->cookie->id_customer = (int) $customer->id;
                $this->context->cookie->customer_lastname = $customer->lastname;
                $this->context->cookie->customer_firstname = $customer->firstname;
                $this->context->cookie->passwd = $customer->passwd;
                $this->context->cookie->logged = 1;
                $this->context->cookie->__set('logged', 1);
                $customer->logged = 1;
                $this->context->cookie->email = $customer->email;		
		        $this->context->cookie->customer_group = (int)$customer->id_default_group;		
                // Update cookie
                $cookie = $this->context->cookie;
                $cookie->id_employee = $this->context->employee->id;
                $cookie->email = $this->context->employee->email;
                $cookie->profile = $this->context->employee->id_profile;
                $cookie->passwd = $this->context->employee->passwd;
                $cookie->remote_addr = $this->context->employee->remote_addr;

                if (!Tools::getValue('stay_logged_in')) {
                    $cookie->last_activity = time();
                } else {
                    // Needed in some edge cases, see Github issue #399.
                    unset($cookie->last_activity);
                }

                $cookie->write();
                
				$url = $this->context->link->getAdminLink('admindashboard');
                // If there is a valid controller name submitted, redirect to it

                

                if (Tools::isSubmit('ajax')) {
                    $this->ajaxDie(json_encode(['hasErrors' => false, 'redirect' => $url]));
                } else {
                    $this->redirect_after = $url;
                }

            }

        }

        if (Tools::isSubmit('ajax')) {
            $this->ajaxDie(json_encode(['hasErrors' => true, 'errors' => $this->errors]));
        }

    }

    /**
     * Process password forgotten
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessPassswordForgot() {

        $employee = new Employee();
        $employeeExists = false;
        $nextEmailTime = PHP_INT_MAX;

        if (_EPH_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');
        } else {
            $email = trim(Tools::getValue('email_forgot'));

            if (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } else {
                $employeeExists = $employee->getByEmail($email);

                if ($employeeExists) {
                    $nextEmailTime = strtotime($employee->last_passwd_gen . '+' . Configuration::get('EPH_PASSWD_TIME_BACK') . ' minutes');
                }

            }

        }

        if (!count($this->errors)
            && $employeeExists
            && $nextEmailTime < time()) {
            $password = Tools::generateStrongPassword(10);
            $employee->passwd = Tools::hash($password);
            $employee->last_passwd_gen = date('Y-m-d H:i:s', time());

            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . '/fr/employee_password.tpl');
            $tpl->assign([
                'email'     => $employee->email,
                'lastname'  => $employee->lastname,
                'firstname' => $employee->firstname,
                'passwd'    => $password,

            ]);
            $postfields = [
                'sender'      => [
                    'name'  => "Service  Administratif ".Configuration::get('EPH_SHOP_NAME'),
                    'email' => 'no-reply@'.Configuration::get('EPH_SHOP_URL'),
                ],
                'to'          => [
                    [
                        'name'  => $employee->firstname . ' ' . $employee->lastname,
                        'email' => $employee->email,
                    ],
                ],

                'subject'     => 'Votre nouveau mot de passe',
                "htmlContent" => $tpl->fetch(),
            ];

            $result = Tools::sendEmail($postfields);
			if(!$result) {
				$this->errors[] = Tools::displayError('Impossible de envoyer le mail.');
			}
        }

        if (!count($this->errors)) {
            $this->ajaxDie(json_encode([
                'hasErrors' => false,
                'confirm'   => sprintf($this->l('A new password has been emailed to the given email address, if it wasn\'t done within the last %s minutes before.', 'AdminTab', false, false), Configuration::get('EPH_PASSWD_TIME_BACK')),
            ]));
        } else if (Tools::isSubmit('ajax')) {
            $this->ajaxDie(json_encode(['hasErrors' => true, 'errors' => $this->errors]));
        }

    }

}
