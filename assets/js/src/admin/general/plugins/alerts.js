/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
( function ( $ ) {
	'use strict';

	var $alerts = $( '.pum-alerts' ),
		$noticeCounts = $( '.pum-alert-count' ),
		count = parseInt( $noticeCounts.eq( 0 ).text() );

	function dismissAlert( $alert, alertAction ) {
		var dismissible = $alert.data( 'dismissible' ),
			expires;

		if ( typeof $alert.data( 'expires' ) !== 'undefined' ) {
			expires = $alert.data( 'expires' );
		} else if (
			dismissible === '1' ||
			dismissible === 1 ||
			dismissible === true
		) {
			expires = null;
		} else {
			expires = dismissible + ' days';
		}

		$.ajax( {
			method: 'POST',
			dataType: 'json',
			url: window.ajaxurl,
			data: {
				action: 'pum_alerts_action',
				nonce: window.pum_alerts_nonce,
				code: $alert.data( 'code' ),
				expires: expires,
				pum_dismiss_alert: alertAction,
			},
		} );
	}

	function trackReviewRequest( reason ) {
		if ( typeof window.pum_review_api_url !== 'undefined' ) {
			$.ajax( {
				method: 'POST',
				dataType: 'json',
				url: window.pum_review_api_url,
				data: {
					trigger_group: window.pum_review_trigger.group,
					trigger_code: window.pum_review_trigger.code,
					reason: reason,
					product: window.pum_review_context
						? window.pum_review_context.product
						: 'core',
					attempt: window.pum_review_context
						? window.pum_review_context.attempt
						: 0,
					uuid: window.pum_review_uuid || null,
				},
			} );
		}
	}

	function recordReviewRequest( reason ) {
		return $.ajax( {
			method: 'POST',
			dataType: 'json',
			url: window.pum_review_ajax_url || window.ajaxurl,
			data: {
				action: 'pum_review_action',
				nonce: window.pum_review_nonce,
				group: window.pum_review_trigger.group,
				code: window.pum_review_trigger.code,
				pri: window.pum_review_trigger.pri,
				reason: reason,
			},
		} );
	}

	function dismissReviewRequest( reason ) {
		recordReviewRequest( reason );
		trackReviewRequest( reason );
	}

	function broadcastReviewDismissal() {
		document.dispatchEvent( new CustomEvent( 'pumReviewRequestDismissed' ) );
	}

	function checkRemoveAlerts() {
		if ( $alerts.find( '.pum-alert-holder' ).length === 0 ) {
			$alerts.slideUp( 100, function () {
				$alerts.remove();
			} );

			$( '#menu-posts-popup .wp-menu-name .update-plugins' ).fadeOut();
		}
	}

	function removeAlert( $alert ) {
		count--;

		$noticeCounts.text( count );

		$alert.fadeTo( 100, 0, function () {
			$alert.slideUp( 100, function () {
				$alert.remove();

				checkRemoveAlerts();
			} );
		} );
	}

	$( document )
		.on( 'pumDismissAlert', checkRemoveAlerts )
		.on( 'pumReviewRequestDismissed', function () {
			$( '.pum-alert-holder[data-code="review_request"]' ).each(
				function () {
					removeAlert( $( this ) );
				}
			);
		} )
		.on( 'click', '.pum-alert-holder .pum-dismiss', function ( event ) {
			var $this = $( this ),
				$alert = $this.parents( '.pum-alert-holder' ),
				reason = $this.data( 'reason' ) || 'maybe_later',
				alertAction = $( this ).data( 'action' ) || 'dismiss',
				isReviewRequest = 'review_request' === $alert.data( 'code' ),
				href = $this.attr( 'href' ) || '',
				isExternalLink = /^https?:\/\//i.test( href );

			if ( ! isExternalLink ) {
				event.preventDefault();
			}

			if ( ! isReviewRequest ) {
				dismissAlert( $alert, alertAction );
				removeAlert( $alert );
			} else {
				dismissReviewRequest( reason );
				broadcastReviewDismissal();
			}
		} )
		.ready( function () {
			var context = window.pum_review_context;

			if ( ! context || ! context.needsImpression ) {
				return;
			}

			context.needsImpression = false;
			recordReviewRequest( 'shown_' + ( context.product || 'core' ) ).done(
				function () {
					trackReviewRequest(
						'shown_' + ( context.product || 'core' )
					);
				}
			);
		} );
} )( jQuery );
