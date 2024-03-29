<?php

/**
 * Class HelperCore
 *
 * @since 1.8.1.0
 */
class HelperCore {

    // @codingStandardsIgnoreStart
    /** @var string */
    public $currentIndex;
    /** @var string $table */
    public $table = 'configuration';
    
    public $className;
    /** @var string $identifier */
    public $identifier;
    /** @var string $token */
    public $token;
    /** @var array $toolbar_btn */
    public $toolbar_btn;
    /** @var mixed $ps_help_context */
    public $ps_help_context;
    /** @var string $title */
    public $title;
    /** @var bool $show_toolbar */
    public $show_toolbar = true;
    /** @var Context $context */
    public $context;
    /** @var bool $toolbar_scroll */
    public $toolbar_scroll = false;
    /** @var bool $bootstrap */
    public $bootstrap = false;
    /** @var Module $module */
    public $module;
    /** @var string Helper tpl folder */
    public $base_folder;
    /** @var string Controller tpl folder */
    public $override_folder;
    /** @var string base template name */
    public $base_tpl = 'content.tpl';
    /** @var array $tpl_vars */
    public $tpl_vars = [];
    /** @var Smarty_Internal_Template base template object */
    protected $tpl;
    // @codingStandardsIgnoreEnd

    /**
     * HelperCore constructor.
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct() {

        $this->context = Context::getContext();
    }

    /**
     * @deprecated 2.0.0
     *
     * @param array  $translations
     * @param array  $selectedCat
     * @param string $inputName
     * @param bool   $useRadio
     * @param bool   $useSearch
     * @param array  $disabledCategories
     * @param bool   $useInPopup
     *
     * @return string
     * @throws PhenyxShopException
     */
    public static function renderAdminCategorieTree(
        $translations,
        $selectedCat = [],
        $inputName = 'categoryBox',
        $useRadio = false,
        $useSearch = false,
        $disabledCategories = [],
        $useInPopup = false
    ) {

        Tools::displayAsDeprecated();

        $helper = new Helper();

        if (isset($translations['Root'])) {
            $root = $translations['Root'];
        } else
        if (isset($translations['Home'])) {
            $root = ['name' => $translations['Home'], 'id_category' => 1];
        } else {
            throw new PhenyxShopException('Missing root category parameter.');
        }

        return $helper->renderCategoryTree($root, $selectedCat, $inputName, $useRadio, $useSearch, $disabledCategories);
    }

    /**
     *
     * @param array  $root        array with the name and ID of the tree root category, if null the Shop's root category will be used
     * @param array  $selectedCat array of selected categories
     * @param string $inputName   name of input
     * @param bool   $useRadio    use radio tree or checkbox tree
     * @param bool   $useSearch   display a find category search box
     * @param array  $disabledCategories
     *
     * @return string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function renderCategoryTree(
        $root = null,
        $selectedCat = [],
        $inputName = 'categoryBox',
        $useRadio = false,
        $useSearch = false,
        $disabledCategories = []
    ) {

        $translations = [
            'selected'     => $this->l('Selected'),
            'Collapse All' => $this->l('Collapse All'),
            'Expand All'   => $this->l('Expand All'),
            'Check All'    => $this->l('Check All'),
            'Uncheck All'  => $this->l('Uncheck All'),
            'search'       => $this->l('Find a category'),
        ];

        if (Tools::isSubmit('id_shop')) {
            $idCompany = Tools::getValue('id_shop');
        } else
        if (Context::getContext()->company->id) {
            $idCompany = Context::getContext()->company->id;
        } else
        $idCompany = Configuration::get('EPH_SHOP_DEFAULT');

        $shop = new Company($idCompany);
        $rootCategory = Category::getRootCategory(null, $shop);
        $disabledCategories[] = (int) Configuration::get('EPH_ROOT_CATEGORY');

        if (!$root) {
            $root = ['name' => $rootCategory->name, 'id_category' => $rootCategory->id];
        }

        if (!$useRadio) {
            $inputName = $inputName . '[]';
        }

        if ($useSearch) {
            $this->context->controller->addJs(_EPH_JS_DIR_ . 'jquery/plugins/autocomplete/jquery.autocomplete.js');
        }

        $html = '
        <script type="text/javascript">
            var inputName = \'' . addcslashes($inputName, '\'') . '\';' . "\n";

        if (count($selectedCat) > 0) {

            if (isset($selectedCat[0])) {
                $html .= '          var selectedCat = "' . implode(',', array_map('intval', $selectedCat)) . '";' . "\n";
            } else {
                $html .= '          var selectedCat = "' . implode(',', array_map('intval', array_keys($selectedCat))) . '";' . "\n";
            }

        } else {
            $html .= '          var selectedCat = \'\';' . "\n";
        }

        $html .= '          var selectedLabel = \'' . $translations['selected'] . '\';
            var home = \'' . addcslashes($root['name'], '\'') . '\';
            var use_radio = ' . (int) $useRadio . ';';
        $html .= '</script>';

        $html .= '
        <div class="category-filter">
            <a class="btn btn-link" href="#" id="collapse_all"><i class="icon-collapse"></i> ' . $translations['Collapse All'] . '</a>
            <a class="btn btn-link" href="#" id="expand_all"><i class="icon-expand"></i> ' . $translations['Expand All'] . '</a>
            ' . (!$useRadio ? '
                <a class="btn btn-link" href="#" id="check_all"><i class="icon-check"></i> ' . $translations['Check All'] . '</a>
                <a class="btn btn-link" href="#" id="uncheck_all"><i class="icon-check-empty"></i> ' . $translations['Uncheck All'] . '</a>' : '')
            . ($useSearch ? '
                <div class="row">
                    <label class="control-label col-lg-6" for="search_cat">' . $translations['search'] . ' :</label>
                    <div class="col-lg-6">
                        <input type="text" name="search_cat" id="search_cat"/>
                    </div>
                </div>' : '')
            . '</div>';

        $homeIsSelected = false;

        if (is_array($selectedCat)) {

            foreach ($selectedCat as $cat) {

                if (is_array($cat)) {
                    $disabled = in_array($cat['id_category'], $disabledCategories);

                    if ($cat['id_category'] != $root['id_category']) {
                        $html .= '<input ' . ($disabled ? 'disabled="disabled"' : '') . ' type="hidden" name="' . $inputName . '" value="' . $cat['id_category'] . '" >';
                    } else {
                        $homeIsSelected = true;
                    }

                } else {
                    $disabled = in_array($cat, $disabledCategories);

                    if ($cat != $root['id_category']) {
                        $html .= '<input ' . ($disabled ? 'disabled="disabled"' : '') . ' type="hidden" name="' . $inputName . '" value="' . $cat . '" >';
                    } else {
                        $homeIsSelected = true;
                    }

                }

            }

        }

        $rootInput = '';

        if ($root['id_category'] != (int) Configuration::get('EPH_ROOT_CATEGORY') || (Tools::isSubmit('ajax') && Tools::getValue('action') == 'getCategoriesFromRootCategory')) {
            $rootInput = '
                <p class="checkbox"><i class="icon-folder-open"></i><label>
                    <input type="' . (!$useRadio ? 'checkbox' : 'radio') . '" name="'
                . $inputName . '" value="' . $root['id_category'] . '" '
                . ($homeIsSelected ? 'checked' : '') . ' onclick="clickOnCategoryBox($(this));" />'
                . $root['name'] .
                '</label></p>';
        }

        $html .= '
            <div class="container">
                <div class="well">
                    <ul id="categories-treeview">
                        <li id="' . $root['id_category'] . '" class="hasChildren">
                            <span class="folder">' . $rootInput . ' </span>
                            <ul>
                                <li><span class="placeholder">&nbsp;</span></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>';

        if ($useSearch) {
            $html .= '<script type="text/javascript">searchCategory();</script>';
        }

        return $html;
    }

    
    /**
     * @param string $tpl
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function setTpl($tpl) {

        $this->tpl = $this->createTemplate($tpl);
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tplName filename
     *
     * @return Smarty_Internal_Template|object
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function createTemplate($tplName) {

       	if ($this->override_folder) {
			

            if ($this->context->controller instanceof ModuleAdminController) {
                $overrideTplPath = $this->context->controller->getTemplatePath($tplName) . $this->override_folder . $this->base_folder . $tplName;
            } else
            if ($this->module) {
                $overrideTplPath = _EPH_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_configure/' . $this->override_folder . $this->base_folder . $tplName;
            } else {
				
                if (file_exists($this->context->smarty->getTemplateDir(1) . $this->override_folder . $this->base_folder . $tplName)) {
                    $overrideTplPath = $this->context->smarty->getTemplateDir(1) . $this->override_folder . $this->base_folder . $tplName;
                } else
                if (file_exists($this->context->smarty->getTemplateDir(0) . 'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $this->base_folder . $tplName)) {
                    $overrideTplPath = $this->context->smarty->getTemplateDir(0) . 'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $this->base_folder . $tplName;
                }

            }

        } else
        if ($this->module) {
			
            $overrideTplPath = _EPH_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_configure/' . $this->base_folder . $tplName;
        }

        if (isset($overrideTplPath) && file_exists($overrideTplPath)) {

            return $this->context->smarty->createTemplate($overrideTplPath, $this->context->smarty);
        } else {
            return $this->context->smarty->createTemplate($this->base_folder . $tplName, $this->context->smarty);
        }

    }

    /**
     * default behaviour for helper is to return a tpl fetched
     *
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function generate() {

        $this->tpl->assign($this->tpl_vars);

        return $this->tpl->fetch();
    }

    /**
     * Render a form with potentials required fields
     *
     * @param string $className
     * @param string $identifier
     * @param array  $tableFields
     *
     * @return string
     *
     * @throws Exception
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function renderRequiredFields($className, $identifier, $tableFields) {

        $rules = call_user_func_array([$className, 'getValidationRules'], [$className]);
        $requiredClassFields = [$identifier];

        foreach ($rules['required'] as $required) {
            $requiredClassFields[] = $required;
        }

        /** @var ObjectModel $object */
        $object = new $className();
        $res = $object->getFieldsRequiredDatabase();

        $requiredFields = [];

        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->tpl_vars = [
            'table_fields'          => $tableFields,
            'irow'                  => 0,
            'required_class_fields' => $requiredClassFields,
            'required_fields'       => $requiredFields,
            'current'               => $this->currentIndex,
            'token'                 => $this->token,
        ];

        $tpl = $this->createTemplate('helpers/required_fields.tpl');
        $tpl->assign($this->tpl_vars);

        return $tpl->fetch();
    }

    /**
     * @param array $modulesList
     *
     * @return mixed
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function renderModulesList($modulesList) {

        $this->tpl_vars = [
            'modules_list' => $modulesList,
            'modules_uri'  => __EPH_BASE_URI__ . basename(_EPH_MODULE_DIR_),
        ];
        // The translations for this are defined by AdminModules, so override the context for the translations
        $overrideControllerNameForTranslations = Context::getContext()->override_controller_name_for_translations;
        Context::getContext()->override_controller_name_for_translations = 'AdminModules';
        $tpl = $this->createTemplate('helpers/modules_list/list.tpl');
        $tpl->assign($this->tpl_vars);
        $html = $tpl->fetch();
        // Restore the previous context
        Context::getContext()->override_controller_name_for_translations = $overrideControllerNameForTranslations;

        return $html;
    }

    /**
     * use translations files to replace english expression.
     *
     * @param mixed  $string       term or expression in english
     * @param string $class
     * @param bool   $addslashes   if set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool   $htmlentities if set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string the translation if available, or the english default text.
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    protected function l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true) {

        // if the class is extended by a module, use modules/[module_name]/xx.php lang file
        $currentClass = get_class($this);

        if (Module::getModuleNameFromClass($currentClass)) {
            return Translate::getModuleTranslation(Module::$classInModule[$currentClass], $string, $currentClass);
        }

        return Translate::getAdminTranslation($string, get_class($this), $addslashes, $htmlentities);
    }

}
