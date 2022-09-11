<?php


/**
 * Class TaxRulesTaxManagerCore
 *
 * @since 1.9.1.0
 */
class TaxRulesTaxManagerCore implements TaxManagerInterface
{
    // @codingStandardsIgnoreStart
    public $address;
    public $type;
    public $tax_calculator;
    // @codingStandardsIgnoreEnd

    /**
     * @var Core_Business_ConfigurationInterface
     */
    private $configurationManager;

    /**
     *
     * @param Address $address
     * @param mixed   $type An additional parameter for the tax manager (ex: tax rules id for TaxRuleTaxManager)
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws Adapter_Exception
     */
    public function __construct(Address $address, $type, Core_Business_ConfigurationInterface $configurationManager = null)
    {
        if ($configurationManager === null) {
            $this->configurationManager = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
        } else {
            $this->configurationManager = $configurationManager;
        }

        $this->address = $address;
        $this->type = $type;
    }

    /**
     * Returns true if this tax manager is available for this address
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function isAvailableForThisAddress(Address $address)
    {
        return true; // default manager, available for all addresses
    }

    /**
     * Return the tax calculator associated to this address
     *
     * @return TaxCalculator
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTaxCalculator()
    {
        static $taxEnabled = null;

        if (isset($this->tax_calculator)) {
            return $this->tax_calculator;
        }

        if ($taxEnabled === null) {
            $taxEnabled = $this->configurationManager->get('PS_TAX');
        }

        if (!$taxEnabled) {
            return new TaxCalculator([]);
        }

        $taxes = [];
        $postcode = 0;

        if (!empty($this->address->postcode)) {
            $postcode = $this->address->postcode;
        }

        $cacheId = (int) $this->address->id_country.'-'.(int) $this->address->id_state.'-'.$postcode.'-'.(int) $this->type;

        if (!Cache::isStored($cacheId)) {
            $rows = Db::getInstance()->executeS(
                '
				SELECT tr.*
				FROM `'._DB_PREFIX_.'tax_rule` tr
				JOIN `'._DB_PREFIX_.'tax_rules_group` trg ON (tr.`id_tax_rules_group` = trg.`id_tax_rules_group`)
				WHERE trg.`active` = 1
				AND tr.`id_country` = '.(int) $this->address->id_country.'
				AND tr.`id_tax_rules_group` = '.(int) $this->type.'
				AND tr.`id_state` IN (0, '.(int) $this->address->id_state.')
				AND (\''.pSQL($postcode).'\' BETWEEN tr.`zipcode_from` AND tr.`zipcode_to`
					OR (tr.`zipcode_to` = 0 AND tr.`zipcode_from` IN(0, \''.pSQL($postcode).'\')))
				ORDER BY tr.`zipcode_from` DESC, tr.`zipcode_to` DESC, tr.`id_state` DESC, tr.`id_country` DESC'
            );

            $behavior = 0;
            $firstRow = true;

            foreach ($rows as $row) {
                $tax = new Tax((int) $row['id_tax']);

                $taxes[] = $tax;

                // the applied behavior correspond to the most specific rules
                if ($firstRow) {
                    $behavior = $row['behavior'];
                    $firstRow = false;
                }

                if ($row['behavior'] == 0) {
                    break;
                }
            }
            $result = new TaxCalculator($taxes, $behavior);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }
}
