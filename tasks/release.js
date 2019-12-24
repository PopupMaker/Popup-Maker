/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/
require('./package');
require('./clean');

const gulp = require('gulp');

gulp.task('release', gulp.series('build', 'package', 'clean_build'));

let release = gulp.task('release');

release.description = "Runs all build routines and generates a release.";
