<?php

class EphAddElementBox {

	protected function getIcon($params) {

		return '<i class="vc_element-icon' . (!empty($params['icon']) ? ' ' . Tools::safeOutput($params['icon']) : '') . '"></i> ';
	}

	public function renderButton($params) {

		if (!is_array($params) || empty($params)) {
			return '';
		}

		$output = $class = $class_out = $data = $category_css_classes = '';
		$idObject = Tools::getValue('idObject');

		if (!empty($params["class"])) {
			$class_ar = $class_at_out = explode(" ", $params["class"]);

			for ($n = 0; $n < count($class_ar); $n++) {
				$class_ar[$n] .= "_nav";
				$class_at_out[$n] .= "_o";
			}

			$class = ' ' . implode(" ", $class_ar);
			$class_out = ' ' . implode(" ", $class_at_out);
		}

		foreach ($params['_category_ids'] as $id) {
			$category_css_classes .= ' category-' . $id;
		}

		if (isset($params['is_container']) && $params['is_container'] === true) {
			$data .= ' data-is-container="true"';
		}

		$description = !empty($params['description']) ? '<i class="vc_element-description">' . htmlspecialchars($params['description']) . '</i>' : '';
		$output .= '<li data-element="' . $params['base'] . '" id="" class="wpb-layout-element-button_' . $idObject . $category_css_classes . $class_out . '"' . $data . '><div class="vc_el-container"><a id="' . $params['base'] . '" data-tag="' . $params['base'] . '" class="dropable_el vc_shortcode-link clickable_action' . $class . '" href="#">' . $this->getIcon($params) . htmlspecialchars(stripslashes($params["name"])) . $description . '</a></div></li>';
		return $output;
	}

	public function getControls() {

		$output = '<ul class="wpb-content-layouts">';

		foreach (WPBMap::getSortedUserShortCodes() as $element) {

			if (isset($element['content_element']) && $element['content_element'] === false) {
				continue;
			}

			$output .= $this->renderButton($element);
		}

		$output .= '</ul>';
		return $output;
	}

	public function contentCategories() {

		$mod = vc_manager();

		$output = '<ul class="isotope-filter vc_filter-content-elements"><li class="active"><a href="#" data-filter="*">' . $mod->l('Show all') . '</a></li>';
		$_other_category_index = 0;
		$show_other = false;

		foreach (WPBMap::getUserCategories() as $key => $name) {

			if ($name === '_other_category_') {
				$_other_category_index = $key;
				$show_other = true;
			} else {
				$output .= '<li><a href="#" data-filter=".category-' . md5($name) . '">' . $name . '</a></li>';
			}

		}

		if ($show_other) {
			$output .= '<li><a href="#" data-filter=".category-' . $_other_category_index . '">' . $mod->l('Other') . '</a></li>';
		}

		$output .= '</ul>';
		return $output;
	}

	public function render($editor) {

		require_once $editor->vc_path_dir('CORE_DIR', 'class-wpb-map.php');

		$editor->vc_include_template('editors/popups/modal_add_element.tpl.php', [
			'box'    => $this,
			'editor' => $editor,
		]);
	}

}

