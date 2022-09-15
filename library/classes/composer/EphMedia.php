<?php

class EphMediaCore extends PhenyxObjectModel {

    public $id_vc_media;
    public $file_name;
	public $base_64;
    public $subdir;
    public $legend;
    public static $definition = [
        'table'     => 'vc_media',
        'primary'   => 'id_vc_media',
        'multilang' => true,
        'fields'    => [
            'file_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'base_64' => ['type' => self::TYPE_STRING, 'validate' => 'isString'], 
            'subdir'    => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'legend'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
        ],
    ];
	
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
    }
	
	public function update($nullValues = false) {

		return parent::update(true);
	}


}