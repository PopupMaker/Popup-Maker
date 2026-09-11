<?php
/**
 * Deterministic popup model fetch/cache benchmark.
 *
 * Not a correctness test — it emits SQL query counts, model hydration counts,
 * and object-identity facts for the canonical-cache refactor. Excluded from the
 * default suite via the `bench` group; run explicitly with:
 *
 *   vendor/bin/phpunit -c tests/php/phpunit.xml --group bench
 *
 * Set PUM_BENCH_POPUPS to change the eligible popup count (default 10).
 *
 * @package PopupMaker
 * @group bench
 */

/**
 * Popup model cache benchmark.
 */
class Popup_Model_Cache_Bench extends WP_UnitTestCase {

	/**
	 * Seeded popup IDs.
	 *
	 * @var int[]
	 */
	private $ids = [];

	/**
	 * Distinct model object ids observed.
	 *
	 * @var array<int,bool>
	 */
	private $seen = [];

	/**
	 * Query count at window start.
	 *
	 * @var int
	 */
	private $query_mark = 0;

	/**
	 * Hydration count at window start.
	 *
	 * @var int
	 */
	private $hydration_mark = 0;

	/**
	 * Collected rows.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $rows = [];

	/**
	 * Seed popups.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$configured = getenv( 'PUM_BENCH_POPUPS' );
		$count      = is_string( $configured ) && '' !== $configured ? (int) $configured : 10;

		for ( $i = 0; $i < $count; $i++ ) {
			$id = wp_insert_post(
				[
					'post_type'    => 'popup',
					'post_title'   => 'Bench popup ' . $i,
					'post_content' => 'Bench content ' . $i,
					'post_status'  => 'publish',
				]
			);

			update_post_meta(
				$id,
				'popup_settings',
				[
					'enabled'   => true,
					'bench_idx' => $i,
				]
			);

			// Real popups always carry data_version. Without it, model setup()
			// performs a SELECT + UPDATE per popup during a read request, which
			// then invalidates the bulk meta cache primed by WP_Query and forces
			// per-popup meta refetches. Seeding it keeps the fixture honest.
			update_post_meta( $id, 'data_version', 3 );

			$this->ids[] = (int) $id;
		}
	}

	/**
	 * Observe an object for hydration counting.
	 *
	 * @param mixed $obj Candidate model.
	 * @return void
	 */
	private function observe( $obj ) {
		if ( is_object( $obj ) ) {
			$this->seen[ spl_object_id( $obj ) ] = true;
		}
	}

	/**
	 * Open a measurement window.
	 *
	 * @return void
	 */
	private function start() {
		global $wpdb;
		$this->query_mark     = $wpdb->num_queries;
		$this->hydration_mark = count( $this->seen );
	}

	/**
	 * Close a measurement window.
	 *
	 * @return array{queries:int,hydrations:int}
	 */
	private function stop() {
		global $wpdb;

		return [
			'queries'    => $wpdb->num_queries - $this->query_mark,
			'hydrations' => count( $this->seen ) - $this->hydration_mark,
		];
	}

	/**
	 * Drop every request-local popup cache.
	 *
	 * @return void
	 */
	private function flush_caches() {
		$controller = \PopupMaker\plugin()->get_controller( 'Frontend\\Popups' );

		foreach ( $this->ids as $id ) {
			if ( $controller && method_exists( $controller, 'invalidate_queried_popup' ) ) {
				$controller->invalidate_queried_popup( $id );
			}

			\PopupMaker\plugin()->get( 'popups' )->forget_item( $id );
			pum()->popups->forget_item( $id );
		}

		$this->seen = [];
	}

	/**
	 * Record a result row.
	 *
	 * @param string              $label   Scenario label.
	 * @param array<string,int>   $metrics Metrics.
	 * @param array<string,mixed> $extra   Extra fields.
	 * @return void
	 */
	private function row( $label, $metrics, $extra = [] ) {
		$this->rows[] = array_merge(
			[
				'scenario'   => $label,
				'queries'    => $metrics['queries'],
				'hydrations' => $metrics['hydrations'],
			],
			$extra
		);
	}

	/**
	 * Emit collected rows.
	 *
	 * @return void
	 */
	private function emit() {
		$count = count( $this->ids );

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI diagnostic output, not a filesystem write.
		fwrite( STDERR, "\n# BENCH popup_count={$count}\n" );

		foreach ( $this->rows as $row ) {
			fwrite( STDERR, wp_json_encode( $row ) . "\n" );
		}

		fwrite( STDERR, "# END BENCH\n" );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}

	/**
	 * Run all benchmark scenarios.
	 *
	 * @return void
	 */
	public function test_bench_popup_model_cache() {
		if ( empty( $this->ids ) ) {
			// Zero eligible popups: only the frontend query path is meaningful.
			$this->start();
			$queried = \PopupMaker\plugin()->get( 'popups' )->query(
				[ 'post_status' => [ 'publish', 'private' ] ]
			);
			$this->row(
				'D:frontend_query_then_readback',
				$this->stop(),
				[
					'popups'             => count( $queried ),
					'distinct_on_second' => 0,
				]
			);
			$this->emit();
			$this->assertSame( [], $queried );

			return;
		}

		$target = $this->ids[0];

		// A: cold single fetch via the legacy helper.
		$this->flush_caches();
		$this->start();
		$popup = pum_get_popup( $target );
		$this->observe( $popup );
		$this->row( 'A:cold_single_pum_get_popup', $this->stop() );

		// B: repeated getter for the same popup, 10 reads + settings touch.
		$this->start();
		$object_ids = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$repeat       = pum_get_popup( $target );
			$object_ids[] = spl_object_id( $repeat );
			$this->observe( $repeat );
			$repeat->get_setting( 'bench_idx' );
		}
		$this->row(
			'B:warm_repeat_same_popup_x10',
			$this->stop(),
			[ 'distinct_objects' => count( array_unique( $object_ids ) ) ]
		);

		// C: cross-API identity for one popup.
		$this->flush_caches();
		$this->start();
		$via_fn          = pum_get_popup( $target );
		$via_modern      = \PopupMaker\plugin()->get( 'popups' )->get_by_id( $target );
		$via_legacy_repo = pum()->popups->get_item( $target );
		$this->observe( $via_fn );
		$this->observe( $via_modern );
		$this->observe( $via_legacy_repo );
		$this->row(
			'C:cross_api_same_popup',
			$this->stop(),
			[
				'distinct_objects' => count(
					array_unique(
						[
							spl_object_id( $via_fn ),
							spl_object_id( $via_modern ),
							spl_object_id( $via_legacy_repo ),
						]
					)
				),
			]
		);

		// D: frontend-style query then per-ID readback.
		$this->flush_caches();
		$mem_before = memory_get_usage();
		$start_time = microtime( true );

		$this->start();
		$queried = \PopupMaker\plugin()->get( 'popups' )->query(
			[ 'post_status' => [ 'publish', 'private' ] ]
		);
		foreach ( $queried as $item ) {
			$this->observe( $item );
		}
		$second = [];
		foreach ( $this->ids as $id ) {
			$readback = pum_get_popup( $id );
			$second[] = spl_object_id( $readback );
			$this->observe( $readback );
			$readback->get_setting( 'bench_idx' );
		}
		$metrics = $this->stop();

		$this->row(
			'D:frontend_query_then_readback',
			$metrics,
			[
				'popups'             => count( $queried ),
				'distinct_on_second' => count( array_unique( $second ) ),
				'elapsed_ms'         => round( ( microtime( true ) - $start_time ) * 1000, 2 ),
				'mem_delta_kb'       => round( ( memory_get_usage() - $mem_before ) / 1024, 1 ),
			]
		);

		// E: stale-read safety after a direct meta write.
		$this->flush_caches();
		$before = pum_get_popup( $target );
		$before->get_setting( 'bench_idx' );

		update_post_meta(
			$target,
			'popup_settings',
			[
				'enabled'   => true,
				'bench_idx' => 9999,
			]
		);

		$this->start();
		$after    = pum_get_popup( $target );
		$observed = $after->get_setting( 'bench_idx' );
		$this->row(
			'E:stale_after_meta_write',
			$this->stop(),
			[
				'observed_value' => $observed,
				'is_fresh'       => 9999 === $observed ? 'yes' : 'NO-STALE',
			]
		);

		$this->emit();

		$this->assertNotEmpty( $this->rows );
	}
}
