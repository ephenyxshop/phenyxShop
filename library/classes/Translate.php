<?php

/**
 * Class TranslateCore
 *
 * @since 1.8.1.0
 */
class TranslateCore {

   
    public static function getAdminTranslation($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true, $sprintf = null) {

        static $modulesTabs = null;

        global $_LANGADM;

        if ($modulesTabs === null) {
            try {
                $modulesTabs = EmployeeMenu::getModuleTabList();
            } catch (PhenyxShopException $e) {
                $modulesTabs = [];
            }

        }

        // if ($_LANGADM == null) {
        $iso = Context::getContext()->language->iso_code;

        if (empty($iso)) {
            try {
                $iso = Language::getIsoById((int) Configuration::get('EPH_LANG_DEFAULT'));
            } catch (PhenyxShopException $e) {
                $iso = 'en';
            }

        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
            include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';
        }

        //    }

        if (isset($modulesTabs[strtolower($class)])) {
            $classNameController = $class . 'controller';
            // if the class is extended by a module, use plugins/[module_name]/xx.php lang file

            if (class_exists($classNameController) && Module::getModuleNameFromClass($classNameController)) {
                return Translate::getModuleTranslation(Module::$classInModule[$classNameController], $string, $classNameController, $sprintf, $addslashes);
            }

        }

        $string = preg_replace("/\\\*'/", "\'", $string);

        $key = md5($string);

        if (isset($_LANGADM[$class . $key])) {
            $str = $_LANGADM[$class . $key];
        } else {
            $str = Translate::getGenericAdminTranslation($string, $_LANGADM, $key);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    public static function getFrontTranslation($string, $class, $addslashes = false, $htmlentities = true, $sprintf = null) {

        global $_LANGFRONT;

        if ($_LANGFRONT == null) {
            $iso = Context::getContext()->language->iso_code;

            if (empty($iso)) {
                try {
                    $iso = Language::getIsoById((int) Configuration::get('EPH_LANG_DEFAULT'));
                } catch (PhenyxShopException $e) {
                    $iso = 'en';
                }

            }

            if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
                include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
            }

        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        if (isset($_LANGFRONT[$class . $key])) {
            $str = $_LANGFRONT[$class . $key];
        } else {
            $str = Translate::getGenericFrontTranslation($string, $_LANGFRONT, $key);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    public static function getClassTranslation($string, $class, $addslashes = false, $htmlentities = true, $sprintf = null) {

        global $_LANGCLASS;

        if ($_LANGCLASS == null) {
            $iso = Context::getContext()->language->iso_code;

            if (empty($iso)) {
                try {
                    $iso = Language::getIsoById((int) Configuration::get('EPH_LANG_DEFAULT'));
                } catch (PhenyxShopException $e) {
                    $iso = 'en';
                }

            }

            if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
                include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';
            }

        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        if (isset($_LANGCLASS[$class . $key])) {
            $str = $_LANGCLASS[$class . $key];
        } else {
            $str = Translate::getGenericFrontTranslation($string, $_LANGCLASS, $key);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    
    public static function getModuleTranslation($module, $string, $source, $sprintf = null, $js = false) {

       
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translationsMerged = [];

        $name = $module instanceof Module ? $module->name : $module;

        $language = Context::getContext()->language;

        if (!isset($translationsMerged[$name]) && isset(Context::getContext()->language)) {
            $filesByPriority = [
                // Translations in theme
                _EPH_THEME_DIR_ . 'plugins/' . $name . '/translations/' . $language->iso_code . '.php',
                _EPH_THEME_DIR_ . 'plugins/' . $name . '/' . $language->iso_code . '.php',
                // PhenyxShop 1.5 translations
                _EPH_MODULE_DIR_ . $name . '/translations/' . $language->iso_code . '.php',
                // PhenyxShop 1.4 translations
                _EPH_MODULE_DIR_ . $name . '/' . $language->iso_code . '.php',
            ];
            
            foreach ($filesByPriority as $file) {

                if (file_exists($file)) {
                    include_once $file;
                  
                    $_MODULES = !empty($_MODULES) ? $_MODULES + $_MODULE : $_MODULE; //we use "+" instead of array_merge() because array merge erase existing values.
                    $translationsMerged[$name] = true;
                }

            }

        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js;

        if (!isset($langCache[$cacheKey])) {
            
            if ($_MODULES == null) {

                if ($sprintf !== null) {
                    $string = Translate::checkAndReplaceArgs($string, $sprintf);
                }

                return str_replace('"', '&quot;', $string);
            }

            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}ephenyx>' . $source) . '_' . $key;
            $PhenyxShopKey = strtolower('<{' . $name . '}phenyxshop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}ephenyx>' . $file) . '_' . $key;
                $PhenyxShopKeyFile = strtolower('<{' . $name . '}phenyxshop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } else if (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } else if (isset($PhenyxShopKeyFile) && !empty($_MODULES[$PhenyxShopKeyFile])) {
                $ret = stripslashes($_MODULES[$PhenyxShopKeyFile]);
            } else if (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } else if (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } else if (!empty($_MODULES[$PhenyxShopKey])) {
                $ret = stripslashes($_MODULES[$PhenyxShopKey]);
            } else if (!empty($_LANGADM)) {
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $_LANGADM, $key));
            } else {
                $ret = stripslashes($string);
            }

            if ($sprintf !== null) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } else {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            } else {
                return $ret;
            }

        }

        return $langCache[$cacheKey];
    }

   
    public static function checkAndReplaceArgs($string, $args) {

        if (preg_match_all('#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#', $string, $matches) && !is_null($args)) {

            if (!is_array($args)) {
                $args = [$args];
            }

            return vsprintf($string, $args);
        }

        return $string;
    }

    
    public static function getGenericAdminTranslation($string, &$langArray, $key = null) {

        $string = preg_replace("/\\\*'/", "\'", $string);

        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['AdminController' . $key])) {
            $str = $langArray['AdminController' . $key];
        } else if (isset($langArray['Helper' . $key])) {
            $str = $langArray['Helper' . $key];
        } else if (isset($langArray['AdminTab' . $key])) {
            $str = $langArray['AdminTab' . $key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }

        return $str;
    }

    public static function getGenericFrontTranslation($string, &$langArray, $key = null) {

        $string = preg_replace("/\\\*'/", "\'", $string);

        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['FrontController' . $key])) {
            $str = $langArray['FrontController' . $key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }

        return $str;
    }

    public static function getPdfTranslation($string, $sprintf = null) {

        global $_LANGPDF;

        $iso = Context::getContext()->language->iso_code;

        if (!Validate::isLangIsoCode($iso)) {
            Tools::displayError(sprintf('Invalid iso lang (%s)', Tools::safeOutput($iso)));
        }

        $overrideI18NFile = _EPH_THEME_DIR_ . 'pdf/lang/' . $iso . '.php';
        $i18NFile = _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';

        if (file_exists($overrideI18NFile)) {
            $i18NFile = $overrideI18NFile;
        }

        if (!include ($i18NFile)) {
            Tools::displayError(sprintf('Cannot include PDF translation language file : %s', $i18NFile));
        }

        if (!isset($_LANGPDF) || !is_array($_LANGPDF)) {
            return str_replace('"', '&quot;', $string);
        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        $str = (array_key_exists('PDF' . $key, $_LANGPDF) ? $_LANGPDF['PDF' . $key] : $string);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
    }
	
	public static function getMailsTranslation($string, $file, $sprintf = null) {

        global $_LANGMAIL;

        $iso = Context::getContext()->language->iso_code;

        if (!Validate::isLangIsoCode($iso)) {
            Tools::displayError(sprintf('Invalid iso lang (%s)', Tools::safeOutput($iso)));
        }
        
        $i18NFile = _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
        

        if (!include ($i18NFile)) {
            Tools::displayError(sprintf('Cannot include PDF translation language file : %s', $i18NFile));
        }

        if (!isset($_LANGMAIL) || !is_array($_LANGMAIL)) {
            return str_replace('"', '&quot;', $string);
        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);
		

        $str = (array_key_exists($file . $key, $_LANGMAIL) ? $_LANGMAIL[$file . $key] : $string);

        if ($sprintf !== null) {
            $str = Translate::checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
    }

    /**
     * Compatibility method that just calls postProcessTranslation.
     *
     * @deprecated 1.0.0 renamed this to postProcessTranslation, since it is not only used in relation to smarty.
     */
    public static function smartyPostProcessTranslation($string, $params) {

        return Translate::postProcessTranslation($string, $params);
    }

    /**
     * Perform operations on translations after everything is escaped and before displaying it
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     *
     * @param string $string
     * @param array  $params
     *
     * @return mixed
     */
    public static function postProcessTranslation($string, $params) {

        // If tags were explicitely provided, we want to use them *after* the translation string is escaped.

        if (!empty($params['tags'])) {

            foreach ($params['tags'] as $index => $tag) {
                // Make positions start at 1 so that it behaves similar to the %1$d etc. sprintf positional params
                $position = $index + 1;
                // extract tag name
                $match = [];

                if (preg_match('/^\s*<\s*(\w+)/', $tag, $match)) {
                    $opener = $tag;
                    $closer = '</' . $match[1] . '>';

                    $string = str_replace('[' . $position . ']', $opener, $string);
                    $string = str_replace('[/' . $position . ']', $closer, $string);
                    $string = str_replace('[' . $position . '/]', $opener . $closer, $string);
                }

            }

        }

        return $string;
    }

    /**
     * Helper function to make calls to postProcessTranslation more readable.
     *
     * @param string $string
     * @param array  $tags
     *
     * @return mixed
     *
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public static function ppTags($string, $tags) {

        return Translate::postProcessTranslation($string, ['tags' => $tags]);
    }

}
