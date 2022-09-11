<?php


/**
 * Class TaxCalculatorCore
 *
 * @since 1.9.1.0
 */
class TaxCalculatorCore
{
    /**
     * COMBINE_METHOD sum taxes
     * eg: 100€ * (10% + 15%)
     */
    const COMBINE_METHOD = 1;

    /**
     * ONE_AFTER_ANOTHER_METHOD apply taxes one after another
     * eg: (100€ * 10%) * 15%
     */
    const ONE_AFTER_ANOTHER_METHOD = 2;

    // @codingStandardsIgnoreStart
    /**
     * @var array $taxes
     */
    public $taxes;

    /**
     * @var int $computation_method (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     */
    public $computation_method;
    // @codingStandardsIgnoreEnd

    /**
     * @param array $taxes
     * @param int   $computationMethod (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     *
     * @throws Exception
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct(array $taxes = [], $computationMethod = self::COMBINE_METHOD)
    {
        // sanity check
        foreach ($taxes as $tax) {
            if (!($tax instanceof Tax)) {
                throw new Exception('Invalid Tax Object');
            }
        }

        $this->taxes = $taxes;
        $this->computation_method = (int) $computationMethod;
    }

    /**
     * Compute and add the taxes to the specified price
     *
     * @param float $priceTaxExcluded price tax excluded
     *
     * @return float price with taxes
     */
    public function addTaxes($priceTaxExcluded)
    {
        return $priceTaxExcluded * (1 + ($this->getTotalRate() / 100));
    }

    /**
     * Compute and remove the taxes to the specified price
     *
     * @param float $priceTaxIncluded price tax inclusive
     *
     * @return float price without taxes
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function removeTaxes($priceTaxIncluded)
    {
        return $priceTaxIncluded / (1 + $this->getTotalRate() / 100);
    }

    /**
     * @return float total taxes rate
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTotalRate()
    {
        $taxes = 0;
        if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
            $taxes = 1;
            foreach ($this->taxes as $tax) {
                $taxes *= (1 + (abs($tax->rate) / 100));
            }

            $taxes = $taxes - 1;
            $taxes = $taxes * 100;
        } else {
            foreach ($this->taxes as $tax) {
                $taxes += abs($tax->rate);
            }
        }

        return (float) $taxes;
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTaxesName()
    {
        $name = '';
        foreach ($this->taxes as $tax) {
            $name .= $tax->name[(int) Context::getContext()->language->id].' - ';
        }

        $name = rtrim($name, ' - ');

        return $name;
    }

    /**
     * Return the tax amount associated to each taxes of the TaxCalculator
     *
     * @param float $priceTaxExcluded
     *
     * @return array $taxes_amount
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTaxesAmount($priceTaxExcluded)
    {
        $taxesAmounts = [];

        foreach ($this->taxes as $tax) {
            if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
                $taxesAmounts[$tax->id] = $priceTaxExcluded * (abs($tax->rate) / 100);
                $priceTaxExcluded = $priceTaxExcluded + $taxesAmounts[$tax->id];
            } else {
                $taxesAmounts[$tax->id] = ($priceTaxExcluded * (abs($tax->rate) / 100));
            }
        }

        return $taxesAmounts;
    }

    /**
     * Return the total taxes amount
     *
     * @param float $priceTaxExcluded
     *
     * @return float $amount
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getTaxesTotalAmount($priceTaxExcluded)
    {
        $amount = 0;

        $taxes = $this->getTaxesAmount($priceTaxExcluded);
        foreach ($taxes as $tax) {
            $amount += $tax;
        }

        return $amount;
    }
}
