<?php

/**
 * Class AdminAttributesControllerCore
 *
 * @since 1.0.0
 */
class AdminAttributesControllerCore extends AdminController {

    public $bootstrap = true;
    protected $id_attribute;
    protected $position_identifier = 'id_attribute';
    protected $attribute_name;

    /**
     * AdminAttributesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'attribute';
        $this->list_id = 'attribute';
        $this->identifier = 'id_attribute';
        $this->className = 'Attributes';
        $this->lang = true;

        parent::__construct();
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function renderForm() {

        if (!$this->loadObject(true)) {
            return '';
        }

        $attributesGroups = AttributeGroup::getAttributesGroups($this->context->language->id);

        $this->table = 'attribute';
        $this->identifier = 'id_attribute';

        $this->show_form_cancel_button = true;
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Values'),
                'icon'  => 'icon-info-sign',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Attribute group'),
                    'name'     => 'id_attribute_group',
                    'required' => true,
                    'options'  => [
                        'query' => $attributesGroups,
                        'id'    => 'id_attribute_group',
                        'name'  => 'name',
                    ],
                    'hint'     => $this->l('Choose the attribute group for this value.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Value'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            // We get all associated shops for all attribute groups, because we will disable group shops
            // for attributes that the selected attribute group don't support
            $sql = 'SELECT id_attribute_group, id_shop FROM ' . _DB_PREFIX_ . 'attribute_group_shop';
            $associations = [];

            foreach (Db::getInstance()->executeS($sql) as $row) {
                $associations[$row['id_attribute_group']][] = $row['id_shop'];
            }

            $this->fields_form['input'][] = [
                'type'   => 'shop',
                'label'  => $this->l('Shop association'),
                'name'   => 'checkBoxShopAsso',
                'values' => Shop::getTree(),
            ];
        } else {
            $associations = [];
        }

        $this->fields_form['shop_associations'] = json_encode($associations);

        $this->fields_form['input'][] = [
            'type'  => 'color',
            'label' => $this->l('Color'),
            'name'  => 'color',
            'hint'  => $this->l('Choose a color with the color picker, or enter an HTML color (e.g. "lightblue", "#CC6600").'),
        ];

        $this->fields_form['input'][] = [
            'type'  => 'file',
            'label' => $this->l('Texture'),
            'name'  => 'texture',
            'hint'  => [
                $this->l('Upload an image file containing the color texture from your computer.'),
                $this->l('This will override the HTML color!'),
            ],
        ];

        $this->fields_form['input'][] = [
            'type'  => 'current_texture',
            'label' => $this->l('Current texture'),
            'name'  => 'current_texture',
        ];

        $this->fields_form['input'][] = [
            'type' => 'closediv',
            'name' => '',
        ];

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $this->fields_form['buttons'] = [
            'save-and-stay' => [
                'title' => $this->l('Save then add another value'),
                'name'  => 'submitAdd' . $this->table . 'AndStay',
                'type'  => 'submit',
                'class' => 'btn btn-default pull-right',
                'icon'  => 'process-icon-save',
            ],
        ];

        $this->fields_value['id_attribute_group'] = (int) Tools::getValue('id_attribute_group');

        // Override var of Controller
        $this->table = 'attribute';
        $this->className = 'Attributes';
        $this->identifier = 'id_attribute';
        $this->lang = true;
        $this->tpl_folder = 'attributes/';

        // Create object Attribute

        if (!$obj = new Attributes((int) Tools::getValue($this->identifier))) {
            return '';
        }

        $this->fields_value['ajax'] = 1;

        if ($obj->id > 0) {
            $this->fields_value['action'] = 'updateAttribute';
            $this->editObject = 'Edition d‘un Attribut';
        } else {
            $this->fields_value['action'] = 'addAttribute';
            $this->editObject = 'Ajouter un nouvel Attribut';
        }

        $strAttributesGroups = '';

        foreach ($attributesGroups as $attributeGroup) {
            $strAttributesGroups .= '"' . $attributeGroup['id_attribute_group'] . '" : ' . ($attributeGroup['group_type'] == 'color' ? '1' : '0') . ', ';
        }

        $image = '../img/' . $this->fieldImageSettings['dir'] . '/' . (int) $obj->id . '.jpg';

        $this->tpl_form_vars = [
            'strAttributesGroups'      => $strAttributesGroups,
            'colorAttributeProperties' => Validate::isLoadedObject($obj) && $obj->isColorAttribute(),
            'imageTextureExists'       => file_exists(_PS_IMG_DIR_ . $this->fieldImageSettings['dir'] . '/' . (int) $obj->id . '.jpg'),
            'imageTexture'             => $image,
            'imageTextureUrl'          => Tools::safeOutput($_SERVER['REQUEST_URI']) . '&deleteImage=1',
        ];

        return parent::renderForm();
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdateAttributesPositions() {

        $way = (int) Tools::getValue('way');
        $idAttribute = (int) Tools::getValue('id_attribute');
        $positions = Tools::getValue('attribute');

        if (is_array($positions)) {

            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);

                if ((isset($pos[1]) && isset($pos[2])) && (int) $pos[2] === $idAttribute) {

                    if ($attribute = new Attribute((int) $pos[2])) {

                        if (isset($position) && $attribute->updatePosition($way, $position)) {
                            echo 'ok position ' . (int) $position . ' for attribute ' . (int) $pos[2] . '\r\n';
                        } else {
                            echo '{"hasError" : true, "errors" : "Can not update the ' . (int) $idAttribute . ' attribute to position ' . (int) $position . ' "}';
                        }

                    } else {
                        echo '{"hasError" : true, "errors" : "The (' . (int) $idAttribute . ') attribute cannot be loaded"}';
                    }

                    break;
                }

            }

        }

    }

}
