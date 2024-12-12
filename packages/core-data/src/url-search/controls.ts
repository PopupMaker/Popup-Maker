import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { getResourcePath } from './utils';

import type { SearchOptions, WPLinkSearchResult } from './types';

export const fetchLinkSuggestions = (
	search: string,
	searchOptions: SearchOptions = {}
) => {
	return {
		type: 'FETCH_LINK_SUGGESTIONS',
		search,
		searchOptions,
	};
};

export default {
	async FETCH_LINK_SUGGESTIONS( {
		search,
		searchOptions = {},
		settings = { disablePostFormats: false },
	}: {
		search: string;
		searchOptions: SearchOptions;
		settings: { [ key: string ]: any };
	} ): Promise< WPLinkSearchResult[] > {
		const {
			isInitialSuggestions = false,
			type = undefined,
			subtype = undefined,
			page = undefined,
			perPage = isInitialSuggestions ? 3 : 20,
		} = searchOptions;

		const { disablePostFormats = false } = settings;

		const queries: Promise< WPLinkSearchResult[] >[] = [];

		const typeCheck = ( t: string ) =>
			! type ||
			type === t ||
			( Array.isArray( type ) && type.indexOf( t ) >= 0 );

		if ( typeCheck( 'post' ) ) {
			queries.push(
				apiFetch< WPLinkSearchResult[] >( {
					path: getResourcePath( {
						search,
						page,
						per_page: perPage,
						type: 'post',
						subtype,
					} ),
					cache: 'no-cache',
				} )
					.then( ( results ) => {
						return results.map( ( result ) => {
							return {
								...result,
								meta: { kind: 'post-type', subtype },
							};
						} );
					} )
					.catch( () => [] ) // Fail by returning no results.
			);
		}

		if ( typeCheck( 'term' ) ) {
			queries.push(
				apiFetch< WPLinkSearchResult[] >( {
					path: getResourcePath( {
						search,
						page,
						per_page: perPage,
						type: 'term',
						subtype,
					} ),
					cache: 'no-cache',
				} )
					.then( ( results ) => {
						return results.map( ( result ) => {
							return {
								...result,
								meta: { kind: 'taxonomy', subtype },
							};
						} );
					} )
					.catch( () => [] ) // Fail by returning no results.
			);
		}

		if ( ! disablePostFormats && typeCheck( 'post-format' ) ) {
			queries.push(
				apiFetch< WPLinkSearchResult[] >( {
					path: getResourcePath( {
						search,
						page,
						per_page: perPage,
						type: 'post-format',
						subtype,
					} ),
					cache: 'no-cache',
				} )
					.then( ( results ) => {
						return results.map( ( result ) => {
							return {
								...result,
								meta: { kind: 'taxonomy', subtype },
							};
						} );
					} )
					.catch( () => [] ) // Fail by returning no results.
			);
		}

		if ( typeCheck( 'attachment' ) ) {
			queries.push(
				apiFetch< WPLinkSearchResult[] >( {
					path: getResourcePath( {
						search,
						page,
						per_page: perPage,
					} ),
					cache: 'no-cache',
				} )
					.then( ( results ) => {
						return results.map( ( result ) => {
							return {
								...result,
								meta: { kind: 'media' },
							};
						} );
					} )
					.catch( () => [] ) // Fail by returning no results.
			);
		}

		return Promise.all( queries ).then( ( results ) => {
			return results
				.reduce(
					( accumulator, current ) => accumulator.concat( current ), // Flatten list.
					[]
				)
				.filter( ( result ) => {
					return !! result.id;
				} )
				.slice( 0, perPage )
				.map( ( result ) => {
					const isMedia = result.type === 'attachment';

					return {
						id: result.id,
						// @ts-ignore fix when we make this a TS file
						url: isMedia ? result.source_url : result.url,
						title:
							// decodeEntities(
							isMedia
								? // @ts-ignore fix when we make this a TS file
								  result.title.rendered ?? suggestion.title.raw
								: result.title ||
								  '' ||
								  /* ) */ __( '(no title)' ),
						type: result?.subtype || result.type,
						kind: result?.meta?.kind,
					};
				} );
		} );
	},
};
