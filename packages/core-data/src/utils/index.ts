export { default as createNoticeActions } from './notice-actions';
export { default as createNoticeSelectors } from './notice-selectors';
export { default as createPostTypeActions } from './entity-actions';
export { default as createPostTypeSelectors } from './entity-selectors';
export type { StoreDescriptor as NoticeStoreDescriptor } from './entity-types';
export type { StoreDescriptor as EntityStoreDescriptor } from './entity-types';
export * from './controls';

/**
 * Append params to url.
 *
 * @param {string} url    Url to append params to.
 * @param {Object} params Object of url parameters.
 * @return {string} Resulting resource path.
 */
export const appendUrlParams = ( url: string, params: object ): string => {
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
