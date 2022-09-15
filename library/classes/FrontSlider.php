<?php

/**
 * Class FrontSliderCore
 *
 * @since 1.9.1.0
 */
class FrontSliderCore extends PhenyxObjectModel {

    public $author;
    public $name;
    public $slug;
    public $data;
    public $date_c;
    public $date_m;
    public $schedule_start = 0;
    public $schedule_end = 0;
    public $flag_hidden = 0;
    public $flag_deleted = 0;
    public static $skins = [];
	
	public $GLOBALS = [];

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'layer_slider',
        'primary' => 'id_layer_slider',
        'fields'  => [
            'author'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'name'           => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 100],
            'slug'           => ['type' => self::TYPE_STRING],
            'data'           => ['type' => self::TYPE_JSON, 'required' => true],
            'date_c'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'date_m'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'schedule_start' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'schedule_end'   => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'flag_hidden'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'flag_deleted'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->data = Tools::jsonDecode($this->data, true);
        }
		$this->GLOBALS['ls_filter'] = [];

    }

    public static function find() {

        
		$base_link = Context::getContext()->link->getBaseFrontLink();
		
		$sliders = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*')
                ->from('layer_slider')
                ->orderBy('`date_c` DESC')
        );

        foreach ($sliders as $key => $val) {

            $sliders[$key]['data'] = Tools::jsonDecode($val['data'], true);

            if (empty($sliders[$key]['data']['properties']['preview'])) {

                if (empty($sliders[$key]['data']['layers'][0]['properties']['background'])) {
                    $sliders[$key]['preview'] = _MODULE_DIR_ . 'layerslider/views/img/admin/blank.gif';
                } else {
					
                   
					if(str_contains($sliders[$key]['data']['layers'][0]['properties']['background'],$base_link)) {
						
						$sliders[$key]['preview'] = $sliders[$key]['data']['layers'][0]['properties']['background'];
					} else {
						
						$sliders[$key]['preview'] = $base_link.$sliders[$key]['data']['layers'][0]['properties']['background'];
					}
                }

            } else {
				
				if(str_contains($sliders[$key]['data']['properties']['preview'],$base_link)) {
					$sliders[$key]['preview'] = $sliders[$key]['data']['properties']['preview'];
				} else {
					$sliders[$key]['preview'] = $base_link.$sliders[$key]['data']['properties']['preview'];
				}
                
            }

        }

        return $sliders;
    }

    public static function addSkins($path) {

        $skinsPath = $skins = [];
        $path = rtrim($path, '/\\');

        // It's a direct skin folder

        if (file_exists($path . '/skin.css')) {
            $skinsPath = [$path];
        } else {
            // Get all children if it's a parent directory
            $skinsPath = glob($path . '/*', GLOB_ONLYDIR);
        }

        // Iterate over the skins

        foreach ($skinsPath as $key => $path) {
            // Exclude non-valid skins

            if (!file_exists($path . '/skin.css')) {
                continue;
            }

            // Gather skin data
            $handle = Tools::strtolower(basename($path));
            $skins[$handle] = [
                'name'   => $handle,
                'handle' => $handle,
                'dir'    => $path,
                'file'   => $path . DIRECTORY_SEPARATOR . 'skin.css',
            ];

            // Get skin info (if any)

            if (file_exists($path . '/info.json')) {
                $skins[$handle]['info'] = Tools::jsonDecode(Tools::file_get_contents($path . '/info.json'), true);
                $skins[$handle]['name'] = $skins[$handle]['info']['name'];

                if (!empty($skins[$handle]['info']['requires'])) {
                    $skins[$handle]['requires'] = $skins[$handle]['info']['requires'];
                }

            }

        }

        self::$skins = array_merge(self::$skins, $skins);
        ksort(self::$skins);

        return self::$skins;
    }

    public static function ls_get_image($id = null, $url = null) {

		$base_link = Context::getContext()->link->getBaseFrontLink();
        if (!empty($id)) {

        } else
        if (!empty($url)) {
            return $base_link.$url;
        }

        return $base_link . 'plugins/layerslider/views/img/admin/blank.gif';
    }

    public static function ls_get_thumbnail($id = null, $url = null, $blankPlaceholder = false) {

        // Image ID
		$base_link = Context::getContext()->link->getBaseFrontLink();
        if (!empty($id)) {

            if ($image = FrontSlider::ls_get_attachment_thumb_url($id, 'thumbnail')) {
                return $image;
            }

        }

        if (!empty($url)) {
            $thumb = substr_replace($url, '-150x150.', strrpos($url, '.'), 1);
            $file = _MODULE_DIR_ . 'layerslider/base/sampleslider/' . basename($thumb);

            if (file_exists($file)) {
                return $base_link.$thumb;
            } else {
                return $base_link.$url;
            }

        }

        return $base_link . 'plugins/layerslider/views/img/admin/blank.gif';
    }

    public static function ls_get_attachment_thumb_url($attachment_id) {

        return false;
    }

    public static function countRevision($sliderId) {

        $sliderId = (int) $sliderId;

        if (empty($sliderId) || !is_numeric($sliderId)) {
            return false;
        }

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('layerslider_revisions')
                ->where('`id_layer_slider` = ' . $sliderId)
        );

    }
	
	public static function ls_apply_filters($tag, $value, $var = null) {
    	
		if (isset($this->GLOBALS['ls_filter'][$tag])) {
        	foreach ($this->GLOBALS['ls_filter'][$tag] as $func) {
            	if ($var === null) {
                	$value = is_string($func) ? call_user_func($func, $value) : $func[0]->{$func[1]}($value);
            	} else {
                	$value = is_string($func) ? call_user_func($func, $value, $var) : $func[0]->{$func[1]}($value, $var);
            	}
        	}
    	}
    	return $value;
	}

    public static function layerslider_convert_urls($arr) {

        // Global BG

        if (!empty($arr['properties']['backgroundimage']) && Tools::strpos($arr['properties']['backgroundimage'], 'http://') !== false) {
            $arr['properties']['backgroundimage'] = parse_url($arr['properties']['backgroundimage'], PHP_URL_PATH);
        }

        // YourLogo img

        if (!empty($arr['properties']['yourlogo']) && Tools::strpos($arr['properties']['yourlogo'], 'http://') !== false) {
            $arr['properties']['yourlogo'] = parse_url($arr['properties']['yourlogo'], PHP_URL_PATH);
        }

        if (!empty($arr['layers'])) {

            foreach ($arr['layers'] as $key => $slide) {
                // Layer BG

                if (Tools::strpos($slide['properties']['background'], 'http://') !== false) {
                    $arr['layers'][$key]['properties']['background'] = parse_url($slide['properties']['background'], PHP_URL_PATH);
                }

                // Layer Thumb

                if (Tools::strpos($slide['properties']['thumbnail'], 'http://') !== false) {
                    $arr['layers'][$key]['properties']['thumbnail'] = parse_url($slide['properties']['thumbnail'], PHP_URL_PATH);
                }

                // Image sublayers

                if (!empty($slide['sublayers'])) {

                    foreach ($slide['sublayers'] as $subkey => $layer) {

                        if ($layer['media'] == 'img' && Tools::strpos($layer['image'], 'http://') !== false) {
                            $arr['layers'][$key]['sublayers'][$subkey]['image'] = parse_url($layer['image'], PHP_URL_PATH);
                        }

                    }

                }

            }

        }

        return $arr;
    }

}
