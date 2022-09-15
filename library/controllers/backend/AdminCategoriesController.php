<?php

/**
 * Class AdminCategoriesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCategoriesControllerCore extends AdminController {

    public $php_self = 'admincategories';
    // @codingStandardsIgnoreStart
    /** @var bool does the product have to be removed during the delete process */
    public $remove_products = true;
    /** @var bool does the product have to be disable during the delete process */
    public $disable_products = false;
    /**
     * @var object Category() instance for navigation
     */
    protected $_category = null;
    protected $position_identifier = 'id_category_to_move';
    protected $original_filter = '';
    // @codingStandardsIgnoreEnd

    protected $admin_composer;

    /**
     * AdminCategoriesControllerCore constructor.
     *
     * @since 1.8.1.0
     * @throws PhenyxShopException
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'category';
        $this->className = 'Category';
        $this->publicName = $this->l('Categories');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_CATEGORIES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_CATEGORIES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_CATEGORIES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_CATEGORIES_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_CATEGORIES_FIELDS', Tools::jsonEncode($this->getCategoryFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CATEGORIES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_CATEGORIES_FIELDS', Tools::jsonEncode($this->getCategoryFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CATEGORIES_FIELDS'), true);
        }

        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/categories.css',

        ]);

    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
            _EPH_JS_DIR_ . 'tinymce.inc.js',
            _EPH_JS_DIR_ . 'categories.js',
            _EPH_JS_DIR_ . 'tree.js?v=' . _EPH_VERSION_,
        ]);
    }

    public function generateParaGridScript() {

        $this->windowHeight = '400';
        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $this->filterModel = [];
        $this->paramPageModel = [];
        $this->paramTitle = '\'' . $this->l('Management of Product Categories') . '\'';
        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'icon'     => '\'ui-icon-disk\'',
                    'label'    => '\'' . $this->l('Ajouter une famille') . '\'',
                    'cls'      => '\'buttonCleanProductPosition changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only pull-right\'',
                    'listener' => 'addNewCategory',
                ],

            ],
        ];
        $this->paramCreate = 'function(){
            var arr = this.pageData().filter(function(rd){
                return (rd.pq_level>0);
            })
            this.Tree().collapseNodes(arr);
        }';
        $this->dragModel = [
            'on'          => true,
            'diHelper'    => "['position']",
            'clsHandle'   => '\'dragHandle\'',
            'dragNodes'   => 'function(rd, evt){
                var checkNodes = this.Tree().getCheckedNodes();
                return (checkNodes.length && checkNodes.indexOf(rd)>-1 )? checkNodes: [ rd ];
            }',
            'isDraggable' => 'function(ui){
                return !(ui.rowData.pq_gsummary || ui.rowData.pq_level == 0);
            }',
        ];
        $this->dropModel = [
            'on'          => true,
            'isDroppable' => 'function(evt, uiDrop){

                var Drag = uiDrop.helper.data(\'Drag\'),
                    uiDrag = Drag.getUI(),
                    rdDrag = uiDrag.rowData,
                    rdDrop = uiDrop.rowData,
                    Tree = this.Tree(),
                    denyDrop = (
                        rdDrop == rdDrag ||
                        rdDrop.pq_gsummary ||
                        Tree.isAncestor( rdDrop,  rdDrag)
                    );

                return !denyDrop;
            }',
        ];
        $this->moveNode = 'function(event, ui) {
                    var grid = this,
                    Tree = grid.Tree();
                    console.log(ui);
                    var Object;
                    var stopIndex;
                    var startIndex = ui.args[0][0].categoryPosition;
                    var level = ui.args[0][0].pq_level;
                    var idCategory = parseInt(ui.args[0][0].id_category);
                    var idParent = ui.args[0][0].id_parent;
                    var i =1;
                    stopIndex = ui.args[2];
                    var way = (startIndex < stopIndex) ? 1 : 0;
                    processCategoriesPosition(idCategory, idParent, startIndex, stopIndex, \'categoryPosition_\'+idParent, \'' . $this->context->link->getAdminLink($this->controller_name) . '\');

                    grid.option(\'dataModel.data\', get' . $this->className . 'Request());
                    grid.refreshDataAndView();

                    Tree.collapseAll();
                    Tree.expandTo(Tree.getNode(idCategory));
                }';

        $this->filterModel = [
            'on'     => true,
            'mode'   => '\'AND\'',
            'header' => true,
        ];
        $this->treeModel = [
            'dataIndx'     => '\'name\'',
            'id'           => '\'id_category\'',
            'checkbox'     => 0,
            'checkboxHead' => 0,
            'icons'        => 0,
            // 'iconCollapse' => ['\'fa fa-circle-minus toggeCategoryClose\'', '\'fa fa-circle-plus toggeCategoryOpen\''],
        ];
        $this->paramContextMenu = [
            '#grid_' . $this->controller_name => [
                'selector'  => '\'.pq-grid-row\'',
                'animation' => [
                    'duration' => 250,
                    'show'     => '\'fadeIn\'',
                    'hide'     => '\'fadeOut\'',
                ],
                'build'     => 'function($triggerElement, e){
                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->className . '.getRowData( {rowIndx: rowIndex} );
                return {
                    callback: function(){},
                    items: {
                        "add": {
                            name: \'' . $this->l('Ajouter une nouvelle famille de produit') . '\',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addNewCategory(rowData.id_category);
                            }
                            },
                        "edit": {
                            name: \'' . $this->l('Editer ou modifier la famille  ') . '\'+rowData.name,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                editCategory(rowData.id_category, rowData.id_parent);
                            }
                        },
                        "sep1": "---------",

                        "delete": {
                            name: \'' . $this->l('Supprimer la famille de formation ') . '\'+rowData.name,
                            icon: "delete",
                            visible: function(key, opt){
                                return !rowData.hasSubmenu;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteCategory(rowData.id_category);


                            }
                        }
                    },
                };
            }',
            ]];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getCategoryRequest() {

        $root = Category::getRootCategory();

        $results = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('c.*, cl.*, c.`position`')
                ->from('category', 'c')
                ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . (int) $this->context->language->id)
                ->rightJoin('category', 'c2', 'c2.`id_category` = ' . (int) $root->id . ' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`')
                ->where('id_lang = ' . (int) $this->context->language->id)
                ->orderBy('c.`level_depth` ASC, c.`position` ASC')

        );

        foreach ($results as &$category) {

            if ($category['active'] == 1) {
                $category['active'] = '<div class="fa fa-check" style="color: green"></div>';
                $category['enable'] = true;
            } else {
                $category['active'] = '<div class="fa fa-times" style="color:red"></div>';
                $category['enable'] = false;
            }

            $category['categoryPosition'] = $category['position'];
            $category['position'] = '<div class="dragGroup"><div class="categoryPosition_' . $category['id_parent'] . ' positions" data-id="' . $category['id_category'] . '" data-position="' . $category['position'] . '">' . $category['position'] . '</div></div>';

        }

        $root_category = Category::getRootCategory()->id;

        $categories = [];
        $buff = [];

        foreach ($results as $row) {
            $current = &$buff[$row['id_category']];
            $current = $row;

            if ($row['id_category'] == $root_category) {
                $categories[$row['id_category']] = &$current;
            } else {
                $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
            }

        }

        $categories = $this->removeFields($categories);

        return $categories;
    }

    public function removeFields($categoryTrees) {

        $catagoryTree = [];

        $fields = [];
        $gridFields = $this->getCategoryFields();

        foreach ($gridFields as $grifField) {
            $fields[] = $grifField['dataIndx'];
        }

        foreach ($categoryTrees as $key => $category) {

            foreach ($category as $key2 => $tree) {

                if ($key2 == 'id_category') {
                    $catagoryTree[$key]['id'] = $tree;
                }

                if (in_array($key2, $fields)) {
                    $catagoryTree[$key][$key2] = $tree;
                } else

                if ($key2 == 'children') {
                    $catagoryTree[$key][$key2] = array_values($this->removeFields($tree));
                }

            }

        }

        return array_values($catagoryTree);
    }

    public function ajaxProcessgetCategoryRequest() {

        die(Tools::jsonEncode($this->getCategoryRequest()));
    }

    public function getCategoryFields() {

        return [

            [
                'title'    => $this->l('ID'),
                'maxWidth' => 100,
                'dataIndx' => 'id_category',
                'dataType' => 'integer',
                'editable' => false,
                'align'    => 'center',
                'valign'   => 'center',
                'hidden'   => true,
            ],

            [
                'title'      => $this->l('Name'),
                'width'      => 300,
                'dataIndx'   => 'name',
                'align'      => 'left',
                'valign'     => 'center',
                'editable'   => true,
                'dataType'   => 'string',
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Description'),
                'width'    => 300,
                'dataIndx' => 'description',
                'cls'      => 'category_description',
                'valign'   => 'top',
                'dataType' => 'html',
                'editable' => false,
            ],
            [
                'title'    => $this->l('Meta Description'),
                'width'    => 300,
                'dataIndx' => 'meta_description',
                'cls'      => 'category_description',
                'dataType' => 'top',
                'editable' => false,
                'hidden'   => true,

            ],

            [
                'title'      => $this->l('Active'),
                'width'      => 50,
                'dataIndx'   => 'enable',
                'dataType'   => 'bool',
                'editable'   => false,
                'align'      => 'left',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Enabled'),
                'width'    => 100,
                'editable' => false,
                'dataIndx' => 'active',
                'align'    => 'center',
                'valign'   => 'center',
                'editable' => false,
                'dataType' => 'html',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'id_parent',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'categoryPosition',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],

            [
                'title'    => $this->l('Position'),
                'width'    => 120,
                'dataIndx' => 'position',
                'cls'      => 'pointer dragHandle',
                'editable' => false,
                'dataType' => 'html',
                'align'    => 'center',
                'valign'   => 'center',
            ],
        ];

    }

    public function ajaxProcessgetCategoryFields() {

        die(EmployeeConfiguration::get('EXPERT_CATEGORIES_FIELDS'));
    }

    public function ajaxProcessAddNewCategory() {

        $_GET['addcategory'] = "";
        $_GET['id_parent'] = Tools::getValue('id_parent');
        $scripHeader = Hook::exec('displayBackOfficeHeader', []);
        $scriptFooter = Hook::exec('displayBackOfficeFooter', []);

        $html = $this->renderForm();
        $li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">' . $this->editObject . '</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $scripHeader . $html . $scriptFooter . '</div>';

        $result = [
            'li'   => $li,
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessEditCategory() {

        $idCategory = Tools::getValue('idCategory');
        $this->identifier = 'id_category';
        $_GET['id_category'] = $idCategory;
        $_GET['id_parent'] = Tools::getValue('idParent');
        $_GET['updatecategory'] = "";
        $scripHeader = Hook::exec('displayBackOfficeHeader', []);
        $scriptFooter = Hook::exec('displayBackOfficeFooter', []);

        $html = $this->renderForm();
        $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">' . $this->editObject . '</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $scripHeader . $html . $scriptFooter . '</div>';

        $result = [
            'success' => true,
            'li'      => $li,
            'html'    => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessDeleteCategory() {

        $idCategory = Tools::getValue('idCategory');
        $category = new Category($idCategory);
        $category->delete();

        $result = [
            'success' => true,
            'message' => 'La catégorie a été supprimée avec succès',
        ];

        die(Tools::jsonEncode($result));
    }

    /**
     * @return string|void
     *
     * @since 1.8.1.0
     */
    public function renderForm() {

        /** @var Category $obj */
        $obj = $this->loadObject();

        $context = $this->context;
        $idCompany = $context->company->id;

        $selectedCategories = [(isset($obj->id_parent) && $obj->isParentCategoryAvailable($idCompany)) ? (int) $obj->id_parent : (int) Tools::getValue('id_parent', Category::getRootCategory()->id)];
        $category_tree = $this->getCategoryTree($selectedCategories, $obj->id);

        $unidentified = new Group(Configuration::get('EPH_UNIDENTIFIED_GROUP'));
        $guest = new Group(Configuration::get('EPH_GUEST_GROUP'));
        $default = new Group(Configuration::get('EPH_CUSTOMER_GROUP'));

        $unidentifiedGroupInformation = sprintf($this->l('%s - All people without a valid customer account.'), '<b>' . $unidentified->name[$this->context->language->id] . '</b>');
        $guestGroupInformation = sprintf($this->l('%s - Customer who placed an order with the guest checkout.'), '<b>' . $guest->name[$this->context->language->id] . '</b>');
        $defaultGroupInformation = sprintf($this->l('%s - All people who have created an account on this site.'), '<b>' . $default->name[$this->context->language->id] . '</b>');

        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $image = _EPH_CAT_IMG_DIR_ . $obj->id . '.' . $this->imageType;

        if (file_exists($image)) {
            $imageUrl = $this->context->link->getBaseFrontLink() . 'content/img/c/' . $obj->id . '.jpg';
        } else {
            $imageUrl = $this->context->link->getBaseFrontLink() . 'content/img/c/fr-default-category_default.jpg';
        }

        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;
        $imagesTypes = ImageType::getImagesTypes('categories');
        $format = [];
        $thumb = $thumbUrl = '';
        $formattedCategory = ImageType::getFormatedName('category');
        $formattedMedium = ImageType::getFormatedName('medium');

        foreach ($imagesTypes as $k => $imageType) {

            if ($formattedCategory == $imageType['name']) {
                $format['category'] = $imageType;
            } else

            if ($formattedMedium == $imageType['name']) {
                $format['medium'] = $imageType;
                $thumb = _EPH_CAT_IMG_DIR_ . $obj->id . '-' . $imageType['name'] . '.' . $this->imageType;

                if (is_file($thumb)) {
                    $thumbUrl = ImageManager::thumbnail($thumb, $this->table . '_' . (int) $obj->id . '-thumb.' . $this->imageType, (int) $imageType['width'], $this->imageType, true, true);
                }

            }

        }

        if (!is_file($thumb)) {
            $thumb = $image;
            $thumbUrl = ImageManager::thumbnail($image, $this->table . '_' . (int) $obj->id . '-thumb.' . $this->imageType, 125, $this->imageType, true, true);
            ImageManager::resize(_EPH_TMP_IMG_DIR_ . $this->table . '_' . (int) $obj->id . '-thumb.' . $this->imageType, _EPH_TMP_IMG_DIR_ . $this->table . '_' . (int) $obj->id . '-thumb.' . $this->imageType, (int) $imageType['width'], (int) $imageType['height']);
        }

        $thumbSize = file_exists($thumb) ? filesize($thumb) / 1000 : false;

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Category'),
                'icon'  => 'icon-tags',
            ],
            'input'   => [
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_parent',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'class'    => 'copy2friendlyUrl',
                    'hint'     => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Displayed'),
                    'name'     => 'active',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Display products from subcategories'),
                    'name'     => 'display_from_sub',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'display_from_sub_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'display_from_sub_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'          => 'gridcategories',
                    'label'         => $this->l('Parent category'),
                    'name'          => 'parent_category',
                    'category_tree' => $category_tree,
                    'radio'         => 1,

                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'hint'         => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'          => 'image',
                    'label'         => $this->l('Category Cover Image'),
                    'name'          => 'image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'format'        => $format['category'],
                ],

                [
                    'type'    => 'text',
                    'label'   => $this->l('Meta title'),
                    'name'    => 'meta_title',
                    'maxchar' => 70,
                    'lang'    => true,
                    'rows'    => 5,
                    'cols'    => 100,
                    'hint'    => $this->l('Forbidden characters:') . ' <>;=#{}',
                ],
                [
                    'type'    => 'textarea',
                    'label'   => $this->l('Meta description'),
                    'name'    => 'meta_description',
                    'maxchar' => 160,
                    'lang'    => true,
                    'rows'    => 5,
                    'cols'    => 100,
                    'hint'    => $this->l('Forbidden characters:') . ' <>;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'hint'  => $this->l('To add "tags," click in the field, write something, and then press "Enter."') . '&nbsp;' . $this->l('Forbidden characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Friendly URL'),
                    'name'     => 'link_rewrite',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Only letters, numbers, underscore (_) and the minus (-) character are allowed.'),
                ],
                [
                    'type'              => 'group',
                    'label'             => $this->l('Group access'),
                    'name'              => 'groupBox',
                    'values'            => Group::getGroups($this->context->language->id),
                    'info_introduction' => $this->l('You now have three default customer groups.'),
                    'unidentified'      => $unidentifiedGroupInformation,
                    'guest'             => $guestGroupInformation,
                    'customer'          => $defaultGroupInformation,
                    'hint'              => $this->l('Mark all of the customer groups which you would like to have access to this category.'),
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'name'  => 'submitAdd' . $this->table . ($this->_category->is_root_category && !Tools::isSubmit('add' . $this->table) && !Tools::isSubmit('add' . $this->table . 'root') ? '' : 'AndBackToParent'),
            ],
        ];

        
        $this->tpl_form_vars['EPH_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('EPH_ALLOW_ACCENTED_CHARS_URL');
        $this->tpl_form_vars['displayBackOfficeCategory'] = Hook::exec('displayBackOfficeCategory');

        

        // Added values of object Group
        $categoryGroupsIds = $obj->getGroups();

        $groups = Group::getGroups($this->context->language->id);
        // if empty $carrier_groups_ids : object creation : we set the default groups

        if (empty($categoryGroupsIds)) {
            $preselected = [Configuration::get('EPH_UNIDENTIFIED_GROUP'), Configuration::get('EPH_GUEST_GROUP'), Configuration::get('EPH_CUSTOMER_GROUP')];
            $categoryGroupsIds = array_merge($categoryGroupsIds, $preselected);
        }

        foreach ($groups as $group) {
            $this->fields_value['groupBox_' . $group['id_group']] = Tools::getValue('groupBox_' . $group['id_group'], (in_array($group['id_group'], $categoryGroupsIds)));
        }

        $this->fields_value['is_root_category'] = (bool) Tools::isSubmit('add' . $this->table . 'root');

        if ($this->object->id > 0) {
            $this->form_action = 'updateCategory';
            $this->editObject = $this->l('Edit and update the category ') . $obj->name[$this->context->language->id];
        } else {
            $this->form_action = 'addCategory';
            $this->editObject = $this->l('Add new Category');
        }

        $this->form_ajax = 1;
        return parent::renderForm();
    }

    protected function _cleanMetaKeywords($keywords) {

        if (!empty($keywords) && $keywords != '') {
            $out = [];
            $words = explode(',', $keywords);

            foreach ($words as $wordItem) {
                $wordItem = trim($wordItem);

                if (!empty($wordItem) && $wordItem != '') {
                    $out[] = $wordItem;
                }

            }

            return ((count($out) > 0) ? implode(',', $out) : '');
        } else {
            return '';
        }

    }

    public function ajaxProcessAddCategory() {

        $file = fopen("testProcessAddCategory.txt", "w");

        $category = new Category();

        foreach ($_POST as $key => $value) {

            if (property_exists($category, $key) && $key != 'id_category') {
                fwrite($file, $key . ' => ' . $value . PHP_EOL);
                $category->{$key}

                = $value;
            }

        }

        $classVars = get_class_vars(get_class($category));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($category->{$field}) || !is_array($category->{$field})) {
                            $category->{$field}

                            = [];
                        }

                        $category->{$field}

                        [(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                    }

                }

            }

        }

        foreach (Language::getIDs(false) as $idLang) {

            if (isset($_POST['meta_keywords_' . $idLang])) {
                $_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
                $category->keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
            }

        }

        fwrite($file, print_r($category, true) . PHP_EOL);
        try {

            $result = $category->add();

        } catch (Exception $e) {

            fwrite($file, "Error : " . $e->getMessage() . PHP_EOL);
        }

        if ($result) {
            $imageUploader = new HelperImageUploader('imageimage');
            $imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                copy($image['save_path'], _EPH_CAT_IMG_DIR_ . $category->id . '.jpg');
                $imagesTypes = ImageType::getImagesTypes('categories');

                foreach ($files as $image) {

                    foreach ($imagesTypes as $imageType) {

                        $success = ImageManager::resize(
                            _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                            _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '.' . $this->imageType,
                            (int) $imageType['width'],
                            (int) $imageType['height']
                        );

                        if (ImageManager::webpSupport()) {
                            $success &= ImageManager::resize(
                                _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '.webp',
                                (int) $imageType['width'],
                                (int) $imageType['height'],
                                'webp'
                            );
                        }

                        if (ImageManager::retinaSupport()) {
                            $success &= ImageManager::resize(
                                _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '2x.' . $this->imageType,
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2
                            );

                            if (ImageManager::webpSupport()) {
                                $success &= ImageManager::resize(
                                    _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                    _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '2x.webp',
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    'webp'
                                );
                            }

                        }

                    }

                }

            }

            $return = [
                'success' => true,
                'message' => $this->l('La catégorie a été ajoutée avec succès'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Bug merde add'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessUpdateCategory() {

        $file = fopen("testProcessUpdateCategory.txt", "w");
        $idCategory = Tools::getValue('id_category');

        if ($idCategory && Validate::isUnsignedId($idCategory)) {
            fwrite($file, $idCategory . PHP_EOL);

            $category = new Category($idCategory);

            if (Validate::isLoadedObject($category)) {

                foreach ($_POST as $key => $value) {

                    if (property_exists($category, $key) && $key != 'id_category') {
                        fwrite($file, $key . ' => ' . $value . PHP_EOL);
                        $category->{$key}

                        = $value;
                    }

                }

                $classVars = get_class_vars(get_class($category));
                $fields = [];

                if (isset($classVars['definition']['fields'])) {
                    $fields = $classVars['definition']['fields'];
                }

                foreach ($fields as $field => $params) {

                    if (array_key_exists('lang', $params) && $params['lang']) {

                        foreach (Language::getIDs(false) as $idLang) {

                            if (Tools::isSubmit($field . '_' . (int) $idLang)) {

                                if (!isset($category->{$field}) || !is_array($category->{$field})) {
                                    $category->{$field}

                                    = [];
                                }

                                $category->{$field}

                                [(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
                            }

                        }

                    }

                }

                foreach (Language::getIDs(false) as $idLang) {

                    if (isset($_POST['meta_keywords_' . $idLang])) {
                        $_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
                        $category->keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
                    }

                }

                try {
                    $result = $category->update();
                } catch (Exception $e) {
                    fwrite($file, "Error : " . $e->getMessage() . PHP_EOL);
                }

                if ($result) {
                    $imageUploader = new HelperImageUploader('imageimage');
                    $imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
                    $files = $imageUploader->process();
                    fwrite($file, print_r($files, true));

                    if (is_array($files) && count($files)) {

                        $imagesTypes = ImageType::getImagesTypes('categories');

                        foreach ($files as $image) {
                            copy($image['save_path'], _EPH_CAT_IMG_DIR_ . $category->id . '.jpg');

                            foreach ($imagesTypes as $imageType) {

                                $success = ImageManager::resize(
                                    _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                    _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '.' . $this->imageType,
                                    (int) $imageType['width'],
                                    (int) $imageType['height']
                                );

                                if (ImageManager::webpSupport()) {
                                    $success &= ImageManager::resize(
                                        _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                        _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '.webp',
                                        (int) $imageType['width'],
                                        (int) $imageType['height'],
                                        'webp'
                                    );
                                }

                                if (ImageManager::retinaSupport()) {
                                    $success &= ImageManager::resize(
                                        _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                        _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '2x.' . $this->imageType,
                                        (int) $imageType['width'] * 2,
                                        (int) $imageType['height'] * 2
                                    );

                                    if (ImageManager::webpSupport()) {
                                        $success &= ImageManager::resize(
                                            _EPH_CAT_IMG_DIR_ . $category->id . '.' . $this->imageType,
                                            _EPH_CAT_IMG_DIR_ . $category->id . '-' . stripslashes($imageType['name']) . '2x.webp',
                                            (int) $imageType['width'] * 2,
                                            (int) $imageType['height'] * 2,
                                            'webp'
                                        );
                                    }

                                }

                            }

                        }

                    }

                    $return = [
                        'success' => true,
                        'message' => $this->l('La catégorie a été mise à jour avec succès'),
                    ];
                }

            } else {
                $return = [
                    'success' => false,
                    'message' => $this->l('Un true a merdé AVEC PNJET'),
                ];
            }


        } else {
            $return = [
                'success' => false,
                'message' => $this->l('Un true a merdé avec  ID'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    protected function postImage($id) {

        $ret = parent::postImage($id);

        if (($idCategory = (int) Tools::getValue('id_category')) && isset($_FILES) && count($_FILES)) {
            $name = 'image';

            if ($_FILES[$name]['name'] != null && file_exists(_EPH_CAT_IMG_DIR_ . $idCategory . '.' . $this->imageType)) {
                try {
                    $imagesTypes = ImageType::getImagesTypes('categories');
                } catch (PhenyxShopException $e) {
                    Logger::addLog("Error while generating category image: {$e->getMessage()}");

                    return false;
                }

                foreach ($imagesTypes as $k => $imageType) {
                    $success = ImageManager::resize(
                        _EPH_CAT_IMG_DIR_ . $idCategory . '.' . $this->imageType,
                        _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '.' . $this->imageType,
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    );

                    if (ImageManager::webpSupport()) {
                        $success &= ImageManager::resize(
                            _EPH_CAT_IMG_DIR_ . $idCategory . '.' . $this->imageType,
                            _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '.webp',
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            'webp'
                        );
                    }

                    if (ImageManager::retinaSupport()) {
                        $success &= ImageManager::resize(
                            _EPH_CAT_IMG_DIR_ . $idCategory . '.' . $this->imageType,
                            _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '2x.' . $this->imageType,
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2
                        );

                        if (ImageManager::webpSupport()) {
                            $success &= ImageManager::resize(
                                _EPH_CAT_IMG_DIR_ . $idCategory . '.' . $this->imageType,
                                _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '2x.webp',
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2,
                                'webp'
                            );
                        }

                    }

                    if (!$success) {
                        $this->errors = Tools::displayError('An error occurred while uploading category image.');
                    } else {

                        if (Configuration::get('EPH_IMAGE_LAST_UPD_CATEGORIES') < $idCategory) {
                            Configuration::updateValue('EPH_IMAGE_LAST_UPD_CATEGORIES', $idCategory);
                        }

                    }

                }

            }

            $name = 'thumb';

            if ($_FILES[$name]['name'] != null) {

                if (!isset($imagesTypes)) {
                    try {
                        $imagesTypes = ImageType::getImagesTypes('categories');
                    } catch (PhenyxShopException $e) {
                        Logger::addLog("Error while generating category image: {$e->getMessage()}");

                        return false;
                    }

                }

                try {
                    $formattedMedium = ImageType::getFormatedName('medium');
                } catch (PhenyxShopException $e) {
                    Logger::addLog("Error while generating category image: {$e->getMessage()}");

                    return false;
                }

                foreach ($imagesTypes as $k => $imageType) {

                    if ($formattedMedium == $imageType['name']) {

                        if ($error = ImageManager::validateUpload($_FILES[$name], Tools::getMaxUploadSize())) {
                            $this->errors[] = $error;
                        } else

                        if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$name]['tmp_name'], $tmpName)) {
                            $ret = false;
                        } else {
                            $success = ImageManager::resize(
                                $tmpName,
                                _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '.' . $this->imageType,
                                (int) $imageType['width'],
                                (int) $imageType['height']
                            );

                            if (ImageManager::webpSupport()) {
                                ImageManager::resize(
                                    $tmpName,
                                    _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '.webp',
                                    (int) $imageType['width'],
                                    (int) $imageType['height'],
                                    'webp'
                                );
                            }

                            if (ImageManager::retinaSupport()) {
                                ImageManager::resize(
                                    $tmpName,
                                    _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '2x.' . $this->imageType,
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2
                                );

                                if (ImageManager::webpSupport()) {
                                    ImageManager::resize(
                                        $tmpName,
                                        _EPH_CAT_IMG_DIR_ . $idCategory . '-' . stripslashes($imageType['name']) . '2x.webp',
                                        (int) $imageType['width'] * 2,
                                        (int) $imageType['height'] * 2,
                                        'webp'
                                    );
                                }

                            }

                            if (Configuration::get('EPH_IMAGE_LAST_UPD_CATEGORIES') < $idCategory) {
                                Configuration::updateValue('EPH_IMAGE_LAST_UPD_CATEGORIES', $idCategory);
                            }

                            if (!$success) {
                                $this->errors = Tools::displayError('An error occurred while uploading thumbnail image.');
                            }

                            if (count($this->errors)) {
                                $ret = false;
                            } else {
                                $ret = true;
                            }

                            unlink($tmpName);
                        }

                    }

                }

            }

        }

        return $ret;
    }

    /**
     * @since 1.8.1.0
     */
    public function processForceDeleteImage() {

        $category = $this->loadObject(true);

        if (Validate::isLoadedObject($category)) {
            $category->deleteImage(true);
        }

    }

    /**
     * @return bool
     *
     * @since 1.8.1.0
     */
    public function processForceDeleteThumb() {

        $category = $this->loadObject(true);

        if (Validate::isLoadedObject($category)) {

            if (file_exists(_EPH_TMP_IMG_DIR_ . $this->table . '_' . $category->id . '-thumb.' . $this->imageType)
                && !unlink(_EPH_TMP_IMG_DIR_ . $this->table . '_' . $category->id . '-thumb.' . $this->imageType)
            ) {
                return false;
            }

            if (file_exists(_EPH_CAT_IMG_DIR_ . $category->id . '_thumb.' . $this->imageType)
                && !unlink(_EPH_CAT_IMG_DIR_ . $category->id . '_thumb.' . $this->imageType)
            ) {
                return false;
            }

            $imagesTypes = ImageType::getImagesTypes('categories');
            $formattedMedium = ImageType::getFormatedName('medium');

            foreach ($imagesTypes as $k => $imageType) {

                if ($formattedMedium == $imageType['name'] &&
                    file_exists(_EPH_CAT_IMG_DIR_ . $category->id . '-' . $imageType['name'] . '.' . $this->imageType) &&
                    !unlink(_EPH_CAT_IMG_DIR_ . $category->id . '-' . $imageType['name'] . '.' . $this->imageType)
                ) {
                    return false;
                }

            }

        }

        return true;
    }

    public function movePosition($id_category, $position) {

        $result = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'category` c
            SET c.`position`= ' . (int) $position . '
            WHERE c.`id_category` =' . (int) $id_category);

        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'category_shop` c
            SET c.`position`= ' . (int) $position . '
            WHERE c.`id_category` =' . (int) $id_category . ' AND c.`id_shop` = ' . $this->context->company->id);

        if (!$result) {
            return false;
        }

        return true;

    }

    public function cleanPositions($id_category) {

        $categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_category`,  `position` ')
                ->from('category')
                ->where('`id_parent` = ' . (int) $id_category)
                ->orderBy('`position` ASC')
        );
        $k = 1;

        foreach ($categories as $category) {

            $categoryObject = new Category($category['id_category']);

            if (Validate::isLoadedObject($categoryObject)) {

                if (!$this->movePosition($categoryObject->id, $k)) {
                    return false;
                } else {
                    $k++;
                    $childrens = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                        (new DbQuery())
                            ->select('`id_category`, `position`')
                            ->from('category')
                            ->where('`id_parent` = ' . (int) $categoryObject->id)
                            ->orderBy('`position` ASC'));

                    if (!empty($childrens)) {
                        $ka = 1;

                        foreach ($childrens as $children) {
                            $childrenObject = new Category($children['id_category']);

                            if (Validate::isLoadedObject($childrenObject)) {

                                if (!$this->movePosition($childrenObject->id, $ka)) {
                                    return false;
                                } else {

                                    if ($this->cleanPositions($childrenObject->id)) {
                                        $ka++;
                                    } else {
                                        return false;
                                    }

                                }

                            } else {
                                return false;
                            }

                        }

                    }

                }

            } else {
                return false;
            }

        }

        return true;
    }

    public function ajaxProcessCleanPositions() {

        $root = Category::getRootCategory()->id;

        if (!$this->cleanPositions($root)) {
            $this->errors[] = Tools::displayError('A problem occur with cleaning Category position ');
        }

        if (empty($this->errors)) {

            $result = [
                'success' => true,
                'message' => $this->l('The data base position fields has been cleaned'),
            ];
        } else {
            $this->errors = array_unique($this->errors);
            $result = [
                'success' => false,
                'message' => implode(PHP_EOL, $this->errors),
            ];

        }

        die(Tools::jsonEncode($result));
    }

    /**
     * @since 1.8.1.0
     */
    public function ajaxProcessUpdatePositions() {

        $positions = Tools::getValue('positions');
        $idObject = Tools::getvalue('idObject');
        $idParent = Tools::getValue('idParent');
        $stopIndex = Tools::getValue('stopIndex');

        $object = new $this->className($idObject);

        if (Validate::isLoadedObject($object)) {
            $initPosition = $object->position;

            if ($initPosition > $stopIndex) {

                $objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select($this->identifier . ',  `position` ')
                        ->from($this->table)
                        ->where('`id_parent` = ' . (int) $idParent . ' AND `position` >= ' . (int) $stopIndex . ' AND `position` < ' . (int) $initPosition)
                        ->orderBy('`position` ASC')
                );

                if (!empty($objects)) {
                    $k = $stopIndex + 1;

                    foreach ($objects as $moveObject) {

                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '`
                            SET `position`= ' . (int) $k . '
                            WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '_shop`
                            SET `position`= ' . (int) $k . '
                            WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

                        if (!$result) {
                            $this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $moveObject[$this->identifier]);
                        } else {
                            $k++;
                        }

                    }

                    if (empty($this->errors)) {
                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '`
                            SET `position`= ' . (int) $stopIndex . '
                            WHERE `' . $this->identifier . '` =' . (int) $idObject);
                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '_shop`
                            SET `position`= ' . (int) $stopIndex . '
                            WHERE `' . $this->identifier . '` =' . (int) $idObject);

                        if (!$result) {
                            $this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $idObject);
                        }

                    }

                }

            } else

            if ($initPosition < $stopIndex) {

                $objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select($this->identifier . ',  `position` ')
                        ->from($this->table)
                        ->where('`id_parent` = ' . (int) $idParent . ' AND `position` >= ' . (int) $initPosition)
                        ->orderBy('`position` ASC')
                );

                if (!empty($objects)) {
                    $k = $initPosition - 1;

                    foreach ($objects as $moveObject) {

                        if ($moveObject[$this->identifier] == $idObject) {
                            $k++;
                            continue;
                        }

                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '`
                            SET `position`= ' . (int) $k . '
                            WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '_shop`
                            SET `position`= ' . (int) $k . '
                            WHERE `' . $this->identifier . '` =' . (int) $moveObject[$this->identifier]);

                        if (!$result) {
                            $this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $moveObject[$this->identifier]);
                        } else {
                            $k++;
                        }

                    }

                    if (empty($this->errors)) {
                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '`
                            SET `position`= ' . (int) $stopIndex . '
                            WHERE `' . $this->identifier . '` =' . (int) $idObject);

                        $result = Db::getInstance()->execute(
                            'UPDATE `' . _DB_PREFIX_ . $this->table . '_shop`
                            SET `position`= ' . (int) $stopIndex . '
                            WHERE `' . $this->identifier . '` =' . (int) $idObject);

                        if (!$result) {
                            $this->errors[] = Tools::displayError('A problem occur with ' . $this->className . ' position update ' . $idObject);
                        }

                    }

                }

            }

        }

        if (empty($this->errors)) {
            $result = [
                'success' => true,
                'message' => $this->l('Tab position has been successfully updated.'),
            ];
        } else {
            $this->errors = array_unique($this->errors);
            $result = [
                'success' => false,
                'message' => implode(PHP_EOL, $this->errors),
            ];

        }

        die(Tools::jsonEncode($result));

    }

    /**
     * @since 1.8.1.0
     */
    public function ajaxProcessStatusCategory() {

        if (!$idCategory = (int) Tools::getValue('id_category')) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
        } else {
            $category = new Category((int) $idCategory);

            if (Validate::isLoadedObject($category)) {
                $category->active = $category->active == 1 ? 0 : 1;
                $category->save() ?
                $this->ajaxDie(json_encode(['success' => true, 'text' => $this->l('The status has been updated successfully')])) :
                $this->ajaxDie(json_encode(['success' => false, 'error' => true, 'text' => $this->l('Failed to update the status')]));
            }

        }

    }

    /**
     * @since 1.8.1.0
     */
    protected function setDeleteMode() {

        if ($this->delete_mode == 'link' || $this->delete_mode == 'linkanddisable') {
            $this->remove_products = false;

            if ($this->delete_mode == 'linkanddisable') {
                $this->disable_products = true;
            }

        } else

        if ($this->delete_mode != 'delete') {
            $this->errors[] = Tools::displayError('Unknown delete mode:' . ' ' . $this->deleted);
        }

    }

    /**
     * @param int $idParent
     *
     * @since 1.8.1.0
     */
    public function processFatherlessProducts($idParent) {

        /* Delete or link products which were not in others categories */
        $fatherlessProducts = Db::getInstance()->executeS(
            '
            SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p
            WHERE NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp WHERE cp.`id_product` = p.`id_product`)'
        );

        foreach ($fatherlessProducts as $idPoorProduct) {
            $poorProduct = new Product((int) $idPoorProduct['id_product']);

            if (Validate::isLoadedObject($poorProduct)) {

                if ($this->remove_products || $idParent == 0) {
                    $poorProduct->delete();
                } else {

                    if ($this->disable_products) {
                        $poorProduct->active = 0;
                    }

                    $poorProduct->id_category_default = (int) $idParent;
                    $poorProduct->addToCategories((int) $idParent);
                    $poorProduct->save();
                }

            }

        }

    }

    /**
     * @param int $id Category ID
     *
     * @return bool
     *
     * @since 1.8.1.0
     * @throws PhenyxShopException
     */

}
