<?php

class PFGRenderer {

	private $form;
	private $fields;
	private $errors = [];

	/**
	 * Create new instance of the PFGRenderer class
	 * Verify if the given form id is valid
	 *
	 * @param int $id The form id
	 */
	public function __construct($id, $processSubmit = true) {

		$this->form = new PFGModel($id);

		if (!$this->form->active) {
			throw new Exception('This form is not active.');
		}

		$this->fields = PFGFieldModel::findFields($this->form->id);

		if (count($this->fields) == 0) {
			throw new Exception('No fields available for this form.');
		}

		if ($processSubmit && Tools::isSubmit('submitMessage')) {
			$this->processSubmit();
		}

	}

	/**
	 * Returns the current form
	 *
	 * @return Object
	 */
	public function getForm() {

		return $this->form;
	}

	/**
	 * Indicate if this form is allowed to be displayed
	 * for the current user
	 *
	 * @param int $id Id of the form to check
	 * @param boolean $checkUrl (default false) : Also check if only URL is allowed
	 *
	 * @return boolean
	 */
	public function isAllowed($check_url = false) {

		if (((bool) $this->form->is_only_connected) && !Context::getContext()->customer->isLogged()) {
			return false;
		}

		if ($check_url) {

			if ($this->form->accessible === '2') {
				return false;
			}

		}

		return true;
	}

	/**
	 * Generate and returns the form
	 *
	 * @return string
	 */
	public function displayForm() {

		$form_fields = [];

		foreach ($this->fields as $field) {
			$type = 'text';
			$element = '';

			switch ($field['type']) {
			case 'text':
			case 'number':
			case 'email':
			case 'url':
			case 'file':
				$element = '<input type="' . $field['type'] . '" ';

				if (Tools::isSubmit($field['name'])) {
					$element .= 'value="' . Tools::getValue($field['name']) . '" ';
				} else
				if ($field['type'] === 'email') {
					$id_customer = Context::getContext()->student->id;

					if (!empty($id_customer)) {
						$element .= 'value="' . Context::getContext()->student->email . '" ';
					}

				}
				$element .= ' placeholder="'.$field['label'].'" ';
				$element = $this->addAttributes($element, $field);
				$element .= '/>';
				break;
			case 'hidden':
				$type = 'hidden';
				$element = '<input type="' . $field['type'] . '" ';
				$element .= 'value="' . $field['values'] . '" ';

				$element = $this->addAttributes($element, $field);
				$element .= '/>';
				break;
			case 'textarea':
				$type = $field['type'];
				$element = '<textarea rows="15" cols="10" ';
				$element = $this->addAttributes($element, $field);
				$element .= '>';

				$element .= Tools::getValue($field['name']);

				$element .= '</textarea>';
				break;
			case 'select':
				$type = $field['type'];
				$value_post = Tools::getValue($field['name']);

				$element = '<select ';
				$element = $this->addAttributes($element, $field);
				$element .= '>';

				if (!empty($field['values'])) {
					$values = explode(',', $field['values']);

					foreach ($values as $value) {
						$value = trim($value);
						$element .= '<option value="' . $value . '"';

						if ($value_post === $value) {
							$element .= ' selected';
						}

						$element .= '>' . $value . '</option>';
					}

				}

				$element .= '</select>';
				break;
			case 'radio':
				$type = $field['type'];
				$value_post = Tools::getValue($field['name']);

				if (!empty($field['values'])) {
					$values = explode(',', $field['values']);
					$element = '<span>';

					foreach ($values as $value) {
						$value = trim($value);

						if (Tools::substr(_EPH_VERSION_, 0, 3) === '1.6') {
							$element .= '<label class="checkbox">';
						} else {
							$element .= '<label class="input">';
						}

						$element .= '<input type="radio" value="' . $value . '" ';

						if ($value_post === $value) {
							$element .= 'checked ';
						}

						$element = $this->addAttributes($element, $field, false);
						$element .= '/> ' . $value . '</label>';
					}

					$element .= '</span>';
				}

				break;
			case 'checkbox':
				$type = $field['type'];

				if (Tools::substr(_EPH_VERSION_, 0, 3) === '1.6') {
					$element = '<label class="checkbox"><input type="checkbox" value="true" ';
				} else {
					$element = '<label class="input"><input type="checkbox" value="true" ';
				}

				if (Tools::getValue($field['name']) === 'true') {
					$element .= 'checked ';
				}

				$element = $this->addAttributes($element, $field);
				$element .= '/> ' . $field['values'] . '</label>';
				break;
			case 'multicheckbox':
				$type = $field['type'];
				$values = explode(',', $field['values']);
				$element = '<span>';

				foreach ($values as $key => $value) {

					if (Tools::substr(_EPH_VERSION_, 0, 3) === '1.6') {
						$element .= '<label class="checkbox"><input type="checkbox" value="' . $key . '" ';
					} else {
						$element .= '<label class="input"><input type="checkbox" value="' . $key . '" ';
					}

					$element .= 'checked ';

					$element = $this->addAttributes($element, $field);
					$element .= '/> ' . $value . '</label>';
				}

				$element .= '</span>';

				break;
			case 'captcha':
				$element = '<label class="checkbox"><img src="' . __EPH_BASE_URI__ .
					'modules/powerfulformgenerator/controllers/front/captcha.php" alt="Captcha value" /></label>';
				$element .= '<input type="' . $field['type'] . '" ';
				$element = $this->addAttributes($element, $field);
				$element .= '/>';
				break;
			case 'separator';
				$type = $field['type'];
				$element = '<hr />';
				break;
			case 'legend':
			case 'static':
				$type = $field['type'];
				$element = '<br />';
				break;
			}

			if (empty($element)) {
				continue;
			}

			$form_fields[] = [
				'type'     => $type,
				'name'     => $field['name'],
				'label'    => $field['label'],
				'value'    => $field['values'],
				'element'  => $element,
				'id'       => 'field_' . $field['name'],
				'class'    => $field['class'],
				'required' => ($field['required'] === '1') ? true : false,
			];
		}

		$smarty_assign = [
			'title'     => $this->form->title[Context::getContext()->language->id],
			'header'    => $this->form->header[Context::getContext()->language->id],
			'footer'    => $this->form->footer[Context::getContext()->language->id],
			'success'   => empty($this->form->success[Context::getContext()->language->id]) ? null : $this->form->success[Context::getContext()->language->id],
			'label_btn' => $this->form->send_label[Context::getContext()->language->id],
			'fields'    => $form_fields,
			'form_id'   => $this->form->id,
			'errors'    => $this->errors,
		];

		if (Context::getContext()->controller instanceOf PowerfulFormGeneratorDisplayModuleFrontController) {
			$smarty_assign['path'] = $this->form->title[Context::getContext()->language->id];
		}

		Context::getContext()->smarty->assign($smarty_assign);

		if (method_exists('Media', 'addJsDef')) {
			Media::addJsDef([
				'contact_fileDefaultHtml' => $this->l('No file selected'),
				'contact_fileButtonHtml'  => $this->l('Choose File'),
			]);
		}

		$template_name = 'form.tpl';

		if (Tools::substr(_EPH_VERSION_, 0, 3) === '1.6') {
			$template_name = 'form-1.6.tpl';
		}

		return Context::getContext()->smarty->fetch(_EPH_THEME_DIR_ . 'formulaire/' . $template_name);
	}

	/**
	 * Add attributes to the current field
	 * Attributes like required, class, style, extra, etc
	 *
	 * @param string $element Current HTML structure of the element
	 * @param array $field Field datas from the database
	 * @param boolean $ignore_id Indicate if weither we add an ID or not to this field
	 *
	 * @return string
	 */
	private function addAttributes($element, $field, $ignore_id = false) {

		//  f.name, f.required, f.class, f.style, f.extra

		if (strpos($field['extra'], 'multiple') !== false && Tools::substr($field['name'], -2) !== '[]') {
			$field['name'] .= '[]';
		} else
		if ($field['type'] === 'multicheckbox' && Tools::substr($field['name'], -2) !== '[]') {
			$field['name'] .= '[]';
		}

		$element .= 'name="' . $field['name'] . '" ';

		if (!$ignore_id) {
			$element .= 'id="field_' . $field['name'] . '" ';
		}

		if ($field['required'] === '1' && $field['type'] !== 'multicheckbox') {
			$element .= 'required ';
		}

		if (Tools::substr(_EPH_VERSION_, 0, 3) === '1.6' && $field['type'] !== 'file') {
			$element .= 'class="form-control "';
		}

		if (isset($field['style']) && !empty($field['style'])) {
			$element .= 'style="' . $field['style'] . '" ';
		}

		if (isset($field['extra']) && !empty($field['extra'])) {
			$element .= ' ' . $field['extra'] . ' ';
		}

		return $element;
	}

	/**
	 * Little helper to translate strings in this class
	 *
	 * @param string $message The original message string
	 * @param array $sprintf Possible variables to replace in the given $message
	 *
	 * @return string
	 */
	private function l($message, $sprintf = []) {

		return Translate::getModuleTranslation('powerfulformgenerator', $message, __CLASS__, $sprintf);
	}

	/**
	 * Process the submitted form
	 */
	public function processSubmit() {

		
		$results = [];
		$contains_files = false;
		$files = [];
		
		if($this->form->one_submission_only) {
			
			$emails = $this->form->getSubmitEmail();
			$mailSubmited = Tools::getValue('email');
			if(in_array($mailSubmited, $emails)) {
				
				return false;
			}
		}
		
		
		
		

		$news_letter_optin = false;
		$senders_email = false;

		foreach ($this->fields as $field) {

			if ($field['type'] === 'legend') {
				continue;
			}

			$validated_field = $this->validateField($field);

			if (!is_null($validated_field)) {
				$results[$field['name']] = $validated_field;
			}

			if ($field['type'] === 'static') {
				$results[$field['name']] = $field['values'];
			}

			if ($field['type'] === 'file' && isset($results[$field['name']])) {
				$contains_files = true;

				if (!is_array($results[$field['name']])) {
					$results[$field['name']] = [$results[$field['name']]];
				}

				foreach ($results[$field['name']] as $key => $value) {

					if (is_null($value)) {
						continue;
					}

					$field_name = $field['name'];

					if (Tools::substr($field_name, -2) === '[]') {
						$field_name = Tools::substr($field_name, 0, -2);
					}

					$files[] = [
						'old' => (is_array($_FILES[$field_name]['tmp_name']) ? $_FILES[$field_name]['tmp_name'][$key] : $_FILES[$field_name]['tmp_name']),
						'new' => $results[$field['name']][$key],
					];

					$results[$field['name']][$key] = _EPH_BASE_URL_ . __EPH_BASE_URI__ . 'upload/pfg/' . $results[$field['name']][$key];
				}

			}

			switch ($field['related']) {
			case 'email':
				$senders_email = Tools::getValue($field['name']);
				break;
			case 'subject':
				$sender_subject = Tools::getValue($field['name']);
				$admin_subject = Tools::getValue($field['name']);
				break;
			case 'newsletter':
				$field_name = Tools::getValue($field['name']);
				$news_letter_optin = !empty($field_name);
				break;
			}

		}

		if (count($this->errors) > 0) {
			return;
		}

		// We replace the variables with their true values

		foreach ($this->fields as $field) {
			$replace_value = null;

			if (!isset($results[$field['name']]) || empty($results[$field['name']])) {
				$replace_value = '';
			} else {

				if (is_array($results[$field['name']])) {
					$replace_value = implode(', ', $results[$field['name']]);
				} else {
					$replace_value = $results[$field['name']];
				}

			}

			$this->form->subject_sender[Context::getContext()->language->id] = str_replace('{$' . $field['name'] . '}', $replace_value, $this->form->subject_sender[Context::getContext()->language->id]);

			$this->form->subject_admin[Context::getContext()->language->id] = str_replace('{$' . $field['name'] . '}', $replace_value, $this->form->subject_admin[Context::getContext()->language->id]);

			$this->form->success[Context::getContext()->language->id] = str_replace('{$' . $field['name'] . '}', $replace_value, $this->form->success[Context::getContext()->language->id]);

			$this->form->message_admin[Context::getContext()->language->id] = str_replace('{$' . $field['name'] . '}', $replace_value, $this->form->message_admin[Context::getContext()->language->id]);

			$this->form->message_sender[Context::getContext()->language->id] = str_replace('{$' . $field['name'] . '}', $replace_value, $this->form->message_sender[Context::getContext()->language->id]);
		}

		if (empty($sender_subject)) {
			$sender_subject = (empty($this->form->subject_sender[Context::getContext()->language->id]) ? $this->l('No subject') : $this->form->subject_sender[Context::getContext()->language->id]);
		}

		if (empty($admin_subject)) {
			$admin_subject = (empty($this->form->subject_admin[Context::getContext()->language->id]) ? $this->l('No subject') : $this->form->subject_admin[Context::getContext()->language->id]);
		}

		if ($contains_files) {
			$destination_directory = _EPH_ROOT_DIR_ . '/upload/pfg/';

			if (!file_exists($destination_directory)) {
				mkdir($destination_directory, 0777, true);
			}

			foreach ($files as $file) {
				rename($file['old'], $destination_directory . $file['new']);
				chmod($destination_directory . $file['new'], 0644);
			}

		}

		// Subscribing to the newsletter

		if ($news_letter_optin && !empty($senders_email)) {

			if (count(Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'newsletter";')) > 0) {
				Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ .
					'newsletter` (`id_shop`, `id_shop_group`, `email`, `newsletter_date_add`, `ip_registration_newsletter`, `active`)
					VALUES (' . pSQL((int) Context::getContext()->shop->id) . ', ' . pSQL((int) Context::getContext()->shop->id_shop_group) . ', "' .
					pSQL($senders_email) . '", NOW(), "' . pSQL($_SERVER['REMOTE_ADDR']) . '", 1)'
				);
			}

			Db::getInstance()->execute('UPDATE IGNORE `' . _DB_PREFIX_ . 'student` SET newsletter = 1 WHERE email = "' . pSQL($senders_email) . '" LIMIT 1;');
		}

		$id_customer = Context::getContext()->student->id;

		if (!empty($id_customer)) {
			$customer = Context::getContext()->student;

			$results['_customer'] = [
				'id'      => $id_customer,
				'display' => $customer->firstname . ' ' . $customer->lastname,
			];
		}

		

		$admin_message = null;

		switch ($this->form->action_admin) {
		case 'message':
			$admin_message = [
				'{message_txt}'  => strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->form->message_admin[Context::getContext()->language->id])),
				'{message_html}' => $this->form->message_admin[Context::getContext()->language->id],
			];
			break;
		case 'form':
			$admin_message = $this->generateMessageFromResults($results);
			break;
		}

		$this->createMailFolder(Context::getContext()->language->iso_code);

		// We save before sending emails
		Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'pfg_submissions` (`id_pfg`, `entry`, `date_add`) VALUES (' .
			pSQL((int) $this->form->id) . ', "' . pSQL(Tools::jsonEncode($results)) . '", NOW());');

		if ($admin_message) {
			$emails = array_map('trim', explode(',', $this->form->send_mail_to));

			foreach ($emails as $email) {

				if (!Mail::Send((int) Context::getContext()->language->id, 'message',
					$admin_subject, $admin_message, $email, null, null, null, null, null, dirname(__FILE__) . '/../mails/')) {
					$this->errors[] = $this->l('An error occured while sending the email.');
					
				}

			}

		}

		if ($senders_email) {
			$sender_message = null;

			switch ($this->form->action_sender) {
			case 'message':
				$sender_message = [
					'{message_txt}'  => strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->form->message_sender[Context::getContext()->language->id])),
					'{message_html}' => $this->form->message_sender[Context::getContext()->language->id],
				];
				break;
			case 'form':
				$sender_message = $this->generateMessageFromResults($results);
				break;
			}

			if ($sender_message) {

				if (!Mail::Send((int) Context::getContext()->language->id, 'message',
					$sender_subject, $sender_message, $senders_email, null, null, null, null, null, dirname(__FILE__) . '/../mails/')) {
					$this->errors[] = $this->l('An error occured while sending the email.');
					
				}

			}

		}
		
		


		return true;

	}

	/**
	 * Validate the field agains't the various restrictions
	 * Like required, type (email, select), etc
	 *
	 * @param array $field Field data from the database
	 *
	 * @return mixed Value of the field(s) or null if a validation error occured.
	 */
	private function validateField($field) {

		if (Tools::substr($field['name'], -2) === '[]') {
			$value = Tools::getValue(Tools::substr($field['name'], 0, -2));
		} else {
			$value = Tools::getValue($field['name']);
		}

		if ($field['required'] === '1' && empty($value) && $field['type'] !== 'file') {
			$this->errors[] = $this->l('The field %s is required.', [$field['label']]);
		}

		if ($field['type'] === 'number') {

			if (!is_numeric($value)) {
				$this->errors[] = $this->l('The field %s must be a valid number.', [$field['label']]);
			}

		} else
		if ($field['type'] === 'email') {

			if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
				$this->errors[] = $this->l('The field %s must be a valid email.', [$field['label']]);
			}

		} else
		if ($field['type'] === 'url') {

			if (!filter_var($value, FILTER_VALIDATE_URL)) {
				$this->errors[] = $this->l('The field %s must be a valid URL.', [$field['label']]);
			}

		} else
		if (in_array($field['type'], ['select', 'radio'])) {
			$values = array_map('trim', explode(',', $field['values']));

			if (is_array($value)) {

				foreach ($value as $select_val) {
					$select_val = Tools::htmlentitiesDecodeUTF8($select_val);

					if (!in_array($select_val, $values)) {
						$this->errors[] = $this->l('Invalid entry given for %s.', [$field['label']]);
					}

				}

			} else {
				$value = Tools::htmlentitiesDecodeUTF8($value);

				if (!in_array($value, $values)) {
					$this->errors[] = $this->l('Invalid entry given for %s.', [$field['label']]);
				}

			}

		} else
		if ($field['type'] === 'multicheckbox') {

			if ($field['required'] === '1' && (empty($value) || count($value) === 0)) {
				$this->errors[] = $this->l('The field %s is required.', [$field['label']]);
				return;
			}

		} else
		if ($field['type'] === 'file') {
			// Tools is buggy for files

			$field_name = $field['name'];
			$is_multiple = false;

			if (Tools::substr($field_name, -2) === '[]') {
				$is_multiple = true;
				$field_name = Tools::substr($field_name, 0, -2);
			}

			if ($field['required'] === '1') {

				if ($is_multiple) {

					if (empty($_FILES[$field_name][0]['name'])) {
						$this->errors[] = $this->l('The field %s is required.', [$field['label']]);
						return;
					}

				} else {

					if (empty($_FILES[$field_name]['name'])) {
						$this->errors[] = $this->l('The field %s is required.', [$field['label']]);
						return;
					}

				}

			}

			$values = array_map('trim', explode(',', $field['values']));

			if (count($_FILES[$field_name]['name']) === 1) {

				if (empty($_FILES[$field_name]['name'])) {
					return;
				}

				$extension = pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);

				if (!in_array($extension, $values)) {
					$this->errors[] = $this->l('Invalid file format given for %s (Only %s allowed).', [$field['label'], $field['values']]);
				}

				return uniqid() . '.' . $extension;
			} else
			if (count($_FILES[$field_name]['name']) > 1) {
				$files_results = [];

				foreach ($_FILES[$field_name]['name'] as $key => $filename) {

					if (empty($_FILES[$field_name]['name'][$key])) {
						continue;
					}

					$extension = pathinfo($_FILES[$field_name]['name'][$key], PATHINFO_EXTENSION);

					if (!in_array($extension, $values)) {
						$this->errors[] = $this->l('Invalid file format given for %s (Only %s allowed).', [$field['label'], $field['values']]);
					}

					$files_results[$key] = uniqid() . '.' . $extension;
				}

				return $files_results;
			}

		} else
		if ($field['type'] === 'captcha') {

			if ($value !== Context::getContext()->cookie->pfg_captcha_string) {
				$this->errors[] = $this->l('Invalid captcha value.', [$field['label']]);
			}

			unset(Context::getContext()->cookie->pfg_captcha_string);
			return null;
		}

		return $value;
	}

	/**
	 * Generate the message based on the form data
	 * This will be used in the back office (when displaying an entry)
	 *     and for the email sent.
	 *
	 * @param array $results An associative array of field_name => value
	 *
	 * @return array The formatted text, in txt and html format
	 */
	private function generateMessageFromResults($results) {

		$message_txt = '';
		$message_html = '';

		foreach ($this->fields as $field) {

			if (!isset($results[$field['name']])) {
				continue;
			}

			if ($field['type'] === 'separator' || $field['type'] === 'captcha') {
				continue;
			}

			$value = $results[$field['name']];

			if ('true' === $value) {
				$value = $this->l('Yes');
			}

			if ('false' === $value) {
				$value = $this->l('No');
			}

			$message_txt .= ' * ' . $field['label'] . ' : ';
			$message_html .= '<strong>' . $field['label'] . '</strong> : ';

			if (is_array($value)) {

				if ($field['type'] === 'multicheckbox') {
					$multi_values = explode(',', $field['values']);
					$tmp_values = [];

					foreach ($value as $element) {
						$tmp_values[] = $multi_values[(int) $element];
					}

					$value = $tmp_values;
				}

				$message_txt .= implode(', ', $value) . "\n";

				$message_html .= '<ul><li>';
				$message_html .= implode('</li><li>', $value);
				$message_html .= '</li></ul>' . "\n";
			} else {
				$message_txt .= $value . "\n";
				$message_html .= $value . '<br />';
			}

		}

		if (isset($results['_customer'])) {
			$message_txt .= ' * Customer informations : ' . $results['_customer']['display'] . ' (ID: ' . $results['_customer']['id'] . ')' . "\n";
			$message_html .= '<strong>Customer informations</strong> : ' . $results['_customer']['display'] . ' (ID: ' . $results['_customer']['id'] . ')<br />';
		}

		if (isset($results['_product'])) {
			$message_txt .= ' * Product informations : ' . $results['_product']['url'] . "\n";
			$message_html .= '<strong>Product informations</strong> : <a href="' . $results['_product']['url'] . '">' . $results['_product']['display'] . '</a><br />';
		}

		return [
			'{message_txt}'  => $message_txt,
			'{message_html}' => $message_html,
		];
	}

	/**
	 * In case the current language is not one pre-created
	 * We create it on the fly to enable email sending.
	 *
	 * @param string $language_code Language code of the email
	 */
	private function createMailFolder($language_code) {

		$mail_dir = dirname(__FILE__) . '/../mails/';

		if (file_exists($mail_dir . $language_code)) {
			return true;
		}

		mkdir($mail_dir . $language_code);
		Tools::copy($mail_dir . 'orig/index.php', $mail_dir . Tools::strtolower($language_code) . '/index.php');
		Tools::copy($mail_dir . 'orig/message.html', $mail_dir . Tools::strtolower($language_code) . '/message.html');
		Tools::copy($mail_dir . 'orig/message.txt', $mail_dir . Tools::strtolower($language_code) . '/message.txt');
	}

}
