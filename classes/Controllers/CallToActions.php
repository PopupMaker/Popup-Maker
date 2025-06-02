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
 * @since X.X.X
 */
class CallToActions extends Controller {

	/**
	 * Initialize cta actions
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
	}
	/**
	 * Checks for valid requests and properly handles them.
	 *
	 * Redirects when needed.
	 */
	public function template_redirect() {
		$cta_args = [ 'cta', 'pid' ];

		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		$cta_uuid = ! empty( $_GET['cta'] ) ? sanitize_text_field( wp_unslash( $_GET['cta'] ) ) : '';
		$popup_id = ! empty( $_GET['pid'] ) ? absint( $_GET['pid'] ) : null;
		$notrack  = (bool) ( ! empty( $_GET['notrack'] ) ? sanitize_text_field( wp_unslash( $_GET['notrack'] ) ) : false );
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		if ( empty( $cta_uuid ) ) {
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

		$extra_args = [
			'cta_uuid'   => $cta_uuid,
			'popup_id'   => $popup_id,
			'notrack'    => $notrack,
			'source_url' => wp_get_raw_referer(),
		];

		// Basic conversion tracking.
		if ( ! $notrack ) {
			\pum_track_conversion_event( $popup_id, $extra_args );
		}

		/**
		 * Allow extensions to handle their own CTA types.
		 *
		 * @param bool  $handled Whether the CTA was handled.
		 * @param CTA   $cta The CTA object.
		 * @param array $args {
		 *     @type int    $popup_id The popup ID if any
		 *     @type string $cta_uuid The CTA UUID
		 *     @type string $source_url The source URL
		 *     @type bool   $notrack Whether to not track the conversion
		 * }
		 */
		$actioned = apply_filters( 'popup_maker/cta_action', false, $call_to_action, $extra_args );

		if ( false !== $actioned ) {
			return;
		}

		$cta_type = $call_to_action->get_setting( 'type' );

		if ( empty( $cta_type ) ) {
			return;
		}

		$cta_type_handler = $this->container->get( 'cta_types' )->get( $cta_type );

		if ( ! $cta_type_handler instanceof \PopupMaker\Base\CallToAction ) {
			/**
			 * Allow extensions to handle their own CTA types.
			 *
			 * @param \PopupMaker\Base\CallToAction $call_to_action Call to action object.
			 * @param array                         $extra_args     Optional. Additional data passed to the handler (will include popup_id).
			 */
			do_action( 'pum_cta_' . $cta_type . '_action', $call_to_action, $extra_args );

			// Default to current URL without CTA parameters.
			$url = remove_query_arg( $cta_args );

			if ( ! $notrack ) {
				$call_to_action->increase_event_count( 'conversion' );
			}

			wp_safe_redirect( esc_url_raw( $url ) );
			exit;
		}

		$cta_type_handler->action_handler( $call_to_action, $extra_args );
	}
}
