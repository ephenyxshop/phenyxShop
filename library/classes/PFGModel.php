<?php

class PFGModelCore extends PhenyxObjectModel {

	public $send_mail_to;
	public $action_sender;
	public $action_admin;
	public $template;
	public $title;
	public $link_rewrite;
	public $subject_sender;
	public $subject_admin;
	public $active;
	public $is_only_connected;
	public $one_submission_only =0;
	public $unauth_redirect_url;
	public $accessible;
	public $header;
	public $footer;
	public $success;
	public $send_label;
	public $message_sender;
	public $message_admin;

	public static $definition = [
		'table'     => 'pfg',
		'primary'   => 'id_pfg',
		'multilang' => true,
		'fields'    => [
			'send_mail_to'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 200],
			'action_sender'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 200],
			'action_admin'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'template'			  => ['type' => self::TYPE_STRING,  'validate' => 'isString', 'size' => 128],
			'active'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
			'is_only_connected'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
			'one_submission_only'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
			'unauth_redirect_url' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
			'accessible'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'title'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'required' => true],
			'link_rewrite'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'size' => 128],
			'subject_sender'      => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
			'subject_admin'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
			'header'              => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'required' => false],
			'footer'              => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'required' => false],
			'success'             => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'required' => false],
			'send_label'          => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'size' => 255],
			'message_sender'      => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'required' => false],
			'message_admin'       => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'lang' => true, 'required' => false],
		],
	];

	/**
	 * Add the model in the database and returns it
	 *
	 * @param boolean $autodate
	 * @param boolean $null_values
	 *
	 * @return object
	 */
	public function add($autodate = true, $null_values = false) {

		foreach ($this->success as $key => $value) {

			if (Tools::substr($this->success[$key], 0, 7) === '<p>http') {
				$this->success[$key] = strip_tags($value);
			}

		}

		$res = parent::add($autodate, $null_values);

		return $res;
	}

	public function update($null_values = false) {

		foreach ($this->success as $key => $value) {

			if (Tools::substr($this->success[$key], 0, 7) === '<p>http') {
				$this->success[$key] = strip_tags($value);
			}

		}

		return parent::update($null_values);
	}

	/**
	 * Delete the instance in the database
	 *
	 * @return boolean
	 */
	public function delete() {
		
		return parent::delete();
	}
	
	public static function getFormsbyId($idForm) {
		
		return Db::getInstance()->getValue(
			(new DbQuery())
				->select('COUNT(id_submission)')
				->from('pfg_submissions')
				->where('id_pfg = '.$idForm)
		);
	}
	
	public function getSubmitEmail() {
		
		$entries = Db::getInstance()->executeS(
			(new DbQuery())
				->select('*')
				->from('pfg_submissions')
				->where('`id_pfg` ='.$this->id)
		);
		$emails = [];
		
		foreach($entries as &$entrie) {
			$entry = Tools::jsonDecode($entrie['entry'], true);
			foreach($entry as $key => $value) {
				if($key == 'email')	 {
					$emails[] = $value;	
				}	   							
			}
		}
		
		return $emails;
	}

}
