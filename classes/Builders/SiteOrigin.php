<?php
/**
 * SiteOrigin Page Builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * SiteOrigin Page Builder support for popup documents.
 *
 * SiteOrigin edits in wp-admin and ships its own Popup Maker content filter,
 * including secondary-document CSS. This provider supplies the missing post
 * type support and routes its live editor through Popup Maker's framed canvas.
 *
 * @since 1.25.0
 */
class SiteOrigin extends PageBuilder {

	/** @var string */
	public $key = 'siteorigin';

	/** @var string */
	protected $label = 'SiteOrigin Page Builder';

	/**
	 * Whether SiteOrigin's APIs used by this provider are available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( '\SiteOrigin_Panels' );
	}

	/**
	 * Keep existing SiteOrigin popup documents in its wp-admin editor.
	 *
	 * Popup support itself is left to SiteOrigin's own supported-post-types
	 * setting, since popups are already registered public and eligible there.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'use_block_editor_for_post_type', [ $this, 'use_classic_editor' ], 999, 2 );
		add_action( 'siteorigin_panels_metabox_end', [ $this, 'override_preview_url' ] );
	}

	/**
	 * Get the popup ID requested by SiteOrigin's live editor.
	 *
	 * Authorization is intentionally absent; the coordinator performs it.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		if ( ! isset( $_GET['siteorigin_panels_live_editor'] ) ) {
			return 0;
		}

		foreach ( [ 'p', 'post_id' ] as $param ) {
			if ( isset( $_GET[ $param ] ) && is_scalar( $_GET[ $param ] ) ) {
				$post_id = absint( wp_unslash( $_GET[ $param ] ) );

				if ( $post_id ) {
					return $post_id;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 0;
	}

	/**
	 * Preserve SiteOrigin's editor choice after Popup Maker's post type filter.
	 *
	 * @param mixed $use_block_editor Whether the block editor should be used.
	 * @param mixed $post_type        Post type being checked.
	 *
	 * @return mixed
	 */
	public function use_classic_editor( $use_block_editor, $post_type = '' ) {
		if ( ! is_string( $post_type ) || 'popup' !== $post_type ) {
			return $use_block_editor;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor routing flag.
		if ( isset( $_GET['siteorigin-page-builder'] ) ) {
			return false;
		}

		global $post;

		if (
			! $post instanceof \WP_Post ||
			'popup' !== $post->post_type ||
			( function_exists( 'has_blocks' ) && has_blocks( $post ) )
		) {
			return $use_block_editor;
		}

		return get_post_meta( $post->ID, 'panels_data', true ) ? false : $use_block_editor;
	}

	/**
	 * Point SiteOrigin's live editor at Popup Maker's authorized canvas.
	 *
	 * SiteOrigin reads the URL from the metabox's `data-preview-url` attribute.
	 * The inline script runs immediately before SiteOrigin's admin bundle, after
	 * the metabox exists but before the builder reads its configuration.
	 *
	 * @return void
	 */
	public function override_preview_url() {
		$post_id = absint( get_the_ID() );

		if ( ! $post_id || 'popup' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! wp_script_is( 'so-panels-admin', 'registered' ) ) {
			return;
		}

		$url = add_query_arg(
			[
				'post_type'                     => 'popup',
				'p'                             => $post_id,
				'siteorigin_panels_live_editor' => 'true',
				'_panelsnonce'                  => wp_create_nonce( 'live-editor-preview' ),
			],
			home_url( '/' )
		);

		$script = sprintf(
			'( function () { var metabox = document.getElementById( "siteorigin-panels-metabox" ); if ( metabox ) { metabox.setAttribute( "data-preview-url", %s ); } }() );',
			wp_json_encode( $url )
		);

		wp_add_inline_script( 'so-panels-admin', $script, 'before' );
	}
}
