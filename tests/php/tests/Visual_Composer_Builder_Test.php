<?php
/**
 * Visual Composer builder tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Builders\VisualComposer;

/**
 * Verify Visual Composer request routing and document rendering.
 */
class Visual_Composer_Builder_Test extends WP_UnitTestCase {

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
		\Mockery::close();

		parent::tearDown();
	}

	/** @return void */
	public function test_only_editable_request_uses_popup_canvas() {
		$builder = new VisualComposer( \PopupMaker\plugin() );

		$_GET = [
			'vcv-action'    => 'frontend',
			'vcv-source-id' => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertFalse( $builder->is_canvas_request() );

		$_GET = [
			'vcv-editable'  => '1',
			'vcv-source-id' => '123',
		];

		$this->assertSame( 123, $builder->get_requested_popup_id() );
		$this->assertTrue( $builder->is_canvas_request() );
		$this->assertSame( '<div id="vcv-editor"></div>', $builder->render_document( 123, true ) );
	}

	/** @return void */
	public function test_document_ownership_uses_visual_composer_meta() {
		$popup_id = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$builder  = new VisualComposer( \PopupMaker\plugin() );

		$this->assertFalse( $builder->owns_document( $popup_id ) );

		$_GET = [
			'vcv-editable'  => '1',
			'vcv-source-id' => (string) $popup_id,
		];

		$this->assertFalse( $builder->owns_document( $popup_id ), 'A request alone must not claim frontend ownership.' );

		$_GET = [];

		update_post_meta( $popup_id, 'vcv-pageContent', rawurlencode( '{"elements":[]}' ) );

		$this->assertTrue( $builder->owns_document( $popup_id ) );

		update_post_meta( $popup_id, 'vcv-be-editor', 'gutenberg' );

		$this->assertFalse( $builder->owns_document( $popup_id ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_secondary_assets_are_batched_and_deduplicated() {
		if ( function_exists( 'vchelper' ) || function_exists( 'vcevent' ) ) {
			$this->markTestSkipped( 'This regression test supplies isolated Visual Composer API doubles.' );
		}

		require_once dirname( __DIR__ ) . '/fixtures/class-pum-visual-composer-service.php';
		require_once dirname( __DIR__ ) . '/fixtures/visual-composer-api.php';

		$posts     = [];
		$allowed   = true;
		$listeners = [];
		$assets    = \Mockery::mock( PUM_Visual_Composer_Service::class );
		$assets->shouldReceive( 'addToEnqueueList' )
			->times( 3 )
			->andReturnUsing(
				function ( $post_id ) use ( &$posts ) {
					$posts[] = absint( $post_id );
				}
			);
		$access = \Mockery::mock( PUM_Visual_Composer_Service::class );
		$access->shouldReceive( 'canEdit' )
			->twice()
			->andReturnUsing(
				function ( $post_id ) use ( &$allowed ) {
					unset( $post_id );

					return $allowed;
				}
			);
		$events = \Mockery::mock( PUM_Visual_Composer_Service::class );
		$events->shouldReceive( 'listen' )
			->once()
			->with( 'vcv:api:postSaved', \Mockery::type( 'callable' ) )
			->andReturnUsing(
				function ( $event, $listener ) use ( &$listeners ) {
					$listeners[ $event ] = $listener;
				}
			);
		$GLOBALS['pum_visual_composer_helpers'] = [
			'AccessUserCapabilities' => $access,
			'AssetsEnqueue'          => $assets,
			'Events'                 => $events,
		];
		$GLOBALS['pum_visual_composer_events']  = 0;

		$builder  = new class( \PopupMaker\plugin() ) extends VisualComposer {

			/** @var int */
			public $remembered_owner = 0;

			/**
			 * @param int $popup_id Popup ID.
			 * @return void
			 */
			protected function remember_document_owner( $popup_id ) {
				$this->remembered_owner = absint( $popup_id );
			}
		};
		$popup_id = self::factory()->post->create( [ 'post_type' => 'popup' ] );

		$this->assertTrue( $builder->can_edit_document( $popup_id ) );
		$allowed = false;
		$this->assertFalse( $builder->can_edit_document( $popup_id ) );

		$builder->register_hooks();
		$this->assertArrayHasKey( 'vcv:api:postSaved', $listeners );

		update_post_meta( $popup_id, 'vcv-pageContent', rawurlencode( '{"elements":[]}' ) );
		call_user_func( $listeners['vcv:api:postSaved'], $popup_id, get_post( $popup_id ) );
		$this->assertSame( $popup_id, $builder->remembered_owner );

		$builder->collect_document_assets( 123 );
		$builder->collect_document_assets( 123 );
		$builder->collect_document_assets( 456 );
		$builder->flush_preloaded_assets();
		$builder->flush_preloaded_assets();

		$this->assertSame( [ 123, 456 ], $posts );
		$this->assertSame( 1, $GLOBALS['pum_visual_composer_events'] );

		$builder->collect_document_assets( 789 );
		$builder->finalize_document_assets( true );

		$this->assertSame( [ 123, 456, 789 ], $posts );
		$this->assertSame( 2, $GLOBALS['pum_visual_composer_events'] );

		remove_action( 'wp_enqueue_scripts', [ $builder, 'flush_preloaded_assets' ], 12 );
	}
}
