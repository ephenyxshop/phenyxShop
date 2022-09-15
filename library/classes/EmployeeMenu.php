<?php

/**
 * Class TabCore
 *
 * @since 1.9.1.0
 */
class EmployeeMenuCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    protected static $_getIdFromClassName = null;
    /**
     * Get tabs
     *
     * @return array tabs
     */
    protected static $_cache_employee_menu = [];
	
	protected static $_tabAccesses = [];
    /**
     * Displayed name
     *
     * Multilang property
     *
     * @var array
     */
    public $name;
    /** @var string Class and file name */
   
    public $function;
	
	public $module;

    public $reference;
	
	public $class_name = null;

    /** @var int parent ID */
    public $id_parent;
    /** @var int position */
    public $position;

    public $is_synch = true;
    /** @var bool active */
    public $active = true;
	
	public $visible;
	
	public $parent;
	
	public $accesses;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'employee_menu',
        'primary'   => 'id_employee_menu',
        'multilang' => true,
        'fields'    => [
            'reference'	 	=> ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32],
			'class_name'     => ['type' => self::TYPE_STRING, 'size' => 64],
            'id_parent' 	=> ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'position'  	=> ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'function'  	=> ['type' => self::TYPE_STRING, 'size' => 64],
			'module'         => ['type' => self::TYPE_STRING, 'validate' => 'isTabName', 'size' => 64],
            'is_synch'  	=> ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active'    	=> ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'visible'    	=> ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */
            'name'      	=> ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTabName', 'size' => 64],
        ],
    ];

    public function __construct($id = null, $full = true, $idLang = null) {

        parent::__construct($id, $idLang);
		
		
		if($this->id) {
			$this->parent = $this->getParentReference();
			$this->accesses = $this->getAccesses();
		}

    }
	
	public function getAccesses() {
		
		$context = Context::getContext();
		
		$profiles = Profile::getProfiles($context->language->id);
		$accesses = [];
		
		foreach ($profiles as $profile) {
			if($profile['id_profile'] == 1) {
				continue;
			}
        	$accesses[$profile['id_profile']] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                 (new DbQuery())
               ->select('`view`, `add`, `edit`, `delete`')
                ->from('employee_access')
                ->where('`id_profile` = ' . (int) $profile['id_profile'])
				->where('`id_employee_menu` = ' . (int) $this->id)
                );

        }
		return $accesses;
	}
	
	public function getParentReference() {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`reference`')
                ->from('employee_menu')
                ->where('`id_employee_menu` = ' . $this->id_parent)
        );
	}
	
	public static function getEducationMenu() {
        
		
		$query = 'SELECT m.*, ml.name, m2.reference as parent
            FROM `' . _DB_PREFIX_ . 'employee_menu` m
			LEFT JOIN `' . _DB_PREFIX_ . 'employee_menu` m2 ON (m2.id_employee_menu = m.id_parent)
			LEFT JOIN `' . _DB_PREFIX_ . 'employee_menu_lang` ml ON (ml.id_employee_menu = m.id_employee_menu AND ml.id_lang = 1)
            WHERE m.`is_synch` = 1
            ORDER BY m.`id_employee_menu`';
		
		$menus = Db::getInstance()->executeS($query);
		foreach($menus as &$menu) {
			$menu['name'] = [1 => $menu['name']];
		}
		
		
		return $menus;        
    }

       /**
     * Get tab id
     *
     * @return int tab id
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getCurrentTabId() {

        $idTab = EmployeeMenu::getIdFromClassName(Tools::getValue('controller'));
        // retro-compatibility 1.4/1.5

        if (empty($idTab)) {
            $idTab = EmployeeMenu::getIdFromClassName(Tools::getValue('EmployeeMenu'));
        }

        return $idTab;
    }
	
	 public static function getCurrentParentId() {
		 
        $cacheId = 'getCurrentParentId_'.mb_strtolower(Tools::getValue('controller'));
        if (!Cache::isStored($cacheId)) {
            $value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_parent`')
                    ->from('employee_menu')
                    ->where('LOWER(`class_name`) = \''.pSQL(mb_strtolower(Tools::getValue('controller'))).'\'')
            );
            if (!$value) {
                $value = -1;
            }
            Cache::store($cacheId, $value);

            return $value;
        }

        return Cache::retrieve($cacheId);
    }
	
	public static function getIdFromClassName($className) {

        if(!is_null($className)) {
			$className = strtolower($className);
		}		
		
        if (static::$_getIdFromClassName === null) {
            static::$_getIdFromClassName = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_employee_menu`, `class_name`')
                    ->from('employee_menu'),
                true,
                false
            );

            if (is_array($result)) {

                foreach ($result as $row) {
                    static::$_getIdFromClassName[strtolower((string)$row['class_name'])] = $row['id_employee_menu'];
                }

            }

        }

        return (isset(static::$_getIdFromClassName[$className]) ? (int) static::$_getIdFromClassName[$className] : false);
    }

	
	public static function getPartnerEmployeeMenus(License $license, $idLang, $idParent = null) {
		
		
		$employee_menus = [];
		
		$query =  'SELECT t.*, tl.`name`
			FROM `eph_employee_menu` t
			LEFT JOIN `eph_employee_menu_lang` `tl` ON t.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang` = '.$idLang.'
			ORDER BY t.`position` ASC';
		
		$result = $license->pushSqlRequest($query, 'executeS');
		
		
		
		if (is_array($result)) {

         	foreach ($result as $row) {
				$employee_menus[$idLang][$row['id_parent']][] = $row;
             }

          }
		
		if ($idParent === null) {
            $arrayAll = [];
            // @codingStandardsIgnoreStart

            foreach ($employee_menus[$idLang] as $arrayParent) {
                $arrayAll = array_merge($arrayAll, $arrayParent);
            }

           

            return $arrayAll;
        }

        // @codingStandardsIgnoreStart
        return (isset($employee_menus[$idLang][$idParent]) ? $employee_menus[$idLang][$idParent] : []);
		
	}
	
	public static function getChlidren($idParent) {
	
		$employee_menu = [];
		$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
 		(new DbQuery())
     		->select('t.id_employee_menu, t.id_parent, tl.`name`')
			->from('employee_menu', 't')
     		->leftJoin('employee_menu_lang', 'tl', 't.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang` = 1')
			->where('id_parent = '.$idParent )
     		->orderBy('t.`position` ASC')
 		);
	
	
		foreach($result as &$row) {
			$row['children'] = self::getChlidren($row['id_employee_menu']);
			$employee_menu[] = $row;
			$level++;
		
		}
	
	
		return $employee_menu;
	}
	
	public static function buildSelect ($employee_menu, $idParent) {
		$select = '';
		foreach($employee_menu as $key => $value) {
	
			//$select .= '<option value="'.$value['id_employee_menu'].'" ';
			if($value['id_employee_menu'] == $idParent) {
				//$select .= 'selected="selected"';
			}
			foreach($value['children'] as $child) {
				$select .= '<option value="'.$child['id_employee_menu'].'" ';
				if($child['id_employee_menu'] == $idParent) {
					$select .= 'selected="selected"';
				}
				$select .= '>'.$value['name'].' > '.$child['name'].'</option>';
       
			}
	
		}
		return $select;
	}

    public static function getEmployeeMenuSelects($idLang, $idParent = null) {

        // @codingStandardsIgnoreStart
		
		$select = '';
		

       	$select .= '<select name="id_parent" id="id_parent">';
		$select .= '<option value="1">Accueil</option>';
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
 			(new DbQuery())
     		->select('t.id_employee_menu, t.id_parent, tl.`name`')
     		->from('employee_menu', 't')
     		->leftJoin('employee_menu_lang', 'tl', 't.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang`  = ' . (int) $idLang)
	 		->where('id_parent = 1' )
     		->orderBy('t.`position` ASC')
 		);

        if (is_array($result)) {
			foreach($result as &$row) {
				$row['children'] = EmployeeMenu::getChlidren($row['id_employee_menu']);
				$employee_menu[$row['id_employee_menu']] = $row;
			}
        
			
			foreach($employee_menu as $key => $value) {
				$select .= '<option value="'.$value['id_employee_menu'].'" ';

				if($value['id_employee_menu'] == $idParent) {
					$select .= 'selected="selected"';
				}
				$select .= '>'.$value['name'].'</option>';
				foreach($value['children'] as $child) {
					$select .= '<option value="'.$child['id_employee_menu'].'" ';
					if($child['id_employee_menu'] == $idParent) {
						$select .= 'selected="selected"';
					}
					$select .= '>'.$value['name'].' > '.$child['name'].'</option>';
					if(is_array($child['children']) && count($child['children']))
						$select .= EmployeeMenu::buildSelect($child['children'], $idParent);       
				}
			 }
		}
		$select .= '</select>';
		return $select;
    }
	
	public static function getEmployeeMenus($idLang, $idParent = null) {

        // @codingStandardsIgnoreStart
		

        if (!isset(static::$_cache_employee_menu[$idLang])) {
            static::$_cache_employee_menu[$idLang] = [];
           
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                ->select('t.*, tl.`name`')
                ->from('employee_menu', 't')
                ->leftJoin('employee_menu_lang', 'tl', 't.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang` = ' . (int) $idLang)
				->orderBy('t.`position` ASC')
            );

            if (is_array($result)) {

                foreach ($result as $row) {
                    

                    if (!isset(static::$_cache_employee_menu[$idLang][$row['id_parent']])) {
                        static::$_cache_employee_menu[$idLang][$row['id_parent']] = [];
                    }

                    static::$_cache_employee_menu[$idLang][$row['id_parent']][] = $row;
					
					
                }

            }

        }

        if ($idParent === null) {
            $arrayAll = [];
            // @codingStandardsIgnoreStart

            foreach (static::$_cache_employee_menu[$idLang] as $arrayParent) {
                $arrayAll = array_merge($arrayAll, $arrayParent);
            }

            

            return $arrayAll;
        }

        // @codingStandardsIgnoreStart
        return (isset(static::$_cache_employee_menu[$idLang][$idParent]) ? static::$_cache_employee_menu[$idLang][$idParent] : []);
        // @codingStandardsIgnoreEnd
    }



    /**
     * Enabling tabs for module
     *
     * @param string $module Module Name
     *
     * @return bool Status
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function enablingForModule($module) {

        $tabs = EmployeeMenu::getCollectionFromModule($module);

        if (!empty($tabs)) {

            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 1;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Get collection from module name
     *
     * @param string   $module Module name
     * @param int|null $idLang Language ID
     *
     * @return array|PhenyxShopCollection Collection of tabs (or empty array)
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getCollectionFromModule($module, $idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        if (!Validate::isModuleName($module)) {
            return [];
        }

        $tabs = new PhenyxShopCollection('EmployeeMenu', (int) $idLang);
        $tabs->where('module', '=', $module);

        return $tabs;
    }

    /**
     * Disabling tabs for module
     *
     * @param string $module Module name
     *
     * @return bool Status
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function disablingForModule($module) {

        $tabs = EmployeeMenu::getCollectionFromModule($module);

        if (!empty($tabs)) {

            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 0;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Get Instance from tab class name
     *
     * @param string   $className Name of tab class
     * @param int|null $idLang    id_lang
     *
     * @return Tab Tab object (empty if bad id or class name)
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getInstanceFromClassName($className, $idLang = null) {

        $idTab = (int) EmployeeMenu::getIdFromClassName($className);

        return new EmployeeMenu($idTab, $idLang);
    }

    /**
     * @param int $idTab
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function checkTabRights($idTab) {

        
		if (Context::getContext()->employee->id_profile == _EPH_ADMIN_PROFILE_) {
            return true;
        }
		static::$_tabAccesses = [];
		$idProfil = Context::getContext()->employee->id_profile;
		
		if(!isset(static::$_tabAccesses[$idProfil][$idTab])) {
			if ($tabAccesses === null) {
            	$tabAccesses = Profile::getProfileAccesses($idProfil);
       		}
			if (isset($tabAccesses[(int) $idTab]['view'])) {
            	static::$_tabAccesses[$idProfil][$idTab] =  $tabAccesses[(int) $idTab]['view'];
        	}
			return static::$_tabAccesses[$idProfil][$idTab];
		}
		

        return static::$_tabAccesses[$idProfil][$idTab];
    }

    /**
     * @param int   $idTab
     * @param array $tabs
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function recursiveTab($idTab, $tabs) {

        $adminTab = EmployeeMenu::getTab((int) Context::getContext()->language->id, $idTab);
        $tabs[] = $adminTab;

        if ($adminTab['id_parent'] > 0) {
            $tabs = EmployeeMenu::recursiveTab($adminTab['id_parent'], $tabs);
        }

        return $tabs;
    }

    /**
     * Get tab
     *
     * @param int $idLang
     * @param int $idTab
     *
     * @return array tab
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getTab($idLang, $idTab) {

        $cacheId = 'EmployeeMenu::getTab_' . (int) $idLang . '-' . (int) $idTab;

        if (!Cache::isStored($cacheId)) {
            /* Tabs selection */
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('employee_menu', 't')
                    ->leftJoin('employee_menu_lang', 'tl', 't.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang` = ' . (int) $idLang)
                    ->where('t.`id_employee_menu` = ' . (int) $idTab)
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param int $idParent
     * @param int $idProfile
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getTabByIdProfile($idParent, $idProfile) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_employee_menu`, t.`id_parent`, tl.`name`, a.`id_profile`')
                ->from('employee_menu', 't')
                ->leftJoin('employee_access', 'a', 'a.`id_employee_menu` = t.`id_employee_menu`')
                ->leftJoin('topba_langr', 'tl', 't.`id_employee_menu` = tl.`id_employee_menu` AND tl.`id_lang` = ' . (int) Context::getContext()->language->id)
                ->where('a.`id_profile` = ' . (int) $idProfile)
                ->where('t.`id_parent` = ' . (int) $idParent)
                ->where('a.`view` = 1')
                ->where('a.`edit` = 1')
                ->where('a.`delete` = 1')
                ->where('a.`add` = 1')
                ->where('t.`id_parent` != 0')
                ->where('t.`id_parent` != -1')
                ->orderBy('t.`id_parent` ASC')
        );
    }

   
    public function add($autoDate = true, $nullValues = false, $init = true) {

        // @retrocompatibility with old menu (before 1.5.0.9)

        // @codingStandardsIgnoreStart
        static::$_cache_employee_menu = [];
        // @codingStandardsIgnoreEnd
		if(!$this->visible) {
			$this->id_parent = 0;
		}

        // Set good position for new tab
        $this->position = EmployeeMenu::getNewLastPosition($this->id_parent);

        if (empty($this->reference)) {
            $this->reference = $this->generateReference();
        }
		
		if (parent::add($autoDate, $nullValues)) {
            //forces cache to be reloaded
            static::$_getIdFromClassName = null;
			if($init) {
            	return EmployeeMenu::initAccess($this);
			}
			return true;
        }

        return false;
    }
	
	public function delete() {

		Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'employee_access` WHERE `id_employee_menu` = ' . (int) $this->id);
		
		return parent::delete();
	}

    public function generateReference() {

        return strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
    }

    /**
     * return an available position in subtab for parent $id_parent
     *
     * @param mixed $idParent
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNewLastPosition($idParent) {

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(MAX(`position`), 0) + 1')
                ->from('employee_menu')
                ->where('`id_parent` = ' . (int) $idParent)
        ));
    }

    /** When creating a new tab $id_employee_menu, this add default rights to the table access
     *
     * @todo    this should not be public static but protected
     *
     * @param int     $idTab
     * @param Context $context
     *
     * @return bool true if succeed
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function initAccess(EmployeeMenu $Tab, Context $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        if (!$context->employee || !$context->employee->id_profile) {
            $rights = 0;
        } else {
			$rights = $profile['id_profile'] == $context->employee->id_profile ? 1 : 0;
		}
		if($Tab->id_parent == 0) {
			$rights = 1;
		}

        /* Profile selection */
        $profiles = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_profile`')
                ->from('profile')
                ->where('`id_profile` != 1')
        );

        /* Query definition */
        $replace = [];
        $replace[] = [
            'id_profile' => 1,
            'id_employee_menu'  => (int) $Tab->id,
            'view'       => 1,
            'add'        => 1,
            'edit'       => 1,
            'delete'     => 1,
        ];
		
		$accesses = $Tab->accesses;
        if(is_array($accesses) && count($accesses))
        foreach ($profiles as $profile) {
            if(array_key_exists($profile['id_profile'], $accesses)) {
				$replace[] = [
               		'id_profile' => (int) $profile['id_profile'],
                	'id_employee_menu'  => (int) $Tab->id,
                	'view'       => (int) $accesses[$profile['id_profile']]['view'],
                	'add'        => (int) $accesses[$profile['id_profile']]['add'],
                	'edit'       => (int) $accesses[$profile['id_profile']]['edit'],
                	'delete'     => (int) $accesses[$profile['id_profile']]['delete'],
            	];
			} else {
				$replace[] = [
                	'id_profile' => (int) $profile['id_profile'],
                	'id_employee_menu'  => (int) $Tab->id,
                	'view'       => (int) $rights,
					'add'        => (int) $rights,
                	'edit'       => (int) $rights,
                	'delete'     => (int) $rights,
            	];
			}
            
        }

        return Db::getInstance()->insert('employee_access', $replace, false, true, Db::REPLACE);
    }

    /**
     * @param bool $nullValues
     * @param bool $autodate
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function save($nullValues = false, $autodate = true) {

        static::$_getIdFromClassName = null;

        return parent::save();
    }

    /**
     * @param int $idParent
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function cleanPositions($idParent) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_employee_menu`')
                ->from('employee_menu')
                ->where('`id_parent` = ' . (int) $idParent)
                ->orderBy('position')
        );
        $sizeof = count($result);

        for ($i = 0; $i < $sizeof; ++$i) {
            Db::getInstance()->update(
                'employee_menu',
                [
                    'position' => $i,
                ],
                '`id_employee_menu` = ' . (int) $result[$i]['id_employee_menu']
            );
        }

        return true;
    }

    /**
     * @param string $direction
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function move($direction) {

        $nbTabs = EmployeeMenu::getNbTabs($this->id_parent);

        if ($direction != 'l' && $direction != 'r') {
            return false;
        }

        if ($nbTabs <= 1) {
            return false;
        }

        if ($direction == 'l' && $this->position <= 1) {
            return false;
        }

        if ($direction == 'r' && $this->position >= $nbTabs) {
            return false;
        }

        $newPosition = ($direction == 'l') ? $this->position - 1 : $this->position + 1;
        Db::getInstance()->execute(
            '
            UPDATE `' . _DB_PREFIX_ . 'tab` t
            SET position = ' . (int) $this->position . '
            WHERE id_parent = ' . (int) $this->id_parent . '
                AND position = ' . (int) $newPosition
        );
        $this->position = $newPosition;

        return $this->update();
    }

    /**
     * @param null $idParent
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNbTabs($idParent = null) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('employee_menu', 't')
                ->where(!is_null($idParent) ? 't.`id_parent` = ' . (int) $idParent : '')
        );
    }

    /**
     * Overrides update to set position to last when changing parent tab
     *
     * @see     PhenyxObjectModel::update
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function update($nullValues = true, $init = true) {

        $currentTab = new EmployeeMenu($this->id, $init);

        if ($currentTab->id_parent != $this->id_parent) {
            $this->position = EmployeeMenu::getNewLastPosition($this->id_parent);
        }

        // @codingStandardsIgnoreStart
        static::$_cache_employee_menu = [];
        // @codingStandardsIgnoreEnd

        if (parent::update($nullValues)) {
			if($init) {
				return EmployeeMenu::initAccess($this);
			}			
			return true;
            
        }

    }

    /**
     * @param string $way
     * @param int    $position
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('t.`id_employee_menu`, t.`position`, t.`id_parent`')
            ->from('employee_menu', 't')
            ->where('t.`id_parent` = ' . (int) $this->id_parent)
            ->orderBy('t.`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $tab) {

            if ((int) $tab['id_employee_menu'] == (int) $this->id) {
                $movedTab = $tab;
            }

        }

        if (!isset($movedTab) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = (Db::getInstance()->update(
            'employee_menu',
            [

                'position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')],
            ],
            '`position` ' . ($way ? '> ' . (int) $movedTab['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $movedTab['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_parent`=' . (int) $movedTab['id_parent']
        )
            && Db::getInstance()->update(
                'employee_menu',
                [
                    'position' => (int) $position,
                ],
                '`id_parent` = ' . (int) $movedTab['id_parent'] . ' AND `id_employee_menu`=' . (int) $movedTab['id_employee_menu']
            ));

        return $result;
    }

    public static function getmetroTabColors() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('tabmetro_color')
        );
    }

    public static function getReferentTopBars() {

        $return = [];
        $referentTopBars = Db::getInstance()->executeS(
            'SELECT `id_employee_menu`, `reference`
            FROM `' . _DB_PREFIX_ . 'employee_menu`
            WHERE `is_synch` = 1
            ORDER BY `id_employee_menu`'
        );

        return $referentTopBars;
    }

    public static function getIdEmployeeMenuTypeByRef($reference) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_employee_menu`')
                ->from('employee_menu')
                ->where('`reference` = \'' . $reference . '\'')
        );
    }

    public static function getIdParentEmployeeMenuTypeByRef($reference) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_parent`')
                ->from('employee_menu')
                ->where('`reference` LIKE \'' . $reference . '\'')
        );
    }
	
	public static function getModuleTabList() {

        $list = [];

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`class_name`, t.`module`')
                ->from('employee_menu', 't')
                ->where('t.`module` IS NOT NULL')
                ->where('t.`module` != ""')
        );

        if (is_array($result)) {

            foreach ($result as $detail) {
                $list[strtolower($detail['class_name'])] = $detail;
            }

        }

        return $list;
    }
	
	public static function getTabModulesList($idTab) {

        $modulesList = ['default_list' => [], 'slider_list' => []];
        $xmlTabModulesList = false;

        if (file_exists(_EPH_ROOT_DIR_ . Module::CACHE_FILE_TAB_MODULES_LIST)) {
            $xmlTabModulesList = @simplexml_load_file(_EPH_ROOT_DIR_ . Module::CACHE_FILE_TAB_MODULES_LIST);
        }

        $className = null;
        $displayType = 'default_list';

        if ($xmlTabModulesList) {

            foreach ($xmlTabModulesList->tab as $tab) {

                foreach ($tab->attributes() as $key => $value) {

                    if ($key == 'class_name') {
                        $className = (string) $value;
                    }

                }

                if (EmployeeMenu::getIdFromClassName((string) $className) == $idTab) {

                    foreach ($tab->attributes() as $key => $value) {

                        if ($key == 'display_type') {
                            $displayType = (string) $value;
                        }

                    }

                    foreach ($tab->children() as $module) {
                        $modulesList[$displayType][(int) $module['position']] = (string) $module['name'];
                    }

                    ksort($modulesList[$displayType]);
                }

            }

        }

        return $modulesList;
    }
	
	public static function getClassNameById($idTab) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`class_name`')
                ->from('employee_menu')
                ->where('`id_employee_menu` = ' . (int) $idTab)
        );
    }
	
	public static function getEmployeeMenu() {
        
		$query = 'SELECT reference
            FROM `' . _DB_PREFIX_ . 'employee_menu`
            ORDER BY `id_employee_menu`';
		
		$menus = Db::getInstance()->executeS($query);
		
		return $menus;        
    }


}
