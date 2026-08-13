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
	 * Empty status arguments retain WordPress's published-post default.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_treats_empty_post_status_as_default() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Published choice',
			]
		);

		foreach ( [ '', false, [] ] as $post_status ) {
			$this->assertSame(
				[ $popup_id => 'Published choice' ],
				PUM_Helpers::popup_selectlist(
					[
						'post__in'    => [ $popup_id ],
						'post_status' => $post_status,
					]
				)
			);
		}
	}

	/**
	 * Explicit private choices remain capability-gated.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_includes_only_readable_private_popups() {
		$previous_user_id = get_current_user_id();
		$admin_user_id    = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$subscriber_id    = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$private_id       = self::factory()->post->create(
			[
				'post_author' => $admin_user_id,
				'post_type'   => 'popup',
				'post_status' => 'private',
				'post_title'  => 'Private popup',
			]
		);
		$args             = [ 'post_status' => [ 'publish', 'private' ] ];

		try {
			wp_set_current_user( $subscriber_id );
			$this->assertArrayNotHasKey( $private_id, PUM_Helpers::popup_selectlist( $args ) );

			wp_set_current_user( $admin_user_id );
			$this->assertSame( [ $private_id => 'Private popup' ], PUM_Helpers::popup_selectlist( $args ) );
		} finally {
			wp_set_current_user( $previous_user_id );
		}
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
	 * Query-result filters retain their title mutations.
	 *
	 * @dataProvider query_result_filter_provider
	 * @param string $hook Query result hook.
	 * @return void
	 */
	public function test_popup_selectlist_preserves_query_result_title_filters( $hook ) {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Stored popup title',
			]
		);
		$filter   = static function ( $posts ) use ( $popup_id ) {
			foreach ( $posts as $post ) {
				if ( $post instanceof WP_Post && $popup_id === (int) $post->ID ) {
					$post->post_title = 'Filtered popup title';
				}
			}

			return $posts;
		};

		add_filter( $hook, $filter );

		try {
			$choices = PUM_Helpers::popup_selectlist( [ 'post__in' => [ $popup_id ] ] );
		} finally {
			remove_filter( $hook, $filter );
		}

		$this->assertSame( [ $popup_id => 'Filtered popup title' ], $choices );
	}

	/**
	 * Query-result hooks that can alter popup titles.
	 *
	 * @return array<string,array{string}>
	 */
	public function query_result_filter_provider() {
		return [
			'posts_results' => [ 'posts_results' ],
			'the_posts'     => [ 'the_posts' ],
		];
	}

	/**
	 * Filtered results cannot inject posts outside the permitted popup set.
	 *
	 * @dataProvider invalid_filtered_post_provider
	 * @param string $post_type         Injected post type.
	 * @param string $post_status       Injected post status.
	 * @param bool   $include_in_request Whether the injected post is requested.
	 * @return void
	 */
	public function test_popup_selectlist_rejects_invalid_filtered_posts( $post_type, $post_status, $include_in_request ) {
		$popup_id      = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Published popup',
			]
		);
		$injected_args = [
			'post_type'   => $post_type,
			'post_status' => $post_status,
			'post_title'  => 'Injected post',
		];

		if ( 'future' === $post_status ) {
			$injected_args['post_date'] = '2035-01-01 00:00:00';
		}

		$injected_id   = self::factory()->post->create( $injected_args );
		$requested_ids = [ $popup_id ];

		if ( $include_in_request ) {
			$requested_ids[] = $injected_id;
		}

		$filter = static function ( $posts ) use ( $injected_id ) {
			$posts[] = get_post( $injected_id );

			return $posts;
		};

		add_filter( 'posts_results', $filter );

		try {
			$choices = PUM_Helpers::popup_selectlist( [ 'post__in' => $requested_ids ] );
		} finally {
			remove_filter( 'posts_results', $filter );
		}

		$this->assertSame( [ $popup_id => 'Published popup' ], $choices );
	}

	/**
	 * Invalid posts that a query-result filter may inject.
	 *
	 * @return array<string,array{string,string,bool}>
	 */
	public function invalid_filtered_post_provider() {
		return [
			'published page'              => [ 'page', 'publish', true ],
			'draft popup'                 => [ 'popup', 'draft', true ],
			'future popup'                => [ 'popup', 'future', true ],
			'private popup'               => [ 'popup', 'private', true ],
			'unrequested published popup' => [ 'popup', 'publish', false ],
		];
	}

	/**
	 * Filtered private choices remain capability-gated.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_filters_private_posts_by_capability() {
		$previous_user_id = get_current_user_id();
		$admin_user_id    = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$subscriber_id    = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$private_id       = self::factory()->post->create(
			[
				'post_author' => $admin_user_id,
				'post_type'   => 'popup',
				'post_status' => 'private',
				'post_title'  => 'Private popup',
			]
		);
		$args             = [ 'post_status' => [ 'publish', 'private' ] ];
		$filter           = static function ( $posts ) use ( $private_id ) {
			$posts[] = get_post( $private_id );

			return $posts;
		};

		add_filter( 'posts_results', $filter );

		try {
			wp_set_current_user( $subscriber_id );
			$this->assertArrayNotHasKey( $private_id, PUM_Helpers::popup_selectlist( $args ) );

			wp_set_current_user( $admin_user_id );
			$this->assertSame( [ $private_id => 'Private popup' ], PUM_Helpers::popup_selectlist( $args ) );
		} finally {
			remove_filter( 'posts_results', $filter );
			wp_set_current_user( $previous_user_id );
		}
	}

	/**
	 * Private choices refresh when the current user's capability changes.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_refreshes_private_choices_after_capability_changes() {
		$previous_user_id = get_current_user_id();
		$admin_user_id    = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id          = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$user             = get_user_by( 'id', $user_id );
		$post_type        = get_post_type_object( 'popup' );
		$private_cap      = $post_type->cap->read_private_posts;
		$private_id       = self::factory()->post->create(
			[
				'post_author' => $admin_user_id,
				'post_type'   => 'popup',
				'post_status' => 'private',
				'post_title'  => 'Private popup',
			]
		);
		$args             = [ 'post_status' => [ 'publish', 'private' ] ];

		try {
			$user->add_cap( $private_cap );
			wp_set_current_user( $user_id );
			$this->assertSame( [ $private_id => 'Private popup' ], PUM_Helpers::popup_selectlist( $args ) );

			wp_get_current_user()->remove_cap( $private_cap );
			$this->assertArrayNotHasKey( $private_id, PUM_Helpers::popup_selectlist( $args ) );
		} finally {
			$user->remove_cap( $private_cap );
			wp_set_current_user( $previous_user_id );
		}
	}

	/**
	 * Nested query IDs cannot expand the outer filtered result set.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_scopes_filtered_ids_to_outer_query() {
		$requested_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Requested popup',
			]
		);
		$nested_id    = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Nested popup',
			]
		);
		$is_nested    = false;
		$filter       = static function ( $posts ) use ( $nested_id, &$is_nested ) {
			if ( $is_nested ) {
				return $posts;
			}

			$is_nested   = true;
			$nested_post = get_posts(
				[
					'post_type'        => 'popup',
					'post_status'      => 'publish',
					'post__in'         => [ $nested_id ],
					'posts_per_page'   => 1,
					'suppress_filters' => false,
				]
			);
			$is_nested   = false;

			if ( isset( $nested_post[0] ) ) {
				$posts[] = $nested_post[0];
			}

			return $posts;
		};

		add_filter( 'posts_results', $filter, PHP_INT_MIN );

		try {
			$choices = PUM_Helpers::popup_selectlist( [ 'post__in' => [ $requested_id ] ] );
		} finally {
			remove_filter( 'posts_results', $filter, PHP_INT_MIN );
		}

		$this->assertSame( [ $requested_id => 'Requested popup' ], $choices );
	}

	/**
	 * Suppressed query filters retain the normal raw-title fast path.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_skips_query_result_filters_when_suppressed() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Stored popup title',
			]
		);
		$filter   = static function ( $posts ) {
			foreach ( $posts as $post ) {
				if ( $post instanceof WP_Post ) {
					$post->post_title = 'Filtered popup title';
				}
			}

			return $posts;
		};

		add_filter( 'posts_results', $filter );

		try {
			$choices = PUM_Helpers::popup_selectlist(
				[
					'post__in'         => [ $popup_id ],
					'suppress_filters' => true,
				]
			);
		} finally {
			remove_filter( 'posts_results', $filter );
		}

		$this->assertSame( [ $popup_id => 'Stored popup title' ], $choices );
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
		$choices     = PUM_Helpers::popup_selectlist( [ 'post__in' => $popup_ids ] );

		$this->assertCount( 10, $choices );
		$this->assertSame( 2, $wpdb->num_queries - $query_count );

		foreach ( $popup_ids as $popup_id ) {
			$this->assertFalse( wp_cache_get( $popup_id, 'posts' ) );
		}
	}

	/**
	 * Same-request query caching refreshes when published popups change.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_refreshes_cached_ids_after_post_changes() {
		$args = [ 'orderby' => 'name' ];

		$this->assertSame( [], PUM_Helpers::popup_selectlist( $args ) );

		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'New popup',
			]
		);

		$this->assertSame( [ $popup_id => 'New popup' ], PUM_Helpers::popup_selectlist( $args ) );

		wp_update_post(
			[
				'ID'          => $popup_id,
				'post_status' => 'draft',
			]
		);

		$this->assertSame( [], PUM_Helpers::popup_selectlist( $args ) );
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

	/**
	 * Title filters run again when a cached select list is requested.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_reapplies_title_filter_to_cached_ids() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Original title',
			]
		);
		$language = 'English';
		$filter   = static function ( $titles ) use ( $popup_id, &$language ) {
			$titles[ $popup_id ] = $language . ' title';

			return $titles;
		};
		$args     = [ 'post__in' => [ $popup_id ] ];

		add_filter( 'popup_maker/popup_title_choices', $filter );

		try {
			$this->assertSame( [ $popup_id => 'English title' ], PUM_Helpers::popup_selectlist( $args ) );

			$language = 'French';

			$this->assertSame( [ $popup_id => 'French title' ], PUM_Helpers::popup_selectlist( $args ) );
		} finally {
			remove_filter( 'popup_maker/popup_title_choices', $filter );
		}
	}

	/**
	 * Dedicated title filters cannot inject unqueried choices.
	 *
	 * @return void
	 */
	public function test_popup_selectlist_rejects_title_filter_injections() {
		$popup_id       = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Published popup',
			]
		);
		$draft_id       = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Draft popup',
			]
		);
		$results_filter = static function ( $posts ) {
			return $posts;
		};
		$title_filter   = static function ( $titles ) use ( $draft_id ) {
			$titles[ $draft_id ] = 'Injected draft';

			return $titles;
		};

		add_filter( 'posts_results', $results_filter );
		add_filter( 'popup_maker/popup_title_choices', $title_filter );

		try {
			$choices = PUM_Helpers::popup_selectlist( [ 'post__in' => [ $popup_id ] ] );
		} finally {
			remove_filter( 'popup_maker/popup_title_choices', $title_filter );
			remove_filter( 'posts_results', $results_filter );
		}

		$this->assertSame( [ $popup_id => 'Published popup' ], $choices );
	}
}
