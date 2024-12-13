import { ACTION_TYPES, initialState } from './constants';

import type {
	AppNotice,
	Popup,
	PopupsState,
	PopupsStore,
	PopupStatuses,
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
	POPUPS_FETCH_ERROR,
} = ACTION_TYPES;

type ActionPayloadTypes = {
	type: keyof typeof ACTION_TYPES;
	popup: Popup;
	popups: Popup[];
	popupId: Popup[ 'id' ];
	editorId: PopupsState[ 'editor' ][ 'id' ];
	editorValues: PopupsState[ 'editor' ][ 'values' ];
	// Boilerplate.
	actionName: PopupsStore[ 'ActionNames' ];
	status: PopupStatuses;
	message: string;
	notice: AppNotice;
	noticeId: AppNotice[ 'id' ];
};

const reducer = (
	state: PopupsState = initialState,
	{
		popups: incomingPopups,
		popup,
		popupId,
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
				popups: [ ...state.popups, popup ],
			};

		case UPDATE:
			return {
				...state,
				popups: state.popups
					.filter( ( existing ) => existing.id !== popup.id )
					.concat( [ popup ] ),
			};

		case DELETE:
			return {
				...state,
				popups: state.popups.filter(
					( existing ) => existing.id !== popupId
				),
			};

		case HYDRATE:
			return {
				...state,
				popups: incomingPopups,
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

		case POPUPS_FETCH_ERROR:
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
