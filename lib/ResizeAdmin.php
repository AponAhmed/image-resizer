<?php

namespace ImageResizer\lib;

/**
 * Description of ResizeAdmin
 *
 * @author Apon
 */
class Resizer {

    public array $option;

    /**
     * MIME Type;
     * @var string
     */
    public string $mimeType;

    /**
     * Extension
     * @var string
     */
    public string $ext;

    /**
     * Actual File Name
     * @var string
     */
    public string $fileName;

    /**
     * File Directory (Only Year & Month) if applicable else empty;
     * @var string
     */
    public string $dir;

    /**
     * Full Size Image Sizes 
     * -width
     * -height
     * @var array
     */
    public array $sizes;

    /**
     * Resource Full Dir
     * @var string
     */
    public string $resource;

    //put your code here
    public function __construct($option) {
        $this->option = $option;
        $this->adminHook();
    }

    /**
     * Admin Hook Register
     */
    public function adminHook() {
        add_filter('wp_handle_upload_prefilter', [$this, 'custom_upload_filter']);
        add_filter('wp_handle_upload', [$this, 'custom_upload_handle_filter'], 10, 2);
      
    }

    /**
     * Find Uploaded Information
     * @param Wp_upload $upload
     * @param string $typeOfexe
     * @return Wp_upload
     */
    public function custom_upload_handle_filter($upload, $typeOfexe) {
        if (isset($upload['file']) && !empty($upload['file'])) {
            $this->pathInfo = pathinfo($upload['file']);
            $this->resource = $upload['file'];
            if ($this->pathInfo) {
                $this->ext = $this->pathInfo['extension']; //Set Extension
                if (in_array($this->ext, $this->option['allow_extensions'])) {
                    $this->fileName = $this->pathInfo['filename']; //Set File Name
                    $this->setDir();
                    $this->process();
                }
            }
        }
        return $upload;
    }

    /**
     * Set Dir
     */
    function setDir() {
        $part = explode("/", $this->pathInfo['dirname']);
        $this->dir = implode("/", array_slice($part, -2));
    }

    /**
     * Main Process of Build Images
     */
    function process() {
        //echo "<pre>";
        //$img = $this->createImageStream($this->resource);

        $binaryData = file_get_contents($this->resource);
        $image = imagecreatefromstring($binaryData);
        //var_dump($image); //GD lib
        //echo "</pre>";
        // exit;
    }

    /**
     * Create Image current Stream GD Image
     */
    function createImageStream($source_url) {
        if ($this->mimeType == 'image/jpeg') {
            $image = imagecreatefromjpeg($source_url);
            //imagejpeg($image, $destination_url, $quality);
        } elseif ($this->mimeType == 'image/gif') {
            $image = imagecreatefromgif($source_url);
            //imagegif($image, $destination_url);
        } elseif ($this->mimeType == 'image/png') {
            $image = imagecreatefrompng($source_url);
            //imagepng($image, $destination_url, $quality);
        } elseif ($this->mimeType == 'image/webp') {
            $image = imagecreatefromwebp($source_url);
            //imagewebp($image, $destination_url, $quality);
        } else {
            $binaryData = file_get_contents($source_url);
            $image = imagecreatefromstring($binaryData);
        }
        return $image;
    }

    /**
     * 
     * @param type $src
     * @param type $dst
     * @param type $dest_imagex
     * @param type $quality
     * @return bolean Status of Created or not 
     */
    function resize_scal($src, $dst, $dest_imagex = false, $quality = 90) {
        $source_image = $this->createImageStream($src); //, $dst, $quality

        $source_imagex = $this->sizes['width'] = imagesx($source_image);
        $source_imagey = $this->sizes['height'] = imagesy($source_image);

        if (!$dest_imagex) {
            $dest_imagex = $source_imagex;
        }

        $dest_imagey = @floor($source_imagey * ($dest_imagex / $source_imagex));

        $dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);

        imagecolortransparent($dest_image, imagecolorallocatealpha($dest_image, 255, 255, 255, 127));
        imagealphablending($dest_image, false);
        imagesavealpha($dest_image, true);
        $transparentindex = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
        imagefill($dest_image, 0, 0, $transparentindex);

        imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $source_imagex, $source_imagey);
        //WaterMark

        $ret = false;

        if ($this->mimeType == 'image/jpeg') {
            $ret = imagejpeg($dest_image, $dst, $quality);
        } elseif ($this->mimeType == 'image/gif') {
            $ret = imagegif($dest_image, $dst);
        } elseif ($this->mimeType == 'image/png') {
            $ret = imagepng($dest_image, $dst, ($quality / 12));
        } elseif ($this->mimeType == 'image/webp') {
            $ret = imagewebp($dest_image, $dst, $quality);
        }
        return $ret;
    }

    

    /**
     * Just Set Files Information In Class Instance.
     * @param _FILES $file
     * @return _FILES $file
     */
    public function custom_upload_filter($file) {
        $this->file = $file;
        $this->mimeType = $file['type'];
        return $file;
    }

}
