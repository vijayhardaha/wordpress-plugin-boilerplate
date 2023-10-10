/* eslint-disable no-console */

/**
 * Import required packages.
 */
import { deleteSync as del } from 'del'; // Import deleteSync from del package for asset cleanup.
import { promises as fs } from 'fs'; // Import promises from fs package for file operations.
import ora from 'ora'; // Import ora for displaying progress spinner.
import { join } from 'path'; // Import join from path for path manipulation.
import prompts from 'prompts'; // Import prompts for user input.
import replace from 'replace-in-file'; // Import replace for text replacement.

// Valid files and path to be used for replace and rename.
const validKeys = [
	'custom-plugin',
	'gulpfile.mjs',
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
const isValidPath = ( filePath ) => validKeys.some( ( key ) => filePath.match( new RegExp( key, 'g' ) ) || filePath === key );

/**
 * Recursively scan a directory for valid files.
 *
 * @param {string} dir Path value.
 * @return {Promise<Array>} Returns a Promise that resolves to an array of scanned files.
 */
const scan = async ( dir = './' ) => {
	// Read the contents of the current directory.
	const items = await fs.readdir( dir );

	// Define an async function to scan an individual item.
	const scanItem = async ( item ) => {
		// Construct the full path of the item.
		const itemPath = join( dir, item );

		// Get information about the item, such as whether it's a directory.
		const stat = await fs.stat( itemPath );

		// Check if the item path is valid based on custom criteria (isValidPath function).
		if ( isValidPath( itemPath ) ) {
			if ( stat.isDirectory() ) {
				// If the item is a directory, scan its contents recursively.
				const subItems = await fs.readdir( itemPath );
				// Use Promise.all to concurrently scan subitems and flatten the results.
				const subResults = await Promise.all( subItems.map( ( subItem ) => scanItem( join( itemPath, subItem ) ) ) );
				return subResults.flat(); // Return the flattened array of scanned items.
			}
			// If the item is a file, return its path as an array.
			return [ itemPath ];
		}

		// If the item path is not valid, return an empty array.
		return [];
	};

	// Use Promise.all to concurrently scan all items in the current directory.
	const results = await Promise.all( items.map( scanItem ) );

	// Flatten the array of results and return it as the final scanned files array.
	return results.flat();
};

/**
 * Rename files by replacing "custom-plugin" with a new file prefix name.
 *
 * @param {Array}  files Array of file paths.
 * @param {string} key   String to replace "custom-plugin" text.
 */
const renameFiles = async ( files, key = '' ) => {
	// Iterate through the 'files' array.
	for ( const oldpath of files ) {
	// Determine the 'newpath' by replacing 'custom-plugin' with 'key' (if 'key' is not empty), otherwise keep it the same.
		const newpath = key.length ? oldpath.replace( 'custom-plugin', key ) : oldpath;

		// Rename the file from 'oldpath' to 'newpath'.
		await fs.rename( oldpath, newpath );
	}
};

/**
 * Check if a value is a non-empty string.
 *
 * @param {string} string String text.
 * @return {boolean} Returns true if the value is a non-empty string, else false.
 */
const isString = ( string ) => typeof string === 'string' && string.length;

/**
 * Change the case of a string.
 *
 * @param {string} string - The input string to convert.
 * @param {string} type   - The target case type ('domain', 'constant', 'function', or 'class').
 * @return {string} - The converted string.
 * @throws {Error} - Throws an error if the input is not a string.
 */
const changeCase = ( string = '', type = '' ) => {
	if ( ! isString( string ) ) {
		throw new Error( 'Plugin name should be a string.' );
	}

	let str = string;

	// Convert the input string to title case.
	str = str
		.split( ' ' ) // Split the string into an array of words.
		.map( ( word ) =>
			word[ 0 ].toUpperCase() + word.slice( 1 ).toLowerCase() // Capitalize the first letter and make the rest lowercase for each word.
		)
		.join( ' ' ); // Join the words back into a single string with spaces.

	// Handle special cases for 'wp' and 'wc' by capitalizing only the first word.
	str = str
		.split( ' ' ) // Split the string into an array of words.
		.map( ( word, i ) =>
			i === 0 && [ 'wp', 'wc' ].includes( word.toLowerCase() ) // Check if it's the first word and matches 'wp' or 'wc'.
				? word.toUpperCase() // Capitalize the first word if it matches 'wp' or 'wc'.
				: word // Keep other words unchanged.
		)
		.join( ' ' ); // Join the words back into a single string with spaces.

	// Convert the string to a specific case type based on the 'type' parameter.
	switch ( type ) {
		case 'domain':
			// Convert spaces to hyphens and make the entire string lowercase (e.g., "My Plugin Name" -> "my-plugin-name").
			str = str.split( ' ' ).join( '-' ).toLowerCase();
			break;
		case 'constant':
			// Convert spaces to underscores and make the entire string uppercase (e.g., "My Plugin Name" -> "MY_PLUGIN_NAME").
			str = str.split( ' ' ).join( '_' ).toUpperCase();
			break;
		case 'function':
			// Convert spaces to underscores and make the entire string lowercase (e.g., "My Plugin Name" -> "my_plugin_name").
			str = str.split( ' ' ).join( '_' ).toLowerCase();
			break;
		case 'class':
			// Convert spaces to underscores (e.g., "My Plugin Name" -> "My_Plugin_Name").
			str = str.split( ' ' ).join( '_' );
			break;
		default:
			// Handle the default case (no conversion) or any other unsupported 'type'.
			break;
	}

	return str;
};

/**
 * Update the package.json file.
 */
const updatePackageJson = async () => {
	// Read the contents of the 'package.json' file.
	let pkg = await fs.readFile( './package.json' );

	// Parse the JSON content of the 'package.json' file into an object.
	pkg = JSON.parse( pkg );

	// Remove specific properties from the parsed 'package.json' object.
	delete pkg.scripts.setup; // Remove the 'setup' script.
	delete pkg.devDependencies.prompts; // Remove 'prompts' from 'devDependencies'.
	delete pkg.devDependencies.ora; // Remove 'ora' from 'devDependencies'.
	delete pkg.devDependencies[ 'replace-in-file' ]; // Remove 'replace-in-file' from 'devDependencies'.

	// Write the modified 'package.json' object back to the file with 2-space indentation.
	await fs.writeFile( './package.json', JSON.stringify( pkg, null, 2 ) );
};

/**
 * Start the asynchronous process.
 */
( async () => {
	const answers = await prompts( {
		type: 'text',
		name: 'name',
		message: 'What will be your Plugin name?',
		validate: ( value ) =>
			value.trim().length && value.trim().match( /^[a-zA-Z ]*$/ )
				? true
				: 'Please provide a valid plugin name. Example: WP Bulk Uploader',
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

		// Read all the files that need to be renamed.
		const files = await scan( './' );
		if ( ! files.length ) {
			throw new Error(
				'Unable to find files for replacements. Please try to reclone the site and run the setup again.'
			);
		}

		// Rename "custom-plugin" word in all the matched files
		// with the new file prefix.
		await renameFiles( files, changeCase( pluginName, 'domain' ) );

		// Replace text in files options.
		const options = {
			files: files.map( ( f ) =>
				f.replace( 'custom-plugin', changeCase( pluginName, 'domain' ) )
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

		// Update package.json.
		await updatePackageJson();

		// Delete unnecessary files.
		del( [ 'setup.mjs', '.git', 'README.md' ] );

		spinner.succeed( 'Complete!' );

		// Resolve the promise at the end.
		Promise.resolve();
	} catch ( err ) {
		spinner.fail( 'Failed!' );
		console.error( 'Runtime Exception:', err.message ); // Print only the error message
		console.error( 'Stack Trace:', err.stack ); // Print the full stack trace for debugging
		process.exitCode = 7; // Set the exit code to indicate an error
	}
} )();
