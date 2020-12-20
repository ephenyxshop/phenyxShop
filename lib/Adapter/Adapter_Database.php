<?php
/*
* 2018-2020 Ephenyx Digital LTD
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PhenyxShop to newer
* versions in the future. If you wish to customize PhenyxShop for your
* needs please refer to http://ephenyx.com for more information.
*
*  @author Ephenyx Digital LTD <contact@ephenyx.com>
*  @copyright  2018-2020 Pphenyx Digital LTD
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of phenyx Digital LTD
*/

/**
 * Class Adapter_Database
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_Database implements Core_Foundation_Database_DatabaseInterface
{
    // @codingStandardsIgnoreEnd

    /**
     * Perform a SELECT sql statement
     *
     * @param string $sql
     *
     * @return array|false
     *
     * @throws PhenyxShopDatabaseException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function select($sql)
    {
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Escape $unsafe to be used into a SQL statement
     *
     * @param mixed $unsafeData
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function escape($unsafeData)
    {
        // Prepare required params
        $htmlOk = true;
        $bqSql = true;

        return Db::getInstance()->escape($unsafeData, $htmlOk, $bqSql);
    }
}
