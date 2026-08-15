<?php
/**
 * Normalized form submission phase tests.
 *
 * @package Popup_Maker
 */

/**
 * Test transport-independent dispatch and phase authorization.
 */
class FormSubmissionPhases_Test extends WP_UnitTestCase {
	/** @var callable|null */
	private $observation_action;

	/** @var callable|null */
	private $runner_action;

	/** @var callable|null */
	private $phases_filter;

	/**
	 * Reset shared hooks and request state.
	 */
	public function tearDown(): void {
		PUM_Integrations::$form_submission = null;
		unset( $_REQUEST['pum_form_popup_id'] );

		if ( $this->observation_action ) {
			remove_action( 'pum_integrated_form_submission', $this->observation_action );
		}
		if ( $this->runner_action ) {
			remove_action( 'pum_integrated_form_submission_actions', $this->runner_action );
		}
		if ( $this->phases_filter ) {
			remove_filter( 'pum_integrated_form_submission_phases', $this->phases_filter );
		}

		parent::tearDown();
	}

	/**
	 * Normal requests authorize every phase by default.
	 */
	public function test_normal_request_defaults_authorize_all_phases() {
		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => true,
				'frontend' => true,
			],
			pum_get_integrated_form_submission_phases()
		);
	}

	/**
	 * Observation remains available when every optional phase is suppressed.
	 */
	public function test_observation_is_independent_from_optional_phases() {
		$observed    = null;
		$action_runs = 0;

		$this->observation_action = static function ( $args ) use ( &$observed ) {
			$observed = $args;
		};
		$this->runner_action      = static function () use ( &$action_runs ) {
			++$action_runs;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );
		add_action( 'pum_integrated_form_submission_actions', $this->runner_action );

		pum_integrated_form_submission(
			[
				'form_provider' => 'example',
				'form_id'       => 7,
				'phases'        => [
					'actions'  => false,
					'tracking' => false,
					'frontend' => false,
				],
			]
		);

		$this->assertNotNull( $observed );
		$this->assertSame( 0, $action_runs );
		$this->assertNull( PUM_Integrations::$form_submission );
	}

	/**
	 * Extensions can adjust phases without changing the normalized envelope.
	 */
	public function test_phase_filter_controls_action_dispatch() {
		$action_runs         = 0;
		$this->phases_filter = static function ( $phases, $args ) {
			if ( 'blocked' === $args['form_provider'] ) {
				$phases['actions'] = false;
			}

			return $phases;
		};
		$this->runner_action = static function () use ( &$action_runs ) {
			++$action_runs;
		};
		add_filter( 'pum_integrated_form_submission_phases', $this->phases_filter, 10, 2 );
		add_action( 'pum_integrated_form_submission_actions', $this->runner_action );

		pum_integrated_form_submission( [ 'form_provider' => 'blocked' ] );

		$this->assertSame( 0, $action_runs );
	}

	/**
	 * Tracking receipts cannot be opted back into Core tracking.
	 */
	public function test_tracking_receipt_forces_tracking_phase_off() {
		$phases = pum_get_integrated_form_submission_phases(
			[
				'tracked' => true,
				'phases'  => [ 'tracking' => true ],
			]
		);

		$this->assertFalse( $phases['tracking'] );
	}

	/**
	 * AJAX provider callbacks dispatch actions without server tracking/replay.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ajax_provider_callback_actions_without_tracking_or_frontend() {
		define( 'DOING_AJAX', true );
		remove_all_actions( 'pum_integrated_form_submission' );
		remove_all_actions( 'pum_analytics_conversion' );

		$popup_id                      = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$_REQUEST['pum_form_popup_id'] = $popup_id;
		$tracking_service              = new \PopupMaker\Services\FormConversionTracking( new stdClass() );
		$tracking_service->reset_site_count();
		$tracking_service->reset_popup_count( $popup_id );
		$tracking_service->init();

		$observed    = null;
		$action_runs = 0;
		add_action(
			'pum_integrated_form_submission',
			static function ( $args ) use ( &$observed ) {
				$observed = $args;
			}
		);
		add_action(
			'pum_integrated_form_submission_actions',
			static function () use ( &$action_runs ) {
				++$action_runs;
			}
		);

		$integration = new PUM_Integration_Form_FluentForms();
		$integration->on_success(
			'entry-91',
			[],
			(object) [ 'attributes' => (object) [ 'id' => 7 ] ]
		);

		$this->assertSame( 'entry-91', $observed['submission_id'] );
		$this->assertTrue( $observed['ajax'] );
		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => false,
				'frontend' => false,
			],
			$observed['phases']
		);
		$this->assertSame( 1, $action_runs );
		$this->assertNull( PUM_Integrations::$form_submission );
		$this->assertSame( 0, (int) get_post_meta( $popup_id, 'popup_conversion_count', true ) );
		$this->assertSame( 0, $tracking_service->get_site_count() );
		$this->assertSame( 0, $tracking_service->get_popup_count( $popup_id ) );

		// The provider's browser success is the one authorized tracking path.
		PUM_Analytics::track(
			[
				'pid'       => $popup_id,
				'event'     => 'conversion',
				'eventData' => [
					'type'         => 'form_submission',
					'submissionId' => 'entry-91',
					'phases'       => [ 'tracking' => true ],
				],
			]
		);

		$this->assertSame( 1, (int) get_post_meta( $popup_id, 'popup_conversion_count', true ) );
		$this->assertSame( 1, $tracking_service->get_site_count() );
		$this->assertSame( 1, $tracking_service->get_popup_count( $popup_id ) );
	}

	/**
	 * Non-AJAX callbacks increment each Core metric exactly once.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_non_ajax_callback_tracks_legacy_and_form_metrics_once() {
		remove_all_actions( 'pum_integrated_form_submission' );
		remove_all_actions( 'pum_analytics_conversion' );

		$popup_id                      = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$_REQUEST['pum_form_popup_id'] = $popup_id;
		$tracking_service              = new \PopupMaker\Services\FormConversionTracking( new stdClass() );
		$tracking_service->reset_site_count();
		$tracking_service->reset_popup_count( $popup_id );
		$tracking_service->init();

		$integration = new PUM_Integration_Form_FluentForms();
		$integration->on_success(
			'entry-92',
			[],
			(object) [ 'attributes' => (object) [ 'id' => 7 ] ]
		);

		$this->assertSame( 1, (int) get_post_meta( $popup_id, 'popup_conversion_count', true ) );
		$this->assertSame( 1, $tracking_service->get_site_count() );
		$this->assertSame( 1, $tracking_service->get_popup_count( $popup_id ) );
		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => true,
				'frontend' => true,
			],
			PUM_Integrations::$form_submission['phases']
		);
	}

	/**
	 * REST submissions use the same server policy as AJAX submissions.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_rest_request_defaults_disable_tracking_and_frontend() {
		define( 'REST_REQUEST', true );

		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => false,
				'frontend' => false,
			],
			pum_get_integrated_form_submission_phases()
		);
	}
}
