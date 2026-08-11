<?php
/**
 * Etch builder shell compatibility tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Base\PageBuilder;
use PopupMaker\Builders\Etch;

/**
 * Test Etch requests without loading Etch itself.
 */
class Etch_Builder_Test extends WP_UnitTestCase {

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
	public function test_etch_request_requires_the_magic_area() {
		$builder = new Etch( \PopupMaker\plugin() );

		$_GET = [
			'etch'    => 'magic',
			'post_id' => '123',
		];
		$this->assertSame( 123, $builder->get_requested_popup_id() );

		$_GET['etch'] = 'frontend';
		$this->assertSame( 0, $builder->get_requested_popup_id() );

		unset( $_GET['post_id'] );
		$this->assertSame( 0, $builder->get_requested_popup_id() );
	}

	/** @return void */
	public function test_etch_matches_its_administrator_permission_gate() {
		$builder = new Etch( \PopupMaker\plugin() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'editor' ] ) );
		$this->assertFalse( $builder->can_edit_document( 123 ) );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( $builder->can_edit_document( 123 ) );
	}

	/** @return void */
	public function test_authorized_etch_shell_preserves_front_page_and_suppresses_popups() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'draft',
			]
		);

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_etch_request( $popup_id );

		$controller = $this->make_controller();
		$query      = [ 'page_id' => 2 ];

		$this->assertSame( $query, $controller->allow_builder_request( $query ) );
		$this->assertSame( $popup_id, $controller->get_edit_popup_id() );
		$this->assertSame( 0, $controller->get_canvas_popup_id() );
		$this->assertFalse( $controller->is_canvas_popup_loadable( true, $popup_id ) );
		$this->assertFalse( $controller->is_canvas_popup_loadable( true, $popup_id + 1 ) );
	}

	/** @return void */
	public function test_unauthorized_etch_request_does_not_change_popup_loading() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'subscriber' ] ) );
		$this->set_etch_request( $popup_id );

		$controller = $this->make_controller();

		$this->assertSame( 0, $controller->get_edit_popup_id() );
		$this->assertTrue( $controller->is_canvas_popup_loadable( true, $popup_id ) );
	}

	/** @return void */
	public function test_popup_rows_include_an_etch_launch_action_for_administrators() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = new Etch( \PopupMaker\plugin() );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$actions = $builder->filter_row_actions( [], get_post( $popup_id ) );

		$this->assertArrayHasKey( 'edit_with_etch', $actions );
		$this->assertStringContainsString( 'etch=magic', $actions['edit_with_etch'] );
		$this->assertStringContainsString( 'post_id=' . $popup_id, $actions['edit_with_etch'] );
	}

	/**
	 * @return \PopupMaker\Controllers\Builders
	 */
	private function make_controller() {
		$builder    = new class( \PopupMaker\plugin() ) extends Etch {

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
	private function set_etch_request( $popup_id ) {
		$_GET = [
			'etch'    => 'magic',
			'post_id' => (string) $popup_id,
		];
	}
}
