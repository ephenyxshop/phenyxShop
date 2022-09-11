<?php

/**
 * Class ShopMaintenance
 *
 * This class implements tasks for maintaining hte shop installation, to be
 * run on a regular schedule. It gets called by an asynchronous Ajax request
 * in DashboardController.
 *
 * @since 1.0.8
 */
class ShopMaintenanceCore {

    /**
     * Run tasks as needed. Should take care of running tasks not more often
     * than needed and that one run takes not longer than a few seconds.
     *
     * This method gets triggered by the 'getNotifications' Ajax request, so
     * every two minutes while somebody has back office open.
     *
     * @since 1.0.8
     */
    public static function run() {

        $now = time();
        $lastRun = Configuration::get('SHOP_MAINTENANCE_LAST_RUN');

        if ($now - $lastRun > 86400) {
            // Run daily tasks.
            static::adjustThemeHeaders();
            static::optinShop();
            static::cleanAdminControllerMessages();

            Configuration::updateGlobalValue('SHOP_MAINTENANCE_LAST_RUN', $now);
        }

    }

    /**
     * Correct the "generator" meta tag in templates. Technology detection
     * sites like builtwith.com don't recognize ephenyx technology if the
     * theme template inserts a meta tag "generator" for PhenyxShop.
     *
     * @since 1.0.8
     */
    public static function adjustThemeHeaders() {

        foreach (scandir(_EPH_ALL_THEMES_DIR_) as $themeDir) {

            if (!is_dir(_EPH_ALL_THEMES_DIR_ . $themeDir)
                || in_array($themeDir, ['.', '..'])) {
                continue;
            }

            $headerPath = _EPH_ALL_THEMES_DIR_ . $themeDir . '/header.tpl';

            if (is_writable($headerPath)) {
                $header = file_get_contents($headerPath);
                $newHeader = preg_replace('/<\s*meta\s*name\s*=\s*["\']generator["\']\s*content\s*=\s*["\'].*["\']\s*>/i',
                    '<meta name="generator" content="ephenyx">', $header);

                if ($newHeader !== $header) {
                    file_put_contents($headerPath, $newHeader);
                    Tools::clearSmartyCache();
                }

            }

        }

    }

    /**
     * Handle shop optin.
     *
     * @since 1.0.8
     */
    public static function optinShop() {

        $name = Configuration::STORE_REGISTERED;

        if (!Configuration::get($name)) {
            $employees = Employee::getEmployeesByProfile(_EPH_ADMIN_PROFILE_);
            // Usually there's only one employee when we run this code.

            foreach ($employees as $employee) {
                $employee = new Employee($employee);
                $employee->optin = true;

                if ($employee->update()) {
                    Configuration::updateValue($name, 1);
                }

            }

        }

    }

    /**
     * Delete lost AdminController messages.
     *
     * @since 1.0.8
     */
    public static function cleanAdminControllerMessages() {

        $name = AdminController::MESSAGE_CACHE_PATH;
        $nameLength = strlen($name);

        foreach (scandir(_EPH_CACHE_DIR_) as $candidate) {

            if (substr($candidate, 0, $nameLength) === $name) {
                $path = _EPH_CACHE_DIR_ . '/' . $candidate;

                if (time() - filemtime($path) > 3600) {
                    unlink($path);
                }

            }

        }

    }

}
