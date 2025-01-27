import {
	appendUrlParams,
	fetchFromApi,
	// getErrorMessage
} from '../utils';
import { RECIEVE_RECORDS, RECIEVE_RECORD } from './constants';

import type { CallToAction, ThunkAction } from './types';

const entityResolvers = {
	getCallToActions:
		(): ThunkAction =>
		async ( { dispatch } ) => {
			// const action = 'getAll';

			try {
				// dispatch.startResolution( action );

				const urlParams = {
					status: [ 'any', 'trash', 'auto-draft' ],
					per_page: 100,
					context: 'edit',
				};

				const url = appendUrlParams( 'ctas', urlParams );

				const results = await fetchFromApi< CallToAction< 'edit' >[] >(
					url,
					{
						method: 'GET',
					}
				);

				if ( results.length ) {
					dispatch( {
						type: RECIEVE_RECORDS,
						payload: {
							records: results,
						},
					} );
					// dispatch.finishResolution( action );
				}

				// dispatch.failResolution( action, 'No call to actions found' );
			} catch ( error: any ) {
				// const errorMessage = getErrorMessage( error );
				console.error( error );
				// dispatch.failResolution( action, errorMessage );
			}
		},

	getCallToAction:
		( id: number ): ThunkAction =>
		async ( { dispatch } ) => {
			// const action = 'getById';

			try {
				// dispatch.startResolution( action );

				const url = appendUrlParams( `ctas/${ id }`, {
					context: 'edit',
				} );

				const record = await fetchFromApi< CallToAction< 'edit' > >(
					url,
					{
						method: 'GET',
					}
				);

				dispatch( {
					type: RECIEVE_RECORD,
					payload: {
						record,
					},
				} );

				// dispatch.finishResolution( action );
			} catch ( error: any ) {
				// const errorMessage = getErrorMessage( error );
				console.error( error );
				// dispatch.failResolution( action, errorMessage );
			}
		},
};

const resolvers = {
	...entityResolvers,
};

export default resolvers;
