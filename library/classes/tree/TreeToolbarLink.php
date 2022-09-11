<?php
/**
 * 2007-2016 PhenyxShop
 *
 * ephenyx is an extension to the PhenyxShop e-commerce software developed by PhenyxShop SA
 * Copyright (C) 2017-2018 ephenyx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@ephenyx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PhenyxShop to newer
 * versions in the future. If you wish to customize PhenyxShop for your
 * needs please refer to https://www.ephenyx.com for more information.
 *
 *  @author    ephenyx <contact@ephenyx.com>
 *  @author    PhenyxShop SA <contact@PhenyxShop.com>
 *  @copyright 2017-2020 ephenyx
 *  @copyright 2007-2016 PhenyxShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PhenyxShop is an internationally registered trademark & property of PhenyxShop SA
 */

/**
 * Class TreeToolbarLinkCore
 *
 * @since 1.9.1.0
 */
class TreeToolbarLinkCore extends TreeToolbarButtonCore implements
    ITreeToolbarButtonCore
{
    // @codingStandardsIgnoreStart
    private $_action;
    private $_icon_class;
    private $_link;
    protected $_template = 'tree_toolbar_link.tpl';
    // @codingStandardsIgnoreEnd

    /**
     * TreeToolbarLinkCore constructor.
     *
     * @param      $label
     * @param null $link
     * @param null $action
     * @param null $iconClass
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($label, $link, $action = null, $iconClass = null)
    {
        parent::__construct($label);

        $this->setLink($link);
        $this->setAction($action);
        $this->setIconClass($iconClass);
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setAction($value)
    {
        return $this->setAttribute('action', $value);
    }

    /**
     * @return null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAction()
    {
        return $this->getAttribute('action');
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setIconClass($value)
    {
        return $this->setAttribute('icon_class', $value);
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getIconClass()
    {
        return $this->getAttribute('icon_class');
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setLink($value)
    {
        return $this->setAttribute('link', $value);
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getLink()
    {
        return $this->getAttribute('link');
    }
}
