<?php

class PFGFieldModelCore extends PhenyxObjectModel {

	public $id_pfg;
	public $type;
	public $name;
	public $label;
	public $values;
	public $required;
	public $class;
	public $style;
	public $extra;
	public $related;
	public $position;

	public static $definition = [
		'table'     => 'pfg_fields',
		'primary'   => 'id_pfg_field',
		'multilang' => true,
		'fields'    => [
			'id_pfg'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
			'type'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255, 'required' => true],
			'name'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
			'label'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255, 'required' => false],
			'values'   => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'size' => 2048, 'required' => false],
			'required' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
			'class'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
			'style'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
			'extra'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
			'related'  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
			'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
		],
	];

	/**
	 * Add the model in the database and returns it
	 *
	 * @param boolean $autodate
	 * @param boolean $null_values
	 *
	 * @return object
	 */
	public function add($autodate = true, $null_values = false) {

		$object = parent::add($autodate, $null_values);
		$this->position = PFGFieldModel::getNextAvailablePosition($this->id_pfg);

		$this->update();

		return $object;
	}

	/**
	 * Update the position of this element in the database.
	 *
	 * @param integer $way Way of positionning
	 * @param integer $new_position The new position
	 *
	 * @return object or false
	 */
	public function updatePosition($way, $new_position) {

		$db = Db::getInstance();
		$count = $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE id_pfg = ' . pSQL((int) $this->id_pfg), false);

		if (($new_position >= 1) && ($new_position <= $count)) {
			$old_position = $way ? ($new_position - 1) : ($new_position + 1);

			if (($old_position >= 1) && ($old_position <= $count)) {
				$sql = implode(
					';',
					[
						'UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET position = 0 WHERE position = ' . pSQL((int) $new_position) . ' AND id_pfg=' . pSQL((int) $this->id_pfg),
						'UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET position = ' . pSQL((int) $new_position) . ' WHERE position = ' . pSQL((int) $old_position) .
						' AND id_pfg=' . pSQL((int) $this->id_pfg),
						'UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET position = ' . pSQL((int) $old_position) . ' WHERE position = 0 AND id_pfg=' . pSQL((int) $this->id_pfg),
					]
				);

				// Both old and new positions are valid, we switch them
				return $db->execute($sql);
			}

		}

		return false;
	}

	/**
	 * Update the position of a specific field
	 *
	 * @param integer $id_pfg ID of the PFG model
	 * @param integer $id_field ID of the PFG Field model
	 * @param integer $position Position of that field
	 *
	 * @return boolean
	 */
	public static function updatePositionField($id_pfg, $id_field, $position) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->execute('UPDATE ' . _DB_PREFIX_ . self::$definition['table'] . ' SET position = ' . pSQL((int) $position) . ' WHERE id_pfg = ' . pSQL((int) $id_pfg) . ' AND id_field = ' . pSQL((int) $id_field));
	}

	/**
	 * Delete the instance in the database
	 *
	 * @return boolean
	 */
	public function delete() {

		$position = $this->position;

		if ($result = parent::delete()) {
			Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . self::$definition['table'] .
				' SET position = position-1 WHERE position > ' . pSQL((int) $position) . ' AND id_pfg = ' . pSQL($this->id_pfg));
		}

		return $result;
	}

	/**
	 * Retrieve the fields from the given PFG model id
	 *
	 * @param integer $id_pfg ID of the PFG model
	 *
	 * @return array or false
	 */
	public static function findFields($id_pfg) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('
				SELECT f.type, f.name, f.required, f.class, f.style, f.extra, f.related, fl.values, fl.label
				FROM `' . _DB_PREFIX_ . 'pfg_fields` f
				LEFT JOIN `' . _DB_PREFIX_ . 'pfg_fields_lang` fl ON f.id_pfg_field = fl.id_pfg_field
				WHERE `id_pfg` = ' . pSQL((int) $id_pfg) . ' AND fl.id_lang = ' . pSQL((int) Context::getContext()->language->id) . ' ORDER BY f.position');
	}

	/**
	 * Return the next available position
	 *
	 * @param integer $id_pfg ID of the PFG model
	 *
	 * @return integer
	 */
	public static function getNextAvailablePosition($id_pfg) {

		$sql = 'SELECT position FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' WHERE id_pfg = ' . pSQL((int) $id_pfg) . ' ORDER BY position DESC';

		$position = (int) Db::getInstance()->getValue($sql, false);
		return $position + 1;
	}

	/**
	 * Indicates if the requested name is available in the database or not
	 *
	 * @param string $name Name to lookup for
	 * @param integer $id_pfg ID of the PFG model
	 * @param integer $id_field ID of the PFG Field model
	 *
	 * @return boolean
	 */
	public static function isNameAlreadyTaken($name, $id_pfg, $id_field = null) {

		if ($id_field) {
			$query = 'SELECT name FROM `' . _DB_PREFIX_ . 'pfg_fields` WHERE id_field != ' . pSQL((int) $id_field) . ' AND id_pfg = ' . pSQL((int) $id_pfg) . ' AND name = "' . pSQL($name) . '"';
		} else {
			$query = 'SELECT name FROM `' . _DB_PREFIX_ . 'pfg_fields` WHERE id_pfg = ' . pSQL((int) $id_pfg) . ' AND name = "' . pSQL($name) . '"';
		}

		return count(Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query)) > 0;
	}

}
