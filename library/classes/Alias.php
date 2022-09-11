<?php

/**
 * Class AliasCore
 *
 * @since 1.9.1.0
 */
class AliasCore extends ObjectModel {

    /** @var string $alias */
    public $alias;
    /** @var string $search */
    public $search;
    /** @var bool $active */
    public $active = true;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'alias',
        'primary' => 'id_alias',
        'fields'  => [
            'search' => ['type' => self::TYPE_STRING, 'validate' => 'isValidSearch', 'required' => true, 'size' => 255],
            'alias'  => ['type' => self::TYPE_STRING, 'validate' => 'isValidSearch', 'required' => true, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    /**
     * AliasCore constructor.
     *
     * @param null $id
     * @param null $alias
     * @param null $search
     * @param null $idLang
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $alias = null, $search = null, $idLang = null) {

        $this->def = Alias::getDefinition($this);
        $this->setDefinitionRetrocompatibility();

        if ($id) {
            parent::__construct($id);
        } else if ($alias && Validate::isValidSearch($alias)) {

            if (!Alias::isFeatureActive()) {
                $this->alias = trim($alias);
                $this->search = trim($search);
            } else {
                $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                    (new DbQuery())
                        ->select('a.`id_alias`, a.`search`, a.`alias`')
                        ->from('alias', 'a')
                        ->where('`alias` = \'' . pSQL($alias) . '\'')
                        ->where('`active` = 1')
                );

                if ($row) {
                    $this->id = (int) $row['id_alias'];
                    $this->search = $search ? trim($search) : $row['search'];
                    $this->alias = $row['alias'];
                } else {
                    $this->alias = trim($alias);
                    $this->search = trim($search);
                }

            }

        }

    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false) {

        $this->alias = Tools::replaceAccentedChars($this->alias);
        $this->search = Tools::replaceAccentedChars($this->search);

        if (parent::add($autoDate, $nullValues)) {
            // Set cache of feature detachable to true
            Configuration::updateGlobalValue('PS_ALIAS_FEATURE_ACTIVE', '1');

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function delete() {

        if (parent::delete()) {
            // Refresh cache of feature detachable
            Configuration::updateGlobalValue('PS_ALIAS_FEATURE_ACTIVE', Alias::isCurrentlyUsed($this->def['table'], true));

            return true;
        }

        return false;
    }

    /**
     * @return string
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAliases() {

        if (!Alias::isFeatureActive()) {
            return '';
        }

        $aliases = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('a.`alias`')
                ->from('alias', 'a')
                ->where('a.`search` = \'' . pSQL($this->search) . '\'')
        );

        $aliases = array_map('implode', $aliases);

        return implode(', ', $aliases);
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isFeatureActive() {

        return Configuration::get('PS_ALIAS_FEATURE_ACTIVE');
    }

    /**
     * This method is allow to know if a alias exist for AdminImportController
     *
     * @param int $idAlias
     *
     * @return bool
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function aliasExists($idAlias) {

        if (!Alias::isFeatureActive()) {
            return false;
        }

        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_alias`')
                ->from('alias', 'a')
                ->where('a.`id_alias` = ' . (int) $idAlias)
        );

        return isset($row['id_alias']);
    }

}
