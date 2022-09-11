<?php

/**
 * Class ZoneCore
 *
 * @since 1.9.1.0
 */
class ZoneCore extends ObjectModel {

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'zone',
        'primary' => 'id_zone',
        'fields'  => [
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'cee'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'id_tax_mode' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'active'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
    /** @var string Name */
    public $name;

    public $cee;
    public $id_tax_mode;
    /** @var bool Zone status */
    public $active = true;
    protected $webserviceParameters = [];

    /**
     * Get all available geographical zones
     *
     * @param bool $active
     *
     * @return array Zones
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getZones($active = false) {

        $cacheId = 'Zone::getZones_' . (bool) $active;

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('zone')
                    ->where($active ? '`active` = 1' : '')
                    ->orderBy('`name` ASC')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Get a zone ID from its default language name
     *
     * @param string $name
     *
     * @return int id_zone
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getIdByName($name) {

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_zone`')
                ->from('zone')
                ->where('`name` = \'' . pSQL($name) . '\'')
        );
    }

    /**
     * Delete a zone
     *
     * @return bool Deletion result
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function delete() {

        if (parent::delete()) {
            // Delete regarding delivery preferences
            $result = Db::getInstance()->delete('carrier_zone', 'id_zone = ' . (int) $this->id);
            $result &= Db::getInstance()->delete('delivery', 'id_zone = ' . (int) $this->id);

            // Update Country & state zone with 0
            $result &= Db::getInstance()->update('country', ['id_zone' => 0], 'id_zone = ' . (int) $this->id);
            $result &= Db::getInstance()->update('state', ['id_zone' => 0], 'id_zone = ' . (int) $this->id);

            return $result;
        }

        return false;
    }

}
