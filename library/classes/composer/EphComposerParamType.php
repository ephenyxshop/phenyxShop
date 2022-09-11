<?php

class EphComposerParamTypeCore extends EphComposer {
	
	
	public $active;
	public $value;
	
	public static $definition = [
		'table'          => 'composer_param_type',
		'primary'        => 'id_composer_param_type',
		'fields'         => [
			'value'       => ['type' => self::TYPE_STRING],
			'active'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
		],
	];
	
	public function __construct($id = null, $idLang = null) {
		
		parent::__construct($id, $idLang);
		
	}
	
	public static function getParamTypes() {
		
		 return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_composer_param_type`, `value`')
                ->from('composer_param_type')
        );

	}

	
	

}
