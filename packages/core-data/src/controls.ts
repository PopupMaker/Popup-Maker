export const restBase = 'popup-maker/v2';

export const restUrl = `${ wpApiSettings.root }${ restBase }/`;

type FetchOptions = { [ key: string ]: any };

type FetchArgs = {
	path: string;
	options: { [ key: string ]: any };
};

/**
 * Fetch opts with creds.
 *
 * @param {FetchArgs[ 'options' ]} options Fetch options.
 * @return {FetchArgs[ 'options' ]} Modified fetch options.
 */
export const fetchCredOpts = ( options: FetchOptions = {} ): RequestInit => ( {
	...options,
	headers: {
		...options.headers,
		'Content-Type': 'application/json',
		'X-WP-Nonce': wpApiSettings.nonce,
	},
	// ensures things work with cors.
	credentials: 'same-origin',
} );

/**
 * Fetch with credentials.
 *
 * @param {FetchArgs[ 'path' ]}    path    Path to fetch.
 * @param {FetchArgs[ 'options' ]} options Fetch options.
 * @return {Object} fetch action.
 */
export const fetch = (
	path: string,
	options: FetchOptions = {}
): {
	type: 'FETCH';
	path: string;
	options: RequestInit;
} => {
	if ( options.body ) {
		options.body = JSON.stringify( options.body );
	}

	if ( wpApiSettings.root.includes( '?' ) ) {
		path = path.replace( '?', '&' );
	}

	return {
		type: 'FETCH',
		path: `${ wpApiSettings.root }${ path }`,
		options: fetchCredOpts( options ),
	};
};

/**
 * Fetch with credentials.
 *
 * @param {FetchArgs[ 'path' ]}    path    Path to fetch.
 * @param {FetchArgs[ 'options' ]} options Fetch options.
 * @return {Promise} fetch promise.
 */
export const fetchWithCreds = (
	path: FetchArgs[ 'path' ],
	options: FetchArgs[ 'options' ]
): Promise< any > => {
	return new Promise( ( resolve, reject ) => {
		window
			.fetch( path, fetchCredOpts( options ) )
			.then( ( response ) => response.json() )
			.then( ( result ) => resolve( result ) )
			.catch( ( error ) => reject( error ) );
	} );
};

export default {
	/**
	 * Fetch with credentials.
	 *
	 * @param {FetchArgs} args Fetch args.
	 * @return {Promise} fetch promise.
	 */
	FETCH( { path, options }: FetchArgs ): Promise< any > {
		return new Promise( ( resolve, reject ) => {
			window
				.fetch( path, options )
				.then( ( response ) => response.json() )
				.then( ( result ) => resolve( result ) )
				.catch( ( error ) => reject( error ) );
		} );
	},
};
