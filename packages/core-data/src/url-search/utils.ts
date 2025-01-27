import { __ } from '@popup-maker/i18n';
import { appendUrlParams, fetchFromApi } from '../utils';

import type {
	SearchArgs,
	SearchOptions,
	WPLinkAPIResult,
	WPLinkSearchResult,
} from './types';

export const apiQueryUrl = ( queryParams: SearchArgs = { search: '' } ) =>
	appendUrlParams( 'wp/v2/search', queryParams );

/**
 * Fetch link suggestions from the WordPress API
 */
export const fetchLinkSuggestions = async (
	search: string,
	searchOptions: SearchOptions = {}
): Promise< WPLinkSearchResult[] > => {
	const {
		isInitialSuggestions = false,
		type = undefined,
		subtype = undefined,
		page = undefined,
		perPage = isInitialSuggestions ? 3 : 20,
	} = searchOptions;

	const queries: Promise< WPLinkAPIResult[] >[] = [];

	const typeCheck = ( t: string ) =>
		! type ||
		type === t ||
		( Array.isArray( type ) && type.indexOf( t ) >= 0 );

	// Helper function to fetch and transform results
	const fetchResults = async ( queryType?: string, kind?: string ) => {
		try {
			const results = await fetchFromApi< WPLinkAPIResult[] >(
				apiQueryUrl( {
					search,
					page,
					per_page: perPage,
					type: queryType,
					subtype,
				} ),
				{ cache: 'no-cache' }
			);

			return results.map( ( result ) => ( {
				...result,
				meta: { kind: kind || queryType, subtype },
			} ) );
		} catch {
			return []; // Fail by returning no results
		}
	};

	// Fetch posts
	if ( typeCheck( 'post' ) ) {
		queries.push( fetchResults( 'post', 'post-type' ) );
	}

	// Fetch terms
	if ( typeCheck( 'term' ) ) {
		queries.push( fetchResults( 'term', 'taxonomy' ) );
	}

	// Fetch post formats
	if ( typeCheck( 'post-format' ) ) {
		queries.push( fetchResults( 'post-format', 'taxonomy' ) );
	}

	// Fetch attachments
	if ( typeCheck( 'attachment' ) ) {
		queries.push( fetchResults( 'attachment', 'media' ) );
	}

	const results = await Promise.all( queries );

	return results
		.flat()
		.filter( ( result ): result is WPLinkAPIResult => !! result.id )
		.slice( 0, perPage )
		.map( ( result ) => {
			const isMedia = result.type === 'attachment';
			const title =
				typeof result.title === 'object'
					? result.title.rendered ?? result.title.raw
					: result.title || __( '(no title)', 'popup-maker' );

			return {
				id: result.id,
				url:
					isMedia && result.source_url
						? result.source_url
						: result.url,
				title,
				type: result?.subtype || result.type,
				kind: result?.meta?.kind,
			} as WPLinkSearchResult;
		} );
};
