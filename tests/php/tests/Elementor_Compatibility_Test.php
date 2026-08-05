<?php
/**
 * Elementor builder compatibility tests.
 *
 * @package Popup_Maker
 */

/**
 * Test Elementor popup preview requests.
 */
class Elementor_Compatibility_Test extends WP_UnitTestCase {

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
	 * An authorized Elementor preview can query its popup.
	 *
	 * @return void
	 */
	public function test_authorized_elementor_preview_restores_popup_post_type() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertSame( $popup_id, $query_vars['p'] );
		$this->assertSame( 'popup', $query_vars['post_type'] );
		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', false, $popup_id ) );
		$this->assertFalse( apply_filters( 'pum_popup_is_loadable', true, $popup_id + 1 ) );
	}

	/**
	 * An authorized preview uses the shared popup builder canvas.
	 *
	 * @return void
	 */
	public function test_authorized_elementor_preview_uses_builder_canvas_template() {
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

		$original_query  = $GLOBALS['wp_query'];
		$popups          = \PopupMaker\plugin()->get_controller( 'Frontend\Popups' );
		$footer_priority = has_action( 'wp_footer', [ $popups, 'render_popups' ] );

		$GLOBALS['wp_query'] = new WP_Query(
			[
				'p'           => $popup_id,
				'post_type'   => 'popup',
				'post_status' => 'any',
			]
		);

		try {
			$this->assertTrue( apply_filters( 'popup_maker/is_builder_preview', false ) );
			$this->assertSame(
				Popup_Maker::$DIR . 'templates/single-popup.php',
				apply_filters( 'template_include', 'theme-single.php' )
			);
			$this->assertFalse( has_action( 'wp_footer', [ $popups, 'render_popups' ] ) );
		} finally {
			$GLOBALS['wp_query'] = $original_query;

			if ( false !== $footer_priority && false === has_action( 'wp_footer', [ $popups, 'render_popups' ] ) ) {
				add_action( 'wp_footer', [ $popups, 'render_popups' ], $footer_priority );
			}
		}
	}

	/**
	 * Elementor's standalone preview uses the authenticated popup canvas.
	 *
	 * @return void
	 */
	public function test_elementor_wp_preview_url_uses_popup_canvas() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $admin_id );

		$document = new class( $popup_id ) {
			/**
			 * Popup ID.
			 *
			 * @var int
			 */
			private $popup_id;

			/**
			 * Constructor.
			 *
			 * @param int $popup_id Popup ID.
			 */
			public function __construct( $popup_id ) {
				$this->popup_id = $popup_id;
			}

			/**
			 * Get the document ID.
			 *
			 * @return int Popup ID.
			 */
			public function get_main_id() {
				return $this->popup_id;
			}
		};

		$preview_url = apply_filters( 'elementor/document/urls/wp_preview', '', $document );
		$query       = [];
		parse_str( (string) wp_parse_url( $preview_url, PHP_URL_QUERY ), $query );

		$this->assertSame( 'popup', $query['post_type'] );
		$this->assertSame( (string) $popup_id, $query['p'] );
		$this->assertSame( 'elementor', $query['pum-builder-preview'] );
		$this->assertNotFalse( wp_verify_nonce( $query['_wpnonce'], 'pum_builder_preview_elementor_' . $popup_id ) );
	}

	/**
	 * A valid standalone Elementor preview can query its popup.
	 *
	 * @return void
	 */
	public function test_authorized_standalone_elementor_preview_restores_popup_post_type() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'pum-builder-preview' => 'elementor',
			'p'                   => (string) $popup_id,
			'_wpnonce'            => wp_create_nonce( 'pum_builder_preview_elementor_' . $popup_id ),
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertSame( $popup_id, $query_vars['p'] );
		$this->assertSame( 'popup', $query_vars['post_type'] );
	}

	/**
	 * A mismatched preview ID must not expose a popup query.
	 *
	 * @return void
	 */
	public function test_mismatched_elementor_preview_does_not_restore_popup_post_type() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $admin_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) ( $popup_id + 1 ),
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) ( $popup_id + 1 ) ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
	}

	/**
	 * Logged-out preview requests must remain non-queryable.
	 *
	 * @return void
	 */
	public function test_logged_out_elementor_preview_does_not_restore_popup_post_type() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', true, $popup_id ) );
	}

	/**
	 * A logged-in user without popup permissions must remain blocked.
	 *
	 * @return void
	 */
	public function test_user_without_popup_permission_does_not_restore_popup_post_type() {
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		$popup_id      = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		wp_set_current_user( $subscriber_id );
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];

		$query_vars = apply_filters( 'request', [ 'p' => (string) $popup_id ] );

		$this->assertArrayNotHasKey( 'post_type', $query_vars );
		$this->assertTrue( apply_filters( 'pum_popup_is_loadable', true, $popup_id ) );
	}
}
