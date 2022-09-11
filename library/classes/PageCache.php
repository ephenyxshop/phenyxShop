<?php

/**
 * Class PageCache
 *
 * @since 1.9.1.0
 * @since 1.0.1 Overridable
 */
class PageCacheCore {

    /**
     * How many seconds should the page remain in cache
     */
    const CACHE_ENTRY_TTL = 86400;

    /**
     * Returns true if full page cache is enabled
     *
     * @since: 1.0.7
     */
    public static function isEnabled() {

        return Cache::isEnabled() && (bool) Configuration::get('EPH_PAGE_CACHE_ENABLED');
    }

    /**
     * Insert new entry for current request into full page cache
     *
     * @param string $template
     *
     * @since 1.0.7
     */
    public static function set($template) {

        if (static::isEnabled()) {
            $key = PageCacheKey::get();

            if ($key) {
                $hash = $key->getHash();
                $cache = Cache::getInstance();
                $cache->set($hash, $template, static::CACHE_ENTRY_TTL);
                static::cacheKey($hash, $key->idCurrency, $key->idLanguage, $key->idCountry, $key->idShop, $key->entityType, $key->entityId);
            }

        }

    }

    /**
     * Returns full page cache entry for current request
     *
     * @return string | null
     *
     * @since 1.0.7
     */
    public static function get() {

        if (static::isEnabled()) {

            // check that there were no changes to hook list
            $hookListHash = static::getHookListFingerprint();

            if ($hookListHash != Configuration::get('EPH_HOOK_LIST_HASH')) {
                // drain the cache if the hook list changed
                Configuration::updateValue('EPH_HOOK_LIST_HASH', $hookListHash);
                static::flush();
                return null;
            }

            $key = PageCacheKey::get();

            if ($key) {
                $cache = Cache::getInstance();

                return $cache->get($key->getHash());
            }

        }

        return null;
    }

    /**
     * Register cache key and set its metadata
     *
     * @param string $key
     * @param int    $idCurrency
     * @param int    $idLanguage
     * @param int    $idCountry
     * @param int    $idShop
     * @param string $entityType
     * @param int    $idEntity
     *
     * @since 1.9.1.0
     */
    public static function cacheKey($key, $idCurrency, $idLanguage, $idCountry, $idShop, $entityType, $idEntity) {

        try {
            Db::getInstance()->insert(
                'page_cache',
                [
                    'cache_hash'  => pSQL($key),
                    'id_currency' => (int) $idCurrency,
                    'id_language' => (int) $idLanguage,
                    'id_country'  => (int) $idCountry,
                    'id_shop'     => (int) $idShop,
                    'entity_type' => pSQL($entityType),
                    'id_entity'   => (int) $idEntity,
                ],
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );
        } catch (Exception $e) {
            // Hash already inserted
        }

    }

    /**
     * Invalidate an entity from the cache
     *
     * @param string   $entityType
     * @param int|null $idEntity
     *
     * @since 1.9.1.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function invalidateEntity($entityType, $idEntity = null) {

        $keysToInvalidate = [];

        if ($entityType === 'product') {
            // Refresh the homepage
            $keysToInvalidate = array_merge(
                $keysToInvalidate,
                static::getKeysToInvalidate('index')
            );

            Db::getInstance()->delete(
                'page_cache',
                '`entity_type` = \'index\''
            );

            if ($idEntity) {
                // Invalidate product's categories only
                $product = new Product((int) $idEntity);

                if ($product) {
                    $categories = $product->getCategories();

                    foreach ($categories as $idCategory) {
                        $keysToInvalidate = array_merge(
                            $keysToInvalidate,
                            static::getKeysToInvalidate('category', $idCategory)
                        );
                        Db::getInstance()->delete(
                            'page_cache',
                            '`entity_type` = \'category\' AND `id_entity` = ' . (int) $idCategory
                        );
                    }

                }

            } else {
                // Invalidate all parent categories
                $keysToInvalidate = array_merge(
                    $keysToInvalidate,
                    static::getKeysToInvalidate('category')
                );
                Db::getInstance()->delete(
                    'page_cache',
                    '`entity_type` = \'category\''
                );
            }

        }

        $keysToInvalidate = array_merge(
            $keysToInvalidate,
            static::getKeysToInvalidate($entityType, $idEntity)
        );
        Db::getInstance()->delete(
            'page_cache',
            '`entity_type` = \'' . pSQL($entityType) . '\'' . ($idEntity ? ' AND `id_entity` = ' . (int) $idEntity : '')
        );

        $cache = Cache::getInstance();

        foreach ($keysToInvalidate as $item) {
            $cache->delete($item);
        }

    }

    /**
     * Flush all data
     *
     * @since 1.9.1.0
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function flush() {

        if (static::isEnabled()) {
            Cache::getInstance()->flush();
        }

        Db::getInstance()->delete('page_cache');
    }

    /**
     * Get keys to invalidate
     *
     * @param string   $entityType
     * @param int|null $idEntity
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    protected static function getKeysToInvalidate($entityType, $idEntity = null) {

        $sql = new DbQuery();
        $sql->select('`cache_hash`');
        $sql->from('page_cache');
        $sql->where('`entity_type` = \'' . pSQL($entityType) . '\'');

        if ($idEntity) {
            $sql->where('`id_entity` = ' . (int) $idEntity);
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!is_array($results)) {
            return [];
        }

        return array_column($results, 'cache_hash');
    }

    /**
     * Return normalized list of all hooks that should be cached
     */
    public static function getCachedHooks() {

        $hookSettings = json_decode(Configuration::get('EPH_PAGE_CACHE_HOOKS'), true);

        if (!is_array($hookSettings)) {
            return [];
        }

        $cachedHooks = [];

        foreach ($hookSettings as $idModule => $hookArr) {
            $idModule = (int) $idModule;

            if ($idModule) {
                $moduleHooks = [];

                foreach ($hookArr as $idHook => $bool) {
                    $idHook = (int) $idHook;

                    if ($idHook && $bool) {
                        $moduleHooks[$idHook] = 1;
                    }

                }

                if ($moduleHooks) {
                    $cachedHooks[$idModule] = $moduleHooks;
                }

            }

        }

        return $cachedHooks;
    }

    /**
     * Modify hook cached status
     *
     * If $status is true, hook output will be cached. Otherwise content of
     * this hook will be refreshed with every page load
     *
     * @param int $idModule
     * @param int $idHook
     * @param bool $status
     */
    public static function setHookCacheStatus($idModule, $idHook, $status) {

        $hookSettings = static::getCachedHooks();
        $idModule = (int) $idModule;
        $idHook = (int) $idHook;

        if (!isset($hookSettings[$idModule])) {
            $hookSettings[$idModule] = [];
        }

        if ($status) {
            $hookSettings[$idModule][$idHook] = 1;
        } else {
            unset($hookSettings[$idModule][$idHook]);

            if (empty($hookSettings[$idModule])) {
                unset($hookSettings[$idModule]);
            }

        }

        if (Configuration::updateGlobalValue('EPH_PAGE_CACHE_HOOKS', json_encode($hookSettings))) {
            static::flush();

            return true;
        }

        return false;
    }

    /**
     * Calculates md5 hash of hook list
     *
     * This has is used to detect any changes to hook execution list, including
     * hook position, new modules, enabled/disabled modules, etc...
     *
     * In multistore environment, every shop will have different hash. That's fine,
     * because PageCacheKey has id_shop as a dimension
     *
     * @return string
     */
    public static function getHookListFingerprint() {

        $hookList = Hook::getHookModuleList();
        $ctx = hash_init('md5');

        foreach ($hookList as $idHook => $moduleList) {
            hash_update($ctx, $idHook);

            foreach ($moduleList as $idModule => $moduleInfo) {
                hash_update($ctx, $idModule);
                hash_update($ctx, $moduleInfo['active']);
            }

        }

        return hash_final($ctx);
    }

}
