<?php
/**
 * Visual Composer builder integration.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Integrates Visual Composer editing and secondary document assets.
 *
 * @since 1.25.0
 */
class VisualComposer extends PageBuilder {

	/** @var string */
	public $key = 'visual-composer';

	/** @var string */
	protected $label = 'Visual Composer';

	/**
	 * Popup documents already given to Visual Composer's asset queue.
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
	 * Whether Visual Composer's request API is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'VCV_VERSION' ) && function_exists( 'vchelper' );
	}

	/**
	 * Flush popup assets after Popup Maker's normal preload pass.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'flush_preloaded_assets' ], 12 );

		if ( ! function_exists( 'vchelper' ) ) {
			return;
		}

		try {
			$events = vchelper( 'Events' );

			if ( is_object( $events ) && method_exists( $events, 'listen' ) ) {
				$events->listen( 'vcv:api:postSaved', [ $this, 'remember_saved_document' ] );
			}
		} catch ( \Throwable $error ) {
			unset( $error );
		}
	}

	/**
	 * Honor Visual Composer's Role Manager and post access rules.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		if ( ! function_exists( 'vchelper' ) ) {
			return false;
		}

		try {
			$access = vchelper( 'AccessUserCapabilities' );

			return is_object( $access ) &&
				method_exists( $access, 'canEdit' ) &&
				(bool) $access->canEdit( absint( $popup_id ) );
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}
	}

	/**
	 * Remember Visual Composer after its authenticated save event.
	 *
	 * Parameter names mirror Visual Composer's associative event payload.
	 *
	 * @param mixed $sourceId Saved post ID.
	 * @param mixed $post     Saved post object.
	 *
	 * @return void
	 */
	public function remember_saved_document( $sourceId = 0, $post = null ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		unset( $post );

		if ( ! is_numeric( $sourceId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			return;
		}

		$popup_id = absint( $sourceId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( ! $popup_id || ! $this->owns_document( $popup_id ) ) {
			return;
		}

		$this->remember_document_owner( $popup_id );
	}

	/**
	 * Get the popup ID requested by Visual Composer's shell or editable page.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		$is_shell = isset( $_GET['vcv-action'] ) && is_scalar( $_GET['vcv-action'] ) && 'frontend' === sanitize_key( wp_unslash( $_GET['vcv-action'] ) );

		if ( ! $is_shell && ! isset( $_GET['vcv-editable'] ) ) {
			return 0;
		}

		if ( ! isset( $_GET['vcv-source-id'] ) || ! is_scalar( $_GET['vcv-source-id'] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_GET['vcv-source-id'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether Visual Composer is rendering its editable page.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		return isset( $_GET['vcv-editable'] );
	}

	/**
	 * Whether a popup was saved with Visual Composer.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		$popup_id     = absint( $popup_id );
		$page_content = get_post_meta( $popup_id, 'vcv-pageContent', true );
		$saved_editor = get_post_meta( $popup_id, 'vcv-be-editor', true );

		// Mirrors Visual Composer's Gutenberg::isVisualComposerPage() marker.
		return ! empty( $page_content ) && 'gutenberg' !== $saved_editor;
	}

	/**
	 * Supply the native editor mount or collect visitor assets.
	 *
	 * Visitor markup continues through WordPress's normal content pipeline.
	 *
	 * @param int  $popup_id         Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		if ( $is_editor_canvas ) {
			return '<div id="vcv-editor"></div>';
		}

		$this->collect_document_assets( $popup_id );

		if ( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) ) {
			$this->finalize_document_assets( true );
		}

		return null;
	}

	/**
	 * Add a secondary popup to Visual Composer's existing asset queue.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	public function collect_document_assets( $popup_id ) {
		$popup_id = absint( $popup_id );

		if ( ! $popup_id || isset( $this->collected_documents[ $popup_id ] ) || ! function_exists( 'vchelper' ) ) {
			return;
		}

		try {
			$assets = vchelper( 'AssetsEnqueue' );

			if ( ! is_object( $assets ) || ! method_exists( $assets, 'addToEnqueueList' ) ) {
				return;
			}

			$assets->addToEnqueueList( $popup_id );
			$this->collected_documents[ $popup_id ] = true;
			$this->assets_finalized                 = false;
		} catch ( \Throwable $error ) {
			unset( $error );
		}
	}

	/**
	 * Flush assets collected by Popup Maker's preload pass.
	 *
	 * @return void
	 */
	public function flush_preloaded_assets() {
		$this->finalize_document_assets( false );
	}

	/**
	 * Ask Visual Composer to enqueue the current secondary asset batch.
	 *
	 * @param bool $after_head Whether head output has already been sent.
	 *
	 * @return bool
	 */
	public function finalize_document_assets( $after_head ) {
		if ( ! $this->collected_documents || $this->assets_finalized ) {
			return true;
		}

		if ( ! function_exists( 'vcevent' ) ) {
			return false;
		}

		global $wp_styles;

		$before = $wp_styles instanceof \WP_Styles ? (array) $wp_styles->queue : [];

		try {
			vcevent( 'vcv:assets:enqueue:css:list' );
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}

		if ( $after_head ) {
			$after = $wp_styles instanceof \WP_Styles ? (array) $wp_styles->queue : [];
			$new   = array_values( array_diff( $after, $before ) );

			if ( $new ) {
				wp_print_styles( $new );
			}
		}

		$this->assets_finalized = true;

		return true;
	}
}
