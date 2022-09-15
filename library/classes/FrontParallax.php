<?php

class FrontParallaxCore extends PhenyxObjectModel {

    public $id;
    public $id_xprtparrallaxblocktbl;
    public $image_type;
	public $video_link;
	public $is_video;
    public $btntarget;
    public $image;
    public $height;
    public $padding;
    public $margin;
    public $hook;
    public $title;
    public $subtitle;
    public $btntext;
    public $btnurl;
    public $fullwidth;
    public $content;
    public $contentposition;
    public $position = 0;
    public $active = 1;
    public static $definition = [
        'table'     => 'xprtparrallaxblocktbl',
        'primary'   => 'id_xprtparrallaxblocktbl',
        'multilang' => true,
        'fields'    => [
			'is_video'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'video_link'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'image_type'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'btntarget'       => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'image'           => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'height'          => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'padding'         => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'margin'          => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'hook'            => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'fullwidth'       => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt'],
            'position'        => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt'],
            'active'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'title'           => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true],
            'subtitle'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true],
            'btntext'         => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true],
            'btnurl'          => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true],
            'content'         => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'lang' => true],
            'contentposition' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
        ],
    ];
    
	
	public function __construct($id = null, $id_lang = null) {

       
        parent::__construct($id, $id_lang);
    }

    public function add($autodate = true, $null_values = false) {

        if ($this->position <= 0) {
            $this->position = self::getTopPosition() + 1;
        }

       
        if (!parent::add($autodate, $null_values) || !Validate::isLoadedObject($this)) {
            return false;
        }

        return true;
    }

    public function update($null_values = false) {

       

        if (!parent::update($null_values)) {
            return false;
        }

        return true;
    }

    
    public static function getTopPosition() {

        $sql = 'SELECT MAX(`position`)
                FROM `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`';
        $position = DB::getInstance()->getValue($sql);
        return (is_numeric($position)) ? $position : -1;
    }

    public static function GetParrallaxBlock($hook = NULL, $id_lang = NULL, $id_company = NULL) {

        if ($hook == NULL) {
            return false;
        }

        if ($id_lang == NULL) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'xprtparrallaxblocktbl x INNER JOIN
                ' . _DB_PREFIX_ . 'xprtparrallaxblocktbl_lang xl ON x.id_xprtparrallaxblocktbl=xl.id_xprtparrallaxblocktbl
                AND x.active= 1 AND x.hook = "' . $hook . '"';
        // print $sql;

        if (!$parrallaxs = DB::getInstance()->executeS($sql)) {
            return false;
        } else {
            return $parrallaxs;
        }

    }

    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance()->executeS('
            SELECT `id_xprtparrallaxblocktbl`, `position`
            FROM `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
            ORDER BY `position` ASC'
        )) {
            return false;
        }

        if (!empty($res)) {
            foreach ($res as $xprtparrallaxblocktbl) {
                if ((int) $xprtparrallaxblocktbl['id_xprtparrallaxblocktbl'] == (int) $this->id) {
                    $moved_xprtparrallaxblocktbl = $xprtparrallaxblocktbl;
                }
            }
        }

        if (!isset($moved_xprtparrallaxblocktbl) || !isset($position)) {
            return false;
        }

        $queryx = ' UPDATE `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
        SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
        WHERE `position`
        ' . ($way
            ? '> ' . (int) $moved_xprtparrallaxblocktbl['position'] . ' AND `position` <= ' . (int) $position
            : '< ' . (int) $moved_xprtparrallaxblocktbl['position'] . ' AND `position` >= ' . (int) $position . '
        ');
        $queryy = ' UPDATE `' . _DB_PREFIX_ . 'xprtparrallaxblocktbl`
        SET `position` = ' . (int) $position . '
        WHERE `id_xprtparrallaxblocktbl` = ' . (int) $moved_xprtparrallaxblocktbl['id_xprtparrallaxblocktbl'];
        return (Db::getInstance()->execute($queryx, false)
            && Db::getInstance()->execute($queryy, false));
    }

}
