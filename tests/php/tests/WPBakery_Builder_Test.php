<?php
/**
 * WPBakery Page Builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\WPBakery;

/**
 * Verify WPBakery document ownership and generated style output.
 */
class WPBakery_Builder_Test extends WP_UnitTestCase {

	/** @return void */
	public function test_document_ownership_requires_wpbakery_popup_meta() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$page_id  = $this->factory->post->create( [ 'post_type' => 'page' ] );
		$builder  = new WPBakery( \PopupMaker\plugin() );

		$this->assertFalse( $builder->owns_document( $popup_id ) );

		update_post_meta( $popup_id, '_wpb_vc_js_status', 'false' );
		$this->assertFalse( $builder->owns_document( $popup_id ) );

		update_post_meta( $popup_id, '_wpb_vc_js_status', 'true' );
		$this->assertTrue( $builder->owns_document( $popup_id ) );

		update_post_meta( $page_id, '_wpb_vc_js_status', 'true' );
		$this->assertFalse( $builder->owns_document( $page_id ) );
	}

	/** @return void */
	public function test_preloaded_document_styles_are_batched_and_deduplicated() {
		$builder = $this->make_output_builder();

		$this->assertNull( $builder->render_document( 123 ) );
		$this->assertNull( $builder->render_document( 123 ) );
		$this->assertNull( $builder->render_document( 456 ) );

		ob_start();
		$builder->flush_preloaded_assets();
		$builder->flush_preloaded_assets();
		$output = ob_get_clean();

		$this->assertSame( [ 123, 456 ], $builder->page_css_ids );
		$this->assertSame( [ 123, 456 ], $builder->shortcode_css_ids );
		$this->assertSame( 1, substr_count( $output, 'page-css-123' ) );
		$this->assertSame( 1, substr_count( $output, 'shortcode-css-123' ) );
		$this->assertSame( 1, substr_count( $output, 'page-css-456' ) );
		$this->assertSame( 1, substr_count( $output, 'shortcode-css-456' ) );
	}

	/** @return void */
	public function test_late_document_styles_are_prepended_once() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$other_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = $this->make_output_builder();

		update_post_meta( $popup_id, '_wpb_vc_js_status', 'true' );
		$builder->head_rendered = true;

		$rendered = $builder->inject_late_document_styles( '<p>Popup content</p>', $popup_id );

		$this->assertStringStartsWith( '<style>page-css-' . $popup_id, $rendered );
		$this->assertStringEndsWith( '<p>Popup content</p>', $rendered );
		$this->assertSame( '<p>Popup content</p>', $builder->inject_late_document_styles( '<p>Popup content</p>', $popup_id ) );
		$this->assertSame( '<p>Other content</p>', $builder->inject_late_document_styles( '<p>Other content</p>', $other_id ) );
		$this->assertSame( [ $popup_id ], $builder->page_css_ids );
		$this->assertSame( [ $popup_id ], $builder->shortcode_css_ids );
	}

	/** @return void */
	public function test_builder_registers_preloaded_and_late_style_hooks() {
		$builder = $this->make_output_builder();

		$builder->register_hooks();

		$this->assertSame( 1000, has_action( 'wp_head', [ $builder, 'flush_preloaded_assets' ] ) );
		$this->assertSame( 12, has_filter( 'pum_popup_content', [ $builder, 'inject_late_document_styles' ] ) );

		remove_action( 'wp_head', [ $builder, 'flush_preloaded_assets' ], 1000 );
		remove_filter( 'pum_popup_content', [ $builder, 'inject_late_document_styles' ], 12 );
	}

	/** @return void */
	public function test_modern_wpbakery_css_apis_receive_popup_id() {
		$base    = new class() {

			/** @var array<int> */
			public $shortcode_ids = [];

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			public function addShortcodesCss( $popup_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors WPBakery's API.
				$this->shortcode_ids[] = $popup_id;
				echo '<style>modern-shortcode-css</style>';
			}
		};
		$module  = new class() {

			/** @var array<int> */
			public $page_ids = [];

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			public function output_custom_css_to_page( $popup_id ) {
				$this->page_ids[] = $popup_id;
				echo '<style>modern-page-css</style>';
			}
		};
		$builder = $this->make_service_builder( $base, $module );

		$builder->collect_document_assets( 321 );
		ob_start();
		$builder->flush_preloaded_assets();
		$output = ob_get_clean();

		$this->assertSame( [ 321 ], $base->shortcode_ids );
		$this->assertSame( [ 321 ], $module->page_ids );
		$this->assertStringContainsString( 'modern-page-css', $output );
		$this->assertStringContainsString( 'modern-shortcode-css', $output );
	}

	/** @return void */
	public function test_legacy_wpbakery_css_apis_remain_supported() {
		$base    = new class() {

			/** @var array<int> */
			public $page_ids = [];

			/** @var array<int> */
			public $shortcode_ids = [];

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			public function addPageCustomCss( $popup_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors WPBakery's API.
				$this->page_ids[] = $popup_id;
				echo '<style>legacy-page-css</style>';
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			public function addShortcodesCustomCss( $popup_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors WPBakery's API.
				$this->shortcode_ids[] = $popup_id;
				echo '<style>legacy-shortcode-css</style>';
			}
		};
		$builder = $this->make_service_builder( $base, null );

		$builder->collect_document_assets( 654 );
		ob_start();
		$builder->flush_preloaded_assets();
		$output = ob_get_clean();

		$this->assertSame( [ 654 ], $base->page_ids );
		$this->assertSame( [ 654 ], $base->shortcode_ids );
		$this->assertStringContainsString( 'legacy-page-css', $output );
		$this->assertStringContainsString( 'legacy-shortcode-css', $output );
	}

	/**
	 * Make controllable WPBakery style output for lifecycle tests.
	 *
	 * @return WPBakery
	 */
	private function make_output_builder() {
		return new class( \PopupMaker\plugin() ) extends WPBakery {

			/** @var bool */
			public $head_rendered = false;

			/** @var array<int> */
			public $page_css_ids = [];

			/** @var array<int> */
			public $shortcode_css_ids = [];

			/** @return bool */
			protected function head_has_rendered() {
				return $this->head_rendered;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			protected function output_page_custom_css( $popup_id ) {
				$this->page_css_ids[] = $popup_id;
				echo '<style>page-css-' . esc_html( (string) $popup_id ) . '</style>';
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			protected function output_shortcodes_css( $popup_id ) {
				$this->shortcode_css_ids[] = $popup_id;
				echo '<style>shortcode-css-' . esc_html( (string) $popup_id ) . '</style>';
			}
		};
	}

	/**
	 * Make controllable WPBakery service access for API compatibility tests.
	 *
	 * @param object      $base   WPBakery base service.
	 * @param object|null $module WPBakery custom CSS module.
	 *
	 * @return WPBakery
	 */
	private function make_service_builder( $base, $module ) {
		return new class( \PopupMaker\plugin(), $base, $module ) extends WPBakery {

			/** @var object */
			private $base;

			/** @var object|null */
			private $module;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param object                  $base      WPBakery base service.
			 * @param object|null             $module    WPBakery custom CSS module.
			 */
			public function __construct( $container, $base, $module ) {
				parent::__construct( $container );

				$this->base   = $base;
				$this->module = $module;
			}

			/** @return object */
			protected function get_vc_base() {
				return $this->base;
			}

			/** @return object|null */
			protected function get_custom_css_module() {
				return $this->module;
			}
		};
	}
}
