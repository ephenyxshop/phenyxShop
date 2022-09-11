<?php

class EphParamMap extends EphComposer {
	
	public $id_composer_map;
	public $type;
	public $id_type;
	public $param_name;
	public $edit_field_class;
	public $std;
	public $admin_label;
	public $holder;
	public $class;
	public $param_holder_class;
	public $dependency;
	public $value;
	public $tpl;
	public $settings;
	public $position;
	
	public $heading;
	public $description;
	public $param_group;
	public $valueCollections;
	

	
	public static $definition = [
		'table'          => 'composer_map_params',
		'primary'        => 'id_composer_map_params',
		'multilang'      => true,
		'fields'         => [
			'id_composer_map'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'id_type'		        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'param_name' 			=> ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'edit_field_class'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'std'     				=> ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'admin_label'     		=> ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'holder' 				=> ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'class'           		=> ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'param_holder_class'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'dependency' 			=> ['type' => self::TYPE_STRING],
			'value' 				=> ['type' => self::TYPE_STRING],
			'tpl' 					=> ['type' => self::TYPE_STRING],
			'settings' 				=> ['type' => self::TYPE_STRING],
			'position' 				=> ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			/* Lang fields */
			'heading'             	=> ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'size' => 128],
			'description'      		=> ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
			'param_group' 			=> ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
		],
	];
	
	public function __construct($id = null, $idLang = null) {
		
		parent::__construct($id, $idLang);
		if ($this->id) {
			$this->valueCollections = $this->getShortCodeParamValues();
			$this->type = Db::getInstance()->getValue('SELECT `value` FROM `'._DB_PREFIX_.'composer_param_type` WHERE `id_composer_param_type` = '.(int)$this->id_type);
		}
	}
	
	public function getShortCodeParamValues() {
		
		$collections = [];
		$params = Db::getInstance()->executeS('SELECT `id_composer_value` FROM `'._DB_PREFIX_.'composer_value` WHERE `id_composer_map_params` = '.(int)$this->id);
		foreach($params as $param) {
			$collections[] = new EphParamValue($param['id_composer_value']);
		}
		return $collections;
	}

}
