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
import terser from 'gulp-terser';

const sass = gulpSass( dartSass );

/**
 * Paths to base asset directories. With trailing slashes.
 * - `paths.src` - Path to the source files. Default: `src/`
 * - `paths.dest` - Path to the build directory. Default: `assets/`
 */
const paths = {
	src: 'src/',
	dest: 'assets/',
	scss: {
		src: {
			admin: [ 'src/scss/admin.scss' ],
			frontend: [ 'src/scss/frontend.scss' ],
		},
		dest: 'assets/css',
	},
	js: {
		src: {
			admin: [ 'src/js/admin.js' ],
			frontend: [ 'src/js/frontend.js' ],
		},
		dest: 'assets/js',
	},
	images: {
		src: 'src/images/**/*',
		dest: 'assets/images',
	},
	fonts: {
		src: 'src/fonts/**/*',
		dest: 'assets/fonts',
	},
};

/**
 * Build CSS.
 *
 * @param {Function} done
 */
const buildCSS = ( done ) => {
	for ( const [ name, path ] of Object.entries( paths.scss.src ) ) {
		const baseSource = gulp
			.src( path )
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

		merge( baseSource, minified ).pipe( gulp.dest( paths.scss.dest ) );
	}

	done();
};

/**
 * Build JS.
 *
 * @param {Function} done
 */
const buildJS = ( done ) => {
	for ( const [ name, path ] of Object.entries( paths.js.src ) ) {
		const baseSource = gulp
			.src( path )
			.pipe( plumber() )
			.pipe( concat( 'merged.js' ) )
			.pipe( rename( { basename: name } ) );

		const minified = baseSource
			.pipe( clone() )
			.pipe( terser() )
			.pipe( rename( { suffix: '.min' } ) );

		merge( baseSource, minified ).pipe( gulp.dest( paths.js.dest ) );
	}

	done();
};

/**
 * Build Fonts
 *
 * @param {Function} done
 */
const buildFonts = ( done ) => {
	gulp.src( paths.fonts.src ).pipe( flatten() ).pipe( gulp.dest( paths.fonts.dest ) );

	done();
};

/**
 * Build Images
 *
 * @param {Function} done
 */
const buildImages = ( done ) => {
	gulp.src( paths.images.src )
		.pipe(
			imagemin( {
				progressive: true,
				interlaced: true,
				svgoPlugins: [ { removeUnknownsAndDefaults: false } ],
			} )
		)
		.pipe( gulp.dest( paths.images.dest ) );

	done();
};

/**
 * Clean the build directory.
 *
 * @param {Function} done
 */
const cleanAssets = ( done ) => {
	deleteSync( paths.dest );

	done();
};

/**
 * Runs parallel tasks for .js compiling with webpack and .scss compiling
 *
 * @param {Function} done
 */
const watchAssets = ( done ) => {
	gulp.watch( paths.src + 'scss/**/*.scss', gulp.series( buildCSS ) );
	gulp.watch( paths.src + 'js/**/*.js', gulp.series( buildJS ) );
	gulp.watch( paths.src + 'fonts/**/*', gulp.series( buildFonts ) );
	gulp.watch( paths.src + 'images/**/*', gulp.series( buildImages ) );

	done();
};

const css = gulp.series( buildCSS );
const js = gulp.series( buildJS );
const build = gulp.series( cleanAssets, buildCSS, buildJS, buildFonts, buildImages );
const watcher = gulp.series( watchAssets );

export { css, js, build };
export { watcher as watch };
