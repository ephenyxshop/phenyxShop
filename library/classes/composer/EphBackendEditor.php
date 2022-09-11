<?php

class EphBackendEditor implements Vc_Editor_Interface {

	protected $layout;

	public function addHooksSettings() {

		$actions = [
			'wpb_get_element_backend_html' => [
				 & $this,
				'elementBackendHtml',
			],
		];

		if ($action = Tools::getValue('action')) {

			if (isset($actions[$action])) {
				call_user_func($actions[$action]);
			}

		}

	}

	public function renderEditor($post = null) {

		$this->post = $post;
		$this->post_custom_css = get_post_meta($post->ID, '_wpb_post_custom_css', true);
		vc_include_template('editors/backend_editor.tpl.php', [
			'editor' => $this,
			'post'   => $this->post,
		]);
		add_action('admin_footer', [
			 & $this,
			'renderEditorFooter',
		]);
		do_action('vc_backend_editor_render');
	}

	public function renderEditorFooter() {

		vc_include_template('editors/partials/backend_editor_footer.tpl.php', [
			'editor' => $this,
			'post'   => $this->post,
		]);
		do_action('vc_backend_editor_footer_render');
	}

	public function elementBackendHtml() {

		$jscomposer = JsComposer::getInstance();
		$data_element = Tools::getValue('data_element');

		if ($data_element == 'vc_column' && Tools::getValue('data_width') !== null) {
			$output = EphComposer::doShortcode('[vc_column width="' . Tools::getValue('data_width') . '"]');
			echo $output;
		} else

		if ($data_element == 'vc_row' || $data_element == 'vc_row_inner') {
			$output = EphComposer::doShortcode('[' . $data_element . ']');
			echo $output;
		} else {
			$output = EphComposer::doShortcode('[' . $data_element . ']');
			echo $output;
		}

		die();
	}

}

