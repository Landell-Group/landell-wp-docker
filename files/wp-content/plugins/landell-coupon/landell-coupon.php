<?php
	ob_start();
	ob_clean();
	//ob_start();
/*
  Plugin Name: Landell Coupon
  Plugin URI: https://landellgroup.com/wordpress
  Description: SverigeShopping
  Author: Emanuel Ländell
  Version: 1.0.50
  Author URI: https://landellgroup.com/
*/

require_once("landell-coupon-admin.php");
require_once("landell-coupon-woocommerce.php");
require_once("tmpl/" . "landell-render_coupon.inc.php");
require_once("tmpl/" . "landell-list_coupons.inc.php");
require_once("tmpl/" . "landell-list_voucher_code.inc.php");
require_once("tmpl/" . "landell-list_voucher_code.inc.php");

// for QR codes
require_once("lib/phpqrcode/qrlib.php");


define("LANDELL_DEBUG", false);

function get_plugin_url() {
	if(defined("LANDELL_DEBUG") && LANDELL_DEBUG === true) {
		return urlencode("klippkortet");
	}
	return urlencode("render-loyalty-card");
}

function generate_url_to_voucher($instance_id = 0, $preview = true) {
	$fullUrl = get_home_url()."/".get_plugin_url()."?i=".strval($instance_id);

	if (!$preview) {
		/* unique id for each image */
		$fullUrl .= "&c=".create_uuid();
	}

	return $fullUrl;
}

function get_qr_code($instance_id) {
	return generate_url_to_voucher($instance_id, false);
}

function get_mypage_url() {
	return urlencode("mitt-konto");
}

function get_my_coupon_codes_url() {
	return urlencode("mina-rabattkoder");
}

function file_is_outdated($filename) {
	$max_date_age = 90;
	return time()-filemtime($filename) > $max_date_age * 86400;
}

function render_qr_code($instance_id, $size = 4) {
	$url_to_coupon = generate_url_to_voucher($instance_id, false);

	$tempDir = 'media/';
	$filename = strval($instance_id).'_H_'.strval($size).'.png';
	$pngAbsoluteFilePath = plugin_dir_path(__FILE__).$tempDir.$filename;
	$url_to_png = plugin_dir_url( __FILE__ ).$tempDir.$filename;
	//$url = plugin_dir_url( __FILE__ )

	// generating
	if (!file_exists($pngAbsoluteFilePath) || file_is_outdated($pngAbsoluteFilePath) || intval($instance_id) <= 1700) {
			QRcode::png($url_to_coupon, $pngAbsoluteFilePath, QR_ECLEVEL_H, $size);
			//echo 'File generated!';
			//echo '<hr />';
	} else {
			//echo 'File already generated! We can use this cached file to speed up site on common codes!';
			//echo '<hr />';
	}

	//QRcode::png($url, $pngAbsoluteFilePath, QR_ECLEVEL_H);
	return "<img src=\"$url_to_png\" />";
}

function is_manager() {
	return current_user_can( 'administrator' );
}

function get_login_url($relative = false) {
	if($relative) {
    $ret = str_replace( home_url( '/' ), "", get_mypage_url() );
	}

	return "/".get_mypage_url();
}

function auto_redirect_after_login() {
	if (is_manager()) {
		wp_safe_redirect( get_admin_url() );
	} else if(!empty($_GET['i']) && !empty($_GET['c'])) {
		wp_safe_redirect( get_plugin_url()."?i={$_GET['i']}&c={$_GET['c']}" );
	} else if(!empty($_GET['i'])) {
		wp_safe_redirect( get_plugin_url()."?i=".$_GET['i'] );
	} else {
		wp_safe_redirect( get_mypage_url() );
	}

  exit;
}

function create_uuid() {
	return uniqid('sad', true);
}

function get_current_page_url() {
	return get_page_link();
}


/**
* is_locked
* check if the card has already been redeemed before and that the user
* needs to wait before he/she can use it again
**/
function is_locked($current_user, $id /*card id*/) {

	$to_early = false;
	$c_user_id = $current_user->ID;
	$now   = new DateTime;

	// returns string without date, convert to object
	$vouchers_for_this_card = get_vouchers($current_user, $id);

	// check when this card was last redeemed by this user
	$user_field = 'last-card-completed'.strval($id);
	$last_voucher_completed = get_user_meta( $c_user_id, $user_field, true);

	if (empty($last_voucher_completed)) {
		return ($to_early = false);
	}

	$ts = new DateTime($last_voucher_completed);

	// check if there's any timeout period on card
	$delay = get_post_meta($id, "coupon_reset_delay", true);

	if (empty($delay) || in_array(strval($delay), ["-1","","0","null",null])) {
		return ($to_early = false);
	} else if (($delay-(intval($now->format('U'))-intval($ts->format('U')))/60) > 0) {
		//print_r(($delay-(intval($now->format('U'))-intval($ts->format('U')))/60));
		//echo "m to wait<br/>";
		$to_early = true;
	}

	//echo "(card) Timeout: {$timeout} | (user) completed at: {$last_voucher_completed} | (diff) ";


	//echo PHP_EOL."Vouchers: ";
	//print_r($vouchers_for_this_card);

	return $to_early;
}

/**
* is_redeemable
* check's if the session id has been used before
* and that the minimum interval has passed
**/
function is_redeemable($code, $current_user, $id) {

	// it can be checked either by interval+code or only code
	$valid = false;
	$user_field = 'session-codes-used'.strval($id);
	$c_user_id = $current_user->ID;
	$now   = new DateTime;

	$value = get_user_meta( $c_user_id, $user_field, true);
	if (empty($value) || !is_array($value)) {
		$value = array();
	}

	$delay = get_post_meta($id, "coupon_checkbox_delay", true);

	// old codes only have values, new once is object with timestamp
	$timestamps  = [];
	$text_values = []; // array with strings
	$updated_arr = []; // array with objects

	// get the codes as string
	foreach($value as $element) {
		if (is_string($element)) {
			$text_values[] = $element;

			$codeObj = new stdClass;
			$codeObj->timestamp = $now->format('Y-m-d H:i:s');
			$codeObj->code = $element;
			$updated_arr[] = $codeObj;
		} else {
			$text_values[] = $element->code;
			$timestamps[] = $element->timestamp;
			$updated_arr[] = $element;
		}
	}

	$result = in_array($code, $text_values);

	if (!is_numeric($delay)) {
		$valid = !$result;
	} else {

		$valid = true;
		foreach($timestamps as $timestamp) {
			if (empty($timestamp)) {
				continue;
			}
			$ts = new DateTime($timestamp);

			$interval = $now->diff($ts);

			if (($delay-(intval($now->format('U'))-intval($ts->format('U')))/60) > 0) {
				//print_r(($delay-(intval($now->format('U'))-intval($ts->format('U')))/60));
				//echo "m to wait<br/>";
				$valid = false;
			}
		}

	}

	$codeObj = new stdClass;
	$codeObj->timestamp = $now->format('Y-m-d H:i:s');
	$codeObj->code = $code;
	$updated_arr[] = $codeObj;

	if ($valid) {
		$updated = update_user_meta( $c_user_id, $user_field, $updated_arr );
	}

	return $valid;
}

/**
* save_voucher
* saves vouchers codes
**/
function save_voucher($code, $current_user, $id) {
	$user_field = 'vouchers-used'.strval($id);
	$c_user_id = $current_user->ID;
	$now   = new DateTime;

	$value = get_user_meta( $c_user_id, $user_field, true);
	if (empty($value) || !is_array($value)) {
		$value = array();
	}

	if(!in_array($code, $value)) {
		$value[] = $code;
		$updated = update_user_meta( $c_user_id, $user_field, $value );

		// set a timestamp so that there can be a cold down period before a new
		// card is generated.
		$updated = update_user_meta( $c_user_id, 'last-card-completed'.strval($id), $now->format('Y-m-d H:i:s') );
	}


	return $code;
}

/**
* get_vouchers
* get vouchers
**/
function get_vouchers($current_user, $id) {

	$user_field = 'vouchers-used'.strval($id);
	$c_user_id = $current_user->ID;

	$value = get_user_meta( $c_user_id, $user_field, true);
	if (empty($value) || !is_array($value)) {
		$value = array();
	}

	return $value;
}

/** new **/
function landell_wordpress_render_my_voucher_codes($atts) {
	return get_list_of_voucher_codes_html($show_all = false, wp_get_current_user(), $atts);
}

function landell_wordpress_render_my_loyalty_cards($atts) {
	if (!empty($_GET['save_id']) && is_numeric($_GET['save_id'])) {
		add_user_voucher(wp_get_current_user(), intval($_GET['save_id']));
	}
	return get_list_of_vouchers_html($show_all = false, wp_get_current_user(), $atts);
}

function landell_wordpress_render_all_loyalty_cards($atts) {
	if (!empty($_GET['save_id']) && is_numeric($_GET['save_id'])) {
		add_user_voucher(wp_get_current_user(), intval($_GET['save_id']));
	}
	return get_list_of_vouchers_html($show_all = true, wp_get_current_user(), $atts);
}

/** render a specific card **/
function landell_wordpress_render_loyalty_card($atts) {

	// save to my library
	if (!empty($_GET['save_id']) && is_numeric($_GET['save_id'])) {
		add_user_voucher(wp_get_current_user(), intval($_GET['save_id']));

		$goToUrl = get_post_meta($_GET['save_id'], "coupon_bookmark_url", true);

		if (empty($goToUrl)) {
			$goToUrl = "/mina-klippkort/";
		}

		wp_redirect( $goToUrl );
		exit;

	}

	// delete from my library
	if (!empty($_GET['del_id']) && is_numeric($_GET['del_id'])) {
		del_user_voucher(wp_get_current_user(), intval($_GET['del_id']));

		$goToUrl = get_post_meta($_GET['del_id'], "coupon_unbookmark_url", true);

		if (empty($goToUrl)) {
			$goToUrl = "/mina-klippkort/";
		}

		wp_redirect( $goToUrl );
		exit;

	}

	if ( !is_admin() && (!is_user_logged_in() || (is_manager() && empty($_GET["i"])))) {

		$redirect_to = get_plugin_url();
		$fullUrl = get_login_url()."&redirect_to={$redirect_to}";

		if (!empty($_GET["i"])) {
			$fullUrl = get_login_url() . "?i=".$_GET["i"] . "&redirect_to={$redirect_to}";
		} else {
			$fullUrl = get_login_url()."&redirect_to={$redirect_to}";
		}

		if (!empty($_GET["c"])) {
			if (strstr($fullUrl, "?")!==FALSE) {
				$fullUrl.= "&c=".$_GET["c"];
			}
		}

		if (is_manager()) {
			$redirect_to =  get_admin_url() ;
			$fullUrl = $redirect_to;
		}

		wp_redirect( $fullUrl );
		exit;
	}

	$card_template_id = ($_GET["i"] ? $_GET["i"] : -1);
	$distinct_session_key = (!empty($_GET['c']) && is_redeemable($_GET['c'],wp_get_current_user(), $card_template_id));

	return get_voucher_html($card_template_id, wp_get_current_user(), $distinct_session_key, $atts);
}

add_action('register_form', 'landell_new_item_register_form');

function get_voucher_design($code, $trophy_image) {

	$forward_url = get_my_coupon_codes_url();

	return '
 <div class="landell-modal fade show" id="myModal" role="dialog">
     <div class="modal-dialog">
         <div class="card">
             <div class="text-right cross"> <i class="fa fa-times"></i> </div>
             <div class="card-body text-center"> <img src="'.$trophy_image.'" style="float:left;">
			 <div class="card-right text-center" style="float:right; max-width:50%;">
			    <h4>GRATTIS!</h4>
                 <p style="margin-bottom:18px;">Här är din unika inlösenkord: <strong>'.$code.'</strong></p> <a class="btn btn-out btn-square continue" style="pointer-events: initial!important;" href="'.$forward_url.'">Spara koden på mitt konto</a>
             </div>
         </div>
     </div>
 </div>';
}

/***
	add shortcodes and register scripts
***/
function register_scripts() {
    add_shortcode('visa-klippkort', 'landell_wordpress_render_loyalty_card');
		add_shortcode('lista-egna-rabattkoder', 'landell_wordpress_render_my_voucher_codes');
		add_shortcode('lista-egna-klippkort', 'landell_wordpress_render_my_loyalty_cards');
		add_shortcode('lista-alla-klippkort', 'landell_wordpress_render_all_loyalty_cards');
    wp_enqueue_style('global', plugins_url('all-style.css',__FILE__ ));
}

add_action('init', 'register_scripts');
add_action('wp_logout','auto_redirect_after_logout');
add_action('wp_login','auto_redirect_after_login');

function enqueue_styles() {
    wp_enqueue_style('global', plugins_url('all-style.css',__FILE__ ));

}

// use the registered jquery and style above
add_action('wp_enqueue_scripts', 'enqueue_styles');
//add_action('admin_enqueue_scripts','enqueue_styles');

/** **/
function get_loyalty_card_url($id = null) {
	$image_url = get_the_post_thumbnail_url($id);
	if (!$image_url) {
		$image_url = plugin_dir_url( __FILE__ )."images/sample-card1.png";
	}
	return $image_url;
}

/**
* returns url to filler icon
**/
function get_filler_url($postid = null) {
	$val = get_post_meta($postid, "coupon_marker_url", true);

	if(empty($val)) {
		$val = plugin_dir_url( __FILE__ )."images/sample-filler1.png";
	}

	return $val;
}

/**
* returns url to trophy image
**/
function get_trophy_image_url($postid = null) {
	$val = get_post_meta($postid, "coupon_trophy_image", true);

	if(empty($val)) {
		$val = "https://img.icons8.com/bubbles/200/000000/trophy.png";
	}

	return $val;
}



/**
* returns a color to be used for the marker
* NOTE: CURRENTLY NOT USED
**/
function get_marker_color($postid) {

	$color = get_post_meta($postid, "coupon_marker_color", true);

	if(empty($color)) {
		$color = 'black';
	}

	return $color;
}

/**
* returns the voucher code
**/
function get_voucher_code($userid, $postid) {
	//$retKey = "0000-000000-0000-00001";
	//return $retKey;

	$coupon_codes_available = get_post_meta($postid, "coupon_codes_available", true);

	//die("countA: ".count($coupon_codes_available));

	if (empty($coupon_codes_available) || (count($coupon_codes_available) === 1 && empty(reset($coupon_codes_available)))) {
		$coupon_codes_available = [];
		// bad config
		for($i=0;$i<100; $i++) {
			$coupon_codes_available[$i] = "000000-0".strval($postid)."-000".strval($i+1);
		}
	}

	$coupon_codes_consumed  = get_post_meta($postid, "coupon_codes_consumed", true);
	if (empty($coupon_codes_consumed)) {
		$coupon_codes_consumed = [];
    }

	foreach($coupon_codes_available as $key => $value) {
		if (!in_array($value, $coupon_codes_consumed)) {
			$coupon_codes_consumed[$key] = $value;
			update_post_meta($postid, "coupon_codes_consumed", $coupon_codes_consumed);
			$retKey = $value;
			//var_dump($coupon_codes_available[$key]);
			break;
        }
    }

	return $retKey;
}

function get_all_vouchers() {
	$products = [];
	$query = new WP_Query(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'tax_query' => array(
					array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => 'klippkort',
					),
			)
	));

	while ($query->have_posts()) {
		$query->the_post();

		$products[get_the_ID()] = get_the_title();
	}

	return $products;
}

function get_user_vouchers($user_id) {
	$filtered = [];
	foreach(get_all_vouchers() as $key => $product_name) {
		$user_card_meta = get_user_meta( $user_id, "coupon-{$key}", true);

		$user_have_this_card = !(empty($user_card_meta) && $user_card_meta !== '0');
		if ($user_have_this_card) {
			$filtered[$key] = $product_name;
		}
	}
	return $filtered;
}

// add voucher to user account (mina sidor)
function add_user_voucher($current_user = null, $id = null) {

	$user_field = 'coupon-'.strval($id);
	$value = get_user_meta( $current_user->ID, $user_field, true);

	if (!empty($value)) {
		return false;
	}

  $updated = update_user_meta( $current_user->ID, $user_field, $new_value=0 );
}

// remove voucher to user account (mina sidor)
function del_user_voucher($current_user = null, $id = null) {

	$user_field = 'coupon-'.strval($id);
	$value = get_user_meta( $current_user->ID, $user_field, true);

	if (empty($value) && $value !== '0') {
		return false;
	}

  $updated = delete_user_meta( $current_user->ID, $user_field);
}

function inc_coupon_count($id = null, $current_user = null, $MAX_COUPON_MARKS = 10) {
    $user_field = 'coupon-'.strval($id);
    $c_user_id = $current_user->ID;

    $value = get_user_meta( $c_user_id, $user_field, true);
    $new_value = 0;
    if (is_numeric($value) && intval($value) < $MAX_COUPON_MARKS) {
        $new_value = intval($value);
    }
    $new_value++;

    $updated = update_user_meta( $c_user_id, $user_field, $new_value );
}


function dec_coupon_count($id = null, $current_user = null) {
    $user_field = 'coupon-'.strval($id);
    $c_user_id = $current_user->ID;

    $value = get_user_meta( $c_user_id, $user_field, true);
    $new_value = 0;
    if (is_numeric($value)) {
        $new_value = intval($value);
    }
    $new_value--;
		if ($new_value <= 0) {
			$new_value = 0;
		}

    $updated = update_user_meta( $c_user_id, $user_field, $new_value );
}


/**
* returns number of checkboxes in picture
**/
function get_number_of_checkboxes($postid) {
	$val = get_post_meta($postid, "coupon_checkbox_squares", true);

	if(empty($val) || !is_numeric($val) ) {
		$val = 10;
	}

  return intval($val);
}

/*** returns number as string of "checked" boxes in card ***/
function get_coupon_count($id = null, $current_user = null) {

    $user_field = 'coupon-'.strval($id);

    $c_user_id = $current_user->ID;
    if(!empty($user_field))
    {
        $value = get_user_meta( $c_user_id, $user_field, true);
        return $value;
    }

    if(!empty($user_field))
    {
        $current_user->{"$user_field"};
    }

    return "1";
}

//Remove error for username, only show error for email only.
add_filter('registration_errors', function($wp_error, $sanitized_user_login, $user_email){
    if(isset($wp_error->errors['empty_username'])){
        unset($wp_error->errors['empty_username']);
    }

    if(isset($wp_error->errors['username_exists'])){
        unset($wp_error->errors['username_exists']);
    }
    return $wp_error;
}, 10, 3);

add_action('login_form_register', function(){
    if(isset($_POST['user_login']) && isset($_POST['user_email']) && !empty($_POST['user_email'])){
        $_POST['user_login'] = $_POST['user_email'];
    }
});


/* ****************************************************************************** */

// AUTO LOGIN STARTS HERE

/* ****************************************************************************** */

function landell_load_plugin_textdomain() {
    load_plugin_textdomain( 'auto-login-new-user-after-registration', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'landell_load_plugin_textdomain');


/* ****************************************************************************** */
function landell_disable_admin_new_user_notification_email($result = '') {
	extract($result); //Array KEY name becomes variable name and KEY value becomes variable value. Should create $to, $subject, $message, $headers, $attachments
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	$admin_email = get_option('admin_email');

	return $result;

}
add_filter('wp_mail', 'landell_disable_admin_new_user_notification_email');

/* ****************************************************************************** */
function landell_auto_login_new_user_after_registration( $user_id ) {



		if ( 1 == 1 || isset($_POST['alnuar']) && $_POST['alnuar'] == "yes" ) { // Need to verify that new user registration came from this plugin
			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id);

			global $_POST;
			if (isset($_POST['redirect_to']) && $_POST['redirect_to'] != "") {
				$redirect = $_POST['redirect_to'];
			} else if (isset($_GET['redirect_to']) && $_GET['redirect_to'] != "") {
				$redirect = $_GET['redirect_to'];
				if(!empty($_GET['i'])) {
					$redirect.=("?i=".$_GET['i']);
				}

				if(!empty($_GET['c'])) {
					$redirect.=("&c=".$_GET['c']);
				}
			} else {
				//$redirect = get_home_url();
				//$redirect .= "/wp-login.php?checkemail=registered";
				$redirect = get_login_url();
			}

		//	print_r($_POST);


			// This does the redirection if we are on default registration page. If we are on any other page, then do not redirect. This fixes WooCommerce bug.
			if (isset($_POST['wp-submit']) && $_POST['wp-submit'] == "Register") {
				wp_redirect($redirect);
				wp_new_user_notification($user_id, null, 'both'); //'admin' or blank sends admin notification email only. Anything else will send admin email and user email
				exit;

			} else if (isset($_POST['register'])) {
				wp_redirect($redirect);
				wp_new_user_notification($user_id, null, 'both'); //'admin' or blank sends admin notification email only. Anything else will send admin email and user email
				exit;
			}

			else {
				// do nothing and SKIP REDIRECTION (fixes WooCommerce bug)
			}

		}

	//}
}
add_action( 'user_register', 'landell_auto_login_new_user_after_registration' );


/* ****************************************************************************** */
function landell_new_item_register_form() {

		$password1 = ( ! empty( $_POST['password1'] ) ) ? trim( $_POST['password1'] ) : '';
		$password2 = ( ! empty( $_POST['password2'] ) ) ? trim( $_POST['password2'] ) : '';
	    ?>
		<p>
			<label for="password1"><?php _e( 'Password:','auto-login-new-user-after-registration') ?>
			<input type="password" name="password1" id="password1" class="input" value="<?php echo esc_attr( wp_unslash( $password1 ) ); ?>" size="25" /></label><br>
			<label for="password2"><?php _e( 'Confirm Password:','auto-login-new-user-after-registration') ?>
			<input type="password" name="password2" id="password2" class="input" value="<?php echo esc_attr( wp_unslash( $password2 ) ); ?>" size="25" /></label><br>
		</p>
		<?php
	//}
		?>
			<input type="hidden" name="alnuar" id="alnuar" value="yes">
		<?php
}

/* ****************************************************************************** */
function landell_register_form_errors( $errors, $sanitized_user_login, $user_email ) {
		if ( empty( $_POST['password1'] ) || ! empty( $_POST['password1'] ) && trim( $_POST['password1'] ) == '' ) {
			$errors->add( 'password1_error', __( '<strong>ERROR</strong>: Password field is required.','auto-login-new-user-after-registration') );
		}
		if ( empty( $_POST['password2'] ) || ! empty( $_POST['password2'] ) && trim( $_POST['password2'] ) == '' ) {
			$errors->add( 'password2_error', __( '<strong>ERROR</strong>: Confirm Password field is required.','auto-login-new-user-after-registration') );
		}
		if ( $_POST['password1'] != $_POST['password2'] ) {
			$errors->add( 'password12_error', __( '<strong>ERROR</strong>: Password field and Confirm Password field do not match.','auto-login-new-user-after-registration') );
		}

    return $errors;
}
add_filter( 'registration_errors', 'landell_register_form_errors', 10, 3 );
