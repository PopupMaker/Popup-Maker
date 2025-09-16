<?php
/**
 * Call To Action handler class.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class CallToActions
 *
 * @since 1.21.0
 */
class CallToActions extends Controller {

	/**
	 * Initialize cta actions
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'popup_maker/cta_conversion', [ $this, 'track_cta_conversion' ], 10, 2 );
	}

	/**
	 * Checks for valid requests and properly handles them.
	 *
	 * Redirects when needed.
	 */
	public function template_redirect() {
		$cta_args = apply_filters( 'popup_maker/cta_valid_url_args', [ 'cta', 'pid' ] );

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$cta_uuid = ! empty( $_GET['cta'] ) ? sanitize_text_field( wp_unslash( $_GET['cta'] ) ) : '';
		$popup_id = ! empty( $_GET['pid'] ) ? absint( $_GET['pid'] ) : null;
		$notrack  = (bool) ( ! empty( $_GET['notrack'] ) ? sanitize_text_field( wp_unslash( $_GET['notrack'] ) ) : false );
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		/**
		 * Filter the CTA identifier before lookup.
		 *
		 * Allows extensions to modify or resolve the CTA identifier.
		 * For example, converting a slug to a UUID.
		 *
		 * @param string $cta_uuid The CTA identifier (UUID, slug, or custom identifier).
		 *
		 * @return string The resolved CTA identifier.
		 *
		 * @since 1.21.0
		 */
		$cta_uuid = apply_filters( 'popup_maker/cta_identifier', $cta_uuid );

		if ( empty( $cta_uuid ) ) {
			$this->handle_url_tracking( $popup_id, $notrack, $cta_args );
			return;
		}

		// Check for matching cta_{uuid}.
		$cta_uuid = str_replace( 'cta_', '', $cta_uuid );

		// Check for matching popup_{uuid}.
		$call_to_action = $this->container->get( 'ctas' )->get_by_uuid( $cta_uuid );

		// If no uuid is found, we don't have what we need, so return.
		if ( ! $call_to_action ) {
			return;
		}

		/**
		 * Filters the arguments passed to the CTA event.
		 *
		 * @param array<string,mixed> $extra_args {
		 *     @type int    $cta_id The CTA ID.
		 *     @type string $cta_uuid The CTA UUID.
		 *     @type int    $popup_id The popup ID.
		 *     @type bool   $notrack Whether to not track the conversion.
		 *     @type string $source_url The source URL.
		 * }
		 * @param \PopupMaker\Models\CallToAction $call_to_action The CTA object.
		 *
		 * @return array<string,mixed>
		 */
		$extra_args = apply_filters( 'popup_maker/cta_event_args', [
			'cta_id'     => $call_to_action->ID,
			'cta_uuid'   => $cta_uuid,
			'popup_id'   => $popup_id,
			'notrack'    => $notrack,
			'source_url' => wp_get_raw_referer(),
		], $call_to_action );

		$cta_type = $call_to_action->get_setting( 'type' );

		if ( empty( $cta_type ) ) {
			return;
		}

		$cta_type_handler = $call_to_action->get_action_type_handler();

		if ( false === $cta_type_handler ) {
			/**
			 * Allow extensions to handle their own CTA types.
			 *
			 * @param \PopupMaker\Models\CallToAction   $call_to_action Call to action object.
			 * @param array                             $extra_args     Optional. Additional data passed to the handler (will include popup_id).
			 */
			do_action( 'popup_maker/cta_' . $cta_type . '_action', $call_to_action, $extra_args );

			// Default to current URL without CTA parameters.
			$url = remove_query_arg( $cta_args );

			$call_to_action->track_conversion( $extra_args );

			\PopupMaker\safe_redirect( $url );
			exit;
		}

		// Check if the CTA requires the user to be logged in.
		$cta_type_handler->check_login_required();

		// Execute the CTA action.
		$cta_type_handler->action_handler( $call_to_action, $extra_args );
	}

	/**
	 * Track a popup conversion when CTA was clicked within a popup.
	 *
	 * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
	 * @param array                           $args           Arguments.
	 *
	 * @return void
	 */
	public function track_cta_conversion( $call_to_action, $args ) {
		$popup_id = $args['popup_id'];
		$notrack  = $args['notrack'];

		if ( ! $notrack && $popup_id && $popup_id > 0 ) {
			// Triggers pum_analytics_event / pum_analytics_conversion actions.
			\pum_track_conversion_event( $popup_id, $args );
		}
	}

	/**
	 * Handle URL tracking for non-CTA links with popup ID.
	 *
	 * @param int   $popup_id   The popup ID.
	 * @param bool  $notrack    Whether tracking is disabled.
	 * @param array $cta_args   Valid URL arguments for removal.
	 *
	 * @return void
	 */
	private function handle_url_tracking( $popup_id, $notrack, $cta_args ) {
		if ( ! $popup_id || $popup_id <= 0 ) {
			return;
		}

		/**
		 * Filters the arguments passed to the URL tracking event.
		 *
		 * @param array<string,mixed> $extra_args {
		 *     @type int    $popup_id   The popup ID.
		 *     @type bool   $notrack    Whether to not track the conversion.
		 *     @type string $source_url The source URL.
		 *     @type string $target_url The current URL being tracked.
		 * }
		 * @param int $popup_id The popup ID.
		 *
		 * @return array<string,mixed>
		 */
		$extra_args = apply_filters( 'popup_maker/url_tracking_event_args', [
			'popup_id'   => $popup_id,
			'notrack'    => $notrack,
			'source_url' => wp_get_raw_referer(),
			'target_url' => remove_query_arg( $cta_args ),
		], $popup_id );

		if ( ! $notrack ) {
			\pum_track_conversion_event( $popup_id, $extra_args );
		}

		$url = remove_query_arg( $cta_args );
		\PopupMaker\safe_redirect( $url );
		exit;
	}
}
