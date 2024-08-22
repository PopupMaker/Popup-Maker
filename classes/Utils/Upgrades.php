<?php
/**
 * Upgrades Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles processing of data migration & upgrade routines.
 */
class PUM_Utils_Upgrades {

	/**
	 * @var PUM_Upgrade_Registry
	 */
	protected $registry;

	/**
	 * @var self
	 */
	public static $instance;

	/**
	 * Popup Maker version.
	 *
	 * @var    string
	 */
	public static $version;

	/**
	 * Popup Maker upgraded from version.
	 *
	 * @var    string
	 */
	public static $upgraded_from;

	/**
	 * Popup Maker initial version.
	 *
	 * @var    string
	 */
	public static $initial_version;

	/**
	 * Popup Maker db version.
	 *
	 * @var    string
	 */
	public static $db_version;

	/**
	 * Popup Maker install date.
	 *
	 * @var    string
	 */
	public static $installed_on;

	/**
	 * Gets everything going with a singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up the Upgrades class instance.
	 */
	public function __construct() {
		// Update stored plugin version info.
		self::update_plugin_version();

		// Render upgrade admin notices.
		add_filter( 'pum_alert_list', [ $this, 'upgrade_alert' ] );
		// Add Upgrade tab to Tools page when upgrades available.
		add_filter( 'pum_tools_tabs', [ $this, 'tools_page_tabs' ] );
		// Render tools page upgrade tab content.
		add_action( 'pum_tools_page_tab_upgrades', [ $this, 'tools_page_tab_content' ] );
		// Ajax upgrade handler.
		add_action( 'wp_ajax_pum_process_upgrade_request', [ $this, 'process_upgrade_request' ] );
		// Register core upgrades.
		add_action( 'pum_register_upgrades', [ $this, 'register_processes' ] );

		// Initiate the upgrade registry. Must be done after versions update for proper comparisons.
		$this->registry = PUM_Upgrade_Registry::instance();
	}

	/**
	 * Update version info.
	 */
	public static function update_plugin_version() {
		self::$version         = get_option( 'pum_ver' );
		self::$upgraded_from   = get_option( 'pum_ver_upgraded_from' );
		self::$initial_version = get_option( 'pum_initial_version' );
		self::$db_version      = get_option( 'pum_db_ver' );
		self::$installed_on    = get_option( 'pum_installed_on' );

		/**
		 * If no version set check if a deprecated one exists.
		 */
		if ( empty( self::$version ) ) {
			$deprecated_ver = get_site_option( 'popmake_version' );

			// set to the deprecated version or last version that didn't have the version option set
			self::$version = $deprecated_ver ? $deprecated_ver : Popup_Maker::$VER; // Since we had versioning in v1 if there isn't one stored its a new install.

			update_option( 'pum_ver', self::$version );
		}

		/**
		 * Back fill the initial version with the oldest version we can detect.
		 */
		if ( ! self::$initial_version ) {
			$oldest_known = Popup_Maker::$VER;

			if ( self::$version && version_compare( self::$version, $oldest_known, '<' ) ) {
				$oldest_known = self::$version;
			}

			if ( self::$upgraded_from && version_compare( self::$upgraded_from, $oldest_known, '<' ) ) {
				$oldest_known = self::$upgraded_from;
			}

			$deprecated_ver = get_site_option( 'popmake_version' );
			if ( $deprecated_ver && version_compare( $deprecated_ver, $oldest_known, '<' ) ) {
				$oldest_known = $deprecated_ver;
			}

			$dep_upgraded_from = get_option( 'popmake_version_upgraded_from' );
			if ( $dep_upgraded_from && version_compare( $dep_upgraded_from, $oldest_known, '<' ) ) {
				$oldest_known = $dep_upgraded_from;
			}

			self::$initial_version = $oldest_known;

			// Only set this value if it doesn't exist.
			update_option( 'pum_initial_version', $oldest_known );
		}

		if ( version_compare( self::$version, Popup_Maker::$VER, '<' ) ) {
			// Allow processing of small core upgrades
			do_action( 'pum_update_core_version', self::$version );

			// Save Upgraded From option
			update_option( 'pum_ver_upgraded_from', self::$version );
			update_option( 'pum_ver', Popup_Maker::$VER );
			self::$upgraded_from = self::$version;
			self::$version       = Popup_Maker::$VER;

			// Reset JS/CSS assets for regeneration.
			pum_reset_assets();
		} elseif ( ! self::$upgraded_from || 'false' === self::$upgraded_from ) {
			// Here to prevent constant extra queries.
			self::$upgraded_from = '0.0.0';
			update_option( 'pum_ver_upgraded_from', self::$upgraded_from );
		}

		// If no current db version, but prior install detected, set db version correctly.
		// Here for backward compatibility.
		if ( ! self::$db_version || self::$db_version < Popup_Maker::$DB_VER ) {
			self::$db_version = Popup_Maker::$DB_VER;
			update_option( 'pum_db_ver', self::$db_version );
		}

		/**
		 * Back fill the initial version with the oldest version we can detect.
		 */
		if ( ! self::$installed_on ) {
			$installed_on = current_time( 'mysql' );

			$review_installed_on = get_option( 'pum_reviews_installed_on' );
			if ( ! empty( $review_installed_on ) ) {
				$installed_on = $review_installed_on;
			}

			self::$installed_on = $installed_on;

			update_option( 'pum_installed_on', self::$installed_on );
		}
	}

	/**
	 * @param PUM_Upgrade_Registry $registry
	 */
	public function register_processes( PUM_Upgrade_Registry $registry ) {

		// v1.7 Upgrades
		$registry->add_upgrade(
			'core-v1_7-popups',
			[
				'rules' => [
					version_compare( self::$initial_version, '1.7', '<' ),
				],
				'class' => 'PUM_Upgrade_v1_7_Popups',
				'file'  => Popup_Maker::$DIR . 'includes/batch/upgrade/class-upgrade-v1_7-popups.php',
			]
		);

		$registry->add_upgrade(
			'core-v1_7-settings',
			[
				'rules' => [
					version_compare( self::$initial_version, '1.7', '<' ),
				],
				'class' => 'PUM_Upgrade_v1_7_Settings',
				'file'  => Popup_Maker::$DIR . 'includes/batch/upgrade/class-upgrade-v1_7-settings.php',
			]
		);

		$registry->add_upgrade(
			'core-v1_8-themes',
			[
				'rules' => [
					$this->needs_v1_8_theme_upgrade(),
				],
				'class' => 'PUM_Upgrade_v1_8_Themes',
				'file'  => Popup_Maker::$DIR . 'includes/batch/upgrade/class-upgrade-v1_8-themes.php',
			]
		);
	}

	/**
	 * @return bool
	 */
	public function needs_v1_8_theme_upgrade() {
		if ( pum_has_completed_upgrade( 'core-v1_8-themes' ) ) {
			return false;
		}

		$needs_upgrade = get_transient( 'pum_needs_1_8_theme_upgrades' );

		if ( false === $needs_upgrade ) {
			$query = new WP_Query(
				[
					'post_type'   => 'popup_theme',
					'post_status' => 'any',
					'fields'      => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'  => [
						'relation' => 'OR',
						[
							'key'     => 'popup_theme_data_version',
							'compare' => 'NOT EXISTS',
							'value'   => 'deprecated', // Here for WP 3.9 or less.
						],
						[
							'key'     => 'popup_theme_data_version',
							'compare' => '<',
							'value'   => 3,
						],
					],
				]
			);

			$needs_upgrade = $query->post_count;
		}

		if ( $needs_upgrade <= 0 ) {
			pum_set_upgrade_complete( 'core-v1_8-themes' );
			delete_transient( 'pum_needs_1_8_theme_upgrades' );

			return false;
		}

		set_transient( 'pum_needs_1_8_theme_upgrades', $needs_upgrade );

		return (bool) $needs_upgrade;
	}

	/**
	 * Registers a new upgrade routine.
	 *
	 * @param string $upgrade_id Upgrade ID.
	 * @param array  $args       {
	 *                           Arguments for registering a new upgrade routine.
	 *
	 * @type array   $rules      Array of true/false values.
	 * @type string  $class      Batch processor class to use.
	 * @type string  $file       File containing the upgrade processor class.
	 * }
	 *
	 * @return bool True if the upgrade routine was added, otherwise false.
	 */
	public function add_routine( $upgrade_id, $args ) {
		return $this->registry->add_upgrade( $upgrade_id, $args );
	}

	/**
	 * Displays upgrade notices.
	 */
	public function upgrade_notices() {
		if ( ! $this->has_uncomplete_upgrades() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Enqueue admin JS for the batch processor.
		wp_enqueue_script( 'pum-admin-batch' );
		wp_enqueue_style( 'pum-admin-batch' ); ?>

		<div class="notice notice-info is-dismissible">
			<?php $this->render_upgrade_notice(); ?>
			<?php $this->render_form(); ?>
		</div>
		<?php
	}


	/**
	 * @param array $alerts
	 *
	 * @return array
	 */
	public function upgrade_alert( $alerts = [] ) {
		if ( ! $this->has_uncomplete_upgrades() || ! current_user_can( 'manage_options' ) ) {
			return $alerts;
		}

		// Enqueue admin JS for the batch processor.
		wp_enqueue_script( 'pum-admin-batch' );
		wp_enqueue_style( 'pum-admin-batch' );

		ob_start();
		$this->render_upgrade_notice();
		$this->render_form();
		$html = ob_get_clean();

		$alerts[] = [
			'code'        => 'upgrades_required',
			'type'        => 'warning',
			'html'        => $html,
			'priority'    => 1000,
			'dismissible' => false,
			'global'      => true,
		];

		return $alerts;
	}

	/**
	 * Renders the upgrade notification message.
	 *
	 * Message only, no form.
	 */
	public function render_upgrade_notice() {
		$resume_upgrade = $this->maybe_resume_upgrade();
		?>
		<p class="pum-upgrade-notice">
			<?php
			if ( empty( $resume_upgrade ) ) {
				?>
				<strong><?php esc_html_e( 'The latest version of Popup Maker requires changes to the Popup Maker settings saved on your site.', 'popup-maker' ); ?></strong>
				<?php
			} else {
				esc_html_e( 'Popup Maker needs to complete a the update of your settings that was previously started.', 'popup-maker' );
			}
			?>
		</p>
		<?php
	}

	/**
	 * Renders the upgrade processing form for reuse.
	 */
	public function render_form() {
		$args = [
			'upgrade_id' => $this->get_current_upgrade_id(),
			'step'       => 1,
		];

		$resume_upgrade = $this->maybe_resume_upgrade();

		if ( $resume_upgrade && is_array( $resume_upgrade ) ) {
			$args = wp_parse_args( $resume_upgrade, $args );
		}
		?>

		<form method="post" class="pum-form  pum-batch-form  pum-upgrade-form" data-ays="<?php esc_attr_e( 'This can sometimes take a few minutes, are you ready to begin?', 'popup-maker' ); ?>" data-upgrade_id="<?php echo esc_attr( $args['upgrade_id'] ); ?>" data-step="<?php echo (int) $args['step']; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'pum_upgrade_ajax_nonce' ) ); ?>">

			<div class="pum-field  pum-field-button  pum-field-submit">
				<p>
					<small><?php esc_html_e( 'The button below will process these changes automatically for you.', 'popup-maker' ); ?></small>
				</p>
				<?php submit_button( ! empty( $resume_upgrade ) ? __( 'Finish Upgrades', 'popup-maker' ) : __( 'Process Changes', 'popup-maker' ), 'secondary', 'submit', false ); ?>
			</div>

			<div class="pum-batch-progress">
				<progress class="pum-overall-progress" max="100">
					<div class="progress-bar"><span></span></div>
				</progress>

				<progress class="pum-task-progress" max="100">
					<div class="progress-bar"><span></span></div>
				</progress>

				<div class="pum-upgrade-messages"></div>
			</div>

		</form>
		<?php
	}

	/**
	 * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle
	 *
	 * @return false|array   When nothing to resume returns false, otherwise starts the upgrade where it left off
	 */
	public function maybe_resume_upgrade() {
		$doing_upgrade = get_option( 'pum_doing_upgrade', [] );

		if ( empty( $doing_upgrade ) ) {
			return false;
		}

		return (array) $doing_upgrade;
	}

	/**
	 * Retrieves an upgrade routine from the registry.
	 *
	 * @param string $upgrade_id Upgrade ID.
	 *
	 * @return array|false Upgrade entry from the registry, otherwise false.
	 */
	public function get_routine( $upgrade_id ) {
		return $this->registry->get( $upgrade_id );
	}

	/**
	 * Get all upgrade routines.
	 *
	 * Note: Unfiltered.
	 *
	 * @return array
	 */
	public function get_routines() {
		return $this->registry->get_upgrades();
	}

	/**
	 * Adds an upgrade action to the completed upgrades array
	 *
	 * @param  string $upgrade_id The action to add to the competed upgrades array
	 *
	 * @return bool If the function was successfully added
	 */
	public function set_upgrade_complete( $upgrade_id = '' ) {

		if ( empty( $upgrade_id ) ) {
			return false;
		}

		$completed_upgrades = $this->get_completed_upgrades();

		if ( ! in_array( $upgrade_id, $completed_upgrades, true ) ) {
			$completed_upgrades[] = $upgrade_id;

			do_action( 'pum_set_upgrade_complete', $upgrade_id );
		}

		// Remove any blanks, and only show uniques
		$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

		return update_option( 'pum_completed_upgrades', $completed_upgrades );
	}

	/**
	 * Get's the array of completed upgrade actions
	 *
	 * @return array The array of completed upgrades
	 */
	public function get_completed_upgrades() {
		$completed_upgrades = get_option( 'pum_completed_upgrades' );

		if ( false === $completed_upgrades ) {
			$completed_upgrades = [];
			update_option( 'pum_completed_upgrades', $completed_upgrades );
		}

		return get_option( 'pum_completed_upgrades', [] );
	}

	/**
	 * Check if the upgrade routine has been run for a specific action
	 *
	 * @param string $upgrade_id The upgrade action to check completion for
	 *
	 * @return bool If the action has been added to the completed actions array
	 */
	public function has_completed_upgrade( $upgrade_id = '' ) {
		if ( empty( $upgrade_id ) ) {
			return false;
		}

		$completed_upgrades = $this->get_completed_upgrades();

		return in_array( $upgrade_id, $completed_upgrades, true );
	}

	/**
	 * Conditional function to see if there are upgrades available.
	 *
	 * @return bool
	 */
	public function has_uncomplete_upgrades() {
		return (bool) count( $this->get_uncompleted_upgrades() );
	}

	/**
	 * Returns array of uncompleted upgrades.
	 *
	 * This doesn't return an upgrade if:
	 * - It was previously complete.
	 * - If any false values in the upgrades $rules array are found.
	 *
	 * @return array
	 */
	public function get_uncompleted_upgrades() {
		$required_upgrades = $this->get_routines();

		foreach ( $required_upgrades as $upgrade_id => $upgrade ) {
			// If the upgrade has already completed or one of the rules failed remove it from the list.
			if ( $this->has_completed_upgrade( $upgrade_id ) || in_array( false, $upgrade['rules'], true ) ) {
				unset( $required_upgrades[ $upgrade_id ] );
			}
		}

		return $required_upgrades;
	}

	/**
	 * Handles Ajax for processing a upgrade upgrade/que request.
	 */
	public function process_upgrade_request() {

		$upgrade_id = isset( $_REQUEST['upgrade_id'] ) ? sanitize_key( $_REQUEST['upgrade_id'] ) : false;

		if ( ! $upgrade_id && ! $this->has_uncomplete_upgrades() ) {
			wp_send_json_error(
				[
					'error' => __( 'A batch process ID must be present to continue.', 'popup-maker' ),
				]
			);
		}

		// Nonce.
		if ( ! check_ajax_referer( 'pum_upgrade_ajax_nonce', 'nonce' ) ) {
			wp_send_json_error(
				[
					'error' => __( 'You do not have permission to initiate this request. Contact an administrator for more information.', 'popup-maker' ),
				]
			);
		}

		if ( ! $upgrade_id ) {
			$upgrade_id = $this->get_current_upgrade_id();
		}

		$step = ! empty( $_REQUEST['step'] ) ? absint( $_REQUEST['step'] ) : 1;

		/**
		 * Instantiate the upgrade class.
		 *
		 * @var PUM_Interface_Batch_Process|PUM_Interface_Batch_PrefetchProcess $upgrade
		 */
		$upgrade = $this->get_upgrade( $upgrade_id, $step );

		if ( false === $upgrade ) {
			wp_send_json_error(
				[
					'error' => sprintf(
						/* translators: 1: Batch process ID. */
						__( '%s is an invalid batch process ID.', 'popup-maker' ),
						esc_html( $upgrade_id )
					),
				]
			);
		}

		/**
		 * Garbage collect any old temporary data in the case step is 1.
		 * Here to prevent case ajax passes step 1 without resetting process counts.
		 */
		$first_step = $step < 2;

		if ( $first_step ) {
			$upgrade->finish();
		}

		$using_prefetch = ( $upgrade instanceof PUM_Interface_Batch_PrefetchProcess );

		// Handle pre-fetching data.
		if ( $using_prefetch ) {
			// Initialize any data needed to process a step.
			$data = isset( $_REQUEST['form'] ) ? sanitize_key( $_REQUEST['form'] ) : [];

			$upgrade->init( $data );
			$upgrade->pre_fetch();
		}

		/** @var int|string|WP_Error $step */
		$step = $upgrade->process_step();

		if ( ! is_wp_error( $step ) ) {
			$response_data = [
				'step' => $step,
				'next' => null,
			];

			// Finish and set the status flag if done.
			if ( 'done' === $step ) {
				$response_data['done']    = true;
				$response_data['message'] = $upgrade->get_message( 'done' );

				// Once all calculations have finished, run cleanup.
				$upgrade->finish();

				// Set the upgrade complete.
				pum_set_upgrade_complete( $upgrade_id );

				if ( $this->has_uncomplete_upgrades() ) {
					// Since the other was complete return the next (now current) upgrade_id.
					$response_data['next'] = $this->get_current_upgrade_id();
				}
			} else {
				$response_data['done']       = false;
				$response_data['message']    = $first_step ? $upgrade->get_message( 'start' ) : '';
				$response_data['percentage'] = $upgrade->get_percentage_complete();
			}

			wp_send_json_success( $response_data );
		} else {
			wp_send_json_error( $step );
		}
	}

	/**
	 * Returns the first key in the uncompleted upgrades.
	 *
	 * @return string|null
	 */
	public function get_current_upgrade_id() {
		$upgrades = $this->get_uncompleted_upgrades();

		reset( $upgrades );

		return key( $upgrades );
	}

	/**
	 * Returns the current upgrade.
	 *
	 * @return bool|PUM_Interface_Batch_PrefetchProcess|PUM_Interface_Batch_Process
	 */
	public function get_current_upgrade() {
		$upgrade_id = $this->get_current_upgrade_id();

		return $this->get_upgrade( $upgrade_id );
	}

	/**
	 * Gets the upgrade process object.
	 *
	 * @param string $upgrade_id
	 * @param int    $step
	 *
	 * @return bool|PUM_Interface_Batch_Process|PUM_Interface_Batch_PrefetchProcess
	 */
	public function get_upgrade( $upgrade_id = '', $step = 1 ) {
		$upgrade = $this->registry->get( $upgrade_id );

		if ( ! $upgrade ) {
			return false;
		}

		$class      = isset( $upgrade['class'] ) ? sanitize_text_field( $upgrade['class'] ) : '';
		$class_file = isset( $upgrade['file'] ) ? $upgrade['file'] : '';

		if ( ! class_exists( $class ) && ! empty( $class_file ) && file_exists( $class_file ) ) {
			require_once $class_file;
		} else {
			wp_send_json_error(
				[
					'error' => sprintf(
						/* translators: 1: Batch process ID. */
						__( 'An invalid file path is registered for the %1$s batch process handler.', 'popup-maker' ),
						"<code>{$upgrade_id}</code>"
					),
				]
			);
		}

		if ( empty( $class ) || ! class_exists( $class ) ) {
			wp_send_json_error(
				[
					'error' => sprintf(
						/* translators: 1: Class name, 2: Batch process ID. */
						__( '%1$s is an invalid handler for the %2$s batch process. Please try again.', 'popup-maker' ),
						"<code>{$class}</code>",
						"<code>{$upgrade_id}</code>"
					),
				]
			);
		}

		/**
		 * @var PUM_Interface_Batch_Process|PUM_Interface_Batch_PrefetchProcess
		 */
		return new $class( $step );
	}

	/**
	 * Add upgrades tab to tools page if there are upgrades available.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function tools_page_tabs( $tabs = [] ) {

		if ( $this->has_uncomplete_upgrades() ) {
			$tabs['upgrades'] = __( 'Upgrades', 'popup-maker' );
		}

		return $tabs;
	}

	/**
	 * Renders upgrade form on the tools page upgrade tab.
	 */
	public function tools_page_tab_content() {
		if ( ! $this->has_uncomplete_upgrades() ) {
			esc_html_e( 'No upgrades currently required.', 'popup-maker' );

			return;
		}

		// Enqueue admin JS for the batch processor.
		wp_enqueue_script( 'pum-admin-batch' );
		wp_enqueue_style( 'pum-admin-batch' );

		$this->render_upgrade_notice();
		$this->render_form();
	}
}
