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
		unset( $_POST['gform_ajax'] );

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
	 * Tracking policy sees the complete envelope once before either counter.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_complete_envelope_controls_all_server_tracking_once() {
		remove_all_actions( 'pum_integrated_form_submission' );
		remove_all_actions( 'pum_analytics_conversion' );

		$filter_calls     = 0;
		$popup_id         = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$tracking_service = new \PopupMaker\Services\FormConversionTracking( new stdClass() );
		$tracking_service->reset_site_count();
		$tracking_service->reset_popup_count( $popup_id );
		$tracking_service->init();

		$this->phases_filter = static function ( $phases, $args ) use ( &$filter_calls ) {
			++$filter_calls;

			if ( 7 === $args['form_id'] && 'entry-7' === $args['submission_id'] && 'blocked' === $args['context']['segment'] ) {
				$phases['tracking'] = false;
			}

			return $phases;
		};
		add_filter( 'pum_integrated_form_submission_phases', $this->phases_filter, 10, 2 );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => 'example',
				'form_id'       => 7,
				'submission_id' => 'entry-7',
				'context'       => [ 'segment' => 'blocked' ],
			]
		);

		$this->assertSame( 1, $filter_calls );
		$this->assertSame( 0, (int) get_post_meta( $popup_id, 'popup_conversion_count', true ) );
		$this->assertSame( 0, $tracking_service->get_site_count() );
		$this->assertSame( 0, $tracking_service->get_popup_count( $popup_id ) );
		$this->assertFalse( PUM_Integrations::$form_submission['phases']['tracking'] );
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
	 * Gravity Forms' provider AJAX marker suppresses server tracking and replay.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_gravity_forms_ajax_marker_uses_async_phases() {
		remove_all_actions( 'pum_integrated_form_submission' );
		remove_all_actions( 'pum_analytics_conversion' );

		$popup_id                      = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
			]
		);
		$_REQUEST['pum_form_popup_id'] = $popup_id;
		$_POST['gform_ajax']           = 'form_id=7';
		$observed                      = null;
		$this->observation_action      = static function ( $args ) use ( &$observed ) {
			$observed = $args;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new PUM_Integration_Form_GravityForms();
		$integration->on_success( [ 'id' => 'entry-93' ], [ 'id' => 7 ] );

		$this->assertSame( 'entry-93', $observed['submission_id'] );
		$this->assertTrue( $observed['ajax'] );
		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => false,
				'frontend' => false,
			],
			$observed['phases']
		);
		$this->assertSame( 0, (int) get_post_meta( $popup_id, 'popup_conversion_count', true ) );
		$this->assertNull( PUM_Integrations::$form_submission );
	}

	/**
	 * Gravity Forms entry objects retain their provider entry ID.
	 */
	public function test_gravity_forms_object_entry_uses_scalar_id() {
		$observed                 = null;
		$this->observation_action = static function ( $args ) use ( &$observed ) {
			$observed = $args;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new PUM_Integration_Form_GravityForms();
		$integration->on_success( (object) [ 'id' => 'entry-94' ], [ 'id' => 7 ] );

		$this->assertSame( 'entry-94', $observed['submission_id'] );
	}

	/**
	 * Formidable's provider option marks submissions using its AJAX transport.
	 */
	public function test_formidable_ajax_option_uses_async_phases() {
		$observed                 = null;
		$this->observation_action = static function ( $args ) use ( &$observed ) {
			$observed = $args;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new class() extends PUM_Integration_Form_FormidableForms {
			/**
			 * Return an AJAX-enabled test form.
			 *
			 * @param string $id Form ID.
			 * @return object
			 */
			public function get_form( $id ) {
				return (object) [ 'options' => [ 'ajax_submit' => true ] ];
			}

			/**
			 * Treat the synthetic entry as a submitted parent entry.
			 *
			 * @param int $entry_id Entry ID.
			 * @return object
			 */
			protected function get_entry( $entry_id ) {
				return (object) [ 'is_draft' => 0 ];
			}
		};
		$integration->on_success( 95, 7 );

		$this->assertSame( 95, $observed['submission_id'] );
		$this->assertTrue( $observed['ajax'] );
		$this->assertSame(
			[
				'actions'  => true,
				'tracking' => false,
				'frontend' => false,
			],
			$observed['phases']
		);
		$this->assertNull( PUM_Integrations::$form_submission );
	}

	/**
	 * Formidable drafts and repeater children never become conversions.
	 */
	public function test_formidable_requires_submitted_parent_entry() {
		$observed                 = 0;
		$this->observation_action = static function () use ( &$observed ) {
			++$observed;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new class() extends PUM_Integration_Form_FormidableForms {
			/** @var object|null */
			public $entry;

			/** @var int */
			public $entry_loads = 0;

			/**
			 * Return a non-AJAX synthetic form.
			 *
			 * @param string $id Form ID.
			 * @return object
			 */
			public function get_form( $id ) {
				return (object) [ 'options' => [] ];
			}

			/**
			 * Verify provider callback state through a controlled entry seam.
			 *
			 * @param int $entry_id Entry ID.
			 * @return object|null
			 */
			protected function get_entry( $entry_id ) {
				++$this->entry_loads;
				return $this->entry;
			}
		};

		foreach ( [ null, [], new stdClass(), 0, -1, 1.5, 'entry-100' ] as $invalid_entry_id ) {
			$integration->on_success( $invalid_entry_id, 7, [ 'is_child' => false ] );
		}
		$this->assertSame( 0, $integration->entry_loads );

		$integration->entry = (object) [ 'is_draft' => 1 ];
		$integration->on_success( 101, 7, [ 'is_child' => false ] );
		$integration->entry = (object) [ 'is_draft' => 0 ];
		$integration->on_success( 102, 7, [ 'is_child' => true ] );
		$integration->entry = (object) [
			'is_draft'       => 0,
			'parent_item_id' => 101,
		];
		$integration->on_success( 103, 7, [ 'is_child' => false ] );
		$this->assertSame( 0, $observed );

		$integration->entry = (object) [
			'is_draft'       => 0,
			'parent_item_id' => 0,
		];
		$integration->on_success( '104', 7, new stdClass() );
		$this->assertSame( 1, $observed );
	}

	/**
	 * A frontend draft becoming submitted emits once from Formidable's update path.
	 */
	public function test_formidable_draft_to_submitted_update_emits_once() {
		$observed                 = 0;
		$this->observation_action = static function () use ( &$observed ) {
			++$observed;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new class() extends PUM_Integration_Form_FormidableForms {
			/** @var object|null */
			public $entry;

			/**
			 * Return a non-AJAX synthetic form.
			 *
			 * @param string $id Form ID.
			 * @return object
			 */
			public function get_form( $id ) {
				return (object) [ 'options' => [] ];
			}

			/**
			 * Return controlled persisted entry state.
			 *
			 * @param int $entry_id Entry ID.
			 * @return object|null
			 */
			protected function get_entry( $entry_id ) {
				return $this->entry;
			}
		};

		$integration->entry = (object) [
			'is_draft'       => 1,
			'parent_item_id' => 0,
		];
		$values             = [
			'form_id'  => 7,
			'is_draft' => 0,
		];
		$malformed_values   = [ 'frm_saving_draft' => new stdClass() ];
		$this->assertSame( $malformed_values, $integration->capture_draft_transition( $malformed_values, 105 ) );
		$integration->entry = (object) [
			'is_draft'       => 0,
			'parent_item_id' => 0,
		];
		$integration->on_update_success( 105, 7 );
		$this->assertSame( 0, $observed );

		$integration->entry = (object) [
			'is_draft'       => 1,
			'parent_item_id' => 0,
		];
		$this->assertSame( $values, $integration->capture_draft_transition( $values, 105 ) );

		$integration->entry = (object) [
			'is_draft'       => 0,
			'parent_item_id' => 0,
		];
		$integration->on_update_success( 105, 7 );
		$integration->on_update_success( 105, 7 );
		$this->assertSame( 1, $observed );

		$integration->entry = (object) [
			'is_draft'       => 1,
			'parent_item_id' => 0,
		];
		$integration->capture_draft_transition( [ 'frm_saving_draft' => 1 ], 106 );
		$integration->entry = (object) [
			'is_draft'       => 0,
			'parent_item_id' => 0,
		];
		$integration->on_update_success( 106, 7 );
		$this->assertSame( 1, $observed );
	}

	/**
	 * Forminator rejects explicit failures and legacy spam/draft entries.
	 */
	public function test_forminator_requires_active_or_legacy_success_entry() {
		$observed                 = 0;
		$this->observation_action = static function () use ( &$observed ) {
			++$observed;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new PUM_Integration_Form_Forminator();
		foreach ( [ 'draft', 'abandoned', 'spam' ] as $status ) {
			$integration->on_success( (object) [ 'status' => $status ], 7, [] );
		}
		$integration->on_success( null, 7, [] );
		$integration->on_success( 'invalid-entry', 7, [] );
		$integration->on_success( (object) [ 'is_spam' => true ], 7, [] );
		$integration->on_success( (object) [ 'draft_id' => 'draft-7' ], 7, [] );

		$this->assertSame( 0, $observed );

		$integration->on_success( (object) [ 'status' => 'active' ], 7, [] );
		$integration->on_success( new stdClass(), 7, [] );
		$this->assertSame( 2, $observed );
	}

	/**
	 * WS Form saves and failed validation never become conversions.
	 */
	public function test_ws_form_requires_one_valid_submit_receipt() {
		$observed                 = 0;
		$this->observation_action = static function () use ( &$observed ) {
			++$observed;
		};
		add_action( 'pum_integrated_form_submission', $this->observation_action );

		$integration = new PUM_Integration_Form_WSForms();
		$valid       = [
			'form_id'                  => 7,
			'post_mode'                => 'submit',
			'error'                    => false,
			'error_validation_actions' => [],
		];

		$integration->on_success( (object) array_merge( $valid, [ 'post_mode' => 'save' ] ) );
		$integration->on_success( (object) array_merge( $valid, [ 'error' => true ] ) );
		$integration->on_success( (object) array_merge( $valid, [ 'error_validation_actions' => [ 'field_1' => 'Required' ] ] ) );
		$integration->on_success( (object) array_merge( $valid, [ 'form_id' => 0 ] ) );

		$this->assertSame( 0, $observed );

		$integration->on_success( (object) $valid );
		$this->assertSame( 1, $observed );
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
