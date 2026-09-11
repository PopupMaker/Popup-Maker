<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Popup_Maker
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Point WP test suite to the Yoast PHPUnit Polyfills.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/yoast/phpunit-polyfills/' );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( dirname( __DIR__ ) ) ) . '/popup-maker.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Run the suite against the Bricks theme when it is installed.
 *
 * Bricks ships as a theme, so its APIs only exist when WordPress boots it. Set
 * PUM_TEST_THEME=bricks to exercise the Bricks provider against the real theme;
 * the Bricks tests skip themselves otherwise.
 *
 * The theme cannot simply be required by hand: Bricks resolves its own includes
 * from `get_template_directory()`, which the WordPress test suite points at its
 * fixture theme directory.
 *
 * @param string $template Current template directory name.
 *
 * @return string Filtered template directory name.
 */
function _pum_test_theme( $template ) {
	$theme = getenv( 'PUM_TEST_THEME' );

	if ( ! $theme ) {
		return $template;
	}

	$theme = basename( $theme );

	return defined( 'WP_CONTENT_DIR' ) && is_dir( WP_CONTENT_DIR . '/themes/' . $theme )
		? $theme
		: $template;
}
tests_add_filter( 'pre_option_template', '_pum_test_theme' );
tests_add_filter( 'pre_option_stylesheet', '_pum_test_theme' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
