import {
	appendUrlParams,
	fetchFromApi,
	//getErrorMessage
} from '../utils';
import { RECIEVE_RECORDS, RECIEVE_RECORD } from './constants';

import type { Popup, ThunkAction } from './types';

const entityResolvers = {
	getPopups:
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

				const url = appendUrlParams( 'popups', urlParams );

				const results = await fetchFromApi< Popup< 'edit' >[] >( url, {
					method: 'GET',
				} );

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

	getPopup:
		( id: number ): ThunkAction =>
		async ( { dispatch } ) => {
			// const action = 'getById';

			try {
				// dispatch.startResolution( action );

				const url = appendUrlParams( `ctas/${ id }`, {
					context: 'edit',
				} );

				const record = await fetchFromApi< Popup< 'edit' > >( url, {
					method: 'GET',
				} );

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
