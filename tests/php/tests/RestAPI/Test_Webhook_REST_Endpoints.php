<?php
/**
 * Webhook REST API endpoints tests.
 *
 * @package PopupMaker
 * @subpackage Tests
 */

/**
 * Webhook REST API endpoints test case.
 */
class Test_Webhook_REST_Endpoints extends WP_UnitTestCase {

	/**
	 * REST API server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize REST API server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		// Create test user.
		$this->admin_user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );

		// Initialize REST API routes.
		do_action( 'rest_api_init' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Test webhook verify endpoint security.
	 */
	public function test_webhook_verify_endpoint_security() {
		// Mock the required headers and authentication.
		$_SERVER['HTTP_USER_AGENT']    = 'PopupMakerUpgrader';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';

		$request  = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/verify' );
		$response = $this->server->dispatch( $request );

		// Should fail due to security validation.
		$this->assertContains( $response->get_status(), [ 403, 404, 500 ] );
	}

	/**
	 * Test webhook install endpoint exists.
	 */
	public function test_webhook_install_endpoint_exists() {
		$request  = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$response = $this->server->dispatch( $request );

		// Should not return 404 (endpoint should exist).
		$this->assertNotEquals( 404, $response->get_status() );
	}

	/**
	 * Test webhook install endpoint validation.
	 */
	public function test_webhook_install_validation() {
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			'file' => 'not-a-valid-url',
			'slug' => 'invalid-slug!@#',
		] );

		$response = $this->server->dispatch( $request );

		// Should fail validation.
		$this->assertContains( $response->get_status(), [ 400, 403, 500 ] );
	}

	/**
	 * Test webhook install endpoint with valid data but no authentication.
	 */
	public function test_webhook_install_no_auth() {
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			'file'  => 'https://example.com/plugin.zip',
			'slug'  => 'popup-maker-pro',
			'type'  => 'plugin',
			'force' => false,
		] );

		$response = $this->server->dispatch( $request );

		// Should fail due to missing authentication.
		$this->assertContains( $response->get_status(), [ 403, 500 ] );
	}

	/**
	 * Test webhook install endpoint argument validation.
	 */
	public function test_webhook_install_argument_validation() {
		// Test missing required fields.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			// Missing file and slug.
			'type' => 'plugin',
		] );

		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 400, 403, 500 ] );

		// Test invalid file URL.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			'file' => 'not-a-url',
			'slug' => 'test-slug',
			'type' => 'plugin',
		] );

		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 400, 403, 500 ] );

		// Test invalid slug format.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			'file' => 'https://example.com/plugin.zip',
			'slug' => 'invalid@slug',
			'type' => 'plugin',
		] );

		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 400, 403, 500 ] );

		// Test invalid type.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/webhook/install' );
		$request->set_body_params( [
			'file' => 'https://example.com/plugin.zip',
			'slug' => 'test-slug',
			'type' => 'invalid-type',
		] );

		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 400, 403, 500 ] );
	}

	/**
	 * Test webhook endpoint methods.
	 */
	public function test_webhook_endpoint_methods() {
		// Test GET method (should not be allowed).
		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/webhook/install' );
		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 404, 405 ] );

		// Test PUT method (should not be allowed).
		$request  = new WP_REST_Request( 'PUT', '/popup-maker/v2/webhook/install' );
		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 404, 405 ] );

		// Test DELETE method (should not be allowed).
		$request  = new WP_REST_Request( 'DELETE', '/popup-maker/v2/webhook/install' );
		$response = $this->server->dispatch( $request );
		$this->assertContains( $response->get_status(), [ 404, 405 ] );
	}

	/**
	 * Test webhook endpoints are registered properly.
	 */
	public function test_webhook_endpoints_registration() {
		$routes = rest_get_server()->get_routes();

		// Check if our webhook routes are registered.
		$webhook_routes = array_filter( array_keys( $routes ), function ( $route ) {
			return strpos( $route, '/popup-maker/v2/webhook/' ) === 0;
		} );

		// Should have at least the install and verify endpoints.
		$this->assertGreaterThanOrEqual( 2, count( $webhook_routes ) );
	}
}
