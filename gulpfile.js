var fs = require('fs'),
    path = require('path'),
    merge = require('merge-stream'),
    gulp = require('gulp'),
    runSequence = require('run-sequence').use(gulp),
    $fn = require('gulp-load-plugins')({camelize: true}),
    plumberErrorHandler = {
        errorHandler: $fn.notify.onError({
            title: 'Gulp',
            message: 'Error: <%= error.message %>'
        })
    },
    pkg = require('./package.json'),

    // customize these
    script_src_path = 'assets/js/src',
    site_script_src_path = path.join(script_src_path, 'site'),
    admin_script_src_path = path.join(script_src_path, 'admin'),
    sass_src_path = 'assets/sass',
    script_output_path = 'assets/js',
    css_output_path = 'assets/css';

function getFolders(dir) {
    return fs.readdirSync(dir)
        .filter(function (file) {
            return fs.statSync(path.join(dir, file)).isDirectory();
        });
}

//region JavaScript
gulp.task('js:admin', function () {
    var folders = getFolders(admin_script_src_path),
        // process each sub-folder
        tasks = folders.map(function (folder) {
            return gulp.src(path.join(admin_script_src_path, folder, '/**/*.js'))
                .pipe($fn.plumber(plumberErrorHandler))
                .pipe($fn.jshint())
                .pipe($fn.jshint.reporter('default'))
                .pipe($fn.order([
                    "vendor/**/*.js",
                    "plugins/**/*.js",
                    'general.js'
                ], {base: path.join(admin_script_src_path, folder)}))
                // concat into foldername.js
                .pipe($fn.concat('admin-' + folder + '.js'))
                .pipe(gulp.dest(script_output_path))
                .pipe($fn.uglify())
                .pipe($fn.rename({extname: '.min.js'}))
                .pipe(gulp.dest(script_output_path));
        }),
        // process all remaining files in admin_script_src_path root into main.js and main.min.js files
        root = gulp.src(path.join(admin_script_src_path, '/*.js'))
            .pipe($fn.plumber(plumberErrorHandler))
            .pipe($fn.jshint())
            .pipe($fn.jshint.reporter('default'))
            .pipe($fn.rename({prefix: 'admin-'}))
            .pipe(gulp.dest(script_output_path))
            .pipe($fn.uglify())
            .pipe($fn.rename({extname: '.min.js'}))
            .pipe(gulp.dest(script_output_path));

    return merge(tasks, root);
});

gulp.task('js:site', function () {
    return gulp.src([path.join(site_script_src_path, '/**/*.js')])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe($fn.order([
            "plugins/compatibility.js",
            "plugins/pum.js",
            "plugins/**/*.js",
            'general.js'
        ], {base: site_script_src_path}))
        .pipe($fn.concat('site.js'))
        .pipe(gulp.dest(script_output_path))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest(script_output_path));
});

gulp.task('js:other', function () {
    return gulp.src(path.join(script_src_path, '*.js'))
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe(gulp.dest(script_output_path))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest(script_output_path));
});

gulp.task('js', ['js:admin', 'js:site', 'js:other']);
//endregion JavaScript

//region Language Files
gulp.task('langpack', function () {
    return gulp.src(['**/*.php', '!build/**/*.*'])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.sort())
        .pipe($fn.wpPot({
            domain: pkg.name,
            bugReport: 'https://wppopupmaker.com/support',
            team: 'WP Popup Maker <support@wppopupmaker.com>'
        }))
        .pipe(gulp.dest('languages'));
});
//endregion Language Files

//region SASS & CSS
gulp.task('css', function () {
    return gulp.src(path.join(sass_src_path, '/*.scss'))
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.sourcemaps.init())
        .pipe($fn.sass({
            errLogToConsole: true,
            outputStyle: 'expanded',
            precision: 10
        }))
        .pipe($fn.sourcemaps.write())
        .pipe($fn.sourcemaps.init({
            loadMaps: true
        }))
        .pipe($fn.autoprefixer('last 2 version', '> 1%', 'safari 5', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
        .pipe($fn.sourcemaps.write('.'))
        .pipe($fn.plumber.stop())
        .pipe(gulp.dest(css_output_path))
        .pipe($fn.filter('**/*.css')) // Filtering stream to only css files
        .pipe($fn.combineMq()) // Combines Media Queries
        .pipe($fn.rename({suffix: '.min'}))
        .pipe($fn.csso({
            //sourceMap: true,
        }))
        .pipe(gulp.dest(css_output_path));
});
//endregion SASS & CSS

//region Cleaners
gulp.task('clean-js:site', function () {
    return gulp.src(path.join(script_output_path, '/site*.js'), {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
});
gulp.task('clean-js:admin', function () {
    return gulp.src(path.join(script_output_path, '/admin*.js'), {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
});
gulp.task('clean-js:other', function () {
    return gulp.src([path.join(script_output_path, '/*.js'), '!'+path.join(script_output_path, 'site*.js'), '!'+path.join(script_output_path, 'admin*.js')], {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
});
gulp.task('clean-css', function () {
    return gulp.src([path.join(css_output_path, '/*.css'), path.join(css_output_path, '*.css.map')], {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
});
gulp.task('clean-build', function () {
    return gulp.src('build/*', {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
});
gulp.task('clean-package', function () {
    return gulp.src('release/' + pkg.name + '_v' + pkg.version + '.zip', {read: false})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean({force: true}));
});

// Cleaning Routines
gulp.task('clean-js', function (done) {
    runSequence(
        ['clean-js:site', 'clean-js:admin', 'clean-js:other'],
        done
    );
});
gulp.task('clean-all', function (done) {
    runSequence(
        ['clean-js', 'clean-css'],
        ['clean-build', 'clean-package'],
        done
    );
});
//endregion Cleaners

//region Watch & Build
gulp.task('watch', function () {
    $fn.livereload.listen();
    gulp.watch(path.join(sass_src_path, '/**/*.scss'), ['css']);
    gulp.watch(path.join(admin_script_src_path, '/**/*.js'), ['js:admin']);
    gulp.watch(path.join(site_script_src_path, '/**/*.js'), ['js:site']);
    gulp.watch([path.join(script_src_path, '*.js')], ['js:other']);
    gulp.watch('**/*.php', ['langpack']);
});

// Cleans & Rebuilds Assets Prior to Builds
gulp.task('prebuild', function (done) {
    runSequence(
        'clean-all',
        ['css', 'js', 'langpack'],
        done
    );
});

// Copies a clean set of build files into the build folder
gulp.task('build', ['prebuild'], function () {
    return gulp.src(['./**/*.*', '!./build/**', '!./release/**', '!./node_modules/**', '!./gulpfile.js', '!./package.json', '!./assets/js/src/**'])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe(gulp.dest('build/' + pkg.name));
});

// Generates a release package with the current version from package.json
gulp.task('package', ['clean-package'], function () {
    return gulp.src('build/**/*.*')
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.zip(pkg.name + '_v' + pkg.version + '.zip'))
        .pipe(gulp.dest('release'));
});

// Runs all build routines and generates a release.
gulp.task('release', function (done) {
    runSequence(
        'build',
        'package',
        done
    );
});

// Runs a releaes and cleans up afterwards.
gulp.task('release:clean', ['release'], function (done) {
    runSequence(
        'clean-build',
        done
    );
});
//endregion Watch & Build

gulp.task('default', function (done) {
    runSequence(
        'prebuild',
        'watch'
    );
});

gulp.task('submodules', function () {
    $fn.git.updateSubmodule({args: '--init --recursive'});
});
