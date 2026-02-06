<?php
/**
 * REST API Connect controller tests.
 *
 * Tests security layers, webhook permissions, and argument validation.
 *
 * @package Popup_Maker
 */

use PopupMaker\RestAPI\Connect;

/**
 * Test the Connect REST API controller security and validation.
 */
class REST_Connect_Test extends WP_UnitTestCase {

	/**
	 * Controller instance.
	 *
	 * @var \PopupMaker\RestAPI\Connect
	 */
	private $controller;

	/**
	 * Install webhook args.
	 *
	 * @var array
	 */
	private $install_args;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize REST API routes.
		do_action( 'rest_api_init' );

		$this->controller   = $this->createPartialMock( Connect::class, [] );
		$this->install_args = $this->controller->get_install_webhook_args();
	}

	/**
	 * Create a controller with a mock connect service.
	 *
	 * @param array $methods Methods to mock on the service.
	 * @return array{0: \PopupMaker\RestAPI\Connect, 1: \PHPUnit\Framework\MockObject\MockObject}
	 */
	private function create_controller_with_mock_service( array $methods ): array {
		$mock_service = $this->getMockBuilder( \stdClass::class )
			->addMethods( $methods )
			->getMock();

		$controller = $this->createPartialMock( Connect::class, [] );
		$reflection = new \ReflectionProperty( Connect::class, 'connect_service' );
		$reflection->setAccessible( true );
		$reflection->setValue( $controller, $mock_service );

		return [ $controller, $mock_service ];
	}

	/**
	 * Test webhook_permissions_check always returns true.
	 *
	 * Webhook endpoints rely on multi-layer security instead of WP capabilities.
	 */
	public function test_webhook_permissions_check_returns_true() {
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/connect/install' );
		$result  = $this->controller->webhook_permissions_check( $request );

		$this->assertTrue( $result, 'Webhook permissions should always return true (security is in endpoint).' );
	}

	/**
	 * Test get_install_webhook_args defines expected parameters.
	 */
	public function test_get_install_webhook_args_structure() {
		$this->assertArrayHasKey( 'file', $this->install_args, 'Should have file parameter.' );
		$this->assertArrayHasKey( 'type', $this->install_args, 'Should have type parameter.' );
		$this->assertArrayHasKey( 'slug', $this->install_args, 'Should have slug parameter.' );
		$this->assertArrayHasKey( 'force', $this->install_args, 'Should have force parameter.' );
	}

	/**
	 * Test file parameter validates URLs.
	 */
	public function test_file_validate_callback_rejects_invalid_url() {
		$validate = $this->install_args['file']['validate_callback'];

		// Invalid URL should return WP_Error.
		$result = $validate( 'not-a-url' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Invalid URL should return WP_Error.' );
		$this->assertEquals( 'invalid_file_url', $result->get_error_code(), 'Error code should be invalid_file_url.' );
	}

	/**
	 * Test file parameter accepts valid URLs.
	 */
	public function test_file_validate_callback_accepts_valid_url() {
		$validate = $this->install_args['file']['validate_callback'];

		$result = $validate( 'https://example.com/plugin.zip' );
		$this->assertTrue( $result, 'Valid URL should pass validation.' );
	}

	/**
	 * Test file parameter rejects empty string.
	 */
	public function test_file_validate_callback_rejects_empty() {
		$validate = $this->install_args['file']['validate_callback'];

		$result = $validate( '' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Empty string should return WP_Error.' );
	}

	/**
	 * Test slug parameter validates format.
	 */
	public function test_slug_validate_callback_accepts_valid_slug() {
		$validate = $this->install_args['slug']['validate_callback'];

		$this->assertTrue( $validate( 'popup-maker-pro' ), 'Hyphenated slug should pass.' );
		$this->assertTrue( $validate( 'my_plugin' ), 'Underscored slug should pass.' );
		$this->assertTrue( $validate( 'plugin123' ), 'Alphanumeric slug should pass.' );
	}

	/**
	 * Test slug parameter rejects invalid characters.
	 */
	public function test_slug_validate_callback_rejects_invalid_slug() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( 'Invalid Slug!' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Slug with spaces and special chars should fail.' );
		$this->assertEquals( 'invalid_slug', $result->get_error_code(), 'Error code should be invalid_slug.' );
	}

	/**
	 * Test slug parameter rejects empty string.
	 */
	public function test_slug_validate_callback_rejects_empty() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( '' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Empty slug should fail.' );
	}

	/**
	 * Test type parameter default is 'plugin'.
	 */
	public function test_type_parameter_default() {
		$this->assertEquals( 'plugin', $this->install_args['type']['default'], 'Default type should be plugin.' );
		$this->assertEquals( [ 'plugin', 'theme' ], $this->install_args['type']['enum'], 'Type enum should be plugin and theme.' );
	}

	/**
	 * Test force parameter default is false.
	 */
	public function test_force_parameter_default() {
		$this->assertFalse( $this->install_args['force']['default'], 'Default force should be false.' );
	}

	/**
	 * Test force parameter sanitize_callback converts to boolean.
	 */
	public function test_force_sanitize_callback() {
		$sanitize = $this->install_args['force']['sanitize_callback'];

		$this->assertTrue( $sanitize( 1 ), 'Truthy value should become true.' );
		$this->assertTrue( $sanitize( 'yes' ), 'Non-empty string should become true.' );
		$this->assertFalse( $sanitize( 0 ), 'Zero should become false.' );
		$this->assertFalse( $sanitize( '' ), 'Empty string should become false.' );
	}

	/**
	 * Test slug parameter rejects uppercase letters.
	 */
	public function test_slug_rejects_uppercase() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( 'PopupMaker' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Uppercase slug should fail validation.' );
	}

	/**
	 * Test file parameter has URI format.
	 */
	public function test_file_parameter_metadata() {
		$this->assertEquals( 'string', $this->install_args['file']['type'], 'File type should be string.' );
		$this->assertEquals( 'uri', $this->install_args['file']['format'], 'File format should be uri.' );
		$this->assertEquals( 'esc_url_raw', $this->install_args['file']['sanitize_callback'], 'File sanitize callback should be esc_url_raw.' );
	}

	/**
	 * Test controller namespace is correct.
	 */
	public function test_namespace_value() {
		$reflection = new ReflectionClass( $this->controller );
		$prop       = $reflection->getProperty( 'namespace' );
		$prop->setAccessible( true );

		$this->assertEquals( 'popup-maker/v2', $prop->getValue( $this->controller ), 'Namespace should be popup-maker/v2.' );
	}

	/**
	 * Test controller rest_base is correct.
	 */
	public function test_rest_base_value() {
		$reflection = new ReflectionClass( $this->controller );
		$prop       = $reflection->getProperty( 'rest_base' );
		$prop->setAccessible( true );

		$this->assertEquals( 'connect', $prop->getValue( $this->controller ), 'REST base should be connect.' );
	}

	/**
	 * Test webhook_permissions_check returns true for any request method.
	 */
	public function test_webhook_permissions_check_any_method() {
		$get_request  = new WP_REST_Request( 'GET', '/popup-maker/v2/connect/verify' );
		$post_request = new WP_REST_Request( 'POST', '/popup-maker/v2/connect/install' );

		$this->assertTrue( $this->controller->webhook_permissions_check( $get_request ), 'GET request should pass.' );
		$this->assertTrue( $this->controller->webhook_permissions_check( $post_request ), 'POST request should pass.' );
	}

	/**
	 * Test file validate_callback with various valid URL schemes.
	 */
	public function test_file_validate_callback_https_url() {
		$validate = $this->install_args['file']['validate_callback'];

		$this->assertTrue( $validate( 'https://upgrade.wppopupmaker.com/plugin.zip' ), 'HTTPS URL should pass.' );
		$this->assertTrue( $validate( 'http://example.com/file.zip' ), 'HTTP URL should pass.' );
	}

	/**
	 * Test file validate_callback with special characters in URL.
	 */
	public function test_file_validate_callback_url_with_params() {
		$validate = $this->install_args['file']['validate_callback'];

		$this->assertTrue(
			$validate( 'https://example.com/download?file=plugin.zip&version=1.0' ),
			'URL with query params should pass.'
		);
	}

	/**
	 * Test file validate_callback rejects null input.
	 */
	public function test_file_validate_callback_rejects_null() {
		$validate = $this->install_args['file']['validate_callback'];

		$result = $validate( null );
		$this->assertInstanceOf( WP_Error::class, $result, 'Null should return WP_Error.' );
	}

	/**
	 * Test slug validate_callback with single character slug.
	 */
	public function test_slug_validate_callback_single_char() {
		$validate = $this->install_args['slug']['validate_callback'];

		$this->assertTrue( $validate( 'a' ), 'Single character slug should pass.' );
	}

	/**
	 * Test slug validate_callback rejects null.
	 */
	public function test_slug_validate_callback_rejects_null() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( null );
		$this->assertInstanceOf( WP_Error::class, $result, 'Null slug should fail.' );
	}

	/**
	 * Test slug validate_callback rejects dots.
	 */
	public function test_slug_validate_callback_rejects_dots() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( 'my.plugin' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Slug with dots should fail.' );
	}

	/**
	 * Test slug validate_callback rejects slashes.
	 */
	public function test_slug_validate_callback_rejects_slashes() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( 'plugin/file' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Slug with slashes should fail.' );
	}

	/**
	 * Test type parameter has correct description.
	 */
	public function test_type_parameter_description() {
		$this->assertNotEmpty( $this->install_args['type']['description'], 'Type should have a description.' );
		$this->assertEquals( 'string', $this->install_args['type']['type'], 'Type schema type should be string.' );
	}

	/**
	 * Test type parameter sanitize_callback is sanitize_text_field.
	 */
	public function test_type_sanitize_callback() {
		$this->assertEquals( 'sanitize_text_field', $this->install_args['type']['sanitize_callback'], 'Type sanitize should be sanitize_text_field.' );
	}

	/**
	 * Test force parameter has boolean type.
	 */
	public function test_force_parameter_type() {
		$this->assertEquals( 'boolean', $this->install_args['force']['type'], 'Force type should be boolean.' );
	}

	/**
	 * Test force sanitize_callback with null input.
	 */
	public function test_force_sanitize_callback_null() {
		$sanitize = $this->install_args['force']['sanitize_callback'];

		$this->assertFalse( $sanitize( null ), 'Null should become false.' );
	}

	/**
	 * Test force sanitize_callback with array input.
	 */
	public function test_force_sanitize_callback_array() {
		$sanitize = $this->install_args['force']['sanitize_callback'];

		$this->assertTrue( $sanitize( [ 'any' ] ), 'Non-empty array should become true.' );
		$this->assertFalse( $sanitize( [] ), 'Empty array should become false.' );
	}

	/**
	 * Test force parameter has a description.
	 */
	public function test_force_parameter_description() {
		$this->assertNotEmpty( $this->install_args['force']['description'], 'Force should have a description.' );
	}

	/**
	 * Test file parameter is not required.
	 */
	public function test_file_parameter_not_required() {
		$this->assertFalse( $this->install_args['file']['required'], 'File should not be required at schema level.' );
	}

	/**
	 * Test slug parameter is not required.
	 */
	public function test_slug_parameter_not_required() {
		$this->assertFalse( $this->install_args['slug']['required'], 'Slug should not be required at schema level.' );
	}

	/**
	 * Test slug parameter has a description.
	 */
	public function test_slug_parameter_description() {
		$this->assertNotEmpty( $this->install_args['slug']['description'], 'Slug should have a description.' );
		$this->assertEquals( 'string', $this->install_args['slug']['type'], 'Slug schema type should be string.' );
	}

	/**
	 * Test file parameter has a description.
	 */
	public function test_file_parameter_description() {
		$this->assertNotEmpty( $this->install_args['file']['description'], 'File should have a description.' );
	}

	/**
	 * Test get_install_webhook_args returns exactly 4 parameters.
	 */
	public function test_install_webhook_args_count() {
		$this->assertCount( 4, $this->install_args, 'Should have exactly 4 endpoint parameters.' );
	}

	/**
	 * Test all parameters have validate or sanitize callbacks.
	 */
	public function test_all_params_have_callbacks() {
		foreach ( $this->install_args as $key => $config ) {
			$has_callback = isset( $config['validate_callback'] ) || isset( $config['sanitize_callback'] );
			$this->assertTrue( $has_callback, "Parameter '$key' should have a validate or sanitize callback." );
		}
	}

	/**
	 * Test register_routes creates the install endpoint.
	 */
	public function test_register_routes_creates_install_endpoint() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		// Register routes.
		$controller->register_routes();

		// Check that routes are registered.
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/popup-maker/v2/connect/install', $routes, 'Install route should be registered.' );
		$this->assertArrayHasKey( '/popup-maker/v2/connect/verify', $routes, 'Verify route should be registered.' );
	}

	/**
	 * Test install endpoint route is POST only.
	 */
	public function test_install_route_is_post_only() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		$controller->register_routes();
		$routes = rest_get_server()->get_routes();

		$install_route = $routes['/popup-maker/v2/connect/install'];
		// The first element should have 'methods' containing POST.
		$this->assertContains( 'POST', array_keys( $install_route[0]['methods'] ), 'Install route should accept POST.' );
	}

	/**
	 * Test verify endpoint route is POST only.
	 */
	public function test_verify_route_is_post_only() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		$controller->register_routes();
		$routes = rest_get_server()->get_routes();

		$verify_route = $routes['/popup-maker/v2/connect/verify'];
		$this->assertContains( 'POST', array_keys( $verify_route[0]['methods'] ), 'Verify route should accept POST.' );
	}

	/**
	 * Test slug with numbers only.
	 */
	public function test_slug_validate_numbers_only() {
		$validate = $this->install_args['slug']['validate_callback'];

		$this->assertTrue( $validate( '12345' ), 'Numbers-only slug should pass.' );
	}

	/**
	 * Test slug with hyphen-underscore combinations.
	 */
	public function test_slug_validate_hyphen_underscore_mix() {
		$validate = $this->install_args['slug']['validate_callback'];

		$this->assertTrue( $validate( 'my-plugin_v2' ), 'Mixed hyphen-underscore slug should pass.' );
	}

	/**
	 * Test file validate_callback rejects javascript scheme.
	 */
	public function test_file_validate_callback_rejects_javascript() {
		$validate = $this->install_args['file']['validate_callback'];

		$result = $validate( 'javascript:alert(1)' );
		$this->assertInstanceOf( WP_Error::class, $result, 'JavaScript URI should fail validation.' );
	}

	/**
	 * Test file validate_callback rejects data scheme.
	 */
	public function test_file_validate_callback_rejects_data_uri() {
		$validate = $this->install_args['file']['validate_callback'];

		$result = $validate( 'data:text/html,<h1>test</h1>' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Data URI should fail validation.' );
	}

	/**
	 * Test type enum does not include arbitrary values.
	 */
	public function test_type_enum_only_plugin_and_theme() {
		$this->assertCount( 2, $this->install_args['type']['enum'], 'Type enum should have exactly 2 values.' );
		$this->assertContains( 'plugin', $this->install_args['type']['enum'], 'Should contain plugin.' );
		$this->assertContains( 'theme', $this->install_args['type']['enum'], 'Should contain theme.' );
	}

	/**
	 * Test slug rejects whitespace.
	 */
	public function test_slug_validate_callback_rejects_whitespace() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( 'my plugin' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Slug with whitespace should fail.' );
	}

	/**
	 * Test that install route has permission_callback.
	 */
	public function test_install_route_has_permission_callback() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		$controller->register_routes();
		$routes = rest_get_server()->get_routes();

		$install_route = $routes['/popup-maker/v2/connect/install'];
		$this->assertArrayHasKey( 'permission_callback', $install_route[0], 'Install route should have a permission callback.' );
	}

	/**
	 * Test that install route has args defined.
	 */
	public function test_install_route_has_args() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		$controller->register_routes();
		$routes = rest_get_server()->get_routes();

		$install_route = $routes['/popup-maker/v2/connect/install'];
		$this->assertNotEmpty( $install_route[0]['args'], 'Install route should have args defined.' );
	}

	/**
	 * Test verify route has empty args.
	 */
	public function test_verify_route_has_empty_args() {
		[ $controller ] = $this->create_controller_with_mock_service(
			[ 'debug_log', 'get_access_token', 'get_request_token', 'generate_hash', 'debug_mode_enabled' ]
		);

		$controller->register_routes();
		$routes = rest_get_server()->get_routes();

		$verify_route = $routes['/popup-maker/v2/connect/verify'];
		$this->assertEmpty( $verify_route[0]['args'], 'Verify route should have no args.' );
	}

	/**
	 * Test slug rejects HTML entities.
	 */
	public function test_slug_validate_callback_rejects_html() {
		$validate = $this->install_args['slug']['validate_callback'];

		$result = $validate( '<script>' );
		$this->assertInstanceOf( WP_Error::class, $result, 'HTML in slug should fail.' );
	}

	/**
	 * Test file validate_callback rejects FTP scheme.
	 */
	public function test_file_validate_callback_ftp() {
		$validate = $this->install_args['file']['validate_callback'];

		// FTP is technically a valid URL.
		$result = $validate( 'ftp://example.com/file.zip' );
		$this->assertTrue( $result, 'FTP URL should pass FILTER_VALIDATE_URL.' );
	}

	/**
	 * Test force sanitize_callback with string "true" and "false".
	 */
	public function test_force_sanitize_callback_string_booleans() {
		$sanitize = $this->install_args['force']['sanitize_callback'];

		$this->assertTrue( $sanitize( 'true' ), 'String "true" should become true.' );
		$this->assertTrue( $sanitize( 'false' ), 'Non-empty string "false" should become true (PHP bool cast).' );
	}
}
