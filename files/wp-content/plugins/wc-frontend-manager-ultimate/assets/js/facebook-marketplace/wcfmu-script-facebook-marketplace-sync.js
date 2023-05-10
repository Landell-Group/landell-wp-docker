/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

jQuery( document ).ready(
	function ( $ ) {
		/**
		 * Gets any new excluded categories being added.
		 *
		 * @return {string[]}
		 */
		function getExcludedCategoriesAdded()
		{
			  const newCategoryIDs = $( '#wc_facebook_excluded_product_category_ids' ).val();
			  let oldCategoryIDs   = [];

			if (window.facebook_marketplace_sync_options && window.facebook_marketplace_sync_options.excluded_category_ids) {
				oldCategoryIDs = window.facebook_marketplace_sync_options.excluded_category_ids;
			}

			// return IDs that are in the new value that were not in the saved value
			return $( newCategoryIDs ).not( oldCategoryIDs ).get();
		}

		/**
		 * Gets any new excluded tags being added.
		 *
		 * @return {string[]}
		 */
		function getExcludedTagsAdded()
		{
			const newTagIDs = $( '#wc_facebook_excluded_product_tag_ids' ).val();
			let oldTagIDs   = [];

			if (window.facebook_marketplace_sync_options && window.facebook_marketplace_sync_options.excluded_tag_ids) {
				oldTagIDs = window.facebook_marketplace_sync_options.excluded_tag_ids;
			}

			// return IDs that are in the new value that were not in the saved value
			return $( newTagIDs ).not( oldTagIDs ).get();
		}

		/**
		 * Toggles availability of input in setting groups.
		 *
		 * @param {boolean} enable whether fields in this group should be enabled or not
		 */
		function toggleSettingOptions( enable )
		{
			$( '.product-sync-field' ).each(
				function () {
					let $element = $( this );

					if ($( this ).hasClass( 'wcfm-select2' )) {
						  $element = $( this ).next( 'span.select2-container' );
					}

					if (enable) {
						$element.css( 'pointer-events', 'all' ).css( 'opacity', '1.0' );
					} else {
						$element.css( 'pointer-events', 'none' ).css( 'opacity', '0.4' );
					}
				}
			);
		}

		// $( '.woocommerce-help-tip' ).tipTip( {
		// 'attribute': 'data-tip',
		// 'fadeIn': 50,
		// 'fadeOut': 50,
		// 'delay': 200
		// } );
		if ($( 'form.wc-facebook-settings' ).hasClass( 'disconnected' )) {
			   toggleSettingOptions( false );
		}

		// toggle availability of options withing field groups
		$( 'input#wc_facebook_enable_product_sync' ).on(
			'change',
			function ( e ) {
				if ($( 'form.wc-facebook-settings' ).hasClass( 'disconnected' )) {
					$( this ).css( 'pointer-events', 'none' ).css( 'opacity', '0.4' );
					return;
				}

				toggleSettingOptions( $( this ).is( ':checked' ) );
			}
		).trigger( 'change' );

		let submitSettingsSave = false;

		$( 'input[name="save_product_sync_settings"]' ).on(
			'click',
			function ( e ) {
				if ( ! submitSettingsSave) {
					  e.preventDefault();
				} else {
					 return true;
				}

				const $submitButton = $( this ),
				categoriesAdded     = getExcludedCategoriesAdded(),
				tagsAdded           = getExcludedTagsAdded();

				if (categoriesAdded.length > 0 || tagsAdded.length > 0) {
					$.post(
						facebook_marketplace_sync_options.ajax_url,
						{
							action: 'facebook_for_woocommerce_set_excluded_terms_prompt',
							security: facebook_marketplace_sync_options.set_excluded_terms_prompt_nonce,
							categories: categoriesAdded,
							tags: tagsAdded,
							wcfm_ajax_nonce: wcfm_params.wcfm_ajax_nonce
						},
						function ( response ) {
							if (response && ! response.success) {
								// close existing modals
								$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

								// open new modal, populate template with AJAX response data
								new $.WCBackboneModal.View(
									{
										target: 'facebook-for-woocommerce-modal',
										string: response.data
									}
								);

								// exclude products: submit form as normal
								$( '.facebook-for-woocommerce-confirm-settings-change' ).on(
									'click',
									function () {
										blockModal();

										submitSettingsSave = true;
										$submitButton.trigger( 'click' );
									}
								);
							} else {
								// no modal displayed: submit form as normal
								submitSettingsSave = true;
								$submitButton.trigger( 'click' );
							}//end if
						}
					);
				} else {
					// no terms added: submit form as normal
					submitSettingsSave = true;
					$submitButton.trigger( 'click' );
				}//end if
			}
		);

		// mark as in-progress if syncing when the page is loaded
		if (facebook_marketplace_sync_options.sync_in_progress) {
			syncInProgress();
		}

		// handle the sync button click
		$( '#wcfm_facebook_marketplace_product_sync' ).click(
			function ( event ) {
				event.preventDefault();

				if (confirm( facebook_marketplace_sync_options.i18n.confirm_sync )) {
					  setProductSyncStatus();

					  let startTime = Date.now();

					$.post(
						facebook_marketplace_sync_options.ajax_url,
						{
							action: 'wcfm_ajax_controller',
							controller: 'wcfm-facebook-marketplace-sync-products',
							nonce: facebook_marketplace_sync_options.sync_products_nonce,
							wcfm_ajax_nonce: wcfm_params.wcfm_ajax_nonce
						  },
						function ( response ) {
							console.log( response );

							if ( ! response.success) {
								  let error = facebook_marketplace_sync_options.i18n.general_error;

								if (response.data && response.data.length > 0) {
									error = response.data;
								}

								clearSyncInProgress( error );
							} else {
								// get the current sync status after a successful response but make sure to wait at least 10 seconds since the button was pressed
								setTimeout( getSyncStatus, Math.max( 0, (10000 - ( Date.now() - startTime )) ) );
							}
						}
					).fail(
						function () {
							clearSyncInProgress( facebook_marketplace_sync_options.i18n.general_error );
						}
					);
				}//end if
			}
		);

		/**
		 * Sets the UI as sync in progress and starts an interval to check the background sync status.
		 *
		 * @since 2.0.0
		 *
		 * @param count number of items remaining
		 */
		function syncInProgress( count = null )
		{
			setProductSyncStatus( count );

			if ( ! window.syncStatusInterval) {
				window.syncStatusInterval = setInterval( getSyncStatus, 10000 );
			}
		}

		/**
		 * Sets the UI as sync in progress.
		 *
		 * @since 2.0.0
		 *
		 * @param count number of items remaining
		 */
		function setProductSyncStatus( count = null )
		{
			  toggleSettingOptions( false );

			  let message = facebook_marketplace_sync_options.i18n.sync_in_progress;

			if (count) {
				if (count > 1) {
					  message = message + facebook_marketplace_sync_options.i18n.sync_remaining_items_plural;
				} else {
					message = message + facebook_marketplace_sync_options.i18n.sync_remaining_items_singular
				}

				message = message.replace( '{count}', count );
			}

			// set products sync status
			$( '#sync_progress' ).show().html( message ).css( 'color', '#3e9304' );

			facebook_marketplace_sync_options.sync_in_progress = true;
		}

		/**
		 * Clears any UI for sync in progress.
		 *
		 * @since 2.0.0
		 *
		 * @param error message to display
		 */
		function clearSyncInProgress( error = '' )
		{
			facebook_marketplace_sync_options.sync_in_progress = false;

			clearInterval( window.syncStatusInterval );

			window.syncStatusInterval = null;

			toggleSettingOptions( true );

			if (error) {
				$( '#sync_progress' ).show().html( error ).css( 'color', '#DC3232' );
			} else {
				$( '#sync_progress' ).hide();
			}
		}

		/**
		 * Gets the current sync status.
		 *
		 * @since 2.0.0
		 */
		function getSyncStatus()
		{
			if ( ! facebook_marketplace_sync_options.sync_in_progress) {
				return;
			}

			$.post(
				facebook_marketplace_sync_options.ajax_url,
				{
					action: 'wcfm_ajax_controller',
					controller: 'wcfm-facebook-marketplace-get-sync-status',
					nonce: facebook_marketplace_sync_options.sync_status_nonce,
					wcfm_ajax_nonce: wcfm_params.wcfm_ajax_nonce
				},
				function ( response ) {
					console.log( response );

					if (response.success) {
						  // the returned data represents the number of products remaining
						if (response.data > 0) {
							syncInProgress( response.data );
						} else {
							clearSyncInProgress();
						}
					}
				}
			);
		}
	}
);
