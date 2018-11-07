var fs = require('fs'),
    path = require('path'),
    merge = require('merge-stream'),
    gulp = require('gulp'),
    $fn = require('gulp-load-plugins')({camelize: true}),
    plumberErrorHandler = {
        errorHandler: $fn.notify.onError({
            title: 'Gulp',
            message: 'Error: <%= error.message %>'
        })
    },
    cleanCSS = require('gulp-clean-css'),
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

function langpack() {
    return gulp.src(['**/*.php', '!build/**/*.*'], {allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.sort())
        .pipe($fn.wpPot({
            domain: pkg.name,
            package: pkg.description,
            bugReport: 'https://wppopupmaker.com/support/',
            team: 'WP Popup Maker <support@wppopupmaker.com>'
        }))
        .pipe(gulp.dest('languages/' + pkg.name + '.pot'));
}

langpack.description = "Generate language files";

function clean_langpack() {
    return gulp.src(['languages/*.pot'], {read: false, allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_langpack.description = "Purge language files";

function js_admin() {
    var folders = getFolders(admin_script_src_path),
        // process each sub-folder
        tasks = folders.map(function (folder) {
            return gulp.src(path.join(admin_script_src_path, folder, '/**/*.js'), {allowEmpty: true})
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
        root = gulp.src(path.join(admin_script_src_path, '/*.js'), {allowEmpty: true})
            .pipe($fn.plumber(plumberErrorHandler))
            .pipe($fn.jshint())
            .pipe($fn.jshint.reporter('default'))
            .pipe($fn.rename({prefix: 'admin-'}))
            .pipe(gulp.dest(script_output_path))
            .pipe($fn.uglify())
            .pipe($fn.rename({extname: '.min.js'}))
            .pipe(gulp.dest(script_output_path));

    return merge(tasks, root);
}

js_admin.description = "Build admin Javascript assets.";

function clean_js_admin() {
    return gulp.src(path.join(script_output_path, '/admin*.js'), {read: false, allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_js_admin.description = "Purge admin Javascript build files.";


function js_site() {
    return gulp.src([path.join(site_script_src_path, '/**/*.js')], {allowEmpty: true})
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
}

js_site.description = "Build site Javascript assets.";

function clean_js_site() {
    return gulp.src(path.join(script_output_path, '/site*.js'), {read: false, allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_js_site.description = "Purge site Javascript build files.";

function js_other() {
    return gulp.src(path.join(script_src_path, '*.js'), {allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe(gulp.dest(script_output_path))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest(script_output_path));
}

js_other.description = "Build 3rd party Javascript assets.";

function clean_js_other() {
    return gulp.src([path.join(script_output_path, '/*.js'), '!' + path.join(script_output_path, 'site*.js'), '!' + path.join(script_output_path, 'admin*.js')], {
        read: false,
        allowEmpty: true
    })
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_js_other.description = "Purge 3rd party Javascript build files.";

function css() {
    return gulp.src(path.join(sass_src_path, '/*.s+(a|c)ss'))
        .pipe($fn.plumber(plumberErrorHandler))
        // .pipe($fn.sassLint({
        //     ignore: {
        //         'no-color-literals': 0
        //     }
        // }))
        // .pipe($fn.sassLint.format())
        // .pipe($fn.sassLint.failOnError())
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
        .pipe(cleanCSS())
        .pipe(gulp.dest(css_output_path));
}

css.description = "Build css assets from sass.";

function clean_css() {
    return gulp.src([path.join(css_output_path, '/*.css'), path.join(css_output_path, '*.css.map')], {
        read: false,
        allowEmpty: true
    })
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_css.description = "Purge css build files.";

function clean_build() {
    return gulp.src('build/**', {read: false, allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean());
}

clean_build.description = "Purge compiled plugin build files & folder.";

function clean_package() {
    return gulp.src('release/' + pkg.name + '_v' + pkg.version + '.zip', {read: false, allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.clean({force: true}));
}

clean_package.description = "Purge packaged release zip file.";

function build() {
    return gulp.src(['./**/*.*', '!./build/**', '!./release/**', '!./node_modules/**', '!./gulpfile.js', '!./package.json', '!./assets/js/src/**'], {allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe(gulp.dest('build/' + pkg.name));
}

build.description = "Copies a clean set of build files into the build folder.";

function package() {
    return gulp.src('build/**', {allowEmpty: true})
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.zip(pkg.name + '_v' + pkg.version + '.zip'))
        .pipe(gulp.dest('release'));
}

package.description = "Generates a release package with the current version from package.json";

function sass_watcher() {
    var watcher = gulp.watch(path.join(sass_src_path, '/*.s+(a|c)ss'));
    watcher.on('all', gulp.parallel('css'));
}

sass_watcher.description = "Starts a scss/sass file watcher.";

function js_admin_watcher() {
    var watcher = gulp.watch(path.join(admin_script_src_path, '/**/*.js'));
    watcher.on('all', gulp.parallel('js_admin'));
}

js_admin_watcher.description = "Starts admin Javascript file watcher.";

function js_site_watcher() {
    var watcher = gulp.watch(path.join(site_script_src_path, '/**/*.js'));
    watcher.on('all', gulp.parallel('js_site'));
}

js_site_watcher.description = "Starts site Javascript file watcher.";

function js_other_watcher() {
    var watcher = gulp.watch([path.join(script_src_path, '*.js')]);
    watcher.on('all', gulp.parallel('js_other'));
}

js_other_watcher.description = "Starts 3rd party Javascript file watcher.";

function langpack_watcher() {
    var watcher = gulp.watch('**/*.php');
    watcher.on('all', gulp.parallel('langpack'));
}

langpack_watcher.description = "Starts langpack php file watcher.";

gulp.task(langpack);
gulp.task(js_admin);
gulp.task(js_site);
gulp.task(js_other);
gulp.task(css);
gulp.task(clean_js_site);
gulp.task(clean_js_admin);
gulp.task(clean_js_other);
gulp.task(clean_css);
gulp.task(clean_langpack);
gulp.task(clean_build);
gulp.task(clean_package);
gulp.task(sass_watcher);
gulp.task(js_admin_watcher);
gulp.task(js_site_watcher);
gulp.task(js_other_watcher);
gulp.task(langpack_watcher);
gulp.task('js', gulp.parallel(['js_admin', 'js_site', 'js_other']));
gulp.task('clean_js', gulp.parallel(['clean_js_site', 'clean_js_admin', 'clean_js_other']));
gulp.task('clean_all', gulp.parallel(['clean_js', 'clean_css', 'clean_langpack', 'clean_build', 'clean_package']));
gulp.task('prebuild', gulp.series('clean_all', gulp.parallel('css', 'js', 'langpack')));
gulp.task('build', gulp.series('prebuild', build));
gulp.task('package', gulp.series('clean_package', package));
gulp.task('release', gulp.series('build', 'package', 'clean_build'));
gulp.task('js_watcher', gulp.parallel(['js_admin_watcher', 'js_site_watcher', 'js_other_watcher']));
gulp.task('watch', gulp.parallel(['sass_watcher', 'js_watcher', 'langpack_watcher']));
gulp.task('default', gulp.series('prebuild', 'watch'));

var _default = gulp.task('default'),
    js = gulp.task('js'),
    clean_js = gulp.task('clean_js'),
    clean_all = gulp.task('clean_all'),
    prebuild = gulp.task('prebuild'),
    release = gulp.task('release'),
    js_watcher = gulp.task('js_watcher'),
    watch = gulp.task('watch');

_default.description = "Prebuild all assets & start watchets.";
js.description = "Build all Javascript assets.";
clean_js.description = "Purge all Javascript build assets.";
clean_all.description = "Clean all build assets.";
prebuild.description = "Purge & rebuilds required assets.";
release.description = "Runs all build routines and generates a release.";
js_watcher.description = "Starts all js file watchers.";
watch.description = "Start the file watchers.";
