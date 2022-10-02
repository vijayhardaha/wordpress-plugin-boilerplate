/**
 * Define required packages.
 */
import gulp from 'gulp';
import autoprefixer from 'autoprefixer';
import cleancss from 'gulp-clean-css';
import clone from 'gulp-clone';
import concat from 'gulp-concat';
import { deleteSync } from 'del';
import duplicates from 'postcss-discard-duplicates';
import flatten from 'gulp-flatten';
import gcmq from 'gulp-group-css-media-queries';
import imagemin from 'gulp-imagemin';
import merge from 'merge-stream';
import plumber from 'gulp-plumber';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass( dartSass );
import terser from 'gulp-terser';

/**
 * Paths to base asset directories. With trailing slashes.
 * - `paths.src` - Path to the source files. Default: `src/`
 * - `paths.dist` - Path to the build directory. Default: `assets/`
 */
const paths = {
	src: 'src/',
	dist: 'assets/',
};

/**
 * Build CSS.
 *
 * @param {Function} done
 */
const buildCSS = ( done ) => {
	const entries = {
		admin: [ 'src/scss/admin.scss' ],
		frontend: [ 'src/scss/frontend.scss' ],
	};

	for ( const [ name, path ] of Object.entries( entries ) ) {
		const baseSource = gulp.src( path )
			.pipe( plumber() )
			.pipe( sass( { outputStyle: 'expanded' } ) )
			.pipe( gcmq() )
			.pipe( concat( 'merged.css' ) )
			.pipe( postcss( [ duplicates(), autoprefixer() ] ) )
			.pipe( cleancss( { format: 'beautify' } ) )
			.pipe( rename( { basename: name } ) );

		const minified = baseSource
			.pipe( clone() )
			.pipe( cleancss() )
			.pipe( rename( { suffix: '.min' } ) );

		merge( baseSource, minified ).pipe( gulp.dest( paths.dist + 'css' ) );
	}

	done();
};

/**
 * Build JS.
 *
 * @param {Function} done
 */
const buildJS = ( done ) => {
	const entries = {
		admin: [ 'src/js/admin.js' ],
		frontend: [ 'src/js/frontend.js' ],
	};

	for ( const [ name, path ] of Object.entries( entries ) ) {
		const baseSource = gulp.src( path )
			.pipe( plumber() )
			.pipe( concat( 'merged.js' ) )
			.pipe( rename( { basename: name } ) );

		const minified = baseSource
			.pipe( clone() )
			.pipe( terser() )
			.pipe( rename( { suffix: '.min' } ) );

		merge( baseSource, minified ).pipe( gulp.dest( paths.dist + 'js' ) );
	}

	done();
};

/**
 * Build Fonts
 *
 * @param {Function} done
 */
const buildFonts = ( done ) => {
	gulp.src( paths.src + 'fonts/**/*' )
		.pipe( flatten() )
		.pipe( gulp.dest( paths.dist + 'fonts' ) );

	done();
};

/**
 * Build Images
 *
 * @param {Function} done
 */
const buildImages = ( done ) => {
	gulp.src( paths.src + 'images/**/*' )
		.pipe(
			imagemin( {
				progressive: true,
				interlaced: true,
				svgoPlugins: [ { removeUnknownsAndDefaults: false } ],
			} )
		)
		.pipe( gulp.dest( paths.dist + 'images' ) );

	done();
};

/**
 * Clean the build directory.
 *
 * @param {Function} done
 */
const cleanAssets = ( done ) => {
	deleteSync( paths.dist );

	done();
};

/**
 * Runs parallel tasks for .js compiling with webpack and .scss compiling
 *
 * @param {Function} done
 */
const watchAssets = ( done ) => {
	gulp.watch( 'src/scss/**/*.scss', gulp.series( buildCSS ) );
	gulp.watch( 'src/js/**/*.js', gulp.series( buildJS ) );
	gulp.watch( 'src/fonts/**/*', gulp.series( buildFonts ) );
	gulp.watch( 'src/images/**/*', gulp.series( buildImages ) );

	done();
};

const css = gulp.series( buildCSS, );
const js = gulp.series( buildJS );
const build = gulp.series( cleanAssets, buildCSS, buildJS, buildFonts, buildImages );
const watcher = gulp.series( watchAssets );

export { css, js, build };
export { watcher as watch };
