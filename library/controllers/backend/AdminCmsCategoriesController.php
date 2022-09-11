<?php

/**
 * Class AdminCmsCategoriesControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCmsCategoriesControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    /** @var object CMSCategory() instance for navigation */
    protected $cms_category;

    protected $position_identifier = 'id_cms_category_to_move';
    // @codingStandardsIgnoreEnd

    /**
     * AdminCmsCategoriesControllerCore constructor.
     *
     * @since 1.8.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->is_cms = true;
        $this->table = 'cms_category';
        $this->list_id = 'cms_category';
        $this->className = 'CMSCategory';
        $this->publicName = $this->l('CMS Categories');

        parent::__construct();
    }

    /**
     * @return string|null
     *
     * @since 1.8.1.0
     */
    public function renderForm() {

        if (!$this->loadObject(true)) {
            return '';
        }

        $this->display = 'edit';

        if (!$this->loadObject(true)) {
            return null;
        }

        $categories = CMSCategory::getCategories($this->context->language->id, false);
        $htmlCategories = CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1, $this->getFieldValue($this->object, 'id_parent'), 1);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('CMS Category'),
                'icon'  => 'icon-folder-close',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'class'    => 'copyMeta2friendlyURL',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
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
                // custom template
                [
                    'type'    => 'select_category',
                    'label'   => $this->l('Parent CMS Category'),
                    'name'    => 'id_parent',
                    'options' => [
                        'html' => $htmlCategories,
                    ],
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                    'lang'  => true,
                    'rows'  => 5,
                    'cols'  => 40,
                    'hint'  => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta title'),
                    'name'  => 'meta_title',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta description'),
                    'name'  => 'meta_description',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'hint'  => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Friendly URL'),
                    'name'     => 'link_rewrite',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Only letters and the minus (-) character are allowed.'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_value['ajax'] = 1;

        if ($this->id > 0) {
            $this->fields_value['action'] = 'updateCmsCategorie';
            $this->editObject = 'Edition d‘une catégorie CMS';
        } else {
            $this->fields_value['action'] = 'addPageCmsCategorie';
            $this->editObject = 'Ajouter une nouvelle catégorie CMS';
        }

        $this->tpl_form_vars['EPH_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('EPH_ALLOW_ACCENTED_CHARS_URL');

        return parent::renderForm();
    }

}
