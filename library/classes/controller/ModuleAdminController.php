<?php

/**
 * Class ModuleAdminControllerCore
 *
 * @since 1.9.1.0
 */
abstract class ModuleAdminControllerCore extends AdminController {

    /** @var Module */
    public $module;

    /**
     * @throws PhenyxShopException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        parent::__construct();

        $this->controller_type = 'moduleadmin';

        $tab = new EmployeeMenu($this->id);

        if (!$tab->module) {
            throw new PhenyxShopException('Admin tab ' . get_class($this) . ' is not a module tab');
        }

        $this->module = Module::getInstanceByName($tab->module);

        if (!$this->module->id) {
            throw new PhenyxShopException("Module {$tab->module} not found");
        }

    }

    /**
     * Creates a template object
     *
     * @param string $tplName Template filename
     *
     * @return Smarty_Internal_Template
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function createTemplate($tplName) {

        if (file_exists(_EPH_THEME_DIR_ . 'plugins/' . $this->module->name . '/views/templates/admin/' . $tplName) && $this->viewAccess()) {
            return $this->context->smarty->createTemplate(_EPH_THEME_DIR_ . 'plugins/' . $this->module->name . '/views/templates/admin/' . $tplName, $this->context->smarty);
        } else if (file_exists($this->getTemplatePath() . $this->override_folder . $tplName) && $this->viewAccess()) {
            return $this->context->smarty->createTemplate($this->getTemplatePath() . $this->override_folder . $tplName, $this->context->smarty);
        }

        return parent::createTemplate($tplName);
    }

    /**
     * Get path to back office templates for the module
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTemplatePath() {

        return _EPH_MODULE_DIR_ . $this->module->name . '/views/templates/admin/';
    }

}
