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

defined( 'ABSPATH' ) || exit;

/**
 * Form Conversion Tracking Service.
 *
 * Tracks site-wide and per-popup form conversion counts for:
 * - Milestone-based upsell triggers
 * - Future analytics dashboard
 *
 * @since X.X.X
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
	 * @since X.X.X
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
	 * @since X.X.X
	 *
	 * @param array<string, mixed> $args {
	 *     Form submission arguments.
	 *
	 *     @type int|null    $popup_id      Popup ID that captured the submission.
	 *     @type string|null $form_provider Form plugin name (e.g., 'gravity-forms').
	 *     @type string|null $form_id       Form ID from the provider.
	 *     @type bool        $tracked       Whether already tracked by other systems.
	 * }
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
		$popup = get_post( $popup_id );
		if ( ! $popup || 'popup' !== get_post_type( $popup ) ) {
			// Log but don't break form submission - tracking is non-critical.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Popup Maker] Skipping form conversion tracking for invalid popup ID: %d', $popup_id ) );
			}
			return;
		}

		// Increment site-wide form conversion count.
		$this->increment_site_count();

		// Increment per-popup count.
		$this->increment_popup_count( $popup_id );
	}

	/**
	 * Track AJAX form conversion from analytics beacon.
	 *
	 * Handles conversions tracked via frontend JS beacon (AJAX submissions).
	 *
	 * @since X.X.X
	 *
	 * @param int   $popup_id Popup ID from analytics beacon.
	 * @param array $args     Additional arguments from beacon.
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
		$popup = get_post( $popup_id );
		if ( ! $popup || 'popup' !== get_post_type( $popup ) ) {
			// Log but don't break form submission - tracking is non-critical.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Popup Maker] Skipping AJAX form conversion tracking for invalid popup ID: %d', $popup_id ) );
			}
			return;
		}

		// Increment site-wide form conversion count.
		$this->increment_site_count();

		// Increment per-popup count.
		$this->increment_popup_count( $popup_id );
	}

	/**
	 * Increment site-wide form conversion count.
	 *
	 * Uses atomic SQL update to prevent race conditions when multiple
	 * form submissions occur simultaneously.
	 *
	 * @since X.X.X
	 *
	 * @return int New count after increment.
	 */
	protected function increment_site_count() {
		global $wpdb;

		// Check if option exists; if not, create it with autoload disabled.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::SITE_COUNT_KEY
			)
		);

		if ( ! $exists ) {
			// Initialize with autoload=no (analytical data doesn't need to load on every request).
			add_option( self::SITE_COUNT_KEY, 0, '', false );
		}

		// Atomic increment (prevents race condition).
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s",
				self::SITE_COUNT_KEY
			)
		);

		// Clear cache since we bypassed WordPress's caching layer.
		wp_cache_delete( self::SITE_COUNT_KEY, 'options' );

		// Return updated count.
		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Increment per-popup form conversion count.
	 *
	 * Uses atomic SQL update to prevent race conditions when multiple
	 * form submissions occur simultaneously for the same popup.
	 *
	 * @since X.X.X
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int New count after increment.
	 */
	protected function increment_popup_count( $popup_id ) {
		global $wpdb;

		// Check if meta exists; if not, create it.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$popup_id,
				self::POPUP_META_KEY
			)
		);

		if ( ! $exists ) {
			add_post_meta( $popup_id, self::POPUP_META_KEY, 0, true );
		}

		// Atomic increment (prevents race condition).
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
				$popup_id,
				self::POPUP_META_KEY
			)
		);

		// Clear cache since we bypassed WordPress's caching layer.
		wp_cache_delete( $popup_id, 'post_meta' );

		// Return updated count.
		return (int) get_post_meta( $popup_id, self::POPUP_META_KEY, true );
	}

	/**
	 * Get site-wide form conversion count.
	 *
	 * @since X.X.X
	 *
	 * @return int Total form conversions across all popups.
	 */
	public function get_site_count() {
		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Get form conversion count for a specific popup.
	 *
	 * @since X.X.X
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
	 * @since X.X.X
	 */
	public function reset_site_count() {
		delete_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Reset form conversion count for a specific popup.
	 *
	 * @since X.X.X
	 *
	 * @param int $popup_id Popup post ID.
	 */
	public function reset_popup_count( $popup_id ) {
		delete_post_meta( $popup_id, self::POPUP_META_KEY );
	}
}
