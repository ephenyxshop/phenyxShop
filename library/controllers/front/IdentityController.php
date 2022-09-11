<?php

/**
 * Class IdentityControllerCore
 *
 * @since 1.8.1.0
 */
class IdentityControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'identity';
    /** @var string $authRedirection */
    public $authRedirection = 'identity';
    /** @var bool $ssl */
    public $ssl = true;
    /** @var Customer */
    protected $customer;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize controller
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();
        $this->customer = $this->context->customer;
    }

    /**
     * Start forms process
     *
     * @return Customer
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        parent::postProcess();
        $originNewsletter = (bool) $this->customer->newsletter;

        $_POST = array_map('stripslashes', $this->customer->getFields());

        return $this->customer;
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        $countries = Country::getCountries($this->context->language->id, true);
		$id_address = Address::getFirstCustomerAddressId($this->customer->id);
		$address = new Address((int) $id_address);
        /* Generate years, months and days */
        $this->context->smarty->assign(
            [
                'student'   => $this->customer,
				'address'   => $address,
                'errors'    => $this->errors,
                'genders'   => Gender::getGenders(),
                'countries' => $countries,
            ]
        );

        // Call a hook to display more information

        $newsletter = Configuration::get('PS_CUSTOMER_NWSL') || (Module::isInstalled('blocknewsletter') && Module::getInstanceByName('blocknewsletter')->active);
        $this->context->smarty->assign('newsletter', $newsletter);
        $this->context->smarty->assign('optin', (bool) Configuration::get('PS_CUSTOMER_OPTIN'));

        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        $this->setTemplate(_PS_THEME_DIR_ . 'identity.tpl');
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_ . 'index.css');
        $this->addCSS(_THEME_CSS_DIR_ . 'authentication.css');
        $this->addJS(_THEME_JS_DIR_ . 'identity.js');
        Media::addJsDef([
            'AjaxIdentityLink' => $this->context->link->getPageLink('identity', true),

        ]);
    }

    public function ajaxProcessUpdateStudent() {

        $idCustomer = (int) Tools::getValue('id_customer');
		$idAddress = (int) Tools::getValue('id_address');
		
		$student = new Customer($idCustomer);
		
		if (Validate::isLoadedObject($student)) {
			$oldPasswd = $student->passwd;
            $newsLetter = $student->newsletter;
			foreach ($_POST as $key => $value) {

            	if (property_exists($student, $key) && $key != 'id_customer') {

                	if ($key == 'passwd' && Tools::getValue('id_customer') && empty($value)) {
                            continue;
                    }

                    if ($key == 'passwd' && Tools::getValue('id_customer') && !empty($value)) {
                    	
						$newPasswd = Tools::hash(Tools::getValue('passwd'));
                        if ($newPasswd == $oldPasswd) {
							continue;
                        }
						$value = $newPasswd;
                        $student->password = Tools::getValue('passwd');
                    }

                    if ($key == 'newsletter' && Tools::getValue('id_customer')) {

                    	if ($value == $newsLetter) {
                        	continue;
                        }
                        $student->ip_registration_newsletter = pSQL(Tools::getRemoteAddr());
                        $student->newsletter_date_add = pSQL(date('Y-m-d H:i:s'));
                   	}
					
					$student->{$key}  = $value;
                }
			}

            $result = $student->update();
		}
		
		if($result) {
			
			$address = new Address($idAddress);
			$oldPhone = $address->phone_mobile;
			foreach ($_POST as $key => $value) {

            	if (property_exists($address, $key) && $key != 'id_customer') {

                	if ($key == 'phone_mobile' && Tools::getValue('id_student')) {

                    	if ($value == $oldPhone) {
                        	continue;
                        }
                        $mobile = str_replace(' ', '', Tools::getValue('phone_mobile'));
                        if (strlen($mobile) == 10 && $address->id_country == 8) {
							$value = '+33' . substr($mobile, 1);
                        }
                    }
					$address->{$key}  = $value;
                }
			}

            $result = $address->update();
		}
		
		$result = [
        	'success' => true,
            'message' => $this->l('Vos donnée personelle ont été mise à jour avec succès'),
        ];	
        

        die(Tools::jsonEncode($result));
    }

}
