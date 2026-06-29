/**
 * ======================================================================
 * Gulp Configuration
 * ======================================================================
 * Purpose: Defines Gulp tasks for building CSS, JavaScript, fonts, and
 *          images with optimization and minification support.
 *          Use `bunx gulp <task>` to run configured tasks.
 * Docs:    https://gulpjs.com/docs/en/getting-started/creating-tasks/
 * ======================================================================
 */

// Node built-ins.
import fs from 'fs';
import { gzipSync } from 'zlib';

// Third-party: Sass.
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';

// Third-party: Gulp & plugins.
import gulp from 'gulp';
import cleancss from 'gulp-clean-css';
import clone from 'gulp-clone';
import concat from 'gulp-concat';
import flatten from 'gulp-flatten';
import imagemin, { gifsicle, mozjpeg, optipng, svgo } from 'gulp-imagemin';
import plumber from 'gulp-plumber';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import terser from 'gulp-terser';

// Third-party: PostCSS plugins.
import autoprefixer from 'autoprefixer';
import { cssDeclarationSorter } from 'css-declaration-sorter';
import duplicates from 'postcss-discard-duplicates';
import mergeRules from 'postcss-merge-rules';
import sortMediaQueries from 'postcss-sort-media-queries';

// Third-party: Utilities.
import { smacssOrder } from '@vijayhardaha/dev-config/gulp-smacss';
import { deleteSync } from 'del';
import log from 'fancy-log';
import merge from 'merge-stream';
import prettier from 'prettier';
import through from 'through2';

/**
 * Build path configuration for all asset pipelines.
 *
 * @typedef {object} PathsConfig
 * @property {string} src - Base source directory.
 * @property {string} dest - Base destination directory.
 * @property {object} scss - SCSS pipeline settings.
 * @property {boolean} [scss.disable] - Disables SCSS build and watch when true.
 * @property {{[key: string]: string[]}} scss.src - Named SCSS entry files.
 * @property {string} scss.dest - Relative SCSS output directory.
 * @property {object} js - JavaScript pipeline settings.
 * @property {boolean} [js.disable] - Disables JS build and watch when true.
 * @property {{[key: string]: string[]}} js.src - Named JS entry files.
 * @property {string} js.dest - Relative JS output directory.
 * @property {object} images - Image pipeline settings.
 * @property {boolean} [images.disable] - Disables image build and watch when true.
 * @property {string} images.src - Relative image source glob.
 * @property {string} images.dest - Relative image output directory.
 * @property {object} fonts - Font pipeline settings.
 * @property {boolean} [fonts.disable] - Disables font build and watch when true.
 * @property {string} fonts.src - Relative font source glob.
 * @property {string} fonts.dest - Relative font output directory.
 */
const PATHS = {
  src: 'src',
  dest: 'assets',
  scss: { src: { admin: ['scss/admin.scss'], frontend: ['scss/frontend.scss'] }, dest: 'css' },
  js: { src: { admin: ['js/admin.js'], frontend: ['js/frontend.js'] }, dest: 'js' },
  images: { src: 'images/**/*', dest: 'images' },
  fonts: { src: 'fonts/**/*', dest: 'fonts' },
};

/**
 * Sass compiler instance configured for Gulp pipelines.
 *
 * @type {import('gulp-sass').GulpSass}
 */
const sass = gulpSass(dartSass);

/**
 * True when running in production mode.
 *
 * @type {boolean}
 */
const isProduction = process.env.NODE_ENV === 'production';

/**
 * Parsed package metadata used by startup banner.
 *
 * @type {{name?: string, version?: string, author?: string|{name?: string, url?: string}}}
 */
let packageJson = {};
try {
  packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
} catch {
  log('Warning: package.json not found or invalid — banner metadata will be missing.');
}

/**
 * CleanCSS optimization levels shared by formatted and minified CSS outputs.
 *
 * @type {{1: object, 2: object}}
 */
const cssOptimizationLevels = {
  1: {
    cleanupCharsets: true, // Remove @charset when possible
    optimizeBackground: true, // Optimize background properties
    optimizeBorderRadius: true, // Optimize border-radius
    optimizeFontWeight: true, // Optimize font-weight values
    optimizeOutline: true, // Optimize outline property
    removeNegativePaddings: true, // Remove negative paddings
    removeQuotes: false, // Keep quotes to avoid breaking `content` values with special characters
    removeWhitespace: true, // Remove unnecessary whitespace
    roundingPrecision: 2, // Round numbers to N decimal places
    selectorsSortingMethod: 'standard', // How to sort selectors
    specialComments: 1, // Keep first special comment
    tidyAtRules: true, // Tidy at-rules
    tidyBlockScopes: true, // Tidy block scopes
    tidySelectors: true, // Tidy selectors
  },
  2: {
    mergeAdjacentRules: true, // Merge adjacent rules
    mergeIntoShorthands: true, // Merge into shorthand properties
    mergeMedia: true, // Merge @media rules
    mergeNonAdjacentRules: true, // Merge non-adjacent rules
    mergeSemantically: false, // Merge rules semantically
    overrideProperties: true, // Override properties
    removeEmpty: true, // Remove empty rules
    reduceNonAdjacentRules: true, // Reduce non-adjacent rules
    removeDuplicateFontRules: true, // Remove duplicate @font-face
    removeDuplicateMediaBlocks: true, // Remove duplicate @media
    removeDuplicateRules: true, // Remove duplicate rules
    removeUnusedAtRules: false, // Remove unused at-rules
    restructureRules: true, // Restructure rules
  },
};

/**
 * Sass compiler options shared across SCSS build entries.
 *
 * @type {{quietDeps: boolean, silenceDeprecations: string[], verbose: boolean, logger: object}}
 */
const sassCompilerOptions = {
  quietDeps: true,
  silenceDeprecations: ['moz-document'],
  verbose: false,
  // sass.compiler exposes the underlying dartSass instance via gulp-sass; Logger.silent suppresses all compile warnings.
  logger: sass.compiler.Logger.silent,
};

/**
 * Imagemin plugin instances for image optimization tasks (gulp-imagemin v8+ API).
 *
 * Each optimizer is configured individually as a plugin instance.
 *
 * @type {Array<import('imagemin').Plugin>}
 */
const imageMinPlugins = [
  gifsicle({ interlaced: true }),
  mozjpeg({ progressive: true }),
  optipng(),
  svgo({ plugins: [{ name: 'removeUnknownsAndDefaults', active: false }] }),
];

/**
 * Build a full source path from base source and relative segment.
 *
 * @param {string|string[]} value - Relative file path or glob.
 *
 * @returns {string|string[]} Full source path or path list.
 */
const makeSrcPath = (value) => {
  const toFull = (item) => (item.includes('node_modules') ? item : `${PATHS.src}/${item}`);

  return Array.isArray(value) ? value.map(toFull) : toFull(value);
};

/**
 * Build a full destination path from base destination and relative segment.
 *
 * @param {string} value - Relative destination directory.
 *
 * @returns {string} Full destination path.
 */
const makeDestPath = (value) => `${PATHS.dest}/${value}`;

/**
 * Check if a path exists and contains files.
 *
 * @param {string|string[]} pathValue - Path or glob pattern to validate.
 *
 * @returns {boolean} True if path exists and has entries.
 */
const validatePath = (pathValue) => {
  if (!pathValue) return false;

  const pathsToCheck = Array.isArray(pathValue) ? pathValue : [pathValue];

  for (const p of pathsToCheck) {
    const isGlob = p.includes('*');
    const checkPath = isGlob ? p.replace(/\/\*+.*$/, '') : p;

    if (!checkPath) return false;

    if (isGlob) {
      if (!fs.existsSync(checkPath)) return false;
      try {
        const files = fs.readdirSync(checkPath, { recursive: true });
        if (!files.length) return false;
      } catch {
        return false;
      }
    } else if (!fs.existsSync(checkPath)) {
      return false;
    }
  }

  return true;
};

/**
 * Filter object entries to include only those with valid paths.
 *
 * @param {object} pathsObj - Object with path entries to validate.
 * @param {string} type - Type identifier for logs.
 *
 * @returns {Array} Filtered entries with valid paths.
 */
const getValidEntries = (pathsObj, type) => {
  return Object.entries(pathsObj).filter(([name, pathValue]) => {
    const resolvedPath = makeSrcPath(pathValue);

    if (!validatePath(resolvedPath)) {
      log(`Skipping ${type} (${name}): source path does not exist or is empty`);
      return false;
    }

    return true;
  });
};

/**
 * Check whether an asset task is enabled.
 *
 * @param {string} type - Asset type key in PATHS.
 *
 * @returns {boolean} True when task is enabled.
 */
const isEnabled = (type) => {
  if (PATHS[type]?.disable === true) {
    log(`Skipping ${type}: task disabled in PATHS.${type}.disable`);
    return false;
  }
  return true;
};

/**
 * Create a reusable plumber error handler for build streams.
 *
 * @param {string} taskName - Task label for contextual error logs.
 *
 * @returns {(this: import('stream').Transform, err: Error) => void} Error handler callback.
 */
const createErrorHandler = (taskName) => {
  return function onStreamError(err) {
    log(`${taskName} Error: ${err.message}`);
    this.emit('end');
  };
};

/**
 * Batch-format CSS files with Prettier using a single Promise.all call.
 *
 * Avoids cold-starting Prettier once per file by collecting all files
 * in the stream and running format concurrently.
 *
 * @returns {import('stream').Duplex} Transform stream that emits Prettier-formatted files.
 */
const formatCssBatch = () => {
  /** @type {Array<import('vinyl')>} */
  const files = [];

  return through.obj(
    function collect(file, _enc, cb) {
      files.push(file);
      cb(null);
    },
    function flush(cb) {
      Promise.all(
        files.map(async (file) => {
          try {
            const formatted = await prettier.format(file.contents.toString(), { parser: 'css' });
            file.contents = Buffer.from(formatted);
            return file;
          } catch (err) {
            err.message = `Prettier Error: ${err.message}`;
            throw err;
          }
        })
      )
        .then(() => {
          files.forEach((file) => this.push(file));
          cb(null);
        })
        .catch((err) => cb(err));
    }
  );
};

/**
 * Print a startup banner with package metadata and runtime mode.
 *
 * @returns {void}
 */
const printStartupBanner = () => {
  // Resolve runtime mode for banner output.
  const mode = isProduction ? 'production' : 'development';
  // Support both string and object author formats.
  const author = typeof packageJson.author === 'string' ? packageJson.author : packageJson.author?.name || 'Unknown';
  const lines = [
    `Project : ${packageJson.name}`,
    `Version : ${packageJson.version}`,
    `Author  : ${author}`,
    `Mode    : ${mode}`,
    'Message : Build pipeline initialized',
  ];
  // Match banner width to the longest row.
  const width = Math.max(...lines.map((line) => line.length));
  // Draw top/bottom frame with padding.
  const border = `+${'-'.repeat(width + 2)}+`;

  // Print frame top.
  console.log(border);

  for (const line of lines) {
    // Print padded content row.
    console.log(`| ${line.padEnd(width)} |`);
  }

  // Print frame bottom.
  console.log(border);
};

/**
 * Display total size information for generated CSS/JS assets.
 *
 * @returns {Promise<void>}
 */
const displayTotalSize = async () => {
  // Resolve root output directory.
  const destination = makeDestPath('');

  if (!fs.existsSync(destination)) {
    log(`Skipping size report: ${destination} does not exist`);
    return;
  }

  // Quick check for generated files in output root.
  const hasFiles = fs.readdirSync(destination).length > 0;
  if (!hasFiles) {
    log(`Skipping size report: No files found in ${destination}`);
    return;
  }

  // Normalize separators for cross-platform compatibility (readdirSync returns
  // backslash-separated paths on Windows when recursive is true).
  const files = fs
    .readdirSync(destination, { recursive: true })
    .map((f) => f.toString().replace(/\\/g, '/'))
    .filter((f) => f.endsWith('.css') || f.endsWith('.js'));

  for (const file of files) {
    const fullPath = `${destination}/${file}`;
    const stats = fs.statSync(fullPath);
    const gzipped = gzipSync(fs.readFileSync(fullPath));
    log(`Asset Output: ${file} ${stats.size} B (gzipped ${gzipped.length} B)`);
  }
};

/**
 * Compile SCSS to CSS and output both beautified and minified versions.
 *
 * @returns {Promise<void>}
 */
const buildCSS = async () => {
  if (!isEnabled('scss')) return;

  const validEntries = getValidEntries(PATHS.scss.src, 'CSS');
  const destination = makeDestPath(PATHS.scss.dest);

  if (validEntries.length === 0) return;

  // Sequential: each entry awaits completion before the next starts, ensuring
  // deterministic output order and avoiding concurrent write conflicts.
  for (const [name, pathValue] of validEntries) {
    const sourcePath = makeSrcPath(pathValue);

    const baseSource = gulp
      .src(sourcePath)
      .pipe(plumber({ errorHandler: createErrorHandler(`CSS (${name})`) }))
      .pipe(sass({ ...sassCompilerOptions }))
      .pipe(concat('merged.css'))
      .pipe(
        postcss([
          duplicates(),
          mergeRules(),
          autoprefixer(),
          sortMediaQueries(),
          cssDeclarationSorter({
            order: (a, b) => {
              const aVar = a.startsWith('--');
              const bVar = b.startsWith('--');
              if (aVar && !bVar) return -1;
              if (!aVar && bVar) return 1;
              const aIndex = smacssOrder.indexOf(a);
              const bIndex = smacssOrder.indexOf(b);
              if (aIndex === -1 && bIndex === -1) return 0;
              if (aIndex === -1) return 1;
              if (bIndex === -1) return -1;
              return aIndex - bIndex;
            },
          }),
        ])
      );

    const beautified = baseSource
      .pipe(clone())
      .pipe(cleancss({ format: 'beautify', level: cssOptimizationLevels }))
      .pipe(formatCssBatch())
      .pipe(rename({ basename: name }));

    const minified = baseSource
      .pipe(clone())
      .pipe(cleancss({ level: cssOptimizationLevels }))
      .pipe(rename({ basename: name, suffix: '.min' }));

    await new Promise((resolve) => {
      merge(beautified, minified).pipe(gulp.dest(destination)).on('end', resolve);
    });
  }
};

/**
 * Build JavaScript and output both beautified and minified versions.
 *
 * @returns {Promise<void>}
 */
const buildJS = async () => {
  if (!isEnabled('js')) return;

  const validEntries = getValidEntries(PATHS.js.src, 'JS');
  const destination = makeDestPath(PATHS.js.dest);

  if (validEntries.length === 0) return;

  for (const [name, pathValue] of validEntries) {
    const sourcePath = makeSrcPath(pathValue);
    const baseSource = gulp
      .src(sourcePath)
      // Keep watch/build alive when a single file fails.
      .pipe(plumber({ errorHandler: createErrorHandler(`JS (${name})`) }))
      // Merge entry scripts into one deterministic output file.
      .pipe(concat('merged.js'))
      .pipe(rename({ basename: name }));

    const unminified = baseSource.pipe(clone());

    const minified = baseSource
      .pipe(clone())
      // Emit minified sibling as *.min.js.
      .pipe(terser())
      .pipe(rename({ suffix: '.min' }));

    // Both unminified and minified are always written regardless of NODE_ENV.
    await new Promise((resolve) => {
      merge(unminified, minified).pipe(gulp.dest(destination)).on('end', resolve);
    });
  }
};

/**
 * Copy font files to the build directory, flattening directory structure.
 *
 * @returns {Promise<void>}
 */
const buildFonts = async () => {
  if (!isEnabled('fonts')) return;

  const sourcePath = makeSrcPath(PATHS.fonts.src);
  const destination = makeDestPath(PATHS.fonts.dest);

  if (!validatePath(sourcePath)) {
    log('Skipping fonts: source path does not exist or is empty');
    return;
  }

  await new Promise((resolve) => {
    gulp
      // Read all font files as binary.
      .src(sourcePath, { encoding: false })
      // Strip nested folders from incoming font paths.
      .pipe(flatten())
      // Write flattened fonts to destination.
      .pipe(gulp.dest(destination))
      .on('end', resolve);
  });
};

/**
 * Optimize images and copy them to the build directory.
 *
 * @returns {Promise<void>}
 */
const buildImages = async () => {
  if (!isEnabled('images')) return;

  const sourcePath = makeSrcPath(PATHS.images.src);
  const destination = makeDestPath(PATHS.images.dest);

  if (!validatePath(sourcePath)) {
    log('Skipping images: source path does not exist or is empty');
    return;
  }

  await new Promise((resolve) => {
    gulp
      // Read image assets as binary.
      .src(sourcePath, { encoding: false })
      // Optimize images with shared settings.
      .pipe(imagemin(imageMinPlugins))
      // Write optimized images to destination.
      .pipe(gulp.dest(destination))
      .on('end', resolve);
  });
};

/**
 * Clean built assets directory.
 *
 * @returns {Promise<void>}
 */
const cleanAssets = async () => {
  log(`Cleaning ${PATHS.dest} directory...`);

  deleteSync(PATHS.dest);
};

/**
 * Watch enabled asset paths and trigger related build tasks.
 *
 * @returns {void}
 */
const watchAssets = () => {
  // Use a plain inline handler: gulp.watch returns an FSWatcher, not a Transform
  // stream, so createErrorHandler (which calls this.emit('end')) does not apply.
  const onWatchError = (err) => log(`Watch Error: ${err.message}`);
  const activeWatchers = [];

  if (isEnabled('scss')) {
    log('Starting SCSS file watcher...');
    activeWatchers.push(gulp.watch(makeSrcPath('scss/**/*.scss'), gulp.series(buildCSS)).on('error', onWatchError));
  }

  if (isEnabled('js')) {
    log('Starting JS file watcher...');
    activeWatchers.push(gulp.watch(makeSrcPath('js/**/*.js'), gulp.series(buildJS)).on('error', onWatchError));
  }

  if (isEnabled('fonts')) {
    log('Starting fonts file watcher...');
    activeWatchers.push(gulp.watch(makeSrcPath('fonts/**/*'), gulp.series(buildFonts)).on('error', onWatchError));
  }

  if (isEnabled('images')) {
    log('Starting images file watcher...');
    activeWatchers.push(gulp.watch(makeSrcPath('images/**/*'), gulp.series(buildImages)).on('error', onWatchError));
  }

  if (activeWatchers.length > 0) {
    log(
      `Watching all enabled asset types (${activeWatchers.length} watcher${activeWatchers.length > 1 ? 's' : ''})...`
    );
  } else {
    log('No asset watchers enabled.');
  }
};

// Build CSS assets only.
const css = gulp.series(buildCSS);
// Build JavaScript assets only.
const js = gulp.series(buildJS);
// Run full build pipeline (clean -> assets -> size report).
const build = gulp.series(cleanAssets, buildCSS, buildJS, buildFonts, buildImages, displayTotalSize);
// Start watch-only workflow.
const watcher = watchAssets;
// Build once, then start watching for changes. If build fails, watchAssets will not run.
const dev = gulp.series(build, watchAssets);

// Print startup metadata banner.
printStartupBanner();

// Export named tasks for CLI usage.
export { css, js, build, dev, watcher as watch };
// Default task for `gulp` command.
export default dev;
