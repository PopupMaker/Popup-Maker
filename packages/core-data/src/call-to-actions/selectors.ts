import { applyFilters } from '@wordpress/hooks';

import { Status } from '../constants';
import { callToActionDefaults } from './constants';

import type { Statuses } from '../constants';

import type { AppNotice, EditorId } from '../types';

import type {
	CallToAction,
	CallToActionsState,
	CallToActionsStore,
} from './types';

/**
 * Get notices.
 *
 * @param {CallToActionsState} state Current state.
 *
 * @return {AppNotice[]} Notices.
 */
export const getNotices = ( state: CallToActionsState ): AppNotice[] =>
	state.notices || [];

/**
 * Get all call to actions.
 *
 * @param {CallToActionsState} state Current state.
 *
 * @return {CallToAction[]} Call to actions.
 */
export const getCallToActions = ( state: CallToActionsState ): CallToAction[] =>
	state.callToActions || [];

/**
 * Get current values for given call to action id.
 * @param {CallToActionsState} state Current state.
 * @param {number}             id    Call to action ID.
 *
 * @return {CallToAction} Call to action.
 */
export const getCallToAction = (
	state: CallToActionsState,
	id: CallToAction[ 'id' ] | null | undefined
): CallToAction | undefined =>
	getCallToActions( state ).find(
		( callToAction ) => callToAction.id === id
	);

/**
 * Check if the editor is active.
 *
 * @param {CallToActionsState} state Current state.
 *
 * @return {boolean} If editor is active.
 */
export const isEditorActive = ( state: CallToActionsState ): boolean => {
	const editorId = state?.editor?.id;

	if ( typeof editorId === 'string' && editorId === 'new' ) {
		return true;
	}

	return typeof editorId === 'number' && editorId > 0;
};

/**
 * Check if the editor is active.
 *
 * @param {CallToActionsState} state Current state.
 *
 * @return {number|"new"|undefined} If editor is active.
 */
export const getEditorId = ( state: CallToActionsState ): EditorId =>
	state?.editor?.id;

/**
 * Get current editor values.
 *
 * @param {CallToActionsState} state Current state.
 *
 * @return {CallToAction|undefined} If editor is active.
 */
export const getEditorValues = (
	state: CallToActionsState
): CallToActionsState[ 'editor' ][ 'values' ] => state?.editor?.values;

export const getCallToActionDefaults = (): CallToAction => {
	const defaults = applyFilters(
		'popupMaker.defaultCallToActionValues',
		callToActionDefaults
	) as CallToAction;

	return {
		...defaults,
	};
};

/**
 * Get current status for dispatched action.
 *
 * @param {CallToActionsState}                state      Current state.
 * @param {CallToActionsStore['ActionNames']} actionName Action name to check.
 *
 * @return {string} Current status for dispatched action.
 */
export const getDispatchStatus = (
	state: CallToActionsState,
	actionName: CallToActionsStore[ 'ActionNames' ]
): Statuses | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 *
 * @param {CallToActionsState}                                                    state       Current state.
 * @param {CallToActionsStore['ActionNames']|CallToActionsStore['ActionNames'][]} actionNames Action name or array of names to check.
 *
 * @return {boolean} True if is dispatching.
 */
export const isDispatching = (
	state: CallToActionsState,
	actionNames:
		| CallToActionsStore[ 'ActionNames' ]
		| CallToActionsStore[ 'ActionNames' ][]
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
 * @param {CallToActionsState}                state      Current state.
 * @param {CallToActionsStore['ActionNames']} actionName Action name to check.
 *
 * @return {boolean} True if dispatched.
 */
export const hasDispatched = (
	state: CallToActionsState,
	actionName: CallToActionsStore[ 'ActionNames' ]
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
 * @param {CallToActionsState}                state      Current state.
 * @param {CallToActionsStore['ActionNames']} actionName Action name to check.
 *
 * @return {string|undefined} Current error message.
 */
export const getDispatchError = (
	state: CallToActionsState,
	actionName: CallToActionsStore[ 'ActionNames' ]
): string | { message: string; [ key: string ]: any } | undefined =>
	state?.dispatchStatus?.[ actionName ]?.error;
