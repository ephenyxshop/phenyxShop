<?php

/**
 * Class AdminMaintenanceControllerCore
 *
 * @since 1.9.1.0
 */
class AdminMaintenanceControllerCore extends AdminController {

    /**
     * AdminMaintenanceControllerCore constructor.
     *
     * @since 1.9.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Maintenance';
        $this->table = 'maintenance';
        $this->publicName = $this->l('Paramètre de Maintenance');

        parent::__construct();

        $this->fields_options = [
            'general' => [
                'title'  => $this->l('General'),
                'fields' => [
                    'PS_SHOP_ENABLE'      => [
                        'title'      => $this->l('Enable Shop'),
                        'desc'       => $this->l('Activate or deactivate your shop (It is a good idea to deactivate your shop while you perform maintenance. Please note that the webservice will not be disabled).'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'PS_MAINTENANCE_IP'   => [
                        'title'      => $this->l('Maintenance IP'),
                        'hint'       => $this->l('IP addresses allowed to access the front office even if the shop is disabled. Please use a comma to separate them (e.g. 42.24.4.2,127.0.0.1,99.98.97.96)'),
                        'validation' => 'isGenericName',
                        'type'       => 'maintenance_ip',
                        'default'    => '',
                    ],
                    'PS_MAINTENANCE_TEXT' => [
                        'title'      => $this->l('Custom maintenance text'),
                        'hint'       => $this->l('90 of 21844 characters allowed'),
                        'validation' => 'isCleanHtml',
                        'type'       => 'textareaLang',
                        'class'      => 'autoload_rte',
                        'lang'       => true,
                        'default'    => '',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
        ];

        $this->ajaxOptions = $this->generateOptions();
    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _PS_JS_DIR_ . 'tinymce/tinymce.min.js',
            _PS_JS_DIR_ . 'tinymce.inc.js',
        ]);
    }

    public function generateOptions() {

        if ($this->fields_options && is_array($this->fields_options)) {

            $helper = new HelperOptions();
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->toolbar_btn = [
                'save' => [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ],
            ];
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            $options = $helper->generateOptions($this->fields_options);

            return $options;
        }

        return '';
    }

    public function ajaxProcessUpdateConfigurationOptions() {

        $languages = Language::getLanguages(false);

        Configuration::updateValue('PS_SHOP_ENABLE', Tools::getValue('PS_SHOP_ENABLE'));
        Configuration::updateValue('PS_MAINTENANCE_IP', Tools::getValue('PS_MAINTENANCE_IP'));

        foreach ($languages as $lang) {
            $PS_MAINTENANCE_TEXT[$lang['id_lang']] = Tools::getValue('PS_MAINTENANCE_TEXT_' . $lang['id_lang']);
        }

        Configuration::updateValue('PS_MAINTENANCE_TEXT', $PS_MAINTENANCE_TEXT, true);

        $result = [
            "success" => true,
            "message" => "Les options ont été mises à jour avec succès",
        ];

        die(Tools::jsonEncode($result));
    }

}
