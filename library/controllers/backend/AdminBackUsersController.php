<?php

/**
 * Class AdminBackUsersControllerCore
 *
 * @since 1.9.1.0
 */
class AdminBackUsersControllerCore extends AdminController {

    public $php_self = 'adminbackusers';
    // @codingStandardsIgnoreStart
    /** @var array profiles list */
    protected $profiles_array = [];

    /** @var array themes list */
    protected $themes = [];

    /** @var array tabs list */
    protected $tabs_list = [];

    /** @var bool $restrict_edition */
    protected $restrict_edition = false;
    // @codingStandardsIgnoreEnd

    /**
     * AdminBackUsersControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'employee';
        $this->className = 'Employee';
        $this->identifier = 'id_employee';
        $this->publicName = $this->l('Gestion des équipes back office');
        $this->lang = false;
        $this->context = Context::getContext();

        $path = _SHOP_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

        foreach (scandir($path) as $theme) {

            if ($theme[0] != '.' && is_dir($path . $theme) && (@filemtime($path . $theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-theme.css'))) {
                $this->themes[] = [
                    'id'   => $theme,
                    'name' => ucfirst($theme),
                ];
            }

        }

        parent::__construct();

        // An employee can edit its own profile
        EmployeeConfiguration::updateValue('EXPERT_EMPLOYEES_FIELDS', Tools::jsonEncode($this->getEmployeeFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EMPLOYEES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_EMPLOYEES_FIELDS', Tools::jsonEncode($this->getEmployeeFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EMPLOYEES_FIELDS'), true);
        }

        EmployeeConfiguration::updateValue('EXPERT_EMPLOYEES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_EMPLOYEES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_EMPLOYEES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_EMPLOYEES_SCRIPT');
        }

    }

    public function generateParaGridScript() {

        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $this->paramComplete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'Ajouter un nouvel employée\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
                ],

            ],
        ];

        $this->paramTitle = '\'' . $this->l('Gestion du personnels') . '\'';

        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

        $this->paramContextMenu = [
            '#grid_' . $this->controller_name => [
                'selector'  => '\'.pq-body-outer .pq-grid-row\'',
                'animation' => [
                    'duration' => 250,
                    'show'     => '\'fadeIn\'',
                    'hide'     => '\'fadeOut\'',
                ],
                'build'     => 'function($triggerElement, e){
                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->className . '.getRowData( {rowIndx: rowIndex} );
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Modifier l‘employee ') . ' \'+rowData.firstname,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(currentProfileId > 2) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_employee)
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer l‘employee ') . ' \ : \'+rowData.firstname,
                            icon: "trash",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(currentProfileId > 2) {
                                    return false;
                                }
                                if(rowData.id_profile ==1) {
                                 return false;
                                }
                                return true;
                            },

                            callback: function(itemKey, opt, e) {
                                deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un employee", "Etes vous sure de vouloir supprimer  "+rowData.lastname+ " ?", "Oui", "Annuler",rowData.id_employee);
                            }
                        },

                    },
                };
            }',
            ]];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getEmployeeRequest() {

        $employees = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('e.id_employee, e.id_profile, e.firstname, e.lastname, e.email,  e.active, case when e.active = 1 then \'<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>\' else \'<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>\' end as emplyee_state, pl.`name`')
                ->from('employee', 'e')
                ->leftJoin('profile_lang', 'pl', 'pl.`id_profile` = e.`id_profile` AND pl.`id_lang`  = ' . (int) $this->context->language->id)
                ->orderBy('e.`id_employee` ASC')
        );

        return $employees;

    }

    public function ajaxProcessgetEmployeeRequest() {

        die(Tools::jsonEncode($this->getEmployeeRequest()));

    }

    public function getEmployeeFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'maxWidth' => 50,
                'dataIndx' => 'id_employee',
                'dataType' => 'integer',
                'editable' => false,
            ],
            [

                'title'    => '',
                'dataIndx' => 'id_profile',
                'dataType' => 'integer',
                'hidden'   => true,
                'filter'   => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],
            [
                'title'    => $this->l('Type'),
                'minWidth' => 100,
                'dataIndx' => 'employee_type',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',

            ],

            [
                'title'    => $this->l('Prénom'),
                'minWidth' => 100,
                'dataIndx' => 'firstname',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Nom'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'lastname',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Adresse email'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'email',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Profile'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'name',
                'dataType' => 'string',
                'align'    => 'left',
                'valign'   => 'center',
            ],
            [
                'title'    => $this->l('Activé'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'emplyee_state',
                'dataType' => 'html',
                'align'    => 'center',
                'valign'   => 'center',
            ],
            [

                'title'    => '',
                'dataIndx' => 'active',
                'dataType' => 'integer',
                'hidden'   => true,
                'filter'   => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],

        ];

    }

    public function ajaxProcessgetEmployeeFields() {

        die(EmployeeConfiguration::get('EXPERT_EMPLOYEES_FIELDS'));
    }

    public function ajaxProcessAddObject() {

        $availableProfiles = Profile::getProfiles($this->context->language->id);
        $data = $this->createTemplate('controllers/back_users/newwemployee.tpl');

        $extracss = $this->pushCSS([
            _EPH_JS_DIR_ . 'trumbowyg/ui/trumbowyg.min.css',
            _EPH_JS_DIR_ . 'jquery-ui/general.min.css',

        ]);
        $pusjJs = $this->pushJS([
            _EPH_JS_DIR_ . 'employee.js',
            _EPH_JS_DIR_ . 'trumbowyg/trumbowyg.min.js',
            _EPH_JS_DIR_ . 'jquery-jeditable/jquery.jeditable.min.js',
            _EPH_JS_DIR_ . 'jquery-ui/jquery-ui-timepicker-addon.min.js',
            _EPH_JS_DIR_ . 'moment/moment.min.js',
            _EPH_JS_DIR_ . 'moment/moment-timezone-with-data.min.js',
            _EPH_JS_DIR_ . 'calendar/working_plan_exceptions_modal.min.js',
            _EPH_JS_DIR_ . 'datejs/date.min.js',

        ]);

        $employee = new Employee();
        $imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/Unknown.png';

        $company = new Company(Configuration::get('EPH_COMPANY_ID'));

        $data->assign([
            'employee'                => $employee,
            'imageUrl'                => $imageUrl,
            'availableProfiles'       => $availableProfiles,
            'themes'                  => $this->themes,
            'currentProfileId'        => $this->context->employee->id_profile,
            'EALang'                  => Tools::jsonEncode($this->getEaLang()),
            'pusjJs'                  => $pusjJs,
            'extracss'                => $extracss,
            'workin_plan'             => Tools::jsonEncode($employee->workin_plan),
            'workin_break'            => Tools::jsonEncode($employee->workin_break),
            'working_plan_exceptions' => Tools::jsonEncode($employee->working_plan_exceptions),
        ]);

        $li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ajouter un eployé</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,

            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessEditObject() {

        if ($this->tabAccess['edit'] == 1) {
            $idEmployee = Tools::getValue('idObject');
            $employee = new Employee($idEmployee);
            $availableProfiles = Profile::getProfiles($this->context->language->id);
            $data = $this->createTemplate('controllers/back_users/editEmployee.tpl');

            $image = _EPH_EMPLOYEE_IMG_DIR_ . $employee->id . '.jpg';

            if (file_exists($image)) {
                $imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/' . $employee->id . '.jpg';
            } else {
                $imageUrl = $this->context->link->getBaseFrontLink() . 'img/e/Unknown.png';
            }

            $extracss = $this->pushCSS([_EPH_JS_DIR_ . 'trumbowyg/ui/trumbowyg.min.css', _EPH_JS_DIR_ . 'jquery-ui/general.min.css',

            ]);
            $pusjJs = $this->pushJS([_EPH_JS_DIR_ . 'employee.js', _EPH_JS_DIR_ . 'trumbowyg/trumbowyg.min.js',
                $this->admin_webpath . _EPH_JS_DIR_ . 'jquery-jeditable/jquery.jeditable.min.js', _EPH_JS_DIR_ . 'jquery-ui/jquery-ui-timepicker-addon.min.js', _EPH_JS_DIR_ . 'moment/moment.min.js', _EPH_JS_DIR_ . 'moment/moment-timezone-with-data.min.js', _EPH_JS_DIR_ . 'calendar/working_plan_exceptions_modal.min.js',
                $this->admin_webpath . _EPH_JS_DIR_ . 'datejs/date.min.js',

            ]);

            $data->assign([
                'employee'                => $employee,
                'imageUrl'                => $imageUrl,
                'availableProfiles'       => $availableProfiles,
                'themes'                  => $this->themes,
                'currentProfileId'        => $this->context->employee->id_profile,
                'EALang'                  => Tools::jsonEncode($this->getEaLang()),
                'pusjJs'                  => $pusjJs,
                'extracss'                => $extracss,
                'workin_plan'             => Tools::jsonEncode($employee->workin_plan),
                'workin_break'            => Tools::jsonEncode($employee->workin_break),
                'working_plan_exceptions' => Tools::jsonEncode($employee->working_plan_exceptions),
            ]);

            $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Modifier ' . $employee->firstname . ' ' . $employee->lastname . '</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
            $html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

            $result = [
                'success' => true,
                'li'      => $li,
                'html'    => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Votre profile administratif ne vous permet pas d‘éditer les employées',
            ];
        }

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessUpdateObject() {

        $id = (int) Tools::getValue('id_employee');

        $object = new $this->className($id);

        $oldPasswd = $object->passwd;

        foreach ($_POST as $key => $value) {

            if (property_exists($object, $key) && $key != 'id_employee') {

                if ($key == 'passwd' && Tools::getValue('id_employee') && empty($value)) {
                    continue;
                }

                if ($key == 'passwd' && Tools::getValue('id_employee') && !empty($value)) {
                    $newPasswd = Tools::hash(Tools::getValue('passwd'));

                    if ($newPasswd == $oldPasswd) {
                        continue;
                    }

                    $value = $newPasswd;
                    $object->password = Tools::getValue('passwd');
                }

                $object->{$key}
                = $value;
            }

        }

        $result = $object->update();

        if ($result) {

            $workingPlan = Tools::jsonDecode(Tools::getValue('workin_plan'), true);
            $workingPlan = Tools::jsonEncode($workingPlan);
            $conge = Tools::jsonDecode(Tools::getValue('workin_plan_exceptions'), true);

            $conge = Tools::jsonEncode($conge);
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'employee_settings` SET `working_plan` = \'' . $workingPlan . '\', `working_break` = \'' . $conge . '\' WHERE `id_employee` = ' . $object->id;
            Db::getInstance()->Execute($sql);

            $imageUploader = new HelperImageUploader('employeeUrl');
            $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {
                    $destinationFile = _EPH_EMPLOYEE_IMG_DIR_ . $object->id . '.jpg';
                    $fileName = $object->id . '.jpg';
                    copy($image['save_path'], $destinationFile);
                }

            }

            $result = [
                'success' => true,
                'message' => $object->firstname . ' ' . $object->lastname . $this->l(' a été mis à jour avec succès'),
            ];

        } else {
            $result = [
                'success' => false,
                'message' => 'Jeff a encore merdé somewhere over the rainbow',
            ];
        }

        die(Tools::jsonEncode($result));

    }

    /**
     * Process delete
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.9.1.0
     */
    public function processDelete() {

        if (!$this->canModifyEmployee()) {
            return false;
        }

        return parent::processDelete();
    }

    public function ajaxProcessAddNewAjaxObject() {

        $employee = new Employee();

        foreach ($_POST as $key => $value) {

            if (property_exists($employee, $key) && $key != 'id_employee') {

                if ($key == 'passwd') {
                    $password = $value;
                }

                $employee->{$key}
                = $value;

            }

        }

        $employee->id_lang = $this->context->language->id;
        $employee->passwd = Tools::hash($password);
        $employee->password = $password;

        try {
            $result = $employee->add();
        } catch (Exception $ex) {
            $file = fopen("testAddNewEmployee.txt", "w");
            fwrite($file, $ex->getMessage());
        }

        if ($result) {

            $workingPlan = Tools::jsonDecode(Tools::getValue('workin_plan'), true);
            $workingPlan = Tools::jsonEncode($workingPlan);
            $conge = Tools::jsonDecode(Tools::getValue('workin_plan_exceptions'), true);

            $conge = Tools::jsonEncode($conge);
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'employee_settings` SET `working_plan` = \'' . $workingPlan . '\', `working_break` = \'' . $conge . '\' WHERE `id_employee` = ' . $object->id;
            Db::getInstance()->Execute($sql);

            $imageUploader = new HelperImageUploader('employeeUrl');
            $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {
                    $destinationFile = _EPH_EMPLOYEE_IMG_DIR_ . $object->id . '.jpg';
                    $fileName = $object->id . '.jpg';
                    copy($image['save_path'], $destinationFile);
                }

            }

            $result = [
                'success' => true,
                'message' => $object->firstname . ' ' . $object->lastname . $this->l(' a été ajouté avec succès'),
            ];

        } else {
            $result = [
                'success' => false,
                'message' => 'Jeff a encore merdé somewhere over the rainbow',
            ];
        }

        die(Tools::jsonEncode($return));
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function processStatus() {

        if (!$this->canModifyEmployee()) {
            return false;
        }

        parent::processStatus();
    }

    /**
     * @param bool $className
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    public function validateRules($className = false) {

        $employee = new Employee((int) Tools::getValue('id_employee'));

        if (!Validate::isLoadedObject($employee) && !Validate::isPasswd(Tools::getvalue('passwd'), Validate::ADMIN_PASSWORD_LENGTH)) {
            return !($this->errors[] = sprintf(
                Tools::displayError('The password must be at least %s characters long.'),
                Validate::ADMIN_PASSWORD_LENGTH
            ));
        }

        return parent::validateRules($className);
    }

    /**
     * Child validation
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function _childValidation() {

        if (!($obj = $this->loadObject(true))) {
            return false;
        }

        if (Tools::getValue('id_profile') == _EPH_ADMIN_PROFILE_ && $this->context->employee->id_profile != _EPH_ADMIN_PROFILE_) {
            $this->errors[] = Tools::displayError('The provided profile is invalid');
        }

        $email = $this->getFieldValue($obj, 'email');

        if (Validate::isEmail($email) && Employee::employeeExists($email) && (!Tools::getValue('id_employee')
            || ($employee = new Employee((int) Tools::getValue('id_employee'))) && $employee->email != $email)
        ) {
            $this->errors[] = Tools::displayError('An account already exists for this email address:') . ' ' . $email;
        }

    }

    /**
     * Process bulk delete
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function processBulkDelete() {

        if (is_array($this->boxes) && !empty($this->boxes)) {

            foreach ($this->boxes as $idEmployee) {

                if ((int) $this->context->employee->id == (int) $idEmployee) {
                    $this->restrict_edition = true;

                    return $this->canModifyEmployee();
                }

            }

        }

        return parent::processBulkDelete();
    }

    /**
     * Ajax process form language
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function ajaxProcessFormLanguage() {

        $this->context->cookie->employee_form_lang = (int) Tools::getValue('form_language_id');

        if (!$this->context->cookie->write()) {
            die('Error while updating cookie.');
        }

        die('Form language updated.');
    }

    protected function ajaxProcessToggleMenu() {

        $this->context->cookie->collapse_menu = (int) Tools::getValue('collapse');
        $this->context->cookie->write();
    }

    protected function postImage($id) {

        if (isset($this->fieldImageSettings['name']) && isset($this->fieldImageSettings['dir'])) {

            if (!Validate::isInt(Tools::getValue('img_width')) || !Validate::isInt(Tools::getValue('img_height'))) {
                $this->errors[] = Tools::displayError('Width and height must be numeric values.');
            } else {

                if ((int) Tools::getValue('img_width') > 0 && (int) Tools::getValue('img_height') > 0) {
                    $width = (int) Tools::getValue('img_width');
                    $height = (int) Tools::getValue('img_height');
                } else {
                    $width = null;
                    $height = null;
                }

                return $this->uploadImage($id, $this->fieldImageSettings['name'], $this->fieldImageSettings['dir'] . '/', false, $width, $height);
            }

        }

        return !count($this->errors) ? true : false;
    }

    protected function afterImageUpload() {

        parent::afterImageUpload();

        if (($id_employee = (int) Tools::getValue('id_employy')) &&
            isset($_FILES) && count($_FILES) && file_exists(_EPH_EMPLOYEE_IMG_DIR_ . $id_employee . '.jpg')) {
            $current_file = _EPH_TMP_IMG_DIR_ . 'emplyee_mini_' . $id_employee . '_' . $this->context->shop->id . '.jpg';

            if (file_exists($current_file)) {
                unlink($current_file);
            }

        }

        return true;
    }

}
