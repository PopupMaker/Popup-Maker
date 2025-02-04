import { appendUrlParams, fetchFromApi } from '../utils';
import { RECEIVE_RECORDS, RECEIVE_RECORD } from './constants';

import type { CallToAction, ThunkAction } from './types';

const entityResolvers = {
	getCallToActions:
		(): ThunkAction =>
		async ( { dispatch } ) => {
			try {
				const urlParams = {
					status: [ 'any', 'trash', 'auto-draft' ],
					per_page: 100,
					context: 'edit',
				};

				const url = appendUrlParams( 'ctas', urlParams );

				const results = await fetchFromApi<
					( CallToAction< 'edit' > & { _links: any } )[]
				>( url, {
					method: 'GET',
				} );

				if ( results.length ) {
					dispatch( {
						type: RECEIVE_RECORDS,
						payload: {
							records: results.map(
								// Remove _links from the API record.
								( { _links, ...record } ) => record
							),
						},
					} );
				}
			} catch ( error: any ) {
				// eslint-disable-next-line no-console
				console.error( error );
			}
		},

	getCallToAction:
		( id: number ): ThunkAction =>
		async ( { dispatch } ) => {
			try {
				const url = appendUrlParams( `ctas/${ id }`, {
					context: 'edit',
				} );

				const { _links, ...record } = await fetchFromApi<
					CallToAction< 'edit' > & { _links: any }
				>( url, {
					method: 'GET',
				} );

				dispatch( {
					type: RECEIVE_RECORD,
					payload: {
						record,
					},
				} );
			} catch ( error: any ) {
				// eslint-disable-next-line no-console
				console.error( error );
			}
		},
};

const resolvers = {
	...entityResolvers,
};

export default resolvers;
