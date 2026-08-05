<?php
/**
 * Page Builder Preview Compatibility Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;
use Popup_Maker;
use function PopupMaker\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Provides a secure, isolated popup canvas for front-end page builders.
 *
 * Builder-specific controllers only need to identify the popup ID represented
 * by the current request. This base controller handles authorization, query
 * restoration, assets, template selection, and suppression of other popups.
 */
abstract class Preview extends Controller {

	/**
	 * Initialize shared builder preview hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'request', [ $this, 'allow_popup_preview_request' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'preload_popup_preview' ], 10 );
		add_filter( 'template_include', [ $this, 'use_popup_preview_template' ], 9999 );
		add_filter( 'pum_popup_is_loadable', [ $this, 'limit_popups_in_preview' ], 9999, 2 );
		add_filter( 'popup_maker/is_builder_preview', [ $this, 'is_builder_preview' ] );
	}

	/**
	 * Restore the popup post type for an authorized builder preview request.
	 *
	 * WordPress removes non-publicly-queryable post types before applying the
	 * `request` filter. Adding it back here lets a builder resolve its preview
	 * without exposing popups to ordinary front-end requests.
	 *
	 * @param mixed $query_vars Parsed WordPress query variables.
	 *
	 * @return mixed Filtered query variables.
	 */
	public function allow_popup_preview_request( $query_vars ) {
		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		$post_id = $this->get_authorized_popup_id_from_request();

		if ( ! $post_id ) {
			return $query_vars;
		}

		$query_vars['p']         = $post_id;
		$query_vars['post_type'] = 'popup';

		return $query_vars;
	}

	/**
	 * Preload the edited popup so its theme and builder assets are available.
	 *
	 * This intentionally calls preload_popup() directly because builders can
	 * edit draft popups, while the ordinary front-end query excludes drafts.
	 *
	 * @return void
	 */
	public function preload_popup_preview() {
		$post_id = $this->get_current_popup_preview_id();

		if ( ! $post_id ) {
			return;
		}

		$popup = pum_get_popup( $post_id );

		if ( pum_is_popup( $popup ) ) {
			plugin()->get_controller( 'Frontend\Popups' )->preload_popup( $popup );
		}
	}

	/**
	 * Use Popup Maker's isolated page-builder canvas for the preview.
	 *
	 * @param string $template Selected WordPress template path.
	 *
	 * @return string Filtered template path.
	 */
	public function use_popup_preview_template( $template ) {
		if ( ! $this->get_current_popup_preview_id() ) {
			return $template;
		}

		$popup_template = Popup_Maker::$DIR . 'templates/single-popup.php';

		if ( ! file_exists( $popup_template ) ) {
			return $template;
		}

		$popups = plugin()->get_controller( 'Frontend\Popups' );
		remove_action( 'wp_footer', [ $popups, 'render_popups' ] );

		return $popup_template;
	}

	/**
	 * Load only the popup being edited during a builder preview.
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 * @param int  $popup_id Popup ID being checked.
	 *
	 * @return bool Whether the popup is loadable.
	 */
	public function limit_popups_in_preview( $loadable, $popup_id ) {
		$preview_id = $this->get_authorized_popup_id_from_request();

		if ( ! $preview_id ) {
			return $loadable;
		}

		return absint( $popup_id ) === $preview_id;
	}

	/**
	 * Identify an active builder preview for the shared popup template.
	 *
	 * @param bool $is_preview Whether another builder identified the request.
	 *
	 * @return bool Whether this is an active popup builder preview.
	 */
	public function is_builder_preview( $is_preview ) {
		return $is_preview || (bool) $this->get_current_popup_preview_id();
	}

	/**
	 * Get the current queried popup when it matches the authorized preview.
	 *
	 * @return int Popup ID, or 0 when the main query is not the preview popup.
	 */
	private function get_current_popup_preview_id() {
		$post_id = $this->get_authorized_popup_id_from_request();

		if (
			! $post_id ||
			! is_singular( 'popup' ) ||
			absint( get_queried_object_id() ) !== $post_id
		) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Get a builder preview popup the current user can edit.
	 *
	 * @return int Popup ID, or 0 when the request is not authorized.
	 */
	private function get_authorized_popup_id_from_request() {
		$post_id = $this->get_popup_id_from_request();

		if (
			! $post_id ||
			'popup' !== get_post_type( $post_id ) ||
			! is_user_logged_in() ||
			! current_user_can( 'edit_post', $post_id )
		) {
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
