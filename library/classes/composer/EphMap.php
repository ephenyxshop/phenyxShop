<?php

class EphMap extends EphComposer {

	protected static $sc = [];
	protected static $categories = [];
	protected static $user_sc = false;
	protected static $user_sorted_sc = false;
	protected static $user_categories = false;
	protected static $settings, $user_role;
	protected static $tags_regexp;
	protected static $is_init = false;

	public static function setInit($value = true) {

		$this->is_init = $value;
	}

	protected static function getSettings() {

		return false;

		global $current_user;

		if ($this->settings === null) {

			$this->user_role = 'author';

			$this->settings = Configuration::get('wpb_js_groups_access_rules');
		}

		return $this->settings;
	}

	public static function exists($tag) {

		return (boolean) isset($this->sc[$tag]);
	}
	
	public static function addMap($tag, $attributes) {

		
			EphMapper::addActivity('mapper', 'map', [
				'tag'        => $tag,
				'attributes' => $attributes,
			]);
			
		

		
	}


	public static function map($tag, $attributes) {
			
		$vcmain = $this->vcmain;
		$vc_mapper = $this->vc_mapper;
		
		
		if ( ! self::$is_init ) {
			$vc_mapper->addActivity('mapper', 'map', [
				'tag'        => $tag,
				'attributes' => $attributes,
			]);
			return false;
		}

		if (empty($attributes['name'])) {
			trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $tag));
		} else

		if (empty($attributes['base'])) {
			trigger_error(sprintf($this->l("Wrong base for shortcode:%s. Base required"), $tag));
		} else {

			if (isset($this->sc[$tag])) {
				return;
			}

			$this->sc[$tag] = $attributes;
			$this->sc[$tag]['params'] = [];

			if (!empty($attributes['params'])) {

				foreach ($attributes['params'] as $attribute) {

					if (isset(EphComposer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']])) {
						$attribute = call_user_func(EphComposer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']], $attribute);
					}

					$this->sc[$tag]['params'][] = $attribute;
				}

			}

			EphBase::addShortCode($this->sc[$tag]);

		}

	}

	protected static function generateUserData($force = false) {

		if (!$force && $this->user_sc !== false && $this->user_categories !== false) {
			return;
		}

		$settings = $this->getSettings();
		$this->user_sc = $this->user_categories = $this->user_sorted_sc = [];

		foreach ($this->sc as $name => $values) {

			if (in_array($name, [
				'vc_column',
				'vc_row',
				'vc_row_inner',
				'vc_column_inner',
			]) || !isset($settings[$this->user_role]['shortcodes']) || (isset($settings[$this->user_role]['shortcodes'][$name]) && (int) $settings[$this->user_role]['shortcodes'][$name] == 1)) {

				if (!isset($values['content_element']) || $values['content_element'] === true) {
					$categories = isset($values['category']) ? $values['category'] : '_other_category_';
					$values['_category_ids'] = [];

					if (is_array($categories)) {

						foreach ($categories as $c) {

							if (array_search($c, $this->user_categories) === false) {
								$this->user_categories[] = $c;
							}

							$values['_category_ids'][] = md5($c);
						}

					} else {

						if (array_search($categories, $this->user_categories) === false) {
							$this->user_categories[] = $categories;
						}

						$values['_category_ids'][] = md5($categories);
					}

				}

				$this->user_sc[$name] = $values;
				$this->user_sorted_sc[] = $values;

			}

		}

		@usort($this->user_sorted_sc, [
			"EPHMap",
			"sort",
		]);
	}

	public static function wpb_generate_custom_shortcodes() {

		$this->generateUserData(true);
	}

	public static function getShortCodes() {

		return $this->sc;
	}

	public static function getSortedUserShortCodes() {

		$this->generateUserData();
		return $this->user_sorted_sc;
	}

	public static function getUserShortCodes() {

		$this->generateUserData();
		return $this->user_sc;
	}

	public static function getShortCode($tag) {

		return $this->sc[$tag];
	}

	public static function getCategories() {

		return $this->categories;
	}

	public static function getUserCategories() {

		$this->generateUserData();
		return $this->user_categories;
	}

	public static function dropParam($name, $attribute_name) {

		if (!$this->is_init) {
			EphMapper::addActivity('mapper', 'drop_param', [
				'name'           => $name,
				'attribute_name' => $attribute_name,
			]);
			return;
		}

		foreach ($this->sc[$name]['params'] as $index => $param) {

			if ($param['param_name'] == $attribute_name) {
				array_splice($this->sc[$name]['params'], $index, 1);
				return;
			}

		}

	}

	public static function getParam($tag, $param_name) {

		if (!isset($this->sc[$tag])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $tag));
		}

		foreach ($this->sc[$tag]['params'] as $index => $param) {

			if ($param['param_name'] == $param_name) {
				return $this->sc[$tag]['params'][$index];
			}

		}

		return false;
	}

	public static function addParam($name, $attribute = []) {

		if (!$this->is_init) {
			EphMapper::addActivity('mapper', 'add_param', [
				'name'      => $name,
				'attribute' => $attribute,
			]);
			return;
		}

		if (!isset($this->sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($this->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach ($this->sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					$this->sc[$name]['params'][$index] = $attribute;
				}

			}

			if ($replaced === false) {
				$this->sc[$name]['params'][] = $attribute;
			}

			EphBase::addShortCode($this->sc[$name]);
		}

	}

	public static function mutateParam($name, $attribute = []) {

		if (!$this->is_init) {
			EphMapper::addActivity('mapper', 'mutate_param', [
				'name'      => $name,
				'attribute' => $attribute,
			]);
			return false;
		}

		if (!isset($this->sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($this->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach ($this->sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					$this->sc[$name]['params'][$index] = array_merge($param, $attribute);
				}

			}

			if ($replaced === false) {
				$this->sc[$name]['params'][] = $attribute;
			}

			EphBase::addShortCode($this->sc[$name]);
		}

		return true;
	}

	public static function dropShortcode($name) {

		if (!$this->is_init) {
			EphMapper::addActivity('mapper', 'drop_shortcode', [
				'name' => $name,
			]);
			return false;
		}

		unset($this->sc[$name]);
		EphBase::removeShortCode($name);

	}

	public static function modify($name, $setting_name, $value = '') {

		if (!$this->is_init) {
			EphMapper::addActivity('mapper', 'modify', [
				'name'         => $name,
				'setting_name' => $setting_name,
				'value'        => $value,
			]);
			return false;
		}

		if (!isset($this->sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if ($setting_name === 'base') {
			return trigger_error(sprintf($this->l("Wrong setting_name for shortcode:%s. Base can't be modified."), $name));
		}

		if (is_array($setting_name)) {

			foreach ($setting_name as $key => $value) {
				$this->modify($name, $key, $value);
			}

		} else {
			$this->sc[$name][$setting_name] = $value;
			EphBase::updateShortcodeSetting($name, $setting_name, $value);
		}

		return $this->sc;
	}

	public static function getTagsRegexp() {

		if (empty($this->tags_regexp)) {
			$this->tags_regexp = implode('|', array_keys($this->sc));
		}

		return $this->tags_regexp;
	}

	public static function sort($a, $b) {

		$a_weight = isset($a['weight']) ? (int) $a['weight'] : 0;
		$b_weight = isset($b['weight']) ? (int) $b['weight'] : 0;

		if ($a_weight == $b_weight) {
			$cmpa = array_search($a, $this->user_sorted_sc);
			$cmpb = array_search($b, $this->user_sorted_sc);
			return ($cmpa > $cmpb) ? 1 : -1;
		}

		return ($a_weight < $b_weight) ? 1 : -1;
	}

}
