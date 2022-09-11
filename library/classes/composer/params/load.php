<?php

require_once (_EPH_CLASS_DIR_ . 'composer/params/textarea_html/textarea_html.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/colorpicker/colorpicker.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/loop/loop.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/vc_link/vc_link.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/options/options.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/sorted_list/sorted_list.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/css_editor/css_editor.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/tab_id/tab_id.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/href/href.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/autocomplete/autocomplete.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/font_container/font_container.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/google_fonts/google_fonts.php' );
require_once (_EPH_CLASS_DIR_ . 'composer/params/column_offset/column_offset.php' );
/* New params */
require_once (_EPH_CLASS_DIR_ . 'composer/params/el_id/el_id.php' );

global $vc_params_list;
$vc_params_list = array( 'textarea_html', 'colorpicker', 'loop', 'vc_link', 'options', 'sorted_list', 'css_editor', 'font_container', 'google_fonts', 'autocomplete' , 'tab_id', 'href', 'el_id');