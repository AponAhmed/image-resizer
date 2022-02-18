<?php

namespace ImageResizer\lib;

/**
 * Description of AdminView
 *
 * @author Mahabub
 */
class AdminView {

    //put your code here
    public function __construct($option) {
        $this->option = $option;
        /**
         * Register  to the admin_menu action hook.
         */
        add_action('admin_menu', [$this, 'resize_admin_menu']);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * Admin Css and JS
     */
    function admin_enqueue_scripts() {
        wp_register_style('resize-admin-css', IMR_ASSET_URI . 'admin-style.css', false, '1.0.0');
        wp_enqueue_style('resize-admin-css');
        wp_enqueue_script('resize-admin-scripts', IMR_ASSET_URI . 'admin-script.js', array('jquery'), '1.0');
        wp_localize_script('resize-admin-scripts', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    /**
     * Admin Menu Register 
     */
    function resize_admin_menu() {
        add_submenu_page(
                'tools.php',
                'Media Size Builder',
                'Media Size Builder',
                'manage_options',
                'media-size-settings',
                [$this, 'media_size_settings_page']
        );
    }

    /**
     * Callback Of Media size settings Admin Page
     */
    function media_size_settings_page() {
        global $_wp_additional_image_sizes, $wpdb;
        //echo "<pre>";
        //var_dump($_wp_additional_image_sizes,$this->option);
        // echo "</pre>";
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1><hr>

            <div>
                <div class="buildingStatus">
                    <div class="progress">
                        <?php
                        $lastID = get_option('lastBuildProcess');
                        $lastID = empty($lastID) ? 0 : $lastID;
                        $done = $wpdb->get_results("SELECT COUNT(*) as done FROM {$wpdb->prefix}posts WHERE post_type='attachment' and ID <= $lastID");
                        //var_dump($done);
                        $total = $wpdb->get_results("SELECT COUNT(*) as total FROM {$wpdb->prefix}posts WHERE post_type='attachment'");
                        $prc = 0;
                        if (isset($total[0]) && $total[0]->total > 0) {
                            $prc = ($done[0]->done * 100) / $total[0]->total;
                        }
                        ?>
                        <div class="progress-bar progress-bar-striped  buildProgress" role="progressbar" aria-valuenow="<?php echo $prc ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $prc ?>%"></div>
                    </div>
                </div>
                <button type="button" class="ImageBuildTriger button button-primary" data-state='false' onclick="ImageBuildTriger(this)">Start</button>
                <?php
                if (isset($total[0]) && $total[0]->total > 0) {
                    if ($done[0]->done == $total[0]->total) {
                        ?>
                        <span class="completeResp">&nbsp;&nbsp;Process Complete.<a href="javascript:void(0)" onclick="clearHistoryImgBuild()">Clear History</a></span>

                        <?php
                    }
                }
                ?>  
            </div>
            <br>
            <form>
                <label><strong>Additional Sizes</strong></label>
                <div class="size-editor-wrap">
                    <div class="sizeSethead">
                        <label>Name</label>
                        <label class="sizeinput">Width</label>
                        <label class="sizeinput">Height</label>
                    </div>
                    <?php
                    foreach ($this->option['media-size'] as $k => $size) {
                        ?>
                        <div class="size-item" data-index="<?php echo $k ?>">
                            <input type="text" class="sizeName" name="media-size[<?php echo $k ?>][name]" value="<?php echo $size['name'] ?>" placeholder="Name">
                            <input type="text" class="sizeinput" name="media-size[<?php echo $k ?>][width]" value="<?php echo $size['width'] ?>" placeholder="Width">
                            <input type="text" class="sizeinput" name="media-size[<?php echo $k ?>][height]" value="<?php echo $size['height'] ?>" placeholder="Height">
                            <span class="reSizeItem" onclick="reSizeItem(this)">&times;</span>
                        </div>
                        <?php
                    }
                    ?>

                    <div class="newSize">
                        <span id="newSize">New</span>
                    </div>
                    <?php
                    $sizesReg = [];
                    foreach ($_wp_additional_image_sizes as $k => $vv) {
                        $sizesReg[] = $k . "(" . $vv['width'] . ")";
                    }
                    $sizesReg = array_unique($sizesReg);
                    sort($sizesReg);
                    echo "<label><strong>Registerted Sizes : </strong>" . implode(", ", $sizesReg) . "</label> ";
                    ?>
                </div>
                <label>Quality (%)</label>
                <input type="text" value="<?php echo $this->option['imageQuality'] ?>" name="imageQuality">&nbsp;&nbsp;
                <input type="hidden" name="replace_existingSize" value="0">
                <label><input type="checkbox" value="1" <?php echo $this->option['replace_existingSize'] == '1' ? 'checked' : "" ?> name="replace_existingSize"> Replace Existing</label>
                <hr><!-- comment -->
                <button type="button" class="button button-primary" onclick="updateMediaSizeOption(this)">Update</button>
            </form>
        </div>
        <?php
    }

}
