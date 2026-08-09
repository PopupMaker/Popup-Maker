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
 * including secondary-document CSS. The provider therefore claims no runtime
 * coordinator capability; its hooks supply only the missing post type support
 * and a working live-preview target for Popup Maker's non-queryable post type.
 *
 * @since 1.25.0
 */
class SiteOrigin extends PageBuilder {

	/**
	 * Whether popup support existed before this provider injected it.
	 *
	 * @var bool|null
	 */
	private $stored_popup_support;

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
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'siteorigin_panels_settings', [ $this, 'add_popup_post_type' ] );
		add_filter( 'pre_update_option_siteorigin_panels_settings', [ $this, 'strip_injected_post_type' ], 10, 2 );

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
	 * Use SiteOrigin's classic wp-admin interface for its popup documents.
	 *
	 * Popup Maker otherwise enables the block editor for popups after
	 * SiteOrigin's own priority-10 compatibility check. Limit the override to
	 * existing SiteOrigin documents or its explicit new-builder request so an
	 * active SiteOrigin plugin does not take over unrelated popups.
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

		if ( ! $post instanceof \WP_Post || 'popup' !== $post->post_type ) {
			return $use_block_editor;
		}

		$panels_data = get_post_meta( $post->ID, 'panels_data', true );

		return ! empty( $panels_data ) ? false : $use_block_editor;
	}

	/**
	 * Add popups to SiteOrigin's supported post types at runtime.
	 *
	 * @param mixed $settings SiteOrigin settings.
	 *
	 * @return mixed
	 */
	public function add_popup_post_type( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$post_types = isset( $settings['post-types'] ) && is_array( $settings['post-types'] )
			? $settings['post-types']
			: [];

		if ( null === $this->stored_popup_support ) {
			$this->stored_popup_support = in_array( 'popup', $post_types, true );
		}

		if ( ! in_array( 'popup', $post_types, true ) ) {
			$post_types[]           = 'popup';
			$settings['post-types'] = $post_types;
		}

		return $settings;
	}

	/**
	 * Keep Popup Maker's runtime injection out of SiteOrigin's stored option.
	 *
	 * @param mixed $value     Settings being saved.
	 * @param mixed $old_value Settings currently stored.
	 *
	 * @return mixed
	 */
	public function strip_injected_post_type( $value, $old_value = null ) {
		if ( ! is_array( $value ) || ! isset( $value['post-types'] ) || ! is_array( $value['post-types'] ) ) {
			return $value;
		}

		$stored        = is_array( $old_value ) && isset( $old_value['post-types'] ) && is_array( $old_value['post-types'] )
			? $old_value['post-types']
			: [];
		$owner_enabled = null !== $this->stored_popup_support
			? $this->stored_popup_support
			: in_array( 'popup', $stored, true );

		if ( $owner_enabled ) {
			return $value;
		}

		$value['post-types'] = array_values(
			array_filter(
				$value['post-types'],
				function ( $post_type ) {
					return 'popup' !== $post_type;
				}
			)
		);

		return $value;
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
