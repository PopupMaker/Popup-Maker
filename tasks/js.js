/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

require('./webpack');

const gulp = require('gulp'),
	$fn = require('gulp-load-plugins')({camelize: true}),
	path = require('path'),
	fs = require('fs'),
	config = require('./config.json'),
	merge = require('merge-stream'),
	jsDistPath = path.join(config.root.dist, config.js.dist),
	jsDevPath = path.join(config.root.dev, config.js.dev);

function getFolders(dir) {
	return fs.readdirSync(dir)
		.filter(function (file) {
			return fs.statSync(path.join(dir, file)).isDirectory();
		});
}

function js_admin() {
	const adminJsDevPath = path.join(jsDevPath, 'admin');

	const folders = getFolders(adminJsDevPath),
		// process each sub-folder
		tasks = folders.map(function (folder) {
			return gulp.src(path.join(adminJsDevPath, folder, '/**/*.js'), {allowEmpty: true})
				.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
				.pipe($fn.order([
					"vendor/**/*.js",
					"plugins/**/*.js",
					'general.js'
				], {base: path.join(adminJsDevPath, folder)}))
				// concat into foldername.js
				.pipe($fn.concat('admin-' + folder + '.js'))
				.pipe(gulp.dest(jsDistPath))
				.pipe($fn.uglify())
				.pipe($fn.rename({extname: '.min.js'}))
				.pipe(gulp.dest(jsDistPath));
		}),
		// process all remaining files in adminJsDevPath root into main.js and main.min.js files
		root = gulp.src(path.join(adminJsDevPath, '/*.js'), {allowEmpty: true})
			.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
			.pipe($fn.rename({prefix: 'admin-'}))
			.pipe(gulp.dest(jsDistPath))
			.pipe($fn.uglify())
			.pipe($fn.rename({extname: '.min.js'}))
			.pipe(gulp.dest(jsDistPath));

	return merge(tasks, root);
}

js_admin.description = "Build admin Javascript assets.";

function js_site() {
	return gulp.src([path.join(jsDevPath,'site','/**/*.js')], {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.order([
			"plugins/compatibility.js",
			"plugins/pum.js",
			"plugins/**/*.js",
			'general.js'
		]))
		.pipe($fn.concat('site.js'))
		.pipe(gulp.dest(jsDistPath))
		.pipe($fn.uglify())
		.pipe($fn.rename({extname: '.min.js'}))
		.pipe(gulp.dest(jsDistPath));
}

js_site.description = "Build site Javascript assets.";

function js_other() {
	return gulp.src(path.join(jsDevPath, '*.js'), {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe(gulp.dest(jsDistPath))
		.pipe($fn.uglify())
		.pipe($fn.rename({extname: '.min.js'}))
		.pipe(gulp.dest(jsDistPath));
}

js_other.description = "Build 3rd party Javascript assets.";

gulp.task(js_admin);
gulp.task(js_site);
gulp.task(js_other);
gulp.task('js', gulp.parallel(['webpack', 'js_admin', 'js_site', 'js_other']));

let js = gulp.task('js');

js.description = "Build all Javascript assets.";
