<?php
/**
 * Page builder preview concern.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder\Concerns;

defined( 'ABSPATH' ) || exit;

/**
 * Adds builder request registration and signed standalone preview URLs.
 */
trait BuilderPreview {

	/**
	 * Register builder preview request handling.
	 *
	 * @return void
	 */
	protected function register_builder_preview() {
		add_filter( 'popup_maker/builder_preview_id', [ $this, 'filter_builder_preview_id' ] );
	}

	/**
	 * Supply this adapter's popup ID when no earlier builder matched.
	 *
	 * @param mixed $popup_id Popup ID supplied by another builder.
	 *
	 * @return int Popup ID, or 0 when no builder matches.
	 */
	public function filter_builder_preview_id( $popup_id ) {
		$popup_id = absint( $popup_id );

		return $popup_id ?: absint( $this->get_popup_id_from_request() );
	}

	/**
	 * Get the current popup builder preview.
	 *
	 * @return int Popup ID, or 0 when the main query is not the preview popup.
	 */
	protected function get_current_popup_preview_id() {
		$previews = $this->container->get_controller( 'Previews' );

		if ( ! $previews instanceof \PopupMaker\Controllers\Previews ) {
			return 0;
		}

		return $previews->get_current_builder_preview();
	}

	/**
	 * Create an authenticated standalone preview URL for a builder.
	 *
	 * @param int    $post_id Popup ID.
	 * @param string $builder Page builder key.
	 *
	 * @return string Preview URL.
	 */
	protected function get_standalone_preview_url( $post_id, $builder ) {
		$post_id = absint( $post_id );
		$builder = sanitize_key( $builder );

		return add_query_arg(
			[
				'post_type'           => 'popup',
				'p'                   => $post_id,
				'pum-builder-preview' => $builder,
				'_wpnonce'            => wp_create_nonce( 'pum_builder_preview_' . $builder . '_' . $post_id ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Read an authenticated standalone preview request for a builder.
	 *
	 * @param string $builder Page builder key.
	 *
	 * @return int Popup ID, or 0 when the request is invalid.
	 */
	protected function get_standalone_popup_id_from_request( $builder ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			! isset( $_GET['pum-builder-preview'], $_GET['p'], $_GET['_wpnonce'] ) ||
			! is_scalar( $_GET['pum-builder-preview'] ) ||
			! is_scalar( $_GET['p'] ) ||
			! is_scalar( $_GET['_wpnonce'] )
		) {
			return 0;
		}

		$builder         = sanitize_key( $builder );
		$request_builder = sanitize_key( wp_unslash( $_GET['pum-builder-preview'] ) );
		$post_id         = absint( wp_unslash( $_GET['p'] ) );
		$wp_nonce        = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		$nonce_key       = 'pum_builder_preview_' . $builder . '_' . $post_id;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $builder !== $request_builder || ! $post_id || ! wp_verify_nonce( $wp_nonce, $nonce_key ) ) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Get the popup ID represented by the builder-specific request.
	 *
	 * @return int Popup ID, or 0 when the request is not a valid builder preview.
	 */
	abstract protected function get_popup_id_from_request();
}
