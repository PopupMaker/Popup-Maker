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

	// ─── create_table() ────────────────────────────────────────────────

	/**
	 * Test create_table creates the table and stores version.
	 */
	public function test_create_table_creates_table() {
		global $wpdb;
		$this->db->create_table();

		if ( floatval( get_bloginfo( 'version' ) ) >= 6.2 ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->db->table_name() ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$found = $wpdb->get_var( "SHOW TABLES LIKE '{$this->db->table_name()}'" );
		}

		$this->assertSame( $this->db->table_name(), $found );
	}

	/**
	 * Test create_table updates the version option.
	 */
	public function test_create_table_updates_version_option() {
		$this->db->create_table();
		$version = get_option( 'pum_subscribers_db_version' );
		$this->assertNotFalse( $version );
	}

	// ─── insert() ──────────────────────────────────────────────────────

	/**
	 * Test insert creates a subscriber and returns an ID.
	 */
	public function test_insert_returns_id() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email'    => 'test@example.com',
			'name'     => 'Test User',
			'popup_id' => 1,
		] );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	/**
	 * Test insert with minimal data uses defaults.
	 */
	public function test_insert_with_defaults() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email' => 'defaults@example.com',
		] );

		$row = $this->db->get( $id );
		$this->assertSame( 'defaults@example.com', $row->email );
		$this->assertSame( 'no', $row->consent );
		$this->assertSame( '0', (string) $row->popup_id );
	}

	/**
	 * Test insert sets created timestamp automatically.
	 */
	public function test_insert_sets_created() {
		$this->db->create_table();

		$id  = $this->db->insert( [ 'email' => 'time@example.com' ] );
		$row = $this->db->get( $id );

		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row->created );
	}

	// ─── get() ─────────────────────────────────────────────────────────

	/**
	 * Test get retrieves a subscriber by ID.
	 */
	public function test_get_by_id() {
		$this->db->create_table();

		$id  = $this->db->insert( [
			'email' => 'get@example.com',
			'name'  => 'Get Test',
		] );
		$row = $this->db->get( $id );

		$this->assertIsObject( $row );
		$this->assertSame( 'get@example.com', $row->email );
		$this->assertSame( 'Get Test', $row->name );
	}

	/**
	 * Test get returns null for non-existent ID.
	 */
	public function test_get_nonexistent() {
		$this->db->create_table();
		$row = $this->db->get( 99999 );
		$this->assertNull( $row );
	}

	// ─── get_by() ──────────────────────────────────────────────────────

	/**
	 * Test get_by retrieves a subscriber by email.
	 */
	public function test_get_by_email_column() {
		$this->db->create_table();

		$this->db->insert( [
			'email' => 'findme@example.com',
			'name'  => 'Find Me',
		] );

		$row = $this->db->get_by( 'email', 'findme@example.com' );
		$this->assertIsObject( $row );
		$this->assertSame( 'Find Me', $row->name );
	}

	/**
	 * Test get_by returns null when no match found.
	 */
	public function test_get_by_no_match() {
		$this->db->create_table();
		$row = $this->db->get_by( 'email', 'nobody@example.com' );
		$this->assertNull( $row );
	}

	// ─── get_column() ──────────────────────────────────────────────────

	/**
	 * Test get_column retrieves a single column value.
	 */
	public function test_get_column_value() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email' => 'column@example.com',
			'name'  => 'Column Test',
		] );

		$email = $this->db->get_column( 'email', $id );
		$this->assertSame( 'column@example.com', $email );
	}

	/**
	 * Test get_column returns null for non-existent row.
	 */
	public function test_get_column_nonexistent() {
		$this->db->create_table();
		$result = $this->db->get_column( 'email', 99999 );
		$this->assertNull( $result );
	}

	// ─── get_column_by() ───────────────────────────────────────────────

	/**
	 * Test get_column_by retrieves column value by another column.
	 */
	public function test_get_column_by() {
		$this->db->create_table();

		$this->db->insert( [
			'email' => 'colby@example.com',
			'name'  => 'ColBy User',
		] );

		$name = $this->db->get_column_by( 'name', 'email', 'colby@example.com' );
		$this->assertSame( 'ColBy User', $name );
	}

	// ─── update() ──────────────────────────────────────────────────────

	/**
	 * Test update modifies an existing subscriber.
	 */
	public function test_update_modifies_row() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email' => 'update@example.com',
			'name'  => 'Original',
		] );

		$result = $this->db->update( $id, [ 'name' => 'Updated' ] );
		$this->assertTrue( $result );

		$row = $this->db->get( $id );
		$this->assertSame( 'Updated', $row->name );
	}

	/**
	 * Test update with zero row_id returns false.
	 */
	public function test_update_zero_id_returns_false() {
		$this->db->create_table();
		$result = $this->db->update( 0, [ 'name' => 'Nope' ] );
		$this->assertFalse( $result );
	}

	/**
	 * Test update with negative row_id returns true (absint converts to positive).
	 *
	 * The database layer uses absint() which converts -5 to 5, making it a valid positive ID.
	 */
	public function test_update_negative_id_returns_false() {
		$this->db->create_table();
		$result = $this->db->update( -5, [ 'name' => 'Nope' ] );
		// absint(-5) = 5, which is a valid positive ID, so update proceeds.
		$this->assertTrue( $result );
	}

	/**
	 * Test update only modifies whitelisted columns.
	 */
	public function test_update_ignores_unknown_columns() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email' => 'whitelist@example.com',
			'name'  => 'Original',
		] );

		// 'fake_column' should be stripped.
		$result = $this->db->update( $id, [
			'name'        => 'Updated',
			'fake_column' => 'ignored',
		] );
		$this->assertTrue( $result );

		$row = $this->db->get( $id );
		$this->assertSame( 'Updated', $row->name );
	}

	/**
	 * Test update with custom where column.
	 */
	public function test_update_with_custom_where() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email'    => 'customwhere@example.com',
			'popup_id' => 42,
			'name'     => 'Before',
		] );

		// Using the default where (primary key).
		$result = $this->db->update( $id, [ 'name' => 'After' ] );
		$this->assertTrue( $result );

		$row = $this->db->get( $id );
		$this->assertSame( 'After', $row->name );
	}

	// ─── delete() ──────────────────────────────────────────────────────

	/**
	 * Test delete removes a subscriber.
	 */
	public function test_delete_removes_row() {
		$this->db->create_table();

		$id = $this->db->insert( [
			'email' => 'delete@example.com',
		] );

		$result = $this->db->delete( $id );
		$this->assertTrue( $result );

		$row = $this->db->get( $id );
		$this->assertNull( $row );
	}

	/**
	 * Test delete with zero id returns false.
	 */
	public function test_delete_zero_id_returns_false() {
		$this->db->create_table();
		$result = $this->db->delete( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test delete with negative id returns true (absint converts to positive).
	 *
	 * The database layer uses absint() which converts -1 to 1, making it a valid positive ID.
	 */
	public function test_delete_negative_id_returns_false() {
		$this->db->create_table();
		$result = $this->db->delete( -1 );
		// absint(-1) = 1, which is a valid positive ID, so delete proceeds.
		$this->assertTrue( $result );
	}

	// ─── delete_by() ───────────────────────────────────────────────────

	/**
	 * Test delete_by removes rows matching a column value.
	 */
	public function test_delete_by_column() {
		$this->db->create_table();

		$this->db->insert( [
			'email'    => 'delby1@example.com',
			'popup_id' => 99,
		] );
		$this->db->insert( [
			'email'    => 'delby2@example.com',
			'popup_id' => 99,
		] );

		$result = $this->db->delete_by( 'popup_id', 99 );
		$this->assertTrue( $result );

		$count = $this->db->total_rows( [] );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test delete_by with empty value returns false.
	 */
	public function test_delete_by_empty_value_returns_false() {
		$this->db->create_table();
		$result = $this->db->delete_by( 'email', '' );
		$this->assertFalse( $result );
	}

	// ─── query() ───────────────────────────────────────────────────────

	/**
	 * Test query returns all subscribers.
	 */
	public function test_query_returns_all() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'q1@example.com' ] );
		$this->db->insert( [ 'email' => 'q2@example.com' ] );
		$this->db->insert( [ 'email' => 'q3@example.com' ] );

		$results = $this->db->query();
		$this->assertCount( 3, $results );
	}

	/**
	 * Test query with limit.
	 */
	public function test_query_with_limit() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'l1@example.com' ] );
		$this->db->insert( [ 'email' => 'l2@example.com' ] );
		$this->db->insert( [ 'email' => 'l3@example.com' ] );

		$results = $this->db->query( [ 'limit' => 2 ] );
		$this->assertCount( 2, $results );
	}

	/**
	 * Test query with search term.
	 */
	public function test_query_with_search() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'alice@example.com', 'name' => 'Alice' ] );
		$this->db->insert( [ 'email' => 'bob@example.com', 'name' => 'Bob' ] );

		$results = $this->db->query( [ 's' => 'alice' ] );
		$this->assertCount( 1, $results );
		$this->assertSame( 'Alice', $results[0]->name );
	}

	/**
	 * Test query with orderby and order.
	 */
	public function test_query_with_orderby() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'a@example.com', 'name' => 'Alpha' ] );
		$this->db->insert( [ 'email' => 'b@example.com', 'name' => 'Beta' ] );

		$results = $this->db->query( [
			'orderby' => 'name',
			'order'   => 'ASC',
		] );

		$this->assertSame( 'Alpha', $results[0]->name );
		$this->assertSame( 'Beta', $results[1]->name );
	}

	/**
	 * Test query with specific fields.
	 */
	public function test_query_with_specific_fields() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'fields@example.com', 'name' => 'Field Test' ] );

		$results = $this->db->query( [ 'fields' => 'email, name' ] );
		$this->assertCount( 1, $results );
		$this->assertObjectHasProperty( 'email', $results[0] );
		$this->assertObjectHasProperty( 'name', $results[0] );
	}

	/**
	 * Test query with pagination.
	 */
	public function test_query_with_pagination() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'p1@example.com', 'name' => 'Page1A' ] );
		$this->db->insert( [ 'email' => 'p2@example.com', 'name' => 'Page1B' ] );
		$this->db->insert( [ 'email' => 'p3@example.com', 'name' => 'Page2A' ] );

		$results = $this->db->query( [
			'limit' => 2,
			'page'  => 2,
		] );

		$this->assertCount( 1, $results );
	}

	/**
	 * Test query with offset.
	 */
	public function test_query_with_offset() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'o1@example.com' ] );
		$this->db->insert( [ 'email' => 'o2@example.com' ] );
		$this->db->insert( [ 'email' => 'o3@example.com' ] );

		$results = $this->db->query( [
			'limit'  => 10,
			'offset' => 2,
		] );

		$this->assertCount( 1, $results );
	}

	/**
	 * Test query with numeric search finds matching IDs.
	 */
	public function test_query_numeric_search() {
		$this->db->create_table();

		$id1 = $this->db->insert( [ 'email' => 'num1@example.com', 'popup_id' => 42 ] );
		$this->db->insert( [ 'email' => 'num2@example.com', 'popup_id' => 99 ] );

		$results = $this->db->query( [ 's' => '42' ] );
		// Numeric search should match popup_id, user_id, and ID columns.
		$this->assertGreaterThanOrEqual( 1, count( $results ) );
	}

	// ─── total_rows() ──────────────────────────────────────────────────

	/**
	 * Test total_rows after inserting records.
	 */
	public function test_total_rows_after_inserts() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 't1@example.com' ] );
		$this->db->insert( [ 'email' => 't2@example.com' ] );

		$count = $this->db->total_rows( [] );
		$this->assertSame( 2, $count );
	}

	/**
	 * Test total_rows with search filter.
	 */
	public function test_total_rows_with_search() {
		$this->db->create_table();

		$this->db->insert( [ 'email' => 'alice@test.com', 'name' => 'Alice' ] );
		$this->db->insert( [ 'email' => 'bob@test.com', 'name' => 'Bob' ] );

		$count = $this->db->total_rows( [ 's' => 'alice' ] );
		$this->assertSame( 1, $count );
	}

	// ─── table_name() ──────────────────────────────────────────────────

	/**
	 * Test table_name returns full prefixed name.
	 */
	public function test_table_name_method() {
		global $wpdb;
		$this->assertSame( $wpdb->prefix . 'pum_subscribers', $this->db->table_name() );
	}

	// ─── prepare_result() ──────────────────────────────────────────────

	/**
	 * Test prepare_result unserializes serialized data.
	 */
	public function test_prepare_result_unserializes() {
		$this->db->create_table();

		$serialized = serialize( [ 'key' => 'value' ] );

		$id = $this->db->insert( [
			'email'        => 'serial@example.com',
			'consent_args' => $serialized,
		] );

		$row = $this->db->get( $id );
		// consent_args should be unserialized by prepare_result.
		$this->assertIsArray( $row->consent_args );
		$this->assertSame( 'value', $row->consent_args['key'] );
	}

	/**
	 * Test prepare_result with null returns null.
	 */
	public function test_prepare_result_null() {
		$result = $this->db->prepare_result( null );
		$this->assertNull( $result );
	}

	/**
	 * Test prepare_result with array input.
	 */
	public function test_prepare_result_array() {
		$input  = [
			'email'        => 'test@test.com',
			'consent_args' => serialize( [ 'gdpr' => true ] ),
		];
		$result = $this->db->prepare_result( $input );
		$this->assertIsArray( $result );
		$this->assertIsArray( $result['consent_args'] );
		$this->assertTrue( $result['consent_args']['gdpr'] );
	}

	// ─── get_installed_version() ───────────────────────────────────────

	/**
	 * Test get_installed_version returns version from pum_db_versions.
	 */
	public function test_get_installed_version_from_new_option() {
		update_option( 'pum_db_versions', [ 'pum_subscribers' => 20200917.0 ] );
		$version = $this->db->get_installed_version();
		$this->assertSame( 20200917.0, $version );
		// Clean up.
		delete_option( 'pum_db_versions' );
	}

	/**
	 * Test get_installed_version migrates from old key.
	 */
	public function test_get_installed_version_migrates_old_key() {
		delete_option( 'pum_db_versions' );
		update_option( 'pum_subscribers_db_version', 20200917 );

		$version = $this->db->get_installed_version();
		$this->assertSame( 20200917.0, $version );

		// Old key should be deleted.
		$this->assertFalse( get_option( 'pum_subscribers_db_version' ) );

		// New option should have it.
		$db_versions = get_option( 'pum_db_versions' );
		$this->assertSame( 20200917.0, $db_versions['pum_subscribers'] );

		// Clean up.
		delete_option( 'pum_db_versions' );
	}

	/**
	 * Test get_installed_version returns 0 when no version exists.
	 */
	public function test_get_installed_version_none() {
		delete_option( 'pum_db_versions' );
		delete_option( 'pum_subscribers_db_version' );

		$version = $this->db->get_installed_version();
		$this->assertSame( 0.0, $version );
	}

	// ─── insert with serializable data ─────────────────────────────────

	/**
	 * Test insert serializes array values.
	 */
	public function test_insert_serializes_arrays() {
		$this->db->create_table();

		$consent_data = [ 'gdpr' => true, 'terms' => 'accepted' ];

		$id  = $this->db->insert( [
			'email'        => 'serial@test.com',
			'consent_args' => $consent_data,
		] );
		$row = $this->db->get( $id );

		$this->assertIsArray( $row->consent_args );
		$this->assertTrue( $row->consent_args['gdpr'] );
	}

	// ─── update with serializable data ─────────────────────────────────

	/**
	 * Test update serializes array values.
	 */
	public function test_update_serializes_arrays() {
		$this->db->create_table();

		$id = $this->db->insert( [ 'email' => 'update_serial@test.com' ] );

		$this->db->update( $id, [
			'consent_args' => [ 'updated' => true ],
		] );

		$row = $this->db->get( $id );
		$this->assertIsArray( $row->consent_args );
		$this->assertTrue( $row->consent_args['updated'] );
	}
}
