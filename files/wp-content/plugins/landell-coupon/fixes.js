jQuery(function($){
	$( document ).ready(function() {
		$(window).on('load', function() {
		 if(jQuery('li.woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--wcfm-store-manager a').length > 0) {
			 jQuery('li.woocommerce-MyAccount-navigation-link.woocommerce-MyAccount-navigation-link--wcfm-store-manager a').text("Butikspanel")
		 }

		 if(jQuery('div.wcfm_menu_wcfm-ledger a span').length > 0) {
			 jQuery('div.wcfm_menu_wcfm-ledger a span.text').text("Huvudbok")
		 }
		});
	});
});
