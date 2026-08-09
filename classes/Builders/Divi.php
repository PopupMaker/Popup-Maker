<?php
/**
 * Divi builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Divi support for popup documents.
 *
 * Divi's front-end Visual Builder needs the popup query restored and the
 * isolated canvas. Divi renders layouts and assets through its existing
 * `the_content` pipeline, so this provider claims no render or asset capability.
 *
 * Divi ships as both a theme and a plugin, so availability is probed the same
 * way Divi probes itself.
 *
 * @since 1.25.0
 */
class Divi extends PageBuilder {

	/**
	 * Whether Divi is active as either a theme or a plugin.
	 *
	 * @return bool
	 */
	public function is_available() {
		return ( defined( 'ET_BUILDER_THEME' ) && ET_BUILDER_THEME ) ||
			function_exists( 'et_divi_fonts_url' ) ||
			defined( 'ET_BUILDER_PLUGIN_VERSION' ) ||
			class_exists( 'ET_Builder_Plugin' ) ||
			defined( 'ET_BUILDER_VERSION' ) ||
			function_exists( 'et_setup_builder' );
	}

	/**
	 * Register Divi-specific hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'et_builder_post_types', [ $this, 'add_popup_support' ] );
		add_filter( 'et_fb_is_enabled', [ $this, 'enable_frontend_builder' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'include_popup_id_in_permalink' ], 10, 2 );
		add_action( 'et_save_post', [ $this, 'remember_saved_document' ], PHP_INT_MAX );

		// Divi 4's block-editor integration prevents Popup Maker's editors from loading.
		add_filter( 'use_block_editor_for_post_type', [ $this, 'force_classic_editor' ], 999, 2 );
		add_filter( 'pum_settings_fields', [ $this, 'explain_classic_editor_requirement' ] );
	}

	/**
	 * Keep the popup ID in URLs Divi turns into Visual Builder links.
	 *
	 * Popup Maker popups have no public rewrite route, so their generated URL
	 * cannot be resolved back to a post before the coordinator restores it.
	 *
	 * @param mixed $url  Post permalink.
	 * @param mixed $post Post being linked.
	 *
	 * @return mixed
	 */
	public function include_popup_id_in_permalink( $url, $post = null ) {
		if ( ! is_string( $url ) || ! $post instanceof \WP_Post || 'popup' !== $post->post_type ) {
			return $url;
		}

		return add_query_arg( 'p', absint( $post->ID ), $url );
	}

	/**
	 * Remember Divi after its authenticated save lifecycle completes.
	 *
	 * @param mixed $post_id Saved post ID.
	 *
	 * @return void
	 */
	public function remember_saved_document( $post_id ) {
		if (
			! is_numeric( $post_id ) ||
			'on' !== get_post_meta( absint( $post_id ), '_et_pb_use_builder', true )
		) {
			return;
		}

		$this->remember_document_owner( absint( $post_id ) );
	}

	/**
	 * Add popups to Divi's supported post types.
	 *
	 * @param mixed $post_types Supported post types.
	 *
	 * @return mixed
	 */
	public function add_popup_support( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return $post_types;
		}

		if ( ! in_array( 'popup', $post_types, true ) ) {
			$post_types[] = 'popup';
		}

		return $post_types;
	}

	/**
	 * Enable Divi's front-end builder for editable popups.
	 *
	 * @param mixed $enabled Current enabled state.
	 * @param mixed $post_id Post ID being checked.
	 *
	 * @return mixed
	 */
	public function enable_frontend_builder( $enabled, $post_id = 0 ) {
		if ( ! is_numeric( $post_id ) ) {
			return $enabled;
		}

		$post_id = absint( $post_id );

		if ( ! $post_id || 'popup' !== get_post_type( $post_id ) ) {
			return $enabled;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get the popup ID Divi is requesting.
	 *
	 * Authorization is intentionally absent; the coordinator performs it.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		if ( ! isset( $_GET['et_fb'] ) && ! isset( $_GET['et_bfb'] ) ) {
			return 0;
		}

		foreach ( [ 'p', 'post', 'post_id', 'et_post_id' ] as $param ) {
			if ( ! isset( $_GET[ $param ] ) || ! is_scalar( $_GET[ $param ] ) ) {
				continue;
			}

			$post_id = absint( wp_unslash( $_GET[ $param ] ) );

			if ( $post_id ) {
				return $post_id;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 0;
	}

	/**
	 * Whether this request renders the isolated canvas.
	 *
	 * Divi's front-end Visual Builder edits the rendered document, so it can use
	 * Popup Maker's isolated canvas. Its back-end builder uses the same `et_fb`
	 * flag together with `et_bfb`, but expects its own wireframe response.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		return isset( $_GET['et_fb'] ) && ! isset( $_GET['et_bfb'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether Divi owns the popup document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		if ( ! function_exists( 'et_pb_is_pagebuilder_used' ) ) {
			return false;
		}

		try {
			return (bool) et_pb_is_pagebuilder_used( absint( $popup_id ) );
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}
	}

	/**
	 * Give Divi's front-end Visual Builder its native mount hierarchy.
	 *
	 * Visitor rendering stays in Divi's existing content pipeline.
	 *
	 * @param int  $popup_id         Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		unset( $popup_id );

		return $is_editor_canvas
			? '<div id="et-boc"><div class="et-l"><div id="et-fb-app"></div></div></div>'
			: null;
	}

	/**
	 * Force the classic editor for popups when Divi 4 is active.
	 *
	 * @param mixed $use_block_editor Whether to use the block editor.
	 * @param mixed $post_type        Post type being checked.
	 *
	 * @return mixed
	 */
	public function force_classic_editor( $use_block_editor, $post_type = '' ) {
		if ( ! is_string( $post_type ) || ! in_array( $post_type, [ 'popup', 'popup_theme' ], true ) ) {
			return $use_block_editor;
		}

		if ( ! $this->is_divi_4() ) {
			return $use_block_editor;
		}

		return false;
	}

	/**
	 * Explain why the classic-editor setting is locked while Divi 4 is active.
	 *
	 * @param mixed $fields Popup Maker settings fields.
	 *
	 * @return mixed
	 */
	public function explain_classic_editor_requirement( $fields ) {
		if (
			! $this->is_divi_4() ||
			! is_array( $fields ) ||
			! isset( $fields['general']['main']['enable_classic_editor'] ) ||
			! is_array( $fields['general']['main']['enable_classic_editor'] )
		) {
			return $fields;
		}

		$field             = &$fields['general']['main']['enable_classic_editor'];
		$field['disabled'] = true;
		$description       = isset( $field['desc'] ) && is_string( $field['desc'] ) ? $field['desc'] : '';
		$field['desc']     = $description . '<br><strong>' . esc_html__(
			'Divi 4 requires the classic editor for popup editing. This setting is automatically enforced for compatibility.',
			'popup-maker'
		) . '</strong>';

		return $fields;
	}

	/**
	 * Whether the active Divi is a 4.x release.
	 *
	 * @return bool
	 */
	protected function is_divi_4() {
		if ( ! defined( 'ET_BUILDER_VERSION' ) ) {
			return false;
		}

		return version_compare( ET_BUILDER_VERSION, '4.0.0', '>=' ) &&
			version_compare( ET_BUILDER_VERSION, '5.0.0', '<' );
	}
}
