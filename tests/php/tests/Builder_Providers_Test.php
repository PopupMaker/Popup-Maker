<?php
/**
 * Builder provider contract tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Interfaces\BuilderProvider;
use PopupMaker\Interfaces\BuilderProvider\EditsPopups;
use PopupMaker\Interfaces\BuilderProvider\LoadsDocumentAssets;
use PopupMaker\Interfaces\BuilderProvider\RendersDocuments;
use PopupMaker\Services\BuilderPreviewUrl;
use PopupMaker\Services\BuilderProviders;

/**
 * Test the builder-agnostic provider registry, coordinator, and signed previews.
 *
 * These tests deliberately use stub providers rather than a live builder so the
 * shared contracts are verified without third-party builders installed.
 */
class Builder_Providers_Test extends WP_UnitTestCase {

	/**
	 * Original query parameters.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get;

	/**
	 * Preserve request globals.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
	}

	/**
	 * Restore global state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		wp_set_current_user( 0 );
		wp_dequeue_script( 'pum-builder-preview' );
		wp_deregister_script( 'pum-builder-preview' );
		wp_dequeue_style( 'pum-builder-preview' );
		wp_deregister_style( 'pum-builder-preview' );

		parent::tearDown();
	}

	/**
	 * The registry only exposes providers whose builder is available.
	 *
	 * @return void
	 */
	public function test_registry_hides_unavailable_providers() {
		$registry = new BuilderProviders();
		$late     = $this->make_provider( 'late', false );

		$registry->register( $this->make_provider( 'present', true ) );
		$registry->register( $this->make_provider( 'absent', false ) );
		$registry->register( $late );

		$this->assertSame( [ 'present', 'absent', 'late' ], array_keys( $registry->all() ) );
		$this->assertSame( [ 'present' ], array_keys( $registry->available() ) );
		$this->assertNull( $registry->get( 'absent' ) );
		$this->assertInstanceOf( BuilderProvider::class, $registry->get( 'present' ) );

		$late->available = true;

		$this->assertSame( [ 'present', 'late' ], array_keys( $registry->available() ) );

		global $wp_current_filter;

		$previous_current_filter = $wp_current_filter;
		$wp_current_filter[]     = 'plugins_loaded';

		try {
			$controller = $this->make_coordinator( [] );
		} finally {
			$wp_current_filter = $previous_current_filter;
		}

		$this->assertSame( 20, has_action( 'plugins_loaded', [ $controller, 'register_providers' ] ) );

		$extension = $this->make_provider( 'extension', true );
		$register  = function ( $providers ) use ( $extension ) {
			if ( $providers instanceof BuilderProviders ) {
				$providers->register( $extension );
			}
		};

		add_action( 'popup_maker/register_builder_providers', $register );

		try {
			$controller->register_providers();

			$this->assertSame( $extension, $controller->providers()->get( 'extension' ) );
		} finally {
			remove_action( 'popup_maker/register_builder_providers', $register );
			remove_action( 'plugins_loaded', [ $controller, 'register_providers' ], 20 );
		}
	}

	/**
	 * Capability lookups only match providers implementing the interface.
	 *
	 * @return void
	 */
	public function test_registry_filters_by_capability() {
		$registry = new BuilderProviders();

		$registry->register( $this->make_provider( 'plain', true ) );
		$registry->register( $this->make_edit_provider( 'editor', 0 ) );

		$this->assertSame( [ 'editor' ], array_keys( $registry->supporting( EditsPopups::class ) ) );
		$this->assertSame( [], array_keys( $registry->supporting( RendersDocuments::class ) ) );
	}

	/**
	 * A signed preview URL round-trips for the builder that created it.
	 *
	 * @return void
	 */
	public function test_signed_preview_url_round_trips() {
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$controller = $this->make_coordinator( [ $this->make_provider( 'preview-only', true ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->apply_request( BuilderPreviewUrl::create( $popup_id, 'preview-only' ) );

		$this->assertSame(
			[ $popup_id, $popup_id, $popup_id ],
			[
				BuilderPreviewUrl::read_request( 'preview-only' ),
				$controller->get_edit_popup_id(),
				$controller->allow_builder_request( [] )['p'],
			],
			'Signed previews work without EditsPopups and restore the popup query.'
		);
	}

	/**
	 * A signature issued for one builder cannot be replayed against another.
	 *
	 * @return void
	 */
	public function test_signed_preview_url_is_bound_to_its_builder() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->apply_request( BuilderPreviewUrl::create( $popup_id, 'bricks' ) );

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'elementor' ) );
	}

	/**
	 * A signature issued for one popup cannot be replayed against another.
	 *
	 * @return void
	 */
	public function test_signed_preview_url_is_bound_to_its_popup() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$other_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->apply_request( BuilderPreviewUrl::create( $popup_id, 'bricks' ) );

		$_GET['p'] = (string) $other_id;

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ) );
	}

	/**
	 * Missing or tampered signatures are rejected.
	 *
	 * @return void
	 */
	public function test_unsigned_preview_requests_are_rejected() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$_GET = [
			'pum-builder-preview' => 'bricks',
			'p'                   => (string) $popup_id,
		];

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ), 'A missing nonce must be rejected.' );

		$_GET['_wpnonce'] = 'tampered';

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ), 'A tampered nonce must be rejected.' );
	}

	/**
	 * An unauthenticated visitor cannot query a popup through a builder request.
	 *
	 * @return void
	 */
	public function test_logged_out_builder_request_is_not_authorized() {
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id ) ] );

		wp_set_current_user( 0 );

		$this->assertSame( 0, $controller->get_edit_popup_id() );
		$this->assertSame( [], $controller->allow_builder_request( [] ) );
	}

	/**
	 * A user without edit rights on the popup cannot query it.
	 *
	 * @return void
	 */
	public function test_unauthorized_user_cannot_query_popup() {
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( 0, $controller->get_edit_popup_id() );
	}

	/**
	 * A builder request naming a non-popup post is ignored.
	 *
	 * @return void
	 */
	public function test_non_popup_post_is_ignored() {
		$page_id    = $this->factory->post->create( [ 'post_type' => 'page' ] );
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $page_id ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 0, $controller->get_edit_popup_id() );
	}

	/**
	 * An authorized request restores the popup query.
	 *
	 * @return void
	 */
	public function test_authorized_request_restores_popup_query() {
		$popup_id   = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$query_vars = $controller->allow_builder_request( [] );

		$this->assertSame( $popup_id, $query_vars['p'] );
		$this->assertSame( 'popup', $query_vars['post_type'] );
		$this->assertArrayNotHasKey( 'post_status', $query_vars, 'Published popups need no status override.' );
	}

	/**
	 * A draft popup is queryable by an authorized editor.
	 *
	 * Builders open unsaved documents, which are drafts. A front-end query
	 * returns only published and private posts, so without this the editor 404s.
	 *
	 * @return void
	 */
	public function test_draft_popup_is_queryable_for_authorized_editor() {
		$popup_id   = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$query_vars = $controller->allow_builder_request( [] );

		$this->assertSame( 'draft', $query_vars['post_status'] );
	}

	/**
	 * A draft canvas preloads its popup before head assets are printed.
	 *
	 * @return void
	 */
	public function test_draft_canvas_preloads_its_popup_assets() {
		$popup_id   = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->go_to(
			add_query_arg(
				[
					'post_type' => 'popup',
					'p'         => $popup_id,
				],
				home_url( '/' )
			)
		);

		$preloaded = [];
		$capture   = function ( $loaded_id ) use ( &$preloaded ) {
			$preloaded[] = absint( $loaded_id );
		};

		add_action( 'pum_preload_popup', $capture );

		try {
			do_action( 'wp_enqueue_scripts' );
		} finally {
			remove_action( 'pum_preload_popup', $capture );
		}

		$this->assertContains( $popup_id, $preloaded, 'The draft canvas must enqueue Popup Maker CSS and JavaScript before wp_head().' );
		$this->assertTrue( wp_script_is( 'pum-builder-preview', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'pum-builder-preview', 'enqueued' ) );
		$this->assertContains( 'pum-builder-preview', $controller->filter_canvas_body_classes( [] ) );
	}

	/**
	 * An ordinary front-end request never exposes a popup permalink.
	 *
	 * @return void
	 */
	public function test_ordinary_request_leaves_query_untouched() {
		$this->factory->post->create( [ 'post_type' => 'popup' ] );

		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', 0 ) ] );

		$this->assertSame( 0, $controller->get_edit_popup_id() );
		$this->assertSame( [ 'name' => 'hello-world' ], $controller->allow_builder_request( [ 'name' => 'hello-world' ] ) );
		$this->assertFalse( get_post_type_object( 'popup' )->publicly_queryable );
	}

	/**
	 * A provider that renders the editor shell does not get the popup canvas.
	 *
	 * @return void
	 */
	public function test_shell_request_does_not_use_popup_canvas() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$provider = $this->make_edit_provider( 'stub', $popup_id, false );

		$controller = $this->make_coordinator( [ $provider ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( $popup_id, $controller->get_edit_popup_id() );
		$this->assertSame( 0, $controller->get_canvas_popup_id() );
		$this->assertSame( 'theme.php', $controller->use_popup_canvas( 'theme.php' ) );
	}

	/**
	 * A builder editor shell renders no popups at all.
	 *
	 * The shell is a normal front-end request, so without suppression Popup Maker
	 * renders the popup in its footer as a fixed, full-screen overlay at its own
	 * stacking order — covering the entire builder interface.
	 *
	 * @return void
	 */
	public function test_editor_shell_renders_no_popups() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		// A shell request: the provider claims the popup but it is not the canvas.
		$controller = $this->make_coordinator( [ $this->make_edit_provider( 'stub', $popup_id, false ) ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$popups = \PopupMaker\plugin()->get_controller( 'Frontend\Popups' );

		add_action( 'wp_footer', [ $popups, 'render_popups' ] );

		$this->assertSame( 0, $controller->get_canvas_popup_id(), 'The shell is not the canvas.' );
		$this->assertSame( 'theme.php', $controller->use_popup_canvas( 'theme.php' ), 'The shell keeps its own template.' );
		$this->assertFalse(
			has_action( 'wp_footer', [ $popups, 'render_popups' ] ),
			'The footer render must be suppressed so no popup covers the builder.'
		);
	}

	/**
	 * Many popups collapse into a single finalization per batch boundary.
	 *
	 * Directly covers the requirement that six builder popups must not trigger
	 * six one-time asset bootstraps.
	 *
	 * @return void
	 */
	public function test_six_popups_finalize_assets_once_per_batch() {
		$provider   = $this->make_asset_provider( 'stub' );
		$controller = $this->make_coordinator( [ $provider ] );

		$popup_ids = [];

		for ( $i = 0; $i < 6; $i++ ) {
			$popup_ids[] = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		}

		foreach ( $popup_ids as $popup_id ) {
			$controller->render_popup_content( 'original', $popup_id );
		}

		$this->assertSame( 6, $provider->collected, 'Every popup should be collected.' );
		$this->assertSame( 0, $provider->finalized, 'Collection alone must not finalize.' );

		$controller->flush_pending_assets();
		$controller->flush_pending_assets();

		$this->assertSame( 1, $provider->finalized, 'Six popups must finalize once.' );
		$this->assertFalse( $provider->last_after_head, 'The early batch must run before head output.' );
	}

	/**
	 * A popup rendered twice only collects its assets once.
	 *
	 * @return void
	 */
	public function test_repeated_render_collects_assets_once() {
		$provider   = $this->make_asset_provider( 'stub' );
		$controller = $this->make_coordinator( [ $provider ] );
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$controller->render_popup_content( 'original', $popup_id );
		$controller->render_popup_content( 'original', $popup_id );

		$this->assertSame( 1, $provider->collected );
		$this->assertSame( 0, $provider->finalized, 'Rendering twice must not finalize the batch early.' );
	}

	/**
	 * A popup discovered after head output finalizes in the late batch.
	 *
	 * Mirrors a `popmake-123` click trigger found while filtering the content.
	 *
	 * @return void
	 */
	public function test_late_discovered_popup_finalizes_after_head() {
		$provider   = $this->make_asset_provider( 'stub' );
		$controller = $this->make_coordinator( [ $provider ] );

		// Nothing pending during the early boundary.
		$controller->flush_pending_assets();

		$this->assertSame( 0, $provider->finalized );

		$controller->render_popup_content( 'original', $this->factory->post->create( [ 'post_type' => 'popup' ] ) );
		$controller->flush_pending_assets_late();

		$this->assertSame( 1, $provider->finalized );
		$this->assertTrue( $provider->last_after_head, 'The late batch must report that head output has passed.' );
	}

	/**
	 * A failed finalization stays pending for the next boundary.
	 *
	 * @return void
	 */
	public function test_failed_finalization_is_retried() {
		$provider           = $this->make_asset_provider( 'stub' );
		$provider->succeeds = false;
		$controller         = $this->make_coordinator( [ $provider ] );

		$controller->render_popup_content( 'original', $this->factory->post->create( [ 'post_type' => 'popup' ] ) );
		$controller->flush_pending_assets();

		$this->assertSame( 1, $provider->finalized );

		$provider->succeeds = true;
		$controller->flush_pending_assets_late();
		$controller->flush_pending_assets_late();

		$this->assertSame( 2, $provider->finalized, 'A retried batch finalizes once more, then stops.' );
	}

	/**
	 * Content for a popup no builder claims is returned unchanged.
	 *
	 * @return void
	 */
	public function test_non_builder_popup_content_is_unchanged() {
		$provider           = $this->make_asset_provider( 'stub' );
		$provider->is_owner = false;
		$controller         = $this->make_coordinator( [ $provider ] );

		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 0, $provider->collected );

		$empty_renderer = \Mockery::mock( BuilderProvider::class . ', ' . RendersDocuments::class );
		$empty_renderer->shouldReceive( 'key' )->andReturn( 'empty-renderer' );
		$empty_renderer->shouldReceive( 'is_available' )->andReturn( true );
		$empty_renderer->shouldReceive( 'is_builder_document' )->with( $popup_id )->andReturn( true );
		$empty_renderer->shouldReceive( 'render_document' )->with( $popup_id )->andReturn( '' );

		try {
			$controller = $this->make_coordinator( [ $empty_renderer ] );

			$this->assertSame( '', $controller->render_popup_content( 'stale legacy content', $popup_id ) );
		} finally {
			\Mockery::close();
		}
	}

	/**
	 * The integration is a no-op when no builder is available.
	 *
	 * @return void
	 */
	public function test_unavailable_builder_is_a_no_op() {
		$provider            = $this->make_asset_provider( 'stub' );
		$provider->available = false;
		$controller          = $this->make_coordinator( [ $provider ] );

		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 0, $controller->get_edit_popup_id() );
		$this->assertSame( 0, $controller->get_canvas_popup_id() );

		$controller->flush_pending_assets();

		$this->assertSame( 0, $provider->finalized );
	}

	/**
	 * Apply a preview URL's query string to the request globals.
	 *
	 * @param string $url Preview URL.
	 *
	 * @return void
	 */
	private function apply_request( $url ) {
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		$args  = [];

		parse_str( $query, $args );

		$_GET = $args;
	}

	/**
	 * Build a coordinator seeded with stub providers.
	 *
	 * @param BuilderProvider[] $providers Providers to register.
	 *
	 * @return \PopupMaker\Controllers\Builders
	 */
	private function make_coordinator( array $providers ) {
		$controller = new class( \PopupMaker\plugin(), $providers ) extends \PopupMaker\Controllers\Builders {

			/**
			 * Providers to register instead of the bundled ones.
			 *
			 * @var BuilderProvider[]
			 */
			private $stubs;

			/**
			 * Construct with stub providers.
			 *
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param BuilderProvider[]       $stubs     Providers to register.
			 */
			public function __construct( $container, array $stubs ) {
				$this->stubs = $stubs;

				parent::__construct( $container );
			}

			/**
			 * Use the stub providers.
			 *
			 * @return BuilderProvider[]
			 */
			protected function default_providers() {
				return $this->stubs;
			}
		};

		$controller->init();

		return $controller;
	}

	/**
	 * Build a minimal provider.
	 *
	 * @param string $key       Provider key.
	 * @param bool   $available Whether the builder is available.
	 *
	 * @return BuilderProvider
	 */
	private function make_provider( $key, $available ) {
		return new class( $key, $available ) implements BuilderProvider {

			/**
			 * Provider key.
			 *
			 * @var string
			 */
			private $provider_key;

			/**
			 * Whether the builder is available.
			 *
			 * @var bool
			 */
			public $available;

			/**
			 * Construct the stub.
			 *
			 * @param string $key       Provider key.
			 * @param bool   $available Whether the builder is available.
			 */
			public function __construct( $key, $available ) {
				$this->provider_key = $key;
				$this->available    = $available;
			}

			/**
			 * Provider key.
			 *
			 * @return string
			 */
			public function key() {
				return $this->provider_key;
			}

			/**
			 * Availability.
			 *
			 * @return bool
			 */
			public function is_available() {
				return $this->available;
			}

			/**
			 * This stub registers no hooks.
			 *
			 * @return void
			 */
			public function register_hooks() {}
		};
	}

	/**
	 * Build a provider that claims an editor request.
	 *
	 * @param string $key       Provider key.
	 * @param int    $popup_id  Popup ID to claim.
	 * @param bool   $is_canvas Whether the request is the isolated canvas.
	 *
	 * @return BuilderProvider
	 */
	private function make_edit_provider( $key, $popup_id, $is_canvas = true ) {
		return new class( $key, $popup_id, $is_canvas ) implements BuilderProvider, EditsPopups {

			/**
			 * Provider key.
			 *
			 * @var string
			 */
			private $provider_key;

			/**
			 * Popup ID to claim.
			 *
			 * @var int
			 */
			private $popup_id;

			/**
			 * Whether the request is the canvas.
			 *
			 * @var bool
			 */
			private $is_canvas;

			/**
			 * Construct the stub.
			 *
			 * @param string $key       Provider key.
			 * @param int    $popup_id  Popup ID to claim.
			 * @param bool   $is_canvas Whether the request is the canvas.
			 */
			public function __construct( $key, $popup_id, $is_canvas ) {
				$this->provider_key = $key;
				$this->popup_id     = $popup_id;
				$this->is_canvas    = $is_canvas;
			}

			/**
			 * Provider key.
			 *
			 * @return string
			 */
			public function key() {
				return $this->provider_key;
			}

			/**
			 * Availability.
			 *
			 * @return bool
			 */
			public function is_available() {
				return true;
			}

			/**
			 * This stub registers no hooks.
			 *
			 * @return void
			 */
			public function register_hooks() {}

			/**
			 * Claimed popup ID.
			 *
			 * @return int
			 */
			public function get_requested_popup_id() {
				return $this->popup_id;
			}

			/**
			 * Whether this is the canvas request.
			 *
			 * @return bool
			 */
			public function is_canvas_request() {
				return $this->is_canvas;
			}
		};
	}

	/**
	 * Build a provider that owns assets without rendering documents.
	 *
	 * @param string $key Provider key.
	 *
	 * @return BuilderProvider
	 */
	private function make_asset_provider( $key ) {
		return new class( $key ) implements BuilderProvider, LoadsDocumentAssets {

			/**
			 * Provider key.
			 *
			 * @var string
			 */
			private $provider_key;

			/**
			 * Whether the builder is available.
			 *
			 * @var bool
			 */
			public $available = true;

			/**
			 * Whether this builder owns the documents it is asked about.
			 *
			 * @var bool
			 */
			public $is_owner = true;

			/**
			 * Whether finalization succeeds.
			 *
			 * @var bool
			 */
			public $succeeds = true;

			/**
			 * Number of documents collected.
			 *
			 * @var int
			 */
			public $collected = 0;

			/**
			 * Number of finalization attempts.
			 *
			 * @var int
			 */
			public $finalized = 0;

			/**
			 * Whether the last finalization reported post-head output.
			 *
			 * @var bool
			 */
			public $last_after_head = false;

			/**
			 * Construct the stub.
			 *
			 * @param string $key Provider key.
			 */
			public function __construct( $key ) {
				$this->provider_key = $key;
			}

			/**
			 * Provider key.
			 *
			 * @return string
			 */
			public function key() {
				return $this->provider_key;
			}

			/**
			 * Availability.
			 *
			 * @return bool
			 */
			public function is_available() {
				return $this->available;
			}

			/**
			 * This stub registers no hooks.
			 *
			 * @return void
			 */
			public function register_hooks() {}

			/**
			 * Whether this builder owns the document.
			 *
			 * @param int $popup_id Popup ID.
			 *
			 * @return bool
			 */
			public function is_builder_document( $popup_id ) {
				unset( $popup_id );

				return $this->available && $this->is_owner;
			}

			/**
			 * Collect the document's assets.
			 *
			 * @param int $popup_id Popup ID.
			 *
			 * @return void
			 */
			public function collect_document_assets( $popup_id ) {
				unset( $popup_id );

				++$this->collected;
			}

			/**
			 * Finalize collected assets.
			 *
			 * @param bool $after_head Whether head output has passed.
			 *
			 * @return bool
			 */
			public function finalize_document_assets( $after_head ) {
				$this->last_after_head = (bool) $after_head;

				++$this->finalized;

				return $this->succeeds;
			}
		};
	}
}
