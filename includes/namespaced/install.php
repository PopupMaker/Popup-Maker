<?php
/**
 * Core functions.
 *
 * @since 1.21.0
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Get current version info. This is the one true API to get this info.
 *
 * @param string|null $key Key of info to return.
 *
 * @return ($key === null ? array{
 *     version: string,
 *     upgraded_from: string,
 *     initial_version: string,
 *     installed_on: string,
 * } : string|null)
 *
 * @since 1.21.0
 */
function get_current_install_info( $key = null ) {
	/**
	 * Should match the option key in the plugin Core class.
	 *
	 * @var array{
	 *     version: string,
	 *     upgraded_from: string,
	 *     initial_version: string,
	 *     installed_on: string,
	 * }
	 */
	$info = get_option( 'popup_maker_version_info' );

	if ( $key ) {
		return $info[ $key ] ?? null;
	}

	return $info;
}

/**
 * Used to detect the previous install version if not currently known.
 *
 * @return string|false
 *
 * @since 1.21.0
 */
function detect_previous_install_version() {
	$version = get_option( 'pum_ver' );

	if ( false === $version ) {
		// Added on install but never used, was likely a typo.
		$version = get_option( 'pum_version' );
	}

	if ( false === $version ) {
		$version = get_option( 'popmake_version' );
	}

	return false === $version ? false : (string) $version;
}

/**
 * Used to detect the initial install version if not currently known.
 *
 * @return string
 *
 * @since 1.21.0
 */
function detect_initial_install_version() {
	$oldest_known = config( 'version' );

	$version = detect_previous_install_version();

	if ( false !== $version && version_compare( $version, $oldest_known, '<' ) ) {
		$oldest_known = (string) $version;
	}

	$upgraded_from = get_option( 'pum_ver_upgraded_from' );
	if ( false !== $upgraded_from && version_compare( $upgraded_from, $oldest_known, '<' ) ) {
		$oldest_known = (string) $upgraded_from;
	}

	$deprecated_ver = get_site_option( 'popmake_version' );
	if ( false !== $deprecated_ver && version_compare( $deprecated_ver, $oldest_known, '<' ) ) {
		$oldest_known = (string) $deprecated_ver;
	}

	$dep_upgraded_from = get_option( 'popmake_version_upgraded_from' );
	if ( false !== $dep_upgraded_from && version_compare( $dep_upgraded_from, $oldest_known, '<' ) ) {
		$oldest_known = (string) $dep_upgraded_from;
	}

	return $oldest_known;
}

/**
 * Used to calculate the initial install date if not currently known.
 *
 * Checks are performed on newest known data points first, then older ones.
 *
 * 1. Current time. (fresh install)
 * 2. 1.8.0 - 1.19.X - pum_installed_on option.
 * 3. <1.8.0 - pum_reviews_installed_on option.
 *
 * @return string
 *
 * @since 1.21.0
 */
function detect_initial_install_date() {
	// 1. Current time. (fresh install)
	$installed_on = current_time( 'mysql' );

	// 2. 1.8.0 - 1.19.X - pum_installed_on option.
	$v1_8_date = get_option( 'pum_installed_on' );
	// 3. <1.8.0 - pum_reviews_installed_on option.
	$review_installed_on = get_option( 'pum_reviews_installed_on' );

	if ( ! empty( $v1_8_date ) ) {
		$installed_on = $v1_8_date;
	} elseif ( ! empty( $review_installed_on ) ) {
		$installed_on = $review_installed_on;
	}

	return $installed_on;
}

/**
 * Cleanup old install data.
 *
 * @since 1.21.0
 */
function cleanup_old_install_data() {
	// Delete old install data.
	delete_option( 'pum_installed_on' );
	delete_option( 'pum_reviews_installed_on' );
	delete_option( 'pum_version_upgraded_from' );
	delete_option( 'pum_initial_version' );
	delete_option( 'pum_db_ver' );
	delete_option( 'pum_ver' );
	delete_option( 'pum_version' ); // Never used but should be removed.
	delete_option( 'popmake_version' );
	delete_option( 'popmake_version_upgraded_from' );
	// TODO search for other data to remove.
}
