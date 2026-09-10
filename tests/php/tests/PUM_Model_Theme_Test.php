<?php
/**
 * Tests for the popup theme model.
 *
 * @package Popup_Maker
 */

/**
 * Test popup theme settings behavior.
 */
class PUM_Model_Theme_Test extends WP_UnitTestCase {

	/**
	 * Raw theme settings are read once while filters still run for every access.
	 */
	public function test_get_settings_caches_meta_and_reapplies_filter() {
		$theme_id = self::factory()->post->create( [
			'post_type'   => 'popup_theme',
			'post_status' => 'publish',
		] );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$metadata_reads = 0;
		$filter_calls   = 0;

		$metadata_filter = function ( $value, $object_id, $meta_key ) use ( $theme_id, &$metadata_reads ) {
			if ( $theme_id === $object_id && 'popup_theme_settings' === $meta_key ) {
				++$metadata_reads;
			}

			return $value;
		};

		$settings_filter = function ( $settings ) use ( &$filter_calls ) {
			++$filter_calls;
			$settings['filter_call'] = $filter_calls;

			return $settings;
		};

		add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );
		add_filter( 'pum_theme_settings', $settings_filter );

		$theme  = new PUM_Model_Theme( $theme_id );
		$first  = $theme->get_settings();
		$second = $theme->get_settings();

		remove_filter( 'get_post_metadata', $metadata_filter, 10 );
		remove_filter( 'pum_theme_settings', $settings_filter );

		$this->assertSame( 2, $metadata_reads );
		$this->assertSame( 1, $first['filter_call'] );
		$this->assertSame( 2, $second['filter_call'] );
		$this->assertSame( 20, $second['container_padding'] );
	}

	/**
	 * Updating one setting keeps the per-model cache synchronized.
	 */
	public function test_update_setting_refreshes_cached_settings() {
		$theme_id = self::factory()->post->create( [
			'post_type'   => 'popup_theme',
			'post_status' => 'publish',
		] );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$theme = new PUM_Model_Theme( $theme_id );
		$theme->get_settings();
		$theme->update_setting( 'container_padding', 30 );

		$this->assertSame( 30, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( 30, get_post_meta( $theme_id, 'popup_theme_settings', true )['container_padding'] );
	}

	/**
	 * Passive migrations and extensions write popup_theme_settings via
	 * update_meta() directly — the instance cache must not serve stale
	 * pre-write settings afterward.
	 *
	 * @return void
	 */
	public function test_direct_update_meta_refreshes_cached_settings() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$theme = new PUM_Model_Theme( $theme_id );
		$theme->get_settings();

		// Mirrors pum_theme_migration_2()'s write path.
		$theme->update_meta( 'popup_theme_settings', [ 'container_padding' => 99 ] );

		$this->assertSame( 99, $theme->get_setting( 'container_padding' ) );

		$theme->delete_meta( 'popup_theme_settings' );

		$this->assertSame( [], $theme->get_settings() );
	}

	/**
	 * The settings cache reflects the value WordPress stores after unslashing.
	 *
	 * @return void
	 */
	public function test_update_settings_caches_stored_normalized_value() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$theme    = new PUM_Model_Theme( $theme_id );

		$theme->update_settings( [ 'close_text' => "Don\\'t close" ] );

		$stored_settings = get_post_meta( $theme_id, 'popup_theme_settings', true );

		$this->assertSame( "Don't close", $stored_settings['close_text'] );
		$this->assertSame( $stored_settings, $theme->get_settings() );
	}

	/**
	 * Native metadata writes invalidate settings cached by an existing model.
	 *
	 * @return void
	 */
	public function test_native_meta_writes_invalidate_cached_settings() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$theme = new PUM_Model_Theme( $theme_id );
		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 40 ] );
		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );

		delete_post_meta( $theme_id, 'popup_theme_settings' );
		$this->assertSame( [], $theme->get_settings() );
	}

	/**
	 * External database writes become visible after metadata cache eviction.
	 *
	 * @return void
	 */
	public function test_object_cache_eviction_refreshes_cached_settings() {
		global $wpdb;

		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$theme = new PUM_Model_Theme( $theme_id );
		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an external database write before cache eviction.
			$wpdb->postmeta,
			[ 'meta_value' => maybe_serialize( [ 'container_padding' => 40 ] ) ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			[
				'post_id'  => $theme_id,
				'meta_key' => 'popup_theme_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			]
		);
		wp_cache_delete( $theme_id, 'post_meta' );
		update_meta_cache( 'post', [ $theme_id ] );

		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an external database write before cache eviction.
			$wpdb->postmeta,
			[ 'meta_value' => maybe_serialize( [ 'container_padding' => 60 ] ) ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			[
				'post_id'  => $theme_id,
				'meta_key' => 'popup_theme_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			]
		);
		clean_post_cache( $theme_id );

		$this->assertSame( 60, $theme->get_setting( 'container_padding' ) );
	}

	/**
	 * Extension theme subclasses retain remapped metadata behavior.
	 *
	 * @return void
	 */
	public function test_theme_subclasses_reapply_remapped_settings_metadata() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$theme    = new class( $theme_id ) extends PUM_Model_Theme {

			/** @var int */
			public $remapped_padding = 20;

			/**
			 * @param string $key Meta key.
			 * @return array<string,int>|false
			 */
			public function remapped_meta( $key = '' ) {
				if ( 'popup_theme_settings' === $key ) {
					return [ 'container_padding' => $this->remapped_padding ];
				}

				return parent::remapped_meta( $key );
			}
		};

		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 20 ], $theme->settings );

		$theme->remapped_padding = 40;

		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 40 ], $theme->settings );
	}

	/**
	 * The same model does not reuse settings across multisite blog switches.
	 *
	 * @return void
	 */
	public function test_settings_cache_is_partitioned_by_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Stored multisite settings require the WordPress multisite test suite.' );
		}

		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$blog_id  = self::factory()->blog->create();

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$theme = new PUM_Model_Theme( $theme_id );
		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );

		try {
			switch_to_blog( $blog_id );
			update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 40 ] );
			$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );
		} finally {
			restore_current_blog();
		}

		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );
	}

	/**
	 * Metadata observers see the stored value even when registered first.
	 *
	 * @return void
	 */
	public function test_native_meta_cache_invalidates_before_observers() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$theme    = null;
		$observed = null;

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );

		$observer = function ( $meta_id, $object_id, $meta_key ) use ( $theme_id, &$theme, &$observed ) {
			if ( $theme_id === (int) $object_id && 'popup_theme_settings' === $meta_key ) {
				$observed = $theme->get_setting( 'container_padding' );
			}
		};

		add_action( 'updated_post_meta', $observer, -1000, 3 );

		$theme = new PUM_Model_Theme( $theme_id );
		$theme->get_settings();
		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 40 ] );

		remove_action( 'updated_post_meta', $observer, -1000 );

		$this->assertSame( 40, $observed );
	}

	/**
	 * Dynamic WordPress metadata overrides are evaluated on every settings read.
	 *
	 * @return void
	 */
	public function test_get_settings_reapplies_dynamic_metadata_overrides() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$padding  = 20;

		update_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 10 ] );

		$metadata_filter = function ( $value, $object_id, $meta_key ) use ( $theme_id, &$padding ) {
			if ( $theme_id === $object_id && 'popup_theme_settings' === $meta_key ) {
				return [ [ 'container_padding' => $padding ] ];
			}

			return $value;
		};

		$theme = new PUM_Model_Theme( $theme_id );
		$this->assertSame( 10, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 10 ], $theme->settings );

		add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );

		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 20 ], $theme->settings );

		$padding = 40;
		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 40 ], $theme->settings );

		remove_filter( 'get_post_metadata', $metadata_filter, 10 );

		$this->assertSame( 10, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 10 ], $theme->settings );
	}

	/**
	 * WordPress metadata defaults are preserved when no settings row exists.
	 *
	 * @return void
	 */
	public function test_get_settings_reapplies_metadata_defaults() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$padding  = 20;

		$default_filter = function ( $value, $object_id, $meta_key ) use ( $theme_id, &$padding ) {
			if ( $theme_id === $object_id && 'popup_theme_settings' === $meta_key ) {
				return [ 'container_padding' => $padding ];
			}

			return $value;
		};

		add_filter( 'default_post_metadata', $default_filter, 10, 3 );

		$theme = new PUM_Model_Theme( $theme_id );

		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 20 ], $theme->settings );

		$padding = 40;
		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );
		$this->assertSame( [ 'container_padding' => 40 ], $theme->settings );

		remove_filter( 'default_post_metadata', $default_filter, 10 );
	}

	/**
	 * Metadata writes by meta ID fire the registered invalidation hooks.
	 *
	 * @return void
	 */
	public function test_by_mid_meta_writes_invalidate_cached_settings() {
		$theme_id = self::factory()->post->create( [ 'post_type' => 'popup_theme' ] );
		$meta_id  = add_post_meta( $theme_id, 'popup_theme_settings', [ 'container_padding' => 20 ] );
		$theme    = new PUM_Model_Theme( $theme_id );

		$this->assertSame( 20, $theme->get_setting( 'container_padding' ) );
		$this->assertTrue( update_metadata_by_mid( 'post', $meta_id, [ 'container_padding' => 40 ] ) );
		$this->assertSame( 40, $theme->get_setting( 'container_padding' ) );
		$this->assertTrue( delete_metadata_by_mid( 'post', $meta_id ) );
		$this->assertSame( [], $theme->get_settings() );
	}
}
