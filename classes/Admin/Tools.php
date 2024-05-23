<?php
/**
 * Class for Admin Tools
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Tools
 */
class PUM_Admin_Tools {

	/**
	 * @var array
	 */
	public static $notices = [];

	/**
	 * Initializes the "Tools" page.
	 */
	public static function init() {
		add_action( 'admin_notices', [ __CLASS__, 'notices' ] );
		add_action( 'admin_init', [ __CLASS__, 'emodal_process_import' ] );
		add_action( 'pum_tools_page_tab_betas', [ __CLASS__, 'betas_display' ] );
		add_action( 'pum_tools_page_tab_error_log', [ __CLASS__, 'errorlog_display' ] );
		add_action( 'pum_tools_page_tab_action_scheduler', [ __CLASS__, 'action_scheduler_display' ] );
		add_action( 'pum_tools_page_tab_import', [ __CLASS__, 'import_display' ] );
		add_action( 'pum_save_enabled_betas', [ __CLASS__, 'save_enabled_betas' ] );
		add_action( 'pum_empty_error_log', [ __CLASS__, 'error_log_empty' ] );
	}

	/**
	 * Displays any saved admin notices.
	 */
	public static function notices() {
		if ( isset( $_GET['imported'] ) ) {
			?>
			<div class="updated">
				<p><?php esc_html_e( 'Successfully Imported your themes &amp; modals from Easy Modal.' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['success'] ) && get_option( 'pum_settings_admin_notice' ) ) {
			self::$notices[] = [
				'type'    => $_GET['success'] ? 'success' : 'error',
				'message' => get_option( 'pum_settings_admin_notice' ),
			];

			delete_option( 'pum_settings_admin_notice' );
		}

		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p><strong><?php esc_html( $notice['message'] ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'popup-maker' ); ?></span>
					</button>
				</div>
				<?php
			}
		}
	}

	/**
	 * Render settings page with tabs.
	 */
	public static function page() {
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], self::tabs() ) ? $_GET['tab'] : 'error_log';
		wp_enqueue_style( 'pum-admin-general' );
		?>

		<div class="wrap">
			<form id="pum-tools" method="post" action="">
				<?php wp_nonce_field( basename( __FILE__ ), 'pum_tools_nonce' ); ?>

				<button class="right top button-primary"><?php esc_html_e( 'Save', 'popup-maker' ); ?></button>
				<h1><?php esc_html_e( 'Popup Maker Tools', 'popup-maker' ); ?></h1>

				<h2 id="popmake-tabs" class="nav-tab-wrapper">
					<?php
					foreach ( self::tabs() as $tab_id => $tab_name ) {
						$tab_url = add_query_arg(
							[
								'tools-updated' => false,
								'tab'           => $tab_id,
							]
						);

						printf( '<a href="%s" title="%s" class="nav-tab %s">%s</a>', esc_url( $tab_url ), esc_attr( $tab_name ), $active_tab === $tab_id ? ' nav-tab-active' : '', esc_html( $tab_name ) );
					}
					?>
				</h2>

				<div id="tab_container">
					<?php do_action( 'pum_tools_page_tab_' . $active_tab ); ?>
					<?php do_action( 'popmake_tools_page_tab_' . $active_tab ); ?>
				</div>

			</form>
		</div>
		<?php
	}


	/**
	 * Tabs & labels
	 *
	 * @return array $tabs
	 * @since 1.0
	 */
	public static function tabs() {
		static $tabs;

		if ( ! isset( $tabs ) ) {
			$tabs = apply_filters(
				'pum_tools_tabs',
				[
					'betas'     => __( 'Beta Versions', 'popup-maker' ),
					'error_log' => __( 'Error Log', 'popup-maker' ),
					'import'    => __( 'Import / Export', 'popup-maker' ),
				]
			);

			/** @deprecated 1.7.0 */
			$tabs = apply_filters( 'popmake_tools_tabs', $tabs );
		}

		return $tabs;
	}

	/**
	 * Return an array of all extensions with beta support
	 *
	 * Extensions should be added as 'extension-slug' => 'Extension Name'
	 *
	 * @return      array $extensions The array of extensions
	 * @since       1.5
	 */
	public static function get_beta_enabled_extensions() {
		return apply_filters( 'pum_beta_enabled_extensions', [] );
	}

	/**
	 * @return int|null|string
	 */
	public static function get_active_tab() {
		$tabs = self::tabs();

		return isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? sanitize_text_field( $_GET['tab'] ) : key( $tabs );
	}

	/**
	 * Display beta opt-ins
	 *
	 * @since       1.3
	 */
	public static function betas_display() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$has_beta = self::get_beta_enabled_extensions();

		do_action( 'pum_tools_betas_before' );
		?>

		<div class="postbox pum-beta-support">
			<h3><span><?php esc_html_e( 'Enable Beta Versions', 'popup-maker' ); ?></span></h3>
			<div class="inside">
				<p><?php esc_html_e( 'Checking any of the below checkboxes will opt you in to receive pre-release update notifications. You can opt-out at any time. Pre-release updates do not install automatically, you will still have the opportunity to ignore update notifications.', 'popup-maker' ); ?></p>
				<table class="form-table pum-beta-support">
					<tbody>
					<?php foreach ( $has_beta as $slug => $product ) : ?>
						<tr>
							<?php $checked = self::extension_has_beta_support( $slug ); ?>
							<th scope="row"><?php echo esc_html( $product ); ?></th>
							<td>
								<input type="checkbox" name="enabled_betas[<?php echo esc_attr( $slug ); ?>]" id="enabled_betas[<?php echo esc_attr( $slug ); ?>]"<?php echo checked( $checked, true, false ); ?> value="1" />
								<label for="enabled_betas[<?php echo esc_attr( $slug ); ?>]"><?php printf( __( 'Get updates for pre-release versions of %s', 'popup-maker' ), $product ); ?></label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<input type="hidden" name="pum_action" value="save_enabled_betas" />
				<?php wp_nonce_field( 'pum_save_betas_nonce', 'pum_save_betas_nonce' ); ?>
				<?php submit_button( __( 'Save', 'popup-maker' ), 'secondary', 'submit', false ); ?>
			</div>
		</div>

		<?php
		do_action( 'pum_tools_betas_after' );
	}

	/**
	 * Displays the contents of the Error Log tab
	 *
	 * @since 1.12.0
	 */
	public static function errorlog_display() {
		$enabled = PUM_Utils_Logging::instance()->enabled();

		?>
		<h2>Error Log<?php echo esc_html( $enabled ? '' : ' (disabled)' ); ?></h2>
		<?php if ( $enabled ) : ?>
		<a target="_blank" rel="noreferrer noopener" href="<?php echo esc_url( PUM_Utils_Logging::instance()->get_file_url() ); ?>" download="pum-debug.log" class="button button-primary button-with-icon"><i class="dashicons dashicons-download"></i>Download Error Log</a>
		<form action="" method="POST">
			<input type="hidden" name="pum_action" value="empty_error_log" />
			<?php wp_nonce_field( 'pum_popup_empty_log_nonce', 'pum_popup_empty_log_nonce' ); ?>
			<?php submit_button( 'Empty Error Log', '', 'popmake-empty-log', false ); ?>
		</form>
		<?php endif; ?>
		<div id="log-viewer">
			<pre><?php echo esc_html( self::display_error_log() ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Displays the content for the Scheduled Actions tab.
	 *
	 * @uses ActionScheduler_AdminView::render_admin_ui()
	 * @since 1.12.0
	 */
	public static function action_scheduler_display() {
		if ( class_exists( 'ActionScheduler_AdminView' ) ) {
			$test = new ActionScheduler_AdminView();
			$test->render_admin_ui();
		}
	}

	/**
	 * Displays the contents for the Import tab
	 *
	 * @since 1.12.0
	 */
	public static function import_display() {
		?>
		<h2>Using Easy Modal?</h2>
		<p>Click this button to import popups from the Easy Modal plugin.</p>
		<button id="popmake_emodal_v2_import" name="popmake_emodal_v2_import" class="button button-large">
			<?php esc_html_e( 'Import From Easy Modal v2', 'popup-maker' ); ?>
		</button>
		<?php
	}

	/**
	 * Add a button to import easy modal data.
	 *
	 * @deprecated
	 */
	public static function emodal_v2_import_button() {
		self::import_display();
	}

	/**
	 * Empties error log when user clicks on button
	 *
	 * @since 1.12.0
	 */
	public static function error_log_empty() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['pum_popup_empty_log_nonce'], 'pum_popup_empty_log_nonce' ) ) {
			return;
		}
		PUM_Utils_Logging::instance()->clear_log();
	}

	/**
	 * Process em import.
	 */
	public static function emodal_process_import() {
		if ( ! isset( $_REQUEST['popmake_emodal_v2_import'] ) ) {
			return;
		}
		popmake_emodal_v2_import();
		wp_safe_redirect( admin_url( 'edit.php?post_type=popup&page=pum-tools&imported=1' ), 302 );
	}

	/**
	 * Save enabled betas
	 *
	 * @since       1.5
	 */
	public static function save_enabled_betas() {
		if ( ! wp_verify_nonce( $_POST['pum_save_betas_nonce'], 'pum_save_betas_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! empty( $_POST['enabled_betas'] ) ) {
			$enabled_betas = array_filter(
				array_map(
					[
						__CLASS__,
						'enabled_betas_sanitize_value',
					],
					$_POST['enabled_betas']
				)
			);
			PUM_Utils_Options::update( 'enabled_betas', $enabled_betas );
		} else {
			PUM_Utils_Options::delete( 'enabled_betas' );
		}
	}

	/**
	 * Sanitize the supported beta values by making them booleans
	 *
	 * @param mixed $value The value being sent in, determining if beta support is enabled.
	 *
	 * @return bool
	 * @since 1.5
	 */
	public static function enabled_betas_sanitize_value( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if a given extensions has beta support enabled
	 *
	 * @param string $slug The slug of the extension to check.
	 *
	 * @return      bool True if enabled, false otherwise
	 * @since       1.5
	 */
	public static function extension_has_beta_support( $slug ) {
		$enabled_betas = PUM_Utils_Options::get( 'enabled_betas', [] );
		$return        = false;

		if ( array_key_exists( $slug, $enabled_betas ) ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Retrieves error log and prepares it for displaying
	 *
	 * @uses PUM_Utils_Logging::get_log()
	 * @since 1.12.0
	 */
	public static function display_error_log() {
		$logger = PUM_Utils_Logging::instance();

		if ( ! $logger->enabled() ) {
			return __( 'Debug logging is disabled.', 'popup-maker' );
		}

		// $logger->log( 'Log viewed from Tools page' );

		return $logger->get_log();
	}
}
