<?php

/**
 * Class AdminCmsControllerCore
 *
 * @since 1.9.1.0
 */
class AdminCmsControllerCore extends AdminController {

    // @codingStandardsIgnoreStart
    public $id_cms_category;
    protected $category;
    protected $position_identifier = 'id_cms';
    /** @var CMS $object */
    public $object;
    // @codingStandardsIgnoreEnd

    /**
     * AdminCmsControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'cms';
        $this->className = 'CMS';
        $this->lang = true;

        parent::__construct();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderForm() {

        if (!$this->loadObject(true)) {
            return '';
        }

        $this->displayGrid = false;

        if (Validate::isLoadedObject($this->object)) {
            $this->display = 'edit';
        } else {
            $this->display = 'add';
        }

        $categories = CMSCategory::getCategories($this->context->language->id, false);
        $htmlCategories = CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1, $this->getFieldValue($this->object, 'id_cms_category'), 1);

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('CMS Page'),
                'icon'  => 'icon-folder-close',
            ],
            'input'   => [
                // custom template
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'    => 'select_category',
                    'label'   => $this->l('CMS Category'),
                    'name'    => 'id_cms_category',
                    'options' => [
                        'html' => $htmlCategories,
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Meta title'),
                    'name'     => 'meta_title',
                    'id'       => 'name', // for copyMeta2friendlyURL compatibility
                    'lang'     => true,
                    'required' => true,
                    'class'    => 'copyMeta2friendlyURL',
                    'hint'     => $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
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
                    'hint'  => [
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:') . ' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Friendly URL'),
                    'name'     => 'link_rewrite',
                    'required' => true,
                    'lang'     => true,
                    'hint'     => $this->l('Only letters and the hyphen (-) character are allowed.'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Page content'),
                    'name'         => 'content',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'rows'         => 5,
                    'cols'         => 40,
                    'hint'         => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Indexation by search engines'),
                    'name'     => 'indexation',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'indexation_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'indexation_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
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
            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
            'buttons' => [
                'save_and_preview' => [
                    'name'  => 'viewcms',
                    'type'  => 'submit',
                    'title' => $this->l('Save and preview'),
                    'class' => 'btn btn-default pull-right',
                    'icon'  => 'process-icon-preview',
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->form_ajax = 1;

        if ($this->object->id > 0) {
            $this->form_action = 'updateCms';
            $this->editObject = 'Edition d‘une page CMS';
        } else {
            $this->form_action = 'addPageCms';
            $this->editObject = 'Ajouter une nouvelle page CMS';
        }

        $this->tpl_form_vars = [
            'active' => $this->object->active,
            'EPH_ALLOW_ACCENTED_CHARS_URL', (int) Configuration::get('EPH_ALLOW_ACCENTED_CHARS_URL'),
        ];

        return parent::renderForm();
    }

}
