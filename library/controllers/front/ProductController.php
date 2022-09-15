<?php


/**
 * Class ProductControllerCore
 *
 * @since 1.8.1.0
 */
class ProductControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    public $php_self = 'product';
    /** @var Product */
    protected $product;
    /** @var Category */
    protected $category;
    // @codingStandardsIgnoreEnd
	
	public $configuration_keys = array(
        'HSMA_DISPLAY_STYLE' => 'isInt',
        'HSMA_SHOW_IMAGES' => 'isInt',
        'HSMA_SHOW_SHORT_DESCRIPTION' => 'isInt',
        'HSMA_SHOW_PRICE' => 'isInt',
        'HSMA_SHOW_COMBINATION' => 'isInt',
        'HSMA_SHOW_PRICE_TABLE' => 'isInt',
        'HSMA_TITLE' => 'isString',
        'HSMA_MESSAGE_AVAILABLE_LATER' => 'isString',
        'HSMA_EACH_ACCESSORY_TO_BASKET' => 'isInt',
        'HSMA_OPEN_ACCESSORIES_IN_NEW_TAB' => 'isInt',
        'HSMA_BUY_ACCESSORY_MAIN_TOGETHER' => 'isInt',
        'HSMA_SHOW_TOTAL_PRICE' => 'isInt',
        'HSMA_ALERT_MESSAGE' => 'isString',
        'HSMA_SHOW_CUSTOM_QUANTITY' => 'isInt',
        'HSMA_ALLOW_CUSTOMER_CHANGE_QTY' => 'isInt',
        'HSMA_CHANGE_MAIN_PRICE' => 'isInt',
        'HSMA_APPLY_FANCYBOX_TO_IMAGE' => 'isInt',
        'HSMA_IMAGE_SIZE_IN_FANCYBOX' => 'isString',
        'HSMA_SHOW_ACCESSORIES_OFS' => 'isInt',
        'HSMA_SHOW_ICON_OUT_OF_STOCK' => 'isInt',
        'HSMA_COLLAPSE_EXPAND_GROUPS' => 'isInt',
    );

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia()
    {
        parent::setMedia();
        if (is_array($this->errors) && count($this->errors)) {
            return;
        }
		
		

        if (!$this->useMobileTheme()) {
            $this->addCSS(_THEME_CSS_DIR_.'product.css');
            $this->addCSS(_THEME_CSS_DIR_.'print.css', 'print');
            $this->addJqueryPlugin(['fancybox', 'idTabs', 'scrollTo', 'serialScroll', 'bxslider']);
            $this->addJS(
                [
                    _THEME_JS_DIR_.'tools.js',  // retro compat themes 1.5
                    _THEME_JS_DIR_.'product.js',
                ]
            );
        } else {
            $this->addJqueryPlugin(['scrollTo', 'serialScroll']);
            $this->addJS(
                [
                    _THEME_JS_DIR_.'tools.js',  // retro compat themes 1.5
                    _THEME_MOBILE_JS_DIR_.'product.js',
                    _THEME_MOBILE_JS_DIR_.'jquery.touch-gallery.js',
					_THEME_JS_DIR_.'accessories/multi_accessories.js',  
					_THEME_JS_DIR_.'accessories/accessoriesprice_16.js', 
					_THEME_JS_DIR_.'accessories/hsma_display_style.js',  
					_THEME_JS_DIR_.'accessories/admin_product_setting.js',   
					_THEME_JS_DIR_.'accessories/pricetable.js',  
					_THEME_JS_DIR_.'accessories/format_string.js',   
					_THEME_JS_DIR_.'accessories/jquery.ddslick.js',   
					_THEME_JS_DIR_.'accessories/jquery.visible.js',  
					_THEME_JS_DIR_.'accessories/hsma_render_accessories.js',   
                ]
            );
        }
		

        if (Configuration::get('EPH_DISPLAY_JQZOOM') == 1) {
            $this->addJqueryPlugin('jqzoom');
        }
    }
	
	protected function isEnableBlockCartAjax()
    {
        if (Configuration::get('EPH_BLOCK_CART_AJAX')) {
        	return true;
        }
        return false;
    }

    /**
     * Initialize product controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init()
    {
        parent::init();

        if ($idProduct = (int) Tools::getValue('id_product')) {
            $this->product = new Product($idProduct, true, $this->context->language->id, $this->context->company->id);
        }

        if (!Validate::isLoadedObject($this->product)) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            $this->errors[] = Tools::displayError('Product not found');
        } else {
            $this->canonicalRedirection();
            /*
             * If the product is associated to the shop
             * and is active or not active but preview mode (need token + file_exists)
             * allow showing the product
             * In all the others cases => 404 "Product is no longer available"
             */
            if ( !$this->product->active) {
                if (Tools::getValue('adtoken') == Tools::getAdminToken('AdminProducts'.(int) Tab::getIdFromClassName('AdminProducts').(int) Tools::getValue('id_employee')) ) {
                    // If the product is not active, it's the admin preview mode
                    $this->context->smarty->assign('adminActionDisplay', true);
                } else {
                    $this->context->smarty->assign('adminActionDisplay', false);
                    if (!$this->product->id_product_redirected || $this->product->id_product_redirected == $this->product->id) {
                        $this->product->redirect_type = '404';
                    }

                    switch ($this->product->redirect_type) {
                        case '301':
                            header('HTTP/1.1 301 Moved Permanently');
                            header('Location: '.$this->context->link->getProductLink($this->product->id_product_redirected));
                            exit;
                            break;
                        case '302':
                            header('HTTP/1.1 302 Moved Temporarily');
                            header('Cache-Control: no-cache');
                            header('Location: '.$this->context->link->getProductLink($this->product->id_product_redirected));
                            exit;
                            break;
                        case '404':
                        default:
                            header('HTTP/1.1 404 Not Found');
                            header('Status: 404 Not Found');
                            $this->errors[] = Tools::displayError('This product is no longer available.');
                            break;
                    }
                }
            } elseif (!$this->product->checkAccess(isset($this->context->customer->id) && $this->context->customer->id ? (int) $this->context->customer->id : 0)) {
                header('HTTP/1.1 403 Forbidden');
                header('Status: 403 Forbidden');
                $this->errors[] = Tools::displayError('You do not have access to this product.');
            } else {
                // Load category
                $idCategory = false;
                if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] == Tools::secureReferrer($_SERVER['HTTP_REFERER']) // Assure us the previous page was one of the shop
                    && preg_match('~^.*(?<!\/content)\/([0-9]+)\-(.*[^\.])|(.*)id_(category|product)=([0-9]+)(.*)$~', $_SERVER['HTTP_REFERER'], $regs)
                ) {
                    // If the previous page was a category and is a parent category of the product use this category as parent category
                    $idObject = false;
                    if (isset($regs[1]) && is_numeric($regs[1])) {
                        $idObject = (int) $regs[1];
                    } elseif (isset($regs[5]) && is_numeric($regs[5])) {
                        $idObject = (int) $regs[5];
                    }
                    if ($idObject) {
                        $referers = [$_SERVER['HTTP_REFERER'], urldecode($_SERVER['HTTP_REFERER'])];
                        if (in_array($this->context->link->getCategoryLink($idObject), $referers)) {
                            $idCategory = (int) $idObject;
                        } elseif (isset($this->context->cookie->last_visited_category) && (int) $this->context->cookie->last_visited_category && in_array($this->context->link->getProductLink($idObject), $referers)) {
                            $idCategory = (int) $this->context->cookie->last_visited_category;
                        }
                    }
                }
                if (!$idCategory || !Category::inShopStatic($idCategory, $this->context->company) || !Product::idIsOnCategoryId((int) $this->product->id, ['0' => ['id_category' => $idCategory]])) {
                    $idCategory = (int) $this->product->id_category_default;
                }
                $this->category = new Category((int) $idCategory, (int) $this->context->cookie->id_lang);
                if (isset($this->context->cookie) && isset($this->category->id_category) && !(Module::isInstalled('blockcategories') && Module::isEnabled('blockcategories'))) {
                    $this->context->cookie->last_visited_category = (int) $this->category->id_category;
                }
            }
        }
    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalUrl
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function canonicalRedirection($canonicalUrl = '')
    {
        if (Tools::getValue('live_edit')) {
            return;
        }
        if (Validate::isLoadedObject($this->product)) {
            parent::canonicalRedirection($this->context->link->getProductLink($this->product));
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
    public function initContent()
    {
        parent::initContent();

        if (!$this->errors) {
            if (Pack::isPack((int) $this->product->id) && !Pack::isInStock((int) $this->product->id)) {
                $this->product->quantity = 0;
            }

            $this->product->description = $this->transformDescriptionWithImg($this->product->description);
			if (Module::isInstalled('jscomposer') && (bool) Module::isEnabled('jscomposer')) {
                   $this->product->description = JsComposer::do_shortcode( $this->product->description );
            }
			$this->product->description = $this->recurseShortCode( $this->product->description );

            // Assign to the template the id of the virtual product. "0" if the product is not downloadable.
            $this->context->smarty->assign('virtual', ProductDownload::getIdFromIdProduct((int) $this->product->id));

            $this->context->smarty->assign('customizationFormTarget', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

            if (Tools::isSubmit('submitCustomizedDatas')) {
                // If cart has not been saved, we need to do it so that customization fields can have an id_cart
                // We check that the cookie exists first to avoid ghost carts
                if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                    $this->context->cart->add();
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
                $this->pictureUpload();
                $this->textRecord();
                $this->formTargetFormat();
            } elseif (Tools::getIsset('deletePicture') && !$this->context->cart->deleteCustomizationToProduct($this->product->id, Tools::getValue('deletePicture'))) {
                $this->errors[] = Tools::displayError('An error occurred while deleting the selected picture.');
            }

            $pictures = [];
            $textFields = [];
            if ($this->product->customizable) {
                $files = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_FILE, true);
                foreach ($files as $file) {
                    $pictures['pictures_'.$this->product->id.'_'.$file['index']] = $file['value'];
                }

                $texts = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_TEXTFIELD, true);

                foreach ($texts as $text_field) {
                    $textFields['textFields_'.$this->product->id.'_'.$text_field['index']] = str_replace('<br />', "\n", $text_field['value']);
                }
            }

            $this->context->smarty->assign(
                [
                    'pictures'   => $pictures,
                    'textFields' => $textFields,
                ]
            );

            $this->product->customization_required = false;
            $customizationFields = $this->product->customizable ? $this->product->getCustomizationFields($this->context->language->id) : false;
            if (is_array($customizationFields)) {
                foreach ($customizationFields as $customizationField) {
                    if ($this->product->customization_required = $customizationField['required']) {
                        break;
                    }
                }
            }

            // Assign template vars related to the category + execute hooks related to the category
            $this->assignCategory();
            // Assign template vars related to the price and tax
            $this->assignPriceAndTax();

            // Assign template vars related to the images
            $this->assignImages();
            // Assign attribute groups to the template
            $this->assignAttributesGroups();

            // Assign attributes combinations to the template
            $this->assignAttributesCombinations();
			
			//$this->assignAccessories();

            // Pack management
            $packItems = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];
            $this->context->smarty->assign('packItems', $packItems);
            $this->context->smarty->assign('packs', Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1));

            if (isset($this->category->id) && $this->category->id) {
                $returnLink = Tools::safeOutput($this->context->link->getCategoryLink($this->category));
            } else {
                $returnLink = 'javascript: history.back();';
            }

            $accessories = $this->product->getAccessories($this->context->language->id);
            if ($this->product->cache_is_pack || is_array($accessories) && ($accessories)) {
                $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
            }
            if ($this->product->customizable) {
                $customizationDatas = $this->context->cart->getProductCustomization($this->product->id, null, true);
            }

            $this->context->smarty->assign(
                [
                    'stock_management'         => Configuration::get('EPH_STOCK_MANAGEMENT'),
                    'customizationFields'      => $customizationFields,
                    'id_customization'         => empty($customizationDatas) ? null : $customizationDatas[0]['id_customization'],
                    'accessories'              => $accessories,
                    'return_link'              => $returnLink,
                    'product'                  => $this->product,
                    'product_manufacturer'     => new Manufacturer((int) $this->product->id_manufacturer, $this->context->language->id),
                    'token'                    => Tools::getToken(false),
                    'features'                 => $this->product->getFrontFeatures($this->context->language->id),
                    'attachments'              => (($this->product->cache_has_attachments) ? $this->product->getAttachments($this->context->language->id) : []),
                    'allow_oosp'               => $this->product->isAvailableWhenOutOfStock((int) $this->product->out_of_stock),
                    'last_qties'               => (int) Configuration::get('EPH_LAST_QTIES'),
                    'HOOK_EXTRA_LEFT'          => Hook::exec('displayLeftColumnProduct'),
                    'HOOK_EXTRA_RIGHT'         => Hook::exec('displayRightColumnProduct'),
                    'HOOK_PRODUCT_OOS'         => Hook::exec('actionProductOutOfStock', ['product' => $this->product]),
                    'HOOK_PRODUCT_ACTIONS'     => Hook::exec('displayProductButtons', ['product' => $this->product]),
                    'HOOK_PRODUCT_TAB'         => Hook::exec('displayProductTab', ['product' => $this->product]),
                    'HOOK_PRODUCT_TAB_CONTENT' => Hook::exec('displayProductTabContent', ['product' => $this->product]),
                    'HOOK_PRODUCT_CONTENT'     => Hook::exec('displayProductContent', ['product' => $this->product]),
                    'display_qties'            => (int) Configuration::get('EPH_DISPLAY_QTIES'),
                    'display_ht'               => !Tax::excludeTaxeOption(),
                    'jqZoomEnabled'            => Configuration::get('EPH_DISPLAY_JQZOOM'),
                    'ENT_NOQUOTES'             => ENT_NOQUOTES,
                    'outOfStockAllowed'        => (int) Configuration::get('EPH_ORDER_OUT_OF_STOCK'),
                    'errors'                   => $this->errors,
                    'body_classes'             => [
                        $this->php_self.'-'.$this->product->id,
                        $this->php_self.'-'.$this->product->link_rewrite,
                        'category-'.(isset($this->category) ? $this->category->id : ''),
                        'category-'.(isset($this->category) ? $this->category->getFieldByLang('link_rewrite') : ''),
                    ],
                    'display_discount_price'   => Configuration::get('EPH_DISPLAY_DISCOUNT_PRICE'),
                    'show_condition'           => Configuration::get('EPH_SHOW_CONDITION'),
                ]
            );
        }
        $this->setTemplate(_EPH_THEME_DIR_.'product.tpl');
    }

    /**
     * Transform description w/ image
     *
     * @param string $desc
     *
     * @return string
     *
     * @since 1.8.1.0
     */
    protected function transformDescriptionWithImg($desc)
    {
        $reg = '/\[img\-([0-9]+)\-(left|right)\-([a-zA-Z0-9-_]+)\]/';
        while (preg_match($reg, $desc, $matches)) {
            $linkLmg = $this->context->link->getImageLink($this->product->link_rewrite, $this->product->id.'-'.$matches[1], $matches[3]);
            $class = $matches[2] == 'left' ? 'class="imageFloatLeft"' : 'class="imageFloatRight"';
            $htmlImg = '<img src="'.$linkLmg.'" alt="" '.$class.'/>';
            $desc = str_replace($matches[0], $htmlImg, $desc);
        }

        return $desc;
    }

    /**
     * Picture upload
     *
     * @return bool
     *
     * @since 1.8.1.0
     */
    protected function pictureUpload()
    {
        if (!$fieldIds = $this->product->getCustomizationFieldIds()) {
            return false;
        }
        $authorizedFileFields = [];
        foreach ($fieldIds as $fieldId) {
            if ($fieldId['type'] == Product::CUSTOMIZE_FILE) {
                $authorizedFileFields[(int) $fieldId['id_customization_field']] = 'file'.(int) $fieldId['id_customization_field'];
            }
        }
        $indexes = array_flip($authorizedFileFields);
        foreach ($_FILES as $fieldName => $file) {
            if (in_array($fieldName, $authorizedFileFields) && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                $fileName = md5(uniqid(rand(), true));
                if ($error = ImageManager::validateUpload($file, (int) Configuration::get('EPH_PRODUCT_PICTURE_MAX_SIZE'))) {
                    $this->errors[] = $error;
                }

                $productPictureWidth = (int) Configuration::get('EPH_PRODUCT_PICTURE_WIDTH');
                $productPictureHeight = (int) Configuration::get('EPH_PRODUCT_PICTURE_HEIGHT');
                $tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS');
                if ($error || (!$tmpName || !move_uploaded_file($file['tmp_name'], $tmpName))) {
                    return false;
                }
                /* Original file */
                if (!ImageManager::resize($tmpName, _EPH_UPLOAD_DIR_.$fileName)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } /* A smaller one */
                elseif (!ImageManager::resize($tmpName, _EPH_UPLOAD_DIR_.$fileName.'_small', $productPictureWidth, $productPictureHeight)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } elseif (!chmod(_EPH_UPLOAD_DIR_.$fileName, 0777) || !chmod(_EPH_UPLOAD_DIR_.$fileName.'_small', 0777)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } else {
                    $this->context->cart->addPictureToProduct($this->product->id, $indexes[$fieldName], Product::CUSTOMIZE_FILE, $fileName);
                }
                unlink($tmpName);
            }
        }

        return true;
    }

    /**
     * Text record
     *
     * @return bool
     *
     * @since 1.8.1.0
     */
    protected function textRecord()
    {
        if (!$fieldIds = $this->product->getCustomizationFieldIds()) {
            return false;
        }

        $authorizedTextFields = [];
        foreach ($fieldIds as $fieldId) {
            if ($fieldId['type'] == Product::CUSTOMIZE_TEXTFIELD) {
                $authorizedTextFields[(int) $fieldId['id_customization_field']] = 'textField'.(int) $fieldId['id_customization_field'];
            }
        }

        $indexes = array_flip($authorizedTextFields);
        foreach ($_POST as $fieldName => $value) {
            if (in_array($fieldName, $authorizedTextFields) && $value != '') {
                if (!Validate::isMessage($value)) {
                    $this->errors[] = Tools::displayError('Invalid message');
                } else {
                    $this->context->cart->addTextFieldToProduct($this->product->id, $indexes[$fieldName], Product::CUSTOMIZE_TEXTFIELD, $value);
                }
            } elseif (in_array($fieldName, $authorizedTextFields) && $value == '') {
                $this->context->cart->deleteCustomizationToProduct((int) $this->product->id, $indexes[$fieldName]);
            }
        }
    }

    /**
     * From target format
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function formTargetFormat()
    {
        $customizationFormTarget = Tools::safeOutput(urldecode($_SERVER['REQUEST_URI']));
        foreach ($_GET as $field => $value) {
            if (strncmp($field, 'group_', 6) == 0) {
                $customizationFormTarget = preg_replace('/&group_([[:digit:]]+)=([[:digit:]]+)/', '', $customizationFormTarget);
            }
        }
        if (isset($_POST['quantityBackup'])) {
            $this->context->smarty->assign('quantityBackup', (int) $_POST['quantityBackup']);
        }
        $this->context->smarty->assign('customizationFormTarget', $customizationFormTarget);
    }
	
	protected function isAccessories()
    {
		$id_groups = AccessoriesGroupAbstract::getIdGroups($this->context->language->id, true);
        if (empty($id_groups) || AccessoriesGroupAbstract::countAccessories($this->product->id, $id_groups) < 1) {
        	return false;
        }
		return true;
		
	}
	
	protected function assignAccessories() {
		
		$id_groups = AccessoriesGroupAbstract::getIdGroups($this->context->language->id, true);
        if (empty($id_groups) || AccessoriesGroupAbstract::countAccessories($this->product->id, $id_groups) < 1) {
        	return false;
        }
		$include_out_of_stock = Configuration::get('HSMA_SHOW_ACCESSORIES_OFS');
        $accessories_groups = AccessoriesGroupAbstract::getAccessoriesByGroups($id_groups, array($this->product->id), true, $this->context->language->id, $include_out_of_stock, true);

        $accessories_table_price = array();
        $currency_decimals = $this->context->currency->decimals * _EPH_PRICE_DISPLAY_PRECISION_;
        $use_tax = Product::getTaxCalculationMethod($this->context->customer->id) ? false : true;
        $random_main_product_id = Tools::passwdGen(8, 'NO_NUMERIC');
       
        $product = $this->product;
        $accessories_table_price[$random_main_product_id] = $this->formatProduct($product);
        
        $id_products_buy_together = array();
        foreach ($accessories_groups as &$accessories_group) {
            foreach ($accessories_group as &$accessory) {
                $product = new Product((int) $accessory['id_accessory'], true, (int) $this->context->language->id);
                $random_product_accessories_id = Tools::passwdGen(8, 'NO_NUMERIC');
                $default_id_product_attribute = (int) Product::getDefaultAttribute($product->id, 1);
                if (!Validate::isLoadedObject($product)) {
                    unset($accessory);
                    continue;
                }
                if ($accessory['is_available_buy_together']) {
                    $id_products_buy_together[$accessory['id_accessory_group']] = $accessory['id_accessory'];
                }
                $accessories_table_price[$random_product_accessories_id] = $this->formatAccessory($accessory);
                //@todo: Fix the price different with group customer
                $price = MaProduct::getPriceStatic($accessory['id_accessory'], $use_tax, $default_id_product_attribute, $currency_decimals);
                $accessory['price'] = $price;
                $accessory['random_product_accessories_id'] = $random_product_accessories_id;
                $accessory['default_id_product_attribute'] = $default_id_product_attribute;
                $accessory['link'] = $this->context->link->getProductLink($product);
                $accessory['available_later'] = $this->getMessageAvailableLater($accessory['available_later']);
            }
        }
        $this->context->smarty->assign(array(
            'accessory_configuration_keys' => Configuration::getMultiple(array_keys($this->configuration_keys)),
            'accessory_block_title' => Configuration::get('HSMA_TITLE', $this->context->language->id),
            'accessory_image_type' => Configuration::get('HSMA_IMAGE_TYPE'),
            'change_main_price' => Configuration::get('HSMA_CHANGE_MAIN_PRICE'),
            'image_size_fancybox' => Configuration::get('HSMA_IMAGE_SIZE_IN_FANCYBOX'),
            'show_table_price' => Configuration::get('HSMA_SHOW_PRICE_TABLE'),
            'show_combination' => Configuration::get('HSMA_SHOW_COMBINATION'),
            'collapse_expand_groups' => (int) Configuration::get('HSMA_COLLAPSE_EXPAND_GROUPS'),
            'accessory_groups' => AccessoriesGroupAbstract::getGroups($this->context->language->id, true),
            'accessories_table_price' => Tools::jsonEncode($accessories_table_price),
            'random_main_product_id' => $random_main_product_id,
			'sub_total' => $this->l('sub_total'),
			'accessory_is_out_of_stock' => $this->l('Warning, this product is out of stock'),
			'there_is_not_enough_product_in_stock' => $this->l('There is not enough product in stock'),
			'quantity_must_be_greater_than_or_equal_to_minimum_quantity' => $this->l('Warning, quantity must match with minimum quantity'),
            'accessories_groups' => $accessories_groups,
            'static_token' => Tools::getToken(false),
            'main_product_minimal_quantity' => 1,
            'buy_main_accessory_together' => MaProductSetting::getBuyTogetherCurrentValue($this->id_product),
            'id_products_buy_together' => $id_products_buy_together,
            'path_theme' =>  '',
        ));
            
	}
	
	protected function formatAccessory(array $accessory = array())
    {
        $default_id_product_attribute = (int) Product::getDefaultAttribute($accessory['id_accessory'], 1);
        $formatted_accessory = array();
        $formatted_accessory['name'] = $accessory['name'];
        $formatted_accessory['description_short'] = $accessory['description_short'];
        $formatted_accessory['qty'] = (int) $accessory['default_quantity'];
        $formatted_accessory['avaiable_quantity'] = (int) $accessory['stock_available'];
        $formatted_accessory['out_of_stock'] = MaProduct::isAvailableWhenOutOfStock($accessory['out_of_stock']);
        $formatted_accessory['is_available_when_out_of_stock'] = (MaProduct::isAvailableWhenOutOfStock($accessory['out_of_stock']) && $accessory['stock_available'] < $accessory['default_quantity']) ? 1 : 0;
        $formatted_accessory['available_later'] = $this->getMessageAvailableLater($accessory['available_later']);
        $formatted_accessory['id_accessory_group'] = (int) $accessory['id_accessory_group'];
        $formatted_accessory['id_accessory'] = (int) $accessory['id_accessory'];
        $formatted_accessory['default_id_product_attribute'] = (int) $accessory['id_product_attribute'] ? $accessory['id_product_attribute'] : $default_id_product_attribute;
        $formatted_accessory['default_quantity'] = (int) $accessory['default_quantity'] > 0 ? (int) $accessory['default_quantity'] : (int) 1;
        $formatted_accessory['min_quantity'] = (int) $accessory['min_quantity'];
        $array_id_product_attributes = array();
        if (empty($accessory['combinations'])) {
            $accessory['combinations'][] = $this->createDefaultCombination($accessory);
        } else {
            foreach ($accessory['combinations'] as $combination) {
                if (!empty($combination['id_product_attribute'])) {
                    $array_id_product_attributes[] = $combination['id_product_attribute'];
                }
            }
            if (!empty($array_id_product_attributes)) {
                $valid_id_product_attributes = $accessory['id_product_attribute'] ? array($accessory['id_product_attribute']) : $array_id_product_attributes;
                $valid_combinations = array_intersect_key($accessory['combinations'], array_flip($valid_id_product_attributes));
                $accessory['combinations'] = $valid_combinations;
            }
        }
        $formatted_accessory['combinations'] = $this->formatCombinations($accessory);
        return $formatted_accessory;
    }
	
	protected function formatCombinations(array $accessory)
    {
        $id_customer = ($this->context->customer->isLogged()) ? (int) $this->context->customer->id : 0;
        $is_cart_rule = !empty($accessory['cart_rule']) ? true : false;
        $formated_combinations = array();
        foreach ($accessory['combinations'] as $id_product_attribute => $combination) {
            $price = MaProduct::getPriceStatic($accessory['id_accessory'], Product::getTaxCalculationMethod($this->context->customer->id) ? false : true, $combination['id_product_attribute']);
            $final_price = AccessoriesGroupAbstract::getFinalPrice($price, $accessory['cart_rule']);
            $formated_combinations[$id_product_attribute] = array(
                'price' => $price,
                'final_price' => $final_price,
                'is_cart_rule' => $is_cart_rule,
                'image_fancybox' => MaLink::getProductImageLink($accessory['link_rewrite'], $combination['id_image'], Configuration::get('HSMA_IMAGE_SIZE_IN_FANCYBOX')),
                'image_default' => $combination['image'],
                'name' => $combination['name'],
                'specific_prices' => MaSpecificPrice::getSpecificPrices($accessory['id_accessory'], $id_customer, ($id_customer ? Customer::getDefaultGroupId((int) $id_customer) : (int) Group::getCurrent()->id), ($id_customer ? Customer::getCurrentCountry($id_customer) : Configuration::get('EPH_COUNTRY_DEFAULT')), (int) $this->context->currency->id, (int) $this->context->company->id, false, $combination['id_product_attribute']),
                'avaiable_quantity' => (int) $combination['stock_available'],
                'out_of_stock' => MaProduct::isAvailableWhenOutOfStock($combination['out_of_stock']),
                'is_stock_available' => (int) $this->isStockAvailable($accessory['id_accessory'], (int) $combination['id_product_attribute'], (int) $accessory['default_quantity']),
                'is_available_when_out_of_stock' => (MaProduct::isAvailableWhenOutOfStock($combination['out_of_stock']) && $combination['stock_available'] < $accessory['default_quantity']) ? 1 : 0
            );
        }
        return $formated_combinations;
    }
	
	protected function isStockAvailable($id_product, $id_product_attribute, $quantity)
    {
        $flag = false;
        $stock_status = MaProduct::getStockStatus((int) $id_product, (int) $id_product_attribute, $this->context->company);
        if (!empty($stock_status)) {
            if (Product::isAvailableWhenOutOfStock($stock_status['out_of_stock']) || (!Product::isAvailableWhenOutOfStock($stock_status['out_of_stock']) && $stock_status['quantity'] >= (int) $quantity)) {
                $flag = true;
            }
        }

        return $flag;
    }
	
	protected function getMessageAvailableLater($available_later)
    {
        $message_available_later = $this->l('out_of_stock_but_backordering_is_allowed');
        $config_message_available_later = Configuration::get('HSMA_MESSAGE_AVAILABLE_LATER', (int) $this->context->language->id);
        if (!empty($available_later)) {
            $message_available_later = $available_later;
        } elseif (!empty($config_message_available_later)) {
            $message_available_later = $config_message_available_later;
        }

        return $message_available_later;
    }
	
	protected function createDefaultCombination(array $accessory = array())
    {
        return array(
            'id_product_attribute' => $accessory['id_product_attribute'],
            'stock_available' => $accessory['stock_available'],
            'out_of_stock' => $accessory['out_of_stock'],
            'id_image' => $accessory['id_image'],
            'combination' => $accessory['name'],
            'image' => $accessory['image'],
            'name' => ''
        );
    }
	
	protected function formatProduct(Product $product)
    {
        $default_id_product_attribute = (int) Product::getDefaultAttribute($product->id, 1);
        $product->id_product_attribute = $default_id_product_attribute;
        $formatted_product = array();
        $formatted_product['id_product'] = $product->id;
        $formatted_product['link_rewrite'] = $product->link_rewrite;
        $formatted_product['name'] = $product->name;
        $formatted_product['qty'] = 1;
        $formatted_product['out_of_stock'] = Product::isAvailableWhenOutOfStock($product->out_of_stock);
        $formatted_product['available_quantity'] = (int) $product->quantity;
        $formatted_product['description_short'] = $product->description_short;
        $formatted_product['default_id_product_attribute'] = $product->id_product_attribute;
        $combinations = MaProduct::getCombinations((int) $product->id, (int) $this->context->company->id);
        if (!empty($combinations)) {
            $formatted_product['combinations'] = $combinations;
        } else {
            $formatted_product['id_product_attribute'] = $formatted_product['default_id_product_attribute'];
            $formatted_product['combinations'][] = $this->createDefaultProductCombination($formatted_product);
        }
        $formatted_product['combinations'] = $this->formatMainProductCombinations($formatted_product);
        return $formatted_product;
    }
	
	protected function createDefaultProductCombination(array $product = array())
    {
        return array(
            'id_product_attribute' => $product['id_product_attribute'],
            'out_of_stock' => $product['out_of_stock'],
            'combination' => $product['name'],
            'name' => $product['name']
        );
    }
	
	protected function formatMainProductCombinations(array $product)
    {
        $id_customer = ($this->context->customer->isLogged()) ? (int) $this->context->customer->id : 0;
        $formated_combinations = array();
        foreach ($product['combinations'] as $id_product_attribute => $combination) {
            $price = MaProduct::getPriceStatic($product['id_product'], Product::getTaxCalculationMethod($this->context->customer->id) ? false : true, $combination['id_product_attribute'], (int) $this->context->currency->decimals * _EPH_PRICE_DISPLAY_PRECISION_);
            $formated_combinations[$id_product_attribute] = array(
                'price' => $price,
                'name' => $combination['name'],
                'specific_prices' => MaSpecificPrice::getSpecificPrices($product['id_product'], $id_customer, ($id_customer ? Customer::getDefaultGroupId((int) $id_customer) : (int) Group::getCurrent()->id), ($id_customer ? Customer::getCurrentCountry($id_customer) : Configuration::get('EPH_COUNTRY_DEFAULT')), (int) $this->context->currency->id, (int) $this->context->company->id, false, $combination['id_product_attribute']),
                //'avaiable_quantity' => (int) $combination['stock_available'],
                'out_of_stock' => MaProduct::isAvailableWhenOutOfStock($combination['out_of_stock']),
            );
        }
        return $formated_combinations;
    }

    /**
     * Assign template vars related to category
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignCategory()
    {
        // Assign category to the template
        if ($this->category !== false && Validate::isLoadedObject($this->category)) {
            $path = Tools::getPath($this->category->id, $this->product->name, true);
        } elseif (Category::inShopStatic($this->product->id_category_default, $this->context->company)) {
            $this->category = new Category((int) $this->product->id_category_default, (int) $this->context->language->id);
            if (Validate::isLoadedObject($this->category) && $this->category->active) {
                $path = Tools::getPath((int) $this->product->id_category_default, $this->product->name);
            }
        }
        if (!isset($path) || !$path) {
            $path = Tools::getPath((int) $this->context->company->id_category, $this->product->name);
        }

        if (Validate::isLoadedObject($this->category)) {
            $subCategories = $this->category->getSubCategories($this->context->language->id, true);

            // various assignements before Hook::exec
            $this->context->smarty->assign(
                [
                    'path'                 => $path,
                    'category'             => $this->category,
                    'subCategories'        => $subCategories,
                    'id_category_current'  => (int) $this->category->id,
                    'id_category_parent'   => (int) $this->category->id_parent,
                    'return_category_name' => Tools::safeOutput($this->category->getFieldByLang('name')),
                    'categories'           => Category::getHomeCategories($this->context->language->id, true, (int) $this->context->company->id),
                ]
            );
        }
        $this->context->smarty->assign(['HOOK_PRODUCT_FOOTER' => Hook::exec('displayFooterProduct', ['product' => $this->product, 'category' => $this->category])]);
    }

    /**
     * Assign price and tax to the template
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignPriceAndTax()
    {
        $idCustomer = (isset($this->context->customer) ? (int) $this->context->customer->id : 0);
        $idGroup = (int) Group::getCurrent()->id;
        $idCountry = $idCustomer ? (int) Customer::getCurrentCountry($idCustomer) : (int) Tools::getCountry();

        $groupReduction = GroupReduction::getValueForProduct($this->product->id, $idGroup);
        if ($groupReduction === false) {
            $groupReduction = Group::getReduction((int) $this->context->cookie->id_customer) / 100;
        }

        // Tax
        $tax = (float) $this->product->getTaxesRate(new Address((int) $this->context->cart->{Configuration::get('EPH_TAX_ADDRESS_TYPE')}));
        $this->context->smarty->assign('tax_rate', $tax);

        $productPriceWithTax = Product::getPriceStatic($this->product->id, true, null, 6);
        if (Product::$_taxCalculationMethod == EPH_TAX_INC) {
            $productPriceWithTax = Tools::ps_round($productPriceWithTax, 2);
        }
        $productPriceWithoutEcoTax = (float) $productPriceWithTax - $this->product->ecotax;

        $ecotaxRate = (float) Tax::getProductEcotaxRate($this->context->cart->{Configuration::get('EPH_TAX_ADDRESS_TYPE')});
        if (Product::$_taxCalculationMethod == EPH_TAX_INC && (int) Configuration::get('EPH_TAX')) {
            $ecotaxTaxAmount = Tools::ps_round($this->product->ecotax * (1 + $ecotaxRate / 100), 2);
        } else {
            $ecotaxTaxAmount = Tools::ps_round($this->product->ecotax, 2);
        }

        $idCurrency = (int) $this->context->cookie->id_currency;
        $idProduct = (int) $this->product->id;
        $idShop = $this->context->company->id;

        $quantityDiscounts = SpecificPrice::getQuantityDiscounts($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, null, true, (int) $this->context->customer->id);
        foreach ($quantityDiscounts as &$quantityDiscount) {
            if (!isset($quantityDiscount['base_price'])) {
                $quantityDiscount['base_price'] = 0;
            }
            if ($quantityDiscount['id_product_attribute']) {
                $quantityDiscount['base_price'] = $this->product->getPrice(Product::$_taxCalculationMethod === EPH_TAX_INC, $quantityDiscount['id_product_attribute']);
                $combination = new Combination((int) $quantityDiscount['id_product_attribute']);
                $attributes = $combination->getAttributesName((int) $this->context->language->id);
                $quantityDiscount['attributes'] = '';
                foreach ($attributes as $attribute) {
                    $quantityDiscount['attributes'] .= $attribute['name'].' - ';
                }
                $quantityDiscount['attributes'] = rtrim($quantityDiscount['attributes'], ' - ');
            } else {
                $quantityDiscount['base_price'] = $this->product->getPrice(Product::$_taxCalculationMethod == EPH_TAX_INC);
            }
            if ((int) $quantityDiscount['id_currency'] == 0 && $quantityDiscount['reduction_type'] == 'amount') {
                $quantityDiscount['reduction'] = Tools::convertPriceFull($quantityDiscount['reduction'], null, $this->context->currency);
            }
        }

        $address = new Address($this->context->cart->{Configuration::get('EPH_TAX_ADDRESS_TYPE')});
        $this->context->smarty->assign(
            [
                'quantity_discounts'         => $this->formatQuantityDiscounts($quantityDiscounts, null, (float) $tax, $ecotaxTaxAmount),
                'ecotax_tax_inc'             => $ecotaxTaxAmount,
                'ecotax_tax_exc'             => Tools::ps_round($this->product->ecotax, 2),
                'ecotaxTax_rate'             => $ecotaxRate,
                'productPriceWithoutEcoTax'  => (float) $productPriceWithoutEcoTax,
                'group_reduction'            => $groupReduction,
                'no_tax'                     => Tax::excludeTaxeOption() || !$this->product->getTaxesRate($address),
                'ecotax'                     => (!count($this->errors) && $this->product->ecotax > 0 ? Tools::convertPrice((float) $this->product->ecotax) : 0),
                'tax_enabled'                => Configuration::get('EPH_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
                'customer_group_without_tax' => Group::getPriceDisplayMethod($this->context->customer->id_default_group),
            ]
        );
    }

    /**
     * Format quantity discounts
     *
     * @param array $specificPrices
     * @param float $price
     * @param float $taxRate
     * @param float $ecotaxAmount
     *
     * @return mixed
     *
     * @since 1.8.1.0
     */
    protected function formatQuantityDiscounts($specificPrices, $price, $taxRate, $ecotaxAmount)
    {
        foreach ($specificPrices as $key => &$row) {
            $row['quantity'] = &$row['from_quantity'];
            if ($row['price'] >= 0) {
                // The price may be directly set

                $currentPrice = (!$row['reduction_tax'] ? $row['price'] : $row['price'] * (1 + $taxRate / 100)) + (float) $ecotaxAmount;

                if ($row['reduction_type'] == 'amount') {
                    $currentPrice -= ($row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $taxRate / 100));
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $taxRate / 100);
                } else {
                    $currentPrice *= 1 - $row['reduction'];
                }

                $row['real_value'] = $row['base_price'] > 0 ? $row['base_price'] - $currentPrice : $currentPrice;
            } else {
                if ($row['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == EPH_TAX_INC) {
                        $row['real_value'] = $row['reduction_tax'] == 1 ? $row['reduction'] : $row['reduction'] * (1 + $taxRate / 100);
                    } else {
                        $row['real_value'] = $row['reduction_tax'] == 0 ? $row['reduction'] : $row['reduction'] / (1 + $taxRate / 100);
                    }
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] + ($row['reduction'] * $taxRate) / 100;
                } else {
                    $row['real_value'] = $row['reduction'] * 100;
                }
            }
            $row['nextQuantity'] = (isset($specificPrices[$key + 1]) ? (int) $specificPrices[$key + 1]['from_quantity'] : -1);
        }

        return $specificPrices;
    }

    /**
     * Assign template vars related to images
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignImages()
    {
        $images = $this->product->getImages((int) $this->context->cookie->id_lang);
        $productImages = [];

        if (isset($images[0])) {
            $this->context->smarty->assign('mainImage', $images[0]);
        }
        foreach ($images as $k => $image) {
            if ($image['cover']) {
                $this->context->smarty->assign('mainImage', $image);
                $cover = $image;
                $cover['id_image'] = (Configuration::get('EPH_LEGACY_IMAGES') ? ($this->product->id.'-'.$image['id_image']) : $image['id_image']);
                $cover['id_image_only'] = (int) $image['id_image'];
            }
            $productImages[(int) $image['id_image']] = $image;
        }

        if (!isset($cover)) {
            if (isset($images[0])) {
                $cover = $images[0];
                $cover['id_image'] = (Configuration::get('EPH_LEGACY_IMAGES') ? ($this->product->id.'-'.$images[0]['id_image']) : $images[0]['id_image']);
                $cover['id_image_only'] = (int) $images[0]['id_image'];
            } else {
                $cover = [
                    'id_image' => $this->context->language->iso_code.'-default',
                    'legend'   => 'No picture',
                    'title'    => 'No picture',
                ];
            }
        }
        $size = Image::getSize(ImageType::getFormatedName('large'));
        $this->context->smarty->assign(
            [
                'have_image'  => (isset($cover['id_image']) && (int) $cover['id_image']) ? [(int) $cover['id_image']] : Product::getCover((int) Tools::getValue('id_product')),
                'cover'       => $cover,
                'imgWidth'    => (int) $size['width'],
                'mediumSize'  => Image::getSize(ImageType::getFormatedName('medium')),
                'largeSize'   => Image::getSize(ImageType::getFormatedName('large')),
                'homeSize'    => Image::getSize(ImageType::getFormatedName('home')),
                'cartSize'    => Image::getSize(ImageType::getFormatedName('cart')),
                'col_img_dir' => _EPH_COL_IMG_DIR_,
            ]
        );
        if (count($productImages)) {
            $this->context->smarty->assign('images', $productImages);
        }
    }

    /**
     * Assign template vars related to attribute groups and colors
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignAttributesGroups()
    {
        $colors = [];
        $groups = [];

        // @todo (RM) should only get groups and not all declination ?
        $attributesGroups = $this->product->getAttributesGroups($this->context->language->id);
        if (is_array($attributesGroups) && $attributesGroups) {
            $combinationImages = $this->product->getCombinationImages($this->context->language->id);
            $combinationPricesSet = [];
            foreach ($attributesGroups as $k => $row) {
                // Color management
                if (isset($row['is_color_group']) && $row['is_color_group'] && (isset($row['attribute_color']) && $row['attribute_color']) || (file_exists(_EPH_COL_IMG_DIR_.$row['id_attribute'].'.jpg'))) {
                    $colors[$row['id_attribute']]['value'] = $row['attribute_color'];
                    $colors[$row['id_attribute']]['name'] = $row['attribute_name'];
                    if (!isset($colors[$row['id_attribute']]['attributes_quantity'])) {
                        $colors[$row['id_attribute']]['attributes_quantity'] = 0;
                    }
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int) $row['quantity'];
                }
                if (!isset($groups[$row['id_attribute_group']])) {
                    $groups[$row['id_attribute_group']] = [
                        'group_name' => $row['group_name'],
                        'name'       => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default'    => -1,
                    ];
                }

                $groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']] = $row['attribute_name'];
                if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1) {
                    $groups[$row['id_attribute_group']]['default'] = (int) $row['id_attribute'];
                }
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                }
                $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += (int) $row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
                $combinations[$row['id_product_attribute']]['attributes'][] = (int) $row['id_attribute'];
                $combinations[$row['id_product_attribute']]['price'] = (float) Tools::convertPriceFull($row['price'], null, $this->context->currency, false);

                // Call getPriceStatic in order to set $combination_specific_price
                if (!isset($combinationPricesSet[(int) $row['id_product_attribute']])) {
                    Product::getPriceStatic((int) $this->product->id, false, $row['id_product_attribute'], 6, null, false, false, 1, false, null, null, null, $combinationSpecificPrice);
                    $combinationPricesSet[(int) $row['id_product_attribute']] = true;
                    $combinations[$row['id_product_attribute']]['specific_price'] = $combinationSpecificPrice;
                }
                $combinations[$row['id_product_attribute']]['name'] = $row['attributeName'];
                $combinations[$row['id_product_attribute']]['description'] = $row['description'];
                $combinations[$row['id_product_attribute']]['description_short'] = $row['description_short'];
                $combinations[$row['id_product_attribute']]['ecotax'] = (float) $row['ecotax'];
                $combinations[$row['id_product_attribute']]['weight'] = (float) $row['weight'];
                $combinations[$row['id_product_attribute']]['quantity'] = (int) $row['quantity'];
                $combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
                $combinations[$row['id_product_attribute']]['unit_impact'] = Tools::convertPriceFull($row['unit_price_impact'], null, $this->context->currency, false);
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = $row['minimal_quantity'];
                if ($row['available_date'] != '0000-00-00' && Validate::isDate($row['available_date'])) {
                    $combinations[$row['id_product_attribute']]['available_date'] = $row['available_date'];
                    $combinations[$row['id_product_attribute']]['date_formatted'] = Tools::displayDate($row['available_date']);
                } else {
                    $combinations[$row['id_product_attribute']]['available_date'] = $combinations[$row['id_product_attribute']]['date_formatted'] = '';
                }

                if (!isset($combinationImages[$row['id_product_attribute']][0]['id_image'])) {
                    $combinations[$row['id_product_attribute']]['id_image'] = -1;
                } else {
                    $combinations[$row['id_product_attribute']]['id_image'] = $idImage = (int) $combinationImages[$row['id_product_attribute']][0]['id_image'];
                    if ($row['default_on']) {
                        if (isset($this->context->smarty->tpl_vars['cover']->value)) {
                            $currentCover = $this->context->smarty->tpl_vars['cover']->value;
                        }

                        if (is_array($combinationImages[$row['id_product_attribute']])) {
                            foreach ($combinationImages[$row['id_product_attribute']] as $tmp) {
                                if (isset($currentCover) && $tmp['id_image'] == $currentCover['id_image']) {
                                    $combinations[$row['id_product_attribute']]['id_image'] = $idImage = (int) $tmp['id_image'];
                                    break;
                                }
                            }
                        }

                        if ($idImage > 0) {
                            if (isset($this->context->smarty->tpl_vars['images']->value)) {
                                $productImages = $this->context->smarty->tpl_vars['images']->value;
                            }
                            if (isset($productImages) && is_array($productImages) && isset($productImages[$idImage])) {
                                $productImages[$idImage]['cover'] = 1;
                                $this->context->smarty->assign('mainImage', $productImages[$idImage]);
                                if (count($productImages)) {
                                    $this->context->smarty->assign('images', $productImages);
                                }
                            }
                            if (isset($this->context->smarty->tpl_vars['cover']->value)) {
                                $cover = $this->context->smarty->tpl_vars['cover']->value;
                            }
                            if (isset($cover) && is_array($cover) && isset($productImages) && is_array($productImages)) {
                                $productImages[$cover['id_image']]['cover'] = 0;
                                if (isset($productImages[$idImage])) {
                                    $cover = $productImages[$idImage];
                                }
                                $cover['id_image'] = (Configuration::get('EPH_LEGACY_IMAGES') ? ($this->product->id.'-'.$idImage) : (int) $idImage);
                                $cover['id_image_only'] = (int) $idImage;
                                $this->context->smarty->assign('cover', $cover);
                            }
                        }
                    }
                }
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && Configuration::get('EPH_DISP_UNAVAILABLE_ATTR') == 0) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => &$quantity) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }

                foreach ($colors as $key => $color) {
                    if ($color['attributes_quantity'] <= 0) {
                        unset($colors[$key]);
                    }
                }
            }
            if (isset($combinations)) {
                foreach ($combinations as $idProductAttribute => $comb) {
                    $attributeList = '';
                    foreach ($comb['attributes'] as $idAttribute) {
                        $attributeList .= '\''.(int) $idAttribute.'\',';
                    }
                    $attributeList = rtrim($attributeList, ',');
                    $combinations[$idProductAttribute]['list'] = $attributeList;
                }
            }

            $this->context->smarty->assign(
                [
                    'groups'            => $groups,
                    'colors'            => (count($colors)) ? $colors : false,
                    'combinations'      => isset($combinations) ? $combinations : [],
                    'combinationImages' => $combinationImages,
                ]
            );
        }
    }

    /**
     * Get and assign attributes combinations informations
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignAttributesCombinations()
    {
        $attributesCombinations = Product::getAttributesInformationsByProduct($this->product->id);
        if (is_array($attributesCombinations) && count($attributesCombinations)) {
            foreach ($attributesCombinations as &$ac) {
                foreach ($ac as &$val) {
                    $val = str_replace(Configuration::get('EPH_ATTRIBUTE_ANCHOR_SEPARATOR'), '_', Tools::link_rewrite(str_replace([',', '.'], '-', $val)));
                }
            }
        } else {
            $attributesCombinations = [];
        }
        $this->context->smarty->assign(
            [
                'attributesCombinations'     => $attributesCombinations,
                'attribute_anchor_separator' => Configuration::get('EPH_ATTRIBUTE_ANCHOR_SEPARATOR'),
            ]
        );
    }

	public function ajaxProcessrenderAccessories()
    {
        $id_products = Tools::getValue('id_products');
		if (empty($id_products) || !Configuration::get('HSMA_BUY_ACCESSORY_MAIN_TOGETHER')) {
            return;
        }
		
        $use_tax = Product::getTaxCalculationMethod($this->context->customer->id) ? false : true;
        $decimals = (int) $this->context->currency->decimals * _EPH_PRICE_DISPLAY_PRECISION_;
        $list_accessories = array(
            'success' => true,
            'show_total_price' => (int) Configuration::get('HSMA_SHOW_TOTAL_PRICE'),
            'accessories' => AccessoriesGroupProductAbstract::getAccessoriesByIdProducts($id_products, $use_tax, $decimals),
            'total_price' => AccessoriesGroupProductAbstract::getTotalPrice(),
            'total_price_without_discount' => AccessoriesGroupProductAbstract::getTotalPriceWithOutDiscount(),
        );
        die(Tools::jsonEncode($list_accessories));
    }
	
	protected function getFormatCurrency()
    {
        $format = $this->context->currency->format;
        switch ($format) {
            case '#,##0.00 ¤':
            case '#,##0.00¤':
                $format_currency = 4;
                break;
            case '# ##0,00 ¤':
            case '# ##0,00¤':
            case '###0,00¤':
            case '###0,00 ¤':
                $format_currency = 2;
                break;
            case '¤ #,##0.00':
            case '¤#,##0.00':
                $format_currency = 1;
                break;
            case '¤ #.##0,00':
            case '¤#.##0,00':
                $format_currency = 3;
                break;
            case '¤ #\##0.00':
            case '¤#\##0.00':
                $format_currency = 5;
                break;
            default:
                $format_currency = 1;
                break;
        }
        return $format_currency;
    }
    /**
     * Get Product
     *
     * @return Product
     *
     * @since 1.8.1.0
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Get Category
     *
     * @return Category
     *
     * @since 1.8.1.0
     */
    public function getCategory()
    {
        return $this->category;
    }
}
