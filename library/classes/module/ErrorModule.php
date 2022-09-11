<?php

/**
 * Class ErrorModuleCore
 *
 * @since 1.9.1.0
 */
abstract class ErrorModuleCore extends Module
{
    /**
     * Register the error handlers
     *
     * This function lets the `ErrorModule` register the necessary error handlers and shutdown functions.
     * This might override the default uncaught exception handler in ephenyx
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    abstract public function hookActionRegisterErrorHandlers();
}
