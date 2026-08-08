<?php
/**
 * Elementor builder compatibility tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Base\PageBuilder;
use PopupMaker\Builders\Elementor;
use PopupMaker\Services\BuilderPreviewUrl;

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
		$_GET = $this->original_get;
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
	public function test_elementor_request_uses_isolated_popup_canvas() {
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

		$this->assertSame( $popup_id, $controller->get_canvas_popup_id() );
		$this->assertSame(
			Popup_Maker::$DIR . 'templates/single-popup.php',
			$controller->use_popup_canvas( 'theme-single.php' )
		);
		$this->assertFalse( has_action( 'wp_footer', [ $popups, 'render_popups' ] ) );
	}

	/** @return void */
	public function test_elementor_preview_button_uses_signed_popup_url() {
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

		$this->assertSame( 'popup', $query['post_type'] );
		$this->assertSame( (string) $popup_id, $query['p'] );
		$this->assertSame( 'elementor', $query['pum-builder-preview'] );
		$this->assertSame( $popup_id, BuilderPreviewUrl::read_request( 'elementor' ) );
	}

	/** @return void */
	public function test_signed_elementor_preview_still_requires_valid_nonce() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->apply_request( BuilderPreviewUrl::create( $popup_id, 'elementor' ) );
		$this->assertSame( $popup_id, $this->make_controller()->get_edit_popup_id() );

		$_GET['_wpnonce'] = 'tampered';
		$this->assertSame( 0, $this->make_controller()->get_edit_popup_id() );
	}

	/** @return void */
	public function test_builder_preview_adds_canonical_toolbar_edit_link() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_elementor_request( $popup_id );

		$builders  = $this->make_controller();
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
		$toolbar   = new \PopupMaker\Controllers\Admin\Toolbar( $container );

		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		}

		$admin_bar = new WP_Admin_Bar();
		$toolbar->add_preview_edit_link( $admin_bar );
		$edit_node = $admin_bar->get_node( 'edit' );

		$this->assertInstanceOf( stdClass::class, $edit_node );
		$this->assertSame( get_edit_post_link( $popup_id, 'raw' ), $edit_node->href );
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

			/** @return PageBuilder[] */
			protected function default_builders() {
				return [ $this->test_builder ];
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

	/**
	 * @param string $url Preview URL.
	 * @return void
	 */
	private function apply_request( $url ) {
		$query = [];
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$_GET = $query;
	}
}
