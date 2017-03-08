<?php
/**
 * Tools Page
 *
 * Renders the tools page contents.
 *
 * @access      private
 * @since        1.0
 * @return      void
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function popmake_tools_page() {
	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], popmake_get_tools_tabs() ) ? $_GET['tab'] : 'system_info';
	ob_start(); ?>
	<div class="wrap">
	<h2><?php esc_html_e( __( 'Popup Maker Tools', 'popup-maker' ) ); ?></h2>
	<?php if ( isset( $_GET['imported'] ) ) : ?>
		<div class="updated">
			<p><?php _e( 'Successfully Imported your themes &amp; modals from Easy Modal.' ); ?></p>
		</div>
	<?php endif; ?>
	<h2 id="popmake-tabs" class="nav-tab-wrapper"><?php
		foreach ( popmake_get_tools_tabs() as $tab_id => $tab_name ) {

			$tab_url = add_query_arg( array(
				'tools-updated' => false,
				'tab'           => $tab_id,
			) );

			$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

			echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
			echo esc_html( $tab_name );
			echo '</a>';
		} ?>
	</h2>

	<form id="popmake-tools-editor" method="post" action="">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="tab_container">
						<?php do_action( 'popmake_tools_page_tab_' . $active_tab ); ?>
					</div>
					<!-- #tab_container-->
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox " id="submitdiv">
						<h3 class="hndle"><span><?php _e( 'Save', 'popup-maker' ); ?></span></h3>

						<div class="inside">
							<div id="submitpost" class="submitbox">
								<div id="major-publishing-actions" class="submitbox">
									<div id="publishing-action">
										<span class="spinner"></span>
										<input type="submit" accesskey="p" value="<?php _e( 'Save', 'popup-maker' ); ?>" class="button button-primary button-large" id="publish" name="publish">
									</div>
									<div class="clear"></div>
								</div>
							</div>
							<div class="clear"></div>
						</div>
					</div>
					<?php if ( ! popmake_get_option( 'disable_admin_support_widget', false ) ) { ?>
						<div class="postbox " id="supportdiv">
							<h3 class="hndle"><span><?php _e( 'Support', 'popup-maker' ); ?></span></h3>

							<div class="inside">
								<?php popmake_render_support_meta_box(); ?>
								<div class="clear"></div>
							</div>
						</div>
					<?php } ?>
					<?php do_action( 'popmake_admin_sidebar' ); ?>
				</div>
			</div>
			<br class="clear" />
		</div>
	</form>
	</div><?php
	echo ob_get_clean();
}

/**
 * Retrieve settings tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function popmake_get_tools_tabs() {

	$tabs = array();

	if ( count( pum_get_beta_enabled_extensions() ) > 0 ) {
		$tabs['betas'] = __( 'Beta Versions', 'popup-maker' );
	}

	$tabs['system_info'] = __( 'System Info', 'popup-maker' );
	$tabs['import']      = __( 'Import / Export', 'popup-maker' );

	return apply_filters( 'popmake_tools_tabs', $tabs );
}

function popmake_emodal_v2_import_button() { ?>
	<button id="popmake_emodal_v2_import" name="popmake_emodal_v2_import" class="button button-large">
	<?php _e( 'Import From Easy Modal v2', 'popup-maker' ); ?>
	</button><?php
}

add_action( 'popmake_tools_page_tab_import', 'popmake_emodal_v2_import_button' );

function popmake_emodal_admin_init() {
	if ( ! isset( $_REQUEST['popmake_emodal_v2_import'] ) ) {
		return;
	}
	popmake_emodal_v2_import();
	wp_redirect( admin_url( 'edit.php?post_type=popup&page=pum-tools&imported=1' ), 302 );
}

add_action( 'admin_init', 'popmake_emodal_admin_init' );

/**
 * Display the system info tab
 *
 * @since       1.3.0
 * @return      void
 */
function popmake_tools_sysinfo_display() { ?>
	<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=popup&page=pum-tools&tab=system_info' ) ); ?>" method="post" dir="ltr">
		<textarea style="min-height: 350px; width: 100%; display: block;" readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="popmake-sysinfo" title="<?php _e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'popup-maker' ); ?>"><?php echo popmake_tools_sysinfo_get(); ?></textarea>
		<p class="submit">
			<input type="hidden" name="popmake_action" value="popup_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'popmake-download-sysinfo', false ); ?>
		</p>
	</form>
	<?php
}

add_action( 'popmake_tools_page_tab_system_info', 'popmake_tools_sysinfo_display' );


/**
 * Get system info
 *
 * @since       2.0
 * @access      public
 * @global      object $wpdb Used to query the database using the WordPress Database API
 * @return      string $return A string containing the info to output
 */
function popmake_tools_sysinfo_get() {
	global $wpdb;

	if ( ! class_exists( 'Browser' ) ) {
		require_once POPMAKE_DIR . 'includes/libs/browser.php';
	}

	$browser = new Browser();

	// Get theme info
	if ( get_bloginfo( 'version' ) < '3.4' ) {
		$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
		$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
	} else {
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;
	}

	// Try to identify the hosting provider
	$host = popmake_get_host();

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

	// The local users' browser information, handled by the Browser class
	$return .= "\n" . '-- User Browser' . "\n\n";
	$return .= $browser;

	$return = apply_filters( 'popmake_sysinfo_after_user_browser', $return );

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if ( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	// Make sure wp_remote_post() is working
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

	// Popup Maker configuration
	$return .= "\n" . '-- Popup Maker Configuration' . "\n\n";
	$return .= 'Version:                  ' . POPMAKE_VERSION . "\n";
	$return .= 'Upgraded From:            ' . get_option( 'popmake_version_upgraded_from', 'None' ) . "\n";

	$return = apply_filters( 'popmake_sysinfo_after_popmake_config', $return );

	// Must-use plugins
	$muplugins = get_mu_plugins();
	if ( count( $muplugins > 0 ) ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'popmake_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
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

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}

	$return = apply_filters( 'popmake_sysinfo_after_wordpress_plugins_inactive', $return );

	if ( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$plugin = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		$return = apply_filters( 'popmake_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return = apply_filters( 'popmake_sysinfo_after_webserver_config', $return );

	// PHP configs... now we're getting to the important stuff
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	$return = apply_filters( 'popmake_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'popmake_sysinfo_after_php_ext', $return );

	// Session stuff
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled
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
 * Generates a System Info download file
 *
 * @since       2.0
 * @return      void
 */
function popmake_tools_sysinfo_download() {

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="popmake-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['popmake-sysinfo'] );
	exit;
}

add_action( 'popmake_popup_sysinfo', 'popmake_tools_sysinfo_download' );


/**
 * Display beta opt-ins
 *
 * @since       2.6.11
 * @return      void
 */
function pum_tools_betas_display() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$has_beta = pum_get_beta_enabled_extensions();

	do_action( 'pum_tools_betas_before' );
	?>

	<div class="postbox pum-beta-support">
		<h3><span><?php _e( 'Enable Beta Versions', 'popup-maker' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Checking any of the below checkboxes will opt you in to receive pre-release update notifications. You can opt-out at any time. Pre-release updates do not install automatically, you will still have the opportunity to ignore update notifications.', 'popup-maker' ); ?></p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=popup&page=pum-tools&tab=betas' ); ?>">
				<table class="form-table pum-beta-support">
					<tbody>
					<?php foreach ( $has_beta as $slug => $product ) : ?>
						<tr>
							<?php $checked = pum_extension_has_beta_support( $slug ); ?>
							<th scope="row"><?php echo esc_html( $product ); ?></th>
							<td>
								<input type="checkbox" name="enabled_betas[<?php echo esc_attr( $slug ); ?>]" id="enabled_betas[<?php echo esc_attr( $slug ); ?>]"<?php echo checked( $checked, true, false ); ?> value="1" />
								<label for="enabled_betas[<?php echo esc_attr( $slug ); ?>]"><?php printf( __( 'Get updates for pre-release versions of %s', 'popup-maker' ), $product ); ?></label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<input type="hidden" name="popmake_action" value="save_enabled_betas" />
				<?php wp_nonce_field( 'pum_save_betas_nonce', 'pum_save_betas_nonce' ); ?>
				<?php submit_button( __( 'Save', 'popup-maker' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
	</div>

	<?php
	do_action( 'pum_tools_betas_after' );
}

add_action( 'popmake_tools_page_tab_betas', 'pum_tools_betas_display' );


/**
 * Return an array of all extensions with beta support
 *
 * Extensions should be added as 'extension-slug' => 'Extension Name'
 *
 * @since       1.5
 * @return      array $extensions The array of extensions
 */
function pum_get_beta_enabled_extensions() {
	return apply_filters( 'pum_beta_enabled_extensions', array() );
}


/**
 * Check if a given extensions has beta support enabled
 *
 * @since       1.5
 *
 * @param       string $slug The slug of the extension to check
 *
 * @return      bool True if enabled, false otherwise
 */
function pum_extension_has_beta_support( $slug ) {
	$enabled_betas = PUM_Options::get( 'enabled_betas', array() );
	$return        = false;

	if ( array_key_exists( $slug, $enabled_betas ) ) {
		$return = true;
	}

	return $return;
}


/**
 * Save enabled betas
 *
 * @since       1.5
 * @return      void
 */
function pum_tools_enabled_betas_save() {
	if ( ! wp_verify_nonce( $_POST['pum_save_betas_nonce'], 'pum_save_betas_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! empty( $_POST['enabled_betas'] ) ) {
		$enabled_betas = array_filter( array_map( 'pum_tools_enabled_betas_sanitize_value', $_POST['enabled_betas'] ) );
		PUM_Options::update( 'enabled_betas', $enabled_betas );
	} else {
		PUM_Options::delete( 'enabled_betas' );
	}
}

add_action( 'popmake_save_enabled_betas', 'pum_tools_enabled_betas_save' );

/**
 * Sanitize the supported beta values by making them booleans
 *
 * @since 1.5
 *
 * @param mixed $value The value being sent in, determining if beta support is enabled.
 *
 * @return bool
 */
function pum_tools_enabled_betas_sanitize_value( $value ) {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}