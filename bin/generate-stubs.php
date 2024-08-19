<?php
/**
 * Generate stubs for a library.
 *
 * @package   PopupMaker
 */

// You'll need the Composer Autoloader.
require_once __DIR__ . '/vendor/autoload.php';

// You may alias the classnames for convenience.
use StubsGenerator\{StubsGenerator, Finder};

$generator = new StubsGenerator( StubsGenerator::ALL );

return Finder::create()
	->in( dirname( __DIR__ ) . '/build' )
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
	->notPath( 'dist' )
	->notPath( 'vendor' )
	->notPath( 'vender-prefixed' )
	->name( '*.php' );
