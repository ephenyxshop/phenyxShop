<?php

/**
 * Override module templates easily.
 *
 * @since 1.7.0.0
 */
class SmartyResourceModuleCore extends Smarty_Resource_Custom {

    public function __construct(array $paths, $isAdmin = false) {

        $this->paths = $paths;
        $this->isAdmin = $isAdmin;
    }

    /**
     * Fetch a template.
     *
     * @param string $name template name
     * @param string $source template source
     * @param int $mtime template modification timestamp (epoch)
     */
    protected function fetch($name, &$source, &$mtime) {

        foreach ($this->paths as $path) {

            if (Tools::file_exists_cache($file = $path . $name)) {

                if (_PS_MODE_DEV_) {
                    $source = implode('', [
                        '<!-- begin ' . $file . ' -->',
                        file_get_contents($file),
                        '<!-- end ' . $file . ' -->',
                    ]);
                } else {
                    $source = file_get_contents($file);
                }

                $mtime = filemtime($file);

                return;
            }

        }

    }

}
