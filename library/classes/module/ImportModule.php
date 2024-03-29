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
 * Class ImportModuleCore
 *
 * @since 1.9.1.0
 *
 * @deprecated 1.0.2 Even though this class still exists in ephenyx, it cannot be used in the
 *             same way as on PhenyxShop 1.6, because ephenyx does not support the older
 *             mysql/mysqli ways of connecting with the database. Everything is modernized to
 *             support only the PDO mysql PHP extension. If your module does extend this class
 *             make sure you refactor everything to directly use the `Db` class instead of the
 *             methods of this class.
 */
abstract class ImportModuleCore extends Module
{
    // @codingStandardsIgnoreStart
    protected $_link = null;

    public $server;

    public $user;

    public $passwd;

    public $database;

    /** @var string Prefix database */
    public $prefix;
    // @codingStandardsIgnoreEnd

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __destruct()
    {
    }

    /**
     * @return null|PDO
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @since 1.0.2 Return a PDO instead of a MySQL resource, because ephenyx only support MySQL PDO
     */
    protected function initDatabaseConnection()
    {
        return Db::getInstance()->getLink();
    }

    /**
     * @param string|DbQuery $query
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function executeS($query)
    {
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * @param string|DbQuery $query
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @since 1.0.2 Just return a bool instead of a mysql resource, because ephenyx only
     *        supports MySQL PDO.
     */
    public function execute($query)
    {
        return (bool) Db::getInstance()->execute($query);
    }

    /**
     * @param string|DbQuery $query
     *
     * @return int|mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getValue($query)
    {
        $this->initDatabaseConnection();
        $result = $this->executeS($query);
        if (!count($result)) {
            return 0;
        } else {
            return array_shift($result[0]);
        }
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getImportModulesOnDisk()
    {
        $modules = Module::getModulesOnDisk(true);
        foreach ($modules as $key => $module) {
            if (!isset($module->parent_class) || $module->parent_class != 'ImportModule') {
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function getDefaultIdLang();
}
