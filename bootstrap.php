<?php
/**
 * Popup Maker Bootstrap.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Define plugin's global configuration.
 *
 * @return array<string,string|bool>
 *
 * @since 1.20.0
 */
function get_plugin_config() {
	return popup_maker_config();
}

/**
 * Get config or config property.
 *
 * @param string|null $key Key of config item to return.
 *
 * @return mixed
 *
 * @since 1.20.0
 */
function config( $key = null ) {
	$config = get_plugin_config();

	if ( ! isset( $key ) ) {
		return $config;
	}

	return isset( $config[ $key ] ) ? $config[ $key ] : false;
}

/**
 * Register autoloader.
 */
require_once __DIR__ . '/vendor-prefixed/code-atlantic/wp-autoloader/src/Autoloader.php';

if ( ! \PopupMaker\Vendor\CodeAtlantic\Autoloader\Autoloader::init( config( 'name' ), config( 'path' ) ) ) {
	return;
}

/**
 * Check plugin prerequisites.
 *
 * @return bool
 *
 * @since 1.20.0
 */
function check_prerequisites() {

	// 1.a Check Prerequisites.
	$prerequisites = new \PopupMaker\Vendor\CodeAtlantic\PrerequisiteChecks\Prerequisites(
		[
			[
				// a. PHP Min Version.
				'type'    => 'php',
				'version' => config( 'min_php_ver' ),
			],
			// a. PHP Min Version.
			[
				'type'    => 'wp',
				'version' => config( 'min_wp_ver' ),
			],
		],
		config()
	);

	/**
	 * 1.b If there are missing requirements, render error messaging and return.
	 */
	if ( $prerequisites->check() === false ) {
		$prerequisites->setup_notices();

		return false;
	}

	return true;
}

return;

/*
This is for future use.
add_action(
	'plugins_loaded',
	function () {
		if ( check_prerequisites() ) {
			plugin_instance();
		}
	},
	// Core plugin loads at 11, Pro loads at 12 & addons load at 13.
	11
);
*/

/**
 * Initiates and/or retrieves an encapsulated container for the plugin.
 *
 * This kicks it all off, loads functions and initiates the plugins main class.
 *
 * @return \PopupMaker\Plugin\Core
 */

/*
This is for future use.

function plugin_instance() {
	static $plugin;

	if ( ! $plugin instanceof \PopupMaker\Plugin\Core ) {
		require_once __DIR__ . '/inc/functions.php';
		require_once __DIR__ . '/inc/deprecated.php';
		$plugin = new Plugin\Core( get_plugin_config() );
	}

	return $plugin;
}
*/

/**
 * Easy access to all plugin services from the container.
 *
 * @see \PopupMaker\plugin_instance
 *
 * @param string|null $service_or_config Key of service or config to fetch.
 * @return \PopupMaker\Plugin\Core|mixed
 */

/*
This is for future use.

function plugin( $service_or_config = null ) {
	if ( ! isset( $service_or_config ) ) {
		return plugin_instance();
	}

	return plugin_instance()->get( $service_or_config );
}
*/

// Future use.
// \register_activation_hook( __FILE__, '\PopupMaker\Plugin\Install::activate_plugin' );
// \register_deactivation_hook( __FILE__, '\PopupMaker\Plugin\Install::deactivate_plugin' );
// \register_uninstall_hook( __FILE__, '\PopupMaker\Plugin\Install::uninstall_plugin' );
