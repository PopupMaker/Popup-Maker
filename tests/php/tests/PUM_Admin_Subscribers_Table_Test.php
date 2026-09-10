<?php
/**
 * Admin subscriber table tests.
 *
 * @package Popup_Maker
 */

/**
 * Verify subscriber list-table query behavior.
 */
class PUM_Admin_Subscribers_Table_Test extends WP_UnitTestCase {

	/**
	 * Subscriber fixture IDs.
	 *
	 * @var int[]
	 */
	private $subscriber_ids = [];

	/**
	 * Remove subscriber fixtures from the custom table.
	 */
	public function tearDown(): void {
		foreach ( $this->subscriber_ids as $subscriber_id ) {
			PUM_DB_Subscribers::instance()->delete( $subscriber_id );
		}

		parent::tearDown();
	}

	/**
	 * Test popup titles are loaded once for the current subscriber page.
	 */
	public function test_prepare_items_avoids_popup_column_n_plus_one_queries() {
		global $wpdb;

		$expected_titles = [];

		for ( $i = 0; $i < 20; ++$i ) {
			$title    = 'Subscriber table popup ' . $i;
			$popup_id = self::factory()->post->create(
				[
					'post_type'   => 'popup',
					'post_status' => 'publish',
					'post_title'  => $title,
				]
			);

			$this->subscriber_ids[]       = PUM_DB_Subscribers::instance()->insert(
				[
					'email'    => sprintf( 'subscriber-table-%d-%d@example.com', $i, $popup_id ),
					'popup_id' => $popup_id,
				]
			);
			$expected_titles[ $popup_id ] = $title;
			clean_post_cache( $popup_id );
		}

		$table         = new PUM_Admin_Subscribers_Table( [ 'screen' => 'edit-popup' ] );
		$start_queries = $wpdb->num_queries;

		$table->prepare_items();

		$output = '';
		foreach ( $table->items as $item ) {
			$output .= $table->column_popup_id( $item );
		}

		$this->assertSame( 3, $wpdb->num_queries - $start_queries );
		foreach ( $expected_titles as $title ) {
			$this->assertStringContainsString( $title, $output );
		}
	}

	/**
	 * Test batched popup titles honor the public popup ID filter.
	 */
	public function test_prepare_items_honors_popup_id_filter() {
		$original_title    = 'Original subscriber popup';
		$replacement_title = 'Filtered subscriber popup';
		$original_id       = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => $original_title,
			]
		);
		$replacement_id    = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => $replacement_title,
			]
		);
		$subscriber_id     = PUM_DB_Subscribers::instance()->insert(
			[
				'email'    => 'subscriber-table-filter@example.com',
				'popup_id' => $original_id,
			]
		);

		$this->subscriber_ids[] = $subscriber_id;
		$filter                 = static function ( $popup_id ) use ( $original_id, $replacement_id ) {
			return $original_id === $popup_id ? $replacement_id : $popup_id;
		};

		add_filter( 'pum_get_popup_id', $filter );

		try {
			$table = new PUM_Admin_Subscribers_Table( [ 'screen' => 'edit-popup' ] );
			$table->prepare_items();
		} finally {
			remove_filter( 'pum_get_popup_id', $filter );
		}

		$output = '';
		foreach ( $table->items as $item ) {
			if ( $subscriber_id === (int) $item['ID'] ) {
				$output = $table->column_popup_id( $item );
				break;
			}
		}

		$this->assertStringContainsString( $replacement_title, $output );
		$this->assertStringNotContainsString( $original_title, $output );
		$this->assertStringContainsString( 'post=' . $original_id, $output );
	}

	/**
	 * Test popup titles use the shared cached title projection.
	 */
	public function test_prepare_items_reuses_cached_popup_title_projection() {
		global $wpdb;

		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Cached subscriber popup',
			]
		);

		$this->subscriber_ids[] = PUM_DB_Subscribers::instance()->insert(
			[
				'email'    => 'subscriber-table-cached-title@example.com',
				'popup_id' => $popup_id,
			]
		);

		$popups = \PopupMaker\plugin( 'popups' );
		$popups->get_title_choices( [ $popup_id ] );

		$start_queries = $wpdb->num_queries;
		$table         = new PUM_Admin_Subscribers_Table( [ 'screen' => 'edit-popup' ] );
		$table->prepare_items();

		$this->assertSame( 2, $wpdb->num_queries - $start_queries );
	}
}
