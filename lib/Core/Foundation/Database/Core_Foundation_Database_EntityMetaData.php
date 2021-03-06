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
*//**
 * Class Core_Foundation_Database_EntityMetaData
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Core_Foundation_Database_EntityMetaData
{
    // @codingStandardsIgnoreEnd

    protected $tableName;
    protected $primaryKeyFieldnames;

    /**
     * @param string $name
     *
     * @return Core_Foundation_Database_EntityMetaData $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setTableName($name)
    {
        $this->tableName = $name;

        return $this;
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param array $primaryKeyFieldnames
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setPrimaryKeyFieldNames(array $primaryKeyFieldnames)
    {
        $this->primaryKeyFieldnames = $primaryKeyFieldnames;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getPrimaryKeyFieldnames()
    {
        return $this->primaryKeyFieldnames;
    }

    /**
     * @param string $entityClassName
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entityClassName = $entityClassName;

        return $this;
    }

    /**
     * @return mixed
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }
}
