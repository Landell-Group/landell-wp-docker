<?php
/*
  FileVersion: 1.0.50
*/

if (!function_exists("get_plugin_url")) {
  function get_plugin_url() {
  	return urlencode("render-loyalty-card");
  }
}

if (!function_exists("get_user_profile_url")) {
  function get_user_profile_url() {
  	return "/wp-admin/user-edit.php";
  }
}

function get_list_of_vouchers_html($show_all, $current_user, $atts) {

  $atts = array_change_key_case( (array) $atts, CASE_LOWER );

  $arrArguments = array(
    "text-inte-inloggad" => "Du måste logga in för att kunna spara kortet",
    "text-redan-sparat" => "Finns sparat på min sida",
    "button-spara" => "Spara till min sida",
    "button-remove" => "Ta bort från min sida"
  );

  $arrArguments = array_merge($arrArguments, $atts);

/*  <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css\" rel=\"stylesheet\" integrity=\"sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x\" crossorigin=\"anonymous\">*/

  $html = "<link href=\"".plugin_dir_url( __FILE__ )."../lib/boostrap/bootstrap-custom.css\" rel=\"stylesheet\" crossorigin=\"anonymous\">";


  $html .= "
  <div class=\"lg-container\">
    <script type=\"text/javascript\">
    jQuery(function($){

        $('.toggle-qr').on( 'click', function( event ) {
            $(this).parent().parent().parent().parent().children('.qr-code-frame').toggleClass('hidden');
            $(this).parent().parent().parent().parent().children('.qr-code-description').toggleClass('hidden');
            $(this).parent().parent().parent().parent().children('.card-wrap-outer').toggleClass('hidden');
            $(this).parent().parent().parent().parent().children('.card-wrap').toggleClass('hidden');
        });

        $('.toggle-members').on( 'click', function( event ) {
            $(this).parent().children('.card-members').toggleClass('hidden');
        });

    });
    </script>

  <div class=\"row\">
  ";

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

    $card_template_id = get_the_ID();
    $user_card_meta = get_user_meta( $current_user->ID, 'coupon-'.$card_template_id, true);
    $user_have_this_card = !(empty($user_card_meta) && $user_card_meta !== '0');
    $url_to_product = get_permalink( $card_template_id );

    $user_is_masqueraded = (wp_get_current_user()->ID !== $current_user->ID); /* true if logged in user is not card owner */

    if (!$show_all && !$user_have_this_card) {
      // only show coupons saved to "my page"
      continue;
    }

    $user_can_access_this_card = true;
    if(function_exists("wc_memberships_user_can")) {
      if(wc_memberships_is_product_viewing_restricted($card_template_id)) {
        $user_can_access_this_card  = wc_memberships_user_can( $current_user->ID, "view", array( 'post' => $card_template_id ));
      }
    }

    if (!$user_can_access_this_card) {
      continue;
    }

    // days since published
    $now = time(); // or your date as well
    $your_date = strtotime(get_the_date( 'Y-m-d' ));
    $datediff = $now - $your_date;
    $days_since_published = round($datediff / (60 * 60 * 24));

    // stamps
    $checked_boxes = get_coupon_count(get_the_ID(), $current_user);
    $checkboxes    = get_number_of_checkboxes(get_the_ID());

    if (empty($checked_boxes)) {
      $checked_boxes = 0;
    }

    try {
      $coupons_used = count(get_post_meta(get_the_ID(), 'coupon_codes_consumed', true));
    } catch(TypeError $e)  {
      $coupons_used =  'Caught TypeError: ' . $e->getMessage();
    } catch (Exception $e) {
      $coupons_used =  'Caught Exception: ' . $e->getMessage();
    }

    try {
      $coupons_available = count(get_post_meta(get_the_ID(), 'coupon_codes_available', true));
    } catch(TypeError $e)  {
      $coupons_available =  'Caught exception: ' . $e->getMessage();
    } catch (Exception $e) {
      $coupons_available =  'Caught exception: ' . $e->getMessage();
    }

    

    $html .= "
      <div class='card mb-3 col-lg-8'  style='border: 1px solid #d2d2dc;'>

         <div class='card-wrap-outer' border=0></div>
         <a href='{$url_to_product}'><div class='card-wrap card-img-top card-wrap-list-coupons' id='image-preview' style='background-image:url(".get_the_post_thumbnail_url().");'></div></a>

         <div class='hidden qr-code-frame' id='qr-code-".get_the_ID()."'>".render_qr_code(get_the_ID())."</div>
         <div class='hidden qr-code-description'>".get_qr_code(get_the_ID())."</div>
        <div class='card-body'>
          <h5 class='card-title'>".get_the_title()."</h5>
          <div class='row'>
          <div class='col-lg-5'>
            <p class='card-text'><small class='text-muted' style='font-weight:bold;'>Stämplar: ".$checked_boxes." av ".$checkboxes."</small></p>
              <p class='card-text hidden'>".$coupons_used." av ".$coupons_available." kuponger använda.</p>
              <p class='card-text hidden'><small class='text-muted'>Skapad för ".$days_since_published." dagar sedan</small></p>
          </div>";

          //".reset(get_post_meta(get_the_ID(), 'coupon_card_contacts', true))."

          // user have this card
          if($user_have_this_card) {
            if(!$user_is_masqueraded) {
              $html.="<div class='col-lg-7'>
                  <button type='button' id='del_".get_the_ID()."' class='btn btn-danger' data-toggle='button' aria-pressed='false' autocomplete='off'>
                    <a class='text-white' href='".get_plugin_url()."?del_id=".get_the_ID()."'>".$arrArguments["button-remove"]."</a>
                  </button>
              </div>";
            }


            // user is manager
            if (is_manager()) {

              $html.="<div class='col-lg-12' style='margin-top:20px;'>";

                  $html.="<small>Nedan syns endast för administratörer och återförsäljare</small><hr>
                  <button type='button' id='".get_the_ID()."' class='btn btn-primary toggle-qr' data-toggle='button' aria-pressed='false' autocomplete='off'>
                  Visa/Dölj QR kod
                  </button>

                  <button type='button' class='btn btn-secondary text-white' data-toggle='button' aria-pressed='false' autocomplete='off'>
                      <a class='text-white' href='".get_plugin_url()."?i=".get_the_ID()."'>Besök som kund</a>
                  </button>";


              if($user_have_this_card) {
                $html .="<button type='button' id='del_".get_the_ID()."' class='btn btn-danger' data-toggle='button' aria-pressed='false' autocomplete='off'>
                  <a class='text-white' href='".get_plugin_url()."?del_id=".get_the_ID()."'>Ta bort från konto</a>
                </button>";
              }

              $html .="<hr><button type='button' id='del_".get_the_ID()."' class='btn btn-secondary text-white' data-toggle='button' aria-pressed='false' autocomplete='off'>
                <a class='text-white' href='".get_user_profile_url()."?card_id=".get_the_ID()."&do=inc&user_id={$current_user->ID}'>+1 Stämpel</a>
              </button>";

              $html .="<button type='button' id='del_".get_the_ID()."' class='btn btn-secondary text-white' data-toggle='button' aria-pressed='false' autocomplete='off'>
                <a class='text-white' href='".get_user_profile_url()."?card_id=".get_the_ID()."&do=dec&user_id={$current_user->ID}'>-1 Stämpel</a>
              </button>";

              $html.="</div>";
            } else {

            }
          } else if (is_user_logged_in()){
            $html.="<div class='col-lg-7'>
                <button type='button' id='save_".get_the_ID()."' class='btn btn-primary' data-toggle='button' aria-pressed='false' autocomplete='off'>
                  <a class='text-white' href='".get_plugin_url()."?save_id=".get_the_ID()."'>".$arrArguments["button-spara"]."</a>
                </button>
            </div>";
          } else {
            $html.="<div class='col-lg-7'>
                <div class='text-center col-lg-7 small text-danger'><a href='".get_login_url(true)."?redirect_to=".urlencode($list_page_url)."'>Du måste logga in för att kunna spara kortet</a></div>
            </div>";
          }
          $html .= "
          </div>
      </div>
      </div>
      ";
      }

      wp_reset_query();

      $html .= "</div></div>";

      return $html;
}
