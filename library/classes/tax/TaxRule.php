<?php


class TaxRuleCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_tax_rules_group;
    public $id_country;
    public $id_state;
    public $zipcode_from;
    public $zipcode_to;
    public $id_tax;
    public $behavior;
    public $description;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tax_rule',
        'primary' => 'id_tax_rule',
        'fields'  => [
            'id_tax_rules_group' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_country'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_state'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                    ],
            'zipcode_from'       => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode'                      ],
            'zipcode_to'         => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode'                      ],
            'id_tax'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'behavior'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                   ],
            'description'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'                        ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_tax_rules_group' => ['xlink_resource' => 'tax_rule_groups'],
            'id_state'           => ['xlink_resource' => 'states'],
            'id_country'         => ['xlink_resource' => 'countries'],
        ],
    ];

    /**
     * @param $idGroup
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function deleteByGroupId($idGroup)
    {
        if (empty($idGroup)) {
            die(Tools::displayError());
        }

        return Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax_rules_group` = '.(int) $idGroup
        );
    }

    /**
     * @param $idTaxRule
     *
     * @return array|bool|null|object
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function retrieveById($idTaxRule)
    {
        return Db::getInstance()->getRow(
            '
			SELECT * FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax_rule` = '.(int) $idTaxRule
        );
    }

    /**
     * @param int $idLang
     * @param int $idGroup
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getTaxRulesByGroupId($idLang, $idGroup)
    {
        return Db::getInstance()->executeS(
            '
		SELECT g.`id_tax_rule`,
				 c.`name` AS country_name,
				 s.`name` AS state_name,
				 t.`rate`,
				 g.`zipcode_from`, g.`zipcode_to`,
				 g.`description`,
				 g.`behavior`,
				 g.`id_country`,
				 g.`id_state`
		FROM `'._DB_PREFIX_.'tax_rule` g
		LEFT JOIN `'._DB_PREFIX_.'country_lang` c ON (g.`id_country` = c.`id_country` AND `id_lang` = '.(int) $idLang.')
		LEFT JOIN `'._DB_PREFIX_.'state` s ON (g.`id_state` = s.`id_state`)
		LEFT JOIN `'._DB_PREFIX_.'tax` t ON (g.`id_tax` = t.`id_tax`)
		WHERE `id_tax_rules_group` = '.(int) $idGroup.'
		ORDER BY `country_name` ASC, `state_name` ASC, `zipcode_from` ASC, `zipcode_to` ASC'
        );
    }

    /**
     * @param int $idTax
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function deleteTaxRuleByIdTax($idTax)
    {
        return Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax` = '.(int) $idTax
        );
    }

    /**
     * @deprecated 1.0.0
     */
    public static function deleteTaxRuleByIdCounty($idCounty)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param int $idTax
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function isTaxInUse($idTax)
    {
        $cacheId = 'TaxRule::isTaxInUse_'.(int) $idTax;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'tax_rule` WHERE `id_tax` = '.(int) $idTax);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param string $zipcode a range of zipcode (eg: 75000 / 75000-75015)
     *
     * @return array an array containing two zipcode ordered by zipcode
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function breakDownZipCode($zipCodes)
    {
        $zipCodes = preg_split('/-/', $zipCodes);

        $from = $zipCodes[0];
        $to = isset($zipCodes[1]) ? $zipCodes[1] : 0;
        if (count($zipCodes) == 2) {
            $from = $zipCodes[0];
            $to = $zipCodes[1];
            if ($zipCodes[0] > $zipCodes[1]) {
                $from = $zipCodes[1];
                $to = $zipCodes[0];
            } elseif ($zipCodes[0] == $zipCodes[1]) {
                $from = $zipCodes[0];
                $to = 0;
            }
        } elseif (count($zipCodes) == 1) {
            $from = $zipCodes[0];
            $to = 0;
        }

        return [$from, $to];
    }

    /**
     * Replace a tax_rule id by an other one in the tax_rule table
     *
     * @param int $oldId
     * @param int $newId
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @return bool
     */
    public static function swapTaxId($oldId, $newId)
    {
        return Db::getInstance()->execute(
            '
		UPDATE `'._DB_PREFIX_.'tax_rule`
		SET `id_tax` = '.(int) $newId.'
		WHERE `id_tax` = '.(int) $oldId
        );
    }
}
