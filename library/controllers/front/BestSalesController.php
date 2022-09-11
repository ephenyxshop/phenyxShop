<?php

/**
 * Class BestSalesControllerCore
 *
 * @since 1.8.1.0
 */
class BestSalesControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'best-sales';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        if (Configuration::get('PS_DISPLAY_BEST_SELLERS')) {
            parent::initContent();

            $this->productSort();
            $nbProducts = (int) ProductSale::getNbSales();
            $this->pagination($nbProducts);

            if (!Tools::getValue('orderby')) {
                $this->orderBy = 'sales';
            }

            $products = ProductSale::getBestSales($this->context->language->id, $this->p - 1, $this->n, $this->orderBy, $this->orderWay);
            $this->addColorsToProductList($products);

            $this->context->smarty->assign(
                [
                    'products'            => $products,
                    'add_prod_display'    => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                    'nbProducts'          => $nbProducts,
                    'homeSize'            => Image::getSize(ImageType::getFormatedName('home')),
                    'comparator_max_item' => Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                ]
            );

            $this->setTemplate(_PS_THEME_DIR_ . 'best-sales.tpl');
        } else {
            Tools::redirect('index.php?controller=404');
        }

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
        $this->addCSS(_THEME_CSS_DIR_ . 'product_list.css');
    }

}
