<?php

/**
 * Class AdminContactsControllerCore
 *
 * @since 1.9.1.0
 */
class AdminContactsControllerCore extends AdminController {

    /**
     * AdminContactsControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'contact';
        $this->className = 'Contact';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_contact'  => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'        => ['title' => $this->l('Title')],
            'email'       => ['title' => $this->l('Email address')],
            'description' => ['title' => $this->l('Description')],
        ];

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

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Contacts'),
                'icon'  => 'icon-envelope-alt',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'col'      => 4,
                    'hint'     => $this->l('Contact name (e.g. Customer Support).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Email address'),
                    'name'     => 'email',
                    'required' => false,
                    'col'      => 4,
                    'hint'     => $this->l('Emails will be sent to this address.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Save messages?'),
                    'name'     => 'customer_service',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('If enabled, all messages will be saved in the "Customer Service" page under the "Customer" menu.'),
                    'values'   => [
                        [
                            'id'    => 'customer_service_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'customer_service_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Description'),
                    'name'     => 'description',
                    'required' => false,
                    'lang'     => true,
                    'col'      => 6,
                    'hint'     => $this->l('Further information regarding this contact.'),
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

        return parent::renderForm();
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @since 1.0.4
     */
    public function getList(
        $idLang,
        $orderBy = null,
        $orderWay = null,
        $start = 0,
        $limit = null,
        $idLangShop = false
    ) {

        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        foreach ($this->_list as &$row) {
            $row['email'] = Tools::convertEmailFromIdn($row['email']);
        }

    }

    /**
     * Return the list of fields value
     *
     * @param ObjectModel $obj Object
     *
     * @return array
     *
     * @since 1.0.4
     */
    public function getFieldsValue($obj) {

        $fieldsValue = parent::getFieldsValue($obj);
        $fieldsValue['email'] = Tools::convertEmailFromIdn($fieldsValue['email']);

        return $fieldsValue;
    }

}
