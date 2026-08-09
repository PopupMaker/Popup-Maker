<?php
/**
 * Brizy builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Integrates Brizy editing, visitor rendering, and secondary assets.
 *
 * @since 1.25.0
 */
class Brizy extends PageBuilder {

	/**
	 * Popup documents already given to Brizy's asset manager.
	 *
	 * @var array<int,bool>
	 */
	private $collected_documents = [];

	/**
	 * Whether the current asset batch has been emitted.
	 *
	 * @var bool
	 */
	private $assets_finalized = false;

	/**
	 * Whether Brizy's editor API is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'BRIZY_VERSION' ) &&
			class_exists( '\Brizy_Editor' ) &&
			method_exists( '\Brizy_Editor', 'get' );
	}

	/**
	 * Opt popups into Brizy's supported post types for this request.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'brizy_supported_post_types', [ $this, 'add_popup_post_type' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_canvas_styles' ], 12 );
	}

	/**
	 * Let Brizy's first editable block size itself inside the popup.
	 *
	 * Brizy otherwise forces its first block to the full viewport height, which
	 * makes an auto-height popup ignore its own content size.
	 *
	 * @return void
	 */
	public function add_canvas_styles() {
		if ( ! $this->is_canvas_request() ) {
			return;
		}

		wp_add_inline_style(
			'popup-maker-builder-preview',
			'body.pum-builder-preview.brz-ed .pum-container .brz-ed-wrap-block-wrap--first{height:auto!important;}'
		);
	}

	/**
	 * Add popups to Brizy's runtime post type list.
	 *
	 * @param mixed $post_types Supported post types.
	 *
	 * @return mixed
	 */
	public function add_popup_post_type( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return $post_types;
		}

		if ( ! in_array( 'popup', $post_types, true ) ) {
			$post_types[] = 'popup';
		}

		return $post_types;
	}

	/**
	 * Get the popup ID requested by Brizy's editor shell or iframe.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		$is_shell  = isset( $_GET['action'] ) && is_scalar( $_GET['action'] ) && 'in-front-editor' === sanitize_key( wp_unslash( $_GET['action'] ) );
		$is_iframe = isset( $_GET['is-editor-iframe'] );

		if ( ! $is_shell && ! $is_iframe ) {
			return 0;
		}

		if ( ! isset( $_GET['post'] ) || ! is_scalar( $_GET['post'] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_GET['post'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether Brizy is rendering its editable iframe.
	 *
	 * The wp-admin shell keeps Brizy's template. Its iframe renders through the
	 * isolated popup canvas so the editable root retains the popup theme, size,
	 * and position around it.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		return isset( $_GET['is-editor-iframe'] );
	}

	/**
	 * Whether a popup is built with Brizy.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		if ( ! class_exists( '\Brizy_Editor_Entity' ) || ! method_exists( '\Brizy_Editor_Entity', 'isBrizyEnabled' ) ) {
			return false;
		}

		try {
			return (bool) \Brizy_Editor_Entity::isBrizyEnabled( absint( $popup_id ) );
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}
	}

	/**
	 * Render Brizy's compiled popup document for visitors.
	 *
	 * @param int  $popup_id         Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		if ( $is_editor_canvas ) {
			return '<div id="brz-ed-root"></div><div id="brz-popups"></div>';
		}

		if (
			! class_exists( '\Brizy_Editor_Post' ) ||
			! method_exists( '\Brizy_Editor_Post', 'get' ) ||
			! class_exists( '\Brizy_Public_Main' ) ||
			! method_exists( '\Brizy_Public_Main', 'get' )
		) {
			return null;
		}

		$this->collect_document_assets( $popup_id );
		$this->finalize_document_assets( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) );

		try {
			$post     = \Brizy_Editor_Post::get( absint( $popup_id ) );
			$renderer = is_object( $post ) ? \Brizy_Public_Main::get( $post ) : null;

			if ( ! is_object( $renderer ) || ! method_exists( $renderer, 'insert_page_content' ) ) {
				return null;
			}

			$content = $renderer->insert_page_content( 'brz-root__container' );

			return is_string( $content ) ? \PUM_Utils_Shortcodes::clean_do_shortcode( $content ) : null;
		} catch ( \Throwable $error ) {
			unset( $error );

			return null;
		}
	}

	/**
	 * Give Brizy one secondary popup document to enqueue.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	public function collect_document_assets( $popup_id ) {
		$popup_id = absint( $popup_id );

		if ( ! $popup_id || isset( $this->collected_documents[ $popup_id ] ) ) {
			return;
		}

		if (
			! class_exists( '\Brizy_Editor_Post' ) ||
			! method_exists( '\Brizy_Editor_Post', 'get' ) ||
			! class_exists( '\Brizy_Public_AssetEnqueueManager' ) ||
			! method_exists( '\Brizy_Public_AssetEnqueueManager', '_init' )
		) {
			return;
		}

		try {
			$post    = \Brizy_Editor_Post::get( $popup_id );
			$manager = \Brizy_Public_AssetEnqueueManager::_init();

			if ( ! is_object( $post ) || ! is_object( $manager ) || ! method_exists( $manager, 'enqueuePost' ) ) {
				return;
			}

			$manager->enqueuePost( $post );
			$this->collected_documents[ $popup_id ] = true;
			$this->assets_finalized                 = false;
		} catch ( \Throwable $error ) {
			unset( $error );
		}
	}

	/**
	 * Let Brizy emit collected assets at the current batch boundary.
	 *
	 * @param bool $after_head Whether head output has already been sent.
	 *
	 * @return bool
	 */
	public function finalize_document_assets( $after_head ) {
		if ( ! $after_head ) {
			return true;
		}

		if ( $this->assets_finalized ) {
			return true;
		}

		if (
			! class_exists( '\Brizy_Public_AssetEnqueueManager' ) ||
			! method_exists( '\Brizy_Public_AssetEnqueueManager', '_init' )
		) {
			return false;
		}

		try {
			$manager = \Brizy_Public_AssetEnqueueManager::_init();
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}

		if ( ! is_object( $manager ) || ! method_exists( $manager, 'enqueueStyles' ) || ! method_exists( $manager, 'enqueueScripts' ) ) {
			return false;
		}

		global $wp_styles;

		$before           = $wp_styles instanceof \WP_Styles ? (array) $wp_styles->queue : [];
		$head_code_before = $this->capture_head_code_assets( $manager );

		try {
			if ( ! did_action( 'brizy_preview_enqueue_scripts' ) ) {
				do_action( 'brizy_preview_enqueue_scripts' );
			}

			$manager->enqueueStyles();
			$manager->enqueueScripts();
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}

		$after = $wp_styles instanceof \WP_Styles ? (array) $wp_styles->queue : [];
		$new   = array_values( array_diff( $after, $before ) );

		if ( $new ) {
			wp_print_styles( $new );
		}

		$head_code_after = $this->capture_head_code_assets( $manager );

		if ( $head_code_after !== $head_code_before ) {
			if ( 0 === strpos( $head_code_after, $head_code_before ) ) {
				$head_code_after = substr( $head_code_after, strlen( $head_code_before ) );
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Brizy-generated head assets.
			echo $head_code_after;
		}

		$this->assets_finalized = true;

		return true;
	}

	/**
	 * Capture Brizy's code-type head assets without printing them immediately.
	 *
	 * @param object $manager Brizy asset manager.
	 *
	 * @return string
	 */
	private function capture_head_code_assets( $manager ) {
		if ( ! method_exists( $manager, 'insertHeadCodeAssets' ) ) {
			return '';
		}

		$buffer_level = ob_get_level();
		ob_start();

		try {
			$manager->insertHeadCodeAssets();
			$output = ob_get_clean();
		} catch ( \Throwable $error ) {
			unset( $error );

			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}

			return '';
		}

		return is_string( $output ) ? $output : '';
	}
}
