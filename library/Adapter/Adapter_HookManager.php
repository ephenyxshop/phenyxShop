<?php

/**
 * Class Adapter_HookManager
 *
 * @since 1.9.1.0
 */
// @codingStandardsIgnoreStart
class Adapter_HookManager {

    // @codingStandardsIgnoreEnd

    public function exec($hookName, $hookArgs = [], $idPlugin = null, $arrayReturn = false, $checkExceptions = true, $usePush = false, $idCompany = null) {

        return Hook::exec($hookName, $hookArgs, $idPlugin, $arrayReturn, $checkExceptions, $usePush, $idCompany);
    }
}
