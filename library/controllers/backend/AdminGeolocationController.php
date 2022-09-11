<?php

/**
 * Class AdminGeolocationControllerCore
 *
 * @since 1.9.1.0
 */
class AdminGeolocationControllerCore extends AdminController {

    /**
     * AdminGeolocationControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        parent::__construct();

        $this->bootstrap = true;
        $this->fields_options = [
            'geolocationConfiguration' => [
                'title'  => $this->l('Geolocation by IP address'),
                'icon'   => 'icon-map-marker',
                'fields' => [
                    'EPH_GEOLOCATION_ENABLED' => [
                        'title'      => $this->l('Geolocation by IP address'),
                        'hint'       => $this->l('This option allows you, among other things, to restrict access to your shop for certain countries. See below.'),
                        'validation' => 'isUnsignedId',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'geolocationCountries'     => [
                'title'       => $this->l('Options'),
                'icon'        => 'icon-map-marker',
                'description' => $this->l('The following features are only available if you enable the Geolocation by IP address feature.'),
                'fields'      => [
                    'EPH_GEOLOCATION_BEHAVIOR'    => [
                        'title'      => $this->l('Geolocation behavior for restricted countries'),
                        'type'       => 'select',
                        'identifier' => 'key',
                        'list'       => [
                            ['key' => _EPH_GEOLOCATION_NO_CATALOG_, 'name' => $this->l('Visitors cannot see your catalog.')],
                            ['key' => _EPH_GEOLOCATION_NO_ORDER_, 'name' => $this->l('Visitors can see your catalog but cannot place an order.')],
                        ],
                    ],
                    'EPH_GEOLOCATION_NA_BEHAVIOR' => [
                        'title'      => $this->l('Geolocation behavior for other countries'),
                        'type'       => 'select',
                        'identifier' => 'key',
                        'list'       => [
                            ['key' => '-1', 'name' => $this->l('All features are available')],
                            ['key' => _EPH_GEOLOCATION_NO_CATALOG_, 'name' => $this->l('Visitors cannot see your catalog.')],
                            ['key' => _EPH_GEOLOCATION_NO_ORDER_, 'name' => $this->l('Visitors can see your catalog but cannot place an order.')],
                        ],
                    ],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
            'geolocationWhitelist'     => [
                'title'       => $this->l('IP address whitelist'),
                'icon'        => 'icon-sitemap',
                'description' => $this->l('You can add IP addresses that will always be allowed to access your shop (e.g. Google bots\' IP).'),
                'fields'      => [
                    'EPH_GEOLOCATION_WHITELIST' => ['title' => $this->l('Whitelisted IP addresses'), 'type' => 'textarea_newlines', 'cols' => 15, 'rows' => 30],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];
    }

    public function setMedia($isNewTheme = false) {

        parent::setMedia($isNewTheme);
        $this->addCSS(__EPH_BASE_URI__ . _EPH_JS_DIR_ . '' . $this->bo_theme . '/css/jquery-ui.css');
        $this->addJquery('3.4.1');
        $this->addJS(__EPH_BASE_URI__ . _EPH_JS_DIR_ . 'jquery-ui/jquery-ui.js');

    }

    /**
     * Process update options
     *
     * @since 1.9.1.0
     */
    public function processUpdateOptions() {

        if ($this->isGeoLiteCityAvailable()) {
            Configuration::updateValue('EPH_GEOLOCATION_ENABLED', (int) Tools::getValue('EPH_GEOLOCATION_ENABLED'));
        }

        // stop processing if geolocation is set to yes but geolite pack is not available
        else
        if (Tools::getValue('EPH_GEOLOCATION_ENABLED')) {
            $this->errors[] = Tools::displayError('The geolocation database is unavailable.');
        }

        if (empty($this->errors)) {

            if (!is_array(Tools::getValue('countries')) || !count(Tools::getValue('countries'))) {
                $this->errors[] = Tools::displayError('Country selection is invalid.');
            } else {
                Configuration::updateValue(
                    'EPH_GEOLOCATION_BEHAVIOR',
                    (!(int) Tools::getValue('EPH_GEOLOCATION_BEHAVIOR') ? _EPH_GEOLOCATION_NO_CATALOG_ : _EPH_GEOLOCATION_NO_ORDER_)
                );
                Configuration::updateValue('EPH_GEOLOCATION_NA_BEHAVIOR', (int) Tools::getValue('EPH_GEOLOCATION_NA_BEHAVIOR'));
                Configuration::updateValue('EPH_ALLOWED_COUNTRIES', implode(';', Tools::getValue('countries')));
            }

            if (!Validate::isCleanHtml(Tools::getValue('EPH_GEOLOCATION_WHITELIST'))) {
                $this->errors[] = Tools::displayError('Invalid whitelist');
            } else {
                Configuration::updateValue(
                    'EPH_GEOLOCATION_WHITELIST',
                    str_replace("\n", ';', str_replace("\r", '', Tools::getValue('EPH_GEOLOCATION_WHITELIST')))
                );
            }

        }

        return parent::processUpdateOptions();
    }

    /**
     * Check if geolite city file is available
     *
     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function isGeoLiteCityAvailable() {

        if (@filemtime(_EPH_GEOIP_DIR_ . _EPH_GEOIP_CITY_FILE_)) {
            return true;
        }

        return false;
    }

    /**
     * Render options
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function renderOptions() {

        // This field is not declared in class constructor because we want it to be manually post processed
        $this->fields_options['geolocationCountries']['fields']['countries'] = [
            'title'      => $this->l('Select the countries from which your store is accessible'),
            'type'       => 'checkbox_table',
            'identifier' => 'iso_code',
            'list'       => Country::getCountries($this->context->language->id),
            'auto_value' => false,
            'tabScript'  => $this->generateTabScript(Tools::getValue('controller')),
            'controller' => Tools::getValue('controller'),
        ];

        $this->tpl_option_vars = ['allowed_countries' => explode(';', Configuration::get('EPH_ALLOWED_COUNTRIES'))];

        return parent::renderOptions();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initContent() {

        $this->display = 'options';

        if (!$this->isGeoLiteCityAvailable()) {
            $this->displayWarning(
                $this->l('In order to use Geolocation, please download') . ' <a href="http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz">' . $this->l('this file') . '</a> ' . $this->l('and extract it (using Winrar or Gzip) into the /tools/geoip/ directory.')
            );
            Configuration::updateValue('EPH_GEOLOCATION_ENABLED', 0);
        }

        parent::initContent();
    }

}
