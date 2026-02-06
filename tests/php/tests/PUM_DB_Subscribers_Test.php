<?php
/**
 * Tests for PUM_DB_Subscribers.
 *
 * @package Popup_Maker
 */

/**
 * Test the Subscribers database handler.
 */
class PUM_DB_Subscribers_Test extends WP_UnitTestCase {

	/**
	 * Subscribers DB instance.
	 *
	 * @var PUM_DB_Subscribers
	 */
	private $db;

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->db = new PUM_DB_Subscribers();
	}

	/**
	 * Test table_name includes wpdb prefix.
	 */
	public function test_table_name_includes_prefix() {
		global $wpdb;
		$this->assertSame( $wpdb->prefix . 'pum_subscribers', $this->db->table_name() );
	}

	/**
	 * Test table_name property is set correctly.
	 */
	public function test_table_name_property() {
		$this->assertSame( 'pum_subscribers', $this->db->table_name );
	}

	/**
	 * Test primary key is ID.
	 */
	public function test_primary_key_is_id() {
		$this->assertSame( 'ID', $this->db->primary_key );
	}

	/**
	 * Test version is set.
	 */
	public function test_version_is_set() {
		$this->assertSame( 20200917, $this->db->version );
	}

	/**
	 * Test get_columns returns all expected columns.
	 */
	public function test_get_columns_returns_expected_keys() {
		$columns  = $this->db->get_columns();
		$expected = [
			'ID',
			'uuid',
			'popup_id',
			'email_hash',
			'email',
			'name',
			'fname',
			'lname',
			'user_id',
			'consent_args',
			'consent',
			'created',
		];

		$this->assertIsArray( $columns );

		foreach ( $expected as $col ) {
			$this->assertArrayHasKey( $col, $columns, "Missing column: $col" );
		}
	}

	/**
	 * Test get_columns format specifiers are valid.
	 */
	public function test_get_columns_format_specifiers() {
		$columns        = $this->db->get_columns();
		$valid_formats  = [ '%d', '%s', '%f' ];

		foreach ( $columns as $col => $format ) {
			$this->assertContains( $format, $valid_formats, "Invalid format for column $col: $format" );
		}
	}

	/**
	 * Test numeric columns use %d format.
	 */
	public function test_numeric_columns_use_d_format() {
		$columns         = $this->db->get_columns();
		$numeric_columns = [ 'ID', 'popup_id', 'user_id' ];

		foreach ( $numeric_columns as $col ) {
			$this->assertSame( '%d', $columns[ $col ], "$col should use %d format." );
		}
	}

	/**
	 * Test string columns use %s format.
	 */
	public function test_string_columns_use_s_format() {
		$columns        = $this->db->get_columns();
		$string_columns = [ 'uuid', 'email_hash', 'email', 'name', 'fname', 'lname', 'consent_args', 'consent', 'created' ];

		foreach ( $string_columns as $col ) {
			$this->assertSame( '%s', $columns[ $col ], "$col should use %s format." );
		}
	}

	/**
	 * Test get_column_defaults has all required fields.
	 */
	public function test_get_column_defaults_has_required_fields() {
		$defaults = $this->db->get_column_defaults();
		$expected = [ 'uuid', 'popup_id', 'email_hash', 'email', 'name', 'fname', 'lname', 'user_id', 'consent_args', 'consent', 'created' ];

		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $defaults, "Missing default for: $key" );
		}
	}

	/**
	 * Test default consent value is 'no'.
	 */
	public function test_default_consent_is_no() {
		$defaults = $this->db->get_column_defaults();
		$this->assertSame( 'no', $defaults['consent'] );
	}

	/**
	 * Test default popup_id is 0.
	 */
	public function test_default_popup_id_is_zero() {
		$defaults = $this->db->get_column_defaults();
		$this->assertSame( 0, $defaults['popup_id'] );
	}

	/**
	 * Test default user_id is 0.
	 */
	public function test_default_user_id_is_zero() {
		$defaults = $this->db->get_column_defaults();
		$this->assertSame( 0, $defaults['user_id'] );
	}

	/**
	 * Test default created is a valid datetime string.
	 */
	public function test_default_created_is_datetime() {
		$defaults = $this->db->get_column_defaults();
		// Should be a MySQL datetime format (Y-m-d H:i:s).
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $defaults['created'] );
	}

	/**
	 * Test column count matches between columns and defaults (minus ID).
	 */
	public function test_column_count_consistency() {
		$columns  = $this->db->get_columns();
		$defaults = $this->db->get_column_defaults();

		// Defaults should have all columns except ID (auto-increment).
		$this->assertCount( count( $columns ) - 1, $defaults );
	}

	/**
	 * Test total_rows returns 0 when table is empty.
	 */
	public function test_total_rows_returns_zero_on_empty() {
		$count = $this->db->total_rows( [] );
		$this->assertSame( 0, $count );
	}
}
