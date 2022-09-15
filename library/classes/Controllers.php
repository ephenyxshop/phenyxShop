<?php

/**
 * Class ControllersCore
 *
 * @since 1.9.1.0
 */
class ControllersCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'controllers',
        'primary'   => 'id_controllers',
        'fields'    => [
            'name' => ['type' => self::TYPE_STRING],
            'active'         => ['type' => self::TYPE_BOOL],
            
        ],
    ];
    /** @var string Name */
    public $name;
    public $active;

   

    public function add($autoDate = false, $nullValues = false) {

        if($this->isRegistered()) {
			return true;
		}
		
		if (!parent::add($autoDate, true)) {
            return false;
        }

        return true;
    }

    public function update($nullValues = false) {

       

        if (parent::update($nullValues)) {
            return true;
        }

        return false;
    }

    public function delete() {

       
        if (parent::delete()) {
            return true;
        }

        return false;
    }
	
	public function isRegistered() {
		
		$request =  Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_controller')
                ->from('controllers')
                ->where('`name` LIKE \''.$this->name.'\'')
        );
		
		if($request > 0) {
			return true;
		}
		return false;
	}
	
	public static function getIdController($controller_name) {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_controller')
                ->from('controllers')
                ->where('`name` LIKE \''.$controller_name.'\'')
        );
	}

    

}
