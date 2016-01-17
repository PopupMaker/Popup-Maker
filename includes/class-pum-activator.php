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

		update_option( 'pum_ver', POPMAKE_VERSION );
		add_option( 'pum_db_ver', POPMAKE_DB_VERSION );

		flush_rewrite_rules();
	}

}