<?php
/**
 * Form Conversion Tracking Service
 *
 * Tracks form submission conversions for upsell messaging and analytics.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2025, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;
use PopupMaker\Utils\AnalyticsCounter;

defined( 'ABSPATH' ) || exit;

/**
 * Form Conversion Tracking Service.
 *
 * Tracks site-wide and per-popup form conversion counts for:
 * - Milestone-based upsell triggers
 * - Future analytics dashboard
 *
 * @since 1.22.0
 */
class FormConversionTracking extends Service {

	/**
	 * Site-wide form conversion count option key.
	 */
	const SITE_COUNT_KEY = 'pum_form_conversion_count';

	/**
	 * Per-popup form conversion count meta key.
	 */
	const POPUP_META_KEY = '_pum_form_conversion_count';

	/**
	 * Initialize service.
	 *
	 * @since 1.22.0
	 */
	public function init() {
		// Track non-AJAX form submissions (PHP-side tracking).
		add_action( 'pum_integrated_form_submission', [ $this, 'track_form_conversion' ], 10, 1 );

		// Track AJAX form submissions (JS beacon tracking).
		add_action( 'pum_analytics_conversion', [ $this, 'track_ajax_conversion' ], 10, 2 );
	}

	/**
	 * Track form conversion when a form is submitted.
	 *
	 * Increments both site-wide and per-popup conversion counts.
	 *
	 * @since 1.22.0
	 *
	 * @param array<string, mixed> $args {
	 *     Form submission arguments.
	 *
	 *     @type int|null    $popup_id      Popup ID that captured the submission.
	 *     @type string|null $form_provider Form plugin name (e.g., 'gravity-forms').
	 *     @type string|null $form_id       Form ID from the provider.
	 *     @type bool        $tracked       Whether already tracked by other systems.
	 * }
	 * @return void
	 */
	public function track_form_conversion( $args ) {
		// Defensive validation for third-party hook callers.
		if ( ! is_array( $args ) ) {
			return;
		}

		// Skip if already tracked by another system to prevent duplicates.
		if ( ! empty( $args['tracked'] ) ) {
			return;
		}

		// Only track submissions that were captured by a popup.
		if ( empty( $args['popup_id'] ) || ! is_numeric( $args['popup_id'] ) ) {
			return;
		}

		$popup_id = (int) $args['popup_id'];

		// Verify popup exists before tracking (prevents orphaned meta).
		$popup = pum_get_popup( $popup_id );
		if ( ! pum_is_popup( $popup ) ) {
			// Log but don't break form submission - tracking is non-critical.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Popup Maker] Skipping form conversion tracking for invalid popup ID: %d', $popup_id ) );
			}
			return;
		}

		// Increment per-popup count.
		$popup_count = $this->increment_popup_count( $popup_id );

		if ( is_wp_error( $popup_count ) ) {
			AnalyticsCounter::report_failure( $popup_count, $popup_id, $args );
			return;
		}

		// Increment site-wide form conversion count.
		$site_count = $this->increment_site_count();

		if ( is_wp_error( $site_count ) ) {
			AnalyticsCounter::report_failure( $site_count, $popup_id, $args );
			return;
		}

		/**
		 * Fires after a form conversion is tracked (non-AJAX).
		 *
		 * @since 1.22.0
		 *
		 * @param int   $popup_id Popup ID.
		 * @param array $args     Form submission arguments.
		 */
		do_action( 'popup_maker/form_conversion_tracked', $popup_id, $args );
	}

	/**
	 * Track AJAX form conversion from analytics beacon.
	 *
	 * Handles conversions tracked via frontend JS beacon (AJAX submissions).
	 *
	 * @since 1.22.0
	 *
	 * @param int   $popup_id Popup ID from analytics beacon.
	 * @param array $args     Additional arguments from beacon.
	 * @return void
	 */
	public function track_ajax_conversion( $popup_id, $args = [] ) {
		// Defensive validation for third-party hook callers.
		if ( ! is_array( $args ) ) {
			return;
		}

		// Extract eventData (matches Pro's pattern).
		// REST endpoint sanitize_event_data() already decoded JSON to array.
		$event_data = isset( $args['eventData'] ) ? $args['eventData'] : [];

		// Only track conversions with explicit form submission metadata.
		if ( empty( $event_data ) || ! is_array( $event_data ) ) {
			return;
		}

		// Verify this is a form submission event (not CTA or link click).
		if ( empty( $event_data['type'] ) || 'form_submission' !== $event_data['type'] ) {
			return;
		}

		// Validate popup ID.
		if ( empty( $popup_id ) || ! is_numeric( $popup_id ) ) {
			return;
		}

		$popup_id = (int) $popup_id;

		// Verify popup exists before tracking (prevents orphaned meta).
		$popup = pum_get_popup( $popup_id );
		if ( ! pum_is_popup( $popup ) ) {
			// Log but don't break form submission - tracking is non-critical.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Popup Maker] Skipping AJAX form conversion tracking for invalid popup ID: %d', $popup_id ) );
			}
			return;
		}

		// Increment per-popup count.
		$popup_count = $this->increment_popup_count( $popup_id );

		if ( is_wp_error( $popup_count ) ) {
			AnalyticsCounter::report_failure( $popup_count, $popup_id, $event_data );
			return;
		}

		// Increment site-wide form conversion count.
		$site_count = $this->increment_site_count();

		if ( is_wp_error( $site_count ) ) {
			AnalyticsCounter::report_failure( $site_count, $popup_id, $event_data );
			return;
		}

		/**
		 * Fires after an AJAX form conversion is tracked.
		 *
		 * @since 1.22.0
		 *
		 * @param int   $popup_id   Popup ID.
		 * @param array $event_data Form submission event data.
		 */
		do_action( 'popup_maker/form_conversion_tracked', $popup_id, $event_data );
	}

	/**
	 * Increment site-wide form conversion count.
	 *
	 * Uses atomic SQL update to prevent race conditions when multiple
	 * form submissions occur simultaneously.
	 *
	 * @since 1.22.0
	 *
	 * @return int|\WP_Error New count or an explicit failure.
	 */
	protected function increment_site_count() {
		return AnalyticsCounter::increment_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Increment per-popup form conversion count.
	 *
	 * Uses atomic SQL update to prevent race conditions when multiple
	 * form submissions occur simultaneously for the same popup.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int|\WP_Error New count or an explicit retryable failure.
	 */
	protected function increment_popup_count( $popup_id ) {
		return AnalyticsCounter::increment_post_meta( $popup_id, self::POPUP_META_KEY );
	}

	/**
	 * Get site-wide form conversion count.
	 *
	 * @since 1.22.0
	 *
	 * @return int Total form conversions across all popups.
	 */
	public function get_site_count() {
		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Get form conversion count for a specific popup.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int Form conversions for this popup.
	 */
	public function get_popup_count( $popup_id ) {
		return (int) get_post_meta( $popup_id, self::POPUP_META_KEY, true );
	}

	/**
	 * Reset site-wide form conversion count.
	 *
	 * Useful for testing or if data needs to be cleared.
	 *
	 * @since 1.22.0
	 */
	public function reset_site_count() {
		delete_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Reset form conversion count for a specific popup.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 */
	public function reset_popup_count( $popup_id ) {
		delete_post_meta( $popup_id, self::POPUP_META_KEY );
	}
}
