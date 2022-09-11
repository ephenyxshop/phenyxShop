<?php

/**
 * Class SmartyCustomCore
 *
 * @since 1.9.1.0
 */
class SmartyCustomCore extends Smarty {

    /**
     * SmartyCustomCore constructor.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct() {

        parent::__construct();
        $this->template_class = 'Smarty_Custom_Template';
    }

    /**
     * Delete compiled template file (lazy delete if resource_name is not specified)
     *
     * @param  string $resourceName template name
     * @param  string $compileId    compile id
     * @param  int    $expTime      expiration time
     *
     * @return int number of template files deleted
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function clearCompiledTemplate($resourceName = null, $compileId = null, $expTime = null) {

        if ($resourceName == null) {
            Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'smarty_last_flush` (`type`, `last_flush`) VALUES (\'compile\', FROM_UNIXTIME(' . time() . '))');

            return 0;
        } else {
            return parent::clearCompiledTemplate($resourceName, $compileId, $expTime);
        }

    }

    /**
     * Mark all template files to be regenerated
     *
     * @param  int    $expTime expiration time
     * @param  string $type    resource type
     *
     * @return int number of cache files which needs to be updated
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function clearAllCache($expTime = null, $type = null) {

        Db::getInstance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'smarty_last_flush` (`type`, `last_flush`) VALUES (\'template\', FROM_UNIXTIME(' . time() . '))');

        return $this->delete_from_lazy_cache(null, null, null);
    }

    /**
     * Delete the current template from the lazy cache or the whole cache if no template name is given
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws PhenyxShopException
     */
    public function delete_from_lazy_cache($template, $cacheId, $compileId) {

        if (!$template) {
            return Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'smarty_lazy_cache`', false);
        }

        $templateMd5 = md5($template);
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'smarty_lazy_cache`
                            WHERE template_hash=\'' . pSQL($templateMd5) . '\'';

        if ($cacheId != null) {
            $sql .= ' AND cache_id LIKE "' . pSQL((string) $cacheId) . '%"';
        }

        if ($compileId != null) {

            if (strlen($compileId) > 32) {
                $compileId = md5($compileId);
            }

            $sql .= ' AND compile_id="' . pSQL((string) $compileId) . '"';
        }

        Db::getInstance()->execute($sql, false);

        return Db::getInstance()->Affected_Rows();
    }

    /**
     * Mark file to be regenerated for a specific template
     *
     * @param  string $templateName template name
     * @param  string $cacheId      cache id
     * @param  string $compileId    compile id
     * @param  int    $expTime      expiration time
     * @param  string $type         resource type
     *
     * @return int number of cache files which needs to be updated
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function clearCache($templateName, $cacheId = null, $compileId = null, $expTime = null, $type = null) {

        return $this->delete_from_lazy_cache($templateName, $cacheId, $compileId);
    }

    public function assign($tpl_var, $value = null, $nocache = false) {

        if (is_array($tpl_var)) {
			$context = Context::getContext();
            $this->assign('shopName', Configuration::get('PS_SHOP_NAME'));
			$this->assign('shop_url', 'https://'.Configuration::get('PS_SHOP_URL'));
			$this->assign('shop_mail', Configuration::get('PS_SHOP_EMAIL'));
			$this->assign('company', new Company(Configuration::get('EPH_COMPANY_ID')));
			$this->assign('today', date("Y-m-d"));
			$this->assign('my_account_url', $context->link->getFrontPageLink('my-account', true, Context::getContext()->language->id, null, false));
			$this->assign('guest_tracking_url', $context->link->getFrontPageLink('guest-tracking', true, Context::getContext()->language->id, null, false));
			$this->assign('history_url', $context->link->getFrontPageLink('history', true, Context::getContext()->language->id, null, false));

            foreach ($tpl_var as $_key => $_val) {
                $this->assign($_key, $_val, $nocache);
            }

        } else {

            if ($tpl_var !== '') {

                if ($this->_objType === 2) {
                    /**
                     *
                     *
                     * @var Smarty_Internal_Template $this
                     */
                    $this->_assignInScope($tpl_var, $value, $nocache);
                } else {
                    $this->tpl_vars[$tpl_var] = new Smarty_Variable($value, $nocache);
                }

            }

        }

        return $this;
    }

    /**
     * @param null $template
     * @param null $cacheId
     * @param null $compileId
     * @param null $parent
     * @param bool $display
     * @param bool $mergeTplVars
     * @param bool $noOutputFilter
     *
     * @return string
     *
     * @throws Exception
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null, $display = false, $mergeTplVars = true, $noOutputFilter = false) {

        $this->check_compile_cache_invalidation();

        return parent::fetch($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
    }

    /**
     * Check the compile cache needs to be invalidated (multi front + local cache compatible)
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function check_compile_cache_invalidation() {

        static $lastFlush = null;

        if (!file_exists($this->getCompileDir() . 'last_flush')) {
            @touch($this->getCompileDir() . 'last_flush', time());
        } else
        if (defined('_DB_PREFIX_')) {

            if ($lastFlush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `' . _DB_PREFIX_ . 'smarty_last_flush` WHERE type=\'compile\'';
                $lastFlush = Db::getInstance()->getValue($sql, false);
            }

            if ((int) $lastFlush && @filemtime($this->getCompileDir() . 'last_flush') < $lastFlush) {
                @touch($this->getCompileDir() . 'last_flush', time());
                parent::clearCompiledTemplate();
            }

        }

    }

    /**
     * @param string $template
     * @param null   $cacheId
     * @param null   $compileId
     * @param null   $parent
     * @param bool   $doClone
     *
     * @return object
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function createTemplate($template, $cacheId = null, $compileId = null, $parent = null, $doClone = true) {

        $this->check_compile_cache_invalidation();

        if ($this->caching) {
            $this->check_template_invalidation($template, $cacheId, $compileId);

            return parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        } else {
            return parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        }

    }

    /**
     * Handle the lazy template cache invalidation
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     * @throws SmartyException
     */
    public function check_template_invalidation($template, $cacheId, $compileId) {

        static $lastFlush = null;

        if (!file_exists($this->getCacheDir() . 'last_template_flush')) {
            @touch($this->getCacheDir() . 'last_template_flush', time());
        } else
        if (defined('_DB_PREFIX_')) {

            if ($lastFlush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `' . _DB_PREFIX_ . 'smarty_last_flush` WHERE type=\'template\'';
                $lastFlush = Db::getInstance()->getValue($sql, false);
            }

            if ((int) $lastFlush && @filemtime($this->getCacheDir() . 'last_template_flush') < $lastFlush) {
                @touch($this->getCacheDir() . 'last_template_flush', time());
                parent::clearAllCache();
            } else {

                if ($cacheId !== null && (is_object($cacheId) || is_array($cacheId))) {
                    $cacheId = null;
                }

                if ($this->is_in_lazy_cache($template, $cacheId, $compileId) === false) {
                    // insert in cache before the effective cache creation to avoid nasty race condition
                    $this->insert_in_lazy_cache($template, $cacheId, $compileId);
                    parent::clearCache($template, $cacheId, $compileId);
                }

            }

        }

    }

    /**
     * Check if the current template is stored in the lazy cache
     * Entry in the lazy cache = no need to regenerate the template
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function is_in_lazy_cache($template, $cacheId, $compileId) {

        static $isInLazyCache = [];
        $templateMd5 = md5($template);

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }

        $key = md5($templateMd5 . '-' . $cacheId . '-' . $compileId);

        if (isset($isInLazyCache[$key])) {
            return $isInLazyCache[$key];
        } else {
            $sql = 'SELECT UNIX_TIMESTAMP(last_update) AS last_update, filepath FROM `' . _DB_PREFIX_ . 'smarty_lazy_cache`
                            WHERE `template_hash`=\'' . pSQL($templateMd5) . '\'';
            $sql .= ' AND cache_id="' . pSQL((string) $cacheId) . '"';
            $sql .= ' AND compile_id="' . pSQL((string) $compileId) . '"';

            $result = Db::getInstance()->getRow($sql, false);
            // If the filepath is not yet set, it means the cache update is in progress in another process.
            // In this case do not try to clear the cache again and tell to use the existing cache, if any

            if ($result !== false && $result['filepath'] == '') {
                // If the cache update is stalled for more than 1min, something should be wrong,
                // remove the entry from the lazy cache

                if ($result['last_update'] < time() - 60) {
                    $this->delete_from_lazy_cache($template, $cacheId, $compileId);
                }

                $return = true;
            } else {

                if ($result === false
                    || @filemtime($this->getCacheDir() . $result['filepath']) < $result['last_update']
                ) {
                    $return = false;
                } else {
                    $return = $result['filepath'];
                }

            }

            $isInLazyCache[$key] = $return;
        }

        return $return;
    }

    /**
     * Insert the current template in the lazy cache
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function insert_in_lazy_cache($template, $cacheId, $compileId) {

        $template_md5 = md5($template);
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'smarty_lazy_cache`
                            (`template_hash`, `cache_id`, `compile_id`, `last_update`)
                            VALUES (\'' . pSQL($template_md5) . '\'';

        $sql .= ',"' . pSQL((string) $cacheId) . '"';

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }

        $sql .= ',"' . pSQL((string) $compileId) . '"';
        $sql .= ', FROM_UNIXTIME(' . time() . '))';

        return Db::getInstance()->execute($sql, false);
    }

    /**
     * Store the cache file path
     *
     * @param  string $filepath  cache file path
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function update_filepath($filepath, $template, $cacheId, $compileId) {

        $templateMd5 = md5($template);
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'smarty_lazy_cache`
                            SET filepath=\'' . pSQL($filepath) . '\'
                            WHERE `template_hash`=\'' . pSQL($templateMd5) . '\'';

        $sql .= ' AND cache_id="' . pSQL((string) $cacheId) . '"';

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }

        $sql .= ' AND compile_id="' . pSQL((string) $compileId) . '"';
        Db::getInstance()->execute($sql, false);
    }

}

/**
 * Class Smarty_Custom_Template
 *
 * @since 1.9.1.0
 */
class Smarty_Custom_Template extends Smarty_Internal_Template {

    /** @var SmartyCustom|null */
    public $smarty = null;

    /**
     * @param null $template
     * @param null $cacheId
     * @param null $compileId
     * @param null $parent
     * @param bool $display
     * @param bool $mergeTplVars
     * @param bool $noOutputFilter
     *
     * @return string
     * @throws SmartyException
     *
     * @since 1.9.1.0
     * @throws Exception
     * @throws Exception
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null, $display = false, $mergeTplVars = true, $noOutputFilter = false) {

        if ($this->smarty->caching) {
            $count = 0;
            $maxTries = 3;

            while (true) {
                try {
                    $tpl = parent::fetch($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
                    break;
                } catch (SmartyException $e) {
                    // handle exception

                    if (++$count === $maxTries) {
                        throw $e;
                    }

                    usleep(1);
                }

            }

            if (property_exists($this, 'cached')) {
                $filepath = str_replace($this->smarty->getCacheDir(), '', $this->cached->filepath);

                if ($this->smarty->is_in_lazy_cache($this->template_resource, $this->cache_id, $this->compile_id) != $filepath) {
                    $this->smarty->update_filepath($filepath, $this->template_resource, $this->cache_id, $this->compile_id);
                }

            }

            return isset($tpl) ? $tpl : '';
        } else {
            $count = 0;
            $maxTries = 3;

            while (true) {
                try {
                    $tpl = parent::fetch($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
                    break;
                } catch (SmartyException $e) {
                    // handle exception

                    if (++$count === $maxTries) {
                        throw $e;
                    }

                    usleep(1);
                }

            }

            return isset($tpl) ? $tpl : '';
        }

    }

}
