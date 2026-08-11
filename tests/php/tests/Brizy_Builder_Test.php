<?php
/**
 * Brizy builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\Brizy;

/**
 * Verify Brizy request routing and document rendering.
 */
class Brizy_Builder_Test extends WP_UnitTestCase {

	/** @var array<string,mixed> */
	private $original_get;

	/** @var array<string,mixed> */
	private $original_request;

	/** @return void */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_get = $_GET;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture preserves request globals.
		$this->original_request = $_REQUEST;
	}

	/** @return void */
	public function tearDown(): void {
		$_GET     = $this->original_get;
		$_REQUEST = $this->original_request;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/** @return void */
	public function test_authenticated_native_save_records_brizy_ownership() {
		$popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$builder  = new class( \PopupMaker\plugin() ) extends Brizy {

			/** @var int */
			public $remembered_popup_id = 0;

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function owns_document( $popup_id ) {
				return 0 < absint( $popup_id );
			}

			/** @return string */
			protected function get_save_action() {
				return 'brizy_update_item';
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			protected function remember_document_owner( $popup_id ) {
				$this->remembered_popup_id = absint( $popup_id );
			}
		};

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
		$builder->register_hooks();

		$this->assertSame( PHP_INT_MAX, has_action( 'save_post_popup', [ $builder, 'remember_saved_document' ] ) );

		$_REQUEST = [
			'action' => 'brizy_update_item',
			'post'   => (string) $popup_id,
			'hash'   => 'invalid',
		];
		$builder->remember_saved_document( $popup_id );
		$this->assertSame( 0, $builder->remembered_popup_id );

		$_REQUEST['hash'] = wp_create_nonce( 'brizy-api' );
		$builder->remember_saved_document( $popup_id );
		$this->assertSame( $popup_id, $builder->remembered_popup_id );

		remove_action( 'save_post_popup', [ $builder, 'remember_saved_document' ], PHP_INT_MAX );
	}

	/** @return void */
	public function test_only_iframe_request_uses_popup_canvas() {
		$builder = new Brizy( \PopupMaker\plugin() );

		$_GET = [
			'action' => 'in-front-editor',
			'post'   => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertFalse( $builder->is_canvas_request() );

		$_GET = [
			'is-editor-iframe' => '1',
			'post'             => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertTrue( $builder->is_canvas_request() );
		$this->assertSame(
			'<div id="brz-ed-root"></div><div id="brz-popups"></div>',
			$builder->render_document( 123, true )
		);

		wp_register_style( 'popup-maker-builder-preview', false, [], 'test' );
		$builder->add_canvas_styles();

		$this->assertNotEmpty( wp_styles()->get_data( 'popup-maker-builder-preview', 'after' ) );

		wp_deregister_style( 'popup-maker-builder-preview' );
	}

	/** @return void */
	public function test_rendered_document_leaves_shortcodes_for_popup_content_pipeline() {
		if (
			class_exists( '\Brizy_Editor_Post' ) ||
			class_exists( '\Brizy_Public_Main' ) ||
			class_exists( '\Brizy_Public_AssetEnqueueManager' )
		) {
			$this->markTestSkipped( 'This regression test supplies isolated Brizy API doubles.' );
		}

		$brizy_api = new class() {

			/** @var string */
			public static $content = '';

			/**
			 * @param mixed $value Ignored API argument.
			 * @return self
			 */
			public static function get( $value ) {
				unset( $value );

				return new self();
			}

			/**
			 * @param string $content Ignored root marker.
			 * @return string
			 */
			public function insert_page_content( $content ) {
				unset( $content );

				return self::$content;
			}
		};

		class_alias( get_class( $brizy_api ), 'Brizy_Editor_Post' );
		class_alias( get_class( $brizy_api ), 'Brizy_Public_Main' );

		$asset_manager = new class() {

			/** @var int */
			public static $enqueue_count = 0;

			/** @var int */
			public static $finalize_count = 0;

			/** @return self */
			// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Mirrors Brizy's API.
			public static function _init() {
				return new self();
			}

			/**
			 * @param mixed $post Brizy document.
			 * @return void
			 */
			// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors Brizy's API.
			public function enqueuePost( $post ) {
				unset( $post );

				++self::$enqueue_count;
				wp_enqueue_style( 'pum-brizy-test-document' );
			}

			/** @return void */
			// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors Brizy's API.
			public function enqueueStyles() {
				++self::$finalize_count;
			}

			/** @return void */
			// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mirrors Brizy's API.
			public function enqueueScripts() {}
		};

		class_alias( get_class( $asset_manager ), 'Brizy_Public_AssetEnqueueManager' );

		$shortcode        = 'pum_brizy_shortcode_proof';
		$nested_shortcode = 'pum_brizy_nested_shortcode_proof';
		$nested_runs      = 0;

		add_shortcode(
			$shortcode,
			function () use ( $nested_shortcode ) {
				return '[' . $nested_shortcode . ']';
			}
		);
		add_shortcode(
			$nested_shortcode,
			function () use ( &$nested_runs ) {
				++$nested_runs;

				return '<strong id="brizy-nested-shortcode-rendered">Nested shortcode rendered</strong>';
			}
		);

		\Brizy_Public_Main::$content = '<div>[' . $shortcode . ']</div>';
		wp_register_style( 'pum-brizy-test-document', home_url( '/brizy-test.css' ), [], 'test' );

		try {
			$builder = new Brizy( \PopupMaker\plugin() );
			$content = $builder->render_document( 123 );
			$builder->render_document( 123 );
			ob_start();
			$builder->finalize_document_assets( true );
			$late_assets = ob_get_clean();
			$builder->finalize_document_assets( true );
			$filtered_content = \PUM_Utils_Shortcodes::clean_do_shortcode( $content );
		} finally {
			remove_shortcode( $shortcode );
			remove_shortcode( $nested_shortcode );
			wp_dequeue_style( 'pum-brizy-test-document' );
			wp_deregister_style( 'pum-brizy-test-document' );
		}

		$this->assertIsString( $content );
		$this->assertStringContainsString( '[' . $shortcode . ']', $content );

		$this->assertStringNotContainsString( '[' . $shortcode . ']', $filtered_content );
		$this->assertStringContainsString( '[' . $nested_shortcode . ']', $filtered_content );
		$this->assertSame( 0, $nested_runs );
		$this->assertStringContainsString( 'brizy-test.css', $late_assets );
		$this->assertSame( 1, \Brizy_Public_AssetEnqueueManager::$enqueue_count );
		$this->assertSame( 1, \Brizy_Public_AssetEnqueueManager::$finalize_count );
	}
}
