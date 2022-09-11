<?php

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @property BookRecords $object
 */
class AdminBookRecordsControllerCore extends AdminController {

    public $php_self = 'adminbookrecords';

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'book_records';
        $this->className = 'BookRecords';
        $this->publicName = $this->l('Liste des écritures');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();

        EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_SCRIPT', $this->generateParaGridScript());
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_SCRIPT');
        }

        EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_FIELDS', Tools::jsonEncode($this->getBookRecordsFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_BOOK_RECORDS_FIELDS', Tools::jsonEncode($this->getBookRecordsFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'), true);
        }

        $this->extra_vars = [
            'diaries' => BookDiary::getBookDiary(),
            'today'   => date("Y-m-d"),
            'company' => $this->context->company,
        ];
    }

    public function ajaxProcessinitController() {

        return $this->initGridController();
    }

    public function generateParaGridScript($regenerate = false) {

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

        $paragrid->height = '800';
        $paragrid->showTop = 0;
        $paragrid->rowBorders = 1;
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 20,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];

        $paragrid->columnBorders = 1;

        $paragrid->showNumberCell = 0;
        $paragrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
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
                         "add": {
                            name: \'' . $this->l('Saisie au kilomètre ') . '\',
                            icon: "book",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                newBookBookRecord();
                            }
                        },
                        "viewbook": {
                            name: \'' . $this->l('Voir ') . '\'+rowData.piece_type,
                            icon: "book",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }

                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                viewbookBookRecord(rowData.id_book_record);
                            }
                        },


                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Supprimer le ') . '\'+rowData.libelle,
                            icon: "list-ul",
                            visible: function(key, opt){
                                if(rowData.isBooked ==true) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                deleteBookRecords(rowData.id_book_record);
                            }
                        },

                    },
                };
            }',
            ]];

        $option = $paragrid->generateParaGridOption();
        $script = $paragrid->generateParagridScript();

        $this->paragridScript = $script;
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getBookRecordsRequest() {

        $bookRecords = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('brd.*, s.account, sl.`name` as accountName, brd.date_add as recordDate, bdl.code as diaryCode, br.id_book_diary, br.piece_type, br.validate')
                ->from('book_record_details', 'brd')
                ->leftJoin('book_records', 'br', 'br.`id_book_record` = brd.`id_book_record`')
                ->leftJoin('stdaccount', 's', 's.`id_stdaccount` = brd.`id_stdaccount`')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = brd.`id_stdaccount` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->leftJoin('book_diary_lang', 'bdl', 'bdl.`id_book_diary` = br.`id_book_diary` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->orderBy('brd.`date_add` DESC')
        );

        foreach ($bookRecords as &$bookRecord) {

            $bookRecord['date_echeance'] = '';
            $bookRecord['pieceNumber'] = '';

            if ($bookRecord['piece_type'] == '') {
                $bookRecord['piece_type'] = 'l‘écriture';
            }

            if ($bookRecord['piece_number'] > 0) {
                $piece = new StudentPieces($bookRecord['piece_number']);

                if ($bookRecord['id_book_diary'] == 7) {
                    $bookRecord['pieceNumber'] = $piece->prefix . $piece->piece_number;
                }

                if ($bookRecord['debit'] > 0) {
                    $bookRecord['date_echeance'] = $piece->date_echeance;
                }

            }

        }

        return $bookRecords;

    }

    public function ajaxProcessgetBookRecordsRequest() {

        die(Tools::jsonEncode($this->getBookRecordsRequest()));

    }

    public function getBookRecordsFields() {

        return [
            [
                'title'      => $this->l('ID'),
                'maxWidth'   => 100,
                'dataIndx'   => 'id_book_record',
                'dataType'   => 'integer',
                'editable'   => false,
                'align'      => 'center',
                'valign'     => 'center',

                'hiddenable' => 'no',
            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'id_book_diary',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'validate',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],

            [
                'title'      => ' ',
                'width'      => 10,
                'dataIndx'   => 'piece_type',
                'dataType'   => 'integer',
                'hidden'     => true,
                'hiddenable' => 'no',

            ],
            [
                'title'    => $this->l('Code du journal'),
                'maxWidth' => 100,
                'dataIndx' => 'diaryCode',
                'valign'   => 'center',
                'dataType' => 'string',
                'align'    => 'center',
            ],
            [
                'title'    => $this->l('Date'),
                'maxWidth' => 230,
                'dataIndx' => 'recordDate',
                'align'    => 'center',
                'valign'   => 'center',
                'cls'      => 'rangeDate',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,

            ],

            [
                'title'    => $this->l('N° de compte'),
                'maxWidth' => 150,
                'dataIndx' => 'account',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Libellé du compte'),
                'maxWidth' => 250,
                'dataIndx' => 'accountName',
                'valign'   => 'left',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Libellé'),
                'minWdth'  => 250,
                'dataIndx' => 'libelle',
                'valign'   => 'center',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Montant Débit'),
                'maxWidth' => 200,
                'minWidth' => 200,
                'dataIndx' => 'debit',
                'valign'   => 'center',
                'dataType' => 'float',
                'format'   => '# ##0,00',
                'summary'  => [
                    'type' => 'sum',
                ],
            ],
            [
                'title'    => $this->l('Montant Crédit'),
                'maxWidth' => 200,
                'minWidth' => 200,
                'dataIndx' => 'credit',
                'valign'   => 'center',
                'dataType' => 'float',
                'format'   => '# ##0,00',
                'summary'  => [
                    'type' => 'sum',
                ],
            ],
            [
                'title'    => $this->l('Numéro de pièce'),
                'maxWidth' => 170,
                'minWidth' => 170,
                'dataIndx' => 'pieceNumber',
                'dataType' => 'string',
                'valign'   => 'center',
            ],
            [
                'title'    => $this->l('Date d‘échéanc'),
                'maxWidth' => 170,
                'minWidth' => 170,
                'dataIndx' => 'date_echeance',
                'align'    => 'center',
                'valign'   => 'center',
                'cls'      => 'rangeDate',
                'dataType' => 'date',
                'format'   => 'dd/mm/yy',
                'editable' => false,

            ],

        ];

    }

    public function ajaxProcessgetBookRecordsFields() {

        die(EmployeeConfiguration::get('EXPERT_BOOK_RECORDS_FIELDS'));
    }

    public function ajaxProcessGetDataMonth() {

        $dateStart = Tools::getValue('start');
        $dateEnd = Tools::getValue('end');
        $book_records = BookRecords::getTotalBookRecordssByRange($dateStart, $dateEnd);
        $date = new DateTime($dateStart);
        $month = Tools::getMonthById($date->format('m'));

        $html = '<p>Total encaissé au mois de ' . $month . ' : ' . number_format($book_records, 2, ",", " ") . '</p>';

        $return = [
            'html' => $html,
        ];
        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessBookBookRecords() {

        $idBookRecords = Tools::getValue('idBookRecords');
        $book_records = new BookRecords($idBookRecords);

        if ($book_records->booked == 0) {

            $piece = new StudentPieces($book_records->id_piece);
            $studentEducation = new StudentEducation($piece->id_student_education);
            $student = new Customer($piece->id_customer);
            $record = new BookRecords();
            $record->id_book_diary = 2;
            $record->name = "Réglement de la Facture " . $piece->prefix . $piece->piece_number . ' Dossier n°' . $studentEducation->reference_edof . ' ' . $student->lastname . ' ' . $student->firstname;
            $record->date_add = $book_records->date_add;
            $success = $record->add();

            if ($success) {

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $book_records->book_records_account;
                $detail->libelle = "Virement en banque Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->debit = $book_records->amount;
                $detail->add();

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $student->id_stdaccount;
                $detail->libelle = "Règlement de la Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->credit = $book_records->amount;
                $detail->add();

            }

            $book_records->id_book_record = $record->id;
            $book_records->booked = 1;
            $book_records->update();
        }

        $return = [
            'success' => true,
            'message' => 'Le règlement de la Facture ' . $piece->prefix . $piece->piece_number . ' a été comptabilisé avec succès',
        ];
        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessBulkBook() {

        $idbook_recordss = Tools::getValue('idbook_recordss');

        foreach ($idbook_recordss as $idBookRecords) {

            $book_records = new BookRecords($idBookRecords);

            if ($book_records->booked == 1) {
                continue;
            }

            $piece = new StudentPieces($book_records->id_piece);
            $studentEducation = new StudentEducation($piece->id_student_education);
            $student = new Customer($piece->id_customer);
            $record = new BookRecords();
            $record->id_book_diary = 2;
            $record->name = "Réglement de la Facture " . $piece->prefix . $piece->piece_number . ' Dossier n°' . $studentEducation->reference_edof . ' ' . $student->lastname . ' ' . $student->firstname;
            $record->date_add = $book_records->date_add;
            $success = $record->add();

            if ($success) {

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $book_records->book_records_account;
                $detail->libelle = "Virement en banque Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->debit = $book_records->amount;
                $detail->add();

                $detail = new BookRecordDetails();
                $detail->id_book_record = $record->id;
                $detail->id_stdaccount = $student->id_stdaccount;
                $detail->libelle = "Règlement de la Facture " . $piece->prefix . $piece->piece_number . ' ' . $student->lastname . ' ' . $student->firstname;
                $detail->credit = $book_records->amount;
                $detail->add();
            }

            $book_records->id_book_record = $record->id;
            $book_records->booked = 1;
            $book_records->update();

        }

        $result = [
            'success' => true,
            'message' => $this->l('Les Réglement ont été comptabilisées avec succès'),
        ];

        die(Tools::jsonEncode($result));
    }

    public function proceedSageCoala($dateStart, $dateEnd) {

        $file = fopen("testproceedSageCoala.txt", "w");

        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
        ];
        $titleStyle = [
            'font'    => [
                'bold' => true,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $dateStart = Configuration::get('EPH_N_ACCOUNT_START');
        $year = new DateTime($dateStart);
        $year = $year->format('Y');
        $month = Tools::getMonthById(date("m", strtotime($dateStart))) . ' ' . $year;
        $dateEnd = date("Y-m-t", strtotime($dateStart));

        $range[$month] =
        Db::getInstance()->executeS(
            (new DbQuery())
                ->select('brd.date_add as recordDate, bdl.code as diaryCode,  s.account, brd.piece_number,  brd.libelle, brd.debit, brd.credit, 0 as `devise`')
                ->from('book_record_details', 'brd')
                ->leftJoin('book_records', 'br', 'br.`id_book_record` = brd.`id_book_record`')
                ->leftJoin('stdaccount', 's', 's.`id_stdaccount` = brd.`id_stdaccount`')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = brd.`id_stdaccount` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->leftJoin('book_diary_lang', 'bdl', 'bdl.`id_book_diary` = br.`id_book_diary` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->where('br.`date_add` >= \'' . $dateStart . '\' AND br.`date_add` <= \'' . $dateEnd . '\' AND br.id_book_diary = 7')
                ->orderBy('br.`date_add` DESC, brd.id_book_record')
        );

        for ($i = 1; $i < 12; $i++) {
            $year = new DateTime($dateStart);
            $dateStart = new DateTime($dateStart);

            $year = $year->format('Y');
            $dateStart->modify('+1 month');

            $dateStart = $dateStart->format('Y-m-d');
            $month = Tools::getMonthById(date("m", strtotime($dateStart))) . ' ' . $year;
            $dateEnd = date("Y-m-t", strtotime($dateStart));
            $range[$month] =
            Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('brd.date_add as recordDate, bdl.code as diaryCode,  s.account, brd.piece_number,  brd.libelle, brd.debit, brd.credit, 0 as `devise`')
                    ->from('book_record_details', 'brd')
                    ->leftJoin('book_records', 'br', 'br.`id_book_record` = brd.`id_book_record`')
                    ->leftJoin('stdaccount', 's', 's.`id_stdaccount` = brd.`id_stdaccount`')
                    ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = brd.`id_stdaccount` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                    ->leftJoin('book_diary_lang', 'bdl', 'bdl.`id_book_diary` = br.`id_book_diary` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                    ->where('br.`date_add` >= \'' . $dateStart . '\' AND br.`date_add` <= \'' . $dateEnd . '\' AND br.id_book_diary = 7')
                    ->orderBy('br.`date_add` DESC, brd.id_book_record')
            );

        }

        $titles = [
            'recordDate'   => 'Date Ecriture',
            'diaryCode'    => 'Code Journal',
            'account'      => 'N° du Compte',
            'piece_number' => 'N° de Pièce',
            'libelle'      => 'Libellé de l‘écriture',
            'debit'        => 'Debit',
            'credit'       => 'Credit',
            'devise'       => 'Devise',
        ];

        $column = chr(64 + count($titles));
        $spreadsheet = new Spreadsheet();
        $i = 0;

        foreach ($range as $key => $lines) {
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($i);
            $spreadsheet->getActiveSheet()->setTitle($key);
            $i++;
        }

        $j = 0;

        foreach ($range as $key => $lines) {
            $i = 1;
            $k = 0;
            $spreadsheet->getSheet($j);

            foreach ($titles as $key => $value) {
                $k++;
                $letter = chr(64 + $k);

                $spreadsheet->setActiveSheetIndex($j)->setCellValue($letter . $i, $value);
                $spreadsheet->getActiveSheet($j)->getColumnDimension($letter)->setAutoSize(true);

            }

            $spreadsheet->getActiveSheet($j)->getStyle('A1:' . $column . $i)->getAlignment()->setVertical(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet($j)->getStyle('A1:' . $column . $i)->applyFromArray($titleStyle);
            $spreadsheet->getActiveSheet($j)->getStyle('A1:' . $column . $i)->getFont()->setSize(12);

            $j++;
        }

        $j = 0;

        foreach ($range as $key => $lines) {
            $i = 2;
            $spreadsheet->getSheet($j);

            foreach ($lines as $k => $line) {

                $index = 0;

                foreach ($line as $key => $value) {

                    if (array_key_exists($key, $titles)) {
                        $index++;

                        if ($key == 'recordDate') {
                            $date = new DateTime($line[$key]);
                            $line[$key] = $date->format('d/m/Y');

                        }

                        if ($key == 'devise') {
                            $line[$key] = 'E';
                        }

                        $letter = chr(64 + $index);
                        $spreadsheet->setActiveSheetIndex($j)
                            ->setCellValue($letter . $i, $line[$key]);

                        $spreadsheet->getActiveSheet($j)->getColumnDimension($letter)->setAutoSize(true);
                        $spreadsheet->getActiveSheet($j)->getStyle('A' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                    }

                }

                $i++;
            }

            $j++;
        }

        $tag = date("H-i-s");
        $fileSave = new Xlsx($spreadsheet);
        $fileSave->save(_EPH_EXPORT_DIR_ . 'exportEcriture' . $tag . '.xlsx');
        $response = [
            'fileExport' => 'fileExport' . DIRECTORY_SEPARATOR . 'exportEcriture' . $tag . '.xlsx',
        ];
        die(Tools::jsonEncode($response));

    }

    public function ajaxProcessExportLines() {

        $dateStart = Tools::getValue('dateStart');
        $dateEnd = Tools::getValue('dateEnd');
        $type = Tools::getValue('type');

        if ($type == 'standard') {
            $this->proceedSageCoala($dateStart, $dateEnd);
        }

        $bookRecords = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('brd.date_add as recordDate, bdl.code as diaryCode,  s.account, brd.piece_number,  brd.libelle, brd.debit, brd.credit, 0 as `devise`')
                ->from('book_record_details', 'brd')
                ->leftJoin('book_records', 'br', 'br.`id_book_record` = brd.`id_book_record`')
                ->leftJoin('stdaccount', 's', 's.`id_stdaccount` = brd.`id_stdaccount`')
                ->leftJoin('stdaccount_lang', 'sl', 'sl.`id_stdaccount` = brd.`id_stdaccount` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->leftJoin('book_diary_lang', 'bdl', 'bdl.`id_book_diary` = br.`id_book_diary` AND sl.`id_lang` = ' . (int) $this->context->language->id)
                ->where('br.`date_add` >= \'' . $dateStart . '\' AND br.`date_add` <= \'' . $dateEnd . '\' AND br.id_book_diary IN (5, 7)')
                ->orderBy('br.`date_add` DESC, brd.id_book_record')
        );

        $titles = ['Date Ecriture', 'Code Journal', 'N° du Compte', 'N° de Pièce', 'Libellé de l‘écriture', 'Debit', 'Credit', 'Devise'];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);

        foreach ($titles as $key => $value) {
            $key++;
            $letter = chr(64 + $key);

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($letter . '1', $value);
            $spreadsheet->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true);

        }

        $i = 2;

        foreach ($bookRecords as $key => $records) {

            $k = 1;

            foreach ($records as $key => $value) {

                if ($key == 'recordDate') {
                    $phpdate = strtotime($value);
                    $value = date('d/m/Y', $phpdate);
                }

                if ($key == 'devise') {
                    $value = 'E';
                }

                $letter = chr(64 + $k);

                $spreadsheet->setActiveSheetIndex(0)->setCellValue($letter . $i, $value);
                $spreadsheet->getActiveSheet()->getColumnDimension($letter)->setAutoSize(true);

                $k++;
            }

            $spreadsheet->getActiveSheet(0)->getStyle('A' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
            $spreadsheet->getActiveSheet(0)->getStyle('C' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $spreadsheet->getActiveSheet(0)->getStyle('F' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            $spreadsheet->getActiveSheet(0)->getStyle('G' . $i)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            $i++;
        }

        $tag = date("H-i-s");
        $fileSave = new Xlsx($spreadsheet);
        $fileSave->save(_EPH_EXPORT_DIR_ . 'exportEcriture' . $tag . '.xlsx');
        $response = [
            'fileExport' => 'fileExport' . DIRECTORY_SEPARATOR . 'exportEcriture' . $tag . '.xlsx',
        ];
        die(Tools::jsonEncode($response));

    }

}
