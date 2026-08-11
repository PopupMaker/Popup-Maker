<?php
/**
 * Etch page builder integration.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps Popup Maker out of Etch's front-page editor shell.
 *
 * Etch authors native WordPress blocks, so frontend rendering requires no
 * custom document renderer. Its editor shell does need request isolation.
 *
 * @since 1.25.0
 */
class Etch extends PageBuilder {

	/**
	 * Whether Etch is active.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'ETCH_PLUGIN_FILE' ) || class_exists( '\\Etch\\Plugin', false );
	}

	/**
	 * Add Etch's native launch action to popup rows.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'post_row_actions', [ $this, 'filter_row_actions' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_canvas_assets' ], 20 );
	}

	/**
	 * Read the popup ID from Etch's editor shell request.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// Etch's editor request has no nonce. The controller applies per-popup
		// capability checks and can_edit_document() mirrors Etch's own gate.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			! isset( $_GET['etch'], $_GET['post_id'] ) ||
			! is_scalar( $_GET['etch'] ) ||
			! is_scalar( $_GET['post_id'] )
		) {
			return 0;
		}

		$area     = sanitize_key( wp_unslash( $_GET['etch'] ) );
		$popup_id = absint( wp_unslash( $_GET['post_id'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 'magic' === $area ? $popup_id : 0;
	}

	/**
	 * Match Etch's own editor permission requirement.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Etch renders its canvas inside a front-page application shell.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return false;
	}

	/**
	 * Show Popup Maker's frame around Etch's iframe-owned document.
	 *
	 * Etch mounts its block nodes directly into a same-origin blank iframe. The
	 * shared preview helper styles that existing body as the popup container; it
	 * does not wrap, move, or replace any Etch-owned nodes.
	 *
	 * @return void
	 */
	public function enqueue_canvas_assets() {
		$builders = $this->container->get_controller( 'Builders' );

		if ( ! $builders instanceof \PopupMaker\Controllers\Builders ) {
			return;
		}

		$popup_id = $builders->get_edit_popup_id();

		if ( ! $popup_id || $popup_id !== $this->get_requested_popup_id() ) {
			return;
		}

		$this->enqueue_owned_canvas_preview(
			$popup_id,
			[
				'iframe_selector' => '#etch-iframe',
				'canvas_selector' => 'body',
				'style_selectors' => [
					'#popup-maker-site-css',
					'#popup-maker-site-inline-css',
				],
			]
		);
	}

	/**
	 * Add an Edit with Etch link to popup list rows.
	 *
	 * @param mixed $actions Existing row actions.
	 * @param mixed $post    Row post.
	 *
	 * @return mixed
	 */
	public function filter_row_actions( $actions, $post = null ) {
		if (
			! is_array( $actions ) ||
			! $post instanceof \WP_Post ||
			'popup' !== $post->post_type ||
			! current_user_can( 'manage_options' ) ||
			! current_user_can( 'edit_post', $post->ID )
		) {
			return $actions;
		}

		$url = add_query_arg(
			[
				'etch'    => 'magic',
				'post_id' => $post->ID,
			],
			home_url( '/' )
		);

		$actions['edit_with_etch'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $url ),
			esc_html__( 'Edit with Etch', 'popup-maker' )
		);

		return $actions;
	}
}
