import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses } from '../constants';
import type { Settings, StoreActionNames } from './types';

const {
	UPDATE,
	STAGE_CHANGES,
	SAVE_CHANGES,
	HYDRATE,
	CHANGE_ACTION_STATUS,
	SETTINGS_FETCH_ERROR,
} = ACTION_TYPES;

export type State = {
	settings: Settings;
	unsavedChanges?: Partial< Settings >;
	// Boilerplate
	dispatchStatus?: {
		[ Property in StoreActionNames ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

type BaseAction = {
	type: keyof typeof ACTION_TYPES;
};

type HydrateAction = BaseAction & {
	type: typeof HYDRATE;
	settings: Settings;
};

type UpdateSettingsAction = BaseAction & {
	type: typeof UPDATE;
	settings: Settings;
};

type StageChangesAction = BaseAction & {
	type: typeof STAGE_CHANGES;
	settings: Settings;
};

type SaveChangesAction = BaseAction & {
	type: typeof SAVE_CHANGES;
	settings: Settings;
};

type FetchSettingsErrorAction = BaseAction & {
	type: typeof SETTINGS_FETCH_ERROR;
	message: string;
};

type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	actionName: StoreActionNames;
	status: DispatchStatuses;
	message: string;
};

export type ReducerAction =
	| HydrateAction
	| UpdateSettingsAction
	| ChangeActionStatusAction
	| FetchSettingsErrorAction
	| StageChangesAction
	| SaveChangesAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case HYDRATE:
			return {
				...state,
				settings: action.settings,
			};

		case SETTINGS_FETCH_ERROR:
			return {
				...state,
				error: action.message,
			};

		case STAGE_CHANGES:
			return {
				...state,
				unsavedChanges: {
					...( state.unsavedChanges ?? {} ),
					...action.settings,
				},
			};

		case SAVE_CHANGES:
			return {
				...state,
				settings: {
					...state.settings,
					...action.settings,
				},
				unsavedChanges: {},
			};

		case UPDATE:
			return {
				...state,
				settings: {
					...state.settings,
					...action.settings,
				},
			};

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ action.actionName ]: {
						...state?.dispatchStatus?.[ action.actionName ],
						status: action.status,
						error: action.message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
