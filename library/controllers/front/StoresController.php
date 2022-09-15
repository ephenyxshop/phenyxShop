<?php

/**
 * Class StoresControllerCore
 *
 * @since 1.8.1.0
 */
class StoresControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'stores';
    // @codingStandardsIgnoreEnd

    /**
     * Initialize stores controller
     *
     * @see   FrontController::init()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();

        if (!extension_loaded('Dom')) {
            $this->errors[] = Tools::displayError('PHP "Dom" extension has not been loaded.');
            $this->context->smarty->assign('errors', $this->errors);
        }

    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        if (Configuration::get('EPH_STORES_SIMPLIFIED')) {
            $this->assignStoresSimplified();
        } else {
            $this->assignStores();
        }

        $this->context->smarty->assign(
            [
                'mediumSize'  => Image::getSize(ImageType::getFormatedName('medium')),
                'defaultLat'  => (float) Configuration::get('EPH_STORES_CENTER_LAT'),
                'defaultLong' => (float) Configuration::get('EPH_STORES_CENTER_LONG'),
                'searchUrl'   => $this->context->link->getPageLink('stores'),
                'logo_store'  => Configuration::get('EPH_STORES_ICON'),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'stores.tpl');
    }

    /**
     * Assign template vars for simplified stores
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignStoresSimplified() {

        $stores = Db::getInstance()->executeS(
            '
        SELECT s.*, cl.name country, st.iso_code state
        FROM ' . _DB_PREFIX_ . 'store s
        LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
        LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
        WHERE s.active = 1 AND cl.id_lang = ' . (int) $this->context->language->id
        );

        $addressesFormatted = [];

        foreach ($stores as &$store) {
            $address = new Address();
            $address->country = Country::getNameById($this->context->language->id, $store['id_country']);
            $address->address1 = $store['address1'];
            $address->address2 = $store['address2'];
            $address->postcode = $store['postcode'];
            $address->city = $store['city'];

            $addressesFormatted[$store['id_store']] = AddressFormat::getFormattedLayoutData($address);

            $store['has_picture'] = file_exists(_EPH_STORE_IMG_DIR_ . (int) $store['id_store'] . '.jpg');

            if ($workingHours = $this->renderStoreWorkingHours($store)) {
                $store['working_hours'] = $workingHours;
            }

        }

        $this->context->smarty->assign(
            [
                'simplifiedStoresDiplay' => true,
                'stores'                 => $stores,
                'addresses_formated'     => $addressesFormatted,
            ]
        );
    }

    /**
     * Assign template vars for classical stores
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignStores() {

        $this->context->smarty->assign('hasStoreIcon', file_exists(_EPH_IMG_DIR_ . Configuration::get('EPH_STORES_ICON')));

        $distanceUnit = Configuration::get('EPH_DISTANCE_UNIT');

        if (!in_array($distanceUnit, ['km', 'mi'])) {
            $distanceUnit = 'km';
        }

        $this->context->smarty->assign(
            [
                'distance_unit'          => $distanceUnit,
                'simplifiedStoresDiplay' => false,
                'stores'                 => $this->getStores(),
            ]
        );
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_ . 'stores.css');

        if (!Configuration::get('EPH_STORES_SIMPLIFIED')) {
            $apiKey = (Configuration::get('EPH_GOOGLE_MAEPH_API_KEY')) ? 'key=' . Configuration::get('EPH_GOOGLE_MAEPH_API_KEY') . '&' : '';
            $defaultCountry = new Country((int) Tools::getCountry());
            $this->addJS('http' . ((Configuration::get('EPH_SSL_ENABLED') && Configuration::get('EPH_SSL_ENABLED_EVERYWHERE')) ? 's' : '') . '://maps.google.com/maps/api/js?' . $apiKey . 'region=' . substr($defaultCountry->iso_code, 0, 2));
            $this->addJS(_THEME_JS_DIR_ . 'stores.js');
        }

    }

    /**
     * Display the Xml for showing the nodes in the google map
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function displayAjax() {

        $stores = $this->getStores();
        $parnode = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><markers></markers>');

        foreach ($stores as $store) {
            $other = '';
            $newnode = $parnode->addChild('marker');
            $newnode->addAttribute('name', $store['name']);
            $address = $this->processStoreAddress($store);

            $other .= $this->renderStoreWorkingHours($store);
            $newnode->addAttribute('addressNoHtml', strip_tags(str_replace('<br />', ' ', $address)));
            $newnode->addAttribute('address', $address);
            $newnode->addAttribute('other', $other);
            $newnode->addAttribute('phone', $store['phone']);
            $newnode->addAttribute('id_store', (int) $store['id_store']);
            $newnode->addAttribute('has_store_picture', file_exists(_EPH_STORE_IMG_DIR_ . (int) $store['id_store'] . '.jpg'));
            $newnode->addAttribute('lat', (float) $store['latitude']);
            $newnode->addAttribute('lng', (float) $store['longitude']);

            if (isset($store['distance'])) {
                $newnode->addAttribute('distance', (int) $store['distance']);
            }

        }

        header('Content-type: text/xml');
        die($parnode->asXML());
    }

    /**
     * Get Stores
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since 1.8.1.0
     */
    public function getStores() {

        $distanceUnit = Configuration::get('EPH_DISTANCE_UNIT');

        if (!in_array($distanceUnit, ['km', 'mi'])) {
            $distanceUnit = 'km';
        }

        if (Tools::getValue('all') == 1) {
            $stores = Db::getInstance()->executeS(
                '
            SELECT s.*, cl.name country, st.iso_code state
            FROM ' . _DB_PREFIX_ . 'store s
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
            LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
            WHERE s.active = 1 AND cl.id_lang = ' . (int) $this->context->language->id
            );
        } else {
            $distance = (int) Tools::getValue('radius', 100);
            $multiplicator = ($distanceUnit == 'km' ? 6371 : 3959);

            $stores = Db::getInstance()->executeS(
                '
            SELECT s.*, cl.name country, st.iso_code state,
            (' . (int) $multiplicator . '
                * acos(
                    cos(radians(' . (float) Tools::getValue('latitude') . '))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(' . (float) Tools::getValue('longitude') . '))
                    + sin(radians(' . (float) Tools::getValue('latitude') . '))
                    * sin(radians(latitude))
                )
            ) distance,
            cl.id_country id_country
            FROM ' . _DB_PREFIX_ . 'store s
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
            LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
            WHERE s.active = 1 AND cl.id_lang = ' . (int) $this->context->language->id . '
            HAVING distance < ' . (int) $distance . '
            ORDER BY distance ASC
            LIMIT 0,20'
            );
        }

        return $stores;
    }

    /**
     * Get formatted string address
     *
     * @param array $store
     *
     * @return string
     *
     * @since 1.8.1.0
     */
    protected function processStoreAddress($store) {

        $ignoreField = [
            'firstname',
            'lastname',
        ];

        $outDatas = [];

        $addressDatas = AddressFormat::getOrderedAddressFields($store['id_country'], false, true);
        $state = (isset($store['id_state'])) ? new State($store['id_state']) : null;

        foreach ($addressDatas as $dataLine) {
            $dataFields = explode(' ', $dataLine);
            $addrOut = [];

            $dataFieldsMod = false;

            foreach ($dataFields as $fieldItem) {
                $fieldItem = trim($fieldItem);

                if (!in_array($fieldItem, $ignoreField) && !empty($store[$fieldItem])) {
                    $addrOut[] = ($fieldItem == 'city' && $state && isset($state->iso_code) && strlen($state->iso_code)) ?
                    $store[$fieldItem] . ', ' . $state->iso_code : $store[$fieldItem];
                    $dataFieldsMod = true;
                }

            }

            if ($dataFieldsMod) {
                $outDatas[] = implode(' ', $addrOut);
            }

        }

        $out = implode('<br />', $outDatas);

        return $out;
    }

    /**
     * Render opening hours
     *
     * @param $store
     *
     * @return bool|string
     *
     * @since 1.8.1.0
     */
    public function renderStoreWorkingHours($store) {

        global $smarty;

        $days[1] = 'Monday';
        $days[2] = 'Tuesday';
        $days[3] = 'Wednesday';
        $days[4] = 'Thursday';
        $days[5] = 'Friday';
        $days[6] = 'Saturday';
        $days[7] = 'Sunday';

        $daysDatas = [];
        $hours = [];

        if ($store['hours']) {
            $hours = json_decode($store['hours'], true);

            // Retrocompatibility for ephenyx <= 1.0.4.
            //
            // To get rid of this, introduce a data converter executed by the
            // upgrader over a couple of releases, making this obsolete.

            if (!$hours) {
                $hours = Tools::unSerialize($store['hours']);
            }

            if (is_array($hours)) {
                $hours = array_filter($hours);
            }

        }

        if (!empty($hours)) {

            for ($i = 1; $i < 8; $i++) {

                if (isset($hours[(int) $i - 1])) {
                    $hoursDatas = [];
                    $hoursDatas['hours'] = $hours[(int) $i - 1];
                    $hoursDatas['day'] = $days[$i];
                    $daysDatas[] = $hoursDatas;
                }

            }

            $smarty->assign('days_datas', $daysDatas);
            $smarty->assign('id_country', $store['id_country']);

            return $this->context->smarty->fetch(_EPH_THEME_DIR_ . 'store_infos.tpl');
        }

        return false;
    }

}
