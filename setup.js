const fs = require( 'fs' );
const path = require( 'path' );
const promisify = require( 'util' ).promisify;
const prompts = require( 'prompts' );
const ora = require( 'ora' );
const spinner = ora( {
  text: ''
} );
const replace = require( 'replace-in-file' );

const readDir = promisify( fs.readdir );
const statp = promisify( fs.stat );

const validKeys = [ "includes", "languages", "gulpfile.js", "custom-plugin" ];

const isValidPath = ( p ) => {
  const match = validKeys.filter( k => {
    const reg = new RegExp( k, "g" );
    return p.match( reg ) || p === k;
  } );
  return match.length ? true : false;
}

const scan = async ( dir = './', results = [] ) => {
  let files = await readDir( dir );
  for ( let f of files ) {
    let fullPath = path.join( dir, f );
    if ( isValidPath( fullPath ) ) {
      let stat = await statp( fullPath );
      if ( stat.isDirectory() ) {
        await scan( fullPath, results );
      } else {
        results.push( fullPath );
      }
    }
  }
  return results;
}

const renameFiles = async ( files, key = "" ) => {
  for ( oldpath of files ) {
    const newpath = key.length ? oldpath.replace( "custom-plugin", key ) : oldpath;
    fs.rename( oldpath, newpath, ( error ) => {
      if ( error ) {
        throw error;
      }
    } );
  }
}

/**
 * 
 * @param {String} string String to be coverted.
 * @param {String} type In which type to be converted, accecpt 4 type [domain,constant,function,class]
 */
const changeCase = ( string = "", type = "" ) => {
  if ( typeof string === "string" && string.length ) {
    let str;
    str = string.split( " " ).map( word => word.split( "" ).map( ( letter, i ) => 0 === i ? letter.toUpperCase() : letter.toLowerCase() ).join( "" ) ).join( " " );
    str = str.split( " " ).map( ( word, i ) => 0 === i && [ "Wp", "Wc" ].includes( word ) ? word.toUpperCase() : word ).join( " " );
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
  }
  return string;
}

/* 
 */
( async () => {
  const answers = await prompts( {
    type: 'text',
    name: 'name',
    message: 'What will be your Plugin name?',
    validate: value => ( value.trim().length && value.trim().match( /^[a-zA-Z ]*$/ ) ) ? true : `Please provide a valid plugin name. Example: WP Bulk Uploader`
  } );

  try {
    const pn = answers.name;
    const replacements = [ changeCase( pn ), changeCase( pn, "class" ), changeCase( pn, "constant" ), changeCase( pn, "domain" ), changeCase( pn, "function" ) ];
    // Read all the file that need to be renamed.
    spinner.info( `Rendering plugin files...` );
    spinner.start( `\n` );
    const files = await scan( "./" );
    if ( files.length ) {
      spinner.succeed( `${files.length} plugin files found.\n` );

      spinner.info( "Renaming plugin files..." );
      spinner.start( `\n` );
      await renameFiles( files, changeCase( pn, "domain" ) );
      spinner.succeed( `${files.length} plugin files renamed.\n` );

      spinner.info( "Replacing plugin keywords in files..." );
      spinner.start( `\n` );
      const options = {
        files: files.map( f => f.replace( "custom-plugin", changeCase( pn, "domain" ) ) ),
        from: [ /Custom Plugin/g, /Custom_Plugin/g, /CUSTOM_PLUGIN/g, /custom-plugin/g, /custom_plugin/g ],
        to: replacements,
      }
      const results = await replace( options );
      spinner.succeed( `Keywords replaced in ${files.length} plugin files.\n` );
    } else {
      spinner.info( "No files found to process." );
    }
  } catch ( error ) {
    spinner.warn( `Error occurred` );
    spinner.warn( `${error.message}` );
    console.log();
  }
} )();