<?php

/**
 * Class AdminCustomerPreferencesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminCustomerPreferencesControllerCore extends AdminController {

    /**
     * AdminCustomerPreferencesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $registrationProcessType = [
            [
                'value' => EPH_REGISTRATION_PROCESS_STANDARD,
                'name'  => $this->l('Only account creation'),
            ],
            [
                'value' => EPH_REGISTRATION_PROCESS_AIO,
                'name'  => $this->l('Standard (account creation and address creation)'),
            ],
        ];

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'EPH_REGISTRATION_PROCESS_TYPE' => [
                        'title'      => $this->l('Registration process type'),
                        'hint'       => $this->l('The "Only account creation" registration option allows the customer to register faster, and create his/her address later.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $registrationProcessType,
                        'identifier' => 'value',
                    ],
                    'EPH_ONE_PHONE_AT_LEAST'        => [
                        'title'      => $this->l('Phone number is mandatory'),
                        'hint'       => $this->l('If you chose yes, your customer will have to provide at least one phone number to register.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_CART_FOLLOWING'            => [
                        'title'      => $this->l('Re-display cart at login'),
                        'hint'       => $this->l('After a customer logs in, you can recall and display the content of his/her last shopping cart.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_CUSTOMER_CREATION_EMAIL'   => [
                        'title'      => $this->l('Send an email after registration'),
                        'hint'       => $this->l('Send an email with summary of the account information (email, password) after registration.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_PASSWD_TIME_FRONT'         => [
                        'title'      => $this->l('Password reset delay'),
                        'hint'       => $this->l('Minimum time required between two requests for a password reset.'),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'size'       => 5,
                        'type'       => 'text',
                        'suffix'     => $this->l('minutes'),
                    ],
                    'EPH_B2B_ENABLE'                => [
                        'title'      => $this->l('Enable B2B mode'),
                        'hint'       => $this->l('Activate or deactivate B2B mode. When this option is enabled, B2B features will be made available.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_CUSTOMER_NWSL'             => [
                        'title'      => $this->l('Enable newsletter registration'),
                        'hint'       => $this->l('Display or not the newsletter registration tick box.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_CUSTOMER_OPTIN'            => [
                        'title'      => $this->l('Enable opt-in'),
                        'hint'       => $this->l('Display or not the opt-in tick box, to receive offers from the store\'s partners.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * Update EPH_B2B_ENABLE and enables / disables the associated tabs
     *
     * @param int $value Value of option
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsB2bEnable($value) {

        $value = (int) $value;

        $tabsClassName = ['AdminOutstanding'];

        if (!empty($tabsClassName)) {

            foreach ($tabsClassName as $tabClassName) {
                $tab = EmployeeMenu::getInstanceFromClassName($tabClassName);

                if (Validate::isLoadedObject($tab)) {
                    $tab->active = $value;
                    $tab->save();
                }

            }

        }

        Configuration::updateValue('EPH_B2B_ENABLE', $value);
    }

}
