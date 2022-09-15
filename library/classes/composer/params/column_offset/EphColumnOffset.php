<?php

class EphColumnOffset extends PhenyxObjectModel {

    protected $settings = [];
    protected $value = '';
    protected $size_types = ['lg' => 'Large', 'md' => 'Medium', 'sm' => 'Small', 'xs' => 'Extra small'];
    protected $column_width_list = [];
    public function __construct($settings, $value) {

        $this->settings = $settings;
        $this->value = $value;
        
        $this->column_width_list = [
            $vc_manager->l('1 column - 1/12')    => '1',
            $vc_manager->l('2 columns - 1/6')    => '2',
            $vc_manager->l('3 columns - 1/4')    => '3',
            $vc_manager->l('4 columns - 1/3')    => '4',
            $vc_manager->l('5 columns - 5/12')   => '5',
            $vc_manager->l('6 columns - 1/2')    => '6',
            $vc_manager->l('7 columns - 7/12')   => '7',
            $vc_manager->l('8 columns - 2/3')    => '8',
            $vc_manager->l('9 columns - 3/4')    => '9',
            $vc_manager->l('10 columns - 5/6')   => '10',
            $vc_manager->l('11 columns - 11/12') => '11',
            $vc_manager->l('12 columns - 1/1')   => '12',
        ];
    }

    public function render() {

        ob_start();
        vc_include_template('params/column_offset/template.tpl.php', [
            'settings' => $this->settings,
            'value'    => $this->value,
            'data'     => $this->valueData(),
            'sizes'    => $this->size_types,
            'param'    => $this,
        ]);
        return ob_get_clean();
    }

    public function valueData() {

        if (!isset($this->data)) {
            $this->data = preg_split('/\s+/', $this->value);
        }

        return $this->data;
    }

    public function sizeControl($size) {

        if ($size === 'sm') {
            return '<span class="vc_description">' . vc_manager()->l('Default value from width attribute') . '</span>';
        }

        $empty_label = $size === 'xs' ? '' : vc_manager()->l('Inherit from smaller');
        $output = '<select name="vc_col_' . $size . '_size" class="vc_column_offset_field" data-type="size-' . $size . '">' . '<option value="" style="color: #ccc;">' . $empty_label . '</option>';

        foreach ($this->column_width_list as $label => $index) {
            $value = 'vc_col-' . $size . '-' . $index;
            $output .= '<option value="' . $value . '"' . (in_array($value, $this->data) ? ' selected="true"' : '') . '>' . $label . '</option>';
        }

        $output .= '</select>';
        return $output;
    }

    public function offsetControl($size) {

        $prefix = 'vc_col-' . $size . '-offset-';
        $empty_label = $size === 'xs' ? vc_manager()->l('No offset') : vc_manager()->l('Inherit from smaller');
        $output = '<select name="vc_' . $size . '_offset_size" class="vc_column_offset_field" data-type="offset-' . $size . '">' . '<option value="" style="color: #ccc;">' . $empty_label . '</option>' . ($size === 'xs' ? '' : '<option value="' . $prefix . '0" style="color: #ccc;">' . vc_manager()->l('No offset') . '</option>');

        foreach ($this->column_width_list as $label => $index) {
            $value = $prefix . $index;
            $output .= '<option value="' . $value . '"' . (in_array($value, $this->data) ? ' selected="true"' : '') . '>' . $label . '</option>';
        }

        $output .= '</select>';
        return $output;
    }
	
	
	public static function vc_load_column_offset_param() {

    	EphComposer::addShortcodeParam('column_offset', 'vc_column_offset_form_field', vc_asset_url('js/params/column_offset.js'));
	}

}

function vc_column_offset_form_field($settings, $value) {

    $column_offset = new Vc_Column_Offset($settings, $value);
    return $column_offset->render();
}

function vc_column_offset_class_merge($column_offset, $width) {

    if (vc_settings()->get('not_responsive_css') === '1') {
        $column_offset = preg_replace('/vc_col\-(lg|md|xs)[^\s]*/', '', $column_offset);
    }

    if (preg_match('/vc_col\-sm\-\d+/', $column_offset)) {
        return $column_offset;
    }

    return $width . (empty($column_offset) ? '' : ' ' . $column_offset);
}

