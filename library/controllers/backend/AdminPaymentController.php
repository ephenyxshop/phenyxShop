<?php

/**
 * @property Payment $object
 */
class AdminPaymentControllerCore extends AdminController {

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'payment';
        $this->className = 'Payment';
        $this->publicName = $this->l('Réglements');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();

        EmployeeConfiguration::updateValue('EXPERT_PAYMENT_SCRIPT', $this->generateParaGridScript());
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_PAYMENT_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_PAYMENT_SCRIPT', $this->generateParaGridScript());
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_PAYMENT_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_PAYMENT_FIELDS', Tools::jsonEncode($this->getPaymentFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PAYMENT_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_PAYMENT_FIELDS', Tools::jsonEncode($this->getPaymentFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PAYMENT_FIELDS'), true);
        }

    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_JS_DIR_ . 'payment.js',
        ]);
    }

    public function ajaxProcessOpenTargetController() {

        $this->paragridScript = $this->generateParaGridScript();
        $this->setAjaxMedia();

        $data = $this->createTemplate($this->table . '.tpl');

        $sessions = EducationSession::getInvoicedEducationSession();

        $rangeMonths = Tools::getExerciceMonthRange();

        $data->assign([
            'paragridScript'     => $this->paragridScript,
            'manageHeaderFields' => $this->manageHeaderFields,
            'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
            'controller'         => $this->controller_name,
            'tableName'          => $this->table,
            'className'          => $this->className,
            'sessions'           => $sessions,
            'rangeMonths'        => $rangeMonths,
            'link'               => $this->context->link,
            'extraJs'            => $this->push_js_files,
        ]);

        $li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,

            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessinitController() {

        return $this->initGridController();
    }

    public function initContent() {

        $this->displayGrid = true;
        $this->paramGridObj = 'obj' . $this->className;
        $this->paramGridVar = 'grid' . $this->className;
        $this->paramGridId = 'grid_' . $this->controller_name;
        $ajaxlink = $this->context->link->getAdminLink($this->controller_name);

        $rangeMonths = Tools::getExerciceMonthRange();
        $sessions = EducationSession::getInvoicedEducationSession();
        $this->TitleBar = $this->l('Liste des règlements');

        $this->context->smarty->assign([
            'controller'     => Tools::getValue('controller'),
            'tabScript'      => $this->generateTabScript(Tools::getValue('controller')),
            'gridId'         => 'grid_AdminPayment',
            'tableName'      => $this->table,
            'className'      => $this->className,
            'linkController' => $this->context->link->getAdminLink($this->controller_name),
            'AjaxLink'       => $this->context->link->getAdminLink($this->controller_name),
            'paragridScript' => $this->generateParaGridScript(),
            'titleBar'       => $this->TitleBar,
            'rangeMonths'    => $rangeMonths,
            'sessions'       => $sessions,
            'bo_imgdir'      => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
            'idController'   => '',
        ]);

        parent::initContent();

    }

    public function generateParaGridScript() {

        $gridExtraFunction = [
            '



            $("#pieceRegSessionSelect" ).selectmenu({
                width:300,
                classes: {
                    "ui-selectmenu-menu": "scrollable"
                },
                "change": function(event, ui) {
                    gridPayment.filter({
                    mode: \'AND\',
                    rules: [
                        { dataIndx: \'id_education_session\', condition: \'equal\', value: ui.item.value}
                        ]
                        });
                    $("#selectedSessionValue").val(ui.item.value);

                }
            });

            $("#monthSelect" ).selectmenu({
                width:300,

                "change": function(event, ui) {
                    var values = ui.item.value;
                    var res = values.split("|");
                    gridPayment.filter({
                        mode: \'AND\',
                        rules: [
                            { dataIndx: \'date_add\', condition: \'between\', value: res[0], value2:res[1]}
                        ]
                    });
                    if(ui.item.value !== "") {
                        getDataMonth(res[0], res[1]);
                    } else {
                        $(\'#heading-actionAdminPayment\').html("");
                    }

                }

            });







            ',

        ];

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = '800';
        $paragrid->columnBorders = 1;
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $paragrid->showNumberCell = 0;
        $paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'Créer un réglement\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                        addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                    }' . PHP_EOL,
                ],

            ],
        ];
        $paragrid->complete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        var screenHeight = $(window).height() -200;
        console.log(screenHeight);
        grid' . $this->className . '.option( "height", screenHeight );
        }';
        $paragrid->selectionModelType = 'row';
        $paragrid->filterModel = [
            'on'          => true,
            'mode'        => '\'AND\'',
            'header'      => true,
            'menuIcon'    => 0,
            'gridOptions' => [
                'numberCell' => [
                    'show' => 0,
                ],
                'width'      => '\'flex\'',
                'flex'       => [
                    'one' => true,
                ],
            ],
        ];
        $paragrid->groupModel = [
            'on'           => true,
            'grandSummary' => true,
            'header'       => 0,
        ];
        $paragrid->summaryTitle = [
            'sum' => '"Total : {0}"',
        ];
        $paragrid->showTitle = 1;
        $paragrid->title = '""';
        $paragrid->fillHandle = '\'all\'';
        $paragrid->contextMenu = [
            '#grid_' . $this->controller_name => [
                'selector'  => '\'.pq-body-outer .pq-grid-row\'',
                'animation' => [
                    'duration' => 250,
                    'show'     => '\'fadeIn\'',
                    'hide'     => '\'fadeOut\'',
                ],
                'build'     => 'function($triggerElement, e){
                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->className . '.getRowData( {rowIndx: rowIndex} );
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Modifier le ') . '\'+rowData.libelle,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.isBooked ==true) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {

                                editPayment(rowData.id_payment);
                            }
                        },
                    "view": {
                            name: \'' . $this->l('Consulter le ') . '\'+rowData.libelle,
                            icon: "view",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.isBooked ==true) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {

                                viewPayment(rowData.id_payment);
                            }
                        },

                         "book": {
                            name: \'' . $this->l('Comptabiliser le ') . '\'+rowData.libelle,
                            icon: "book",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.isBooked  == 0) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                bookPayment(rowData.id_payment);
                            }
                        },
                        "bulkbook": {
                            name: \'' . $this->l('Comptabiliser les Réglements sélectionnées') . '\',
                            icon: "book",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                var pieceSelected = selgrid' . $this->className . '.getSelection();
                                if(selected < 2) {
                                    return false;
                                }


                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                bookBulkPayment(selgrid' . $this->className . ');
                            }
                        },
                         "viewbook": {
                            name: \'' . $this->l('Voir l‘écriture de règlement du ') . '\'+rowData.libelle,
                            icon: "book",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                if(rowData.id_book_record  == 0) {
                                    return false;
                                }
                                if(rowData.isBooked  == 1) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                viewbookPayment(rowData.id_book_record);
                            }
                        },


                        "sep1": "---------",
                        "select": {
                            name: \'' . $this->l('Tous sélectionner') . '\',
                            icon: "lock",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                selgrid' . $this->className . '.selectAll();
                            }
                        },
                        "unselect": {
                            name: \'' . $this->l('Tous désélectionner') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 2) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                ' . 'grid' . $this->className . '.setSelection( null );
                            }
                        },
                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer la Règlement ') . '\'+rowData.libelle,
                            icon: "list-ul",
                            visible: function(key, opt){
                                if(rowData.isBooked ==true) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                deletePayment(rowData.id_payment);
                            }
                        },

                    },
                };
            }',
            ]];

        $paragrid->gridExtraFunction = $gridExtraFunction;
        $option = $paragrid->generateParaGridOption();
        $script = $paragrid->generateParagridScript();

        $this->paragridScript = $script;
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getPaymentRequest() {

        $payments = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('p.*,  pm.`code`, pml.`name` as `modeName`, ba.`code` as `bankCode`, ba.`bank_name`, case when pd.id_student_piece > 0 then pd.id_student_piece else spp.id_supplier_piece end as id_origin_piece, sp.id_education_session, sp.id_student_education, case when pd.id_student_piece > 0 then sp.piece_number else spp.piece_number end as piece_number, se.id_education, se.id_education_attribute, case when pd.id_student_piece > 0 then CONCAT(st.`firstname`, " ", st.`lastname`) else CONCAT(sa.`firstname`, " ", sa.`lastname`) end  AS `student`, es.name as `dateSession`, es.`session_date` as dateStart, case when p.booked = 1 then \'<div class="orderBook"><i class="icon icon-book" aria-hidden="true"></i></div>\' else \'<div class="orderUnBook"><i class="icon icon-times" aria-hidden="true" style="color:red;"></i></div>\' end as booked, case when p.booked = 1 then 1 else 0 end as isBooked')
                ->from('payment', 'p')
                ->leftJoin('payment_details', 'pd', 'pd.`id_payment` = p.`id_payment`')
                ->leftJoin('payment_mode', 'pm', 'pm.`id_payment_mode` = p.`id_payment_mode`')
                ->leftJoin('payment_mode_lang', 'pml', 'pml.`id_payment_mode` = p.`id_payment_mode` AND pml.`id_lang`  = ' . (int) $this->context->language->id)
                ->leftJoin('bank_account', 'ba', 'ba.`id_bank_account` = pm.`id_bank_account`')
                ->leftJoin('student_pieces', 'sp', 'sp.`id_student_piece` = pd.`id_student_piece`')
                ->leftJoin('supplier_pieces', 'spp', 'spp.`id_supplier_piece` = pd.`id_supplier_piece`')
                ->leftJoin('student_education', 'se', 'se.`id_student_education` = sp.`id_student_education`')
                ->leftJoin('customer', 'st', 'st.`id_customer` = se.`id_customer`')
                ->leftJoin('sale_agent', 'sa', 'sa.`id_sale_agent` = spp.`id_sale_agent`')
                ->leftJoin('education_session', 'es', 'es.`id_education_session` = sp.`id_education_session`')
                ->orderBy('p.`payment_date` DESC')
        );
        $paymentLink = $this->context->link->getAdminLink($this->controller_name);

        foreach ($payments as &$payment) {
            $educations = Education::getEducationDetails($payment['id_education'], $payment['id_education_attribute'], false);

            foreach ($educations as $key => $value) {

                $payment[$key] = $value;
            }

            $payment['piece_number'] = 'FA' . $payment['piece_number'];

            $date = new DateTime($payment['date_add']);
            $payment['libelle'] = 'Réglement du ' . $date->format('d/m/Y');

        }

        return $payments;

    }

    public function ajaxProcessgetPaymentRequest() {

        die(Tools::jsonEncode($this->getPaymentRequest()));

    }

    public function getPaymentFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 50,
                'dataIndx'   => 'id_payment',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'valign'     => 'center',

                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'dateStart',
                'dataType'   => 'date',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'id_book_record',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'date_add',
                'dataType'   => 'date',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],

            [
                'title'    => $this->l('Libellé'),
                'minWidth' => 150,
                'dataIndx' => 'libelle',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Facture'),
                'wdth'     => 100,
                'exWidth'  => 40,
                'dataIndx' => 'piece_number',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'        => $this->l('Montant'),
                'minWidth'     => 150,
                'exWidth'      => 40,
                'dataIndx'     => 'amount',
                'numberFormat' => '#,##0.00_-"€ ' . $this->l('TTC') . '"',
                'valign'       => 'center',
                'dataType'     => 'float',
                'format'       => '# ##0,00 € ' . $this->l('TTC'),
                'summary'      => [
                    'type' => 'sum',
                ],
            ],
            [
                'title'    => $this->l('Mode de Payment'),
                'minWidth' => 200,
                'dataIndx' => 'modeName',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'    => $this->l('Code banque'),
                'maxWidth' => 100,
                'exWidth'  => 40,
                'dataIndx' => 'bankCode',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'    => $this->l('Etudiant'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'student',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'    => $this->l('Formation'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'name',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'id_education_session',
                'dataType'   => 'string',
                'align'      => 'center',
                'valign'     => 'center',
                'hidden'     => true,
                'hiddenable' => 'no',
                'filter'     => [
                    'crules' => [['condition' => "equal"]],
                ],

            ],
            [
                'title'    => $this->l('Session'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'dateSession',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'isBooked',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Comptabilisé'),
                'width'    => 100,
                'exWidth'  => 20,
                'dataIndx' => 'booked',
                'editable' => false,
                'align'    => 'center',
                'valign'   => 'center',
                'halign'   => 'HORIZONTAL_CENTER',
                'cls'      => 'checkValidate',
                'dataType' => 'html',

            ],

        ];

    }

    public function ajaxProcessgetPaymentFields() {

        die(EmployeeConfiguration::get('EXPERT_PAYMENT_FIELDS'));
    }

    public function ajaxProcessGetDataMonth() {

        $dateStart = Tools::getValue('start');
        $dateEnd = Tools::getValue('end');
        $payment = Payment::getTotalPaymentsByRange($dateStart, $dateEnd);
        $date = new DateTime($dateStart);
        $month = Tools::getMonthById($date->format('m'));

        $html = '<p>Total encaissé au mois de ' . $month . ' : ' . number_format($payment, 2, ",", " ") . '</p>';

        $return = [
            'html' => $html,
        ];
        die(Tools::jsonEncode($return));

    }

    public function initPageHeaderToolbar() {

        parent::initPageHeaderToolbar();
    }

    public function renderForm() {

        $this->displayGrid = false;
        $paymentModules = [];
        $paymentModules[] = [
            'id_module' => 0,
            'name'      => $this->l('Select Module'),
        ];

        foreach (PaymentModule::getInstalledPaymentModules() as $pModule) {
            $module = Module::getInstanceById((int) $pModule['id_module']);
            $paymentModules[] = [
                'id_module' => $pModule['id_module'],
                'name'      => $module->displayName,
            ];
        }

        $paymentType = [];

        foreach (PaymentType::getPaymentTypes() as $type) {
            $paymentType[] = [
                'id_payment_type' => $type->id,
                'name'            => $type->name,
            ];
        }

        $bankaccount = [];

        foreach (BankAccount::getBankAccounts() as $bank) {
            $bankaccount[] = [
                'id_bank_account' => $bank->id,
                'name'            => $bank->bank_name,
            ];
        }

        $bookdiary = [];
        $bookdiary[] = [
            'id_book_diary' => 0,
            'name'          => $this->l('Select Associated Diary'),
        ];

        foreach (BookDiary::getBookDiary() as $diary) {
            $bookdiary[] = [
                'id_book_diary' => $diary->id,
                'name'          => $diary->code,
            ];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Payment Mode'),
                'icon'  => 'icon-money',
            ],
            'input'  => [

                [
                    'type'     => 'text',
                    'label'    => $this->l('Payment Name'),
                    'name'     => 'name',
                    'col'      => 3,
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:') . ' <>;=#{}',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Payment code'),
                    'name'     => 'code',
                    'maxchar'  => 8,
                    'col'      => 1,
                    'required' => true,
                    'hint'     => $this->l('4 chars max.'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Relaying Bank:'),
                    'name'    => 'id_bank_account',
                    'col'     => 6,
                    'options' => [
                        'query' => $bankaccount,
                        'id'    => 'id_bank_account',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Diary:'),
                    'name'    => 'id_book_diary',
                    'col'     => 6,
                    'options' => [
                        'query' => $bookdiary,
                        'id'    => 'id_book_diary',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Payment Type:'),
                    'name'    => 'id_payment_type',
                    'col'     => 6,
                    'options' => [
                        'query' => $paymentType,
                        'id'    => 'id_payment_type',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Module payment association:'),
                    'name'    => 'id_module',
                    'col'     => 6,
                    'options' => [
                        'query' => $paymentModules,
                        'id'    => 'id_module',
                        'name'  => 'name',
                    ],
                    'hint'    => $this->l('This optionaly relay on existing payment module.'),
                ],
                [
                    'type'          => 'switch',
                    'label'         => $this->l('Status'),
                    'name'          => 'active',
                    'required'      => false,
                    'is_bool'       => true,
                    'default_value' => 1,
                    'values'        => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],

        ];

        return parent::renderForm();
    }

    public function ajaxProcessBookPayment() {

        $idPayment = Tools::getValue('idPayment');
        $payment = new Payment($idPayment);

        if ($payment->booked == 0) {

            $piece = new StudentPieces($payment->id_piece);
            $studentEducation = new StudentEducation($piece->id_student_education);
            $student = new Customer($piece->id_customer);
            $record = new BookRecords();
            $record->id_book_diary = 2;
            $record->name = "Réglement de la Facture " . $piece->prefix . $piece->piece_number . ' Dossier n°' . $studentEducation->reference_edof . ' ' . $student->lastname . ' ' . $student->firstname;
            $record->piece_type = 'le règlement étudiant';
            $record->date_add = $payment->date_add;
            $success = $record->add();

            if ($success) {

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $payment->payment_account;
                $detail->libelle = "Virement en banque Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->piece_number = $payment->id;
                $detail->debit = $payment->amount;
                $detail->date_add = $record->date_add;
                $detail->add();

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $student->id_stdaccount;
                $detail->libelle = "Règlement de la Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->piece_number = $payment->id;
                $detail->credit = $payment->amount;
                $detail->date_add = $record->date_add;
                $detail->add();

            }

            $payment->id_book_record = $record->id;
            $payment->booked = 1;
            $payment->update();
        }

        $return = [
            'success' => true,
            'message' => 'Le règlement de la Facture ' . $piece->prefix . $piece->piece_number . ' a été comptabilisé avec succès',
        ];
        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessBulkBook() {

        $idpayments = Tools::getValue('idpayments');

        foreach ($idpayments as $idPayment) {

            $payment = new Payment($idPayment);

            if ($payment->booked == 1) {
                continue;
            }

            $piece = new StudentPieces($payment->id_piece);
            $studentEducation = new StudentEducation($piece->id_student_education);
            $student = new Customer($piece->id_customer);
            $record = new BookRecords();
            $record->id_book_diary = 2;
            $record->name = "Réglement de la Facture " . $piece->prefix . $piece->piece_number . ' Dossier n°' . $studentEducation->reference_edof . ' ' . $student->lastname . ' ' . $student->firstname;
            $record->piece_type = 'le règlement étudiant';
            $record->date_add = $payment->date_add;
            $success = $record->add();

            if ($success) {

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $payment->payment_account;
                $detail->libelle = "Virement en banque Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->piece_number = $payment->id;
                $detail->debit = $payment->amount;
                $detail->date_add = $record->date_add;
                $detail->add();

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $student->id_stdaccount;
                $detail->libelle = "Règlement de la Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->piece_number = $payment->id;
                $detail->credit = $payment->amount;
                $detail->date_add = $record->date_add;
                $detail->add();
            }

            $payment->id_book_record = $record->id;
            $payment->booked = 1;
            $payment->update();

        }

        $result = [
            'success' => true,
            'message' => $this->l('Les Réglement ont été comptabilisées avec succès'),
        ];

        die(Tools::jsonEncode($result));
    }

}
