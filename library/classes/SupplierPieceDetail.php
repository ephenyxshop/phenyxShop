<?php

/**
 * Class CustomerPieceDetailCore
 *
 * @since 2.1.0.0
 */
class SupplierPieceDetailCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'supplier_piece_detail',
        'primary' => 'id_supplier_piece_detail',
        'fields'  => [
            'id_supplier_piece'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
			'id_sale_agent_commission' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_student_education'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product_attribute'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_education'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'id_education_attribute'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'product_name'               => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'product_quantity'           => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'reduction_percent'          => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'reduction_amount_tax_incl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'reduction_amount_tax_excl'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'product_ean13'              => ['type' => self::TYPE_STRING, 'validate' => 'isEan13'],
            'product_upc'                => ['type' => self::TYPE_STRING, 'validate' => 'isUpc'],
            'product_reference'          => ['type' => self::TYPE_STRING, 'validate' => 'isReference'],
			'id_product_batch'			 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'product_weight'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'tax_rate'                   => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'id_tax_rules_group'         => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'ecotax'                     => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'download_hash'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'download_nb'                => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'download_deadline'          => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
			'original_price_tax_excl'	 => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'original_price_tax_incl'	 => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'unit_tax_incl'		         => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'unit_tax_excl'              => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax_excl'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'total_tax'                  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
			'total_tax_incl'       		 => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
        ],
    ];
   
    /** @var int $id_order */
    public $id_supplier_piece;
	
	public $id_sale_agent_commission;
	
	public $id_student_education;
    /** @var int $id_order_invoice */
    public $id_product;
    /** @var int $id_product_attribute_id */
    public $id_product_attribute;
	
	public $id_education;
    /** @var int $id_product_attribute_id */
    public $id_education_attribute;
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
	public $total_tax_excl;
	
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
    /** @var int $id_tax_rules_group Id tax rules group */
    public $id_tax_rules_group;
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

        parent::__construct($id, $idLang, $context);

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
     * @param int $idShop
     *
     * @since 2.1.0.0
     */
    protected function setContext($idShop) {

        if ($this->context->shop->id != $idShop) {
            $this->context->shop = new Shop((int) $idShop);
        }

    }

    

}
