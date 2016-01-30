<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.4.0
 * @package    PUM
 * @subpackage PUM/includes
 * @author     Daniel Iser <danieliser@wizardinternetsolutions.com>
 */
class PUM_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.4.0
	 */
	public static function activate( $network_wide = false ) {

		global $wpdb, $popmake_options, $wp_version;

		// Setup the Popup & Theme Custom Post Type
		popmake_setup_post_types();

		// Setup the Popup Taxonomies
		popmake_setup_taxonomies();


		$deprecated_ver = get_site_option( 'popmake_version', false );
		$current_ver    = get_site_option( 'pum_ver', $deprecated_ver );

		// Save Upgraded From option
		if ( $current_ver ) {
			update_site_option( 'pum_ver_upgraded_from', $current_ver );
		}

		update_site_option( 'pum_ver', PUM::VER );

		// Setup some default options
		$options = array();

		if ( ! isset( $popmake_options['popmake_powered_by_size'] ) ) {
			$popmake_options['popmake_powered_by_size'] = '';
		}

		update_option( 'popmake_settings', array_merge( $popmake_options, $options ) );
		update_option( 'popmake_version', POPMAKE_VERSION );

		// Add a temporary option to note that POPMAKE theme is ready for customization
		set_transient( '_popmake_installed', true, 30 );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}
		// Add the transient to redirect to welcome page.
		set_transient( '_popmake_activation_redirect', true, 30 );

		popmake_get_default_popup_theme();

		pum_install_built_in_themes();

		// Clear the permalinks
		flush_rewrite_rules();

	}

}