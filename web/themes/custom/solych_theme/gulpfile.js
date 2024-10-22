const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleancss = require('gulp-clean-css');

// Define paths
const paths = {
  scss: {
    src: './scss/styles.scss',
    dest: './css/'
  }
};

// Compile SCSS into CSS
function styles() {
  return gulp.src(paths.scss.src)
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(cleancss({ level: { 1: { specialComments: 0 } } }))
    .pipe(gulp.dest(paths.scss.dest));
}

// Watch files
function watchFiles() {
  gulp.watch('./scss/**/*.scss', styles);
}

// Define complex tasks
const watch = gulp.series(styles, watchFiles);

// Export tasks
exports.styles = styles;
exports.watch = watch;
exports.default = watch;
