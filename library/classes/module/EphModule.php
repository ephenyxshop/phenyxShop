<?php

class EphModuleCore extends ObjectModel {

    public $name;
	public $base64;
    public $active;
    public $version;
    public static $definition = [
        'table'     => 'module',
        'primary'   => 'id_module',
        'fields'    => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'base64' => ['type' => self::TYPE_STRING, 'validate' => 'isString'], 
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'version'    => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
        ],
    ];
	
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
    }
	


}