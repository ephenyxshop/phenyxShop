<?php

class EphAutomapModel {

	protected static $option_name = 'vc_automapped_shortcodes';
	protected static $option_data;
	public $id = false;
	protected $data;
	protected $vars = ['tag', 'name', 'category', 'description', 'params'];

	function __construct($d) {

		$this->loadOptionData();
		$this->id = is_array($d) && isset($d['id']) ? $d['id'] : $d;

		if (is_array($d)) {
			$this->data = stripslashes_deep($d);
		}

		foreach ($this->vars as $var) {
			$this->$var = $this->get($var);
		}

	}

	static function findAll() {

		$this->loadOptionData();
		$records = [];

		foreach ($this->$option_data as $id => $record) {
			$record['id'] = $id;
			$model = new EphAutomapModel($record);

			if ($model) {
				$records[] = $model;
			}

		}

		return $records;
	}

	final protected static function loadOptionData() {

		if (is_null($this->$option_data)) {
			$this->$option_data = Configuration::get($this->$option_name);
		}

		if (!$this->$option_data) {
			$this->$option_data = [];
		}

		return $this->$option_data;
	}

	function get($key) {

		if (is_null($this->data)) {
			$this->data = isset($this->$option_data[$this->id]) ? $this->$option_data[$this->id] : [];
		}

		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	function set($attr, $value = null) {

		if (is_array($attr)) {

			foreach ($attr as $key => $value) {
				$this->set($key, $value);
			}

		} else

		if (!is_null($value)) {
			$this->$attr = $value;
		}

	}

	function save() {

		if (!$this->isValid()) {
			return false;
		}

		foreach ($this->vars as $var) {
			$this->data[$var] = $this->$var;
		}

		return $this->saveOption();
	}

	function delete() {

		return $this->deleteOption();
	}

	public function isValid() {

		if (!is_string($this->name) || empty($this->name)) {
			return false;
		}

		if (!preg_match('/^\S+$/', $this->tag)) {
			return false;
		}

		return true;
	}

	protected function saveOption() {

		$this->$option_data[$this->id] = $this->data;
		return Configuration::updateValue($this->$option_name, $this->$option_data);
	}

	protected function deleteOption() {

		unset($this->$option_data[$this->id]);
		return Configuration::updateValue($this->$option_name, $this->$option_data);
	}

}

