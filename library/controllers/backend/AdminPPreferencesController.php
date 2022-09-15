<?php

/**
 * Class AdminPPreferencesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminPPreferencesControllerCore extends AdminController {

    public $php_self = 'adminpperformance';
    /**
     * AdminPPreferencesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'ppreference';
        $this->publicName = $this->l('Paramètre des Produitss');

        parent::__construct();

        $this->ajaxOptions = $this->generateOptions();
    }

    public function generateOptions() {

        $warehouseList = Warehouse::getWarehouses();
        $warehouseNo = [['id_warehouse' => 0, 'name' => $this->l('No default warehouse (default setting)')]];
        $warehouseList = array_merge($warehouseNo, $warehouseList);
        $tabs = [];
        $this->fields_options = [
            'products' => [
                'title'  => $this->l('Products (general)'),
                'fields' => [
                    'EPH_CATALOG_MODE'                => [
                        'title'      => $this->l('Catalog mode'),
                        'hint'       => $this->l('When active, all shopping features will be disabled.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_COMPARATOR_MAX_ITEM'         => [
                        'title'      => $this->l('Product comparison'),
                        'hint'       => $this->l('Set the maximum number of products that can be selected for comparison. Set to "0" to disable this feature.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'EPH_NB_DAYS_NEW_PRODUCT'         => [
                        'title'      => $this->l('Number of days for which the product is considered \'new\''),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'EPH_CART_REDIRECT'               => [
                        'title'      => $this->l('Redirect after adding product to cart'),
                        'hint'       => $this->l('Only for non-AJAX versions of the cart.'),
                        'cast'       => 'intval',
                        'show'       => true,
                        'required'   => false,
                        'type'       => 'radio',
                        'validation' => 'isBool',
                        'choices'    => [
                            0 => $this->l('Previous page'),
                            1 => $this->l('Cart summary'),
                        ],
                    ],
                    'EPH_PRODUCT_SHORT_DESC_LIMIT'    => [
                        'title'      => $this->l('Max size of short description'),
                        'hint'       => $this->l('Set the maximum size of product short description (in characters).'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('characters'),
                    ],
                    'EPH_QTY_DISCOUNT_ON_COMBINATION' => [
                        'title'      => $this->l('Quantity discounts based on'),
                        'hint'       => $this->l('How to calculate quantity discounts.'),
                        'cast'       => 'intval',
                        'show'       => true,
                        'required'   => false,
                        'type'       => 'radio',
                        'validation' => 'isBool',
                        'choices'    => [
                            0 => $this->l('Products'),
                            1 => $this->l('Combinations'),
                        ],
                    ],
                    'EPH_FORCE_FRIENDLY_PRODUCT'      => [
                        'title'      => $this->l('Force update of friendly URL'),
                        'hint'       => $this->l('When active, friendly URL will be updated on every save.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
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

        $tabs['products'] = [
            'key'     => $this->fields_options['products']['title'],
            'content' => $options,
        ];
        $this->fields_options = [
            'order_by_pagination' => [
                'title'  => $this->l('Pagination'),
                'fields' => [
                    'EPH_PRODUCTS_PER_PAGE'  => [
                        'title'      => $this->l('Products per page'),
                        'hint'       => $this->l('Number of products displayed per page. Default is 10.'),
                        'validation' => 'isUnsignedInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'EPH_PRODUCTS_ORDER_BY'  => [
                        'title'      => $this->l('Default order by'),
                        'hint'       => $this->l('The order in which products are displayed in the product list.'),
                        'type'       => 'select',
                        'list'       => [
                            ['id' => '0', 'name' => $this->l('Product name')],
                            ['id' => '1', 'name' => $this->l('Product price')],
                            ['id' => '2', 'name' => $this->l('Product add date')],
                            ['id' => '3', 'name' => $this->l('Product modified date')],
                            ['id' => '4', 'name' => $this->l('Position inside category')],
                            ['id' => '5', 'name' => $this->l('Manufacturer')],
                            ['id' => '6', 'name' => $this->l('Product quantity')],
                            ['id' => '7', 'name' => $this->l('Product reference')],
                        ],
                        'identifier' => 'id',
                    ],
                    'EPH_PRODUCTS_ORDER_WAY' => [
                        'title'      => $this->l('Default order method'),
                        'hint'       => $this->l('Default order method for product list.'),
                        'type'       => 'select',
                        'list'       => [
                            [
                                'id'   => '0',
                                'name' => $this->l('Ascending'),
                            ],
                            [
                                'id'   => '1',
                                'name' => $this->l('Descending'),
                            ],
                        ],
                        'identifier' => 'id',
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

        $tabs['order_by_pagination'] = [
            'key'     => $this->fields_options['order_by_pagination']['title'],
            'content' => $options,
        ];

        $this->fields_options = [
            'fo_product_page' => [
                'title'  => $this->l('Product page'),
                'fields' => [
                    'EPH_DISPLAY_QTIES'              => [
                        'title'      => $this->l('Display available quantities on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_LAST_QTIES'                 => [
                        'title'      => $this->l('Display remaining quantities when the quantity is lower than'),
                        'hint'       => $this->l('Set to "0" to disable this feature.'),
                        'validation' => 'isUnsignedId',
                        'required'   => true,
                        'cast'       => 'intval',
                        'type'       => 'text',
                    ],
                    'EPH_DISPLAY_JQZOOM'             => [
                        'title'      => $this->l('Enable JqZoom instead of Fancybox on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_DISP_UNAVAILABLE_ATTR'      => [
                        'title'      => $this->l('Display unavailable product attributes on the product page'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_ATTRIBUTE_CATEGORY_DISPLAY' => [
                        'title'      => $this->l('Display the "add to cart" button when a product has attributes'),
                        'hint'       => $this->l('Display or hide the "add to cart" button on category pages for products that have attributes forcing customers to see product details.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_ATTRIBUTE_ANCHOR_SEPARATOR' => [
                        'title'      => $this->l('Separator of attribute anchor on the product links'),
                        'type'       => 'select',
                        'list'       => [
                            ['id' => '-', 'name' => '-'],
                            ['id' => ',', 'name' => ','],
                        ],
                        'identifier' => 'id',
                    ],
                    'EPH_DISPLAY_DISCOUNT_PRICE'     => [
                        'title'      => $this->l('Display discounted price'),
                        'desc'       => $this->l('In the volume discounts board, display the new price with the applied discount instead of showing the discount (ie. "-5%").'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_SHOW_CONDITION'             => [
                        'title'      => $this->l('Show condition'),
                        'hint'       => $this->l('Show/Hide condition on product page.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
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

        $tabs['fo_product_page'] = [
            'key'     => $this->fields_options['fo_product_page']['title'],
            'content' => $options,
        ];

        $this->fields_options = [
            'stock' => [
                'title'  => $this->l('Products stock'),
                'fields' => [
                    'EPH_ORDER_OUT_OF_STOCK'            => [
                        'title'      => $this->l('Allow ordering of out-of-stock products'),
                        'hint'       => $this->l('By default, the Add to Cart button is hidden when a product is unavailable. You can choose to have it displayed in all cases.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_STOCK_MANAGEMENT'              => [
                        'title'      => $this->l('Enable stock management'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                        'js'         => [
                            'on'  => 'onchange="stockManagementActivationAuthorization()"',
                            'off' => 'onchange="stockManagementActivationAuthorization()"',
                        ],
                    ],
                    'EPH_ADVANCED_STOCK_MANAGEMENT'     => [
                        'title'      => $this->l('Enable advanced stock management'),
                        'hint'       => $this->l('Allows you to manage physical stock, warehouses and supply orders in a new Stock menu.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                        'js'         => [
                            'on'  => 'onchange="advancedStockManagementActivationAuthorization()"',
                            'off' => 'onchange="advancedStockManagementActivationAuthorization()"',
                        ],
                    ],
                    'EPH_FORCE_ASM_NEW_PRODUCT'         => [
                        'title'      => $this->l('New products use advanced stock management'),
                        'hint'       => $this->l('New products will automatically use advanced stock management and depends on stock, but no warehouse will be selected'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'required'   => false,
                        'type'       => 'bool',
                    ],
                    'EPH_DEFAULT_WAREHOUSE_NEW_PRODUCT' => [
                        'title'      => $this->l('Default warehouse on new products'),
                        'hint'       => $this->l('Automatically set a default warehouse when new product is created'),
                        'type'       => 'select',
                        'list'       => $warehouseList,
                        'identifier' => 'id_warehouse',
                    ],
                    'EPH_PACK_STOCK_TYPE'               => [
                        'title'      => $this->l('Default pack stock management'),
                        'type'       => 'select',
                        'list'       => [
                            [
                                'pack_stock' => 0,
                                'name'       => $this->l('Decrement pack only.'),
                            ],
                            [
                                'pack_stock' => 1,
                                'name'       => $this->l('Decrement products in pack only.'),
                            ],
                            [
                                'pack_stock' => 2,
                                'name'       => $this->l('Decrement both.'),
                            ],
                        ],
                        'identifier' => 'pack_stock',
                    ],
                ],
                'bottom' => '<script type="text/javascript">stockManagementActivationAuthorization();advancedStockManagementActivationAuthorization();</script>',
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

        $tabs['stock'] = [
            'key'     => $this->fields_options['stock']['title'],
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
