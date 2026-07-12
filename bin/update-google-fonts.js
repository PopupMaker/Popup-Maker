#!/usr/bin/env node

const fs = require( 'fs' );
const path = require( 'path' );
const https = require( 'https' );

/**
 * Update Google Fonts JSON with latest API data
 *
 * This script fetches the latest font metadata from Google Fonts API
 * and updates the local google-fonts.json file used by Popup Maker.
 */

// Key comes from the environment, never source (it ships with the repo).
const API_KEY = process.env.GOOGLE_FONTS_API_KEY;
const FONTS_JSON_PATH = path.join( __dirname, '../includes/google-fonts.json' );

if ( ! API_KEY ) {
	console.error(
		'❌ Missing GOOGLE_FONTS_API_KEY environment variable. ' +
			'Set it to a restricted Google Fonts API key before running this script.'
	);
	process.exit( 1 );
}

/**
 * Fetch data from Google Fonts API
 */
function fetchGoogleFonts() {
	return new Promise( ( resolve, reject ) => {
		const apiUrl = `https://www.googleapis.com/webfonts/v1/webfonts?key=${ API_KEY }&sort=popularity`;
		console.log( '📡 Fetching from Google Fonts API...' );

		fetchFromUrl( apiUrl ).then( resolve ).catch( reject );
	} );
}

/**
 * Fetch from official API endpoint
 */
function fetchFromUrl( url ) {
	return new Promise( ( resolve, reject ) => {
		https
			.get( url, ( res ) => {
				let data = '';

				res.on( 'data', ( chunk ) => {
					data += chunk;
				} );

				res.on( 'end', () => {
					if ( res.statusCode === 200 ) {
						try {
							const parsed = JSON.parse( data );
							resolve( parsed );
						} catch ( err ) {
							reject(
								new Error(
									`Failed to parse API response: ${ err.message }`
								)
							);
						}
					} else {
						reject(
							new Error(
								`API request failed with status ${ res.statusCode }: ${ data }`
							)
						);
					}
				} );
			} )
			.on( 'error', ( err ) => {
				reject( new Error( `Network error: ${ err.message }` ) );
			} );
	} );
}

/**
 * Transform Google API format to optimized Popup Maker format
 * Only includes family and variants to reduce file size from 1.5MB to ~75KB
 */
function transformToPopupMakerFormat( apiData ) {
	console.log( '🔄 Transforming data to optimized Popup Maker format...' );

	const transformed = {};

	apiData.items.forEach( ( font ) => {
		transformed[ font.family ] = {
			family: font.family,
			variants: font.variants,
		};
	} );

	console.log( `✅ Transformed ${ Object.keys( transformed ).length } fonts` );
	console.log( `📦 Optimized structure: family + variants only` );
	return transformed;
}

/**
 * Save transformed data to JSON file
 */
function saveToFile( data ) {
	console.log( '💾 Saving to google-fonts.json...' );

	// Create backup of existing file
	if ( fs.existsSync( FONTS_JSON_PATH ) ) {
		const backupPath = FONTS_JSON_PATH.replace(
			'.json',
			`-backup-${ Date.now() }.json`
		);
		fs.copyFileSync( FONTS_JSON_PATH, backupPath );
		console.log( `📦 Backup created: ${ path.basename( backupPath ) }` );
	}

	// Write new data
	fs.writeFileSync( FONTS_JSON_PATH, JSON.stringify( data, null, 2 ) );
	console.log( '✅ google-fonts.json updated successfully' );
}

/**
 * Main execution
 */
async function main() {
	try {
		console.log( '🚀 Starting Google Fonts JSON update...\n' );

		const apiData = await fetchGoogleFonts();
		const transformedData = transformToPopupMakerFormat( apiData );
		saveToFile( transformedData );

		console.log( '\n🎉 Google Fonts JSON update completed!' );
		console.log( `📊 Total fonts: ${ Object.keys( transformedData ).length }` );
		console.log( `📅 Last updated: ${ new Date().toISOString() }` );

		// Calculate file size
		const stats = fs.statSync( FONTS_JSON_PATH );
		const fileSizeKB = ( stats.size / 1024 ).toFixed( 2 );
		console.log( `📏 File size: ${ fileSizeKB } KB (optimized from ~1.5MB)` );
	} catch ( error ) {
		console.error( '\n❌ Error updating Google Fonts JSON:' );
		console.error( `   ${ error.message }` );
		process.exit( 1 );
	}
}

// Run if called directly
if ( require.main === module ) {
	main();
}

module.exports = { fetchGoogleFonts, transformToPopupMakerFormat, saveToFile };
