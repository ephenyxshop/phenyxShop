<?php

class EphBase {

	protected $shortcode_edit_form = false;
	protected $templates_editor = false;
	protected $shortcodes = [];
	
	public function init() {

		if (defined('_EPH_ROOT_DIR_')) {
			$this->initAdmin();
		} else {
			$this->addPageCustomCss();
		}

	}

	public function is_admin() {

		 if (defined('_EPH_ROOT_DIR_')) {
			return true;
		}

		return false;
	}

	
	public function initAdmin() {

		$this->editForm()->init();
		$this->templatesEditor()->init();
	}

	public function editForm() {

		return $this->shortcode_edit_form;
	}

	public function templatesEditor() {

		return $this->templates_editor;
	}

	
	public function addShortCode($shortcode) {
		
		$this->shortcodes[$shortcode['base']] = new EPHShortCodeFishBones($shortcode);

	}

	public function getShortCode($tag) {

		return $this->shortcodes[$tag];
	}

	public function removeShortCode($tag) {

		EphComposer::removeShortcode($tag);
	}

	public function updateShortcodeSetting($tag, $name, $value) {

		$this->shortcodes[$tag]->setSettings($name, $value);
	}

	protected function parseShortcodesCustomCss($content) {

		$css = '';

		if (!preg_match('/\s*(\.[^\{]+)\s*\{\s*([^\}]+)\s*\}\s*/', $content)) {
			return $css;
		}

		preg_match_all('/' . Tools::get_shortcode_regex() . '/', $content, $shortcodes);

		foreach ($shortcodes[2] as $index => $tag) {
			$shortcode = EPHMap::getShortCode($tag);
			$attr_array = Tools::shortcode_parse_atts(trim($shortcodes[3][$index]));

			foreach ($shortcode['params'] as $param) {

				if ($param['type'] == 'css_editor' && isset($attr_array[$param['param_name']])) {
					$css .= $attr_array[$param['param_name']];
				}

			}

		}

		foreach ($shortcodes[5] as $shortcode_content) {
			$css .= $this->parseShortcodesCustomCss($shortcode_content);
		}

		return $css;
	}

	public function addPageCustomCss($custom_page_id = null, $custom_page_type = null) {

		$context = Context::getContext();
		$id_lang = $context->language->id;

		if (empty($custom_page_id)) {

			if (Tools::getValue('controller') == 'AdminBlogPost') {
				$page_type = 'smartblog';
				$page_id = Tools::getValue('id_smart_blog_post') ? Tools::getValue('id_smart_blog_post') : "null";
			} else

			if (Tools::getValue('controller') == 'AdminSuppliers') {
				$page_type = 'sup';
				$page_id = Tools::getValue('id_supplier') ? Tools::getValue('id_supplier') : "null";
			} else

			if (Tools::getValue('controller') == 'AdminManufacturers') {
				$page_type = 'man';
				$page_id = Tools::getValue('id_manufacturer') ? Tools::getValue('id_manufacturer') : "null";
			} else

			if (Tools::getValue('controller') == 'AdminCategories') {
				$page_type = 'cat';
				$page_id = Tools::getValue('id_category') ? Tools::getValue('id_category') : "null";
			} else

			if (Tools::getValue('controller') == 'AdminCmsContent') {
				$page_type = 'cms';
				$page_id = Tools::getValue('id_cms') ? Tools::getValue('id_cms') : "null";
			} else

			if (Tools::getValue('controller') == 'Adminvccontentanywhere') {
				$page_type = 'vccaw';
				$page_id = Tools::getValue('id_vccontentanywhere') ? Tools::getValue('id_vccontentanywhere') : "null";
			} else

			if (Tools::getValue('controller') == 'Adminvcproducttabcreator') {
				$page_type = 'vctc';
				$page_id = Tools::getValue('id_vcproducttabcreator') ? Tools::getValue('id_vcproducttabcreator') : "null";
			} else

			if (Tools::getValue('controller') == 'VC_frontend') {

				if (Tools::getValue('id_cms')) {
					$page_type = 'cms';
					$page_id = Tools::getValue('id_cms') ? Tools::getValue('id_cms') : "null";
				} else

				if (Tools::getValue('id_category')) {
					$page_id = Tools::getValue('id_category');
					$page_type = 'cat';
				}

			} else {
				$page_type = $context->controller->php_self;
				$page_id = null;

				if ($page_type == 'product' && Tools::getValue('id_product')) {
					$page_id = intval(Tools::getValue('id_product'));
				} else

				if ($page_type == 'category' && Tools::getValue('id_category')) {
					$page_id = intval(Tools::getValue('id_category'));
					$page_type = 'cat';
				} else

				if ($page_type == 'cms' && isset($context->controller->$page_type->id)) {
					$page_id = $context->controller->$page_type->id;
				} else

				if (Tools::getValue('controller') == 'details' && Tools::getValue('id_post')) {
					// smartblog
					$page_id = Tools::getValue('id_post');
					$page_type = 'smartblog';
				} else

				if (Tools::getValue('controller') == 'supplier' && Tools::getValue('id_supplier')) {
					$page_id = Tools::getValue('id_supplier');
					$page_type = 'sup';
				} else

				if (Tools::getValue('controller') == 'manufacturer' && Tools::getValue('id_manufacturer')) {
					$page_id = Tools::getValue('id_manufacturer');
					$page_type = 'man';
				}

			}

		} else {
			$page_id = $custom_page_id;
			$page_type = $custom_page_type;
		}

		if (!empty($page_id)) {
			$id = $page_id;
			$optionname = "_wpb_{$page_type}_{$id}_{$id_lang}_css";

			$post_custom_css = Configuration::get($optionname);

			if (!empty($post_custom_css)) {
				echo '<style type="text/css" data-type="vc_custom-css">';
				echo htmlspecialchars_decode($post_custom_css);
				echo '</style>';
			}

		}

	}
	
	public static function getVcShared($asset = '') {

        switch ($asset) {
        case 'colors':
            return EphSharedLibrary::getColors();
            break;

        case 'icons':
            return EphSharedLibrary::getIcons();
            break;

        case 'sizes':
            return EphSharedLibrary::getSizes();
            break;

        case 'button styles':
        case 'alert styles':
            return EphSharedLibrary::getButtonStyles();
            break;

        case 'cta styles':
            return EphSharedLibrary::getCtaStyles();
            break;

        case 'text align':
            return EphSharedLibrary::getTextAlign();
            break;

        case 'cta widths':
        case 'separator widths':
            return EphSharedLibrary::getElementWidths();
            break;

        case 'separator styles':
            return EphSharedLibrary::getSeparatorStyles();
            break;

        case 'single image styles':
            return EphSharedLibrary::getBoxStyles();
            break;

        default:
            # code...
            break;
        }

    }

}
