'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var sassGlob = require('gulp-sass-glob');
var autoprefixer = require('autoprefixer');

// Define list of vendors.
var _vendors = [
  './libraries/compass-sass-mixins/lib'
];

gulp.task('sass:build', function () {
  gulp.src('./sass/style.scss')
    .pipe(sassGlob())
    .pipe(sass({
      outputStyle: 'expanded',
      includePaths: _vendors
    }).on('error', sass.logError))
    .pipe(postcss([
      autoprefixer({
        browsers: ['last 2 versions', '>5%']
      })
    ]))
    .pipe(gulp.dest('./css'));
});

gulp.task('sass:watch', function () {
  gulp.watch('./sass/**/*.scss', ['sass:build']);
});

gulp.task('default', ['sass:build', 'sass:watch']);
