<?php

/**
 * Class EPHFont_Container
 * Since 4.3
 * vc_map examples:
 *  array(
 *		'type' => 'font_container',
 *		'param_name' => 'font_container',
 *		'value'=>'',
 *		'settings'=>array(
 *			'fields'=>array(
 *
 *				'tag'=>'h2', // default value h2
 *				'text_align',
 *				'font_size',
 *				'line_height',
 * 				'color',
 *				//'font_style_italic'
 *				//'font_style_bold'
 *				//'font_family'
 *
 *				'tag_description' => vc_manager()->l('Select element tag.'),
 *				'text_align_description' => vc_manager()->l('Select text alignment.'),
 *				'font_size_description' => vc_manager()->l('Enter font size.'),
 *				'line_height_description' => vc_manager()->l('Enter line height.'),
 *				'color_description' => vc_manager()->l('Select color for your element.'),
 *				//'font_style_description' => vc_manager()->l('Put your description here'),
 *				//'font_family_description' => vc_manager()->l('Put your description here'),
 *			),
 *		),
 *		// 'description' => '' , // description for field group
 *	),
 *  Ordering of fields, font_family, tag, text_align and etc. will be Same as ordering in array!
 *  To provide default value to field use 'key' => 'value'
 */

class EPHFont_Container {

	/**
	 * @param $settings
	 * @param $value
	 *
	 * @return string
	 */
	public function render( $settings, $value ) {
		$fields = array();
		$values = array();
		extract( $this->_vc_font_container_parse_attributes( $settings['settings']['fields'], $value ) );

		$data   = array();
		$output = '';
		if ( ! empty( $fields ) ) {
                    $vc_manager = vc_manager();
			if ( isset( $fields['tag'] ) ) {
				$data['tag'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Element tag') . '</div>
                    <div class="vc_font_container_form_field-tag-container">
                        <select class="vc_font_container_form_field-tag-select">';
				$tags        = $this->_vc_font_container_get_allowed_tags();
                                
				foreach ( $tags as $tag ) {
					$data['tag'] .= '<option value="' . $tag . '" class="' . $tag . '" ' . ( $values['tag'] == $tag ? 'selected="selected"' : '' ) . '>' .  $tag . '</option>';
				}
				$data['tag'] .= '
                        </select>
                    </div>';
				if ( isset( $fields['tag_description'] ) && strlen( $fields['tag_description'] ) > 0 ) {
					$data['tag'] .= '
                    <span class="vc_description clear">' . $fields['tag_description'] . '</span>
                    ';
				}

				$data['tag'] .= '</div>';
			}
			if ( isset( $fields['font_size'] ) ) {
				$data['font_size'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Font size') . '</div>
                    <div class="vc_font_container_form_field-font_size-container">
                        <input class="vc_font_container_form_field-font_size-input" type="text" value="' . $values['font_size'] . '" />
                    </div>';

				if ( isset( $fields['font_size_description'] ) && strlen( $fields['font_size_description'] ) > 0 ) {
					$data['font_size'] .= '
                    <span class="vc_description clear">' . $fields['font_size_description'] . '</span>
                    ';
				}
				$data['font_size'] .= '</div>';
			}
			if ( isset( $fields['text_align'] ) ) {
				$data['text_align'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Text align') . '</div>
                    <div class="vc_font_container_form_field-text_align-container">
                        <select class="vc_font_container_form_field-text_align-select">
                            <option value="left" class="left" ' . ( $values['text_align'] == 'left' ? 'selected="selected"' : '' ) . '>' . $vc_manager->l('left') . '</option>
                            <option value="right" class="right" ' . ( $values['text_align'] == 'right' ? 'selected="selected"' : '' ) . '>' . $vc_manager->l('right') . '</option>
                            <option value="center" class="center" ' . ( $values['text_align'] == 'center' ? 'selected="selected"' : '' ) . '>' . $vc_manager->l('center') . '</option>
                            <option value="justify" class="justify" ' . ( $values['text_align'] == 'justify' ? 'selected="selected"' : '' ) . '>' . $vc_manager->l('justify') . '</option>
                        </select>
                    </div>';
				if ( isset( $fields['text_align_description'] ) && strlen( $fields['text_align_description'] ) > 0 ) {
					$data['text_align'] .= '
                    <span class="vc_description clear">' . $fields['text_align_description'] . '</span>
                    ';
				}
				$data['text_align'] .= '</div>';
			}
			if ( isset( $fields['line_height'] ) ) {
				$data['line_height'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Line height') . '</div>
                    <div class="vc_font_container_form_field-line_height-container">
                        <input class="vc_font_container_form_field-line_height-input"  type="text"  value="' . $values['line_height'] . '" />
                    </div>';
				if ( isset( $fields['line_height_description'] ) && strlen( $fields['line_height_description'] ) > 0 ) {
					$data['line_height'] .= '
                    <span class="vc_description clear">' . $fields['line_height_description'] . '</span>
                    ';
				}
				$data['line_height'] .= '</div>';
			}
			if ( isset( $fields['color'] ) ) {
				$data['color'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Text color') . '</div>
                    <div class="vc_font_container_form_field-color-container">
                        <div class="color-group">
                            <input type="text" value="' . $values['color'] . '" class="vc_font_container_form_field-color-input vc_color-control" />
                        </div>
                    </div>';
				if ( isset( $fields['color_description'] ) && strlen( $fields['color_description'] ) > 0 ) {
					$data['color'] .= '
                    <span class="vc_description clear">' . $fields['color_description'] . '</span>
                    ';
				}
				$data['color'] .= '</div>';
			}                        
			if ( isset( $fields['font_family'] ) ) {
				$data['font_family'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Font Family') . '</div>
                    <div class="vc_font_container_form_field-font_family-container">
                        <select class="vc_font_container_form_field-font_family-select">';
				$fonts               = $this->_vc_font_container_get_web_safe_fonts();
                                
				foreach ( $fonts as $font_name => $font_data ) {
					$data['font_family'] .= '<option value="' . $font_name . '" class="' . vc_build_safe_css_class( $font_name ) . '" ' . ( strtolower( $values['font_family'] ) == strtolower( $font_name ) ? 'selected="selected"' : '' ) . ' data[font_family]="' . urlencode( $font_data ) . '">' .  $font_name . '</option>';
				}
				$data['font_family'] .= '
                        </select>
                    </div>';
				if ( isset( $fields['font_family_description'] ) && strlen( $fields['font_family_description'] ) > 0 ) {
					$data['font_family'] .= '
                    <span class="vc_description clear">' . $fields['font_family_description'] . '</span>
                    ';
				}
				$data['font_family'] .= '</div>';
			}
			if ( isset( $fields['font_style'] ) ) {
				$data['font_style'] = '
                <div class="vc_row-fluid vc_shortcode-param vc_column">
                    <div class="wpb_element_label">' . $vc_manager->l('Font style') . '</div>
                    <div class="vc_font_container_form_field-font_style-container">
                        <label>
                            <input type="checkbox" class="vc_font_container_form_field-font_style-checkbox italic" value="italic" ' . ( $values['font_style_italic'] == "1" ? 'checked="checked"' : '' ) . '><span class="vc_font_container_form_field-font_style-label italic">' . $vc_manager->l('italic') . '</span>
                         </label>
                        <br />
                        <label>
                            <input type="checkbox" class="vc_font_container_form_field-font_style-checkbox bold" value="bold" ' . ( $values['font_style_bold'] == "1" ? 'checked="checked"' : '' ) . '><span class="vc_font_container_form_field-font_style-label bold">' . $vc_manager->l('bold') . '</span>
                        </label>
                    </div>';
				if ( isset( $fields['font_style_description'] ) && strlen( $fields['font_style_description'] ) > 0 ) {
					$data['font_style'] .= '
                    <span class="vc_description clear">' . $fields['font_style_description'] . '</span>
                    ';
				}
				$data['font_style'] .= '</div>';
			}

			// combine all in output, make sure you follow ordering
			foreach ( $fields as $key => $field ) {
				if ( isset( $data[ $key ] ) ) {
					$output .= $data[ $key ];
				}
			}
		}
		$output .= '<input name="' . $settings['param_name'] . '" class="wpb_vc_param_value  ' . $settings['param_name'] . ' ' . $settings['type'] . '_field" type="hidden" value="' . $value . '" />';

		return $output;
	}

	/**
	 * If field 'font_family' is used this is list of fonts available
	 * To modify this list, you should use add_filter('vc_font_container_get_fonts_filter','your_custom_function');
	 * @return array list of fonts
	 */
	public function _vc_font_container_get_web_safe_fonts() {
		// this is "Web Safe FONTS" from w3c: http://www.w3schools.com/cssref/css_websafe_fonts.asp
		$web_fonts = array(
			'Georgia'             => 'Georgia, serif',
			'Palatino Linotype'   => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
			'Book Antiqua'        => '"Book Antiqua", Palatino, serif',
			'Palatino'            => 'Palatino, serif',
			'Times New Roman'     => '"Times New Roman", Times, serif',
			'Arial'               => 'Arial, Helvetica, sans-serif',
			'Arial Black'         => '"Arial Black", Gadget, sans-serif',
			'Helvetica'           => 'Helvetica, sans-serif',
			'Comic Sans MS'       => '"Comic Sans MS", cursive, sans-serif',
			'Impact'              => 'Impact, Charcoal, sans-serif',
			'Charcoal'            => 'Charcoal, sans-serif',
			'Lucida Sans Unicode' => '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
			'Lucida Grande'       => '"Lucida Grande", sans-serif',
			'Tahoma'              => 'Tahoma, Geneva, sans-serif',
			'Geneva'              => 'Geneva, sans-serif',
			'Trebuchet MS'        => '"Trebuchet MS", Helvetica, sans-serif',
			'Verdana'             => '"Trebuchet MS", Helvetica, sans-serif',
			'Courier New'         => '"Courier New", Courier, monospace',
			'Lucida Console'      => '"Lucida Console", Monaco, monospace',
			'Monaco'              => 'Monaco, monospace'
		);

//		return apply_filters( 'vc_font_container_get_fonts_filter', $web_fonts );
		return $web_fonts;
	}

	/**
	 * If 'tag' field used this is list of allowed tags
	 * To modify this list, you should use add_filter('vc_font_container_get_allowed_tags','your_custom_function');
	 * @return array list of allowed tags
	 */
	public function _vc_font_container_get_allowed_tags() {
		$allowed_tags = array(
			'h2',
			'h3',
			'h4',
			'h5',
			'h6',
			'p',
			//'span', // @note this is not inline element, so you can't use this for text-align
			'div',
		);

//		return apply_filters( 'vc_font_container_get_allowed_tags', $allowed_tags );
		return $allowed_tags ;

	}

	/**
	 * @param $attr
	 * @param $value
	 *
	 * @return array
	 */
	public function _vc_font_container_parse_attributes( $attr, $value ) {
		$fields = array();
		if ( isset( $attr ) ) {
			foreach ( $attr as $key => $val ) {
				if ( is_numeric( $key ) ) {
					$fields[ $val ] = "";
				} else {
					$fields[ $key ] = $val;
				}
			}
		}

		$values = vc_parse_multi_attribute( $value, array(
				'tag'                     => isset( $fields['tag'] ) ? $fields['tag'] : 'h2',
				'font_size'               => isset( $fields['font_size'] ) ? $fields['font_size'] : '',
				'font_style_italic'       => isset( $fields['font_style_italic'] ) ? $fields['font_style_italic'] : '',
				'font_style_bold'         => isset( $fields['font_style_bold'] ) ? $fields['font_style_bold'] : '',
				'font_family'             => isset( $fields['font_family'] ) ? $fields['font_family'] : '',
				'color'                   => isset( $fields['color'] ) ? $fields['color'] : '',
				'line_height'             => isset( $fields['line_height'] ) ? $fields['line_height'] : '',
				'text_align'              => isset( $fields['text_align'] ) ? $fields['text_align'] : 'left',
				'tag_description'         => isset( $fields['tag_description'] ) ? $fields['tag_description'] : '',
				'font_size_description'   => isset( $fields['font_size_description'] ) ? $fields['font_size_description'] : '',
				'font_style_description'  => isset( $fields['font_style_description'] ) ? $fields['font_style_description'] : '',
				'font_family_description' => isset( $fields['font_family_description'] ) ? $fields['font_family_description'] : '',
				'color_description'       => isset( $fields['color_description'] ) ? $fields['color_description'] : 'left',
				'line_height_description' => isset( $fields['line_height_description'] ) ? $fields['line_height_description'] : '',
				'text_align_description'  => isset( $fields['text_align_description'] ) ? $fields['text_align_description'] : ''
			)
		);

		return array( 'fields' => $fields, 'values' => $values );
	}
}


/**
 * @param $settings
 * @param $value
 *
 * @return mixed|void
 */
function vc_font_container_form_field( $settings, $value ) {
	$font_container = new EPHFont_Container();

//	return apply_filters( 'vc_font_container_render_filter', $font_container->render( $settings, $value ) );
	return $font_container->render( $settings, $value );
}
