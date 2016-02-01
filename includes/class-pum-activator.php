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
	 *
	 * @param bool $network_wide
	 */
	public static function activate( $network_wide = false ) {

		global $popmake_options;

		// Setup the Popup & Theme Custom Post Type
		popmake_setup_post_types();

		// Setup the Popup Taxonomies
		popmake_setup_taxonomies();

		// Get the Version Data Set.
		if ( ! class_exists( 'PUM_Admin_Upgrades' ) ) {
			require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-upgrades.php';
		}
		PUM_Admin_Upgrades::instance();

		// Setup some default options
		$options = array(
			'disable_popup_category_tag' => 1,
		);
		update_option( 'popmake_settings', array_merge( $popmake_options, $options ) );

		// Add a temporary option that will fire a hookable action on next load.
		set_transient( '_popmake_installed', true, 30 );

		//
		if ( $network_wide ) {
			// TODO Add a loop here for each blog.
			// foreach ( blog ) { do the actions }
		} else {
			popmake_get_default_popup_theme();
			pum_install_built_in_themes();
		}

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Add the transient to redirect to welcome page.
		set_transient( '_popmake_activation_redirect', true, 30 );

		// Clear the permalinks
		flush_rewrite_rules();

	}

}