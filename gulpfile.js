var gulp = require('gulp');
var gulpLoadPlugins = require('gulp-load-plugins');
var $ = gulpLoadPlugins();

gulp.task('sass', function() {
    return gulp.src('assets/sass/*.scss')
        .pipe($.sass())
        .pipe($.autoprefixer({
            browsers: ['> 10%', 'last 2 versions'],
            cascade: false
        }))
        .pipe($.csscomb())
        .pipe(gulp.dest('assets/css'));
});


gulp.task('css', ['sass'], function() {
    return gulp.src(['!assets/css/*.min.css', 'assets/css/*.css', ])
        // Minify the CSS
        .pipe($.csso())
        // Rename the file with the .min.css extension
        .pipe($.rename({ extname: '.min.css' }))
        // Save the file
        .pipe(gulp.dest('assets/css'))
        .pipe($.livereload());

});

gulp.task('css:minify', function() {
    return gulp.src(['!assets/css/*.min.css', 'assets/css/*.css', ])
        // Minify the CSS
        .pipe($.csso())

        // Rename the file with the .min.css extension
        .pipe($.rename({ extname: '.min.css' }))

        // Save the file
        .pipe(gulp.dest('assets/css'));
});

gulp.task('css:lint', function() {
    return gulp.src(['!assets/css/*.min.css', 'assets/css/*.css', ])
        // Lint the CSS
        .pipe($.csslint())
        .pipe($.csslint.reporter());
});

gulp.task('js', ['js:admin', 'js:site', 'js:other']);

gulp.task('js:admin', function() {
    return gulp.src(['assets/js/src/admin/plugins/**/*.js', 'assets/js/src/admin/general.js'])
        .pipe($.concat('popup-maker-admin.js'))
        .pipe(gulp.dest('assets/js'))
        .pipe($.uglify())
        .pipe($.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($.livereload());
});
gulp.task('js:site', function() {
    return gulp.src(['assets/js/src/site/plugins/**/*.js', 'assets/js/src/site/general.js'])
        .pipe($.order([
            "plugins/compatibility.js",
            "plugins/pum.js",
            "plugins/**/*.js",
            'general.js'
        ], { base: 'assets/js/src/site/' }))
        .pipe($.concat('popup-maker-site.js'))
        .pipe(gulp.dest('assets/js'))
        .pipe($.uglify())
        .pipe($.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($.livereload());
});
gulp.task('js:other', function() {
    return gulp.src('assets/js/src/*.js')
        .pipe(gulp.dest('assets/js'))
        .pipe($.uglify())
        .pipe($.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'))
        .pipe($.livereload());
});


gulp.task('js:complexity', function () {
    return gulp.src('assets/js/src/*.js')
        .pipe($.complexity());
});
gulp.task('js:duplicates', function () {
    return gulp.src('assets/js/src/*.js')
        .pipe($.jscpd({
            'min-lines': 10,
            verbose    : true
        }));
});
gulp.task('js:lint', function () {
    return gulp.src('assets/js/src/*.js')
        .pipe($.jshint())
        .pipe($.jshint.reporter('default'));
});

gulp.task('watch', ['build'], function() {
    $.livereload.listen();
    gulp.watch('assets/sass/**/*.scss', ['css']);
    gulp.watch('assets/js/src/admin/**/*.js', ['js:admin']);
    gulp.watch('assets/js/src/site/**/*.js', ['js:site']);
    gulp.watch(['assets/js/src/**/*.js', '!assets/js/src/site/**/*.js', '!assets/js/src/admin/**/*.js'], ['js:other']);
});

gulp.task('build', ['css', 'js']);

gulp.task('default', ['build']);