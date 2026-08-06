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

		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', false, $popup_id ) );
		$this->assertSame( 'admin_debug', $settings['triggers'][0]['type'] );
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

	/**
	 * Builder previews do not register the popup's live triggers.
	 *
	 * @return void
	 */
	public function test_builder_preview_strips_live_triggers() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$triggers  = [ [ 'type' => 'auto_open' ] ];
		$data_attr = apply_filters( 'pum_popup_data_attr', [ 'triggers' => $triggers ], $popup_id );
		$settings  = apply_filters(
			'pum_popup_get_public_settings',
			[ 'triggers' => $triggers ],
			pum_get_popup( $popup_id )
		);

		$this->assertSame( [], $data_attr['triggers'] );
		$this->assertSame( [], $settings['triggers'] );
	}
}
