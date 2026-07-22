<?php
/**
 * Post duplication compatibility controller tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Controllers\Compatibility\Plugin\PostDuplication;

/**
 * Test post duplication plugin compatibility.
 */
class PostDuplication_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Controller under test.
	 *
	 * @var PostDuplication
	 */
	private $controller;

	/**
	 * Original request data.
	 *
	 * @var array<string, mixed>
	 */
	private $original_request;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test state backup.
		$this->controller       = new PostDuplication( new stdClass() );
	}

	/**
	 * Restore request data.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_REQUEST = $this->original_request;

		parent::tearDown();
	}

	/**
	 * Test that core, Pro, and identity metadata are excluded.
	 *
	 * @return void
	 */
	public function test_get_excluded_meta_keys_covers_popup_specific_data() {
		$meta_keys = $this->controller->get_excluded_meta_keys();

		$this->assertContains( 'popup_open_count', $meta_keys );
		$this->assertContains( 'popup_conversion_count_total', $meta_keys );
		$this->assertContains( 'popup_views', $meta_keys );
		$this->assertContains( 'popup_revenue', $meta_keys );
		$this->assertContains( '_pum_form_conversion_count', $meta_keys );
		$this->assertContains( '_pum_split_test_source', $meta_keys );
		$this->assertContains( 'pum_example_popup', $meta_keys );
	}

	/**
	 * Test native exclusion hooks used by popular duplication plugins.
	 *
	 * @return void
	 */
	public function test_popular_plugin_filters_exclude_popup_metadata() {
		$yoast_exclusions = apply_filters( 'duplicate_post_excludelist_filter', [ '_edit_lock' ] );
		$yoast_copy_keys  = apply_filters( 'duplicate_post_meta_keys_filter', [ 'popup_settings', 'popup_open_count' ] );
		$post_duplicator  = apply_filters( 'mtphr_post_duplicator_excluded_meta_keys', [ '_edit_lock' ] );
		$wp_duplicate     = apply_filters( 'wp_duplicate_page_exclude_meta_key', [ '_order_number' ] );
		$orbit_fox        = apply_filters( 'obfx_post_duplicator_skip_meta_keys', [ '_edit_lock' ], 123 );

		$this->assertContains( 'popup_open_count', $yoast_exclusions );
		$this->assertContains( 'popup_analytic_*', $yoast_exclusions );
		$this->assertSame( [ 'popup_settings' ], $yoast_copy_keys );
		$this->assertContains( 'popup_open_count', $post_duplicator );
		$this->assertContains( 'popup_open_count', $wp_duplicate );
		$this->assertContains( 'popup_open_count', $orbit_fox );
		$this->assertFalse( apply_filters( 'mtphr_post_duplicator_meta_popup_open_count_enabled', true ) );
	}

	/**
	 * Test malformed third-party filter values are left unchanged.
	 *
	 * @return void
	 */
	public function test_plugin_filter_callbacks_defensively_handle_invalid_values() {
		$this->assertSame( 'invalid', $this->controller->add_excluded_meta_keys( 'invalid' ) );
		$this->assertSame( 'invalid', $this->controller->add_yoast_excluded_meta_keys( 'invalid' ) );
		$this->assertSame( 'invalid', $this->controller->remove_excluded_meta_keys( 'invalid' ) );
	}

	/**
	 * Test request-scoped filtering blocks unique metadata only on popups.
	 *
	 * @return void
	 */
	public function test_request_guard_blocks_only_excluded_popup_metadata() {
		$_REQUEST['action'] = 'dt_duplicate_post_as_draft';

		$popup_id = wp_insert_post(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Duplicated Popup',
			]
		);

		$post_id = wp_insert_post(
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_title'  => 'Duplicated Post',
			]
		);

		$this->assertTrue( $this->controller->prevent_excluded_meta_copy( null, $popup_id, 'popup_open_count', 25 ) );
		$this->assertNull( $this->controller->prevent_excluded_meta_copy( null, $popup_id, 'popup_settings', [ 'enabled' => true ] ) );
		$this->assertNull( $this->controller->prevent_excluded_meta_copy( null, $post_id, 'popup_open_count', 25 ) );
		$this->assertFalse( $this->controller->prevent_excluded_meta_copy( false, $popup_id, 'popup_open_count', 25 ) );
	}

	/**
	 * Test custom analytics event counters are recognized by pattern.
	 *
	 * @return void
	 */
	public function test_request_guard_blocks_custom_event_counters() {
		$_REQUEST['action'] = 'duplicate_content';

		$popup_id = wp_insert_post(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Duplicated Popup',
			]
		);

		$this->assertTrue(
			$this->controller->prevent_excluded_meta_copy( null, $popup_id, 'popup_engagement_count_total', 10 )
		);
	}

	/**
	 * Test shutdown cleanup removes metadata copied with direct SQL.
	 *
	 * @return void
	 */
	public function test_cleanup_removes_direct_sql_copies_and_preserves_settings() {
		global $wpdb;

		$popup_id = wp_insert_post(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'SQL Duplicated Popup',
			]
		);

		$_REQUEST['action'] = 'content_clone';
		$this->controller->queue_duplicated_popup_cleanup( $popup_id, get_post( $popup_id ), false );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery -- Simulate third-party plugins that bypass metadata APIs.
		$wpdb->insert(
			$wpdb->postmeta,
			[
				'post_id'    => $popup_id,
				'meta_key'   => 'popup_open_count',
				'meta_value' => '99',
			]
		);
		$wpdb->insert(
			$wpdb->postmeta,
			[
				'post_id'    => $popup_id,
				'meta_key'   => 'popup_engagement_count_total',
				'meta_value' => '123',
			]
		);
		$wpdb->insert(
			$wpdb->postmeta,
			[
				'post_id'    => $popup_id,
				'meta_key'   => 'popup_settings',
				'meta_value' => maybe_serialize( [ 'enabled' => true ] ),
			]
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery

		$this->controller->cleanup_duplicated_popup_meta();

		$this->assertFalse( metadata_exists( 'post', $popup_id, 'popup_open_count' ) );
		$this->assertFalse( metadata_exists( 'post', $popup_id, 'popup_engagement_count_total' ) );
		$this->assertTrue( metadata_exists( 'post', $popup_id, 'popup_settings' ) );
		$this->assertSame( [ 'enabled' => true ], get_post_meta( $popup_id, 'popup_settings', true ) );
	}

	/**
	 * Test the public exclusion filter can cover extension-specific identity data.
	 *
	 * @return void
	 */
	public function test_excluded_meta_keys_are_extensible() {
		$callback = function ( $meta_keys ) {
			$meta_keys[] = '_pum_extension_unique_id';

			return $meta_keys;
		};

		add_filter( 'popup_maker/post_duplication_excluded_meta_keys', $callback );

		$this->assertContains( '_pum_extension_unique_id', $this->controller->get_excluded_meta_keys() );

		remove_filter( 'popup_maker/post_duplication_excluded_meta_keys', $callback );
	}
}
