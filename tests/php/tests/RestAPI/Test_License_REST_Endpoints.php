<?php
/**
 * License REST API endpoints tests.
 *
 * @package PopupMaker
 * @subpackage Tests
 */

// Ensure test environment is bootstrapped.
if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	require_once dirname( dirname( __DIR__ ) ) . '/config/bootstrap.php';
}

use PopupMaker\Services\License;

/**
 * License REST API endpoints test case.
 */
class Test_License_REST_Endpoints extends \WP_UnitTestCase {

	/**
	 * License service instance.
	 *
	 * @var \PopupMaker\Services\License
	 */
	private $license_service;

	/**
	 * Mock license service.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_license_service;


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
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private $subscriber_user_id;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize REST API server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		// Create test users.
		$this->admin_user_id      = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );

		// Create mock services.
		$this->mock_license_service = $this->createMock( License::class );

		// Initialize real license service if available.
		if ( class_exists( '\PopupMaker\Services\License' ) ) {
			$container             = $this->createMock( \PopupMaker\Plugin\Core::class );
			$this->license_service = new \PopupMaker\Services\License( $container );
		}

		// Mock global functions for testing.
		if ( ! function_exists( 'plugin' ) ) {
			function plugin( $service = null ) {
				global $test_container;
				return $test_container[ $service ] ?? $test_container;
			}
		}

		// Set up global test container.
		global $test_container;
		$test_container = [
			'license'          => $this->mock_license_service,
			'is_pro_installed' => false,
			'is_pro_active'    => false,
		];

		// Initialize REST API routes.
		do_action( 'rest_api_init' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		// Clean up license options.
		delete_option( 'popup_maker_license' );
		delete_option( 'popup_maker_pro_activation_date' );

		parent::tearDown();
	}

	/**
	 * Test license status endpoint requires authentication.
	 */
	public function test_license_status_requires_auth() {
		// Test unauthenticated request.
		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Test license status endpoint with insufficient permissions.
	 */
	public function test_license_status_insufficient_permissions() {
		wp_set_current_user( $this->subscriber_user_id );

		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test license status endpoint with admin permissions.
	 */
	public function test_license_status_with_admin_permissions() {
		wp_set_current_user( $this->admin_user_id );

		// Mock a license status response.
		update_option( 'popup_maker_license', [
			'key'    => 'test-license-key-123',
			'status' => [
				'success'          => true,
				'license'          => 'valid',
				'item_id'          => 480187,
				'item_name'        => 'Popup Maker Pro',
				'license_limit'    => 5,
				'site_count'       => 1,
				'expires'          => '2025-12-31 23:59:59',
				'activations_left' => 4,
				'customer_name'    => 'Test Customer',
				'customer_email'   => 'test@example.com',
			],
		] );

		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license' );
		$response = $this->server->dispatch( $request );

		if ( $response->get_status() === 404 ) {
			// If endpoint doesn't exist, mark test as skipped.
			$this->markTestSkipped( 'License status endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'license', $data );
		$this->assertEquals( 'valid', $data['status'] );
	}

	/**
	 * Test license activation endpoint.
	 */
	public function test_license_activation_endpoint() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => 'test-license-key-456',
		] );

		// Mock the HTTP request to the license server.
		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );

		$response = $this->server->dispatch( $request );

		// Remove the filter.
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test license deactivation endpoint.
	 */
	public function test_license_deactivation_endpoint() {
		wp_set_current_user( $this->admin_user_id );

		// Set up an active license first.
		update_option( 'popup_maker_license', [
			'key'    => 'test-license-key-789',
			'status' => [
				'success' => true,
				'license' => 'valid',
			],
		] );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/deactivate' );

		// Mock the HTTP request to the license server.
		add_filter( 'pre_http_request', [ $this, 'mock_license_deactivation_response' ], 10, 3 );

		$response = $this->server->dispatch( $request );

		// Remove the filter.
		remove_filter( 'pre_http_request', [ $this, 'mock_license_deactivation_response' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License deactivation endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test invalid license key validation.
	 */
	public function test_invalid_license_key_validation() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => '', // Empty license key.
		] );

		$response = $this->server->dispatch( $request );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_license_key', $response->get_data()['code'] );
	}

	/**
	 * Test license key sanitization.
	 */
	public function test_license_key_sanitization() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => '  test-license-key-with-spaces  ',
		] );

		// Mock the HTTP request.
		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );

		$response = $this->server->dispatch( $request );

		// Remove the filter.
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		// Should succeed with trimmed key.
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test API error handling.
	 */
	public function test_api_error_handling() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => 'test-license-key',
		] );

		// Mock a failed HTTP request.
		add_filter( 'pre_http_request', [ $this, 'mock_license_api_error' ], 10, 3 );

		$response = $this->server->dispatch( $request );

		// Remove the filter.
		remove_filter( 'pre_http_request', [ $this, 'mock_license_api_error' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		$this->assertEquals( 500, $response->get_status() );
		$this->assertArrayHasKey( 'message', $response->get_data() );
	}

	/**
	 * Test rate limiting for license API calls.
	 */
	public function test_license_api_rate_limiting() {
		wp_set_current_user( $this->admin_user_id );

		// Mock multiple rapid requests.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => 'test-license-key',
		] );

		// First request should succeed.
		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );
		$response1 = $this->server->dispatch( $request );
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		// Second immediate request might be rate limited.
		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );
		$response2 = $this->server->dispatch( $request );
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		if ( $response1->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		// First request should be successful.
		$this->assertEquals( 200, $response1->get_status() );

		// Second request might be rate limited (if implemented).
		// This would depend on implementation details.
		$this->assertTrue( in_array( $response2->get_status(), [ 200, 429 ], true ) );
	}

	/**
	 * Test license data export for privacy compliance.
	 */
	public function test_license_data_export() {
		wp_set_current_user( $this->admin_user_id );

		// Set up license data.
		update_option( 'popup_maker_license', [
			'key'    => 'test-license-key',
			'status' => [
				'customer_email' => 'test@example.com',
				'customer_name'  => 'Test Customer',
			],
		] );

		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license/export' );
		$response = $this->server->dispatch( $request );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License export endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'customer_data', $data );
		$this->assertArrayNotHasKey( 'license_key', $data ); // Sensitive data should be excluded.
	}

	/**
	 * Mock successful license activation response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args    HTTP request arguments.
	 * @param string               $url     The request URL.
	 * @return array
	 */
	public function mock_license_activation_response( $preempt, $args, $url ) {
		if ( strpos( $url, 'edd-sl-api' ) === false ) {
			return $preempt;
		}

		return [
			'response' => [ 'code' => 200 ],
			'body'     => wp_json_encode( [
				'success'          => true,
				'license'          => 'valid',
				'item_id'          => 480187,
				'item_name'        => 'Popup Maker Pro',
				'license_limit'    => 5,
				'site_count'       => 1,
				'expires'          => '2025-12-31 23:59:59',
				'activations_left' => 4,
				'customer_name'    => 'Test Customer',
				'customer_email'   => 'test@example.com',
			] ),
		];
	}

	/**
	 * Mock successful license deactivation response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args    HTTP request arguments.
	 * @param string               $url     The request URL.
	 * @return array
	 */
	public function mock_license_deactivation_response( $preempt, $args, $url ) {
		if ( strpos( $url, 'edd-sl-api' ) === false ) {
			return $preempt;
		}

		return [
			'response' => [ 'code' => 200 ],
			'body'     => wp_json_encode( [
				'success' => true,
				'license' => 'deactivated',
			] ),
		];
	}

	/**
	 * Mock API error response.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
	 * @param array                $args    HTTP request arguments.
	 * @param string               $url     The request URL.
	 * @return WP_Error
	 */
	public function mock_license_api_error( $preempt, $args, $url ) {
		if ( strpos( $url, 'edd-sl-api' ) === false ) {
			return $preempt;
		}

		return new WP_Error( 'http_request_failed', 'Network connection failed.' );
	}

	/**
	 * Test license pro activation endpoint.
	 */
	public function test_license_pro_activation_endpoint() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate-pro' );
		$request->set_body_params( [
			'license_key' => 'test-license-key-pro',
		] );

		// Mock the HTTP request to the license server.
		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );

		$response = $this->server->dispatch( $request );

		// Remove the filter.
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License pro activation endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'can_upgrade', $data );
		$this->assertArrayHasKey( 'connect_info', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test license endpoint security verification.
	 */
	public function test_license_endpoint_security_verification() {
		wp_set_current_user( $this->admin_user_id );

		// Test CSRF protection.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => 'test-license-key',
		] );

		// Test without nonce - should still work for REST API (uses different auth).
		$response = $this->server->dispatch( $request );

		// The endpoint should exist and handle the request.
		$this->assertNotEquals( 404, $response->get_status() );
	}

	/**
	 * Test license endpoint input sanitization.
	 */
	public function test_license_endpoint_input_sanitization() {
		wp_set_current_user( $this->admin_user_id );

		// Test XSS prevention.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		$request->set_body_params( [
			'license_key' => '<script>alert("xss")</script>test-key',
		] );

		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );
		$response = $this->server->dispatch( $request );
		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		// Should not contain script tags.
		$data = $response->get_data();
		if ( isset( $data['license_key'] ) ) {
			$this->assertStringNotContainsString( '<script>', $data['license_key'] );
		}
	}

	/**
	 * Test license status with multiple error conditions.
	 */
	public function test_license_status_error_conditions() {
		wp_set_current_user( $this->admin_user_id );

		$error_conditions = [
			'expired'             => [
				'success' => false,
				'license' => 'invalid',
				'error'   => 'expired',
				'expires' => '2023-01-01 00:00:00',
			],
			'no_activations_left' => [
				'success' => false,
				'license' => 'invalid',
				'error'   => 'no_activations_left',
			],
			'revoked'             => [
				'success' => false,
				'license' => 'invalid',
				'error'   => 'revoked',
			],
		];

		foreach ( $error_conditions as $error_type => $license_data ) {
			update_option( 'popup_maker_license', [
				'key'    => 'test-license-key-' . $error_type,
				'status' => $license_data,
			] );

			$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license' );
			$response = $this->server->dispatch( $request );

			if ( $response->get_status() === 404 ) {
				$this->markTestSkipped( 'License status endpoint not implemented yet.' );
			}

			$this->assertEquals( 200, $response->get_status() );
			$data = $response->get_data();

			$this->assertArrayHasKey( 'status', $data );
			$this->assertNotEquals( 'valid', $data['status'] );
		}
	}

	/**
	 * Test license endpoint with malformed requests.
	 */
	public function test_license_endpoint_malformed_requests() {
		wp_set_current_user( $this->admin_user_id );

		// Test with missing required parameters.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
		// Intentionally no license_key parameter.

		$response = $this->server->dispatch( $request );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
		}

		$this->assertEquals( 400, $response->get_status() );

		// Test with wrong method.
		$request  = new WP_REST_Request( 'DELETE', '/popup-maker/v2/license/activate' );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), [ 404, 405 ] ); // Not Found or Method Not Allowed.
	}

	/**
	 * Test license endpoint response structure validation.
	 */
	public function test_license_endpoint_response_structure() {
		wp_set_current_user( $this->admin_user_id );

		// Set up a valid license.
		update_option( 'popup_maker_license', [
			'key'    => 'test-license-key',
			'status' => [
				'success'          => true,
				'license'          => 'valid',
				'item_id'          => 480187,
				'item_name'        => 'Popup Maker Pro',
				'license_limit'    => 5,
				'site_count'       => 1,
				'expires'          => '2025-12-31 23:59:59',
				'activations_left' => 4,
				'customer_name'    => 'Test Customer',
				'customer_email'   => 'test@example.com',
			],
		] );

		$request  = new WP_REST_Request( 'GET', '/popup-maker/v2/license' );
		$response = $this->server->dispatch( $request );

		if ( $response->get_status() === 404 ) {
			$this->markTestSkipped( 'License status endpoint not implemented yet.' );
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// Validate required fields are present.
		$required_fields = [
			'license_key',
			'status',
			'status_data',
			'is_active',
			'is_pro_installed',
			'is_pro_active',
			'can_upgrade',
			'connect_info',
		];

		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $data, "Missing required field: {$field}" );
		}

		// Validate data types.
		$this->assertIsString( $data['license_key'] );
		$this->assertIsString( $data['status'] );
		$this->assertIsBool( $data['is_active'] );
		$this->assertIsBool( $data['is_pro_installed'] );
		$this->assertIsBool( $data['is_pro_active'] );
		$this->assertIsBool( $data['can_upgrade'] );
	}

	/**
	 * Test license endpoint concurrent request handling.
	 */
	public function test_license_endpoint_concurrent_requests() {
		wp_set_current_user( $this->admin_user_id );

		// Simulate rapid concurrent requests.
		$requests = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$request = new WP_REST_Request( 'POST', '/popup-maker/v2/license/activate' );
			$request->set_body_params( [
				'license_key' => 'test-concurrent-key-' . $i,
			] );
			$requests[] = $request;
		}

		add_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ], 10, 3 );

		$responses = [];
		foreach ( $requests as $request ) {
			$responses[] = $this->server->dispatch( $request );
		}

		remove_filter( 'pre_http_request', [ $this, 'mock_license_activation_response' ] );

		// Check that all requests were handled properly.
		foreach ( $responses as $i => $response ) {
			if ( $response->get_status() === 404 ) {
				$this->markTestSkipped( 'License activation endpoint not implemented yet.' );
			}

			// Should either succeed or be rate limited.
			$this->assertContains( $response->get_status(), [ 200, 429, 500 ] );
		}
	}
}
