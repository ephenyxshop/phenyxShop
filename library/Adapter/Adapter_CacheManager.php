<?php

/**
 * Class Adapter_CacheManager
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_CacheManager {

    // @codingStandardsIgnoreEnd

    /**
     * Cleans the cache for specific cache key.
     *
     * @param string $key
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function clean($key) {

        Cache::clean($key);
    }
}
