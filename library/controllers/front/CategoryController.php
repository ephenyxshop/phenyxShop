<?php

/**
 * Class CategoryControllerCore
 *
 * @since 1.8.1.0
 */
class CategoryControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** string Internal controller name */
    public $php_self = 'category';
    /** @var bool If set to false, customer cannot view the current category. */
    public $customer_access = true;
    /** @var Category Current category object */
    protected $category;
    /** @var int Number of products in the current page. */
    protected $nbProducts;
    /** @var array Products to be displayed in the current page . */
    protected $cat_products;
    // @codingStandardsIgnoreEnd

    /**
     * Sets default media for this controller
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();

        if (!$this->useMobileTheme()) {
            //TODO : check why cluetip css is include without js file
            $this->addCSS(
                [
                    _THEME_CSS_DIR_ . 'scenes.css'       => 'all',
                    _THEME_CSS_DIR_ . 'category.css'     => 'all',
                    _THEME_CSS_DIR_ . 'product_list.css' => 'all',
                ]
            );
        }

        $this->addJS(_THEME_JS_DIR_ . 'category.js');
    }

    /**
     * Redirects to canonical or "Not Found" URL
     *
     * @param string $canonicalUrl
     *
     * @since 1.8.1.0
     */
    public function canonicalRedirection($canonicalUrl = '') {

        if (Tools::getValue('live_edit')) {
            return;
        }

        if (!Validate::isLoadedObject($this->category) || in_array($this->category->id, [Configuration::get('EPH_HOME_CATEGORY'), Configuration::get('EPH_ROOT_CATEGORY')])) {
            $this->redirect_after = '404';
            $this->redirect();
        }

        if (!Tools::getValue('noredirect') && Validate::isLoadedObject($this->category)) {
            parent::canonicalRedirection($this->context->link->getCategoryLink($this->category));
        }

    }

    /**
     * Initializes controller
     *
     * @see   FrontController::init()
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     */
    public function init() {

        // Get category ID
        $idCategory = (int) Tools::getValue('id_category');

        if (!$idCategory || !Validate::isUnsignedId($idCategory)) {
            $this->errors[] = Tools::displayError('Missing category ID');
        }

        // Instantiate category
        $this->category = new Category($idCategory, $this->context->language->id);

        parent::init();

        // Check if the category is active and return 404 error if is disable.

        if (!$this->category->active) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }

        // Check if category can be accessible by current customer and return 403 if not

        if (!$this->category->checkAccess($this->context->customer->id)) {
            header('HTTP/1.1 403 Forbidden');
            header('Status: 403 Forbidden');
            $this->errors[] = Tools::displayError('You do not have access to this category.');
            $this->customer_access = false;
        }

    }

    /**
     * Initializes page content variables
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        $description = $this->category->description;
        parent::initContent();

        if (Module::isInstalled('jscomposer') && (bool) Module::isEnabled('jscomposer')) {
            $this->context->smarty->assign(
                [
                    'description_short' => JsComposer::do_shortcode($description),
                ]
            );
            $this->category->description = JsComposer::do_shortcode($description);
        }

        $this->setTemplate(_EPH_THEME_DIR_ . 'category.tpl');

        if (!$this->customer_access) {
            return;
        }

        if (isset($this->context->cookie->id_compare)) {
            $this->context->smarty->assign('compareProducts', CompareProduct::getCompareProducts((int) $this->context->cookie->id_compare));
        }

        // Product sort must be called before assignProductList()
        $this->productSort();

        $this->assignSubcategories();
        $this->assignProductList();

        $this->context->smarty->assign(
            [
                'category'             => $this->category,
                'description_short'    => Tools::truncateString($this->category->description, 350),
                'products'             => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
                'id_category'          => (int) $this->category->id,
                'id_category_parent'   => (int) $this->category->id_parent,
                'return_category_name' => Tools::safeOutput($this->category->name),
                'path'                 => Tools::getPath($this->category->id),
                'add_prod_display'     => Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                'categorySize'         => Image::getSize(ImageType::getFormatedName('category')),
                'mediumSize'           => Image::getSize(ImageType::getFormatedName('medium')),
                'thumbSceneSize'       => Image::getSize(ImageType::getFormatedName('m_scene')),
                'homeSize'             => Image::getSize(ImageType::getFormatedName('home')),
                'allow_oosp'           => (int) Configuration::get('EPH_ORDER_OUT_OF_STOCK'),
                'comparator_max_item'  => (int) Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
                'body_classes'         => [$this->php_self . '-' . $this->category->id, $this->php_self . '-' . $this->category->link_rewrite],
            ]
        );
    }

    /**
     * Assigns subcategory templates variables
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignSubcategories() {

        if ($subCategories = $this->category->getSubCategories($this->context->language->id)) {
            $this->context->smarty->assign(
                [
                    'subcategories'          => $subCategories,
                    'subcategories_nb_total' => count($subCategories),
                    'subcategories_nb_half'  => ceil(count($subCategories) / 2),
                ]
            );
        }

    }

    /**
     * Assigns product list template variables
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function assignProductList() {

        $hookExecuted = false;
        Hook::exec(
            'actionProductListOverride',
            [
                'nbProducts'   => &$this->nbProducts,
                'catProducts'  => &$this->cat_products,
                'hookExecuted' => &$hookExecuted,
            ]
        );

        // The hook was not executed, standard working

        if (!$hookExecuted) {
            $this->context->smarty->assign('categoryNameComplement', '');
            $this->nbProducts = $this->category->getProducts(null, null, null, $this->orderBy, $this->orderWay, true);
            $this->pagination((int) $this->nbProducts); // Pagination must be call after "getProducts"
            $this->cat_products = $this->category->getProducts($this->context->language->id, (int) $this->p, (int) $this->n, $this->orderBy, $this->orderWay);
        }
        // Hook executed, use the override
        else {
            // Pagination must be call after "getProducts"
            $this->pagination($this->nbProducts);
        }

        $this->addColorsToProductList($this->cat_products);

        Hook::exec(
            'actionProductListModifier',
            [
                'nb_products'  => &$this->nbProducts,
                'cat_products' => &$this->cat_products,
            ]
        );

        foreach ($this->cat_products as &$product) {

            if (isset($product['id_product_attribute']) && $product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity'])) {
                $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
            }

        }

        $this->context->smarty->assign('nb_products', $this->nbProducts);
    }

    /**
     * Returns an instance of the current category
     *
     * @return Category
     *
     * @since 1.8.1.0
     */
    public function getCategory() {

        return $this->category;
    }

}
