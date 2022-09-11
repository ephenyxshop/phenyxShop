<?php


class LsRevisionCore extends ObjectModel {

   
	public $id_layer_slider;
	public $author;
    public $data;
    public $date_c;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'layerslider_revisions',
        'primary' => 'id_layerslider_revision',
        'fields'  => [
			'id_layer_slider' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'author'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            'data'           => ['type' => self::TYPE_STRING, 'required' => true],
            'date_c'         => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false],
            
        ],
    ];


    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->data = Tools::jsonDecode($this->data, true);
        }

    }


    
}
