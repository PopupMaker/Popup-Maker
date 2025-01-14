import { __, sprintf } from '@wordpress/i18n';
import { select } from '@wordpress/data-controls';

import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { Status, Statuses } from '../constants';

import {
	getResourcePath,
	convertApiCallToAction,
	convertCallToActionToApi,
} from './utils';
import { validateCallToAction } from './validation';
import { ACTION_TYPES, STORE_NAME } from './constants';

import type { AppNotice, EditorId } from '../types';

import type {
	CallToAction,
	ApiCallToAction,
	CallToActionsState,
	CallToActionsStore,
} from './types';
import { getEditorId } from './selectors';

const {
	CREATE,
	DELETE,
	UPDATE,
	HYDRATE,
	ADD_NOTICE,
	CLEAR_NOTICE,
	CLEAR_NOTICES,
	CHANGE_ACTION_STATUS,
	EDITOR_CHANGE_ID,
	EDITOR_CLEAR_DATA,
	EDITOR_UPDATE_VALUES,
} = ACTION_TYPES;

/**
 * Change status of a dispatch action request.
 *
 * @param {CallToActionsStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                            status     New status.
 * @param {string|undefined}                    message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus = (
	actionName: CallToActionsStore[ 'ActionNames' ],
	status: Statuses,
	message?: string | { message: string; [ key: string ]: any }
): {
	type: string;
	actionName: CallToActionsStore[ 'ActionNames' ];
	status: Statuses;
	message?: string | { message: string; [ key: string ]: any };
} => {
	if ( message ) {
		// eslint-disable-next-line no-console
		console.log( actionName, message );
	}

	return {
		type: CHANGE_ACTION_STATUS,
		actionName,
		status,
		message,
	};
};

/**
 * Add notice to the editor.
 *
 * @param {AppNotice} notice Notice to display.
 *
 * @return {Object} Action object.
 */
export const addNotice = (
	notice: AppNotice
): { type: string; notice: AppNotice } => {
	return {
		type: ADD_NOTICE,
		notice,
	};
};

/**
 * Clear notice from the editor.
 *
 * @param {AppNotice[ 'id' ]} noticeId Id of the notice to clear.
 *
 * @return {Object} Action object.
 */
export const clearNotice = (
	noticeId: AppNotice[ 'id' ]
): { type: string; noticeId: string } => {
	return {
		type: CLEAR_NOTICE,
		noticeId,
	};
};

/**
 * Clear all notices from the editor.
 *
 * @return {Object} Action object.
 */
export const clearNotices = (): { type: string } => {
	return {
		type: CLEAR_NOTICES,
	};
};

/**
 * Changes the editor to edit the new item by id.
 *
 * @param {number|"new"|undefined} editorId Id of the item to be edited.
 *
 * @return {Object} Action to be dispatched.
 */
export function* changeEditorId(
	editorId: CallToActionsState[ 'editor' ][ 'id' ]
): Generator {
	// catch any request errors.
	try {
		if ( typeof editorId === 'undefined' ) {
			return {
				type: EDITOR_CHANGE_ID,
				editorId: undefined,
				editorValues: undefined,
			};
		}

		const callToActionDefaults = yield* select(
			STORE_NAME,
			'getCallToActionDefaults'
		);

		let callToAction: CallToAction | undefined =
			editorId === 'new' ? callToActionDefaults : undefined;

		if ( typeof editorId === 'number' && editorId > 0 ) {
			callToAction = yield* select(
				STORE_NAME,
				'getCallToAction',
				editorId
			);
		}

		return {
			type: EDITOR_CHANGE_ID,
			editorId,
			editorValues: callToAction,
		};
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.log( error );
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			'changeEditorId',
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Update value of the current editor data.
 *
 * @param {Partial< CallToAction >} editorValues Values to update.
 * @return {Object} Action to be dispatched.
 */
export const updateEditorValues = (
	editorValues: Partial< CallToAction >
): { type: string; editorValues: Partial< CallToAction > } => {
	return {
		type: EDITOR_UPDATE_VALUES,
		editorValues,
	};
};

/**
 * Clear the current editor data.
 *
 * @return {Object} Action to be dispatched.
 */
export const clearEditorData = (): { type: string } => {
	return {
		type: EDITOR_CLEAR_DATA,
	};
};

/**
 * Update a call to action.
 *
 * @param {CallToAction} callToAction Call to action to be updated.
 * @return {Generator} Action to be dispatched.
 */
export function* createCallToAction( callToAction: CallToAction ): Generator {
	const actionName = 'createCallToAction';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const { id, ...noIdCallToAction } = callToAction;

		// Validate the call to action.
		const validation = validateCallToAction( callToAction );

		if ( true !== validation ) {
			yield changeActionStatus(
				actionName,
				Status.Error,
				validation
					? validation
					: __( 'An error occurred, call to action was not saved.' )
			);

			return addNotice( {
				id: 'call-to-action-error',
				type: 'error',
				message:
					typeof validation === 'object' ? validation.message : '',
				closeDelay: 5000,
			} );
		}

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const result = ( yield fetch( getResourcePath(), {
			method: 'POST',
			body: convertCallToActionToApi( noIdCallToAction ),
		} ) ) as ApiCallToAction;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			const editorId = yield* select( STORE_NAME, 'getEditorId' );

			const returnAction = {
				type: CREATE,
				callToAction: convertApiCallToAction( result ),
			};

			if ( editorId === 'new' ) {
				yield returnAction;
				// Change editor ID to continue editing.
				yield changeEditorId( result.id );
				return;
			}

			yield addNotice( {
				id: 'call-to-action-saved',
				type: 'success',
				message: sprintf(
					// translators: %s: call to action title.
					__(
						'Call to action "%s" saved successfully.',
						'popup-maker'
					),
					callToAction?.title
				),
				closeDelay: 5000,
			} );

			return returnAction;
		}

		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, popup was not saved.', 'popup-maker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Save current editor values.
 *
 * @return {Generator} Action to be dispatched.
 */
export function* saveEditorValues(): Generator {
	const values = yield* select( STORE_NAME, 'getEditorValues' );
	const editorId = yield* select( STORE_NAME, 'getEditorId' ) ?? 'new';

	if ( ! values ) {
		return;
	}

	const exists = Number( editorId ) > 0;

	const valuesToSave = {
		...values,
		settings: {
			...values.settings,
		},
	};

	if ( exists ) {
		yield* updateCallToAction( valuesToSave );
	} else {
		yield* createCallToAction( valuesToSave );
	}
}

/**
 * Update a call to action.
 *
 * @param {CallToAction} callToAction Call to action to be updated.
 * @return {Generator} Action to be dispatched.
 */
export function* updateCallToAction( callToAction: CallToAction ): Generator {
	const actionName = 'updateCallToAction';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// Validate the call to action.
		const validation = validateCallToAction( callToAction );

		if ( true !== validation ) {
			yield changeActionStatus(
				actionName,
				Status.Error,
				validation
					? validation
					: __( 'An error occurred, call to action was not saved.' )
			);

			return addNotice( {
				id: 'call-to-action-error',
				type: 'error',
				message:
					typeof validation === 'object' ? validation.message : '',
				closeDelay: 5000,
			} );
		}

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const canonicalCallToAction = yield* select(
			STORE_NAME,
			'getCallToAction',
			callToAction.id
		);

		if ( ! canonicalCallToAction ) {
			return changeActionStatus(
				actionName,
				Status.Error,
				__( 'Call to action not found.', 'popup-maker' )
			);
		}

		const result = ( yield fetch(
			getResourcePath( canonicalCallToAction.id ),
			{
				method: 'POST',
				body: convertCallToActionToApi( callToAction ),
			}
		) ) as ApiCallToAction;

		if ( result ) {
			// call to action was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			yield addNotice( {
				id: 'call-to-action-saved',
				type: 'success',
				message: sprintf(
					// translators: %s: call to action title.
					__(
						'Call to action "%s" saved successfully.',
						'popup-maker'
					),
					callToAction?.title
				),
				closeDelay: 5000,
			} );

			return {
				type: UPDATE,
				callToAction: convertApiCallToAction( result ),
			};
		}

		// if execution arrives here, then call to action didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__(
				'An error occurred, call to action was not saved.',
				'popup-maker'
			)
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Delete a call to action from the store.
 *
 * @param {number}  callToActionId Call to action ID.
 * @param {boolean} forceDelete    Whether to trash or force delete.
 * @return {Generator} Delete Action.
 */
export function* deleteCallToAction(
	callToActionId: CallToAction[ 'id' ],
	forceDelete: boolean = false
): Generator {
	const actionName = 'deleteCallToAction';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const callToAction = yield* select(
			STORE_NAME,
			'getCallToAction',
			callToActionId
		);

		if ( ! callToAction ) {
			return changeActionStatus(
				actionName,
				Status.Error,
				__( 'Call to action not found.', 'popup-maker' )
			);
		}

		const force = forceDelete ? '?force=true' : '';
		const path = getResourcePath( callToAction.id ) + force;

		const result = ( yield fetch( path, {
			method: 'DELETE',
		} ) ) as boolean;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return forceDelete
				? {
						type: DELETE,
						callToActionId,
				  }
				: {
						type: UPDATE,
						callToAction: { ...callToAction, status: 'trash' },
				  };
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__(
				'An error occurred, call to action was not deleted.',
				'popup-maker'
			)
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Hyrdate the call to action store.
 *
 * @param {CallToAction[]} callToActions Array of call to actions.
 * @return {Object} Action.
 */
export const hydrate = (
	callToActions: CallToAction[]
): { type: string; callToActions: CallToAction[] } => {
	return {
		type: HYDRATE,
		callToActions,
	};
};
