<?php

/**
 * Class HelperViewCore
 *
 * @since 1.8.1.0
 */
class HelperViewCore extends Helper {

    public $id;
    public $toolbar = true;
    public $table;
    public $token;

    /** @var string|null If not null, a title will be added on that list */
    public $title = null;

    /**
     * HelperViewCore constructor.
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct() {

        $this->base_folder = 'helpers/view/';
        $this->base_tpl = 'view.tpl';
        parent::__construct();
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function generateView() {

        $this->tpl = $this->createTemplate($this->base_tpl);

        $this->tpl->assign(
            [
                'title'          => $this->title,
                'current'        => $this->currentIndex,
                'token'          => $this->token,
                'table'          => $this->table,
                'show_toolbar'   => $this->show_toolbar,
                'toolbar_scroll' => $this->toolbar_scroll,
                'toolbar_btn'    => $this->toolbar_btn,
                'link'           => $this->context->link,
            ]
        );

        return parent::generate();
    }
}
