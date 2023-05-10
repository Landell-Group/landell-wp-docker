<?php
/*
  Plugin Name: Landell Bulk Edit
  Plugin URI: https://landellgroup.com/wordpress
  Description: SverigeShopping
  Author: Emanuel Ländell
  Version: 1.0.0
  Author URI: https://landellgroup.com/
*/

//if ( ! check_ajax_referer( 'wcfm_ajax_nonce', 'wcfm_ajax_nonce', false ) ) {
//	wp_send_json_error( __( 'Invalid nonce! Refresh your page and try again.', 'wc-frontend-manager' ) );
//	wp_die();
//}

//add_action( 'woocommerce_product_bulk_edit_end', 'wcfmmp_bulk_store_edit_landell');
add_action( 'wcfm_product_bulk_edit_end', 'wcfmmp_bulk_store_edit_landell' );
//add_action( 'woocommerce_product_bulk_edit_save', 'wcfmmp_bulk_store_edit_save2' );
add_action( 'wcfm_product_bulk_edit_save', 'wcfmmp_bulk_store_edit_save_landell', 10, 2 );

/**
* Register CSS/JS Scripts
**/
function landell_bulk_edit_register_scripts() {
	$other_plugin_base_url = '/wp-content/plugins/wc-frontend-manager';

	/* gives error */
	// wp_enqueue_script(
	//  'wcfm_products_manage_js', // name your script so that you can attach other scripts and de-register, etc.
	//  $other_plugin_base_url."/assets/js/products-manager/wcfm-script-products-manage.js", // this is the location of your script file
	//  array('jquery', 'select2_js', 'jquery-ui-sortable') // this array lists the scripts upon which your script depends
	// );

	wp_enqueue_style(
		'landell_bulk_custom_css', // name your script so that you can attach other scripts and de-register, etc.
		$other_plugin_base_url."/assets/css/products-manager/wcfm-style-products-manage.css" // this is the location of your script file
	);

	wp_enqueue_style('wcfm_menu_css', $other_plugin_base_url."/assets/css/" . 'menu/wcfm-style-menu.css');
}
add_action('init', 'landell_bulk_edit_register_scripts');

	function get_selected_categories($product_id, $tax) {
		$categories = array();
		$pcategories = get_the_terms( $product_id, $tax );
		if( !empty($pcategories) ) {
			foreach($pcategories as $pkey => $pcategory) {
				$categories[] = $pcategory->term_id;
			}
		} else {
			$categories = array();
		}

		return $categories;
	}

	/**
	 * Bulk Category Assign / Change
	 */
	function wcfmmp_bulk_store_edit_landell($p) {

		global $WCFM, $WCFMmp, $wpdb;

		$selected_products = (array)$_POST['selected_products'];

		$product_id = reset($selected_products);

		$categories = get_selected_categories($product_id, "product_cat");
		$municipalities = get_selected_categories($product_id, "kommunort");
		$producttypes = get_selected_categories($product_id, "produktvara");

		?>

		<!-- kategorier -->
		<label>
			<div style="width:100%">
		  <span class="wcfm_popup_label title"><strong><?php echo apply_filters( 'wcfm_taxonomy_custom_label', __( 'Categories', 'wc-frontend-manager' ), 'product_cat' ); ?></strong></span>

				<div class="wcfm_popup_label title" style="width: 100%;min-height: 1vh;overflow: scroll;border: 1px solid #c0c0c0;">

					<ul class="product_taxonomy_checklist">
					<?php
					$product_categories    = get_terms( 'product_cat', 'orderby=name&hide_empty=0&parent=0' );

					$WCFM->library->generateTaxonomyHTML( 'product_cat', $product_categories, $categories, '', true );
					?>
				</ul>
				</div>
	</div>
		</label>
		<!-- </kategorier -->

		<!-- kommunort -->
		<label>
			<div style="width:100%">
			<span class="wcfm_popup_label title"><strong><?php echo apply_filters( 'wcfm_taxonomy_custom_label', __( 'Kommun/Ort', 'wc-frontend-manager' ), 'kommunort' ); ?></strong></span>

				<div class="wcfm_popup_label title" style="width: 100%;min-height: 1vh;overflow: scroll;border: 1px solid #c0c0c0;">
					<ul class="product_taxonomy_checklist">

						<?php
						$product_kommunort    = get_terms( 'kommunort', 'orderby=name&hide_empty=0&parent=0' );

						$WCFM->library->generateTaxonomyHTML( 'kommunort', $product_kommunort, $municipalities, '', true, true );
						?>
					</ul>
				</div>
	</div>
		</label>
		<!-- </kommunort -->

		<!-- kommunort -->
		<label>
			<div style="width:100%">
			<span class="wcfm_popup_label title"><strong><?php echo apply_filters( 'wcfm_taxonomy_custom_label', __( 'Produktvara', 'wc-frontend-manager' ), 'produktvara' ); ?></strong></span>

				<div class="wcfm_popup_label title" style="width: 100%;min-height: 1vh;overflow: scroll;border: 1px solid #c0c0c0;">
					<ul class="product_taxonomy_checklist">
					<?php
					$product_terms    = get_terms( 'produktvara', 'orderby=name&hide_empty=0&parent=0' );

					$WCFM->library->generateTaxonomyHTML( 'produktvara', $product_terms, $producttypes, '', true, true );
					?>
				</ul>
				</div>
	</div>
		</label>
		<!-- </produktvara -->





				<label>
		<?php
	}

/**
 * Bulk Store Edit Save
 */
function wcfmmp_bulk_store_edit_save_landell( $product, $wcfm_bulk_edit_form_data ) {
	$product_custom_taxonomies = array();

	global $WCFM, $WCFMmp, $wpdb;

	if ( isset( $wcfm_bulk_edit_form_data['product_cats'] ) && ! empty( $wcfm_bulk_edit_form_data['product_cats'] ) ) {
		$post_categories = $wcfm_bulk_edit_form_data['product_cats'];
		wp_set_post_terms( $product->get_id(), $post_categories, 'product_cat', false );
	}

	if ( isset( $wcfm_bulk_edit_form_data['product_custom_taxonomies'] ) && ! empty( $wcfm_bulk_edit_form_data['product_custom_taxonomies'] ) ) {
		$product_custom_taxonomies = $wcfm_bulk_edit_form_data['product_custom_taxonomies'];
	}


	if ( isset( $product_custom_taxonomies['kommunort'] ) && ! empty( $product_custom_taxonomies['kommunort'] ) ) {
		$post_args = $product_custom_taxonomies['kommunort'];
		wp_set_post_terms( $product->get_id(), $post_args, 'kommunort', false );
	}

	if ( isset( $product_custom_taxonomies['produktvara'] ) && ! empty( $product_custom_taxonomies['produktvara'] ) ) {
		$post_args = $product_custom_taxonomies['produktvara'];
		wp_set_post_terms( $product->get_id(), $post_args, 'produktvara', false );
	}
}
