<?php

/**
 * Class ImageTypeCore
 *
 * @since 1.9.1.0
 */
class ImageTypeCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @var array Image types cache
     */
    protected static $images_types_cache = [];
    /** @var array $images_types_name_cache */
    protected static $images_types_name_cache = [];
    /** @var string Name */
    public $name;
    /** @var int Width */
    public $width;
    /** @var int Height */
    public $height;
    /** @var bool Apply to products */
    public $products;
    /** @var int Apply to categories */
    public $categories;
    /** @var int Apply to manufacturers */
    public $manufacturers;
    /** @var int Apply to suppliers */
    public $suppliers;
    /** @var int Apply to store */
    public $stores;
    // @codingStandardsIgnoreEnd

    const SINGULAR_DIR = [
        'img'          => ['dir' => _EPH_IMG_DIR_, 'iterate' => false],
        'module'       => ['dir' => _EPH_MODULE_DIR_, 'iterate' => true],
        'category'     => ['dir' => _EPH_CAT_IMG_DIR_, 'iterate' => false],
        'manufacturer' => ['dir' => _EPH_MANU_IMG_DIR_, 'iterate' => false],
        'supplier'     => ['dir' => _EPH_SUPP_IMG_DIR_, 'iterate' => false],
        'product'      => ['dir' => _EPH_PROD_IMG_DIR_, 'iterate' => true],
        'store'        => ['dir' => _EPH_STORE_IMG_DIR_, 'iterate' => false],

    ];

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'image_type',
        'primary' => 'id_image_type',
        'fields'  => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'width'         => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'height'        => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'categories'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'products'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'manufacturers' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'suppliers'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'stores'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected $webserviceParameters = [];

    /**
     * Returns image type definitions
     *
     * @param string|null $type Image type
     * @param bool        $orderBySize
     *
     * @return array Image type definitions
     * @throws PhenyxShopDatabaseException
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getImagesTypes($type = null, $orderBySize = false) {

        // @codingStandardsIgnoreStart

        if (!isset(static::$images_types_cache[$type])) {
            $query = (new DbQuery())
                ->select('*')
                ->from('image_type');

            if (!empty($type)) {
                $query->where('`' . bqSQL($type) . '` = 1');
            }

            if ($orderBySize) {
                $query->orderBy('`width` DESC, `height` DESC, `name` ASC');
            } else {
                $query->orderBy('`name` ASC');
            }

            static::$images_types_cache[$type] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
        }

        return static::$images_types_cache[$type];
        // @codingStandardsIgnoreEnd
    }

    public static function getImages() {

        $list = [];

        $singularTypes = self::SINGULAR_DIR;

        foreach ($singularTypes as $key => $singularType) {

            $result = ImageType::getFolderImg($singularType['dir'], $singularType['iterate']);
            $list[$key]['todo'] = $result['todo'];

            $list[$key]['done'] = $result['done'];
            $list[$key]['total'] = $result['done'] + sizeof($result['todo']);


        }

        return $list;
    }

    public static function getFolderImg($folder, $iterate = false, $done = false) {

        $images = [];
        $done = 0;

        //if (!isset(static::$_image_type_check_cache)) {

        if ($iterate) {

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

            foreach ($iterator as $filename) {

                if (preg_match('/\.(jpg|png|jpeg)$/', $filename->getBasename())) {
                    $extension = '.' . $filename->getExtension();
                    $name = str_replace($extension, '.webp', $filename->getBasename());

                    if (file_exists($filename->getPath() . '/' . $name)) {
                        $done++;
                    } else {
                        $images[] = [$filename->getPath() . DIRECTORY_SEPARATOR, $filename->getBasename()];
                    }

                }

            }

        } else {

            foreach (glob($folder . '*.{jpg,JPG,jpeg,JPEG,png,PNG}', GLOB_BRACE) as $file) {

                $path_parts = pathinfo($folder . $file);

                $name = pathinfo($folder . $file, PATHINFO_FILENAME) . '.webp';

                if (file_exists($folder . $name)) {
                    $done++;
                } else {
                    $images[] = [$folder, basename($file)];
                }

            }

        }

        return [
            'done' => $done,
            'todo' => $images,

        ];

        //return static::$_image_type_check_cache;
        //  }

        //return static::$_image_type_check_cache;

    }

    /**
     * Check if type already is already registered in database
     *
     * @param string $typeName Name
     *
     * @return int Number of results found
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function typeAlreadyExists($typeName) {

        if (!Validate::isImageTypeName($typeName)) {
            die(Tools::displayError());
        }

        Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_image_type`')
                ->from('image_type')
                ->where('`name` = \'' . pSQL($typeName) . '\'')
        );

        return Db::getInstance()->NumRows();
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getFormatedName($name) {

        $themeName = Context::getContext()->company->theme_name;
        $nameWithoutThemeName = str_replace(['_' . $themeName, $themeName . '_'], '', $name);

        //check if the theme name is already in $name if yes only return $name

        if (strstr($name, $themeName) && static::getByNameNType($name)) {
            return $name;
        } else if (static::getByNameNType($nameWithoutThemeName . '_' . $themeName)) {
            return $nameWithoutThemeName . '_' . $themeName;
        } else if (static::getByNameNType($themeName . '_' . $nameWithoutThemeName)) {
            return $themeName . '_' . $nameWithoutThemeName;
        } else {
            return $nameWithoutThemeName . '_default';
        }

    }

    /**
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string $type
     *
     * @param int    $order
     *
     * @return bool|mixed
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getByNameNType($name, $type = null, $order = 0) {

        static $isPassed = false;

        // @codingStandardsIgnoreStart

        if (!isset(static::$images_types_name_cache[$name . '_' . $type . '_' . $order]) && !$isPassed) {
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'image_type`');

            $types = ['products', 'categories', 'manufacturers', 'suppliers', 'stores'];
            $total = count($types);

            foreach ($results as $result) {

                foreach ($result as $value) {

                    for ($i = 0; $i < $total; ++$i) {
                        static::$images_types_name_cache[$result['name'] . '_' . $types[$i] . '_' . $value] = $result;
                    }

                }

            }

            $isPassed = true;
        }

        $return = false;

        if (isset(static::$images_types_name_cache[$name . '_' . $type . '_' . $order])) {
            $return = static::$images_types_name_cache[$name . '_' . $type . '_' . $order];
        }

        // @codingStandardsIgnoreEnd

        return $return;
    }

}
