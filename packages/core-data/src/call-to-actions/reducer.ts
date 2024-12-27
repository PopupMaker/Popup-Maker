import { ACTION_TYPES, initialState } from './constants';

import type { AppNotice } from '../types';

import type {
	CallToAction,
	CallToActionsState,
	CallToActionsStore,
	CallToActionStatuses,
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
	CALL_TO_ACTIONS_FETCH_ERROR,
} = ACTION_TYPES;

type ActionPayloadTypes = {
	type: keyof typeof ACTION_TYPES;
	callToAction: CallToAction;
	callToActions: CallToAction[];
	callToActionId: CallToAction[ 'id' ];
	editorId: CallToActionsState[ 'editor' ][ 'id' ];
	editorValues: CallToActionsState[ 'editor' ][ 'values' ];
	// Boilerplate.
	actionName: CallToActionsStore[ 'ActionNames' ];
	status: CallToActionStatuses;
	message: string;
	notice: AppNotice;
	noticeId: AppNotice[ 'id' ];
};

const reducer = (
	state: CallToActionsState = initialState,
	{
		callToActions: incomingCallToActions,
		callToAction,
		callToActionId,
		type,
		editorId,
		editorValues,
		// Boilerplate
		actionName,
		status,
		message,
		notice,
		noticeId,
	}: ActionPayloadTypes
) => {
	switch ( type ) {
		case CREATE:
			return {
				...state,
				callToActions: [ ...state.callToActions, callToAction ],
			};

		case UPDATE:
			return {
				...state,
				callToActions: state.callToActions
					.filter( ( existing ) => existing.id !== callToAction.id )
					.concat( [ callToAction ] ),
			};

		case DELETE:
			return {
				...state,
				callToActions: state.callToActions.filter(
					( existing ) => existing.id !== callToActionId
				),
			};

		case HYDRATE:
			return {
				...state,
				callToActions: incomingCallToActions,
			};

		case ADD_NOTICE:
			return {
				...state,
				notices: [
					...state.notices.filter( ( { id } ) => id !== notice.id ),
					notice,
				],
			};

		case CLEAR_NOTICE:
			return {
				...state,
				notices: state.notices.filter( ( { id } ) => id !== noticeId ),
			};

		case CLEAR_NOTICES:
			return {
				...state,
				notices: [],
			};

		case CALL_TO_ACTIONS_FETCH_ERROR:
			return {
				...state,
				error: message,
			};

		case EDITOR_CHANGE_ID:
			return {
				...state,
				editor: {
					...state.editor,
					id: editorId,
					values: editorValues,
				},
			};

		case EDITOR_UPDATE_VALUES:
			return {
				...state,
				editor: {
					...state.editor,
					values: {
						...state.editor?.values,
						...editorValues,
					},
				},
			};

		case EDITOR_CLEAR_DATA:
			return {
				...state,
				editor: {},
			};

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ actionName ]: {
						...state?.dispatchStatus?.[ actionName ],
						status,
						error: message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
