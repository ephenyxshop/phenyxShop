<?php

/**
 * Class AdminAttributesGroupsControllerCore
 *
 * @since 1.0.0
 */
class AdminAttributesGroupsControllerCore extends AdminController {

    public $bootstrap = true;
    protected $id_attribute;
    protected $position_identifier = 'id_attribute_group';
    protected $attribute_name;

    /**
     * AdminAttributesGroupsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'attribute_group';
        $this->list_id = 'attribute_group';
        $this->identifier = 'id_attribute_group';
        $this->className = 'AttributeGroup';
        $this->lang = true;
        $this->_defaultOrderBy = 'position';

        parent::__construct();
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function renderForm() {

        $this->table = 'attribute_group';
        $this->identifier = 'id_attribute_group';

        $groupType = [
            [
                'id'   => 'select',
                'name' => $this->l('Drop-down list'),
            ],
            [
                'id'   => 'radio',
                'name' => $this->l('Radio buttons'),
            ],
            [
                'id'   => 'color',
                'name' => $this->l('Color or texture'),
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Attributes'),
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
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'col'      => '4',
                    'hint'     => $this->l('Your internal name for this attribute.') . '&nbsp;' . $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Public name'),
                    'name'     => 'public_name',
                    'lang'     => true,
                    'required' => true,
                    'col'      => '4',
                    'hint'     => $this->l('The public name for this attribute, displayed to the customers.') . '&nbsp;' . $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Attribute type'),
                    'name'     => 'group_type',
                    'required' => true,
                    'options'  => [
                        'query' => $groupType,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'col'      => '2',
                    'hint'     => $this->l('The way the attribute\'s values will be presented to the customers in the product\'s page.'),
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        if (!($obj = $this->loadObject(true))) {
            return null;
        }

        $this->fields_value['ajax'] = 1;

        if ($obj->id > 0) {
            $this->fields_value['action'] = 'updateAttributeGroup';
            $this->editObject = 'Edition d‘un groupe d‘attribut';
        } else {
            $this->fields_value['action'] = 'addAttributeGroup';
            $this->editObject = 'Ajouter un groupe d‘attribut';
        }

        return parent::renderForm();
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessUpdateGroupsPositions() {

        $way = (int) Tools::getValue('way');
        $idAttributeGroup = (int) Tools::getValue('id_attribute_group');
        $positions = Tools::getValue('attribute_group');

        $newPositions = [];

        foreach ($positions as $k => $v) {

            if (count(explode('_', $v)) == 4) {
                $newPositions[] = $v;
            }

        }

        foreach ($newPositions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $idAttributeGroup) {

                if ($groupAttribute = new AttributeGroup((int) $pos[2])) {

                    if (isset($position) && $groupAttribute->updatePosition($way, $position)) {
                        echo 'ok position ' . (int) $position . ' for attribute group ' . (int) $pos[2] . '\r\n';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update the ' . (int) $idAttributeGroup . ' attribute group to position ' . (int) $position . ' "}';
                    }

                } else {
                    echo '{"hasError" : true, "errors" : "The (' . (int) $idAttributeGroup . ') attribute group cannot be loaded."}';
                }

                break;
            }

        }

    }

}
