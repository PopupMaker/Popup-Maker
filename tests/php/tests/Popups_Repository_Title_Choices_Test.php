<?php
/**
 * Popup title choices repository tests.
 *
 * @package Popup_Maker
 */

/**
 * Verify cached popup title lookups.
 */
class Popups_Repository_Title_Choices_Test extends WP_UnitTestCase {

	/**
	 * Requested popup titles are returned without unrelated post types.
	 *
	 * @return void
	 */
	public function test_get_title_choices_returns_popup_title_map() {
		$first_popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'First popup title',
			]
		);
		$second_popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'Second popup title',
			]
		);
		$non_popup_id    = self::factory()->post->create(
			[
				'post_type'  => 'post',
				'post_title' => 'Non-popup title',
			]
		);

		$titles = \PopupMaker\plugin( 'popups' )->get_title_choices( [ $first_popup_id, $second_popup_id, $non_popup_id ] );

		$this->assertCount( 2, $titles );
		$this->assertSame( 'First popup title', $titles[ $first_popup_id ] );
		$this->assertSame( 'Second popup title', $titles[ $second_popup_id ] );
		$this->assertArrayNotHasKey( $non_popup_id, $titles );
	}

	/**
	 * Repeated popup title lookups are served from cache.
	 *
	 * @return void
	 */
	public function test_get_title_choices_uses_cached_result_on_repeated_call() {
		global $wpdb;

		$popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'Cached popup title',
			]
		);
		$popups   = \PopupMaker\plugin( 'popups' );

		$popups->get_title_choices( [ $popup_id ] );
		$queries_after_first_call = $wpdb->num_queries;
		$titles                   = $popups->get_title_choices( [ $popup_id ] );

		$this->assertSame( $queries_after_first_call, $wpdb->num_queries );
		$this->assertSame( [ $popup_id => 'Cached popup title' ], $titles );
	}

	/**
	 * Post updates rotate the cache key and return the current title.
	 *
	 * @return void
	 */
	public function test_get_title_choices_refreshes_after_popup_title_update() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'Original popup title',
			]
		);
		$popups   = \PopupMaker\plugin( 'popups' );

		$this->assertSame( [ $popup_id => 'Original popup title' ], $popups->get_title_choices( [ $popup_id ] ) );

		wp_update_post(
			[
				'ID'         => $popup_id,
				'post_title' => 'Updated popup title',
			]
		);

		$this->assertSame( [ $popup_id => 'Updated popup title' ], $popups->get_title_choices( [ $popup_id ] ) );
	}

	/**
	 * Invalid, zero, and duplicate popup IDs are ignored before querying.
	 *
	 * @return void
	 */
	public function test_get_title_choices_sanitizes_ids() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'Sanitized popup title',
			]
		);

		$titles = \PopupMaker\plugin( 'popups' )->get_title_choices(
			[
				0,
				'not-a-popup-id',
				$popup_id,
				(string) $popup_id,
				$popup_id,
			]
		);

		$this->assertSame( [ $popup_id => 'Sanitized popup title' ], $titles );
	}

	/**
	 * Plain-text consumers receive raw punctuation, not HTML entities.
	 *
	 * @return void
	 */
	public function test_get_title_choices_preserves_raw_punctuation() {
		$title    = 'Sales & “Offers” — Today';
		$popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => $title,
			]
		);
		$raw_title = get_post_field( 'post_title', $popup_id, 'raw' );
		$filter    = static function ( $filtered_title, $filtered_popup_id ) use ( $popup_id ) {
			return $popup_id === $filtered_popup_id ? 'Formatted & encoded title' : $filtered_title;
		};

		add_filter( 'the_title', $filter, 10, 2 );

		try {
			$titles = \PopupMaker\plugin( 'popups' )->get_title_choices( [ $popup_id ] );
		} finally {
			remove_filter( 'the_title', $filter, 10 );
		}

		$this->assertSame( $raw_title, $titles[ $popup_id ] );
		$this->assertStringContainsString( '“Offers” — Today', $titles[ $popup_id ] );
		$this->assertStringNotContainsString( '&#038;', $titles[ $popup_id ] );
	}

	/**
	 * Extensions can remap cached raw titles for multilingual display.
	 *
	 * @return void
	 */
	public function test_get_title_choices_applies_dedicated_filter_to_cached_titles() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'  => 'popup',
				'post_title' => 'Original language',
			]
		);
		$popups   = \PopupMaker\plugin( 'popups' );
		$filter   = static function ( $titles ) use ( $popup_id ) {
			$titles[ $popup_id ] = 'Translated title';

			return $titles;
		};

		$this->assertSame( [ $popup_id => 'Original language' ], $popups->get_title_choices( [ $popup_id ] ) );

		add_filter( 'popup_maker/popup_title_choices', $filter );

		try {
			$this->assertSame( [ $popup_id => 'Translated title' ], $popups->get_title_choices( [ $popup_id ] ) );
		} finally {
			remove_filter( 'popup_maker/popup_title_choices', $filter );
		}
	}
}
