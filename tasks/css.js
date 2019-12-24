/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

const gulp = require('gulp'),
	$fn = require('gulp-load-plugins')({camelize: true}),
	path = require('path'),
	config = require('./config.json'),
	cssPath = path.join(config.root.dist, config.css.dist);


function css() {
	return gulp
		.src(path.join(config.root.dev, config.css.dev, '/*.s+(a|c)ss'))
		.pipe($fn.rename({prefix: 'pum-'}))
		.pipe($fn.sourcemaps.init())
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.sass({
			includePaths: [config.modules.node, config.modules.bower],
			sourceMap: true,
			errLogToConsole: true,
			outputStyle: 'expanded',
			precision: 10
		}))
		.pipe($fn.autoprefixer({
			browsers: ['last 3 version'],
		}))
		.pipe($fn.sourcemaps.write('.'))
		.pipe(gulp.dest(cssPath))
		.pipe($fn.filter('**/*.css'))
		.pipe($fn.rename({suffix: '.min'}))
		.pipe($fn.cleanCss({
			level: {
				1: {
					specialComments: false
				},
				2: {
					all: true
				}
			}
		}))
		.pipe(gulp.dest(cssPath));
}

css.description = "Build css assets from sass.";

function cssrtl() {
	return gulp
		.src(path.join(config.root.dev, config.css.dev, '/*.s+(a|c)ss'))
		.pipe($fn.rename({prefix: 'pum-'}))
		.pipe($fn.sourcemaps.init())
		.pipe($fn.plumber({errorHandler: $fn.notify.onError('Error: <%= error.message %>')}))
		.pipe($fn.sass({
			includePaths: [config.modules.node, config.modules.bower],
			sourceMap: true,
			errLogToConsole: true,
			outputStyle: 'expanded',
			precision: 10
		}))
		.pipe($fn.rtlcss())
		.pipe($fn.rename({suffix: '-rtl'}))
		.pipe($fn.autoprefixer({
			browsers: ['last 3 version'],
		}))
		.pipe($fn.sourcemaps.write('.'))
		.pipe(gulp.dest(cssPath))
		.pipe($fn.filter('**/*.css'))
		.pipe($fn.rename({suffix: '.min'}))
		.pipe($fn.cleanCss({
			level: {
				1: {
					specialComments: false
				},
				2: {
					all: true
				}
			}
		}))
		.pipe(gulp.dest(cssPath));
}

cssrtl.description = "Build css assets from sass in RTL.";

gulp.task(css);
gulp.task(cssrtl);
