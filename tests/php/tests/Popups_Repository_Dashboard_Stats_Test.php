<?php
/**
 * Popup Dashboard analytics repository tests.
 *
 * @package Popup_Maker
 */

/**
 * Verify lightweight Popup Dashboard analytics queries.
 */
class Popups_Repository_Dashboard_Stats_Test extends WP_UnitTestCase {

	/**
	 * Only published, enabled popups with views contribute to the totals.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_include_only_eligible_popups() {
		$first_id = $this->create_popup_with_stats( 'First', 100, 25, 0.25 );
		$top_id   = $this->create_popup_with_stats( 'Top', 10, 3, 0.30 );

		$this->create_popup_with_stats( 'Disabled', 500, 500, 1.0, false );
		$this->create_popup_with_stats( 'No views', 0, 0, 1.0 );
		$this->create_popup_with_stats( 'Draft', 1000, 1000, 1.0, true, 'draft' );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( 110, $stats['total_views'] );
		$this->assertSame( 28, $stats['total_conversions'] );
		$this->assertEqualsWithDelta( ( 28 / 110 ) * 100, $stats['conversion_rate'], 0.0001 );
		$this->assertInstanceOf( WP_Post::class, $stats['top_performer'] );
		$this->assertSame( $top_id, $stats['top_performer']->ID );
		$this->assertEqualsWithDelta( 30.0, $stats['top_performer_rate'], 0.0001 );
		$this->assertNotSame( $first_id, $stats['top_performer']->ID );
	}

	/**
	 * Historical ranking uses stored conversion rate before current counters.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_rank_by_stored_rate_before_current_ratio() {
		$stored_rate_winner = $this->create_popup_with_stats( 'Stored winner', 10, 1, 0.80 );
		$this->create_popup_with_stats( 'Current ratio winner', 10, 9, 0.10 );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( $stored_rate_winner, $stats['top_performer']->ID );
		$this->assertEqualsWithDelta( 10.0, $stats['top_performer_rate'], 0.0001 );
	}

	/**
	 * Conversion count breaks a stored-rate tie.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_break_stored_rate_tie_by_conversions() {
		$this->create_popup_with_stats( 'Fewer conversions', 10, 2, 0.50 );
		$top_id = $this->create_popup_with_stats( 'More conversions', 100, 3, 0.50 );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( $top_id, $stats['top_performer']->ID );
	}

	/**
	 * View count breaks a stored-rate and conversion-count tie.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_break_conversion_tie_by_views() {
		$this->create_popup_with_stats( 'Fewer views', 10, 2, 0.50 );
		$top_id = $this->create_popup_with_stats( 'More views', 100, 2, 0.50 );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( $top_id, $stats['top_performer']->ID );
	}

	/**
	 * Empty and zero-view result sets return stable zero values.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_return_zero_values_without_eligible_popups() {
		$this->create_popup_with_stats( 'No views', 0, 8, 1.0 );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( 0, $stats['total_views'] );
		$this->assertSame( 0, $stats['total_conversions'] );
		$this->assertSame( 0.0, $stats['conversion_rate'] );
		$this->assertNull( $stats['top_performer'] );
		$this->assertSame( 0.0, $stats['top_performer_rate'] );
	}

	/**
	 * The lightweight query never instantiates Popup Maker models.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_do_not_hydrate_popup_models() {
		$this->create_popup_with_stats( 'Model-free', 10, 2, 0.20 );

		$repository = new class( \PopupMaker\plugin() ) extends \PopupMaker\Services\Repository\Popups {
			/**
			 * @var int
			 */
			public $model_hydrations = 0;

			/**
			 * @param WP_Post $post Post object.
			 * @return PUM_Model_Popup|null
			 */
			public function instantiate_model_from_post( $post ) {
				++$this->model_hydrations;

				return parent::instantiate_model_from_post( $post );
			}
		};

		$stats = $repository->get_dashboard_stats();

		$this->assertSame( 0, $repository->model_hydrations );
		$this->assertInstanceOf( WP_Post::class, $stats['top_performer'] );
	}

	/**
	 * Dashboard aggregation does not load unrelated popup metadata.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_do_not_prime_all_popup_metadata() {
		$popup_id = $this->create_popup_with_stats( 'Metadata-light', 10, 2, 0.20 );

		update_post_meta( $popup_id, 'unrelated_dashboard_payload', str_repeat( 'x', 1024 ) );
		wp_cache_delete( $popup_id, 'post_meta' );

		$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();

		$this->assertSame( 10, $stats['total_views'] );
		$this->assertSame( 2, $stats['total_conversions'] );
		$this->assertFalse( wp_cache_get( $popup_id, 'post_meta' ) );
	}

	/**
	 * WordPress metadata overrides still affect totals and ranking.
	 *
	 * @return void
	 */
	public function test_dashboard_stats_preserve_filtered_metadata_values() {
		$this->create_popup_with_stats( 'Stored winner', 100, 10, 0.20 );
		$filtered_id = $this->create_popup_with_stats( 'Filtered winner', 10, 1, 0.10 );

		$filter = function ( $value, $object_id, $meta_key, $single ) use ( $filtered_id ) {
			if ( $filtered_id !== (int) $object_id || ! $single ) {
				return $value;
			}

			$overrides = [
				'popup_open_count'       => 50,
				'popup_conversion_count' => 25,
				'popup_conversion_rate'  => 0.90,
			];

			return array_key_exists( $meta_key, $overrides ) ? [ $overrides[ $meta_key ] ] : $value;
		};

		add_filter( 'get_post_metadata', $filter, 10, 4 );

		try {
			$stats = \PopupMaker\plugin( 'popups' )->get_dashboard_stats();
		} finally {
			remove_filter( 'get_post_metadata', $filter, 10 );
		}

		$this->assertSame( 150, $stats['total_views'] );
		$this->assertSame( 35, $stats['total_conversions'] );
		$this->assertSame( $filtered_id, $stats['top_performer']->ID );
		$this->assertEqualsWithDelta( 50.0, $stats['top_performer_rate'], 0.0001 );
	}

	/**
	 * Create a popup with analytics metadata.
	 *
	 * @param string $title       Popup title.
	 * @param int    $views       Open count.
	 * @param int    $conversions Conversion count.
	 * @param float  $rate        Stored conversion rate used for ranking.
	 * @param bool   $enabled     Whether the popup is enabled.
	 * @param string $status      Post status.
	 *
	 * @return int
	 */
	private function create_popup_with_stats( $title, $views, $conversions, $rate, $enabled = true, $status = 'publish' ) {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => $status,
				'post_title'  => $title,
			]
		);

		update_post_meta( $popup_id, 'enabled', $enabled ? 1 : 0 );
		update_post_meta( $popup_id, 'popup_open_count', $views );
		update_post_meta( $popup_id, 'popup_conversion_count', $conversions );
		update_post_meta( $popup_id, 'popup_conversion_rate', $rate );

		return $popup_id;
	}
}
