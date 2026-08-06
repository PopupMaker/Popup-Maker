<?php
/**
 * Popup preview controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Manages popup previews for core editors and page builders.
 */
class Previews extends Controller {

	/**
	 * Whether the controller hooks have been registered.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Initialize preview hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		add_filter( 'request', [ $this, 'allow_builder_preview_request' ] );
		add_action( 'template_redirect', [ $this, 'force_load_preview' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_builder_preview' ], 11 );
		add_filter( 'template_include', [ $this, 'use_builder_preview_template' ], 9999 );
		add_filter( 'pum_popup_is_loadable', [ $this, 'is_loadable' ], 1000, 2 );
		add_filter( 'pum_popup_data_attr', [ $this, 'data_attr' ], 1000, 2 );
		add_filter( 'pum_popup_get_public_settings', [ $this, 'get_public_settings' ], 1000, 2 );
		add_filter( 'popup_maker/is_builder_preview', [ $this, 'is_builder_preview' ] );
	}

	/**
	 * Get the popup ID from a core editor preview request.
	 *
	 * @return false|int
	 */
	public function get_popup_preview() {
		if (
			! isset( $_GET['popup_preview'], $_GET['popup'] ) ||
			! is_scalar( $_GET['popup_preview'] ) ||
			! is_scalar( $_GET['popup'] )
		) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$nonce    = sanitize_text_field( wp_unslash( $_GET['popup_preview'] ) );
		$popup_id = sanitize_text_field( wp_unslash( $_GET['popup'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( $nonce, 'popup-preview' ) ) {
			return false;
		}

		if ( is_numeric( $popup_id ) && absint( $popup_id ) > 0 ) {
			return absint( $popup_id );
		}

		$post = get_page_by_path( $popup_id, OBJECT, 'popup' );

		return $post instanceof \WP_Post ? $post->ID : false;
	}

	/**
	 * Get the authorized popup ID supplied by a builder adapter.
	 *
	 * @return int Popup ID, or 0 when the request is not authorized.
	 */
	public function get_builder_preview() {
		$popup_id = absint( apply_filters( 'popup_maker/builder_preview_id', 0 ) );

		if (
			! $popup_id ||
			'popup' !== get_post_type( $popup_id ) ||
			! is_user_logged_in() ||
			! current_user_can( 'edit_post', $popup_id )
		) {
			return 0;
		}

		return $popup_id;
	}

	/**
	 * Get the current queried popup when it matches the builder preview.
	 *
	 * @return int Popup ID, or 0 when the main query is not the preview popup.
	 */
	public function get_current_builder_preview() {
		$popup_id = $this->get_builder_preview();

		if (
			! $popup_id ||
			! is_singular( 'popup' ) ||
			absint( get_queried_object_id() ) !== $popup_id
		) {
			return 0;
		}

		return $popup_id;
	}

	/**
	 * Restore the popup query for an authorized builder preview.
	 *
	 * @param mixed $query_vars Parsed WordPress query variables.
	 *
	 * @return mixed Filtered query variables.
	 */
	public function allow_builder_preview_request( $query_vars ) {
		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		$popup_id = $this->get_builder_preview();

		if ( $popup_id ) {
			$query_vars['p']         = $popup_id;
			$query_vars['post_type'] = 'popup';
		}

		return $query_vars;
	}

	/**
	 * Preload the builder preview popup and its assets.
	 *
	 * @return void
	 */
	public function preload_builder_preview() {
		$popup_id = $this->get_current_builder_preview();

		if ( ! $popup_id ) {
			return;
		}

		$popup  = $this->container->get( 'popups' )->get_by_id( $popup_id );
		$popups = $this->container->get_controller( 'Frontend\Popups' );

		if ( pum_is_popup( $popup ) && $popups instanceof \PopupMaker\Controllers\Frontend\Popups ) {
			$popups->preload_popup( $popup );
		}
	}

	/**
	 * Select the isolated popup canvas for a builder preview.
	 *
	 * @param string $template Selected WordPress template path.
	 *
	 * @return string Filtered template path.
	 */
	public function use_builder_preview_template( $template ) {
		if ( ! $this->get_current_builder_preview() ) {
			return $template;
		}

		$popup_template = $this->container->get_path( 'templates/single-popup.php' );

		if ( ! file_exists( $popup_template ) ) {
			return $template;
		}

		$popups = $this->container->get_controller( 'Frontend\Popups' );

		if ( ! $popups instanceof \PopupMaker\Controllers\Frontend\Popups ) {
			return $template;
		}

		remove_action( 'wp_footer', [ $popups, 'render_popups' ] );

		return $popup_template;
	}

	/**
	 * Identify an active builder preview for the shared popup template.
	 *
	 * @param bool $is_preview Whether another integration identified the request.
	 *
	 * @return bool Whether this is an active popup builder preview.
	 */
	public function is_builder_preview( $is_preview ) {
		return $is_preview || (bool) $this->get_current_builder_preview();
	}

	/**
	 * Force a core editor preview popup to load regardless of post status.
	 *
	 * @return void
	 */
	public function force_load_preview() {
		$popup_id = $this->get_popup_preview();

		if ( ! $popup_id || ! current_user_can( 'edit_post', $popup_id ) ) {
			return;
		}

		$popup = $this->container->get( 'popups' )->get_by_id( $popup_id );

		if ( ! pum_is_popup( $popup ) || $popup_id !== $popup->ID ) {
			return;
		}

		$popups = $this->container->get_controller( 'Frontend\Popups' );

		if ( $popups instanceof \PopupMaker\Controllers\Frontend\Popups ) {
			$popups->preload_popup( $popup );
		}
	}

	/**
	 * Force the target popup to load and isolate builder previews to that popup.
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 * @param int  $popup_id Popup ID.
	 *
	 * @return bool Whether the popup is loadable.
	 */
	public function is_loadable( $loadable, $popup_id ) {
		$builder_preview_id = $this->get_builder_preview();

		if ( $builder_preview_id ) {
			return absint( $popup_id ) === $builder_preview_id;
		}

		return $this->is_previewing_popup( $popup_id ) ? true : $loadable;
	}

	/**
	 * Add an admin debug trigger to core editor previews.
	 *
	 * @deprecated 1.16.10 Use get_public_settings instead.
	 *
	 * @param array $data_attr Popup data attributes.
	 * @param int   $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public function data_attr( $data_attr, $popup_id ) {
		if ( absint( $popup_id ) === $this->get_builder_preview() ) {
			$data_attr['triggers'] = [];

			return $data_attr;
		}

		if ( ! $this->is_previewing_popup( $popup_id ) ) {
			return $data_attr;
		}

		$data_attr['triggers'] = [
			[
				'type' => 'admin_debug',
			],
		];

		return $data_attr;
	}

	/**
	 * Add an admin debug trigger to core editor previews.
	 *
	 * @param array           $settings Popup public settings.
	 * @param PUM_Model_Popup $popup Popup model.
	 *
	 * @return array
	 */
	public function get_public_settings( $settings, $popup ) {
		if ( absint( $popup->ID ) === $this->get_builder_preview() ) {
			$settings['triggers'] = [];

			return $settings;
		}

		if ( ! $this->is_previewing_popup( $popup->ID ) ) {
			return $settings;
		}

		$settings['triggers'] = [
			[
				'type' => 'admin_debug',
			],
		];

		return $settings;
	}

	/**
	 * Whether a popup is the authorized core editor preview.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	private function is_previewing_popup( $popup_id = 0 ) {
		if ( wp_doing_ajax() ) {
			return false;
		}

		$preview_id = $this->get_popup_preview();

		return $popup_id === $preview_id && current_user_can( 'edit_post', $preview_id );
	}
}
