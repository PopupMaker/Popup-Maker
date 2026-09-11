<?php
/**
 * Link Click Tracking Service
 *
 * Tracks link click conversions from popups for analytics.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2025, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;
use PopupMaker\Utils\AnalyticsCounter;

defined( 'ABSPATH' ) || exit;

/**
 * Link Click Tracking Service.
 *
 * Tracks site-wide and per-popup link click counts for:
 * - Analytics dashboard reporting
 * - Conversion tracking for external/special links (mailto, tel, etc.)
 *
 * @since 1.22.0
 */
class LinkClickTracking extends Service {

	/**
	 * Site-wide link click count option key.
	 */
	const SITE_COUNT_KEY = 'pum_link_click_count';

	/**
	 * Per-popup link click count meta key.
	 */
	const POPUP_META_KEY = '_pum_link_click_count';

	/**
	 * Initialize service.
	 *
	 * @since 1.22.0
	 */
	public function init() {
		// Track link click conversions from JS beacon.
		add_action( 'pum_analytics_conversion', [ $this, 'track_link_click' ], 10, 2 );
	}

	/**
	 * Track link click conversion from analytics beacon.
	 *
	 * Handles link clicks tracked via frontend JS beacon.
	 *
	 * @since 1.22.0
	 *
	 * @param int   $popup_id Popup ID from analytics beacon.
	 * @param array $args     Additional arguments from beacon.
	 * @return void
	 */
	public function track_link_click( $popup_id, $args = [] ) {
		// Defensive validation for third-party hook callers.
		if ( ! is_array( $args ) ) {
			return;
		}

		// Extract eventData (REST endpoint already decoded JSON to array).
		$event_data = isset( $args['eventData'] ) ? $args['eventData'] : [];

		// Only track conversions with explicit link click metadata.
		if ( empty( $event_data ) || ! is_array( $event_data ) ) {
			return;
		}

		// Verify this is a link click event (not form submission or CTA).
		if ( empty( $event_data['type'] ) || 'link_click' !== $event_data['type'] ) {
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
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Popup Maker] Skipping link click tracking for invalid popup ID: %d', $popup_id ) );
			}
			return;
		}

		// Increment per-popup count.
		$popup_count = $this->increment_popup_count( $popup_id );

		if ( is_wp_error( $popup_count ) ) {
			AnalyticsCounter::report_failure( $popup_count, $popup_id, $event_data );
			return;
		}

		// Increment site-wide link click count.
		$site_count = $this->increment_site_count();

		if ( is_wp_error( $site_count ) ) {
			AnalyticsCounter::report_failure( $site_count, $popup_id, $event_data );
			return;
		}

		/**
		 * Fires after a link click is tracked.
		 *
		 * @since 1.22.0
		 *
		 * @param int   $popup_id   Popup ID.
		 * @param array $event_data Link click event data (url, linkType, etc.).
		 */
		do_action( 'popup_maker/link_click_tracked', $popup_id, $event_data );
	}

	/**
	 * Increment site-wide link click count.
	 *
	 * Uses atomic SQL update to prevent race conditions.
	 *
	 * @since 1.22.0
	 *
	 * @return int|\WP_Error New count or an explicit failure.
	 */
	protected function increment_site_count() {
		return AnalyticsCounter::increment_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Increment per-popup link click count.
	 *
	 * Uses atomic SQL update to prevent race conditions.
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
	 * Get site-wide link click count.
	 *
	 * @since 1.22.0
	 *
	 * @return int Total link clicks across all popups.
	 */
	public function get_site_count() {
		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Get link click count for a specific popup.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int Link clicks for this popup.
	 */
	public function get_popup_count( $popup_id ) {
		return (int) get_post_meta( $popup_id, self::POPUP_META_KEY, true );
	}

	/**
	 * Reset site-wide link click count.
	 *
	 * @since 1.22.0
	 */
	public function reset_site_count() {
		delete_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Reset link click count for a specific popup.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 */
	public function reset_popup_count( $popup_id ) {
		delete_post_meta( $popup_id, self::POPUP_META_KEY );
	}
}
