/**************************************
 * Copyright (c) 2020, Popup Maker
 *************************************/
require("./package");
require("./clean");

const gulp = require("gulp");

gulp.task("release", gulp.series("build", "package", "clean_build"));

let release = gulp.task("release");

release.description = "Runs all build routines and generates a release.";
