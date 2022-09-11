<?php

/**
 * Class ProductSaleCore
 *
 * @since 1.9.1.0
 */
class ProductSaleCore {

    /**
     * Fill the `product_sale` SQL table with data from `order_detail`
     *
     * @return bool True on success
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function fillProductSales() {

        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'product_sale
                (`id_product`, `quantity`, `sale_nbr`, `date_upd`)
                SELECT od.product_id, SUM(od.product_quantity), COUNT(od.product_id), NOW()
                            FROM ' . _DB_PREFIX_ . 'order_detail od GROUP BY od.product_id';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get number of actives products sold
     *
     * @return int number of actives products listed in product_sales
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getNbSales() {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(ps.`id_product`) AS `nb`')
                ->from('product_sale', 'ps')
                ->leftJoin('product', 'p', 'p.`id_product` = ps.`id_product`')
                ->where('p.`active` = 1')
        );
    }

    /**
     * Get required informations on best sales products
     *
     * @param int         $idLang     Language id
     * @param int         $pageNumber Start from (optional)
     * @param int         $nbProducts Number of products to return (optional)
     * @param string|null $orderBy
     * @param string|null $orderWay
     *
     * @return false| array from Product::getProductProperties
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getBestSales($idLang, $pageNumber = 0, $nbProducts = 10, $orderBy = null, $orderWay = null) {

        $context = Context::getContext();

        if ($pageNumber < 0) {
            $pageNumber = 0;
        }

        if ($nbProducts < 1) {
            $nbProducts = 10;
        }

        $finalOrderBy = $orderBy;
        $orderTable = '';

        if (is_null($orderBy)) {
            $orderBy = 'quantity';
            $orderTable = 'ps';
        }

        if ($orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderTable = 'p';
        }

        if (is_null($orderWay) || $orderBy == 'sales') {
            $orderWay = 'DESC';
        }

        $interval = Validate::isUnsignedInt(Configuration::get('EPH_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('EPH_NB_DAYS_NEW_PRODUCT') : 20;

        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = (new DbQuery())
            ->select('p.*,  stock.`out_of_stock`, IFNULL(stock.quantity, 0) as quantity')
            ->select(Combination::isFeatureActive() ? 'pa.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(pa.id_product_attribute,0) id_product_attribute' : '')
            ->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`')
            ->select('pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`')
            ->select('m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer')
            ->select('i.`id_image` id_image, il.`legend`')
            ->select('ps.`quantity` AS sales, t.`rate`, pl.`meta_keywords`, pl.`meta_title`, pl.`meta_description`')
            ->select('DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00"')
            ->select('INTERVAL ' . (int) $interval . ' DAY)) > 0 AS new')
            ->from('product_sale', 'ps')
            ->leftJoin('product', 'p', 'ps.`id_product` = p.`id_product`')
            ->join(Combination::isFeatureActive() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (p.`id_product` = pa.`id_product` AND pa.`default_on` = 1)' : '')
            ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')
            ->leftJoin('image', 'i', 'i.`id_product` = p.`id_product` AND i.`cover` = 1')
            ->leftJoin('image_lang', 'il', 'i.`id_image` = il.`id_image`')
            ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->leftJoin('tax_rule', 'tr', 'p.`id_tax_rules_group` = tr.`id_tax_rules_group` AND tr.`id_country` = ' . (int) $context->country->id . ' AND tr.`id_state` = 0')
            ->leftJoin('tax', 't', 't.`id_tax` = tr.`id_tax` ' . Product::sqlStock('p', 0))
            ->where('pl.`id_lang` = ' . (int) $idLang )
            ->where('il.`id_lang` = ' . (int) $idLang)
            ->where('p.`active` = 1')
            ->where('p.`visibility` != \'none\'')
            ->where('EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count(FrontController::getCurrentCustomerGroups()) ? 'IN (' . implode(',', FrontController::getCurrentCustomerGroups()) . ')' : '= 1') . ') WHERE cp.`id_product` = p.`id_product`)');

        if ($finalOrderBy != 'price') {
            $sql->orderBy((!empty($orderTable) ? '`' . pSQL($orderTable) . '`.' : '') . '`' . pSQL($orderBy) . '` ' . pSQL($orderWay));
            $sql->limit((int) $nbProducts, (int) ($pageNumber * $nbProducts));
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        if ($finalOrderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        if (!$result) {
            return false;
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * Get required informations on best sales products
     *
     * @param int $idLang     Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     *
     * @return array keys : id_product, link_rewrite, name, id_image, legend, sales, ean13, upc, link
     */
    public static function getBestSalesLight($idLang, $pageNumber = 0, $nbProducts = 10, Context $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        if ($pageNumber < 0) {
            $pageNumber = 0;
        }

        if ($nbProducts < 1) {
            $nbProducts = 10;
        }

        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = '
        SELECT
            p.id_product, IFNULL(pa.id_product_attribute,0) id_product_attribute, pl.`link_rewrite`, pl.`name`, pl.`description_short`, p.`id_category_default`,
            i.`id_image` id_image, il.`legend`,
            ps.`quantity` AS sales, p.`ean13`, p.`upc`, cl.`link_rewrite` AS category, p.show_price, p.available_for_order, IFNULL(stock.quantity, 0) as quantity, p.customizable,
            IFNULL(pa.minimal_quantity, p.minimal_quantity) as minimal_quantity, stock.out_of_stock,
            p.`date_add` > "' . date('Y-m-d', strtotime('-' . (Configuration::get('EPH_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('EPH_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY')) . '" as new,
            p.`on_sale`, pa.minimal_quantity AS product_attribute_minimal_quantity
        FROM `' . _DB_PREFIX_ . 'product_sale` ps
        LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON ps.`id_product` = p.`id_product`
        LEFT JOIN `' . _DB_PREFIX_ . 'pa` pa
            ON (p.`id_product` = pa.`id_product` AND pa.`default_on` = 1)
        LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.id_product_attribute=pa.id_product_attribute)
        LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
            ON p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = ' . (int) $idLang .  '
        LEFT JOIN `' . _DB_PREFIX_ . 'image` i
            ON (i.`id_product` = p.`id_product` AND i.cover=1)
        LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $idLang . ')
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
            ON cl.`id_category` = p.`id_category_default`
            AND cl.`id_lang` = ' . (int) $idLang .  Product::sqlStock('p', 0);

        $sql .= '
        WHERE p.`active` = 1
        AND p.`visibility` != \'none\'';

        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql .= ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
                JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
                WHERE cp.`id_product` = p.`id_product`)';
        }

        $sql .= '
        ORDER BY ps.quantity DESC
        LIMIT ' . (int) ($pageNumber * $nbProducts) . ', ' . (int) $nbProducts;

        if (!$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql)) {
            return false;
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function addProductSale($idProduct, $qty = 1) {

        return Db::getInstance()->execute(
            '
            INSERT INTO ' . _DB_PREFIX_ . 'product_sale
            (`id_product`, `quantity`, `sale_nbr`, `date_upd`)
            VALUES (' . (int) $idProduct . ', ' . (int) $qty . ', 1, NOW())
            ON DUPLICATE KEY UPDATE `quantity` = `quantity` + ' . (int) $qty . ', `sale_nbr` = `sale_nbr` + 1, `date_upd` = NOW()'
        );
    }

    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function removeProductSale($idProduct, $qty = 1) {

        $totalSales = ProductSale::getNbrSales($idProduct);

        if ($totalSales > 1) {
            return Db::getInstance()->execute(
                '
                UPDATE ' . _DB_PREFIX_ . 'product_sale
                SET `quantity` = CAST(`quantity` AS SIGNED) - ' . (int) $qty . ', `sale_nbr` = CAST(`sale_nbr` AS SIGNED) - 1, `date_upd` = NOW()
                WHERE `id_product` = ' . (int) $idProduct
            );
        } else if ($totalSales == 1) {
            return Db::getInstance()->delete('product_sale', 'id_product = ' . (int) $idProduct);
        }

        return true;
    }

    /**
     * @param int $idProduct
     *
     * @return int
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getNbrSales($idProduct) {

        $result = Db::getInstance()->getRow('SELECT `sale_nbr` FROM ' . _DB_PREFIX_ . 'product_sale WHERE `id_product` = ' . (int) $idProduct);

        if (!$result || empty($result) || !array_key_exists('sale_nbr', $result)) {
            return -1;
        }

        return (int) $result['sale_nbr'];
    }

}
