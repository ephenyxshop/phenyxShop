<?php

/**
 * Class Adapter_StockManager
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_StockManager {

    // @codingStandardsIgnoreEnd

    /**
     * @param Product $product
     * @param null    $idProductAttribute
     * @param null    $idCompany
     *
     * @return StockAvailable
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getStockAvailableByProduct($product, $idProductAttribute = null) {

        return new StockAvailable(StockAvailable::getStockAvailableIdByProductId($product->id, $idProductAttribute));
    }
}
