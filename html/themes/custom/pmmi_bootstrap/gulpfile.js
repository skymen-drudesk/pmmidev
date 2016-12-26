'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var sassGlob = require('gulp-sass-glob');
var autoprefixer = require('autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var livereload = require('gulp-livereload');
var pixrem = require('gulp-pixrem');
var eslint = require('gulp-eslint');

// Define list of vendors.
var _vendors = [
  './libraries/compass-sass-mixins/lib',
  './libraries/components-font-awesome/scss'
];

gulp.task('sass:build', function () {
  gulp.src('./sass/style.scss')
    .pipe(sourcemaps.init())
    .pipe(sassGlob())
    .pipe(sass({
      outputStyle: 'expanded',
      sourcemap: true,
      includePaths: _vendors
    }).on('error', sass.logError))
    .pipe(pixrem())
    .pipe(postcss([
      autoprefixer({
        browsers: ['last 2 versions', '>5%']
      })
    ]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./css'))
    .pipe(livereload());
});

gulp.task('watch', function () {
  livereload.listen();
  gulp.watch('./sass/**/*.scss', ['sass:build']);
  gulp.watch('./js/**/*.js', ['eslint']);
  gulp.watch(['./templates/**/*.twig', './js/*.js'], function (files) {
    livereload.changed(files);
  });
});

gulp.task('eslint', function () {
  return gulp.src('./js/**/*.js')
    .pipe(eslint())
    .pipe(eslint.format())
    .pipe(eslint.failAfterError());
});

gulp.task('default', ['sass:build', 'watch', 'eslint']);
