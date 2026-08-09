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
 * Third-party page builders are *not* handled here. They need query
 * restoration, an isolated canvas, and secondary-document asset loading, none
 * of which are preview concerns — those live in
 * {@see \PopupMaker\Controllers\Builders}. This controller only asks that
 * controller whether a builder owns the request, so the two never fight over
 * the same popup.
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
		$builder_popup_id = $this->get_builder_popup_id();

		// A builder request edits exactly one popup; loading any other would
		// put unrelated popups in the editing canvas.
		if ( $builder_popup_id ) {
			return absint( $popup_id ) === $builder_popup_id;
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
		if ( ! is_array( $data_attr ) ) {
			return $data_attr;
		}

		$data_attr['triggers'] = $this->filter_triggers(
			isset( $data_attr['triggers'] ) ? $data_attr['triggers'] : [],
			$popup_id
		);

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
		if ( ! is_array( $settings ) || ! is_object( $popup ) || ! isset( $popup->ID ) ) {
			return $settings;
		}

		$settings['triggers'] = $this->filter_triggers(
			isset( $settings['triggers'] ) ? $settings['triggers'] : [],
			$popup->ID
		);

		return $settings;
	}

	/**
	 * Replace a popup's triggers for preview contexts.
	 *
	 * Builder canvases get no triggers at all: the canvas is already visible,
	 * so a time-delay or auto-open trigger would re-open it mid-edit and apply
	 * animations and focus locks while the user is working.
	 *
	 * Core editor previews get a single debug trigger so the popup opens once
	 * for inspection.
	 *
	 * @param array $triggers Existing triggers.
	 * @param int   $popup_id Popup ID.
	 *
	 * @return array
	 */
	private function filter_triggers( $triggers, $popup_id ) {
		if ( ! is_array( $triggers ) ) {
			$triggers = [];
		}

		if ( absint( $popup_id ) === $this->get_builder_popup_id() ) {
			return [];
		}

		if ( ! $this->is_previewing_popup( $popup_id ) ) {
			return $triggers;
		}

		return [
			[
				'type' => 'admin_debug',
			],
		];
	}

	/**
	 * Get the popup ID owned by an authorized builder request.
	 *
	 * @return int
	 */
	private function get_builder_popup_id() {
		$builders = $this->container->get_controller( 'Builders' );

		return $builders instanceof \PopupMaker\Controllers\Builders
			? $builders->get_edit_popup_id()
			: 0;
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
