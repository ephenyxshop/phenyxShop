<?php

/**
 * @since 1.9.1.0
 */
class FrontLinkCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'blocklink',
        'primary'   => 'id_blocklink',
        'multilang' => true,
        'fields'    => [
            'url' 		 => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 100],
			'new_window' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'position'         => ['type' => self::TYPE_INT],

            /* Lang fields */
            'text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 100],
        ],
    ];
    public $url;
	public $new_window;
	public $position;
    public $text;
    

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $idShop = null) {

        parent::__construct($id, $idLang, $idShop);
    }
	
	public function add($autoDate = false, $nullValues = false) {

        $this->position = FrontLink::getLastPosition((int) $this->id);

        if (!parent::add($autoDate, true)) {
            return false;
        }

        return true;
    }
	
	public static function getLastPosition($idBlockLink) {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`) + 1')
                ->from('blocklink')
                ->where('`id_blocklink` = ' . (int) $idBlockLink)
        );
    }


    /**
     * @param null $idLang
     *
     * @return PhenyxShopCollection
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getFrontLink($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $links = new PhenyxShopCollection('FrontLink', $idLang);

        return $links;
    }

   

}
