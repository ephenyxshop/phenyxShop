<?php
use \Curl\Curl;

/**
 * Class AdminEmployeeThreadsControllerCore
 *
 * @since 1.8.1.0
 */
class AdminEmployeeThreadsControllerCore extends AdminController {

    public $php_self = 'adminemployeethreads';
    /**
     * AdminEmployeeThreadsControllerCore constructor.
     *
     * @since 1.8.1.0
     */
    public function __construct() {

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'employee_thread';
        $this->className = 'EmployeeThread';
        $this->publicName = $this->l('Ticket CRM');
        $this->lang = false;

        parent::__construct();
        $this->extracss = $this->pushCSS([_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/threads.css']);
    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _PS_JS_DIR_ . 'tickets_crm.js',
        ]);
    }

    public function generateParaGridScript() {

        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
        $this->requestModel = '{
            location: "remote",
            dataType: "json",
            method: "GET",
            recIndx: "id_employee_thread",
            url: AjaxLink' . $this->controller_name . ',
            postData: function () {
                return {
                    action: "getEmployeeThreadRequest",
                    ajax: 1
                };
            },
            getData: function (dataJSON) {
                if (dataJSON && dataJSON.length) {
                    $.each(dataJSON, function( index, value ) {
                        if(value.messages.length)
                        dataJSON[index][\'pq_detail\'] = { \'show\': true };
                    });
                    //dataJSON[0][\'pq_detail\'] = { \'show\': true };
                }
                return { data: dataJSON };
            }


        }';

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'icon'     => '\'ui-icon-disk\'',
                    'label'    => '\'' . $this->l('Ajouter un nouveau ticket') . '\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only pull-right\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
                ],

            ],
        ];
        $this->paramShowTitle = 1;
        $this->paramTitle = '\'' . $this->l('Assistance CRM') . '\'';
        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $this->paramSelectModelType = null;
        $this->detailModel = [
            'cache'        => true,
            'collapseIcon' => '\'ui-icon-triangle-1-e\'',
            'expandIcon'   => '\'ui-icon-triangle-1-se\'',
            'init'         => 'function (ui) {
                var rowData = ui.rowData;
                var model = sub' . $this->className . 'Model(rowData.messages);
                var $grid = $(\'<div id="sub' . $this->className . '"></div>\').pqGrid(model);
                rowData[ \'pq_detail\' ][ \'show\' ] == true;

                return $grid;
            }',
        ];

        $this->subDetailModel = [
            'sub' . $this->className . 'Model' => [
                'dataModel'      => [
                    'recIndx' => '\'subject\'',
                    'data'    => 'data',
                ],
                'colModel'       => "getMessageFields()",
                'rowInit'        => 'function (ui) {
                    return {' . PHP_EOL . '
                        attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="CustomerMessage" data-rowIndx="\' + ui.rowIndx+\'"  data-object="\' + ui.rowData.id_employee_message+\' "\',
                    };
                }',
                'scrollModel'    => [
                    'flexContent' => true,
                ],
                'numberCell'     => [
                    'show' => 0,
                ],
                'height'         => '\'flex\'',
                'showTitle'      => 0,
                'showToolbar'    => 0,
                'showTop'        => 0,
                'showBottom'     => 0,
                'collapsible'    => 0,
                'freezeCols'     => 0,
                'rowBorders'     => 0,
                'selectionModel' => [
                    'type' => '\'row\'',
                ],
            ],
        ];

        return parent::generateParaGridScript();

    }

    public function getEmployeeThreadRequest() {

        $file = fopen("testgetEmployeeThreadRequest.txt", "w");
        $threads = EmployeeThread::getEmployeeMessages();

        foreach ($threads as $key => &$thread) {

            $thread['action'] = '<a class="btn btn-default ui-widget ui-state-default ui-corner-all" onClick="editAjaxObject(\'' . $this->controller_name . '\', ' . $thread['id_employee_thread'] . ')" href="javascript:void(0)" title="">Voir le détail</a>';

            if (Configuration::get('EPH_LICENSE_ID') == $thread['id_of'] && $thread['private'] == 1 && $thread['id_employee'] != $this->context->employee->id) {
                unset($threads[$key]);
            }

        }

        fwrite($file, print_r($threads, true));
        return $threads;

    }

    public function ajaxProcessgetEmployeeThreadRequest() {

        die(Tools::jsonEncode($this->getEmployeeThreadRequest()));

    }

    public function getEmployeeThreadFields() {

        return [
            [
                'title'    => '',
                'maxWidth' => 100,
                'cls'      => 'thread-line',
                'dataIndx' => 'image',
                'dataType' => 'html',
            ],
            [
                'title'     => '',
                'minWidth'  => 27,
                'maxWidth'  => 27,
                'type'      => 'detail',
                'resizable' => false,
                'editable'  => false,
                'sortable'  => false,
                'hidden'    => false,
                'show'      => true,
            ],
            [
                'title'    => $this->l('ID'),
                'width'    => 50,
                'dataIndx' => 'id_employee_thread',
                'dataType' => 'integer',
                'editable' => false,
                'cls'      => 'thread-line',
                'align'    => 'center',
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('Sujet'),
                'width'    => 500,
                'cls'      => 'thread-line quote sujet',
                'dataIndx' => 'subject',
                'dataType' => 'string',
            ],

            [
                'title'    => $this->l('Ouvert par'),
                'width'    => 100,
                'dataIndx' => 'employee',
                'cls'      => 'thread-line ouvert_par',
                'dataType' => 'string',
                'align'    => 'left',
                'editable' => false,
                'hidden'   => false,

            ],

            [
                'dataIndx'   => 'status',
                'dataType'   => 'string',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Status'),
                'width'    => 50,
                'cls'      => 'thread-line status',
                'dataIndx' => 'showStatus',
                'align'    => 'center',
                'dataType' => 'html',
                'editable' => false,
            ],

            [
                'title'    => $this->l('Ajouté le'),
                'minWidth' => 150,
                'exWidth'  => 20,
                'dataIndx' => 'date_add',
                'cls'      => 'thread-line',
                'align'    => 'center',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,
            ],
            [
                'title'    => $this->l('Action'),
                'width'    => 150,
                'dataIndx' => 'action',
                'cls'      => 'thread-line',
                'align'    => 'center',
                'dataType' => 'html',
                'editable' => false,
            ],
        ];

    }

    public function ajaxProcessgetEmployeeThreadFields() {

        die(Tools::jsonEncode($this->getEmployeeThreadFields()));
    }

    public function getMessageFields() {

        return [
            [
                'title'    => '',
                'maxWidth' => 100,
                'dataIndx' => 'image',
                'dataType' => 'html',
            ],

            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 50,
                'dataIndx'   => 'id_employee_message',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'hidden'     => true,
                'hiddenable' => 'no',
            ],

            [
                'title'    => $this->l('Sujet'),
                'width'    => 350,
                'dataIndx' => 'subject',
                'dataType' => 'string',
                'filter'   => [
                    'crules' => [['condition' => "begin"]],

                ],
            ],
            [
                'title'    => $this->l('Envoyé par'),
                'width'    => 200,
                'dataIndx' => 'employee',
                'dataType' => 'string',
                'align'    => 'left',
                'editable' => false,
                'hidden'   => false,
                'filter'   => [
                    'crules' => [['condition' => "begin"]],
                ],

            ],
            [
                'title'    => $this->l('Lu par l‘équipe'),
                'width'    => 100,
                'dataIndx' => 'is_read',
                'dataType' => 'html',
                'align'    => 'center',

            ],

        ];

    }

    public function ajaxProcessGetMessageFields() {

        die(Tools::jsonEncode($this->getMessageFields()));

    }

    public function ajaxProcessAddObject() {

        $targetController = $this->targetController;

        if ($this->tabAccess['add'] == 1) {

            $_GET['add' . $this->table] = "";

            $html = $this->renderForm();

            $li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ouvrir un ticket</a><button type="button" class="close tabdetail" data-id="uperAdd' . $targetController . '"><i class="icon icon-times-circle"></i></button></li>';
            $html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $html . '</div>';

            $result = [
                'li'   => $li,
                'html' => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Votre profile administratif ne vous permet pas d‘ajouter cette objet',
            ];
        }

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessEditObject() {

        $targetController = $this->targetController;

        if ($this->tabAccess['edit'] == 1) {

            $id_employee_thread = Tools::getValue('idObject');
            $_GET[$this->identifier] = $id_employee_thread;
            $_GET['view' . $this->table] = "";
            $thread = EmployeeThread::getEmployeeThreadDetails($id_employee_thread);

            $html = $this->renderView();
            $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Voir le Ticket ' . $thread['subject'] . '</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
            $html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $html . '</div>';

            $result = [
                'success' => true,
                'li'      => $li,
                'html'    => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Votre profile administratif ne vous permet pas d‘éditer cette objet',
            ];
        }

        die(Tools::jsonEncode($result));
    }

    public function renderView() {

        if (!$id_employee_thread = (int) Tools::getValue('idObject')) {
            return '';
        }

        $thread = EmployeeThread::getEmployeeThreadDetails($id_employee_thread);
        $extraJs = [_PS_JS_DIR_ . 'tinymce/tinymce.min.js', _PS_JS_DIR_ . 'tinymce.inc.js',
        ];

        $this->tpl_view_vars = [
            'thread'   => $thread,
            'employee' => $this->context->employee,
            'extraJs'  => $extraJs,
            'iso'      => $this->context->language->iso_code,
            'path_css' => _THEME_CSS_DIR_,
            'ad'       => __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_),
        ];

        return parent::renderView();
    }

    public function renderForm() {

        if (!$this->loadObject(true)) {
            return '';
        }

        $priorities = [
            [
                'id'   => 'Low',
                'name' => $this->l('Basse'),
            ],
            [
                'id'   => 'Medium',
                'name' => $this->l('Modéré'),
            ],
            [
                'id'   => 'High',
                'name' => $this->l('Prioritaire (bloquant)'),
            ],
        ];

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Nouveau ticket'),
                'icon'  => 'icon-envelope-alt',
            ],
            'input'   => [
                [
                    'type' => 'hidden',
                    'name' => 'ajax',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'action',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_license',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_employee',
                ],

                [
                    'type'   => 'radio',
                    'label'  => $this->l('Niveau de priorité'),
                    'name'   => 'thread_priority',
                    'class'  => 't',
                    'values' => [
                        [
                            'id'    => 'Low',
                            'value' => 'Low',
                            'label' => $this->l('Basse'),
                        ],
                        [
                            'id'    => 'Medium',
                            'value' => 'Medium',
                            'label' => $this->l('Medium'),
                        ],
                        [
                            'id'    => 'High',
                            'value' => 'High',
                            'label' => $this->l('Prioritaire (bloquant)'),
                        ],
                    ],
                ],
                [
                    'type'        => 'switch',
                    'label'       => $this->l('Message privé (n‘apparaîtra que pour vous).'),
                    'name'        => 'private',
                    'required'    => false,
                    'is_bool'     => true,
                    'default_val' => 0,
                    'values'      => [
                        [
                            'id'    => 'private_on',
                            'value' => 1,
                            'label' => $this->l('Oui'),
                        ],
                        [
                            'id'    => 'private_off',
                            'value' => 0,
                            'label' => $this->l('Non'),
                        ],
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Sujet'),
                    'name'     => 'subject',
                    'required' => true,
                    'col'      => '4',
                    'desc'     => $this->l('Renseigner un titre bref pour decrire le problème.'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description du problème rencontré'),
                    'name'         => 'message',
                    'autoload_rte' => true,
                ],

            ],
            'submit'  => [
                'title' => $this->l('Save'),
            ],
        ];

        $this->fields_value['id_license'] = Configuration::get('EPH_LICENSE_ID');
        $this->fields_value['id_employee'] = $this->context->employee->id;
        $this->form_action = 'openTicket';
        $this->form_ajax = 1;
        $this->extraJs = [_PS_JS_DIR_ . 'tinymce/tinymce.min.js', _PS_JS_DIR_ . 'tinymce.inc.js',
        ];

        return parent::renderForm();
    }

    public function ajaxProcessopenTicket() {

        $thread = new EmployeeThread();

        foreach ($_POST as $key => $value) {

            if (property_exists($thread, $key) && $key != 'id_employee_thread') {
                $thread->{$key}
                = $value;
            }

        }

        $thread->status = 'open';

        $result = $thread->add();

        if ($result) {
            $tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/pdf/ticket.tpl');
            $tpl->assign([
                'thread'   => $thread,
                'employee' => $this->context->employee,
                'website'  => Configuration::get('PS_SHOP_NAME'),
            ]);
            $postfields = [
                'sender'      => [
                    'name'  => $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                    'email' => Configuration::get('PS_SHOP_EMAIL'),
                ],
                'to'          => [
                    [
                        'name'  => 'Jeff',
                        'email' => 'jeff@ephenyx.com',
                    ],
                ],
                'cc'          => [
                    [
                        'name'  => "Alex",
                        'email' => 'alexandre.cayzac@ephenyx.com',
                    ],
                ],
                'subject'     => 'Demande d‘assistance pour  ' . Configuration::get('PS_SHOP_NAME'),
                "htmlContent" => $tpl->fetch(),
            ];
            Tools::sendEmail($postfields);

            $tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/pdf/ticket_confirmation.tpl');

            $tpl->assign([
                'thread'   => $thread,
                'employee' => $this->context->employee,
                'website'  => Configuration::get('PS_SHOP_NAME'),
            ]);
            $postfields = [
                'sender'      => [
                    'name'  => $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                    'email' => Configuration::get('PS_SHOP_EMAIL'),
                ],
                'to'          => [
                    [
                        'name'  => $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                        'email' => $this->context->employee->email,
                    ],
                ],
                'subject'     => 'Votre demande d‘assistance pour  ' . Configuration::get('PS_SHOP_NAME'),
                "htmlContent" => $tpl->fetch(),
            ];
            Tools::sendEmail($postfields);

            $return = [
                'success' => true,
                'message' => $this->l('La demande d‘assistance a été envoyé avec succès'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('pas glop, ça a merdé !'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessAddAnswer() {

        $id_employee_thread = Tools::getValue('id_employee_thread');
        $thread = EmployeeThread::getEmployeeThreadDetails($id_employee_thread);

        $message = new EmployeeMessage();
        $message->id_license = Configuration::get('EPH_LICENSE_ID');
        $message->id_employee_thread = $id_employee_thread;
        $message->id_employee = $this->context->employee->id;
        $message->subject = Tools::getValue('subject');
        $message->message = Tools::getValue('reply_message');
        $message->read = 0;

        $result = $message->add();

        if ($result) {
            $tpl = $this->context->smarty->createTemplate(_PS_MAIL_DIR_ . '/pdf/answer.tpl');
            $tpl->assign([
                'thread'   => $thread,
                'employee' => $this->context->employee,
                'message'  => $message,
                'website'  => Configuration::get('PS_SHOP_NAME'),
            ]);
            $postfields = [
                'sender'      => [
                    'name'  => $this->context->employee->firstname . ' ' . $this->context->employee->lastname,
                    'email' => Configuration::get('PS_SHOP_EMAIL'),
                ],
                'to'          => [
                    [
                        'name'  => 'Jeff',
                        'email' => 'jeff@ephenyx.com',
                    ],
                ],
                'cc'          => [
                    [
                        'name'  => "Alex",
                        'email' => 'alexandre.cayzac@ephenyx.com',
                    ],
                ],
                'subject'     => 'Réponse posté pour un problème de SAV ' . Configuration::get('PS_SHOP_NAME'),
                "htmlContent" => $tpl->fetch(),
            ];
            Tools::sendEmail($postfields);

            $return = [
                'success' => true,
                'message' => $this->l('La demande d‘assistance a été envoyé avec succès'),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->l('pas glop, ça a merdé !'),
            ];
        }

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessDeleteObject() {

        $idObject = Tools::getValue('idObject');
        $url = 'https://ephenyx.io/ticket';
        $string = Configuration::get('_EPHENYX_LICENSE_KEY_') . '/' . Configuration::get('PS_SHOP_DOMAIN');
        $crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);

        $data_array = [
            'crypto_key' => $crypto_key,
            'action'     => 'deleteTicket',
            'object'     => $idObject,
        ];

        $curl = new Curl();
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setHeader('Content-Type', 'application/json');
        $result = $curl->post($url, json_encode($data_array));

        if ($result) {
            $result = [
                'success' => true,
                'message' => 'La suppression s‘est déroulée avec succès.',
            ];

        } else {
            $result = [
                'success' => false,
                'message' => 'La suppression a rencontré un problème.',
            ];

        }

        die(Tools::jsonEncode($result));
    }

}
