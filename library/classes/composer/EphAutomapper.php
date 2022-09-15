<?php

class EphAutomapper extends PhenyxObjectModel {

	protected static $disabled = false;

	public function __construct() {

		$jscomposer = EphComposer::getInstance();
		$this->title = $this->l('My shortcodes');
	}

	public function addAjaxActions() {

		$actions = [
			'vc_automapper' => [ & $this, 'goAction'],
		];

		if ($action = Tools::getValue('action')) {

			if (isset($actions[$action])) {
				call_user_func($actions[$action]);
			}

		}

	}

	public function delete() {

		$id = Tools::getValue('id');
		$shortcode = new EphAutomapModel($id);
		return $shortcode->delete();
	}

	public function read() {

		return EphAutomapModel::findAll();
	}

	function result($data) {

		echo is_array($data) || is_object($data) ? json_encode($data) : $data;
		die();
	}

	public static function setDisabled($disable = true) {

		$this->$disabled = $disable;
	}

	public static function disabled() {

		return $this->$disabled;
	}

	public function setTitle($title) {

		$this->title = $title;
	}

	public function title() {

		return $this->title;
	}

	public static function map() {

		$shortcodes = EphAutomapModel::findAll();

		if (!empty($shortcodes)) {

			foreach ($shortcodes as $shortcode) {
				vc_map([
					"name"                    => $shortcode->name,
					"base"                    => $shortcode->tag,
					"category"                => vc_atm_build_categories_array($shortcode->category),
					"description"             => $shortcode->description,
					"params"                  => vc_atm_build_params_array($shortcode->params),
					"show_settings_on_create" => !empty($shortcode->params),
					"atm"                     => true,
					"icon"                    => 'icon-wpb-atm',
				]);
			}

		}

	}

}

