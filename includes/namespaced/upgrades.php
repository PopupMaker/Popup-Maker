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
 * @since 1.21.0
 */
function current_data_versions() {
	return apply_filters( 'popup_maker/current_data_versions', [
		// CTA data has never had a dedicated migration.
		'pum_ctas'     => 1,
		// Popups were migrated to version 3 in v1.7.0.
		'popups'       => 3,
		// Popup themes reached version 3 in v1.8.0.
		'popup_themes' => 3,
		// Global settings were consolidated in version 3.
		'settings'     => 2, // 3 once we've migrated all settings to JS based camelCase keys to new popup_maker_settings option name.
		// Plugin meta & user meta currently only have one schema.
		'plugin_meta'  => 1,
		'user_meta'    => 1,
	] );
}

/**
 * Get all data versions.
 *
 * @return int[]
 *
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
 *
 * @todo Add support for all data types.
 */
function get_data_version( $key ) {
	$versioning = get_data_versions();

	$current  = isset( $versioning[ $key ] ) ? (int) $versioning[ $key ] : 0;
	$detected = $current;

	switch ( $key ) {
		case 'popups':
				$query = get_posts( [
					'post_type'      => 'popup',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'       => 'popup_settings',
				] );
			if ( ! empty( $query ) ) {
				$detected = 3;
			} else {
					$query = get_posts( [
						'post_type'      => 'popup',
						'post_status'    => 'any',
						'posts_per_page' => 1,
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'       => 'popup_display',
					] );
				if ( ! empty( $query ) ) {
					$detected = 2;
				} elseif ( pum_count_popups() > 0 ) {
						$detected = 1;
				}
			}
			break;

		case 'popup_themes':
				$query = get_posts( [
					'post_type'      => 'popup_theme',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'       => 'popup_theme_settings',
				] );
			if ( ! empty( $query ) ) {
				$detected = 3;
			} else {
					$query = get_posts( [
						'post_type'      => 'popup_theme',
						'post_status'    => 'any',
						'posts_per_page' => 1,
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'       => 'popup_theme_overlay',
					] );
				if ( ! empty( $query ) ) {
					$detected = 2;
				} elseif ( pum_count_themes() > 0 ) {
						$detected = 1;
				}
			}
			break;

		case 'settings':
			// if ( get_option( 'popup_maker_settings', false ) !== false ) {
			// $detected = 3;
			// } else {
			if ( get_option( 'pum_settings', false ) !== false ) {
					$detected = 2;
			} elseif ( get_option( 'popmake_settings', false ) !== false ) {
				$detected = 1;
			}
			break;
	}

	if ( 0 === $current || $detected !== $current ) {
			$versioning[ $key ] = $detected;
			set_data_versions( $versioning );
	}

	return $detected;
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
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
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
 * @since 1.21.0
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
