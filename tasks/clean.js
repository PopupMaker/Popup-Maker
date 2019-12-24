/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

const gulp = require('gulp'),
	del = require('del'),
	$fn = require('gulp-load-plugins')({camelize: true}),
	path = require('path'),
	pkg = require('../package.json'),
	config = require('./config.json'),
	jsDistPath = path.join(config.root.dist, config.js.dist),
	cssDistPath = path.join(config.root.dist, config.css.dist),
	pkgName = pkg.name + '_v' + pkg.version + '.zip';

function clean_langpack() {
	return del(['languages/*.pot']);
}

clean_langpack.description = "Purge language files";

function clean_js_site() {
	return del([path.join(jsDistPath, '/site*.js'), path.join(jsDistPath, '/pum-site*.js')]);
}

clean_js_site.description = "Purge site Javascript build files.";

function clean_js_admin() {
	return del([path.join(jsDistPath, '/admin*.js'), path.join(jsDistPath, '/pum-admin*.js')]);
}

clean_js_admin.description = "Purge admin Javascript build files.";

function clean_js_other() {
	return del([path.join(jsDistPath, '/*.js'), path.join(jsDistPath, 'vendor/*.js'), '!' + path.join(jsDistPath, 'site*.js'), '!' + path.join(jsDistPath, 'pum-site*.js'), '!' + path.join(jsDistPath, 'admin*.js'), '!' + path.join(jsDistPath, 'pum-admin*.js')]);
}

clean_js_other.description = "Purge 3rd party Javascript build files.";

function clean_css() {
	return del([path.join(cssDistPath, '/*.css'), path.join(cssDistPath, '*.css.map')]);
}

clean_css.description = "Purge css build files.";

function clean_build() {
	return del(config.root.build);
}

clean_build.description = "Purge compiled plugin build files & folder.";

function clean_package() {
	return del(config.root.release + '/' + pkgName);
}

clean_package.description = "Purge packaged release zip file.";

gulp.task(clean_langpack);
gulp.task(clean_js_site);
gulp.task(clean_js_admin);
gulp.task(clean_css);
gulp.task(clean_build);
gulp.task(clean_package);
gulp.task('clean_js', gulp.parallel(['clean_js_site', 'clean_js_admin']));
gulp.task('clean_all', gulp.parallel(['clean_js', 'clean_css', 'clean_langpack', 'clean_build', 'clean_package']));

let clean_js = gulp.task('clean_js'),
	clean_all = gulp.task('clean_all');

clean_js.description = "Purge all Javascript build assets.";
clean_all.description = "Clean all build assets.";
