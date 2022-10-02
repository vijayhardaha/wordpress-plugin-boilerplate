/* eslint-disable no-console */

/**
 * Define Packages.
 */
import del from 'del';
import { promises as fs } from 'fs';
import ora from 'ora';
import { join } from 'path';
import prompts from 'prompts';
import replace from 'replace-in-file';

// Valid files and path to be used for replace and rename.
const validKeys = [
	'custom-plugin',
	'gulpfile.js',
	'includes',
	'languages',
	'package.json',
	'phpcs.xml',
];

/**
 * Validate if path is valid for replace and rename.
 *
 * @param {string} filePath Path value.
 * @return {boolean} Validation status.
 */
const isValidPath = ( filePath ) => ( validKeys.filter( ( key ) => filePath.match( new RegExp( key, 'g' ) ) || filePath === key ).length ? true : false );

/**
 * Scan dir recursively.
 *
 * @param {string} dir     Path value.
 * @param {Array}  results Array to restore the recursive values.
 * @return {Array} Returns scanned files array.
 */
const scan = async ( dir = './', results = [] ) => {
	const items = await fs.readdir( dir );
	for ( const item of items ) {
		const itemPath = join( dir, item );
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
 * @param {Array}  files Array of files path.
 * @param {string} key   String that has to be replaced with "custom-plugin" text.
 */
const renameFiles = async ( files, key = '' ) => {
	for ( const oldpath of files ) {
		const newpath = key.length ? oldpath.replace( 'custom-plugin', key ) : oldpath;
		await fs.rename( oldpath, newpath );
	}
};

/**
 * Check if string type is "String".
 *
 * @param {string} string String text.
 * @return {boolean} Returns true if string else false.
 */
const isString = ( string ) => typeof string === 'string' && string.length;

/**
 * Change the case of string.
 *
 * @param {string} string String to be coverted.
 * @param {string} type   In which type to be converted, accecpt 4 type [domain,constant,function,class]
 * @return {string} Retuns modified string.
 */
const changeCase = ( string = '', type = '' ) => {
	if ( ! isString( string ) ) {
		throw 'Plugin name is not a string';
	}

	let str = string;
	str = str
		.split( ' ' )
		.map( ( word ) =>
			word
				.split( '' )
				.map( ( letter, i ) => ( 0 === i ? letter.toUpperCase() : letter.toLowerCase() ) )
				.join( '' )
		)
		.join( ' ' );
	str = str
		.split( ' ' )
		.map( ( word, i ) => ( 0 === i && [ 'wp', 'wc' ].includes( word.toLowerCase() ) ? word.toUpperCase() : word ) )
		.join( ' ' );
	switch ( type ) {
		case 'domain':
			str = str.split( ' ' ).join( '-' ).toLowerCase();
			break;
		case 'constant':
			str = str.split( ' ' ).join( '_' ).toUpperCase();
			break;
		case 'function':
			str = str.split( ' ' ).join( '_' ).toLowerCase();
			break;
		case 'class':
			str = str.split( ' ' ).join( '_' );
			break;
		default:
			break;
	}
	return str;
};

const updatePackageJson = async () => {
	let pkg = await fs.readFile( './package.json' );
	pkg = JSON.parse( pkg );
	delete pkg.scripts.setup;
	delete pkg.devDependencies.prompts;
	delete pkg.devDependencies.ora;
	delete pkg.devDependencies[ 'replace-in-file' ];
	await fs.writeFile( './package.json', JSON.stringify( pkg, null, 2 ) );
};

/**
 * Start the async process.
 */
( async () => {
	const answers = await prompts( {
		type: 'text',
		name: 'name',
		message: 'What will be your Plugin name?',
		validate: ( value ) => ( value.trim().length && value.trim().match( /^[a-zA-Z ]*$/ ) ? true : 'Please provide a valid plugin name. Example: WP Bulk Uploader' ),
	} );

	const spinner = ora( { text: 'Processing...' } );

	try {
		// Store plugin name from input.
		const pluginName = answers.name;

		const replacements = [
			// Title case: {Custom Plugin}.
			changeCase( pluginName ),
			// Pascal case with snake case: {Custom_Plugin}.
			changeCase( pluginName, 'class' ),
			// Upper case with snake case: {CUSTOM_PLUGIN}
			changeCase( pluginName, 'constant' ),
			// Lower case with kebab case: {custom-plugin}
			changeCase( pluginName, 'domain' ),
			// Lower case with snake case: {custom_plugin}
			changeCase( pluginName, 'function' ),
		];

		spinner.start();

		// Read all the file that need to be renamed.
		const files = await scan( './' );
		if ( ! files.length ) {
			throw 'Unable to find files for replacements. Please try to reclone the site and run the setup again.';
		}

		// Rename "custom-plugin" word in all the matched files
		// with new file prefix.
		await renameFiles( files, changeCase( pluginName, 'domain' ) );

		// Replae in files options.
		const options = {
			files: files.map( ( f ) => f.replace( 'custom-plugin', changeCase( pluginName, 'domain' ) ) ),
			from: [ /Custom Plugin/g, /Custom_Plugin/g, /CUSTOM_PLUGIN/g, /custom-plugin/g, /custom_plugin/g ],
			to: replacements,
		};

		// Start the replacement in files.
		await replace( options );

		// Update package json.
		await updatePackageJson();

		// Delete files.
		await del( [ 'setup.js', '.git' ] );

		spinner.succeed( 'Complete!' );

		// Resolve the promise at the end.
		return Promise.resolve();
	} catch ( err ) {
		spinner.fail( 'Failed!' );
		console.log( 'Runtime Exception: ' + err );
		process.exit( 1 );
	}
} )();
