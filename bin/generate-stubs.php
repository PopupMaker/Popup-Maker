<?php
/**
 * Generate stubs for a library.
 *
 * @package   PopupMaker
 */

// You'll need the Composer Autoloader.
// require_once __DIR__ . '/vendor/autoload.php';


/**
 * Class Autoloader
 *
 * @param string $class_name The class name to load.
 */
function pum_autoloader( $class_name ) {

	if ( strncmp( 'PUM_Newsletter_', $class_name, strlen( 'PUM_Newsletter_' ) ) === 0 && class_exists( 'PUM_MCI' ) && ! empty( PUM_MCI::$VER ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) ) {
		return;
	}

	$pum_autoloaders = [
		[
			'prefix'  => 'PUM_',
			'dir'     => __DIR__ . '../classes/',
			'search'  => '_',
			'replace' => '/',

		],
	];

	foreach ( $pum_autoloaders as $autoloader ) {
		// project-specific namespace prefix
		$prefix = $autoloader['prefix'];

		// does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			// no, move to the next registered autoloader
			continue;
		}

		// get the relative class name
		$relative_class = substr( $class_name, $len );

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $autoloader['dir'] . str_replace( $autoloader['search'], $autoloader['replace'], $relative_class ) . '.php';

		// if the file exists, require it
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

if ( ! function_exists( 'spl_autoload_register' ) ) {
	include 'includes/compat.php';
}

spl_autoload_register( 'pum_autoloader' ); // Register autoloader


// You may alias the classnames for convenience.
use StubsGenerator\{StubsGenerator, Finder};

$generator = new StubsGenerator( StubsGenerator::ALL );

return Finder::create()
	->in( dirname( __DIR__ ) . '/' )
	// ->notPath( 'classes/Base' )
	// ->notPath( 'classes/Controllers' )
	// ->notPath( 'classes/Installers' )
	// ->notPath( 'classes/Interfaces' )
	// ->notPath( 'classes/Models' )
	// ->notPath( 'classes/Plugin' )
	// ->notPath( 'classes/QueryMonitor' )
	// ->notPath( 'classes/RestAPI' )
	// ->notPath( 'classes/RuleEngine' )
	// ->notPath( 'classes/Services' )
	// ->notPath( 'classes/Upgrades' )
	->notPath( 'assets' )
	->notPath( 'bin' )
	->notPath( 'dist' )
	->notPath( 'vendor' )
	->notPath( 'vender-prefixed' )
	->name( '*.php' );
