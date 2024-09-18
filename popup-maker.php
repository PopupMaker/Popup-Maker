<?php
/**
 * Plugin Name:       Popup Maker
 * Plugin URI:        https://wppopupmaker.com/?utm_campaign=plugin-info&utm_source=plugin-header&utm_medium=plugin-uri
 * Description:       Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Version:           1.20.1
 * Requires PHP:      5.6
 * Requires at least: 4.9
 * Author:            Popup Maker
 * Author URI:        https://wppopupmaker.com/?utm_campaign=plugin-info&utm_source=plugin-header&utm_medium=author-uri
 * License:           GPL2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       popup-maker
 * Domain Path:       /languages/
 *
 * @package     PopupMaker
 * @author      Daniel Iser
 * @copyright   Copyright (c) 2023, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define plugin's global configuration.
 *
 * @return array<string,string|bool>
 *
 * @since 1.20.0
 */
function popup_maker_config() {
	return [
		'name'           => __( 'Popup Maker', 'popup-maker' ),
		'slug'           => 'popup-maker',
		'version'        => '1.20.1',
		'option_prefix'  => 'popup_maker',
		'text_domain'    => 'popup-maker',
		'fullname'       => __( 'Popup Maker', 'popup-maker' ),
		'min_wp_ver'     => '4.9.0',
		'min_php_ver'    => '5.6.0',
		'future_wp_req'  => '6.5.0',
		'future_php_req' => '7.4.0',
		'file'           => __FILE__,
		'basename'       => plugin_basename( __FILE__ ),
		'url'            => plugin_dir_url( __FILE__ ),
		'path'           => __DIR__ . \DIRECTORY_SEPARATOR,
		'api_url'        => 'https://wppopupmaker.com/',
	];
}

/**
 * Legacy bootstrap.
 *
 * Includes a non composer autoloadaer for backwards compatibility.
 * This self unregisters itself if no autoloaders are present.
 *
 * This goes first as we potentially bail early if the autoloader fails below.
 * This order serves to prevent errors with extension initialization or causing errors.
 */
require_once __DIR__ . '/bootstrap.legacy.php';

/**
 * Load the current main class.
 *
 * This is a placeholder for the eventual removal and deferal to the autoloader.
 */
require_once __DIR__ . '/includes/class-popup-maker.php';

/**
 * Load the plugin config and register autoloader.
 */
require_once __DIR__ . '/bootstrap.php';

/**
 * Initialize Popup Maker if requirements are met.
 *
 * NOTE: This will be replaced with the simpler init function
 * below once we add a plugin container class.
 *
 * @since 1.8.0
 */
function pum_init() {
	if ( ! \PopupMaker\check_prerequisites() ) {
		/**
		 * Required, some older extensions init and require
		 * these functions to not error.
		 *
		 * TODO In the near future we could move the requires to
		 * the bootstrap.php file meaning they would always be
		 * available.
		 */
		require_once 'includes/failsafes.php';
		return;
	}

	// Get Popup Maker
	pum();

	// Initialize old PUM extensions.
	add_action( 'plugins_loaded', 'popmake_initialize' );
}

// Get Popup Maker running.
add_action( 'plugins_loaded', 'pum_init', 9 );

// Ensure plugin & environment compatibility or deactivate if not.
register_activation_hook( __FILE__, [ 'PUM_Install', 'activation_check' ] );

// Register activation, deactivation & uninstall hooks.
register_activation_hook( __FILE__, [ 'PUM_Install', 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ 'PUM_Install', 'deactivate_plugin' ] );
register_uninstall_hook( __FILE__, [ 'PUM_Install', 'uninstall_plugin' ] );
