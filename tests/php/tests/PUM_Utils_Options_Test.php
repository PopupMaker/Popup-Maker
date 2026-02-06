<?php
/**
 * Tests for PUM_Utils_Options.
 *
 * @package Popup_Maker
 */

/**
 * Test the Options utility class.
 */
class PUM_Utils_Options_Test extends WP_UnitTestCase {

	/**
	 * Reset the static $data cache before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Clear static cache via reflection.
		$ref = new ReflectionProperty( 'PUM_Utils_Options', 'data' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
		// Clean up the option entirely.
		delete_option( 'popmake_settings' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$ref = new ReflectionProperty( 'PUM_Utils_Options', 'data' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
		delete_option( 'popmake_settings' );
		parent::tearDown();
	}

	// ─── init() ────────────────────────────────────────────────────────

	/**
	 * Test init populates static cache from wp_options.
	 */
	public function test_init_populates_cache() {
		update_option( 'popmake_settings', [ 'key1' => 'value1' ] );
		PUM_Utils_Options::init( true );

		$this->assertSame( 'value1', PUM_Utils_Options::get( 'key1' ) );
	}

	/**
	 * Test init without force does not reload.
	 */
	public function test_init_without_force_uses_cache() {
		update_option( 'popmake_settings', [ 'key1' => 'original' ] );
		PUM_Utils_Options::init( true );

		// Change the DB value behind the scenes.
		update_option( 'popmake_settings', [ 'key1' => 'changed' ] );
		PUM_Utils_Options::init( false );

		// Should still have cached value.
		$this->assertSame( 'original', PUM_Utils_Options::get( 'key1' ) );
	}

	/**
	 * Test init with force reloads from DB.
	 */
	public function test_init_force_reloads_from_db() {
		update_option( 'popmake_settings', [ 'key1' => 'original' ] );
		PUM_Utils_Options::init( true );

		update_option( 'popmake_settings', [ 'key1' => 'changed' ] );
		PUM_Utils_Options::init( true );

		$this->assertSame( 'changed', PUM_Utils_Options::get( 'key1' ) );
	}

	/**
	 * Test init sets the global $popmake_options variable.
	 */
	public function test_init_sets_global_variable() {
		global $popmake_options;
		update_option( 'popmake_settings', [ 'legacy' => 'test' ] );
		PUM_Utils_Options::init( true );

		$this->assertIsArray( $popmake_options );
		$this->assertSame( 'test', $popmake_options['legacy'] );
	}

	// ─── get_all() ─────────────────────────────────────────────────────

	/**
	 * Test get_all returns all stored settings.
	 */
	public function test_get_all_returns_settings() {
		$settings = [
			'key1' => 'value1',
			'key2' => 42,
		];
		update_option( 'popmake_settings', $settings );

		$result = PUM_Utils_Options::get_all();

		$this->assertIsArray( $result );
		$this->assertSame( 'value1', $result['key1'] );
		$this->assertSame( 42, $result['key2'] );
	}

	/**
	 * Test get_all returns empty array when no option exists.
	 */
	public function test_get_all_returns_empty_array_when_no_option() {
		$result = PUM_Utils_Options::get_all();
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_all returns empty array when option is not an array.
	 */
	public function test_get_all_returns_empty_array_for_non_array_option() {
		update_option( 'popmake_settings', 'not_an_array' );
		$result = PUM_Utils_Options::get_all();
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ─── get() ─────────────────────────────────────────────────────────

	/**
	 * Test get returns value for existing key.
	 */
	public function test_get_returns_existing_value() {
		update_option( 'popmake_settings', [ 'color' => 'red' ] );
		$this->assertSame( 'red', PUM_Utils_Options::get( 'color' ) );
	}

	/**
	 * Test get returns default when key does not exist.
	 */
	public function test_get_returns_default_for_missing_key() {
		update_option( 'popmake_settings', [] );
		$this->assertSame( 'blue', PUM_Utils_Options::get( 'missing', 'blue' ) );
	}

	/**
	 * Test get returns false as default when no default specified.
	 */
	public function test_get_default_is_false() {
		$this->assertFalse( PUM_Utils_Options::get( 'nope' ) );
	}

	/**
	 * Test get with empty key returns default.
	 */
	public function test_get_empty_key_returns_default() {
		update_option( 'popmake_settings', [ 'a' => 1 ] );
		$this->assertSame( 'fallback', PUM_Utils_Options::get( '', 'fallback' ) );
	}

	// ─── update() ──────────────────────────────────────────────────────

	/**
	 * Test update stores a new value.
	 */
	public function test_update_stores_value() {
		PUM_Utils_Options::update( 'font_size', '16px' );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( '16px', $all['font_size'] );
	}

	/**
	 * Test update overwrites existing value.
	 */
	public function test_update_overwrites_existing() {
		update_option( 'popmake_settings', [ 'font_size' => '14px' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::update( 'font_size', '18px' );
		$this->assertSame( '18px', PUM_Utils_Options::get( 'font_size' ) );
	}

	/**
	 * Test update with empty key returns false.
	 */
	public function test_update_empty_key_returns_false() {
		$result = PUM_Utils_Options::update( '' );
		$this->assertFalse( $result );
	}

	/**
	 * Test update with empty value triggers delete.
	 */
	public function test_update_empty_value_deletes_key() {
		update_option( 'popmake_settings', [ 'to_delete' => 'exists' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::update( 'to_delete', '' );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayNotHasKey( 'to_delete', $all );
	}

	/**
	 * Test update with false value triggers delete.
	 */
	public function test_update_false_value_deletes_key() {
		update_option( 'popmake_settings', [ 'flag' => 'on' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::update( 'flag', false );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayNotHasKey( 'flag', $all );
	}

	/**
	 * Test update with null value triggers delete.
	 */
	public function test_update_null_value_deletes_key() {
		update_option( 'popmake_settings', [ 'nullable' => 'value' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::update( 'nullable', null );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayNotHasKey( 'nullable', $all );
	}

	/**
	 * Test update with zero value triggers delete (0 is empty).
	 */
	public function test_update_zero_value_deletes_key() {
		update_option( 'popmake_settings', [ 'count' => 5 ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::update( 'count', 0 );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayNotHasKey( 'count', $all );
	}

	/**
	 * Test update updates the static cache.
	 */
	public function test_update_refreshes_static_cache() {
		PUM_Utils_Options::update( 'cached', 'fresh_value' );
		$this->assertSame( 'fresh_value', PUM_Utils_Options::get( 'cached' ) );
	}

	/**
	 * Test update with array value.
	 */
	public function test_update_with_array_value() {
		PUM_Utils_Options::update( 'nested', [ 'a' => 1, 'b' => 2 ] );
		$result = PUM_Utils_Options::get( 'nested' );
		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['a'] );
	}

	// ─── update_all() ──────────────────────────────────────────────────

	/**
	 * Test update_all replaces all options with merge.
	 */
	public function test_update_all_merges_with_existing() {
		update_option( 'popmake_settings', [
			'existing' => 'keep',
			'old'      => 'value',
		] );

		$result = PUM_Utils_Options::update_all( [
			'old' => 'new_value',
			'new' => 'added',
		] );

		$this->assertTrue( $result );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 'keep', $all['existing'] );
		$this->assertSame( 'new_value', $all['old'] );
		$this->assertSame( 'added', $all['new'] );
	}

	/**
	 * Test update_all updates the static cache.
	 */
	public function test_update_all_updates_cache() {
		PUM_Utils_Options::update_all( [ 'fresh' => 'data' ] );
		PUM_Utils_Options::init( true );

		$this->assertSame( 'data', PUM_Utils_Options::get( 'fresh' ) );
	}

	/**
	 * Test update_all with empty array preserves existing.
	 */
	public function test_update_all_empty_array_preserves_existing() {
		update_option( 'popmake_settings', [ 'keep' => 'this' ] );

		PUM_Utils_Options::update_all( [] );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 'this', $all['keep'] );
	}

	// ─── merge() ───────────────────────────────────────────────────────

	/**
	 * Test merge adds new options.
	 */
	public function test_merge_adds_new_options() {
		update_option( 'popmake_settings', [ 'a' => 1 ] );

		PUM_Utils_Options::merge( [ 'b' => 2 ] );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 1, $all['a'] );
		$this->assertSame( 2, $all['b'] );
	}

	/**
	 * Test merge with empty value sets it to false.
	 */
	public function test_merge_empty_value_becomes_false() {
		update_option( 'popmake_settings', [ 'a' => 1 ] );

		PUM_Utils_Options::merge( [ 'empty_val' => '' ] );

		$all = get_option( 'popmake_settings' );
		$this->assertFalse( $all['empty_val'] );
	}

	/**
	 * Test merge with zero value becomes false.
	 */
	public function test_merge_zero_value_becomes_false() {
		PUM_Utils_Options::merge( [ 'zero' => 0 ] );

		$all = get_option( 'popmake_settings' );
		$this->assertFalse( $all['zero'] );
	}

	/**
	 * Test merge with null value becomes false.
	 */
	public function test_merge_null_value_becomes_false() {
		PUM_Utils_Options::merge( [ 'nil' => null ] );

		$all = get_option( 'popmake_settings' );
		$this->assertFalse( $all['nil'] );
	}

	/**
	 * Test merge overwrites existing with non-empty value.
	 */
	public function test_merge_overwrites_with_non_empty_value() {
		update_option( 'popmake_settings', [ 'color' => 'red' ] );

		PUM_Utils_Options::merge( [ 'color' => 'blue' ] );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 'blue', $all['color'] );
	}

	/**
	 * Test merge updates the static cache.
	 */
	public function test_merge_updates_cache() {
		PUM_Utils_Options::merge( [ 'cached_merge' => 'yes' ] );
		PUM_Utils_Options::init( true );

		$this->assertSame( 'yes', PUM_Utils_Options::get( 'cached_merge' ) );
	}

	// ─── delete() ──────────────────────────────────────────────────────

	/**
	 * Test delete removes a single key.
	 */
	public function test_delete_single_key() {
		update_option( 'popmake_settings', [
			'keep'   => 'yes',
			'remove' => 'this',
		] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::delete( 'remove' );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayHasKey( 'keep', $all );
		$this->assertArrayNotHasKey( 'remove', $all );
	}

	/**
	 * Test delete removes multiple keys.
	 */
	public function test_delete_multiple_keys() {
		update_option( 'popmake_settings', [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::delete( [ 'a', 'c' ] );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayNotHasKey( 'a', $all );
		$this->assertArrayHasKey( 'b', $all );
		$this->assertArrayNotHasKey( 'c', $all );
	}

	/**
	 * Test delete with empty key returns false.
	 */
	public function test_delete_empty_key_returns_false() {
		$this->assertFalse( PUM_Utils_Options::delete( '' ) );
	}

	/**
	 * Test delete with non-existent key still succeeds.
	 */
	public function test_delete_nonexistent_key() {
		update_option( 'popmake_settings', [ 'existing' => 'value' ] );
		PUM_Utils_Options::init( true );

		// Should not throw, key just does not exist in options.
		$result = PUM_Utils_Options::delete( 'nonexistent' );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayHasKey( 'existing', $all );
	}

	/**
	 * Test delete updates the static cache.
	 */
	public function test_delete_updates_cache() {
		update_option( 'popmake_settings', [ 'temp' => 'value' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::delete( 'temp' );

		$this->assertFalse( PUM_Utils_Options::get( 'temp' ) );
	}

	// ─── remap_keys() ─────────────────────────────────────────────────

	/**
	 * Test remap_keys renames keys correctly.
	 */
	public function test_remap_keys_renames() {
		update_option( 'popmake_settings', [
			'old_name'  => 'value1',
			'old_color' => 'blue',
		] );

		PUM_Utils_Options::remap_keys( [
			'old_name'  => 'new_name',
			'old_color' => 'color',
		] );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayHasKey( 'new_name', $all );
		$this->assertArrayHasKey( 'color', $all );
		$this->assertArrayNotHasKey( 'old_name', $all );
		$this->assertArrayNotHasKey( 'old_color', $all );
		$this->assertSame( 'value1', $all['new_name'] );
	}

	/**
	 * Test remap_keys with non-existent old key removes the old key entry.
	 */
	public function test_remap_keys_nonexistent_old_key() {
		update_option( 'popmake_settings', [ 'existing' => 'stay' ] );

		PUM_Utils_Options::remap_keys( [ 'ghost' => 'new_ghost' ] );

		$all = get_option( 'popmake_settings' );
		$this->assertArrayHasKey( 'existing', $all );
		// The new key should NOT be set since old key did not exist.
		$this->assertArrayNotHasKey( 'new_ghost', $all );
	}

	/**
	 * Test remap_keys with empty array does not change options.
	 */
	public function test_remap_keys_empty_array() {
		update_option( 'popmake_settings', [ 'key' => 'val' ] );

		PUM_Utils_Options::remap_keys( [] );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 'val', $all['key'] );
	}

	/**
	 * Test remap_keys updates the static cache.
	 */
	public function test_remap_keys_updates_cache() {
		update_option( 'popmake_settings', [ 'old_key' => 'cached' ] );
		PUM_Utils_Options::init( true );

		PUM_Utils_Options::remap_keys( [ 'old_key' => 'new_key' ] );
		PUM_Utils_Options::init( true );

		$this->assertSame( 'cached', PUM_Utils_Options::get( 'new_key' ) );
		$this->assertFalse( PUM_Utils_Options::get( 'old_key' ) );
	}

	// ─── prefix ────────────────────────────────────────────────────────

	/**
	 * Test that the static prefix is correct.
	 */
	public function test_prefix_is_popmake() {
		$this->assertSame( 'popmake_', PUM_Utils_Options::$prefix );
	}

	// ─── filter integration ────────────────────────────────────────────

	/**
	 * Test that get_all fires the popmake_get_options filter.
	 */
	public function test_get_all_fires_filter() {
		add_filter( 'popmake_get_options', function ( $settings ) {
			$settings['injected'] = 'by_filter';
			return $settings;
		} );

		$result = PUM_Utils_Options::get_all();
		$this->assertSame( 'by_filter', $result['injected'] );

		// Clean up filter.
		remove_all_filters( 'popmake_get_options' );
	}

	/**
	 * Test that get fires the popmake_get_option filter.
	 */
	public function test_get_fires_option_filter() {
		update_option( 'popmake_settings', [ 'filtered' => 'original' ] );

		add_filter( 'popmake_get_option', function ( $value, $key ) {
			if ( 'filtered' === $key ) {
				return 'modified';
			}
			return $value;
		}, 10, 2 );

		$result = PUM_Utils_Options::get( 'filtered' );
		$this->assertSame( 'modified', $result );

		remove_all_filters( 'popmake_get_option' );
	}

	/**
	 * Test that update fires the popmake_update_option filter.
	 */
	public function test_update_fires_update_filter() {
		add_filter( 'popmake_update_option', function ( $value, $key ) {
			if ( 'transform_me' === $key ) {
				return strtoupper( $value );
			}
			return $value;
		}, 10, 2 );

		PUM_Utils_Options::update( 'transform_me', 'lowercase' );

		$all = get_option( 'popmake_settings' );
		$this->assertSame( 'LOWERCASE', $all['transform_me'] );

		remove_all_filters( 'popmake_update_option' );
	}
}
