<?php
/**
 * Standard REST aliases for Popup Maker post types.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-failing-alias-registrar.php';

/**
 * Test standard WordPress REST aliases.
 *
 * These tests assert observable routing and response behavior rather than the
 * classes that implement it, so the alias layer can be refactored without
 * rewriting the suite.
 */
class REST_PostTypeAliases_Test extends WP_UnitTestCase {

	/**
	 * Plugin REST namespace.
	 *
	 * @var string
	 */
	const PLUGIN_NS = 'popup-maker/v2';

	/**
	 * Standard REST namespace.
	 *
	 * @var string
	 */
	const ALIAS_NS = 'wp/v2';

	/**
	 * Reset the REST server before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;

		$wp_rest_server = null;
	}

	/**
	 * Reset the REST server after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wp_rest_server;

		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Get all registered routes.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_routes() {
		return rest_get_server()->get_routes();
	}

	/**
	 * Count the handlers registered for a route.
	 *
	 * @param string $route Route pattern.
	 *
	 * @return int
	 */
	protected function count_handlers( $route ) {
		$routes = $this->get_routes();

		return isset( $routes[ $route ] ) ? count( $routes[ $route ] ) : 0;
	}

	/**
	 * Post type collection bases under test.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function post_type_provider() {
		return [
			'popup'       => [ 'popup', 'popups' ],
			'popup_theme' => [ 'popup_theme', 'popup-themes' ],
		];
	}

	/**
	 * Collection and item routes exist in both namespaces.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	public function test_collection_and_item_routes_exist_in_both_namespaces( $post_type, $rest_base ) {
		$routes = $this->get_routes();

		foreach ( [ self::PLUGIN_NS, self::ALIAS_NS ] as $namespace ) {
			$this->assertArrayHasKey(
				'/' . $namespace . '/' . $rest_base,
				$routes,
				$post_type . ' collection missing from ' . $namespace
			);
			$this->assertArrayHasKey(
				'/' . $namespace . '/' . $rest_base . '/(?P<id>[\d]+)',
				$routes,
				$post_type . ' item route missing from ' . $namespace
			);
		}
	}

	/**
	 * The alias namespace exposes the same methods as the plugin namespace.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	public function test_alias_namespace_supports_same_methods( $post_type, $rest_base ) {
		$routes = $this->get_routes();

		foreach ( [ '', '/(?P<id>[\d]+)' ] as $suffix ) {
			$plugin_methods = $this->collect_methods( $routes, '/' . self::PLUGIN_NS . '/' . $rest_base . $suffix );
			$alias_methods  = $this->collect_methods( $routes, '/' . self::ALIAS_NS . '/' . $rest_base . $suffix );

			$this->assertNotEmpty( $plugin_methods, $post_type . $suffix . ' has no plugin-namespace methods' );
			$this->assertSame(
				$plugin_methods,
				$alias_methods,
				$post_type . $suffix . ' methods differ between namespaces'
			);
		}
	}

	/**
	 * Collect the sorted, unique HTTP methods registered for a route.
	 *
	 * @param array<string,mixed> $routes Registered routes.
	 * @param string              $route  Route pattern.
	 *
	 * @return string[]
	 */
	protected function collect_methods( $routes, $route ) {
		if ( ! isset( $routes[ $route ] ) ) {
			return [];
		}

		$methods = [];

		foreach ( $routes[ $route ] as $handler ) {
			foreach ( array_keys( array_filter( $handler['methods'] ) ) as $method ) {
				$methods[ $method ] = true;
			}
		}

		$methods = array_keys( $methods );
		sort( $methods );

		return $methods;
	}

	/**
	 * Revision routes mirror the plugin namespace wherever they exist.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	public function test_revision_routes_mirror_plugin_namespace( $post_type, $rest_base ) {
		$this->assertTrue(
			post_type_supports( $post_type, 'revisions' ),
			$post_type . ' is expected to support revisions'
		);

		$routes = $this->get_routes();
		$suffix = '/(?P<parent>[\d]+)/revisions';

		$this->assertArrayHasKey( '/' . self::PLUGIN_NS . '/' . $rest_base . $suffix, $routes );
		$this->assertArrayHasKey( '/' . self::ALIAS_NS . '/' . $rest_base . $suffix, $routes );
	}

	/**
	 * Autosave routes appear in the alias namespace only when Core registers them.
	 *
	 * WordPress grants `autosave` support implicitly alongside `editor`, and
	 * only registers autosave routes when the post type supports it. The alias
	 * layer must apply the same gate so the two namespaces cannot drift.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	public function test_autosave_routes_match_core_support_gate( $post_type, $rest_base ) {
		$routes   = $this->get_routes();
		$suffix   = '/(?P<id>[\d]+)/autosaves';
		$supports = post_type_supports( $post_type, 'autosave' );

		$plugin_route = '/' . self::PLUGIN_NS . '/' . $rest_base . $suffix;
		$alias_route  = '/' . self::ALIAS_NS . '/' . $rest_base . $suffix;

		$this->assertSame(
			$supports,
			isset( $routes[ $plugin_route ] ),
			$post_type . ' plugin-namespace autosaves should track autosave support'
		);
		$this->assertSame(
			$supports,
			isset( $routes[ $alias_route ] ),
			$post_type . ' alias-namespace autosaves should track autosave support'
		);
	}

	/**
	 * An authorized user can read a popup through the standard alias.
	 *
	 * @return void
	 */
	public function test_standard_popup_alias_uses_core_permissions_and_response() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Builder-compatible popup',
			]
		);

		wp_set_current_user( $admin_id );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $popup_id, $response->get_data()['id'] );
	}

	/**
	 * Both namespaces return the same item payload for the same popup.
	 *
	 * @return void
	 */
	public function test_both_namespaces_return_equivalent_item_payloads() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Parity popup',
			]
		);

		wp_set_current_user( $admin_id );

		$plugin = rest_do_request( new WP_REST_Request( 'GET', '/popup-maker/v2/popups/' . $popup_id ) );
		$alias  = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id ) );

		$this->assertSame( 200, $plugin->get_status() );
		$this->assertSame( 200, $alias->get_status() );

		$plugin_data = $plugin->get_data();
		$alias_data  = $alias->get_data();

		$this->assertSame( $plugin_data['id'], $alias_data['id'] );
		$this->assertSame( $plugin_data['title']['rendered'], $alias_data['title']['rendered'] );
		$this->assertSame( $plugin_data['status'], $alias_data['status'] );
	}

	/**
	 * The alias enforces the same permissions as the plugin namespace.
	 *
	 * @return void
	 */
	public function test_alias_denies_unauthorized_reads_of_private_popups() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
				'post_title'  => 'Unpublished popup',
			]
		);

		wp_set_current_user( 0 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id ) );

		$this->assertContains(
			$response->get_status(),
			[ 401, 403 ],
			'Anonymous reads of a draft popup must not succeed through the alias'
		);
	}

	/**
	 * A revision created on a popup is readable through the alias namespace.
	 *
	 * @return void
	 */
	public function test_revisions_are_readable_through_the_alias() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$popup_id = $this->factory->post->create(
			[
				'post_type'    => 'popup',
				'post_status'  => 'publish',
				'post_title'   => 'Revisioned popup',
				'post_content' => 'First revision.',
			]
		);

		wp_update_post(
			[
				'ID'           => $popup_id,
				'post_content' => 'Second revision.',
			]
		);

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/popups/' . $popup_id . '/revisions' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data(), 'Expected at least one revision through the alias' );
	}

	/**
	 * Repeated registration does not duplicate route handlers.
	 *
	 * `rest_api_init` can fire more than once per request; registering the same
	 * route twice appends a second handler rather than replacing the first.
	 *
	 * @return void
	 */
	public function test_repeated_registration_does_not_duplicate_handlers() {
		$route = '/wp/v2/popups';

		$initial = $this->count_handlers( $route );

		$this->assertGreaterThan( 0, $initial, 'Alias route should be registered before the repeat check' );

		do_action( 'rest_api_init' );
		do_action( 'rest_api_init' );

		$this->assertSame(
			$initial,
			$this->count_handlers( $route ),
			'Repeated rest_api_init must not append duplicate handlers'
		);
	}

	/**
	 * Registration restores any temporary post type property change.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	public function test_post_type_properties_are_restored_after_registration( $post_type, $rest_base ) {
		$post_type_object = get_post_type_object( $post_type );

		$before_base      = $post_type_object->rest_base;
		$before_namespace = $post_type_object->rest_namespace;

		do_action( 'rest_api_init' );

		$this->assertSame( $before_base, $post_type_object->rest_base, $post_type . ' rest_base was not restored' );
		$this->assertSame( $before_namespace, $post_type_object->rest_namespace, $post_type . ' rest_namespace changed' );
	}

	/**
	 * A failure during child registration still restores the post type property.
	 *
	 * Guards the `finally` restore in the registrar: without it, a controller
	 * that throws mid-registration would leave the global post type object
	 * mutated for the remainder of the request.
	 *
	 * @return void
	 */
	public function test_property_is_restored_when_registration_throws() {
		$post_type_object = get_post_type_object( 'popup' );
		$original_base    = $post_type_object->rest_base;

		// Alias under a base that differs from the declared one, so the
		// registrar must actually mutate the property before restoring it.
		$registrar = new PUM_Test_Failing_Alias_Registrar( [ 'popup' => 'alias-probe-popups' ] );

		$caught = false;

		// Register on the action WordPress expects, matching production timing.
		$run = function () use ( $registrar ) {
			$registrar->register();
		};

		add_action( 'rest_api_init', $run, 99 );

		try {
			do_action( 'rest_api_init' );
		} catch ( \RuntimeException $e ) {
			$caught = true;
		} finally {
			remove_action( 'rest_api_init', $run, 99 );
		}

		$this->assertTrue( $caught, 'The registration failure should have propagated' );
		$this->assertSame(
			'alias-probe-popups',
			$registrar->observed_base,
			'The registrar should have mutated rest_base before failing'
		);
		$this->assertSame(
			$original_base,
			$post_type_object->rest_base,
			'rest_base must be restored even when registration fails'
		);
	}

	/**
	 * Popup Maker's own controllers are untouched by the alias layer.
	 *
	 * @return void
	 */
	public function test_plugin_namespace_controllers_are_unchanged() {
		$routes = $this->get_routes();

		foreach ( [ 'popups', 'popup-themes' ] as $rest_base ) {
			$route = '/' . self::PLUGIN_NS . '/' . $rest_base;

			$this->assertArrayHasKey( $route, $routes );

			foreach ( $routes[ $route ] as $handler ) {
				$controller = is_array( $handler['callback'] ) ? $handler['callback'][0] : null;

				$this->assertInstanceOf(
					'WP_REST_Posts_Controller',
					$controller,
					$route . ' should still be served by a Core posts controller'
				);
				$this->assertNotInstanceOf(
					'PopupMaker\RestAPI\Alias\PostsController',
					$controller,
					$route . ' must not be served by the alias controller'
				);
			}
		}
	}

	/**
	 * Non-aliased Popup Maker post types are left alone.
	 *
	 * @return void
	 */
	public function test_other_post_types_are_not_aliased() {
		$routes = $this->get_routes();

		$this->assertArrayNotHasKey( '/wp/v2/ctas', $routes );
		$this->assertArrayNotHasKey( '/wp/v2/pum_cta', $routes );
	}
}
