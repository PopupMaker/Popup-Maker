<?php
/**
 * WordPress.org distribution REST route tests.
 *
 * @package PopupMaker
 * @subpackage Tests
 */

/**
 * Confirms that Core does not expose private plugin delivery routes.
 */
class Test_Distribution_REST_Routes extends WP_UnitTestCase {

	/**
	 * REST API server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Set up the REST server.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
	}

	/**
	 * Reset the REST server.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Private download, installation, and activation routes stay absent.
	 */
	public function test_private_plugin_delivery_routes_are_not_registered() {
		$routes         = $this->server->get_routes();
		$removed_routes = [
			'/popup-maker/v1/license/validate',
			'/popup-maker/v1/connect/verify',
			'/popup-maker/v1/upgrade/install',
			'/popup-maker/v1/connect/info',
			'/popup-maker/v2/connect/install',
			'/popup-maker/v2/connect/verify',
			'/popup-maker/v2/license/activate-pro',
			'/popup-maker/v2/license/connect-info',
			'/popup-maker/v2/license/activate-plugin',
		];

		foreach ( $removed_routes as $route ) {
			$this->assertArrayNotHasKey( $route, $routes );
		}

		$this->assertArrayNotHasKey( '/popup-maker/v2/addons/install', $routes );
		$this->assertArrayNotHasKey( '/popup-maker/v2/addons/download', $routes );
	}

	/**
	 * Core exposes only its local catalog and installed-plugin lifecycle routes.
	 */
	public function test_core_addon_catalog_routes_are_registered_without_delivery() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/popup-maker/v2/addons', $routes );
		$this->assertArrayHasKey( '/popup-maker/v2/addons/activate', $routes );
		$this->assertArrayHasKey( '/popup-maker/v2/addons/deactivate', $routes );
	}

	/**
	 * The public catalog response contains no entitlement or delivery metadata.
	 */
	public function test_core_addon_catalog_contains_only_static_product_and_local_status_data() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/popup-maker/v2/addons' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $data );

		foreach ( $data as $item ) {
			$this->assertArrayNotHasKey( 'package', $item );
			$this->assertArrayNotHasKey( 'downloadUrl', $item );
			$this->assertArrayNotHasKey( 'license', $item );
			$this->assertArrayNotHasKey( 'hasPackage', $item );
		}
	}

	/**
	 * Core alone exposes an external download link instead of a REST installer.
	 */
	public function test_core_alone_uses_external_pro_download() {
		if ( \PopupMaker\plugin()->is_pro_installed() ) {
			$this->markTestSkipped( 'Core-only distribution assertion requires Pro to be absent.' );
		}

		$routes = $this->server->get_routes();
		$hero   = PUM_Admin_Settings::field_go_pro_hero();

		$this->assertArrayNotHasKey( '/popup-maker/v2/license', $routes );
		$this->assertArrayNotHasKey( '/popup-maker/v2/license/activate', $routes );
		$this->assertArrayNotHasKey( '/popup-maker/v2/license/deactivate', $routes );
		$this->assertStringContainsString( 'https://wppopupmaker.com/account/file-downloads/', $hero );
		$this->assertStringContainsString( 'Already own Pro? Download it', $hero );
		$this->assertStringNotContainsString( '/license/activate-pro', $hero );
		$this->assertStringNotContainsString( '/connect/', $hero );
	}
}
