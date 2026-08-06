<?php
/**
 * Page builder asset batching concern.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder\Concerns;

defined( 'ABSPATH' ) || exit;

/**
 * Batches builder asset finalization across early and late popup rendering.
 */
trait AssetBatching {

	/**
	 * Whether this builder has assets waiting to be finalized.
	 *
	 * @var bool
	 */
	private $builder_assets_pending = false;

	/**
	 * Register builder asset batch boundaries.
	 *
	 * @return void
	 */
	protected function register_builder_asset_batching() {
		add_action( 'wp_enqueue_scripts', [ $this, 'flush_pending_builder_assets' ], 12 );
		add_action( 'wp_footer', [ $this, 'flush_pending_builder_assets' ], 0 );
	}

	/**
	 * Mark this builder's collected assets for the next batch finalization.
	 *
	 * @return void
	 */
	protected function mark_builder_assets_pending() {
		$this->builder_assets_pending = true;
	}

	/**
	 * Finalize one batch of builder assets when work is pending.
	 *
	 * @return void
	 */
	public function flush_pending_builder_assets() {
		if ( ! $this->builder_assets_pending ) {
			return;
		}

		if ( $this->finalize_builder_assets() ) {
			$this->builder_assets_pending = false;
		}
	}

	/**
	 * Finalize assets registered by the builder adapter.
	 *
	 * @return bool Whether the pending batch was finalized.
	 */
	abstract protected function finalize_builder_assets();
}
