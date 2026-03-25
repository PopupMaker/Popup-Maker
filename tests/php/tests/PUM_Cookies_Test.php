<?php
/**
 * Tests for PUM_Cookies registry class.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Cookies registry.
 */
class PUM_Cookies_Test extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var PUM_Cookies
	 */
	private $cookies;

	/**
	 * Set up each test with a fresh instance.
	 */
	public function setUp(): void {
		parent::setUp();
		// Reset singleton so each test is independent.
		PUM_Cookies::$instance = null;
		$this->cookies         = new PUM_Cookies();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		PUM_Cookies::$instance = null;
		parent::tearDown();
	}

	// ─── Singleton ─────────────────────────────────────────────────────

	/**
	 * Test instance returns the same object.
	 */
	public function test_instance_returns_singleton() {
		$a = PUM_Cookies::instance();
		$b = PUM_Cookies::instance();
		$this->assertSame( $a, $b, 'instance() should return the same object.' );
	}

	/**
	 * Test init calls instance without error.
	 */
	public function test_init_creates_instance() {
		PUM_Cookies::init();
		$this->assertInstanceOf( PUM_Cookies::class, PUM_Cookies::$instance );
	}

	// ─── add_cookie / get_cookie ───────────────────────────────────────

	/**
	 * Test adding and retrieving a single cookie.
	 */
	public function test_add_cookie_and_get_cookie() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookie( [
			'id'   => 'test_cookie',
			'name' => 'Test Cookie',
		] );

		$result = $this->cookies->get_cookie( 'test_cookie' );
		$this->assertNotNull( $result, 'Cookie should be retrievable.' );
		$this->assertEquals( 'Test Cookie', $result['name'] );
	}

	/**
	 * Test default values are merged for a cookie.
	 */
	public function test_add_cookie_merges_defaults() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookie( [
			'id'   => 'minimal_cookie',
			'name' => 'Minimal',
		] );

		$result = $this->cookies->get_cookie( 'minimal_cookie' );
		$this->assertNotNull( $result );
		$this->assertEquals( 10, $result['priority'], 'Default priority should be 10.' );
		$this->assertArrayHasKey( 'tabs', $result, 'Should have tabs.' );
		$this->assertArrayHasKey( 'fields', $result, 'Should have fields.' );
	}

	/**
	 * Test adding a cookie without id is ignored.
	 */
	public function test_add_cookie_without_id_is_ignored() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookie( [
			'name' => 'No ID Cookie',
		] );

		$this->assertEmpty( $this->cookies->cookies, 'Cookie without id should not be added.' );
	}

	/**
	 * Test null cookie is ignored.
	 */
	public function test_add_cookie_null_is_ignored() {
		$this->cookies->cookies = [];
		$this->cookies->add_cookie( null );
		$this->assertEmpty( $this->cookies->cookies );
	}

	/**
	 * Test duplicate cookie ids are not overwritten.
	 */
	public function test_add_cookie_does_not_overwrite_existing() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookie( [
			'id'   => 'dup_cookie',
			'name' => 'First',
		] );

		$this->cookies->add_cookie( [
			'id'   => 'dup_cookie',
			'name' => 'Second',
		] );

		$result = $this->cookies->get_cookie( 'dup_cookie' );
		$this->assertEquals( 'First', $result['name'], 'First registered cookie should win.' );
	}

	/**
	 * Test get_cookie returns null for unknown cookie.
	 */
	public function test_get_cookie_returns_null_for_unknown() {
		$this->cookies->cookies = [];
		$this->assertNull( $this->cookies->get_cookie( 'nonexistent' ) );
	}

	/**
	 * Test backward compatibility label merging.
	 */
	public function test_add_cookie_merges_labels_for_backwards_compat() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookie( [
			'id'     => 'labeled_cookie',
			'labels' => [
				'name' => 'From Labels',
			],
		] );

		$result = $this->cookies->get_cookie( 'labeled_cookie' );
		$this->assertEquals( 'From Labels', $result['name'], 'Label name should be promoted.' );
		$this->assertArrayNotHasKey( 'labels', $result, 'Labels key should be removed.' );
	}

	// ─── add_cookies (batch) ───────────────────────────────────────────

	/**
	 * Test adding multiple cookies at once.
	 */
	public function test_add_cookies_batch() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookies( [
			'cookie_a' => [
				'name' => 'Cookie A',
			],
			'cookie_b' => [
				'name' => 'Cookie B',
			],
		] );

		$this->assertNotNull( $this->cookies->get_cookie( 'cookie_a' ) );
		$this->assertNotNull( $this->cookies->get_cookie( 'cookie_b' ) );
	}

	/**
	 * Test add_cookies assigns key as id when id is missing.
	 */
	public function test_add_cookies_uses_key_as_id() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookies( [
			'my_key' => [
				'name' => 'Keyed Cookie',
			],
		] );

		$result = $this->cookies->get_cookie( 'my_key' );
		$this->assertNotNull( $result );
		$this->assertEquals( 'my_key', $result['id'] );
	}

	/**
	 * Test add_cookies with numeric key does not set id.
	 */
	public function test_add_cookies_numeric_key_does_not_become_id() {
		$this->cookies->cookies = [];

		$this->cookies->add_cookies( [
			0 => [
				'name' => 'No ID',
			],
		] );

		$this->assertEmpty( $this->cookies->cookies );
	}

	// ─── get_cookies (lazy loading) ────────────────────────────────────

	/**
	 * Test get_cookies triggers registration when not yet set.
	 */
	public function test_get_cookies_auto_registers() {
		$cookies = $this->cookies->get_cookies();
		$this->assertIsArray( $cookies );
		$this->assertNotEmpty( $cookies, 'Should auto-register built-in cookies.' );
	}

	/**
	 * Test default registered cookies include expected types.
	 */
	public function test_default_cookies_include_expected() {
		$cookies = $this->cookies->get_cookies();

		$this->assertArrayHasKey( 'on_popup_close', $cookies, 'Should include On Popup Close.' );
		$this->assertArrayHasKey( 'on_popup_open', $cookies, 'Should include On Popup Open.' );
		$this->assertArrayHasKey( 'on_popup_conversion', $cookies, 'Should include On Popup Conversion.' );
		$this->assertArrayHasKey( 'form_submission', $cookies, 'Should include Form Submission.' );
		$this->assertArrayHasKey( 'pum_sub_form_success', $cookies, 'Should include Subscription Form: Successful.' );
		$this->assertArrayHasKey( 'pum_sub_form_already_subscribed', $cookies, 'Should include Already Subscribed.' );
		$this->assertArrayHasKey( 'manual', $cookies, 'Should include Manual.' );
	}

	/**
	 * Test each default cookie has a name field.
	 */
	public function test_default_cookies_all_have_names() {
		$cookies = $this->cookies->get_cookies();

		foreach ( $cookies as $id => $cookie ) {
			$this->assertNotEmpty( $cookie['name'], "Cookie '{$id}' should have a name." );
		}
	}

	// ─── cookie_fields ─────────────────────────────────────────────────

	/**
	 * Test cookie_fields returns expected structure.
	 */
	public function test_cookie_fields_structure() {
		$fields = $this->cookies->cookie_fields();
		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'general', $fields, 'Should have general tab.' );
		$this->assertArrayHasKey( 'advanced', $fields, 'Should have advanced tab.' );
	}

	/**
	 * Test cookie_fields general tab has name and time fields.
	 */
	public function test_cookie_fields_general_tab() {
		$fields = $this->cookies->cookie_fields();

		$this->assertArrayHasKey( 'name', $fields['general'] );
		$this->assertArrayHasKey( 'time', $fields['general'] );
		$this->assertNotEmpty( $fields['general']['name']['label'] );
		$this->assertNotEmpty( $fields['general']['time']['label'] );
	}

	/**
	 * Test cookie_fields advanced tab has session, path, and key fields.
	 */
	public function test_cookie_fields_advanced_tab() {
		$fields = $this->cookies->cookie_fields();

		$this->assertArrayHasKey( 'session', $fields['advanced'] );
		$this->assertArrayHasKey( 'path', $fields['advanced'] );
		$this->assertArrayHasKey( 'key', $fields['advanced'] );
	}

	/**
	 * Test cookie_fields default values.
	 */
	public function test_cookie_fields_default_values() {
		$fields = $this->cookies->cookie_fields();

		$this->assertEquals( '1 month', $fields['general']['time']['std'], 'Default time should be 1 month.' );
		$this->assertFalse( $fields['advanced']['session']['std'], 'Default session should be false.' );
		$this->assertTrue( $fields['advanced']['path']['std'], 'Default sitewide path should be true.' );
	}

	// ─── get_tabs ──────────────────────────────────────────────────────

	/**
	 * Test get_tabs returns expected tabs.
	 */
	public function test_get_tabs() {
		$tabs = $this->cookies->get_tabs();
		$this->assertIsArray( $tabs );
		$this->assertArrayHasKey( 'general', $tabs );
		$this->assertArrayHasKey( 'advanced', $tabs );
	}

	// ─── dropdown_list ─────────────────────────────────────────────────

	/**
	 * Test dropdown_list returns id => name pairs.
	 */
	public function test_dropdown_list_structure() {
		$list = $this->cookies->dropdown_list();
		$this->assertIsArray( $list );
		$this->assertNotEmpty( $list );

		foreach ( $list as $id => $name ) {
			$this->assertIsString( $name, 'Cookie name should be a string.' );
		}
	}

	/**
	 * Test dropdown_list includes all default cookies.
	 */
	public function test_dropdown_list_includes_defaults() {
		$list = $this->cookies->dropdown_list();
		$this->assertArrayHasKey( 'on_popup_close', $list );
		$this->assertArrayHasKey( 'on_popup_open', $list );
		$this->assertArrayHasKey( 'manual', $list );
	}

	// ─── validate_cookie (deprecated) ──────────────────────────────────

	/**
	 * Test deprecated validate_cookie returns settings unchanged.
	 */
	public function test_validate_cookie_returns_settings() {
		$settings = [ 'name' => 'test', 'time' => '1 day' ];
		$result   = $this->cookies->validate_cookie( 'on_popup_close', $settings );
		$this->assertSame( $settings, $result, 'Deprecated method should return settings as-is.' );
	}

	// ─── get_labels ────────────────────────────────────────────────────

	/**
	 * Test get_labels returns array.
	 */
	public function test_get_labels_returns_array() {
		$labels = $this->cookies->get_labels();
		$this->assertIsArray( $labels );
	}

	// ─── Filter integration ────────────────────────────────────────────

	/**
	 * Test pum_registered_cookies filter can add cookies.
	 */
	public function test_pum_registered_cookies_filter() {
		add_filter( 'pum_registered_cookies', function ( $cookies ) {
			$cookies['custom_cookie'] = [
				'name' => 'Custom Cookie Event',
			];
			return $cookies;
		} );

		$cookies = $this->cookies->get_cookies();
		$this->assertArrayHasKey( 'custom_cookie', $cookies );
		$this->assertEquals( 'Custom Cookie Event', $cookies['custom_cookie']['name'] );

		// Clean up.
		remove_all_filters( 'pum_registered_cookies' );
	}

	/**
	 * Test pum_get_cookie_fields filter can modify cookie fields.
	 */
	public function test_pum_get_cookie_fields_filter() {
		add_filter( 'pum_get_cookie_fields', function ( $fields ) {
			$fields['general']['custom_field'] = [
				'label' => 'Custom Field',
				'type'  => 'text',
			];
			return $fields;
		} );

		$fields = $this->cookies->cookie_fields();
		$this->assertArrayHasKey( 'custom_field', $fields['general'] );

		// Clean up.
		remove_all_filters( 'pum_get_cookie_fields' );
	}

	/**
	 * Test deprecated pum_get_cookies filter still works.
	 */
	public function test_deprecated_pum_get_cookies_filter() {
		add_filter( 'pum_get_cookies', function ( $cookies ) {
			$cookies['legacy_cookie'] = [
				'name' => 'Legacy Cookie',
			];
			return $cookies;
		} );

		$cookies = $this->cookies->get_cookies();
		$this->assertArrayHasKey( 'legacy_cookie', $cookies );

		// Clean up.
		remove_all_filters( 'pum_get_cookies' );
	}
}
