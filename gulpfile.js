// Package json.
const pkg = require( "./package.json" );

// See https://github.com/austinpray/asset-builder
const manifest = require( "asset-builder" )( "./src/manifest.json" );

// `path` - Paths to base asset directories. With trailing slashes.
// - `path.source` - Path to the source files. Default: `assets/`
// - `path.dist` - Path to the build directory. Default: `dist/`
const path = manifest.paths;

// `globs` - These ultimately end up in their respective `gulp.src`.
// - `globs.js` - Array of asset-builder JS dependency objects. Example:
//   ```
//   {type: "js", name: "main.js", globs: []}
//   ```
// - `globs.css` - Array of asset-builder CSS dependency objects. Example:
//   ```
//   {type: "css", name: "main.css", globs: []}
//   ```
// - `globs.fonts` - Array of font path globs.
// - `globs.images` - Array of image path globs.
// - `globs.bower` - Array of all the main Bower files.
const globs = manifest.globs;

// `project` - paths to first-party assets.
// - `project.js` - Array of first-party JS assets.
// - `project.css` - Array of first-party CSS assets.
const project = manifest.getProjectGlobs();

const DEST_CSS = path.dist + "css";
const DEST_JS = path.dist + "js";

const gulp = require( "gulp" );

const autoprefixer = require( "autoprefixer" ),
  cleanCSS = require( "gulp-clean-css" ),
  concat = require( "gulp-concat" ),
  cssbeautify = require( "gulp-cssbeautify" ),
  dartSass = require( "sass" ),
  del = require( "del" ),
  discardDuplicates = require( "postcss-discard-duplicates" ),
  flatten = require( "gulp-flatten" ),
  gcmq = require( "gulp-group-css-media-queries" ),
  gulpSass = require( "gulp-sass" ),
  imagemin = require( "gulp-imagemin" ),
  jshint = require( "gulp-jshint" ),
  lazypipe = require( "lazypipe" ),
  merge = require( "merge-stream" ),
  plumber = require( "gulp-plumber" ),
  postcss = require( "gulp-postcss" ),
  rename = require( "gulp-rename" ),
  strip = require( "gulp-strip-css-comments" ),
  uglify = require( "gulp-terser" ),
  wpPot = require( "wp-pot" );

const scss = gulpSass( dartSass );

// ## Reusable Pipelines
// See https://github.com/OverZealous/lazypipe

// ### CSS processing pipeline
// Example
// ```
// gulp.src(cssFiles)
//   .pipe(cssTasks("main.css")
//   .pipe(gulp.dest(path.dist + "styles"))
// ```
const cssTasks = ( filename ) => {
  return lazypipe()
    .pipe( plumber )
    .pipe( () =>
      scss( {
        outputStyle: "expanded",
        precision: 10,
        includePaths: [ "." ],
      } )
    )
    .pipe( () => strip() )
    .pipe( gcmq )
    .pipe( concat, filename )
    .pipe( () => postcss( [ discardDuplicates(), autoprefixer() ] ) )
    .pipe( () =>
      cssbeautify( {
        autosemicolon: true,
      } )
    )();
};

// ### Build css
// `gulp styles` - Compiles, combines, and optimizes  CSS and project CSS.
// By default this task will only log a warning if a precompiler error is
// raised.
function buildCSS ( done ) {
  let merged = merge();

  manifest.forEachDependency( "css", function ( dep ) {
    merged.add(
      gulp
      .src( dep.globs, {
        base: "css",
      } )
      .pipe( cssTasks( dep.name ) )
    );
  } );

  merged
    .pipe( gulp.dest( DEST_CSS ) )
    .pipe(
      cleanCSS( {
        compatibility: "ie8",
      } )
    )
    .pipe(
      rename( {
        suffix: ".min",
      } )
    )
    .pipe( gulp.dest( DEST_CSS ) );

  done();
}

// ### JS processing pipeline
// Example
// ```
// gulp.src(jsFiles)
//   .pipe(jsTasks("main.js")
//   .pipe(gulp.dest(path.dist + "scripts"))
// ```
const jsTasks = ( filename ) => {
  return lazypipe().pipe( plumber ).pipe( concat, filename )();
};

// ### JSHint
// `gulp jshint` - Lints configuration JSON and project JS.
function lintJS ( done ) {
  const files = project.js.filter( ( str ) => !str.includes( ".min.js" ) );

  gulp.src( files )
    .pipe(
      jshint( {
        esversion: 6,
      } )
    )
    .pipe( jshint.reporter( "default" ) );

  done();
}

// ### Build JS
// `gulp scripts` - compiles, combines, and optimizes JS
// and project JS.
function buildJS ( done ) {
  let merged = merge();

  manifest.forEachDependency( "js", function ( dep ) {
    merged.add(
      gulp
      .src( dep.globs, {
        base: "js",
      } )
      .pipe( jsTasks( dep.name ) )
    );
  } );

  merged
    .pipe( gulp.dest( DEST_JS ) )
    .pipe( uglify() )
    .pipe(
      rename( {
        suffix: ".min",
      } )
    )
    .pipe( gulp.dest( DEST_JS ) );

  done();
}

// ### Build Fonts
// `gulp fonts` - Grabs all the fonts and outputs them in a flattened directory
// structure. See: https://github.com/armed/gulp-flatten
function buildFonts ( done ) {
  gulp.src( globs.fonts )
    .pipe( flatten() )
    .pipe( gulp.dest( path.dist + "fonts" ) );

  done();
}

// ### Build Images
// `gulp images` - Run lossless compression on all the images.
function buildImages ( done ) {
  gulp.src( globs.images )
    .pipe(
      imagemin( {
        progressive: true,
        interlaced: true,
        svgoPlugins: [ {
          removeUnknownsAndDefaults: false,
        }, ],
      } )
    )
    .pipe( gulp.dest( path.dist + "images" ) );

  done();
}

// ### Clean
// `gulp clean` - Deletes the build folder entirely.
function clean ( done ) {
  del.sync( path.dist );
  done();
}

// ### Make Pot
function makePot ( done ) {
  wpPot( {
    destFile: `./languages/${pkg.name}.pot`,
    domain: pkg.name,
    package: `${pkg.pluginName} ${pkg.version}`,
    src: "**/*.php",
  } );

  done();
}

// ### Watch
// `gulp watch` - Use BrowserSync to proxy your dev server and synchronize code
// changes across devices. Specify the hostname of your dev server at
// `manifest.config.devUrl`. When a modification is made to an asset, run the
// build step for that asset and inject the changes into the page.
// See: http://www.browsersync.io
function watch ( done ) {
  gulp.watch( [ path.source + "css/**/*" ], gulp.parallel( buildCSS ) );
  gulp.watch( [ path.source + "js/**/*" ], gulp.parallel( lintJS, buildJS ) );
  gulp.watch( [ path.source + "images/**/*" ], buildImages );
  gulp.watch( [ path.source + "fonts/**/*" ], buildFonts );

  done();
}

// EXPORT methods
const js = gulp.series( lintJS, buildJS );
const build = gulp.parallel(
  clean,
  buildCSS,
  buildFonts,
  buildImages,
  js,
  makePot
);

exports.css = buildCSS;
exports.js = js;
exports.lint = lintJS;
exports.images = buildImages;
exports.fonts = buildFonts;
exports.clean = clean;
exports.makepot = makePot;
exports.build = build;
exports.watch = watch;
exports.default = build;