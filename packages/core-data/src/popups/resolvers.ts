import {
	appendUrlParams,
	fetchFromApi,
	//getErrorMessage
} from '../utils';
import { RECEIVE_RECORDS, RECEIVE_RECORD } from './constants';
import type { ReducerAction } from './reducer';

import type { Popup, ThunkAction } from './types';

export const getPopups =
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
					type: RECEIVE_RECORDS,
					payload: {
						records: results,
					},
				} as ReducerAction );
				// dispatch.finishResolution( action );
			}

			// dispatch.failResolution( action, 'No call to actions found' );
		} catch ( error: any ) {
			// const errorMessage = getErrorMessage( error );
			// eslint-disable-next-line no-console
			console.error( error );
			// dispatch.failResolution( action, errorMessage );
		}
	};

export const getPopup =
	( id: number ): ThunkAction =>
	async ( { dispatch } ) => {
		// const action = 'getById';
		try {
			// dispatch.startResolution( action );

			const url = appendUrlParams( `popups/${ id }`, {
				context: 'edit',
			} );

			const record = await fetchFromApi< Popup< 'edit' > >( url, {
				method: 'GET',
			} );

			dispatch( {
				type: RECEIVE_RECORD,
				payload: {
					record,
				},
			} as ReducerAction );

			// dispatch.finishResolution( action );
		} catch ( error: any ) {
			// const errorMessage = getErrorMessage( error );
			// eslint-disable-next-line no-console
			console.error( error );
			// dispatch.failResolution( action, errorMessage );
		}
	};

export default {
	getPopups,
	getPopup,
};
