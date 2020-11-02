/*********************************************
 * Copyright (c) 2020, Popup Maker
 ********************************************/

const gulp = require("gulp"),
	$fn = require("gulp-load-plugins")({ camelize: true }),
	pkg = require("../package.json");

function langpack() {
	return gulp.src(['**/*.php', '!build/**/*.*', '!node_modules/**/*.*', '!vendor/**/*.*'], {allowEmpty: true})
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.wpPot({
			domain: pkg.name,
			package: pkg.description,
			bugReport: 'https://wppopupmaker.com/support/',
			team: 'Popup Maker <support@wppopupmaker.com>'
		}))
		.pipe(gulp.dest('languages/' + pkg.name + '.pot'));
}

langpack.description = "Generate language files";

gulp.task(langpack);
