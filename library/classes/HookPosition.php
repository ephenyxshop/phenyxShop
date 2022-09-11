<?php

/**
 * Class HookCore
 *
 * @since 1.9.1.0
 */
class HookPositionCore extends ObjectModel {

    public $id_hook;
    /**
     * @var string Hook name identifier
     */
    public $name;
    
    public $target;
    
    public $is_tag =0;
    
    public $position;
    /**
     * @var string Hook title (displayed in BO)
     */
    public $title;
    /**
     * @var string Hook description
     */
    public $description;
    
    public $tag_value;
    /**
     * @var bool
     */
    public $modules;
   
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'hook_position',
        'primary' => 'id_hook_position',
        'multilang' => true,
        'fields'  => [
            'id_hook'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isHookName', 'required' => true, 'size' => 64],    
            'target'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'is_tag'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'position'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            /* Lang fields */
            'title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName'],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'tag_value'   => ['type' => self::TYPE_STRING, 'lang' => true],
        ],
    ];

    
    public function __construct($id = null, $idLang = null, $idShop = null) {

        parent::__construct($id, $idLang, $idShop);
        if($this->id) {
            $this->modules = $this->getModules();
        }
        
    }
    
    public function getModules() {
		$modules = Hook::getHookModuleExecList($this->name);
       
        if(is_array($modules))
        foreach($modules as &$module) {
            $tmpInstance = Module::getInstanceById($module['id_module']);
            if(!$tmpInstance->active) {
                continue;
            }
            $module['displayName'] = $tmpInstance->displayName;
        }
        return $modules;
	}
    
    public static function getHookByName($name) {

        return  Db::getInstance()->getValue(
            (new DbQuery())
	        ->select('`id_hook`')
            ->from('hook_position')
            ->where('name LIKE \''.$name.'\'')
            );
    }
    
    
    
    public static function getHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        foreach($hooks as $hook) {
            $collection[] = new HookPosition($hook->id, $idLang);
        }

        return $collection;
    }
    
    public static function getLiveHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        foreach($hooks as $hook) {
            $collection[] = $hook->name;
        }

        return $collection;
    }
    
    public static function getIndexHooks($idLang = null) {

        
        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        $liveHooks = HookPosition::getLiveHooks();
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('target', 'IN', ['header', 'index', 'footer']);
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
    
    public static function getHeaderHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        $liveHooks = HookPosition::getLiveHooks();
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('target', '=', 'header');
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
    
    public static function getHomeHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }     
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('target', '=', 'index');
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
    
    public static function getFooterHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        $liveHooks = HookPosition::getLiveHooks();
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('target', '=', 'footer');
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
    
    public static function getLeftColumnHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        $liveHooks = HookPosition::getLiveHooks();
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('name', '=', 'displayLeftColumn');
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
    
    public static function getRightColumnHooks($idLang = null) {

        $collection = [];
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }
        $liveHooks = HookPosition::getLiveHooks();
        
        $hooks = new PhenyxShopCollection('HookPosition', $idLang);
        $hooks->where('name', '=', 'displayRightColumn');
        foreach($hooks as $hook) {
            $hook = new HookPosition($hook->id, $idLang);
            if($hook->is_tag) {
                $instances = [];
            } else {
                $modules = Hook::getHookModuleExecList($hook->name);
                $instances = [];
                if(is_array($modules) && count($modules))   {             
                
                    foreach($modules as &$module) {
                    
                        $tmpInstance = Module::getInstanceById($module['id_module']);
                        if(!$tmpInstance->active) {
                            continue;
                        }                    
                        $module['displayName'] = $tmpInstance->displayName;
                        $tmpHooks = $tmpInstance->getPossibleHooksList();
                        if(is_array($tmpHooks)) {
                            foreach($tmpHooks as $tmpHook) {
                                if($tmpHook['name'] == $hook->name) {
                                    $position =  Db::getInstance()->getValue(
                                        (new DbQuery())
	                                   ->select('position')
                                        ->from('hook_module')
                                        ->where('`id_hook` ='.$tmpHook['id_hook'])
                                        ->where('`id_module` ='.$module['id_module'])
                                    );
                                    if($position > 0) {
                                        $instances[$tmpInstance->name]['position'] = $position;
                                        $instances[$tmpInstance->name]['id_module'] = $module['id_module'];
                                        $instances[$tmpInstance->name]['displayName'] = $tmpInstance->displayName;
                                        $instances[$tmpInstance->name]['id_hook'] = $tmpHook['id_hook'];
                                    } 
                                }
                            }
                        }                        
                    }                
                }
            }
            
            $collection[$hook->target_name][$hook->name] = [
                'hook' => Tools::jsonDecode(Tools::jsonEncode($hook), true),
                'instances' => $instances
            ];
        }
        
        return $collection;
    }
}
