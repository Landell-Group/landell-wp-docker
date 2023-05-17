<?php
/*
  FileVersion: 1.0.50
*/
function get_voucher_html($card_template_id, $current_user, $distinct_session_key, $atts) {

    if (in_array(strval($card_template_id), ['-1',null, ''])) {
      wp_safe_redirect( get_mypage_url() );
    }

    $arrArguments = array(
      "text-inte-inloggad" => "Du måste logga in för att kunna spara kortet",
      "text-maste-vanta" => "Du kan inte använda kortet än",
      "text-saknar-access" => "Du saknar rätt medlemskap för att kunna använda den här produkten",
      "button-spara" => "Spara till min sida"
    );

    $atts = array_change_key_case( (array) $atts, CASE_LOWER );
    $arrArguments = array_merge($arrArguments, $atts);


    $user_card_meta = get_user_meta( $current_user->ID, 'coupon-'.$card_template_id, true);
    $user_have_this_card = !(empty($user_card_meta) && $user_card_meta !== '0');

    $user_can_use_this_card = true;
    $user_can_access_this_card = true;
    if(function_exists("wc_memberships_user_can")) {
      if(wc_memberships_is_product_viewing_restricted($card_template_id)) {
        $user_can_access_this_card  = wc_memberships_user_can( $current_user->ID, "view", array( 'post' => $card_template_id ));
        $user_can_use_this_card = wc_memberships_user_can( $current_user->ID, "purchase", array( 'post' => $card_template_id ));
      }
    }

    /* text on content, number of fillers, placement for filler etc. */
    $card_template_layout = 2;

    $path_to_file = get_loyalty_card_url($card_template_id);
    $path_to_filler = get_filler_url($card_template_id);

    $content = "<link href=\"".plugin_dir_url( __FILE__ )."../lib/boostrap/bootstrap-custom.css\" rel=\"stylesheet\" crossorigin=\"anonymous\">";

  	$content = "
  	<style type=\"text/css\">

  .card-wrap-outer {
      position: relative;

      margin-bottom: 0;
      margin-top: -70px !important;
  }

  .card-wrap {
      width: 660px;
      height: 350px;
      background-size: contain;
      background-image: url({$path_to_file});
  	background-repeat: no-repeat;
  	margin:0;
  	max-height: 80vh;
  }

  .card-filler {
      background-image: url({$path_to_filler});
      position: absolute;
      background-size: cover;
  }

  .entry-header, .site-footer, .widget-area, #wpadminbar, #masthead, .edit-link {
      display: none !important;
  }

	</style>
	";

  $card_total_checkbox = get_number_of_checkboxes($card_template_id);
	if ($distinct_session_key && strval($distinct_session_key) !== '-1') {
    if (!is_locked(wp_get_current_user(), $card_template_id) && $user_can_access_this_card) {
      inc_coupon_count($card_template_id, wp_get_current_user(), get_number_of_checkboxes($card_template_id));
    }
	}

  $checkmarks = get_coupon_count($card_template_id, wp_get_current_user());

  if (is_numeric($checkmarks)) {
      $checkmarks = intval($checkmarks);
  } else {
      $checkmarks = 0;
  }

	$customer_name = $current_user->user_nicename;
	$customer_email = reset(explode("@", $current_user->user_email));
	if (strlen($customer_name) > strlen($customer_email)) {
		$customer_name = ucwords($customer_email);
    }

	$client_name = get_the_title($card_template_id);
	if (!$client_name){
		$client_name = 'sverigeshopping.se';
	}

	$content .= "<div class=\"floating_welcome_message\"><h2>Hej, {$customer_name}</h2> <a href=/wp-login.php?action=logout&redirect_to=".get_login_url()."><small>Logga ut</small></a>";
	$content .= "</div>";

  if (!$user_can_access_this_card) {
    $content .= "<div class=\"card_text\" style=\"color:#cbb9a8;padding-left:10vw;font-weight:bold;\"><small>".$arrArguments["text-saknar-access"]."</small></div>";
    $checkmarks = 0;
  } else if (is_locked($current_user, $card_template_id)) {
    $content .= "<div class=\"card_text\" style=\"color:#cbb9a8;padding-left:10vw;font-weight:bold;\"><small>".$arrArguments["text-maste-vanta"]."</small></div>";
    $checkmarks = 0;
  } else {
    $content .= "<div class=\"card_text\"><small>Du har <b>{$checkmarks}/{$card_total_checkbox}</b> Stämplar hos {$client_name}</small></div>";
  }

  $content .= "
      <div class=\"card-wrap-outer\" border=0>";

  for($i=1; $i<=$checkmarks; $i++) {
      $content .= "<div class=\"card-filler card-filler-{$i}\">&nbsp;</div>";
  }

  $content .= "</div>";

	$marker_color = get_marker_color($card_template_id);
	$trophy_image = get_trophy_image_url($card_template_id);

  $url_to_product = get_permalink( $card_template_id );

  $content .= "
      <a href=\"{$url_to_product}\"><div class=\"card-wrap\" style=\"background-image: url({$path_to_file});\"></div></a>";


//if ($checkmarks!=get_number_of_checkboxes($card_template_id)) {
		$vouchers = get_vouchers($current_user, $card_template_id);
		if (!empty($vouchers)) {
			$content.="<h2>Dina kuponger:</h3>";
			foreach($vouchers as $voucher) {
					$content.= "<p>".$voucher."</p>";
			}
		}
//}

//die("{$checkmarks} vs ".get_number_of_checkboxes($card_template_id));

  if ($checkmarks==get_number_of_checkboxes($card_template_id)) {

      	$code = null;
      	if($distinct_session_key) {

          $code = get_voucher_code($current_user, $card_template_id); //"gxHeQ-wBzmn-9Ue4T-ttqDb-N00sw";
      		if (!empty($code)) {
      			save_voucher($code, $current_user, $card_template_id);
      		}
      	} else {
          //die("not distinct: {$distinct_session_key}");
        }

    		if (empty($code)) {
    			$code = reset(array_reverse($vouchers));
    		}

      	$content .= get_voucher_design($code, $trophy_image);

      	$content .= "<style type=text/css>'
        @import url(http://fonts.googleapis.com/css?family=Calibri:400,300,700);

         body {
             background-color: #D32F2F;
             font-family: 'Calibri', sans-serif !important
         }

         .mt-100 {
             margin-top: 100px
         }

         .container {
             margin-top: 200px
         }

         .card {
             position: relative;
             display: flex;
             width: 450px;
             flex-direction: column;
             min-width: 0;
             word-wrap: break-word;
             background-color: #fff;
             background-clip: border-box;
             border: 1px solid #d2d2dc;
             border-radius: 4px;
             -webkit-box-shadow: 0px 0px 5px 0px rgb(249, 249, 250);
             -moz-box-shadow: 0px 0px 5px 0px rgba(212, 182, 212, 1);
             box-shadow: 0px 0px 5px 0px rgb(161, 163, 164)
         }

         .card .card-body {
             padding: 1rem 1rem
         }

         .card-body {
             flex: 1 1 auto;
             padding: 1.25rem
         }

         p {
             font-size: 14px
         }

         h4 {
             margin-top: 18px
         }

         .cross {
             padding: 10px;
             /*color: #d6312d;*/
        		 color: {$marker_color};
             cursor: pointer
         }

         .continue:focus {
             outline: none
         }

         .continue {
             border-radius: 5px;
             text-transform: capitalize;
             font-size: 16px;
             padding: 18px 19px;
             cursor: pointer;
             color: #cbb9a8;
             background-color: #a45861;
         }

         .continue:hover {
             background-color: #D32F2F !important
         }
        </style>";

      }

      if(!is_user_logged_in()) {
        $content.="<hr><div class='col-lg-7'>
            <div class='text-center col-lg-7 small text-danger'><a href='".get_login_url(true)."?redirect_to=".urlencode($list_page_url)."'>".$arrArguments["text-inte-inloggad"]."</a></div>
        </div><br/>";
      } else if (!$user_can_access_this_card) {
        $content.="<hr><div class='col-lg-7'>
          <div class='text-center col-lg-7 small text-danger'><a href='".get_login_url(true)."?redirect_to=".urlencode($list_page_url)."'>".$arrArguments["text-saknar-access"]."</a></div>
        </div><br/>";
      } else if (!$user_have_this_card && strval($distinct_session_key) !== '-1'){
        $content.="<hr><div class='col-lg-7'>
            <button type='button' id='save_".$card_template_id."' class='btn btn-primary' data-toggle='button' aria-pressed='false' autocomplete='off'>
              <a class='text-white' href='".$list_page_url."?save_id=".$card_template_id."'>".$arrArguments["button-spara"]."</a>
            </button>
        </div><br/>";
      } else {
        // TODO: we should not end up here
      }

    /*if (strval($card_template_id)==strval(1257)) {
      print_r($content);
      die();
    }*/

      return $content;
}
?>
