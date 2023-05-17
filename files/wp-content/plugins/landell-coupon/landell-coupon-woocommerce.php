<?php
/*
  FileVersion: 1.0.50
*/

// register product type
function register_coupon_product_type() {
  class WC_Product_Klippkort extends WC_Product {
    public function __construct( $product ) {
      $this->product_type = 'klippkort';
      parent::__construct( $product );
    }
  }
}
add_action( 'init', 'register_coupon_product_type' );


// Add new type to product selector
function add_coupon_product_type( $types ){
    $types[ 'klippkort' ] = __( 'Klippkort');
    return $types;
}
add_filter( 'product_type_selector', 'add_coupon_product_type' );


/**
 * Show pricing fields for klippkort product.
 */
function klippkort_custom_js() {

	if ( 'product' != get_post_type() ) :
		return;
	endif;

	?><script type='text/javascript'>
		jQuery( document ).ready( function() {
			jQuery( '.options_group.pricing' ).addClass( 'show_if_klippkort' ).show();
      jQuery('.toggle-qr').on( 'click', function( event ) {
          jQuery('.qr-code-frame').toggleClass('hidden');
          jQuery('.qr-code-description').toggleClass('hidden');
          jQuery('.card-wrap-outer').toggleClass('hidden');
          jQuery('.card-wrap').toggleClass('hidden');
      });
		});


	</script><?php

}
add_action( 'admin_footer', 'klippkort_custom_js' );

/**
 * Add a custom product tab.
 */
function custom_product_tabs( $tabs) {

	$tabs['klippkort'] = array(
		'label'		=> __( 'Klippkort', 'woocommerce' ),
		'target'	=> 'klippkort_options',
		'class'		=> array( 'show_if_klippkort', 'show_if_variable_klippkort'  ),
	);

	return $tabs;

}
add_filter( 'woocommerce_product_data_tabs', 'custom_product_tabs' );


/**
 * Contents of the klippkort options product tab.
 */
function klippkort_options_product_tab_content() {

	global $post;

	?><div id='klippkort_options' class='panel woocommerce_options_panel'><?php

		?><div class='options_group'><?php

      $contacts = [];
      foreach ( landell_get_b2b_users() as $user ) {
          $id = esc_html( $user->ID );
          $value = esc_html( $user->display_name ) . '[' . esc_html( $user->user_email ) . ']';
          $contacts[$id] = $value;
      }

      // Add the PIN code field to the wooCommerce card
  	  $pin_code = ( $pin_code = get_post_meta( $post->ID, 'pin_code', true ) ) ? $pin_code : '0000';
    
      woocommerce_form_field( 'pin_code', array(
          'type'        => 'number',
          'class'       => array('form-row-wide'),
          'label'       => __('PIN Kod'),
      'maxlength'		  => 5,
          'placeholder' => __('0000'),
          'required'    => false,
      ), $pin_code);
      
      woocommerce_wp_select( array(
        'id'			=> 'coupon_card_contacts',
        'label'			=> __( 'Select customer', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'description'	=> __( 'Välj en kundkontakt', 'woocommerce' ),
        'options' => $contacts
          )
      );

			woocommerce_wp_select( array(
				'id'			=> 'coupon_checkbox_squares',
				'label'			=> __( 'Number of checkboxes to render', 'woocommerce' ),
				'desc_tip'		=> 'true',
				'description'	=> __( 'Antalet rutor i detta klippkort', 'woocommerce' ),
        'options' => array(
              '11'   => __( '11', 'woocommerce' ),
    					'10'   => __( '10', 'woocommerce' ),
    					'9'   => __( '9', 'woocommerce' ),
    					'8' => __( '8', 'woocommerce' ),
              '7' => __( '7', 'woocommerce' ),
              '6' => __( '6', 'woocommerce' ),
              '5' => __( '5', 'woocommerce' ),
              '4' => __( '4', 'woocommerce' ),
              '3' => __( '3', 'woocommerce' ),
              '2' => __( '2', 'woocommerce' ),
              '1' => __( '1', 'woocommerce' )
    					)
    				)
			);

      woocommerce_wp_select( array(
        'id'			=> 'coupon_checkbox_delay',
        'label'			=> __( 'Time to wait before the code will work again', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'description'	=> __( 'Tid innan URL/QR kod går att använda/scanna igen', 'woocommerce' ),
        'options' => array(
                  '1440'   => __( '1 per 24/h', 'woocommerce' ),
                  '60' => __( '1 per timma', 'woocommerce' ),
                  '30' => __( '1 gång per 30m', 'woocommerce' ),
                  '15' => __( '1 gång per 15m', 'woocommerce' ),
                  '10' => __( '1 gång per 10m', 'woocommerce' ),
                  '5' => __( '1 gång per 5m', 'woocommerce' ),
                  '0' => __( 'ingen väntetid', 'woocommerce' ),
                  'dynamic' => __( 'dynamisk (används ej)', 'woocommerce' ),
                  )
                )
      );

      woocommerce_wp_select( array(
        'id'			=> 'coupon_reset_delay',
        'label'			=> __( 'Time to wait before the card will work again', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'description'	=> __( 'Tid innan kortet går att scanna igen (efter att det blivit inlöst)', 'woocommerce' ),
        'options' => array(
                  '1440'   => __( '1 per dag (24/h)', 'woocommerce' ),
                  '43200' => __( '1 per månad (30 dagar)', 'woocommerce' ),
                  '259200' => __( '1 gång per 6månader', 'woocommerce' ),
                  '525600' => __( '1 gång per år', 'woocommerce' ),
                  '0' => __( 'ingen väntetid', 'woocommerce' )
                  )
                )
      );

      woocommerce_wp_text_input( array(
        'id'			=> 'coupon_bookmark_url',
        'label'			=> __( 'On save go to this url', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'placeholder'		=> "/mina-klippkort/",
        'description'	=> __( 'Vilken sida man ska hamna på när man klickat på spara', 'woocommerce' ),
        'type' 			=> 'text',
      ) );

      woocommerce_wp_text_input( array(
        'id'			=> 'coupon_unbookmark_url',
        'label'			=> __( 'On remove go to this url', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'placeholder'		=> "/mina-klippkort/",
        'description'	=> __( 'Vilken sida man ska hamna på när man klickat på ta bort', 'woocommerce' ),
        'type' 			=> 'text',
      ) );

      woocommerce_wp_text_input( array(
        'id'			=> 'coupon_marker_url',
        'label'			=> __( 'Marker filler URL', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'placeholder'		=> plugin_dir_url( __FILE__ )."images/sample-filler1.png",
        'description'	=> __( 'URL till ifyllning ex. kryss eller liknande', 'woocommerce' ),
        'type' 			=> 'text',
      ) );

      woocommerce_wp_text_input( array(
        'id'			=> 'coupon_trophy_image',
        'label'			=> __( 'Image displayed together with the coupon', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'placeholder'		=> "https://img.icons8.com/bubbles/200/000000/trophy.png",
        'description'	=> __( 'Bild som ersätter "pokalen" som visas när kupongen nått maxantal', 'woocommerce' ),
        'type' 			=> 'text',
      ) );

      $coupon_codes_available_value = "";
      $coupon_codes_available = get_post_meta($post->ID, "coupon_codes_available", true);
      if(!empty($coupon_codes_available)) {
        $coupon_codes_available_value = implode(PHP_EOL, $coupon_codes_available);
      }

      woocommerce_wp_textarea_input( array(
        'id'			=> 'coupon_codes_available',
        'label'			=> __( 'Coupon codes available', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'style' => "width: 625px;
            text-align: center;
            background-color: cornsilk;
            font-family: monospace;
            height: 210px;",
        'value' => $coupon_codes_available_value,
        'placeholder'		=> "
        perh-52bfk-v4he-5e7b-gdp966n
        y7me-w6rxk-34vs-cdt6-hhx3oe5
        nfos-e2xuw-dpys-iibd-imo6hwr
        mukc-mv9ev-9p9o-g2kv-c8tyfhv
        58vn-inpwf-kuqy-3ktm-fxphgsx
        fgk2-s8og8-o4ty-8u2e-fzar69d
        f3nk-7zs3i-ufwj-c5rn-py66xco
        uw7b-uoknt-vq5y-x2bt-rm3ey6h
        n2rf-9yngd-ayry-d9ba-f87irkv
        jid2-2xqh2-u2xu-qsa7-q3799qa",
        'description'	=> __( 'Det är en av dessa kuponger som visas när kunden nått maxantal, sen är den förbrukad', 'woocommerce' )
      ) );

/*
      woocommerce_wp_text_input( array(
        'id'			=> 'coupon_marker_color',
        'label'			=> __( 'Marker color', 'woocommerce' ),
        'desc_tip'		=> 'true',
        'placeholder'		=> 'orange',
        'description'	=> __( 'What color should the marker have?', 'woocommerce' ),
        'type' 			=> 'text',
      ) );
*/

    $qr_code_url = get_qr_code(get_the_ID());
		?>
    <?php if(!empty($post) && !empty($post->ID)) : ?>
      <p class="preview"><a href="<?php echo get_plugin_url(); ?>?i=<?php echo $post->ID; ?>" target="_blank">Preview</a></p>
    <?php endif; ?>
    <?php if(!empty($post) && !empty($post->ID)) : ?>
      <p class="toggle-qr"><a href="#qr-code-frame">QR</a></p>

      <?php echo "<div class='hidden qr-code-frame' id='qr-code-".get_the_ID()."'>".render_qr_code(get_the_ID())."</div>"; ?>
      <?php echo "<div class='hidden qr-code-description'>".get_qr_code(get_the_ID())."</div>"; ?>


    <?php endif; ?>
  </div>


	</div><?php


}
add_action( 'woocommerce_product_data_panels', 'klippkort_options_product_tab_content' );


/**
 * Save the custom fields.
 */
function save_klippkort_option_field( $post_id ) {


  if ( isset( $_POST['coupon_card_contacts'] ) ) :
    update_post_meta( $post_id, 'coupon_card_contacts', sanitize_text_field( $_POST['coupon_card_contacts'] ) );
  endif;

  if ( isset( $_POST['coupon_codes_available'] ) ) :
    update_post_meta( $post_id, 'coupon_codes_available', explode(PHP_EOL, sanitize_textarea_field($_POST['coupon_codes_available']) ) );
  endif;

	if ( isset( $_POST['coupon_checkbox_squares'] ) ) :
		update_post_meta( $post_id, 'coupon_checkbox_squares', sanitize_text_field( $_POST['coupon_checkbox_squares'] ) );
	endif;

  if ( isset( $_POST['coupon_checkbox_delay'] ) ) :
    update_post_meta( $post_id, 'coupon_checkbox_delay', sanitize_text_field( $_POST['coupon_checkbox_delay'] ) );
  endif;

  if ( isset( $_POST['coupon_reset_delay'] ) ) :
    update_post_meta( $post_id, 'coupon_reset_delay', sanitize_text_field( $_POST['coupon_reset_delay'] ) );
  endif;

  if ( isset( $_POST['coupon_trophy_image'] ) ) :
    update_post_meta( $post_id, 'coupon_trophy_image', sanitize_text_field( $_POST['coupon_trophy_image'] ) );
  endif;

  if ( isset( $_POST['coupon_marker_url'] ) ) :
		update_post_meta( $post_id, 'coupon_marker_url', sanitize_text_field( $_POST['coupon_marker_url'] ) );
	endif;

  if ( isset( $_POST['coupon_bookmark_url'] ) ) :
    update_post_meta( $post_id, 'coupon_bookmark_url', sanitize_text_field( $_POST['coupon_bookmark_url'] ) );
  endif;

  if ( isset( $_POST['coupon_unbookmark_url'] ) ) :
    update_post_meta( $post_id, 'coupon_unbookmark_url', sanitize_text_field( $_POST['coupon_unbookmark_url'] ) );
  endif;



  if ( isset( $_POST['coupon_marker_color'] ) ) :
		update_post_meta( $post_id, 'coupon_marker_color', sanitize_text_field( $_POST['coupon_marker_color'] ) );
	endif;


}
add_action( 'woocommerce_process_product_meta_klippkort', 'save_klippkort_option_field'  );
add_action( 'woocommerce_process_product_meta_variable_klippkort', 'save_klippkort_option_field'  );
