/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

require( './css' );
require( './js' );
require( './langpack' );

const gulp = require( 'gulp' ),
	$fn = require( 'gulp-load-plugins' )( { camelize: true } ),
	path = require( 'path' ),
	config = require( './config.json' ),
	srcPath = path.join( config.root.dev, config.js.dev );

function sass_watcher() {
	$fn.saneWatch( path.join( config.root.dev, config.css.dev , '/**/*.s+(a|c)ss' ), { debounce: 300 }, gulp.parallel( 'css', 'cssrtl' ) );
}

sass_watcher.description = 'Starts a scss/sass file watcher.';

function js_admin_watcher() {
	$fn.saneWatch( path.join( srcPath, 'admin', '/**/*.js' ), { debounce: 300 }, gulp.parallel( 'js_admin' ) );
}

js_admin_watcher.description = 'Starts admin Javascript file watcher.';

function js_site_watcher() {
	$fn.saneWatch( path.join( srcPath, 'site', '/**/*.js' ), { debounce: 300 }, gulp.parallel( 'js_site' ) );
}
js_site_watcher.description = 'Starts site Javascript file watcher.';

function webpack_block_editor_watcher() {
	$fn.saneWatch( path.join( config.root.dir, 'src/block-editor', '/**/*.js' ), { debounce: 300 }, gulp.parallel( 'webpack:blockEditor' ) );
	$fn.saneWatch( path.join( config.root.dir, 'src/block-editor', '/**/*.s+(a|c)ss' ), { debounce: 300 }, gulp.parallel( 'webpack:blockEditor' ) );
}

function langpack_watcher() {
	$fn.saneWatch( '**/*.php', { debounce: 300 }, gulp.parallel( 'langpack' ) );
}

langpack_watcher.description = 'Starts langpack php file watcher.';

gulp.task( sass_watcher );
gulp.task( js_admin_watcher );
gulp.task( js_site_watcher );
gulp.task( webpack_block_editor_watcher );
gulp.task( langpack_watcher );
gulp.task( 'js_watcher', gulp.parallel( [ 'js_admin_watcher', 'js_site_watcher' ] ) );
gulp.task( 'watch', gulp.parallel( [ 'sass_watcher', 'js_watcher', 'langpack_watcher' ] ) );

const js_watcher = gulp.task( 'js_watcher' ),
	watch = gulp.task( 'watch' );

js_watcher.description = 'Starts all js file watchers.';
watch.description = 'Start the file watchers.';
