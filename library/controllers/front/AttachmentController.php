<?php

/**
 * Class AttachmentControllerCore
 *
 * @since 1.8.1.0
 */
class AttachmentControllerCore extends FrontController {

    /**
     * Post process
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        $a = new Attachment(Tools::getValue('id_attachment'), $this->context->language->id);

        if (!$a->id) {
            Tools::redirect('index.php');
        }

        Hook::exec('actionDownloadAttachment', ['attachment' => &$a]);

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . $a->mime);
        header('Content-Length: ' . filesize(_PS_DOWNLOAD_DIR_ . $a->file));
        header('Content-Disposition: attachment; filename="' . utf8_decode($a->file_name) . '"');
        @set_time_limit(0);
        readfile(_PS_DOWNLOAD_DIR_ . $a->file);
        exit;
    }

}
