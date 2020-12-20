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
 * Class Adapter_ProductPriceCalculator
 */
// @codingStandardIgnoreStart
class Adapter_ProductPriceCalculator
{
    // @codingStandardIgnoreEnd

    /**
     * @param int          $idProduct
     * @param bool         $usetax
     * @param null         $idProductAttribute
     * @param int          $decimals
     * @param null         $divisor
     * @param bool         $onlyReduc
     * @param bool         $usereduc
     * @param int          $quantity
     * @param bool         $forceAssociatedTax
     * @param null         $idCustomer
     * @param null         $idCart
     * @param null         $idAddress
     * @param null         $specificPriceOutput
     * @param bool         $withEcotax
     * @param bool         $useGroupReduction
     * @param Context|null $context
     * @param bool         $useCustomerPrice
     *
     * @return float
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getProductPrice(
        $idProduct,
        $usetax = true,
        $idProductAttribute = null,
        $decimals = 6,
        $divisor = null,
        $onlyReduc = false,
        $usereduc = true,
        $quantity = 1,
        $forceAssociatedTax = false,
        $idCustomer = null,
        $idCart = null,
        $idAddress = null,
        &$specificPriceOutput = null,
        $withEcotax = true,
        $useGroupReduction = true,
        Context $context = null,
        $useCustomerPrice = true
    ) {
        return Product::getPriceStatic(
            $idProduct,
            $usetax,
            $idProductAttribute,
            $decimals,
            $divisor,
            $onlyReduc,
            $usereduc,
            $quantity,
            $forceAssociatedTax,
            $idCustomer,
            $idCart,
            $idAddress,
            $specificPriceOutput,
            $withEcotax,
            $useGroupReduction,
            $context,
            $useCustomerPrice
        );
    }
}
