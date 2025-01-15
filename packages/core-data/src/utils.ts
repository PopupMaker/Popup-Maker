import { resolveSelect as wpResolveSelect } from '@wordpress/data';
import type { StoreKeys, StoreSelectors } from './types';

/**
 * Custom exported `resolveSelect` with the same type as `select` from `@wordpress/data`.
 *
 * @param storeName The name of the store.
 * @param selectorName The name of the selector.
 * @param args Arguments for the selector function.
 * @returns The selector's return value.
 */
export function resolveSelect<
	K extends StoreKeys,
	S extends keyof StoreSelectors< K >,
	R = StoreSelectors< K >[ S ] extends ( ...args: any[] ) => infer T ? T : never
>(
	storeName: K,
	selectorName: S,
	...args: StoreSelectors< K >[ S ] extends ( ...args: infer P ) => any
		? P
		: never
): R {
	return wpResolveSelect( storeName, selectorName, ...args ) as R;
}

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
