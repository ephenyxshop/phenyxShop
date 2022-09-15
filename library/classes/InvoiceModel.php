<?php


class InvoiceModelCore extends PhenyxObjectModel {

  
    public static $definition = [
        'table'     => 'invoice_model',
        'primary'   => 'id_invoice_model',
        'fields'    => [
			'name' => ['type' => self::TYPE_STRING,  'validate' => 'isString', 'required' => true, 'size' => 64],
			'color' => ['type' => self::TYPE_STRING,  'validate' => 'isString', 'required' => true, 'size' => 64],
            'fields' => ['type' => self::TYPE_STRING, 'required' => true],

            /* Lang fields */
           
        ],
    ];
    
    public $name;
	public $color;
    public $fields;
	
	public $noFields;
	
	public $publicName;
	
	public $pieceFields;

   
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);
		
		$this->pieceFields = [
         	'product_reference'          => ['name' => $this->l('Référence'), 'format' => ''],
            'product_name'               => ['name' => $this->l('Libellé'), 'format' => '', 'required' => true],
            'product_quantity'           => ['name' => $this->l('Quantité'), 'talign' => 'center', 'align' => 'center', 'format' => ''],
            'reduction_percent'          => ['name' => $this->l('Réduction %'), 'align' => 'center', 'format' => ''],
            'reduction_amount_tax_incl'  => ['name' => $this->l('Montant de réduction HT'), 'align' => 'right', 'format' => 'monney'],
            'reduction_amount_tax_excl'  => ['name' => $this->l('Montant de réduction TTC'), 'align' => 'right', 'format' => 'monney'],
            'product_ean13'              => ['name' => $this->l('EAN13'), 'format' => ''],
            'product_upc'                => ['name' => $this->l('UPC'), 'format' => ''],
            'product_weight'             => ['name' => $this->l('Poids'), 'format' => ''],
            'tax_rate'                   => ['name' => $this->l('Taux de TVA'), 'align' => 'right', 'format' => 'percent'],
            'ecotax'                     => ['name' => $this->l('Eco participation'), 'align' => 'right', 'format' => 'monney'],
			'original_price_tax_excl'	 => ['name' => $this->l('Prix unitaire HT'), 'align' => 'right', 'format' => 'monney'],
			'original_price_tax_incl'	 => ['name' => $this->l('Prix unitaire TTC'), 'align' => 'right', 'format' => 'monney'],
            'unit_tax_incl'		         => ['name' => $this->l('Prix unitaire remisé HT'), 'align' => 'right', 'format' => 'monney'],
            'unit_tax_excl'              => ['name' => $this->l('Prix unitaire remisé TTC'), 'align' => 'right', 'format' => 'monney'],
            'total_tax_excl'             => ['name' => $this->l('Total HT'), 'align' => 'right', 'format' => 'monney'],
            'total_tax'                  => ['name' => $this->l('Total TVA'), 'align' => 'right', 'format' => 'monney'],
			'total_tax_incl'       		 => ['name' => $this->l('Total TTC'), 'align' => 'right', 'format' => 'monney'],
       
    	];
		
		if($this->id) {
			$this->fields = Tools::jsonDecode($this->fields, true);
			$this->publicName = ucfirst(strtolower(str_replace('_', ' ', str_replace('EPH_TEMPLATE_', '', $this->name))));
			$this->noFields = $this->getNoFields();
		}
		
		
		
    } 
	
	protected function l($string, $class = 'InvoiceModel', $addslashes = false, $htmlentities = true) {

        // if the class is extended by a module, use modules/[module_name]/xx.php lang file
        $currentClass = get_class($this);

        
        global $_LANGCLASS;

        if ($class == __CLASS__) {
            $class = 'InvoiceModel';
        }

        $key = md5(str_replace('\'', '\\\'', $string));
        if(is_array($_LANGCLASS)) {
			 $str = (array_key_exists(get_class($this) . $key, $_LANGCLASS)) ? $_LANGCLASS[get_class($this) . $key] : ((array_key_exists($class . $key, $_LANGCLASS)) ? $_LANGCLASS[$class . $key] : $string);
        	$str = $htmlentities ? htmlentities($str, ENT_QUOTES, 'utf-8') : $str;

        	return str_replace('"', '&quot;', ($addslashes ? addslashes($str) : stripslashes($str)));
		}
		
		return $string;
    }

	
	public function getNoFields() {
		
		
		$nofields = [];		
		
		foreach($this->pieceFields as $key =>  $fields) {
					
			if(array_key_exists($key, $this->fields)) {
				continue;
			}			
			$nofields[$key] = $this->pieceFields[$key];
		}
		
		return $nofields;
	}
	
	public static function getInvoiceModels() {
		
		$template = [];
		$models = Db::getInstance()->executeS(
			(new DbQuery())
			->select('`id_invoice_model`')
			->from('invoice_model')
		);
		foreach($models as &$model) {
			$template[] = new InvoiceModel($model['id_invoice_model']);
			
		}
		
		return $template;
	}

}
