<?php

class AdminLayerSliderMediaControllerCore extends AdminController {

    public $php_self = 'adminlayerslidermedia';
	public function __construct() {

        $this->context = Context::getContext();
        parent::__construct();
		$this->ajax = true;

    }

    public function postProcess() {

        parent::postProcess();


    }

    public function ajaxProcessListImages() {

		$dir = preg_replace('/\.+/', '.', Tools::getValue('d'));
        $imagepath = _EPH_IMG_DIR_ . $dir;
		

        $del = Tools::getValue('del');

        if ($del) {
            $delimagepath = $imagepath . urldecode($del);

            if (file_exists($delimagepath)) {
                unlink($delimagepath);
            }

        }

        if (is_dir($imagepath)) {
            $files = scandir($imagepath);
        }

        // $array_count = 0;
        $files_array = [];

        if ($imagepath != _EPH_IMG_DIR_) {
            $files_array[] = '..';
        }
		
		

        foreach ($files as $file_name) {
            $file_path = $imagepath . $file_name;

            if (is_dir($file_path) && $file_name[0] !== '.') {
                $files_array[] = $file_name;
            }

        }
		

        $pattern = '/\.(jpg|jpe|jpeg|png|gif|bmp)$/';
        // $mediatype = Tools::getValue('type', 'image');

        foreach ($files as $file_name) {
            $file_path = $imagepath . $file_name;

            if (is_file($file_path) && $file_name[0] !== '.' && preg_match($pattern, $file_name)) {
                $files_array[] = $file_name;
            }

        }
		

        $nofiles = count($files_array);
        $resultspp = 4096;
        $nopages = ceil($nofiles / $resultspp);

        $pageno = Tools::getValue('p');

        $error = [
            'thumbhtml'      => iconv('UTF-8', 'UTF-8//IGNORE', Tools::display_gallery_page($files_array, $pageno, $dir, $resultspp, false)),
            'paginationhtml' => Tools::display_gallery_pagination('', count($files_array), $pageno, $resultspp, false),
            'noofpages'      => $nopages,
        ];

        die(Tools::jsonEncode([$error]));
    }

    public function ajaxProcessUploadFile() {

        $uploadfolder = _EPH_IMG_DIR_;
        $reluploadfolder = _EPH_IMG_;
		
        if (Tools::getIsset('uploadfolder')) {
            $dir = preg_replace('/\.+/', '.', Tools::getValue('uploadfolder'));
            $uploadfolder .= $dir;
            $reluploadfolder .= $dir;
        }

        if (file_exists($uploadfolder)) {

			$imageUploader = new HelperImageUploader('image');
			$imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg']);
			$files = $imageUploader->process();
			if (is_array($files) && count($files)) {

                foreach ($files as $image) {
					$ext = pathinfo($image['name'], PATHINFO_EXTENSION);
					$destinationFile = $uploadfolder. $image['name'];
					$reldestination = $reluploadfolder. $image['name'];
					if (copy($image['save_path'], $destinationFile)) {
                        $uploadinfo = new stdClass();
                    	$uploadinfo->name = $image['name'];
                    	$uploadinfo->destination = $reldestination;
                    	$uploadinfo->mediatype = Tools::getValue('mediatype');
						Hook::exec('actionOnImageUploadAfter', ['dst_file' => $destination, 'file_type' => $fileext]);
                    	die(Tools::jsonEncode([$uploadinfo]));
                    }
                }

            } else {
				$result= [
					'success' => false,
					'message' => 'Aucun fichier embarqué'
				];
				die(Tools::jsonEncode($result));
			}
		

        } else {
            $error = ['error' => 'Upload folder not correctly configured! ' . $uploadfolder];
            die(Tools::jsonEncode([$error]));
        }

    }

    public function display() {

		$base_link = Context::getContext()->link->getBaseFrontLink();
		$imagepath = $base_link.'content/img/';
        $data = $this->createTemplate('mediamanager.tpl');

        $mediatype = Tools::getValue('type', 'image');
		
		$script = '{% for (var i=0, file; file=o.files[i]; i++) { %}
        	<tr class="template-upload">
            <td class="preview"><span class=""></span></td>
            <td class="name"><span>{%=file.name%}</span></td>
            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
            {% if (file.error) { %}
                <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
            {% } else if (o.files.valid && !i) { %}
                <td style="display:none;">
                    <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
                </td>
                <td class="start">{% if (!o.options.autoUpload) { %}
                    <button class="btn btn-primary" style="display:none;">
                        <i class="icon-upload icon-white"></i>
                        <span>{%=locale.fileupload.start%}</span>
                    </button>
                {% } %}</td>
            {% } else { %}
                <td colspan="2"></td>
            {% } %}
            <td class="cancel" style="display:none;">{% if (!i) { %}
                <button class="btn btn-function btn-cancel">
                    <i class="icon-ban-circle icon-black"></i>
                    <!--<span>{%=locale.fileupload.cancel%}</span>-->
                </button>
            {% } %}</td>
        	</tr>
    	{% } %}';
        $extracss = $this->pushCSS([
            '/content/js/layerslider/css/mediamanager/bootstrap/bootstrap.min.css',
            '/content/js/layerslider/css/mediamanager/bootstrap-responsive.min.css',
            '/content/js/layerslider/css/mediamanager/fileupload/jquery.fileupload-ui.css',
            '/content/js/layerslider/css/mediamanager/fileupload/bootstrap-image-gallery.min.css',
			'/content/themes/' .$this->bo_theme . '/css/dashicons/dashicons.css',
			'/content/themes/blacktie/css/slider/admin.css',
        ]);

        $extraJs = $this->pushJS([
			'https://code.jquery.com/jquery-3.6.0.min.js',			
            '/content/js/layerslider/js/mediamanager/fileupload/vendor/jquery.ui.widget.js',
            '/content/js/layerslider/js/mediamanager/fileupload/vendor/tmpl.min.js',
            '/content/js/layerslider/js/mediamanager/fileupload/vendor/load-image.min.js',
            '/content/js/layerslider/js/mediamanager/fileupload/vendor/canvas-to-blob.min.js',
            '/content/js/layerslider/js/mediamanager/fileupload/vendor/bootstrap.min.js',
            '/content/js/layerslider/js/mediamanager/fileupload/jquery.iframe-transport.js',
            '/content/js/layerslider/js/mediamanager/fileupload/jquery.fileupload.js',
            '/content/js/layerslider/js/mediamanager/fileupload/bootstrap-image-gallery.min.js',
			'/content/js/mediamanager/mediamanager.js',
			'/content/js/manageruploadify.min.js'
        ]);
        $data->assign([
            'link'         => $this->context->link,
			'script'	   => $script,
            'extracss'     => $extracss,
            'extraJs'      => $extraJs,
            'mediatype'    => $mediatype,
            'image_folder' => $imagepath,
            'AjaxLink'     => $this->context->link->getAdminLink('adminlayerslidermedia'),
			'bo_imgdir'                 =>  '/content/themes/' . $this->bo_theme . '/img/',
        ]);
	
        $this->content = $data->fetch();
		
        die($this->content);
    }

}
