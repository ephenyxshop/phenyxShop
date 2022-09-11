<?php
use MatthiasMullie\Minify;

/**
 * Class AdminThemesControllerCore
 *
 * @since 1.9.1.0
 */
class AdminThemesControllerCore extends AdminController {

    public $php_self = 'adminthemes';

    const MAX_NAME_LENGTH = 128;
    // @codingStandardsIgnoreStart
    public $className = 'Theme';
    public $table = 'theme';
    protected $toolbar_scroll = false;
    private $img_error;
    public $can_display_themes = false;
    public $to_install = [];
    public $to_enable = [];
    public $to_disable = [];
    public $to_hook = [];
    public $hook_list = [];
    public $module_list = [];
    public $native_modules = [];
    public $user_doc = [];
    public $image_list = [];
    public $to_export = [];

    public $bootsrap_index;

    public $bootsrap_education;

    public $xml_file;

    public $all_demo = [];

    public $fieldForm = [];

    public static $shortname = 'xprt';

    public static $color_group = [

        'default' => 'Default Schema',
    ];

    public $fonts_files;

    public $fulle_management;

    public $education_management;

    // @codingStandardsIgnoreEnd

    /**
     * AdminThemesControllerCore constructor.
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'theme';
        $this->className = 'Theme';
        $this->publicName = $this->l('Thème front office');
        $this->context = Context::getContext();

        parent::__construct();

        $this->updateFontFamily();

        $this->fulle_management = Configuration::get('EPH_FULL_THEME_MANAGEMENT_MODE');
        $this->education_management = _EDUCATION_MODE_;
        $this->ajaxOptions = $this->generateThemeConfigurator();

        $this->extra_vars = [
            'fulle_management'     => $this->fulle_management,
            'education_management' => $this->education_management,
            'EPH_POLYGON_ACTIVE'   => !empty(Configuration::get('EPH_POLYGON_ACTIVE')) ? Configuration::get('EPH_POLYGON_ACTIVE') : 1,
        ];

        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/themes.css', _PS_JS_DIR_ . 'colorpicker/jquery.colorpicker.css', _PS_JS_DIR_ . 'ace/theme/twilight.css',
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/css_back.css',
        ]);

        $this->bootsrap_index = Configuration::get('xprtbootsrap_index');

        $this->bootsrap_education = Configuration::get('xprtbootsrap_education');

        $this->fonts_files = _EPH_THEME_DIR . 'phenyx_fonts.json';

        $this->fieldForm = [
            'input' => [

                [
                    'name' => 'EPH_POLYGON_ACTIVE',

                ],
                [
                    'name' => 'EPH_HOME_SLIDER_ACTIVE',
                ],
                [
                    'name' => 'EPH_HOME_VIDEO_ACTIVE',
                ],
                [
                    'name' => 'EPH_HOME_VIDEO_LINK',
                ],
                [
                    'name' => 'EPH_HOME_PARALLAX_ACTIVE',
                ],
                [
                    'name' => 'parallaxImage',
                    'type' => 'img_base64',
                ],
                [
                    'name' => 'logo_position',
                ],
                [
                    'name' => 'logo_qualiopi',
                    'type' => 'img_base64',
                ],
                [
                    'name' => 'bck_footer_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'footer_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'footer_link_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'footer_link_hvr_color',

                    'type' => 'color',
                ],
                [
                    'name' => 'footer_style',
                ],
                [
                    'name' => 'bootsrap_index',
                ],
                [
                    'name' => 'bootsrap_education',
                ],
                [
                    'name' => 'enable_papier_bg',
                ],
                [
                    'name' => 'papier_bg_image',
                    'type' => 'img_base64',
                ],
                [
                    'name' => 'EPH_PICTO_MYSPACE',
                    'type' => 'img_base64',
                ],
                [
                    'name' => 'EPH_FIXED_PICTO_MYSPACE',
                    'type' => 'img_base64',
                ],
                [
                    'name' => 'dynamic_picto_space',
                ],
                [
                    'name' => 'cadenas_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'cadenas_width',
                ],
                [
                    'name' => 'cadenas_height',
                ],
                [
                    'name' => 'cadenas_bck_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'footer_width',
                ],
                [
                    'name' => 'footer_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'footer_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'footer_f_size',
                    'type' => 'select_font_size',
                ],
                [
                    'name' => 'footer_before',
                    'type' => 'element_before',
                ],
                [
                    'name' => 'footer_after',
                    'type' => 'element_after',
                ],
                [
                    'name' => 'like_h4_f_size',
                    'type' => 'select_font_size',
                ],
                [
                    'name' => 'like_h4_height',
                ],
                [
                    'name' => 'like_h4_f_line_height',
                    'type' => 'select_line_height',
                ],
                [
                    'name' => 'footer_font_transform',
                ],
                [
                    'name' => 'like_h4_font_transform',
                ],
                [
                    'name' => 'like_h4_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'like_h4_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'sticky_menu',
                ],

                [
                    'name' => 'gray_image_bg',
                ],
                [
                    'name' => 'per_line_home',
                ],
                [
                    'name' => 'per_line_category',
                ],
                [
                    'name' => 'per_line_product',
                ],
                [
                    'name' => 'per_line_othr',
                ],
                [
                    'name' => 'hometopleftright_col',
                ],
                [
                    'name' => 'homebottomleftright_col',
                ],
                [
                    'name' => 'searchtaxstatus',
                ],
                [
                    'name' => 'breadcrumb_height',
                ],
                [
                    'name' => 'page_global_bg_color',
                    'type' => 'gradient',
                ],
                [
                    'name' => 'body_page_style',
                ],
                [
                    'name' => 'body_boxed_width',
                ],
                [
                    'name' => 'container_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'container_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'body_f_size',
                    'type' => 'select_font_size',
                ],
                [
                    'name' => 'body_font_weight',
                ],
                [
                    'name' => 'body_bg_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'page_boxed_bg',
                    'type' => 'color',
                ],

                [
                    'name' => 'body_f_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'like_h4_f_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'enable_body_bg',
                ],
                [
                    'name' => 'body_bg_image',
                ],
                [
                    'name' => 'body_bg_pattern',
                ],
                [
                    'name' => 'body_bg_repeat',
                ],
                [
                    'name' => 'body_bg_position',
                    'type' => 'background_position',
                ],
                [
                    'name' => 'body_bg_size',
                ],
                [
                    'name' => 'body_bg_attachment',
                ],
                [
                    'name' => 'bodyfont',
                    'type' => 'googlefont',
                ],
                [
                    'name' => 'page_header_style',
                ],
                [
                    'name' => 'header_width',
                ],
                [
                    'name' => 'header_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'header_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'headingfont',
                    'type' => 'googlefont',
                ],

                [
                    'name' => 'header_height',
                ],
                [
                    'name' => 'header_fixed',
                ],

                [
                    'name' => 'bck_header_color',
                ],
                [
                    'name' => 'additionalfont',
                    'type' => 'googlefont',
                ],
                [
                    'name' => 'additionalfont2',
                    'type' => 'googlefont',
                ],
                [
                    'name' => 'footerfont',
                    'type' => 'googlefont',
                ],
                [
                    'name' => 'prod_page_style',
                ],
                [
                    'name' => 'prod_tab_style',
                ],
                [
                    'name' => 'prd_next_prev',
                ],

                [
                    'name' => 'link_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'sigin_txt_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'signin_bg_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'link_hvr_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'label_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'slider_bck_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'slider_row_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'slider_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'bottom_slider_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'slider_offset',
                    'type' => 'bootstrap_offset',
                ],
                [
                    'name' => 'slider_bootsrap_width',
                    'type' => 'bootstrap_offset',
                ],
                [
                    'name' => 'color_forte',
                    'type' => 'color',
                ],
                [
                    'name' => 'color_forte_second',
                    'type' => 'color',
                ],
                [
                    'name' => 'color_forte_text',
                ],
                [
                    'name' => 'color_forte_second_text',
                ],
                [
                    'name' => 'title_color',
                ],
                [
                    'name' => 'title_font',
                    'type' => 'googlefont',
                ],
                [
                    'name' => 'title_font_bold',
                ],
                [
                    'name' => 'title_font_transform',
                ],
                [
                    'name' => 'button_txt_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'button_bg_color',
                    'type' => 'color',
                ],

                [
                    'name' => 'button_hvr_txt_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'button_hvr_bg_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'page_headidng_color',
                ],
                [
                    'name' => 'header_bck_color',
                ],

                [
                    'name' => 'prod_name_color',
                ],
                [
                    'name' => 'prod_price_color',
                ],
                [
                    'name' => 'prod_rating_color',
                ],
                [
                    'name' => 'prod_sale_badge',
                ],
                [
                    'name' => 'prod_new_badge',
                ],
                [
                    'name' => 'footer_botm_bg_color',
                ],
                [
                    'name' => 'blog_style',
                ],
                [
                    'name' => 'blog_no_of_col',
                ],
                [
                    'name' => 'jk_style_demo',
                ],
                [
                    'name' => 'custom_css',
                ],
                [
                    'name' => 'custom_js',
                ],
                [
                    'name' => 'maintanance_date',
                ],
                [
                    'name' => 'maintanance_title',
                ],
                [
                    'name' => 'maintanance_desc',
                ],
                [
                    'name' => 'maintanance_bg',
                ],
                [
                    'name' => 'polygon_menu',
                ],
                [
                    'name' => 'polygon_core',
                ],
                [
                    'name' => 'cef_polygon_menu',
                ],
                [
                    'name' => 'cef_polygon_core',
                ],
                [
                    'name' => 'polygon_parallax',
                ],
                [
                    'name' => 'polygon_footer',
                ],
                [
                    'name' => 'polygon_menu_color_1',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_2',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_3',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_4',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_5',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_6',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_7',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_8',
                    'type' => 'color',

                ],
                [
                    'name' => 'polygon_menu_color_9',
                    'type' => 'color',

                ],
                [
                    'name' => 'logo_margin',
                    'type' => '4size',

                ],
                [
                    'name' => 'logo_height',

                ],
                [
                    'name' => 'logo_width',

                ],
                [
                    'name' => 'logo_bg_position',
                    'type' => 'background_position',
                ],
                [
                    'name' => 'logo_mobile_bg_position',
                    'type' => 'background_position',
                ],
                [
                    'name' => 'logo_bck_size',
                    'type' => 'background_size',
                ],
                [
                    'name' => 'hookDisplayNav_position',
                    'type' => 'contener_position',

                ],
                [
                    'name' => 'hookDisplayNav_zindex',
                ],
                [
                    'name' => 'hookDisplayNav_width',

                ],
                [
                    'name' => 'bandline_position',
                    'type' => 'contener_position',
                ],
                [
                    'name' => 'bandline_border',
                    'type' => 'contener_border',
                ],
                [
                    'name' => 'headerheader_position',
                    'type' => 'contener_position',
                ],
                [
                    'name' => 'headerheader_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'headerheader_margin',
                    'type' => '4size',
                ],
                [
                    'name' => 'phenyxIndex_border',
                    'type' => 'contener_border',
                ],
                [
                    'name' => 'phenyxIndex_border_radius',
                    'type' => '4size',
                ],
                [
                    'name' => 'phenyxIndex_bck_color',
                    'type' => 'color',
                ],
                [
                    'name' => 'phenyxIndex_width',
                ],
                [
                    'name' => 'phenyxIndex_padding',
                    'type' => '4size',
                ],
                [
                    'name' => 'phenyxIndex_box_shadow',
                    'type' => 'box_shadow',
                ],
                [
                    'name' => 'cadenas_bck_position',
                    'type' => 'background_position',
                ],
                [
                    'name' => 'cadenas_bck_size',
                    'type' => 'background_size',
                ],
                [
                    'name' => 'cadenas_open_bck_size',
                    'type' => 'background_size',
                ],
                [
                    'name' => 'cadenas_open_bck_position',
                    'type' => 'background_position',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager',
                    'type' => 'theme_manager',
                ],
                [

                    'name' => 'phenyxIndex_theme_manager_row_sm',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_row_lg',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_row_sm_offset',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_row_lg_offset',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_row_img_lg',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_row_tag_lg',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_column_xs',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_column_sm',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_column_md',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_column_img_lg',
                ],
                [
                    'name' => 'phenyxIndex_theme_manager_column_tag_lg',
                ],
                [
                    'name' => 'education_image_before',
                    'type' => 'element_before',
                ],

            ],

        ];

        Configuration::updateValue('EPH_EXPERT_THEME_FIELDS', Tools::jsonEncode($this->fieldForm));

    }

    public function generateThemeConfigurator() {

        $tabs = [];

        $tabs['Thèmes'] = [
            'key'     => 'theme',
            'content' => $this->renderTabTheme(),
        ];
        $tabs['Style de la Page'] = [
            'key'     => 'ephthemebst',
            'content' => $this->renderBodyStyleForm(),
        ];

        $tabs['Réglages Polygone'] = [
            'key'     => 'polygone_rules',
            'content' => $this->renderPolygon(),
        ];
        $tabs['Logos & Réglage Généraux'] = [
            'key'     => 'ephtheme',
            'content' => $this->renderGeneralSeetingsForm(),
        ];

        $tabs['Css & Js personalisé'] = [
            'key'     => 'ephthemecustomcss',
            'content' => $this->renderCustomCssForm(),
        ];

        $tabs['Appliquer un thème'] = [
            'key'     => 'theme_save',
            'content' => $this->generateTabTheme(),
        ];

        return $tabs;

    }

    public function renderTabTheme() {

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Thème'),
            ],
            'id_form' => 'theme_base_data',
            'input'   => [
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Utiliser le mode Polygone'),
                    'name'        => 'EPH_POLYGON_ACTIVE',
                    'desc'        => $this->l('Si activé, le site passera en mode polygone Alex trade mark.'),
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'EPH_POLYGON_ACTIVE_on',
                            'value'   => 1,
                            'label_1' => $this->l('Oui'),
                        ],
                        [
                            'id'      => 'EPH_POLYGON_ACTIVE_off',
                            'value'   => 0,
                            'label_0' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Afficher le slider en page d‘acceuil'),
                    'name'        => 'EPH_HOME_SLIDER_ACTIVE',
                    'desc'        => $this->l('Si activé, affiche le slider en page d‘acceuil en front office.'),
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'EPH_HOME_SLIDER_ACTIVE_on',
                            'value'   => 1,
                            'label_1' => $this->l('Oui'),
                        ],
                        [
                            'id'      => 'EPH_HOME_SLIDER_ACTIVE_off',
                            'value'   => 0,
                            'label_0' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Afficher une video en page d‘acceuil'),
                    'name'        => 'EPH_HOME_VIDEO_ACTIVE',
                    'desc'        => $this->l('Si activé, affiche une Vidéo en page d‘acceuil en front office.'),
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'EPH_HOME_VIDEO_ACTIVE_on',
                            'value'   => 1,
                            'label_1' => $this->l('Oui'),
                        ],
                        [
                            'id'      => 'EPH_HOME_VIDEO_ACTIVE_off',
                            'value'   => 0,
                            'label_0' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Lien de la Video'),
                    'name'  => 'EPH_HOME_VIDEO_LINK',
                    'desc'  => $this->l('Lien de la Vidéo, youtube, vimeo ex https://vimeo.com/xxxx...'),
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Afficher une image en Parallax en page d‘acceuil'),
                    'name'        => 'EPH_HOME_PARALLAX_ACTIVE',
                    'desc'        => $this->l('Si activé, affiche une image en Parallax en page d‘acceuil en front office.'),
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'EPH_HOME_PARALLAX_ACTIVE_on',
                            'value'   => 1,
                            'label_1' => $this->l('Oui'),
                        ],
                        [
                            'id'      => 'EPH_HOME_PARALLAX_ACTIVE_off',
                            'value'   => 0,
                            'label_0' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'  => 'upload_img',
                    'label' => $this->l('Prévisualisation de l‘image'),
                    'name'  => 'parallaxImage',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $context = Context::getContext();
        $url_no_image = $context->link->getBaseFrontLink() . 'img/fr.jpg';

        $this->fields_value = [];
        $this->assignFormValue($this->fields_form);

        return parent::renderForm();
    }

    public function renderBodyStyleForm() {

        $forms = [];
        $fields_forms['body'] = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Le Body'),
            ],
            'id_form' => 'theme_body_style',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Paramètres Globales :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'       => 'googlefont',
                    'label'      => $this->l('Police générale (body)'),
                    'name'       => 'bodyfont',
                    'colorclass' => 'success',
                ],
                [
                    'type'        => 'select_font_size',
                    'label'       => $this->l('Taille de la Police du Site'),
                    'name'        => 'body_f_size',
                    'default_val' => '13',
                    'size'        => 10,
                    'required'    => true,
                ],
                [
                    'type'        => 'font_weight',
                    'label'       => $this->l('Poids de la Police'),
                    'name'        => 'body_font_weight',
                    'default_val' => 'normal',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur de la Police du Site'),
                    'name'        => 'body_f_color',

                    'default_val' => '#666666',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Background du Site'),
                    'name'        => 'body_bg_color',

                    'default_val' => '#FFFFFF',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Utiliser une image pour le Background du site'),
                    'name'          => 'enable_body_bg',
                    'form_group_id' => 'body_background',
                    'default_val'   => 'none',
                    'options'       => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => '1',
                                'name' => 'BackGround',
                            ],
                            [
                                'id'   => '0',
                                'name' => 'Pattern',
                            ],
                            [
                                'id'   => 'none',
                                'name' => 'None',
                            ],
                        ],
                    ],
                ],
                [
                    'type'             => 'upload_img',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Select Background Image'),
                    'desc'             => $this->l('Select body background Image'),
                    'name'             => 'body_bg_image',
                    'default_val'      => '',
                ],
                [
                    'type'             => 'bg_pattern',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Select Background Pattern'),
                    'desc'             => $this->l('Select body background pattern'),
                    'name'             => 'body_bg_pattern',
                    'default_val'      => '',
                ],
                [
                    'type'             => 'select',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Body background repeat'),
                    'name'             => 'body_bg_repeat',
                    'default_val'      => 'repeat',
                    'desc'             => $this->l('Select body background repeat/no-repeat'),
                    'options'          => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'repeat',
                                'name' => 'Repeat',
                            ],
                            [
                                'id'   => 'no-repeat',
                                'name' => 'No repeat',
                            ],
                            [
                                'id'   => 'repeat-x',
                                'name' => 'Repeat-X',
                            ],
                            [
                                'id'   => 'repeat-y',
                                'name' => 'Repeat-Y',
                            ],
                        ],
                    ],
                ],
                [
                    'type'             => 'background_position',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Body background position'),
                    'name'             => 'body_bg_position',
                    'desc'             => $this->l('Select body background position'),
                ],
                [
                    'type'             => 'select',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Body background size'),
                    'name'             => 'body_bg_size',
                    'default_val'      => 'initial',
                    'desc'             => $this->l('Select body background size'),
                    'options'          => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'cover',
                                'name' => 'Cover',
                            ],
                            [
                                'id'   => 'contain',
                                'name' => 'Contain',
                            ],
                            [
                                'id'   => 'initial',
                                'name' => 'initial',
                            ],
                            [
                                'id'   => '100% 100%',
                                'name' => '100% 100%',
                            ],
                        ],
                    ],
                ],
                [
                    'type'             => 'select',
                    'form_group_class' => 'enable_body_bg_on',
                    'label'            => $this->l('Body background attachment'),
                    'name'             => 'body_bg_attachment',
                    'default_val'      => 'scroll',
                    'desc'             => $this->l('Select body background attachment'),
                    'options'          => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'scroll',
                                'name' => 'scroll',
                            ],
                            [
                                'id'   => 'fixed',
                                'name' => 'fixed',
                            ],
                            [
                                'id'   => 'local',
                                'name' => 'local',
                            ],
                        ],
                    ],
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Paramètres De la Page :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'gradient',
                    'label'       => $this->l('Couleur de fond (gradiant)'),
                    'name'        => 'page_global_bg_color',
                    'default_val' => '#6e7072',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Paramètres du Corps :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'select',
                    'label'       => $this->l('Conteneur du Corps de la Page'),
                    'name'        => 'body_page_style',

                    'default_val' => 'full_width',
                    'desc'        => $this->l('Choississez le style de largeur. (Fonctionne avec le Header Simple)'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'boxed',
                                'name' => 'Largeur fixe',
                            ],
                            [
                                'id'   => 'full_width',
                                'name' => 'Pleine page',
                            ],
                        ],
                    ],
                ],

                [
                    'type'        => 'select',
                    'label'       => $this->l('Page container width'),
                    'name'        => 'body_boxed_width',
                    'default_val' => '1170',
                    'desc'        => $this->l('Select page container width. (Works with Header Simple style Black)'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => '970',
                                'name' => '970px',
                            ],
                            [
                                'id'   => '1080',
                                'name' => '1080px',
                            ],
                            [
                                'id'   => '1170',
                                'name' => '1170px',
                            ],
                            [
                                'id'   => '1200',
                                'name' => '1200px',
                            ],
                            [
                                'id'   => '1600',
                                'name' => '1600px',
                            ],
                            [
                                'id'   => '1440',
                                'name' => '1440px',
                            ],

                        ],
                    ],
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur du contener- padding (px)'),
                    'name'        => 'container_padding',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du contener - margin (px)'),
                    'name'        => 'container_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Police Additionelle :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'       => 'googlefont',
                    'label'      => $this->l('Police Additionelle'),
                    'name'       => 'additionalfont',
                    'sublabel'   => 'Select additional google font',
                    'colorclass' => 'success',
                ],
                [
                    'type'       => 'googlefont',
                    'label'      => $this->l('Police Additionelle'),
                    'name'       => 'additionalfont2',
                    'sublabel'   => 'Select additional google font',
                    'colorclass' => 'success',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $fields_forms['colors'] = [
            'legend'  => [
                'title' => $this->l('Réglages des Couleurs'),
            ],
            'id_form' => 'theme_color_link_style',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Les liens :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Liens'),
                    'name'        => 'link_color',
                    'default_val' => '#666',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Liens Hover'),
                    'name'        => 'link_hvr_color',
                    'default_val' => '#000',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Les Titres & Labels :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Titres'),
                    'name'        => 'title_color',
                    'default_val' => '#666',
                ],
                [
                    'type'        => 'googlefont',
                    'label'       => $this->l('Police des Titres'),
                    'name'        => 'title_font',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'        => 'font_weight',
                    'label'       => $this->l('Poids de la Police'),
                    'name'        => 'title_font_bold',
                    'default_val' => 'normal',
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Transformation du texte pour les titres'),
                    'name'    => 'title_font_transform',

                    'options' => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'none',
                                'name' => 'Normal (héritage)',
                            ],
                            [
                                'id'   => 'lowercase',
                                'name' => 'minuscules',
                            ],
                            [
                                'id'   => 'uppercase',
                                'name' => 'MAJUSCULES',
                            ],
                            [
                                'id'   => 'capitalize',
                                'name' => 'Première lettre en majuscule',
                            ],
                        ],
                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Labels'),
                    'name'        => 'label_color',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Couleurs Majeures :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Forte Princpale'),
                    'name'        => 'color_forte',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Forte Secondaire'),
                    'name'        => 'color_forte_second',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Forte Texte'),
                    'name'        => 'color_forte_text',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Forte Texte Secondaire'),
                    'name'        => 'color_forte_second_text',
                    'default_val' => '#666',
                    'predefine'   => [

                    ],
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $fields_forms['headerseetings'] = [
            'legend'  => [
                'title' => $this->l('Le Header'),
            ],
            'id_form' => 'header_theme_seetings',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Paramètres Globales :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'select_header',
                    'label'       => $this->l('Style du Header'),
                    'name'        => 'page_header_style',
                    'default_val' => 'header_style_full_boxed',

                    'desc'        => $this->l('Choisissez parmis différents style e header'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'header_style_full_boxed',
                                'name' => 'Header pleine largeur',
                            ],

                            [
                                'id'   => 'header_simple',
                                'name' => 'Largeur Fixe',
                            ],

                        ],
                    ],
                ],
                [
                    'type'        => 'select',
                    'label'       => $this->l('Header width'),
                    'name'        => 'header_width',
                    'default_val' => '1170',
                    'desc'        => $this->l('Select Header width. (Works with Header Largeur Fixe)'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => '1170',
                                'name' => '1170px',
                            ],
                            [
                                'id'   => '1200',
                                'name' => '1200px',
                            ],
                            [
                                'id'   => '1440',
                                'name' => '1440px',
                            ],
                            [
                                'id'   => '970',
                                'name' => '970px',
                            ],
                        ],
                    ],
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur du Header- padding (px)'),
                    'name'        => 'header_padding',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du Header - margin (px)'),
                    'name'        => 'header_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'       => 'googlefont',
                    'label'      => $this->l('Police du Header'),
                    'name'       => 'headingfont',
                    'colorclass' => 'success',
                ],
                [
                    'type'  => 'height',
                    'label' => $this->l('Hauteur du Header'),
                    'desc'  => $this->l('Laisser vide pour auto'),
                    'name'  => 'header_height',
                ],
                [
                    'type'             => 'switch',
                    'label'            => $this->l('Comportement du Header'),
                    'name'             => 'header_fixed',
                    'desc'             => 'Vous pouvez activer ou désactiver un Header Fixe',
                    'class'            => 't',
                    'is_bool'          => true,
                    'form_group_class' => 'header_fixed',
                    'default_val'      => '1',
                    'values'           => [
                        [
                            'id'      => 'header_fixed',
                            'value'   => 1,
                            'label_1' => $this->l('Fixe'),
                        ],
                        [
                            'id'      => 'header_fixed',
                            'value'   => 0,
                            'label_0' => $this->l('Relatif'),
                        ],
                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Background du Header'),
                    'name'        => 'header_bck_color',
                    'default_val' => '#000000',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Header Conteneur'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'          => 'contener_position',
                    'label'         => $this->l('Position du Brandline'),
                    'form_group_id' => 'headerheader_contenerPosition',
                    'name'          => 'headerheader_position',
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur du Conteneur- padding (px)'),
                    'name'        => 'headerheader_padding',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du Conteneur - margin (px)'),
                    'name'        => 'headerheader_margin',
                    'default_val' => '0 auto 0 auto',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Paramètres Globales :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'          => 'contener_position',
                    'label'         => $this->l('Position du Brandline'),
                    'form_group_id' => 'bandline_contenerPosition',
                    'name'          => 'bandline_position',
                ],
                [
                    'type'  => 'contener_border',
                    'label' => $this->l('Border du Brandline'),
                    'name'  => 'bandline_border',
                ],

            ],

        ];

        $fields_forms['indexseetings'] = [
            'legend'  => [
                'title' => $this->l('Page Index'),
            ],
            'id_form' => 'index_theme_seetings',
            'input'   => [

                [
                    'type'      => 'theme_manager',
                    'label'     => $this->l('Gestion de l‘ordre des Widgets'),
                    'name'      => 'phenyxIndex_theme_manager',
                    'templates' => [
                        'bootsrap_index' => [
                            [
                                'key'    => 'row',
                                'name'   => 'Affichage en Ligne',
                                'class'  => 'lines',
                                'fields' => true,
                                'box'    => [
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'id'     => 'phenyxIndex_conteneur',
                                        'name'   => 'Debut du Conteneur Principale',
                                        'drag'   => false,
                                        'fields' => [
                                            [
                                                'id'   => 'row_sm',
                                                'name' => 'Bootstrap sm',
                                            ],
                                            [
                                                'id'   => 'row_lg',
                                                'name' => 'Bootstrap lg',
                                            ],
                                            [
                                                'id'   => 'row_sm_offset',
                                                'name' => 'Bootstrap sm Offset',
                                            ],
                                            [
                                                'id'   => 'row_lg_offset',
                                                'name' => 'Bootstrap lg Offset',
                                            ],

                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayEphenyxIndex',
                                        'name'    => 'Zone Hook displayEphenyxIndex',
                                        'content' => '',
                                    ],
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'drag'   => false,
                                        'id'     => 'qualiopi_conteneur',
                                        'name'   => 'Conteneur Image Qualiopi',
                                        'fields' => [
                                            [
                                                'id'   => 'row_img_lg',
                                                'name' => 'bootstrap lg',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'qualiopiLogo',
                                        'name'    => 'Logo Qualiopi',
                                        'content' => '',
                                    ],
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'drag'   => false,
                                        'id'     => 'qualiopi_tag_conteneur',
                                        'name'   => 'Conteneur Tag Qualiopi',
                                        'fields' => [
                                            [
                                                'id'   => 'row_tag_lg',
                                                'name' => 'bootstrap lg',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'qualiopiTag',
                                        'name'    => 'Tag Certification',
                                        'content' => '$shopName est certifié Qualiopi',
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayEphenyxIndexBottom',
                                        'name'    => 'Zone Hook displayEphenyxIndexBottom',
                                        'content' => '',
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayCertification',
                                        'name'    => 'Zone Hook displayCertification',
                                        'content' => '',
                                    ],
                                ],
                            ],
                            [
                                'key'    => 'column',
                                'name'   => 'Affichage en Colonne',
                                'class'  => 'grid',
                                'fields' => true,
                                'box'    => [
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'id'     => 'phenyxIndex_conteneur',
                                        'name'   => 'Debut du Conteneur Principale',
                                        'drag'   => false,
                                        'fields' => [
                                            [
                                                'id'   => 'column_xs',
                                                'name' => 'Bootstrap xs',
                                            ],
                                            [
                                                'id'   => 'column_sm',
                                                'name' => 'Bootstrap sm',
                                            ],
                                            [
                                                'id'   => 'column_md',
                                                'name' => 'Bootstrap md',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayEphenyxIndex',
                                        'name'    => 'Zone Hook displayEphenyxIndex',
                                        'content' => '',
                                    ],
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'drag'   => false,
                                        'id'     => 'qualiopi_conteneur',
                                        'name'   => 'Conteneur Image Qualiopi',
                                        'fields' => [
                                            [
                                                'id'   => 'column_img_lg',
                                                'name' => 'bootstrap lg',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'qualiopiLogo',
                                        'name'    => 'Logo Qualiopi',
                                        'content' => '',
                                    ],
                                    [
                                        'type'   => 'bootstrap_conteneur',
                                        'drag'   => false,
                                        'id'     => 'qualiopi_tag_conteneur',
                                        'name'   => 'Conteneur Tag Qualiopi',
                                        'fields' => [
                                            [
                                                'id'   => 'column_tag_lg',
                                                'name' => 'bootstrap lg',
                                            ],
                                        ],
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'qualiopiTag',
                                        'name'    => 'Tag Certification',
                                        'content' => '$shopName est certifié Qualiopi',
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayEphenyxIndexBottom',
                                        'name'    => 'Zone Hook displayEphenyxIndexBottom',
                                        'content' => '',
                                    ],
                                    [
                                        'type'    => 'block',
                                        'drag'    => false,
                                        'id'      => 'displayCertification',
                                        'name'    => 'Zone Hook displayCertification',
                                        'content' => '',
                                    ],
                                ],

                            ],
                        ],
                    ],
                ],
                [
                    'type'        => 'border_radius',
                    'label'       => $this->l('Border Radius du tag phenyxIndex'),
                    'name'        => 'phenyxIndex_border_radius',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Background du tag phenyxIndex'),
                    'name'        => 'phenyxIndex_bck_color',
                    'default_val' => 'transparent',
                ],
                [
                    'type'        => 'box_shadow',
                    'label'       => $this->l('Box Shadow'),
                    'name'        => 'phenyxIndex_box_shadow',
                    'default_val' => '0 0 0 rgba(255,255,255,1)',
                ],
                [
                    'type'  => 'contener_border',
                    'label' => $this->l('Border du tag phenyxIndex'),
                    'name'  => 'phenyxIndex_border',
                ],
                [
                    'type'  => 'larger',
                    'label' => $this->l('Largeur du tag phenyxIndex'),
                    'name'  => 'phenyxIndex_width',
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur du tag phenyxIndex- padding (px)'),
                    'name'        => 'phenyxIndex_padding',
                    'default_val' => '0 15px 0 15px',
                ],

                [
                    'type'        => 'select',
                    'label'       => $this->l('Utiliser une image pour le Background Papier'),
                    'name'        => 'enable_papier_bg',
                    'default_val' => '0',
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => '1',
                                'name' => 'Oui',
                            ],
                            [
                                'id'   => '0',
                                'name' => 'Non',
                            ],
                        ],
                    ],
                ],
                [
                    'type'             => 'upload_img',
                    'form_group_class' => 'papier_body_bg_on',
                    'label'            => $this->l('Select Papier Class Image'),
                    'form_group_class' => 'papier_background',
                    'name'             => 'papier_bg_image',
                    'default_val'      => '',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['familyType'] = [
            'legend'  => [
                'title' => $this->l('Les Familles'),
            ],
            'id_form' => 'Family_theme_seetings',
            'input'   => [

                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage FX '),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'  => 'element_before',
                    'label' => $this->l('Lien Image Education Before'),
                    'name'  => 'education_image_before',
                ],

            ],

        ];
        $fields_forms['educations'] = [
            'legend'  => [
                'title' => $this->l('Les Formations'),
            ],
            'id_form' => 'education_theme_seetings',
            'input'   => [
                [
                    'type'      => 'theme_manager',
                    'label'     => $this->l('Gestion de l‘ordre des Widgets'),
                    'name'      => 'phenyxIndex_theme_manager',
                    'templates' => [
                        'bootsrap_education' => [
                            [
                                'key'    => 'row',
                                'name'   => 'Affichage en Ligne',
                                'class'  => 'lines',
                                'fields' => false,

                            ],
                            [
                                'key'    => 'column',
                                'name'   => 'Affichage en Colonne',
                                'class'  => 'grid',
                                'fields' => false,

                            ],
                        ],
                    ],
                ],

            ],

        ];

        $fields_forms['footerseetings'] = [
            'legend'  => [
                'title' => $this->l('Le Footer'),
            ],
            'id_form' => 'footer_theme_seetings',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage généraux :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'select',
                    'label'       => $this->l('Footer style'),
                    'name'        => 'footer_style',

                    'default_val' => 'footer_style_full_boxed',
                    'desc'        => $this->l('Choose different footer style'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'footer_style_full_boxed',
                                'name' => 'Footer pleine Largeur',
                            ],
                            [
                                'id'   => 'footer_simple',
                                'name' => 'Largeur Fixe',
                            ],

                        ],
                    ],
                ],
                [
                    'type'        => 'select',
                    'label'       => $this->l('Footer width'),
                    'name'        => 'footer_width',
                    'default_val' => '1170',
                    'desc'        => $this->l('Select Footer width. (Works with Footer Largeur Fixe)'),
                    'options'     => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => '1170',
                                'name' => '1170px',
                            ],
                            [
                                'id'   => '1200',
                                'name' => '1200px',
                            ],
                            [
                                'id'   => '1440',
                                'name' => '1440px',
                            ],
                            [
                                'id'   => '970',
                                'name' => '970px',
                            ],
                        ],
                    ],
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur du Footer- padding (px)'),
                    'name'        => 'footer_padding',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du Footer - margin (px)'),
                    'name'        => 'footer_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Background du Footer'),
                    'name'        => 'bck_footer_color',
                    'default_val' => '#fff',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Footer'),
                    'name'        => 'footer_color',
                    'default_val' => '#fff',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Liens du Footer'),
                    'name'        => 'footer_link_color',
                    'default_val' => '#fff',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur des Liens Hover'),
                    'name'        => 'footer_link_hvr_color',
                    'default_val' => '#666',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage de la Police'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'       => 'googlefont',
                    'label'      => $this->l('Police du Footer'),
                    'name'       => 'footerfont',
                    'sublabel'   => 'Select additional google font',
                    'colorclass' => 'success',
                ],
                [
                    'type'        => 'select_font_size',
                    'label'       => $this->l('Taille de la Police du Footer'),
                    'name'        => 'footer_f_size',
                    'default_val' => '13',
                    'size'        => 10,
                    'required'    => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Transformation du texte'),
                    'name'    => 'footer_font_transform',
                    'options' => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'none',
                                'name' => 'Normal (héritage)',
                            ],
                            [
                                'id'   => 'lowercase',
                                'name' => 'minuscules',
                            ],
                            [
                                'id'   => 'uppercase',
                                'name' => 'MAJUSCULES',
                            ],
                            [
                                'id'   => 'capitalize',
                                'name' => 'Première lettre en majuscule',
                            ],
                        ],
                    ],
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage #footer .like_h4'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'height',
                    'label'       => $this->l('Hauteur du like_h4'),
                    'name'        => 'like_h4_height',
                    'default_val' => '60',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur de la Police du like_h4'),
                    'name'        => 'like_h4_f_color',

                    'default_val' => '#666666',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'        => 'select_font_size',
                    'label'       => $this->l('Taille de la Police like_h4'),
                    'name'        => 'like_h4_f_size',
                    'default_val' => '13',
                    'size'        => 10,
                    'required'    => true,
                ],
                [
                    'type'        => 'select_line_height',
                    'label'       => $this->l('Hauteur de Ligne like_h4'),
                    'name'        => 'like_h4_f_line_height',
                    'default_val' => '13',
                    'size'        => 10,
                    'required'    => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Transformation du texte like_h4'),
                    'name'    => 'like_h4_font_transform',
                    'options' => [
                        'id'    => 'id',
                        'name'  => 'name',
                        'query' => [
                            [
                                'id'   => 'none',
                                'name' => 'Normal (héritage)',
                            ],
                            [
                                'id'   => 'lowercase',
                                'name' => 'minuscules',
                            ],
                            [
                                'id'   => 'uppercase',
                                'name' => 'MAJUSCULES',
                            ],
                            [
                                'id'   => 'capitalize',
                                'name' => 'Première lettre en majuscule',
                            ],
                        ],
                    ],
                ],
                [
                    'type'        => 'padding',
                    'label'       => $this->l('Espacement intérieur dulike_h4- padding (px)'),
                    'name'        => 'like_h4_padding',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du like_h4 - margin (px)'),
                    'name'        => 'like_h4_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage FX '),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'  => 'element_before',
                    'label' => $this->l('Footer Before'),
                    'name'  => 'footer_before',
                ],
                [
                    'type'  => 'element_after',
                    'label' => $this->l('Footer After'),
                    'name'  => 'footer_after',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['Bouton'] = [
            'legend'  => [
                'title' => $this->l('Les boutons'),
            ],
            'id_form' => 'theme_color_button_style',
            'input'   => [
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Texte'),
                    'name'        => 'button_txt_color',
                    'default_val' => '#000',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Background'),
                    'name'        => 'button_bg_color',
                    'default_val' => 'transparent',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Texte Hover'),
                    'name'        => 'button_hvr_txt_color',
                    'default_val' => '#FFF',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Background Hover'),
                    'name'        => 'button_hvr_bg_color',
                    'default_val' => '#000000',
                    'predefine'   => [
                    ],
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['signIn'] = [
            'legend'  => [
                'title' => $this->l('Popup SignIn'),
            ],
            'id_form' => 'theme_color_signin_style',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Réglage généraux :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'          => 'contener_position',
                    'label'         => $this->l('Position du Conteneur'),
                    'form_group_id' => 'hookDisplayNav_contenerPosition',
                    'name'          => 'hookDisplayNav_position',
                    'default_val'   => '0 0 0 0',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Z index du Conteneur'),
                    'name'  => 'hookDisplayNav_zindex',
                ],
                [
                    'type'        => 'larger',
                    'label'       => $this->l('Largeur du Conteneur'),
                    'name'        => 'hookDisplayNav_width',
                    'default_val' => '150',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Pictogramme mon Espace'),
                    'name'        => 'EPH_PICTO_MYSPACE',
                    'default_val' => '',
                ],

                [
                    'type'        => 'switch',
                    'label'       => $this->l('Pictogramme Dynamique'),
                    'name'        => 'dynamic_picto_space',
                    'desc'        => 'Image css Sprite',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'dynamic_picto_space_on',
                            'value'   => 1,
                            'label_1' => $this->l('Oui'),
                        ],
                        [
                            'id'      => 'dynamic_picto_space_off',
                            'value'   => 0,
                            'label_0' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'  => 'background_position',
                    'label' => $this->l('BackGround Position du Cadenas'),
                    'name'  => 'cadenas_bck_position',
                ],
                [
                    'type'  => 'background_size',
                    'label' => $this->l('BackGround Size du Cadenas'),
                    'name'  => 'cadenas_bck_size',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du cadenas - margin (px)'),
                    'name'        => 'cadenas_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'larger',
                    'label'       => $this->l('Largeur du Cadenas'),
                    'name'        => 'cadenas_width',
                    'default_val' => '50',
                ],
                [
                    'type'        => 'height',
                    'label'       => $this->l('Hauteur du Cadenas'),
                    'name'        => 'cadenas_height',
                    'default_val' => '100',
                ],

                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Popup Ouverte :'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Background'),
                    'name'        => 'signin_bg_color',
                    'default_val' => 'transparent',
                    'predefine'   => [
                    ],
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur du Texte'),
                    'name'        => 'sigin_txt_color',
                    'default_val' => '#000',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Pictogramme Popup Ouverte'),
                    'name'        => 'EPH_FIXED_PICTO_MYSPACE',
                    'default_val' => '',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('BackGround du Cadenas'),
                    'name'        => 'cadenas_bck_color',
                    'default_val' => 'transparent',
                ],
                [
                    'type'  => 'background_size',
                    'label' => $this->l('BackGround Size du Cadenas'),
                    'name'  => 'cadenas_open_bck_size',
                ],
                [
                    'type'  => 'background_position',
                    'label' => $this->l('BackGround Position du Cadenas'),
                    'name'  => 'cadenas_open_bck_position',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['sliderHome'] = [
            'legend'  => [
                'title' => $this->l('Le Slider'),
            ],
            'id_form' => 'slider_style',
            'input'   => [

                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Revslider Home'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Appliquer un BackGround au Slider'),
                    'name'        => 'slider_bck_color',
                    'default_val' => 'transparent',
                    'predefine'   => [

                    ],
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du conteneur - margin (%)'),
                    'name'        => 'slider_row_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement du Slider - margin (%)'),
                    'name'        => 'slider_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'        => 'bootstrap_offset',
                    'label'       => $this->l('Larger Bootsrap du conteneur'),
                    'name'        => 'slider_bootsrap_width',
                    'default_val' => '12',
                ],
                [
                    'type'        => 'bootstrap_offset',
                    'label'       => $this->l('Espacement extérieur Bootsrap Offset'),
                    'name'        => 'slider_offset',
                    'default_val' => '0',

                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Revslider Index Bottom'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement du Slider - margin (%)'),
                    'name'        => 'bottom_slider_margin',
                    'default_val' => '0 0 0 0',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $url_no_image = Context::getContext()->link->getBaseFrontLink() . 'img/fr.jpg';
        $xprt = [];
        $expertFields = Tools::jsonDecode(Configuration::get('EPH_EXPERT_THEME_FIELDS'), true);

        foreach ($expertFields['input'] as $mvalue) {

            if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {
                $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name'], $id_lang);
            } else
            if (isset($mvalue['name'])) {
                $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name']);
            }

        }

        foreach ($fields_forms as $key => $fields_form) {

            $this->fields_form = $fields_form;

            $rand_time = time();
            $image_path = _PS_EPH_THEME_DIR_ . $rand_time . '_img.jpg';
            $image_url = _EPH_THEME_DIR . $rand_time . '_img.jpg';
            $color_group_name = Configuration::get(self::$shortname . "color_group_name");
            $color_group_name_sel = Configuration::get(self::$shortname . "color_group_name_sel");
            $theme_dir = Context::getContext()->shop->theme_directory;
            $url_no_image = $this->context->link->getBaseFrontLink() . 'img/fr.jpg';
            $this->tpl_form_vars = [
                'type_image_path'      => $image_path,
                'type_image_url'       => $image_url,
                'type_rand_time'       => $rand_time,
                'favicon_src'          => !empty(Configuration::get('EPH_SOURCE_FAVICON')) ? $this->context->link->getBaseFrontLink() . 'img/' . Configuration::get('EPH_SOURCE_FAVICON') : $url_no_image,
                'type_patterns'        => $this->GetAllPattern(),
                'type_bgimages'        => $this->GetAllBGImage(),
                'color_group'          => self::$color_group,
                'color_group_name'     => $color_group_name,
                'color_group_name_sel' => $color_group_name_sel,
                'gets_fonts_family'    => $this->gets_fonts_family(),
                'gets_fonts_variants'  => $this->gets_fonts_variants('ABeeZee'),
                'gets_fonts_subsets'   => $this->gets_fonts_subsets('ABeeZee'),
                'xprt'                 => $xprt,
            ];
            $this->fields_value = [];
            $this->assignFormValue($this->fields_form);

            $html = parent::renderForm();
            $forms[$fields_form['legend']['title']] = [
                'key'     => $fields_form['id_form'],
                'content' => $html,
            ];

        }

        $data = $this->createTemplate('controllers/themes/site_seetings.tpl');

        $data->assign([
            'forms' => $forms,

        ]);

        return $data->fetch();
    }

    public function generateTabTheme() {

        $data = $this->createTemplate('controllers/themes/theme_tabs.tpl');
        $cur_theme = Theme::getThemeInfo($this->context->shop->id_theme);
        $theme = new Theme($this->context->shop->id_theme);
        $xprt_demo_number = Configuration::get("xprt_demo_number");
        $all_demo = [];
        $all_demo_link = [];
        $iterator = new AppendIterator();
        $iterator->append(new DirectoryIterator(_PS_EPH_THEME_DIR_ . 'demo'));

        foreach ($iterator as $file) {
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if ($ext != 'xml') {
                continue;
            }

            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $all_demo[$file->getFilename()] = $filename;
            $filename = str_replace(' ', '', $filename);
            $all_demo_link[$filename] = 'themes' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . $file->getFilename();
        }

        $data->assign([

            'controller'             => 'AdminThemes',
            'theme_all_demo'         => $all_demo,
            'all_demo_link'          => $all_demo_link,
            'xprt_demo_number'       => $xprt_demo_number,
            'link'                   => $this->context->link,
            'cur_theme'              => $cur_theme,
            'theme'                  => $theme,
            'EPH_HOME_SLIDER_ACTIVE' => Configuration::get('EPH_HOME_SLIDER_ACTIVE'),
            '_THEME_IMG_DIR_'        => _THEME_IMG_DIR_,

        ]);

        return $data->fetch();
    }

    public function renderPolygon() {

        $fields_forms['polygon_param'] = [

            'legend'  => [
                'title' => $this->l('Champs d‘application'),
            ],
            'id_form' => 'theme_polygon_param',
            'input'   => [
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Front Office Public'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone au Menu'),
                    'name'        => 'polygon_menu',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'polygon_menu_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'polygon_menu_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone au Corps'),
                    'name'        => 'polygon_core',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'polygon_core_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'polygon_core_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone sur Parallax'),
                    'name'        => 'polygon_parallax',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'polygon_parallax_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'polygon_parallax_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone au Footer'),
                    'name'        => 'polygon_footer',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'polygon_footer_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'polygon_footer_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'       => 'infoheading',
                    'label'      => $this->l('Espace CEF'),
                    'name'       => 'infoheading',
                    'colorclass' => 'info_custom success',
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone au Menu'),
                    'name'        => 'cef_polygon_menu',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'cef_polygon_menu_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'cef_polygon_menu_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Appliquer la classe Polygone au Corps'),
                    'name'        => 'cef_polygon_core',
                    'class'       => 't',
                    'is_bool'     => true,
                    'default_val' => '1',
                    'values'      => [
                        [
                            'id'      => 'cef_polygon_core_on',
                            'value'   => 1,
                            'label_1' => $this->l('Yes'),
                        ],
                        [
                            'id'      => 'cef_polygon_core_off',
                            'value'   => 0,
                            'label_0' => $this->l('No'),
                        ],
                    ],
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['polygon_color'] = [
            'legend'  => [
                'title' => $this->l('Appliquer un effet Polygone dans le menu'),
            ],
            'id_form' => 'theme_polygon_colors',
            'input'   => [
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 1'),
                    'name'        => 'polygon_menu_color_1',
                    'default_val' => '#56589a',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 2'),
                    'name'        => 'polygon_menu_color_2',
                    'default_val' => '#9ea3cf',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 3'),
                    'name'        => 'polygon_menu_color_3',
                    'default_val' => '#838bc3',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 4'),
                    'name'        => 'polygon_menu_color_4',
                    'default_val' => '#6c75b1',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 5'),
                    'name'        => 'polygon_menu_color_5',
                    'default_val' => '#4f589b',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 6'),
                    'name'        => 'polygon_menu_color_6',
                    'default_val' => '#3b4489',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 7'),
                    'name'        => 'polygon_menu_color_7',
                    'default_val' => '#282f6c',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 8'),
                    'name'        => 'polygon_menu_color_8',
                    'default_val' => '#0f1039',
                ],
                [
                    'type'        => 'color',
                    'label'       => $this->l('Couleur Polygone 9'),
                    'name'        => 'polygon_menu_color_9',
                    'default_val' => '#ff7e00',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        foreach ($fields_forms as $key => $fields_form) {

            $this->fields_form = $fields_form;

            $this->fields_value = [];
            $this->assignFormValue($this->fields_form);

            $html = parent::renderForm();
            $forms[$fields_form['legend']['title']] = [
                'key'     => $fields_form['id_form'],
                'content' => $html,
            ];

        }

        $data = $this->createTemplate('controllers/themes/form_polygons.tpl');

        $data->assign([
            'forms' => $forms,
        ]);

        return $data->fetch();
    }

    public function renderGeneralSeetingsForm() {

        $forms = [];
        $fields_forms['logos'] = [

            'legend'  => [
                'title' => $this->l('Mes logos et Images'),
            ],
            'id_form' => 'theme_images',
            'input'   => [
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Le logo du site'),
                    'name'        => 'PS_LOGO',
                    'default_val' => '',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Logo mobile du site'),
                    'name'        => 'PS_LOGO_MOBILE',
                    'default_val' => '',
                ],

                [
                    'type'     => 'radio',
                    'label'    => $this->l('Position Du Logo'),
                    'name'     => 'logo_position',
                    'required' => true,
                    'values'   => [
                        [
                            'id'    => 'left',
                            'value' => 'left',
                            'label' => $this->l('A Gauche'),
                        ],
                        [
                            'id'    => 'middle',
                            'value' => 'middle',
                            'label' => $this->l('Au centre'),
                        ],
                    ],
                ],

                [
                    'type'        => 'margin',
                    'label'       => $this->l('Espacement extérieur du Logo - margin (px)'),
                    'name'        => 'logo_margin',
                    'default_val' => '0 0 0 0',
                ],
                [
                    'type'  => 'height',
                    'label' => $this->l('Hauteur du Logo'),
                    'desc'  => $this->l('Laisser vide pour auto'),
                    'name'  => 'logo_height',
                ],
                [
                    'type'  => 'larger',
                    'label' => $this->l('Largeur du Logo'),
                    'desc'  => $this->l('Laisser vide pour auto'),
                    'name'  => 'logo_width',
                ],
                [
                    'type'  => 'background_position',
                    'label' => $this->l('Position du BackGround du Logo'),
                    'name'  => 'logo_bg_position',

                ],
                [
                    'type'  => 'background_size',
                    'label' => $this->l('Taille du Background du Logo'),
                    'name'  => 'logo_bck_size',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Le logo des Factures'),
                    'name'        => 'PS_LOGO_INVOICE',
                    'default_val' => '',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Le Tampon de la société'),
                    'name'        => 'EPH_SOURCE_STAMP',
                    'default_val' => '',
                ],
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Le logo des réseaux sociaux'),
                    'name'        => 'EPH_OGGPIC',
                    'default_val' => '',
                ],
                [
                    'type'        => 'favicon',
                    'label'       => $this->l('Le Favicon'),
                    'name'        => 'PS_FAVICON',
                    'default_val' => '',
                ],

                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Logo Qualiopi'),
                    'name'        => 'logo_qualiopi',
                    'default_val' => '',
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];
        $fields_forms['mailseetings'] = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Réglage des Emails'),
            ],
            'id_form' => 'email_seetings',
            'input'   => [
                [
                    'type'        => 'img_upload',
                    'label'       => $this->l('Le logo des emails'),
                    'name'        => 'PS_LOGO_MAIL',
                    'default_val' => '',
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Tags Footer Pour les Emails'),
                    'name'         => 'EPH_FOOTER_EMAIL',
                    'autoload_rte' => true,
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        foreach ($fields_forms as $key => $fields_form) {

            $this->fields_form = $fields_form;

            $rand_time = time();
            $image_path = _PS_EPH_THEME_DIR_ . $rand_time . '_img.jpg';
            $image_url = _EPH_THEME_DIR . $rand_time . '_img.jpg';
            $color_group_name = Configuration::get(self::$shortname . "color_group_name");
            $color_group_name_sel = Configuration::get(self::$shortname . "color_group_name_sel");
            $theme_dir = Context::getContext()->shop->theme_directory;
            $url_no_image = $this->context->link->getBaseFrontLink() . 'img/fr.jpg';

            $this->tpl_form_vars = [
                'type_image_path'      => $image_path,
                'type_image_url'       => $image_url,
                'type_rand_time'       => $rand_time,
                'favicon_src'          => !empty(Configuration::get('EPH_SOURCE_FAVICON')) ? $this->context->link->getBaseFrontLink() . 'img/' . Configuration::get('EPH_SOURCE_FAVICON') : $url_no_image,
                'type_patterns'        => $this->GetAllPattern(),
                'type_bgimages'        => $this->GetAllBGImage(),
                'color_group'          => self::$color_group,
                'color_group_name'     => $color_group_name,
                'color_group_name_sel' => $color_group_name_sel,

            ];
            $this->fields_value = [];
            $this->assignFormValue($this->fields_form);

            $html = parent::renderForm();
            $forms[$fields_form['legend']['title']] = [
                'key'     => $fields_form['id_form'],
                'content' => $html,
            ];

        }

        $data = $this->createTemplate('controllers/themes/form_seetings.tpl');

        $data->assign([
            'forms' => $forms,

        ]);

        return $data->fetch();
    }

    public function renderCustomCssForm() {

        $forms = [];
        $fields_forms['cssCorp'] = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Custom CSS/JS'),
            ],
            'id_form' => 'theme_custom_css',
            'input'   => [

                [
                    'type'        => 'customtextarea',
                    'label'       => $this->l('Custom CSS'),
                    'name'        => 'custom_css',
                    'desc'        => $this->l('Please Enter Your Custom CSS'),
                    'rows'        => 30,
                    'cols'        => 25,
                    'mode'        => 'css',
                    'class'       => "custom_css_class",
                    'default_val' => '',
                ],
                [
                    'type'        => 'customtextarea',
                    'label'       => $this->l('Custom JS'),
                    'name'        => 'custom_js',
                    'class'       => "custom_js_class",
                    'mode'        => 'javascript',
                    'desc'        => $this->l('Please Enter Your Custom JS'),
                    'rows'        => 30,
                    'cols'        => 25,
                    'default_val' => '',
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        $fields_forms['cssMenu'] = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('JS/CSS menu Front Office'),
            ],
            'id_form' => 'menu_custom_css',
            'input'   => [

                [
                    'type'        => 'customtextarea',
                    'label'       => $this->l('CSS personnalisé du menu Front Office'),
                    'name'        => 'custom_menu_css',
                    'desc'        => $this->l('Please Enter Your Custom CSS'),
                    'rows'        => 30,
                    'cols'        => 25,
                    'mode'        => 'css',
                    'class'       => "custom_css_class",
                    'default_val' => '',
                ],
                [
                    'type'        => 'customtextarea',
                    'label'       => $this->l('JS personnalisé du menu Front Office'),
                    'name'        => 'custom_menu_js',
                    'class'       => "custom_js_class",
                    'mode'        => 'javascript',
                    'desc'        => $this->l('Please Enter Your Custom JS'),
                    'rows'        => 30,
                    'cols'        => 25,
                    'default_val' => '',
                ],
            ],
            'submit'  => [
                'title' => $this->l('Save'),
                'class' => 'button',
            ],
        ];

        foreach ($fields_forms as $key => $fields_form) {

            $this->fields_form = $fields_form;

            $rand_time = time();
            $image_path = _PS_EPH_THEME_DIR_ . $rand_time . '_img.jpg';
            $image_url = _EPH_THEME_DIR . $rand_time . '_img.jpg';
            $color_group_name = Configuration::get(self::$shortname . "color_group_name");
            $color_group_name_sel = Configuration::get(self::$shortname . "color_group_name_sel");
            $theme_dir = Context::getContext()->shop->theme_directory;

            $this->tpl_form_vars = [
                'type_image_path'      => $image_path,
                'type_image_url'       => $image_url,
                'type_rand_time'       => $rand_time,
                'type_patterns'        => $this->GetAllPattern(),
                'type_bgimages'        => $this->GetAllBGImage(),
                'color_group'          => self::$color_group,
                'color_group_name'     => $color_group_name,
                'color_group_name_sel' => $color_group_name_sel,
            ];

            $this->fields_value = [];
            $this->assignFormValue($this->fields_form);

            $html = parent::renderForm();
            $forms[$fields_form['legend']['title']] = [
                'key'     => $fields_form['id_form'],
                'content' => $html,
            ];

        }

        $data = $this->createTemplate('controllers/themes/menu.tpl');

        $data->assign([
            'forms' => $forms,

        ]);

        return $data->fetch();
    }

    public function assignFormValue($fields_form) {

        foreach ($fields_form['input'] as $mvalue) {

            if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {
                $languages = Language::getLanguages(false);

                foreach ($languages as $lang) {
                    $this->fields_value[$mvalue['name']][$lang['id_lang']] = Configuration::get('xprt' . $mvalue['name'], $lang['id_lang']);
                }

            } else {

                if (isset($mvalue['name'])) {

                    if (isset($mvalue['type']) && ($mvalue['type'] == "color")) {

                        $fields_value = !is_null(Configuration::get('xprt' . $mvalue['name'])) ? Configuration::get('xprt' . $mvalue['name']) : '';

                        if (!empty($fields_value)) {
                            $fields_value = $this->timberpress_rgb_to_hex($fields_value);
                        }

                        $this->fields_value[$mvalue['name']] = $fields_value;

                    } else {
                        $this->fields_value[$mvalue['name']] = !is_null(Configuration::get('xprt' . $mvalue['name'])) ? Configuration::get('xprt' . $mvalue['name']) : '';
                    }

                }

            }

        }

        $context = Context::getContext();
        $url_no_image = $context->link->getBaseFrontLink() . 'img/fr.jpg';
        $this->fields_value['PS_LOGO'] = !empty(Configuration::get('PS_LOGO')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO') : $url_no_image;

        $this->fields_value['PS_LOGO_MOBILE'] = !empty(Configuration::get('PS_LOGO_MOBILE')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO_MOBILE') : $url_no_image;

        $this->fields_value['EPH_OGGPIC'] = !empty(Configuration::get('EPH_OGGPIC')) ? $context->link->getBaseFrontLink() . 'oggpic/' . Configuration::get('EPH_OGGPIC') : $url_no_image;

        $this->fields_value['PS_LOGO_MAIL'] = !empty(Configuration::get('PS_LOGO_MAIL')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO_MAIL') : $url_no_image;

        $this->fields_value['PS_LOGO_INVOICE'] = !empty(Configuration::get('PS_LOGO_INVOICE')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO_INVOICE') : $url_no_image;

        $this->fields_value['EPH_SOURCE_STAMP'] = !empty(Configuration::get('EPH_SOURCE_STAMP')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('EPH_SOURCE_STAMP') : $url_no_image;

        $this->fields_value['PS_FAVICON'] = !empty(Configuration::get('PS_FAVICON')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_FAVICON') : $url_no_image;

        $this->fields_value['EPH_FOOTER_EMAIL'] = !empty(Configuration::get('EPH_FOOTER_EMAIL')) ? Configuration::get('EPH_FOOTER_EMAIL') : '';

        $this->fields_value['EPH_POLYGON_MODE'] = !empty(Configuration::get('EPH_POLYGON_MODE')) ? Configuration::get('EPH_POLYGON_MODE') : '';

        $this->fields_value['logo_qualiopi'] = !empty(Configuration::get('xprtlogo_qualiopi')) ? Configuration::get('xprtlogo_qualiopi') : $url_no_image;
        $this->fields_value['EPH_POLYGON_ACTIVE'] = !empty(Configuration::get('EPH_POLYGON_ACTIVE')) ? Configuration::get('EPH_POLYGON_ACTIVE') : 1;
        $this->tpl_form_vars['EPH_POLYGON_ACTIVE'] = $this->fields_value['EPH_POLYGON_ACTIVE'];
        $this->tpl_form_vars['EPH_HOME_SLIDER_ACTIVE'] = Configuration::get('EPH_HOME_SLIDER_ACTIVE');
        $this->tpl_form_vars['EPH_HOME_VIDEO_ACTIVE'] = Configuration::get('EPH_HOME_VIDEO_ACTIVE');
        $this->tpl_form_vars['EPH_HOME_PARALLAX_ACTIVE'] = Configuration::get('EPH_HOME_PARALLAX_ACTIVE');

        if ($this->assigngFontsValues()) {

            foreach ($this->assigngFontsValues() as $key => $value) {
                $this->tpl_form_vars[$key] = $value;
            }

        }

    }

    public function assigngFontsValues() {

        $multiple_arr = [];
        $return_arr = [];

        $file = fopen("testassigngFontsValues.txt", "w");

        $multiple_arr = array_merge($multiple_arr, $this->fields_form['input']);

        if (isset($multiple_arr) && !empty($multiple_arr)) {

            foreach ($multiple_arr as $mvalue) {

                if (isset($mvalue['type']) && $mvalue['type'] == "googlefont") {
                    $return_arr[$mvalue['name'] . "_family"] = Configuration::get($mvalue['name'] . "_family");
                    $font_variants = Configuration::get($mvalue['name'] . "_variants");

                    if (isset($font_variants)) {
                        $return_arr[$mvalue['name'] . "_variants"] = explode(",", $font_variants);
                    } else {
                        $return_arr[$mvalue['name'] . "_variants"] = "";
                    }

                    $font_subsets = Configuration::get($mvalue['name'] . "_subsets");

                    if (isset($font_subsets)) {
                        $return_arr[$mvalue['name'] . "_subsets"] = explode(",", $font_subsets);
                    } else {
                        $return_arr[$mvalue['name'] . "_subsets"] = "";
                    }

                }

            }

        }

        fwrite($file, print_r($return_arr, true));
        return $return_arr;
    }

    public function GetAllBGImage() {

        $theme_dir = $this->context->shop->theme_directory;
        $pattern_path = _PS_EPH_THEME_DIR_;
        $pattern_images_url = Context::getContext()->shop->getBaseURL() . 'img/theme/';
        $pattern_images = [];

        if (is_dir($pattern_path)) {

            if ($pattern_images_dir = opendir($pattern_path)) {

                while (($pattern_images_file = readdir($pattern_images_dir)) !== false) {

                    if (stristr($pattern_images_file, ".png") !== false || stristr($pattern_images_file, ".jpg") !== false) {
                        $pattern_images[] = $pattern_images_file;
                    }

                }

            }

        }

        return $pattern_images;
    }

    public function GetAllPattern() {

        $theme_dir = $this->context->shop->theme_directory;
        $pattern_path = _PS_THEME_DIR_ . 'img/patterns/';

        $pattern_images_url = Context::getContext()->shop->getBaseURL() . 'themes/' . $theme_dir . '/img/patterns/';
        $pattern_images = [];

        if (is_dir($pattern_path)) {

            if ($pattern_images_dir = opendir($pattern_path)) {

                while (($pattern_images_file = readdir($pattern_images_dir)) !== false) {

                    if (stristr($pattern_images_file, ".png") !== false || stristr($pattern_images_file, ".jpg") !== false) {
                        $pattern_images[] = $pattern_images_file;
                    }

                }

            }

        }

        return $pattern_images;
    }

    public function AllFields() {

        include_once _EPH_THEME_DIR . 'fields_array.php';
        return $this->fields_form;
    }

    public function setAjaxMedia() {

        return $this->pushJS([_PS_JS_DIR_ . 'themes.js', _PS_JS_DIR_ . 'tinymce/tinymce.min.js', _PS_JS_DIR_ . 'tinymce.inc.js', _PS_JS_DIR_ . 'colorpicker/jquery.colorpicker.js', _PS_JS_DIR_ . 'colorpicker/i18n/jquery.ui.colorpicker-fr.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-pantone.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-crayola.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-ral-classic.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-x11.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-copic.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-prismacolor.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-isccnbs.js', _PS_JS_DIR_ . 'colorpicker/swatches/jquery.ui.colorpicker-din6164.js', _PS_JS_DIR_ . 'colorpicker/parts/jquery.ui.colorpicker-rgbslider.js', _PS_JS_DIR_ . 'colorpicker/parts/jquery.ui.colorpicker-memory.js', _PS_JS_DIR_ . 'colorpicker/parts/jquery.ui.colorpicker-swatchesswitcher.js', _PS_JS_DIR_ . 'colorpicker/parsers/jquery.ui.colorpicker-cmyk-parser.js', _PS_JS_DIR_ . 'colorpicker/parsers/jquery.ui.colorpicker-cmyk-percentage-parser.js', _PS_JS_DIR_ . 'vendor/spin.js', _PS_JS_DIR_ . 'vendor/ladda.js', _PS_JS_DIR_ . 'pdfuploadify.min.js',
            'https://cdn.ephenyxapi.com/ace/ace.js',
        ]);
    }

    public function updateThemesDocumnents() {

        $pdfUploader = new HelperUploader('reglementUrl');

        $files = $pdfUploader->process();

        if (is_array($files) && count($files)) {

            foreach ($files as $image) {
                $destinationFile = _PS_IMG_DIR_ . 'reglement.pdf';
                copy($image['save_path'], $destinationFile);
            }

        }

        $pdfUploader = new HelperUploader('deontologieUrl');

        $files = $pdfUploader->process();

        if (is_array($files) && count($files)) {

            foreach ($files as $image) {
                $destinationFile = _PS_IMG_DIR_ . 'deontologie.pdf';
                copy($image['save_path'], $destinationFile);
            }

        }

        $pdfUploader = new HelperUploader('catalogueUrl');

        $files = $pdfUploader->process();

        if (is_array($files) && count($files)) {

            foreach ($files as $image) {
                $destinationFile = _PS_CATALOGUE_DIR_ . 'catalogue.pdf';
                copy($image['save_path'], $destinationFile);
            }

        }

    }

    public function AsignGlobalSettingValue() {

        $id_lang = Context::getcontext()->language->id;
        $multiple_arr = [];
        $xprt = [];
        $theme_dir = Context::getcontext()->shop->theme_directory;
        $mod_name = $this->name;
        $xprt['xprtpatternsurl'] = Context::getContext()->shop->getBaseURL() . _EPH_THEMES_DIR_ . $theme_dir . '/img/patterns/';
        $xprt['xprtimageurl'] = Context::getContext()->shop->getBaseURL() . 'img/theme/';
        $file = fopen("testAsignGlobalSettingValue.txt", "w");

        $field_menu = Tools::jsonDecode(Configuration::get('EPH_EXPERT_MENU_FIELDS'), true);

        if (is_array($field_menu)) {
            $fields_form['input'] = array_merge(
                $this->fieldForm['input'],
                $field_menu['input']
            );
        } else {
            $fields_form['input'] = $this->fieldForm['input'];
        }

        foreach ($fields_form['input'] as $mvalue) {

            if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {
                $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name'], $id_lang);
            } else {

                if (isset($mvalue['name'])) {

                    if (isset($mvalue['type']) && ($mvalue['type'] == "gradient")) {
                        $value = Configuration::get('xprt' . $mvalue['name']);
                        $keys = explode('-', $value);
                        $key = $keys[0];

                        if (!empty($keys[1])) {
                            $xprt[$mvalue['name']] = 'linear-gradient(to bottom, ' . $keys[0] . ' 0%, ' . $keys[1] . ' ' . $keys[2] . $keys[3] . ')';
                        } else {

                            $xprt[$mvalue['name']] = $keys[0];
                        }

                    } else

                    if (isset($mvalue['type']) && ($mvalue['type'] == "element_before") || ($mvalue['type'] == "element_after")) {
                        $result = [];
                        $fields_value = Tools::jsonDecode(stripslashes(Configuration::get('xprt' . $mvalue['name'])));

                        $result['content'] = '" "';
                        $result['display'] = 'block';
                        $result['position'] = 'absolute';
                        $result['z-index'] = '-1';

                        if ($fields_value->height > 0) {
                            $result['height'] = $fields_value->height . 'px';
                        }

                        if ($fields_value->position[0] != '') {
                            $result['top'] = $fields_value->position[0];
                        }

                        if ($fields_value->position[1] != '') {
                            $result['right'] = $fields_value->position[1];
                        }

                        if ($fields_value->position[2] != '') {
                            $result['bottom'] = $fields_value->position[2];
                        }

                        if ($fields_value->position[3] != '') {
                            $result['left'] = $fields_value->position[3];
                        }

                        if ($fields_value->bck_type == 'bck') {
                            $result['background'] = $fields_value->bck_color;
                        } else
                        if ($fields_value->bck_type == 'gradient') {
                            $keys = explode('-', $fields_value->bck_value);
                            $result['background-image'] = 'linear-gradient(to bottom, ' . $keys[0] . ' 0%, ' . $keys[1] . '  ' . $keys[2] . $keys[3] . ')';
                        } else {
                            $result['border'] = $fields_value->border_size . ' solid ' . $fields_value->border_color;
                            $keys = explode(' ', $fields_value->radius);
                            fwrite($file, print_r($keys, true) . PHP_EOL);
                            $top = isset($keys[0]) ? $keys[0] : '';
                            $right = isset($keys[1]) ? $keys[1] : '';
                            $bottom = isset($keys[2]) ? $keys[2] : '';
                            $left = isset($keys[3]) ? $keys[3] : '';

                            if (count(array_unique($keys)) === 1) {
                                $result['border-radius'] = $top;
                            } else
                            if ($top == $bottom && $right == $left) {
                                $result['border-radius'] = $top . ' ' . $right;
                            } else {
                                $result['border-radius'] = $fields_value->radius;
                            }

                        }

                        fwrite($file, print_r($result, true) . PHP_EOL);
                        $xprt[$mvalue['name']] = $result;

                    } else

                    if (isset($mvalue['type']) && ($mvalue['type'] == "contener_position")) {
                        $result = [];
                        $fields_value = Tools::jsonDecode(stripslashes(Configuration::get('xprt' . $mvalue['name'])));
                        $result['position'] = $fields_value->position;

                        if ($fields_value->position == 'absolute' || $fields_value->position == 'fixed') {

                            if ($fields_value->positions[0] != '') {
                                $result['top'] = $fields_value->positions[0];
                            }

                            if ($fields_value->positions[1] != '') {
                                $result['right'] = $fields_value->positions[1];
                            }

                            if ($fields_value->positions[2] != '') {
                                $result['bottom'] = $fields_value->positions[2];
                            }

                            if ($fields_value->positions[3] != '') {
                                $result['left'] = $fields_value->positions[3];
                            }

                        }

                        $xprt[$mvalue['name']] = $result;

                    } else

                    if (isset($mvalue['type']) && ($mvalue['type'] == "contener_border")) {
                        $result = [];
                        $fields_value = Tools::jsonDecode(stripslashes(Configuration::get('xprt' . $mvalue['name'])));

                        if ($fields_value->type != 'none') {
                            $result[$fields_value->type] = $fields_value->size . ' ' . $fields_value->style . ' ' . $fields_value->color;
                            $xprt[$mvalue['name']] = $result;
                        }

                    } else

                    if (isset($mvalue['type']) && ($mvalue['type'] == "4size")) {

                        $values = Configuration::get('xprt' . $mvalue['name']);

                        $keys = explode(' ', $values);
                        $top = isset($keys[0]) ? $keys[0] : '';
                        $right = isset($keys[1]) ? $keys[1] : '';
                        $bottom = isset($keys[2]) ? $keys[2] : '';
                        $left = isset($keys[3]) ? $keys[3] : '';

                        if (count(array_unique($keys)) === 1) {
                            $xprt[$mvalue['name']] = $top;
                        } else
                        if ($top == $bottom && $right == $left) {
                            $xprt[$mvalue['name']] = $top . ' ' . $right;
                        } else {
                            $xprt[$mvalue['name']] = $values;
                        }

                    } else {
                        $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name']);
                    }

                }

            }

        }

        $gfonts = Tools::singleFontsUrl();

        if ($gfonts) {
            Context::getcontext()->smarty->assignGlobal('xprtgfontslink', $gfonts);
        }

        return $xprt;
    }

    public function ajaxProcessGenereteTheme() {

        if ($theme_name = Tools::getValue('theme_name')) {
            $file = fopen(_PS_EPH_THEME_DIR_ . 'demo/' . $theme_name . ".xml", "w");

            $themeToExport = new Theme(1);
            $metas = $themeToExport->getMetas();
            $this->generateXML($themeToExport, $theme_name);

        }

        fwrite($file, $this->xml_file);
        $xmlDocument = new DOMDocument('1.0', 'utf-8');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->recover = true;
        libxml_use_internal_errors(true);
        try {
            $result = $xmlDocument->load(_PS_EPH_THEME_DIR_ . 'demo/' . $theme_name . ".xml");
        } catch (Exception $e) {

        }

        if ($result) {

            $xmlDocument->save(_PS_EPH_THEME_DIR_ . 'demo/' . $theme_name . '.xml');
        }

        $return = [
            'success' => true,
            'message' => 'Le fichier ' . $fileName . ' a été crée avec succès',
        ];
        die(Tools::jsonEncode($return));
    }

    public function generateXML($themeToExport, $theme_name) {

        $xprt = [];
        $fonts = [];
        $field_menu = Tools::jsonDecode(Configuration::get('EPH_EXPERT_MENU_FIELDS'), true);

        $fields_form['input'] = array_merge(
            $this->fieldForm['input'],
            $field_menu['input']
        );

        if (is_array($fields_form) && count($fields_form)) {

            foreach ($fields_form['input'] as $mvalue) {

                if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {
                    $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name'], $id_lang);
                } else {

                    if (isset($mvalue['name'])) {

                        if (isset($mvalue['type']) && ($mvalue['type'] == "googlefont")) {
                            $font = Configuration::get($mvalue['name'] . '_family');

                            if ($font == 'inherit') {
                                continue;
                            }

                            $fonts[] = $mvalue['name'];
                        }

                        $xprt[$mvalue['name']] = Configuration::get('xprt' . $mvalue['name']);
                    }

                }

            }

        }

        $theme = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><theme></theme>');
        $theme->addAttribute('version', '2.0.5');
        $theme->addAttribute('name', 'phenyx-theme-default');
        $theme->addAttribute('directory', 'phenyx-theme-default');
        $author = $theme->addChild('author');
        $author->addAttribute('name', 'EphenyxShop');
        $author->addAttribute('email', 'jeff@ephenyx.com');
        $author->addAttribute('url', 'https://ephrnyx.com');

        $descriptions = $theme->addChild('descriptions');
        $languages = Language::getLanguages();

        foreach ($languages as $language) {
            $val = 'Phenyx theme est un theme multi configurable.';
            $description = $descriptions->addChild('description');
            $description->addAttribute('description', Tools::htmlentitiesUTF8($val));
            $description->addAttribute('iso', $language['iso_code']);
        }

        $variations = $theme->addChild('variations');

        $variation = $variations->addChild('variation');
        $variation->addAttribute('name', 'phenyx-theme-default');
        $variation->addAttribute('directory', 'phenyx-theme-default');
        $variation->addAttribute('responsive', $themeToExport->responsive);
        $variation->addAttribute('default_left_column', $themeToExport->default_left_column);
        $variation->addAttribute('default_right_column', $themeToExport->default_right_column);
        $variation->addAttribute('product_per_page', $themeToExport->product_per_page);
        $variation->addAttribute('from', _EPH_VERSION_);
        $variation->addAttribute('to', _EPH_VERSION_);

        $xprtfields = $theme->addChild('xprtfields');

        foreach ($xprt as $key => $value) {
            $xprtfield = $xprtfields->addChild('xprtfield');
            $xprtfield->addAttribute('name', $key);
            $xprtfield->addAttribute('value', Tools::htmlentitiesUTF8($value));
        }

        $fontsfields = $theme->addChild('fontfields');

        foreach ($fonts as $key => $value) {
            $xprtfield = $xprtfields->addChild('fontfield');
            $xprtfield->addAttribute('name', $key);
            $xprtfield->addAttribute('value', Tools::htmlentitiesUTF8($value));
        }

        $this->xml_file = $theme->asXML();

    }

    public function processExportTheme() {

        if (Tools::isSubmit('name')) {

            if ($this->checkPostedDatas()) {
                $filename = Tools::htmlentitiesUTF8($_FILES['documentation']['name']);
                $name = Tools::htmlentitiesUTF8(Tools::getValue('documentationName'));
                $this->user_doc = [$name . '¤doc/' . $filename];

                $table = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`name`, `width`, `products`, `categories`, `manufacturers`, `suppliers`, `scenes`')
                        ->from('image_type')
                );

                $this->image_list = [];

                foreach ($table as $row) {
                    $this->image_list[] = $row['name'] . ';' . $row['width'] . ';' . $row['height'] . ';' .
                        ($row['products'] == 1 ? 'true' : 'false') . ';' .
                        ($row['categories'] == 1 ? 'true' : 'false') . ';' .
                        ($row['manufacturers'] == 1 ? 'true' : 'false') . ';' .
                        ($row['suppliers'] == 1 ? 'true' : 'false') . ';' .
                        ($row['scenes'] == 1 ? 'true' : 'false');
                }

                $idShop = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                        ->select('`id_shop`')
                        ->from('shop')
                        ->where('`id_theme` = ' . (int) Tools::getValue('id_theme_export'))
                );

                // Select the list of module for this shop
                $this->module_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('m.`id_module`, m.`name`, m.`active`, ms.`id_shop`')
                        ->from('module', 'm')
                        ->leftJoin('module_shop', 'ms', 'm.`id_module` = ms.`id_module`')
                        ->where('ms.`id_shop` = ' . (int) $idShop)
                );

                // Select the list of hook for this shop
                $this->hook_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('h.`id_hook`, h.`name` AS `name_hook`, hm.`position`, hm.`id_module`, m.`name` AS `name_module`, GROUP_CONCAT(hme.`file_name`, ",") AS `exceptions`')
                        ->from('hook', 'h')
                        ->leftJoin('hook_module', 'hm', 'hm.`id_hook` = h.`id_hook`')
                        ->leftJoin('module', 'm', 'hm.`id_module` = m.`id_module`')
                        ->leftOuterJoin('hook_module_exceptions', 'hme', 'hme.`id_module` = hm.`id_module` AND hme.`id_hook` = h.`id_hook`')
                        ->where('hm.`id_shop` = ' . (int) $idShop)
                        ->groupBy('hm.`id_module`, h.`id_hook`')
                        ->orderBy('name_module')
                );

                $this->native_modules = $this->getNativeModule();

                foreach ($this->hook_list as &$row) {
                    $row['exceptions'] = trim(preg_replace('/(,,+)/', ',', $row['exceptions']), ',');
                }

                $this->to_install = [];
                $this->to_enable = [];
                $this->to_hook = [];

                foreach ($this->module_list as $array) {

                    if (!static::checkParentClass($array['name'])) {
                        continue;
                    }

                    if (in_array($array['name'], $this->native_modules)) {

                        if ($array['active'] == 1) {
                            $this->to_enable[] = $array['name'];
                        } else {
                            $this->to_disable[] = $array['name'];
                        }

                    } else

                    if ($array['active'] == 1) {
                        $this->to_install[] = $array['name'];
                    }

                }

                foreach ($this->native_modules as $str) {
                    $flag = 0;

                    if (!static::checkParentClass($str)) {
                        continue;
                    }

                    foreach ($this->module_list as $tmp) {

                        if (in_array($str, $tmp)) {
                            $flag = 1;
                            break;
                        }

                    }

                    if ($flag == 0) {
                        $this->to_disable[] = $str;
                    }

                }

                foreach ($_POST as $key => $value) {

                    if (strncmp($key, 'modulesToExport_module', strlen('modulesToExport_module')) == 0) {
                        $this->to_export[] = $value;
                    }

                }

                if ($this->to_install) {

                    foreach ($this->to_install as $string) {

                        foreach ($this->hook_list as $tmp) {

                            if ($tmp['name_module'] == $string) {
                                $this->to_hook[] = $string . ';' . $tmp['name_hook'] . ';' . $tmp['position'] . ';' . $tmp['exceptions'];
                            }

                        }

                    }

                }

                if ($this->to_enable) {

                    foreach ($this->to_enable as $string) {

                        foreach ($this->hook_list as $tmp) {

                            if ($tmp['name_module'] == $string) {
                                $this->to_hook[] = $string . ';' . $tmp['name_hook'] . ';' . $tmp['position'] . ';' . $tmp['exceptions'];
                            }

                        }

                    }

                }

                $themeToExport = new Theme((int) Tools::getValue('id_theme_export'));
                $metas = $themeToExport->getMetas();

                $this->generateXML($themeToExport, $metas);
                $this->generateArchive();
            } else {
                $this->display = 'exporttheme';
            }

        } else {
            $this->display = 'exporttheme';
        }

    }

    private function getModules($xml) {

        $native_modules = $this->getNativeModule();
        $theme_module = [];
        $theme_module['to_install'] = [];
        $theme_module['to_enable'] = [];
        $theme_module['to_disable'] = [];

        foreach ($xml->modules->module as $row) {

            if (strval($row['action']) == 'install' && !in_array(strval($row['name']), $native_modules)) {
                $theme_module['to_install'][] = strval($row['name']);
            } else

            if (strval($row['action']) == 'enable') {
                $theme_module['to_enable'][] = strval($row['name']);
            } else

            if (strval($row['action']) == 'disable') {
                $theme_module['to_disable'][] = strval($row['name']);
            }

        }

        return $theme_module;
    }

    public function updateCurrentLogoTheme() {

        $arrayImage = [
            'PS_LOGO'            => 'PS_LOGO',
            'PS_LOGO_MOBILE'     => 'PS_LOGO_MOBILE',
            'PS_LOGO_MAIL'       => 'PS_LOGO_MAIL',
            'PS_LOGO_INVOICE'    => 'PS_LOGO_INVOICE',
            'EPH_SOURCE_STAMP'   => 'EPH_SOURCE_STAMP',
            'EPH_SOURCE_FAVICON' => 'EPH_SOURCE_FAVICON',
            'PS_FAVICON'         => 'PS_FAVICON',
            'EPH_OGGPIC'         => 'EPH_OGGPIC',
        ];
        $file = fopen("testupdateCurrentLogoTheme.txt", "w");

        foreach ($arrayImage as $key => $type) {

            $imageUploader = new HelperImageUploader($key);
            $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {

                    if (empty($image['save_path'])) {
                        continue;
                    }

                    fwrite($file, print_r($image, true));

                    if ($key == 'PS_FAVICON') {
                        $destinationFile = _PS_FRONT_DIR_ . '/img/favicon.ico';
                        $this->uploadIco($image, $destinationFile);
                        $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
                        $destinationFile = _PS_FRONT_DIR_ . '/img/favicon_src.' . $ext;
                        $fileName = 'favicon_src.' . $ext;

                        if (copy($image['save_path'], $destinationFile)) {
                            Configuration::updateValue('EPH_SOURCE_FAVICON', $fileName);
                            $adminFile = _PS_ROOT_DIR_ . '/themes/img/favicon.ico';
                            copy($destinationFile, $adminFile);
                        }

                        continue;
                    }

                    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);

                    switch ($key) {
                    case 'PS_LOGO':
                        $destinationFile = _PS_FRONT_DIR_ . '/img/logoFrontOffice.' . $ext;
                        $fileName = 'logoFrontOffice.' . $ext;
                        break;
                    case 'PS_LOGO_MOBILE':
                        $destinationFile = _PS_FRONT_DIR_ . '/img/logoMobile.' . $ext;
                        $fileName = 'logoMobile.' . $ext;
                        break;
                    case 'PS_LOGO_MAIL':
                        $destinationFile = _PS_FRONT_DIR_ . '/img/logoMail.' . $ext;
                        $fileName = 'logoMail.' . $ext;
                        break;
                    case 'PS_LOGO_INVOICE':
                        $destinationFile = _PS_FRONT_DIR_ . '/img/logoInvoice.' . $ext;
                        $fileName = 'logoInvoice.' . $ext;
                        break;
                    case 'EPH_SOURCE_STAMP':
                        $destinationFile = _PS_FRONT_DIR_ . '/img/companyStamp.' . $ext;
                        $fileName = 'companyStamp.' . $ext;
                        break;
                    case 'EPH_OGGPIC':
                        $destinationFile = _PS_FRONT_DIR_ . '/oggpic/oggpic.' . $ext;
                        $fileName = 'oggpic.' . $ext;
                        break;
                    }

                    if (copy($image['save_path'], $destinationFile)) {
                        Configuration::updateValue($type, $fileName);
                    }

                }

            }

        }

    }

    public function updateCurrentLogoPictures() {

        $arrayImage = [
            'serviceAccesUrl'         => 'EPH_SERVICE_ACCES_LOGO',
            'serviceCertificationUrl' => 'EPH_SERVICE_CERTIFICATION_LOGO',
            'serviceCompetanceUrl'    => 'EPH_SERVICE_COMPETENCE_LOGO',
            'serviceOutilsUrl'        => 'EPH_SERVICE_OUTIL_LOGO',
            'imageCertification'      => 'EPH_CERTIFICATION_IMAGE',
        ];

        foreach ($arrayImage as $key => $type) {
            $imageUploader = new HelperImageUploader($key);
            $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
            $files = $imageUploader->process();

            if (is_array($files) && count($files)) {

                foreach ($files as $image) {
                    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);

                    switch ($key) {

                    case 'serviceAccesUrl':
                        $destinationFile = _PS_IMG_DIR_ . 'serviceAcces.' . $ext;
                        $fileName = 'serviceAcces.' . $ext;
                        break;
                    case 'serviceCertificationUrl':
                        $destinationFile = _PS_IMG_DIR_ . 'serviceCertification.' . $ext;
                        $fileName = 'serviceCertification.' . $ext;
                        break;
                    case 'serviceCompetanceUrl':
                        $destinationFile = _PS_IMG_DIR_ . 'serviceCompetance.' . $ext;
                        $fileName = 'serviceCompetance.' . $ext;
                        break;
                    case 'serviceOutilsUrl':
                        $destinationFile = _PS_IMG_DIR_ . 'serviceOutils.' . $ext;
                        $fileName = 'serviceOutils.' . $ext;
                        break;
                    case 'imageCertification':
                        $destinationFile = _PS_IMG_DIR_ . 'imageCertif.' . $ext;
                        $fileName = 'imageCertif.' . $ext;
                        break;
                    }

                    if (copy($image['save_path'], $destinationFile)) {
                        Configuration::updateValue($type, $fileName);
                    }

                }

            }

        }

        $result = [
            'success' => true,
            'message' => 'Les réglages du thème ont été mis à jour avec succès',
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessUpdateTheme() {

        $file = fopen("testProcessUpdateTheme.txt", "w");
        fwrite($file, $this->bootsrap_index . PHP_EOL);
        $this->updateCurrentLogoTheme();

        $this->updateThemesDocumnents();

        $bootsrap_index = Tools::getValue('bootsrap_index');

        if ($bootsrap_index != $this->bootsrap_index) {

            if (file_exists(_PS_ADMIN_DIR_ . '/template/front/index_' . $bootsrap_index . '.tpl')) {
                copy(_PS_ADMIN_DIR_ . '/template/front/index_' . $bootsrap_index . '.tpl', _PS_THEME_DIR_ . 'index.tpl');
            }

        }

        $bootsrap_education = Tools::getValue('bootsrap_education');

        if ($bootsrap_education != $this->bootsrap_education) {

            if (file_exists(_PS_ADMIN_DIR_ . '/template/front/education_' . $bootsrap_education . '.tpl')) {
                copy(_PS_ADMIN_DIR_ . '/template/front/education_' . $bootsrap_education . '.tpl', _PS_THEME_DIR_ . 'education.tpl');
            }

        }

        $this->saveTheme($this->fieldForm);

        Configuration::updateValue('EPH_POLYGON_ACTIVE', Tools::getValue('EPH_POLYGON_ACTIVE'));
        Configuration::updateValue('EPH_HOME_SLIDER_ACTIVE', Tools::getValue('EPH_HOME_SLIDER_ACTIVE'));
        Configuration::updateValue('EPH_HOME_VIDEO_ACTIVE', Tools::getValue('EPH_HOME_VIDEO_ACTIVE'));
        Configuration::updateValue('EPH_HOME_VIDEO_LINK', Tools::getValue('EPH_HOME_VIDEO_LINK'));
        Configuration::updateValue('EPH_HOME_PARALLAX_ACTIVE', Tools::getValue('EPH_HOME_PARALLAX_ACTIVE'));
        Configuration::updateValue('EPH_POLYGON_MODE', Tools::getValue('EPH_POLYGON_MODE'));

        Configuration::updateValue('EPH_FOOTER_EMAIL', Tools::getValue('EPH_FOOTER_EMAIL'), true);

        $return = [
            'success' => true,
            'message' => 'Le Thème Front Office a été été mis à jour avec succès',
        ];

        die(Tools::jsonEncode($return));

    }

    public function saveTheme($fields_form) {

        $file = fopen("testsaveTheme.txt", "w");

        foreach ($fields_form['input'] as $mvalue) {

            if (empty(Tools::getValue($mvalue['name']))) {
                continue;
            }

            if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {
                $languages = Language::getLanguages(false);

                foreach ($languages as $lang) {
                    ${$mvalue['name'] . '_lang'}

                    [$lang['id_lang']] = Tools::getvalue($mvalue['name'] . '_' . $lang['id_lang']);
                }

            }

        }

        foreach ($fields_form['input'] as $mvalue) {

            if (isset($mvalue['type']) && ($mvalue['type'] == "googlefont")) {
                $this->SaveGoogleFonts($mvalue['name']);

            } else

            if (isset($mvalue['type']) && $mvalue['type'] == "4size") {
                $result = '';
                $values = Tools::getValue($mvalue['name']);
                $result = $values[0] . ' ' . $values[1] . ' ' . $values[2] . ' ' . $values[3];
                Configuration::updateValue('xprt' . $mvalue['name'], $result);

            } else

            if (isset($mvalue['type']) && ($field['type'] == 'shadow')) {
                Configuration::updateValue('xprt' . $mvalue['name'], $this->getBorderSizeFromArray(Tools::getValue($mvalue['name'])));

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "color")) {
                $color = Tools::getValue($mvalue['name']);

                if ($color != 'transparent') {
                    $color = $this->_hex2rgb(Tools::getValue($mvalue['name']));
                }

                Configuration::updateValue('xprt' . $mvalue['name'], $color);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "select_font_size")) {
                $type = Tools::getValue($mvalue['name']);
                $size = Tools::getValue($mvalue['name'] . '_' . $type);
                Configuration::updateValue('xprt' . $mvalue['name'], $size . $type);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "rvb_opacity")) {
                $values = Tools::getValue($mvalue['name']);

                $color = $values[0];

                $opacity = $values[1];
                Configuration::updateValue('xprt' . $mvalue['name'], $this->_hex2rgb($color, true, $opacity));

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "text_shadow") || ($mvalue['type'] == "box_shadow")) {

                $value = Tools::getValue($mvalue['name']);
                $result = $value[0] . ' ' . $value[1] . ' ' . $value[2] . ' ' . $this->_hex2rgb($value[3], true, $value[4]);

                Configuration::updateValue('xprt' . $mvalue['name'], $result);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "img_base64")) {

                $key = $mvalue['name'] . '_mid';
                $imageUploader = new HelperImageUploader($key);
                $imageUploader->setAcceptTypes(['jpeg', 'png', 'jpg']);
                $files = $imageUploader->process();

                if (is_array($files) && count($files)) {

                    foreach ($files as $image) {
                        $type = pathinfo($image['name'], PATHINFO_EXTENSION);
                        $data = file_get_contents($image['save_path']);
                        $base64_code = base64_encode($data);
                        $base64_str = 'data:image/' . $type . ';base64,' . $base64_code;
                        $image = $base64_str;
                    }

                    Configuration::updateValue('xprt' . $mvalue['name'], $image);
                }

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "gradient")) {

                $value = Tools::getValue($mvalue['name']);
                $key = '';

                $key = $this->_hex2rgb($value[0]);

                if (!empty($value[1])) {
                    $key .= '-' . $this->_hex2rgb($value[1]);

                    if (!empty($value[2])) {
                        $key .= '-' . $value[2];
                    } else {
                        $key .= '-100';
                    }

                    if (!empty($value[3])) {
                        $key .= '-' . $value[3];
                    } else {
                        $key .= '-%';
                    }

                }

                Configuration::updateValue('xprt' . $mvalue['name'], $key);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "background_size")) {

                $value = Tools::getValue($mvalue['name']);

                if ($value == 'custom') {
                    $width = Tools::getValue($mvalue['name'] . '_width');
                    $height = Tools::getValue($mvalue['name'] . '_height');
                    $value = $width . ' ' . $height;
                }

                Configuration::updateValue('xprt' . $mvalue['name'], $value);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "background_position")) {

                $value = Tools::getValue($mvalue['name']);

                if ($value == 'custom') {
                    $width = Tools::getValue($mvalue['name'] . '_width');
                    $height = Tools::getValue($mvalue['name'] . '_height');
                    $value = $width . ' ' . $height;
                }

                Configuration::updateValue('xprt' . $mvalue['name'], $value);

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "contener_position")) {
                $positions = ['0', '0', '0', '0'];

                $position = Tools::getValue($mvalue['name']);
                $value = [];
                $value['position'] = $position;

                if ($position == 'absolute' || $position == 'fixed') {
                    $top = Tools::getValue($mvalue['name'] . '_top');
                    $right = Tools::getValue($mvalue['name'] . '_right');
                    $bottom = Tools::getValue($mvalue['name'] . '_bottom');
                    $left = Tools::getValue($mvalue['name'] . '_left');
                    $positions = [$top, $right, $bottom, $left];
                }

                $value['positions'] = $positions;
                Configuration::updateValue('xprt' . $mvalue['name'], Tools::jsonEncode($value));

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "element_before") || ($mvalue['type'] == "element_after")) {

                $value = Tools::getValue($mvalue['name'] . '_bck_type');
                $height = Tools::getValue($mvalue['name']);

                if (!empty($value)) {
                    $result = [];
                    $result['bck_type'] = $value;

                    if ($height > 0) {
                        $result['height'] = $height;
                    }

                    $top = Tools::getValue($mvalue['name'] . '_top');
                    $right = Tools::getValue($mvalue['name'] . '_right');
                    $bottom = Tools::getValue($mvalue['name'] . '_bottom');
                    $left = Tools::getValue($mvalue['name'] . '_left');
                    $result['position'] = [$top, $right, $bottom, $left];

                    if ($value == 'bck') {
                        $result['bck_color'] = $this->_hex2rgb(Tools::getValue($mvalue['name'] . '_bck'));
                    }

                    if ($value == 'gradient') {
                        $color1 = $this->_hex2rgb(Tools::getValue($mvalue['name'] . '_bck1'));
                        $color2 = $this->_hex2rgb(Tools::getValue($mvalue['name'] . '_bck2'));
                        $profondeur = Tools::getValue($mvalue['name'] . '_gradient');
                        $type = Tools::getValue($mvalue['name'] . '_type');
                        $result['bck_value'] = $color1 . '-' . $color2 . '-' . $profondeur . '-' . $type;
                    } else {
                        $color = $this->_hex2rgb(Tools::getValue($mvalue['name'] . '_border_color'));
                        $border_size = Tools::getValue($mvalue['name'] . '_border_size');
                        $radius = Tools::getValue($mvalue['name'] . '_radius');
                        $result['border_color'] = $color;
                        $result['border_size'] = $border_size;
                        $result['radius'] = $radius[0] . ' ' . $radius[1] . ' ' . $radius[2] . ' ' . $radius[3];
                    }

                    Configuration::updateValue('xprt' . $mvalue['name'], Tools::jsonEncode($result));
                }

            } else

            if (isset($mvalue['type']) && ($mvalue['type'] == "contener_border")) {

                $type = Tools::getValue($mvalue['name']);
                $result = [];
                $result['type'] = $type;

                if ($type != 'none') {
                    $result['style'] = Tools::getValue($mvalue['name'] . '_border_type');
                    $result['size'] = Tools::getValue($mvalue['name'] . '_size');
                    $result['color'] = Tools::getValue($mvalue['name'] . '_color');
                }

                Configuration::updateValue('xprt' . $mvalue['name'], Tools::jsonEncode($result));
            } else {

                if (isset($mvalue['lang']) && $mvalue['lang'] == true && isset($mvalue['name'])) {

                    Configuration::updateValue('xprt' . $mvalue['name'], ${$mvalue['name'] . '_lang'});
                } else {

                    if (isset($mvalue['name'])) {
                        Configuration::updateValue('xprt' . $mvalue['name'], Tools::getvalue($mvalue['name']));
                    }

                }

            }

        }

        $this->GenerateCustomCss();
        $this->GenerateCustomJs();
        Configuration::updateValue('xprt' . "color_group_name_sel", Tools::getValue('color_group_name_sel'));
    }

    public function GenerateCustomCss() {

        $context = Context::getContext();
        $url_no_image = $context->link->getBaseFrontLink() . 'img/fr.jpg';
        $xprt = $this->AsignGlobalSettingValue();
        $tpl = $context->smarty->createTemplate(_PS_ALL_THEMES_DIR_ . "xprtroot_css_.tpl");

        $tpl->assign([
            'polygon'     => Configuration::get('PS_LOGO'),
            'logo_shop'   => !empty(Configuration::get('PS_LOGO')) ? $this->context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO') : $url_no_image,
            'logo_mobile' => !empty(Configuration::get('PS_LOGO_MOBILE')) ? $this->context->link->getBaseFrontLink() . 'img/' . Configuration::get('PS_LOGO_MOBILE') : $url_no_image,
        ]);

        $imageCertif = !empty(Configuration::get('EPH_CERTIFICATION_IMAGE')) ? $context->link->getBaseFrontLink() . 'img/' . Configuration::get('EPH_CERTIFICATION_IMAGE') : $url_no_image;
        $tpl->assign([
            'imageCertif' => $imageCertif,
        ]);

        foreach ($xprt as $key => $value) {

            $tpl->assign([
                $key => $value,
            ]);
        }

        $custom_css = $tpl->fetch();
        $css = fopen($this->cssRootfile, 'w');
        fwrite($css, $custom_css);
        $css = fopen($this->agent_cssRootfile, 'w');
        fwrite($css, $custom_css);

        $tpl = $context->smarty->createTemplate(_PS_ALL_THEMES_DIR_ . "xprtcustom_css.tpl");

        foreach ($xprt as $key => $value) {

            if ($key == 'custom_css' || $key == 'menu_custom_css') {

                $value = str_replace(["\\r\\n", "\\r"], '', $value);
                $minifier = new Minify\CSS();
                $minifier->add($value);
                $value = $minifier->minify();

            }

            $tpl->assign([
                $key => $value,
            ]);
        }

        $custom_css = $tpl->fetch();
        $css = fopen($this->cssfile, 'w');
        fwrite($css, $custom_css);
        $css = fopen($this->agent_cssfile, 'w');
        fwrite($css, $custom_css);
        $is_polygone = Configuration::get('EPH_POLYGON_ACTIVE');

        if ($is_polygone) {
            $tpl = $context->smarty->createTemplate(_PS_ALL_THEMES_DIR_ . "xprtpolygon_css.tpl");

            foreach ($xprt as $key => $value) {
                $tpl->assign([
                    $key => $value,
                ]);
            }

            $custom_css = $tpl->fetch();
            $css = fopen($this->cssPolygonfile, 'w');
            fwrite($css, $custom_css);
            $tpl = $context->smarty->createTemplate(_PS_ALL_THEMES_DIR_ . "xprtpolygon_agent_css.tpl");

            foreach ($xprt as $key => $value) {
                $tpl->assign([
                    $key => $value,
                ]);
            }

            $custom_css = $tpl->fetch();
            $css = fopen($this->agent_cssPolygonfile, 'w');
            fwrite($css, $custom_css);
        } else {

            if (file_exists($this->cssPolygonfile)) {
                unlink($this->cssPolygonfile);
            }

            if (file_exists($this->agent_cssPolygonfile)) {
                unlink($this->agent_cssPolygonfile);
            }

        }

    }

    public function GenerateCustomJs() {

        $context = Context::getContext();
        $xprt = $this->AsignGlobalSettingValue();
        $tpl = $context->smarty->createTemplate(_PS_ALL_THEMES_DIR_ . "xprtcustom_js_.tpl");

        $tpl->assign([
            'polygon' => Configuration::get('EPH_POLYGON_ACTIVE'),
        ]);

        foreach ($xprt as $key => $value) {

            $tpl->assign([
                $key => $value,
            ]);
        }

        $custom_css = $tpl->fetch();
        $css = fopen($this->jsfile, 'w');
        fwrite($css, $custom_css);
        $css = fopen($this->agent_jsfile, 'w');
        fwrite($css, $custom_css);
        @chmod($generatecssfilename, 0777);
    }

    public function ajaxProcessColorSchemaset() {

        $color_array = [];
        $color_schema = Tools::getValue("color_group");
        $multiple_arr = [];

        if (!$this->fields_form) {
            $this->AllFields();
        }

        Configuration::updateValue('xprt' . "color_group_name", $color_schema);

        foreach ($this->fields_form as $key => $value) {
            $multiple_arr = array_merge($multiple_arr, $value['form']['input']);
        }

        if (isset($multiple_arr) && !empty($multiple_arr)) {

            foreach ($multiple_arr as $mvalue) {

                if (isset($mvalue['type']) && $mvalue['type'] == "color") {

                    if ($color_schema == 'default') {
                        $color_array[$mvalue['name']] = $mvalue['default_val'];
                    } else {

                        if (isset($mvalue['predefine'][$color_schema]) && !empty($mvalue['predefine'][$color_schema])) {
                            $color_array[$mvalue['name']] = $mvalue['predefine'][$color_schema];
                        } else {
                            $color_array[$mvalue['name']] = $mvalue['default_val'];
                        }

                    }

                }

            }

        }

        die(Tools::jsonEncode($color_array));
    }

    public function ajaxProcessDeleteDemoTheme() {

        $selected_demo = Tools::getValue('select_demo_ready');

        if (file_exists(_PS_EPH_THEME_DIR_ . 'demo/' . $selected_demo)) {
            unlink(_PS_EPH_THEME_DIR_ . 'demo/' . $selected_demo);
        }

        $return = [
            'success' => true,
            'message' => 'Cette sauvegarde a été détruite avec succès',
        ];

        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessApplyDemoTheme() {

        $selecteddemo = Tools::getValue('select_demo_ready');

        if ($this->selecteddemosetup($selecteddemo)) {
            Configuration::updateValue('xprt_demo_number', $selecteddemo);
            $this->GenerateCustomCss();
            $return = [
                'success' => true,
                'message' => 'Le thème a été mis à jour avec succès',
            ];
        } else {
            $return = [
                'success' => false,
                'message' => 'Un truc a merder',
            ];
        }

        die(Tools::jsonEncode($return));

    }

    public function selecteddemosetup($selected_demo = NULL) {

        if ($selected_demo == NULL) {
            return false;
        }

        $xml = false;

        if (file_exists(_PS_EPH_THEME_DIR_ . 'demo/' . $selected_demo)) {
            $xml = @simplexml_load_file(_PS_EPH_THEME_DIR_ . 'demo/' . $selected_demo);
        } else {
            return false;
        }

        if ($xml) {
            $xprtfields = json_decode(json_encode($xml->xprtfields), true);

            foreach ($xprtfields as $rows) {

                foreach ($rows as $row) {

                    $key = strval($row['@attributes']['name']);
                    $value = strval($row['@attributes']['value']);

                    Configuration::updateValue('xprt' . $key, $value);
                }

            }

            $fontfields = json_decode(json_encode($xml->fontfields), true);

            foreach ($fontfields as $rows) {

                foreach ($rows as $row) {

                    $key = strval($row['@attributes']['name']);
                    $value = strval($row['@attributes']['value']);

                    Configuration::updateValue($key, $value);
                }

            }

        } else {
            return false;
        }

        Tools::clearCache($this->context->smarty);

        return true;
    }

    public function ChooseThemeModule($xml = NULL) {

        $values = [];
        $to_install = [];
        $to_enable = [];
        $to_disable = [];

        if ($xml) {
            $theme_module = $this->getModules($xml);

            if (isset($theme_module['to_install'])) {
                $to_install = $this->formatHelperArray($theme_module['to_install'], 'to_install');
            }

            if (isset($theme_module['to_enable'])) {
                $to_enable = $this->formatHelperArray($theme_module['to_enable'], 'to_enable');
            }

            if (isset($theme_module['to_disable'])) {
                $to_disable = $this->formatHelperArray($theme_module['to_disable'], 'to_disable');
            }

        }

        $values = array_merge($to_install, $to_enable, $to_disable);
        return $values;
    }

    private function formatHelperArray($origin_arr, $postfix = NULL) {

        if ($postfix) {
            $postfix = $postfix . '_module';
        }

        $fmt_arr = [];

        foreach ($origin_arr as $module) {
            $display_name = $module;
            $module_obj = Module::getInstanceByName($module);

            if (Validate::isLoadedObject($module_obj)) {
                $display_name = $module_obj->displayName;
            }

            $tmp = [];
            $fmt_arr[$postfix . $module] = $module;
        }

        return $fmt_arr;
    }

    protected function checkPostedDatas() {

        $mail = Tools::getValue('email');
        $website = Tools::getValue('website');

        if ($mail && !preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#', $mail)) {
            $this->errors[] = $this->l('There is an error in your email syntax!');
        } else

        if ($website && (!Validate::isURL($website) || !Validate::isAbsoluteUrl($website))) {
            $this->errors[] = $this->l('There is an error in your URL syntax!');
        } else

        if (!$this->checkVersionsAndCompatibility() || !$this->checkNames() || !$this->checkDocumentation()) {
            return false;
        } else {
            return true;
        }

        return false;
    }

    protected function checkVersionsAndCompatibility() {

        $exp = '#^[0-9]+[.]+[0-9.]*[0-9]$#';

        if (!preg_match('#^[0-9][.][0-9]$#', Tools::getValue('theme_version')) ||
            !preg_match($exp, Tools::getValue('compa_from')) || !preg_match($exp, Tools::getValue('compa_to')) ||
            version_compare(Tools::getValue('compa_from'), Tools::getValue('compa_to')) == 1
        ) {
            $this->errors[] = $this->l('Syntax error on version field. Only digits and periods (.) are allowed, and the compatibility version should be increasing or at least be equal to the previous version.');
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    protected function checkNames() {

        $author = Tools::getValue('name');
        $themeName = Tools::getValue('theme_name');

        if (!$author || !Validate::isGenericName($author) || strlen($author) > static::MAX_NAME_LENGTH) {
            $this->errors[] = $this->l('Please enter a valid author name');
        } else

        if (!$themeName || !Validate::isGenericName($themeName) || strlen($themeName) > static::MAX_NAME_LENGTH) {
            $this->errors[] = $this->l('Please enter a valid theme name');
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    protected function checkDocumentation() {

        $extensions = [
            '.pdf',
            '.txt',
        ];

        if (isset($_FILES['documentation']) && $_FILES['documentation']['name'] != '') {
            $extension = strrchr($_FILES['documentation']['name'], '.');
            $name = Tools::getValue('documentationName');

            if (!in_array($extension, $extensions)) {
                $this->errors[] = $this->l('File extension must be .txt or .pdf');
            } else

            if ($_FILES['documentation']['error'] > 0) {
                $this->errors[] = $this->l('An error occurred during documentation upload');
            } else

            if ($_FILES['documentation']['size'] > 1048576) {
                $this->errors[] = $this->l('An error occurred while uploading the documentation. Maximum size allowed is 1MB.');
            } else

            if (!$name || !Validate::isGenericName($name) || strlen($name) > static::MAX_NAME_LENGTH) {
                $this->errors[] = $this->l('Please enter a valid documentation name');
            }

        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    protected function getNativeModule($type = 0) {

        return [
            'addsharethis',
            'bankwire',
            'blockadvertising',
            'blockbanner',
            'blockbestsellers',
            'blockcart',
            'blockcategories',
            'blockcms',
            'blockcmsinfo',
            'blockcontact',
            'blockcontactinfos',
            'blockcurrencies',
            'blockcustomerprivacy',
            'blockfacebook',
            'blocklanguages',
            'blocklayered',
            'blocklink',
            'blockmanufacturer',
            'blockmyaccount',
            'blockmyaccountfooter',
            'blocknewproducts',
            'blocknewsletter',
            'blockpaymentlogo',
            'blockpermanentlinks',
            'blockreinsurance',
            'blockrss',
            'blocksearch',
            'blocksharefb',
            'blocksocial',
            'blockspecials',
            'blockstore',
            'blocksupplier',
            'blocktags',
            'blocktopmenu',
            'blockuserinfo',
            'blockviewed',
            'blockwishlist',
            'carriercompare',
            'cashondelivery',
            'cheque',
            'crossselling',
            'dashactivity',
            'dashgoals',
            'dashproducts',
            'dashtrends',
            'dateofdelivery',
            'editorial',
            'favoriteproducts',
            'feeder',
            'followup',
            'gapi',
            'gridhtml',
            'homefeatured',
            'homeslider',
            'loyalty',
            'mailalerts',
            'newsletter',
            'pagesnotfound',
            'productcomments',
            'productpaymentlogos',
            'productscategory',
            'producttooltip',
            'pscleaner',
            'referralprogram',
            'sekeywords',
            'sendtoafriend',
            'socialsharing',
            'statsbestcategories',
            'statsbestcustomers',
            'statsbestmanufacturers',
            'statsbestproducts',
            'statsbestsuppliers',
            'statsbestvouchers',
            'statscarrier',
            'statscatalog',
            'statscheckup',
            'statsdata',
            'statsequipment',
            'statsforecast',
            'statslive',
            'statsnewsletter',
            'statsorigin',
            'statspersonalinfos',
            'statsproduct',
            'statsregistrations',
            'statssales',
            'statssearch',
            'statsstock',
            'statsvisits',
            'themeconfigurator',
            'trackingfront',
            'vatnumber',
            'watermark',
        ];
    }

    protected function checkParentClass($name) {

        if (!$obj = Module::getInstanceByName($name)) {
            return false;
        }

        if (is_callable([$obj, 'validateOrder'])) {
            return false;
        }

        if (is_callable([$obj, 'getDateBetween'])) {
            return false;
        }

        if (is_callable([$obj, 'getGridEngines'])) {
            return false;
        }

        if (is_callable([$obj, 'getGraphEngines'])) {
            return false;
        }

        if (is_callable([$obj, 'hookAdminStatsModules'])) {
            return false;
        } else {
            return true;
        }

    }

    protected function generateArchive() {

        $zip = new ZipArchive();
        $zipFileName = md5(time()) . '.zip';

        if ($zip->open(_PS_CACHE_DIR_ . $zipFileName, ZipArchive::OVERWRITE | ZipArchive::CREATE) === true) {

            if (!$zip->addFromString('Config.xml', $this->xml_file)) {
                $this->errors[] = $this->l('Cannot create config file.');
            }

            if (isset($_FILES['documentation'])) {

                if (!empty($_FILES['documentation']['tmp_name']) &&
                    !empty($_FILES['documentation']['name']) &&
                    !$zip->addFile($_FILES['documentation']['tmp_name'], 'doc/' . $_FILES['documentation']['name'])
                ) {
                    $this->errors[] = $this->l('Cannot copy documentation.');
                }

            }

            $givenPath = realpath(_PS_ALL_THEMES_DIR_ . Tools::getValue('theme_directory'));

            if ($givenPath !== false) {
                $psAllThemeDirLenght = strlen(realpath(_PS_ALL_THEMES_DIR_));
                $toComparePath = substr($givenPath, 0, $psAllThemeDirLenght);

                if ($toComparePath != realpath(_PS_ALL_THEMES_DIR_)) {
                    $this->errors[] = $this->l('Wrong theme directory path');
                } else {
                    $this->archiveThisFile($zip, Tools::getValue('theme_directory'), _PS_ALL_THEMES_DIR_, 'themes/');

                    foreach ($this->to_export as $row) {

                        if (!in_array($row, $this->native_modules)) {
                            $this->archiveThisFile($zip, $row, _PS_FRONT_DIR_ . '/modules/', 'modules/');
                        }

                    }

                }

            } else {
                $this->errors[] = $this->l('Wrong theme directory path');
            }

            $zip->close();

            if (!is_file(_PS_CACHE_DIR_ . $zipFileName)) {
                $this->errors[] = $this->l(sprintf('Could not create %1s', _PS_CACHE_DIR_ . $zipFileName));
            }

            if (!$this->errors) {

                if (ob_get_length() > 0) {
                    ob_end_clean();
                }

                ob_start();
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public');
                header('Content-Description: File Transfer');
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Transfer-Encoding: binary');
                ob_end_flush();
                readfile(_PS_CACHE_DIR_ . $zipFileName);
                @unlink(_PS_CACHE_DIR_ . $zipFileName);
                exit;
            }

        }

        $this->errors[] = $this->l('An error occurred during the archive generation');
    }

    protected function archiveThisFile($obj, $file, $serverPath, $archivePath) {

        if (is_dir($serverPath . $file)) {
            $dir = scandir($serverPath . $file);

            foreach ($dir as $row) {

                if ($row[0] != '.') {
                    $this->archiveThisFile($obj, $row, $serverPath . $file . '/', $archivePath . $file . '/');
                }

            }

        } else

        if (!$obj->addFile($serverPath . $file, $archivePath . $file)) {
            $this->error = true;
        }

    }

    public function renderExportTheme() {

        if (Tools::getIsset('id_theme_export') && (int) Tools::getValue('id_theme_export') > 0) {
            return $this->renderExportTheme1();
        }

        $themeList = Theme::getThemes();
        $fieldsForm = [
            'form' => [
                'tinymce' => false,
                'legend'  => [
                    'title' => $this->l('Theme'),
                    'icon'  => 'icon-picture',
                ],
                'input'   => [
                    [
                        'type'    => 'select',
                        'name'    => 'id_theme_export',
                        'label'   => $this->l('Choose the theme that you want to export'),
                        'options' => [
                            'id'    => 'id',
                            'name'  => 'name',
                            'query' => $themeList,
                        ],

                    ],
                ],
                'submit'  => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $toolbarBtn['save'] = [
            'href' => '#',
            'desc' => $this->l('Export'),
        ];

        $fieldsValue['id_theme_export'] = [];
        $helper = new HelperForm();

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false) . '&action=exporttheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $this->fields_value = $fieldsValue;
        $helper->toolbar_btn = $toolbarBtn;
        $helper->override_folder = $this->tpl_folder;

        return $helper->generateForm([$fieldsForm]);
    }

    protected function renderExportTheme1() {

        $toInstall = [];

        $moduleList = Db::getInstance()->executeS(
            '
            SELECT m.`id_module`, m.`name`, m.`active`, ms.`id_shop`
            FROM `' . _DB_PREFIX_ . 'module` m
            LEFT JOIN `' . _DB_PREFIX_ . 'module_shop` ms On (m.`id_module` = ms.`id_module`)
            WHERE ms.`id_shop` = ' . (int) $this->context->shop->id . '
        '
        );

        // Select the list of hook for this shop
        $hookList = Db::getInstance()->executeS(
            '
            SELECT h.`id_hook`, h.`name` as name_hook, hm.`position`, hm.`id_module`, m.`name` as name_module, GROUP_CONCAT(hme.`file_name`, ",") as exceptions
            FROM `' . _DB_PREFIX_ . 'hook` h
            LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_hook` = h.`id_hook`
            LEFT JOIN `' . _DB_PREFIX_ . 'module` m ON hm.`id_module` = m.`id_module`
            LEFT OUTER JOIN `' . _DB_PREFIX_ . 'hook_module_exceptions` hme ON (hme.`id_module` = hm.`id_module` AND hme.`id_hook` = h.`id_hook`)
            WHERE hm.`id_shop` = ' . (int) $this->context->shop->id . '
            GROUP BY `id_module`, `id_hook`
            ORDER BY `name_module`
        '
        );

        foreach ($hookList as &$row) {
            $row['exceptions'] = trim(preg_replace('/(,,+)/', ',', $row['exceptions']), ',');
        }

        $nativeModules = $this->getNativeModule();

        foreach ($moduleList as $array) {

            if (!static::checkParentClass($array['name'])) {
                continue;
            }

            if (in_array($array['name'], $nativeModules)) {

                if ($array['active'] == 1) {
                    $toEnable[] = $array['name'];
                } else {
                    $toDisable[] = $array['name'];
                }

            } else

            if ($array['active'] == 1) {
                $toInstall[] = $array['name'];
            }

        }

        foreach ($nativeModules as $str) {
            $flag = 0;

            if (!$this->checkParentClass($str)) {
                continue;
            }

            foreach ($moduleList as $tmp) {

                if (in_array($str, $tmp)) {
                    $flag = 1;
                    break;
                }

            }

            if ($flag == 0) {
                $toDisable[] = $str;
            }

        }

        $employee = $this->context->employee;
        $mail = Tools::getValue('email') ? Tools::getValue('email') : $employee->email;
        $author = Tools::getValue('author_name') ? Tools::getValue('author_name') : $employee->firstname . ' ' . $employee->lastname;
        $website = Tools::getValue('website') ? Tools::getValue('website') : Tools::getHttpHost(true);

        $this->formatHelperArray($toInstall);

        $theme = new Theme(Tools::getValue('id_theme_export'));

        $fieldsForm = [
            'form' => [
                'tinymce' => false,
                'legend'  => [
                    'title' => $this->l('Theme configuration'),
                    'icon'  => 'icon-picture',
                ],
                'input'   => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_theme_export',
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'name',
                        'label' => $this->l('Name'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'email',
                        'label' => $this->l('Email'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'website',
                        'label' => $this->l('Website'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_name',
                        'label' => $this->l('Theme name'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_directory',
                        'label' => $this->l('Theme directory'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'body_title',
                        'lang'  => true,
                        'label' => $this->l('Description'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'theme_version',
                        'label' => $this->l('Theme version'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'compa_from',
                        'label' => $this->l('Compatible from'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'compa_to',
                        'label' => $this->l('Compatible to'),
                    ],
                    [
                        'type'  => 'file',
                        'name'  => 'documentation',
                        'label' => $this->l('Documentation'),
                    ],
                    [
                        'type'  => 'text',
                        'name'  => 'documentationName',
                        'label' => $this->l('Documentation name'),
                    ],
                ],
                'submit'  => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        if (count($toInstall) > 0) {

            foreach ($toInstall as $module) {
                $fieldsValue['modulesToExport_module' . $module] = true;
            }

            $fieldsForm['form']['input'][] = [
                'type'   => 'checkbox',
                'label'  => $this->l('Select the theme\'s modules that you wish to export'),
                'values' => [
                    'query' => $this->formatHelperArray($toInstall),
                    'id'    => 'id',
                    'name'  => 'name',
                ],
                'name'   => 'modulesToExport',
            ];
        }

        $defaultLanguage = (int) $this->context->language->id;
        $languages = $this->getLanguages();

        foreach ($languages as $language) {
            $fieldsValue['body_title'][$language['id_lang']] = '';
        }

        $helper = new HelperForm();
        $helper->languages = $languages;
        $helper->default_form_language = $defaultLanguage;
        $fieldsValue['name'] = $author;
        $fieldsValue['email'] = $mail;
        $fieldsValue['website'] = $website;
        $fieldsValue['theme_name'] = $theme->name;
        $fieldsValue['theme_directory'] = $theme->directory;
        $fieldsValue['theme_version'] = '1.0';
        $fieldsValue['compa_from'] = _PS_VERSION_;
        $fieldsValue['compa_to'] = _PS_VERSION_;
        $fieldsValue['id_theme_export'] = Tools::getValue('id_theme_export');
        $fieldsValue['documentationName'] = $this->l('documentation');

        $toolbarBtn['save'] = [
            'href' => '',
            'desc' => $this->l('Save'),
        ];

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false) . '&action=exporttheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $this->fields_value = $fieldsValue;
        $helper->toolbar_btn = $toolbarBtn;
        $helper->override_folder = $this->tpl_folder;

        return $helper->generateForm([$fieldsForm]);
    }

    public function processImportTheme() {

        if (!($this->tabAccess['add'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to add here.');

            return false;
        } else {
            $this->display = 'importtheme';

            if ($this->context->mode == Context::MODE_HOST) {
                return true;
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['themearchive']) && isset($_POST['filename']) && Tools::isSubmit('theme_archive_server')) {
                $uniqid = uniqid();
                $sandbox = _PS_CACHE_DIR_ . 'sandbox' . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR;
                mkdir($sandbox, 0777, true);
                $archiveUploaded = false;

                if (Tools::getValue('filename') != '') {
                    $uploader = new Uploader('themearchive');
                    $uploader->setCheckFileSize(false);
                    $uploader->setAcceptTypes(['zip']);
                    $uploader->setSavePath($sandbox);
                    $file = $uploader->process(Theme::UPLOADED_THEME_DIR_NAME . '.zip');

                    if ($file[0]['error'] === 0) {

                        if (Tools::ZipTest($sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip')) {
                            $archiveUploaded = true;
                        } else {
                            $this->errors[] = $this->l('Zip file seems to be broken');
                        }

                    } else {
                        $this->errors[] = $file[0]['error'];
                    }

                } else

                if (Tools::getValue('themearchiveUrl') != '') {

                    if (!Validate::isModuleUrl($url = Tools::getValue('themearchiveUrl'), $this->errors)) {
                        $this->errors[] = $this->l('Only zip files are allowed');
                    } else

                    if (!Tools::copy($url, $sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip')) {
                        $this->errors[] = $this->l('Error during the file download');
                    } else

                    if (Tools::ZipTest($sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip')) {
                        $archiveUploaded = true;
                    } else {
                        $this->errors[] = $this->l('Zip file seems to be broken');
                    }

                } else

                if (Tools::getValue('theme_archive_server') != '') {
                    $filename = _PS_ALL_THEMES_DIR_ . Tools::getValue('theme_archive_server');

                    if (substr($filename, -4) != '.zip') {
                        $this->errors[] = $this->l('Only zip files are allowed');
                    } else

                    if (!copy($filename, $sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip')) {
                        $this->errors[] = $this->l('An error has occurred during the file copy.');
                    } else

                    if (Tools::ZipTest($sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip')) {
                        $archiveUploaded = true;
                    } else {
                        $this->errors[] = $this->l('Zip file seems to be broken');
                    }

                } else {
                    $this->errors[] = $this->l('You must upload or enter a location of your zip');
                }

                if ($archiveUploaded) {

                    if ($this->extractTheme($sandbox . Theme::UPLOADED_THEME_DIR_NAME . '.zip', $sandbox)) {
                        $this->installTheme(Theme::UPLOADED_THEME_DIR_NAME, $sandbox);
                    }

                }

                Tools::deleteDirectory($sandbox);

                if (count($this->errors) > 0) {
                    $this->display = 'importtheme';
                } else {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes') . '&conf=18');
                }

            } else

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                //method is POST but no uplad info -> there is post error
                $maxPost = (int) ini_get('post_max_size');
                $this->errors[] = sprintf($this->l('The file size exceeds the size allowed by the server. The limit is set to %s MB.'), '<b>' . $maxPost . '</b>');
            }

        }

    }

    protected function extractTheme($themeZipFile, $sandbox) {

        if (!($this->tabAccess['add'] && $this->tabAccess['edit'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = $this->l('You do not have permission to extract here.');

            return false;
        }

        if (Tools::ZipExtract($themeZipFile, $sandbox . Theme::UPLOADED_THEME_DIR_NAME . '/')) {
            return true;
        }

        $this->errors[] = $this->l('Error during zip extraction');

        return false;
    }

    protected function installTheme($themeDir, $sandbox = false, $redirect = true) {

        if ($this->tabAccess['add'] && $this->tabAccess['delete'] && !_PS_MODE_DEMO_) {

            if (!$sandbox) {
                $uniqid = uniqid();
                $sandbox = _PS_CACHE_DIR_ . 'sandbox' . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR;
                mkdir($sandbox);
                Tools::recurseCopy(_PS_ALL_THEMES_DIR_ . $themeDir, $sandbox . $themeDir);
            }

            $configFile = '/Config.xml';
            $xml = Theme::loadConfigFromFile($sandbox . $themeDir . $configFile, true);

            if (!$xml) {
                $configFile = '/config.xml';
                $xml = Theme::loadConfigFromFile($sandbox . $themeDir . $configFile, true);

                if (!$xml) {
                    $this->errors[] = $this->l('Bad or missing configuration file.');
                }

            }

            if ($xml) {
                $importedTheme = $this->importThemeXmlConfig($xml);

                foreach ($importedTheme as $theme) {

                    if (Validate::isLoadedObject($theme)) {

                        if (!copy($sandbox . $themeDir . $configFile, _PS_FRONT_DIR_ . '/app/xml/themes/' . $theme->directory . '.xml')) {
                            $this->errors[] = $this->l('Can\'t copy configuration file');
                        }

                        $targetDir = _PS_ALL_THEMES_DIR_ . $theme->directory;

                        if (file_exists($targetDir)) {
                            Tools::deleteDirectory($targetDir);
                        }

                        $themeDocDir = $targetDir . '/docs/';

                        if (file_exists($themeDocDir)) {
                            Tools::deleteDirectory($themeDocDir);
                        }

                        mkdir($targetDir);
                        mkdir($themeDocDir);
                        Tools::recurseCopy($sandbox . $themeDir . '/themes/' . $theme->directory . '/', $targetDir . '/');
                        Tools::recurseCopy($sandbox . $themeDir . '/doc/', $themeDocDir);
                        Tools::recurseCopy($sandbox . $themeDir . '/modules/', _PS_MODULE_DIR_);
                    } else {
                        $this->errors[] = $theme;
                    }

                }

            }

            Tools::deleteDirectory($sandbox);
        }

        if (!count($this->errors)) {

            if ($redirect) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes') . '&conf=18');
            } else {
                return true;
            }

        } else {
            return false;
        }

    }

    protected function checkXmlFields($xmlFile) {

        Tools::displayAsDeprecated();

        if (!file_exists($xmlFile)) {
            return false;
        }

        return Theme::validateConfigFile(@simplexml_load_file($xmlFile));
    }

    protected function importThemeXmlConfig(SimpleXMLElement $xml, $themeDir = false) {

        $attr = $xml->attributes();
        $thName = (string) $attr->name;

        if ($this->isThemeInstalled($thName)) {
            return [sprintf($this->l('Theme %s already installed.'), $thName)];
        }

        $newThemeArray = [];

        foreach ($xml->variations->variation as $variation) {
            $name = strval($variation['name']);

            $newTheme = new Theme();
            $newTheme->name = $name;

            $newTheme->directory = strval($variation['directory']);

            if ($themeDir) {
                $newTheme->name = $themeDir;
                $newTheme->directory = $themeDir;
            }

            if ($this->isThemeInstalled($newTheme->name)) {
                continue;
            }

            $newTheme->product_per_page = Configuration::get('PS_PRODUCTS_PER_PAGE');

            if (isset($variation['product_per_page'])) {
                $newTheme->product_per_page = intval($variation['product_per_page']);
            }

            $newTheme->responsive = false;

            if (isset($variation['responsive'])) {
                $newTheme->responsive = (bool) strval($variation['responsive']);
            }

            $newTheme->default_left_column = true;
            $newTheme->default_right_column = true;

            if (isset($variation['default_left_column'])) {
                $newTheme->default_left_column = (bool) strval($variation['default_left_column']);
            }

            if (isset($variation['default_right_column'])) {
                $newTheme->default_right_column = (bool) strval($variation['default_right_column']);
            }

            $fillDefaultMeta = true;
            $metasXml = [];

            if ($xml->metas->meta) {

                foreach ($xml->metas->meta as $meta) {
                    $metaId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        (new DbQuery())
                            ->select('`id_meta`')
                            ->from('meta')
                            ->where('`page` = \'' . pSQL($meta['meta_page']) . '\'')
                    );

                    if ((int) $metaId > 0) {
                        $tmpMeta = [];
                        $tmpMeta['id_meta'] = (int) $metaId;
                        $tmpMeta['left'] = intval($meta['left']);
                        $tmpMeta['right'] = intval($meta['right']);
                        $metasXml[(int) $metaId] = $tmpMeta;
                    }

                }

                $fillDefaultMeta = false;

                if (count($xml->metas->meta) < (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('meta')
                )) {
                    $fillDefaultMeta = true;
                }

            }

            if ($fillDefaultMeta == true) {
                $metas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_meta`')
                        ->from('meta')
                );

                foreach ($metas as $meta) {

                    if (!isset($metasXml[(int) $meta['id_meta']])) {
                        $tmpMeta['id_meta'] = (int) $meta['id_meta'];
                        $tmpMeta['left'] = $newTheme->default_left_column;
                        $tmpMeta['right'] = $newTheme->default_right_column;
                        $metasXml[(int) $meta['id_meta']] = $tmpMeta;
                    }

                }

            }

            if (!is_dir(_PS_ALL_THEMES_DIR_ . $newTheme->directory)) {

                if (!mkdir(_PS_ALL_THEMES_DIR_ . $newTheme->directory)) {
                    return sprintf($this->l('Error while creating %s directory'), _PS_ALL_THEMES_DIR_ . $newTheme->directory);
                }

            }

            $newTheme->add();

            if ($newTheme->id > 0) {
                $newTheme->updateMetas($metasXml);
                $newThemeArray[] = $newTheme;
            } else {
                $newThemeArray[] = sprintf($this->l('Error while installing theme %s'), $newTheme->name);
            }

        }

        return $newThemeArray;
    }

    protected function isThemeInstalled($themeName) {

        $themes = Theme::getThemes();

        foreach ($themes as $themeObject) {
            /** @var Theme $themeObject */

            if ($themeObject->name == $themeName) {
                return true;
            }

        }

        return false;
    }

    public function renderImportTheme() {

        $fieldsForm = [];

        $toolbarBtn['save'] = [
            'href' => '#',
            'desc' => $this->l('Save'),
        ];

        if ($this->context->mode != Context::MODE_HOST) {
            $fieldsForm[0] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from your computer'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'  => 'file',
                            'label' => $this->l('Zip file'),
                            'desc'  => $this->l('Browse your computer files and select the Zip file for your new theme.'),
                            'name'  => 'themearchive',
                        ],
                    ],
                    'submit'  => [
                        'id'    => 'zip',
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            $fieldsForm[1] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from the web'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'  => 'text',
                            'label' => $this->l('Archive URL'),
                            'desc'  => $this->l('Indicate the complete URL to an online Zip file that contains your new theme. For instance, "http://example.com/files/theme.zip".'),
                            'name'  => 'themearchiveUrl',
                        ],
                    ],
                    'submit'  => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            $themeArchiveServer = [];
            $files = scandir(_PS_ALL_THEMES_DIR_);
            $themeArchiveServer[] = '-';

            foreach ($files as $file) {

                if (is_file(_PS_ALL_THEMES_DIR_ . $file) && substr(_PS_ALL_THEMES_DIR_ . $file, -4) == '.zip') {
                    $themeArchiveServer[] = [
                        'id'   => basename(_PS_ALL_THEMES_DIR_ . $file),
                        'name' => basename(_PS_ALL_THEMES_DIR_ . $file),
                    ];
                }

            }

            $fieldsForm[2] = [
                'form' => [
                    'tinymce' => false,
                    'legend'  => [
                        'title' => $this->l('Import from FTP'),
                        'icon'  => 'icon-picture',
                    ],
                    'input'   => [
                        [
                            'type'    => 'select',
                            'label'   => $this->l('Select the archive'),
                            'name'    => 'theme_archive_server',
                            'desc'    => $this->l('This selector lists the Zip files that you uploaded in the \'/themes\' folder.'),
                            'options' => [
                                'id'    => 'id',
                                'name'  => 'name',
                                'query' => $themeArchiveServer,
                            ],
                        ],
                    ],
                    'submit'  => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }

        $this->context->smarty->assign(
            [
                'import_theme'        => true,
                'logged_on_addons'    => false,
                'iso_code'            => $this->context->language->iso_code,
                'add_new_theme_href'  => static::$currentIndex . '&addtheme&token=' . $this->token,
                'add_new_theme_label' => $this->l('Create a new theme'),
            ]
        );

        $createNewThemePanel = $this->context->smarty->fetch('controllers/themes/helpers/view/importtheme_view.tpl');

        $helper = new HelperForm();

        $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false) . '&action=importtheme';
        $helper->token = Tools::getAdminTokenLite('AdminThemes');
        $helper->show_toolbar = true;
        $helper->toolbar_btn = $toolbarBtn;
        $this->fields_value['themearchiveUrl'] = '';
        $this->fields_value['theme_archive_server'] = [];
        $helper->multiple_fieldsets = true;
        $helper->override_folder = $this->tpl_folder;
        $helper->languages = $this->getLanguages();
        $helper->default_form_language = (int) $this->context->language->id;

        return $helper->generateForm($fieldsForm) . $createNewThemePanel;
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws Exception
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 1.9.1.0
     */
    public function initContent() {

        if ($this->display == 'list') {
            $this->display = '';
        }

        if (isset($this->display) && method_exists($this, 'render' . $this->display)) {

            $this->content .= $this->{'render' . $this->display}

            ();
            $this->context->smarty->assign(
                [
                    'content'                   => $this->content,
                    'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                    'page_header_toolbar_title' => $this->page_header_toolbar_title,
                    'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
                ]
            );
        } else {
            $content = '';

            if (Configuration::hasKey('PS_LOGO') && trim(Configuration::get('PS_LOGO')) != ''
                && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO')) && filesize(_PS_IMG_DIR_ . Configuration::get('PS_LOGO'))
            ) {
                list($width, $height, $type, $attr) = getimagesize(_PS_IMG_DIR_ . Configuration::get('PS_LOGO'));
                Configuration::updateValue('SHOP_LOGO_HEIGHT', (int) round($height));
                Configuration::updateValue('SHOP_LOGO_WIDTH', (int) round($width));
            }

            $this->content .= $content;

            parent::initContent();
        }

    }

    /**
     * Render choose theme modules
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws Exception
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 1.9.1.0
     */
    public function renderChooseThemeModule() {

        $theme = new Theme((int) Tools::getValue('id_theme'));

        $xml = $theme->loadConfigFile();

        if ($xml) {
            $themeModule = $this->getModules($xml);

            $toolbarBtn['save'] = [
                'href' => '#',
                'desc' => $this->l('Save'),
            ];

            $toInstall = [];
            $toEnable = [];
            $toDisable = [];

            if (isset($themeModule['to_install'])) {
                $toInstall = $this->formatHelperArray($themeModule['to_install']);
            }

            if (isset($themeModule['to_enable'])) {
                $toEnable = $this->formatHelperArray($themeModule['to_enable']);
            }

            if (isset($themeModule['to_disable'])) {
                $toDisable = $this->formatHelperArray($themeModule['to_disable']);
            }

            $fieldsForm = [
                'form' => [
                    'tinymce'     => false,
                    'legend'      => [
                        'title' => $this->l('Modules to install'),
                        'icon'  => 'icon-picture',
                    ],
                    'description' => $this->l('Themes often include their own modules in order to work properly. This option enables you to choose which modules should be enabled and which should be disabled. If you are unsure of what to do next, just press the "Save" button and proceed to the next step.'),
                    'input'       => [
                        [
                            'type'  => 'shop',
                            'label' => $this->l('Shop association'),
                            'name'  => 'checkBoxShopAsso_theme',
                        ],
                        [
                            'type' => 'hidden',
                            'name' => 'id_theme',
                        ],
                    ],
                    'submit'      => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];

            if (count($toInstall) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to install'),
                    'values' => [
                        'query' => $toInstall,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_install',
                    'expand' => [
                        'print_total' => count($toInstall),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }

            if (count($toEnable) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to enable'),
                    'values' => [
                        'query' => $toEnable,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_enable',
                    'expand' => [
                        'print_total' => count($toEnable),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }

            if (count($toDisable) > 0) {
                $fieldsForm['form']['input'][] = [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Select the theme\'s modules you wish to disable'),
                    'values' => [
                        'query' => $toDisable,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'name'   => 'to_disable',
                    'expand' => [
                        'print_total' => count($toDisable),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                ];
            }

            $shops = [];
            $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
            $tmp['id_shop'] = $shop->id;
            $tmp['id_theme'] = $shop->id_theme;
            $shops[] = $tmp;

            if (Shop::isFeatureActive()) {
                $shops = Shop::getShops();
            }

            $currentShop = $this->context->shop->id;

            foreach ($shops as $shop) {
                $shopTheme = new Theme((int) $shop['id_theme']);

                if ((int) Tools::getValue('id_theme') == (int) $shop['id_theme']) {
                    continue;
                }

                $shopXml = $shopTheme->loadConfigFile();

                if (!$shopXml) {
                    continue;
                }

                $themeShopModule = $this->getModules($shopXml);

                $toShopUninstall = array_merge($themeShopModule['to_install'], $themeShopModule['to_enable']);

                $toShopUninstall = preg_grep('/dash/', $toShopUninstall, PREG_GREP_INVERT);

                $toShopUninstallClean = array_diff($toShopUninstall, $themeModule['to_enable']);

                $toShopUninstallFormated = $this->formatHelperArray($toShopUninstallClean);

                if (count($toShopUninstallFormated) == 0) {
                    continue;
                }

                $class = '';

                if ($shop['id_shop'] == $currentShop) {
                    $themeModule['to_disable_shop' . $shop['id_shop']] = array_merge($themeShopModule['to_install'], $toShopUninstallClean);
                } else {
                    $class = 'hide';
                }

                $fieldsForm['form']['input'][] = [
                    'type'             => 'checkbox',
                    'label'            => sprintf($this->l('Select the modules from the old %1s theme that you wish to disable'), $shopTheme->directory),
                    'form_group_class' => $class,
                    'values'           => [
                        'query' => $toShopUninstallFormated,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'expand'           => [
                        'print_total' => count($toShopUninstallFormated),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ],
                    'name'             => 'to_disable_shop' . $shop['id_shop'],
                ];
            }

            $fieldsValue = $this->formatHelperValuesArray($themeModule);

            $fieldsValue['id_theme'] = (int) Tools::getValue('id_theme');

            $helper = new HelperForm();

            $helper->currentIndex = $this->context->link->getAdminLink('AdminThemes', false) . '&action=ThemeInstall';
            $helper->token = Tools::getAdminTokenLite('AdminThemes');
            $helper->submit_action = '';
            $helper->show_toolbar = true;
            $helper->toolbar_btn = $toolbarBtn;
            $this->fields_value = $fieldsValue;
            $helper->languages = $this->getLanguages();
            $helper->default_form_language = (int) $this->context->language->id;
            $helper->table = 'theme';

            $helper->override_folder = $this->tpl_folder;

            return $helper->generateForm([$fieldsForm]);
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes'));

        return '';
    }

    /**
     * Format helper values array
     *
     * @param array $originArr
     *
     * @return array
     *
     * @since 1.9.1.0
     */
    protected function formatHelperValuesArray($originArr) {

        $fmtArr = [];

        foreach ($originArr as $key => $type) {

            foreach ($type as $module) {
                $fmtArr[$key . '_module' . $module] = true;
            }

        }

        return $fmtArr;
    }

    /**
     * Process theme install
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    public function processThemeInstall() {

        $shopsAsso = $this->context->employee->getAssociatedShops();

        if (Shop::isFeatureActive() && !Tools::getIsset('checkBoxShopAsso_theme') && count($shopsAsso) > 1) {
            $this->errors[] = $this->l('You must choose at least one shop.');
            $this->display = 'ChooseThemeModule';

            return;
        }

        $theme = new Theme((int) Tools::getValue('id_theme'));

        if (count($shopsAsso) == 1) {
            $shops = $shopsAsso;
        } else {
            $shops = [Configuration::get('PS_SHOP_DEFAULT')];

            if (Tools::isSubmit('checkBoxShopAsso_theme')) {
                $shops = Tools::getValue('checkBoxShopAsso_theme');
            }

        }

        $xml = $theme->loadConfigFile();

        if ($xml) {
            $moduleHook = [];

            foreach ($xml->modules->hooks->hook as $row) {
                $name = strval($row['module']);

                $exceptions = (isset($row['exceptions']) ? explode(',', strval($row['exceptions'])) : []);

                $moduleHook[$name]['hook'][] = [
                    'hook'       => strval($row['hook']),
                    'position'   => strval($row['position']),
                    'exceptions' => $exceptions,
                ];
            }

            $this->img_error = $this->updateImages($xml);

            $this->modules_errors = [];

            foreach ($shops as $idShop) {

                foreach ($_POST as $key => $value) {

                    if (strncmp($key, 'to_install', strlen('to_install')) == 0) {
                        $module = Module::getInstanceByName($value);

                        if ($module) {
                            $isInstalledSuccess = true;

                            if (!Module::isInstalled($module->name)) {
                                $isInstalledSuccess = $module->install();
                            }

                            if ($isInstalledSuccess) {

                                if (!Module::isEnabled($module->name)) {
                                    $module->enable();
                                }

                                if ((int) $module->id > 0 && isset($moduleHook[$module->name])) {
                                    $this->hookModule($module->id, $moduleHook[$module->name], $idShop);
                                }

                            } else {
                                $this->modules_errors[] = ['module_name' => $module->name, 'errors' => $module->getErrors()];
                            }

                            unset($moduleHook[$module->name]);
                        }

                    } else

                    if (strncmp($key, 'to_enable', strlen('to_enable')) == 0) {
                        $module = Module::getInstanceByName($value);

                        if ($module) {
                            $isInstalledSuccess = true;

                            if (!Module::isInstalled($module->name)) {
                                $isInstalledSuccess = $module->install();
                            }

                            if ($isInstalledSuccess) {

                                if (!Module::isEnabled($module->name)) {
                                    $module->enable();
                                }

                                if ((int) $module->id > 0 && isset($moduleHook[$module->name])) {
                                    $this->hookModule($module->id, $moduleHook[$module->name], $idShop);
                                }

                            } else {
                                $this->modules_errors[] = ['module_name' => $module->name, 'errors' => $module->getErrors()];
                            }

                            unset($moduleHook[$module->name]);
                        }

                    } else

                    if (strncmp($key, 'to_disable', strlen('to_disable')) == 0) {
                        $keyExploded = explode('_', $key);
                        $idShopModule = (int) substr($keyExploded[2], 4);

                        if ((int) $idShopModule > 0 && $idShopModule != (int) $idShop) {
                            continue;
                        }

                        $moduleObj = Module::getInstanceByName($value);

                        if (Validate::isLoadedObject($moduleObj)) {

                            if (Module::isEnabled($moduleObj->name)) {
                                $moduleObj->disable();
                            }

                            unset($moduleHook[$moduleObj->name]);
                        }

                    }

                }

                $shop = new Shop((int) $idShop);
                $shop->id_theme = (int) Tools::getValue('id_theme');
                $this->context->shop->id_theme = $shop->id_theme;
                $this->context->shop->update();
                $shop->save();

                if (Shop::isFeatureActive()) {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $theme->product_per_page, false, null, (int) $idShop);
                } else {
                    Configuration::updateValue('PS_PRODUCTS_PER_PAGE', (int) $theme->product_per_page);
                }

            }

            $this->doc = [];

            foreach ($xml->docs->doc as $row) {
                $this->doc[strval($row['name'])] = __PS_BASE_URI__ . 'themes/' . $theme->directory . '/docs/' . basename(strval($row['path']));
            }

        }

        Tools::clearCache($this->context->smarty);
        $this->theme_name = $theme->name;
        $this->display = 'view';
    }

    /**
     * Update images
     *
     * @param SimpleXMLElement $xml
     *
     * @return array
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    protected function updateImages($xml) {

        $return = [];

        if (isset($xml->images->image)) {

            foreach ($xml->images->image as $row) {
                Db::getInstance()->delete('image_type', '`name` = \'' . pSQL($row['name']) . '\'');
                Db::getInstance()->execute(
                    '
                    INSERT INTO `' . _DB_PREFIX_ . 'image_type` (`name`, `width`, `height`, `products`, `categories`, `manufacturers`, `suppliers`, `scenes`)
                    VALUES (\'' . pSQL($row['name']) . '\',
                        ' . (int) $row['width'] . ',
                        ' . (int) $row['height'] . ',
                        ' . ($row['products'] == 'true' ? 1 : 0) . ',
                        ' . ($row['categories'] == 'true' ? 1 : 0) . ',
                        ' . ($row['manufacturers'] == 'true' ? 1 : 0) . ',
                        ' . ($row['suppliers'] == 'true' ? 1 : 0) . ',
                        ' . ($row['scenes'] == 'true' ? 1 : 0) . ')'
                );

                $return['ok'][] = [
                    'name'   => strval($row['name']),
                    'width'  => (int) $row['width'],
                    'height' => (int) $row['height'],
                ];
            }

        }

        return $return;
    }

    /**
     * Hook module
     *
     * @param int   $idModule
     * @param array $moduleHooks
     * @param int   $shop
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    protected function hookModule($idModule, $moduleHooks, $shop) {

        Db::getInstance()->execute('INSERT IGNORE INTO ' . _DB_PREFIX_ . 'module_shop (id_module, id_shop) VALUES(' . (int) $idModule . ', ' . (int) $shop . ')');

        Db::getInstance()->execute($sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_module` WHERE `id_module` = ' . (int) $idModule . ' AND id_shop = ' . (int) $shop);

        foreach ($moduleHooks as $hooks) {

            foreach ($hooks as $hook) {
                $idHook = (int) Hook::getIdByName($hook['hook']);
                // Create new hook if module hook is not registered

                if (!$idHook) {
                    $newHook = new Hook();
                    $newHook->name = pSQL($hook['hook']);
                    $newHook->title = pSQL($hook['hook']);
                    $newHook->live_edit = (bool) preg_match('/^display/i', $newHook->name);
                    $newHook->position = (bool) $newHook->live_edit;
                    $newHook->add();
                    $idHook = (int) $newHook->id;
                }

                $sqlHookModule = 'INSERT INTO `' . _DB_PREFIX_ . 'hook_module` (`id_module`, `id_shop`, `id_hook`, `position`)
                                    VALUES (' . (int) $idModule . ', ' . (int) $shop . ', ' . $idHook . ', ' . (int) $hook['position'] . ')';

                if (count($hook['exceptions']) > 0) {

                    foreach ($hook['exceptions'] as $exception) {
                        $sqlHookModuleExcept = 'INSERT INTO `' . _DB_PREFIX_ . 'hook_module_exceptions` (`id_module`, `id_hook`, `file_name`) VALUES (' . (int) $idModule . ', ' . $idHook . ', "' . pSQL($exception) . '")';
                        Db::getInstance()->execute($sqlHookModuleExcept);
                    }

                }

                Db::getInstance()->execute($sqlHookModule);
            }

        }

    }

    /**
     * Render view
     *
     * @return string
     *
     * @throws Exception
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 1.9.1.0
     */
    public function renderView() {

        $this->tpl_view_vars = [
            'doc'            => $this->doc,
            'theme_name'     => $this->theme_name,
            'img_error'      => $this->img_error,
            'modules_errors' => $this->modules_errors,
            'back_link'      => $this->context->link->getAdminLink('AdminThemes'),
            'image_link'     => $this->context->link->getAdminLink('AdminImages'),
        ];

        return parent::renderView();
    }

    /**
     * This functions make checks about AdminThemes configuration edition only.
     *
     * @since 1.4
     */
    public function postProcess() {

        return parent::postProcess();
    }

    /**
     * Update PS_LOGO
     *
     * @since 1.9.1.0
     */
    public function updateOptionPsLogo() {

        $this->updateLogo('PS_LOGO', 'logo');
    }

    /**
     * Generic function which allows logo upload
     *
     * @param string $fieldName
     * @param string $logoPrefix
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    protected function updateLogo($fieldName, $logoPrefix) {

        $idShop = $this->context->shop->id;

        if (isset($_FILES[$fieldName]['tmp_name']) && $_FILES[$fieldName]['tmp_name'] && $_FILES[$fieldName]['size']) {

            if ($error = ImageManager::validateUpload($_FILES[$fieldName], Tools::getMaxUploadSize())) {
                $this->errors[] = $error;

                return false;
            }

            $tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');

            if (!$tmpName || !move_uploaded_file($_FILES[$fieldName]['tmp_name'], $tmpName)) {
                return false;
            }

            $ext = ($fieldName == 'PS_STORES_ICON') ? '.gif' : '.jpg';
            $logoName = str_replace('%', '', urlencode(Tools::link_rewrite($this->context->shop->name))) . '-' . $logoPrefix . '-' . (int) Configuration::get('PS_IMG_UPDATE_TIME') . (int) $idShop . $ext;

            if ($this->context->shop->getContext() == Shop::CONTEXT_ALL || $idShop == 0
                || Shop::isFeatureActive() == false
            ) {
                $logoName = str_replace('%', '', urlencode(Tools::link_rewrite($this->context->shop->name))) . '-' . $logoPrefix . '-' . (int) Configuration::get('PS_IMG_UPDATE_TIME') . $ext;
            }

            if ($fieldName == 'PS_STORES_ICON') {

                if (!@ImageManager::resize($tmpName, _PS_IMG_DIR_ . $logoName, null, null, 'gif', true)) {
                    $this->errors[] = Tools::displayError('An error occurred while attempting to copy your logo.');
                }

            } else {

                if (!@ImageManager::resize($tmpName, _PS_IMG_DIR_ . $logoName)) {
                    $this->errors[] = Tools::displayError('An error occurred while attempting to copy your logo.');
                }

            }

            $idShop = null;
            $idShopGroup = null;

            if (!count($this->errors) && @filemtime(_PS_IMG_DIR_ . Configuration::get($fieldName))) {

                if (Shop::isFeatureActive()) {

                    if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                        $idShop = Shop::getContextShopID();
                        $idShopGroup = Shop::getContextShopGroupID();
                        Shop::setContext(Shop::CONTEXT_ALL);
                        $logoAll = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_GROUP);
                        $logoGroup = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_SHOP);
                        $logoShop = Configuration::get($fieldName);

                        if ($logoAll != $logoShop && $logoGroup != $logoShop && $logoShop != false) {
                            @unlink(_PS_IMG_DIR_ . Configuration::get($fieldName));
                        }

                    } else

                    if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                        $idShopGroup = Shop::getContextShopGroupID();
                        Shop::setContext(Shop::CONTEXT_ALL);
                        $logoAll = Configuration::get($fieldName);
                        Shop::setContext(Shop::CONTEXT_GROUP);

                        if ($logoAll != Configuration::get($fieldName)) {
                            @unlink(_PS_IMG_DIR_ . Configuration::get($fieldName));
                        }

                    }

                } else {
                    @unlink(_PS_IMG_DIR_ . Configuration::get($fieldName));
                }

            }

            Configuration::updateValue($fieldName, $logoName, false, $idShopGroup, $idShop);
            Hook::exec('actionAdminThemesControllerUpdate_optionsAfter');
            @unlink($tmpName);
        }

    }

    /**
     * Update PS_LOGO_MAIL
     *
     * @return void
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function updateOptionPsLogoMail() {

        $this->updateLogo('PS_LOGO_MAIL', 'logo_mail');
    }

    /**
     * Update PS_LOGO_INVOICE
     *
     * @return void
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function updateOptionPsLogoInvoice() {

        $this->updateLogo('PS_LOGO_INVOICE', 'logo_invoice');
    }

    /**
     * Update PS_STORES_ICON
     *
     * @return void
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function updateOptionPsStoresIcon() {

        $this->updateLogo('PS_STORES_ICON', 'logo_stores');
    }

    /**
     * Update PS_FAVICON
     *
     * @return void
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON', _PS_IMG_DIR_ . 'favicon.ico');
        }

        if ($this->uploadIco('PS_FAVICON', _PS_IMG_DIR_ . 'favicon_' . (int) $idShop . '.ico')) {
            Configuration::updateValue('PS_FAVICON', 'favicon_' . (int) $idShop . '.ico');
        }

        Configuration::updateGlobalValue('PS_FAVICON', 'favicon.ico');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        }

    }

    /**
     * Process the favicon sizes
     *
     * @since 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionTbSourceFaviconCode() {

        if (!file_exists(_PS_IMG_DIR_ . 'favicon')) {
            $definedUmask = defined('_EPH_UMASK_') ? _EPH_UMASK_ : 0000;
            $previousUmask = @umask($definedUmask);
            mkdir(_PS_IMG_DIR_ . 'favicon', 0777);
            @umask($previousUmask);
        }

        $idShop = (int) $this->context->shop->id;
        $this->uploadIco('EPH_SOURCE_FAVICON', _PS_IMG_DIR_ . "favicon/favicon_{$idShop}_source.png");

        $newTemplate = Tools::getValue('EPH_SOURCE_FAVICON_CODE');

        // Generate the new header HTML
        $filteredHtml = '';

        // Generate a browserconfig.xml
        $browserConfig = new DOMDocument('1.0', 'UTF-8');
        $main = $browserConfig->createElement('browserconfig');
        $ms = $browserConfig->createElement('msapplication');
        $tile = $browserConfig->createElement('tile');
        $ms->appendChild($tile);
        $main->appendChild($ms);
        $browserConfig->appendChild($main);
        $browserConfig->formatOutput = true;

        // Generate a new manifest.json
        $manifest = [
            'name'             => Configuration::get('PS_SHOP_NAME'),
            'icons'            => [],
            'theme_color'      => '#fad629',
            'background_color' => '#fad629',
            'display'          => 'standalone',
        ];

        // Filter and detect sizes
        $dom = new DOMDocument();
        $dom->loadHTML($newTemplate);
        $links = [];

        foreach ($dom->getElementsByTagName('link') as $elem) {
            $links[] = $elem;
        }

        foreach ($dom->getElementsByTagName('meta') as $elem) {
            $links[] = $elem;
        }

        foreach ($links as $link) {

            foreach ($link->attributes as $attribute) {
                /** @var DOMElement $link */

                if ($favicon = Tools::parseFaviconSizeTag(urldecode($attribute->value))) {
                    ImageManager::resize(
                        _PS_IMG_DIR_ . "favicon/favicon_{$idShop}_source.png",
                        _PS_IMG_DIR_ . "favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png",
                        (int) $favicon['width'],
                        (int) $favicon['height'],
                        'png'
                    );

                    if (in_array("{$favicon['width']}x{$favicon['height']}", [
                        '70x70',
                        '150x150',
                        '310x310',
                        '310x150',
                    ])) {
                        $path = Media::getMediaPath(_PS_IMG_DIR_ . "favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png");
                        $logo = $favicon['width'] == $favicon['height']
                        ? $browserConfig->createElement("square{$favicon['width']}x{$favicon['height']}logo", $path)
                        : $browserConfig->createElement("wide{$favicon['width']}x{$favicon['height']}logo", $path);
                        $tile->appendChild($logo);
                    }

                    $manifest['icons'][] = [
                        'src'   => Media::getMediaPath(_PS_IMG_DIR_ . "favicon/favicon_{$idShop}_{$favicon['width']}_{$favicon['height']}.png"),
                        'sizes' => "{$favicon['width']}x{$favicon['height']}",
                        'type'  => "image/{$favicon['type']}",
                    ];
                }

                if ($link->hasAttribute('name') && $link->getAttribute('name') === 'theme-color') {
                    $manifest['theme_color'] = $link->getAttribute('content');
                }

                if ($link->hasAttribute('name') && $link->getAttribute('name') === 'background-color') {
                    $manifest['background_color'] = $link->getAttribute('content');
                }

            }

            $filteredHtml .= $dom->saveHTML($link);
        }

        file_put_contents(_PS_IMG_DIR_ . "favicon/browserconfig_{$idShop}.xml", $browserConfig->saveXML());
        file_put_contents(_PS_IMG_DIR_ . "favicon/manifest_{$idShop}.json", json_encode($manifest, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));
        Configuration::updateValue('EPH_SOURCE_FAVICON_CODE', nl2br(urldecode($filteredHtml)), true);

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        }

    }

    /**
     * Upload ICO
     *
     * @param string $name
     * @param string $dest
     *

     * @return bool
     *
     * @since 1.9.1.0
     */
    protected function uploadIco($image, $dest) {

        if (isset($image['tmp_name']) && !empty($image['tmp_name'])) {

            if ($error = ImageManager::validateIconUpload($image)) {
                $this->errors[] = $name . ': ' . $error;
            }

            if (mb_substr($dest, -3) === 'ico' && !@file_put_contents($dest, ImageManager::generateFavicon($image))) {
                $this->errors[] = sprintf(Tools::displayError('An error occurred while uploading the favicon: cannot copy file "%s" to folder "%s".'), $image['name'], $dest);
            } else

            if (mb_substr($dest, -3) !== 'ico' && !@copy($image, $dest)) {
                $this->errors[] = sprintf(Tools::displayError('An error occurred while uploading the favicon: cannot copy file "%s" to folder "%s".'), $image['name'], $dest);
            }

        }

        return !count($this->errors);
    }

    /**
     * Update PS_FAVICON_57
     *
     * @return void
     *
     * @since 1.9.1.0
     * @deprecated 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon_57() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_57', _PS_IMG_DIR_ . 'favicon_57.png');
        }

        if ($this->uploadIco('PS_FAVICON_57', _PS_IMG_DIR_ . 'favicon_57-' . (int) $idShop . '.png')) {
            Configuration::updateValue('PS_FAVICON_57', 'favicon_57-' . (int) $idShop . '.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_57', 'favicon_57.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        } else {
            $this->redirect_after = false;
        }

    }

    /**
     * Update PS_FAVICON_72
     *
     * @since 1.9.1.0
     * @deprecated 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon_72() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_72', _PS_IMG_DIR_ . 'favicon_72.png');
        }

        if ($this->uploadIco('PS_FAVICON_72', _PS_IMG_DIR_ . 'favicon_72-' . (int) $idShop . '.png')) {
            Configuration::updateValue('PS_FAVICON_72', 'favicon_72-' . (int) $idShop . '.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_72', 'favicon_72.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        } else {
            $this->redirect_after = false;
        }

    }

    /**
     * Update PS_FAVICON_114
     *
     * @since 1.9.1.0
     * @deprecated 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon_114() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_114', _PS_IMG_DIR_ . 'favicon_114.png');
        }

        if ($this->uploadIco('PS_FAVICON_114', _PS_IMG_DIR_ . 'favicon_114-' . (int) $idShop . '.png')) {
            Configuration::updateValue('PS_FAVICON_114', 'favicon_114-' . (int) $idShop . '.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_114', 'favicon_114.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        } else {
            $this->redirect_after = false;
        }

    }

    /**
     * Update PS_FAVICON_144
     *
     * @since 1.9.1.0
     * @deprecated 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon_144() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_144', _PS_IMG_DIR_ . 'favicon_144.png');
        }

        if ($this->uploadIco('PS_FAVICON_144', _PS_IMG_DIR_ . 'favicon_144-' . (int) $idShop . '.png')) {
            Configuration::updateValue('PS_FAVICON_144', 'favicon_144-' . (int) $idShop . '.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_144', 'favicon_144.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        } else {
            $this->redirect_after = false;
        }

    }

    /**
     * Update PS_FAVICON_192
     *
     * @since 1.9.1.0
     * @deprecated 1.0.4
     * @throws PhenyxShopException
     */
    public function updateOptionPsFavicon_192() {

        $idShop = $this->context->shop->id;

        if ($idShop == Configuration::get('PS_SHOP_DEFAULT')) {
            $this->uploadIco('PS_FAVICON_192', _PS_IMG_DIR_ . 'favicon_192.png');
        }

        if ($this->uploadIco('PS_FAVICON_192', _PS_IMG_DIR_ . 'favicon_192-' . (int) $idShop . '.png')) {
            Configuration::updateValue('PS_FAVICON_192', 'favicon_192-' . (int) $idShop . '.png');
        }

        Configuration::updateGlobalValue('PS_FAVICON_192', 'favicon_192.png');

        if (!$this->errors) {
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        } else {
            $this->redirect_after = false;
        }

    }

    /**
     * Refresh the favicon template
     *
     * @since 1.0.4 to enable the favicon template
     */
    public function ajaxProcessRefreshFaviconTemplate() {

        try {
            $template = (string) (new \GuzzleHttp\Client([
                'verify'  => _PS_TOOL_DIR_ . 'cacert.pem',
                'timeout' => 20,
            ]))->get('https://raw.githubusercontent.com/ephenyx/favicons/master/template.html')->getBody();
        } catch (Exception $e) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'error'    => $e->getMessage(),
            ]));
        }

        if (!$template) {
            $this->ajaxDie(json_encode([
                'hasError' => true,
                'error'    => '',
            ]));
        }

        $this->ajaxDie(json_encode([
            'hasError' => false,
            'template' => base64_encode($template),
            'error'    => '',
        ]));
    }

    /**
     * Update theme for current shop
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    public function updateOptionThemeForShop() {

        if (!$this->can_display_themes) {
            return;
        }

        $idTheme = (int) Tools::getValue('id_theme');

        if ($idTheme && $this->context->shop->id_theme != $idTheme) {
            $this->context->shop->id_theme = $idTheme;
            $this->context->shop->update();
            $this->redirect_after = static::$currentIndex . '&token=' . $this->token;
        }

    }

    /**
     * Initialize processing
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    public function initProcess() {

        if (isset($_GET['error'])) {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }

        if ((isset($_GET['responsive' . $this->table]) || isset($_GET['responsive'])) && Tools::getValue($this->identifier)) {

            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'responsive';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

        } else

        if ((isset($_GET['default_left_column' . $this->table]) || isset($_GET['default_left_column'])) && Tools::getValue($this->identifier)) {

            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'defaultleftcolumn';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

        } else

        if ((isset($_GET['default_right_column' . $this->table]) || isset($_GET['default_right_column'])) && Tools::getValue($this->identifier)) {

            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'defaultrightcolumn';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

        } else

        if (Tools::getIsset('id_theme_meta') && Tools::getIsset('leftmeta')) {

            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'leftmeta';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

        } else

        if (Tools::getIsset('id_theme_meta') && Tools::getIsset('rightmeta')) {

            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'rightmeta';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

        }

        parent::initProcess();
        // This is a composite page, we don't want the "options" display mode

        if ($this->display == 'options' || $this->display == 'list') {
            $this->display = '';
        }

    }

    /**
     * Print responsive icon
     *
     * @param mixed $value
     *
     * @return string
     *
     * @since 1.9.1.0
     */
    public function printResponsiveIcon($value) {

        return ($value ? '<span class="list-action-enable  action-enabled"><i class="icon-check"></i></span>' : '<span class="list-action-enable  action-disabled"><i class="icon-remove"></i></span>');
    }

    /**
     * Process responsive
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function processResponsive() {

        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */

            if ($object->toggleResponsive()) {
                $this->redirect_after = static::$currentIndex . '&conf=5&token=' . $this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating responsive status.');
            }

        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the responsive status for this object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Process default left column
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function processDefaultLeftColumn() {

        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */

            if ($object->toggleDefaultLeftColumn()) {
                $this->redirect_after = static::$currentIndex . '&conf=5&token=' . $this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating default left column status.');
            }

        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the default left column status for this object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Process default right column
     *
     * @return false|ObjectModel|Theme
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    public function processDefaultRightColumn() {

        if (Validate::isLoadedObject($object = $this->loadObject())) {
            /** @var Theme $object */

            if ($object->toggleDefaultRightColumn()) {
                $this->redirect_after = static::$currentIndex . '&conf=5&token=' . $this->token;
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating default right column status.');
            }

        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating the default right column status for this object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
        }

        return $object;
    }

    /**
     * Ajax process left meta
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    public function ajaxProcessLeftMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'left_column' => ['type' => 'sql', 'value' => 'NOT `left_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }

    }

    /**
     * Process left meta
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    public function processLeftMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'left_column' => ['type' => 'sql', 'value' => 'NOT `left_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme_meta')
                    ->where('`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'))
            );

            $this->redirect_after = static::$currentIndex . '&updatetheme&id_theme=' . $idTheme . '&conf=5&token=' . $this->token;
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating this meta.');
        }

    }

    /**
     * Ajax process right meta
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @sicne 1.0.0
     */
    public function ajaxProcessRightMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'right_column' => ['type' => 'sql', 'value' => 'NOT `right_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $this->ajaxDie(json_encode(['success' => 1, 'text' => $this->l('The status has been updated successfully.')]));
        } else {
            $this->ajaxDie(json_encode(['success' => 0, 'text' => $this->l('An error occurred while updating this meta.')]));
        }

    }

    /**
     * Process right meta
     *
     * @return void
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     */
    public function processRightMeta() {

        $result = Db::getInstance()->update(
            'theme_meta',
            [
                'right_column' => ['type' => 'sql', 'value' => 'NOT `right_column`'],
            ],
            '`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'),
            1
        );

        if ($result) {
            $idTheme = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme_meta')
                    ->where('`id_theme_meta` = ' . (int) Tools::getValue('id_theme_meta'))
            );

            $this->redirect_after = static::$currentIndex . '&updatetheme&id_theme=' . $idTheme . '&conf=5&token=' . $this->token;
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating this meta.');
        }

    }

    /**
     * Function used to render the options for this controller
     *
     * @return string
     *
     * @throws Exception
     * @throws HTMLPurifier_Exception
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @throws SmartyException
     * @since 1.9.1.0
     */
    public function renderOptions() {

        if (isset($this->display) && method_exists($this, 'render' . $this->display)) {
            return $this->{'render' . $this->display}

            ();
        }

        $_GET['id_theme'] = $this->context->shop->id_theme;
        $_GET['updatetheme'] = true;

        $this->object = new Theme($this->context->shop->id_theme);

        if ($this->fields_options && is_array($this->fields_options)) {

            $this->tpl_option_vars['controller'] = Tools::getValue('controller');
            $this->context->smarty->assign([
                'tabScript'             => $this->generateTabScript(Tools::getValue('controller')),
                'EPH_HOME_VIDEO_ACTIVE' => Configuration::get('EPH_HOME_VIDEO_ACTIVE'),
                'EPH_HOME_VIDEO_LINK'   => Configuration::get('EPH_HOME_VIDEO_LINK'),
                'importTheme'           => $this->renderImportTheme(),
                'advancedTheme'         => $this->renderForm(),
            ]);
            $helper = new HelperOptions($this);
            $this->setHelperDisplay($helper);
            $helper->toolbar_scroll = true;
            $helper->title = $this->l('Theme appearance');
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

    /**
     * Process update options
     *
     * @return void
     *
     * @since 1.9.1.0
     * @throws PhenyxShopException
     */
    protected function processUpdateOptions() {

        if (!($this->tabAccess['add'] && $this->tabAccess['edit'] && $this->tabAccess['delete']) || _PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('You do not have permission to edit here.');
        } else {
            parent::processUpdateOptions();
        }

        if (!count($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes') . '&conf=6');
        }

    }

    /**
     * Recursive copy
     *
     * @param string $src
     * @param string $dst
     *
     * @return void
     *
     * @since 1.9.1.0
     */
    protected function recurseCopy($src, $dst) {

        if (!$dir = opendir($src)) {
            return;
        }

        if (!file_exists($dst)) {
            mkdir($dst);
        }

        while (($file = readdir($dir)) !== false) {

            if (strncmp($file, '.', 1) != 0) {

                if (is_dir($src . '/' . $file)) {
                    static::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else

                if (is_readable($src . '/' . $file) && $file != 'Thumbs.db' && $file != '.DS_Store' && substr($file, -1) != '~') {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }

            }

        }

        closedir($dir);
    }

    /**
     * Generate a cached thumbnail for object lists (eg. carrier, order statuses...etc)
     *
     * @param string $image        Real image filename
     * @param string $cacheImage   Cached filename
     * @param int    $size         Desired size
     * @param string $imageType    Image type
     * @param bool   $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool   $regenerate   When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @since   1.0.4
     */
    protected function thumbnail($image, $cacheImage, $size, $imageType = 'jpg', $disableCache = true, $regenerate = false) {

        if (!file_exists($image)) {
            return '';
        }

        if (file_exists(_PS_TMP_IMG_DIR_ . $cacheImage) && $regenerate) {
            @unlink(_PS_TMP_IMG_DIR_ . $cacheImage);
        }

        if ($regenerate || !file_exists(_PS_TMP_IMG_DIR_ . $cacheImage)) {
            $infos = getimagesize($image);

            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.

            if (!ImageManager::checkImageMemoryLimit($image)) {
                return false;
            }

            $x = $infos[0];
            $y = $infos[1];
            $maxX = $size * 3;

            // Size is already ok

            if ($y < $size && $x <= $maxX) {
                copy($image, _PS_TMP_IMG_DIR_ . $cacheImage);
            }

            // We need to resize */
            else {
                $ratio_x = $x / ($y / $size);

                if ($ratio_x > $maxX) {
                    $ratio_x = $maxX;
                    $size = $y / ($x / $maxX);
                }

                ImageManager::resize($image, _PS_TMP_IMG_DIR_ . $cacheImage, $ratio_x, $size, $imageType);
            }

        }

        // Relative link will always work, whatever the base uri set in the admin

        if (Context::getContext()->controller->controller_type == 'admin') {
            return '../img/tmp/' . $cacheImage . ($disableCache ? '?time=' . time() : '');
        } else {
            return _PS_TMP_IMG_ . $cacheImage . ($disableCache ? '?time=' . time() : '');
        }

    }

}
