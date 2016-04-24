var gulp = require('gulp'),
    $fn = require('gulp-load-plugins')({ camelize: true }),
    plumberErrorHandler = {
        errorHandler: $fn.notify.onError({
            title: 'Gulp',
            message: 'Error: <%= error.message %>'
        })
    },
    pkg = require('./package.json');

//region JavaScript
gulp.task('js:admin', function() {
    return gulp.src(['assets/js/src/admin/plugins/**/*.js', 'assets/js/src/admin/general.js'])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe($fn.concat('admin.js'))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.notify({
            message: 'Admin JS task complete',
            onLast: true
        }))
        .pipe($fn.livereload());
});

gulp.task('js:site', function() {
    return gulp.src(['assets/js/src/site/plugins/**/*.js', 'assets/js/src/site/general.js'])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe($fn.order([
            "plugins/compatibility.js",
            "plugins/pum.js",
            "plugins/**/*.js",
            'general.js'
        ], { base: 'assets/js/src/site/' }))
        .pipe($fn.concat('site.js'))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.notify({
            message: 'Site JS task complete',
            onLast: true
        }))
        .pipe($fn.livereload());
});

gulp.task('js:other', function() {
    return gulp.src('assets/js/src/*.js')
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.jshint())
        .pipe($fn.jshint.reporter('default'))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.uglify())
        .pipe($fn.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($fn.notify({
            message: 'Other JS task complete',
            onLast: true
        }))
        .pipe($fn.livereload());
});

gulp.task('js', ['js:admin', 'js:site', 'js:other']);
//endregion JavaScript

//region Language Files
gulp.task('langpack', function () {
    return gulp.src(['**/*.php', '!dist/**/*.*'])
        .pipe($fn.plumber(plumberErrorHandler))
        .pipe($fn.sort())
        .pipe($fn.wpPot( {
            domain: pkg.name,
            bugReport: 'https://wppopupmaker.com/support',
            team: 'WP Popup Maker <support@wppopupmaker.com>'
        } ))

        .pipe(gulp.dest('languages'))
        .pipe($fn.notify({
            message: 'Language files task complete',
            onLast: true
        }));
});
//endregion Language Files

//region SASS & CSS
gulp.task('css', function() {
    return gulp.src('assets/sass/*.scss')
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
        .pipe(gulp.dest('assets/css'))
        .pipe($fn.filter('**/*.css')) // Filtering stream to only css files
        .pipe($fn.combineMq()) // Combines Media Queries
        .pipe($fn.livereload())
        .pipe($fn.rename({ suffix: '.min' }))
        .pipe($fn.csso({
            //sourceMap: true,
        }))
        .pipe(gulp.dest('assets/css'))
        .pipe($fn.livereload())
        .pipe($fn.notify({
            message: 'Styles task complete',
            onLast: true
        }))
        .pipe(gulp.dest('assets/css'));
});
//endregion SASS & CSS

//region Watch & Build
gulp.task('watch', function () {
    $fn.livereload.listen();
    gulp.watch('assets/sass/**/*.scss', ['css']);
    gulp.watch('assets/js/src/admin/**/*.js', ['js:admin']);
    gulp.watch('assets/js/src/site/**/*.js', ['js:site']);
    gulp.watch(['assets/js/src/**/*.js', '!assets/js/src/site/**/*.js', '!assets/js/src/admin/**/*.js'], ['js:other']);
    gulp.watch('**/*.php', ['langpack']);
});

gulp.task('prebuild', ['css', 'js', 'langpack'], function () {
    return gulp.src(['./**/*.*', '!./dist/**', '!./build/**', '!./node_modules/**', '!./gulpfile.js', '!./package.json', '!./assets/js/src/**'])
        .pipe(gulp.dest('dist/'+pkg.version+'/'+pkg.name));
});

gulp.task('build', ['prebuild'], function () {
    return gulp.src('dist/'+pkg.version+'/**/*.*')
        .pipe($fn.zip(pkg.name+'_v'+pkg.version+'.zip'))
        .pipe(gulp.dest('build'));
});

gulp.task('default', ['langpack', 'css', 'js', 'watch']);
//endregion Watch & Build