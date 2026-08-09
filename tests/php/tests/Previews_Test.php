<?php
/**
 * Popup preview controller tests.
 *
 * @package Popup_Maker
 */

/**
 * Test core editor preview behavior after the controller migration.
 */
class Previews_Test extends WP_UnitTestCase {

	/**
	 * Original query parameters.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get;

	/**
	 * Preserve query parameters before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
	}

	/**
	 * Restore global state after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * The modern controller owns the preview hooks.
	 *
	 * @return void
	 */
	public function test_modern_controller_owns_preview_hooks() {
		$previews = \PopupMaker\plugin()->get_controller( 'Previews' );

		$this->assertInstanceOf( \PopupMaker\Controllers\Previews::class, $previews );
		$this->assertNotFalse( has_filter( 'pum_popup_is_loadable', [ $previews, 'is_loadable' ] ) );
		$this->assertFalse( has_filter( 'pum_popup_is_loadable', [ PUM_Previews::class, 'is_loadable' ] ) );
	}

	/**
	 * Preview URLs use the existing real-page preview flow and preserve autosaves.
	 *
	 * @return void
	 */
	public function test_preview_url_targets_the_frontend_and_preserves_autosaves() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$previews = \PopupMaker\plugin()->get_controller( 'Previews' );
		$url      = $previews->get_preview_url( $popup_id );
		$query    = [];
		wp_parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$this->assertSame( home_url( '/' ), strtok( $url, '?' ) );
		$this->assertSame( (string) $popup_id, $query['popup'] );
		$this->assertSame( 'true', $query['preview'] );
		$this->assertSame( (string) $popup_id, $query['preview_id'] );
		$this->assertSame( 1, wp_verify_nonce( $query['popup_preview'], 'popup-preview' ) );
		$this->assertSame( 1, wp_verify_nonce( $query['preview_nonce'], 'post_preview_' . $popup_id ) );
	}

	/**
	 * Core editor previews retain their load and debug-trigger behavior.
	 *
	 * @return void
	 */
	public function test_core_editor_preview_behavior_is_preserved() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'popup_preview' => wp_create_nonce( 'popup-preview' ),
			'popup'         => (string) $popup_id,
		];

		$settings = apply_filters( 'pum_popup_get_public_settings', [], pum_get_popup( $popup_id ) );
		$toolbar  = \PopupMaker\plugin()->get_controller( 'Admin\\Toolbar' );

		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		}

		$admin_bar = new WP_Admin_Bar();

		$toolbar->add_preview_edit_link( $admin_bar );
		$edit_node = $admin_bar->get_node( 'edit' );

		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', false, $popup_id ) );
		$this->assertSame( 'admin_debug', $settings['triggers'][0]['type'] );
		$this->assertInstanceOf( stdClass::class, $edit_node );
		$this->assertSame( get_edit_post_link( $popup_id, 'raw' ), $edit_node->href );
		$this->assertSame( get_post_type_object( 'popup' )->labels->edit_item, $edit_node->title );

		$_GET['popup_preview'] = 'tampered';
		$admin_bar             = new WP_Admin_Bar();

		$toolbar->add_preview_edit_link( $admin_bar );

		$this->assertNull( $admin_bar->get_node( 'edit' ) );
	}

	/**
	 * An unknown popup slug fails safely.
	 *
	 * @return void
	 */
	public function test_unknown_popup_preview_slug_returns_false() {
		$_GET = [
			'popup_preview' => wp_create_nonce( 'popup-preview' ),
			'popup'         => 'missing-popup',
		];

		$previews = \PopupMaker\plugin()->get_controller( 'Previews' );

		$this->assertFalse( $previews->get_popup_preview() );
	}
}
