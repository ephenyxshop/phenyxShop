<?php
class PFGSubmissionModelCore extends PhenyxObjectModel {
		
	public $id_pfg;
	public $entry;
	public $date_add;

	public static $definition = array (
		'table' => 'pfg_submissions',
		'primary' => 'id_submission',
		'fields' => array (
			'id_pfg'       => array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'),
			'entry'        => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
			'date_add'     => array('type' => self::TYPE_DATE,   'validate' => 'isDate'),
		),
	);
	
}
