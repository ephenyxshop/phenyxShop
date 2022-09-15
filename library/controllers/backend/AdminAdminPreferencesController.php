<?php

/**
 * Class AdminAdminPreferencesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminAdminPreferencesControllerCore extends AdminController {

    public $php_self = 'adminadminpreferences';

    public function __construct() {

        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        // Upload quota
        $maxUpload = (int) ini_get('upload_max_filesize');
        $maxPost = (int) ini_get('post_max_size');
        $uploadMb = min($maxUpload, $maxPost);

        // Options list
        $this->fields_options = [
            'general'       => [
                'title'  => $this->l('General'),
                'icon'   => 'icon-cogs',
                'fields' => [
                    'PRESTASTORE_LIVE'      => [
                        'title'      => $this->l('Automatically check for module updates'),
                        'hint'       => $this->l('New modules and updates are displayed on the modules page.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_COOKIE_CHECKIP'     => [
                        'title'      => $this->l('Check the cookie\'s IP address'),
                        'hint'       => $this->l('Check the IP address of the cookie in order to prevent your cookie from being stolen.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                        'default'    => '0',
                    ],
                    'EPH_COOKIE_LIFETIME_FO' => [
                        'title'      => $this->l('Lifetime of front office cookies'),
                        'hint'       => $this->l('Set the amount of hours during which the front office cookies are valid. After that amount of time, the customer will have to log in again.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('hours'),
                        'default'    => '480',
                    ],
                    'EPH_COOKIE_LIFETIME_BO' => [
                        'title'      => $this->l('Lifetime of back office cookies'),
                        'hint'       => $this->l('Set the amount of hours during which the back office cookies are valid. After that amount of time, the ephenyx user will have to log in again.'),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('hours'),
                        'default'    => '480',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'upload'        => [
                'title'  => $this->l('Upload quota'),
                'icon'   => 'icon-cloud-upload',
                'fields' => [
                    'EPH_ATTACHMENT_MAXIMUM_SIZE'  => [
                        'title'      => $this->l('Maximum size for attachment'),
                        'hint'       => sprintf($this->l('Set the maximum size allowed for attachment files (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '2',
                    ],
                    'EPH_LIMIT_UPLOAD_FILE_VALUE'  => [
                        'title'      => $this->l('Maximum size for a downloadable product'),
                        'hint'       => sprintf($this->l('Define the upload limit for a downloadable product (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '1',
                    ],
                    'EPH_LIMIT_UPLOAD_IMAGE_VALUE' => [
                        'title'      => $this->l('Maximum size for a product\'s image'),
                        'hint'       => sprintf($this->l('Define the upload limit for an image (in megabytes). This value has to be lower or equal to the maximum file upload allotted by your server (currently: %s MB).'), $uploadMb),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'type'       => 'text',
                        'suffix'     => $this->l('megabytes'),
                        'default'    => '1',
                    ],
                ],
                'submit' => ['title' => $this->l('Save')],
            ],
            'notifications' => [
                'title'       => $this->l('Notifications'),
                'icon'        => 'icon-list-alt',
                'description' => $this->l('Notifications are numbered bubbles displayed at the very top of your back office, right next to the shop\'s name. They display the number of new items since you last clicked on them.'),
                'fields'      => [
                    'EPH_SHOW_NEW_ORDERS'    => [
                        'title'      => $this->l('Show notifications for new orders'),
                        'hint'       => $this->l('This will display notifications when new orders are made in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_SHOW_NEW_CUSTOMERS' => [
                        'title'      => $this->l('Show notifications for new customers'),
                        'hint'       => $this->l('This will display notifications every time a new customer registers in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                    'EPH_SHOW_NEW_MESSAGES'  => [
                        'title'      => $this->l('Show notifications for new messages'),
                        'hint'       => $this->l('This will display notifications when new messages are posted in your shop.'),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'type'       => 'bool',
                    ],
                ],
                'submit'      => ['title' => $this->l('Save')],
            ],
        ];
    }

    /**
     * @since 1.9.1.0
     */
    public function postProcess() {

        $uploadMaxSize = (int) str_replace('M', '', ini_get('upload_max_filesize'));
        $postMaxSize = (int) str_replace('M', '', ini_get('post_max_size'));
        $maxSize = $uploadMaxSize < $postMaxSize ? $uploadMaxSize : $postMaxSize;

        if (Tools::getValue('EPH_LIMIT_UPLOAD_FILE_VALUE') > $maxSize || Tools::getValue('EPH_LIMIT_UPLOAD_IMAGE_VALUE') > $maxSize) {
            $this->errors[] = Tools::displayError('The limit chosen is larger than the server\'s maximum upload limit. Please increase the limits of your server.');

            return;
        }

        if (Tools::getIsset('EPH_LIMIT_UPLOAD_FILE_VALUE') && !Tools::getValue('EPH_LIMIT_UPLOAD_FILE_VALUE')) {
            $_POST['EPH_LIMIT_UPLOAD_FILE_VALUE'] = 1;
        }

        if (Tools::getIsset('EPH_LIMIT_UPLOAD_IMAGE_VALUE') && !Tools::getValue('EPH_LIMIT_UPLOAD_IMAGE_VALUE')) {
            $_POST['EPH_LIMIT_UPLOAD_IMAGE_VALUE'] = 1;
        }

        parent::postProcess();
    }

    /**
     * Update EPH_ATTACHMENT_MAXIMUM_SIZE
     *
     * @param mixed $value
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsAttachementMaximumSize($value) {

        if (!$value) {
            return;
        }

        $uploadMaxSize = (int) str_replace('M', '', ini_get('upload_max_filesize'));
        $postMaxSize = (int) str_replace('M', '', ini_get('post_max_size'));
        $maxSize = $uploadMaxSize < $postMaxSize ? $uploadMaxSize : $postMaxSize;
        $value = ($maxSize < Tools::getValue('EPH_ATTACHMENT_MAXIMUM_SIZE')) ? $maxSize : Tools::getValue('EPH_ATTACHMENT_MAXIMUM_SIZE');
        Configuration::updateValue('EPH_ATTACHMENT_MAXIMUM_SIZE', $value);
    }

}
