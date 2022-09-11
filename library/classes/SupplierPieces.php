<?php

/**
 * @since 2.1.0.0
 */
class SupplierPiecesCore extends ObjectModel {

    const ROUND_ITEM = 1;
    const ROUND_LINE = 2;
    const ROUND_TOTAL = 3;
	
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supplier_pieces',
        'primary' => 'id_supplier_piece',
        'fields'  => [
			'piece_type'               => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'id_shop_group'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_currency'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_sale_agent'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_supplier'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'base_tax_excl'  		   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_products_tax_excl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_products_tax_incl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_excl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_shipping_tax_incl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'total_with_freight_tax_excl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'shipping_no_subject'      => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'shipping_tax_subject'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'discount_rate'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_discounts_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_excl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_wrapping_tax_incl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'total_tax_excl'  			   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'total_tax'  			   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'total_tax_incl'  			   => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'id_payment_mode'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'total_paid'               => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'id_carrier'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_delivery'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_invoice'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'conversion_rate'          => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'shipping_number'          => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'],
			'last_transfert'           => ['type' => self::TYPE_INT],
            'round_mode'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'round_type'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'piece_number'             => ['type' => self::TYPE_STRING],
            'delivery_number'          => ['type' => self::TYPE_INT],
            'validate'                 => ['type' => self::TYPE_BOOL],
			'observation'              => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
			"is_book"                     => ['type' => self::TYPE_BOOL],
			'id_book_record'              => ['type' => self::TYPE_INT],
            'date_add'                 => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
			'date_upd'                 => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

        ],
    ];

	public $piece_type;
    public $id_shop_group;
    public $id_shop;
    public $id_lang;
    public $id_currency;
    public $id_order;
    public $id_sale_agent;
	public $id_supplier;
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
    public $total_paid;
    public $id_carrier;
    public $id_address_delivery;
    public $id_address_invoice;
    public $conversion_rate = 1;
    public $shipping_number;
    public $round_mode = 2;
    public $round_type = 1;
    public $piece_number;
    public $delivery_number;
	public $last_transfert;
    public $validate;
	public $observation;
	public $is_book;
	public $id_book_record;
    public $date_add;
    public $date_upd;

    public $prefix;
	public $nameType;
	public $pieceOrigin;
	public $payment_mode;
	
	
	public $balance_due;
	
	
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->prefix = $this->getPrefix();
			$this->nameType = $this->getTypeName();
			$this->balance_due = $this->getBalanceDue();
			$this->payment_mode = PaymentMode::getPaymentModeNameById($this->id_payment_mode);
        }

    }
	
	public function getBalanceDue() {
		
		return $this->total_tax_incl - $this->total_paid;
	}
	
	
	
	public function getDiscountPercent() {
		return (1- ($this->total_products_tax_excl/($this->total_products_tax_excl - $this->total_discounts_tax_excl)))*100;
	}

    public function getPrefix() {

        switch ($this->piece_type) {

       
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

        
        case 'INVOICE':
            return $this->l('Facture');
            break;
        case 'ASSET':
            return $this->l('Avoir');
            break;
        }

    }
    
	public function l($string, $idLang = null, Context $context = null) {

       
       $class = get_class($this);
		if (strtolower(substr($class, -4)) == 'core') {
            $class = substr($class, 0, -4);
        }
        
       return Translate::getClassTranslation($string, $class, $addslashes, $htmlentities);
    }
	
    public function getFields() {

        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT', null, null, $this->id_shop);
        }

        return parent::getFields();
    }
	
    public function add($autoDate = false, $nullValues = true) {

        $this->roundAmounts();
		if(empty($this->piece_number)) {
			$this->piece_number = $this->generateNewInvoiceNumber();
		}
		

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
			
		Db::getInstance()->execute(
			'DELETE FROM `' . _DB_PREFIX_ . 'supplier_piece_detail` 
             WHERE `id_supplier_piece` = ' . (int) $this->id
		);
		
	}
	
	public function getPieceDetail() {
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
				->select('*')
				->from('supplier_piece_detail')
				->where('`id_supplier_piece` = '.$this->id)
			);
	}
	
	public static function getRequest($pieceType = null) {

        $context = Context::getContext();
		
		$query = new DbQuery();
		$query->select('a.*, (a.total_products_tax_incl+a.total_shipping_tax_incl+a.total_wrapping_tax_incl) as `total` , case when a.id_sale_agent > 0  then CONCAT(sa.`firstname`, " ", sa.`lastname`)  else s.name end AS `customer`, pml.`name` AS `paymentMode`,
		(a.`total_products_tax_incl` + a.`total_shipping_tax_incl` + a.`total_wrapping_tax_incl` - a.`total_paid`) as `balanceDue`, case when a.validate = 1 then \'<div class="orderValidate"></div>\' else \'<div class="orderOpen"></div>\' end as validate, case when a.validate = 1 then 1 else 0 end as isLocked, case when a.id_sale_agent > 0 then ca.`id_country` else sad.`id_country` end as id_country , case when a.id_sale_agent > 0 then ca.`address1` else sad.`address1` end as address1, case when a.id_sale_agent > 0 then ca.`address2` else sad.`address2` end as address2, case when a.id_sale_agent > 0 then ca.`postcode` else sad.`postcode` end as postcode, case when a.id_sale_agent > 0 then ca.`city` else sad.`city` end as city, case when a.id_sale_agent > 0 then cl.`name` else cls.`name` end AS country, case when a.is_book = 1 then \'<div class="orderBook"><i class="icon icon-book" aria-hidden="true"></i></div>\' else \'<div class="orderUnBook"><i class="icon icon-times" aria-hidden="true" style="color:red;"></i></div>\' end as booked, case when a.is_book = 1 then 1 else 0 end as isBooked');
        $query->from('supplier_pieces', 'a');
        $query->leftJoin('supplier', 's', 's.`id_supplier` = a.`id_supplier`');
		$query->leftJoin('sale_agent', 'sa', 'sa.`id_sale_agent` = a.`id_sale_agent`');
        $query->leftJoin('payment_mode_lang', 'pml', 'pml.`id_payment_mode` = a.`id_payment_mode` AND pml.`id_lang` = ' . $context->language->id);
		$query->leftJoin('address', 'ca', 'a.`id_address_delivery` = ca.`id_address`');
		$query->leftJoin('address', 'sad', 'a.`id_address_delivery` = s.`id_supplier`');
		$query->leftJoin('country_lang', 'cl', 'cl.`id_country` = ca.`id_country` AND cl.`id_lang` = ' . $context->language->id);
		$query->leftJoin('country_lang', 'cls', 'cls.`id_country` = sad.`id_country` AND cl.`id_lang` = ' . $context->language->id);
		if($pieceType) {
			$query->where('a`piece_type` LIKE \''.$pieceType.'\'');
		}
        $query->orderBy('a.`date_add` DESC');
		
		$orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
		
		return $orders;
       

    }
	
	public function generateNewInvoiceNumber() {
		
		
		if(empty($this->piece_number)) {
			$year = date('Y');
			$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
				(new DbQuery())
				->select('`piece_number`')
				->from('supplier_pieces')
				->where('`piece_type` LIKE \'INVOICE\'')
				->orderBy('`id_supplier_piece` DESC')
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
                ->from('supplier_piece_detail', 'od')
                ->leftJoin('product', 'p', 'p.`id_product` = od.`id_product`')
                ->leftJoin('product_shop', 'ps', 'ps.`id_product` = od.`id_product`')
                ->where('od.`id_supplier_piece` = ' . (int) $this->id)
        );
    }

    public function getProducts($products = false, $selectedProducts = false, $selectedQty = false) {

        if (!$products) {
            $products = $this->getProductsDetail();
        }

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

            // Add information for virtual product

            if ($row['download_hash'] && !empty($row['download_hash'])) {
                $row['filename'] = ProductDownload::getFilenameFromIdProduct((int) $row['id_product']);
                // Get the display filename
                $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
            }

            $row['id_address_delivery'] = $this->id_address_delivery;

            /* Stock product */
            $resultArray[(int) $row['id_supplier_piece_detail']] = $row;
        }

        return $resultArray;
    }

    public function getVirtualProducts() {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_product`, `id_product_attribute`, `download_hash`, `download_deadline`')
                ->from('supplier_piece_detail', 'od')
                ->where('od.`id_supplier_piece` = ' . (int) $this->id)
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

    
	
	public static function getLastInvoiceNumber() {
		
		$sql = 'SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \''._DB_NAME_.'\' AND   TABLE_NAME   = \''._DB_PREFIX_.'supplier_pieces\'';
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
		$piece->id_order = $order->id;
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
	
		
	
	
	public static function getIncrementByType($type) {
		
		
		
		$year = date('Y');
		$lastValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`piece_number`')
            ->from('supplier_pieces')
			->where('`piece_type` LIKE \''.$type.'\'')
            ->orderBy('`id_supplier_piece` DESC')
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
	

}
