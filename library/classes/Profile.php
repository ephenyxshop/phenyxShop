<?php

/**
 * Class ProfileCore
 *
 * @since 1.9.1.0
 */
class ProfileCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    protected static $_cache_accesses = [];

    protected static $_cache_employee_accesses = [];
    /** @var string Name */
    public $name;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'profile',
        'primary'   => 'id_profile',
        'multilang' => true,
        'fields'    => [
            /* Lang fields */
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    /**
     * Get all available profiles
     *
     * @param $idLang
     *
     * @return array Profiles
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfiles($idLang) {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('p.`id_profile`, `name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('`id_lang` = ' . (int) $idLang)
                ->orderBy('`id_profile` ASC')
        );
    }

    /**
     * Get the current profile name
     *
     * @param int      $idProfile
     * @param int|null $idLang
     *
     * @return string Profile
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfile($idProfile, $idLang = null) {

        if (!$idLang) {
            $idLang = Configuration::get('PS_LANG_DEFAULT');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`name`')
                ->from('profile', 'p')
                ->leftJoin('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')
                ->where('p.`id_profile` = ' . (int) $idProfile)
                ->where('pl.`id_lang` = ' . (int) $idLang)
        );
    }

    /**
     * @param int $idProfile
     * @param int $idTab
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfileAccess($idProfile, $idTab) {

        // getProfileAccesses is cached so there is no performance leak
        $accesses = Profile::getProfileAccesses($idProfile);

        return (isset($accesses[$idTab]) ? $accesses[$idTab] : false);
    }

    /**
     * @param int    $idProfile
     * @param string $type
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProfileAccesses($idProfile, $type = 'id_employee_menu') {

        static::$_cache_employee_accesses = [];
		// @codingStandardsIgnoreStart
		if (!in_array($type, ['id_employee_menu', 'class_name'])) {
            return false;
        }
		
        if (!isset(static::$_cache_employee_accesses[$idProfile])) {
            static::$_cache_employee_accesses[$idProfile] = [];
        }
		
        if (!isset(static::$_cache_employee_accesses[$idProfile][$type])) {
            static::$_cache_employee_accesses[$idProfile][$type] = [];
			
            if ($idProfile == _PS_ADMIN_PROFILE_) {
                foreach (EmployeeMenu::getEmployeeMenus(Context::getContext()->language->id) as $tab) {
                    static::$_cache_employee_accesses[$idProfile][$type][$tab['id_employee_menu']] = [
                        'id_profile' => _PS_ADMIN_PROFILE_,
                        'id_employee_menu'  => $tab['id_employee_menu'],
                        'view'       => '1',
                        'add'        => '1',
                        'edit'       => '1',
                        'delete'     => '1',
                    ];
                }

            } else {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('employee_access', 'a')
                        ->leftJoin('employee_menu', 't', 't.`id_employee_menu` = a.`id_employee_menu`')
                        ->where('`id_profile` = ' . (int) $idProfile)
                );

                foreach ($result as $row) {
                    static::$_cache_employee_accesses[$idProfile][$type][$row[$type]] = $row;
                }

            }

        }

        return static::$_cache_employee_accesses[$idProfile][$type];
    }

    
	
	public static function getProfilePartnerAccesses(License $license, $idProfile, $type = 'id_employee_menu') {

      
		
		$accesses = [];
        if ($idProfile == _PS_ADMIN_PROFILE_) {

        	foreach (EmployeeMenu::getEmployeeMenus(Context::getContext()->language->id) as $tab) {
            	$accesses[$idProfile][$type][$tab[$type]] = [
                	'id_profile' => _PS_ADMIN_PROFILE_,
                    'id_employee_menu'  => $tab['id_employee_menu'],
                    'view'       => '1',
                    'add'        => '1',
                    'edit'       => '1',
                    'delete'     => '1',
                ];
            }

       } else {
			
			$query =  'SELECT *
			FROM `eph_employee_access` a
			LEFT JOIN `eph_employee_menu` `t` ON t.`id_employee_menu` = a.`id_employee_menu`
			WHERE a.`id_profile` = '. (int) $idProfile;
			
			$result = $license->pushSqlRequest($query, 'executeS');
           	

            foreach ($result as $row) {
				
           		$accesses[$idProfile][$type][$row[$type]] = $row;
            }

        }
        return $accesses[$idProfile][$type];
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function add($autoDate = true, $nullValues = false) {

        if (parent::add($autoDate, true)) {
            $result = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'access (SELECT ' . (int) $this->id . ', id_employee_menu, 0, 0, 0, 0 FROM ' . _DB_PREFIX_ . 'tab)');
            $result &= Db::getInstance()->execute(
                '
                INSERT INTO ' . _DB_PREFIX_ . 'module_access
                (`id_profile`, `id_module`, `configure`, `view`, `uninstall`)
                (SELECT ' . (int) $this->id . ', id_module, 0, 1, 0 FROM ' . _DB_PREFIX_ . 'module)
            '
            );

            return $result;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     */
    public function delete() {

        if (parent::delete()) {
            return (
                Db::getInstance()->delete('access', '`id_profile` = ' . (int) $this->id)
                && Db::getInstance()->delete('module_access', '`id_profile` = ' . (int) $this->id)
            );
        }

        return false;
    }

}
