<?php
/**
 * Tests for the frontend popups controller.
 *
 * @package Popup_Maker
 */

/**
 * Test frontend popup model reuse.
 */
class Frontend_Popups_Controller_Test extends WP_UnitTestCase {

	/**
	 * Legacy getters reuse models already queried by the frontend controller.
	 */
	public function test_legacy_getter_reuses_queried_popup_model() {
		$popup_id = self::factory()->post->create( [
			'post_type'   => 'popup',
			'post_status' => 'publish',
		] );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$controller->preload_popups();
		$queried_popup = $controller->get_queried_popup( $popup_id );

		$this->assertInstanceOf( PUM_Model_Popup::class, $queried_popup );
		$this->assertSame( $queried_popup, pum_get_popup( $popup_id ) );
	}

	/**
	 * Models requested before preloading remain canonical afterward.
	 *
	 * @return void
	 */
	public function test_preload_preserves_model_requested_by_legacy_getter() {
		$popup_id = self::factory()->post->create( [
			'post_type'   => 'popup',
			'post_status' => 'publish',
		] );

		$popup           = pum_get_popup( $popup_id );
		$popup->settings = [ 'custom_request_value' => 'preserved' ];

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$controller->preload_popups();

		$this->assertSame( $popup, $controller->get_queried_popup( $popup_id ) );
		$this->assertSame( $popup, pum_get_popup( $popup_id ) );
		$this->assertSame( $popup, \PopupMaker\plugin()->get( 'popups' )->get_by_id( $popup_id ) );
		$this->assertSame( 'preserved', pum_get_popup( $popup_id )->settings['custom_request_value'] );
	}

	/**
	 * Extensions may retain the original protected cache hook visibility.
	 *
	 * @return void
	 */
	public function test_extension_repository_can_override_protected_cache_item() {
		$repository = new class( \PopupMaker\plugin() ) extends \PopupMaker\Services\Repository\Popups {

			/**
			 * @param PUM_Model_Popup $item Popup model.
			 *
			 * @return void
			 */
			protected function cache_item( $item ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Proves protected visibility remains compatible.
				parent::cache_item( $item );
			}
		};
		$popup_id   = self::factory()->post->create( [ 'post_type' => 'popup' ] );
		$popup      = new PUM_Model_Popup( $popup_id );

		$repository->replace_cached_item( $popup );

		$this->assertSame( $popup, $repository->get_by_id( $popup_id ) );
	}

	/**
	 * Modern repository popup models are isolated by site.
	 *
	 * @return void
	 */
	public function test_modern_repository_popup_models_are_partitioned_by_blog() {
		$popup_id   = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$blog_id    = get_current_blog_id();
		$repository = \PopupMaker\plugin()->get( 'popups' );

		$metadata_filter = function ( $value, $object_id, $meta_key ) use ( $popup_id, $blog_id ) {
			if ( $popup_id !== $object_id || 'popup_settings' !== $meta_key ) {
				return $value;
			}

			return [ [ 'animation_speed' => get_current_blog_id() === $blog_id ? 200 : 400 ] ];
		};

		add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );

		$first = $repository->get_by_id( $popup_id );
		$this->assertSame( 200, $first->get_setting( 'animation_speed' ) );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;

			$second = $repository->get_by_id( $popup_id );

			$this->assertNotSame( $first, $second );
			$this->assertSame( 400, $second->get_setting( 'animation_speed' ) );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
			remove_filter( 'get_post_metadata', $metadata_filter, 10 );
		}

		$this->assertSame( $first, $repository->get_by_id( $popup_id ) );
	}

	/**
	 * Canonical popup models are isolated by site in multisite requests.
	 *
	 * @return void
	 */
	public function test_queried_popup_cache_is_partitioned_by_blog() {
		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$popup_id   = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$first      = new PUM_Model_Popup( $popup_id );
		$second     = new PUM_Model_Popup( $popup_id );
		$blog_id    = get_current_blog_id();

		$controller->cache_queried_popup( $first );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;

			$this->assertNull( $controller->get_queried_popup( $popup_id ) );

			$controller->cache_queried_popup( $second );

			$this->assertSame( $second, $controller->get_queried_popup( $popup_id ) );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
		}

		$this->assertSame( $first, $controller->get_queried_popup( $popup_id ) );
	}

	/**
	 * Legacy repository models cannot carry settings between sites.
	 *
	 * @return void
	 */
	public function test_legacy_repository_popup_models_are_partitioned_by_blog() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$blog_id  = get_current_blog_id();

		$metadata_filter = function ( $value, $object_id, $meta_key ) use ( $popup_id, $blog_id ) {
			if ( $popup_id !== $object_id || 'popup_settings' !== $meta_key ) {
				return $value;
			}

			return [ [ 'animation_speed' => get_current_blog_id() === $blog_id ? 200 : 400 ] ];
		};

		add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );

		$first = pum_get_popup( $popup_id );
		$this->assertSame( 200, $first->get_setting( 'animation_speed' ) );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;

			$second = pum_get_popup( $popup_id );

			$this->assertNotSame( $first, $second );
			$this->assertSame( 400, $second->get_setting( 'animation_speed' ) );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
			remove_filter( 'get_post_metadata', $metadata_filter, 10 );
		}

		$this->assertSame( $first, pum_get_popup( $popup_id ) );
		$this->assertSame( 200, $first->get_setting( 'animation_speed' ) );
	}

	/**
	 * Native post changes discard stale canonical popup post data.
	 *
	 * @return void
	 */
	public function test_native_post_changes_invalidate_queried_popup() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Original title',
			]
		);

		$original = pum_get_popup( $popup_id );

		wp_update_post(
			[
				'ID'         => $popup_id,
				'post_title' => 'Updated title',
			]
		);

		$updated = pum_get_popup( $popup_id );

		$this->assertNotSame( $original, $updated );
		$this->assertSame( 'Updated title', $updated->post_title );
	}

	/**
	 * Reprimed WordPress post caches cannot preserve stale canonical post data.
	 *
	 * @return void
	 */
	public function test_direct_post_write_and_cache_reprime_refreshes_canonical_popup() {
		global $wpdb;

		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Original title',
			]
		);
		$original = pum_get_popup( $popup_id );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an importer writing before cache eviction.
			$wpdb->posts,
			[ 'post_title' => 'Imported title' ],
			[ 'ID' => $popup_id ]
		);
		wp_cache_delete( $popup_id, 'posts' );
		get_post( $popup_id );

		$updated = pum_get_popup( $popup_id );

		$this->assertNotSame( $original, $updated );
		$this->assertSame( 'Imported title', $updated->post_title );
	}

	/**
	 * Native cache cleaning evicts both repositories for the current site.
	 *
	 * @return void
	 */
	public function test_clean_post_cache_evicts_current_site_repository_models() {
		$popup_id   = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$blog_id    = get_current_blog_id();
		$repository = \PopupMaker\plugin()->get( 'popups' );
		$first      = $repository->get_by_id( $popup_id );
		$legacy     = pum()->popups->get_item( $popup_id );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;
			$other_site         = $repository->get_by_id( $popup_id );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
		}

		clean_post_cache( $popup_id );

		$this->assertNotSame( $first, $repository->get_by_id( $popup_id ) );
		$this->assertNotSame( $legacy, pum()->popups->get_item( $popup_id ) );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;
			$this->assertSame( $other_site, $repository->get_by_id( $popup_id ) );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
		}
	}

	/**
	 * Legacy popup lookup tolerates an unavailable frontend controller.
	 *
	 * @return void
	 */
	public function test_legacy_getter_handles_missing_frontend_controller() {
		$popup_id   = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$plugin     = \PopupMaker\plugin();
		$controller = $plugin->get_controller( 'Frontend\\Popups' );

		try {
			unset( $plugin->controllers['Frontend\\Popups'] );

			$this->assertNull( $plugin->get_controller( 'Frontend\\Popups' ) );
			$this->assertInstanceOf( PUM_Model_Popup::class, pum_get_popup( $popup_id ) );
		} finally {
			$plugin->controllers['Frontend\\Popups'] = $controller;
		}
	}

	/**
	 * Native settings writes refresh the canonical model before preloading.
	 *
	 * @return void
	 */
	public function test_native_popup_settings_writes_refresh_canonical_model() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$popup = pum_get_popup( $popup_id );
		$this->assertSame( 200, $popup->get_setting( 'animation_speed' ) );

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 400 ] );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$controller->preload_popups();

		$this->assertSame( $popup, $controller->get_queried_popup( $popup_id ) );
		$this->assertSame( 400, $popup->get_setting( 'animation_speed' ) );
	}

	/**
	 * Native settings writes evict modern models not cached by the controller.
	 *
	 * @return void
	 */
	public function test_native_popup_settings_writes_evict_repository_only_model() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$repository = \PopupMaker\plugin()->get( 'popups' );
		$original   = $repository->get_by_id( $popup_id );

		$this->assertSame( 200, $original->get_setting( 'animation_speed' ) );

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 400 ] );

		$updated = $repository->get_by_id( $popup_id );

		$this->assertNotSame( $original, $updated );
		$this->assertSame( 400, $updated->get_setting( 'animation_speed' ) );
	}

	/**
	 * Native settings writes evict legacy models not cached by the controller.
	 *
	 * @return void
	 */
	public function test_native_popup_settings_writes_evict_legacy_repository_only_model() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$original = pum()->popups->get_item( $popup_id );

		$this->assertSame( 200, $original->get_setting( 'animation_speed' ) );

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 400 ] );

		$updated = pum_get_popup( $popup_id );

		$this->assertNotSame( $original, $updated );
		$this->assertSame( 400, $updated->get_setting( 'animation_speed' ) );
	}

	/**
	 * Reprimed WordPress metadata refreshes canonical popup settings.
	 *
	 * @return void
	 */
	public function test_preload_refreshes_canonical_settings_after_meta_cache_eviction() {
		global $wpdb;

		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$popup = pum_get_popup( $popup_id );
		$this->assertSame( 200, $popup->get_setting( 'animation_speed' ) );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an importer writing before cache eviction.
			$wpdb->postmeta,
			[ 'meta_value' => maybe_serialize( [ 'animation_speed' => 400 ] ) ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			[
				'post_id'  => $popup_id,
				'meta_key' => 'popup_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			]
		);
		wp_cache_delete( $popup_id, 'post_meta' );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$controller->preload_popups();

		$this->assertSame( $popup, $controller->get_queried_popup( $popup_id ) );
		$this->assertSame( 400, $popup->get_setting( 'animation_speed' ) );
	}

	/**
	 * A newly inserted settings row refreshes a model that first observed no row.
	 *
	 * @return void
	 */
	public function test_absent_settings_row_refreshes_after_direct_insert_and_cache_reprime() {
		global $wpdb;

		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$popup    = pum_get_popup( $popup_id );

		$this->assertFalse( $popup->get_setting( 'animation_speed' ) );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an importer creating metadata directly.
			$wpdb->postmeta,
			[
				'post_id'    => $popup_id,
				'meta_key'   => 'popup_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => maybe_serialize( [ 'animation_speed' => 400 ] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		wp_cache_delete( $popup_id, 'post_meta' );
		update_meta_cache( 'post', [ $popup_id ] );

		$this->assertSame( 400, $popup->get_setting( 'animation_speed' ) );
	}

	/**
	 * Model writes refresh provenance before a later direct metadata update.
	 *
	 * @return void
	 */
	public function test_update_setting_refreshes_provenance_before_direct_write() {
		global $wpdb;

		$popup_id   = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$popup      = pum_get_popup( $popup_id );
		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$callback   = [ $controller, 'invalidate_queried_popup_settings' ];

		remove_action( 'added_post_meta', $callback, PHP_INT_MIN );
		remove_action( 'updated_post_meta', $callback, PHP_INT_MIN );

		try {
			$popup->update_setting( 'animation_speed', 300 );
			$this->assertSame( 300, $popup->get_setting( 'animation_speed' ) );

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an importer writing before cache eviction.
				$wpdb->postmeta,
				[ 'meta_value' => maybe_serialize( [ 'animation_speed' => 400 ] ) ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				[
					'post_id'  => $popup_id,
					'meta_key' => 'popup_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				]
			);
			wp_cache_delete( $popup_id, 'post_meta' );

			$this->assertSame( 400, $popup->get_setting( 'animation_speed' ) );
		} finally {
			add_action( 'added_post_meta', $callback, PHP_INT_MIN, 3 );
			add_action( 'updated_post_meta', $callback, PHP_INT_MIN, 3 );
		}
	}

	/**
	 * Unrelated metadata writes preserve request-local popup settings.
	 *
	 * @return void
	 */
	public function test_unrelated_meta_writes_preserve_request_local_settings() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$popup = pum_get_popup( $popup_id );
		$this->assertSame( 200, $popup->get_setting( 'animation_speed' ) );

		$popup->settings = [ 'animation_speed' => 999 ];

		update_post_meta( $popup_id, 'enabled', false );

		$this->assertSame( 999, $popup->get_setting( 'animation_speed' ) );
	}

	/**
	 * Unrelated metadata writes preserve filtered popup settings.
	 *
	 * @return void
	 */
	public function test_unrelated_meta_writes_preserve_filtered_settings() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 200 ] );

		$filter_calls    = 0;
		$settings_filter = function ( $settings, $filtered_popup_id ) use ( $popup_id, &$filter_calls ) {
			if ( $popup_id !== $filtered_popup_id ) {
				return $settings;
			}

			++$filter_calls;
			$settings['filter_call'] = $filter_calls;

			return $settings;
		};

		add_filter( 'pum_popup_settings', $settings_filter, 10, 2 );

		try {
			$popup = pum_get_popup( $popup_id );
			$this->assertSame( 1, $popup->get_setting( 'filter_call' ) );

			update_post_meta( $popup_id, 'enabled', false );

			$this->assertSame( 1, $popup->get_setting( 'filter_call' ) );
			$this->assertSame( 1, $filter_calls );
		} finally {
			remove_filter( 'pum_popup_settings', $settings_filter, 10 );
		}
	}

	/**
	 * Metadata short-circuits do not invoke defaults or become stored provenance.
	 *
	 * @return void
	 */
	public function test_metadata_short_circuit_skips_default_and_remains_request_local() {
		global $wpdb;

		$popup_id      = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$filter_calls  = 0;
		$default_calls = 0;

		$metadata_filter = function ( $value, $object_id, $meta_key ) use ( $popup_id, &$filter_calls ) {
			if ( $popup_id !== $object_id || 'popup_settings' !== $meta_key ) {
				return $value;
			}

			++$filter_calls;

			return [
				[
					'animation_speed' => 400,
					'filter_call'     => $filter_calls,
				],
			];
		};
		$default_filter  = function ( $value, $object_id, $meta_key ) use ( $popup_id, &$default_calls ) {
			if ( $popup_id !== $object_id || 'popup_settings' !== $meta_key ) {
				return $value;
			}

			++$default_calls;

			return [
				'animation_speed' => 400,
				'filter_call'     => 1,
			];
		};

		add_filter( 'get_post_metadata', $metadata_filter, 10, 3 );
		add_filter( 'default_post_metadata', $default_filter, 10, 3 );

		try {
			$popup = pum_get_popup( $popup_id );

			$this->assertSame( 400, $popup->get_setting( 'animation_speed' ) );
			$this->assertSame( 0, $default_calls );

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Simulates an importer writing beneath a dynamic metadata override.
				$wpdb->postmeta,
				[
					'post_id'    => $popup_id,
					'meta_key'   => 'popup_settings', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => maybe_serialize( [ 'animation_speed' => 200 ] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				]
			);
			wp_cache_delete( $popup_id, 'post_meta' );
			update_meta_cache( 'post', [ $popup_id ] );

			$this->assertSame( 1, $popup->get_setting( 'filter_call' ) );
			$this->assertSame( 1, $filter_calls );
		} finally {
			remove_filter( 'get_post_metadata', $metadata_filter, 10 );
			remove_filter( 'default_post_metadata', $default_filter, 10 );
		}
	}
}
