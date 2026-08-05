<?php
/**
 * WPBakery Page Builder compatibility controller tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Controllers\Compatibility\Builder\WPBakery;

/**
 * Test WPBakery Page Builder compatibility.
 */
class WPBakery_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Close Mockery after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		\Mockery::close();
		parent::tear_down();
	}

	/**
	 * Test that the compatibility controller and its filter are registered.
	 *
	 * @return void
	 */
	public function test_compatibility_filter_is_registered() {
		$controller = \PopupMaker\plugin( 'Compatibility\Builder\WPBakery' );

		$this->assertInstanceOf( WPBakery::class, $controller );
		$this->assertSame( 999, has_filter( 'use_block_editor_for_post_type', [ $controller, 'force_classic_editor' ] ) );
	}

	/**
	 * Test that WPBakery forces the classic popup editor.
	 *
	 * @return void
	 */
	public function test_wpbakery_forces_classic_editor_for_popups() {
		$controller = $this->get_controller( true, [ 'popup' ] );

		$this->assertFalse( $controller->force_classic_editor( true, 'popup' ) );
	}

	/**
	 * Test that unrelated post types retain their editor choice.
	 *
	 * @return void
	 */
	public function test_wpbakery_does_not_change_other_post_types() {
		$controller = $this->get_controller( true, [ 'popup' ] );

		$this->assertTrue( $controller->force_classic_editor( true, 'post' ) );
		$this->assertTrue( $controller->force_classic_editor( true, 'popup_theme' ) );
	}

	/**
	 * Test that inactive WPBakery leaves the editor choice unchanged.
	 *
	 * @return void
	 */
	public function test_inactive_wpbakery_does_not_change_popup_editor() {
		$controller = $this->get_controller( false, null );

		$this->assertTrue( $controller->force_classic_editor( true, 'popup' ) );
		$this->assertFalse( $controller->force_classic_editor( false, 'popup' ) );
	}

	/**
	 * Test that WPBakery does not affect popups when it is not enabled for them.
	 *
	 * @return void
	 */
	public function test_wpbakery_does_not_change_popup_editor_when_popup_is_not_enabled() {
		$controller = $this->get_controller( true, [ 'page' ] );

		$this->assertTrue( $controller->force_classic_editor( true, 'popup' ) );
	}

	/**
	 * Test compatibility with WPBakery versions lacking the post type helper.
	 *
	 * @return void
	 */
	public function test_legacy_wpbakery_forces_classic_editor_for_popups() {
		$controller = $this->get_controller( true, null );

		$this->assertFalse( $controller->force_classic_editor( true, 'popup' ) );
	}

	/**
	 * Create a controller with simulated WPBakery availability.
	 *
	 * @param bool          $wpbakery_available Whether WPBakery is available.
	 * @param string[]|null $post_types         Post types enabled in WPBakery.
	 * @return WPBakery
	 */
	private function get_controller( $wpbakery_available, $post_types ) {
		$controller = \Mockery::mock( WPBakery::class, [ new stdClass() ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$controller->shouldReceive( 'is_wpbakery_active' )->andReturn( $wpbakery_available );
		$controller->shouldReceive( 'get_wpbakery_editor_post_types' )->andReturn( $post_types );

		return $controller;
	}
}
