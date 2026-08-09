<?php
/**
 * Beaver Builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\BeaverBuilder;

if ( ! class_exists( 'FLBuilderPopupMaker' ) ) {
	require_once __DIR__ . '/fixtures/class-fl-builder-popup-maker.php';
}

/**
 * Verify the small compatibility layer around Beaver's native integration.
 */
class Beaver_Builder_Test extends WP_UnitTestCase {

	/** @var array<string,mixed> */
	private $original_get;

	/** @return void */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
	}

	/** @return void */
	public function tearDown(): void {
		$_GET = $this->original_get;
		wp_set_current_user( 0 );
		remove_action( 'wp', 'FLBuilderPopupMaker::redirect_to_admin_edit' );

		parent::tearDown();
	}

	/** @return void */
	public function test_native_editor_request_identifies_shell_and_canvas() {
		$builder = new BeaverBuilder( \PopupMaker\plugin() );

		$_GET = [
			'fl_builder'    => '',
			'fl_builder_ui' => '',
			'post_type'     => 'popup',
			'p'             => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertFalse( $builder->is_canvas_request() );

		unset( $_GET['fl_builder_ui'] );
		$_GET['fl_builder_ui_iframe'] = '';

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertTrue( $builder->is_canvas_request() );

		unset( $_GET['fl_builder'] );

		$this->assertSame( 0, $builder->get_requested_popup_id() );
	}

	/** @return void */
	public function test_signed_popup_preview_bypasses_native_redirect() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$_GET = [
			'popup_preview' => wp_create_nonce( 'popup-preview' ),
			'popup'         => (string) $popup_id,
		];

		add_action( 'wp', 'FLBuilderPopupMaker::redirect_to_admin_edit' );

		$builder = new BeaverBuilder( \PopupMaker\plugin() );
		$builder->preserve_builder_request();

		$this->assertFalse( has_action( 'wp', 'FLBuilderPopupMaker::redirect_to_admin_edit' ) );
	}
}
