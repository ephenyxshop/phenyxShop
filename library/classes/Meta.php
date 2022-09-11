<?php

/**
 * Class MetaCore
 *
 * @since 1.9.1.0
 */
class MetaCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    public $page;
	public $controller;
    public $configurable = 1;
    public $title;
    public $description;
    public $keywords;
    public $url_rewrite;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'meta',
        'primary'        => 'id_meta',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            'page'         => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'required' => true, 'size' => 64],
			'controller'   => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'size' => 12],
            'configurable' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],

            /* Lang fields */
            'title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'url_rewrite'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'size' => 255],
        ],
    ];
    /**
     * @param bool $excludeFilled
     * @param bool $addPage
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getPages($excludeFilled = false, $addPage = false) {

		
        $selectedPages = [];
		$adminModuleFiles = [];
        if (!$files = Tools::scandir(_EPH_CORE_DIR_. DIRECTORY_SEPARATOR . '/includes/controllers/front' .  DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan front root directory'));
        }
		if (!$adminFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/controllers/backend' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan admin root directory'));
        }
		foreach (glob(_EPH_MODULE_DIR_ . '*/controllers/admin/*.php') as $file) {
            $filename = basename($file);			          
            $adminModuleFiles[] = $filename;
        }
            
        if (!$overrideFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/override' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'front' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan "override" directory'));
        }

        $files = array_values(array_unique(array_merge($files, $adminFiles)));
		
		$files = array_values(array_unique(array_merge($files, $adminModuleFiles)));

        $files = array_values(array_unique(array_merge($files, $overrideFiles)));
		

        // Exclude pages forbidden
        $exludePages = [
            'category',
            'changecurrency',
            'cms',
            'footer',
            'header',
            'pagination',
            'product',
            'product-sort',
            'statistics',
        ];

        foreach ($files as $file) {

            if ($file != 'index.php' && !in_array(strtolower(str_replace('Controller.php', '', $file)), $exludePages)) {
                $className = str_replace('.php', '', $file);
                $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                $properties = $reflection ? $reflection->getDefaultProperties() : [];

                if (isset($properties['php_self'])) {
                    $selectedPages[$properties['php_self']] = $properties['php_self'];
                } else if (preg_match('/^[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages[strtolower(str_replace('Controller.php', '', $file))] = strtolower(str_replace('Controller.php', '', $file));
                } else if (preg_match('/^([a-z0-9_.-]*\/)?[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages[strtolower(sprintf(Tools::displayError('%2$s (in %1$s)'), dirname($file), str_replace('Controller.php', '', basename($file))))] = strtolower(str_replace('Controller.php', '', basename($file)));
                }

            }

        }
		
        // Add modules controllers to list (this function is cool !)

        foreach (glob(_EPH_MODULE_DIR_ . '*/controllers/front/*.php') as $file) {
            $filename = mb_strtolower(basename($file, '.php'));

            if ($filename == 'index') {
                continue;
            }

            $module = mb_strtolower(basename(dirname(dirname(dirname($file)))));
            $selectedPages[$module . ' - ' . $filename] = 'module-' . $module . '-' . $filename;
        }
		
		

        // Exclude page already filled

        if ($excludeFilled) {
            $metas = Meta::getMetas();

            foreach ($metas as $meta) {

                if (in_array($meta['page'], $selectedPages)) {
                    unset($selectedPages[array_search($meta['page'], $selectedPages)]);
                }

            }

        }

        // Add selected page

        if ($addPage) {
            $name = $addPage;

            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9]+)$#i', $addPage, $m)) {
                $addPage = $m[1] . ' - ' . $m[2];
            }

            $selectedPages[$addPage] = $name;
            asort($selectedPages);
        }
		
        return $selectedPages;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMetas() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta')
                ->orderBy('`page` ASC')
        );
    }
	
	public static function getLinkRewrite($page, $idLang) {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('ml.url_rewrite')
            ->from('meta', 'm')
			->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` AND ml.`id_lang` = ' . (int) $idLang)
			->where('page = \'' . pSQL($page) . '\'')
        );
	}
	
	public static function getTitle($page, $idLang) {
		
		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('ml.title')
            ->from('meta', 'm')
			->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` AND ml.`id_lang` = ' . (int) $idLang)
			->where('page = \'' . pSQL($page) . '\'')
        );
	}

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMetasByIdLang($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('ml.`id_lang` = ' . (int) $idLang . ' ' . Shop::addSqlRestrictionOnLang('ml'))
                ->orderBy('`page` ASC')
        );
    }

    /**
     * @param int    $newIdLang
     * @param int    $idLang
     * @param string $urlRewrite
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws PhenyxShopException
     */
    public static function getEquivalentUrlRewrite($newIdLang, $idLang, $urlRewrite) {

        $metaSql = (new DbQuery())
            ->select('`id_meta`')
            ->from('meta_lang')
            ->where('`url_rewrite` = \'' . pSQL($urlRewrite) . '\'')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_shop` = ' . (int) Context::getContext()->shop->id);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('url_rewrite')
                ->from('meta_lang')
                ->where('id_meta = (' . $metaSql->build() . ')')
                ->where('`id_lang` = ' . (int) $newIdLang)
                ->where('`id_shop` = ' . (int) Context::getContext()->shop->id)
        );
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @param int    $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getMetaTags($idLang, $pageName, $title = '') {

        $allowed = false;
		if(!empty(Configuration::get('EPH_MAINTENANCE_IP'))) {
			$allowed = in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('EPH_MAINTENANCE_IP')));
		}
		
		if (!(!Configuration::get('EPH_SHOP_ENABLE') && !$allowed)) {

            if ($pageName == 'education' && ($idProduct = Tools::getValue('id_education'))) {
                return Meta::getEducationMetas($idProduct, $idLang, $pageName);
            } else if ($pageName == 'educationtype' && ($idCategory = Tools::getValue('id_education_type'))) {
                return Meta::getEducationTypeMetas($idCategory, $idLang, $pageName, $title);
            } else if ($pageName == 'supplier' && ($idSupplier = Tools::getValue('id_supplier'))) {
                return Meta::getSupplierMetas($idSupplier, $idLang, $pageName);
            } else if ($pageName == 'cms' && ($idCms = Tools::getValue('id_cms'))) {
                return Meta::getCmsMetas($idCms, $idLang, $pageName);
            } else if ($pageName == 'cms' && ($idCmsCategory = Tools::getValue('id_cms_category'))) {
                return Meta::getCmsCategoryMetas($idCmsCategory, $idLang, $pageName);
            }

        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get product meta tags
     *
     *
     * @param int    $idProduct
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getEducationMetas($idProduct, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`, `description_short`')
            ->from('education_lang')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_education` = ' . (int) $idProduct)
        )) {

            if (empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['description_short']);
            }

            return Meta::completeMetaTags($row, $row['name']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * @param array   $metaTags
     * @param string  $defaultValue
     * @param Context $context
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function completeMetaTags($metaTags, $defaultValue, Context $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        if (empty($metaTags['meta_title'])) {
            $metaTags['meta_title'] = $defaultValue . ' - ' . Configuration::get('EPH_SHOP_NAME');
        }

        if (empty($metaTags['meta_description'])) {
            $metaTags['meta_description'] = Configuration::get('EPH_META_DESCRIPTION', $context->language->id) ? Configuration::get('EPH_META_DESCRIPTION', $context->language->id) : '';
        }

        if (empty($metaTags['meta_keywords'])) {
            $metaTags['meta_keywords'] = Configuration::get('EPH_META_KEYWORDS', $context->language->id) ? Configuration::get('EPH_META_KEYWORDS', $context->language->id) : '';
        }

        return $metaTags;
    }

    /**
     * Get meta tags for a given page
     *
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array Meta tags
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getHomeMetas($idLang, $pageName) {

        $metas = Meta::getMetaByPage($pageName, $idLang);
        $ret['meta_title'] = (isset($metas['title']) && $metas['title']) ? $metas['title'] . ' - ' . Configuration::get('EPH_SHOP_NAME') : Configuration::get('EPH_SHOP_NAME');
        $ret['meta_description'] = (isset($metas['description']) && $metas['description']) ? $metas['description'] : '';
        $ret['meta_keywords'] = (isset($metas['keywords']) && $metas['keywords']) ? $metas['keywords'] : '';

        return $ret;
    }

    /**
     * @param string $page
     * @param int    $idLang
     *
     * @return array|bool|null|object
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMetaByPage($page, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('m.`page` = \'' . pSQL($page) . '\' OR m.`page` = \'' . pSQL(str_replace('_', '', strtolower($page))) . '\'')
                ->where('ml.`id_lang` = ' . (int) $idLang . ' ' . Shop::addSqlRestrictionOnLang('ml'))
        );
    }

    public static function getMetaById($idMeta, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('m.`id_meta` = ' . (int) $idMeta)
                ->where('ml.`id_lang` = ' . (int) $idLang . ' ' . Shop::addSqlRestrictionOnLang('ml'))
        );
    }

    /**
     * Get category meta tags
     *
     * @param int    $idCategory
     * @param int    $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getEducationTypeMetas($idCategory, $idLang, $pageName, $title = '') {

        if (!empty($title)) {
            $title = ' - ' . $title;
        }

        $pageNumber = (int) Tools::getValue('p');
        $cacheId = 'Meta::getEducationTypeMetas' . (int) $idCategory . '-' . (int) $idLang;

        if (!Cache::isStored($cacheId)) {

            if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                ->select('`name`, `meta_title`, `meta_description`, `description`')
                ->from('education_type_lang', 'cl')
                ->where('cl.`id_lang` = ' . (int) $idLang)
                ->where('cl.`id_education_type` = ' . (int) $idCategory)
            )) {

                if (empty($row['meta_description'])) {
                    $row['meta_description'] = strip_tags($row['description']);
                }

                // Paginate title

                if (!empty($row['meta_title'])) {
                    $row['meta_title'] = $title . $row['meta_title'] . (!empty($pageNumber) ? ' (' . $pageNumber . ')' : '') . ' - ' . Configuration::get('EPH_SHOP_NAME');
                } else {
                    $row['meta_title'] = $row['name'] . (!empty($pageNumber) ? ' (' . $pageNumber . ')' : '') . ' - ' . Configuration::get('EPH_SHOP_NAME');
                }

                if (!empty($title)) {
                    $row['meta_title'] = $title . (!empty($pageNumber) ? ' (' . $pageNumber . ')' : '') . ' - ' . Configuration::get('EPH_SHOP_NAME');
                }

                $result = Meta::completeMetaTags($row, $row['name']);
            } else {
                $result = Meta::getHomeMetas($idLang, $pageName);
            }

            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get supplier meta tags
     *
     * @param int    $idSupplier
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getSupplierMetas($idSupplier, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`name`, `meta_title`, `meta_description`, `meta_keywords`')
            ->from('supplier_lang', 'sl')
            ->leftJoin('supplier', 's', 'sl.`id_supplier` = s.`id_supplier`')
            ->where('sl.`id_lang` = ' . (int) $idLang)
            ->where('sl.`id_supplier` = ' . (int) $idSupplier)
        )) {

            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags($row['meta_description']);
            }

            if (!empty($row['meta_title'])) {
                $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('EPH_SHOP_NAME');
            }

            return Meta::completeMetaTags($row, $row['name']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get CMS meta tags
     *
     * @param int    $idCms
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCmsMetas($idCms, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`meta_title`, `meta_description`, `meta_keywords`')
            ->from('cms_lang')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_cms` = ' . (int) $idCms)
            ->where(Context::getContext()->shop->id ? '`id_shop` = ' . (int) Context::getContext()->shop->id : '')
        )) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('EPH_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * Get CMS category meta tags
     *
     * @param int    $idCmsCategory
     * @param int    $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCmsCategoryMetas($idCmsCategory, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`meta_title`, `meta_description`, `meta_keywords`')
            ->from('cms_category_lang')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_cms_category` = ' . (int) $idCmsCategory)
            ->where(Context::getContext()->shop->id ? '`id_shop` = ' . (int) Context::getContext()->shop->id : '')
        )) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('EPH_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function update($nullValues = false) {

        if (!parent::update($nullValues)) {
            return false;
        }

        return Tools::generateHtaccess();
    }

    /**
     * @param array $selection
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function deleteSelection($selection) {

        if (!is_array($selection)) {
            die(Tools::displayError());
        }

        $result = true;

        foreach ($selection as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }

        return $result && Tools::generateHtaccess();
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function delete() {

        if (!parent::delete()) {
            return false;
        }

        return Tools::generateHtaccess();
    }
	
	public static function getAdminEducationMeta() {
		
		$query = 'SELECT m.`id_meta`, m.`controller`, m.`page`, m.`configurable`, ml.`title`, ml.`description`, ml.`url_rewrite`
            FROM `' . _DB_PREFIX_ . 'meta` m
			LEFT JOIN `' . _DB_PREFIX_ . 'meta_lang` ml ON (ml.id_meta = m.id_meta AND ml.id_lang = 1)
            WHERE m.`controller` = "admin"';
		
		$metas = Db::getInstance()->executeS($query);
		
		
		return $metas;	

	}

}
