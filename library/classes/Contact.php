<?php

/**
 * Class ContactCore
 *
 * @since 1.9.1.0
 */
class ContactCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    public $id;
    /** @var string Name */
    public $name;
    /** @var string e-mail */
    public $email;
    /** @var string Detailed description */
    public $description;
    public $customer_service;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'contact',
        'primary'   => 'id_contact',
        'multilang' => true,
        'fields'    => [
            'email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 128],
            'customer_service' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */
            'name'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'description'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
        ],
    ];

    /**
     * Return available contacts
     *
     * @param int $idLang Language ID
     *
     * @return array Contacts
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getContacts($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('contact', 'c')
                ->leftJoin('contact_lang', 'cl', 'c.`id_contact` = cl.`id_contact` AND cl.`id_lang` = ' . (int) $idLang)
                ->groupBy('c.`id_contact`')
                ->orderBy('`name` ASC')
        );
    }

    /**
     * Return available categories contacts
     *
     * @return array Contacts
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCategoriesContacts() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cl.*')
                ->from('contact', 'ct')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = ct.`id_contact` AND cl.`id_lang` = ' . (int) Context::getContext()->language->id)
                ->where('ct.`customer_service` = 1')
                ->groupBy('ct.`id_contact`')
        );
    }
}
