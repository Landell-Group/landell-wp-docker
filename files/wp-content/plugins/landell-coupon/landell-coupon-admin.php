<?php

/*
  FileVersion: 1.0.50
*/


	function ui_new_role() {

    $business_user_capabilities = array(
            'coupon_view'   => true,
            'coupon_edit' => true,
            'coupon_list_users' => true,
            'coupon_create' => false,
            'coupon_delete' => false
        );
    //add the new user role
    add_role(
        'Client',
        'Business customer',
        $business_user_capabilities
    );

    if (current_user_can( 'administrator' )) {
        $user = wp_get_current_user();
        foreach( $business_user_capabilities as $cap => $value ) {
                $user->add_cap( $cap );
        }
    }
}

/* Custom Post Type End */
function landell_coupon_start_render() {
    global $title;

    $file = plugin_dir_path( __FILE__ ) . "tmpl/" . "landell-list_coupons-admin.php";

    if ( file_exists( $file ) )
        require $file;
}

function landell_get_b2b_users() {
        $args = array(
        'role'    => 'Client',
        'orderby' => 'user_nicename',
        'order'   => 'ASC'
    );
    return get_users( $args );

}

// add_action( 'init', 'create_posttype' );
add_action('admin_init', 'ui_new_role');

add_action( 'show_user_profile', 'extra_user_profile_fields' );
add_action( 'edit_user_profile', 'extra_user_profile_fields' );

/*** START CUSTOM Column, Sort and Filter in User view ***/
function new_contact_methods( $contactmethods ) {
    $contactmethods['klippkort'] = 'Klippkort';
    return $contactmethods;
}
add_filter( 'user_contactmethods', 'new_contact_methods', 10, 1 );


function new_modify_user_table( $column ) {
    $column['klippkort'] = 'Klippkort';
    return $column;
}
add_filter( 'manage_users_columns', 'new_modify_user_table' );

function new_modify_user_table_row( $val, $column_name, $user_id ) {

    switch ($column_name) {
        case 'klippkort' :
            return implode(",",get_user_vouchers($user_id));
        default:
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );
add_action('restrict_manage_users', 'filter_by_coupon_product');

//
// Adds <select>...coupon...</select> to user profile list view
//
function filter_by_coupon_product($which) {

	// template for filtering
 	$st = '<select name="job_role_%s" style="float:none;margin-left:10px;">
    <option value="">%s</option>%s</select>';
	$options = "";

	// identify if it's top or bottom filter that has been used
	$top = (!empty($_GET['job_role_top'])) ? $_GET['job_role_top'] : null;
	$bottom = (!empty($_GET['job_role_bottom'])) ? $_GET['job_role_bottom'] : null;
	$selected_field = '';
	$selected = '';
	if (!empty($top) OR !empty($bottom))
	{
	 $selected = !empty($top) ? $top : $bottom;
	 $selected_field = !empty($top) ? 'top' : 'bottom';
 	}

	// generate options for <select>
	foreach(get_all_vouchers() as $key => $value) {
		if ($selected_field == $which && $selected ==  "coupon-{$key}") {
			$options.= "<option value=\"coupon-{$key}\" selected>".$value."</option>";
		} else {
			$options.= "<option value=\"coupon-{$key}\">".$value."</option>";
		}
	}



 // combine template and options
 $select = sprintf( $st, $which, __( 'Klippkort...' ), $options );

 // output <select> and submit button
 echo $select;
 submit_button(__( 'Filter' ), null, $which, false);
}

add_filter('pre_get_users', 'filter_users_by_job_role_section');

//
// Logic for <select>...coupon...</select> to user profile list view
//
function filter_users_by_job_role_section($query)
{
 global $pagenow;
 if (is_admin() && 'users.php' == $pagenow) {
  // figure out which button was clicked. The $which in filter_by_coupon_product()
	$top = (!empty($_GET['job_role_top'])) ? $_GET['job_role_top'] : null;
	$bottom = (!empty($_GET['job_role_bottom'])) ? $_GET['job_role_bottom'] : null;
  if (!empty($top) OR !empty($bottom))
  {
   $section = !empty($top) ? $top : $bottom;

   // change the meta query based on which option was chosen
   $meta_query = array (array (
      'key' => $section,
      'value' => 0,
      'compare' => '>=',
			'type' => 'NUMERIC'
   ));

   $query->set('meta_query', $meta_query);
  }
 }
}


function extra_user_profile_fields( $user ) {
  //if( current_user_can( 'administrator' ) || current_user_can( 'coupon_create' ) ) {
  //}

  if (!user_can( $user->ID, 'coupon_edit' )) {
      return;
  }

?>
<script>
jQuery(function($){
    //Move my HTML code below user's role
    $('.create_new_card').insertBefore($('#role').parentsUntil('tr').parent());

    //$('.form-table tr').insertAfter($('#role').parentsUntil('tr').parent());
});
</script>
<?php } ?>
<?php
/******** START ADD FIELD TO USER PROFILE VIEW *******/
add_action( 'show_user_profile', 'render_user_profile_cards' );
add_action( 'edit_user_profile', 'render_user_profile_cards' );

function render_user_profile_cards( $user ) {

	// save card to account
	if (!empty($_GET['save_id']) && is_numeric($_GET['save_id'])) {
		add_user_voucher($user, intval($_GET['save_id']));
	}

	if (!empty($_GET['del_id']) && is_numeric($_GET['del_id'])) {
		del_user_voucher($user, intval($_GET['del_id']));
	}

	if (!empty($_GET['card_id'])) {
		$user = get_user_by( 'id', $_GET['user_id'] );
		$MAX_COUPON_MARKS = get_number_of_checkboxes($_GET['card_id']);
		if (!empty($_GET['do']) && $_GET['do'] == "inc") {
			inc_coupon_count($_GET['card_id'], $user, $MAX_COUPON_MARKS);
		} else if (!empty($_GET['do']) && $_GET['do'] == "dec") {
			dec_coupon_count($_GET['card_id'], $user);
		}
	}?>
		<h3><?php _e("Alla klippkort", "blank"); ?></h3>
		<?php
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

		$list_page_url = get_current_page_url();

		echo "<table>";
		echo "<tr><th style='text-align:left;border-bottom:1px solid #c0c0c0'>Klippkort</th><th style='border-bottom:1px solid #c0c0c0'><small>Antal Stämplar / Totalt Antal Stämplar</small></th><th style='min-width:25vw;border-bottom:1px solid #c0c0c0'>Åtgärder</th></tr>";

		while ($query->have_posts()) {

			$query->the_post();

			// stamps
			$checked_boxes = get_coupon_count(get_the_ID(), $user);
			$checkboxes    = get_number_of_checkboxes(get_the_ID());

			//if (empty($checked_boxes) && strval($checked_boxes) !== '0') {
			//	$checked_boxes = "0";
			//}

			echo "<tr>";


	    $user_card_meta = get_user_meta( $user->ID, 'coupon-'.get_the_ID(), true);
	    $user_have_this_card = !(empty($user_card_meta) && $user_card_meta !== '0');

			if ($user_have_this_card) {
				echo "<td style='text-align:left;'><h4 class='card-title'>".get_the_title()."</h5></td>";
			} else {
				echo "<td style='text-align:left;'><h4 class='card-title'>".get_the_title()."</h5></td>";
			}

			if (empty($checked_boxes) && strval($checked_boxes) !== '0') {
				echo "<td><h3 class='card-title' style='text-align:center;'>-</h3></td>";
			} else {
				echo "<td><h3 class='card-title' style='text-align:center;'>{$checked_boxes} av {$checkboxes}</h3></td>";
			}
			echo "<td style='min-width:25vw;text-align:center;'>";
			if ($user_have_this_card) {

				echo "<button type='button' id='dec_".get_the_ID()."' class='btn btn-secondary text-white btn-sm' data-toggle='button' aria-pressed='false' autocomplete='off'>
					<a class='text-black' href='".get_user_profile_url()."?card_id=".get_the_ID()."&do=dec&user_id={$user->ID}'>-1</a>
				</button>&nbsp;";

				echo "&nbsp;<button type='button' id='inc_".get_the_ID()."' class='btn btn-secondary text-white btn-sm' data-toggle='button' aria-pressed='false' autocomplete='off'>
					<a class='text-black' href='".get_user_profile_url()."?card_id=".get_the_ID()."&do=inc&user_id={$user->ID}'>+1</a>
				</button>&nbsp;";
			} else {
				echo "<button type='button' id='save_".get_the_ID()."' class='btn btn-primary' data-toggle='button' aria-pressed='false' autocomplete='off'>
					<a class='text-black' href='".get_user_profile_url()."?card_id=".get_the_ID()."&user_id={$user->ID}&save_id=".get_the_ID()."'>Lägg till på konto</a>
				</button>";
			}
			echo "&nbsp;";

			if($user_have_this_card) {
				echo "<button type='button' id='del_".get_the_ID()."' class='btn btn-danger' data-toggle='button' aria-pressed='false' autocomplete='off'>
					<a class='text-red' style='color:red;' onClick=\"return confirm('Warning: Your about to delete this card from the user..'); \" href='".get_user_profile_url()."?card_id=".get_the_ID()."&user_id={$user->ID}&del_id=".get_the_ID()."'>X</a>
				</button>";
			} else {

			}

			echo "</td>";

			echo "</tr>";

		}
		echo "</table>";
 }
