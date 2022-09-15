<?php

/**
 * Class PageCacheKey - composite key for full page cache
 *
 * @since 1.0.7
 */
class PageCacheKeyCore {

    protected static $instance = null;

    public $entityType;
    public $entityId;
    public $url;
    public $idCurrency;
    public $idLanguage;
    public $idCountry;
    public $idCompany;
    public $idStudent;

    /**
     * Creates new cache key and set its metadata
     *
     * @param string $entityType -- controller name
     * @param int    $entityId - specific entity, for example product id
     * @param string $url
     * @param int    $idCurrency
     * @param int    $idLanguage
     * @param int    $idCountry
     * @param int    $idCompany
     * @param int    $idGroup
     * @param int    $idStudent id of logged-in student, zero otherwise
     *
     * @since 1.0.7
     */
    protected function __construct($entityType, $entityId, $url, $idCurrency, $idLanguage, $idCountry, $idCompany, $idStudent) {

        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->url = $url;
        $this->idCurrency = $idCurrency;
        $this->idLanguage = $idLanguage;
        $this->idCountry = $idCountry;
        $this->idShop = $idCompany;
        $this->idStudent = $idStudent;
    }

    /**
     * Returns unique hash for this key
     *
     * @return string
     * @since 1.0.7
     */
    public function getHash() {

        return Tools::encrypt('pagecache_public_'
            . $this->url
            . $this->idCurrency
            . $this->idLanguage
            . $this->idCountry
            . $this->idShop
            . $this->idStudent
        );
    }

    /**
     * Returns full page cache key for current request
     *
     * @return PageCacheKey | false
     * @since   1.0.7
     * @version 1.0.7 Initial version
     */
    public static function get() {

        if (is_null(static::$instance)) {
            static::$instance = static::resolvePageKey();
        }

        return static::$instance;
    }

    /**
     * Returns full page cache key for current request
     *
     * @return PageCacheKey | false
     * @since   1.0.7
     * @version 1.0.7 Initial version
     */
    protected static function resolvePageKey() {

        // we can cache only GET request

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'GET') {
            return false;
        }

        // don't cache when request contains 'no_cache=1'

        if (Tools::getValue('no_cache')) {
            return false;
        }

        // don't cache pages when live edit mode is enabled

        if (Tools::isSubmit('live_edit') || Tools::isSubmit('live_configurator_token')) {
            return false;
        }

        // ajax calls are not cached
        $ajaxCalling = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($ajaxCalling) {
            return false;
        }

        $context = Context::getContext();

        if (!$context->currency) {
            $currency = Tools::setCurrency($context->cookie);
        } else {
            $currency = $context->currency;
        }

        // check that current controller can be cached
        $entityType = Performer::getInstance()->getController();
        $cacheableControllers = json_decode(Configuration::get('EPH_PAGE_CACHE_CONTROLLERS'), true);

        if (!in_array($entityType, $cacheableControllers)) {
            return false;
        }

        // this page can be cached -- let's compute cache key
        $protocol = Configuration::get('EPH_SSL_ENABLED') ? 'https://' : 'http://';
        $url = explode('?', $_SERVER['REQUEST_URI']);
        $uri = $url[0];
        $queryString = isset($url[1]) ? $url[1] : '';

        if ($queryString === '') {
            $newUrl = $protocol . $_SERVER['HTTP_HOST'] . $uri;
        } else {
            parse_str($queryString, $queryStringParams);
            $paramsToIgnoreStr = Configuration::get('EPH_PAGE_CACHE_IGNOREPARAMS');

            if ($paramsToIgnoreStr) {
                $paramsToIgnore = explode(',', $paramsToIgnoreStr);

                if (is_array($paramsToIgnore)) {

                    foreach ($paramsToIgnore as $param) {

                        if (isset($queryStringParams[$param])) {
                            unset($queryStringParams[$param]);
                        }

                    }

                }

            }

            ksort($queryStringParams);
            $newQueryString = http_build_query($queryStringParams);
            $newUrl = $protocol . $_SERVER['HTTP_HOST'] . $uri . '?' . $newQueryString;
        }

        $entityId = (int) Tools::getValue('id_' . $entityType);

        return new PageCacheKey(
            $entityType,
            $entityId,
            $newUrl,
            (int) $currency->id,
            (int) $context->language->id,
            (int) $context->country->id,
            (int) $context->company->id,
            (int) $context->customer->id
        );
    }

}
