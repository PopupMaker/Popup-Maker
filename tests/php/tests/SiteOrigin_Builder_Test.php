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

		$filtered = $builder->add_popup_post_type( $settings );

		$this->assertSame( [ 'post-types' => [ 'post', 'page', 'popup' ] ], $filtered );
		$this->assertSame(
			$settings,
			$builder->strip_injected_post_type( $filtered, $filtered ),
			'An already-filtered old value must not persist the runtime injection.'
		);

		$owner_enabled = new SiteOrigin( \PopupMaker\plugin() );
		$stored        = [ 'post-types' => [ 'post', 'page', 'popup' ] ];

		$owner_enabled->add_popup_post_type( $stored );
		$this->assertSame( $stored, $owner_enabled->strip_injected_post_type( $stored, $stored ) );
	}

	/** @return void */
	public function test_existing_siteorigin_document_uses_classic_editor_without_overriding_blocks() {
		global $post;

		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = new SiteOrigin( \PopupMaker\plugin() );
		$post     = get_post( $popup_id );

		update_post_meta( $popup_id, 'panels_data', [ 'widgets' => [] ] );
		$this->assertFalse( $builder->use_classic_editor( true, 'popup' ) );

		wp_update_post( [
			'ID'           => $popup_id,
			'post_content' => '<!-- wp:paragraph --><p>Block content</p><!-- /wp:paragraph -->',
		] );
		$post = get_post( $popup_id );

		$this->assertTrue( $builder->use_classic_editor( true, 'popup' ) );

		$_GET['siteorigin-page-builder'] = '';
		$this->assertFalse( $builder->use_classic_editor( true, 'popup' ) );
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

		$script = implode( "\n", $before );
		$this->assertStringContainsString( 'siteorigin_panels_live_editor', $script );
		$this->assertStringContainsString( 'post_type=popup', $script );
		$this->assertStringContainsString( 'p=' . $popup_id, $script );
		$this->assertSame( 1, preg_match( '/setAttribute\( "data-preview-url", (.+?) \);/', $script, $matches ) );

		$url = json_decode( $matches[1], true );
		$this->assertIsString( $url );

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );
		$this->assertArrayHasKey( '_panelsnonce', $query_args );
		$this->assertSame( 1, wp_verify_nonce( $query_args['_panelsnonce'], 'live-editor-preview' ) );
	}
}
