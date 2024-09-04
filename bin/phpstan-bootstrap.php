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

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', false );
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

if ( ! defined( 'POPMAKE' ) ) {
	define( 'POPMAKE', '/path/to/wp-content/plugins/popup-maker/popup-maker.php' );
}

if ( ! defined( 'POPMAKE_NAME' ) ) {
	define( 'POPMAKE_NAME', 'Popup Maker' );
}

if ( ! defined( 'POPMAKE_SLUG' ) ) {
	define( 'POPMAKE_SLUG', 'popup-maker' );
}

if ( ! defined( 'POPMAKE_DIR' ) ) {
	define( 'POPMAKE_DIR', '/path/to/wp-content/plugins/popup-maker/' );
}

if ( ! defined( 'POPMAKE_URL' ) ) {
	define( 'POPMAKE_URL', '/wp-content/plugins/popup-maker/' );
}

if ( ! defined( 'POPMAKE_NONCE' ) ) {
	define( 'POPMAKE_NONCE', 'popmake_nonce' );
}

if ( ! defined( 'POPMAKE_VERSION' ) ) {
	define( 'POPMAKE_VERSION', '1.19.1' );
}

if ( ! defined( 'POPMAKE_DB_VERSION' ) ) {
	define( 'POPMAKE_DB_VERSION', 8 );
}

if ( ! defined( 'POPMAKE_API_URL' ) ) {
	define( 'POPMAKE_API_URL', 'https://wppopupmaker.com' );
}

if ( ! defined( 'PUM_AWEBER_INTEGRATION_URL' ) ) {
	define( 'PUM_AWEBER_INTEGRATION_URL', 'https://wppopupmaker.com' );
}

if ( ! defined( 'PUM_NEWSLETTER_URL' ) ) {
	define( 'PUM_NEWSLETTER_URL', 'https://wppopupmaker.com' );
}

if ( ! defined( 'PUM_NEWSLETTER_VERSION' ) ) {
	define( 'PUM_NEWSLETTER_VERSION', '1.0.0' );
}

if ( ! defined( 'PUM_MAILCHIMP_INTEGRATION_VER' ) ) {
	define( 'PUM_MAILCHIMP_INTEGRATION_VER', '1.0.0' );
}

if ( ! defined( 'POPUP_MAKER_DISABLE_LOGGING' ) ) {
	define( 'POPUP_MAKER_DISABLE_LOGGING', false );
}

if ( ! defined( 'POPUP_MAKER_UPGRADE_DEBUG_LOGGING' ) ) {
	define( 'POPUP_MAKER_UPGRADE_DEBUG_LOGGING', true );
}

if ( ! defined( 'POPUP_MAKER_LICENSE_KEY' ) ) {
	define( 'POPUP_MAKER_LICENSE_KEY', '' );
}
