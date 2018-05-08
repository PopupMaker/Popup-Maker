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
 * @since      1.4
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
	 * @since    1.4
	 *
	 * @param bool $network_wide
	 */
	public static function activate( $network_wide = false ) {
		global $wpdb;

		// Setup the Popup & Theme Custom Post Type
		PUM_Types::register_post_types();

		// Setup the Popup Taxonomies
		PUM_Types::register_taxonomies( true );

		if ( is_multisite() && $network_wide ) { // See if being activated on the entire network or one blog

			$current_blog = $wpdb->blogid;

			$activated = array();

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

			// Try to reduce the chances of a timeout with a large number of sites.
			if ( count( $blog_ids ) > 2 ) {

				ignore_user_abort( true );

				if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
					@set_time_limit( 0 );
				}

			}

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				self::activate_site();

				$activated[] = $blog_id;
			}

			// Switch back to the current blog
			switch_to_blog( $current_blog );

			// Store the array for a later function
			update_site_option( 'pum_activated', $activated );

			return;
		}

		// Get the Version Data Set.
		if ( ! class_exists( 'PUM_Admin_Upgrades' ) ) {
			require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-upgrades.php';
		}

		// Running on a single blog

		self::activate_site();

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Clear the permalinks
		flush_rewrite_rules();

		return;

	}


	public static function activate_site() {

		$options = array_merge( get_option( 'popmake_settings', array() ), array(
			'disable_popup_category_tag' => 1,
		) );

		// Setup some default options
		add_option( 'popmake_settings', $options );

		add_option( 'pum_version', Popup_Maker::$VER );

		// Updates stored values for versioning.
		PUM_Upgrades::update_plugin_version();

		// Add a temporary option that will fire a hookable action on next load.
		set_transient( '_popmake_installed', true, 30 );

		popmake_get_default_popup_theme();
		pum_install_built_in_themes();

	}

}