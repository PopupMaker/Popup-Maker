<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @since 1.7.0
 *
 * @see PUM_Upgrades::set_upgrade_complete
 *
 * @param  string $upgrade_id The action to add to the competed upgrades array
 *
 * @return bool If the function was successfully added
 */
function pum_set_upgrade_complete( $upgrade_id = '' ) {
	return PUM_Upgrades::instance()->set_upgrade_complete( $upgrade_id );
}

/**
 * Get's the array of completed upgrade actions
 *
 * @since 1.7.0
 *
 * @return array The array of completed upgrades
 */
function pum_get_completed_upgrades() {
	return PUM_Upgrades::instance()->get_completed_upgrades();
}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since 1.7.0
 *
 * @param  string $upgrade_id The upgrade action to check completion for
 *
 * @return bool  If the action has been added to the completed actions array
 */
function pum_has_completed_upgrade( $upgrade_id = '' ) {
	return PUM_Upgrades::instance()->has_completed_upgrade( $upgrade_id );
}


