<?php

/**
 * Class SupplierControllerCore
 *
 * @since 1.8.1.0
 */
class SupplierControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'supplier';
    /** @var Supplier $supplier */
    protected $supplier;
    // @codingStandardsIgnoreEnd

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_ . 'product_list.css');
    }

    /**
     * Initialize supplier controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();

        if ($idSupplier = (int) Tools::getValue('id_supplier')) {
            $this->supplier = new Supplier($idSupplier, $this->context->language->id);

            if (!Validate::isLoadedObject($this->supplier) || !$this->supplier->active) {
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
                $this->errors[] = Tools::displayError('The chosen supplier does not exist.');
            } else {
                $this->canonicalRedirection();
            }

        }

    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalURL
     *
     * @since 1.8.1.0
     */
    public function canonicalRedirection($canonicalURL = '') {

        if (Tools::getValue('live_edit')) {
            return;
        }

        if (Validate::isLoadedObject($this->supplier)) {
            parent::canonicalRedirection($this->context->link->getSupplierLink($this->supplier));
        }

    }

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        if (Validate::isLoadedObject($this->supplier) && $this->supplier->active && $this->supplier->isAssociatedToShop()) {
            $this->productSort(); // productSort must be called before assignOne
            $this->assignOne();
            $this->setTemplate(_EPH_THEME_DIR_ . 'supplier.tpl');
        } else {
            $this->assignAll();
            $this->setTemplate(_EPH_THEME_DIR_ . 'supplier-list.tpl');
        }

    }

    /**
     * Get instance of current supplier
     *
     * @return Supplier
     *
     * @since 1.8.1.0
     */
    public function getSupplier() {

        return $this->supplier;
    }

    /**
     * Assign template vars if displaying one supplier
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignOne() {

        if (Configuration::get('EPH_DISPLAY_SUPPLIERS')) {
            $this->supplier->description = Tools::nl2br(trim($this->supplier->description));
            $nbProducts = $this->supplier->getProducts($this->supplier->id, null, null, null, $this->orderBy, $this->orderWay, true);
            $this->pagination((int) $nbProducts);

            $products = $this->supplier->getProducts($this->supplier->id, $this->context->cookie->id_lang, (int) $this->p, (int) $this->n, $this->orderBy, $this->orderWay);
            $this->addColorsToProductList($products);

            $this->context->smarty->assign(
                [
                    'nb_products'         => $nbProducts,
                    'products'            => $products,
                    'path'                => ($this->supplier->active ? Tools::safeOutput($this->supplier->name) : ''),
                    'supplier'            => $this->supplier,
                    'comparator_max_item' => Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
                    'body_classes'        => [
                        $this->php_self . '-' . $this->supplier->id,
                        $this->php_self . '-' . $this->supplier->link_rewrite,
                    ],
                ]
            );
        } else {
            Tools::redirect('index.php?controller=404');
        }

    }

    /**
     * Assign template vars if displaying the supplier list
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignAll() {

        if (Configuration::get('EPH_DISPLAY_SUPPLIERS')) {
            $result = Supplier::getSuppliers(true, $this->context->language->id, true);
            $nbProducts = count($result);
            $this->pagination($nbProducts);

            $suppliers = Supplier::getSuppliers(true, $this->context->language->id, true, $this->p, $this->n);

            foreach ($suppliers as &$row) {
                $row['image'] = (!file_exists(_EPH_SUPP_IMG_DIR_ . '/' . $row['id_supplier'] . '-' . ImageType::getFormatedName('medium') . '.jpg')) ? $this->context->language->iso_code . '-default' : $row['id_supplier'];
            }

            $this->context->smarty->assign(
                [
                    'pages_nb'         => ceil($nbProducts / (int) $this->n),
                    'nbSuppliers'      => $nbProducts,
                    'mediumSize'       => Image::getSize(ImageType::getFormatedName('medium')),
                    'suppliers_list'   => $suppliers,
                    'add_prod_display' => Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                ]
            );
        } else {
            Tools::redirect('index.php?controller=404');
        }

    }

}
