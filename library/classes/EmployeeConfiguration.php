<?php

class EmployeeConfigurationCore extends ObjectModel {

	public static $definition = [
		'table'     => 'employee_configuration',
		'primary'   => 'id_employee_configuration',
		'multilang' => true,
		'fields'    => [
			'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'name'        => ['type' => self::TYPE_STRING],
			'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
			'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
			'value'       => ['type' => self::TYPE_HTML, 'lang' => true],
		],
	];

	/** @var string Key */
	public $name;
	public $id_employee;
	/** @var string Value */
	public $value;
	/** @var string Object creation date */
	public $date_add;
	/** @var string Object last modification date */
	public $date_upd;

	public function __construct($id = null, $idLang = null) {

		parent::__construct($id, $idLang);
	}

	public function add($autoDate = true, $nullValues = false) {

		return parent::add($autoDate, $nullValues);

	}

	public static function get($key, $idEmployee = null, $idLang = null) {

		$context = Context::getContext();

		if (empty($idEmployee)) {
			$idEmployee = $context->employee->id;
		}

		if (empty($idLang)) {
			$idLang = $context->employee->id_lang;
		}
		
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('ecl.`value`')
				->from('employee_configuration', 'ec')
				->leftJoin('employee_configuration_lang', 'ecl', 'ecl.`id_employee_configuration` = ec.`id_employee_configuration` AND ecl.`id_lang` = ' . $idLang)
				->where('ec.`name` LIKE \'' . $key . '\' AND ec.id_employee = '.$idEmployee)
		);

	}

	public static function updateValue($key, $values, $idEmployee = false) {

		$context = Context::getContext();
		if(!$idEmployee) {
			$idEmployee = $context->employee->id;
		}
		
		$idLang = $context->employee->id_lang;

		$hasKey = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`id_employee_configuration`')
				->from('employee_configuration')
				->where('`id_employee` = ' . (int) $idEmployee . ' AND `name` LIKE \'' . $key . '\'')
		);

		if (!empty($hasKey)) {
			$configuration = new EmployeeConfiguration($hasKey);
			$configuration->value[$idLang] = $values;
			$configuration->update();
			return true;
		}

		$configuration = new EmployeeConfiguration();
		$configuration->id_employee = $idEmployee;
		$configuration->name = $key;
		$configuration->value[$idLang] = $values;

		$configuration->add();

		return true;
	}

}
