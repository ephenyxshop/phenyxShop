<?php

/**
 * Class PricesDropControllerCore
 *
 * @since 1.8.1.0
 */
class PricesDropControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'prices-drop';
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
        $nbProducts = Product::getPricesDrop($this->context->language->id, null, null, true);
        $this->pagination($nbProducts);

        $products = Product::getPricesDrop($this->context->language->id, (int) $this->p - 1, (int) $this->n, false, $this->orderBy, $this->orderWay);
        $this->addColorsToProductList($products);

        $this->context->smarty->assign(
            [
                'products'            => $products,
                'add_prod_display'    => Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                'nbProducts'          => $nbProducts,
                'homeSize'            => Image::getSize(ImageType::getFormatedName('home')),
                'comparator_max_item' => Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'prices-drop.tpl');
    }
}
