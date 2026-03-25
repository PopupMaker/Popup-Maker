// Must set global inside jest.mock factory so it runs before imports
// (jest.mock is hoisted, but import statements are too).
jest.mock( '@wordpress/hooks', () => {
	// Set global here because jest.mock factories run before imports.
	( global as unknown as Record< string, unknown > ).popupMakerCoreData = {
		currentSettings: {
			permissions: {
				view_block_controls: 'edit_posts',
				edit_block_controls: 'edit_posts',
				edit_restrictions: 'manage_options',
				manage_settings: 'manage_options',
			},
		},
	};
	return {
		applyFilters: jest.fn( ( _: string, v: unknown ) => v ),
	};
} );

import reducer from '../reducer';
import { ACTION_TYPES, initialState } from '../constants';
import { DispatchStatus } from '../../constants';

import type { State, ReducerAction } from '../reducer';
import type { Settings } from '../types';

const mockSettings: Settings = {
	permissions: {
		view_block_controls: 'edit_posts',
		edit_block_controls: 'edit_posts',
		edit_restrictions: 'manage_options',
		manage_settings: 'manage_options',
	},
};

describe( 'settings reducer', () => {
	it( 'returns initial state for unknown action', () => {
		const result = reducer( undefined, { type: 'UNKNOWN' } as unknown as ReducerAction );
		expect( result ).toEqual( initialState );
	} );

	describe( 'HYDRATE', () => {
		it( 'replaces settings with hydrated data', () => {
			const newSettings: Settings = {
				permissions: {
					view_block_controls: 'manage_options',
					edit_block_controls: 'manage_options',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			};

			const state = reducer( initialState, {
				type: ACTION_TYPES.HYDRATE,
				payload: { settings: newSettings },
			} as unknown as ReducerAction );

			expect( state.settings ).toEqual( newSettings );
		} );

		it( 'preserves other state properties', () => {
			const stateWithChanges: State = {
				...initialState,
				unsavedChanges: { permissions: {} as unknown as Settings[ 'permissions' ] },
			};

			const state = reducer( stateWithChanges, {
				type: ACTION_TYPES.HYDRATE,
				payload: { settings: mockSettings },
			} as unknown as ReducerAction );

			expect( state.unsavedChanges ).toEqual( { permissions: {} as unknown as Settings[ 'permissions' ] } );
		} );
	} );

	describe( 'SETTINGS_FETCH_ERROR', () => {
		it( 'stores the error message', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.SETTINGS_FETCH_ERROR,
				payload: { message: 'Network error' },
			} as unknown as ReducerAction );

			expect( ( state as unknown as Record< string, unknown > ).error ).toBe( 'Network error' );
		} );

		it( 'overwrites previous error', () => {
			const stateWithError = {
				...initialState,
				error: 'Old error',
			} as unknown as State;

			const state = reducer( stateWithError, {
				type: ACTION_TYPES.SETTINGS_FETCH_ERROR,
				payload: { message: 'New error' },
			} as unknown as ReducerAction );

			expect( ( state as unknown as Record< string, unknown > ).error ).toBe( 'New error' );
		} );
	} );

	describe( 'STAGE_CHANGES', () => {
		it( 'merges changes into unsavedChanges', () => {
			const changes: Partial< Settings > = {
				permissions: {
					view_block_controls: 'manage_options',
					edit_block_controls: 'edit_posts',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			};

			const state = reducer( initialState, {
				type: ACTION_TYPES.STAGE_CHANGES,
				payload: { settings: changes },
			} as unknown as ReducerAction );

			expect( state.unsavedChanges ).toEqual( changes );
		} );

		it( 'accumulates multiple staged changes', () => {
			const stateWithChanges: State = {
				...initialState,
				unsavedChanges: {
					permissions: {
						view_block_controls: 'manage_options',
						edit_block_controls: 'edit_posts',
						edit_restrictions: 'manage_options',
						manage_settings: 'manage_options',
					},
				},
			};

			const moreChanges = {
				permissions: {
					view_block_controls: 'edit_posts',
					edit_block_controls: 'manage_options',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			} as Settings;

			const state = reducer( stateWithChanges, {
				type: ACTION_TYPES.STAGE_CHANGES,
				payload: { settings: moreChanges },
			} as unknown as ReducerAction );

			expect( state.unsavedChanges?.permissions?.edit_block_controls ).toBe(
				'manage_options'
			);
		} );
	} );

	describe( 'SAVE_CHANGES', () => {
		it( 'merges saved settings and clears unsaved changes', () => {
			const stateWithChanges: State = {
				...initialState,
				unsavedChanges: {
					permissions: {
						view_block_controls: 'manage_options',
						edit_block_controls: 'edit_posts',
						edit_restrictions: 'manage_options',
						manage_settings: 'manage_options',
					},
				},
			};

			const savedSettings: Settings = {
				permissions: {
					view_block_controls: 'manage_options',
					edit_block_controls: 'edit_posts',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			};

			const state = reducer( stateWithChanges, {
				type: ACTION_TYPES.SAVE_CHANGES,
				payload: { settings: savedSettings },
			} as unknown as ReducerAction );

			expect( state.settings.permissions.view_block_controls ).toBe(
				'manage_options'
			);
			expect( state.unsavedChanges ).toEqual( {} );
		} );
	} );

	describe( 'UPDATE', () => {
		it( 'merges updated settings into current settings', () => {
			const update: Settings = {
				permissions: {
					view_block_controls: 'read',
					edit_block_controls: 'edit_posts',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			};

			const state = reducer( initialState, {
				type: ACTION_TYPES.UPDATE,
				payload: { settings: update },
			} as unknown as ReducerAction );

			expect( state.settings.permissions.view_block_controls ).toBe(
				'read'
			);
		} );

		it( 'does not clear unsavedChanges', () => {
			const stateWithChanges: State = {
				...initialState,
				unsavedChanges: {
					permissions: { manage_settings: 'edit_posts' } as unknown as Settings[ 'permissions' ],
				},
			};

			const state = reducer( stateWithChanges, {
				type: ACTION_TYPES.UPDATE,
				payload: { settings: mockSettings },
			} as unknown as ReducerAction );

			expect( state.unsavedChanges ).toEqual(
				stateWithChanges.unsavedChanges
			);
		} );
	} );

	describe( 'CHANGE_ACTION_STATUS', () => {
		it( 'sets resolution status for an action', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'fetchSettings',
					status: DispatchStatus.Resolving,
					message: undefined,
				},
			} as unknown as ReducerAction );

			expect( state.resolutionState.fetchSettings ).toEqual( {
				status: DispatchStatus.Resolving,
				error: undefined,
			} );
		} );

		it( 'stores error on failure', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'saveSettings',
					status: DispatchStatus.Error,
					message: 'Permission denied',
				},
			} as unknown as ReducerAction );

			expect( state.resolutionState.saveSettings ).toEqual( {
				status: DispatchStatus.Error,
				error: 'Permission denied',
			} );
		} );
	} );

	describe( 'INVALIDATE_RESOLUTION', () => {
		it( 'clears resolution for operation/id', () => {
			const existing: State = {
				...initialState,
				resolutionState: {
					fetchSettings: {
						1: {
							status: DispatchStatus.Success,
						},
						2: {
							status: DispatchStatus.Success,
						},
					},
				},
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.INVALIDATE_RESOLUTION,
				payload: { id: 1, operation: 'fetchSettings' },
			} as unknown as ReducerAction );

			// ID 1 should be invalidated.
			expect( state.resolutionState.fetchSettings?.[ 1 ] ).toBeUndefined();
			// ID 2 should remain.
			expect( state.resolutionState.fetchSettings?.[ 2 ] ).toEqual( {
				status: DispatchStatus.Success,
			} );
		} );
	} );
} );
