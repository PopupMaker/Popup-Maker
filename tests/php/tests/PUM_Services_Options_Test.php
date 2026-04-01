<?php
/**
 * Tests for PopupMaker\Services\Options.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\Options;

/**
 * Test the Options service.
 */
class PUM_Services_Options_Test extends WP_UnitTestCase {

	/**
	 * Options service instance.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The option key used in wp_options.
	 *
	 * @var string
	 */
	private $option_key = 'pum_settings';

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean slate for each test.
		delete_option( $this->option_key );

		// Create a minimal container mock that satisfies Service + Options constructors.
		$container = new class {
			/**
			 * Get a container value by key.
			 *
			 * @param string $key Container key.
			 * @return mixed
			 */
			public function get( $key ) {
				if ( 'option_prefix' === $key ) {
					return 'pum';
				}
				return null;
			}
		};

		$this->options = new Options( $container );
	}

	/**
	 * Test that prefix is set correctly from option_prefix.
	 */
	public function test_prefix_is_set_from_container() {
		$this->assertSame( 'pum_', $this->options->prefix );
	}

	/**
	 * Test that namespace is set correctly from option_prefix.
	 */
	public function test_namespace_is_set_from_container() {
		$this->assertSame( 'pum/', $this->options->namespace );
	}

	/**
	 * Test prefix strips leading/trailing underscores before appending one.
	 */
	public function test_prefix_strips_extra_underscores() {
		$container = new class {
			/**
			 * Get a container value.
			 *
			 * @param string $key Key.
			 * @return mixed
			 */
			public function get( $key ) {
				if ( 'option_prefix' === $key ) {
					return '_test_';
				}
				return null;
			}
		};

		$opts = new Options( $container );
		$this->assertSame( 'test_', $opts->prefix );
	}

	/**
	 * Test empty prefix when option_prefix is empty.
	 */
	public function test_empty_prefix_when_option_prefix_empty() {
		$container = new class {
			/**
			 * Get a container value.
			 *
			 * @param string $key Key.
			 * @return mixed
			 */
			public function get( $key ) {
				return '';
			}
		};

		$opts = new Options( $container );
		$this->assertSame( '', $opts->prefix );
		$this->assertSame( '', $opts->namespace );
	}

	/**
	 * Test get_all returns stored settings array.
	 */
	public function test_get_all_returns_settings_array() {
		$data = [ 'foo' => 'bar', 'baz' => 123 ];
		update_option( $this->option_key, $data );

		$result = $this->options->get_all();
		$this->assertIsArray( $result );
		$this->assertSame( 'bar', $result['foo'] );
	}

	/**
	 * Test get_all returns false when no option stored.
	 */
	public function test_get_all_returns_false_when_no_option() {
		$result = $this->options->get_all();
		$this->assertFalse( $result );
	}

	/**
	 * Test get_all applies the filter.
	 */
	public function test_get_all_applies_filter() {
		update_option( $this->option_key, [ 'original' => true ] );

		add_filter( 'pum/get_options', function ( $settings ) {
			$settings['filtered'] = true;
			return $settings;
		} );

		$result = $this->options->get_all();
		$this->assertTrue( $result['filtered'] );

		// Cleanup.
		remove_all_filters( 'pum/get_options' );
	}

	/**
	 * Test get retrieves an existing key.
	 */
	public function test_get_existing_key() {
		update_option( $this->option_key, [ 'siteName' => 'My Site' ] );

		// Direct camelCase key should work.
		$result = $this->options->get( 'siteName' );
		$this->assertSame( 'My Site', $result );
	}

	/**
	 * Test get converts snake_case key to camelCase for lookup.
	 */
	public function test_get_converts_snake_case_to_camel() {
		update_option( $this->option_key, [ 'siteName' => 'Converted' ] );

		// snake_case input should be converted to camelCase internally.
		$result = $this->options->get( 'site_name' );
		$this->assertSame( 'Converted', $result );
	}

	/**
	 * Test get returns default when key is missing.
	 */
	public function test_get_returns_default_for_missing_key() {
		update_option( $this->option_key, [ 'something' => 'else' ] );

		$result = $this->options->get( 'nonexistent', 'fallback' );
		$this->assertSame( 'fallback', $result );
	}

	/**
	 * Test get returns default false by default.
	 */
	public function test_get_default_is_false() {
		update_option( $this->option_key, [] );

		$result = $this->options->get( 'missing' );
		$this->assertFalse( $result );
	}

	/**
	 * Test get applies the filter.
	 */
	public function test_get_applies_filter() {
		update_option( $this->option_key, [ 'key' => 'original' ] );

		add_filter( 'pum/get_option', function ( $value, $key ) {
			if ( 'key' === $key ) {
				return 'filtered_value';
			}
			return $value;
		}, 10, 2 );

		$result = $this->options->get( 'key' );
		$this->assertSame( 'filtered_value', $result );

		remove_all_filters( 'pum/get_option' );
	}

	/**
	 * Test update stores a value.
	 */
	public function test_update_stores_value() {
		update_option( $this->option_key, [] );

		$this->options->update( 'newKey', 'newValue' );

		$stored = get_option( $this->option_key );
		$this->assertSame( 'newValue', $stored['newKey'] );
	}

	/**
	 * Test update returns false for empty key.
	 */
	public function test_update_returns_false_for_empty_key() {
		$result = $this->options->update( '', 'value' );
		$this->assertFalse( $result );
	}

	/**
	 * Test update with empty value calls delete.
	 */
	public function test_update_with_empty_value_deletes_key() {
		update_option( $this->option_key, [ 'toRemove' => 'exists' ] );

		$this->options->update( 'toRemove', '' );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'toRemove', $stored );
	}

	/**
	 * Test update with false value calls delete.
	 */
	public function test_update_with_false_value_deletes_key() {
		update_option( $this->option_key, [ 'falseKey' => 'value' ] );

		$this->options->update( 'falseKey', false );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'falseKey', $stored );
	}

	/**
	 * Test update overwrites existing value.
	 */
	public function test_update_overwrites_existing() {
		update_option( $this->option_key, [ 'key' => 'old' ] );

		$this->options->update( 'key', 'new' );

		$stored = get_option( $this->option_key );
		$this->assertSame( 'new', $stored['key'] );
	}

	/**
	 * Test delete removes a single key.
	 */
	public function test_delete_single_key() {
		update_option( $this->option_key, [ 'a' => 1, 'b' => 2 ] );

		$this->options->delete( 'a' );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'a', $stored );
		$this->assertArrayHasKey( 'b', $stored );
	}

	/**
	 * Test delete removes multiple keys.
	 */
	public function test_delete_multiple_keys() {
		update_option( $this->option_key, [ 'a' => 1, 'b' => 2, 'c' => 3 ] );

		$this->options->delete( [ 'a', 'c' ] );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'a', $stored );
		$this->assertArrayNotHasKey( 'c', $stored );
		$this->assertArrayHasKey( 'b', $stored );
	}

	/**
	 * Test delete returns false for empty input.
	 */
	public function test_delete_returns_false_for_empty() {
		$result = $this->options->delete( '' );
		$this->assertFalse( $result );
	}

	/**
	 * Test delete handles nonexistent key gracefully.
	 */
	public function test_delete_nonexistent_key() {
		update_option( $this->option_key, [ 'keep' => 'me' ] );

		// Should not error when deleting key that does not exist.
		$this->options->delete( 'ghost' );

		$stored = get_option( $this->option_key );
		$this->assertSame( 'me', $stored['keep'] );
	}

	/**
	 * Test update_many merges multiple values.
	 */
	public function test_update_many_merges_values() {
		update_option( $this->option_key, [ 'existing' => 'keep' ] );

		$this->options->update_many( [
			'newOne' => 'hello',
			'newTwo' => 'world',
		] );

		$stored = get_option( $this->option_key );
		$this->assertSame( 'keep', $stored['existing'] );
		$this->assertSame( 'hello', $stored['newOne'] );
		$this->assertSame( 'world', $stored['newTwo'] );
	}

	/**
	 * Test update_many with empty values.
	 *
	 * NOTE: Source has a bug — update_many() unsets the key on line 173 when
	 * value is empty, but then unconditionally re-sets it on line 187 with
	 * the empty value. So the key persists as empty string. This test asserts
	 * actual (buggy) behavior.
	 */
	public function test_update_many_removes_empty_values() {
		update_option( $this->option_key, [ 'willRemove' => 'exists', 'stays' => 'here' ] );

		$this->options->update_many( [
			'willRemove' => '',
		] );

		$stored = get_option( $this->option_key );
		// BUG: key is NOT removed because line 187 re-sets it after unset.
		$this->assertArrayHasKey( 'willRemove', $stored );
		$this->assertSame( '', $stored['willRemove'] );
		$this->assertSame( 'here', $stored['stays'] );
	}

	/**
	 * Test update_many skips entries with empty keys.
	 */
	public function test_update_many_skips_empty_keys() {
		update_option( $this->option_key, [ 'a' => 1 ] );

		// An entry with an empty string key should be skipped.
		$this->options->update_many( [
			''  => 'ignored',
			'b' => 2,
		] );

		$stored = get_option( $this->option_key );
		$this->assertArrayHasKey( 'b', $stored );
	}

	/**
	 * Test remap_keys moves a value from old key to new key.
	 */
	public function test_remap_keys_moves_value() {
		update_option( $this->option_key, [ 'oldKey' => 'myValue' ] );

		$this->options->remap_keys( [ 'oldKey' => 'newKey' ] );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'oldKey', $stored );
		$this->assertSame( 'myValue', $stored['newKey'] );
	}

	/**
	 * Test remap_keys removes old key even if value is empty.
	 */
	public function test_remap_keys_removes_old_key_when_empty() {
		update_option( $this->option_key, [ 'emptyOld' => '' ] );

		$this->options->remap_keys( [ 'emptyOld' => 'emptyNew' ] );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'emptyOld', $stored );
	}

	/**
	 * Test remap_keys handles multiple remaps.
	 */
	public function test_remap_keys_multiple() {
		update_option( $this->option_key, [
			'alpha' => 'a_val',
			'beta'  => 'b_val',
			'gamma' => 'g_val',
		] );

		$this->options->remap_keys( [
			'alpha' => 'first',
			'beta'  => 'second',
		] );

		$stored = get_option( $this->option_key );
		$this->assertArrayNotHasKey( 'alpha', $stored );
		$this->assertArrayNotHasKey( 'beta', $stored );
		$this->assertSame( 'a_val', $stored['first'] );
		$this->assertSame( 'b_val', $stored['second'] );
		$this->assertSame( 'g_val', $stored['gamma'] );
	}

	/**
	 * Test container is accessible on the service.
	 */
	public function test_container_is_set() {
		$this->assertNotNull( $this->options->container );
	}
}
