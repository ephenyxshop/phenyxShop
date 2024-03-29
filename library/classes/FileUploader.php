<?php

/**
 * Class FileUploaderCore
 *
 * @since 1.9.1.0
 */
class FileUploaderCore {

    protected $allowedExtensions = [];

    /** @var QqUploadedFileXhr|QqUploadedFileForm|false */
    protected $file;
    protected $sizeLimit;

    /**
     * FileUploaderCore constructor.
     *
     * @param array $allowedExtensions
     * @param int   $sizeLimit
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct(array $allowedExtensions = [], $sizeLimit = 10485760) {

        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        if (isset($_GET['qqfile'])) {
            $this->file = new QqUploadedFileXhr();
        } else if (isset($_FILES['qqfile'])) {
            $this->file = new QqUploadedFileForm();
        } else {
            $this->file = false;
        }

    }

    /**
     * @param $str
     *
     * @return int|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function toBytes($str) {

        $val = trim($str);
        $last = strtolower($str[strlen($str) - 1]);

        switch ($last) {
        case 'g':
            $val *= 1024;
        // Fall though allowed
        case 'm':
            $val *= 1024;
        // Fall through allowed
        case 'k':
            $val *= 1024;
        }

        return $val;
    }

    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function handleUpload() {

        if (!$this->file) {
            return ['error' => Tools::displayError('No files were uploaded.')];
        }

        $size = $this->file->getSize();

        if ($size == 0) {
            return ['error' => Tools::displayError('File is empty')];
        }

        if ($size > $this->sizeLimit) {
            return ['error' => Tools::displayError('File is too large')];
        }

        $pathinfo = pathinfo($this->file->getName());
        $these = implode(', ', $this->allowedExtensions);

        if (!isset($pathinfo['extension'])) {
            return ['error' => sprintf(Tools::displayError('File has an invalid extension, it should be one of these: %s.'), $these)];
        }

        $ext = $pathinfo['extension'];

        if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)) {
            return ['error' => sprintf(Tools::displayError('File has an invalid extension, it should be one of these: %s.'), $these)];
        }

        return $this->file->save();
    }

}

/**
 * Class QqUploadedFileForm
 *
 * @since 1.9.1.0
 */
class QqUploadedFileForm {

    /**
     * Save the file to the specified path
     *
     * @return bool|array TRUE on success
     * @throws PhenyxShopException
     */
    public function save() {

        $product = new Product($_GET['id_product']);

        if (!Validate::isLoadedObject($product)) {
            return ['error' => Tools::displayError('Cannot add image because product creation failed.')];
        } else {
            $image = new Image();
            $image->id_product = (int) $product->id;
            $image->position = Image::getHighestPosition($product->id) + 1;
            $legends = Tools::getValue('legend');

            if (is_array($legends)) {

                foreach ($legends as $key => $legend) {

                    if (Validate::isGenericName($legend)) {
                        $image->legend[(int) $key] = $legend;
                    } else {
                        return ['error' => sprintf(Tools::displayError('Error on image caption: "%1s" is not a valid caption.'), Tools::safeOutput($legend))];
                    }

                }

            }

            if (!Image::getCover($image->id_product)) {
                $image->cover = 1;
            } else {
                $image->cover = 0;
            }

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                return ['error' => Tools::displayError($validate)];
            }

            if (!$image->add()) {
                return ['error' => Tools::displayError('Error while creating additional image')];
            } else {
                return $this->copyImage($product->id, $image->id);
            }

        }

    }

    /**
     * @param int    $idProduct
     * @param int    $idImage
     * @param string $method
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function copyImage($idProduct, $idImage, $method = 'auto') {

        $image = new Image($idImage);

        if (!$newPath = $image->getPathForCreation()) {
            return ['error' => Tools::displayError('An error occurred during new folder creation')];
        }

        if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['qqfile']['tmp_name'], $tmpName)) {
            return ['error' => Tools::displayError('An error occurred during the image upload')];
        } else if (!ImageManager::resize($tmpName, $newPath . '.' . $image->image_format)) {
            return ['error' => Tools::displayError('An error occurred while copying image.')];
        } else if ($method == 'auto') {
            $imagesTypes = ImageType::getImagesTypes('products');

            foreach ($imagesTypes as $imageType) {

                if (!ImageManager::resize($tmpName, $newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                    return ['error' => Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name'])];
                }

            }

        }

        unlink($tmpName);
        Hook::exec('actionWatermark', ['id_image' => $idImage, 'id_product' => $idProduct]);

        if (!$image->update()) {
            return ['error' => Tools::displayError('Error while updating status')];
        }

        $img = ['id_image' => $image->id, 'position' => $image->position, 'cover' => $image->cover, 'name' => $this->getName(), 'legend' => $image->legend];

        return ['success' => $img];
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getName() {

        return $_FILES['qqfile']['name'];
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getSize() {

        return $_FILES['qqfile']['size'];
    }

}

/**
 * Handle file uploads via XMLHttpRequest
 *
 * @since 1.9.1.0
 */
class QqUploadedFileXhr {

    /**
     * Save the file to the specified path
     *
     * @param string $path
     *
     * @return bool TRUE on success
     */
    public function upload($path) {

        $input = fopen('php://input', 'r');
        $target = fopen($path, 'w');

        $realSize = stream_copy_to_stream($input, $target);

        if ($realSize != $this->getSize()) {
            return false;
        }

        fclose($input);
        fclose($target);

        return true;
    }

    /**
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopException
     */
    public function save() {

        $product = new Product($_GET['id_product']);

        if (!Validate::isLoadedObject($product)) {
            return ['error' => Tools::displayError('Cannot add image because product creation failed.')];
        } else {
            $image = new Image();
            $image->id_product = (int) $product->id;
            $image->position = Image::getHighestPosition($product->id) + 1;
            $legends = Tools::getValue('legend');

            if (is_array($legends)) {

                foreach ($legends as $key => $legend) {

                    if (Validate::isGenericName($legend)) {
                        $image->legend[(int) $key] = $legend;
                    } else {
                        return ['error' => sprintf(Tools::displayError('Error on image caption: "%1s" is not a valid caption.'), Tools::safeOutput($legend))];
                    }

                }

            }

            if (!Image::getCover($image->id_product)) {
                $image->cover = 1;
            } else {
                $image->cover = 0;
            }

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                return ['error' => Tools::displayError($validate)];
            }

            if (!$image->add()) {
                return ['error' => Tools::displayError('Error while creating additional image')];
            } else {
                return $this->copyImage($product->id, $image->id);
            }

        }

    }

    /**
     * @param int    $idProduct
     * @param int    $idImage
     * @param string $method
     *
     * @return array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     */
    public function copyImage($idProduct, $idImage, $method = 'auto') {

        $image = new Image($idImage);

        if (!$newPath = $image->getPathForCreation()) {
            return ['error' => Tools::displayError('An error occurred during new folder creation')];
        }

        if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !$this->upload($tmpName)) {
            return ['error' => Tools::displayError('An error occurred during the image upload')];
        } else if (!ImageManager::resize($tmpName, $newPath . '.' . $image->image_format)) {
            return ['error' => Tools::displayError('An error occurred while copying image.')];
        } else if ($method == 'auto') {
            $imagesTypes = ImageType::getImagesTypes('products');

            foreach ($imagesTypes as $imageType) {
               

                if (!ImageManager::resize($tmpName, $newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                    return ['error' => Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name'])];
                }

            }

        }

        unlink($tmpName);
        Hook::exec('actionWatermark', ['id_image' => $idImage, 'id_product' => $idProduct]);

        if (!$image->update()) {
            return ['error' => Tools::displayError('Error while updating status')];
        }

        $img = ['id_image' => $image->id, 'position' => $image->position, 'cover' => $image->cover, 'name' => $this->getName(), 'legend' => $image->legend];

        return ['success' => $img];
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getName() {

        return $_GET['qqfile'];
    }

    /**
     * @return bool|int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getSize() {

        if (isset($_SERVER['CONTENT_LENGTH']) || isset($_SERVER['HTTP_CONTENT_LENGTH'])) {

            if (isset($_SERVER['HTTP_CONTENT_LENGTH'])) {
                return (int) $_SERVER['HTTP_CONTENT_LENGTH'];
            } else {
                return (int) $_SERVER['CONTENT_LENGTH'];
            }

        }

        return false;
    }

}
