<?php

/**
 * Class ObjectModelCore
 *
 * @since 1.9.1.0
 */
abstract class ObjectModelCore implements Core_Foundation_Database_EntityInterface {

    /**
     * List of field types
     */
    const TYPE_INT = 1;
    const TYPE_BOOL = 2;
    const TYPE_STRING = 3;
    const TYPE_FLOAT = 4;
    const TYPE_DATE = 5;
    const TYPE_HTML = 6;
    const TYPE_NOTHING = 7;
    const TYPE_SQL = 8;
	const TYPE_JSON = 9;

    /**
     * List of data to format
     */
    const FORMAT_COMMON = 1;
    const FORMAT_LANG = 2;

    /**
     * List of association types
     */
    const HAS_ONE = 1;
    const HAS_MANY = 2;

    // @codingStandardsIgnoreStart
    /** @var int Object ID */
    public $id;

    /** @var int Language ID */
    public $id_lang = null;


    /** @var array|null Holds required fields for each ObjectModel class */
    protected static $fieldsRequiredDatabase = null;

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var string
     */
    protected $table;

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var string
     */
    protected $identifier;

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsRequired = [];

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsSize = [];

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsValidate = [];

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsRequiredLang = [];

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsSizeLang = [];

    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fieldsValidateLang = [];

    /**
     * @deprecated 1.0.0
     * @var array
     */
    protected $tables = [];

    /** @var array Tables */
    protected $webserviceParameters = [];

    /** @var string Path to image directory. Used for image deletion. */
    protected $image_dir = null;

    /** @var String file type of image files. */
    protected $image_format = 'jpg';

    /**
     * @var array Contains object definition
     * @since 1.5.0.1
     */
    public static $definition = [];

    /**
     * Holds compiled definitions of each ObjectModel class.
     * Values are assigned during object initialization.
     *
     * @var array
     */
    protected static $loaded_classes = [];

    /** @var array Contains current object definition. */
    protected $def;

    /** @var array|null List of specific fields to update (all fields if null). */
    protected $update_fields = null;

    /** @var Db An instance of the db in order to avoid calling Db::getInstance() thousands of times. */
    protected static $db = false;

    /** @var bool Enables to define an ID before adding object. */
    public $force_id = false;

    /**
     * @var bool If true, objects are cached in memory.
     */
    protected static $cache_objects = true;
    // @codingStandardsIgnoreEnd

    /**
     * @return null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getRepositoryClassName() {

        return null;
    }

    /**
     * Returns object validation rules (fields validity)
     *
     * @param  string $class Child class name for static use (optional)
     *
     * @return array Validation rules (fields validity)
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getValidationRules($class = __CLASS__) {

        $object = new $class();

        return [
            'required'     => $object->fieldsRequired,
            'size'         => $object->fieldsSize,
            'validate'     => $object->fieldsValidate,
            'requiredLang' => $object->fieldsRequiredLang,
            'sizeLang'     => $object->fieldsSizeLang,
            'validateLang' => $object->fieldsValidateLang,
        ];
    }

   
    public function __construct($id = null, $idLang = null) {

        $className = get_class($this);

        if (!isset(PhenyxObjectModel::$loaded_classes[$className])) {
            $this->def = PhenyxObjectModel::getDefinition($className);
            $this->setDefinitionRetrocompatibility();

            if (!Validate::isTableOrIdentifier($this->def['primary']) || !Validate::isTableOrIdentifier($this->def['table'])) {
                throw new PhenyxShopException('Identifier or table format not valid for class ' . $className);
            }

            PhenyxObjectModel::$loaded_classes[$className] = get_object_vars($this);
        } else {

            foreach (PhenyxObjectModel::$loaded_classes[$className] as $key => $value) {
                $this->{$key}

                = $value;
            }

        }

        if ($idLang !== null) {
            $this->id_lang = (Language::getLanguage($idLang) !== false) ? $idLang : Configuration::get('EPH_LANG_DEFAULT');
        }

        

        if ($id) {
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($id, $idLang, $this, $this->def, static::$cache_objects);
        }

        if (!defined('_EPH_ROOT_DIR_')) {
            $class = get_class($this);

            if ($class == 'CategoriesClass') {
                $obj = ['description' => $this->description];
                $res = Hook::exec('filterCategoryContent', ['object' => $obj], null, true);

                if (isset($res['layerslider']) && isset($res['layerslider']['object']) && !empty($res['layerslider']['object']['description'])) {
                    $this->description = $res['layerslider']['object']['description'];
                }

            } else
            if ($class == 'NewsClass') {
                $obj = ['content' => $this->content];
                $res = Hook::exec('filterCmsContent', ['object' => $obj], null, true);

                if (isset($res['layerslider']) && isset($res['layerslider']['object']) && !empty($res['layerslider']['object']['content'])) {
                    $this->content = $res['layerslider']['object']['content'];
                }

            }

        }

    }

    
    public function &__get($property) {

        // Property to camelCase for backwards compatibility
        $camelCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (property_exists($this, $camelCaseProperty)) {
            return $this->$camelCaseProperty;
        }

        return $this->$property;
    }

    
    public function __set($property, $value) {

        // Property to camelCase for backwards compatibility
        $snakeCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (property_exists($this, $snakeCaseProperty)) {
            $this->$snakeCaseProperty = $value;
        } else {
            $this->$property = $value;
        }

    }

    
    public function getFields() {

        
		$this->validateFields();
        $fields = $this->formatFields(static::FORMAT_COMMON);

        // Ensure that we get something to insert

        if (!$fields && isset($this->id) && Validate::isUnsignedId($this->id)) {
            $fields[$this->def['primary']] = $this->id;
        }

        return $fields;
    }

    public function getFieldsLang() {

        if (method_exists($this, 'getTranslationsFieldsChild')) {
            return $this->getTranslationsFieldsChild();
        }

        $this->validateFieldsLang();

        $fields = [];

        if ($this->id_lang === null || empty($this->id_lang)) {

            foreach (Language::getIDs(false) as $idLang) {
                $fields[$idLang] = $this->formatFields(static::FORMAT_LANG, $idLang);
                $fields[$idLang]['id_lang'] = $idLang;
            }

        } else {
            $fields = [$this->id_lang => $this->formatFields(static::FORMAT_LANG, $this->id_lang)];
            $fields[$this->id_lang]['id_lang'] = $this->id_lang;
        }

        return $fields;
    }

    protected function formatFields($type, $idLang = null) {

        $fields = [];
		
        // Set primary key in fields

        if (isset($this->id)) {
            $fields[$this->def['primary']] = $this->id;
        }

        foreach ($this->def['fields'] as $field => $data) {
            // Only get fields we need for the type
            // E.g. if only lang fields are filtered, ignore fields without lang => true

            if (($type == static::FORMAT_LANG && empty($data['lang']))
                || ($type == static::FORMAT_COMMON && !empty($data['lang']))) {
                continue;
            }

            if (is_array($this->update_fields)) {

                if (!empty($data['lang']) && (empty($this->update_fields[$field]) || ($type == static::FORMAT_LANG && empty($this->update_fields[$field][$idLang])))) {
                    continue;
                }

            }

            // Get field value, if value is multilang and field is empty, use value from default lang
            $value = $this->$field;

            if ($type == static::FORMAT_LANG && $idLang && is_array($value)) {

                if (!empty($value[$idLang])) {
                    $value = $value[$idLang];
                } else
                if (!empty($data['required'])) {
                    $value = $value[Configuration::get('EPH_LANG_DEFAULT')];
                } else {
                    $value = '';
                }

            }

            $purify = (isset($data['validate']) && mb_strtolower($data['validate']) == 'iscleanhtml') ? true : false;
            // Format field value
			
            $fields[$field] = PhenyxObjectModel::formatValue($value, $data['type'], false, $purify, !empty($data['allow_null']));
        }

        return $fields;
    }
    
    public static function formatValue($value, $type, $withQuotes = false, $purify = true, $allowNull = false) {

        if ($allowNull && $value === null) {
            return ['type' => 'sql', 'value' => 'NULL'];
        }

        switch ($type) {
        case self::TYPE_INT:
            return (int) $value;

        case self::TYPE_BOOL:
            return (int) $value;

        case self::TYPE_FLOAT:
			if(is_null($value)) {
				$value = '0.00';
			}
            return (float) str_replace(',', '.', $value);

        case self::TYPE_DATE:

            if (!$value) {
                return '0000-00-00';
            }

            if ($withQuotes) {
                return '\'' . pSQL($value) . '\'';
            }

            return pSQL($value);

        case self::TYPE_HTML:

            if ($purify) {
                $value = Tools::purifyHTML($value);
            }

            if ($withQuotes) {
                return '\'' . pSQL($value, true) . '\'';
            }

            return pSQL($value, true);

        case self::TYPE_SQL:

            if ($withQuotes) {
                return '\'' . pSQL($value, true) . '\'';
            }

            return pSQL($value, true);

        case self::TYPE_NOTHING:
            return $value;
		case self::TYPE_JSON:
			return '\'' . $value . '\'';

        case self::TYPE_STRING:
        default:

            if ($withQuotes) {
                return '\'' . pSQL($value) . '\'';
            }

            return pSQL($value);
        }

    }

    
    public function save($nullValues = false, $autoDate = true) {

        return (int) $this->id > 0 ? $this->update($nullValues) : $this->add($autoDate, $nullValues);
    }

    
    public function add($autoDate = true, $nullValues = false) {

        if (isset($this->id) && !$this->force_id) {
            unset($this->id);
        }

        // @hook actionObject*AddBefore
        Hook::exec('actionObjectAddBefore', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'AddBefore', ['object' => $this]);

        // Automatically fill dates

        if ($autoDate && property_exists($this, 'date_add')) {
            $this->date_add = date('Y-m-d H:i:s');
        }

        if ($autoDate && property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');
        }


        $fields = $this->getFields();

        if (!$result = Db::getInstance()->insert($this->def['table'], $fields, $nullValues)) {
            return false;
        }

        // Get object id in database
        $this->id = Db::getInstance()->Insert_ID();

        

        if (!$result) {
            return false;
        }

        // Database insertion for multilingual fields related to the object

        if (!empty($this->def['multilang'])) {
            $fields = $this->getFieldsLang();

            if ($fields && is_array($fields)) {

                foreach ($fields as $field) {

                    foreach (array_keys($field) as $key) {

                        if (!Validate::isTableOrIdentifier($key)) {
                            throw new PhenyxShopException('key ' . $key . ' is not table or identifier');
                        }

                    }

                    $field[$this->def['primary']] = (int) $this->id;

                    $result &= Db::getInstance()->insert($this->def['table'] . '_lang', $field);

                }

            }

        }

        // @hook actionObject*AddAfter
        Hook::exec('actionObjectAddAfter', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'AddAfter', ['object' => $this]);

        return $result;
    }

    
    public function duplicateObject() {

        $definition = PhenyxObjectModel::getDefinition($this);

        $res = Db::getInstance()->getRow('
                    SELECT *
                    FROM `' . _DB_PREFIX_ . bqSQL($definition['table']) . '`
                    WHERE `' . bqSQL($definition['primary']) . '` = ' . (int) $this->id
        );

        if (!$res) {
            return false;
        }

        unset($res[$definition['primary']]);

        foreach ($res as $field => &$value) {

            if (isset($definition['fields'][$field])) {
                $value = PhenyxObjectModel::formatValue($value, $definition['fields'][$field]['type'], false, true, !empty($definition['fields'][$field]['allow_null']));
            }

        }

        if (!Db::getInstance()->insert($definition['table'], $res)) {
            return false;
        }

        $objectId = Db::getInstance()->Insert_ID();

        if (isset($definition['multilang']) && $definition['multilang']) {
            $result = Db::getInstance()->executeS('
            SELECT *
            FROM `' . _DB_PREFIX_ . bqSQL($definition['table']) . '_lang`
            WHERE `' . bqSQL($definition['primary']) . '` = ' . (int) $this->id);

            if (!$result) {
                return false;
            }

            foreach ($result as &$row) {

                foreach ($row as $field => &$value) {

                    if (isset($definition['fields'][$field])) {
                        $value = PhenyxObjectModel::formatValue($value, $definition['fields'][$field]['type'], false, true, !empty($definition['fields'][$field]['allow_null']));
                    }

                }

            }

            // Keep $row2, you cannot use $row because there is an unexplicated conflict with the previous usage of this variable

            foreach ($result as $row2) {
                $row2[$definition['primary']] = (int) $objectId;

                if (!Db::getInstance()->insert($definition['table'] . '_lang', $row2)) {
                    return false;
                }

            }

        }

        $objectDuplicated = new $definition['classname']((int) $objectId);

        return $objectDuplicated;
    }
    
    public function update($nullValues = false) {

        // @hook actionObject*UpdateBefore
        Hook::exec('actionObjectUpdateBefore', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'UpdateBefore', ['object' => $this]);

        $this->clearCache();

        // Automatically fill dates

        if (property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');

            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_upd'] = true;
            }

        }

        // Automatically fill dates

        if (property_exists($this, 'date_add') && $this->date_add == null) {
            $this->date_add = date('Y-m-d H:i:s');

            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_add'] = true;
            }

        }        

        // Database update

        if (!$result = Db::getInstance()->update($this->def['table'], $this->getFields(), '`' . pSQL($this->def['primary']) . '` = ' . (int) $this->id, 0, $nullValues)) {
            return false;
        }

       
        // Database update for multilingual fields related to the object

        if (isset($this->def['multilang']) && $this->def['multilang']) {
            $fields = $this->getFieldsLang();

            if (is_array($fields)) {

                foreach ($fields as $field) {

                    foreach (array_keys($field) as $key) {

                        if (!Validate::isTableOrIdentifier($key)) {
                            throw new PhenyxShopException('key ' . $key . ' is not a valid table or identifier');
                        }

                    }

                    $where = pSQL($this->def['primary']) . ' = ' . (int) $this->id . ' AND id_lang = ' . (int) $field['id_lang'];

                    if (Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . pSQL(_DB_PREFIX_ . $this->def['table']) . '_lang WHERE ' . $where)) {
                            $result &= Db::getInstance()->update($this->def['table'] . '_lang', $field, $where);
                    } else {
                        $result &= Db::getInstance()->insert($this->def['table'] . '_lang', $field, $nullValues);
                    }

                }

            }

        }

        // @hook actionObject*UpdateAfter
        Hook::exec('actionObjectUpdateAfter', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'UpdateAfter', ['object' => $this]);

        return $result;
    }

    public function delete() {

        // @hook actionObject*DeleteBefore
        Hook::exec('actionObjectDeleteBefore', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'DeleteBefore', ['object' => $this]);

        $this->clearCache();
        $result = true;
        

        $result &= Db::getInstance()->delete($this->def['table'], '`' . bqSQL($this->def['primary']) . '` = ' . (int) $this->id);

        if (!$result) {
            return false;
        }
        
        if (!empty($this->def['multilang'])) {
            $result &= Db::getInstance()->delete($this->def['table'] . '_lang', '`' . bqSQL($this->def['primary']) . '` = ' . (int) $this->id);
        }

        // @hook actionObject*DeleteAfter
        Hook::exec('actionObjectDeleteAfter', ['object' => $this]);
        Hook::exec('actionObject' . get_class($this) . 'DeleteAfter', ['object' => $this]);

        return $result;
    }

    public function deleteSelection($ids) {

        $result = true;

        foreach ($ids as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }

        return $result;
    }

    public function toggleStatus() {

        // Object must have a variable called 'active'

        if (!property_exists($this, 'active')) {
            throw new PhenyxShopException('property "active" is missing in object ' . get_class($this));
        }

        // Update only active field
        $this->setFieldsToUpdate(['active' => true]);

        // Update active status on object
        $this->active = !(int) $this->active;

        // Change status to active/inactive
        return $this->update(false);
    }

    protected function getTranslationsFields($fieldsArray) {

        $fields = [];

        if ($this->id_lang == null) {

            foreach (Language::getIDs(false) as $id_lang) {
                $this->makeTranslationFields($fields, $fieldsArray, $id_lang);
            }

        } else {
            $this->makeTranslationFields($fields, $fieldsArray, $this->id_lang);
        }

        return $fields;
    }

    protected function makeTranslationFields(&$fields, &$fieldsArray, $idLanguage) {

        $fields[$idLanguage]['id_lang'] = $idLanguage;
        $fields[$idLanguage][$this->def['primary']] = (int) $this->id;

        foreach ($fieldsArray as $k => $field) {
            $html = false;
            $fieldName = $field;

            if (is_array($field)) {
                $fieldName = $k;
                $html = (isset($field['html'])) ? $field['html'] : false;
            }

            /* Check fields validity */

            if (!Validate::isTableOrIdentifier($fieldName)) {
                throw new PhenyxShopException('identifier is not table or identifier : ' . $fieldName);
            }

            // Copy the field, or the default language field if it's both required and empty

            if ((!$this->id_lang && isset($this->{$fieldName}

                [$idLanguage]) && !empty($this->{$fieldName}

                [$idLanguage]))
                || ($this->id_lang && isset($this->$fieldName) && !empty($this->$fieldName))) {
                $fields[$idLanguage][$fieldName] = $this->id_lang ? pSQL($this->$fieldName, $html) : pSQL($this->{$fieldName}

                    [$idLanguage], $html);
            } else
            if (in_array($fieldName, $this->fieldsRequiredLang)) {
                $fields[$idLanguage][$fieldName] = pSQL($this->id_lang ? $this->$fieldName : $this->{$fieldName}

                    [Configuration::get('EPH_LANG_DEFAULT')], $html);
            } else {
                $fields[$idLanguage][$fieldName] = '';
            }

        }

    }

    
    public function validateFields($die = true, $errorReturn = false) {

        foreach ($this->def['fields'] as $field => $data) {

            if (!empty($data['lang'])) {
                continue;
            }

            $message = $this->validateField($field, $this->$field);

            if ($message !== true) {

                $return = [
        			'success' => false,
            		'message' => $message,
        		];

                die(Tools::jsonEncode($return));
            }

        }

        return true;
    }

    public function validateFieldsLang($die = true, $errorReturn = false) {

        $idLangDefault = Configuration::get('EPH_LANG_DEFAULT');

        foreach ($this->def['fields'] as $field => $data) {

            if (empty($data['lang'])) {
                continue;
            }

            $values = $this->$field;

            // If the object has not been loaded in multilanguage, then the value is the one for the current language of the object

            if (!is_array($values)) {
                $values = [$this->id_lang => $values];
            }

            // The value for the default must always be set, so we put an empty string if it does not exists

            if (!isset($values[$idLangDefault])) {
                $values[$idLangDefault] = '';
            }

            foreach ($values as $idLang => $value) {

                if (is_array($this->update_fields) && empty($this->update_fields[$field][$idLang])) {
                    continue;
                }

                $message = $this->validateField($field, $value, $idLang);

                if ($message !== true) {

                    $return = [
        				'success' => false,
            			'message' => $message,
        			];

                	die(Tools::jsonEncode($return));
                }

            }

        }

        return true;
    }

    public function validateField($field, $value, $idLang = null, $skip = [], $humanErrors = false) {

        static $psLangDefault = null;
        static $psAllowHtmlIframe = null;

        if ($psLangDefault === null) {
            $psLangDefault = Configuration::get('EPH_LANG_DEFAULT');
        }

        if ($psAllowHtmlIframe === null) {
            $psAllowHtmlIframe = (int) Configuration::get('EPH_ALLOW_HTML_IFRAME');
        }

        $this->cacheFieldsRequiredDatabase();
        $data = $this->def['fields'][$field];

        // Check if field is required
        $requiredFields = (isset(static::$fieldsRequiredDatabase[get_class($this)])) ? static::$fieldsRequiredDatabase[get_class($this)] : [];

        if (!$idLang || $idLang == $psLangDefault) {

            if (!in_array('required', $skip) && (!empty($data['required']) || in_array($field, $requiredFields))) {

                if (Tools::isEmpty($value)) {

                    if ($humanErrors) {
                       $message = sprintf(Tools::displayError('The %s field is required.'), $this->displayFieldName($field, get_class($this)));
                    } else {
                        $message = 'Property ' . get_class($this) . '->' . $field . ' is empty';
                    }
					
					$return = [
        				'success' => false,
            			'message' => $message,
        			];

                	die(Tools::jsonEncode($return));

                }

            }

        }

        // Default value

        if (!$value && !empty($data['default'])) {
            $value = $data['default'];
            $this->$field = $value;
        }

        // Check field values

        if (!in_array('values', $skip) && !empty($data['values']) && is_array($data['values']) && !in_array($value, $data['values'])) {
            return 'Property ' . get_class($this) . '->' . $field . ' has bad value (allowed values are: ' . implode(', ', $data['values']) . ')';
        }

        // Check field size

        if (!in_array('size', $skip) && !empty($data['size'])) {
            $size = $data['size'];

            if (!is_array($data['size'])) {
                $size = ['min' => 0, 'max' => $data['size']];
            }
			$length = 0;
			if(!is_null($value) && is_string($value)) {
				$length = mb_strlen($value);
			}
            

            if ($length < $size['min'] || $length > $size['max']) {

                if ($humanErrors) {

                    if (isset($data['lang']) && $data['lang']) {
                        $language = new Language((int) $idLang);
                        $message =  sprintf(Tools::displayError('The field %1$s (%2$s) is too long (%3$d chars max, html chars including).'), $this->displayFieldName($field, get_class($this)), $language->name, $size['max']);
                    } else {
                        $message = sprintf(Tools::displayError('The %1$s field is too long (%2$d chars max).'), $this->displayFieldName($field, get_class($this)), $size['max']);
                    }
					
					$return = [
        				'success' => false,
            			'message' => $message,
        			];

                	die(Tools::jsonEncode($return));

                } else {
                   $message = 'Property ' . get_class($this) . '->' . $field . ' length (' . $length . ') must be between ' . $size['min'] . ' and ' . $size['max'];
					
					$return = [
        				'success' => false,
            			'message' => $message,
        			];

                	die(Tools::jsonEncode($return));
                }

            }

        }

        // Check field validator

        if (!in_array('validate', $skip) && !empty($data['validate'])) {

            if (!method_exists('Validate', $data['validate'])) {
                throw new PhenyxShopException('Validation function not found. ' . $data['validate']);
            }

            if (!empty($value)) {
                $res = true;

                if (mb_strtolower($data['validate']) == 'iscleanhtml') {

                    if (!call_user_func(['Validate', $data['validate']], $value, $psAllowHtmlIframe)) {
                        $res = false;
                    }

                } else {

                    if (!call_user_func(['Validate', $data['validate']], $value)) {
                        $res = false;
                    }

                }

                if (!$res) {

                    if ($humanErrors) {
                        $message = sprintf(Tools::displayError('The %s field is invalid.'), $this->displayFieldName($field, get_class($this)));
                    } else {
                        $message = 'Property ' . get_class($this) . '->' . $field . ' is not valid';
                    }
					
					$return = [
        				'success' => false,
            			'message' => $message,
        			];

                	die(Tools::jsonEncode($return));

                }

            }

        }

        return true;
    }

    public static function displayFieldName($field, $class = __CLASS__, $htmlentities = true, Context $context = null) {

        global $_FIELDS;

        if (!isset($context)) {
            $context = Context::getContext();
        }

        if ($_FIELDS === null && file_exists(_EPH_TRANSLATIONS_DIR_ . $context->language->iso_code . '/fields.php')) {
            include_once _EPH_TRANSLATIONS_DIR_ . $context->language->iso_code . '/fields.php';
        }

        $key = $class . '_' . md5($field);

        return ((is_array($_FIELDS) && array_key_exists($key, $_FIELDS)) ? ($htmlentities ? htmlentities($_FIELDS[$key], ENT_QUOTES, 'utf-8') : $_FIELDS[$key]) : $field);
    }

    public function validateControler($htmlentities = true) {

        Tools::displayAsDeprecated();

        return $this->validateController($htmlentities);
    }

    public function validateController($htmlentities = true) {

        $this->cacheFieldsRequiredDatabase();
        $errors = [];
        $requiredFieldsDatabase = (isset(static::$fieldsRequiredDatabase[get_class($this)])) ? static::$fieldsRequiredDatabase[get_class($this)] : [];

        foreach ($this->def['fields'] as $field => $data) {
            $value = Tools::getValue($field, $this->{$field});
            // Check if field is required by user

            if (in_array($field, $requiredFieldsDatabase)) {
                $data['required'] = true;
            }

            // Checking for required fields

            if (isset($data['required']) && $data['required'] && empty($value) && $value !== '0') {

                if (!$this->id || $field != 'passwd') {
                    $errors[$field] = '<b>' . static::displayFieldName($field, get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
                }

            }

            // Checking for maximum fields sizes

            if (isset($data['size']) && !empty($value) && is_string($value) && mb_strlen($value) > $data['size']) {
                $errors[$field] = sprintf(
                    Tools::displayError('%1$s is too long. Maximum length: %2$d'),
                    static::displayFieldName($field, get_class($this), $htmlentities),
                    $data['size']
                );
            }

            // Checking for fields validity
            // Hack for postcode required for country which does not have postcodes

            if (!empty($value) || $value === '0' || ($field == 'postcode' && $value == '0')) {
                $validationError = false;

                if (isset($data['validate'])) {
                    $dataValidate = $data['validate'];

                    if (!Validate::$dataValidate($value) && (!empty($value) || $data['required'])) {
                        $errors[$field] = '<b>' . static::displayFieldName($field, get_class($this), $htmlentities) .
                        '</b> ' . Tools::displayError('is invalid.');
                        $validationError = true;
                    }

                }

                if (!$validationError) {

                    if (isset($data['copy_post']) && !$data['copy_post']) {
                        continue;
                    }

                    if ($field == 'passwd') {

                        if ($value = Tools::getValue($field)) {
                            $this->{$field}

                            = Tools::hash($value);
                        }

                    } else {
                        $this->{$field}

                        = $value;
                    }

                }

            }

        }

        return $errors;
    }

    public function validateFieldsRequiredDatabase($htmlentities = true) {

        $this->cacheFieldsRequiredDatabase();
        $errors = [];
        $requiredFields = (isset(static::$fieldsRequiredDatabase[get_class($this)])) ? static::$fieldsRequiredDatabase[get_class($this)] : [];

        foreach ($this->def['fields'] as $field => $data) {

            if (!in_array($field, $requiredFields)) {
                continue;
            }

            if (!method_exists('Validate', $data['validate'])) {
                throw new PhenyxShopException('Validation function not found. ' . $data['validate']);
            }

            $value = Tools::getValue($field);

            if (empty($value)) {
                $errors[$field] = sprintf(Tools::displayError('The field %s is required.'), static::displayFieldName($field, get_class($this), $htmlentities));
            }

        }

        return $errors;
    }

    public function getFieldsRequiredDatabase($all = false) {

        return Db::getInstance()->executeS('
        SELECT id_required_field, object_name, field_name
        FROM ' . _DB_PREFIX_ . 'required_field
        ' . (!$all ? 'WHERE object_name = \'' . pSQL(get_class($this)) . '\'' : ''));
    }

    public function cacheFieldsRequiredDatabase($all = true) {

        if (!is_array(static::$fieldsRequiredDatabase)) {
            $fields = $this->getfieldsRequiredDatabase((bool) $all);

            if ($fields) {

                foreach ($fields as $row) {
                    static::$fieldsRequiredDatabase[$row['object_name']][(int) $row['id_required_field']] = pSQL($row['field_name']);
                }

            } else {
                static::$fieldsRequiredDatabase = [];
            }

        }

    }

    public function addFieldsRequiredDatabase($fields) {

        if (!is_array($fields)) {
            return false;
        }

        if (!Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'required_field WHERE object_name = \'' . get_class($this) . '\'')) {
            return false;
        }

        foreach ($fields as $field) {

            if (!Db::getInstance()->insert('required_field', ['object_name' => get_class($this), 'field_name' => pSQL($field)])) {
                return false;
            }

        }

        return true;
    }

    public function clearCache($all = false) {

        if ($all) {
            Cache::clean('objectmodel_' . $this->def['classname'] . '_*');
        } else
        if ($this->id) {
            Cache::clean('objectmodel_' . $this->def['classname'] . '_' . (int) $this->id . '_*');
        }

    }
    
    public function deleteImage($forceDelete = false) {

        if (!$this->id) {
            return false;
        }

        if ($forceDelete) {
            /* Deleting object images and thumbnails (cache) */

            if ($this->image_dir) {

                if (file_exists($this->image_dir . $this->id . '.' . $this->image_format)
                    && !unlink($this->image_dir . $this->id . '.' . $this->image_format)) {
                    return false;
                }

            }

            if (file_exists(_EPH_TMP_IMG_DIR_ . $this->def['table'] . '_' . $this->id . '.' . $this->image_format)
                && !unlink(_EPH_TMP_IMG_DIR_ . $this->def['table'] . '_' . $this->id . '.' . $this->image_format)) {
                return false;
            }

            if (file_exists(_EPH_TMP_IMG_DIR_ . $this->def['table'] . '_mini_' . $this->id . '.' . $this->image_format)
                && !unlink(_EPH_TMP_IMG_DIR_ . $this->def['table'] . '_mini_' . $this->id . '.' . $this->image_format)) {
                return false;
            }

            $types = ImageType::getImagesTypes();

            foreach ($types as $imageType) {

                if (file_exists($this->image_dir . $this->id . '-' . stripslashes($imageType['name']) . '.' . $this->image_format)
                    && !unlink($this->image_dir . $this->id . '-' . stripslashes($imageType['name']) . '.' . $this->image_format)) {
                    return false;
                }

            }

        }

        return true;
    }

    public static function existsInDatabase($idEntity, $table) {

        $row = Db::getInstance()->getRow('
            SELECT `id_' . bqSQL($table) . '` as id
            FROM `' . _DB_PREFIX_ . bqSQL($table) . '` e
            WHERE e.`id_' . bqSQL($table) . '` = ' . (int) $idEntity, false
        );

        return isset($row['id']);
    }

    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false) {

        if ($table === null) {
            $table = static::$definition['table'];
        }

        $query = new DbQuery();
        $query->select('`id_' . bqSQL($table) . '`');
        $query->from($table);

        if ($hasActiveColumn) {
            $query->where('`active` = 1');
        }

        return (bool) Db::getInstance()->getValue($query);
    }

    public function hydrate(array $data, $idLang = null) {

        $this->id_lang = $idLang;

        if (isset($data[$this->def['primary']])) {
            $this->id = $data[$this->def['primary']];
        }

        foreach ($data as $key => $value) {

            if (property_exists($this, $key)) {
                $this->$key = $value;
            }

        }

    }

    public function hydrateMultilang(array $data) {

        foreach ($data as $row) {

            if (isset($row[$this->def['primary']])) {
                $this->id = $row[$this->def['primary']];
            }

            foreach ($row as $key => $value) {

                if (array_key_exists($key, $this)) {

                    if (!empty($this->def['fields'][$key]['lang']) && !empty($row['id_lang'])) {
                        // Multilang

                        if (!is_array($this->$key)) {
                            $this->$key = [];
                        }

                        $this->$key[(int) $row['id_lang']] = $value;
                    } else {
                        // Normal

                        if (array_key_exists($key, $this)) {
                            $this->$key = $value;
                        }

                    }

                }

            }

        }

    }

    public static function hydrateCollection($class, array $datas, $idLang = null) {

        if (!class_exists($class)) {
            throw new PhenyxShopException("Class '$class' not found");
        }

        $collection = [];
        $rows = [];

        if ($datas) {
            $definition = PhenyxObjectModel::getDefinition($class);

            if (!array_key_exists($definition['primary'], $datas[0])) {
                throw new PhenyxShopException("Identifier '{$definition['primary']}' not found for class '$class'");
            }

            foreach ($datas as $row) {
                // Get object common properties
                $id = $row[$definition['primary']];

                if (!isset($rows[$id])) {
                    $rows[$id] = $row;
                }

                // Get object lang properties

                if (isset($row['id_lang']) && !$idLang) {

                    foreach ($definition['fields'] as $field => $data) {

                        if (!empty($data['lang'])) {

                            if (!is_array($rows[$id][$field])) {
                                $rows[$id][$field] = [];
                            }

                            $rows[$id][$field][$row['id_lang']] = $row[$field];
                        }

                    }

                }

            }

        }

        // Hydrate objects

        foreach ($rows as $row) {
            /** @var ObjectModel $obj */
            $obj = new $class();
            $obj->hydrate($row, $idLang);
            $collection[] = $obj;
        }

        return $collection;
    }

    public static function getDefinition($class, $field = null) {

        if (is_object($class)) {
            $class = get_class($class);
        }

        if ($field === null) {
            $cacheId = 'objectmodel_def_' . $class;
        }

        if ($field !== null || !Cache::isStored($cacheId)) {
            $reflection = new ReflectionClass($class);

            if (!$reflection->hasProperty('definition')) {
                return false;
            }

            $definition = $reflection->getStaticPropertyValue('definition');

            $definition['classname'] = $class;

            if (!empty($definition['multilang'])) {
                $definition['associations'][PhenyxShopCollection::LANG_ALIAS] = [
                    'type'          => static::HAS_MANY,
                    'field'         => $definition['primary'],
                    'foreign_field' => $definition['primary'],
                ];
            }

            if ($field) {
                return isset($definition['fields'][$field]) ? $definition['fields'][$field] : null;
            }

            Cache::store($cacheId, $definition);

            return $definition;
        }

        return Cache::retrieve($cacheId);
    }

    protected function setDefinitionRetrocompatibility() {

        // Retrocompatibility with $table property ($definition['table'])

        if (isset($this->def['table'])) {
            $this->table = $this->def['table'];
        } else {
            $this->def['table'] = $this->table;
        }

        // Retrocompatibility with $identifier property ($definition['primary'])

        if (isset($this->def['primary'])) {
            $this->identifier = $this->def['primary'];
        } else {
            $this->def['primary'] = $this->identifier;
        }

        // Check multilang retrocompatibility

        if (method_exists($this, 'getTranslationsFieldsChild')) {
            $this->def['multilang'] = true;
        }

        // Retrocompatibility with $fieldsValidate, $fieldsRequired and $fieldsSize properties ($definition['fields'])

        if (isset($this->def['fields'])) {

            foreach ($this->def['fields'] as $field => $data) {
                $suffix = (isset($data['lang']) && $data['lang']) ? 'Lang' : '';

                if (isset($data['validate'])) {
                    $this->{'fieldsValidate' . $suffix}

                    [$field] = $data['validate'];
                }

                if (isset($data['required']) && $data['required']) {
                    $this->{'fieldsRequired' . $suffix}

                    [] = $field;
                }

                if (isset($data['size'])) {
                    $this->{'fieldsSize' . $suffix}

                    [$field] = $data['size'];
                }

            }

        } else {
            $this->def['fields'] = [];
            $suffixs = ['', 'Lang'];

            foreach ($suffixs as $suffix) {

                foreach ($this->{'fieldsValidate' . $suffix} as $field => $validate) {
                    $this->def['fields'][$field]['validate'] = $validate;

                    if ($suffix == 'Lang') {
                        $this->def['fields'][$field]['lang'] = true;
                    }

                }

                foreach ($this->{'fieldsRequired' . $suffix} as $field) {
                    $this->def['fields'][$field]['required'] = true;

                    if ($suffix == 'Lang') {
                        $this->def['fields'][$field]['lang'] = true;
                    }

                }

                foreach ($this->{'fieldsSize' . $suffix} as $field => $size) {
                    $this->def['fields'][$field]['size'] = $size;

                    if ($suffix == 'Lang') {
                        $this->def['fields'][$field]['lang'] = true;
                    }

                }

            }

        }

    }

    public function getFieldByLang($fieldName, $idLang = null) {

        $definition = PhenyxObjectModel::getDefinition($this);
        // Is field in definition?

        if ($definition && isset($definition['fields'][$fieldName])) {
            $field = $definition['fields'][$fieldName];
            // Is field multilang?

            if (isset($field['lang']) && $field['lang']) {

                if (is_array($this->{$fieldName})) {
                    return $this->{$fieldName}

                    [$idLang ?: Context::getContext()->language->id];
                }

            }

            return $this->{$fieldName};
        } else {
            throw new PhenyxShopException('Could not load field from definition.');
        }

    }

    public function setFieldsToUpdate(array $fields) {

        $this->update_fields = $fields;
    }

    public static function enableCache() {

        PhenyxObjectModel::$cache_objects = true;
    }

    public static function disableCache() {

        PhenyxObjectModel::$cache_objects = false;
    }

    public static function createDatabase($className = null) {

        $success = true;

        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = static::getDefinition($className);
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . bqSQL($definition['table']) . '` (';
        $sql .= '`' . $definition['primary'] . '` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,';

        foreach ($definition['fields'] as $fieldName => $field) {

            if ($fieldName === $definition['primary']) {
                continue;
            }

            if (isset($field['lang']) && $field['lang']) {
                continue;
            }

            if (empty($field['db_type'])) {

                switch ($field['type']) {
                case '1':
                    $field['db_type'] = 'INT(11) UNSIGNED';
                    break;
                case '2':
                    $field['db_type'] .= 'TINYINT(1)';
                    break;
                case '3':
                    (isset($field['size']) && $field['size'] > 256)
                    ? $field['db_type'] = 'VARCHAR(256)'
                    : $field['db_type'] = 'VARCHAR(512)';
                    break;
                case '4':
                    $field['db_type'] = 'DECIMAL(20,6)';
                    break;
                case '5':
                    $field['db_type'] = 'DATETIME';
                    break;
                case '6':
                    $field['db_type'] = 'TEXT';
                    break;
                }

            }

            $sql .= '`' . $fieldName . '` ' . $field['db_type'];

            if (isset($field['required'])) {
                $sql .= ' NOT NULL';
            }

            if (isset($field['default'])) {
                $sql .= ' DEFAULT \'' . $field['default'] . '\'';
            }

            $sql .= ',';
        }

        $sql = trim($sql, ',');
        $sql .= ')';

        try {
            $success &= Db::getInstance()->execute($sql);
        } catch (\PhenyxShopDatabaseException $exception) {
            static::dropDatabase($className);

            return false;
        }

        if (isset($definition['multilang']) && $definition['multilang']) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . bqSQL($definition['table']) . '_lang` (';
            $sql .= '`' . $definition['primary'] . '` INT(11) UNSIGNED NOT NULL,';

            foreach ($definition['fields'] as $fieldName => $field) {

                if ($fieldName === $definition['primary'] || !(isset($field['lang']) && $field['lang'])) {
                    continue;
                }

                $sql .= '`' . $fieldName . '` ' . $field['db_type'];

                if (isset($field['required'])) {
                    $sql .= ' NOT NULL';
                }

                if (isset($field['default'])) {
                    $sql .= ' DEFAULT \'' . $field['default'] . '\'';
                }

                $sql .= ',';
            }

            // Lang field
            $sql .= '`id_lang` INT(11) NOT NULL,';


            // Primary key
            $sql .= 'PRIMARY KEY (`' . bqSQL($definition['primary']) . '`, `id_lang`)';

            $sql .= ')';

            try {
                $success &= Db::getInstance()->execute($sql);
            } catch (\PhenyxShopDatabaseException $exception) {
                static::dropDatabase($className);

                return false;
            }

        }       

        return $success;
    }

    
    public static function dropDatabase($className = null) {

        $success = true;

        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = \PhenyxObjectModel::getDefinition($className);

        $success &= Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . bqSQL($definition['table']) . '`');

        if (isset($definition['multilang']) && $definition['multilang']) {
            $success &= Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . bqSQL($definition['table']) . '_lang`');
        }

        return $success;
    }

    public static function getDatabaseColumns($className = null) {

        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = \PhenyxObjectModel::getDefinition($className);

        $sql = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\'' . _DB_NAME_ . '\' AND TABLE_NAME=\'' . _DB_PREFIX_ . pSQL($definition['table']) . '\'';

        return Db::getInstance()->executeS($sql);
    }

    public static function createColumn($name, $columnDefinition, $className = null) {

        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = static::getDefinition($className);
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . bqSQL($definition['table']) . '`';
        $sql .= ' ADD COLUMN `' . bqSQL($name) . '` ' . bqSQL($columnDefinition['db_type']) . '';

        if ($name === $definition['primary']) {
            $sql .= ' INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT';
        } else {

            if (isset($columnDefinition['required']) && $columnDefinition['required']) {
                $sql .= ' NOT NULL';
            }

            if (isset($columnDefinition['default'])) {
                $sql .= ' DEFAULT "' . pSQL($columnDefinition['default']) . '"';
            }

        }

        return (bool) Db::getInstance()->execute($sql);
    }

    public static function createMissingColumns($className = null) {

        if (empty($className)) {
            $className = get_called_class();
        }

        $success = true;

        $definition = static::getDefinition($className);
        $columns = static::getDatabaseColumns();

        foreach ($definition['fields'] as $columnName => $columnDefinition) {
            //column exists in database
            $exists = false;

            foreach ($columns as $column) {

                if ($column['COLUMN_NAME'] === $columnName) {
                    $exists = true;
                    break;
                }

            }

            if (!$exists) {
                $success &= static::createColumn($columnName, $columnDefinition);
            }

        }

        return $success;
    }
	
	

}
