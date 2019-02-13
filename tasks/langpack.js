/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

const gulp = require('gulp'),
	$fn = require('gulp-load-plugins')({camelize: true}),
	pkg = require('../package.json');

function langpack() {
	return gulp.src(['**/*.php', '!build/**/*.*'], {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.wpPot({
			domain: pkg.name,
			package: pkg.description,
			bugReport: 'https://wppopupmaker.com/support/',
			team: 'WP Popup Maker <support@wppopupmaker.com>'
		}))
		.pipe(gulp.dest('languages/' + pkg.name + '.pot'));
}

langpack.description = "Generate language files";

gulp.task(langpack);
