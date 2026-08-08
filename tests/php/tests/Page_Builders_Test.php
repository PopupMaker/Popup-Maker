<?php
/**
 * Page builder foundation tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Base\PageBuilder;
use PopupMaker\Services\BuilderPreviewUrl;

/**
 * Verify the shared builder contract and coordinator.
 */
class Page_Builders_Test extends WP_UnitTestCase {

	/** @var array<string,mixed> */
	private $original_get;

	/** @return void */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
	}

	/** @return void */
	public function tearDown(): void {
		$_GET = $this->original_get;
		wp_set_current_user( 0 );
		wp_dequeue_script( 'pum-builder-preview' );
		wp_dequeue_style( 'pum-builder-preview' );

		parent::tearDown();
	}

	/** @return void */
	public function test_signed_preview_url_round_trips() {
		$popup_id      = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder       = $this->make_builder( 'preview-only' );
		$builder->owns = false;
		$controller    = $this->make_controller( [ $builder ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$url = BuilderPreviewUrl::create( $popup_id, 'preview-only' );
		wp_parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$this->apply_request( $url );

		$this->assertSame( 'true', $query['preview'] );
		$this->assertSame( (string) $popup_id, $query['preview_id'] );
		$this->assertSame( 1, wp_verify_nonce( $query['preview_nonce'], 'post_preview_' . $popup_id ) );
		$this->assertSame( $popup_id, BuilderPreviewUrl::read_request( 'preview-only' ) );
		$this->assertSame( $popup_id, $controller->get_edit_popup_id() );
		$this->assertSame( $popup_id, $controller->allow_builder_request( [] )['p'] );
	}

	/** @return void */
	public function test_signed_preview_is_bound_to_builder_and_popup() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$other_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->apply_request( BuilderPreviewUrl::create( $popup_id, 'bricks' ) );

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'elementor' ) );

		$_GET['p'] = (string) $other_id;
		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ) );
	}

	/** @return void */
	public function test_unsigned_preview_requests_are_rejected() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$_GET = [
			'pum-builder-preview' => 'bricks',
			'p'                   => (string) $popup_id,
		];

		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ) );

		$_GET['_wpnonce'] = 'tampered';
		$this->assertSame( 0, BuilderPreviewUrl::read_request( 'bricks' ) );
	}

	/** @return void */
	public function test_builder_request_requires_login_permission_and_popup_post_type() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $popup_id;

		wp_set_current_user( 0 );
		$this->assertSame( 0, $this->make_controller( [ $builder ] )->get_edit_popup_id() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertSame( 0, $this->make_controller( [ $builder ] )->get_edit_popup_id() );

		$page_id                     = $this->factory->post->create( [ 'post_type' => 'page' ] );
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $page_id;
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 0, $this->make_controller( [ $builder ] )->get_edit_popup_id() );
	}

	/** @return void */
	public function test_authorized_draft_request_restores_private_popup_query() {
		$popup_id                    = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $popup_id;

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$query = $this->make_controller( [ $builder ] )->allow_builder_request( [] );

		$this->assertSame( $popup_id, $query['p'] );
		$this->assertSame( 'popup', $query['post_type'] );
		$this->assertSame( 'draft', $query['post_status'] );
	}

	/** @return void */
	public function test_ordinary_request_leaves_query_untouched() {
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = 0;
		$controller                  = $this->make_controller( [ $builder ] );

		$this->assertSame( [ 'name' => 'hello-world' ], $controller->allow_builder_request( [ 'name' => 'hello-world' ] ) );
		$this->assertFalse( get_post_type_object( 'popup' )->publicly_queryable );
	}

	/** @return void */
	public function test_shell_request_keeps_builder_template_and_renders_no_popups() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $popup_id;
		$builder->canvas             = false;
		$controller                  = $this->make_controller( [ $builder ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$popups = \PopupMaker\plugin()->get_controller( 'Frontend\Popups' );
		add_action( 'wp_footer', [ $popups, 'render_popups' ] );

		$this->assertSame( 0, $controller->get_canvas_popup_id() );
		$this->assertSame( 'theme.php', $controller->use_popup_canvas( 'theme.php' ) );
		$this->assertFalse( has_action( 'wp_footer', [ $popups, 'render_popups' ] ) );
	}

	/** @return void */
	public function test_builder_preview_strips_live_popup_triggers() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $popup_id;
		$builders                    = $this->make_controller( [ $builder ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$container = new class( $builders ) {

			/** @var \PopupMaker\Controllers\Builders */
			private $builders;

			/** @param \PopupMaker\Controllers\Builders $builders Builder controller. */
			public function __construct( $builders ) {
				$this->builders = $builders;
			}

			/**
			 * @param string $name Controller name.
			 * @return \PopupMaker\Controllers\Builders|null
			 */
			public function get_controller( $name ) {
				return 'Builders' === $name ? $this->builders : null;
			}
		};
		$previews  = new \PopupMaker\Controllers\Previews( $container );
		$triggers  = [ [ 'type' => 'auto_open' ] ];
		$data_attr = $previews->data_attr( [ 'triggers' => $triggers ], $popup_id );
		$settings  = $previews->get_public_settings(
			[ 'triggers' => $triggers ],
			pum_get_popup( $popup_id )
		);

		$this->assertSame( [], $data_attr['triggers'] );
		$this->assertSame( [], $settings['triggers'] );
	}

	/** @return void */
	public function test_draft_canvas_preloads_popup_and_preview_assets() {
		$popup_id                    = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$builder                     = $this->make_builder( 'stub' );
		$builder->requested_popup_id = $popup_id;
		$controller                  = $this->make_controller( [ $builder ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );
		$script_was_registered = wp_script_is( 'pum-builder-preview', 'registered' );
		$style_was_registered  = wp_style_is( 'pum-builder-preview', 'registered' );

		if ( ! $script_was_registered ) {
			wp_register_script( 'pum-builder-preview', false, [], 'test', true );
		}

		if ( ! $style_was_registered ) {
			wp_register_style( 'pum-builder-preview', false, [], 'test' );
		}

		$preloaded = [];
		$capture   = function ( $loaded_id ) use ( &$preloaded ) {
			$preloaded[] = absint( $loaded_id );
		};

		add_action( 'pum_preload_popup', $capture );
		do_action( 'wp_enqueue_scripts' );
		remove_action( 'pum_preload_popup', $capture );

		try {
			$this->assertContains( $popup_id, $preloaded );
			$this->assertTrue( wp_script_is( 'pum-builder-preview', 'enqueued' ) );
			$this->assertTrue( wp_style_is( 'pum-builder-preview', 'enqueued' ) );
			$this->assertContains( 'pum-builder-preview', $controller->filter_canvas_body_classes( [] ) );
		} finally {
			if ( ! $script_was_registered ) {
				wp_deregister_script( 'pum-builder-preview' );
			}

			if ( ! $style_was_registered ) {
				wp_deregister_style( 'pum-builder-preview' );
			}
		}
	}

	/** @return void */
	public function test_builder_boot_is_idempotent_and_retries_late_availability() {
		$builder                     = $this->make_builder( 'late' );
		$builder->available          = false;
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder->requested_popup_id = $popup_id;
		$controller                  = $this->make_controller( [ $builder ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertFalse( $builder->is_ready() );
		$this->assertSame( 0, $builder->hooks_registered );
		$this->assertFalse( has_filter( 'request', [ $controller, 'allow_builder_request' ] ) );
		$this->assertSame( 0, $controller->get_edit_popup_id() );

		$builder->available = true;
		$controller->boot_builders();
		$controller->boot_builders();

		$this->assertTrue( $builder->is_ready() );
		$this->assertSame( 1, $builder->hooks_registered );
		$this->assertSame( 10, has_filter( 'request', [ $controller, 'allow_builder_request' ] ) );
		$this->assertSame( 15, has_action( 'wp_footer', [ $controller, 'flush_pending_assets_late' ] ) );
		$this->assertSame( $popup_id, $controller->get_edit_popup_id() );
	}

	/** @return void */
	public function test_owner_is_resolved_once_for_rendering_and_assets() {
		$builder                  = $this->make_builder( 'stub' );
		$builder->rendered        = 'builder content';
		$builder->collects_assets = true;
		$controller               = $this->make_controller( [ $builder ] );
		$popup_id                 = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 1, $builder->ownership_checks );
		$this->assertSame( 1, $builder->collected );
	}

	/** @return void */
	public function test_native_rendering_can_still_collect_builder_assets() {
		$builder                  = $this->make_builder( 'native' );
		$builder->rendered        = null;
		$builder->collects_assets = true;
		$controller               = $this->make_controller( [ $builder ] );
		$popup_id                 = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'native content', $controller->render_popup_content( 'native content', $popup_id ) );
		$this->assertSame( 1, $builder->collected );
	}

	/** @return void */
	public function test_empty_builder_render_replaces_stale_content() {
		$builder           = $this->make_builder( 'empty' );
		$builder->rendered = '';
		$controller        = $this->make_controller( [ $builder ] );
		$popup_id          = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( '', $controller->render_popup_content( 'stale content', $popup_id ) );
	}

	/** @return void */
	public function test_unowned_or_unavailable_builder_is_a_no_op() {
		$builder       = $this->make_builder( 'stub' );
		$builder->owns = false;
		$controller    = $this->make_controller( [ $builder ] );
		$popup_id      = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );

		$builder            = $this->make_builder( 'unavailable' );
		$builder->available = false;
		$controller         = $this->make_controller( [ $builder ] );

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );
	}

	/** @return void */
	public function test_six_popups_finalize_assets_once_per_batch() {
		$builder                  = $this->make_builder( 'stub' );
		$builder->collects_assets = true;
		$controller               = $this->make_controller( [ $builder ] );

		for ( $i = 0; $i < 6; $i++ ) {
			$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
			$controller->render_popup_content( 'original', $popup_id );
		}

		$this->assertSame( 6, $builder->collected );
		$this->assertSame( 0, $builder->finalized );

		$controller->flush_pending_assets();
		$controller->flush_pending_assets();

		$this->assertSame( 1, $builder->finalized );
		$this->assertFalse( $builder->last_after_head );
	}

	/** @return void */
	public function test_late_and_failed_asset_finalization_are_retried() {
		$builder                  = $this->make_builder( 'stub' );
		$builder->collects_assets = true;
		$builder->finalizes       = false;
		$controller               = $this->make_controller( [ $builder ] );
		$popup_id                 = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$controller->render_popup_content( 'original', $popup_id );
		$controller->flush_pending_assets();
		$this->assertSame( 1, $builder->finalized );

		$builder->finalizes = true;
		$controller->flush_pending_assets_late();
		$controller->flush_pending_assets_late();

		$this->assertSame( 2, $builder->finalized );
		$this->assertTrue( $builder->last_after_head );
	}

	/**
	 * @param string $url Preview URL.
	 * @return void
	 */
	private function apply_request( $url ) {
		$args = [];
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

		$_GET = $args;
	}

	/**
	 * @param PageBuilder[] $builders Test builders.
	 * @return \PopupMaker\Controllers\Builders
	 */
	private function make_controller( array $builders ) {
		$controller = new class( \PopupMaker\plugin(), $builders ) extends \PopupMaker\Controllers\Builders {

			/** @var PageBuilder[] */
			private $test_builders;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param PageBuilder[]           $builders Test builders.
			 */
			public function __construct( $container, array $builders ) {
				$this->test_builders = $builders;

				parent::__construct( $container );
			}

			/** @return PageBuilder[] */
			protected function default_builders() {
				return $this->test_builders;
			}
		};
		$controller->init();

		return $controller;
	}

	/**
	 * @param string $key Builder key.
	 * @return PageBuilder
	 */
	private function make_builder( $key ) {
		return new class( \PopupMaker\plugin(), $key ) extends PageBuilder {

			/** @var bool */
			public $available = true;

			/** @var int */
			public $requested_popup_id = 0;

			/** @var bool */
			public $canvas = true;

			/** @var bool */
			public $owns = true;

			/** @var string|null */
			public $rendered = null;

			/** @var bool */
			public $collects_assets = false;

			/** @var bool */
			public $finalizes = true;

			/** @var int */
			public $hooks_registered = 0;

			/** @var int */
			public $ownership_checks = 0;

			/** @var int */
			public $collected = 0;

			/** @var int */
			public $finalized = 0;

			/** @var bool */
			public $last_after_head = false;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param string                  $key Builder key.
			 */
			public function __construct( $container, $key ) {
				$this->key = $key;

				parent::__construct( $container );
			}

			/** @return bool */
			public function is_available() {
				return $this->available;
			}

			/** @return void */
			protected function register_hooks() {
				++$this->hooks_registered;
			}

			/** @return int */
			public function get_requested_popup_id() {
				return $this->requested_popup_id;
			}

			/** @return bool */
			public function is_canvas_request() {
				return $this->canvas;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function owns_document( $popup_id ) {
				unset( $popup_id );

				++$this->ownership_checks;

				return $this->owns;
			}

			/**
			 * @param int  $popup_id Popup ID.
			 * @param bool $is_canvas Whether this is the canvas.
			 * @return string|null
			 */
			public function render_document( $popup_id, $is_canvas = false ) {
				unset( $popup_id, $is_canvas );

				return $this->rendered;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function collect_document_assets( $popup_id ) {
				unset( $popup_id );

				++$this->collected;

				return $this->collects_assets;
			}

			/**
			 * @param bool $after_head Whether head output passed.
			 * @return bool
			 */
			public function finalize_document_assets( $after_head ) {
				$this->last_after_head = (bool) $after_head;
				++$this->finalized;

				return $this->finalizes;
			}
		};
	}
}
