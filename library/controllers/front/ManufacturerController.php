<?php

/**
 * Class ManufacturerControllerCore
 *
 * @since 1.8.1.0
 */
class ManufacturerControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'manufacturer';
    /** @var Manufacturer */
    protected $manufacturer;
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
     * Initialize this controller
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();

        if ($idManufacturer = Tools::getValue('id_manufacturer')) {
            $this->manufacturer = new Manufacturer((int) $idManufacturer, $this->context->language->id);

            if (!Validate::isLoadedObject($this->manufacturer) || !$this->manufacturer->active || !$this->manufacturer->isAssociatedToShop()) {
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
                $this->errors[] = Tools::displayError('The manufacturer does not exist.');
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

        if (Validate::isLoadedObject($this->manufacturer)) {
            parent::canonicalRedirection($this->context->link->getManufacturerLink($this->manufacturer));
        }

    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        if (Validate::isLoadedObject($this->manufacturer) && $this->manufacturer->active && $this->manufacturer->isAssociatedToShop()) {
            $this->productSort();
            $this->assignOne();
            $this->setTemplate(_EPH_THEME_DIR_ . 'manufacturer.tpl');
        } else {
            $this->assignAll();
            $this->setTemplate(_EPH_THEME_DIR_ . 'manufacturer-list.tpl');
        }

    }

    /**
     * Assign template vars if displaying one manufacturer
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignOne() {

        $this->manufacturer->description = Tools::nl2br(trim($this->manufacturer->description));
        $nbProducts = $this->manufacturer->getProducts($this->manufacturer->id, null, null, null, $this->orderBy, $this->orderWay, true);
        $this->pagination((int) $nbProducts);

        $products = $this->manufacturer->getProducts($this->manufacturer->id, $this->context->language->id, (int) $this->p, (int) $this->n, $this->orderBy, $this->orderWay);
        $this->addColorsToProductList($products);

        $this->context->smarty->assign(
            [
                'nb_products'         => $nbProducts,
                'products'            => $products,
                'path'                => ($this->manufacturer->active ? Tools::safeOutput($this->manufacturer->name) : ''),
                'manufacturer'        => $this->manufacturer,
                'comparator_max_item' => Configuration::get('EPH_COMPARATOR_MAX_ITEM'),
                'body_classes'        => [$this->php_self . '-' . $this->manufacturer->id, $this->php_self . '-' . $this->manufacturer->link_rewrite],
            ]
        );
    }

    /**
     * Assign template vars if displaying the manufacturer list
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignAll() {

        if (Configuration::get('EPH_DISPLAY_SUPPLIERS')) {
            $data = Manufacturer::getManufacturers(false, $this->context->language->id, true, false, false, false);
            $nbProducts = count($data);
            $this->pagination($nbProducts);
            $data = Manufacturer::getManufacturers(true, $this->context->language->id, true, $this->p, $this->n, false);

            foreach ($data as &$item) {
                $item['image'] = (!file_exists(_EPH_MANU_IMG_DIR_ . $item['id_manufacturer'] . '-' . ImageType::getFormatedName('medium') . '.jpg')) ? $this->context->language->iso_code . '-default' : $item['id_manufacturer'];
            }

            $this->context->smarty->assign(
                [
                    'pages_nb'         => ceil($nbProducts / (int) $this->n),
                    'nbManufacturers'  => $nbProducts,
                    'mediumSize'       => Image::getSize(ImageType::getFormatedName('medium')),
                    'manufacturers'    => $data,
                    'add_prod_display' => Configuration::get('EPH_ATTRIBUTE_CATEGORY_DISPLAY'),
                ]
            );
        } else {
            $this->context->smarty->assign('nbManufacturers', 0);
        }

    }

    /**
     * Get instance of current manufacturer
     *
     * @return Manufacturer
     *
     * @since 1.8.1.0
     */
    public function getManufacturer() {

        return $this->manufacturer;
    }

}
