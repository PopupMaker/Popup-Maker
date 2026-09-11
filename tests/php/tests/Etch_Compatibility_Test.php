<?php
/**
 * Etch compatibility through WordPress-native blocks.
 *
 * @package Popup_Maker
 */

/**
 * Test the generic contracts used by Etch-authored popup documents.
 */
class Etch_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Test block name.
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'etch/popup-maker-test';

	/**
	 * Register an Etch-shaped dynamic block for each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		wp_register_style( 'etch-popup-maker-test', false, [], 'test' );
		wp_register_script( 'etch-popup-maker-test', false, [], 'test', true );

		register_block_type(
			self::BLOCK_NAME,
			[
				'attributes'      => [
					'label' => [
						'type'    => 'string',
						'default' => '',
					],
				],
				'style'           => 'etch-popup-maker-test',
				'script'          => 'etch-popup-maker-test',
				'render_callback' => static function ( $attributes ) {
					$label = isset( $attributes['label'] ) && is_string( $attributes['label'] )
						? $attributes['label']
						: '';

					return '<div data-etch-test="document">' . esc_html( $label ) . '</div>';
				},
			]
		);
	}

	/**
	 * Remove the test block and its assets.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unregister_block_type( self::BLOCK_NAME );

		wp_dequeue_style( 'etch-popup-maker-test' );
		wp_deregister_style( 'etch-popup-maker-test' );
		wp_dequeue_script( 'etch-popup-maker-test' );
		wp_deregister_script( 'etch-popup-maker-test' );

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Popup Maker renders Etch-authored custom blocks through its native pipeline.
	 *
	 * @return void
	 */
	public function test_etch_authored_block_renders_in_popup_content() {
		$popup_id = $this->create_popup( 'Popup document' );
		$content  = pum_get_popup( $popup_id )->get_content();

		$this->assertSame(
			'<div data-etch-test="document">Popup document</div>',
			$content
		);
		$this->assertTrue( wp_style_is( 'etch-popup-maker-test', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'etch-popup-maker-test', 'enqueued' ) );
	}

	/**
	 * A normal page document and a popup document render independently.
	 *
	 * @return void
	 */
	public function test_page_and_popup_etch_documents_coexist() {
		$page_content = $this->block_markup( 'Main page document' );
		$popup_id     = $this->create_popup( 'Popup document' );

		$page_output  = do_blocks( $page_content );
		$popup_output = pum_get_popup( $popup_id )->get_content();

		$this->assertStringContainsString( 'Main page document', $page_output );
		$this->assertStringNotContainsString( 'Popup document', $page_output );
		$this->assertStringContainsString( 'Popup document', $popup_output );
		$this->assertStringNotContainsString( 'Main page document', $popup_output );
	}

	/**
	 * Preloading renders blocks early enough to discover their frontend assets.
	 *
	 * @return void
	 */
	public function test_popup_preload_discovers_etch_block_assets_before_render() {
		$popup_id = $this->create_popup( 'Preloaded document' );
		$popup    = pum_get_popup( $popup_id );
		$popups   = PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );

		$this->assertInstanceOf( PopupMaker\Controllers\Frontend\Popups::class, $popups );
		$this->assertFalse( wp_style_is( 'etch-popup-maker-test', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'etch-popup-maker-test', 'enqueued' ) );

		$popups->preload_popup( $popup );

		$this->assertTrue( wp_style_is( 'etch-popup-maker-test', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'etch-popup-maker-test', 'enqueued' ) );
		$this->assertStringContainsString(
			'Preloaded document',
			$popups->get_content_cache( $popup_id )
		);
	}

	/**
	 * Etch can read and update popup block content through core REST semantics.
	 *
	 * @return void
	 */
	public function test_standard_rest_route_reads_and_updates_etch_block_content() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->create_popup( 'Initial document', 'draft' );
		$updated  = $this->block_markup( 'Updated through REST' );

		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/popups/' . $popup_id );
		$request->set_param( 'content', $updated );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $updated, get_post_field( 'post_content', $popup_id ) );
		$this->assertSame( $updated, $response->get_data()['content']['raw'] );
	}

	/**
	 * Private popup documents remain unavailable to anonymous REST requests.
	 *
	 * @return void
	 */
	public function test_standard_rest_route_keeps_draft_popup_private() {
		$popup_id = $this->create_popup( 'Private document', 'draft' );

		wp_set_current_user( 0 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id ) );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Core revision and autosave endpoints exist for Etch's editing lifecycle.
	 *
	 * @return void
	 */
	public function test_standard_rest_route_exposes_revision_and_autosave_controllers() {
		global $wp_rest_server;

		$wp_rest_server = null;
		$routes         = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wp/v2/popups/(?P<parent>[\\d]+)/revisions', $routes );
		$this->assertArrayHasKey( '/wp/v2/popups/(?P<id>[\\d]+)/autosaves', $routes );
	}

	/**
	 * Child routes follow Popup Maker's alias when another integration changes the native base.
	 *
	 * @return void
	 */
	public function test_standard_rest_child_routes_follow_the_alias_base() {
		global $wp_rest_server;

		$post_type = get_post_type_object( 'popup' );

		if ( ! $post_type ) {
			$this->fail( 'Popup post type was not registered.' );
		}

		$original_rest_base   = $post_type->rest_base;
		$post_type->rest_base = 'third-party-popups';
		$wp_rest_server       = null;

		try {
			$routes = rest_get_server()->get_routes();
		} finally {
			$post_type->rest_base = $original_rest_base;
			$wp_rest_server       = null;
		}

		$this->assertArrayHasKey( '/wp/v2/popups/(?P<parent>[\\d]+)/revisions', $routes );
		$this->assertArrayHasKey( '/wp/v2/popups/(?P<id>[\\d]+)/autosaves', $routes );
	}

	/**
	 * Create a popup containing a serialized test block.
	 *
	 * @param string $label  Block label.
	 * @param string $status Post status.
	 *
	 * @return int
	 */
	private function create_popup( $label, $status = 'publish' ) {
		return $this->factory->post->create(
			[
				'post_type'    => 'popup',
				'post_status'  => $status,
				'post_title'   => $label,
				'post_content' => $this->block_markup( $label ),
			]
		);
	}

	/**
	 * Serialize an Etch-shaped custom block.
	 *
	 * @param string $label Block label.
	 *
	 * @return string
	 */
	private function block_markup( $label ) {
		return serialize_block(
			[
				'blockName'    => self::BLOCK_NAME,
				'attrs'        => [ 'label' => $label ],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			]
		);
	}
}
