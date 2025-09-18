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

const API_KEY = 'AIzaSyCjkbFHtpK1fwdqTfACg_wZ9iJ0DtXjqrg';
const FONTS_JSON_PATH = path.join( __dirname, '../includes/google-fonts.json' );

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
 * Transform Google API format to Popup Maker format
 */
function transformToPopupMakerFormat( apiData ) {
	console.log( '🔄 Transforming data to Popup Maker format...' );

	const transformed = {
		kind: apiData.kind,
		items: apiData.items.map( ( font ) => ( {
			family: font.family,
			variants: font.variants,
			subsets: font.subsets,
			version: font.version,
			lastModified: font.lastModified,
			files: font.files,
			category: font.category,
		} ) ),
	};

	console.log( `✅ Transformed ${ transformed.items.length } fonts` );
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
		console.log( `📊 Total fonts: ${ transformedData.items.length }` );
		console.log( `📅 Last updated: ${ new Date().toISOString() }` );
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
