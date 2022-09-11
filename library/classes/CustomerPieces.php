<?php

/**
 * @since 2.1.0.0
 */
class CustomerPiecesCore extends ObjectModel {

    const ROUND_ITEM = 1;
    const ROUND_LINE = 2;
    const ROUND_TOTAL = 3;
	
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_pieces',
        'primary' => 'id_customer_piece',
        'fields'  => [
            'id_piece_origine'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'piece_type'                  => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'id_shop_group'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop'                     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang'                     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_currency'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
			'current_state'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                         ],
            'base_tax_excl'               => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_products_tax_excl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_products_tax_incl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_excl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_incl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_with_freight_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'shipping_no_subject'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'shipping_tax_subject'        => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'discount_rate'               => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_excl'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_incl'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_excl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_incl'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax_excl'              => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax'                   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax_incl'              => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'piece_margin'                => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'id_payment_mode'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'module'                      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'payment'                     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'total_paid'                  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'id_carrier'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_delivery'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_invoice'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'conversion_rate'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'shipping_number'             => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'],
            'last_transfert'              => ['type' => self::TYPE_INT],
            'round_mode'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'round_type'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'piece_number'                => ['type' => self::TYPE_INT],
            'delivery_number'             => ['type' => self::TYPE_INT],
            'validate'                    => ['type' => self::TYPE_BOOL],
            'observation'                 => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'note'                        => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'booked'                      => ['type' => self::TYPE_BOOL],
            'date_add'                    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'deadline_date'               => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'                    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

        ],
    ];

    public $id_piece_origine;
    public $piece_type;
    public $id_shop_group;
    public $id_shop;
    public $id_lang;
    public $id_currency;
    public $id_cart;
    public $id_customer;
	public $current_state;
    public $base_tax_excl;
    public $total_products_tax_excl;
    public $total_products_tax_incl;
    public $total_shipping_tax_excl;
    public $total_shipping_tax_incl;
    public $shipping_no_subject;
    public $shipping_tax_subject;
    public $total_with_freight_tax_excl;
    public $discount_rate;
    public $total_discounts_tax_excl;
    public $total_discounts_tax_incl;
    public $total_wrapping_tax_excl;
    public $total_wrapping_tax_incl;
    public $total_tax_excl;
    public $total_tax;
    public $total_tax_incl;
    public $piece_margin;
    public $id_payment_mode;
    public $module;
	public $payment;
    public $total_paid;
    public $id_carrier;
    public $id_address_delivery;
    public $id_address_invoice;
    public $conversion_rate;
    public $shipping_number;
    public $round_mode;
    public $round_type;
    public $piece_number;
    public $delivery_number;
    public $last_transfert;
    public $validate;
    public $observation;
    public $note;
    public $booked;
    public $date_add;
    public $deadline_date;
    public $date_upd;

    public $prefix;
    public $nameType;
    public $pieceOrigin;
    public $payment_mode;

    public $balance_due;
	
	public $pieceTypes = [];
	
	
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->prefix = $this->getPrefix();
			$this->nameType = $this->getTypeName();
			$this->pieceOrigin = $this->getPieceOrigin();
			$this->balance_due = $this->getBalanceDue();
			$this->payment_mode = PaymentMode::getPaymentModeNameById($this->id_payment_mode);
        }
		
		$this->pieceTypes = [
			'QUOTATION'    => $this->l('Devis'),
			'ORDER'        => $this->l('Commandes'),
			'DELIVERYFORM' => $this->l('Bons de Livraison'),
			'DOWNPINVOICE' => $this->l('Factures d‘accompte'),
			'INVOICE'      => $this->l('Factures'),
			'ASSET'        => $this->l('Avoirs'),
		];

    }
	
	public function getBalanceDue() {
		
		return $this->total_tax_incl - $this->total_paid;
	}
	
	public function getPieceOrigin() {
		
		if($this->id_piece_origine > 0) {
			$piece = new CustomerPieces($this->id_piece_origine);
			return Translate::getClassTranslation($piece->nameType, 'CustomerPieces').' '.Translate::getClassTranslation($piece->prefix, 'CustomerPieces').$piece->piece_number;
		}
	}
	
	public function getDiscountPercent() {
		return (1- ($this->total_products_tax_excl/($this->total_products_tax_excl - $this->total_discounts_tax_excl)))*100;
	}
	
	public static function getByReference($piece_number) {
        
		$orders = new PhenyxShopCollection('CustomerPieces');
        $orders->where('piece_number', '=', $piece_number);

        return $orders;
    }

    public function getPrefix() {

        switch ($this->piece_type) {

        case 'QUOTATION':
            return $this->l('DE');
            break;
        case 'ORDER':
            return $this->l('CD');
            break;
        case 'DELIVERYFORM':
            return $this->l('BL');
            break;
        case 'DOWNPINVOICE':
            return $this->l('FAA');
            break;
        case 'INVOICE':
            return $this->l('FA');
            break;
        case 'ASSET':
            return $this->l('DE');
            break;
        }

    }
	
	public function getTypeName() {

        switch ($this->piece_type) {

        case 'QUOTATION':
            return $this->l('Devis');
            break;
        case 'ORDER':
            return $this->l('Commande');
            break;
        case 'DELIVERYFORM':
            return $this->l('Bon de livraison');
            break;
        case 'DOWNPINVOICE':
            return $this->l('Facture Accompte');
            break;
        case 'INVOICE':
            return $this->l('Facture');
            break;
        case 'ASSET':
            return $this->l('Avoir');
            break;
        }

    }
	
	public function getCartRules()  {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_cart_rule', 'ocr')
                ->where('ocr.`id_order` = '.(int) $this->id)
        );
    }
    
	public function l($string, $idLang = null, Context $context = null) {

       
       $class = get_class($this);
		if (strtolower(substr($class, -4)) == 'core') {
            $class = substr($class, 0, -4);
        }
        
       return Translate::getClassTranslation($string, $class, $context);
    }
	
	public static function generateReference() {
        return strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
    }
	
    public function getFields() {

        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT', null, null, $this->id_shop);
        }

        return parent::getFields();
    }
	
    public function add($autoDate = true, $nullValues = true) {

        $this->roundAmounts();
		$this->piece_number = $this->generateNewInvoiceNumber();

        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = true) {

        $this->roundAmounts();

        return parent::update($nullValues);
    }
	
	public function delete() {

		if($this->piece_type == 'INVOICE' && $this->validate == 1) {
			return false;
		}
		
		$this->deletePieceDetatil();

		return parent::delete();
	}
	
	public function deletePieceDetatil() {
		
		if($this->getParentTransfert()) {
			return true;
		}
		Db::getInstance()->execute(
				'DELETE FROM `' . _DB_PREFIX_ . 'customer_piece_detail` 
                WHERE `id_customer_piece` = ' . (int) $this->id
		);
		
	}
	
	public static function getCartIdStatic($idOrder, $idCustomer = 0)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_cart`')
                ->from('customer_pieces')
                ->where('`id_customer_piece` = '.(int) $idOrder)
                ->where($idCustomer ? '`id_customer` = '.(int) $idCustomer : '')
        );
    }
	
	public function getParentTransfert() {
		
		$idParent = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('id_customer_piece')
            ->from('customer_pieces')
            ->where('`last_transfert` = '.(int) $this->id)
        );
		if(!empty($idParent)) {
			Db::getInstance()->execute(
				'UPDATE `' . _DB_PREFIX_ . 'customer_pieces` 
                SET `last_transfert` = 0
                WHERE `id_customer_piece` = ' . (int) $idParent
			);
			Db::getInstance()->execute(
				'UPDATE `' . _DB_PREFIX_ . 'customer_piece_detail` 
                SET `id_customer_piece` = ' . (int) $idParent
			);
			return true;

		}
		return false;
	}
	
	public static function getInvoicesbyidSession($idSession) {

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('id_customer_piece')
				->from('customer_pieces')
				->where('`id_education_session` = ' . (int) $idSession)
		);
	}
	
	public static function getRequest($pieceType = null) {

       $file = fopen("testgetRequest.txt","w");
		$context = Context::getContext();
		$query = new DbQuery();
		$query->select('a.*, (a.total_products_tax_incl+a.total_shipping_tax_incl+a.total_wrapping_tax_incl) as `total` , CONCAT(c.`firstname`, " ", c.`lastname`) AS `customer`, c.customer_code  as customer_code, c.company, pml.`name` AS `paymentMode`, osl.`name` AS `osname`,	os.`color`,
		(a.`total_products_tax_incl` + a.`total_shipping_tax_incl` + a.`total_wrapping_tax_incl` - a.`total_paid`) as `balanceDue`, case when a.validate = 1 then \'<div class="orderValidate"></div>\' else \'<div class="orderOpen"></div>\' end as validate, case when a.validate = 1 then 1 else 0 end as isLocked,  ca.`id_country`, ca.`address1`, ca.`address2`, ca.`postcode`, ca.`city`, cl.`name` AS country, case when a.booked = 1 then \'<div class="orderBook"><i class="icon icon-book" aria-hidden="true"></i></div>\' else \'<div class="orderUnBook"><i class="icon icon-times" aria-hidden="true" style="color:red;"></i></div>\' end as booked, case when a.booked = 1 then 1 else 0 end as isBooked');
        $query->from('customer_pieces', 'a');
        $query->leftJoin('customer', 'c', 'c.`id_customer` = a.`id_customer`');
        $query->leftJoin('payment_mode_lang', 'pml', 'pml.`id_payment_mode` = a.`id_payment_mode` AND pml.`id_lang` = ' . $context->language->id);
		$query->leftJoin('address', 'ca', 'a.`id_address_delivery` = ca.`id_address`');
		$query->leftJoin('country_lang', 'cl', 'cl.`id_country` = ca.`id_country` AND cl.`id_lang` = ' . $context->language->id);
		$query->leftJoin('customer_piece_state', 'os', 'os.`id_customer_piece_state` = a.`current_state`');
		$query->leftJoin('customer_piece_state_lang', 'osl', 'os.`id_customer_piece_state` = osl.`id_customer_piece_state` AND osl.`id_lang` = ' . $context->language->id);
		if($pieceType) {
			$query->where('a`piece_type` LIKE \''.$pieceType.'\'');
		}
        $query->orderBy('a.`date_add` DESC');
		fwrite($file,$query);
		$orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
		
		return $orders;
       

    }
	
	public function getCurrentState()
    {
        return $this->current_state;
    }
	
	public function getCurrentOrderState()  {
        if ($this->current_state) {
            return new CustomerPieceState($this->current_state);
        }

        return null;
    }
	
	public function generateNewInvoiceNumber() {
		
		
		if(empty($this->piece_number)) {
			$year = date('Y');
			$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
				->select('`piece_number`')
				->from('customer_pieces')
				->where('`piece_type` LIKE \'INVOICE\'')
				->orderBy('`id_customer_piece` DESC')
			);
		
			if(empty($lastValue)) {
				$lastValue =1;
				return $year.sprintf("%06s", $lastValue);
			}
			$test = substr($lastValue, 0, 4);
			if($test == $year) {
				return $lastValue+1;
			} else {
				$lastValue =1;
				return $year.sprintf("%06s", $lastValue);
			}
		}
		return $this->piece_number;
		
	}
	
	public static function getCreditCardInfo($idCustomerPiece) {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('order_credit_card')
                ->where('`id_order` = ' . (int) $idCustomerPiece)
        );
		
	}
	
	public static function getSepaInfo($idCustomerPiece) {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('ba.*')
                ->from('sepa', 's')
				->leftJoin('bank_account', 'ba', 'ba.id_bank_account = s.id_bank')
                ->where('`id_order` = ' . (int) $idCustomerPiece)
        );
		
	}
	
	public function generateCartNumber() {
		
		
		if(empty($this->piece_number)) {
			$year = date('Y');
			$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
				->select('`piece_number`')
				->from('customer_pieces')
				->where('`piece_type` LIKE \'ORDER\'')
				->orderBy('`id_customer_piece` DESC')
			);
		
			if(empty($lastValue)) {
				$lastValue =1;
				return $year.sprintf("%06s", $lastValue);
			}
			$test = substr($lastValue, 0, 4);
			if($test == $year) {
				return $lastValue+1;
			} else {
				$lastValue =1;
				return $year.sprintf("%06s", $lastValue);
			}
		}
		return $this->piece_number;
		
	}

    public function roundAmounts() {

        foreach (static::$definition['fields'] as $fieldName => $field) {

            if ($field['type'] === static::TYPE_FLOAT && isset($this->$fieldName)) {
                $this->$fieldName = Tools::ps_round($this->$fieldName, _EPH_PRICE_DATABASE_PRECISION_);
            }

        }

    }

    public function deleteProduct(CustomerPieces $customerPiece, CustomerPieceDetail $customerPieceDetail, $quantity) {

        if ($customerPiece->validate || !validate::isLoadedObject($customerPieceDetail)) {
            return false;
        }

        return $this->_deleteProduct($customerPieceDetail, (int) $quantity);
    }

    protected function _deleteProduct($customerPieceDetail, $quantity) {

        $productPriceTaxExcl = $customerPieceDetail->unit_price_tax_excl * $quantity;
        $productPriceTaxIncl = $customerPieceDetail->unit_price_tax_incl * $quantity;

        $this->total_products_tax_excl -= $productPriceTaxExcl;
        $this->total_products_tax_incl -= $productPriceTaxIncl;
        $this->roundAmounts();
        $customerPieceDetail->product_quantity -= (int) $quantity;

        if ($customerPieceDetail->product_quantity == 0) {

            if (!$customerPieceDetail->delete()) {
                return false;
            }

            return $this->update();
        } else {
            $customerPieceDetail->total_price_tax_incl -= $productPriceTaxIncl;
            $customerPieceDetail->total_price_tax_excl -= $productPriceTaxExcl;
        }

        return $customerPieceDetail->update() && $this->update();
    }
	
    public function getProductsDetail() {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('customer_piece_detail', 'od')
                ->leftJoin('product', 'p', 'p.`id_product` = od.`id_product`')
                ->leftJoin('product_shop', 'ps', 'ps.`id_product` = od.`id_product`')
                ->where('od.`id_customer_piece` = ' . (int) $this->id)
        );
    }

    public function getProducts($products = false, $selectedProducts = false, $selectedQty = false) {

        if (!$products) {
            $products = $this->getProductsDetail();
        }
		$customizedDatas = Product::getAllCustomizedDatas($this->id_cart);
        $resultArray = [];

        foreach ($products as $row) {
            // Change qty if selected

            if ($selectedQty) {
                $row['product_quantity'] = 0;

                if (is_array($selectedProducts) && !empty($selectedProducts)) {

                    foreach ($selectedProducts as $key => $idProduct) {

                        if ($row['id_customer_piece_detail'] == $idProduct) {
                            $row['product_quantity'] = (int) $selectedQty[$key];
                        }

                    }

                }

                if (!$row['product_quantity']) {
                    continue;
                }

            }
			
			$this->setProductImageInformations($row);
            $this->setProductCurrentStock($row);

            // Backward compatibility 1.4 -> 1.5
            $this->setProductPrices($row);

            $this->setProductCustomizedDatas($row, $customizedDatas);

            // Add information for virtual product

            if ($row['download_hash'] && !empty($row['download_hash'])) {
                $row['filename'] = ProductDownload::getFilenameFromIdProduct((int) $row['id_product']);
                // Get the display filename
                $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
            }

            $row['id_address_delivery'] = $this->id_address_delivery;

            /* Stock product */
            $resultArray[(int) $row['id_customer_piece_detail']] = $row;
        }

        return $resultArray;
    }
	
	public function setProductPrices(&$row)
    {
        $taxCalculator = CustomerPieceDetail::getTaxCalculatorStatic((int) $row['id_customer_piece_detail']);
        $row['tax_calculator'] = $taxCalculator;
        $row['tax_rate'] = $taxCalculator->getTotalRate();

        $row['unit_tax_excl'] = Tools::ps_round($row['unit_tax_excl'], 2);
        $row['unit_tax_incl'] = Tools::ps_round($row['unit_tax_incl'], 2);

        $row['product_price_wt_but_ecotax'] = $row['unit_tax_incl'] - $row['ecotax'];

        $row['total_wt'] = $row['total_tax_incl'];
        $row['total_price'] = $row['total_tax_excl'];
    }
	
	protected function setProductCustomizedDatas(&$product, $customizedDatas)
    {
        $product['customizedDatas'] = null;
        if (isset($customizedDatas[$product['id_product']][$product['id_product_attribute']])) {
            $product['customizedDatas'] = $customizedDatas[$product['id_product']][$product['id_product_attribute']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }
	
	protected function setProductCurrentStock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
            && (int) $product['advanced_stock_management'] == 1
            && (int) $product['id_warehouse'] > 0) {
            $product['current_stock'] = StockManagerFactory::getManager()->getProductPhysicalQuantities($product['id_product'], $product['id_product_attribute'], (int) $product['id_warehouse'], true);
        } else {
            $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute'], (int) $this->id_shop);
        }
    }
	
	protected function setProductImageInformations(&$product)
    {
        if (isset($product['id_product_attribute']) && $product['id_product_attribute']) {
            $idImage = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('image_shop.`id_image`')
                    ->from('product_attribute_image', 'pai')
                    ->join(Shop::addSqlAssociation('image', 'pai', true))
                    ->leftJoin('image', 'i', 'i.`id_image` = pai.`id_image`')
                    ->where('`id_product_attribute` = '.(int) $product['id_product_attribute'])
                    ->orderBy('i.`position` ASC')
            );
        }

        if (!isset($idImage) || !$idImage) {
            $idImage = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('image_shop.`id_image`')
                    ->from('image', 'i')
                    ->join(Shop::addSqlAssociation('image', 'i', true, 'image_shop.`cover` = 1'))
                    ->where('i.`id_product` = '.(int) $product['id_product'])
            );
        }

        $product['image'] = null;
        $product['image_size'] = null;

        if ($idImage) {
            $product['image'] = new Image($idImage);
        }
    }


    public function getVirtualProducts() {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`, `download_hash`, `download_deadline`')
                ->from('customer_piece_detail', 'od')
                ->where('od.`id_customer_piece` = ' . (int) $this->id)
                ->where('`download_hash` <> \'\'')
        );
    }

    public function isVirtual($strict = true) {

        $products = $this->getProducts();

        if (count($products) < 1) {
            return false;
        }

        $virtual = true;

        foreach ($products as $product) {

            if ($strict === false && (bool) $product['is_virtual']) {
                return true;
            }

            $virtual &= (bool) $product['is_virtual'];
        }

        return $virtual;
    }

    public static function getCustomerOrders($idCustomer, $showHiddenStatus = false, Context $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }
		$pieceTypes = [
			'QUOTATION'    =>	'Devis',
			'ORDER'        => 'Commandes',
			'DELIVERYFORM' => 'Bons de Livraison',
			'DOWNPINVOICE' => 'Factures d‘accompte',
			'INVOICE'      => 'Factures',
			'ASSET'        => 'Avoirs',
		];
		$orders = [];
		foreach($pieceTypes as $key => $pieceType) {
			
			$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
                	->select('o.*, (SELECT SUM(od.`product_quantity`) FROM `' . _DB_PREFIX_ . 'customer_piece_detail` od WHERE od.`id_customer_piece` = o.`id_customer_piece`) nb_products, osl.name as order_state, os.`color` AS `order_state_color`')
                	->from('customer_pieces', 'o')
					->leftJoin('customer_piece_state', 'os', 'os.`id_customer_piece_state` = o.`current_state`')
					->leftJoin('customer_piece_state_lang', 'osl', 'o.`current_state` = osl.`id_customer_piece_state` AND osl.`id_lang` = '.(int) $context->language->id)
                	->where('o.`id_customer` = ' . (int) $idCustomer . ' ' . Shop::addSqlRestriction(Shop::SHARE_ORDER))
					->where('`piece_type` LIKE \''.$key.'\'')
					->groupBy('o.`id_customer_piece`')
				->orderBy('o.`date_add` DESC')
			);
			if (is_array($res) && count($res)) {
            	$orders[$key] = [
					'orders' => $res,
					'key' => $pieceType
				];
        	}
		}

        return $orders;
    }
	
	public static function getOrdersbyIdCustomer($idCustomer) {

        
		$collection = new PhenyxShopCollection('CustomerPieces');
        $collection->where('id_customer', '=', (int) $idCustomer);
		$collection->where('piece_type', 'like', 'INVOICE');
		foreach ($collection as $order) {
			$order->payment_mode = PaymentMode::getPaymentModeNameById($order->id_payment_mode);
			$order->balance_due = $order->total_tax_incl - $order->total_paid;
		}

        return $collection;
    }
	
	public static function getOrderTotalbyIdCustomer($idCustomer) {

        $total = 0;
		$collection = new PhenyxShopCollection('CustomerPieces');
        $collection->where('id_customer', '=', (int) $idCustomer);
		$collection->where('piece_type', 'like', 'INVOICE');
		foreach ($collection as $order) {
			$total = $total + $order->total_paid;
		}

        return $total;
    }


    public function getTotalProductsWithoutTaxes($products = false) {

        return $this->total_products_tax_excl;
    }

    public function getTotalProductsWithTaxes($products = false) {

        if ($this->total_products_tax_incl != '0.00' && !$products) {
            return $this->total_products_tax_incl;
        }

        /* Retro-compatibility (now set directly on the validateOrder() method) */

        if (!$products) {
            $products = $this->getProductsDetail();
        }

        $return = 0;

        foreach ($products as $row) {
            $return += $row['total_price_tax_incl'];
        }

        if (!$products) {
            $this->total_products_tax_incl = $return;
            $this->update();
        }

        return $return;
    }

    public static function getCustomerNbOrders($idCustomer) {

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('COUNT(`id_customer_piece`) AS `nb`')
                ->from('customer_pieces')
                ->where('`id_customer` = ' . (int) $idCustomer . ' ' . Shop::addSqlRestriction())
        );

        return isset($result['nb']) ? $result['nb'] : 0;
    }

    public function getTotalWeight() {

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`product_weight` * `product_quantity`)')
                ->from('customer_piece_detail')
                ->where('`id_customer_piece` = ' . (int) $this->id)
        );

        return (float) $result;
    }

    public static function getInvoice($idInvoice) {

        Tools::displayAsDeprecated();

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`invoice_number`, `id_customer_piece`')
                ->from('orders')
                ->where('`invoice_number` = ' . (int) $idInvoice)
        );
    }

    public function getWsOrderRows() {

        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_customer_piece_detail` AS `id`')
                ->select('`id_product`')
                ->select('`product_price`')
                ->select('`id_customer_piece`')
                ->select('`id_product_attribute`')
                ->select('`product_quantity`')
                ->select('`product_name`')
                ->select('`product_reference`')
                ->select('`product_ean13`')
                ->select('`product_upc`')
                ->select('`unit_price_tax_incl`')
                ->select('`unit_price_tax_excl`')
                ->from('customer_piece_detail')
                ->where('`id_customer_piece` = ' . (int) $this->id)
        );

        return $result;
    }

    public function deleteAssociations() {

        return Db::getInstance()->delete('customer_piece_detail', '`id_customer_piece` = ' . (int) $this->id) !== false;
    }
	
	public static function getLastInvoiceNumber() {
		
		$sql = 'SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \''._DB_NAME_.'\' AND   TABLE_NAME   = \''._DB_PREFIX_.'customer_pieces\'';
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);
	}
	
	public static function mergeCartTable(Order $order, $pieceNumber, $valid = null) {
		
		$cart = new Cart($order->id_cart);
		if (!Validate::isLoadedObject($carte)) {
			return null;
		}
		
		$piece = new CustomerPieces();
		$piece->id_piece_origine = 0;
		$piece->piece_type = 'ORDER';
		$piece->id_shop_group = $cart->id_shop_group;
		$piece->id_shop = $cart->id_shop;
		$piece->id_lang = $cart->id_lang;
		$piece->id_currency = $cart->id_currency;
		$piece->id_cart = $order->id_cart;
		$piece->id_customer = $cart->id_customer;
		$piece->base_tax_excl = $order->total_products + $order->total_discounts_tax_excl;
		$piece->total_products_tax_excl = $order->total_products;
		$piece->total_products_tax_incl = $order->total_products_wt;
		$piece->total_shipping_tax_excl = $order->total_shipping_tax_excl;
		$piece->shipping_tax_subject = $order->total_shipping_tax_excl;
		$piece->total_with_freight_tax_excl = $order->total_products+$order->total_shipping_tax_excl; 
		$piece->total_shipping_tax_incl = $order->total_shipping_tax_incl;
		$piece->total_discounts_tax_excl = $order->total_discounts_tax_excl;
		$piece->total_discounts_tax_incl = $order->total_discounts_tax_incl;
		$piece->total_wrapping_tax_excl = $order->total_wrapping_tax_excl;
		$piece->total_wrapping_tax_incl = $order->total_wrapping_tax_incl;
		$piece->total_tax_excl = $order->total_products+$order->total_shipping_tax_excl+$order->total_wrapping_tax_excl;
		$piece->total_tax_incl = $order->total_products_wt+$order->total_shipping_tax_incl+$order->total_wrapping_tax_incl;
		$piece->total_tax = $piece->total_tax_incl-$piece->total_tax_excl;
		$piece->id_payment_mode = CustomerPieces::getPaymentModeByModule($order->module);
		$piece->module = $order->payment;
		$piece->total_paid = $order->total_paid;
		$piece->id_carrier = $order->id_carrier;
		$piece->id_address_delivery = $order->id_address_delivery;
    	$piece->id_address_invoice = $order->id_address_invoice;
    	$piece->conversion_rate = $order->conversion_rate;
    	$piece->shipping_number = $order->shipping_number;
    	$piece->round_mode = $order->round_mode;
    	$piece->round_type = $order->round_type;
    	$piece->piece_number = $cart->id;
    	$piece->delivery_number = $order->delivery_number;
		$piece->last_transfert = $pieceNumber;
		$piece->validate = $valid;
		$piece->date_add = $cart->date_add;
    	$piece->deadline_date = $cart->date_add;
    	$piece->date_upd = $cart->date_upd;
		if($piece->add()) {
			return $piece->id;
		} else {
			return false;
		}
		
		
		
	}
	
	public static function getValidOrderState() {
		
		$validates = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('id_order_state')
            ->from('order_state')
			->where('logable = 1 ')
        );
		$return = [];
		foreach($validates as $key => $value) {
			$return[] = $value['id_order_state'];
		}
		
		return implode(',',$return);
	}
	
	public function getUniqReference()  {
        $query = new DbQuery();
        $query->select('MIN(id_customer_piece) as min, MAX(id_customer_piece) as max');
        $query->from('customer_pieces');
        $query->where('id_cart = '.(int) $this->id_cart);

        $order = Db::getInstance()->getRow($query);
		$prefix = $this->getPrefix();
        return $prefix.$this->piece_number;
    }
	
	 public static function getUniqReferenceOf($idOrder)  {
        
		 $order = new CustomerPieces($idOrder);

        return $order->getUniqReference();
    }
	
	public static function generateInvoiceNumber(Order $order) {
		
		
		$date = new dateTime($order->date_add);
		$year =  $date->format('Y');
		$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`piece_number`')
            ->from('customer_pieces')
			->where('`piece_type` LIKE \'INVOICE\'')
            ->orderBy('`id_customer_piece` DESC')
        );
		
		if(empty($lastValue)) {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		$test = substr($lastValue, 0, 4);
		if($test == $year) {
			return $lastValue+1;
		} else {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		
	}
	
	public static function isMergeOrderTable($idOrder) {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_customer_piece`')
                ->from('customer_pieces')
			   ->where('`piece_type` LIKE \'INVOICE\'')
                ->where('`id_order` = '.(int)$idOrder)
        );
	}
	
	public static function getIncrementByType($type) {
		
		
		
		$year = date('Y');
		$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`piece_number`')
            ->from('customer_pieces')
			->where('`piece_type` LIKE \''.$type.'\'')
            ->orderBy('`id_customer_piece` DESC')
        );
		
		if(empty($lastValue)) {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		$test = substr($lastValue, 0, 4);
		if($test == $year) {
			return $lastValue+1;
		} else {
			$lastValue =1;
			return $year.sprintf("%06s", $lastValue);
		}
		
	}
	
	public static function getPaymentModeByModule($moduleName) {
		
		$module = Module::getInstanceByName($moduleName);
            if ($module instanceof Module) {
                return PaymentMode::getPaymentModeByModuleId($module->id, $moduleName);
            }
	}
	
	public static function generatePieceDetail($idOrder, $pieceCart) {
		
		$orderDeatils = CustomerPieces::getProductsOrderDetail($idOrder);
		if(!empty($orderDeatils)) {
			$error = false;
			foreach($orderDeatils as $detail) {
				
				$pieceDetail = new CustomerPieceDetail();
				$pieceDetail->id_customer_piece = $pieceCart;
				$pieceDetail->id_product = $detail['product_id'];
    			$pieceDetail->id_product_attribute = $detail['product_attribute_id'];
    			$pieceDetail->product_name = $detail['product_name'];
    			$pieceDetail->product_quantity = $detail['product_quantity'];
				$pieceDetail->original_price_tax_excl = $detail['original_product_price'];
				$pieceDetail->original_price_tax_incl = $detail['original_product_price']*(1+$detail['rateTaxe']/100);
    			$pieceDetail->unit_tax_excl = $detail['unit_price_tax_excl'];
				$pieceDetail->unit_tax_incl = $detail['unit_price_tax_incl'];
    			$pieceDetail->total_tax_excl = $detail['total_price_tax_excl'];
				$pieceDetail->total_tax = $detail['total_line_tax'];
    			$pieceDetail->total_tax_incl = $detail['total_price_tax_incl'];
    			$pieceDetail->reduction_percent = $detail['reduction_percent'];
    			$pieceDetail->reduction_amount_tax_excl = $detail['reduction_amount_tax_excl'];
    			$pieceDetail->reduction_amount_tax_incl = $detail['reduction_amount_tax_incl'];
    			$pieceDetail->product_ean13 = $detail['product_ean13'];
    			$pieceDetail->product_upc = $detail['product_upc'];
    			$pieceDetail->product_reference = $detail['product_reference'];
    			$pieceDetail->product_weight = $detail['product_weight'];
    			$pieceDetail->ecotax = $detail['ecotax'];
    			$pieceDetail->download_hash = $detail['download_hash'];
    			$pieceDetail->download_nb = $detail['download_nb'];
    			$pieceDetail->download_deadline = $detail['download_deadline'];
    			$pieceDetail->tax_rate = $detail['rateTaxe'];
    			$pieceDetail->id_tax_rules_group = $detail['id_tax'];
    			$pieceDetail->id_warehouse = $detail['id_warehouse'];
    			$pieceDetail->product_wholesale_price = $detail['original_wholesale_price'];
				if(!$pieceDetail->add()) {
					$error = true;
				}
			}
			if($error) {
				return false;
			}
			return true;
			
		}
		return true;
		
	}
	
	public static function getProductsOrderDetail($idOrder) {
		
       	return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('od.*, odt.`id_tax`, odt.`total_amount` as `total_line_tax`, t.`rate` as `rateTaxe`')
            ->from('order_detail', 'od')
			->leftjoin('order_detail_tax', 'odt', 'odt.`id_order_detail` = od.`id_order_detail`')
			->leftjoin('tax', 't', 't.`id_tax` = odt.`id_tax`')
            ->where('od.`id_order` = '.(int) $idOrder)
        );
    }
	
	public static function getPieceIdbyTransfert($lastTransfert) {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('id_customer_piece')
            ->from('customer_pieces')
            ->where('`piece_number` = '.(int) $lastTransfert)
        );
	}
	
	public static function generatePayment(Order $order, CustomerPieces $piece) {
		
		
		$payments = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('order_payment')
                    ->where('`order_reference` = \''.pSQL($order->reference).'\'')
            );
		
		if(is_array($payments) && sizeof($payments)) {
			
			foreach($payments as $payment) {
			
				$piecePayment = new Payment();
				$piecePayment->id_currency = $payment['id_currency'];
    			$piecePayment->amount = $payment['amount'];
				$piecePayment->id_payment_mode = $piece->id_payment_mode;
    			$piecePayment->payment_method = $payment['payment_method'];
				$piecePayment->conversion_rate = $payment['conversion_rate'];
    			$piecePayment->booked = 0;
				$piecePayment->date_add = $payment['date_add'];
				if($piecePayment->add()) {
					$paymentDetail = new PaymentDetails();
					$paymentDetail->id_payment = $piecePayment->id;
    				$paymentDetail->id_customer_piece = $piece->id;
    				$paymentDetail->amount = $payment['amount'];
    				$paymentDetail->date_add = $payment['date_add'];
					if($paymentDetail->add()) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			}
		} else {
			
			$piecePayment = new Payment();
			$piecePayment->id_currency = $order->id_currency;
    		$piecePayment->amount = $order->total_paid;
			$piecePayment->id_payment_mode = $piece->id_payment_mode;
    		$piecePayment->payment_method = $order->payment;
			$piecePayment->conversion_rate = $order->conversion_rate;
    		$piecePayment->booked = 0;
			$piecePayment->date_add = $order->date_add;
			
			if($piecePayment->add()) {
				$paymentDetail = new PaymentDetails();
				$paymentDetail->id_payment = $piecePayment->id;
    			$paymentDetail->id_customer_piece = $piece->id;
    			$paymentDetail->amount = $order->total_paid;
    			$paymentDetail->date_add = $order->date_add;
				if($paymentDetail->add()) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		return true;
	}
	
	public static  function generatePhenyxInvoices() {

		$context = Context::getContext();
		$educationPCSupply = new FormatPackSupplies(1);
		$educationIpadSupply = new FormatPackSupplies(2);
		$educationTabletteSupply = new FormatPackSupplies(3);
		$licenses = License::getLiceneCollection();
		$invoices = [];

		foreach ($licenses as $licence) {
			$invoices[$licence->id] = $licence->getPhenyxInvoices();
		}

		foreach ($invoices as $of => $sessions) {

			if (is_array($sessions) && count($sessions)) {
				$license = new License($of);
				$customer = new Customer($license->id_customer);
				$idAddress = Address::getFirstCustomerAddressId($customer->id);

				foreach ($sessions as $session => $details) {
					$piceCost1 = 0;
					$piceCost2 = 0;
					$piceCost3 = 0;
					$piceCost4 = 0;
					$totalPack1 = 0;
					$totalPack2 = 0;
					$totalPack3 = 0;
					$totalPack4 = 0;
					$totalPack5 = 0;
					$pack1 = 0;
					$pack2 = 0;
					$pack3 = 0;
					$pack4 = 0;
					$pack5 = 0;
					$supply1 = 0;
					$supply2 = 0;
					$supply3 = 0;
					$piceCost = 0;

					foreach ($details as $key => $detail) {
						$formatpack = new FormatPack($detail['id_formatpack']);
						if($formatpack->id ==1) {
							$totalPack1 = $totalPack1 + $formatpack->price;
							$pack1 ++;
							if($detail['id_education_supplies'] == 1) {
								$piceCost1 = $piceCost1 + $educationPCSupply->pamp;
								$supply1--;
							}
							if($detail['id_education_supplies'] == 2) {
								$piceCost1 = $piceCost1 + $educationIpadSupply->pamp;
								$supply2--;
							}
							if($detail['id_education_supplies'] == 6) {
								$piceCost1 = $piceCost1 + $educationTabletteSupply->pamp;
								$supply3--;
							}
						} else if($formatpack->id == 2) {
							$totalPack2 = $totalPack2 + $formatpack->price;
							$pack2++;
							if($detail['id_education_supplies'] == 1) {
								$piceCost2 = $piceCost2 + $educationPCSupply->pamp;
								$supply1--;
							}
							if($detail['id_education_supplies'] == 2) {
								$piceCost2 = $piceCost2 + $educationIpadSupply->pamp;
								$supply2--;
							}
							if($detail['id_education_supplies'] == 6) {
								$piceCost2 = $piceCost2 + $educationTabletteSupply->pamp;
								$supply3--;
							}
						} else if($formatpack->id == 3){
							$piceCost3 = $piceCost3 +80;
							$totalPack3 = $totalPack3 + $formatpack->price;
							$pack3++;
						} else if($formatpack->id == 4){
							$totalPack4 = $totalPack4 + $formatpack->price;
							$pack4++;
						} else if($formatpack->id == 5){
							$totalPack5 = $totalPack5 + $formatpack->price;
							$pack5++;
						}
						
						

						
						$id_education_session = $detail['id_education_session'];
						$session_date = $detail['session_date'];
					}
					
					$supply = new FormatPackSupplies(1);
					$supply->stock = $supply->stock + (int)$supply1;
					$supply->sold = $supply->sold -(int)$supply1;
					$supply->update();
				
					$supply = new FormatPackSupplies(2);
					$supply->stock = $supply->stock + (int)$supply2;
					$supply->sold = $supply->sold -(int)$supply2;
					$supply->update();
					$supply = new FormatPackSupplies(3);
					$supply->stock = $supply->stock + (int)$supply3;
					$supply->sold = $supply->sold -(int)$supply3;
					$supply->update();
					
					$total = $totalPack1 + $totalPack2 + $totalPack3;

					$newPiece = new CustomerPieces();
					$newPiece->piece_type = 'INVOICE';
					$newPiece->note = $session;
					$newPiece->is_education = 0;
					$newPiece->id_currency = 1;
					$newPiece->id_customer = $customer->id;
					$newPiece->id_address_delivery = $idAddress;
					$newPiece->id_address_invoice = $idAddress;
					$newPiece->id_education_session = $id_education_session;
					$newPiece->id_student_education = 0;
					$newPiece->id_payment_mode = 1;
					$newPiece->base_tax_excl = $total;
					$newPiece->total_products_tax_excl = $total;
					$newPiece->total_products_tax_incl = $total * 1.2;
					$newPiece->total_with_freight_tax_excl = $newPiece->total_products_tax_incl;
					$newPiece->total_tax_excl = $total;
					$newPiece->total_tax = $newPiece->total_products_tax_incl - $newPiece->total_products_tax_excl;
					$newPiece->total_tax_incl = $newPiece->total_products_tax_incl;
					$newPiece->piece_margin = $newPiece->total_products_tax_excl - $piceCost1 -$piceCost2 - $piceCost3 - $piceCost4;
					$newPiece->total_paid = 0;
					$newPiece->conversion_rate = 1;
					$newPiece->validate = 0;
					$newPiece->booked = 0;
					$newPiece->id_book_record = 0;
					$newPiece->id_shop = (int) $context->shop->id;
					$newPiece->id_shop_group = (int) $context->shop->id_shop_group;
					$newPiece->id_lang = $context->language->id;
					$newPiece->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
					$newPiece->round_type = Configuration::get('PS_ROUND_TYPE');
					$newPiece->date_add = $session_date;
					$result = $newPiece->add();

					if ($result) {
						
						if($pack1 > 0) {
							
							$unitCost1 = round($piceCost1/$pack1, 3);
							$formatpack = new FormatPack(1);
							$object = new CustomerPieceDetail();
							$object->id_customer_piece = $newPiece->id;
							$object->id_warehouse = 0;
							$object->id_formatpack = $formatpack->id;
							$object->product_name = $formatpack->name[$context->language->id];
							$object->product_quantity = $pack1;
							$object->original_price_tax_excl = $formatpack->price;
							$object->original_price_tax_incl = $formatpack->price * 1.2;
							$object->unit_tax_excl = $formatpack->price;
							$object->unit_tax_incl = $object->original_price_tax_incl;
							$object->total_tax_excl = $object->original_price_tax_excl * $pack1;
							$object->total_tax_incl = $object->original_price_tax_incl *$pack1;
							$object->tax_rate = 20;
							$object->total_tax = $object->total_tax_incl - $object->total_tax_excl;
							$object->product_reference = $formatpack->reference;
							$object->product_wholesale_price = $unitCost1;
							$result = $object->add();
						}
						if($pack2 > 0) {
							$unitCost2 = round($piceCost2/$pack2,3);
							$formatpack = new FormatPack(2);
							$object = new CustomerPieceDetail();
							$object->id_customer_piece = $newPiece->id;
							$object->id_warehouse = 0;
							$object->id_formatpack = $formatpack->id;
							$object->product_name = $formatpack->name[$context->language->id];
							$object->product_quantity = $pack2;
							$object->original_price_tax_excl = $formatpack->price;
							$object->original_price_tax_incl = $formatpack->price * 1.2;
							$object->unit_tax_excl = $formatpack->price;
							$object->unit_tax_incl = $object->original_price_tax_incl;
							$object->total_tax_excl = $object->original_price_tax_excl * $pack2;
							$object->total_tax_incl = $object->original_price_tax_incl *$pack2;
							$object->tax_rate = 20;
							$object->total_tax = $object->total_tax_incl - $object->total_tax_excl;
							$object->product_reference = $formatpack->reference;
							$object->product_wholesale_price = $unitCost2;
							$result = $object->add();
						}
						if($pack3 > 0) {
							$unitCost3 = round($piceCost3/$pack3, 3);
							$formatpack = new FormatPack(3);
							$object = new CustomerPieceDetail();
							$object->id_customer_piece = $newPiece->id;
							$object->id_warehouse = 0;
							$object->id_formatpack = $formatpack->id;
							$object->product_name = $formatpack->name[$context->language->id];
							$object->product_quantity = $pack3;
							$object->original_price_tax_excl = $formatpack->price;
							$object->original_price_tax_incl = $formatpack->price * 1.2;
							$object->unit_tax_excl = $formatpack->price;
							$object->unit_tax_incl = $object->original_price_tax_incl;
							$object->total_tax_excl = $object->original_price_tax_excl * $pack3;
							$object->total_tax_incl = $object->original_price_tax_incl *$pack3;
							$object->tax_rate = 20;
							$object->total_tax = $object->total_tax_incl - $object->total_tax_excl;
							$object->product_reference = $formatpack->reference;
							$object->product_wholesale_price = $unitCost3;
							$result = $object->add();
						}
						
						if($pack4 > 0) {
							$formatpack = new FormatPack(4);
							$object = new CustomerPieceDetail();
							$object->id_customer_piece = $newPiece->id;
							$object->id_warehouse = 0;
							$object->id_formatpack = $formatpack->id;
							$object->product_name = $formatpack->name[$context->language->id];
							$object->product_quantity = $pack4;
							$object->original_price_tax_excl = $formatpack->price;
							$object->original_price_tax_incl = $formatpack->price * 1.2;
							$object->unit_tax_excl = $formatpack->price;
							$object->unit_tax_incl = $object->original_price_tax_incl;
							$object->total_tax_excl = $object->original_price_tax_excl * $pack4;
							$object->total_tax_incl = $object->original_price_tax_incl *$pack4;
							$object->tax_rate = 20;
							$object->total_tax = $object->total_tax_incl - $object->total_tax_excl;
							$object->product_reference = $formatpack->reference;
							$result = $object->add();
						}
						if($pack5 > 0) {
							$formatpack = new FormatPack(5);
							$object = new CustomerPieceDetail();
							$object->id_customer_piece = $newPiece->id;
							$object->id_warehouse = 0;
							$object->id_formatpack = $formatpack->id;
							$object->product_name = $formatpack->name[$context->language->id];
							$object->product_quantity = $pack5;
							$object->original_price_tax_excl = $formatpack->price;
							$object->original_price_tax_incl = $formatpack->price * 1.2;
							$object->unit_tax_excl = $formatpack->price;
							$object->unit_tax_incl = $object->original_price_tax_incl;
							$object->total_tax_excl = $object->original_price_tax_excl * $pack5;
							$object->total_tax_incl = $object->original_price_tax_incl *$pack5;
							$object->tax_rate = 20;
							$object->total_tax = $object->total_tax_incl - $object->total_tax_excl;
							$object->product_reference = $formatpack->reference;
							$result = $object->add();
						}

					}

				}

			}

		}

		return true;

	}
	
	public static function getPhenyxSupplies() {
		
		$licenses = License::getLiceneCollection();
		$invoices = [];

		foreach ($licenses as $licence) {
			$invoices[$licence->id] = $licence->getPhenyxSupplies();
		}
		
		$supply1 = 0;
		$supply2 = 0;
		$supply3 = 0;
		foreach ($invoices as $of => $sessions) {
			
			foreach ($sessions as $session => $details) {
				
				
				
				foreach ($details as $key => $detail) {
					if($detail['id_education_supplies'] == 1) {
						$supply1++;
					}
					if($detail['id_education_supplies'] == 2) {
						$supply2++;
					}
					if($detail['id_education_supplies'] == 6) {
						$supply3++;
					}

				}
				
			}
			
			
		}
		$supply = new FormatPackSupplies(1);
		$supply->stock_previsionnel = (int)$supply1;
		$supply->update();
				
		$supply = new FormatPackSupplies(2);
		$supply->stock_previsionnel =  (int)$supply2;
		$supply->update();
		$supply = new FormatPackSupplies(3);
		$supply->stock_previsionnel = (int)$supply3;
		$supply->update();
		
		return true;
		
	}
	
	public function getStaticPrefix() {

        switch ($this->piece_type) {

        case 'QUOTATION':
            return $this->l('DE');
            break;
        case 'ORDER':
            return $this->l('CD');
            break;
        case 'DELIVERYFORM':
            return $this->l('BL');
            break;
        case 'DOWNPINVOICE':
            return $this->l('FAC');
            break;
        case 'INVOICE':
            return $this->l('FA');
            break;
        case 'ASSET':
            return $this->l('AV');
            break;
        }

    }
	public function getStaticPieceName() {

		switch ($this->piece_type) {

		case 'QUOTATION':
			return $this->l('Devis');
			break;
		case 'ORDER':
			return $this->l('Commande');
			break;
		case 'DELIVERYFORM':
			return $this->l('Bon de Livraison');
			break;
		case 'DOWNPINVOICE':
			return $this->l('Facture Accompte');
			break;
		case 'INVOICE':
			return $this->l('Facture');
			break;
		case 'ASSET':
			return $this->l('Avoir');
			break;
		}

	}

	public function printPdf() {

		$idPiece = $this->id;
		$context = Context::getContext();
		$this->prefix = $this->getStaticPrefix();
		$this->nameType = $this->getStaticPieceName();
		$productDetails = $this->getProductsDetail();
		$customer = new Customer($this->id_customer);

		$model = Configuration::get('PS_INVOICE_MODEL');
		$template = new InvoiceModel($model);
		$address = new Address($this->id_address_invoice);

		$fileName = $this->nameType."_" . $this->prefix . $this->piece_number . '_' . $customer->lastname . '_' . $customer->firstname . '.pdf';

		

		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		$pathLogo = $this->getLogo();

		$width = 0;
		$height = 0;

		if (!empty($pathLogo)) {
			list($width, $height) = getimagesize($pathLogo);
		}

		// Limit the height of the logo for the PDF render
		$maximumHeight = 150;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$payments = Payment::getByCustomerPieceId($this->id, $context->language->id);
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 120,
			'margin_bottom' => 75,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);

		$data = $context->smarty->createTemplate(_PS_PDF_DIR_.'headertemplate.tpl');

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $pathLogo,
				'width_logo'     => $width,
				'height_logo'    => $height,
				'piece'          => $this,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($this->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLHeader($data->fetch());

		$data = $context->smarty->createTemplate(_PS_PDF_DIR_.'footertemplate.tpl');

		$data->assign(
			[
				'company'        => $context->company,
				'free_text'      => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'      => $pathLogo,
				'piece'          => $this,
				'payments'       => $payments,
				'nameType'       => $this->getStaticPieceName($this->piece_type),
				'productDetails' => $productDetails,
				'customer'       => $customer,
				'address'        => $address,
			]
		);
		$mpdf->SetHTMLFooter($data->fetch(), 'O');

		$data = $context->smarty->createTemplate(_PS_PDF_DIR_.'pdf.css.tpl');
		$data->assign(
			[
				'color' => $template->color,
			]
		);
		$stylesheet = $data->fetch();

		$data = $context->smarty->createTemplate(_PS_PDF_DIR_.'bodytemplate.tpl');

		$data->assign(
			[
				'company'          => $context->company,
				'free_text'        => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $context->shop->id),
				'logo_path'        => $pathLogo,
				'piece'            => $this,
				'payments'         => $payments,
				'nameType'         => $this->getStaticPieceName($this->piece_type),
				'productDetails'   => $productDetails,
				'customer'         => $customer,
				'address'          => $address,
				'fields'           => $template->fields,
			]
		);

		if ($this->validate == 0 && $this->piece_type == 'INVOICE') {
			$watermark = $this->l('Provisoire');
			$mpdf->SetWatermarkText($watermark);
		} else

		if ($this->validate == 1 && $this->piece_type == 'INVOICE') {
			$mpdf->SetProtection(['copy', 'print'], '', _DB_PASSWD_);
		}

		$filePath = _PS_INVOICE_DIR_ ;

		$mpdf->SetTitle($context->company->company_name . " " . $this->getStaticPieceName($this->piece_type) . " " . $this->prefix . $this->piece_number);
		$mpdf->SetAuthor($context->company->company_name);
		$mpdf->showWatermarkText = true;
		$mpdf->watermark_font = 'DejaVuSansCondensed';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->SetDisplayMode('fullpage');

		$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($data->fetch());

		$mpdf->Output($filePath . $fileName, "F");
		
		return $fileName;

		

	}
	
	protected function getLogo() {

		$logo = '';
		$context = Context::getContext();
		$idShop = (int) $context->shop->id;

		if (Configuration::get('PS_LOGO_INVOICE', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $idShop);
		} else

		if (Configuration::get('PS_LOGO', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop))) {
			$logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $idShop);
		}

		return $logo;
	}
	
	public function generateReglement() {

		
		$paymentMode = new PaymentMode($this->id_payment_mode);
		$bank = new BankAccount($paymentMode->id_bank_account);
		$bankAccount = new StdAccount($bank->id_stdaccount);
		$customer = new Customer($this->id_customer);

		$piecePayment = new Payment();
		$piecePayment->id_currency = $this->id_currency;
		$piecePayment->id_customer = $this->id_customer;
		$piecePayment->amount = $this->total_tax_incl;
		$piecePayment->id_payment_mode = $this->id_payment_mode;
		$piecePayment->payment_method = $this->payment;
		$piecePayment->booked = 0;
		$piecePayment->date_add = $this->date_add;
		
		$success = $piecePayment->add();

		if ($success) {
			$paymentDetail = new PaymentDetails();
			$paymentDetail->id_payment = $piecePayment->id;
			$paymentDetail->id_student_piece = $this->id;
			$paymentDetail->id_customer_piece = $this->id;
			$paymentDetail->amount = $this->total_tax_incl;
			$paymentDetail->date_add = $piecePayment->date_add;
			$paymentDetail->add();
		}

		

	}
	
	public static function getDiscountsCustomer($idCustomer, $idCartRule) {
		
        $cacheId = 'CustomerPieces::getDiscountsCustomer_'.(int) $idCustomer.'-'.(int) $idCartRule;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from(bqSQL(static::$definition['table']), 'o')
                    ->leftJoin('order_cart_rule', 'ocr', 'ocr.`id_order` = o.`id_customer_piece`')
                    ->where('o.`id_customer` = '.(int) $idCustomer)
                    ->where('ocr.`id_cart_rule` = '.(int) $idCartRule)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }
	
	public static function getOrderByCartId($idCart) {
        
		$result = Db::getInstance()->getRow(
            (new DbQuery())
                ->select('`id_customer_piece`')
                ->from('customer_pieces')
                ->where('`id_cart` = '.(int) $idCart.' '.Shop::addSqlRestriction())
        );

        return isset($result['id_customer_piece']) ? $result['id_customer_piece'] : false;
    }
	
	public function getHistory($idLang, $idOrderState = false, $noHidden = false, $filters = 0) {
        
		if (!$idOrderState) {
            $idOrderState = 0;
        }

        $logable = false;
        $delivery = false;
        $paid = false;
        $shipped = false;
        if ($filters > 0) {
            if ($filters & CustomerPieceState::FLAG_NO_HIDDEN) {
                $noHidden = true;
            }
            if ($filters & CustomerPieceState::FLAG_DELIVERY) {
                $delivery = true;
            }
            if ($filters & CustomerPieceState::FLAG_LOGABLE) {
                $logable = true;
            }
            if ($filters & CustomerPieceState::FLAG_PAID) {
                $paid = true;
            }
            if ($filters & CustomerPieceState::FLAG_SHIPPED) {
                $shipped = true;
            }
        }

        if (!isset(static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters]) || $noHidden) {
            $idLang = $idLang ? (int) $idLang : 'o.`id_lang`';
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('os.*, oh.*, e.`firstname` AS `employee_firstname`, e.`lastname` AS `employee_lastname`, osl.`name` AS `ostate_name`')
                    ->from('customer_pieces', 'o')
                    ->leftJoin('customer_piece_history', 'oh', 'o.`id_customer_piece` = oh.`id_customer_piece`')
                    ->leftJoin('customer_piece_state', 'os', 'os.`id_customer_piece_state` = oh.`id_customer_piece_state`')
                    ->leftJoin('customer_piece_state_lang', 'osl', 'os.`id_customer_piece_state` = osl.`id_customer_piece_state` AND osl.`id_lang` = '.(int) $idLang)
                    ->leftJoin('employee', 'e', 'e.`id_employee` = oh.`id_employee`')
                    ->where('oh.`id_customer_piece` = '.(int) $this->id)
                    ->where($noHidden ? 'os.`hidden` = 0' : '')
                    ->where($logable ? 'os.`logable` = 1' : '')
                    ->where($delivery ? 'os.`delivery` = 1' : '')
                    ->where($paid ? 'os.`paid` = 1' : '')
                    ->where($shipped ? 'os.`shipped` = 1' : '')
                    ->where((int) $idOrderState ? 'oh.`id_customer_piece_state` = '.(int) $idOrderState : '')
                    ->orderBy('oh.`date_add` DESC, oh.`id_customer_piece_history` DESC')
            );
            if ($noHidden) {
                return $result;
            }
            static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters] = $result;
        }

        return static::$_historyCache[$this->id.'_'.$idOrderState.'_'.$filters];
    }
	
	public function getCustomer() {
		
        if (is_null($this->cacheCustomer)) {
            $this->cacheCustomer = new Customer((int) $this->id_customer);
        }

        return $this->cacheCustomer;
    }
	
	public static function getIdOrderProduct($idCustomer, $idProduct) {
		
        return (int) Db::getInstance()->getValue(
            (new DbQuery())
            ->select('o.`id_customer_piece`')
                ->from('customer_pieces', 'o')
                ->leftJoin('customer_piece_detail', 'od', 'o.`id_customer_piece` = od.`id_customer_piece`')
                ->where('o.`id_customer` = '.(int) $idCustomer)
                ->where('od.`product_id` = '.(int) $idProduct)
                ->orderBy('o.`date_add` DESC')
        );
    }
	
	public function setCurrentState($idOrderState, $idEmployee = 0) {
        
		if (empty($idOrderState)) {
            return false;
        }
        $history = new CustomerPiecesHistory();
        $history->id_customer_piece = (int) $this->id;
        $history->id_employee = (int) $idEmployee;
        $history->changeIdOrderState((int) $idOrderState, $this);
        $history->addWithemail();
    }
	
	public function getOrdersTotalPaid() {
		
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`total_paid`)')
                ->from('customer_pieces')
                ->where('`piece_number` = \''.pSQL($this->piece_number).'\'')
                ->where('`id_cart` = '.(int) $this->id_cart)
        );
    }
	
	public function getCurrentStateFull($idLang) {
		
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('os.`id_customer_piece_state`, osl.`name`, os.`logable`, os.`shipped`')
                ->from('customer_piece_state', 'os')
                ->leftJoin('customer_piece_state_lang', 'osl', 'osl.`id_customer_piece_state` = os.`id_customer_piece_state` AND osl.`id_lang` = '.(int) $idLang)
                ->where('os.`id_customer_piece_state` = '.(int) $this->current_state)
        );
    }
	
	public static function getCarrier($idCustomerPiece) {
        $carrier = false;
        if ($idCarrier = static::getCarrierId($idCustomerPiece)) {
            $carrier = new Carrier((int) $idCarrier);
        }

        return $carrier;
    }

    public static function getCarrierId($idCustomerPiece)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_carrier`')
                ->from('customer_pieces')
                ->where('`id_customer_piece` = '.(int) $idCustomerPiece)
        );
    }


}
