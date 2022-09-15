<?php

/**
 * @since 1.9.1.0
 */
class NewsletterCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'newsletter',
        'primary'   => 'id_newsletter',
        'fields'    => [
            'email'             => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128],
			'newsletter_date_add'              => ['type' => self::TYPE_DATE,  'validate' => 'isDate'],
			'ip_registration_newsletter' => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'http_referer'                 => ['type' => self::TYPE_STRING, 'size' => 255],
			'id_sendinblue'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'active'              => ['type' => self::TYPE_BOOL],
			
        ],
    ];
   
    public $email;
	public $newsletter_date_add;
	public $ip_registration_newsletter;
	public $http_referer;
	public $id_sendinblue = 0;
	public $active;

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idCompany
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);
		
    }
	
	public static function getIdByEmail($email) {
		
		return Db::getInstance()->getValue(
			(new DbQuery())
				->select('`id_newsletter`')
				->from('newsletter')
				->where('`email` LIKE \'' . $email.'\'')
			);		
	}
	
	public static function getUnSynchUser() {
		
		
		return Db::getInstance()->executeS(
			(new DbQuery())
			->select('n.`id_newsletter`, s.`lastname`, s.`firstname`, n.`email`, s.`phone_mobile`, n.`newsletter_date_add`, n.`id_sendinblue`')
			->leftJoin('student', 's', 's.email = n.email')
			->from('newsletter', 'n')
			->where('n.`active` = 1')
			->where('n.`id_sendinblue` = 0')
		);

		
		
	}

	
	
    
}
