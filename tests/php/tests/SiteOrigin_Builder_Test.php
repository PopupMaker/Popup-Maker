<?php
/**
 * SiteOrigin Page Builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\SiteOrigin;

/**
 * Verify SiteOrigin's small editor-routing compatibility layer.
 */
class SiteOrigin_Builder_Test extends WP_UnitTestCase {

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
		wp_dequeue_script( 'so-panels-admin' );
		wp_deregister_script( 'so-panels-admin' );

		parent::tearDown();
	}

	/** @return void */
	public function test_live_editor_request_identifies_popup_canvas() {
		$builder = new SiteOrigin( \PopupMaker\plugin() );

		$_GET = [
			'siteorigin_panels_live_editor' => 'true',
			'p'                             => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertTrue( $builder->is_canvas_request() );

		unset( $_GET['siteorigin_panels_live_editor'] );

		$this->assertSame( 0, $builder->get_requested_popup_id() );
	}

	/** @return void */
	public function test_popup_support_is_runtime_only() {
		$builder  = new SiteOrigin( \PopupMaker\plugin() );
		$settings = [ 'post-types' => [ 'post', 'page' ] ];

		$this->assertSame(
			[ 'post-types' => [ 'post', 'page', 'popup' ] ],
			$builder->add_popup_post_type( $settings )
		);
		$this->assertSame( $settings, $builder->strip_injected_post_type( $builder->add_popup_post_type( $settings ), $settings ) );
	}

	/** @return void */
	public function test_live_editor_uses_authorized_popup_url() {
		global $post;

		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$user_id  = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$builder  = new SiteOrigin( \PopupMaker\plugin() );

		wp_set_current_user( $user_id );
		$post = get_post( $popup_id );

		wp_register_script( 'so-panels-admin', '/siteorigin-admin.js', [], 'test', true );
		$builder->override_preview_url();

		$before = wp_scripts()->get_data( 'so-panels-admin', 'before' );

		$this->assertIsArray( $before );
		$this->assertStringContainsString( 'siteorigin_panels_live_editor', implode( "\n", $before ) );
		$this->assertStringContainsString( 'post_type=popup', implode( "\n", $before ) );
		$this->assertStringContainsString( 'p=' . $popup_id, implode( "\n", $before ) );
	}
}
