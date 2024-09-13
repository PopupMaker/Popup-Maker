<?php
/**
 * Popup Maker Legacy Bootstrap.
 *
 * Global scope, no namespacing.
 *
 * Deprecated in favor of composer autoloader.
 *
 * Here for the following backwards compatibility reasons:
 * - Some popup maker extensions may still use this.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get deprecated autoloaders.
 *
 * Staticly caches the autoloaders to reduce `apply_filters` & `wp_parse_args` calls.
 *
 * @return array
 *
 * @since 1.20.0
 * @deprecated 1.20.0 - Use composer autoloader instead.
 */
function pum_get_deprecated_autoloaders() {
	static $deprecated_autoloaders;

	if ( ! isset( $deprecated_autoloaders ) || ! did_action( 'plugins_loaded' ) ) {
		$deprecated_autoloaders = apply_filters(
			'pum_autoloaders',
			[
				// Popup Maker Core prior to 1.20.0. To Be removed when all classes are namespaced.
				[
					'prefix' => 'PUM_',
					'dir'    => __DIR__ . '/classes/',
				],
			]
		);

		// Precache this parsing to reduce the number of calls to wp_parse_args.
		foreach ( $deprecated_autoloaders as $key => $autoloader ) {
			$deprecated_autoloaders[ $key ] = wp_parse_args(
				$autoloader,
				[
					'prefix'  => 'PUM_',
					'dir'     => __DIR__ . '/classes/',
					'search'  => '_',
					'replace' => '/',
				]
			);
		}
	}

	return $deprecated_autoloaders;
}

/**
 * Class Autoloader
 *
 * @param string $class_name The class name to load.
 *
 * @deprecated 1.20.0 - Use composer autoloader instead.
 */
function pum_autoloader( $class_name ) {
	$pum_autoloaders = pum_get_deprecated_autoloaders();

	if ( count( $pum_autoloaders ) === 0 ) {
		// Unregister this autoloader if there are no deprecated autoloaders .
		spl_autoload_unregister( 'pum_autoloader' );
	}

	// Deprecated newsletter autoloader, remove this in future.
	if ( strncmp( 'PUM_Newsletter_', $class_name, strlen( 'PUM_Newsletter_' ) ) === 0 && class_exists( 'PUM_MCI' ) && ! empty( PUM_MCI::$VER ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) ) {
		return;
	}

	foreach ( $pum_autoloaders as $autoloader ) {
		// Project-specific namespace prefix.
		$prefix = $autoloader['prefix'];

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			// No, move to the next registered autoloader.
			continue;
		}

		// Get the relative class name.
		$relative_class = substr( $class_name, $len );

		// Build the file path.
		$file = $autoloader['dir'] . str_replace( $autoloader['search'], $autoloader['replace'], $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}
}

spl_autoload_register( 'pum_autoloader' ); // Register autoloader

/**
 * Triggers the initialization of the Popup Maker plugin and extensions.
 *
 * @since      1.0.0
 * @deprecated 1.7.0
 */
function popmake_initialize() {
	// Disable Unlimited Themes extension if active.
	remove_action( 'popmake_initialize', 'popmake_ut_initialize' );

	// Initialize old PUM extensions.
	do_action( 'pum_initialize' );
	do_action( 'popmake_initialize' );
}

/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $popmake = PopMake(); ?>
 *
 * @return object The one true Popup_Maker Instance
 *
 * @since      1.0.0
 * @deprecated 1.7.0
 */
function PopMake() {
	return Popup_Maker::instance();
}

/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return Popup_Maker
 *
 * @since      1.8.0
 */
function pum() {
	return Popup_Maker::instance();
}
