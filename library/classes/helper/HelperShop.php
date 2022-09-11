<?php

/**
 * Class HelperShopCore
 *
 * @since 1.8.1.0
 */
class HelperShopCore extends Helper {

    /**
     * Render shop list
     *
     * @return string
     *
     * @throws Exception
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function getRenderedShopList() {

        if (!Shop::isFeatureActive() || Shop::getTotalShops(false, null) < 2) {
            return '';
        }

        $shopContext = Shop::getContext();
        $context = Context::getContext();
        $tree = Shop::getTree();

        if ($shopContext == Shop::CONTEXT_ALL || ($context->controller->multishop_context_group == false && $shopContext == Shop::CONTEXT_GROUP)) {
            $currentShopValue = '';
            $currentShopName = Translate::getAdminTranslation('All shops');
        } else
        if ($shopContext == Shop::CONTEXT_GROUP) {
            $currentShopValue = 'g-' . Shop::getContextShopGroupID();
            $currentShopName = sprintf(Translate::getAdminTranslation('%s group'), $tree[Shop::getContextShopGroupID()]['name']);
        } else {
            $currentShopValue = 's-' . Shop::getContextShopID();

            foreach ($tree as $groupId => $groupData) {

                foreach ($groupData['shops'] as $shopId => $shopData) {

                    if ($shopId == Shop::getContextShopID()) {
                        $currentShopName = $shopData['name'];
                        break;
                    }

                }

            }

        }

        $tpl = $this->createTemplate('helpers/shops_list/list.tpl');
        $tpl->assign(
            [
                'tree'                    => $tree,
                'current_shop_name'       => $currentShopName,
                'current_shop_value'      => $currentShopValue,
                'multishop_context'       => $context->controller->multishop_context,
                'multishop_context_group' => $context->controller->multishop_context_group,
                'is_shop_context'         => ($context->controller->multishop_context & Shop::CONTEXT_SHOP),
                'is_group_context'        => ($context->controller->multishop_context & Shop::CONTEXT_GROUP),
                'shop_context'            => $shopContext,
                'url'                     => $_SERVER['REQUEST_URI'] . (($_SERVER['QUERY_STRING']) ? '&' : '?') . 'setShopContext=',
            ]
        );

        return $tpl->fetch();
    }

}
