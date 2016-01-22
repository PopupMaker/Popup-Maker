<?php

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

		$deprecated_ver = get_site_option( 'popmake_version', false );
		$current_ver    = get_site_option( 'pum_ver', $deprecated_ver );

		// Save Upgraded From option
		if ( $current_ver ) {
			update_site_option( 'pum_ver_upgraded_from', $current_ver );
		}

		update_site_option( 'pum_ver', PUM::VER );

		add_site_option( 'pum_db_ver', PUM::DB_VER );

		pum_install_built_in_themes();

		flush_rewrite_rules();
	}

}