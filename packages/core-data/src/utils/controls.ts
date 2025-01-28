import apiFetch, { type APIFetchOptions } from '@wordpress/api-fetch';

export const restBase = 'popup-maker/v2';

export const restUrl = `${ wpApiSettings.root }${ restBase }/`;

type WPError = {
	code?: string;
	message?: string;
	data?: {
		status?: number;
	};
};

/**
 * Creates default headers with authentication and content type
 */
const getDefaultHeaders = (): Record< string, string > => ( {
	'Content-Type': 'application/json',
	'X-WP-Nonce': wpApiSettings.nonce,
} );

/**
 * Handles path modification for WordPress API URLs with existing query parameters
 * @param {string} path The API path to modify if needed
 * @return {string} Modified path
 */
const getModifiedPath = ( path: string ): string => {
	if ( wpApiSettings.root.includes( '?' ) ) {
		return path.replace( '?', '&' );
	}
	return path;
};

/**
 * Fetches data from the WordPress API with proper authentication and error handling
 *
 * @param {string}          path         - The API endpoint path
 * @param {APIFetchOptions} [options={}] - API Fetch options
 * @return {Promise<T>} Promise that resolves with the JSON response
 * @throws {Error} If the fetch fails or returns an error status
 *
 * Example usage:
 *  const saveRecord = (id: string, record: any) => async (dispatch: any) => {
 *      try {
 *          dispatch({ type: 'BEFORE_SAVE', id, record });
 *          const results = await fetchFromApi(`/records/${id}`, {
 *              method: 'POST',
 *              body: record,
 *          });
 *          dispatch({ type: 'AFTER_SAVE', id, results });
 *          return results;
 *      } catch (error) {
 *          dispatch({ type: 'SAVE_ERROR', id, error });
 *          throw error;
 *      }
 *  };
 */
export const fetchFromApi = async < T extends any = any >(
	path: string,
	options: APIFetchOptions = {}
): Promise< T > => {
	// Combine default and custom headers
	const headers: Record< string, string > = {
		...getDefaultHeaders(),
		...( ( options.headers as Record< string, string > ) || {} ),
	};

	// Modify path if needed
	const modifiedPath = getModifiedPath( path );

	try {
		// Use apiFetch instead of window.fetch
		return await apiFetch< T >( {
			url: `${ restUrl }${ modifiedPath }`,
			...options,
			headers,
			credentials: 'same-origin',
			parse: true, // Let apiFetch handle JSON parsing
		} );
	} catch ( error ) {
		// If it's already an Error instance, just throw it
		if ( error instanceof Error ) {
			throw error;
		}

		// Handle WordPress API error format
		if ( typeof error === 'object' && error !== null ) {
			const wpError = error as WPError;
			const message = wpError.message || 'API request failed';
			throw new Error( message );
		}

		// Fallback error
		throw new Error( 'Unknown error occurred' );
	}
};
