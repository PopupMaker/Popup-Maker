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
}
