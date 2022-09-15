<?php

/**
 * Class ShopUrlCore
 *
 * @since 1.9.1.0
 */
class ShopUrlCore extends PhenyxObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_shop */
    public $id_shop;
    /** @var string $domain */
    public $domain;
    /** @var string $domain_ssl */
    public $domain_ssl;
	
	public $admin_ssl;
    /** @var string $physical_uri */
    public $physical_uri;
    /** @var string $virtual_uri */
    public $virtual_uri;
    /** @var bool $main */
    public $main;
    /** @var bool $active */
    public $active;
    /** @var array $main_domain */
    protected static $main_domain = [];
    /** @var array $main_domain_ssl */
    protected static $main_domain_ssl = [];
	
	protected static $main_admin_ssl = [];
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'shop_url',
        'primary' => 'id_shop_url',
        'fields'  => [
            'active'       => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'main'         => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'domain'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'required' => true, 'size' => 255],
            'domain_ssl'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 255],
			'admin_ssl'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',                       'size' => 255],
            'id_shop'      => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true               ],
            'physical_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isString',                          'size' => 64 ],
            'virtual_uri'  => ['type' => self::TYPE_STRING, 'validate' => 'isString',                          'size' => 64 ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_shop' => ['xlink_resource' => 'shops'],
        ],
    ];

    /**
     * @see     PhenyxObjectModel::getFields()
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function getFields()
    {
        $this->domain = trim($this->domain);
        $this->domain_ssl = trim($this->domain_ssl);
		$this->admin_ssl = trim($this->admin_ssl);
        $this->physical_uri = trim(str_replace(' ', '', $this->physical_uri), '/');
		

        if ($this->physical_uri) {
            $this->physical_uri = preg_replace('#/+#', '/', '/'.$this->physical_uri.'/');
        } else {
            $this->physical_uri = '/';
        }

        $this->virtual_uri = trim(str_replace(' ', '', $this->virtual_uri), '/');
        if ($this->virtual_uri) {
            $this->virtual_uri = preg_replace('#/+#', '/', trim($this->virtual_uri, '/')).'/';
        }

        return parent::getFields();
    }

    /**
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getBaseURI()
    {
        return $this->physical_uri.$this->virtual_uri;
    }

    /**
     * @param bool $ssl
     *
     * @return string|null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getURL($ssl = false)
    {
        if (!$this->id) {
            return null;
        }

		if (defined('_EPH_ROOT_DIR_')) {
    		$url = ($ssl) ? 'https://'.$this->admin_ssl : 'http://'.$this->admin_ssl;
		} else {
			$url = ($ssl) ? 'https://'.$this->domain_ssl : 'http://'.$this->domain;
		}
        

        return $url.$this->getBaseUri();
    }

    /**
     * Get list of shop urls
     *
     * @param bool $idShop
     *
     * @return PhenyxShopCollection Collection of ShopUrl
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public static function getShopUrls($idShop = false)
    {
        $urls = new PhenyxShopCollection('ShopUrl');
        if ($idShop) {
            $urls->where('id_shop', '=', $idShop);
        }

        return $urls;
    }

    /**
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setMain()
    {
        $res = Db::getInstance()->update('shop_url', ['main' => 0], 'id_shop = '.(int) $this->id_shop);
        $res &= Db::getInstance()->update('shop_url', ['main' => 1], 'id_shop_url = '.(int) $this->id);
        $this->main = true;

        // Reset main URL for all shops to prevent problems
        $sql = 'SELECT s1.id_shop_url FROM '._DB_PREFIX_.'shop_url s1
				WHERE (
					SELECT COUNT(*) FROM '._DB_PREFIX_.'shop_url s2
					WHERE s2.main = 1
					AND s2.id_shop = s1.id_shop
				) = 0
				GROUP BY s1.id_shop';
        foreach (Db::getInstance()->executeS($sql) as $row) {
            Db::getInstance()->update('shop_url', ['main' => 1], 'id_shop_url = '.$row['id_shop_url']);
        }

        return $res;
    }

    /**
     * @param string $domain
     * @param string $domainSsl
     * @param string $physicalUri
     * @param string $virtualUri
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function canAddThisUrl($domain, $domainSsl, $physicalUri, $virtualUri)
    {
        $physicalUri = trim($physicalUri, '/');

        if ($physicalUri) {
            $physicalUri = preg_replace('#/+#', '/', '/'.$physicalUri.'/');
        } else {
            $physicalUri = '/';
        }

        $virtualUri = trim($virtualUri, '/');
        if ($virtualUri) {
            $virtualUri = preg_replace('#/+#', '/', trim($virtualUri, '/')).'/';
        }
		if (defined('_EPH_ROOT_DIR_')) {
			return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            	(new DbQuery())
                	->select('`id_shop_url`')
					->from('shop_url')
                	->where('`physical_uri` = \''.pSQL($physicalUri).'\'')
                	->where('`virtual_uri` = \''.pSQL($virtualUri).'\'')
                	->where('`admin_ssl` = \''.pSQL($domain).'\'')
                	->where($this->id ? '`id_shop_url` != '.(int) $this->id : '')
        	);
		} else {
			return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            	(new DbQuery())
					->select('`id_shop_url`')
                	->from('shop_url')
                	->where('`physical_uri` = \''.pSQL($physicalUri).'\'')
                	->where('`virtual_uri` = \''.pSQL($virtualUri).'\'')
					->where('`domain` = \''.pSQL($domain).'\''.(($domainSsl) ? ' OR domain_ssl = \''.pSQL($domainSsl).'\'' : ''))
                	->where($this->id ? '`id_shop_url` != '.(int) $this->id : '')
				);
		}
        
    }

    /**
     * @param int $idShop
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function cacheMainDomainForShop($idShop)
    {
        // @codingStandardsIgnoreStart
        if (!isset(static::$main_domain_ssl[(int) $idShop]) || !isset(static::$main_domain[(int) $idShop])) {
            $row = Db::getInstance()->getRow(
                (new DbQuery())
                    ->select('`domain`, `domain_ssl`, `admin_ssl`')
                    ->from('shop_url')
                    ->where('`main` = 1')
                    ->where('`id_shop` = '.($idShop !== null ? (int) $idShop : (int) Context::getContext()->shop->id))
            );
            static::$main_domain[(int) $idShop] = $row['domain'];
            static::$main_domain_ssl[(int) $idShop] = $row['domain_ssl'];
			static::$main_admin_ssl[(int) $idShop] = $row['admin_ssl'];
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function resetMainDomainCache()
    {
        // @codingStandardsIgnoreStart
        static::$main_domain = [];
        static::$main_domain_ssl = [];
		static::$main_admin_ssl = [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param null $idShop
     *
     * @return mixed
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMainShopDomain($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);
		if (defined('_EPH_ROOT_DIR_')) {
			return static::$main_admin_ssl[(int) $idShop];
		} else {
			return static::$main_domain[(int) $idShop];
		}
    }

    /**
     * @param int|null $idShop
     *
     * @return mixed
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getMainShopDomainSSL($idShop = null)
    {
        static::cacheMainDomainForShop($idShop);

		if (defined('_EPH_ROOT_DIR_')) {
			return static::$main_admin_ssl[(int) $idShop];
		} else {
			return static::$main_domain_ssl[(int) $idShop];
		}
    }
}
