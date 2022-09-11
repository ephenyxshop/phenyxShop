<?php

/**
 * Class AdminOrderPreferencesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminOrderPreferencesControllerCore extends AdminController {

    /**
     * AdminOrderPreferencesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'opreference';
        $this->publicName = $this->l('Paramètre des Commandes');

        parent::__construct();

        if (!Configuration::get('EPH_ALLOW_MULTISHIPPING')) {
            unset($this->fields_options['general']['fields']['EPH_ALLOW_MULTISHIPPING']);
        }

        if (Configuration::get('EPH_ATCP_SHIPWRAP')) {
            unset($this->fields_options['gift']['fields']['EPH_GIFT_WRAPPING_TAX_RULES_GROUP']);
        }

        $this->ajaxOptions = $this->generateOptions();
    }

    public function generateOptions() {

        $cmsTab = [
            0 => [
                'id'   => 0,
                'name' => $this->l('None'),
            ],
        ];

        foreach (CMS::listCms($this->context->language->id) as $cmsFile) {
            $cmsTab[] = ['id' => $cmsFile['id_cms'], 'name' => $cmsFile['meta_title']];
        }

        // List of order process types
        $orderProcessType = [
            [
                'value' => EPH_ORDER_PROCESS_STANDARD,
                'name'  => $this->l('Standard (Five steps)'),
            ],
            [
                'value' => EPH_ORDER_PROCESS_OPC,
                'name'  => $this->l('One-page checkout'),
            ],
        ];
        $tabs = [];
        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'EPH_ORDER_PROCESS_TYPE'          => [
                        'title'      => $this->l('Order process type'),
                        'hint'       => $this->l('Please choose either the five-step or one-page checkout process.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => $orderProcessType,
                        'identifier' => 'value',
                    ],
                    'EPH_GUEST_CHECKOUT_ENABLED'      => [
                        'title'      => $this->l('Enable guest checkout'),
                        'hint'       => $this->l('Allow guest visitors to place an order without registering.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_DISALLOW_HISTORY_REORDERING' => [
                        'title'      => $this->l('Disable Reordering Option'),
                        'hint'       => $this->l('Disable the option to allow customers to reorder in one click from the order history page (required in some European countries).'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_PURCHASE_MINIMUM'            => [
                        'title'      => $this->l('Minimum purchase total required in order to validate the order'),
                        'hint'       => $this->l('Set to 0 to disable this feature.'),
                        'validation' => 'isFloat',
                        'cast'       => 'floatval',
                        'type'       => 'price',
                    ],
                    'EPH_ALLOW_MULTISHIPPING'         => [
                        'title'      => $this->l('Allow multishipping'),
                        'hint'       => $this->l('Allow the customer to ship orders to multiple addresses. This option will convert the customer\'s cart into one or more orders.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_SHIP_WHEN_AVAILABLE'         => [
                        'title'      => $this->l('Delayed shipping'),
                        'hint'       => $this->l('Allows you to delay shipping at your customers\' request. '),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_CONDITIONS'                  => [
                        'title'      => $this->l('Terms of service'),
                        'hint'       => $this->l('Require customers to accept or decline terms of service before processing an order.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'js'         => [
                            'on'  => 'onchange="changeCMSActivationAuthorization()"',
                            'off' => 'onchange="changeCMSActivationAuthorization()"',
                        ],
                    ],
                    'EPH_CONDITIONS_CMS_ID'           => [
                        'title'      => $this->l('CMS page for the Conditions of use'),
                        'hint'       => $this->l('Choose the CMS page which contains your store\'s conditions of use.'),
                        'validation' => 'isInt',
                        'type'       => 'select',
                        'list'       => $cmsTab,
                        'identifier' => 'id',
                        'cast'       => 'intval',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],

        ];

        $helper = new HelperOptions();
        $this->setHelperDisplay($helper);
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = [
            'save' => [
                'href' => '#',
                'desc' => $this->l('Save'),
            ],
        ];
        $helper->id = $this->id;
        $helper->tpl_vars = $this->tpl_option_vars;
        $options = $helper->generateOptions($this->fields_options);

        $tabs['general'] = [
            'key'     => $this->fields_options['general']['title'],
            'content' => $options,
        ];
        $this->fields_options = [
            'gift' => [
                'title'  => $this->l('Gift options'),
                'icon'   => 'icon-gift',
                'fields' => [
                    'EPH_GIFT_WRAPPING'                 => [
                        'title'      => $this->l('Offer gift wrapping'),
                        'hint'       => $this->l('Suggest gift-wrapping to customers.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_GIFT_WRAPPING_PRICE'           => [
                        'title'      => $this->l('Gift-wrapping price'),
                        'hint'       => $this->l('Set a price for gift wrapping.'),
                        'validation' => 'isPrice',
                        'cast'       => 'floatval',
                        'type'       => 'price',
                    ],
                    'EPH_GIFT_WRAPPING_TAX_RULES_GROUP' => [
                        'title'      => $this->l('Gift-wrapping tax'),
                        'hint'       => $this->l('Set a tax for gift wrapping.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'select',
                        'list'       => array_merge([['id_tax_rules_group' => 0, 'name' => $this->l('None')]], TaxRulesGroup::getTaxRulesGroups(true)),
                        'identifier' => 'id_tax_rules_group',
                    ],
                    'EPH_RECYCLABLE_PACK'               => [
                        'title'      => $this->l('Offer recycled packaging'),
                        'hint'       => $this->l('Suggest recycled packaging to customer.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],

        ];
        $helper = new HelperOptions();
        $this->setHelperDisplay($helper);
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = [
            'save' => [
                'href' => '#',
                'desc' => $this->l('Save'),
            ],
        ];
        $helper->id = $this->id;
        $helper->tpl_vars = $this->tpl_option_vars;
        $options = $helper->generateOptions($this->fields_options);

        $tabs['gift'] = [
            'key'     => $this->fields_options['gift']['title'],
            'content' => $options,
        ];

        return $tabs;

    }

    public function ajaxProcessUpdateConfigurationOptions() {

        foreach ($_POST as $key => $value) {

            if ($key == 'action' || $key == 'ajax') {

                continue;
            }

            Configuration::updateValue($key, $value);

        }

        $result = [
            "success" => true,
            "message" => "Les options ont été mises à jour avec succès",
        ];

        die(Tools::jsonEncode($result));
    }

}
