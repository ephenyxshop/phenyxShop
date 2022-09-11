<?php
/**
 * WPBakery Visual Composer shortcodes attributes class.
 *
 * This class and functions represents ability which will allow you to create attributes settings fields to
 * control new attributes.
 * New attributes can be added to shortcode settings by using param array in wp_map function
 *
 * @package WPBakeryVisualComposer
 *
 */



class EPHShortcodeParams {
	
	protected static $params = array();
	
	protected static $scripts = array();
	protected static $enqueue_script = array();
	protected static $scripts_to_register = array();
	protected static $is_enqueue = false;

	
	public static function registerScript( $script ) {
		$script_name = 'vc_edit_form_enqueue_script_' . md5( $script );
		self::$enqueue_script[] = array('name' => $script_name, 'script' => $script);
	}

	

	public static function addField( $name, $form_field_callback, $script_url = null ) {

		$result = false;
		if ( ! empty( $name ) && ! empty( $form_field_callback ) ) {
			self::$params[$name] = array(
				'callbacks' => array(
					'form' => $form_field_callback
				)
			);
			$result = true;

			if ( is_string( $script_url ) && ! in_array( $script_url, self::$scripts ) ) {
				self::registerScript( $script_url );
				self::$scripts[] = $script_url;
			}
		}
		return $result;
	}

	
	public static function renderSettingsField( $name, $param_settings, $param_value ) {
                
		if ( isset( self::$params[$name]['callbacks']['form'] ) ) {
			return call_user_func( self::$params[$name]['callbacks']['form'], $param_settings, $param_value );
		}
		return '';
	}

	

	public static function getScripts() {
		return self::$scripts;
	}

	public static function setEnqueue( $value ) {
		self::$is_enqueue = (boolean)$value;
	}

	public static function isEnqueue() {
		return self::$is_enqueue;
	}
}


function add_shortcode_param( $name, $form_field_callback, $script_url = null ) {
	return EPHShortcodeParams::addField( $name, $form_field_callback, $script_url );
}

function do_shortcode_param_settings_field( $name, $param_settings, $param_value ) {
	return EPHShortcodeParams::renderSettingsField( $name, $param_settings, $param_value );
}

function vc_generate_dependencies_attributes( $settings ) {
	return '';
}
