<?php
/**
 * Plugin Name:       Popup Maker
 * Plugin URI:        https://wppopupmaker.com/?utm_campaign=plugin-info&utm_source=plugin-header&utm_medium=plugin-uri
 * Description:       Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Version:           1.20.5
 * Requires PHP:      7.2
 * Requires at least: 6.5
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
 * @return (array{
 *     name: string,
 *     slug: string,
 *     version: string,
 *     option_prefix: string,
 *     text_domain: string,
 *     fullname: string,
 *     min_php_ver: string,
 *     min_wp_ver: string,
 *     file: string,
 *     basename: string,
 *     url: string,
 *     path: string,
 *     api_url: string,
 * })
 *
 * @since 1.20.0
 */
function popup_maker_config() {
	static $config;

	if ( ! isset( $config ) ) {
		$config = [
			// Using untranslated strings in config to avoid early translation loading.
			// Translations for these strings should be handled at the point of display.
			'name'           => 'Popup Maker',
			'slug'           => 'popup-maker',
			'version'        => '1.20.5',
			'option_prefix'  => 'popup_maker',
			'text_domain'    => 'popup-maker',
			'fullname'       => 'Popup Maker',
			'min_wp_ver'     => '6.6.0',
			'min_php_ver'    => '7.4.0',
			'future_wp_req'  => '6.6.0',
			'future_php_req' => '7.4.0',
			'file'           => __FILE__,
			'basename'       => plugin_basename( __FILE__ ),
			'url'            => plugin_dir_url( __FILE__ ),
			'path'           => plugin_dir_path( __FILE__ ),
			'api_url'        => 'https://wppopupmaker.com/',
		];
	}

	return $config;
}

/**
 * Legacy bootstrap.
 *
 * Includes a non composer autoloader for backwards compatibility.
 * This self unregisters itself if no autoloaders are present.
 *
 * This goes first as we potentially bail early if the autoloader fails below.
 * This order serves to prevent errors with extension initialization or causing errors.
 */
require_once __DIR__ . '/bootstrap.legacy.php';

/**
 * Load the plugin config and register autoloader.
 * This handles the main initialization logic.
 */
require_once __DIR__ . '/bootstrap.php';

// Register activation, deactivation & uninstall hooks.
register_activation_hook( __FILE__, [ 'PUM_Install', 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ 'PUM_Install', 'deactivate_plugin' ] );
register_uninstall_hook( __FILE__, [ 'PUM_Install', 'uninstall_plugin' ] );
