const WPDependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const packages = require( '../assets/packages' );

const POPUPMAKER_NAMESPACE = '@popup-maker/';

/**
 * Given a string, returns a new string with dash separators converted to
 * camelCase equivalent. This is not as aggressive as `_.camelCase` in
 * converting to uppercase, where Lodash will also capitalize letters
 * following numbers.
 *
 * @param {string} string Input dash-delimited string.
 *
 * @return {string} Camel-cased string.
 */
function camelCaseDash( string ) {
	return string.replace( /-([a-z])/g, ( _, letter ) => letter.toUpperCase() );
}

const popupMakerRequestToExternal = ( request, excludedExternals ) => {
	if ( packages.includes( request ) ) {
		const handle = request.substring( POPUPMAKER_NAMESPACE.length );
		const irregularExternalMap = {
			// Used to map irregular package names to script handles.
			frontend: [ 'popupMaker', 'frontend' ],
		};

		if ( ( excludedExternals || [] ).includes( request ) ) {
			return;
		}

		if ( irregularExternalMap[ handle ] ) {
			return irregularExternalMap[ handle ];
		}

		return [ 'popupMaker', camelCaseDash( handle ) ];
	}
};

const popupMakerRequestToHandle = ( request ) => {
	if ( packages.includes( request ) ) {
		const handle = request.substring( POPUPMAKER_NAMESPACE.length );
		const irregularHandleMap = {
			// Used to map irregular package names to script handles.
			// ex. 'csv-export': 'wc-csv',
		};

		if ( irregularHandleMap[ handle ] ) {
			return irregularHandleMap[ handle ];
		}

		return 'popup-maker-' + handle;
	}
};

class DependencyExtractionWebpackPlugin extends WPDependencyExtractionWebpackPlugin {
	externalizeWpDeps( { context, request }, cb ) {
		let externalRequest;

		// Handle via options.requestToExternal first
		if ( typeof this.options.requestToExternal === 'function' ) {
			externalRequest = this.options.requestToExternal( request );
		}

		// Cascade to default if unhandled and enabled
		if (
			typeof externalRequest === 'undefined' &&
			this.options.useDefaults
		) {
			externalRequest = popupMakerRequestToExternal(
				request,
				this.options.bundledPackages || []
			);
		}

		if ( externalRequest ) {
			this.externalizedDeps.add( request );
			cb( null, externalRequest );
			return;
		}

		// Fall back to the WP method
		super.externalizeWpDeps( { context, request }, cb );
	}

	mapRequestToDependency( request ) {
		// Handle via options.requestToHandle first
		if ( typeof this.options.requestToHandle === 'function' ) {
			const scriptDependency = this.options.requestToHandle( request );
			if ( scriptDependency ) {
				return scriptDependency;
			}
		}

		// Cascade to default if enabled
		if ( this.options.useDefaults ) {
			const scriptDependency = popupMakerRequestToHandle( request );
			if ( scriptDependency ) {
				return scriptDependency;
			}
		}

		return super.mapRequestToDependency( request );
	}
}

module.exports = DependencyExtractionWebpackPlugin;
