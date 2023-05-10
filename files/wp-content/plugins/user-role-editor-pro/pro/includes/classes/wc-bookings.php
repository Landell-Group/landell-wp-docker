<?php
/*
 * Intergration of 'Edit restrictions' module with "Woocommerce Bookings" plugin
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */


class URE_WC_Bookings {

    const TRANSIENT_KEY = 'book_dr_';
    const TRANSIENT_KEY_ESC = 'book\_dr\_';
    
    private $user = null;
    
    
    public function __construct($pea_user) {
        $this->user = $pea_user;
        add_filter('get_booking_products_args', array($this, 'get_booking_products_args'), 10, 1);
        add_filter('woocommerce_bookings_in_date_range_query', array($this, 'restrict_wc_bookings'), 10, 1);
        
    }
    // end of init()
    
    
    public static function separate_user_transients() {
        
        add_action('setted_transient', 'URE_WC_Bookings::save_transient_user', 10, 3);
        add_action('ure_save_user_edit_content_restrictions', 'URE_WC_Bookings::remove_transients', 10);
        self::set_transients_filter();
        
    }
    
    /**
     * Apply edit restrictions to the Woocommerce Bookings list, generated by 'Woocommerce Bookings' plugin
     * @param array $booking_ids (just array of integers)
     * @return array
     */
     
    public function restrict_wc_bookings($booking_ids) {
     
        // do not limit user with Administrator role
        if (!$this->user->is_restriction_applicable()) {
            return $booking_ids;
        }
        
        $posts_list = $this->user->get_posts_list( 'wc_booking' );
        if (count($posts_list)==0) {
            return $booking_ids;
        }
        $restriction_type = $this->user->get_restriction_type();
        $booking_ids1 = array();
        foreach($booking_ids as $booking_id) {
            if ($restriction_type==1) { // Allow: not edit others
                if (in_array($booking_id, $posts_list)) {    // not edit others
                    $booking_ids1[] = $booking_id;                    
                }
            } else {    // Prohibit: Not edit these
                if (!in_array($booking_id, $posts_list)) {    // not edit these
                    $booking_ids1[] = $booking_id;
                }                
            }            
        }
        
        return $booking_ids1;
    }
    // end of restrict_wc_bookings()

    
    public static function remove_transients() {
        global $wpdb;
        
        $key = self::TRANSIENT_KEY_ESC;
        $query = "select option_name from {$wpdb->options} where option_name like '\_transient\_{$key}%'";
        $data = $wpdb->get_col($query);
        if (empty($data)) {
            return;
        }
        
        foreach($data as $record) {
            $transient = str_replace('_transient_', '', $record);
            delete_transient($transient);
        }
        
    }
    // end of remove_transients()
            

    public static function get_user_transient($value, $transient) {
                
        $current_user_id = get_current_user_id();        
        if ($current_user_id==0) {
            return false;
        }
        
        $value = get_transient($transient .'-'. $current_user_id);
        if (empty($value)) {
            delete_transient($transient);
            return false;
        }
                
        return $value;
    }
    // end of get_user_transient()

    
    public static function save_transient_user($transient, $value, $expiration) {
        
        $current_user_id = get_current_user_id();
        if ($current_user_id==0) {
            return false;
        }
        if (substr($transient, 0, strlen(self::TRANSIENT_KEY))!==self::TRANSIENT_KEY) {
            return false;
        }
        remove_action('setted_transient', 'URE_WC_Bookings::save_transient_user', 10);
        set_transient($transient .'-'. $current_user_id, $value, $expiration);
        add_action('setted_transient', 'URE_WC_Bookings::save_transient_user', 10, 3);
        
        add_filter('pre_transient_' . $transient, 'URE_WC_Bookings::get_user_transient', 10, 2);
        
        return true;
    }
    // end of save_trancient_user()
    

    public static function set_transients_filter() {        
        global $wpdb;
        
        $key = self::TRANSIENT_KEY_ESC;
        $query = "select option_name, option_value from {$wpdb->options} where option_name like '\_transient\_{$key}%'";
        $data = $wpdb->get_results($query);
        if (empty($data)) {
            return;
        }
        foreach($data as $record) { 
            $transient = str_replace('_transient_', '', $record->option_name);
            if (strpos($transient, '-')===false) {
                add_filter('pre_transient_' . $transient, 'URE_WC_Bookings::get_user_transient', 10, 2);
            }
        }
    }
    // end of set_transients_filter() 
    
    
    public function get_booking_products_args($args) {
        $args['suppress_filters'] = false;
        
        return $args;
    }
    // end of get_booking_products_args()
    
    
    /**
     * Add related bookings created by WooCommerce Bookings plugin
     * 
     * @global string $pagenow
     * @global WPDB $wpdb
     * @param array $posts
     * @return array
     */
    public static function add_related_wc_bookings( $posts ) {
        global $pagenow, $wpdb;
        
        if ( !URE_Plugin_Presence::is_active('woocommerce-bookings') || 
            $pagenow!=='edit.php' || $_SERVER['QUERY_STRING']!=='post_type=wc_booking') {
            return $posts;
        }
        
        // Add WooCommerce bookings with products linked to the owner/vendor
        $add_bookings = apply_filters('ure_edit_posts_access_add_bookings_by_product_owner', true ) ;
        if ( !$add_bookings ) {
            return $posts;
        } 
        
        $current_user_id = get_current_user_id();
        $query = "SELECT ID from {$wpdb->posts} WHERE post_type='wc_booking'";
        $list = $wpdb->get_col( $query );
        $meta_field = '_booking_product_id';
        $products = array();
        $bookings = array(); 
        foreach ( $list as $post_id ) {
            $custom_fields = get_post_meta($post_id);
            if ( !isset( $custom_fields[$meta_field][0] ) || $custom_fields[$meta_field][0]==='') {
                continue;
            }
            $product_id = $custom_fields[$meta_field][0];
            if ( !isset( $products[$product_id] ) ) {
                $product = get_post($product_id);
                if ( !empty( $product ) ) {
                    $products[$product_id] = (int) $product->post_author;
                } else {
                    $products[$product_id] = null;
                }
            }
            if ( $products[$product_id]===$current_user_id ) {
                //$booking = new stdClass();
                //$booking->ID = $post_id;
                //$booking->product_id = $product_id;
                //$booking->post_type = 'wc_booking';
                $bookings[] = $post_id;
            }
        }
        $posts = ure_array_merge( $posts, $bookings );
        
        return $posts;
    }
    // end of add_related_wc_orders()
        
}
// end of URE_WC_Bookings class