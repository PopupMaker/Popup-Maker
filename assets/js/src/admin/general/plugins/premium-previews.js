/**
 * Premium Feature Previews
 *
 * Intercepts clicks on pro_required triggers/conditions,
 * fires a global event, and renders upsell modals.
 *
 * @since 1.22.0
 */
( function ( $, document ) {
	'use strict';

	var previews = pum_admin_vars.premium_previews || {};

	// Bail if no preview data (Pro is active or data missing).
	if (
		! previews ||
		( ! previews.triggers &&
			! previews.conditions )
	) {
		return;
	}

	var previewTriggers = previews.triggers || {};
	var previewConditions = previews.conditions || {};
	var I10n = previews.I10n || {};

	/**
	 * Check if a trigger/condition ID is a premium preview.
	 *
	 * @param {string} id    Feature ID.
	 * @param {string} type  'trigger' or 'condition'.
	 * @return {Object|false} Preview data or false.
	 */
	function getPreviewData( id, type ) {
		var source = type === 'trigger' ? previewTriggers : previewConditions;
		return source[ id ] || false;
	}

	/**
	 * Build upsell content using existing Go Pro hero/bar markup.
	 *
	 * Reuses pum-go-pro-hero and pum-proplus-bar CSS classes
	 * for visual consistency with the Go Pro settings tab.
	 *
	 * @param {Object} data Preview feature data.
	 * @return {string} HTML content.
	 */
	function buildModalContent( data ) {
		var tierLabel = data.tier === 'pro_plus'
			? I10n.pro_plus || 'Pro+'
			: I10n.pro_feature || 'Pro';
		var ctaLabel = data.cta || 'Increase My Conversion Rate';
		var badgeClass = data.tier === 'pro_plus' ? 'pum-premium-hero__badge--plus' : '';

		var html = '';

		// Hero layout — compact version for modal context.
		html += '<div class="pum-go-pro-hero pum-premium-hero">';
		html += '<div class="pum-go-pro-hero__body">';
		html += '<div class="pum-go-pro-hero__main">';

		// Badge + feature name (small).
		html += '<div class="pum-premium-hero__label">';
		html += '<span class="pum-go-pro-hero__pro-badge ' + badgeClass + '">' + tierLabel + '</span>';
		html += ' ' + data.label;
		html += '</div>';

		// Punchy headline.
		html += '<p class="pum-go-pro-hero__tagline">' + data.description + '</p>';

		// Bullet points as feature list.
		if ( data.bullets && data.bullets.length ) {
			html += '<ul class="pum-go-pro-hero__features">';
			for ( var i = 0; i < data.bullets.length; i++ ) {
				html += '<li>' + data.bullets[ i ] + '</li>';
			}
			html += '</ul>';
		}

		html += '</div>'; // __main
		html += '</div>'; // __body

		// Stats + CTA footer.
		html += '<div class="pum-go-pro-hero__footer">';

		// Inline stats.
		html += '<div class="pum-premium-hero__stats-row">';
		html += '<div class="pum-go-pro-stat"><div class="pum-go-pro-stat__value">780K+</div><div class="pum-go-pro-stat__label">Active Sites</div></div>';
		html += '<div class="pum-go-pro-stat"><div class="pum-go-pro-stat__value">4,271</div><div class="pum-go-pro-stat__label">5-Star Reviews</div></div>';
		html += '<div class="pum-go-pro-stat"><div class="pum-go-pro-stat__value">4.9</div><div class="pum-go-pro-stat__label">Rating</div></div>';
		html += '</div>';

		// CTA.
		html += '<a href="' + data.upgrade_url + '" target="_blank" rel="noopener" class="pum-go-pro-hero__cta">' +
			ctaLabel +
			' <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>' +
			'</a>';

		if ( previews.all_features_url ) {
			html += '<span class="pum-go-pro-hero__price-note">' +
				'<a href="' + previews.all_features_url + '" target="_blank" rel="noopener">' +
				( previews.all_features_text || 'See All Features' ) +
				'</a></span>';
		}

		html += '</div>'; // __footer
		html += '</div>'; // pum-go-pro-hero

		return html;
	}

	/**
	 * Open the upsell modal for a premium feature.
	 *
	 * Swaps the current modal's content in-place to avoid flash.
	 * On close, restores the original modal content.
	 *
	 * @param {string} id   Feature ID.
	 * @param {string} type 'trigger' or 'condition'.
	 */
	function openUpsellModal( id, type ) {
		var data = getPreviewData( id, type );
		if ( ! data ) {
			return;
		}

		var tierLabel =
			data.tier === 'pro_plus'
				? I10n.pro_plus || 'Pro+ Feature'
				: I10n.pro_feature || 'Pro Feature';

		// Fire global event for future Phase 3 rich modals to intercept.
		var event = $.Event( 'pumProFeatureClick' );
		event.feature = id;
		event.featureType = type;
		event.tier = data.tier;
		event.label = data.label;
		event.group = data.group || '';
		$( document ).trigger( event );

		// If the event was default-prevented, a richer handler took over.
		if ( event.isDefaultPrevented() ) {
			return;
		}

		var tierClass = data.tier === 'pro_plus' ? 'pum-tier-pro_plus' : 'pum-tier-pro';

		// Find the currently visible modal and swap its content in-place.
		var $currentModal = $( '.pum-modal-background:visible' );

		if ( $currentModal.length ) {
			// Save original content for restoration on close.
			var $wrap = $currentModal.find( '.pum-modal-wrap' );
			var originalContent = $wrap.html();
			var originalClasses = $currentModal.attr( 'class' );

			// Add upsell classes.
			$currentModal.addClass( 'pum-premium-preview-modal ' + tierClass );

			// Swap the inner content — hero is the entire modal body.
			$wrap.html(
				'<button type="button" class="pum-premium-close"></button>' +
				buildModalContent( data )
			);

			// Restore function — shared by close button and Esc key.
			function restoreModal() {
				$currentModal.attr( 'class', originalClasses );
				$wrap.html( originalContent );
				$( document ).off( 'keydown.pumUpsell' );
				$( document ).trigger( 'pum_init' );
			}

			// Close button.
			$wrap.find( '.pum-premium-close' ).one( 'click.pumUpsell', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				restoreModal();
			} );

			// Escape key.
			$( document ).on( 'keydown.pumUpsell', function ( e ) {
				if ( e.keyCode === 27 ) {
					e.preventDefault();
					restoreModal();
				}
			} );

			// Click on background overlay.
			$currentModal.one( 'click.pumUpsell', function ( e ) {
				if ( $( e.target ).hasClass( 'pum-modal-background' ) ) {
					restoreModal();
				}
			} );
		} else {
			// No modal currently open — show standalone upsell (e.g., from conditions picker).
			var modalId = 'pum-premium-preview-modal';

			// Build a simple modal wrapper around the hero content.
			var modalHtml = '<div id="' + modalId + '" class="pum-modal-background pum-premium-preview-modal ' + tierClass + '" role="dialog" aria-modal="true">' +
				'<div class="pum-modal-wrap">' +
					'<button type="button" class="pum-premium-close"></button>' +
					buildModalContent( data ) +
				'</div>' +
			'</div>';

			$( '#' + modalId ).remove();
			$( 'body' ).append( modalHtml );
			PUM_Admin.modals.show( '#' + modalId );

			$( '#' + modalId + ' .pum-premium-close' ).one( 'click', function () {
				PUM_Admin.modals.closeAll();
			} );
		}
	}

	// Note: Trigger/condition intercepts are handled directly in triggers.js
	// and conditions.js to ensure they fire before the settings modal opens.
	// Lock indicators are added in triggers.select_list() and PHP condition names.

	// Export for external use.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.premiumPreviews = {
		getPreviewData: getPreviewData,
		openUpsellModal: openUpsellModal,
	};
} )( jQuery, document );
