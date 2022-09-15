<?php

class AdminSepaControllerCore extends AdminController {

    public $php_self = 'adminasepa';

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'sepa';
        $this->className = 'Sepa';
        $this->publicName = $this->l('Adresses Clients');
        $this->lang = false;
        $this->identifier = 'id_sepa';
        $this->controller_name = 'AdminSepa';
        $this->context = Context::getContext();

        parent::__construct();

        $this->extracss = $this->pushCSS([
            _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/sepa.css',
        ]);
    }

    public function setAjaxMedia() {

        return $this->pushJS([_EPH_JS_DIR_ . 'sepa.js',
        ]);
    }

    public function ajaxProcessOpenTargetController() {

        $targetController = $this->targetController;
        $data = $this->createTemplate('sepa.tpl');
        $idCompany = Configuration::get('EPH_COMPANY_ID');
        $company = new Company($idCompany);

        $this->paragridScript = $this->generateParaGridScript();

        chdir(_EPH_EXPORT_DIR_);
        $xml_files = glob('*.xml');
        $pdf_files = glob('*.pdf');

        $jsDef = [
            'contextMenu_1'     => $this->l('PDF'),
            'contextMenu_2'     => $this->l('Xml'),
            'contextMenu_3'     => $this->l('Bank Transfered '),
            'success_delete'    => '<i class="fa fa-trash"></i>' . $this->l('File deleted! '),
            'success_valuedate' => '<i class="fa fa-trash"></i>' . $this->l('Execution date update! '),
        ];

        $idSepaBank = Configuration::get('EPH_SEPA_BANK');
        $bank = new BankAccount($idSepaBank);

        $data->assign([
            'paragridScript'      => $this->paragridScript,
            'xml_files'           => $xml_files,
            'pdf_files'           => $pdf_files,
            'controller'          => $this->controller_name,
            'link'                => $this->context->link,
            'extraJs'             => $this->setAjaxMedia(),
            'extracss'            => $this->extracss,
            'total'               => Sepa::getTotal(),
            'company'             => $this->context->company,
            'ics_number'          => Configuration::get('EPH_ICS_NUMBER'),
            'account_regisration' => Configuration::get('EPH_BANK_IBAN'),
            'b_to_b'              => Configuration::get('EPH_B2B_ENABLE'),
            'btob_mode'           => unserialize(Configuration::get('BTOB_MODE')),
            'btob_groups'         => unserialize(Configuration::get('BTOB_GROUPS')),
            'countries'           => BankAccount::getCountries($this->context->language->id),
            'groups'              => Group::getGroups($this->context->language->id),
            'bank'                => $bank,
            'sepas'               => Sepa::getSepa((int) $this->context->language->id),
        ]);

        $li = '<li id="uper' . $targetController . '" data-controller="AdminDashboard"><a href="#content' . $targetController . '">Gestion des Sepas</a><button type="button" class="close tabdetail" data-id="uper' . $targetController . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="content' . $targetController . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,
            'html' => $html,
        ];

        die(Tools::jsonEncode($result));
    }

    public function generateParaGridScript() {

        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $this->paramComplete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 100,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $this->paramTitle = '\'' . $this->l('Liste prélèvement en cours') . '\'';

        $this->paramContextMenu = [
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
                selected = selgrid' . $this->className . '.getSelection().length;
                var dataLenght = grid' . $this->className . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                        "exporter": {
                            name: \'' . $this->l('Générer ce prélèvement de :') . '\'' . '+rowData.customer,
                            icon: "export",
                            visible: function(key, opt) {
                                if (selected > 1) {
                                    return false;
                                }
                                if (rowData.is_export == 1) {
                                    return false;
                                }

                                return true;
                            },

                            items: {

                                "dDev": {
                                    name: "' . $this->l('Format Pdf') . ' ",
                                    icon: "pdf",

                                    callback: function(itemKey, opt, e) {
                                        exportPdf(rowData.id_sepa)
                                    }
                                },
                                "dAcc": {
                                    name: "' . $this->l('Format xml') . ' ",
                                    icon: "edit",
                                    callback: function(itemKey, opt, e) {
                                        exportXml(rowData.id_sepa)
                                    }
                                },


                                }


                        },
                        "delete": {
                            name: \'' . $this->l('Supprimer un prélèvement :') . '\'' . '+rowData.customer,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                              deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un Prélèvement", "Etes vous sure de vouloir supprimer ce prélèvement ?", "Oui", "Annuler",rowData.id_sepa, rowIndex);
                            }
                        },





                    },
                };
            }',
            ]];

        return parent::generateParaGridScript();
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getSepaRequest() {

        $sepas = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('s.id_sepa, s.value_date as value_date, s.export, s.step as statut, b.*, m.*,
            o.date_add as order_date, cl.name as country_name, o.piece_number as reference, o.total_tax_incl,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`, c.company')
                ->from('sepa', 's')
                ->leftJoin('bank_account', 'b', 'b.id_bank_account = s.id_bank')
                ->leftJoin('mandat', 'm', 'm.id_bank = b.id_bank_account')
                ->leftJoin('customer_pieces', 'o', 'o.id_customer_piece = s.id_order')
                ->leftJoin('country_lang', 'cl', 'cl.id_country = b.id_country AND cl.id_lang = ' . (int) $this->context->language->id)
                ->leftJoin('customer', 'c', 'o.id_customer = c.id_customer')
        );

        foreach ($sepas as &$sepa) {

            if ($sepa['export'] == 1) {
                $sepa['is_export'] = 1;
                $sepa['export'] = '<div class="p-active"><i class="fa file-export" aria-hidden="true" style="color:green;"></div>';
            } else {
                $sepa['is_export'] = 0;
                $sepa['export'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
            }

        }

        return $sepas;

    }

    public function ajaxProcessgetSepaRequest() {

        die(Tools::jsonEncode($this->getSepaRequest()));

    }

    public function getSepaFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'width'    => 50,
                'dataIndx' => 'id_sepa',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => 'true',
            ],
            [

                'dataIndx'   => 'id_export',
                'dataType'   => 'integer',
                'hidden'     => 'false',
                'hiddenable' => 'no',
            ],
            [
                'title'    => $this->l('Order Reference:'),
                'minWidth' => 100,
                'dataIndx' => 'reference',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Customer:'),
                'minWidth' => 100,
                'dataIndx' => 'customer',
                'align'    => 'left',
                'valign'   => 'center',
                'dataType' => 'string',

            ],
            [
                'title'    => $this->l('Company:'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'company',
                'dataType' => 'string',
            ],
            [
                'title'     => $this->l('Total:'),
                'dataIndx'  => 'total_tax_incl',
                'dataType'  => 'float',
                'updatable' => true,
                'format'    => "#.###,00 € " . $this->l('Tax incl.'),
            ],
            [
                'title'    => $this->l('Order Date:'),
                'width'    => 150,
                'dataIndx' => 'order_date',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,
            ],
            [
                'title'    => $this->l('Value Date:'),
                'width'    => 150,
                'dataIndx' => 'value_date',
                'cls'      => 'pq-calendar pq-side-icon',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editor'   => [
                    'type'    => "textbox",
                    'init'    => 'dateEditor',
                    'getData' => 'getDataDate',
                ],
                'render'   => 'renderBirthDate',
            ],
            [
                'title'    => $this->l('Bank Name:'),
                'width'    => 150,
                'dataIndx' => 'bank_name',
                'dataType' => 'string',
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('IBAN:'),
                'width'    => 150,
                'dataIndx' => 'iban',
                'dataType' => 'string',
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('SWIFT/BIC Code:'),
                'width'    => 150,
                'dataIndx' => 'swift',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Sepa Mandate:'),
                'width'    => 150,
                'dataIndx' => 'mandat_sepa',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Mandate Status:'),
                'width'    => 150,
                'dataIndx' => 'statut',
                'dataType' => 'string',
            ],

            [
                'title'    => $this->l('Export:'),
                'minWidth' => 100,
                'dataIndx' => 'export',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],

        ];

    }

    public function ajaxProcessgetSepaFields() {

        die(Tools::jsonEncode($this->getSepaFields()));
    }

    public function ajaxProcessDeleteMandat() {

        Db::getInstance()->Execute('
            UPDATE `' . _DB_PREFIX_ . 'mandat`
            SET `active` = 0
            WHERE `id_mandat`= ' . (int) Tools::getValue('id_mandat'));

        die(true);

    }

    public function ajaxProcessAddMandat() {

        $mandat_type = Tools::getValue('mandat_type');

        if ($mandat_type == 'Unique') {
            $step = 'OOFF';
        } else

        if ($mandat_type == 'Recurent') {
            $step = 'FRST';
        }

        $id_bank = (int) Tools::getValue('id_bank');
        $is_mandate = Mandat::getMandatIdbyBankId($id_bank);

        if (!empty($is_mandate)) {
            $mandat = new Mandat($is_mandate);
        } else {
            $mandat = new Mandat();
        }

        $mandat->id_bank = $id_bank;
        $mandat->mandat_sepa = '+' . CustomerPieces::generateReference();
        $mandat->mandat_type = $mandat_type;
        $mandat->step = $step;
        $mandat->IP_Registration = $_SERVER['REMOTE_ADDR'];

        if (!empty($is_mandate)) {
            $mandat->date_add = date("Y-m-d H:i:s");
            $mandat->active = 1;
            $mandat->update();
        } else {
            $mandat->add(true);
        }

        die(true);

    }

    public function ajaxProcessUpdateValueDate() {

        $new_date = Tools::getValue('new_date');
        $id_sepa = Tools::getValue('id_sepa');

        if (Db::getInstance()->update('sepa', ['value_date' => pSql($new_date)], '`id_sepa`=' . (int) $id_sepa)) {
            die(true);
        }

    }

    public function ajaxProcessAddBankMandat() {

        $account = Tools::getValue('iban');
        $bank_name = Tools::getValue('bank_name');
        $swift = Tools::getValue('swift');
        $mandat_type = Tools::getValue('mandat_type');

        if ($mandat_type == 'Unique') {
            $step = 'OOFF';
        } else

        if ($mandat_type == 'Recurent') {
            $step = 'FRST';
        }

        if (empty($account)) {
            $account = Tools::getValue('bban');
        }

        if (empty($account)) {
            die(false);
        }

        if (empty($account)) {
            return false;
        } else

        if (empty($bank_name)) {
            die(false);
        } else

        if (empty($swift)) {
            die(false);
        } else {
            $bank = new BankAccount();
            $bank->id_customer = (int) Tools::getValue('id_customer');
            $bank->id_country = (int) Tools::getValue('country');
            $bank->bank_name = Tools::getValue('bank_name');
            $bank->iban = Tools::getValue('iban');
            $bank->swift = Tools::getValue('swift');
            $bank->bban = Tools::getValue('bban');
            $bank->add(true);

            $mandat = new Mandat();
            $mandat->id_bank = $bank->id;
            $mandat->mandat_sepa = Tools::getValue('mandat');
            $mandat->mandat_type = $mandat_type;
            $mandat->step = $step;
            $mandat->IP_Registration = $_SERVER['REMOTE_ADDR'];
            $mandat->add(true);

            die(true);

        }

    }

    public function ajaxProcessDeleteFile() {

        unlink(_EPH_EXPORT_DIR_ . Tools::getValue('file'));

    }

    public function ajaxProcessTransfertSepa() {

        $action = Tools::getValue('value');
        $step = Tools::getValue('step');

        if ($step == 'FRST') {
            $sepa = new Sepa((int) Tools::getValue('id_sepa'));
            Db::getInstance()->update('mandat', ['step' => 'RCUR', 'date_execution' => date('Y-m-d H:i:s')], '`id_bank`=' . (int) $sepa->id_bank);
        }

        if (Db::getInstance()->update('sepa', ['export' => $action, 'date_transfered' => date('Y-m-d H:i:s')], '`id_sepa`=' . (int) Tools::getValue('id_sepa'))) {
            die(true);
        }

    }

    public function ajaxProcessExportAllXml() {

        $fp = fopen(_EPH_EXPORT_DIR_ . "Pending_Sepa.xml", "w");
        $CtrlSum = Sepa::getTotal();
        $sepas = Sepa::getSepaExport();
        $NbOfTxs = count($sepas);
        $CreDtTm = str_replace(' ', 'T', date('Y-m-d h:i:s'));
        $MsgId = date('d-m-Y h:i:s') . ' ' . Configuration::get('SEPA_COMPANY_NAME');
        $Nm = Configuration::get('SEPA_COMPANY_NAME');
        $this->context = Context::getContext();
        $xml_header = '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        $xml_header .= '<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">' . "\r\n";
        $xml_header .= '<CstmrDrctDbtInitn>' . "\r\n";
        $xml_header .= '<GrpHdr>' . "\r\n";
        $xml_header .= '<MsgId>' . $MsgId . '</MsgId>' . "\r\n";
        $xml_header .= '<CreDtTm>' . $CreDtTm . '</CreDtTm>' . "\r\n";
        $xml_header .= '<NbOfTxs>' . $NbOfTxs . '</NbOfTxs>' . "\r\n";
        $xml_header .= '<CtrlSum>' . $CtrlSum . '</CtrlSum>' . "\r\n";
        $xml_header .= '<InitgPty>' . "\r\n";
        $xml_header .= '<Nm>' . $Nm . '</Nm>' . "\r\n";
        $xml_header .= '</InitgPty>' . "\r\n";
        $xml_header .= '</GrpHdr>' . "\r\n";
        fwrite($fp, $xml_header);
        $IBAN = Configuration::get('SEPA_COMPANY_IBAN');
        $BIC = Configuration::get('SEPA_COMPANY_BIC');
        $Id = Configuration::get('EPH_ICS_NUMBER');

        foreach ($sepas as $sepa) {
            $sepa = new Sepa($sepa['id_sepa']);
            $bank = new Bankaccount($sepa->id_bank);
            $id_mandat = Mandat::getMandatIdbyBankId($bank->id);
            $mandat = new Mandat($id_mandat);
            $order = new CustomerPieces((int) $sepa->id_order);
            $customer = new Customer($order->id_customer);
            $PmtInfId = $customer->firstname . ' ' . $customer->lastname;
            $CtrlSum = number_format($order->total_tax_incl, 2);

            $date = new DateTime($sepa->date_add);
            $date_time = $date->format('d-m-Y H:i:s');
            $date = $date->format('Y-m-d');
            $value_date = new DateTime($sepa->value_date);
            $value_date = $value_date->format('Y-m-d');
            $SeqTp = $sepa->step;

            if ($SeqTp == 'RCUR' && $mandat->step == 'FRST') {
                $value_date = strtotime(date("Y-m-d", strtotime($value_date)) . " +7 day");
                $value_date = date('Y-m-d', $value_date);
            }

            $ReqdColltnDt = $value_date;
            $Ustrd = $this->l('Invoice : ') . $order->piece_number . $this->l(' placed on ') . $date_time;
            $MndtId = $mandat->mandat_sepa;

            $date_mandat = new DateTime($mandat->date_add);
            $date_mandat = $date_mandat->format('Y-m-d');
            $DtOfSgntr = $date_mandat;
            $Customer_Bic = $bank->swift;
            $Customer_Iban = $bank->iban;

            $xml = '<PmtInf>' . "\r\n";
            $xml .= '<PmtInfId>' . $PmtInfId . '</PmtInfId>' . "\r\n";
            $xml .= '<PmtMtd>DD</PmtMtd>' . "\r\n";
            $xml .= '<NbOfTxs>1</NbOfTxs>' . "\r\n";
            $xml .= '<CtrlSum>' . $CtrlSum . '</CtrlSum>' . "\r\n";
            $xml .= '<PmtTpInf>' . "\r\n";
            $xml .= '<SvcLvl>' . "\r\n";
            $xml .= '<Cd>SEPA</Cd>' . "\r\n";
            $xml .= '</SvcLvl>' . "\r\n";
            $xml .= '<LclInstrm>' . "\r\n";
            $xml .= '<Cd>CORE</Cd>' . "\r\n";
            $xml .= '</LclInstrm>' . "\r\n";
            $xml .= '<SeqTp>' . $SeqTp . '</SeqTp>' . "\r\n";
            $xml .= '</PmtTpInf>' . "\r\n";
            $xml .= '<ReqdColltnDt>' . $ReqdColltnDt . '</ReqdColltnDt>' . "\r\n";
            $xml .= '<Cdtr>' . "\r\n";
            $xml .= '<Nm>' . $Nm . '</Nm>' . "\r\n";
            $xml .= '</Cdtr>' . "\r\n";
            $xml .= '<CdtrAcct>' . "\r\n";
            $xml .= '<Id>' . "\r\n";
            $xml .= '<IBAN>' . $IBAN . '</IBAN>' . "\r\n";
            $xml .= '</Id>' . "\r\n";
            $xml .= '</CdtrAcct>' . "\r\n";
            $xml .= '<CdtrAgt>' . "\r\n";
            $xml .= '<FinInstnId>' . "\r\n";
            $xml .= '<BIC>' . $BIC . '</BIC>' . "\r\n";
            $xml .= '</FinInstnId>' . "\r\n";
            $xml .= '</CdtrAgt>' . "\r\n";
            $xml .= '<ChrgBr>SLEV</ChrgBr>' . "\r\n";
            $xml .= '<CdtrSchmeId>' . "\r\n";
            $xml .= '<Id>' . "\r\n";
            $xml .= '<PrvtId>' . "\r\n";
            $xml .= '<Othr>' . "\r\n";
            $xml .= '<Id>' . $Id . '</Id>' . "\r\n";
            $xml .= '<SchmeNm>' . "\r\n";
            $xml .= '<Prtry>SEPA</Prtry>' . "\r\n";
            $xml .= '</SchmeNm>' . "\r\n";
            $xml .= '</Othr>' . "\r\n";
            $xml .= '</PrvtId>' . "\r\n";
            $xml .= '</Id>' . "\r\n";
            $xml .= '</CdtrSchmeId>' . "\r\n";
            $xml .= '<DrctDbtTxInf>' . "\r\n";
            $xml .= '<PmtId>' . "\r\n";
            $xml .= '<EndToEndId>' . $PmtInfId . '</EndToEndId>' . "\r\n";
            $xml .= '</PmtId>' . "\r\n";
            $xml .= '<InstdAmt Ccy="EUR">' . $CtrlSum . '</InstdAmt>' . "\r\n";
            $xml .= '<DrctDbtTx>' . "\r\n";
            $xml .= '<MndtRltdInf>' . "\r\n";
            $xml .= '<MndtId>' . $MndtId . '</MndtId>' . "\r\n";
            $xml .= '<DtOfSgntr>' . $DtOfSgntr . '</DtOfSgntr>' . "\r\n";
            $xml .= '<AmdmntInd>false</AmdmntInd>' . "\r\n";
            $xml .= '</MndtRltdInf>' . "\r\n";
            $xml .= '</DrctDbtTx>' . "\r\n";
            $xml .= '<DbtrAgt>' . "\r\n";
            $xml .= '<FinInstnId>' . "\r\n";
            $xml .= '<BIC>' . $Customer_Bic . '</BIC>' . "\r\n";
            $xml .= '</FinInstnId>' . "\r\n";
            $xml .= '</DbtrAgt>' . "\r\n";
            $xml .= '<Dbtr>' . "\r\n";
            $xml .= '<Nm>' . $PmtInfId . '</Nm>' . "\r\n";
            $xml .= '</Dbtr>' . "\r\n";
            $xml .= '<DbtrAcct>' . "\r\n";
            $xml .= '<Id>' . "\r\n";
            $xml .= '<IBAN>' . $Customer_Iban . '</IBAN>' . "\r\n";
            $xml .= '</Id>' . "\r\n";
            $xml .= '</DbtrAcct>' . "\r\n";
            $xml .= '<RmtInf>' . "\r\n";
            $xml .= '<Ustrd>' . $Ustrd . '</Ustrd>' . "\r\n";
            $xml .= '</RmtInf>' . "\r\n";
            $xml .= '</DrctDbtTxInf>' . "\r\n";
            $xml .= '</PmtInf>' . "\r\n";

            fwrite($fp, $xml);
        }

        $xml_footer = '';
        $xml_footer = '</CstmrDrctDbtInitn>' . "\r\n";
        $xml_footer .= '</Document>' . "\r\n";
        fwrite($fp, $xml_footer);
        fclose($fp);

        $response = [
            'fileExport' => '../export' . DIRECTORY_SEPARATOR . "Pending_Sepa.xml",
        ];
        die(Tools::jsonEncode($response));

    }

    public function ajaxProcessExportXml() {

        $xml = '';
        $this->context = Context::getContext();
        $sepa = new Sepa(Tools::getValue('id_sepa'));
        $order = new CustomerPieces((int) $sepa->id_order);
        $bank = new BankAccount($sepa->id_bank);
        $id_mandat = Mandat::getMandatIdbyBankId($bank->id);
        $mandat = new Mandat($id_mandat);
        $customer = new Customer($order->id_customer);
        $fp = fopen(_EPH_EXPORT_DIR_ . $order->piece_number . ".xml", "w");
        $date = new DateTime($sepa->date_add);
        $value_date = new DateTime($sepa->value_date);
        $value_date = $value_date->format('Y-m-d');
        $date_time = $date->format('d-m-Y H:i:s');
        $date = $date->format('Y-m-d');
        $date_mandat = new DateTime($mandat->date_add);
        $date_mandat = $date_mandat->format('Y-m-d');
        $MsgId = $date_time . ' ' . Configuration::get('SEPA_COMPANY_NAME');
        $CreDtTm = str_replace(' ', 'T', $sepa->date_add);
        $CtrlSum = number_format($order->total_tax_incl, 2);
        $Nm = Configuration::get('SEPA_COMPANY_NAME');
        $PmtInfId = $customer->firstname . ' ' . $customer->lastname;
        $SeqTp = $sepa->step;

        if ($SeqTp == 'RCUR' && $mandat->step == 'FRST') {
            $value_date = strtotime(date("Y-m-d", strtotime($value_date)) . " +7 day");
            $value_date = date('Y-m-d', $value_date);
        }

        $ReqdColltnDt = $value_date;
        $Ustrd = $this->l('Invoice : ') . $order->piece_number . $this->l(' placed on ') . $date_time;
        $IBAN = Configuration::get('SEPA_COMPANY_IBAN');
        $BIC = Configuration::get('SEPA_COMPANY_BIC');
        $Id = Configuration::get('EPH_ICS_NUMBER');
        $MndtId = $mandat->mandat_sepa;
        $DtOfSgntr = $date_mandat;
        $Customer_Bic = $bank->swift;
        $Customer_Iban = $bank->iban;

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
        $xml .= '<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">' . "\r\n";
        $xml .= '<CstmrDrctDbtInitn>' . "\r\n";
        $xml .= '<GrpHdr>' . "\r\n";
        $xml .= '<MsgId>' . $MsgId . '</MsgId>' . "\r\n";
        $xml .= '<CreDtTm>' . $CreDtTm . '</CreDtTm>' . "\r\n";
        $xml .= '<NbOfTxs>1</NbOfTxs>' . "\r\n";
        $xml .= '<CtrlSum>' . $CtrlSum . '</CtrlSum>' . "\r\n";
        $xml .= '<InitgPty>' . "\r\n";
        $xml .= '<Nm>' . $Nm . '</Nm>' . "\r\n";
        $xml .= '</InitgPty>' . "\r\n";
        $xml .= '</GrpHdr>' . "\r\n";
        $xml .= '<PmtInf>' . "\r\n";
        $xml .= '<PmtInfId>' . $PmtInfId . '</PmtInfId>' . "\r\n";
        $xml .= '<PmtMtd>DD</PmtMtd>' . "\r\n";
        $xml .= '<NbOfTxs>1</NbOfTxs>' . "\r\n";
        $xml .= '<CtrlSum>' . $CtrlSum . '</CtrlSum>' . "\r\n";
        $xml .= '<PmtTpInf>' . "\r\n";
        $xml .= '<SvcLvl>' . "\r\n";
        $xml .= '<Cd>SEPA</Cd>' . "\r\n";
        $xml .= '</SvcLvl>' . "\r\n";
        $xml .= '<LclInstrm>' . "\r\n";
        $xml .= '<Cd>CORE</Cd>' . "\r\n";
        $xml .= '</LclInstrm>' . "\r\n";
        $xml .= '<SeqTp>' . $SeqTp . '</SeqTp>' . "\r\n";
        $xml .= '</PmtTpInf>' . "\r\n";
        $xml .= '<ReqdColltnDt>' . $ReqdColltnDt . '</ReqdColltnDt>' . "\r\n";
        $xml .= '<Cdtr>' . "\r\n";
        $xml .= '<Nm>' . $Nm . '</Nm>' . "\r\n";
        $xml .= '</Cdtr>' . "\r\n";
        $xml .= '<CdtrAcct>' . "\r\n";
        $xml .= '<Id>' . "\r\n";
        $xml .= '<IBAN>' . $IBAN . '</IBAN>' . "\r\n";
        $xml .= '</Id>' . "\r\n";
        $xml .= '</CdtrAcct>' . "\r\n";
        $xml .= '<CdtrAgt>' . "\r\n";
        $xml .= '<FinInstnId>' . "\r\n";
        $xml .= '<BIC>' . $BIC . '</BIC>' . "\r\n";
        $xml .= '</FinInstnId>' . "\r\n";
        $xml .= '</CdtrAgt>' . "\r\n";
        $xml .= '<ChrgBr>SLEV</ChrgBr>' . "\r\n";
        $xml .= '<CdtrSchmeId>' . "\r\n";
        $xml .= '<Id>' . "\r\n";
        $xml .= '<PrvtId>' . "\r\n";
        $xml .= '<Othr>' . "\r\n";
        $xml .= '<Id>' . $Id . '</Id>' . "\r\n";
        $xml .= '<SchmeNm>' . "\r\n";
        $xml .= '<Prtry>SEPA</Prtry>' . "\r\n";
        $xml .= '</SchmeNm>' . "\r\n";
        $xml .= '</Othr>' . "\r\n";
        $xml .= '</PrvtId>' . "\r\n";
        $xml .= '</Id>' . "\r\n";
        $xml .= '</CdtrSchmeId>' . "\r\n";
        $xml .= '<DrctDbtTxInf>' . "\r\n";
        $xml .= '<PmtId>' . "\r\n";
        $xml .= '<EndToEndId>' . $PmtInfId . '</EndToEndId>' . "\r\n";
        $xml .= '</PmtId>' . "\r\n";
        $xml .= '<InstdAmt Ccy="EUR">' . $CtrlSum . '</InstdAmt>' . "\r\n";
        $xml .= '<DrctDbtTx>' . "\r\n";
        $xml .= '<MndtRltdInf>' . "\r\n";
        $xml .= '<MndtId>' . $MndtId . '</MndtId>' . "\r\n";
        $xml .= '<DtOfSgntr>' . $DtOfSgntr . '</DtOfSgntr>' . "\r\n";
        $xml .= '<AmdmntInd>false</AmdmntInd>' . "\r\n";
        $xml .= '</MndtRltdInf>' . "\r\n";
        $xml .= '</DrctDbtTx>' . "\r\n";
        $xml .= '<DbtrAgt>' . "\r\n";
        $xml .= '<FinInstnId>' . "\r\n";
        $xml .= '<BIC>' . $Customer_Bic . '</BIC>' . "\r\n";
        $xml .= '</FinInstnId>' . "\r\n";
        $xml .= '</DbtrAgt>' . "\r\n";
        $xml .= '<Dbtr>' . "\r\n";
        $xml .= '<Nm>' . $PmtInfId . '</Nm>' . "\r\n";
        $xml .= '</Dbtr>' . "\r\n";
        $xml .= '<DbtrAcct>' . "\r\n";
        $xml .= '<Id>' . "\r\n";
        $xml .= '<IBAN>' . $Customer_Iban . '</IBAN>' . "\r\n";
        $xml .= '</Id>' . "\r\n";
        $xml .= '</DbtrAcct>' . "\r\n";
        $xml .= '<RmtInf>' . "\r\n";
        $xml .= '<Ustrd>' . $Ustrd . '</Ustrd>' . "\r\n";
        $xml .= '</RmtInf>' . "\r\n";
        $xml .= '</DrctDbtTxInf>' . "\r\n";
        $xml .= '</PmtInf>' . "\r\n";
        $xml .= '</CstmrDrctDbtInitn>' . "\r\n";
        $xml .= '</Document>' . "\r\n";

        fwrite($fp, $xml);
        fclose($fp);
        $response = [
            'fileExport' => $this->context->link->getBaseLink() . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . $order->piece_number . ".xml",
        ];
        die(Tools::jsonEncode($response));

    }

    public function ajaxProcessExportAllPdf() {

        $this->context = Context::getContext();

        $this->context->smarty->assign([
            'title'                => 'Export SEPA',
            'logo_path'            => _EPH_ROOT_DIR_ . '/img/' . Configuration::get('EPH_LOGO', null, null, $this->context->company->id),
            'total_sepas'          => Sepa::getTotal(),
            'date'                 => date('d-m-Y'),
            'sepas'                => Sepa::getSepa((int) $this->context->language->id, true),
            'ICSNumber'            => Configuration::get('EPH_ICS_NUMBER'),
            'SEPA_COMPANY_NAME'    => Configuration::get('SEPA_COMPANY_NAME'),
            'SEPA_COMPANY_ADDRESS' => nl2br(Configuration::get('SEPA_COMPANY_ADDRESS')),
            'SEPA_COMPANY_IBAN'    => Configuration::get('SEPA_IBAN'),
            'SEPA_COMPANY_BIC'     => Configuration::get('SEPA_COMPANY_BIC'),
        ]);

        $pdf = new MyPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setPrintHeader(false);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();
        $html = $this->context->smarty->fetch(_EPH_MODULE_DIR_ . 'ephsepa/views/templates/admin/pdf/pdf_sepas.tpl');
        $pdf->writeHTML($html, false);
        $mail_vars = [
            '{total_sepas}' => Sepa::getTotal(),
            '{detail}'      => $html,
        ];
        $file_attachement = [];
        $file_attachement['content'] = $pdf->Output('Pending_SEPA.pdf', 'S');
        $file_attachement['name'] = 'Pending SEPA';
        $file_attachement['mime'] = 'application/pdf';
        $pdf->Output(_EPH_EXPORT_DIR_ . 'Pending_SEPA.pdf', 'F');
        $email = Configuration::get('EPH_SHOP_EMAIL');

        if (Mail::Send(
            (int) $this->context->language->id,
            'admin_sepa',
            Mail::l('SEPA Direct Debit Mandate', (int) $this->context->language->id),
            $mail_vars,
            $email,
            'Pending sepa',
            null,
            null,
            $file_attachement,
            null,
            _EPH_SEPA_MAIL_DIR_,
            false,
            (int) $this->context->company->id
        )) {
            $json = [
                'Email'  => $email,
                'return' => $this->l('PDF File has been send to: '),
            ];
            die(Tools::jsonEncode($json));
        }

    }

    public function ajaxProcessExportPdf() {

        $this->context = Context::getContext();
        $sepa = new Sepa(Tools::getValue('id_sepa'));
        $order = new CustomerPieces((int) $sepa->id_order);
        $bank = new BankAccount($sepa->id_bank);
        $id_mandat = Mandat::getMandatIdbyBankId($bank->id);
        $mandat = new Mandat($id_mandat);
        $customer = new Customer($order->id_customer);
        $adresse = Address::getFirstCustomerAddressId((int) ($customer->id));
        $address = new Address($adresse);
        $idCompany = Configuration::get('EPH_COMPANY_ID');
        $company = new Company($idCompany);

        $pathLogo = $this->getLogo();

        $width = 0;
        $height = 0;

        if (!empty($pathLogo)) {
            list($width, $height) = getimagesize($pathLogo);
        }

        // Limit the height of the logo for the PDF render
        $maximumHeight = 150;

        if ($height > $maximumHeight) {
            $ratio = $maximumHeight / $height;
            $height *= $ratio;
            $width *= $ratio;
        }

        $mpdf = new \Mpdf\Mpdf([
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 120,
            'margin_bottom' => 75,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $data = $this->context->smarty->createTemplate(_EPH_PDF_DIR_ . 'header.tpl', $this->context->smarty);
        $data->assign(
            [
                'company'        => $company,
                'logo_path'      => $pathLogo,
                'width_logo'     => $width,
                'height_logo'    => $height,
                'productDetails' => $productDetails,
                'customer'       => $customer,
                'address'        => $address,
            ]
        );
        $mpdf->SetHTMLHeader($data->fetch());
        $data = $this->context->smarty->createTemplate(_EPH_PDF_DIR_ . 'pdf_footer.tpl', $this->context->smarty);

        $mpdf->SetHTMLFooter($data->fetch(), 'O');

        $data = $this->context->smarty->createTemplate(_EPH_PDF_DIR_ . 'pdf.css.tpl', $this->context->smarty);
        $data->assign(
            [
                'color' => '#ef9331',
            ]
        );
        $stylesheet = $data->fetch();

        $data = $this->context->smarty->createTemplate(_EPH_PDF_DIR_ . 'pdf_sepa.tpl', $this->context->smarty);

        $data->assign(
            [
                'title'    => 'Export SEPA',
                'sepa'     => $sepa,
                'mandat'   => $mandat,
                'piece'    => $order,
                'bank'     => $bank,
                'customer' => $customer,
                'date'     => date('d-m-Y'),
            ]
        );

        $filePath = _EPH_EXPORT_DIR_;
        $fileName = $this->l('Pending Sepa ') . $order->piece_number . '.pdf';

        $mpdf->SetTitle($this->l('Pending Sepa ') . $order->piece_number . '.pdf');
        $mpdf->SetAuthor($company->company_name);

        $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($data->fetch());

        $mpdf->Output($filePath . $fileName, "F");

        $response = [
            'fileExport' => $this->context->link->getBaseLink() . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . $fileName,
        ];
        die(Tools::jsonEncode($response));
    }

}
