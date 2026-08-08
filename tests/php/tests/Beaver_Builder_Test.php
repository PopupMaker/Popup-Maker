<?php
/**
 * Beaver Builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\BeaverBuilder;

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
}
