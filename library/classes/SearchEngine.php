<?php

/**
 * Class SearchEngineCore
 *
 * @since 1.9.1.0
 */
class SearchEngineCore extends PhenyxObjectModel {

    public $server;
    public $getvar;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'search_engine',
        'primary' => 'id_search_engine',
        'fields'  => [
            'server' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true],
            'getvar' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true],
        ],
    ];

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getKeywords($url) {

        $parsedUrl = @parse_url($url);

        if (!isset($parsedUrl['host']) || !isset($parsedUrl['query'])) {
            return false;
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT `server`, `getvar` FROM `' . _DB_PREFIX_ . 'search_engine`');

        foreach ($result as $row) {
            $host = &$row['server'];
            $varname = &$row['getvar'];

            if (strstr($parsedUrl['host'], $host)) {
                $array = [];
                preg_match('/[^a-z]' . $varname . '=.+\&/U', $parsedUrl['query'], $array);

                if (empty($array[0])) {
                    preg_match('/[^a-z]' . $varname . '=.+$/', $parsedUrl['query'], $array);
                }

                if (empty($array[0])) {
                    return false;
                }

                $str = urldecode(str_replace('+', ' ', ltrim(substr(rtrim($array[0], '&'), strlen($varname) + 1), '=')));

                if (!Validate::isMessage($str)) {
                    return false;
                }

                return $str;
            }

        }

    }

}
