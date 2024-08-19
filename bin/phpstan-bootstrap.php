<?php
/**
 * PHP Stan bootstrap file.
 *
 * @package ContentControl
 */

if ( ! defined( 'WP_CLI' ) ) {
	/**
	 * WP CLI.
	 *
	 * @phpstan-type bool $wp_cli
	 * @var bool $wp_cli
	 */
	define( 'WP_CLI', false );
}

if ( ! defined( 'IS_WPCOM' ) ) {
	/**
	 * Is WPCOM.
	 *
	 * @phpstan-type bool $is_wpcom
	 * @var bool $is_wpcom
	 */
	define( 'IS_WPCOM', false );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	/**
	 * Plugin directory.
	 *
	 * @var string $wp_plugin_dir
	 */
	define( 'WP_PLUGIN_DIR', '/path/to/wp-content/plugins' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	/**
	 * Content directory.
	 *
	 * @var string $wp_content_dir
	 */
	define( 'WP_CONTENT_DIR', '/path/to/wp-content' );
}
