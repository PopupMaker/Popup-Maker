<?php
/**
 * Bricks provider tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\Bricks;

/**
 * Test the Bricks provider's handling of Bricks' shared request-scoped state.
 *
 * These regressions are easy to reintroduce because they involve statics owned by
 * another product. Each test is skipped when Bricks is absent, so the suite still
 * runs on a plain WordPress install.
 */
class Bricks_Provider_Test extends WP_UnitTestCase {

	/**
	 * Provider under test.
	 *
	 * @var Bricks|null
	 */
	private $provider;

	/**
	 * Skip unless Bricks is installed, then build a fresh provider.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->provider = new Bricks( \PopupMaker\plugin() );

		if ( ! $this->provider->is_available() ) {
			$this->markTestSkipped( 'Bricks is not installed.' );
		}

		/**
		 * Bricks sanitizes element data on save and strips it for users who
		 * cannot execute code (`Ajax::sanitize_bricks_postmeta()` calling
		 * `Helpers::security_check_elements_before_save()`), so the fixtures below
		 * need a capable user.
		 */
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Restore the current user.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Create a Bricks-built popup.
	 *
	 * @param string $element_id Element ID used in generated selectors.
	 *
	 * @return int Popup ID.
	 */
	private function create_bricks_popup( $element_id = 'testel' ) {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta(
			$popup_id,
			'_bricks_page_content_2',
			[
				[
					'id'       => $element_id,
					'name'     => 'heading',
					'parent'   => 0,
					'children' => [],
					'settings' => [
						'text'        => 'Heading',
						'tag'         => 'h2',
						'_typography' => [ 'font-size' => 31 ],
					],
				],
			]
		);

		update_post_meta( $popup_id, '_bricks_editor_mode', 'bricks' );

		return $popup_id;
	}

	/**
	 * CSS Popup Maker did not generate is never emitted or discarded.
	 *
	 * `Assets::$inline_css['popup']` is Bricks' shared bucket: Bricks appends its
	 * own native popup CSS to it and reads it back without clearing it.
	 *
	 * @return void
	 */
	public function test_native_bricks_popup_css_is_left_untouched() {
		$popup_id = $this->create_bricks_popup( 'ownedel' );

		\Bricks\Assets::$inline_css['popup'] = '/*NATIVE*/';

		$this->provider->collect_document_assets( $popup_id );

		$this->assertSame(
			'/*NATIVE*/',
			\Bricks\Assets::$inline_css['popup'],
			'Collection must hand the shared bucket back unchanged.'
		);

		ob_start();
		$this->provider->finalize_document_assets( true );
		$printed = ob_get_clean();

		$this->assertStringContainsString( 'ownedel', $printed, 'Our own CSS should be emitted.' );
		$this->assertStringNotContainsString( 'NATIVE', $printed, 'Foreign CSS must not be emitted by us.' );
		$this->assertSame( '/*NATIVE*/', \Bricks\Assets::$inline_css['popup'], 'Foreign CSS must survive finalization.' );

		$this->provider->collect_document_assets( $this->create_bricks_popup( 'earlyel' ) );

		ob_start();
		$this->provider->finalize_document_assets( false );
		$early_output = ob_get_clean();
		$inline_css   = wp_styles()->get_data( 'pum-bricks-popup-inline', 'after' );

		wp_dequeue_style( 'pum-bricks-popup-inline' );
		wp_deregister_style( 'pum-bricks-popup-inline' );

		$this->assertSame( '', trim( $early_output ), 'An enqueue callback must not print markup before the document.' );
		$this->assertStringContainsString( 'earlyel', implode( '', (array) $inline_css ), 'Early CSS should be queued for wp_head().' );
	}

	/**
	 * A second finalization does not repeat the batch.
	 *
	 * @return void
	 */
	public function test_finalization_does_not_double_emit() {
		$this->provider->collect_document_assets( $this->create_bricks_popup( 'dupel' ) );

		ob_start();
		$this->provider->finalize_document_assets( true );
		$first = ob_get_clean();

		ob_start();
		$this->provider->finalize_document_assets( true );
		$second = ob_get_clean();

		$this->assertStringContainsString( 'dupel', $first );
		$this->assertSame( '', trim( $second ), 'The second flush must emit nothing.' );
	}

	/**
	 * Each popup's CSS is generated once even if collected repeatedly.
	 *
	 * @return void
	 */
	public function test_repeated_collection_generates_css_once() {
		$popup_id = $this->create_bricks_popup( 'oncel' );

		$this->assertSame( '', $this->provider->render_document( $popup_id ) );
		$this->provider->collect_document_assets( $popup_id );

		ob_start();
		$this->provider->finalize_document_assets( true );
		$printed = ob_get_clean();

		$this->assertSame( 1, substr_count( $printed, '#brxe-oncel' ), 'CSS should appear once per popup.' );
	}

	/**
	 * Rendering restores the element map Bricks was already rendering.
	 *
	 * `render_data()` overwrites `Frontend::$elements` and `$area` without
	 * restoring them, and this render can be nested inside the host page's own.
	 *
	 * @return void
	 */
	public function test_render_restores_bricks_element_state() {
		$popup_id = $this->create_bricks_popup( 'nestel' );

		\Bricks\Frontend::$elements = [ 'sentinel' => [ 'id' => 'sentinel' ] ];
		\Bricks\Frontend::$area     = 'header';

		$this->provider->render_document( $popup_id );

		$this->assertSame( [ 'sentinel' => [ 'id' => 'sentinel' ] ], \Bricks\Frontend::$elements );
		$this->assertSame( 'header', \Bricks\Frontend::$area );
	}

	/** @return void */
	public function test_editor_canvas_uses_bricks_owned_render_tree() {
		$popup_id = $this->create_bricks_popup( 'canvastree' );

		$this->assertSame( '', $this->provider->render_document( $popup_id, true ) );
	}

	/**
	 * Rendering restores the global post.
	 *
	 * @return void
	 */
	public function test_render_restores_global_post() {
		$popup_id = $this->create_bricks_popup( 'postel' );
		$page_id  = $this->factory->post->create( [ 'post_type' => 'page' ] );

		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test fixture.
		$post = get_post( $page_id );

		$this->provider->render_document( $popup_id );

		$this->assertSame( $page_id, $post->ID, 'The host page must remain current after rendering.' );
	}

	/**
	 * Rendering does not run Bricks' one-time template bootstrap.
	 *
	 * @return void
	 */
	public function test_render_does_not_populate_active_templates() {
		$before = \Bricks\Database::$active_templates;

		$this->provider->render_document( $this->create_bricks_popup( 'tmplel' ) );

		$this->assertSame( $before, \Bricks\Database::$active_templates );
	}

	/**
	 * The popup is registered for Bricks page-settings CSS generation.
	 *
	 * @return void
	 */
	public function test_collection_registers_popup_for_page_settings() {
		$popup_id = $this->create_bricks_popup( 'pagesetel' );

		$this->provider->collect_document_assets( $popup_id );

		$this->assertContains( $popup_id, \Bricks\Assets::$page_settings_post_ids );

		$late_popup_id = $this->create_bricks_popup( 'latepageset' );

		update_post_meta(
			$late_popup_id,
			BRICKS_DB_PAGE_SETTINGS,
			[
				'customCss'               => '#brxe-latepageset{outline:17px solid red}',
				'customScriptsHeader'     => '<script>window.pumLateHeader=1;</script>',
				'customScriptsBodyHeader' => '<script>window.pumLateBodyHeader=1;</script>',
			]
		);

		global $wp_actions;

		$previous_head      = isset( $wp_actions['wp_head'] ) ? $wp_actions['wp_head'] : null;
		$previous_body_open = isset( $wp_actions['wp_body_open'] ) ? $wp_actions['wp_body_open'] : null;

		$wp_actions['wp_head']      = 1;
		$wp_actions['wp_body_open'] = 1;

		$this->provider->collect_document_assets( $late_popup_id );

		ob_start();
		$this->provider->finalize_document_assets( true );
		$printed = ob_get_clean();

		if ( null === $previous_head ) {
			unset( $wp_actions['wp_head'] );
		} else {
			$wp_actions['wp_head'] = $previous_head;
		}

		if ( null === $previous_body_open ) {
			unset( $wp_actions['wp_body_open'] );
		} else {
			$wp_actions['wp_body_open'] = $previous_body_open;
		}

		$this->assertStringContainsString( '#brxe-latepageset', $printed );
		$this->assertStringContainsString( 'window.pumLateHeader=1', $printed );
		$this->assertStringContainsString( 'window.pumLateBodyHeader=1', $printed );
	}

	/**
	 * Empty documents still collect their page-level styles and scripts.
	 *
	 * @return void
	 */
	public function test_empty_document_collects_page_settings() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		update_post_meta( $popup_id, '_bricks_page_content_2', [] );
		update_post_meta( $popup_id, '_bricks_editor_mode', 'bricks' );
		update_post_meta(
			$popup_id,
			BRICKS_DB_PAGE_SETTINGS,
			[ 'customCss' => '.empty-bricks-popup{outline:17px solid red}' ]
		);

		global $wp_actions;

		$previous_head         = isset( $wp_actions['wp_head'] ) ? $wp_actions['wp_head'] : null;
		$wp_actions['wp_head'] = 1;

		$this->assertSame( '', $this->provider->render_document( $popup_id ) );

		ob_start();
		$this->provider->finalize_document_assets( true );
		$printed = ob_get_clean();

		if ( null === $previous_head ) {
			unset( $wp_actions['wp_head'] );
		} else {
			$wp_actions['wp_head'] = $previous_head;
		}

		$this->assertContains( $popup_id, \Bricks\Assets::$page_settings_post_ids );
		$this->assertStringContainsString( '.empty-bricks-popup', $printed );
	}

	/**
	 * The injected post type is not written back to Bricks' stored settings.
	 *
	 * Bricks reads these settings, modifies them, and writes the whole array back
	 * in several places, which would otherwise persist our runtime injection.
	 *
	 * @return void
	 */
	public function test_injected_post_type_is_stripped_on_write() {
		$filtered = $this->provider->strip_injected_post_type(
			[ 'postTypes' => [ 'page', 'popup' ] ],
			[ 'postTypes' => [ 'page' ] ]
		);

		$this->assertSame( [ 'page' ], $filtered['postTypes'] );

		$filtered_old = $this->provider->filter_global_settings( [ 'postTypes' => [ 'page' ] ] );
		$filtered     = $this->provider->strip_injected_post_type(
			[
				'postTypes' => [ 'page', 'popup' ],
				'probe'     => true,
			],
			$filtered_old
		);

		$this->assertSame( [ 'page' ], $filtered['postTypes'] );
	}

	/**
	 * A post type the owner enabled themselves is preserved on write.
	 *
	 * @return void
	 */
	public function test_owner_enabled_post_type_is_preserved_on_write() {
		$filtered = $this->provider->strip_injected_post_type(
			[ 'postTypes' => [ 'page', 'popup' ] ],
			[ 'postTypes' => [ 'page', 'popup' ] ]
		);

		$this->assertSame( [ 'page', 'popup' ], $filtered['postTypes'] );
	}

	/**
	 * Bricks accepts popup registration and its native preview request.
	 *
	 * @return void
	 */
	public function test_popup_post_type_is_supported_after_registration() {
		$this->provider->register_post_type_support();

		$popup_id = $this->create_bricks_popup( 'previewel' );
		update_post_meta(
			$popup_id,
			'popup_settings',
			[
				'position_fixed'   => true,
				'overlay_disabled' => true,
				'close_text'       => 'fas fa-camera',
			]
		);

		$this->assertTrue( \Bricks\Helpers::is_post_type_supported( $popup_id ) );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Test fixture.
		$previous_get = $_GET;
		$_GET         = [
			'p'              => (string) $popup_id,
			'bricks_preview' => (string) time(),
		];
		$builders     = $this->make_controller();

		$this->go_to(
			add_query_arg(
				$_GET,
				home_url( '/' )
			)
		);

		try {
			$this->assertSame( $popup_id, $this->provider->get_requested_popup_id() );
			$this->assertTrue( $this->provider->is_canvas_request() );

			$this->assertSame( $popup_id, $builders->get_canvas_popup_id() );
			$canvas_body_classes = $this->provider->filter_canvas_body_classes( [ 'existing' ] );

			$this->assertSame(
				array_merge( [ 'existing' ], $this->provider->filter_canvas_body_classes( [] ) ),
				$canvas_body_classes
			);
			$this->assertGreaterThan( 1, count( $canvas_body_classes ), 'The popup theme must remain above Bricks-owned content.' );
			$this->assertContains( 'pum-overlay-disabled', $canvas_body_classes );
			$this->assertSame(
				[ 'pum-container', 'pum-content' ],
				$this->provider->filter_content_attributes( [] )['class']
			);
			$this->assertSame(
				[ 'existing', 'split', 'classes', '7', 'pum-container', 'pum-content' ],
				$this->provider->filter_content_attributes(
					[ 'class' => [ 'existing split', new stdClass(), 'classes', 7, [] ] ]
				)['class']
			);
			$this->assertSame( PHP_INT_MAX, has_action( 'save_post_popup', [ $this->provider, 'remember_saved_document' ] ) );

			$this->provider->enqueue_canvas_assets();

			$localized = wp_scripts()->get_data( 'popup-maker-builder-preview', 'data' );

			$this->assertIsString( $localized );
			$this->assertMatchesRegularExpression( '/var pumBuilderOwnedCanvas = (.+);/', $localized );

			preg_match( '/var pumBuilderOwnedCanvas = (.+);/', $localized, $matches );
			$display = json_decode( $matches[1], true );

			$this->assertSame( '1', $display['position_fixed'] );
			$this->assertSame( '<i class="fas fa-camera"></i>', $display['close_content'] );
		} finally {
			$this->remove_controller_hooks( $builders );
				wp_dequeue_script( 'popup-maker-builder-preview' );
			$_GET = $previous_get;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Editor mode distinguishes an empty Bricks document from a non-Bricks popup.
	 *
	 * @return void
	 */
	public function test_document_ownership_preserves_empty_bricks_content() {
		$popup_id = $this->factory->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);

		$this->assertFalse( $this->provider->owns_document( $popup_id ) );
		$this->assertNull( $this->provider->render_document( $popup_id ) );

		$empty_popup_id = $this->factory->post->create(
			[
				'post_type'    => 'popup',
				'post_status'  => 'publish',
				'post_content' => 'Stale WordPress content.',
			]
		);

		update_post_meta( $empty_popup_id, '_bricks_page_content_2', [] );
		update_post_meta( $empty_popup_id, '_bricks_editor_mode', 'bricks' );

		$this->assertTrue( $this->provider->owns_document( $empty_popup_id ) );
		$this->assertSame( '', $this->provider->render_document( $empty_popup_id ) );
	}

	/**
	 * The rendered document carries no `brx-content` wrapper.
	 *
	 * `render_content()` would add `<main id="brx-content">`, duplicating that ID
	 * when a Bricks page renders on the same request.
	 *
	 * @return void
	 */
	public function test_rendered_document_has_no_content_wrapper() {
		$rendered = $this->provider->render_document( $this->create_bricks_popup( 'wrapel' ) );

		$this->assertIsString( $rendered );
		$this->assertStringNotContainsString( 'brx-content', $rendered );
		$this->assertStringContainsString( 'brxe-heading', $rendered );
	}

	/** @return \PopupMaker\Controllers\Builders */
	private function make_controller() {
		$controller = new class( \PopupMaker\plugin(), $this->provider ) extends \PopupMaker\Controllers\Builders {

			/** @var Bricks */
			private $test_builder;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param Bricks                  $builder Test builder.
			 */
			public function __construct( $container, Bricks $builder ) {
				$this->test_builder = $builder;

				parent::__construct( $container );
			}

			/** @return string[] */
			protected function detected_builder_classes() {
				return [ get_class( $this->test_builder ) ];
			}

			/**
			 * @param string $builder_class Builder class.
			 * @return Bricks
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
	 * Remove hooks registered by the isolated test controller.
	 *
	 * @param \PopupMaker\Controllers\Builders $controller Test controller.
	 * @return void
	 */
	private function remove_controller_hooks( $controller ) {
		remove_action( 'plugins_loaded', [ $controller, 'boot_builders' ], 20 );
		remove_action( 'after_setup_theme', [ $controller, 'boot_builders' ], 20 );
		remove_action( 'init', [ $controller, 'boot_builders' ], 20 );
		remove_filter( 'request', [ $controller, 'allow_builder_request' ] );
		remove_filter( 'body_class', [ $controller, 'filter_canvas_body_classes' ] );
		remove_filter( 'the_content', [ $controller, 'suppress_canvas_content' ], PHP_INT_MAX );
		remove_filter( 'pum_popup_is_loadable', [ $controller, 'is_canvas_popup_loadable' ], 1001 );
		remove_filter( 'pum_popup_data_attr', [ $controller, 'filter_canvas_data_attr' ], 1001 );
		remove_filter( 'pum_popup_get_public_settings', [ $controller, 'filter_canvas_settings' ], 1001 );
		remove_filter( 'pum_popup_content', [ $controller, 'render_popup_content' ], 1000 );
		remove_action( 'wp_enqueue_scripts', [ $controller, 'preload_canvas_popup' ], 11 );
	}
}
