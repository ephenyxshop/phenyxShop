<?php

/**
 * Class DiscountControllerCore
 *
 * @since 1.8.1.0
 */
class DiscountControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'discount';
    /** @var string $authRedirection */
    public $authRedirection = 'discount';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        $cartRules = CartRule::getCustomerCartRules(
            $this->context->language->id,
            $this->context->customer->id,
            true,
            false,
            true,
            null,
            false,
            true
        );

        foreach ($cartRules as $key => $discount) {

            if ($discount['quantity_for_user'] === 0) {
                unset($cartRules[$key]);

                continue;
            }

            if (!empty($discount['value'])) {
                $cartRules[$key]['value'] = Tools::convertPriceFull(
                    $discount['value'],
                    new Currency((int) $discount['reduction_currency']),
                    new Currency((int) $this->context->cart->id_currency)
                );
            }

            if ((int) $discount['gift_product'] !== 0) {
                $product = new Product((int) $discount['gift_product'], false, (int) $this->context->language->id);

                if (!Validate::isLoadedObject($product) || !$product->isAssociatedToShop() || !$product->active) {
                    unset($cartRules[$key]);
                }

                if (Combination::isFeatureActive() && (int) $discount['gift_product_attribute'] !== 0) {
                    $attributes = $product->getAttributeCombinationsById(
                        (int) $discount['gift_product_attribute'],
                        (int) $this->context->language->id
                    );
                    $giftAttributes = [];

                    foreach ($attributes as $attribute) {
                        $giftAttributes[] = $attribute['group_name'] . ' : ' . $attribute['attribute_name'];
                    }

                    $cartRules[$key]['gift_product_attributes'] = implode(', ', $giftAttributes);
                }

                $cartRules[$key]['gift_product_name'] = $product->name;
                $cartRules[$key]['gift_product_link'] = $this->context->link->getProductLink(
                    $product,
                    $product->link_rewrite,
                    $product->category,
                    $product->ean13,
                    $this->context->language->id,
                    $this->context->company->id,
                    $discount['gift_product_attribute'],
                    false,
                    false,
                    true
                );
            }

        }

        $nbCartRules = count($cartRules);

        $this->context->smarty->assign(
            [
                'nb_cart_rules' => (int) $nbCartRules,
                'cart_rules'    => $cartRules,
                'discount'      => $cartRules, // retro compat
                'nbDiscounts'   => (int) $nbCartRules, // retro compat
            ]
        );
        $this->setTemplate(_EPH_THEME_DIR_ . 'discount.tpl');
    }

}
