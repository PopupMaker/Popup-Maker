<?php
/**
 * Canonical popup model cache correctness tests.
 *
 * Covers the invariants the canonical fetch/cache boundary must hold: shared
 * identity across every public API, stale-read prevention after saves, deletes
 * and direct meta writes, multisite partitioning, legacy getter compatibility,
 * and extension subclass support.
 *
 * @package PopupMaker
 */

require_once __DIR__ . '/fixtures/class-canonical-cache-subclass-popup.php';
require_once __DIR__ . '/fixtures/class-canonical-cache-subclass-repository.php';
require_once __DIR__ . '/fixtures/class-canonical-cache-legacy-subclass-popup.php';
require_once __DIR__ . '/fixtures/class-canonical-cache-legacy-subclass-repository.php';

/**
 * Canonical popup cache tests.
 */
class Canonical_Popup_Cache_Test extends WP_UnitTestCase {

	/**
	 * Create a published popup with settings.
	 *
	 * @param array<string,mixed> $settings Popup settings.
	 *
	 * @return int
	 */
	private function make_popup( $settings = [] ) {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, 'popup_settings', $settings );

		return (int) $popup_id;
	}

	/**
	 * Every public fetch API returns one shared model per request.
	 *
	 * @return void
	 */
	public function test_all_public_apis_share_one_model() {
		$popup_id = $this->make_popup( [ 'animation_speed' => 350 ] );

		$via_helper = pum_get_popup( $popup_id );
		$via_modern = \PopupMaker\plugin()->get( 'popups' )->get_by_id( $popup_id );
		$via_legacy = pum()->popups->get_item( $popup_id );
		$via_canon  = \PopupMaker\plugin()->get( 'popups' )->get_canonical_item( $popup_id );

		$this->assertSame( $via_helper, $via_modern );
		$this->assertSame( $via_helper, $via_legacy );
		$this->assertSame( $via_helper, $via_canon );
		$this->assertSame( 350, $via_helper->get_setting( 'animation_speed' ) );
	}

	/**
	 * A mutation through one API is observable through the others.
	 *
	 * @return void
	 */
	public function test_shared_identity_propagates_mutations() {
		$popup_id = $this->make_popup( [ 'animation_speed' => 100 ] );

		$first                    = pum_get_popup( $popup_id );
		$first->some_runtime_flag = 'set-by-first';

		$second = pum()->popups->get_item( $popup_id );

		$this->assertSame( 'set-by-first', $second->some_runtime_flag );
	}

	/**
	 * Direct metadata writes are never served stale.
	 *
	 * @return void
	 */
	public function test_direct_meta_write_is_not_served_stale() {
		$popup_id = $this->make_popup( [ 'animation_speed' => 100 ] );

		$popup = pum_get_popup( $popup_id );
		$this->assertSame( 100, $popup->get_setting( 'animation_speed' ) );

		update_post_meta( $popup_id, 'popup_settings', [ 'animation_speed' => 900 ] );

		$this->assertSame( 900, pum_get_popup( $popup_id )->get_setting( 'animation_speed' ) );
		// The already-held reference must also observe the new value.
		$this->assertSame( 900, $popup->get_setting( 'animation_speed' ) );
	}

	/**
	 * Saving a popup post evicts the cached model.
	 *
	 * @return void
	 */
	public function test_post_update_evicts_cached_model() {
		$popup_id = $this->make_popup();

		$before = pum_get_popup( $popup_id );
		$this->assertSame( 'publish', $before->post_status );

		wp_update_post(
			[
				'ID'         => $popup_id,
				'post_title' => 'Renamed popup',
			]
		);

		$after = pum_get_popup( $popup_id );

		$this->assertSame( 'Renamed popup', $after->post_title );
	}

	/**
	 * Deleting a popup does not leave a resolvable cached model.
	 *
	 * @return void
	 */
	public function test_delete_clears_cached_model() {
		$popup_id = $this->make_popup();

		$this->assertTrue( pum_is_popup( pum_get_popup( $popup_id ) ) );

		wp_delete_post( $popup_id, true );

		$repository = \PopupMaker\plugin()->get( 'popups' );

		$this->assertNull( $repository->get_canonical_item( $popup_id ) );
		$this->assertNull( $repository->get_cached_item( $popup_id ) );
	}

	/**
	 * Canonical models are partitioned by site.
	 *
	 * @return void
	 */
	public function test_canonical_models_are_partitioned_by_blog() {
		$popup_id   = $this->make_popup();
		$repository = \PopupMaker\plugin()->get( 'popups' );
		$blog_id    = get_current_blog_id();

		$original = $repository->get_canonical_item( $popup_id );
		$this->assertInstanceOf( 'PUM_Model_Popup', $original );

		try {
			$GLOBALS['blog_id'] = $blog_id + 1;

			// Nothing has been hydrated for the other site yet.
			$this->assertNull( $repository->get_cached_item( $popup_id ) );
		} finally {
			$GLOBALS['blog_id'] = $blog_id;
		}

		// The original site's model is untouched.
		$this->assertSame( $original, $repository->get_cached_item( $popup_id ) );
	}

	/**
	 * The legacy helper still returns an object for unknown IDs.
	 *
	 * @return void
	 */
	public function test_legacy_getter_returns_object_for_unknown_id() {
		$popup = pum_get_popup( 987654321 );

		$this->assertInstanceOf( 'PUM_Model_Popup', $popup );
		$this->assertFalse( $popup->is_valid() );
	}

	/**
	 * The legacy helper honours the current popup when no ID is given.
	 *
	 * @return void
	 */
	public function test_legacy_getter_returns_current_popup() {
		$popup_id = $this->make_popup();
		$popup    = pum_get_popup( $popup_id );

		\PopupMaker\set_current_popup( $popup );

		try {
			$this->assertSame( $popup, pum_get_popup() );
		} finally {
			\PopupMaker\set_current_popup( null );
		}
	}

	/**
	 * A repository query reuses models callers already hold.
	 *
	 * @return void
	 */
	public function test_query_reuses_canonical_models() {
		$popup_id = $this->make_popup();
		$held     = pum_get_popup( $popup_id );

		$queried = \PopupMaker\plugin()->get( 'popups' )->query(
			[ 'post_status' => [ 'publish', 'private' ] ]
		);

		$matched = null;
		foreach ( $queried as $item ) {
			if ( (int) $item->ID === $popup_id ) {
				$matched = $item;
				break;
			}
		}

		$this->assertSame( $held, $matched );
	}

	/**
	 * Frontend preload leaves one shared model per popup.
	 *
	 * @return void
	 */
	public function test_preload_leaves_shared_models() {
		$popup_id = $this->make_popup( [ 'enabled' => true ] );
		$held     = pum_get_popup( $popup_id );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );
		$controller->preload_popups();

		$this->assertSame( $held, pum_get_popup( $popup_id ) );
		$this->assertSame( $held, $controller->get_queried_popup( $popup_id ) );
	}

	/**
	 * Extension subclasses keep their own hydration path.
	 *
	 * A repository subclass that returns a custom model must not have its model
	 * replaced by the canonical PUM_Model_Popup instance.
	 *
	 * @return void
	 */
	public function test_extension_subclass_models_are_preserved() {
		$popup_id = $this->make_popup();

		$repository = new Canonical_Cache_Subclass_Repository(
			\PopupMaker\plugin()
		);

		$item = $repository->get_by_id( $popup_id );

		$this->assertInstanceOf( 'Canonical_Cache_Subclass_Popup', $item );
	}

	/**
	 * Regression: get_by_id() must not serve a stale model when invalidation
	 * hooks are absent, as in wp-admin and admin-AJAX.
	 *
	 * @return void
	 */
	public function test_get_by_id_validates_without_invalidation_hooks() {
		$popup_id = $this->make_popup();
		$repo     = \PopupMaker\plugin()->get( 'popups' );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );

		// Assert the detach succeeded: if the callback were still attached it
		// would clear the cache itself, and this test would pass without ever
		// exercising the repository's freshness validation.
		$this->assertTrue(
			remove_action( 'clean_post_cache', [ $controller, 'invalidate_queried_popup' ], PHP_INT_MIN )
		);

		try {
			$repo->get_by_id( $popup_id );

			wp_update_post(
				[
					'ID'         => $popup_id,
					'post_title' => 'Updated without hooks',
				]
			);

			$this->assertSame( 'Updated without hooks', $repo->get_by_id( $popup_id )->post_title );
		} finally {
			add_action( 'clean_post_cache', [ $controller, 'invalidate_queried_popup' ], PHP_INT_MIN, 2 );
		}
	}

	/**
	 * Regression: a deleted popup must not be returned from cache.
	 *
	 * @return void
	 */
	public function test_get_by_id_returns_null_for_deleted_popup_without_hooks() {
		$popup_id = $this->make_popup();
		$repo     = \PopupMaker\plugin()->get( 'popups' );

		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );

		// Assert the detach succeeded: if the callback were still attached it
		// would clear the cache itself, and this test would pass without ever
		// exercising the repository's freshness validation.
		$this->assertTrue(
			remove_action( 'clean_post_cache', [ $controller, 'invalidate_queried_popup' ], PHP_INT_MIN )
		);

		try {
			$repo->get_by_id( $popup_id );

			wp_delete_post( $popup_id, true );

			$this->assertNull( $repo->get_by_id( $popup_id ) );
		} finally {
			add_action( 'clean_post_cache', [ $controller, 'invalidate_queried_popup' ], PHP_INT_MIN, 2 );
		}
	}

	/**
	 * Regression: get_by_field() reuses the canonical model.
	 *
	 * @return void
	 */
	public function test_get_by_field_reuses_canonical_model() {
		$popup_id = $this->make_popup();
		$slug     = get_post_field( 'post_name', $popup_id );

		$held = pum_get_popup( $popup_id );
		$repo = \PopupMaker\plugin()->get( 'popups' );

		$this->assertSame( $held, $repo->get_by_field( 'post_name', $slug ) );
		$this->assertSame( $held, $repo->get_canonical_item( $popup_id ) );
	}

	/**
	 * Regression: values injected by the_posts filters survive legacy hydration.
	 *
	 * @return void
	 */
	public function test_legacy_query_preserves_filtered_post_values() {
		$popup_id = $this->make_popup();

		$filter = static function ( $posts ) {
			foreach ( $posts as $post ) {
				if ( 'popup' === $post->post_type ) {
					$post->post_title = 'Translated title';
				}
			}

			return $posts;
		};

		add_filter( 'the_posts', $filter );

		try {
			$popups = pum_get_popups( [ 'post__in' => [ $popup_id ] ] );
		} finally {
			remove_filter( 'the_posts', $filter );
		}

		$matched = null;
		foreach ( $popups as $popup ) {
			if ( (int) $popup->ID === $popup_id ) {
				$matched = $popup;
				break;
			}
		}

		$this->assertInstanceOf( 'PUM_Model_Popup', $matched );
		$this->assertSame( 'Translated title', $matched->post_title );
	}

	/**
	 * Regression: a cached model holding filtered values is not thrashed.
	 *
	 * @return void
	 */
	public function test_filtered_model_survives_repeated_reads() {
		$popup_id = $this->make_popup();
		$repo     = \PopupMaker\plugin()->get( 'popups' );

		$post = get_post( $popup_id );
		$this->assertInstanceOf( 'WP_Post', $post );

		$filtered             = clone $post;
		$filtered->post_title = 'Filtered title';

		$model = $repo->get_canonical_item_for_post( $filtered );
		$this->assertSame( 'Filtered title', $model->post_title );

		// Repeated reads must return the same filtered model, not rebuild it
		// from the unfiltered database row.
		$this->assertSame( $model, $repo->get_canonical_item( $popup_id ) );
		$this->assertSame( 'Filtered title', $repo->get_canonical_item( $popup_id )->post_title );
	}

	/**
	 * Regression: pum_get_popup() honours a custom legacy model class.
	 *
	 * @return void
	 */
	public function test_helper_preserves_custom_legacy_model() {
		$popup_id = $this->make_popup();
		$original = pum()->popups;

		pum()->popups = new Canonical_Cache_Legacy_Subclass_Repository();

		try {
			$this->assertInstanceOf( 'Canonical_Cache_Legacy_Subclass_Popup', pum_get_popup( $popup_id ) );
		} finally {
			pum()->popups = $original;
		}
	}

	/**
	 * The default repository still yields the canonical core model.
	 *
	 * @return void
	 */
	public function test_helper_uses_canonical_model_by_default() {
		$popup_id = $this->make_popup();

		$this->assertTrue( pum()->popups->uses_core_model() );
		$this->assertSame(
			\PopupMaker\plugin()->get( 'popups' )->get_canonical_item( $popup_id ),
			pum_get_popup( $popup_id )
		);
	}

	/**
	 * Regression: filtered values are adopted regardless of lookup order.
	 *
	 * Anything that touches a popup by ID earlier in the request — frontend
	 * preloading does — used to leave an unfiltered model cached, so a later
	 * filtered query silently returned database values.
	 *
	 * @return void
	 */
	public function test_filtered_post_adopted_after_id_lookup() {
		$popup_id = $this->make_popup();
		$repo     = \PopupMaker\plugin()->get( 'popups' );

		// Fill the cache with an unfiltered model first.
		$held = $repo->get_by_id( $popup_id );
		$this->assertSame( get_post( $popup_id )->post_title, $held->post_title );

		$filtered             = clone get_post( $popup_id );
		$filtered->post_title = 'Translated title';

		$model = $repo->get_canonical_item_for_post( $filtered );

		// Same object (identity preserved) carrying the filtered value.
		$this->assertSame( $held, $model );
		$this->assertSame( 'Translated title', $model->post_title );
		$this->assertSame( 'Translated title', $repo->get_canonical_item( $popup_id )->post_title );
	}

	/**
	 * Regression: a filtered legacy query after a helper lookup keeps filters.
	 *
	 * @return void
	 */
	public function test_filtered_query_after_helper_lookup() {
		$popup_id = $this->make_popup();

		// Something touches the popup by ID early in the request.
		$held = pum_get_popup( $popup_id );

		$filter = static function ( $posts ) {
			foreach ( $posts as $post ) {
				if ( 'popup' === $post->post_type ) {
					$post->post_title = 'Translated title';
				}
			}

			return $posts;
		};

		add_filter( 'the_posts', $filter );

		try {
			$popups = pum_get_popups( [ 'post__in' => [ $popup_id ] ] );
		} finally {
			remove_filter( 'the_posts', $filter );
		}

		$matched = null;
		foreach ( $popups as $popup ) {
			if ( (int) $popup->ID === $popup_id ) {
				$matched = $popup;
				break;
			}
		}

		$this->assertSame( $held, $matched );
		$this->assertSame( 'Translated title', $matched->post_title );

		// Refreshing in place must not discard resolved settings or provenance.
		$this->assertIsArray( $held->get_settings() );
		$this->assertSame( 3, (int) $held->data_version );
	}
}
