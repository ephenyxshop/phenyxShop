<?php

/**
 * Class HelperTreeShopsCore
 *
 * @since 1.8.1.0
 */
class HelperTreeShopsCore extends TreeCore {

    const DEFAULT_TEMPLATE = 'tree_shops.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_checkbox_shops.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_checkbox_shops.tpl';

    // @codingStandardsIgnoreStart
    protected $_lang;
    protected $_selected_shops;
    // @codingStandardsIgnoreEnd

    /**
     * HelperTreeShopsCore constructor.
     *
     * @param int  $id
     * @param null $title
     * @param null $lang
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function __construct($id, $title = null, $lang = null) {

        parent::__construct($id);

        if (isset($title)) {
            $this->setTitle($title);
        }

        $this->setLang($lang);
    }

    /**
     * @return mixed
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function getData() {

        if (!isset($this->_data)) {
            $this->setData(Shop::getTree());
        }

        return $this->_data;
    }

    /**
     * @param int $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setLang($value) {

        $this->_lang = $value;

        return $this;
    }

    /**
     * @return int
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getLang() {

        if (!isset($this->_lang)) {
            $this->setLang($this->getContext()->employee->id_lang);
        }

        return $this->_lang;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getNodeFolderTemplate() {

        if (!isset($this->_node_folder_template)) {
            $this->setNodeFolderTemplate(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }

        return $this->_node_folder_template;
    }

    /**
     * @return mixed
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getNodeItemTemplate() {

        if (!isset($this->_node_item_template)) {
            $this->setNodeItemTemplate(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }

        return $this->_node_item_template;
    }

    /**
     * @param int[] $value
     *
     * @return $this
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setSelectedShops($value) {

        if (!is_array($value)) {
            throw new PhenyxShopException('Selected shops value must be an array');
        }

        $this->_selected_shops = $value;

        return $this;
    }

    /**
     * @return int[]
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getSelectedShops() {

        if (!isset($this->_selected_shops)) {
            $this->_selected_shops = [];
        }

        return $this->_selected_shops;
    }

    /**
     * @return string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getTemplate() {

        if (!isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_TEMPLATE);
        }

        return $this->_template;
    }

    /**
     * @param null $data
     * @param bool $useDefaultActions
     * @param bool $useSelectedShop
     *
     * @return string
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     * @throws PhenyxShopException
     */
    public function render($data = null, $useDefaultActions = true, $useSelectedShop = true) {

        if (!isset($data)) {
            $data = $this->getData();
        }

        if ($useDefaultActions) {
            $this->setActions(
                [
                    new TreeToolbarLink(
                        'Collapse All',
                        '#',
                        '$(\'#' . $this->getId() . '\').tree(\'collapseAll\'); return false;',
                        'icon-collapse-alt'
                    ),
                    new TreeToolbarLink(
                        'Expand All',
                        '#',
                        '$(\'#' . $this->getId() . '\').tree(\'expandAll\'); return false;',
                        'icon-expand-alt'
                    ),
                    new TreeToolbarLink(
                        'Check All',
                        '#',
                        'checkAllAssociatedShops($(\'#' . $this->getId() . '\')); return false;',
                        'icon-check-sign'
                    ),
                    new TreeToolbarLink(
                        'Uncheck All',
                        '#',
                        'uncheckAllAssociatedShops($(\'#' . $this->getId() . '\')); return false;',
                        'icon-check-empty'
                    ),
                ]
            );
        }

        if ($useSelectedShop) {
            $this->setAttribute('selected_shops', $this->getSelectedShops());
        }

        return parent::render($data);
    }

    /**
     * @param null $data
     *
     * @return string
     * @throws PhenyxShopException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function renderNodes($data = null) {

        if (!isset($data)) {
            $data = $this->getData();
        }

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PhenyxShopException('Data value must be an traversable array');
        }

        $html = '';

        foreach ($data as $item) {

            if (array_key_exists('shops', $item)
                && !empty($item['shops'])) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(
                    [
                        'children' => $this->renderNodes($item['shops']),
                        'node'     => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(
                    [
                        'node' => $item,
                    ]
                )->fetch();
            }

        }

        return $html;
    }

}
