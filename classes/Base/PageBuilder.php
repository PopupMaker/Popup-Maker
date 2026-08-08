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
 * Shared contract for bundled page builder integrations.
 *
 * A builder owns its third-party API calls and hooks. The Builders controller
 * owns the WordPress request lifecycle, authorization, rendering order, and
 * asset batching. Optional operations intentionally have safe defaults so a
 * builder only overrides the behavior it needs.
 *
 * @since 1.25.0
 */
abstract class PageBuilder {

	/**
	 * Stable builder key.
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * Plugin container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	protected $container;

	/**
	 * Whether this builder has registered its runtime hooks.
	 *
	 * @var bool
	 */
	private $ready = false;

	/**
	 * Store shared dependencies.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Get the stable builder key.
	 *
	 * @return string
	 */
	final public function key() {
		return sanitize_key( $this->key );
	}

	/**
	 * Register this builder when its third-party APIs are available.
	 *
	 * False results are not cached because plugin and theme builders become
	 * available at different WordPress lifecycle boundaries.
	 *
	 * @return bool Whether the builder is ready.
	 */
	final public function boot() {
		if ( $this->ready ) {
			return true;
		}

		if ( ! $this->is_available() ) {
			return false;
		}

		$this->register_hooks();
		$this->ready = true;

		return true;
	}

	/**
	 * Whether the builder has registered its runtime hooks.
	 *
	 * @return bool
	 */
	final public function is_ready() {
		return $this->ready;
	}

	/**
	 * Whether the third-party builder APIs used by this integration exist.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Register builder-specific hooks.
	 *
	 * @return void
	 */
	protected function register_hooks() {}

	/**
	 * Read the popup ID from the builder's native editor request.
	 *
	 * Authorization belongs to the controller.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		return 0;
	}

	/**
	 * Whether the native builder request renders the isolated popup canvas.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return false;
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
	 * Return null to preserve content from WordPress' normal content pipeline.
	 *
	 * @param int  $popup_id Popup ID.
	 * @param bool $is_canvas Whether the document is in the isolated canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_canvas = false ) {
		unset( $popup_id, $is_canvas );

		return null;
	}

	/**
	 * Collect one secondary document's assets.
	 *
	 * Return true only when the controller should schedule finalization.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function collect_document_assets( $popup_id ) {
		unset( $popup_id );

		return false;
	}

	/**
	 * Finalize all assets collected for this request boundary.
	 *
	 * Return false to retry at the next boundary.
	 *
	 * @param bool $after_head Whether wp_head() output has passed.
	 *
	 * @return bool
	 */
	public function finalize_document_assets( $after_head ) {
		unset( $after_head );

		return true;
	}
}
