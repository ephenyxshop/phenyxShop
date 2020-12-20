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
 * Class Adapter_AddressFactory
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_AddressFactory
{
    // @codingStandardsIgnoreEnd

    /**
     * Initialize an address corresponding to the specified id address or if empty to the
     * default shop configuration
     *
     * @param null $idAddress       Address ID
     * @param bool $withGeoLocation Indicates whether Geo location is used
     *
     * @return Address
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function findOrCreate($idAddress = null, $withGeoLocation = false)
    {
        $funcArgs = func_get_args();

        return call_user_func_array(['Address', 'initialize'], $funcArgs);
    }

    /**
     * Check if an address exists depending on given $idAddress
     *
     * @param int $idAddress Address ID
     *
     * @return bool Indicates whether the address exists
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function addressExists($idAddress)
    {
        return Address::addressExists($idAddress);
    }
}
