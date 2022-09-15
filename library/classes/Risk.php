<?php

/**
 * Class RiskCore
 *
 * @since 1.9.1.0
 */
class RiskCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    public $id_risk;
    public $name;
    public $color;
    public $percent;
    // @codingStandardsIgnoreEnd

    public static $definition = [
        'table'     => 'risk',
        'primary'   => 'id_risk',
        'multilang' => true,
        'fields'    => [
            'name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 20],
            'color'   => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
            'percent' => ['type' => self::TYPE_INT, 'validate' => 'isPercentage'],
        ],
    ];

    /**
     * @param int|null $idLang
     *
     * @return PhenyxShopCollection
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getRisks($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $risks = new PhenyxShopCollection('Risk', $idLang);

        return $risks;
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getFields() {

        $this->validateFields();
        $fields['id_risk'] = (int) $this->id_risk;
        $fields['color'] = pSQL($this->color);
        $fields['percent'] = (int) $this->percent;

        return $fields;
    }

    /**
     * Check then return multilingual fields for database interaction
     *
     * @return array Multilingual fields
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws PhenyxShopException
     */
    public function getTranslationsFieldsChild() {

        $this->validateFieldsLang();

        return $this->getTranslationsFields(['name']);
    }

}
