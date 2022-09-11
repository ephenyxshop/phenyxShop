<?php


/**
 * Class TaxManagerFactoryCore
 *
 * @since 1.9.1.0
 */
class TaxManagerFactoryCore
{
    protected static $cache_tax_manager;

    /**
     * Returns a tax manager able to handle this address
     *
     * @param Address $address
     * @param string  $type
     *
     * @return TaxManagerInterface
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getManager(Address $address, $type)
    {
        $cacheId = TaxManagerFactory::getCacheKey($address).'-'.$type;
        if (!isset(TaxManagerFactory::$cache_tax_manager[$cacheId])) {
            $taxManager = TaxManagerFactory::execHookTaxManagerFactory($address, $type);
            if (!($taxManager instanceof TaxManagerInterface)) {
                $taxManager = new TaxRulesTaxManager($address, $type);
            }

            TaxManagerFactory::$cache_tax_manager[$cacheId] = $taxManager;
        }

        return TaxManagerFactory::$cache_tax_manager[$cacheId];
    }

    /**
     * Check for a tax manager able to handle this type of address in the module list
     *
     * @param Address $address
     * @param string  $type
     *
     * @return TaxManagerInterface|false
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function execHookTaxManagerFactory(Address $address, $type)
    {
        $modulesInfos = Hook::getModulesFromHook(Hook::getIdByName('taxManager'));
        $taxManager = false;

        foreach ($modulesInfos as $moduleInfos) {
            $moduleInstance = Module::getInstanceByName($moduleInfos['name']);
            if (is_callable([$moduleInstance, 'hookTaxManager'])) {
                $taxManager = $moduleInstance->hookTaxManager(
                    [
                        'address' => $address,
                        'params'  => $type,
                    ]
                );
            }

            if ($taxManager) {
                break;
            }
        }

        return $taxManager;
    }

    /**
     * Create a unique identifier for the address
     *
     * @param Address
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @return string
     */
    protected static function getCacheKey(Address $address)
    {
        return $address->id_country.'-'
            .(int) $address->id_state.'-'
            .$address->postcode.'-'
            .$address->vat_number.'-'
            .$address->dni;
    }
}
