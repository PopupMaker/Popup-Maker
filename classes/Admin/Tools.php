<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

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
	public static $notices = array();

	/**
	 * Initializes the "Tools" page.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'admin_init', array( __CLASS__, 'emodal_process_import' ) );
		add_action( 'pum_tools_page_tab_betas', array( __CLASS__, 'betas_display' ) );
		add_action( 'pum_tools_page_tab_system_info', array( __CLASS__, 'sysinfo_display' ) );
		add_action( 'pum_tools_page_tab_error_log', array( __CLASS__, 'errorlog_display' ) );
		add_action( 'pum_tools_page_tab_action_scheduler', array( __CLASS__, 'action_scheduler_display' ) );
		add_action( 'pum_tools_page_tab_import', array( __CLASS__, 'import_display' ) );
		add_action( 'pum_save_enabled_betas', array( __CLASS__, 'save_enabled_betas' ) );
		add_action( 'pum_popup_sysinfo', array( __CLASS__, 'popup_sysinfo' ) );
		add_action( 'pum_empty_error_log', array( __CLASS__, 'error_log_empty' ) );
	}

	/**
	 * Displays any saved admin notices.
	 */
	public static function notices() {

		if ( isset( $_GET['imported'] ) ) {
			?>
            <div class="updated">
                <p><?php _e( 'Successfully Imported your themes &amp; modals from Easy Modal.' ); ?></p>
            </div>
			<?php
		}


		if ( isset( $_GET['success'] ) && get_option( 'pum_settings_admin_notice' ) ) {
			self::$notices[] = array(
				'type'    => $_GET['success'] ? 'success' : 'error',
				'message' => get_option( 'pum_settings_admin_notice' ),
			);

			delete_option( 'pum_settings_admin_notice' );
		}

		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) { ?>
                <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                    <p><strong><?php esc_html_e( $notice['message'] ); ?></strong></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'popup-maker' ); ?></span>
                    </button>
                </div>
			<?php }
		}
	}

	/**
	 * Render settings page with tabs.
	 */
	public static function page() {

		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], self::tabs() ) ? $_GET['tab'] : 'system_info';
		wp_enqueue_style( 'pum-admin-general' );
		?>

        <div class="wrap">

            <form id="pum-tools" method="post" action="">

				<?php wp_nonce_field( basename( __FILE__ ), 'pum_tools_nonce' ); ?>

                <button class="right top button-primary"><?php _e( 'Save', 'popup-maker' ); ?></button>

                <h1><?php _e( 'Popup Maker Tools', 'popup-maker' ); ?></h1>

                <h2 id="popmake-tabs" class="nav-tab-wrapper"><?php
					foreach ( self::tabs() as $tab_id => $tab_name ) {
						$tab_url = add_query_arg( array(
							'tools-updated' => false,
							'tab'           => $tab_id,
						) );

						printf( '<a href="%s" title="%s" class="nav-tab %s">%s</a>', esc_url( $tab_url ), esc_attr( $tab_name ), $active_tab == $tab_id ? ' nav-tab-active' : '', esc_html( $tab_name ) );
					} ?>
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
				array(
					'betas'            => __( 'Beta Versions', 'popup-maker' ),
					'system_info'      => __( 'System Info', 'popup-maker' ),
					'error_log'        => __( 'Error Log', 'popup-maker' ),
					'action_scheduler' => __( 'Scheduled Actions', 'popup-maker' ),
					'import'           => __( 'Import / Export', 'popup-maker' ),
				)
			);

			/** @deprecated 1.7.0 */
			$tabs = apply_filters( 'popmake_tools_tabs', $tabs );
		}

		/*if ( count( self::get_beta_enabled_extensions() ) == 0 ) {
			unset( $tabs['betas'] );
		}*/

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
		return apply_filters( 'pum_beta_enabled_extensions', array() );
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
			<h3><span><?php _e( 'Enable Beta Versions', 'popup-maker' ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Checking any of the below checkboxes will opt you in to receive pre-release update notifications. You can opt-out at any time. Pre-release updates do not install automatically, you will still have the opportunity to ignore update notifications.', 'popup-maker' ); ?></p>
				<table class="form-table pum-beta-support">
					<tbody>
					<?php foreach ( $has_beta as $slug => $product ) : ?>
						<tr>
							<?php $checked = self::extension_has_beta_support( $slug ); ?>
							<th scope="row"><?php echo esc_html( $product ); ?></th>
							<td>
								<input type="checkbox" name="enabled_betas[<?php echo esc_attr( $slug ); ?>]" id="enabled_betas[<?php echo esc_attr( $slug ); ?>]"<?php echo checked( $checked, true, false ); ?>
									   value="1" />
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
	 * Display the system info tab
	 *
	 * @since       1.3.0
	 */
	public static function sysinfo_display() {
		?>
		<form action="" method="post">
			<textarea style="min-height: 350px; width: 100%; display: block;" readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" title="<?php esc_html_e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'popup-maker' ); ?>"><?php echo esc_html( self::sysinfo_text() ); ?></textarea>
			<p class="submit">
				<input type="hidden" name="pum_action" value="popup_sysinfo" />
				<?php wp_nonce_field( 'pum_popup_sysinfo_nonce', 'pum_popup_sysinfo_nonce' ); ?>
				<?php submit_button( 'Download System Info File', 'primary', 'popmake-download-sysinfo', false ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Displays the contents of the Error Log tab
	 *
	 * @since 1.12.0
	 */
	public static function errorlog_display() {
		?>
		<h2>Error Log</h2>
		<a target="_blank" rel="noreferrer noopener" href="<?php echo esc_url( PUM_Utils_Logging::instance()->get_file_url() ); ?>" download="pum-debug.log" class="button button-primary button-with-icon"><i class="dashicons dashicons-download"></i>Download Error Log</a>
		<form action="" method="POST">
			<input type="hidden" name="pum_action" value="empty_error_log" />
			<?php wp_nonce_field( 'pum_popup_empty_log_nonce', 'pum_popup_empty_log_nonce' ); ?>
			<?php submit_button( 'Empty Error Log', '', 'popmake-empty-log', false ); ?>
		</form>
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
			<?php _e( 'Import From Easy Modal v2', 'popup-maker' ); ?>
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
	 * Get system info
	 *
	 * @return      string $return A string containing the info to output
	 * @since       1.5
	 */
	public static function sysinfo_text() {
		global $wpdb;

		if ( ! class_exists( 'Browser' ) ) {
			require_once POPMAKE_DIR . 'includes/libs/browser.php';
		}

		$browser = new Browser();

		// Get theme info.
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		// Try to identify the hosting provider.
		$host = self::get_host();

		$return = '### Begin System Info ###' . "\n\n";

		// Start with the basics...
		$return .= '-- Site Info' . "\n\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_site_info', $return );

		// Can we determine the site's host?
		if ( $host ) {
			$return .= "\n" . '-- Hosting Provider' . "\n\n";
			$return .= 'Host:                     ' . $host . "\n";

			$return = apply_filters( 'popmake_sysinfo_after_host_info', $return );
		}

		// The local users' browser information, handled by the Browser class.
		$return .= "\n" . '-- User Browser' . "\n\n";
		$return .= $browser;

		$return = apply_filters( 'popmake_sysinfo_after_user_browser', $return );

		// WordPress configuration.
		$return .= "\n" . '-- WordPress Configuration' . "\n\n";
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$return .= 'Active Theme:             ' . $theme . "\n";
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

		// Only show page specs if frontpage is set to 'page'.
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id  = get_option( 'page_for_posts' );

			$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}

		// Make sure wp_remote_post() is working.
		$request['cmd'] = '_notify-validate';

		$params = array(
			'sslverify'  => false,
			'timeout'    => 60,
			'user-agent' => 'POPMAKE/' . POPMAKE_VERSION,
			'body'       => $request,
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works';
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work';
		}

		$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_wordpress_config', $return );

		// Popup Maker configuration.
		$return .= "\n" . '-- Popup Maker Configuration' . "\n\n";
		$return .= 'Version:                  ' . POPMAKE_VERSION . "\n";
		$return .= 'Upgraded From:            ' . get_option( 'popmake_version_upgraded_from', 'None' ) . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_popmake_config', $return );

		// Must-use plugins.
		$muplugins = function_exists( 'get_mu_plugins' ) ? get_mu_plugins() : array();
		if ( $muplugins && count( $muplugins ) ) {
			$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

			foreach ( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}

			$return = apply_filters( 'popmake_sysinfo_after_wordpress_mu_plugins', $return );
		}

		// WordPress active plugins.
		$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		$return = apply_filters( 'popmake_sysinfo_after_wordpress_plugins', $return );

		// WordPress inactive plugins.
		$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		$return = apply_filters( 'popmake_sysinfo_after_wordpress_plugins_inactive', $return );

		if ( is_multisite() ) {
			// WordPress Multisite active plugins.
			$return .= "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}

				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
			}

			$return = apply_filters( 'popmake_sysinfo_after_wordpress_ms_plugins', $return );
		}

		// Server configuration (really just versioning).
		$return .= "\n" . '-- Webserver Configuration' . "\n\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_webserver_config', $return );

		// PHP configs... now we're getting to the important stuff.
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_php_config', $return );

		// PHP extensions and such.
		$return .= "\n" . '-- PHP Extensions' . "\n\n";
		$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
		$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

		$return = apply_filters( 'popmake_sysinfo_after_php_ext', $return );

		// Session stuff.
		$return .= "\n" . '-- Session Configuration' . "\n\n";
		$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

		// The rest of this is only relevant is session is enabled.
		if ( isset( $_SESSION ) ) {
			$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
			$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
			$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
			$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
			$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
		}

		$return = apply_filters( 'popmake_sysinfo_after_session_config', $return );

		$return .= "\n" . '### End System Info ###';

		return $return;
	}

	/**
	 * Get user host
	 *
	 * Returns the webhost this site is using if possible
	 *
	 * @return mixed string $host if detected, false otherwise
	 * @since 1.3.0
	 */
	public static function get_host() {
		if ( defined( 'WPE_APIKEY' ) ) {
			return 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			return 'Pagely';
		} elseif ( DB_HOST === 'localhost:/tmp/mysql5.sock' ) {
			return 'ICDSoft';
		} elseif ( DB_HOST === 'mysqlv5' ) {
			return 'NetworkSolutions';
		} elseif ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
			return 'iPage';
		} elseif ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
			return 'IPower';
		} elseif ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
			return 'MediaTemple Grid';
		} elseif ( strpos( DB_HOST, '.pair.com' ) !== false ) {
			return 'pair Networks';
		} elseif ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
			return 'Rackspace Cloud';
		} elseif ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
			return 'SysFix.eu Power Hosting';
		} elseif ( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
			return 'Flywheel';
		} else {
			// Adding a general fallback for data gathering.
			return 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
		}
	}

	/**
	 * Generates a System Info download file
	 *
	 * @since       1.5
	 */
	public static function popup_sysinfo() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['pum_popup_sysinfo_nonce'], 'pum_popup_sysinfo_nonce' ) ) {
			return;
		}

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="popmake-system-info.txt"' );

		echo self::sysinfo_text();
		exit;
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
		wp_redirect( admin_url( 'edit.php?post_type=popup&page=pum-tools&imported=1' ), 302 );
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
			$enabled_betas = array_filter( array_map( array(
				__CLASS__,
				'enabled_betas_sanitize_value',
			), $_POST['enabled_betas'] ) );
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
	 * @param string $slug The slug of the extension to check
	 *
	 * @return      bool True if enabled, false otherwise
	 * @since       1.5
	 *
	 */
	public static function extension_has_beta_support( $slug ) {
		$enabled_betas = PUM_Utils_Options::get( 'enabled_betas', array() );
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
		return PUM_Utils_Logging::instance()->get_log();
	}
}
