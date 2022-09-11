<?php

class EphMapper {

	protected $init_activity = [];

	function __construct() {

		
	}

	public function init() {

		require_once _EPH_CLASS_DIR_ . 'composer/params/load.php';
		EphMap::setInit();
		$seetings_maps = $this->composerobj->seetings_maps;
		foreach ($seetings_maps as $key => $map) {
			if (!isset($map['base'])) {
            	trigger_error(__("Wrong wpb_map object. Base attribute is required", 'js_composer'), E_USER_ERROR);
            	die();
        	}

        	EphMap::map($key, $attributes);

		}

		$this->callActivities();

	}
	public function vc_textarea_html_form_field( $settings, $value ) {
	$settings_line = '';
	if ( function_exists( 'wp_editor' ) ) {
		$default_content = __( $value, "js_composer" );
		$output_value = '';
		// WP 3.3+
		ob_start();
		wp_editor( '', 'wpb_tinymce_' . $settings['param_name'], array( 'editor_class' => 'wpb-textarea visual_composer_tinymce ' . $settings['param_name'] . ' ' . $settings['type'], 'media_buttons' => true, 'wpautop' => false ) );
		$output_value = ob_get_contents();
		ob_end_clean();
		$settings_line .= $output_value . '<input type="hidden" name="'.$settings['param_name'].'"  class="vc_textarea_html_content wpb_vc_param_value ' . $settings['param_name'] . '" value="' . htmlspecialchars( $default_content ) . '"/>';
	}
	return $settings_line;
}

	public function addActivity($object, $method, $params = []) {

		$this->init_activity[] = [$object, $method, $params];
	}

	protected function callActivities() {

		foreach ($this->init_activity as $activity) {
			list($object, $method, $params) = $activity;

			if ($object == 'mapper') {

				switch ($method) {
				case 'map':
					EPHMap::map($params['tag'], $params['attributes']);
					break;
				case 'drop_param':
					EPHMap::dropParam($params['name'], $params['attribute_name']);
					break;
				case 'add_param':
					EPHMap::addParam($params['name'], $params['attribute']);
					break;
				case 'mutate_param':
					EPHMap::mutateParam($params['name'], $params['attribute']);
					break;
				case 'drop_shortcode':
					EPHMap::dropShortcode($params['name']);
					break;
				case 'modify':
					EPHMap::modify($params['name'], $params['setting_name'], $params['value']);
					break;
				}

			}

		}

	}

}

