<?php
/**
 * Shared page-builder asset batching tests.
 *
 * @package Popup_Maker
 */

/**
 * Test the shared page-builder asset batching concern.
 */
class Page_Builder_Preview_Test extends WP_UnitTestCase {

	/**
	 * Multiple documents are finalized once per successful batch.
	 *
	 * @return void
	 */
	public function test_builder_assets_are_finalized_once_per_batch() {
		$preview = $this->get_preview_double();

		$preview->mark_assets_pending();
		$preview->mark_assets_pending();
		$preview->flush_pending_builder_assets();
		$preview->flush_pending_builder_assets();

		$this->assertSame( 1, $preview->finalization_count );

		$preview->mark_assets_pending();
		$preview->flush_pending_builder_assets();

		$this->assertSame( 2, $preview->finalization_count );
	}

	/**
	 * A failed finalization remains pending for the next batch boundary.
	 *
	 * @return void
	 */
	public function test_failed_builder_asset_finalization_is_retried() {
		$preview                        = $this->get_preview_double();
		$preview->finalization_succeeds = false;

		$preview->mark_assets_pending();
		$preview->flush_pending_builder_assets();

		$preview->finalization_succeeds = true;
		$preview->flush_pending_builder_assets();
		$preview->flush_pending_builder_assets();

		$this->assertSame( 2, $preview->finalization_count );
	}

	/**
	 * Create a builder preview test double.
	 *
	 * @return \PopupMaker\Plugin\Controller
	 */
	private function get_preview_double() {
		return new class( \PopupMaker\plugin() ) extends \PopupMaker\Plugin\Controller {

			use \PopupMaker\Controllers\Compatibility\Builder\Concerns\AssetBatching;

			/**
			 * Number of finalization attempts.
			 *
			 * @var int
			 */
			public $finalization_count = 0;

			/**
			 * Whether finalization should succeed.
			 *
			 * @var bool
			 */
			public $finalization_succeeds = true;

			/**
			 * This test double does not register WordPress hooks.
			 *
			 * @return void
			 */
			public function init() {
			}

			/**
			 * Mark builder assets as pending.
			 *
			 * @return void
			 */
			public function mark_assets_pending() {
				$this->mark_builder_assets_pending();
			}

			/**
			 * Count a builder asset finalization attempt.
			 *
			 * @return bool Whether the batch was finalized.
			 */
			protected function finalize_builder_assets() {
				++$this->finalization_count;

				return $this->finalization_succeeds;
			}
		};
	}
}
