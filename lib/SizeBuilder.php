<?php

namespace ImageResizer\lib;

/**
 * Description of SizeBuilder
 *
 * @author Mahabub
 */
class SizeBuilder {

    public $attachmentMeta;
    public $sizes;

    /**
     * Path Information Of Current File
     * @index
     * -dirname
     * -basename
     * -extension
     * -filename
     */
    public $pathInfo;

    /**
     * OG Image Object
     * @var type
     */
    public $SourceImage;

    //put your code here
    public function __construct($option) {
        $this->option = $option;
//        add_filter('jpeg_quality', function ($arg) {
//            return $this->option['imageQuality'];
//        });

        add_action('wp_ajax_buildImageSize', [$this, 'buildImageSize']);
        add_action('wp_ajax_clean_size_build_history', [$this, 'clean_size_build_history']);
        $this->upDir = wp_upload_dir(); //basedir
    }

    function clean_size_build_history() {
        $this->updateLastBuild("0");
        wp_die();
    }

    function buildImageSize() {
        global $wpdb, $_wp_additional_image_sizes;

        //ini_set('display_errors', 1);
        //ini_set('display_startup_errors', 1);
        //error_reporting(E_ALL);
        //Registerted Sizes
        $RegSizes = $_wp_additional_image_sizes;
//      usort($RegSizes, function ($a, $b) {
//          return $a['width'] - $b['width'];
//      });
        if (is_array($this->option['media-size']) && count($this->option['media-size']) > 0) {
            foreach ($this->option['media-size'] as $k => $s) {
                $RegSizes[$s['name']] = array('width' => $s['width'], 'height' => $s['height'], 'crop' => false);
            }
        }
        $this->sizes = $RegSizes;

        //echo "<pre>";
        //var_dump($this->sizes);
        //exit;
        //Debug
//        ini_set('display_errors', 1);
//        ini_set('display_startup_errors', 1);
//        error_reporting(E_ALL);

        $MxItm = empty($this->option['maxItemResize']) ? 1 : $this->option['maxItemResize'];
        $lastBuild = $this->option['lastBuild'];

        $attachments = $wpdb->get_results("SELECT p.ID,p.post_mime_type,pm.meta_value FROM {$wpdb->prefix}posts as p left join {$wpdb->prefix}postmeta pm on p.ID=pm.post_id WHERE pm.meta_key='_wp_attachment_metadata' and p.post_type='attachment' and p.ID > $lastBuild ORDER BY p.ID ASC  limit $MxItm ");

        //var_dump($attachments);

        $infAll = array();
        if (count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $this->ID = $attachment->ID;
                $this->attachmentMeta = wp_get_attachment_metadata($this->ID);

                $this->mime = $attachment->post_mime_type;
                $this->pathInfo = pathinfo($this->attachmentMeta['file']);
                $source_url = $this->upDir['basedir'] . "/" . $this->pathInfo['dirname'] . "/" . $this->pathInfo['basename'];
                if (!in_array($this->pathInfo['extension'], $this->option['allow_extensions'])) {
                    $infAll[$this->ID] = array('info' => 'file Not an Image');
                    $infAll[$this->ID]['Url'] = $this->attachmentMeta['file'];
                    $this->updateLastBuild();
                    continue;
                }
                if (!file_exists($source_url)) {
                    $infAll[$this->ID]['notFound'][] = $source_url;
                    $infAll[$this->ID] = $inf;
                    //end of size loop
                    $this->updateMeta();
                    $lastBuild = $this->ID;
                    continue;
                }
                $this->SourceImage = $this->createImageStream($source_url);

                $inf = [];

                $ex_sizes = ['m4', 'm6', 'col-1', 'col-3', 'col-4', 'col-6'];
                $this->attachmentMeta['sizes'] = []; //Remove All Existing sizes
                foreach ($this->attachmentMeta['sizes'] as $sizeKey => $size) { //Remove Existing sizes
                    $sizePathInfo = pathinfo($size['file']);
                    if (in_array($sizeKey, $ex_sizes) || $sizePathInfo['extension'] != $this->pathInfo['extension']) {
                        unset($this->attachmentMeta['sizes'][$sizeKey]);
                    }
                }
                //Delete All Sizes
                if ($this->option['replace_existingSize'] == "1") {
                    $this->DeleteAllSizes();
                }

                foreach ($this->sizes as $sizeName => $val) {
                    if ($this->attachmentMeta['width'] > $val['width']) {
                        $sW = $val['width'];
                        $dest_imagey = @floor($this->attachmentMeta['height'] * ($val['width'] / $this->attachmentMeta['width']));
                        $val['height'] = $dest_imagey;
                        $fileName = $this->pathInfo['filename'] . "-" . "$sW" . "x" . $dest_imagey . "." . $this->pathInfo['extension']; //$this->pathInfo;

                        $res = $this->createImage($val, $fileName, $inf);
                        if ($res) {
                            $sizeData = array(
                                'file' => $fileName,
                                'width' => $sW,
                                'height' => $dest_imagey,
                                'mime-type' => $this->mime,
                            );
                            //Append Single Size into Attachment Meta
                            $this->attachmentMeta['sizes'][$sizeName] = $sizeData;
                        }
                    } else {
                        $inf['sizeLess'][] = $val['width'];
                    }
                }
                $infAll[$this->ID] = $inf;
                //end of size loop
                $this->updateMeta();
                $lastBuild = $this->ID;
            }
        } else {
            $infAll = "complete";
        }

        $lastBuild = get_option('lastBuildProcess');
        $done = $wpdb->get_results("SELECT COUNT(*) as done FROM {$wpdb->prefix}posts WHERE post_type='attachment' and ID <= $lastBuild ORDER BY ID ASC");
        //var_dump($done);
        $total = $wpdb->get_results("SELECT COUNT(*) as total FROM {$wpdb->prefix}posts WHERE post_type='attachment' ORDER BY ID ASC");

        $doneC = 0;
        $totalC = 0;
        if (isset($done[0])) {
            $doneC = $done[0]->done;
        }
        if (isset($total[0])) {
            $totalC = $total[0]->total;
        }
        echo json_encode(array('total' => $totalC, 'done' => $doneC, 'info' => $infAll));
        wp_die();
    }

    /**
     * Delete Image sizes 
     */
    function DeleteAllSizes() {
        $orginalImage = $this->upDir['basedir'] . "/" . $this->pathInfo['dirname'] . "/" . $this->pathInfo['basename'];
        $targetedImagePrifix = $this->upDir['basedir'] . "/" . $this->pathInfo['dirname'] . "/" . $this->pathInfo['filename'];

        foreach (glob($targetedImagePrifix . "*." . $this->pathInfo['extension']) as $filename) {
            if ($filename !== $orginalImage && file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * 
     * @param array $sizeInfo  information of size - contain [height,width,file,mime]
     * @param string $fileName Output File name with new size 
     * @param array $info
     * @return bolean 
     */
    function createImage($sizeInfo, $fileName, &$info) {
        //$upDir = wp_upload_dir(); //basedir
        $quality = $this->option['imageQuality'];
        $dst = $this->upDir['basedir'] . "/" . $this->pathInfo['dirname'] . "/" . $fileName;

        $gDir = $this->upDir['basedir'] . "/" . $this->pathInfo['dirname'] . "/" . $this->pathInfo['filename'] . "-" . $sizeInfo['width'] . "x";
        $exists = glob($gDir . "*");
        //var_dump($exists);
        $res = false;
        if (count($exists) > 0) {
            if ($this->option['replace_existingSize'] == "1") {
                foreach ($exists as $file) {
                    unlink($file);
                }
                $res = $this->resize_scal($this->SourceImage, $dst, $sizeInfo['width'], $sizeInfo['height'], $quality);
                if ($res) {
                    $info['Replaced'][] = $sizeInfo['width'];
                }
            } else {
                $info['exist'][] = $sizeInfo['width'];
            }
        } else {
            $res = $this->resize_scal($this->SourceImage, $dst, $sizeInfo['width'], $sizeInfo['height'], $quality);
            if ($res) {
                $info['created'][] = $sizeInfo['width'];
            }
        }
        return $res;
    }

    /**
     * Create Image current Stream GD Image
     */
    function createImageStream($source_url) {
        if ($this->mime == 'image/jpeg') {
            $image = imagecreatefromjpeg($source_url);
            //imagejpeg($image, $destination_url, $quality);
        } elseif ($this->mime == 'image/gif') {
            $image = imagecreatefromgif($source_url);
            //imagegif($image, $destination_url);
        } elseif ($this->mime == 'image/png') {
            $image = imagecreatefrompng($source_url);
            //imagepng($image, $destination_url, $quality);
        } elseif ($this->mime == 'image/webp') {
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
     * @param int $quality
     * @return bolean Status of Created or not 
     */
    function resize_scal($source_image, $dst, $dest_imagex = false, $dest_imagey = false, $quality = 100) {
        //$source_image = $this->createImageStream($src); //, $dst, $quality
        if (!$source_image) {
            return false;
        }

        $source_imagex = imagesx($source_image);
        $source_imagey = imagesy($source_image);

        if (!$dest_imagex) {
            $dest_imagex = $source_imagex;
        }

        if (!$dest_imagey) {
            $dest_imagey = @floor($source_imagey * ($dest_imagex / $source_imagex));
        }

        $dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);

        imagecolortransparent($dest_image, imagecolorallocatealpha($dest_image, 255, 255, 255, 127));
        imagealphablending($dest_image, false);
        imagesavealpha($dest_image, true);
        $transparentindex = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
        imagefill($dest_image, 0, 0, $transparentindex);

        imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $source_imagex, $source_imagey);
        //WaterMark

        $ret = false;

        if ($this->mime == 'image/jpeg') {
            $ret = imagejpeg($dest_image, $dst, $quality);
        } elseif ($this->mime == 'image/gif') {
            $ret = imagegif($dest_image, $dst);
        } elseif ($this->mime == 'image/png') {
            $ret = imagepng($dest_image, $dst, ($quality / 12));
        } elseif ($this->mime == 'image/webp') {
            $ret = imagewebp($dest_image, $dst, $quality);
        }
        return $ret;
    }

    function updateMeta() {
        wp_update_attachment_metadata($this->ID, $this->attachmentMeta);
        $this->updateLastBuild();
    }

    function updateLastBuild($val = false) {
        $postId = $this->ID;
        if ($val) {
            $postId = $val;
        }
        update_option('lastBuildProcess', $postId);
    }

}
