<?php
/**
 * Lightweight repository query tests.
 *
 * @package Popup_Maker
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Count model instantiation while exercising repository query shapes.
 */
class PUM_Test_Popups_Repository extends \PopupMaker\Services\Repository\Popups {

	/**
	 * Number of instantiated popup models.
	 *
	 * @var int
	 */
	public $instantiations = 0;

	/**
	 * Number of post-object queries.
	 *
	 * @var int
	 */
	public $post_queries = 0;

	/**
	 * Query popup post objects.
	 *
	 * @param array<string,mixed> $args Query arguments.
	 * @return WP_Post[]
	 */
	public function query_posts( $args = [] ) {
		++$this->post_queries;

		return parent::query_posts( $args );
	}

	/**
	 * Instantiate a popup model.
	 *
	 * @param WP_Post $post Popup post.
	 * @return PUM_Model_Popup|null
	 */
	public function instantiate_model_from_post( $post ) {
		++$this->instantiations;

		return parent::instantiate_model_from_post( $post );
	}
}

/**
 * Verify repositories own their lightweight WordPress query boundaries.
 */
class Repository_Query_Test extends WP_UnitTestCase {

	/**
	 * Popup repository fixture.
	 *
	 * @var PUM_Test_Popups_Repository
	 */
	private $repository;

	/**
	 * Popup fixture IDs in creation order.
	 *
	 * @var int[]
	 */
	private $popup_ids = [];

	/**
	 * Non-popup fixture ID.
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Set up query fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->repository = new PUM_Test_Popups_Repository( \PopupMaker\plugin() );
		$this->popup_ids  = [
			self::factory()->post->create(
				[
					'post_type'   => 'popup',
					'post_status' => 'publish',
					'post_title'  => 'First popup',
					'menu_order'  => 1,
				]
			),
			self::factory()->post->create(
				[
					'post_type'   => 'popup',
					'post_status' => 'publish',
					'post_title'  => 'Second popup',
					'menu_order'  => 2,
				]
			),
		];
		$this->post_id    = self::factory()->post->create( [ 'post_type' => 'post' ] );
	}

	/**
	 * Lightweight query shapes enforce repository ownership and avoid models.
	 */
	public function test_query_ids_and_posts_enforce_type_shape_order_flags_and_filters() {
		$observed = [];
		$inspect  = static function ( $query ) use ( &$observed ) {
			if ( 'popup' === $query->get( 'post_type' ) ) {
				$observed[] = [
					'fields'                 => $query->get( 'fields' ),
					'no_found_rows'          => $query->get( 'no_found_rows' ),
					'update_post_meta_cache' => $query->get( 'update_post_meta_cache' ),
					'update_post_term_cache' => $query->get( 'update_post_term_cache' ),
				];
			}
		};
		$filter   = static function ( $posts ) {
			foreach ( $posts as $post ) {
				if ( $post instanceof WP_Post && 'popup' === $post->post_type ) {
					$post->post_title = 'Filtered ' . $post->post_title;
				}
			}

			return $posts;
		};

		add_action( 'pre_get_posts', $inspect );
		add_filter( 'posts_results', $filter );

		try {
			$ids   = $this->repository->query_ids(
				[
					'post_type' => 'post',
					'post__in'  => array_merge( $this->popup_ids, [ $this->post_id ] ),
					'orderby'   => 'post__in',
				]
			);
			$posts = $this->repository->query_posts(
				[
					'post_type'              => 'post',
					'orderby'                => 'menu_order',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);
		} finally {
			remove_action( 'pre_get_posts', $inspect );
			remove_filter( 'posts_results', $filter );
		}

		$this->assertSame( $this->popup_ids, $ids );
		$this->assertSame( array_reverse( $this->popup_ids ), wp_list_pluck( $posts, 'ID' ) );
		$this->assertSame( 'Filtered Second popup', $posts[0]->post_title );
		$this->assertContainsOnlyInstancesOf( WP_Post::class, $posts );
		$this->assertSame( 0, $this->repository->instantiations );
		$this->assertSame(
			[
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			],
			$observed[0]
		);
		$this->assertSame( 'all', $observed[1]['fields'] );
		$this->assertTrue( $observed[1]['no_found_rows'] );
		$this->assertFalse( $observed[1]['update_post_meta_cache'] );
		$this->assertFalse( $observed[1]['update_post_term_cache'] );
	}

	/**
	 * The model-returning API reuses the post-object query before hydrating.
	 */
	public function test_query_delegates_to_query_posts_before_model_hydration() {
		$items = $this->repository->query(
			[
				'post__in' => $this->popup_ids,
				'orderby'  => 'post__in',
			]
		);

		$this->assertCount( 2, $items );
		$this->assertSame( 1, $this->repository->post_queries );
		$this->assertSame( 2, $this->repository->instantiations );
	}
}
