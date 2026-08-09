<?php
/**
 * Page builder foundation tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Base\PageBuilder;

/**
 * Verify the shared builder contract and coordinator.
 */
class Page_Builders_Test extends WP_UnitTestCase {

	/** @return void */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		wp_dequeue_script( 'popup-maker-builder-preview' );
		wp_dequeue_style( 'popup-maker-builder-preview' );

		parent::tearDown();
	}

	/** @return void */
	public function test_builder_request_requires_popup_edit_permission() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;

		wp_set_current_user( 0 );
		$this->assertSame( 0, $this->make_controller( $builder )->get_edit_popup_id() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertSame( 0, $this->make_controller( $builder )->get_edit_popup_id() );

		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $this->factory->post->create( [ 'post_type' => 'page' ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 0, $this->make_controller( $builder )->get_edit_popup_id() );
	}

	/** @return void */
	public function test_authorized_draft_request_restores_popup_query() {
		$popup_id                    = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$query = $this->make_controller( $builder )->allow_builder_request( [] );

		$this->assertSame( $popup_id, $query['p'] );
		$this->assertSame( 'popup', $query['post_type'] );
		$this->assertSame( 'draft', $query['post_status'] );
		$this->assertFalse( get_post_type_object( 'popup' )->publicly_queryable );
	}

	/** @return void */
	public function test_builder_specific_permission_can_reject_request() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$builder->can_edit           = false;
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 0, $this->make_controller( $builder )->get_edit_popup_id() );
	}

	/** @return void */
	public function test_authorized_editor_request_temporarily_owns_an_unbuilt_document() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$builder->owns               = false;
		$builder->rendered           = 'builder content';
		$controller                  = $this->make_controller( $builder );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( '', get_post_meta( $popup_id, $controller::OWNER_META_KEY, true ) );
		$this->assertSame( 0, $builder->ownership_checks );
	}

	/** @return void */
	public function test_authenticated_builder_save_persists_and_revalidates_document_owner() {
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder    = $this->make_builder();
		$controller = $this->make_controller( $builder );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertTrue( $controller->remember_document_owner( $builder, $popup_id ) );
		$this->assertSame( get_class( $builder ), get_post_meta( $popup_id, $controller::OWNER_META_KEY, true ) );

		$builder->owns     = false;
		$builder->rendered = 'saved builder content';

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( '', get_post_meta( $popup_id, $controller::OWNER_META_KEY, true ) );
		$this->assertSame( 1, $builder->ownership_checks );
	}

	/** @return void */
	public function test_document_owner_rejects_an_unauthorized_save() {
		$popup_id   = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder    = $this->make_builder();
		$controller = $this->make_controller( $builder );

		wp_set_current_user( 0 );

		$this->assertFalse( $controller->remember_document_owner( $builder, $popup_id ) );
		$this->assertSame( '', get_post_meta( $popup_id, $controller::OWNER_META_KEY, true ) );
	}

	/** @return void */
	public function test_builder_boot_retries_without_registering_twice() {
		$builder            = $this->make_builder();
		$builder->available = false;
		$controller         = $this->make_controller( $builder );

		$this->assertSame( 0, $builder->hooks_registered );
		$this->assertFalse( has_filter( 'request', [ $controller, 'allow_builder_request' ] ) );

		$builder->available = true;
		$controller->boot_builders();
		$controller->boot_builders();

		$this->assertSame( 1, $builder->hooks_registered );
		$this->assertSame( 10, has_filter( 'request', [ $controller, 'allow_builder_request' ] ) );
		$this->assertSame( PHP_INT_MAX, has_filter( 'the_content', [ $controller, 'suppress_canvas_content' ] ) );
	}

	/** @return void */
	public function test_inactive_bundled_builders_are_not_loaded_or_constructed() {
		if ( defined( 'ELEMENTOR_VERSION' ) || did_action( 'elementor/loaded' ) ) {
			$this->markTestSkipped( 'Elementor is active in this test environment.' );
		}

		$elementor_loaded = class_exists( \PopupMaker\Builders\Elementor::class, false );
		$controller       = new class( \PopupMaker\plugin() ) extends \PopupMaker\Controllers\Builders {

			/** @var int */
			public $builders_constructed = 0;

			/**
			 * @param string $builder_class Builder class.
			 * @return PageBuilder
			 */
			protected function instantiate_builder( $builder_class ) {
				++$this->builders_constructed;

				return parent::instantiate_builder( $builder_class );
			}
		};

		$controller->init();

		$this->assertSame( 0, $controller->builders_constructed );
		$this->assertSame( $elementor_loaded, class_exists( \PopupMaker\Builders\Elementor::class, false ) );
	}

	/** @return void */
	public function test_canvas_reuses_theme_and_disables_live_popup_behavior() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$controller                  = $this->make_controller( $builder );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );

		$GLOBALS['wp_query']->in_the_loop = true;
		$data_attr                        = $controller->filter_canvas_data_attr(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			$popup_id
		);
		$settings                         = $controller->filter_canvas_settings(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			pum_get_popup( $popup_id )
		);
		$close_attributes                 = $controller->filter_canvas_close_attributes(
			[ 'type' => 'button' ],
			pum_get_popup( $popup_id )
		);

		$this->assertSame( $popup_id, $controller->get_canvas_popup_id() );
		$this->assertSame( '', $controller->suppress_canvas_title( 'Duplicate popup title', $popup_id ) );
		$this->assertSame( 'Other title', $controller->suppress_canvas_title( 'Other title', $popup_id + 1 ) );
		$this->assertSame( '', $controller->suppress_canvas_content( 'duplicate builder content' ) );
		$this->assertContains( 'pum-builder-preview', $controller->filter_canvas_body_classes( [] ) );
		$this->assertTrue( $controller->is_canvas_popup_loadable( false, $popup_id ) );
		$this->assertFalse( $controller->is_canvas_popup_loadable( true, $popup_id + 1 ) );
		$this->assertSame( [], $data_attr['triggers'] );
		$this->assertSame( [], $settings['triggers'] );
		$this->assertSame( 'true', $close_attributes['aria-disabled'] );
		$this->assertSame( '-1', $close_attributes['tabindex'] );
		$this->assertSame( 'pointer-events:none', $close_attributes['style'] );
	}

	/** @return void */
	public function test_frontend_builder_shell_does_not_claim_the_canvas() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$builder->canvas             = false;
		$builder->rendered           = 'builder content';
		$controller                  = $this->make_controller( $builder );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );

		$this->assertSame( $popup_id, $controller->get_edit_popup_id() );
		$this->assertSame( 0, $controller->get_canvas_popup_id() );
		$this->assertTrue( $controller->is_canvas_popup_loadable( true, $popup_id + 1 ) );
		$this->assertSame(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			$controller->filter_canvas_data_attr( [ 'triggers' => [ [ 'type' => 'auto_open' ] ] ], $popup_id )
		);
		$this->assertSame(
			[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
			$controller->filter_canvas_settings(
				[ 'triggers' => [ [ 'type' => 'auto_open' ] ] ],
				pum_get_popup( $popup_id )
			)
		);
		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertFalse( $builder->last_editor_canvas );
	}

	/** @return void */
	public function test_builder_preview_handle_matches_workspace_dependency_mapping() {
		$assets   = \PopupMaker\plugin()->get_controller( 'Assets' );
		$packages = $assets->get_packages();

		$this->assertSame( 'popup-maker-builder-preview', $packages['builder-preview']['handle'] );
	}

	/** @return void */
	public function test_draft_canvas_preloads_popup_and_preview_assets() {
		$popup_id                    = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$controller                  = $this->make_controller( $builder );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );

		wp_register_script( 'popup-maker-builder-preview', false, [], 'test', true );
		wp_register_style( 'popup-maker-builder-preview', false, [], 'test' );
		$preloaded = [];
		$capture   = function ( $loaded_id ) use ( &$preloaded ) {
			$preloaded[] = absint( $loaded_id );
		};

		add_action( 'pum_preload_popup', $capture );
		$controller->preload_canvas_popup();
		remove_action( 'pum_preload_popup', $capture );

		$this->assertContains( $popup_id, $preloaded );
		$this->assertTrue( wp_script_is( 'popup-maker-builder-preview', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'popup-maker-builder-preview', 'enqueued' ) );
	}

	/** @return void */
	public function test_native_request_owner_is_cached_and_uses_editor_rendering() {
		$popup_id                    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder                     = $this->make_builder();
		$builder->requested_popup_id = $popup_id;
		$builder->rendered           = 'builder content';
		$controller                  = $this->make_controller( $builder );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );

		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		$this->assertSame( 0, $builder->ownership_checks );
		$this->assertTrue( $builder->last_editor_canvas );
	}

	/** @return void */
	public function test_six_popup_documents_render_through_one_builder() {
		$builder           = $this->make_builder();
		$builder->rendered = 'builder content';
		$controller        = $this->make_controller( $builder );
		$popup_ids         = [];

		for ( $index = 0; $index < 6; $index++ ) {
			$popup_id    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
			$popup_ids[] = $popup_id;

			$this->assertSame( 'builder content', $controller->render_popup_content( 'original', $popup_id ) );
		}

		$this->assertSame( $popup_ids, $builder->rendered_popup_ids );
		$this->assertSame( 6, $builder->ownership_checks );
		$this->assertSame( 1, $builder->hooks_registered );
	}

	/** @return void */
	public function test_unowned_document_preserves_content() {
		$builder           = $this->make_builder();
		$builder->owns     = false;
		$builder->rendered = 'builder content';
		$controller        = $this->make_controller( $builder );
		$popup_id          = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		$this->assertSame( 'original', $controller->render_popup_content( 'original', $popup_id ) );
	}

	/**
	 * @param PageBuilder $builder Test builder.
	 * @return \PopupMaker\Controllers\Builders
	 */
	private function make_controller( PageBuilder $builder ) {
		$controller = new class( \PopupMaker\plugin(), $builder ) extends \PopupMaker\Controllers\Builders {

			/** @var PageBuilder */
			private $test_builder;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param PageBuilder             $builder Test builder.
			 */
			public function __construct( $container, PageBuilder $builder ) {
				$this->test_builder = $builder;

				parent::__construct( $container );
			}

			/** @return string[] */
			protected function detected_builder_classes() {
				return [ get_class( $this->test_builder ) ];
			}

			/**
			 * @param string $builder_class Builder class.
			 * @return PageBuilder
			 */
			protected function instantiate_builder( $builder_class ) {
				unset( $builder_class );

				return $this->test_builder;
			}
		};
		$controller->init();

		return $controller;
	}

	/** @return PageBuilder */
	private function make_builder() {
		return new class( \PopupMaker\plugin() ) extends PageBuilder {

			/** @var bool */
			public $available = true;

			/** @var int */
			public $requested_popup_id = 0;

			/** @var bool */
			public $owns = true;

			/** @var bool */
			public $canvas = true;

			/** @var bool */
			public $can_edit = true;

			/** @var string|null */
			public $rendered = null;

			/** @var int */
			public $hooks_registered = 0;

			/** @var int */
			public $ownership_checks = 0;

			/** @var bool */
			public $last_editor_canvas = false;

			/** @var int[] */
			public $rendered_popup_ids = [];

			/** @return bool */
			public function is_available() {
				return $this->available;
			}

			/** @return void */
			public function register_hooks() {
				++$this->hooks_registered;
			}

			/** @return int */
			public function get_requested_popup_id() {
				return $this->requested_popup_id;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function can_edit_document( $popup_id ) {
				unset( $popup_id );

				return $this->can_edit;
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
			 * @param bool $is_editor_canvas Whether this is the native editor canvas.
			 * @return string|null
			 */
			public function render_document( $popup_id, $is_editor_canvas = false ) {
				$this->rendered_popup_ids[] = absint( $popup_id );
				$this->last_editor_canvas   = (bool) $is_editor_canvas;

				return $this->rendered;
			}
		};
	}
}
