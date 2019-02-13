/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

require('./css');
require('./js');
require('./langpack');

const gulp = require('gulp'),
	$fn = require('gulp-load-plugins')({camelize: true}),
	pkg = require('../package.json'),
	config = require('./config'),
	pkgName = pkg.name + '_v' + pkg.version + '.zip';

function packageFiles() {
	return gulp.src('build/**', {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.zip(pkgName))
		.pipe(gulp.dest(config.root.release));
}

packageFiles.description = "Generates a release package with the current version from package.json";

function build() {

	return gulp
		.src([config.build.files,...config.build.ignore.map(value => '!' + value)], {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe(gulp.dest(config.root.build + '/' + pkg.name));
}

gulp.task('package', gulp.series('clean_package', packageFiles));
gulp.task('prebuild', gulp.series('clean_all', gulp.parallel('css', 'js', 'langpack')));
gulp.task('build', gulp.series('prebuild', build));

let	prebuildTask = gulp.task('prebuild'),
	buildTask = gulp.task('build');

prebuildTask.description = "Purge & rebuilds required assets.";
buildTask.description = "Copies a clean set of build files into the build folder.";

