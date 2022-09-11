<?php

use XhtmlFormatter\Formatter;

/**
 * @property PaymentMode $object
 */
class AdminMailTemplatesControllerCore extends AdminController {

    public function __construct() {

        $this->bootstrap = true;
        $this->table = 'mail_template';
        $this->className = 'MailTemplate';
        $this->publicName = $this->l('Gestion des modèles de mails');
        $this->lang = true;

        $this->context = Context::getContext();

        parent::__construct();
        EmployeeConfiguration::updateValue('EXPERT_MAILTEMPLATES_FIELDS', Tools::jsonEncode($this->getMailTemplateFields()));
        $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_MAILTEMPLATES_FIELDS'), true);

        if (empty($this->configurationField)) {
            EmployeeConfiguration::updateValue('EXPERT_MAILTEMPLATES_FIELDS', Tools::jsonEncode($this->getMailTemplateFields()));
            $this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_MAILTEMPLATES_FIELDS'), true);
        }

        EmployeeConfiguration::updateValue('EXPERT_MAILTEMPLATES_SCRIPT', $this->generateParaGridScript(true));
        $this->paragridScript = EmployeeConfiguration::get('EXPERT_MAILTEMPLATES_SCRIPT');

        if (empty($this->paragridScript)) {
            EmployeeConfiguration::updateValue('EXPERT_MAILTEMPLATES_SCRIPT', $this->generateParaGridScript(true));
            $this->paragridScript = EmployeeConfiguration::get('EXPERT_MAILTEMPLATES_SCRIPT');
        }

    }

    public function generateParaGridScript() {

        $gridExtraFunction = [
            '

            function editMailTemplate(idMailTemplate) {

            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminMailTemplates,
                data: {
                    action: \'editMailTemplate\',
                    idMailTemplate: idMailTemplate,
                    ajax: true
                },
                async: false,
                dataType: \'json\',
                success: function(data) {
                    $("#viewMailTemplate").html(data.html);
                    $("#paragrid_' . $this->controller_name . '").slideUp();
                    $("body").addClass("edit");
                    $("#viewMailTemplate").slideDown();
                }
                });

            }



        ',

        ];

        $paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);
        $paragrid->paramTable = $this->table;
        $paragrid->paramController = $this->controller_name;
        $paragrid->height = 700;
        $paragrid->showNumberCell = 0;
        $paragrid->pageModel = [
            'type'       => '\'local\'',
            'rPP'        => 100,
            'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
        ];
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

        $paragrid->toolbar = [
            'items' => [

                [
                    'type'     => '\'button\'',
                    'label'    => '\'' . $this->l('Scanner le repertoir Mail') . '\'',
                    'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
                    'listener' => 'scanDirectoryMail',
                ],

            ],
        ];

        $paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';
        $paragrid->complete = 'function(){
        window.dispatchEvent(new Event(\'resize\'));
        }';
        $paragrid->selectionModelType = 'row';

        $paragrid->showTitle = 1;
        $paragrid->title = '\'' . $this->l('Gestion des modèle d‘email') . '\'';
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
                            name: \'' . $this->l('Visualiser ou modifier ce modèle ') . ' \'+rowData.template,
                            icon: "edit",
                            visible: function(key, opt){
                               var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {

                                editMailTemplate(rowData.id_mail_template);
                            }
                        },
                    "delete": {
                            name: \'' . $this->l('Supprimer la table :') . '\'+rowData.name,
                            icon: "delete",

                            callback: function(itemKey, opt, e) {
                                deleteTemplate(rowData.id_mail_template);

                            }
                        }



                    },
                };
            }',
            ]];

        $paragrid->gridExtraFunction = $gridExtraFunction;
        $option = $paragrid->generateParaGridOption();
        $this->paragridScript = $paragrid->generateParagridScript();
        return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
    }

    public function generateParaGridOption() {

        return '';

    }

    public function getMailTemplateRequest() {

        $templates = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*')
                ->from('mail_template')
                ->orderBy('`target` ASC')
        );

        return $templates;

    }

    public function ajaxProcessgetMailTemplateRequest() {

        die(Tools::jsonEncode($this->getMailTemplateRequest()));

    }

    public function getMailTemplateFields() {

        return [
            [
                'title'    => $this->l('ID'),
                'maxWidth' => 50,
                'dataIndx' => 'id_mail_template',
                'dataType' => 'integer',
                'editable' => false,
                'hidden'   => true,
            ],
            [
                'title'    => $this->l('Fichier Template '),
                'maxWidth' => 300,
                'minWidth' => 300,
                'exWidth'  => 40,
                'dataIndx' => 'template',
                'dataType' => 'string',
                'hidden'   => false,

            ],
            [
                'title'    => $this->l('Destinataire'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'target',
                'dataType' => 'string',
            ],

            [
                'title'    => $this->l('Description'),
                'width'    => 100,
                'exWidth'  => 40,
                'dataIndx' => 'name',
                'dataType' => 'string',
            ],
            [
                'title'    => $this->l('Version'),
                'maxWidth' => 150,
                'exWidth'  => 40,
                'dataIndx' => 'version',
                'dataType' => 'string',
            ],

        ];

    }

    public function ajaxProcessgetMailTemplateFields() {

        die(EmployeeConfiguration::get('EXPERT_MAILTEMPLATES_FIELDS'));
    }

    public function ajaxProcessScanDirectoryMail() {

        $iterator = new AppendIterator();

        $iterator->append(new DirectoryIterator(_PS_ROOT_ADMIN_DIR_ . '/mails/fr'));

        foreach ($iterator as $file) {

            $filePath = $file->getFilename();
            $filePath = str_replace(_PS_ROOT_ADMIN_DIR_, '', $filePath);

            if (in_array($file->getFilename(), ['.', '..', 'index.php', '.htaccess', 'dwsync.xml'])) {
                continue;
            }

            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if ($ext == 'tpl') {
                $idTemplate = MailTemplate::getObjectByTemplateName($filePath);

                if ($idTemplate > 0) {
                    continue;
                } else {
                    $template = new MailTemplate();
                    $template->template = $filePath;
                    $template->target = 'A Definir';
                    $template->name = 'A Definir';
                    $template->add();
                }

            }

        }

        $return = [
            'success' => true,
            'message' => 'La liste des templates a été régénéré',
        ];

        die(Tools::jsonEncode($return));
    }

    public function ajaxProcessEditMailTemplate() {

        $idMailTemplate = Tools::getValue('idMailTemplate');
        $mailTemplate = new MailTemplate($idMailTemplate);

        $pushjJs = $this->pushJS([
            __PS_BASE_URI__ . _PS_JS_DIR_ . 'tinymce/tinymce.min.js',
            _PS_JS_DIR_ . 'admin/tinymce.inc.js',

        ]);
        $data = $this->createTemplate('controllers/mail_templates/editTemplate.tpl');
        $data->assign(
            [
                'mailTemplate' => $mailTemplate,
                'tinymce'      => true,
                'iso'          => file_exists(_SHOP_CORE_DIR_ . _PS_JS_DIR_ . 'tiny_mce/langs/' . $iso . '.js') ? $iso : 'en',
                'path_css'     => _THEME_CSS_DIR_,
                'ad'           => __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_),
                'pushjJs'      => $pushjJs,
            ]
        );

        $return = [
            'html' => $data->fetch(),
        ];

        die(Tools::jsonEncode($return));

    }

    public function ajaxProcessDeleteTemplate() {

        $idMailTemplate = Tools::getValue('idMailTemplate');
        $template = new MailTemplate($idMailTemplate);
        $filePath = _PS_ROOT_ADMIN_DIR_ . '/mails/fr/' . $template->template;

        if (unlink($filePath)) {
            $template->delete();
            $result = [
                'success' => true,
                'message' => 'Le Controller ' . $template->template . ' a été supprimée avec succès',
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Un beug est apparu lors de la tenative de suppression du fichier ' . $template->template,
            ];
        }

        die(Tools::jsonEncode($result));
    }

    public function ajaxProcessUpdateMailTemplate() {

        $idMailTemplate = Tools::getValue('id_mail_template');
        $mailTemplate = new MailTemplate($idMailTemplate);

        foreach ($_POST as $key => $value) {

            if (property_exists($mailTemplate, $key) && $key != 'id_mail_template') {

                $mailTemplate->{$key}

                = $value;

            }

        }

        $formatter = new Formatter();
        $content = str_replace('&gt;', '>', $mailTemplate->content);
        $output = $formatter->format($content);

        $file = fopen(_PS_ROOT_ADMIN_DIR_ . "/mails/fr/" . $mailTemplate->template, "w");
        fwrite($file, $output);

        $result = $mailTemplate->update();
        $return = [
            'success' => true,
            'message' => 'Le contenu de l‘email a été mis à jour avec succès',
        ];
        die(Tools::jsonEncode($return));
    }

}
