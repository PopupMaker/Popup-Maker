/**
 * Append params to url.
 *
 * @param {string} url    Url to append params to.
 * @param {Object} params Object of url parameters.
 * @return {string} Resulting resource path.
 */
export const appendUrlParams = ( url: string, params: object ) => {
	const filteredParams = Object.fromEntries(
		Object.entries( params ).filter( ( [ , value ] ) => !! value )
	);

	const query = new URLSearchParams( {
		...filteredParams,
	} );

	return `${ url }?${ query }`;
};

/**
 * Gets error message from unknonw error type.
 *
 * @param {unknown} error Error typeed variable or string.
 * @return {string} String error message.
 */
export const getErrorMessage = ( error: unknown ): string => {
	if ( error instanceof Error ) {
		return error.message;
	}

	return String( error );
};
