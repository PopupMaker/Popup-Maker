<?php
/**
 * REST API ObjectSearch controller tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\RestAPI\ObjectSearch;

/**
 * Test the ObjectSearch REST API controller.
 */
class REST_ObjectSearch_Test extends WP_UnitTestCase {

	/**
	 * Controller instance.
	 *
	 * @var ObjectSearch
	 */
	private $controller;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private $subscriber_user;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new ObjectSearch();

		$this->admin_user = $this->factory->user->create(
			[ 'role' => 'administrator' ]
		);

		$this->subscriber_user = $this->factory->user->create(
			[ 'role' => 'subscriber' ]
		);
	}

	// ─── Permissions ────────────────────────────────────────────────────

	/**
	 * Test permissions check passes for admin user.
	 */
	public function test_permissions_check_passes_for_admin() {
		wp_set_current_user( $this->admin_user );

		$result = $this->controller->permissions_check();

		$this->assertTrue( $result, 'Admin should have permission to search objects.' );
	}

	/**
	 * Test permissions check fails for subscriber.
	 */
	public function test_permissions_check_fails_for_subscriber() {
		wp_set_current_user( $this->subscriber_user );

		$result = $this->controller->permissions_check();

		$this->assertInstanceOf( WP_Error::class, $result, 'Subscriber should not have permission.' );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code(), 'Error code should be rest_forbidden.' );
	}

	/**
	 * Test permissions check fails for logged-out user.
	 */
	public function test_permissions_check_fails_for_logged_out() {
		wp_set_current_user( 0 );

		$result = $this->controller->permissions_check();

		$this->assertInstanceOf( WP_Error::class, $result, 'Logged-out user should not have permission.' );
	}

	// ─── Route registration ─────────────────────────────────────────────

	/**
	 * Test routes are registered correctly.
	 */
	public function test_register_routes() {
		// Register routes within rest_api_init to avoid WP "incorrect usage" notice.
		$controller = $this->controller;
		add_action( 'rest_api_init', function () use ( $controller ) {
			$controller->register_routes();
		} );

		// Force the REST server to reinitialize with our new route.
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			'/popup-maker/v2/object-search',
			$routes,
			'Object search route should be registered.'
		);
	}

	// ─── Post type search ───────────────────────────────────────────────

	/**
	 * Test search_objects returns valid response structure.
	 */
	public function test_search_objects_returns_valid_structure() {
		wp_set_current_user( $this->admin_user );

		// Create test posts.
		$this->factory->post->create( [ 'post_title' => 'Test Post Alpha' ] );
		$this->factory->post->create( [ 'post_title' => 'Test Post Beta' ] );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'post_type' );
		$request->set_param( 'object_key', 'post' );
		$request->set_param( 's', 'Test' );

		$response = $this->controller->search_objects( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response, 'Should return WP_REST_Response.' );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'items', $data, 'Response should have items key.' );
		$this->assertArrayHasKey( 'total_count', $data, 'Response should have total_count key.' );
		$this->assertIsArray( $data['items'], 'Items should be an array.' );
	}

	/**
	 * Test search with include param adds included items.
	 */
	public function test_search_objects_with_include() {
		wp_set_current_user( $this->admin_user );

		$post_id = $this->factory->post->create( [ 'post_title' => 'Included Post' ] );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'post_type' );
		$request->set_param( 'object_key', 'post' );
		$request->set_param( 'include', [ $post_id ] );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$this->assertGreaterThanOrEqual( 1, $data['total_count'], 'Should include the specified post.' );
	}

	// ─── Taxonomy search ────────────────────────────────────────────────

	/**
	 * Test taxonomy search returns results.
	 */
	public function test_search_taxonomy() {
		wp_set_current_user( $this->admin_user );

		// Create a category to search for.
		wp_insert_term( 'Test Category Alpha', 'category' );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'taxonomy' );
		$request->set_param( 'object_key', 'category' );
		$request->set_param( 's', 'Alpha' );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data['items'], 'Should return items array.' );
	}

	// ─── User search ────────────────────────────────────────────────────

	/**
	 * Test user search requires list_users capability.
	 */
	public function test_user_search_requires_list_users() {
		// Editor can edit_posts but not list_users.
		$editor = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'user' );

		$response = $this->controller->search_objects( $request );

		$this->assertInstanceOf( WP_Error::class, $response, 'Editor should not be able to search users.' );
		$this->assertEquals( 'rest_forbidden', $response->get_error_code(), 'Error code should be rest_forbidden.' );
	}

	/**
	 * Test user search succeeds for admin.
	 */
	public function test_user_search_succeeds_for_admin() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'user' );
		$request->set_param( 's', 'admin' );
		$request->set_param( 'paged', 1 );

		$response = $this->controller->search_objects( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response, 'Admin should get valid response.' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data, 'Response should have items key.' );
	}

	// ─── Custom entity / filter support ─────────────────────────────────

	/**
	 * Test custom entity type returns empty result by default.
	 */
	public function test_custom_entity_returns_empty_by_default() {
		wp_set_current_user( $this->admin_user );

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'custom_entity' );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$this->assertEquals( 0, $data['total_count'], 'Custom entity should return 0 results by default.' );
		$this->assertEmpty( $data['items'], 'Custom entity items should be empty by default.' );
	}

	/**
	 * Test pre_object_search filter can override results.
	 */
	public function test_pre_object_search_filter() {
		wp_set_current_user( $this->admin_user );

		add_filter(
			'popup_maker/pre_object_search',
			function () {
				return [
					'items'       => [ [ 'id' => 1, 'text' => 'Filtered Item' ] ],
					'total_count' => 1,
				];
			}
		);

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'post_type' );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$this->assertEquals( 1, $data['total_count'], 'Filter should override total_count.' );
		$this->assertEquals( 'Filtered Item', $data['items'][0]['text'], 'Filter should override items.' );

		// Clean up.
		remove_all_filters( 'popup_maker/pre_object_search' );
	}

	/**
	 * Test object_search filter can modify results.
	 */
	public function test_object_search_filter() {
		wp_set_current_user( $this->admin_user );

		add_filter(
			'popup_maker/object_search',
			function ( $results ) {
				$results['items'][]     = [ 'id' => 999, 'text' => 'Added by Filter' ];
				$results['total_count'] += 1;
				return $results;
			}
		);

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'custom_entity' );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$this->assertEquals( 1, $data['total_count'], 'Filter should add to total_count.' );

		// Clean up.
		remove_all_filters( 'popup_maker/object_search' );
	}

	// ─── Items array is re-indexed ──────────────────────────────────────

	/**
	 * Test response items are numerically re-indexed with array_values.
	 */
	public function test_items_are_reindexed() {
		wp_set_current_user( $this->admin_user );

		add_filter(
			'popup_maker/pre_object_search',
			function () {
				return [
					// Use string keys to simulate deduplication leftovers.
					'items'       => [
						5 => [ 'id' => 5, 'text' => 'A' ],
						9 => [ 'id' => 9, 'text' => 'B' ],
					],
					'total_count' => 2,
				];
			}
		);

		$request = new WP_REST_Request( 'GET', '/popup-maker/v2/object-search' );
		$request->set_param( 'object_type', 'post_type' );

		$response = $this->controller->search_objects( $request );
		$data     = $response->get_data();

		$keys = array_keys( $data['items'] );
		$this->assertEquals( [ 0, 1 ], $keys, 'Items should be numerically re-indexed.' );

		// Clean up.
		remove_all_filters( 'popup_maker/pre_object_search' );
	}
}
