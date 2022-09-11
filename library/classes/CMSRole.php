<?php

/**
 * Class CMSRoleCore
 *
 * @since 1.9.1.0
 */
class CMSRoleCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'cms_role',
        'primary' => 'id_cms_role',
        'fields'  => [
            'name'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'id_cms' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];
    /** @var string name */
    public $name;
    // @codingStandarsIgnoreEnd
    /** @var integer id_cms */
    public $id_cms;

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getRepositoryClassName() {

        return 'Core_Business_CMS_CMSRoleRepository';
    }
}
