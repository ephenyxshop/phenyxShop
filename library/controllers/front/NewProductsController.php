<?php

/**
 * Class NewProductsControllerCore
 *
 * @since 1.8.1.0
 */
class NewProductsControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'new-products';
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

        $this->productSort();

        // Override default configuration values: cause the new products page must display latest products first.

        if (!Tools::getIsset('orderway') || !Tools::getIsset('orderby')) {
            $this->orderBy = 'date_add';
            $this->orderWay = 'DESC';
        }

        $nbProducts = (int) Product::getNewProducts(
            $this->context->language->id,
            (isset($this->p) ? (int) $this->p - 1 : null),
            (isset($this->n) ? (int) $this->n : null),
            true
        );

        $this->pagination($nbProducts);

        $products = Product::getNewProducts($this->context->language->id, (int) $this->p - 1, (int) $this->n, false, $this->orderBy, $this->orderWay);
        $this->addColorsToProductList($products);

        $this->context->smarty->assign(
            [
                'products'            => $products,
                'add_prod_display'    => Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                'nbProducts'          => (int) $nbProducts,
                'homeSize'            => Image::getSize(ImageType::getFormatedName('home')),
                'comparator_max_item' => Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'new-products.tpl');
    }

}
