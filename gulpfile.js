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
gulp.task('css', function() {
    return gulp.src(['!assets/css/*.min.css', 'assets/css/*.css', ])
        // Lint the CSS
        .pipe($.csslint())
        .pipe($.csslint.reporter())

        // Minify the CSS
        .pipe($.csso())

        // Rename the file with the .min.css extension
        .pipe($.rename({ extname: '.min.css' }))

        // Save the file
        .pipe(gulp.dest('assets/css'));
});
gulp.task('js', function() {
    return gulp.src('assets/js/src/*.js')
        .pipe(gulp.dest('assets/js'))
        .pipe($.uglify())
        .pipe($.rename({extname: '.min.js'}))
        .pipe(gulp.dest('assets/js'));
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

gulp.task('watch', function() {
    gulp.watch('assets/sass/*.scss', ['sass']);
    gulp.watch('assets/css/*.css', ['css']);
    gulp.watch('assets/js/src/*.js', ['js']);
});
