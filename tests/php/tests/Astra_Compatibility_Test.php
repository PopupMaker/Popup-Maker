<?php
/**
 * Astra compatibility controller tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Controllers\Compatibility\Builder\Astra;

/**
 * Test Astra Custom Layout rendering compatibility.
 */
class Astra_Compatibility_Test extends WP_UnitTestCase {

	/**
	 * Controller under test.
	 *
	 * @var Astra
	 */
	private $controller;

	/**
	 * Preserve globals changed by the test fixture.
	 *
	 * @var array<string, mixed>
	 */
	private $global_state = [];

	/**
	 * Temporary popup content filter.
	 *
	 * @var callable|null
	 */
	private $content_filter;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( [ 'wp_query', 'wp_the_query', 'post' ] as $global_name ) {
			$this->global_state[ $global_name ] = $GLOBALS[ $global_name ] ?? null;
		}

		$this->controller = new Astra( new stdClass() );
		$this->controller->init();
	}

	/**
	 * Restore test hooks and globals.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'pum_popup_content', [ $this->controller, 'capture_query_globals' ], 1 );
		remove_filter( 'pum_popup_content', [ $this->controller, 'restore_query_globals' ], PHP_INT_MAX );

		if ( $this->content_filter ) {
			remove_filter( 'pum_popup_content', $this->content_filter, 11 );
		}

		foreach ( $this->global_state as $global_name => $value ) {
			$GLOBALS[ $global_name ] = $value;
		}

		parent::tearDown();
	}

	/**
	 * Astra Custom Layout rendering must not mutate the pending main query.
	 */
	public function test_astra_custom_layout_preserves_main_query_and_post_globals() {
		$query          = $this->set_main_query_fixture();
		$original_posts = $query->posts;
		$original_post  = $GLOBALS['post'];

		$this->add_query_mutation_filter( $query );

		$content = apply_filters( 'pum_popup_content', '[astra_custom_layout id="123"]', 456 );

		$this->assertStringContainsString( 'astra_custom_layout', $content );
		$this->assertSame( $query, $GLOBALS['wp_query'] );
		$this->assertSame( $query, $GLOBALS['wp_the_query'] );
		$this->assertSame( $original_posts, $query->posts );
		$this->assertSame( -1, $query->current_post );
		$this->assertFalse( $query->in_the_loop );
		$this->assertSame( $original_post, $GLOBALS['post'] );
	}

	/**
	 * Ordinary popup content must not invoke the query isolation helper.
	 */
	public function test_ordinary_popup_content_is_not_isolated() {
		$query = $this->set_main_query_fixture();

		$this->add_query_mutation_filter( $query );

		apply_filters( 'pum_popup_content', 'Ordinary popup content', 456 );

		$this->assertSame( [ $query->post->ID ], $query->posts );
		$this->assertSame( 1, $query->current_post );
		$this->assertTrue( $query->in_the_loop );
		$this->assertSame( $query->post, $GLOBALS['post'] );
	}

	/**
	 * Create a main query fixture with two posts.
	 *
	 * @return WP_Query
	 */
	private function set_main_query_fixture() {
		$post_ids = self::factory()->post->create_many( 2 );
		$query    = new WP_Query(
			[
				'post__in'       => $post_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 2,
			]
		);

		$GLOBALS['wp_query']     = $query;
		$GLOBALS['wp_the_query'] = $query;
		$GLOBALS['post']         = $query->posts[0];

		return $query;
	}

	/**
	 * Add a builder-like filter that mutates the main query.
	 *
	 * @param WP_Query $query Query fixture.
	 * @return void
	 */
	private function add_query_mutation_filter( $query ) {
		$this->content_filter = function ( $content ) use ( $query ) {
			$rendered_post       = $query->posts[1];
			$query->posts        = [ $rendered_post->ID ];
			$query->current_post = 1;
			$query->post         = $rendered_post;
			$query->in_the_loop  = true;
			$GLOBALS['post']     = $rendered_post;

			return $content;
		};

		add_filter( 'pum_popup_content', $this->content_filter, 11 );
	}
}
