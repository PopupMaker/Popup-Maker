<?php
/**
 * Page builder preview adapter base.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Shares builder request registration, preview URLs, and asset batching.
 *
 * The main Previews controller owns authorization, query restoration, popup
 * loading, and template selection for every editor.
 */
abstract class Preview extends Controller {

	/**
	 * Whether this builder has assets waiting to be finalized.
	 *
	 * @var bool
	 */
	private $builder_assets_pending = false;

	/**
	 * Initialize shared builder adapter hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'popup_maker/builder_preview_id', [ $this, 'filter_builder_preview_id' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'flush_pending_builder_assets' ], 12 );
		add_action( 'wp_footer', [ $this, 'flush_pending_builder_assets' ], 0 );
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
	 * Mark this builder's collected assets for the next batch finalization.
	 *
	 * @return void
	 */
	protected function mark_builder_assets_pending() {
		$this->builder_assets_pending = true;
	}

	/**
	 * Finalize one batch of builder assets when work is pending.
	 *
	 * @return void
	 */
	public function flush_pending_builder_assets() {
		if ( ! $this->builder_assets_pending ) {
			return;
		}

		if ( $this->finalize_builder_assets() ) {
			$this->builder_assets_pending = false;
		}
	}

	/**
	 * Finalize assets registered by the builder adapter.
	 *
	 * @return bool Whether the pending batch was finalized.
	 */
	protected function finalize_builder_assets() {
		return true;
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
