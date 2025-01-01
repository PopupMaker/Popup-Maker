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
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		if ( empty( $cta_uuid ) ) {
			return;
		}

		// Check for matching cta_{uuid}.
		$cta_uuid = str_replace( 'cta_', '', $cta_uuid );

		// Check for matching popup_{uuid}.
		$cta = $this->container->get( 'ctas' )->get_by_uuid( $cta_uuid );

		// If no uuid is found, we don't have what we need, so return.
		if ( ! $cta ) {
			return;
		}

		/**
		 * Allow extensions to handle their own CTA types.
		 *
		 * @param bool  $handled Whether the CTA was handled.
		 * @param CTA   $cta The CTA object.
		 * @param array $args {
		 *     @type int    $popup_id The popup ID if any
		 *     @type string $cta_uuid The CTA UUID
		 * }
		 */
		$actioned = apply_filters('popup_maker/cta_action', false, $cta, [
			'popup_id' => $popup_id,
			'cta_uuid' => $cta_uuid,
		]);

		if ( ! $actioned ) {
			switch ( $cta->get_setting( 'type' ) ) {
				case 'link':
					// Simple link CTAs track basic conversion
					pum_track_conversion_event( $popup_id, [
						'cta_id' => $cta->id,
					]);

					// Get url from CTA (defaults to current URL) and strip CTA query args.
					$url = remove_query_arg(
						$cta_args,
						$cta->get_setting( 'url' )
					);

					wp_safe_redirect( esc_url_raw( $url ) );
					exit;

				default:
					/**
					 * Allow extensions to handle their own CTA types.
					 *
					 * @param int $popup_id The popup ID if any.
					 * @param CTA $cta The CTA object.
					 *
					 * @since X.X.X
					 */
					do_action( 'pum_cta_' . $cta->get_setting( 'type' ) . '_action', $popup_id, $cta );

					// Default to current URL without CTA parameters.
					$url = remove_query_arg( $cta_args );

					wp_safe_redirect( esc_url_raw( $url ) );
					exit;
			}
		}
	}
}
