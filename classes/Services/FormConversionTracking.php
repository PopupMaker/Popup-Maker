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
 * - Opt-in usage tracking integration
 *
 * @since 1.21.3
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
	 * @since 1.21.3
	 */
	public function init() {
		add_action( 'pum_integrated_form_submission', [ $this, 'track_form_conversion' ], 10, 1 );
	}

	/**
	 * Track form conversion when a form is submitted.
	 *
	 * Increments both site-wide and per-popup conversion counts.
	 *
	 * @since 1.21.3
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
		// Skip if already tracked by another system to prevent duplicates.
		if ( ! empty( $args['tracked'] ) ) {
			return;
		}

		// Increment site-wide form conversion count.
		$this->increment_site_count();

		// Increment per-popup count if popup_id is available.
		if ( ! empty( $args['popup_id'] ) && is_numeric( $args['popup_id'] ) ) {
			$this->increment_popup_count( (int) $args['popup_id'] );
		}

		/*
		 * TODO: Integrate with opt-in usage tracking when ready.
		 * Example:
		 * if ( pum_is_tracking_enabled() ) {
		 *     PUM_Tracking::track_event( 'form_conversion', [
		 *         'popup_id'      => $args['popup_id'],
		 *         'form_provider' => $args['form_provider'],
		 *     ]);
		 * }
		 */
	}

	/**
	 * Increment site-wide form conversion count.
	 *
	 * @since 1.21.3
	 *
	 * @return int New count after increment.
	 */
	protected function increment_site_count() {
		$count = (int) get_option( self::SITE_COUNT_KEY, 0 );
		++$count;
		update_option( self::SITE_COUNT_KEY, $count );

		return $count;
	}

	/**
	 * Increment per-popup form conversion count.
	 *
	 * @since 1.21.3
	 *
	 * @param int $popup_id Popup post ID.
	 * @return int New count after increment.
	 */
	protected function increment_popup_count( $popup_id ) {
		$count = (int) get_post_meta( $popup_id, self::POPUP_META_KEY, true );
		++$count;
		update_post_meta( $popup_id, self::POPUP_META_KEY, $count );

		return $count;
	}

	/**
	 * Get site-wide form conversion count.
	 *
	 * @since 1.21.3
	 *
	 * @return int Total form conversions across all popups.
	 */
	public function get_site_count() {
		return (int) get_option( self::SITE_COUNT_KEY, 0 );
	}

	/**
	 * Get form conversion count for a specific popup.
	 *
	 * @since 1.21.3
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
	 * @since 1.21.3
	 */
	public function reset_site_count() {
		delete_option( self::SITE_COUNT_KEY );
	}

	/**
	 * Reset form conversion count for a specific popup.
	 *
	 * @since 1.21.3
	 *
	 * @param int $popup_id Popup post ID.
	 */
	public function reset_popup_count( $popup_id ) {
		delete_post_meta( $popup_id, self::POPUP_META_KEY );
	}
}
