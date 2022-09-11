<?php

/**
 * Class CustomerPieceDetailCore
 *
 * @since 2.1.0.0
 */
class CustomerPieceDetailCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_piece_detail',
        'primary' => 'id_customer_piece_detail',
        'fields'  => [
            'id_customer_piece'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_warehouse'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product_attribute'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'product_name'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'product_quantity'          => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'reduction_percent'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'reduction_amount_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'reduction_amount_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'product_ean13'             => ['type' => self::TYPE_STRING, 'validate' => 'isEan13'],
            'product_upc'               => ['type' => self::TYPE_STRING, 'validate' => 'isUpc'],
            'product_reference'         => ['type' => self::TYPE_STRING, 'validate' => 'isReference'],
            'id_product_batch'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'product_weight'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'tax_rate'                  => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
			'tax_computation_method'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                     ],
            'id_tax_rules_group'        => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'ecotax'                    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'download_hash'             => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'download_nb'               => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'download_deadline'         => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'original_price_tax_excl'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'original_price_tax_incl'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'unit_tax_incl'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'unit_tax_excl'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax_excl'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax'                 => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax_incl'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'product_wholesale_price'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
        ],
    ];

    /** @var int $id_order */
    public $id_customer_piece;
    /** @var int $id_order_invoice */
    public $id_product;
    /** @var int $id_product_attribute_id */
    public $id_product_attribute;
    /** @var string $product_name */
    public $product_name;
    /** @var int $product_quantity */
    public $product_quantity;
    /** @var float $original_price_tax_excl */
    public $original_price_tax_excl;
    /** @var float $original_price_tax_incl */
    public $original_price_tax_incl;
    /** @var float $unit_tax_incl */
    public $unit_tax_incl;
    /** @var float $unit_price_tax_excl */
    public $unit_tax_excl;
    /** @var float $total_tax_incl */
    public $total_tax_incl;
    /** @var float $total_tax_excl */
    public $total_tax;
    /** @var float $reduction_percent */
    public $reduction_percent;
    /** @var float $reduction_amount_tax_excl */
    public $reduction_amount_tax_excl;
    /** @var float $reduction_amount_tax_incl */
    public $reduction_amount_tax_incl;
    /** @var string $product_ean13 */
    public $product_ean13;
    /** @var string $product_upc */
    public $product_upc;
    /** @var string $product_reference */
    public $product_reference;
    /** @var int $id_product_batch */
    public $id_product_batch;
    /** @var float $product_weight */
    public $product_weight;
    /** @var float $ecotax */
    public $ecotax;
    /** @var string $download_hash */
    public $download_hash;
    /** @var int $download_nb */
    public $download_nb;
    /** @var datetime $download_deadline */
    public $download_deadline;
    /** @var float $tax_rate * */
    public $tax_rate;
	
	public $tax_computation_method;
    /** @var int $id_tax_rules_group Id tax rules group */
    public $id_tax_rules_group;
    /** @var int $id_warehouse Id warehouse */
    public $id_warehouse;
    /** @var float $original_wholesale_price */
    public $product_wholesale_price;
    /** @var bool $outOfStock */
    protected $outOfStock = false;
    /** @var null|TaxCalculator $tax_calculator */
    protected $tax_calculator = null;
    /** @var null|Address $vat_address */
    protected $vat_address = null;
    /** @var null|Address $specificPrice */
    protected $specificPrice = null;
    /** @var null|Customer $customer */
    protected $customer = null;
    /** @var null|Context $context */
    protected $context = null;

    /**
     * CustomerPieceDetailCore constructor.
     *
     * @param null $id
     * @param null $idLang
     * @param null $context
     *
     * @since 2.1.0.0
     */
    public function __construct($id = null, $idLang = null, $context = null) {

        $this->context = $context;
        $idShop = null;
        if ($this->context != null && isset($this->context->shop)) {
            $idShop = $this->context->shop->id;
        }
        parent::__construct($id, $idLang, $idShop);

        if ($context == null) {
            $context = Context::getContext();
        }
        $this->context = $context->cloneContext();

    }

    /**
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 2.1.0.0
     */
    public function delete() {

        return parent::delete();
    }
	
	
	
	public function createList(CustomerPieces $order, Cart $cart, $productList, $idOrderInvoice = 0, $useTaxes = true, $idWarehouse = 0) {
        $this->vat_address = new Address((int) $order->{Configuration::get('EPH_TAX_ADDRESS_TYPE')});
        $this->customer = new Customer((int) $order->id_customer);

        $this->id_customer_piece = $order->id;
        $this->outOfStock = false;

        foreach ($productList as $product) {
            $this->create($order, $cart, $product, $idOrderInvoice, $useTaxes, $idWarehouse);
        }

        unset($this->vat_address);
        unset($products);
        unset($this->customer);
    }
	
	protected function create(CustomerPieces $order, Cart $cart, $product, $idOrderInvoice, $useTaxes = false, $idWarehouse = 0)  {
        if ($useTaxes) {
            $this->tax_calculator = new TaxCalculator();
        }

        $this->id = null;

        $this->id_product = (int) $product['id_product'];
        $this->id_product_attribute = $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : 0;
        $this->product_name = $product['name'].((isset($product['attributes']) && $product['attributes'] != null) ? ' - '.$product['attributes'] : '');

        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
        $this->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
        $this->product_reference = empty($product['reference']) ? null : pSQL($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
        $this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $idWarehouse;
        $this->product_quantity = $product['cart_quantity'];
		$this->tax_rate = $product['rate'];
		$this->total_tax = $product['total_wt'] - $product['total'];
		$this->total_tax_excl = $product['total'];
		$this->total_tax_incl = $product['total_wt'];
		$this->original_price_tax_excl = $product['price'];
		$this->original_price_tax_incl = $product['price_without_reduction'];
		$this->unit_tax_excl = $product['price'];
		$this->unit_tax_incl = $product['price_without_reduction'];


        

        if ($useTaxes) {
            $this->setProductTax($order, $product);
        }
        $this->setShippingCost($order, $product);
        $this->setDetailProductPrice($order, $cart, $product);

        // Set order invoice id
        $this->id_order_invoice = (int) $idOrderInvoice;

        // Set shop id
        $this->id_shop = (int) $product['id_shop'];

        // Add new entry to the table
        $this->save();

        if ($useTaxes) {
            //$this->saveTaxCalculator($order);
        }
        unset($this->tax_calculator);
    }
	
	protected function setDetailProductPrice(CustomerPieces $order, Cart $cart, $product)  {
       
		$this->setContext((int) $product['id_shop']);
        Product::getPriceStatic((int) $product['id_product'], true, (int) $product['id_product_attribute'], 6, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('EPH_TAX_ADDRESS_TYPE')}, $specificPrice, true, true, $this->context);
        $this->specificPrice = $specificPrice;
        $this->original_product_price = Product::getPriceStatic($product['id_product'], false, (int) $product['id_product_attribute'], 6, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
        $this->product_price = $this->original_product_price;
        $this->unit_price_tax_incl = (float) $product['price_wt'];
        $this->unit_price_tax_excl = (float) $product['price'];
        $this->total_price_tax_incl = (float) $product['total_wt'];
        $this->total_price_tax_excl = (float) $product['total'];

        $this->purchase_supplier_price = (float) $product['wholesale_price'];
        if ($product['id_supplier'] > 0 && ($supplierPrice = ProductSupplier::getProductPrice((int) $product['id_supplier'], $product['id_product'], $product['id_product_attribute'], true)) > 0) {
            $this->purchase_supplier_price = (float) $supplierPrice;
        }

        $this->setSpecificPrice($order, $product);

        $this->group_reduction = (float) Group::getReduction((int) $order->id_customer);

        $shopId = $this->context->shop->id;

        $quantityDiscount = SpecificPrice::getQuantityDiscount(
            (int) $product['id_product'],
            $shopId,
            (int) $cart->id_currency,
            (int) $this->vat_address->id_country,
            (int) $this->customer->id_default_group,
            (int) $product['cart_quantity'],
            false,
            null
        );

        $unitPrice = Product::getPriceStatic(
            (int) $product['id_product'],
            true,
            ($product['id_product_attribute'] ? intval($product['id_product_attribute']) : null),
            _EPH_PRICE_DATABASE_PRECISION_,
            null,
            false,
            true,
            1,
            false,
            (int) $order->id_customer,
            null,
            (int) $order->{Configuration::get('EPH_TAX_ADDRESS_TYPE')},
            $null,
            true,
            true,
            $this->context
        );
        $this->product_quantity_discount = 0.00;
        if ($quantityDiscount) {
            $this->product_quantity_discount = $unitPrice;
            if (Product::getTaxCalculationMethod((int) $order->id_customer) == EPH_TAX_EXC) {
                $this->product_quantity_discount = Tools::ps_round($unitPrice, _EPH_PRICE_DATABASE_PRECISION_);
            }

            if (isset($this->tax_calculator)) {
                $this->product_quantity_discount -= $this->tax_calculator->addTaxes($quantityDiscount['price']);
            }
        }

        $this->discount_quantity_applied = (($this->specificPrice && $this->specificPrice['from_quantity'] > 1) ? 1 : 0);
    }
	
	public function setShippingCost(CustomerPieces $order, $product) {
        $taxRate = 0;

        $carrier = CustomerPieces::getCarrier((int) $this->id);
        if (isset($carrier) && Validate::isLoadedObject($carrier)) {
            $taxRate = $carrier->getTaxesRate(new Address((int) $order->{Configuration::get('EPH_TAX_ADDRESS_TYPE')}));
        }

        $this->total_shipping_price_tax_excl = (float) $product['additional_shipping_cost'];
        $this->total_shipping_price_tax_incl = (float) ($this->total_shipping_price_tax_excl * (1 + ($taxRate / 100)));
        $this->total_shipping_price_tax_incl = Tools::ps_round($this->total_shipping_price_tax_incl, 2);
    }
	
	protected function setProductTax(CustomerPieces $order, $product)  {
        
		$this->ecotax = Tools::convertPrice(floatval($product['ecotax']), intval($order->id_currency));

        // Exclude VAT
        if (!Tax::excludeTaxeOption()) {
            $this->setContext((int) $product['id_shop']);
            $this->id_tax_rules_group = (int) Product::getIdTaxRulesGroupByIdProduct((int) $product['id_product'], $this->context);

            $taxManager = TaxManagerFactory::getManager($this->vat_address, $this->id_tax_rules_group);
            $this->tax_calculator = $taxManager->getTaxCalculator();
            $this->tax_computation_method = (int) $this->tax_calculator->computation_method;
        }

        $this->ecotax_tax_rate = 0;
        if (!empty($product['ecotax'])) {
            $this->ecotax_tax_rate = Tax::getProductEcotaxRate($order->{Configuration::get('EPH_TAX_ADDRESS_TYPE')});
        }
    }
	
	 protected function setSpecificPrice(CustomerPieces $order, $product = null)
    {
        $this->reduction_amount = 0.00;
        $this->reduction_percent = 0.00;
        $this->reduction_amount_tax_incl = 0.00;
        $this->reduction_amount_tax_excl = 0.00;

        if ($this->specificPrice) {
            switch ($this->specificPrice['reduction_type']) {
                case 'percentage':
                    $this->reduction_percent = (float) $this->specificPrice['reduction'] * 100;
                    break;

                case 'amount':
                    $price = Tools::convertPrice($this->specificPrice['reduction'], $order->id_currency);
                    $this->reduction_amount = !$this->specificPrice['id_currency'] ? (float) $price : (float) $this->specificPrice['reduction'];
                    if ($product !== null) {
                        $this->setContext((int) $product['id_shop']);
                    }
                    $idTaxRules = (int) Product::getIdTaxRulesGroupByIdProduct((int) $this->specificPrice['id_product'], $this->context);
                    $taxManager = TaxManagerFactory::getManager($this->vat_address, $idTaxRules);
                    $this->tax_calculator = $taxManager->getTaxCalculator();

                    if ($this->specificPrice['reduction_tax']) {
                        $this->reduction_amount_tax_incl = $this->reduction_amount;
                        $this->reduction_amount_tax_excl = Tools::ps_round($this->tax_calculator->removeTaxes($this->reduction_amount), _EPH_PRICE_DATABASE_PRECISION_);
                    } else {
                        $this->reduction_amount_tax_incl = Tools::ps_round($this->tax_calculator->addTaxes($this->reduction_amount), _EPH_PRICE_DATABASE_PRECISION_);
                        $this->reduction_amount_tax_excl = $this->reduction_amount;
                    }
                    break;
            }
        }
    }

	
	protected function setContext($idShop) {
        if ($this->context->shop->id != $idShop) {
            $this->context->shop = new Shop((int) $idShop);
        }
    }
	
	public function saveTaxCalculator(CustomerPieces $order, $replace = false)
    {
        // Nothing to save
        if ($this->tax_calculator == null) {
            return true;
        }

        if (!($this->tax_calculator instanceof TaxCalculator)) {
            return false;
        }

        if (count($this->tax_calculator->taxes) == 0) {
            return true;
        }

        if ($order->total_products <= 0) {
            return true;
        }

        $shippingTaxAmount = 0;

        foreach ($order->getCartRules() as $cartRule) {
            if ($cartRule['free_shipping']) {
                $shippingTaxAmount = $order->total_shipping_tax_excl;
                break;
            }
        }

        $ratio = $this->unit_price_tax_excl / $order->total_products;
        $orderReductionAmount = ($order->total_discounts_tax_excl - $shippingTaxAmount) * $ratio;
        $discountedPriceTaxExcl = $this->unit_price_tax_excl - $orderReductionAmount;

        $values = [];
        foreach ($this->tax_calculator->getTaxesAmount($discountedPriceTaxExcl) as $idTax => $amount) {
            $unitAmount = $totalAmount = 0;
            switch ((int) Configuration::get('EPH_ROUND_TYPE')) {
                case CustomerPieces::ROUND_ITEM:
                    $unitAmount = (float) Tools::ps_round($amount, _EPH_PRICE_DISPLAY_PRECISION_);
                    $totalAmount = $unitAmount * $this->product_quantity;
                    break;
                case CustomerPieces::ROUND_LINE:
                    $unitAmount = $amount;
                    $totalAmount = Tools::ps_round($unitAmount * $this->product_quantity, _EPH_PRICE_DISPLAY_PRECISION_);
                    break;
                case CustomerPieces::ROUND_TOTAL:
                    $unitAmount = $amount;
                    $totalAmount = $unitAmount * $this->product_quantity;
                    break;
                default:
                    break;
            }

            $values[] = [
                static::$definition['primary'] => (int) $this->id,
                Tax::$definition['primary']    => (int) $idTax,
                'unit_amount'                  => (float) $unitAmount,
                'total_amount'                 => (float) $totalAmount,
            ];
        }

        if ($replace) {
            Db::getInstance()->delete('order_detail_tax', '`id_order_detail` = '.(int) $this->id);
        }

        return Db::getInstance()->insert('order_detail_tax', $values);
    }

    /**
     * @param string $hash
     *
     * @return array|bool|null|object
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 2.1.0.0
     */
    public static function getDownloadFromHash($hash) {

        if ($hash == '') {
            return false;
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('customer_piece_detail', 'od')
                ->leftJoin('product_download', 'pd', 'od.`id_product` = pd.`id_product`')
                ->where('od.`download_hash` = \'' . pSQL($hash) . '\'')
                ->where('pd.`active` = 1')
        );
    }

    /**
     * @param int $idCustomerPieceDetail
     * @param int $increment
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 2.1.0.0
     */
    public static function incrementDownload($idCustomerPieceDetail, $increment = 1) {

        return Db::getInstance()->update(
            'customer_piece_detail',
            [
                'download_nb' => ['type' => 'sql', 'value' => '`download_nb` + ' . (int) $increment],
            ],
            '`id_customer_piece_detail` = ' . (int) $idCustomerPieceDetail
        );
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 2.1.0.0
     * @throws PhenyxShopException
     */
    public function add($autoDate = true, $nullValues = true) {

        $this->product_wholesale_price = $this->getWholeSalePrice();
		if (!parent::add($autoDate, $nullValues)) {
            return false;
        }

        return true;
    }

    /**
     * @return float
     *
     * @since 2.1.0.0
     */
    public function getWholeSalePrice() {

        $product = new Product($this->id_product);
        $wholesalePrice = $product->wholesale_price;

        if ($this->id_product_attribute_id) {
            $combination = new Combination((int) $this->id_product_attribute_id);

            if ($combination && $combination->wholesale_price != '0.000000') {
                $wholesalePrice = $combination->wholesale_price;
            }

        }

        return $wholesalePrice;
    }

   
    /**
     * @param $product
     *
     * @since 2.1.0.0
     * @throws PhenyxShopException
     */
    protected function setVirtualProductInformation($product) {

        // Add some informations for virtual products
        $this->download_deadline = '0000-00-00 00:00:00';
        $this->download_hash = null;

        if ($idProductDownload = ProductDownload::getIdFromIdProduct((int) $product['id_product'])) {
            $productDownload = new ProductDownload((int) $idProductDownload);
            $this->download_deadline = $productDownload->getDeadLine();
            $this->download_hash = $productDownload->getHash();

            unset($productDownload);
        }

    }
	
	public static function getTaxCalculatorStatic($idOrderDetail)  {
        
		
		$computationMethod = 1;
        $taxes = [];
        if ($results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('customer_piece_detail')
                ->where('`id_customer_piece_detail` = '.(int) $idOrderDetail)
        )) {
            foreach ($results as $result) {
                $taxes[] = new Tax((int) $result['id_tax_rules_group']);
                $computationMethod = $result['tax_computation_method'];
            }

        }

        return new TaxCalculator($taxes, $computationMethod);
    }
	
	public static function getCrossSells($idProduct, $idLang, $limit = 12) {
		
        if (!$idProduct || !$idLang) {
            return [];
        }

        $front = true;
        if (!in_array(Context::getContext()->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $orders = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('o.`id_customer_piece`')
                ->from('customer_pieces', 'o')
                ->leftJoin('customer_piece_detail', 'od', 'od.`id_customer_piece` = o.`id_customer_piece`')
                ->where('o.`validate` = 1')
                ->where('od.`id_product` = '.(int) $idProduct)
        );

        if (count($orders)) {
            $list = '';
            foreach ($orders as $order) {
                $list .= (int) $order['id_customer_piece'].',';
            }
            $list = rtrim($list, ',');

            $orderProducts = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('DISTINCT od.`id_product` as produc_id, p.`id_product`, pl.`name`, pl.`link_rewrite`, p.`reference`, i.`id_image`, p.`show_price`')
                    ->select('cl.`link_rewrite` AS `category`, p.`ean13`, p.`out_of_stock`, p.`id_category_default`')
                    ->select(Combination::isFeatureActive() ? 'IFNULL(`product_attribute_shop`.`id_product_attribute`, 0) id_product_attribute' : '')
                    ->from('customer_piece_detail', 'od')
                    ->leftJoin('product', 'p', 'p.`id_product` = od.`id_product`')
                    ->join((Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) Context::getContext()->shop->id.')' : ''))
                    ->leftJoin('product_lang', 'pl', 'pl.`id_product` = od.`id_product` AND pl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('category_lang', 'cl', 'cl.`id_category` = p.`id_category_default` AND cl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('image', 'i', 'i.`id_product` = od.`id_product` ')
                    ->where('od.`id_customer_piece` IN ('.$list.')')
                    ->where('od.`id_product` != '.(int) $idProduct)
                    ->where($front ? '`p`.`visibility` IN ("both", "catalog")' : '')
                    ->orderBy('RAND()')
                    ->limit((int) $limit),
                true,
                false
            );

            $taxCalc = Product::getTaxCalculationMethod();
            if (is_array($orderProducts)) {
                foreach ($orderProducts as &$orderProduct) {
                    $orderProduct['image'] = Context::getContext()->link->getImageLink(
                        $orderProduct['link_rewrite'],
                        (int) $orderProduct['id_product'].'-'.(int) $orderProduct['id_image'],
                        ImageType::getFormatedName('medium')
                    );
                    $orderProduct['link'] = Context::getContext()->link->getProductLink(
                        (int) $orderProduct['id_product'],
                        $orderProduct['link_rewrite'],
                        $orderProduct['category'],
                        $orderProduct['ean13']
                    );
                    if ($taxCalc == 0 || $taxCalc == 2) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['id_product'], true, null);
                    } elseif ($taxCalc == 1) {
                        $orderProduct['displayed_price'] = Product::getPriceStatic((int) $orderProduct['id_product'], false, null);
                    }
                }


                return Product::getProductsProperties($idLang, $orderProducts);
            }
        }

        return [];
    }



}
