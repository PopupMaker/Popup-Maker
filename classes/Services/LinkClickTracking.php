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

		// Increment site-wide link click count.
		$this->increment_site_count();

		// Increment per-popup count.
		$this->increment_popup_count( $popup_id );

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
	 * @return int New count after increment.
	 */
	protected function increment_site_count() {
		global $wpdb;

		// Check if option exists; if not, create it with autoload disabled.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT option_id FROM %i WHERE option_name = %s LIMIT 1',
				$wpdb->options,
				self::SITE_COUNT_KEY
			)
		);

		if ( ! $exists ) {
			add_option( self::SITE_COUNT_KEY, 0, '', false );
		}

		// Atomic increment (prevents race condition).
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET option_value = option_value + 1 WHERE option_name = %s',
				$wpdb->options,
				self::SITE_COUNT_KEY
			)
		);

		wp_cache_delete( self::SITE_COUNT_KEY, 'options' );

		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Increment per-popup link click count.
	 *
	 * Uses atomic SQL update to prevent race conditions.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int New count after increment.
	 */
	protected function increment_popup_count( $popup_id ) {
		global $wpdb;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT meta_id FROM %i WHERE post_id = %d AND meta_key = %s LIMIT 1',
				$wpdb->postmeta,
				$popup_id,
				self::POPUP_META_KEY
			)
		);

		if ( ! $exists ) {
			add_post_meta( $popup_id, self::POPUP_META_KEY, 0, true );
		}

		// Atomic increment.
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s',
				$wpdb->postmeta,
				$popup_id,
				self::POPUP_META_KEY
			)
		);

		wp_cache_delete( $popup_id, 'post_meta' );

		return (int) get_post_meta( $popup_id, self::POPUP_META_KEY, true );
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
