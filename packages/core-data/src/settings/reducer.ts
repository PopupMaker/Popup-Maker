import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses, ResolutionState } from '../constants';
import type { Settings } from './types';

const {
	UPDATE,
	STAGE_CHANGES,
	SAVE_CHANGES,
	HYDRATE,
	CHANGE_ACTION_STATUS,
	SETTINGS_FETCH_ERROR,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

export type State = {
	/**
	 * The settings.
	 */
	settings: Settings;

	/**
	 * The unsaved changes.
	 */
	unsavedChanges?: Partial< Settings >;

	/**
	 * The resolution state for each operation.
	 */
	resolutionState: Record< string | number, ResolutionState >;
};

export type BaseAction = {
	type: keyof typeof ACTION_TYPES;
};

export type HydrateAction = BaseAction & {
	type: typeof HYDRATE;
	payload: {
		settings: Settings;
	};
};

export type UpdateSettingsAction = BaseAction & {
	type: typeof UPDATE;
	payload: {
		settings: Settings;
	};
};

export type StageChangesAction = BaseAction & {
	type: typeof STAGE_CHANGES;
	payload: {
		settings: Settings;
	};
};

export type SaveChangesAction = BaseAction & {
	type: typeof SAVE_CHANGES;
	payload: {
		settings: Settings;
	};
};

export type FetchSettingsErrorAction = BaseAction & {
	type: typeof SETTINGS_FETCH_ERROR;
	payload: {
		message: string;
	};
};

export type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	payload: {
		actionName: string;
		status: DispatchStatuses;
		message?: string;
	};
};

export type InvalidateResolutionAction = BaseAction & {
	type: typeof INVALIDATE_RESOLUTION;
	payload: {
		id: number | string;
		operation: string;
	};
};

export type ReducerAction =
	| HydrateAction
	| UpdateSettingsAction
	| FetchSettingsErrorAction
	| StageChangesAction
	| SaveChangesAction
	| ChangeActionStatusAction
	| InvalidateResolutionAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case HYDRATE: {
			const { settings } = action.payload;

			return {
				...state,
				settings,
			};
		}

		case SETTINGS_FETCH_ERROR: {
			const { message } = action.payload;

			return {
				...state,
				error: message,
			};
		}

		case STAGE_CHANGES: {
			const { settings } = action.payload;

			return {
				...state,
				unsavedChanges: {
					...( state.unsavedChanges ?? {} ),
					...settings,
				},
			};
		}

		case SAVE_CHANGES: {
			const { settings } = action.payload;

			return {
				...state,
				settings: {
					...state.settings,
					...settings,
				},
				unsavedChanges: {},
			};
		}

		case UPDATE: {
			const { settings } = action.payload;
			return {
				...state,
				settings: {
					...state.settings,
					...settings,
				},
			};
		}

		case CHANGE_ACTION_STATUS: {
			const { actionName, status, message } = action.payload;

			return {
				...state,
				resolutionState: {
					...state.resolutionState,
					[ actionName ]: {
						status,
						error: message,
					},
				},
			};
		}

		case INVALIDATE_RESOLUTION: {
			const { id, operation } = action.payload;

			return {
				...state,
				resolutionState: {
					...state.resolutionState,
					[ operation ]: {
						...state.resolutionState?.[ operation ],
						[ id ]: undefined,
					},
				},
			};
		}

		default:
			return state;
	}
};

export default reducer;
