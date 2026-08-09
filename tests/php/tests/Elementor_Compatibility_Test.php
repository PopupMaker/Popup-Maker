<?php
/**
 * Elementor builder compatibility tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Base\PageBuilder;
use PopupMaker\Builders\Elementor;

require_once __DIR__ . '/fixtures/class-elementor-plugin.php';

/**
 * Test Elementor popup preview requests without loading Elementor itself.
 */
class Elementor_Compatibility_Test extends WP_UnitTestCase {

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
		$_GET                        = $this->original_get;
		\Elementor\Plugin::$instance = null;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/** @return void */
	public function test_authorized_elementor_request_restores_popup_query() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_elementor_request( $popup_id );

		$controller = $this->make_controller();
		$query      = $controller->allow_builder_request( [] );

		$this->assertSame( $popup_id, $query['p'] );
		$this->assertSame( 'popup', $query['post_type'] );
		$this->assertSame( 'draft', $query['post_status'] );
	}

	/** @return void */
	public function test_elementor_request_requires_matching_ids_and_post_type() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = new Elementor( \PopupMaker\plugin() );

		$this->set_elementor_request( $popup_id );
		$this->assertSame( $popup_id, $builder->get_requested_popup_id() );

		$_GET['p'] = (string) ( $popup_id + 1 );
		$this->assertSame( 0, $builder->get_requested_popup_id() );

		$_GET['p']         = (string) $popup_id;
		$_GET['post_type'] = 'page';
		$this->assertSame( 0, $builder->get_requested_popup_id() );
	}

	/** @return void */
	public function test_elementor_request_requires_login_and_popup_permission() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$this->set_elementor_request( $popup_id );

		wp_set_current_user( 0 );
		$this->assertSame( 0, $this->make_controller()->get_edit_popup_id() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertSame( 0, $this->make_controller()->get_edit_popup_id() );
	}

	/** @return void */
	public function test_elementor_request_uses_theme_popup_canvas() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_elementor_request( $popup_id );
		$this->go_to( add_query_arg( [
			'post_type' => 'popup',
			'p'         => $popup_id,
		], home_url( '/' ) ) );
		$this->set_elementor_request( $popup_id );

		$controller = $this->make_controller();
		$popups     = \PopupMaker\plugin()->get_controller( 'Frontend\Popups' );
		add_action( 'wp_footer', [ $popups, 'render_popups' ] );
		$GLOBALS['wp_query']->in_the_loop = true;

		$this->assertSame( $popup_id, $controller->get_canvas_popup_id() );
		$this->assertSame( '', $controller->suppress_canvas_content( 'duplicate document' ) );
		$this->assertNotFalse( has_action( 'wp_footer', [ $popups, 'render_popups' ] ) );
	}

	/** @return void */
	public function test_elementor_preview_button_uses_real_page_preview_url() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = new Elementor( \PopupMaker\plugin() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$document    = new class( $popup_id ) {

			/** @var int */
			private $popup_id;

			/** @param int $popup_id Popup ID. */
			public function __construct( $popup_id ) {
				$this->popup_id = $popup_id;
			}

			/** @return int */
			public function get_main_id() {
				return $this->popup_id;
			}
		};
		$preview_url = $builder->filter_preview_url( '', $document );
		$query       = [];
		parse_str( (string) wp_parse_url( $preview_url, PHP_URL_QUERY ), $query );
		$_GET = $query;

		$previews = \PopupMaker\plugin()->get_controller( 'Previews' );

		$this->assertSame( home_url( '/' ), strtok( $preview_url, '?' ) );
		$this->assertSame( (string) $popup_id, $query['popup'] );
		$this->assertSame( 'true', $query['preview'] );
		$this->assertSame( $popup_id, $previews->get_popup_preview() );
		$this->assertSame( 0, $this->make_controller()->get_edit_popup_id() );
	}

	/** @return void */
	public function test_preview_filter_tolerates_a_missing_document_argument() {
		$builder = new Elementor( \PopupMaker\plugin() );

		$this->assertSame( 'https://example.com/preview', $builder->filter_preview_url( 'https://example.com/preview' ) );
	}

	/** @return void */
	public function test_elementor_registers_its_authenticated_save_hook() {
		$builder = new Elementor( \PopupMaker\plugin() );

		$builder->register_hooks();

		$this->assertSame( 10, has_action( 'elementor/editor/after_save', [ $builder, 'remember_saved_document' ] ) );
		$this->assertSame( 11, has_action( 'template_redirect', [ $builder, 'disable_canvas_content_filter' ] ) );

		remove_action( 'elementor/editor/after_save', [ $builder, 'remember_saved_document' ], 10 );
		remove_action( 'template_redirect', [ $builder, 'disable_canvas_content_filter' ], 11 );
		remove_filter( 'elementor/document/urls/wp_preview', [ $builder, 'filter_preview_url' ], 10 );
	}

	/** @return void */
	public function test_elementor_canvas_skips_its_native_content_render() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$this->set_elementor_request( $popup_id );

		$frontend  = new class() {

			/** @var int */
			public $render_count = 0;

			/** @return void */
			public function remove_content_filter() {
				remove_filter( 'the_content', [ $this, 'apply_builder_in_content' ], 9 );
			}

			/**
			 * @param mixed $content Post content.
			 * @return mixed
			 */
			public function apply_builder_in_content( $content ) {
				++$this->render_count;

				return $content;
			}
		};
		$elementor = new \Elementor\Plugin();

		$elementor->frontend         = $frontend;
		\Elementor\Plugin::$instance = $elementor;

		$builder = new class( \PopupMaker\plugin(), $popup_id ) extends Elementor {

			/** @var int */
			private $canvas_popup_id;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param int                     $popup_id  Canvas popup ID.
			 */
			public function __construct( $container, $popup_id ) {
				parent::__construct( $container );

				$this->canvas_popup_id = $popup_id;
			}

			/** @return int */
			protected function get_canvas_popup_id() {
				return $this->canvas_popup_id;
			}
		};

		add_filter( 'the_content', [ $frontend, 'apply_builder_in_content' ], 9 );

		$builder->disable_canvas_content_filter();
		apply_filters( 'the_content', 'Theme loop content.' );

		$this->assertSame( 0, $frontend->render_count );
	}

	/**
	 * @return \PopupMaker\Controllers\Builders
	 */
	private function make_controller() {
		$builder    = new class( \PopupMaker\plugin() ) extends Elementor {

			/** @return bool */
			public function is_available() {
				return true;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function can_edit_document( $popup_id ) {
				unset( $popup_id );

				return true;
			}
		};
		$controller = new class( \PopupMaker\plugin(), $builder ) extends \PopupMaker\Controllers\Builders {

			/** @var PageBuilder */
			private $test_builder;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param PageBuilder             $builder Builder integration.
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

	/**
	 * @param int $popup_id Popup ID.
	 * @return void
	 */
	private function set_elementor_request( $popup_id ) {
		$_GET = [
			'elementor-preview' => (string) $popup_id,
			'p'                 => (string) $popup_id,
			'post_type'         => 'popup',
		];
	}
}
