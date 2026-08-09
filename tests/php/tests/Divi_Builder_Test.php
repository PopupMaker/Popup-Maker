<?php
/**
 * Divi builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\Divi;

/**
 * Verify Divi request routing without loading Divi itself.
 */
class Divi_Builder_Test extends WP_UnitTestCase {

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
	public function test_frontend_visual_builder_uses_popup_canvas() {
		$provider = $this->make_provider( 123 );

		$_GET = [
			'et_fb'  => '1',
			'et_bfb' => '1',
			'p'      => '123',
		];

		$this->assertSame( 123, $provider->get_requested_popup_id() );
		$this->assertFalse( $provider->is_canvas_request() );
		$this->assertNull( $provider->render_document( 123, false ) );

		$_GET = [
			'et_fb'      => '1',
			'et_post_id' => '123',
		];

		$this->assertSame( 123, $provider->get_requested_popup_id() );
		$this->assertTrue( $provider->is_canvas_request() );
		$this->assertSame(
			'<div id="et-boc"><div class="et-l"><div id="et-fb-app"></div></div></div>',
			$provider->render_document( 123, true )
		);
	}

	/** @return void */
	public function test_support_and_editor_filters_stay_scoped_to_popups() {
		$control_popup_id = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$divi_popup_id    = $this->factory->post->create( [ 'post_type' => 'popup' ] );
		$provider         = $this->make_provider( $divi_popup_id );

		$this->assertFalse( $provider->force_classic_editor( true, 'popup' ) );
		$this->assertFalse( $provider->force_classic_editor( true, 'popup_theme' ) );
		$this->assertTrue( $provider->force_classic_editor( true, 'page' ) );
		$this->assertTrue( $provider->force_classic_editor( true, get_post( $divi_popup_id ) ) );
		$this->assertSame( [ 'post', 'popup' ], $provider->add_popup_support( [ 'post' ] ) );
		$this->assertFalse( $provider->enable_frontend_builder( true, $control_popup_id ) );

		$fields = $provider->explain_classic_editor_requirement(
			[
				'general' => [
					'main' => [
						'enable_classic_editor' => [ 'desc' => 'Existing description.' ],
					],
				],
			]
		);

		$this->assertTrue( $fields['general']['main']['enable_classic_editor']['disabled'] );
		$this->assertStringContainsString( 'Divi 4 requires', $fields['general']['main']['enable_classic_editor']['desc'] );

		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertTrue( $provider->enable_frontend_builder( false, $control_popup_id ) );

		$provider->register_hooks();

		try {
			$this->assertNotFalse( has_filter( 'et_builder_post_types', [ $provider, 'add_popup_support' ] ) );
			$this->assertNotFalse( has_filter( 'et_fb_is_enabled', [ $provider, 'enable_frontend_builder' ] ) );
			$this->assertNotFalse( has_filter( 'use_block_editor_for_post_type', [ $provider, 'force_classic_editor' ] ) );
			$this->assertNotFalse( has_filter( 'pum_settings_fields', [ $provider, 'explain_classic_editor_requirement' ] ) );
		} finally {
			remove_filter( 'et_builder_post_types', [ $provider, 'add_popup_support' ] );
			remove_filter( 'et_fb_is_enabled', [ $provider, 'enable_frontend_builder' ] );
			remove_filter( 'use_block_editor_for_post_type', [ $provider, 'force_classic_editor' ], 999 );
			remove_filter( 'pum_settings_fields', [ $provider, 'explain_classic_editor_requirement' ] );
		}
	}

	/**
	 * @param int $document_id Divi document fixture ID.
	 * @return Divi
	 */
	private function make_provider( $document_id ) {
		return new class( \PopupMaker\plugin(), $document_id ) extends Divi {

			/** @var int */
			private $document_id;

			/**
			 * @param \PopupMaker\Plugin\Core $container Plugin container.
			 * @param int                     $document_id Divi document fixture ID.
			 */
			public function __construct( $container, $document_id ) {
				$this->document_id = $document_id;

				parent::__construct( $container );
			}

			/** @return bool */
			protected function is_divi_4() {
				return true;
			}

			/**
			 * @param int $popup_id Popup ID.
			 * @return bool
			 */
			public function owns_document( $popup_id ) {
				return $this->document_id === $popup_id;
			}
		};
	}
}
