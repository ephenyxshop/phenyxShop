<?php

/**
 * Class AdminEmployeeAccessControllerCore
 *
 * @since 1.9.1.0
 */
class AdminEmployeeAccessControllerCore extends AdminController {

    public $php_self = 'adminemployeeaccess';
    // @codingStandardsIgnoreStart
    /* @var array : Black list of id_tab that do not have access */
    public $accesses_black_list = [];
    // @codingStandardsIgnoreEnd

    /**
     * AdminEmployeeAccessControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->show_toolbar = false;
        $this->table = 'employee_access';
        $this->className = 'Profile';
        $this->publicName = $this->l('Accès et restriction Employé');

        // Blacklist AdminLogin
        $this->accesses_black_list[] = EmployeeMenu::getIdFromClassName('AdminLogin');

        parent::__construct();
    }

    public function ajaxProcessOpenTargetController() {

        $domaine = Configuration::get('EPH_SHOP_DOMAIN');

        $currentProfile = (int) $this->getCurrentProfileId();
        $profiles = Profile::getProfiles($this->context->language->id);

        foreach ($profiles as $key => &$value) {

            if ($value['name'] == 'SuperAdmin') {
                unset($profiles[$key]);
            }

        }

        $modules = [];

        foreach ($profiles as $profile) {
            $modules[$profile['id_profile']] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                '
                SELECT ma.`id_module`, m.`name`, ma.`view`, ma.`configure`, ma.`uninstall`
                FROM ' . _DB_PREFIX_ . 'module_access ma
                LEFT JOIN ' . _DB_PREFIX_ . 'module m
                    ON ma.id_module = m.id_module
                WHERE id_profile = ' . (int) $profile['id_profile'] . '
                ORDER BY m.name
            '
            );

            foreach ($modules[$profile['id_profile']] as $k => &$module) {
                $m = Module::getInstanceById($module['id_module']);
                // the following condition handles invalid modules

                if ($m) {
                    $module['name'] = $m->displayName;
                } else {
                    unset($modules[$profile['id_profile']][$k]);
                }

            }

            uasort($modules[$profile['id_profile']], [$this, 'sortModuleByName']);
        }

        $data = $this->createTemplate('employee_access.tpl');
        $currentProfile = (int) $this->getCurrentProfileId();

        $tabs[0] = EmployeeMenu::getEmployeeMenus($this->context->language->id);

        $accesses = [];

        foreach ($profiles as $profile) {
            $accesses[0][$profile['id_profile']] = Profile::getProfileAccesses($profile['id_profile']);
        }

        foreach ($tabs[0] as $key => $tab) {

            if (empty($tab['name'])) {
                unset($tabs[$key]);
            }

            foreach ($this->accesses_black_list as $idTab) {

                if ($tab['id_employee_menu'] == (int) $idTab) {
                    unset($tabs[$key]);
                }

            }

        }

        $data->assign([
            'profiles'        => $profiles,
            'accesses'        => $accesses,
            'tabs'            => $tabs,
            'current_profile' => (int) $currentProfile,
            'admin_profile'   => (int) _EPH_ADMIN_PROFILE_,
            'access_edit'     => $this->tabAccess['edit'],
            'perms'           => ['view', 'add', 'edit', 'delete'],
            'link'            => $this->context->link,
            'controller'      => 'AdminEmployeeAccess',
            'modules'         => $modules,
            'extraJs'         => $extraJs,
            'extracss'        => $extracss,
        ]);

        $li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"  data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,

            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * Get the current profile id
     *
     * @return int the $_GET['profile'] if valid, else 1 (the first profile id)
     *
     * @since 1.9.1.0
     */
    public function getCurrentProfileId() {

        return (isset($_GET['id_profile']) && !empty($_GET['id_profile']) && is_numeric($_GET['id_profile'])) ? (int) $_GET['id_profile'] : 1;
    }

    /**
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessUpdateAccess() {

        if (_EPH_MODE_DEMO_) {
            $result = [
                'success' => false,
                'message' => 'Vous êtes en mode Démo',
            ];
            die(Tools::jsonEncode($result));
        }

        $file = fopen("testProcessUpdateAccess.txt", "w");

        if (Tools::isSubmit('submitAddAccess')) {
            $perm = Tools::getValue('perm');

            if (!in_array($perm, ['view', 'add', 'edit', 'delete', 'all'])) {
                $result = [
                    'success' => false,
                    'message' => 'Les permissions n‘existe pas',
                ];
                die(Tools::jsonEncode($result));
            }

            $enabled = (int) Tools::getValue('enabled');
            $idTab = (int) Tools::getValue('id_employee_menu');
            $idProfile = (int) Tools::getValue('id_profile');
            $where = '`id_employee_menu`';
            $join = '';
            $idLicence = Tools::getValue('license');

            if (Tools::isSubmit('addFromParent')) {
                $where = 't.`id_parent`';
                $join = 'LEFT JOIN `' . _DB_PREFIX_ . 'employee_menu` t ON (t.`id_employee_menu` = a.`id_employee_menu`)';
            }

            if ($idTab == -1) {

                if ($perm == 'all') {
                    $sql = '
                    UPDATE `' . _DB_PREFIX_ . 'employee_access` a
                    SET `view` = ' . (int) $enabled . ', `add` = ' . (int) $enabled . ', `edit` = ' . (int) $enabled . ', `delete` = ' . (int) $enabled . '
                    WHERE `id_profile` = ' . (int) $idProfile;
                } else {
                    $sql = '
                    UPDATE `' . _DB_PREFIX_ . 'employee_access` a
                    SET `' . bqSQL($perm) . '` = ' . (int) $enabled . '
                    WHERE `id_profile` = ' . (int) $idProfile;
                }

            } else {

                if ($perm == 'all') {
                    $sql = '
                    UPDATE `' . _DB_PREFIX_ . 'employee_access` a ' . $join . '
                    SET `view` = ' . (int) $enabled . ', `add` = ' . (int) $enabled . ', `edit` = ' . (int) $enabled . ', `delete` = ' . (int) $enabled . '
                    WHERE ' . $where . ' = ' . (int) $idTab . ' AND `id_profile` = ' . (int) $idProfile;
                } else {
                    $sql = '
                    UPDATE `' . _DB_PREFIX_ . 'employee_access` a ' . $join . '
                    SET `' . bqSQL($perm) . '` = ' . (int) $enabled . '
                    WHERE ' . $where . ' = ' . (int) $idTab . ' AND `id_profile` = ' . (int) $idProfile;
                }

            }

            $idLicence = Tools::getValue('license');

            if ($idLicence > 0) {

                $license = new License($idLicence);
                $license->pushSqlRequest($sql, 'execute');
                $res = 'ok';
            } else {
                fwrite($file, $sql);
                $res = Db::getInstance()->execute($sql);

                if ($res) {
                    $result = [
                        'success' => true,
                        'message' => 'Droits mis à jour avec succès',
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => 'Un truc a merdé',
                    ];
                }

                die(Tools::jsonEncode($result));
            }

            die($res);
        }

    }

    /**
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     */
    public function ajaxProcessUpdateModuleAccess() {

        if (_EPH_MODE_DEMO_) {
            throw new PhenyxShopException(Tools::displayError('This functionality has been disabled.'));
        }

        if ($this->tabAccess['edit'] != '1') {
            throw new PhenyxShopException(Tools::displayError('You do not have permission to edit this.'));
        }

    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     *
     * @since 1.9.1.0
     */
    protected function sortModuleByName($a, $b) {

        return strnatcmp($a['name'], $b['name']);
    }

}
