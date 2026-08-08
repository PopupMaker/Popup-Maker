<?php
/**
 * Signed builder preview URLs.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and verifies signed standalone popup preview URLs.
 *
 * This is the one piece of preview handling that is genuinely identical across
 * builders, so it lives in a stateless service rather than a trait: both sides
 * of the signature must agree, and a trait copied into each provider makes that
 * agreement easy to break.
 *
 * These URLs exist because WordPress cannot produce a preview URL for a
 * non-publicly-queryable post type. Unlike a builder's own canvas request —
 * which carries no nonce and relies on capability alone — this URL is one we
 * mint, so it is signed.
 *
 * @since 1.25.0
 */
class BuilderPreviewUrl {

	/**
	 * Request argument naming the builder being previewed.
	 */
	const BUILDER_ARG = 'pum-builder-preview';

	/**
	 * Create a signed standalone preview URL for a popup.
	 *
	 * @param int    $popup_id Popup ID.
	 * @param string $builder  Provider key.
	 *
	 * @return string Preview URL, or an empty string for invalid input.
	 */
	public static function create( $popup_id, $builder ) {
		$popup_id = absint( $popup_id );
		$builder  = sanitize_key( $builder );

		if ( ! $popup_id || '' === $builder ) {
			return '';
		}

		return add_query_arg(
			[
				'post_type'       => 'popup',
				'p'               => $popup_id,
				self::BUILDER_ARG => $builder,
				'_wpnonce'        => wp_create_nonce( self::action( $popup_id, $builder ) ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Read the popup ID from a signed standalone preview request.
	 *
	 * Verifies the signature only. The coordinator still checks post type and
	 * capability, so a valid signature alone never grants access.
	 *
	 * @param string $builder Provider key.
	 *
	 * @return int Popup ID, or 0 when the request is absent or unsigned.
	 */
	public static function read_request( $builder ) {
		$builder = sanitize_key( $builder );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified below.
		if (
			! isset( $_GET[ self::BUILDER_ARG ], $_GET['p'], $_GET['_wpnonce'] ) ||
			! is_scalar( $_GET[ self::BUILDER_ARG ] ) ||
			! is_scalar( $_GET['p'] ) ||
			! is_scalar( $_GET['_wpnonce'] )
		) {
			return 0;
		}

		$requested = sanitize_key( wp_unslash( $_GET[ self::BUILDER_ARG ] ) );
		$popup_id  = absint( wp_unslash( $_GET['p'] ) );
		$nonce     = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' === $builder || $builder !== $requested || ! $popup_id ) {
			return 0;
		}

		if ( ! wp_verify_nonce( $nonce, self::action( $popup_id, $builder ) ) ) {
			return 0;
		}

		return $popup_id;
	}

	/**
	 * Build the nonce action for a popup and builder pair.
	 *
	 * Binding both values means a signature for one popup cannot be replayed
	 * against another.
	 *
	 * @param int    $popup_id Popup ID.
	 * @param string $builder  Provider key.
	 *
	 * @return string
	 */
	private static function action( $popup_id, $builder ) {
		return 'pum_builder_preview_' . $builder . '_' . absint( $popup_id );
	}
}
