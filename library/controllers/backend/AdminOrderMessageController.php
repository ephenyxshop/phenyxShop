<?php

/**
 * Class AdminOrderMessageControllerCore
 *
 * @since 1.9.1.0
 */
class AdminOrderMessageControllerCore extends AdminController {

    /**
     * AdminOrderMessageControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'order_message';
        $this->className = 'OrderMessage';
        $this->lang = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->fields_list = [
            'id_order_message' => [
                'title' => $this->l('ID'),
                'align' => 'center',
            ],
            'name'             => [
                'title' => $this->l('Name'),
            ],
            'message'          => [
                'title'     => $this->l('Message'),
                'maxlength' => 300,
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Order messages'),
                'icon'  => 'icon-mail',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'lang'     => true,
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'size'     => 53,
                    'required' => true,
                ],
                [
                    'type'     => 'textarea',
                    'lang'     => true,
                    'label'    => $this->l('Message'),
                    'name'     => 'message',
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order_message'] = [
                'href' => static::$currentIndex . '&addorder_message&token=' . $this->token,
                'desc' => $this->l('Add new order message'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

}
