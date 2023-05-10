<?php
/*
  Plugin name:  Wp qrcode

  Plugin URI: https://www.sarkarifindjob.com

  Description: This is a free contemporary multi-purpose responsive plugin will Generate the Qrcode Based on the  Shortcode

  Author: Sarkari Find Job

  Author URI: https://www.sarkarifindjob.com

  Version: 1.0.9

  Licens: GPL2

 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly 
if (!class_exists('QRcode')) {
    include('lib/qrlib.php');
}

/**
 * public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false) 
 * 
 * @param type $atts
 * @return type
 * 
 */
function autoqrcode_func($atts) {
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/autoqrcode/';
    $size = isset($atts['size']) ? $atts['size'] : 3;
    $margin = isset($atts['margin']) ? $atts['margin'] : 4;
    $name = str_replace(' ', '', wp_qrcode_clean($atts['qrcode']) . $size . $margin);
    if (!file_exists($upload_dir . $name . '.png')) {
        QRcode::png($atts['qrcode'], $upload_dir . $name . '.png', QR_ECLEVEL_H, $size, $margin);
    }
    $get_img = $upload['baseurl'] . '/autoqrcode/' . $name . '.png';
    return "<img src=$get_img>";
}

add_shortcode('auto_qrcode', 'autoqrcode_func');

/**
 *  Make Qr Code Image at uploads Directory
 *  @since 1.0.1
 */
function autoqrcode_activate() {
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/autoqrcode';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0700);
    }
}

register_activation_hook(__FILE__, 'autoqrcode_activate');

/**
 * Register and load the widget
 * @since 1.0.3
 */
function wpqr_load_widget() {
    register_widget('wpqr_widget');
}

add_action('widgets_init', 'wpqr_load_widget');

class wpqr_widget extends WP_Widget {

    function __construct() {
        parent::__construct(
                'wpqr_widget',
                __('Qr Code', 'Qr Code'),
                array('description' => __('Widgets for Qr Code Generator', 'wpqr_widget_domain'),)
        );
    }

    /**
     * Creating widget front-end 
     * public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false) 
     * 
     * @param type $args
     * @param type $instance
     */
    public function widget($args, $instance) {
        $qrcode = isset($instance['qrcode']) ? $instance['qrcode'] : '';
        if (isset($qrcode) && !empty($qrcode)) {
            $size = isset($instance['size']) ? $instance['size'] : 3;
            $margin = isset($instance['margin']) ? $instance['margin'] : 4;
            $upload = wp_upload_dir();
            $upload_dir = $upload['basedir'];
            $upload_dir = $upload_dir . '/autoqrcode/';
            $name = str_replace(' ', '', $qrcode . $size . $margin);
           if (!file_exists($upload_dir . $name . '.png')) {

                QRcode::png($qrcode, $upload_dir . $name . '.png', QR_ECLEVEL_H, $size, $margin);
            }
            $get_img = $upload['baseurl'] . '/autoqrcode/' . $name . '.png';
            echo "<img src=$get_img>";
        } else {
            echo "Enter Qr Code";
        }
    }

    /**
     *  Widget Backend 
     * @param type $instance
     * 
     */
    public function form($instance) {

        $qrcode = (isset($instance['qrcode']) && !empty($instance['qrcode'])) ? $instance['qrcode'] : 'wpqrcode';
        $size = (isset($instance['size']) && !empty($instance['size'])) ? $instance['size'] : 6;
        $margin = (isset($instance['margin']) && !empty($instance['margin'])) ? $instance['margin'] : 4;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('qrcode'); ?>"><?php _e('Enter Your Qrcode:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('qrcode'); ?>" name="<?php echo $this->get_field_name('qrcode'); ?>" type="text" value="<?php echo esc_attr($qrcode); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('size'); ?>"><?php _e('Enter Qrcode size:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" type="text" value="<?php echo esc_attr($size); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('margin'); ?>"><?php _e('Enter  Qrcode margin:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('margin'); ?>" name="<?php echo $this->get_field_name('margin'); ?>" type="text" value="<?php echo esc_attr($margin); ?>" />
        </p>
        <?php
    }

    /**
     * Updating widget replacing old instances with new
     * 
     * @param type $new_instance
     * @param type $old_instance
     * @return type
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['qrcode'] = (!empty($new_instance['qrcode']) ) ? strip_tags($new_instance['qrcode']) : '';
        $instance['size'] = (!empty($new_instance['size']) ) ? strip_tags($new_instance['size']) : '';
        $instance['margin'] = (!empty($new_instance['margin']) ) ? strip_tags($new_instance['margin']) : '';
        return $instance;
    }

}

/**
 *  
 * wp_qrcode_clean the String
 * 
 * @param type $string
 * @return string  
 * @since 1.0.4
 */
function wp_qrcode_clean($string) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

    return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}
