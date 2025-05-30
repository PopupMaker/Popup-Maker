<?php
/**
 * Backward compatibility functions.
 *
 * @package PopupMaker
 */

namespace PopupMaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function wp_parse_args;
use function apply_filters;

/**
 * Get the current data versions.
 *
 * @return int[]
 *
 * @since X.X.X
 */
function current_data_versions() {
	// TODO: Add current data versions.
	return apply_filters( 'popup_maker/current_data_versions', [
		'ctas'         => 1,
		'popups'       => 3,
		'popup_themes' => 3,
		'settings'     => 3,
		'plugin_meta'  => 3,
		'user_meta'    => 3,
	] );
}

/**
 * Get all data versions.
 *
 * @return int[]
 *
 * @since X.X.X
 */
function get_data_versions() {
	$versioning = \get_option( 'popup_maker_data_versioning', [] );

	return wp_parse_args( $versioning, current_data_versions() );
}

/**
 * Set the data version.
 *
 * @param string $key    Data key.
 * @param int    $version Data version.
 *
 * @return bool
 *
 * @since X.X.X
 */
function set_data_version( $key, $version ) {
	$versioning = get_data_versions();

	$versioning[ $key ] = $version;

	return set_data_versions( $versioning );
}

/**
 * Set the data version.
 *
 * @param int[] $versioning Data versions.
 *
 * @return bool
 *
 * @since X.X.X
 */
function set_data_versions( $versioning ) {
	$versioning = wp_parse_args( $versioning, get_data_versions() );

	return \update_option( 'popup_maker_data_versioning', $versioning );
}

/**
 * Get the current data version.
 *
 * @param string $key Type of data to get version for.
 *
 * @return int|bool
 *
 * @since X.X.X
 *
 * @todo Add support for all data types.
 */
function get_data_version( $key ) {
	$versioning = get_data_versions();

	switch ( $key ) {
		// Fallthrough.
		case 'popups':
			// If set to v1 and there are no v1 popups, set to v2.
			if ( 1 === $versioning[ $key ] ) {
				// TODO check for v1 popups (and v2, v3, etc) this is mocked.
				$v1_popups = [];

				if ( false === $v1_popups ) {
					$versioning[ $key ] = 2;
					set_data_versions( $versioning );
					return 2;
				}
			}

			break;

		case 'settings':
			// If set to v1 and there are no v1 settings, set to v2.
			if ( 1 === $versioning[ $key ] ) {
				// TODO check for v1 settings (and v2, v3, etc) this is mocked.
				$v1_settings = get_option( 'popup_maker_settings', [] );

				if ( empty( $v1_settings ) ) {
					$versioning[ $key ] = 2;
					set_data_versions( $versioning );
					return 2;
				}
			}

			break;
	}

	return isset( $versioning[ $key ] ) ? $versioning[ $key ] : false;
}

/**
 * Checks if user is upgrading from < 2.0.0.
 *
 * Sets data versioning to 1 as they didn't exist before.
 *
 * @param string $old_version Old version.
 *
 * @return void
 *
 * @since X.X.X
 *
 * @todo This is placeholder for future v2 migrations.
 */
function maybe_force_v2_migrations( $old_version ) {
	if ( version_compare( $old_version, '2.0.0', '<' ) ) {
		$versioning = get_data_versions();

		// Forces updates for all data types to v2.
		$versioning = wp_parse_args( [
			'popups'       => 1,
			'popup_themes' => 1,
			'settings'     => 1,
			'plugin_meta'  => 1,
			'user_meta'    => 1,
		], $versioning );

		\update_option( 'popup_maker_data_versioning', $versioning );
	}
}

/**
 * Get the name of an upgrade.
 *
 * @param string|\PopupMaker\Base\Upgrade $upgrade Upgrade to get name for.
 *
 * @return string
 *
 * @since X.X.X
 *
 * @todo Add support for all upgrade types.
 */
function get_upgrade_name( $upgrade ) {
	if ( is_object( $upgrade ) ) {
		$upgrade = $upgrade::TYPE . '-' . $upgrade::VERSION;
	}

	return $upgrade;
}

/**
 * Get the completed upgrades.
 *
 * @return string[]
 *
 * @since X.X.X
 *
 * @todo migrate functionality from class to here.
 */
function get_completed_upgrades() {
	return \PUM_Utils_Upgrades::instance()->get_completed_upgrades();

	// TODO Remove this once all upgrades are converted to use the new ID format.
	// phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
	return \get_option( 'popup_maker_completed_upgrades', [] );
}

/**
 * Set the completed upgrades.
 *
 * @param string[] $upgrades Completed upgrades.
 *
 * @return bool
 *
 * @since X.X.X
 *
 * @todo migrate functionality from class to here.
 */
function set_completed_upgrades( $upgrades ) {
	return \update_option( 'popup_maker_completed_upgrades', $upgrades );
}

/**
 * Mark an upgrade as complete.
 *
 * @param \PopupMaker\Base\Upgrade|string $upgrade Upgrade to mark as complete.
 *
 * @return void
 *
 * @since X.X.X
 *
 * @todo migrate functionality from class to here.
 */
function mark_upgrade_complete( $upgrade ) {
	\PUM_Utils_Upgrades::instance()->set_upgrade_complete( $upgrade );

	return;
	// TODO Remove this once all upgrades are converted to use the new ID format.
	// phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable

	$upgrade_name = get_upgrade_name( $upgrade );

	$upgrades = get_completed_upgrades();

	if ( ! in_array( $upgrade_name, $upgrades, true ) ) {
		$upgrades[] = $upgrade_name;
	}

	set_completed_upgrades( $upgrades );

	// Update the data version.
	set_data_version( $upgrade::TYPE, $upgrade::VERSION );

	/**
	 * Fires when an upgrade is marked as complete.
	 *
	 * @param \PopupMaker\Base\Upgrade $upgrade Upgrade type.
	 */
	do_action( 'popup_maker/upgrade_complete', $upgrade );

	/**
	 * Fires when a specific upgrade is marked as complete.
	 *
	 * @param \PopupMaker\Base\Upgrade $upgrade Upgrade type.
	 */
	do_action( "popup_maker/upgrade_complete/{$upgrade_name}", $upgrade );

	// TODO Remove this once all upgrades are converted to use the new ID format.
	// phpcs:enable Squiz.PHP.NonExecutableCode.Unreachable
}

/**
 * Check if an upgrade has been completed.
 *
 * @param string|\PopupMaker\Base\Upgrade $upgrade Upgrade to check.
 *
 * @return bool
 *
 * @since X.X.X
 *
 * @todo migrate functionality from class to here.
 */
function is_upgrade_complete( $upgrade ) {
	return \PUM_Utils_Upgrades::instance()->has_completed_upgrade( $upgrade );

	// TODO Remove this once all upgrades are converted to use the new ID format.
	// phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable

	$upgrade = get_upgrade_name( $upgrade );

	$upgrades = get_completed_upgrades();

	return in_array( $upgrade, $upgrades, true );

	// TODO Remove this once all upgrades are converted to use the new ID format.
	// phpcs:enable Squiz.PHP.NonExecutableCode.Unreachable
}
