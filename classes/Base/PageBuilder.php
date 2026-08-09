<?php
/**
 * Page builder integration base.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal contract for bundled page builder integrations.
 *
 * Builders own their third-party APIs. The Builders controller owns the shared
 * WordPress request and popup rendering lifecycle.
 *
 * @since 1.25.0
 */
abstract class PageBuilder {

	/**
	 * Plugin container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	protected $container;

	/**
	 * Store shared dependencies.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Whether the third-party APIs used by this integration exist.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Register builder-specific hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {}

	/**
	 * Read a popup ID from the builder's native canvas request.
	 *
	 * Authorization belongs to the controller.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		return 0;
	}

	/**
	 * Whether the native builder request is its editable canvas.
	 *
	 * Frontend builders may claim a separate shell request and return false.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return true;
	}

	/**
	 * Whether this builder owns a popup document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		unset( $popup_id );

		return false;
	}

	/**
	 * Render a builder document.
	 *
	 * Return null to preserve WordPress's normal content pipeline.
	 *
	 * @param int  $popup_id        Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		unset( $popup_id, $is_editor_canvas );

		return null;
	}
}
