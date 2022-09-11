<?php

/**
 * Class AdminNotFoundControllerCore
 *
 * @since 1.9.1.0
 */
class AdminNotFoundControllerCore extends AdminController {

    /**
     * AdminNotFoundControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * Check accesss
     *
     * Always returns true to make it always available
     *
     * @return true
     *
     * @since 1.9.1.0
     */
    public function checkAccess() {

        return true;
    }

    /**
     * Has view access
     *
     * Always returns true to make it always available
     *
     * @param bool $disable
     *
     * @return true
     */
    public function viewAccess($disable = false) {

        return true;
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initContent() {

        $file = fopen("testAdminNotFound.txt", "w");
        $this->errors[] = Tools::displayError('Controller not found');

        $tplVars['controller'] = Tools::getvalue('controllerUri', Tools::getvalue('controller'));
        fwrite($file, $tplVars['controller']);
        $this->context->smarty->assign($tplVars);

        parent::initContent();
    }
}
