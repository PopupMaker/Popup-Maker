<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Updates {

	public static function process_updates( $network_wide = false ) {

		// no PHP timeout for running updates
		set_time_limit( 0 );

		$new_ver        = POPMAKE_VERSION;
		$deprecated_ver = get_site_option( 'popmake_version', false );
		$current_ver    = get_site_option( 'pum_ver', $deprecated_ver );

		// Save Upgraded From option
		if ( $current_ver ) {
			update_site_option( 'pum_ver_upgraded_from', $current_ver );
		}

		update_site_option( 'pum_ver', $new_ver );

		// Process DB Updates
		//static::process_db_updates();

		// Install Built In Themes (Only those never installed).
		# TODO Move this to an action hooked here.
		pum_install_built_in_themes();

	}

	public static function process_db_updates() {

		$upgraded_from = get_site_option( 'pum_ver_upgraded_from', false );
		// this is the current database schema version number
		$current_db_ver = get_site_option( 'pum_db_ver', false );

		// If no current db version, but prior install detected, set db version correctly.
		// TODO this is where we left off.
		if ( $upgraded_from && ! $current_db_ver ) {

		}

		// this is the target version that we need to reach
		$target_db_ver = POPMAKE_DB_VERSION;

		require_once POPMAKE_DIR . 'includes/pum-update-functions.php';

		// run update routines one by one until the current version number
		// reaches the target version number
		while ( $current_db_ver < $target_db_ver ) {
			// increment the current db_ver by one
			$current_db_ver ++;

			// each db version will require a separate update function
			// for example, for db_ver 3, the function name should be pum_update_routine_3
			$func = "pum_update_routine_{$current_db_ver}";
			if ( is_callable( $func ) ) {
				call_user_func( $func );
			}

			// update the option in the database, so that this process can always
			// pick up where it left off
			update_option( 'pum_db_ver', $current_db_ver );
		}

	}

}