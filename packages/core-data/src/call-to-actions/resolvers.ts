import { appendUrlParams, fetchFromApi } from '../utils';
import { RECEIVE_RECORDS, RECEIVE_RECORD, RECEIVE_ERROR } from './constants';
import type { ReducerAction } from './reducer';

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
					} as ReducerAction );
				}
			} catch ( error: any ) {
				// eslint-disable-next-line no-console
				console.error( error );
				dispatch( {
					type: RECEIVE_ERROR,
					payload: {
						error: error.message,
					},
				} as ReducerAction );
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
				} as ReducerAction );
			} catch ( error: any ) {
				// eslint-disable-next-line no-console
				console.error( error );
				dispatch( {
					type: RECEIVE_ERROR,
					payload: {
						error: error.message,
						id,
					},
				} as ReducerAction );
			}
		},
};

const resolvers = {
	...entityResolvers,
};

export default resolvers;
