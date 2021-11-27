/**
 * Define Packages.
 */
const fs = require( "fs" ).promises;
const path = require( "path" );
const prompts = require( "prompts" );
const ora = require( "ora" );
const replace = require( "replace-in-file" );
const rimraf = require( "rimraf" );

// Valid files and path to be used for replace and rename.
const validKeys = [
  "includes",
  "languages",
  "gulpfile.js",
  "custom-plugin",
];

/**
 * Validate if path is valid for replace and rename.
 *
 * @param {String} path Path value.
 * @returns {Bool}
 */
const isValidPath = ( path ) =>
  validKeys.filter( ( key ) => path.match( new RegExp( key, "g" ) ) || path === key )
  .length ?
  true :
  false;

/**
 * Scan dir recursively.
 *
 * @param {Strin} dir Path value.
 * @param {Array} results Array to restore the recursive values.
 * @returns {Array}
 */
const scan = async ( dir = "./", results = [] ) => {
  const items = await fs.readdir( dir );
  for ( const item of items ) {
    const itemPath = path.join( dir, item );
    if ( isValidPath( itemPath ) ) {
      const stat = await fs.stat( itemPath );
      if ( stat.isDirectory() ) {
        await scan( itemPath, results );
      } else {
        results.push( itemPath );
      }
    }
  }
  return results;
};

/**
 * Replace "custom-plugin" text in files with new file prefix name.
 *
 * @param {Array} files Array of files path.
 * @param {String} key String that has to be replaced with "custom-plugin" text.
 */
const renameFiles = async ( files, key = "" ) => {
  for ( oldpath of files ) {
    const newpath = key.length ?
      oldpath.replace( "custom-plugin", key ) :
      oldpath;
    await fs.rename( oldpath, newpath );
  }
};

/**
 * Check if string type is "String".
 *
 * @param {String} string String text.
 * @returns {Bool}
 */
const isString = ( string ) => typeof string === "string" && string.length;

/**
 * Change the case of string.
 *
 * @param {String} string String to be coverted.
 * @param {String} type In which type to be converted, accecpt 4 type [domain,constant,function,class]
 * @returns {String}
 */
const changeCase = ( string = "", type = "" ) => {
  if ( !isString( string ) ) {
    throw "Plugin name is not a string";
  }

  let str = string;
  str = str
    .split( " " )
    .map( ( word ) =>
      word
      .split( "" )
      .map( ( letter, i ) =>
        0 === i ? letter.toUpperCase() : letter.toLowerCase()
      )
      .join( "" )
    )
    .join( " " );
  str = str
    .split( " " )
    .map( ( word, i ) =>
      0 === i && [ "wp", "wc" ].includes( word.toLowerCase() ) ?
      word.toUpperCase() :
      word
    )
    .join( " " );
  switch ( type ) {
    case "domain":
      str = str.split( " " ).join( "-" ).toLowerCase();
      break;
    case "constant":
      str = str.split( " " ).join( "_" ).toUpperCase();
      break;
    case "function":
      str = str.split( " " ).join( "_" ).toLowerCase();
      break;
    case "class":
      str = str.split( " " ).join( "_" );
      break;
    default:
      break;
  }
  return str;
};

const updatePackageJson = async () => {
  let pkg = await fs.readFile( "./package.json" );
  pkg = JSON.parse( pkg );
  delete pkg.scripts.setup;
  delete pkg.devDependencies[ "setup;" ];
  delete pkg.devDependencies[ "prompts" ];
  delete pkg.devDependencies[ "ora" ];
  delete pkg.devDependencies[ "replace-in-file" ];
  delete pkg.devDependencies[ "rimraf" ];
  await fs.writeFile( "./package.json", JSON.stringify( pkg, null, 2 ) );
};

/**
 * Start the async process.
 */
( async () => {
  const answers = await prompts( {
    type: "text",
    name: "name",
    message: "What will be your Plugin name?",
    validate: ( value ) =>
      value.trim().length && value.trim().match( /^[a-zA-Z ]*$/ ) ?
      true : `Please provide a valid plugin name. Example: WP Bulk Uploader`,
  } );

  const spinner = ora( { text: "Processing..." } );

  try {
    // Store plugin name from input.
    const pluginName = answers.name;

    const replacements = [
      // Title case: {Custom Plugin}.
      changeCase( pluginName ),
      // Pascal case with snake case: {Custom_Plugin}.
      changeCase( pluginName, "class" ),
      // Upper case with snake case: {CUSTOM_PLUGIN}
      changeCase( pluginName, "constant" ),
      // Lower case with kebab case: {custom-plugin}
      changeCase( pluginName, "domain" ),
      // Lower case with snake case: {custom_plugin}
      changeCase( pluginName, "function" ),
    ];

    spinner.start();

    // Read all the file that need to be renamed.
    const files = await scan( "./" );
    if ( !files.length ) {
      throw "Unable to find files for replacements. Please try to reclone the site and run the setup again.";
    }

    // Rename "custom-plugin" word in all the matched files
    // with new file prefix.
    await renameFiles( files, changeCase( pluginName, "domain" ) );

    // Replae in files options.
    const options = {
      files: files.map( ( f ) =>
        f.replace( "custom-plugin", changeCase( pluginName, "domain" ) )
      ),
      from: [
        /Custom Plugin/g,
        /Custom_Plugin/g,
        /CUSTOM_PLUGIN/g,
        /custom-plugin/g,
        /custom_plugin/g,
      ],
      to: replacements,
    };

    // Start the replacement in files.
    await replace( options );

    await updatePackageJson();

    spinner.succeed( "Complete!" );

    rimraf( "./setup.js", ( error ) => {
      if ( error ) throw new Error( error );
    } );

    rimraf( "./.git", ( error ) => {
      if ( error ) throw new Error( error );
    } );

    // Resolve the promise at the end.
    return Promise.resolve();
  } catch ( err ) {
    spinner.fail( "Failed!" );
    console.log( "Runtime Exception: " + err );
    process.exit( 1 );
  }
} )();