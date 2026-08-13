<?php
/**
 * Tests for Popup Maker helpers.
 *
 * @package Popup_Maker
 */

/**
 * Verify helper query behavior.
 */
class PUM_Helpers_Test extends WP_UnitTestCase {

	/**
	 * Popup select lists return published IDs and titles without draft content.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_returns_published_choices() {
		$published_id = self::factory()->post->create(
			[
				'post_type'    => 'popup',
				'post_status'  => 'publish',
				'post_title'   => 'Published choice',
				'post_content' => 'Large content is not needed by a select list.',
			]
		);
		$draft_id     = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Draft choice',
			]
		);

		$choices = PUM_Helpers::popup_selectlist( [ 'post__in' => [ $published_id, $draft_id ] ] );

		$this->assertSame( 'Published choice', $choices[ $published_id ] );
		$this->assertArrayNotHasKey( $draft_id, $choices );
	}

	/**
	 * Existing exclusions and status filters retain their behavior.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_honors_query_filters() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Excluded choice',
			]
		);

		$this->assertArrayNotHasKey(
			$popup_id,
			PUM_Helpers::popup_selectlist( [ 'post__not_in' => [ $popup_id ] ] )
		);
		$this->assertSame( [], PUM_Helpers::popup_selectlist( [ 'post_status' => 'draft' ] ) );
	}

	/**
	 * Legacy name ordering remains ascending unless explicitly overridden.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_preserves_name_ordering() {
		$last_id  = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Zulu choice',
			]
		);
		$first_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Alpha choice',
			]
		);

		$choices = PUM_Helpers::popup_selectlist(
			[
				'popups'  => [ $last_id, $first_id ],
				'orderby' => 'name',
			]
		);

		$this->assertSame( [ $first_id, $last_id ], array_keys( $choices ) );
	}

	/**
	 * A cold select list uses one ID query and one title projection query.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_uses_two_cold_queries() {
		global $wpdb;

		$popup_ids = self::factory()->post->create_many(
			10,
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		foreach ( $popup_ids as $popup_id ) {
			update_post_meta( $popup_id, 'popup_settings', [ 'overlay_disabled' => false ] );
		}

		wp_cache_flush();
		get_option( 'posts_per_page' );
		$query_count = $wpdb->num_queries;
		$choices    = PUM_Helpers::popup_selectlist( [ 'post__in' => $popup_ids ] );

		$this->assertCount( 10, $choices );
		$this->assertSame( 2, $wpdb->num_queries - $query_count );
	}

	/**
	 * Dedicated title filters reach the select-list consumer.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_applies_title_choice_filter() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Original title',
			]
		);
		$filter   = static function ( $titles ) use ( $popup_id ) {
			$titles[ $popup_id ] = 'Translated title';

			return $titles;
		};

		add_filter( 'popup_maker/popup_title_choices', $filter );

		try {
			$this->assertSame(
				[ $popup_id => 'Translated title' ],
				PUM_Helpers::popup_selectlist( [ 'post__in' => [ $popup_id ] ] )
			);
		} finally {
			remove_filter( 'popup_maker/popup_title_choices', $filter );
		}
	}
}
