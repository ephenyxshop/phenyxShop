<?php

class EphParamValueCore extends EphComposer {
	

	public $id_composer_map;
	public $id_composer_map_params;
	public $value_key;
	public $name;
	
	
	

	
	public static $definition = [
		'table'          => 'composer_value',
		'primary'        => 'id_composer_value',
		'multilang'      => true,
		'fields'         => [
			'id_composer_map'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'id_composer_map_params'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'value_key'             	=> ['type' => self::TYPE_STRING, 'size' => 256],
			
			/* Lang fields */
			
			'name'             	=> ['type' => self::TYPE_STRING, 'lang' => true, 'size' => 256],
		],
	];
	
	public function __construct($id = null, $idLang = null) {
		
		parent::__construct($id, $idLang);
	
	}

}
