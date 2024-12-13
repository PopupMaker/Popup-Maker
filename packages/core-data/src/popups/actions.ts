import { __, sprintf } from '@wordpress/i18n';
import { select } from '@wordpress/data-controls';

import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { Status, Statuses } from '../constants';

import { getResourcePath, convertApiPopup, convertPopupToApi } from './utils';
import { validatePopup } from './validation';
import { ACTION_TYPES, STORE_NAME } from './constants';

import type {
	AppNotice,
	EditorId,
	Popup,
	ApiPopup,
	PopupsState,
	PopupsStore,
} from './types';

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
 * @param {PopupsStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                     status     New status.
 * @param {string|undefined}             message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus = (
	actionName: PopupsStore[ 'ActionNames' ],
	status: Statuses,
	message?: string | { message: string; [ key: string ]: any }
): {
	type: string;
	actionName: PopupsStore[ 'ActionNames' ];
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
	editorId: PopupsState[ 'editor' ][ 'id' ]
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

		const popupDefaults = yield select( STORE_NAME, 'getPopupDefaults' );

		let popup: Popup | undefined =
			editorId === 'new' ? popupDefaults : undefined;

		if ( typeof editorId === 'number' && editorId > 0 ) {
			popup = yield select( STORE_NAME, 'getPopup', editorId );
		}

		return {
			type: EDITOR_CHANGE_ID,
			editorId,
			editorValues: popup,
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
 * @param {Partial< Popup >} editorValues Values to update.
 * @return {Object} Action to be dispatched.
 */
export const updateEditorValues = (
	editorValues: Partial< Popup >
): { type: string; editorValues: Partial< Popup > } => {
	return {
		type: EDITOR_UPDATE_VALUES,
		editorValues,
	};
};

/**
 * Update value of the current editor data.
 *
 * @return {Object} Action to be dispatched.
 */
export const clearEditorData = (): { type: string } => {
	return {
		type: EDITOR_CLEAR_DATA,
	};
};

/**
 * Update a popup.
 *
 * @param {Popup} popup Popup to be updated.
 * @return {Generator} Action to be dispatched.
 */
export function* createPopup( popup: Popup ): Generator {
	const actionName = 'createPopup';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const { id, ...noIdPopup } = popup;

		// Validate the popup.
		const validation = validatePopup( popup );

		if ( true !== validation ) {
			yield changeActionStatus(
				actionName,
				Status.Error,
				validation
					? validation
					: __( 'An error occurred, popup was not saved.' )
			);

			return addNotice( {
				id: 'popup-error',
				type: 'error',
				message:
					typeof validation === 'object' ? validation.message : '',
				closeDelay: 5000,
			} );
		}

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const result: ApiPopup = yield fetch( getResourcePath(), {
			method: 'POST',
			body: convertPopupToApi( noIdPopup ),
		} );

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			const editorId: EditorId = yield select(
				STORE_NAME,
				'getEditorId'
			);

			const returnAction = {
				type: CREATE,
				popup: convertApiPopup( result ),
			};

			if ( editorId === 'new' ) {
				yield returnAction;
				// Change editor ID to continue editing.
				yield changeEditorId( result.id );
				return;
			}

			yield addNotice( {
				id: 'popup-saved',
				type: 'success',
				message: sprintf(
					// translators: %s: popup title.
					__( 'Popup "%s" saved successfully.', 'popup-maker' ),
					popup?.title
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
 * Update a popup.
 *
 * @param {Popup} popup Popup to be updated.
 * @return {Generator} Action to be dispatched.
 */
export function* updatePopup( popup: Popup ): Generator {
	const actionName = 'updatePopup';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// Validate the popup.
		const validation = validatePopup( popup );

		if ( true !== validation ) {
			yield changeActionStatus(
				actionName,
				Status.Error,
				validation
					? validation
					: __( 'An error occurred, popup was not saved.' )
			);

			return addNotice( {
				id: 'popup-error',
				type: 'error',
				message:
					typeof validation === 'object' ? validation.message : '',
				closeDelay: 5000,
			} );
		}

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const canonicalPopup: Popup = yield select(
			STORE_NAME,
			'getPopup',
			popup.id
		);

		const result: ApiPopup = yield fetch(
			getResourcePath( canonicalPopup.id ),
			{
				method: 'POST',
				body: convertPopupToApi( popup ),
			}
		);

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			yield addNotice( {
				id: 'popup-saved',
				type: 'success',
				message: sprintf(
					// translators: %s: popup title.
					__( 'Popup "%s" saved successfully.', 'popup-maker' ),
					popup?.title
				),
				closeDelay: 5000,
			} );

			return {
				type: UPDATE,
				popup: convertApiPopup( result ),
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
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
 * Delete a popup from the store.
 *
 * @param {number}  popupId     Popup ID.
 * @param {boolean} forceDelete Whether to trash or force delete.
 * @return {Generator} Delete Action.
 */
export function* deletePopup(
	popupId: Popup[ 'id' ],
	forceDelete: boolean = false
): Generator {
	const actionName = 'deletePopup';

	// catch any request errors.
	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const popup: Popup = yield select( STORE_NAME, 'getPopup', popupId );

		const force = forceDelete ? '?force=true' : '';
		const path = getResourcePath( popup.id ) + force;

		const result: boolean = yield fetch( path, {
			method: 'DELETE',
		} );

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return forceDelete
				? {
						type: DELETE,
						popupId,
				  }
				: {
						type: UPDATE,
						popup: { ...popup, status: 'trash' },
				  };
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, popup was not deleted.', 'popup-maker' )
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
 * Hyrdate the popup store.
 *
 * @param {Popup[]} popups Array of popups.
 * @return {Object} Action.
 */
export const hydrate = (
	popups: Popup[]
): { type: string; popups: Popup[] } => {
	return {
		type: HYDRATE,
		popups,
	};
};
