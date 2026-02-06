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
	 * Test webhook_permissions_check always returns true.
	 *
	 * Webhook endpoints rely on multi-layer security instead of WP capabilities.
	 */
	public function test_webhook_permissions_check_returns_true() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		// Override constructor by using reflection to skip plugin() call.
		$request = new WP_REST_Request( 'POST', '/popup-maker/v2/connect/install' );
		$result  = $controller->webhook_permissions_check( $request );

		$this->assertTrue( $result, 'Webhook permissions should always return true (security is in endpoint).' );
	}

	/**
	 * Test get_install_webhook_args defines expected parameters.
	 */
	public function test_get_install_webhook_args_structure() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args = $controller->get_install_webhook_args();

		$this->assertArrayHasKey( 'file', $args, 'Should have file parameter.' );
		$this->assertArrayHasKey( 'type', $args, 'Should have type parameter.' );
		$this->assertArrayHasKey( 'slug', $args, 'Should have slug parameter.' );
		$this->assertArrayHasKey( 'force', $args, 'Should have force parameter.' );
	}

	/**
	 * Test file parameter validates URLs.
	 */
	public function test_file_validate_callback_rejects_invalid_url() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['file']['validate_callback'];

		// Invalid URL should return WP_Error.
		$result = $validate( 'not-a-url' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Invalid URL should return WP_Error.' );
		$this->assertEquals( 'invalid_file_url', $result->get_error_code(), 'Error code should be invalid_file_url.' );
	}

	/**
	 * Test file parameter accepts valid URLs.
	 */
	public function test_file_validate_callback_accepts_valid_url() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['file']['validate_callback'];

		$result = $validate( 'https://example.com/plugin.zip' );
		$this->assertTrue( $result, 'Valid URL should pass validation.' );
	}

	/**
	 * Test file parameter rejects empty string.
	 */
	public function test_file_validate_callback_rejects_empty() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['file']['validate_callback'];

		$result = $validate( '' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Empty string should return WP_Error.' );
	}

	/**
	 * Test slug parameter validates format.
	 */
	public function test_slug_validate_callback_accepts_valid_slug() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['slug']['validate_callback'];

		$this->assertTrue( $validate( 'popup-maker-pro' ), 'Hyphenated slug should pass.' );
		$this->assertTrue( $validate( 'my_plugin' ), 'Underscored slug should pass.' );
		$this->assertTrue( $validate( 'plugin123' ), 'Alphanumeric slug should pass.' );
	}

	/**
	 * Test slug parameter rejects invalid characters.
	 */
	public function test_slug_validate_callback_rejects_invalid_slug() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['slug']['validate_callback'];

		$result = $validate( 'Invalid Slug!' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Slug with spaces and special chars should fail.' );
		$this->assertEquals( 'invalid_slug', $result->get_error_code(), 'Error code should be invalid_slug.' );
	}

	/**
	 * Test slug parameter rejects empty string.
	 */
	public function test_slug_validate_callback_rejects_empty() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['slug']['validate_callback'];

		$result = $validate( '' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Empty slug should fail.' );
	}

	/**
	 * Test type parameter default is 'plugin'.
	 */
	public function test_type_parameter_default() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args = $controller->get_install_webhook_args();

		$this->assertEquals( 'plugin', $args['type']['default'], 'Default type should be plugin.' );
		$this->assertEquals( [ 'plugin', 'theme' ], $args['type']['enum'], 'Type enum should be plugin and theme.' );
	}

	/**
	 * Test force parameter default is false.
	 */
	public function test_force_parameter_default() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args = $controller->get_install_webhook_args();

		$this->assertFalse( $args['force']['default'], 'Default force should be false.' );
	}

	/**
	 * Test force parameter sanitize_callback converts to boolean.
	 */
	public function test_force_sanitize_callback() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$sanitize = $args['force']['sanitize_callback'];

		$this->assertTrue( $sanitize( 1 ), 'Truthy value should become true.' );
		$this->assertTrue( $sanitize( 'yes' ), 'Non-empty string should become true.' );
		$this->assertFalse( $sanitize( 0 ), 'Zero should become false.' );
		$this->assertFalse( $sanitize( '' ), 'Empty string should become false.' );
	}

	/**
	 * Test slug parameter rejects uppercase letters.
	 */
	public function test_slug_rejects_uppercase() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args     = $controller->get_install_webhook_args();
		$validate = $args['slug']['validate_callback'];

		$result = $validate( 'PopupMaker' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Uppercase slug should fail validation.' );
	}

	/**
	 * Test file parameter has URI format.
	 */
	public function test_file_parameter_metadata() {
		$controller = $this->createPartialMock(
			Connect::class,
			[]
		);

		$args = $controller->get_install_webhook_args();

		$this->assertEquals( 'string', $args['file']['type'], 'File type should be string.' );
		$this->assertEquals( 'uri', $args['file']['format'], 'File format should be uri.' );
		$this->assertEquals( 'esc_url_raw', $args['file']['sanitize_callback'], 'File sanitize callback should be esc_url_raw.' );
	}
}
