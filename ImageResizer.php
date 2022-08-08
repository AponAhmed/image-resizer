<?php

/**
 * Plugin Name: Image Resizer
 * Plugin URI: https://www.siatex.com
 * Description: Generate Multiple size Of Image in Media 
 * Author: SiATEX
 * Author URI: https://www.siatex.com
 * Version: 2.3
 */

namespace ImageResizer;

use ImageResizer\lib\Resizer;
use ImageResizer\lib\SizeBuilder;

define('ImageResizer', dirname(__FILE__));
//Define
define("IMRLIB", ImageResizer . "/lib/");
define("IMR_ASSET_URI", plugin_dir_url(__FILE__) . "assets/");
define("IMR_ASSET_DIR", plugin_dir_path(__FILE__) . "assets/");
foreach (glob(IMRLIB . "*.php") as $filename) {
    include $filename;
}

define('IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'JPG']);

/**
 * Description of ImageResizer
 *
 * @author Apon
 */
//Functions
//wp_get_attachment_image_srcset
//Meta
//_wp_attached_file
//_wp_attachment_metadata

class ImageResizer {

    public $option;
    private $optionKey = "media_resize_options";

    //put your code here
    public function __construct() {
        $this->setOption();
        $this->mediaSizeSet();
        if (is_admin()) {
            $this->builder = new SizeBuilder($this->option);
            //$this->resize = new Resizer($this->option);
            $this->adminView = new lib\AdminView($this->option);
        }
        //Ajax Hook Register
        add_action('wp_ajax_media_resize_option', [$this, 'media_resize_option']);
    }

    /**
     * Set Options
     */
    function setOption() {
        $lastBuild = get_option('lastBuildProcess');
        $this->option = [
            'allow_extensions' => IMAGE_TYPES,
            'maxItemResize' => 1,
            'lastBuild' => empty($lastBuild) ? 0 : $lastBuild,
            'imageQuality' => 100,
            'media-size' => [
                ['name' => 'small-size', 'width' => 100, 'height' => 100],
                ['name' => 'thumbnail', 'width' => 150, 'height' => 150],
            ]
        ];
        $optString = get_option($this->optionKey);
        if (!empty($optString)) {
            $optArr = json_decode($optString, true);
            $this->option = array_merge($this->option, $optArr);
        }
    }

    public function mediaSizeSet() {
        foreach ($this->option['media-size'] as $size) {
            add_image_size($size['name'], $size['width'], $size['height'], false);
        }
    }

    /**
     * Ajax Callback of Media Resize Option store
     * 
     */
    function media_resize_option() {
        $data = [];
        parse_str($_POST['data'], $data);
        $options = json_encode($data);
        update_option($this->optionKey, $options);
        echo 1;
        wp_die();
    }

    function frontendHook() {
        
    }

}

$imageResizer = new ImageResizer();
//var_dump($imageResizer);
