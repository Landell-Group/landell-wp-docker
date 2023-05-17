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


  if (!empty($_GET['pin_code']) && !empty($_GET['voucher_id']) && !empty($_GET['voucher_code'])) {
    redeem_voucher($current_user, $_GET['voucher_id'], $_GET['voucher_code'], $_GET['pin_code']);
  }

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

    $redeemed = get_redeemed_vouchers($current_user, get_the_ID());


    $vouchers = get_vouchers($current_user, get_the_ID());
    $content = "";

    if (!empty($vouchers)) {
      $content.="<h2>Dina inlösningskoder:</h2>";
      foreach($vouchers as $key => $voucher) {
          $content.= "<p class=\"\" id=\"view-row-{$key}\">".$voucher." <button style='margin-left:25%;width:200px;' onClick=\"document.getElementById('view-row-{$key}').className = 'hidden';document.getElementById('edit-row-{$key}').className = '';\">Lös in</button></p>"
                  .  "<p class=\"hidden\" id=\"edit-row-{$key}\"><input class=\"pin_code\" id=\"pin_code-{$key}\" style=\"max-width: 127px;text-align: center;margin-left: 2vw;font-size: xx-large;color: #a45861;\" placeholder=\"skriv kod\"/> <button onClick=\"window.location='{$get_my_coupon_codes_url}?voucher_id=".get_the_ID()."&voucher_code={$voucher}&pin_code='+document.getElementById('pin_code-{$key}').value;\" style=\"margin-left:25%;width:200px;background-color:#a45861;\">Spara & Förbruka</button></p>";
          $totalAmountOfCodes+=1;
      }
    } 
    if (!empty($redeemed)) {
      $content.="<h2>Förbrukade inlösningskoder:</h2>";
      foreach($redeemed as $key => $voucher) {
          $content.= "<p style=\"text-decoration-line: line-through;\">{$voucher}</p>";
      }
    } 
    
    if (empty($vouchers) && empty($redeemed)) {
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

      $html = str_ireplace("http://localhost:8080/wp-content/uploads", "https://sverigeshopping.se/wp-content/uploads", $html);

      return $html;
}
