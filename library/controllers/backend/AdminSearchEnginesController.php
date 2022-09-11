<?php

/**
 * Class AdminSearchEnginesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminSearchEnginesControllerCore extends AdminController {

    /**
     * AdminSearchEnginesControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'search_engine';
        $this->className = 'SearchEngine';
        $this->lang = false;

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
            'id_search_engine' => ['title' => $this->l('ID'), 'width' => 25],
            'server'           => ['title' => $this->l('Server')],
            'getvar'           => ['title' => $this->l('GET variable'), 'width' => 100],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Referrer'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Server'),
                    'name'     => 'server',
                    'size'     => 20,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('$_GET variable'),
                    'name'     => 'getvar',
                    'size'     => 40,
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
     * @since 1.9.1.0
     */
    public function initPageHeaderToolbar() {

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_search_engine'] = [
                'href' => static::$currentIndex . '&addsearch_engine&token=' . $this->token,
                'desc' => $this->l('Add new search engine', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        $this->identifier_name = 'server';

        parent::initPageHeaderToolbar();
    }

}
