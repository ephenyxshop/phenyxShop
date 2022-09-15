<?php

/**
 * Class Adapter_PackItemsManager
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_PackItemsManager {

    // @codingStandardsIgnoreEnd

    /**
     * Get the Products contained in the given Pack.
     *
     * @param Product  $product
     * @param bool|int $idLang
     *
     * @return array The products contained in this Pack, with special dynamic attributes [pack_quantity, id_pack_product_attribute]
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws Adapter_Exception
     */
    public function getPackItems($product, $idLang = false) {

        if (!static::isPack($product)) {
            return [];
        }

        if ($idLang === false) {
            $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
            $idLang = (int) $configuration->get('EPH_LANG_DEFAULT');
        }

        return Pack::getItems($product->id, $idLang);
    }

    /**
     * Get all Packs that contains the given item in the corresponding declination.
     *
     * @param Product  $item
     * @param int      $itemAttributeId
     * @param int|bool $idLang
     *
     * @return array The packs that contains the given item, with special dynamic attribute [pack_item_quantity]
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws Adapter_Exception
     */
    public function getPacksContainingItem($item, $itemAttributeId, $idLang = false) {

        if ($idLang === false) {
            $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
            $idLang = (int) $configuration->get('EPH_LANG_DEFAULT');
        }

        return Pack::getPacksContainingItem($item->id, $itemAttributeId, $idLang);
    }

    /**
     * Is this product a pack?
     *
     * @param Product $product
     *
     * @return boolean
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function isPack($product) {

        return Pack::isPack($product->id);
    }

    /**
     * Is this product in a pack?
     *
     * If $idProductAttribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param Product  $product
     * @param int|bool $idProductAttribute Optional: combination of the product
     *
     * @return boolean
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function isPacked($product, $idProductAttribute = false) {

        return Pack::isPacked($product->id, $idProductAttribute);
    }

}
