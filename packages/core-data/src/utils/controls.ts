export const restBase = 'popup-maker/v2';

export const restUrl = `${ wpApiSettings.root }${ restBase }/`;

/**
 * Creates default headers with authentication and content type
 */
const getDefaultHeaders = (): HeadersInit => ( {
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
 * @param {string} path - The API endpoint path
 * @param {RequestInit} [options={}] - Fetch options (method, body, etc.)
 * @return {Promise<any>} Promise that resolves with the JSON response
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
	options: RequestInit = {}
): Promise< T > => {
	// Prepare the request body
	if ( options.body && typeof options.body === 'object' ) {
		options.body = JSON.stringify( options.body );
	}

	// Combine default and custom headers
	const headers = {
		...getDefaultHeaders(),
		...options.headers,
	};

	// Prepare the full request configuration
	const fetchOptions: RequestInit = {
		...options,
		headers,
		credentials: 'same-origin',
	};

	// Modify path if needed and add API root
	const modifiedPath = getModifiedPath( path );
	const fullPath = `${ wpApiSettings.root }${ modifiedPath }`;

	// Perform the fetch
	const response = await window.fetch( fullPath, fetchOptions );

	// Handle non-OK responses
	if ( ! response.ok ) {
		const error = await response.json().catch( () => ( {
			message: response.statusText || 'Unknown error',
		} ) );
		throw new Error( error.message || 'API request failed' );
	}

	// Parse and return the JSON response
	return response.json() as Promise< T >;
};
