<?php
/**
 * Tests for the assets controller.
 *
 * @package Popup_Maker
 */

/**
 * Verify expensive localized values are resolved only when needed.
 */
class Assets_Controller_Test extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_block_editor_localized_values_are_deferred() {
		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();

		$this->assertIsCallable( $packages['block-editor']['vars'] );

		$vars = call_user_func( $packages['block-editor']['vars'] );

		$this->assertSame( home_url(), $vars['homeUrl'] );
		$this->assertSame( pum_get_all_popups(), $vars['popups'] );
		$this->assertArrayHasKey( 'cta_types', $vars );
		$this->assertArrayHasKey( 'previewNonce', $vars );
		$this->assertArrayHasKey( 'popupTriggerExcludedBlocks', $vars );
	}
}
