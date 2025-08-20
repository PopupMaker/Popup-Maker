<?php
/**
 * Generate stubs for a library.
 *
 * @package   PopupMaker
 */

// You may alias the classnames for convenience.
use StubsGenerator\{StubsGenerator, Finder};

$generator = new StubsGenerator( StubsGenerator::ALL );

return Finder::create()
	->in( dirname( __DIR__ ) . '/' )
	->notPath( '.wordpress-org' )
	->notPath( 'assets' )
	->notPath( 'bin' )
	->notPath( 'build' )
	->notPath( 'dist' )
	->notPath( 'node_modules' )
	->notPath( 'languages' )
	->notPath( 'tests' )
	->notPath( 'vendor' )
	->notPath( 'vendor-prefixed' )
	->notPath( '.phpstorm.meta.php' )
	->name( '*.php' );
