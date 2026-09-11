<?php
/**
 * Feature announcement query tests.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-feature-announcements.php';

/**
 * Verify feature checks batch-prime popup metadata.
 */
class Feature_Announcements_Query_Test extends WP_UnitTestCase {

	/**
	 * Popup fixture IDs.
	 *
	 * @var int[]
	 */
	private $popup_ids = [];

	/**
	 * Set up popup fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->popup_ids = self::factory()->post->create_many(
			20,
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		foreach ( $this->popup_ids as $popup_id ) {
			update_post_meta( $popup_id, 'popup_triggers', [ [ 'type' => 'click_open' ] ] );
		}
	}

	/**
	 * Cold metadata is loaded in one query before scanning triggers.
	 */
	public function test_exit_intent_check_uses_two_queries() {
		global $wpdb;

		wp_cache_flush();
		get_option( 'posts_per_page' );

		$service     = new PUM_Test_Feature_Announcements( $GLOBALS['popup_maker'] );
		$query_count = $wpdb->num_queries;

		$this->assertFalse( $service->has_exit_intent_popup() );
		$this->assertSame( 2, $wpdb->num_queries - $query_count );
	}

	/**
	 * An early match does not prime metadata outside the first batch.
	 */
	public function test_exit_intent_check_stops_before_priming_later_batches() {
		global $wpdb;

		foreach ( $this->popup_ids as $index => $popup_id ) {
			$date = sprintf( '2025-01-%02d 12:00:00', $index + 1 );
			wp_update_post(
				[
					'ID'            => $popup_id,
					'post_date'     => $date,
					'post_date_gmt' => $date,
				]
			);
		}

		$exit_popup_id = end( $this->popup_ids );
		update_post_meta( $exit_popup_id, 'popup_triggers', [ [ 'type' => 'exit_intent' ] ] );

		$outside_batch_id = self::factory()->post->create(
			[
				'post_type'     => 'popup',
				'post_status'   => 'publish',
				'post_date'     => '2024-01-01 12:00:00',
				'post_date_gmt' => '2024-01-01 12:00:00',
			]
		);
		update_post_meta( $outside_batch_id, 'popup_triggers', [ [ 'type' => 'click_open' ] ] );

		wp_cache_flush();
		get_option( 'posts_per_page' );

		$service     = new PUM_Test_Feature_Announcements( $GLOBALS['popup_maker'] );
		$query_count = $wpdb->num_queries;

		$this->assertTrue( $service->has_exit_intent_popup() );
		$this->assertSame( 2, $wpdb->num_queries - $query_count );
		$this->assertFalse( wp_cache_get( $outside_batch_id, 'post_meta' ) );
	}

	/**
	 * Scheduling statistics skip unused term-cache queries.
	 */
	public function test_scheduling_stats_use_two_queries() {
		global $wpdb;

		foreach ( $this->popup_ids as $popup_id ) {
			update_post_meta( $popup_id, 'popup_enabled', true );
		}

		wp_cache_flush();
		get_option( 'posts_per_page' );

		$service     = new PUM_Test_Feature_Announcements( $GLOBALS['popup_maker'] );
		$query_count = $wpdb->num_queries;
		$stats       = $service->get_scheduling_stats();

		$this->assertSame( 20, $stats['total'] );
		$this->assertSame( 2, $wpdb->num_queries - $query_count );
	}

	/**
	 * Feature scans delegate their popup queries to the repository projections.
	 */
	public function test_feature_scans_use_popup_repository_query_shapes() {
		$repository = new class( \PopupMaker\plugin( 'popups' ) ) {
			/** @var \PopupMaker\Services\Repository\Popups */
			private $delegate;

			/** @var int */
			public $id_queries = 0;

			/** @var int */
			public $post_queries = 0;

			public function __construct( $delegate ) {
				$this->delegate = $delegate;
			}

			public function query_ids( $args = [] ) {
				++$this->id_queries;
				return $this->delegate->query_ids( $args );
			}

			public function query_posts( $args = [] ) {
				++$this->post_queries;
				return $this->delegate->query_posts( $args );
			}
		};
		$container  = new class( $repository ) {
			private $repository;

			public function __construct( $repository ) {
				$this->repository = $repository;
			}

			public function get( $service ) {
				return 'popups' === $service ? $this->repository : null;
			}
		};
		$service    = new PUM_Test_Feature_Announcements( $container );

		$service->has_published_popup();
		$service->needs_split_testing();
		$service->has_exit_intent_popup();
		$service->get_scheduling_stats();

		$this->assertSame( 3, $repository->id_queries );
		$this->assertSame( 1, $repository->post_queries );
	}
}
