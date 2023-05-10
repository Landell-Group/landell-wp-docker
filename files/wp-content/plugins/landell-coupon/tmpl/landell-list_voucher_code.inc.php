<?php
/*
  FileVersion: 1.0.47
*/

if (!function_exists("get_plugin_url")) {
  function get_plugin_url() {
  	return urlencode("render-loyalty-card");
  }
}

function get_list_of_voucher_codes_html($show_all, $current_user, $atts) {

  $show_all = true;
  $atts = array_change_key_case( (array) $atts, CASE_LOWER );

  $arrArguments = array(
    "text-inga-kuponger" => "Du har inga rabattkoder, fortsätt stämpla",
  );

  $totalAmountOfCodes = 0;

  $arrArguments = array_merge($arrArguments, $atts);

  $html = "<link href=\"".plugin_dir_url( __FILE__ )."../lib/boostrap/bootstrap-custom.css\" rel=\"stylesheet\" crossorigin=\"anonymous\">";
  //<style>#sidebar {float: initial!important;}</style>";

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

  while ($query->have_posts()) {
    $query->the_post();

    $user_card_meta = get_user_meta( $current_user->ID, 'coupon-'.get_the_ID(), true);
    $user_have_this_card = !(empty($user_card_meta) && $user_card_meta !== '0');

    //if (!$user_have_this_card) {
      // only show coupons saved to "my page"
    //  continue;
    //}


    $vouchers = get_vouchers($current_user, get_the_ID());
    $content = "";

    if (!empty($vouchers)) {
      $content.="<h2>Dina inlösningskoder:</h2>";
      foreach($vouchers as $voucher) {
          $content.= "<p>".$voucher."</p>";
          $totalAmountOfCodes+=1;
      }
    } else {
      continue;
    }

    $html .=
    "<div class='card mb-3 col-md-10 col-lg-8 list-voucher-codes'>
       <div class='card-wrap-outer' border=0></div>
       <div class='card-wrap card-img-top' id='image-preview' style='background-image:url(".get_the_post_thumbnail_url().")'></div>
       <div class='card-body'>
          <h5 class='card-title'>".get_the_title()."</h5>
          <div class='row'>
            {$content}
          </div>
        </div>
    </div>";
      }

      wp_reset_query();


      if($totalAmountOfCodes==0) {
        $html.="<div class='col-lg-7'>".$arrArguments["text-inga-kuponger"]."</div>";
      }

      return $html;
}
