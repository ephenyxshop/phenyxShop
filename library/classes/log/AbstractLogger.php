<?php
/**
 * 2007-2016 PhenyxShop
 *
 * ephenyx is an extension to the PhenyxShop e-commerce software developed by PhenyxShop SA
 * Copyright (C) 2017-2018 ephenyx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@ephenyx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PhenyxShop to newer
 * versions in the future. If you wish to customize PhenyxShop for your
 * needs please refer to https://www.ephenyx.com for more information.
 *
 *  @author    ephenyx <contact@ephenyx.com>
 *  @author    PhenyxShop SA <contact@PhenyxShop.com>
 *  @copyright 2017-2020 ephenyx
 *  @copyright 2007-2016 PhenyxShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PhenyxShop is an internationally registered trademark & property of PhenyxShop SA
 */

/**
 * Class AbstractLoggerCore
 */
abstract class AbstractLoggerCore
{
    // @codingStandardsIgnoreStart
    public $level;
    protected $level_value = [
        0 => 'DEBUG',
        1 => 'INFO',
        2 => 'WARNING',
        3 => 'ERROR',
    ];
    // @codingStandardsIgnoreEnd

    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;

    /**
     * AbstractLoggerCore constructor.
     *
     * @param int $level
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($level = self::INFO)
    {
        if (array_key_exists((int) $level, $this->level_value)) {
            $this->level = $level;
        } else {
            $this->level = static::INFO;
        }
    }

    /**
     * Check the level and log the message if needed
     *
     * @param string $message
     * @param int    $level
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function log($message, $level = self::DEBUG)
    {
        if ($level >= $this->level) {
            $this->logMessage($message, $level);
        }
    }

    /**
    * Log a debug message
    *
    * @param string $message
    *
    * @since 1.9.1.0
    * @version 1.8.1.0 Initial version
    */
    public function logDebug($message)
    {
        $this->log($message, static::DEBUG);
    }

    /**
    * Log an info message
    *
    * @param string $message
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
    */
    public function logInfo($message)
    {
        $this->log($message, static::INFO);
    }

    /**
    * Log a warning message
    *
    * @param string $message
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
    */
    public function logWarning($message)
    {
        $this->log($message, static::WARNING);
    }

    /**
    * Log an error message
    *
    * @param string $message
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
    */
    public function logError($message)
    {
        $this->log($message, static::ERROR);
    }

    /**
     * Log the message
     *
     * @param string message
     * @param level
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract protected function logMessage($message, $level);
}
