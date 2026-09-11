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
 * Manages core editor popup previews.
 *
 * Scope is deliberately narrow: the `popup_preview` nonce flow used by the
 * classic and block editors, which renders a real popup on a real page with a
 * debug trigger.
 *
 * Builder preview buttons may reuse this real-page flow, while their native
 * editing canvases remain the responsibility of the Builders controller.
 *
 * @since 1.25.0
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

		add_action( 'template_redirect', [ $this, 'force_load_preview' ] );
		add_filter( 'pum_popup_is_loadable', [ $this, 'is_loadable' ], 1000, 2 );
		add_filter( 'pum_popup_data_attr', [ $this, 'data_attr' ], 1000, 2 );
		add_filter( 'pum_popup_get_public_settings', [ $this, 'get_public_settings' ], 1000, 2 );
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

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified below.
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
	 * Create an authorized real-page preview URL for a popup.
	 *
	 * WordPress preview parameters let page builders select the current
	 * autosave while Popup Maker's existing preview parameters force the popup
	 * to load on the site's front page.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return string Preview URL, or an empty string for an invalid popup.
	 */
	public function get_preview_url( $popup_id ) {
		$popup_id = absint( $popup_id );

		if (
			! $popup_id ||
			'popup' !== get_post_type( $popup_id ) ||
			! current_user_can( 'edit_post', $popup_id )
		) {
			return '';
		}

		return add_query_arg(
			[
				'popup_preview' => wp_create_nonce( 'popup-preview' ),
				'popup'         => $popup_id,
				'preview'       => 'true',
				'preview_id'    => $popup_id,
				'preview_nonce' => wp_create_nonce( 'post_preview_' . $popup_id ),
			],
			home_url( '/' )
		);
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
	 * Force the previewed popup to load.
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 * @param int  $popup_id Popup ID.
	 *
	 * @return bool Whether the popup is loadable.
	 */
	public function is_loadable( $loadable, $popup_id ) {
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
		if ( ! is_array( $data_attr ) || ! $this->is_previewing_popup( $popup_id ) ) {
			return $data_attr;
		}

		$data_attr['triggers'] = $this->preview_triggers();

		return $data_attr;
	}

	/**
	 * Adjust triggers for previewed popups.
	 *
	 * @param array           $settings Popup public settings.
	 * @param PUM_Model_Popup $popup Popup model.
	 *
	 * @return array
	 */
	public function get_public_settings( $settings, $popup ) {
		if (
			! is_array( $settings ) ||
			! is_object( $popup ) ||
			! isset( $popup->ID ) ||
			! $this->is_previewing_popup( $popup->ID )
		) {
			return $settings;
		}

		$settings['triggers'] = $this->preview_triggers();

		return $settings;
	}

	/**
	 * Get the debug trigger used by real-page previews.
	 *
	 * @return array
	 */
	private function preview_triggers() {
		return [
			[
				'type' => 'admin_debug',
			],
		];
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

		return absint( $popup_id ) === $preview_id && current_user_can( 'edit_post', $preview_id );
	}
}
