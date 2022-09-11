<?php

/**
 * @property PaymentMode $object
 */
class AdminBankAccountsControllerCore extends AdminController {

    public $php_self = 'adminbankaccounts';

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'bank_account';
        $this->className = 'BankAccount';
        $this->publicName = $this->l('Company Bank Accounts');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_BANKACCOUNTS_FIELDS', Tools::jsonEncode($this->getBankAccountFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BANKACCOUNTS_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_BANKACCOUNTS_FIELDS', Tools::jsonEncode($this->getBankAccountFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BANKACCOUNTS_FIELDS'), true);
        }

        EmployeeConfiguration::updateValue('EXPERT_BANKACCOUNTS_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_BANKACCOUNTS_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_BANKACCOUNTS_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_BANKACCOUNTS_SCRIPT');
        }

    }

    public function setAjaxMedia() {

        return $this->pushJS([
            _EPH_JS_DIR_ . 'bank.js',
        ]);
    }

    public function generateParaGridScript() {

        $this->paramPageModel = [
            'type'       => '\'local\'',
            'rPP'        => 40,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $this->paramToolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'Ajouter un compte bancaire\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
                ],

            ],
        ];

        $this->paramTitle = '\'' . $this->l('Gestion des comptes bancaires') . '\'';
        $this->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

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
                var pieceType = rowData.pieceType;

                return {
                    callback: function(){},

                    items: {


                    "edit": {
                            name: \'' . $this->l('Modifier la banque ') . ' \'+rowData.bank_name,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                editAjaxObject("' . $this->controller_name . '", rowData.id_bank_account)
                                //editBank(rowData.id_bank_account);
                            }
                        },

                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer la banque') . ' \ : \'+rowData.bank_name,
                            icon: "trash",

                            callback: function(itemKey, opt, e) {
                                //deleteBank(rowData.id_bank_account);
                                deleteObject("' . $this->controller_name . '", "' . $this->className . '", "Supprimer un compte bancaire", "Etes vous sure de vouloir supprimer ce compta bancaire ?", "Oui", "Annuler",rowData.id_bank_account);
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

    public function getBankAccountRequest() {

        $bankaccounts = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, sl.`name`')
                ->from('bank_account', 'a')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = a.`id_stdaccount` AND sl.`id_lang`  = ' . (int) $this->context->language->id)
                ->orderBy('a.`id_bank_account` ASC')
        );
        $bankaccountLink = $this->context->link->getAdminLink($this->controller_name);

        if (!empty($bankaccounts)) {

            foreach ($bankaccounts as &$bankaccount) {

                if ($bankaccount['id_customer'] > 0) {
                    $bankaccount['is_customer'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
                }

                if ($bankaccount['company_bank'] == 1) {
                    $bankaccount['is_company'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
                }

                if ($bankaccount['active'] == 1) {
                    $bankaccount['active'] = '<div class="p-active"><i class="fa fa-check" aria-hidden="true" style="color:green;"></i></div>';
                } else {
                    $bankaccount['active'] = '<div class="p-inactive"><i class="fa fa-times" aria-hidden="true" style="color:red;"></i></div>';
                }

            }

        }

        return $bankaccounts;

    }

    public function ajaxProcessgetBankAccountRequest() {

        die(Tools::jsonEncode($this->getBankAccountRequest()));

    }

    public function getBankAccountFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'width'    => 50,
                'dataIndx' => 'id_bank_account',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => 'true',
            ],
            [
                'title'    => $this->l('Société ') . $this->context->company->name,
                'minWidth' => 100,
                'dataIndx' => 'is_company',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],
            [
                'title'    => $this->l('Client'),
                'minWidth' => 100,
                'dataIndx' => 'is_customer',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],
            [
                'title'    => $this->l('Titulaire'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'owner',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Code'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'code',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Nom de la banque'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'bank_name',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Iban'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'iban',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('B ban'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'bban',
                'dataType' => 'string',
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('Swift'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'swift',
                'dataType' => 'string',
            ],

            [
                'title'    => $this->l('Active'),
                'minWidth' => 100,
                'dataIndx' => 'active',
                'align'    => 'center',
                'valign'   => 'center',
                'dataType' => 'html',

            ],

        ];

    }

    public function ajaxProcessgetBankAccountFields() {

        die(EmployeeConfiguration::get('EXPERT_BANKACCOUNTS_FIELDS'));
    }

    public function getStdAccountFields() {

        return [
            [

                'dataIndx' => 'id_stdaccount',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => true,
            ],

            [
                'title'    => $this->l('N° de compte'),
                'width'    => 100,
                'dataIndx' => 'account',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Nom'),
                'width'    => 150,
                'dataIndx' => 'name',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Information'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'description',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('B ban'),
                'width'    => 150,
                'exWidth'  => 40,
                'dataIndx' => 'bban',
                'dataType' => 'string',
                'hidden'   => true,
            ],
        ];

    }

    public function ajaxProcessGetStdAccountFields() {

        die(Tools::jsonEncode($this->getStdAccountFields()));
    }

    public function ajaxProcessGetStdAccountRequest() {

        die(Tools::jsonEncode(StdAccount::getBankStdAccount()));
    }

    public function ajaxProcessAddObject() {

        $data = $this->createTemplate('controllers/bank_accounts/addbank.tpl');
        $data->assign(
            [
                'countries' => Country::getCountries($this->context->language->id, true),
            ]
        );

        $li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ajouter un compte bancaire</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
        $html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

        $result = [
            'li'   => $li,

            'html' => $html,
        ];

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessEditObject() {

        if ($this->tabAccess['edit'] == 1) {
            $idBank = Tools::getValue('idObject');
            $bankAccount = new BankAccount($idBank);
            $stdAccount = new StdAccount($bankAccount->id_stdaccount, $this->context->language->id);
            $data = $this->createTemplate('controllers/bank_accounts/editbank.tpl');
            $data->assign(
                [
                    'bankAccount' => $bankAccount,
                    'countries'   => Country::getCountries($this->context->language->id, true),
                    'stdAccount'  => $stdAccount,
                ]
            );

            $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Edition d‘un compte bancaire</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
            $html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

            $result = [
                'success' => true,
                'li'      => $li,
                'html'    => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Votre profile administratif ne vous permet pas d‘éditer les comptes bancaires',
            ];
        }

        die(Tools::jsonEncode($result));

    }

    public function ajaxProcessHasIban() {

        $id_country = Tools::getValue('id_country');
        $iban = BankAccount::hasIban($id_country);

        if ($iban) {
            die(Tools::jsonEncode($iban));
        } else {
            die(false);
        }

    }

    public function ajaxProcessNewBankAccount() {

        $bank = new BankAccount();

        foreach ($_POST as $key => $value) {

            if (property_exists($bank, $key) && $key != 'id_bank_account') {

                $bank->{$key}

                = $value;

            }

        }

        $result = $bank->add();
        $return = [
            'success' => true,
            'message' => 'Le compte bancaire & été ajouté avec succès',
        ];
        die(Tools::jsonEncode($return));
    }

}
