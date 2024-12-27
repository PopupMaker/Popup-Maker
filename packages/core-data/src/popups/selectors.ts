import { applyFilters } from '@wordpress/hooks';

import { Status } from '../constants';
import { popupDefaults } from './constants';

import type { Statuses } from '../constants';

import type { AppNotice, EditorId } from '../types';
import type { Popup, PopupsState, PopupsStore } from './types';

/**
 * Get notices.
 *
 * @param {PopupsState} state Current state.
 *
 * @return {AppNotice[]} Notices.
 */
export const getNotices = ( state: PopupsState ): AppNotice[] =>
	state.notices || [];

/**
 * Get all popups.
 *
 * @param {PopupsState} state Current state.
 *
 * @return {Popup[]} Popups.
 */
export const getPopups = ( state: PopupsState ): Popup[] => state.popups || [];

/**
 * Get current values for given popup id.
 *
 * @param {PopupsState} state Current state.
 * @param {number}      id    Popup ID.
 *
 * @return {Popup} Popup.
 */
export const getPopup = (
	state: PopupsState,
	id: Popup[ 'id' ] | null | undefined
): Popup | undefined => getPopups( state ).find( ( popup ) => popup.id === id );

/**
 * Check if the editor is active.
 *
 * @param {PopupsState} state Current state.
 *
 * @return {boolean} If editor is active.
 */
export const isEditorActive = ( state: PopupsState ): boolean => {
	const editorId = state?.editor?.id;

	if ( typeof editorId === 'string' && editorId === 'new' ) {
		return true;
	}

	return typeof editorId === 'number' && editorId > 0;
};

/**
 * Check if the editor is active.
 *
 * @param {PopupsState} state Current state.
 *
 * @return {number|"new"|undefined} If editor is active.
 */
export const getEditorId = ( state: PopupsState ): EditorId =>
	state?.editor?.id;

/**
 * Get current editor values.
 *
 * @param {PopupsState} state Current state.
 *
 * @return {Popup|undefined} If editor is active.
 */
export const getEditorValues = (
	state: PopupsState
): PopupsState[ 'editor' ][ 'values' ] => state?.editor?.values;

/**
 * Get the next priority number for new popup.
 *
 * @param {PopupsState} state Current state.
 * @return {number} Next priority number.
 */
export const getNextPriority = ( state: PopupsState ): number => {
	const popups = getPopups( state );

	if ( popups.length === 0 ) {
		return 0;
	}

	// Get popup with lowest priority (higher number = lower priority).
	const lowestPriority = popups.reduce( ( lowest, popup ) => {
		if ( popup.priority > lowest ) {
			return popup.priority;
		}

		return lowest;
	}, 0 );

	return lowestPriority + 1;
};

export const getPopupDefaults = ( state: PopupsState ): Popup => {
	const priority = getNextPriority( state );

	const defaults = applyFilters(
		'popupMaker.defaultPopupValues',
		popupDefaults
	) as Popup;

	return {
		...defaults,
		priority,
	};
};

/**
 * Get current status for dispatched action.
 *
 * @param {PopupsState}                state      Current state.
 * @param {PopupsStore['ActionNames']} actionName Action name to check.
 *
 * @return {string} Current status for dispatched action.
 */
export const getDispatchStatus = (
	state: PopupsState,
	actionName: PopupsStore[ 'ActionNames' ]
): Statuses | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 *
 * @param {PopupsState}                                             state       Current state.
 * @param {PopupsStore['ActionNames']|PopupsStore['ActionNames'][]} actionNames Action name or array of names to check.
 *
 * @return {boolean} True if is dispatching.
 */
export const isDispatching = (
	state: PopupsState,
	actionNames: PopupsStore[ 'ActionNames' ] | PopupsStore[ 'ActionNames' ][]
): boolean => {
	if ( ! Array.isArray( actionNames ) ) {
		return getDispatchStatus( state, actionNames ) === Status.Resolving;
	}

	let dispatching = false;

	for ( let i = 0; actionNames.length > i; i++ ) {
		dispatching =
			getDispatchStatus( state, actionNames[ i ] ) === Status.Resolving;

		if ( dispatching ) {
			return true;
		}
	}

	return dispatching;
};

/**
 * Check if action has finished dispatching.
 *
 * @param {PopupsState}                state      Current state.
 * @param {PopupsStore['ActionNames']} actionName Action name to check.
 *
 * @return {boolean} True if dispatched.
 */
export const hasDispatched = (
	state: PopupsState,
	actionName: PopupsStore[ 'ActionNames' ]
): boolean => {
	const status = getDispatchStatus( state, actionName );

	return !! (
		status &&
		( [ Status.Success, Status.Error ] as string[] ).indexOf( status ) >= 0
	);
};

/**
 * Get dispatch action error if esists.
 *
 * @param {PopupsState}                state      Current state.
 * @param {PopupsStore['ActionNames']} actionName Action name to check.
 *
 * @return {string|undefined} Current error message.
 */
export const getDispatchError = (
	state: PopupsState,
	actionName: PopupsStore[ 'ActionNames' ]
): string | { message: string; [ key: string ]: any } | undefined =>
	state?.dispatchStatus?.[ actionName ]?.error;
