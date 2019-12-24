/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

const gulp = require('gulp');

require('./package');
require('./watch');

gulp.task('default', gulp.series('prebuild', 'watch'));

let _default = gulp.task('default');

_default.description = "Prebuild all assets & start watchets.";
