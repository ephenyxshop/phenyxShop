<?php

/**
 * Use this helper to generate preferences forms, with values stored in the configuration table
 *
 * @since 2.1.0.0
 */
class HelperOptionsCore extends Helper {

    /** @var bool $required */
    public $required = false;
    /** @var int $id */
    public $id;

    public $isParagrid = false;

    /**
     * HelperOptionsCore constructor.
     *
     * @since 2.1.0.0
     */
    public function __construct() {

        $this->base_folder = 'helpers/options/';
        $this->base_tpl = 'options.tpl';
        parent::__construct();
    }

    /**
     * Generate a form for options
     *
     * @param array $optionList
     *
     * @return string html
     *
     * @throws Exception
     * @throws HTMLPurifier_Exception
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 2.1.0.0
     */
    public function generateOptions($optionList) {

        if ($this->isParagrid) {
            $this->base_tpl = 'options-grid.tpl';
        }

        $this->tpl = $this->createTemplate($this->base_tpl);
        $tab = EmployeeMenu::getTab($this->context->language->id, $this->id);

        if (!isset($languages)) {
            $languages = Language::getLanguages(false);
        }

       
        
	
        foreach ($optionList as $category => &$categoryData) {

            if (!is_array($categoryData)) {
                continue;
            }

            if (!isset($categoryData['image'])) {
                $categoryData['image'] = '';
            }

            if (!isset($categoryData['fields'])) {
                $categoryData['fields'] = [];
            }

            $categoryData['hide_multishop_checkbox'] = true;

            if (isset($categoryData['tabs'])) {
                $tabs[$category] = $categoryData['tabs'];
                $tabs[$category]['misc'] = $this->l('Miscellaneous');
            }

            foreach ($categoryData['fields'] as $key => &$field) {

                

                // Set field value unless explicitly denied

                if (!isset($field['auto_value']) || $field['auto_value']) {
                    $field['value'] = $this->getOptionValue($key, $field);
                }

                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $isDisabled = $isInvisible = false;


                $field['is_disabled'] = $isDisabled;
                $field['is_invisible'] = $isInvisible;

                $field['required'] = isset($field['required']) ? $field['required'] : $this->required;

                if ($field['type'] === 'color') {
                    $this->context->controller->addJqueryPlugin('colorpicker');
                }

                if ($field['type'] === 'textarea' || $field['type'] === 'textareaLang') {
                    $this->context->controller->addJqueryPlugin('autosize');
                    $iso = file_exists(_SHOP_ROOT_DIR_ .'/js/tinymce/langs/' .$this->context->language->iso_code. '.js') ? $this->context->language->iso_code : 'en';
                    $this->tpl->assign(
                        [
                            'iso'      => $iso,
                            'path_css' => _THEME_CSS_DIR_,
                            'ad'       => __EPH_BASE_URI__ . basename(_EPH_ROOT_DIR_),
                        ]
                    );

                }

                if ($field['type'] === 'code') {
                    $this->context->controller->addJS('https://cdn.ephenyxapi.com/ace/ace.js');
                }

                if ($field['type'] == 'tags') {
                    $this->context->controller->addJqueryPlugin('tagify');
                }

                if ($field['type'] == 'file') {
                    $uploader = new HelperUploader();
                    $uploader->setId(isset($field['id']) ? $field['id'] : null);
                    $uploader->setName($field['name']);
                    $uploader->setUrl(isset($field['url']) ? $field['url'] : null);
                    $uploader->setMultiple(isset($field['multiple']) ? $field['multiple'] : false);
                    $uploader->setUseAjax(isset($field['ajax']) ? $field['ajax'] : false);
                    $uploader->setMaxFiles(isset($field['max_files']) ? $field['max_files'] : null);

                    if (isset($field['files']) && $field['files']) {
                        $uploader->setFiles($field['files']);
                    } else if (isset($field['image']) && $field['image']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'       => HelperUploader::TYPE_IMAGE,
                                    'image'      => isset($field['image']) ? $field['image'] : null,
                                    'size'       => isset($field['size']) ? $field['size'] : null,
                                    'delete_url' => isset($field['delete_url']) ? $field['delete_url'] : null,
                                ],
                            ]
                        );
                    }

                    if (isset($field['file']) && $field['file']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'         => HelperUploader::TYPE_FILE,
                                    'size'         => isset($field['size']) ? $field['size'] : null,
                                    'delete_url'   => isset($field['delete_url']) ? $field['delete_url'] : null,
                                    'download_url' => isset($field['file']) ? $field['file'] : null,
                                ],
                            ]
                        );
                    }

                    if (isset($field['thumb']) && $field['thumb']) {
                        // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'  => HelperUploader::TYPE_IMAGE,
                                    'image' => isset($field['thumb']) ? '<img src="' . $field['thumb'] . '" alt="' . $field['title'] . '" title="' . $field['title'] . '" />' : null,
                                ],
                            ]
                        );
                    }

                    $uploader->setTitle(isset($field['title']) ? $field['title'] : null);
                    $field['file'] = $uploader->render();
                }

                // Cast options values if specified

                if ($field['type'] == 'select' && isset($field['cast'])) {

                    foreach ($field['list'] as $optionKey => $option) {
                        $field['list'][$optionKey][$field['identifier']] = $field['cast']($option[$field['identifier']]);
                    }

                }

                if (isset($field['json']) && $field['json']) {
                    $field['value'] = explode(',', Configuration::get($key));

                }

                // Fill values for all languages for all lang fields

                if (substr($field['type'], -4) == 'Lang') {

                    foreach ($languages as $language) {

                        if ($field['type'] == 'textLang') {
                            $value = Tools::getValue($key . '_' . $language['id_lang'], Configuration::get($key, $language['id_lang']));
                        } else if ($field['type'] == 'textareaLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        } else if ($field['type'] == 'selectLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        }

                        $field['languages'][$language['id_lang']] = isset($value) ? $value : '';

                        if (!is_array($field['value'])) {
                            $field['value'] = [];
                        }

                        $field['value'][$language['id_lang']] = $this->getOptionValue($key . '_' . strtoupper($language['iso_code']), $field);
                    }

                }

                // pre-assign vars to the tpl
                // @todo move this

                if ($field['type'] == 'maintenance_ip') {
                    $field['script_ip'] = '
                        <script type="text/javascript">
                            function addRemoteAddr()
                            {
                                var length = $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\').length;
                                if (length > 0)
                                    $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\',$(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\') +\',' . Tools::getRemoteAddr() . '\');
                                else
                                    $(\'input[name=EPH_MAINTENANCE_IP]\').attr(\'value\',\'' . Tools::getRemoteAddr() . '\');
                            }
                        </script>';
                    $field['link_remove_ip'] = '<button type="button" class="btn btn-default" onclick="addRemoteAddr();"><i class="icon-plus"></i> ' . $this->l('Add my IP', 'Helper') . '</button>';
                }

                // Multishop default value
                $field['multishop_default'] = false;

                

                // Assign the modifications back to parent array
                $categoryData['fields'][$key] = $field;

                // Is at least one required field present?

                if (isset($field['required']) && $field['required']) {
                    $categoryData['required_fields'] = true;
                }

            }

            // Assign the modifications back to parent array
            //$optionList[$category] = $optionList;
        }

        $this->tpl->assign(
            [
                'title'               => $this->title,
                'toolbar_btn'         => $this->toolbar_btn,
                'show_toolbar'        => $this->show_toolbar,
                'toolbar_scroll'      => $this->toolbar_scroll,
                'current'             => $this->currentIndex,
                'table'               => $this->table,
                'token'               => $this->token,
                'tabs'                => (isset($tabs)) ? $tabs : null,
                'option_list'         => $optionList,
                'current_id_lang'     => $this->context->language->id,
                'languages'           => isset($languages) ? $languages : null,
                'currency_left_sign'  => $this->context->currency->getSign('left'),
                'currency_right_sign' => $this->context->currency->getSign('right'),
				'controller'		  => $this->controller_name,
				'theme_path'	      => _EPH_THEMES_DIR_
            ]
        );

        return parent::generate();
    }

    /**
     * Type = image
     *
     * @since 2.1.0.0
     */
    public function displayOptionTypeImage($key, $field, $value) {

        echo '<table cellspacing="0" cellpadding="0">';
        echo '<tr>';

        $i = 0;

        foreach ($field['list'] as $theme) {
            echo '<td class="center" style="width: 180px; padding:0px 20px 20px 0px;">';
            echo '<input type="radio" name="' . $key . '" id="' . $key . '_' . $theme['name'] . '_on" style="vertical-align: text-bottom;" value="' . $theme['name'] . '"' . (_THEME_NAME_ == $theme['name'] ? 'checked="checked"' : '') . ' />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on"> ' . mb_strtolower($theme['name']) . '</label>';
            echo '<br />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on">';
            echo '<img src="../themes/' . $theme['name'] . '/preview.jpg" alt="' . mb_strtolower($theme['name']) . '">';
            echo '</label>';
            echo '</td>';

            if (isset($field['max']) && ($i + 1) % $field['max'] == 0) {
                echo '</tr><tr>';
            }

            $i++;
        }

        echo '</tr>';
        echo '</table>';
    }

    /**
     * Type = price
     *
     * @since 2.1.0.0
     */
    public function displayOptionTypePrice($key, $field, $value) {

        echo $this->context->currency->getSign('left');
        $this->displayOptionTypeText($key, $field, $value);
        echo $this->context->currency->getSign('right') . ' ' . $this->l('(tax excl.)', 'Helper');
    }

    /**
     * Type = disabled
     *
     * @since 2.1.0.0
     */
    public function displayOptionTypeDisabled($key, $field, $value) {

        echo $field['disabled'];
    }

    /**
     * @param string $key
     * @param array  $field
     *
     * @return string
     *
     * @throws HTMLPurifier_Exception
     * @throws PhenyxShopException
     * @since 2.1.0.0
     */
    public function getOptionValue($key, $field) {

        $value = Tools::getValue($key, Configuration::get($key));

        if (!Validate::isCleanHtml($value)) {
            $value = Configuration::get($key);
        }

        if (isset($field['defaultValue']) && !$value) {
            $value = $field['defaultValue'];
        }

        return Tools::purifyHTML($value);
    }

}
