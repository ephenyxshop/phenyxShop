<?php

/**
 * Class AttachmentCore
 *
 * @since 1.9.1.0
 */
class AttachmentCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string $file */
    public $file;
    /** @var string $file_name */
    public $file_name;
    /** @var int $file_size */
    public $file_size;
    /** @var string $name */
    public $name;
    /** @var string $mime */
    public $mime;
    /** @var string $description */
    public $description;
    /** @var int position */
    public $position;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'attachment',
        'primary'   => 'id_attachment',
        'multilang' => true,
        'fields'    => [
            'file'        => ['type' => self::TYPE_STRING,                 'validate' => 'isGenericName',                 'required' => true, 'size' => 40],
            'mime'        => ['type' => self::TYPE_STRING,                 'validate' => 'isCleanHtml',                   'required' => true, 'size' => 128],
            'file_name'   => ['type' => self::TYPE_STRING,                 'validate' => 'isGenericName',                                     'size' => 128],
            'file_size'   => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedId'],

            /* Lang fields */
            'name'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->file_size = filesize(_EPH_DOWNLOAD_DIR_.$this->file);

        return parent::add($autoDate, $nullValues);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function update($nullValues = false)
    {
        $this->file_size = filesize(_EPH_DOWNLOAD_DIR_.$this->file);

        return parent::update($nullValues);
    }

    /**
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function delete()
    {
        @unlink(_EPH_DOWNLOAD_DIR_.$this->file);

        $products = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_product`')
                ->from('product_attachment')
                ->where('`id_attachment` = '.(int) $this->id)
        );

        Db::getInstance()->delete('product_attachment', '`id_attachment` = '.(int) $this->id);

        foreach ($products as $product) {
            Product::updateCacheAttachment((int) $product['id_product']);
        }

        return parent::delete();
    }

    /**
     * @param array $attachments
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function deleteSelection($attachments)
    {
        if (empty($attachments)) {
            return true;
        }

        $return = true;

        $attachmentsData = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from(bqSQL(Attachment::$definition['table']))
                ->where('`id_attachment` IN ('.implode(',', $attachments).')')
        );

        if (empty($attachmentsData)) {
            return true;
        }

        foreach ($attachmentsData as $attachmentData) {
            $attachment = new Attachment();
            $attachment->hydrate($attachmentData);
            $return &= $attachment->delete();
        }

        return $return;
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     *
     * @param int  $idLang
     * @param int  $idProduct
     * @param bool $include
     *
     * @return array|false|null|PDOStatement
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function getAttachments($idLang, $idProduct, $include = true)
    {
        return Db::getInstance()->executeS('
            SELECT *
            FROM '._DB_PREFIX_.'attachment a
            LEFT JOIN '._DB_PREFIX_.'attachment_lang al
                ON (a.id_attachment = al.id_attachment AND al.id_lang = '.(int) $idLang.')
            WHERE a.id_attachment '.($include ? 'IN' : 'NOT IN').' (
                SELECT pa.id_attachment
                FROM '._DB_PREFIX_.'product_attachment pa
                WHERE id_product = '.(int) $idProduct.'
            )'
        );
    }

    /**
     * Unassociate $id_product from the current object
     *
     * @param int $idProduct
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public static function deleteProductAttachments($idProduct)
    {
        $res = Db::getInstance()->delete(
            'product_attachment',
            '`id_product` = '.(int) $idProduct
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * associate $id_product to the current object.
     *
     * @param int $idProduct id of the product to associate
     *
     * @return bool true if succed
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function attachProduct($idProduct)
    {
        $res = Db::getInstance()->insert(
            'product_attachment',
            [
                'id_attachment' => (int) $this->id,
                'id_product'    => (int) $idProduct,
            ]
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * Associate an array of id_attachment $array to the product $id_product
     * and remove eventual previous association
     *
     * @param int   $idProduct
     * @param array $array
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function attachToProduct($idProduct, $array)
    {
        $result1 = Attachment::deleteProductAttachments($idProduct);

        if (is_array($array)) {
            $ids = [];
            foreach ($array as $idAttachment) {
                if ((int) $idAttachment > 0) {
                    $ids[] = ['id_product' => (int) $idProduct, 'id_attachment' => (int) $idAttachment];
                }
            }

            if (!empty($ids)) {
                $result2 = Db::getInstance()->insert('product_attachment', $ids);
            }
        }

        Product::updateCacheAttachment((int) $idProduct);
        if (is_array($array)) {
            return ($result1 && (!isset($result2) || $result2));
        }

        return $result1;
    }

    /**
     * @param int   $idLang
     * @param array $list
     *
     * @return array|bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getProductAttached($idLang, $list)
    {
        $idAttachments = [];
        if (is_array($list)) {
            foreach ($list as $attachment) {
                $idAttachments[] = $attachment['id_attachment'];
            }

            $tmp = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('product_attachment', 'pa')
                    ->leftJoin('product_lang', 'pl', 'pa.`id_product` = pl.`id_product`')
                    ->where('pa.`id_attachment` IN ('.implode(',', array_map('intval', $idAttachments)).')')
                    ->where('pl.`id_lang` = '.(int) $idLang)
            );
            $productAttachments = [];
            foreach ($tmp as $t) {
                $productAttachments[$t['id_attachment']][] = $t['name'];
            }

            return $productAttachments;
        } else {
            return false;
        }
    }
}
