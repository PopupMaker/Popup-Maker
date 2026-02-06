// Must set global inside jest.mock factory so it runs before imports
// (jest.mock is hoisted, but import statements are too).
jest.mock( '@wordpress/hooks', () => {
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

jest.mock( '@wordpress/data', () => ( {
	createSelector: ( selector: Function ) => selector,
	createRegistrySelector: ( fn: Function ) =>
		fn( ( storeName: string ) => ( {
			getNotices: () => [],
		} ) ),
} ) );

import selectors from '../selectors';
import { DispatchStatus } from '../../constants';
import { initialState } from '../constants';

import type { State } from '../reducer';
import type { Settings } from '../types';

const {
	getSettings,
	getSetting,
	getUnsavedChanges,
	hasUnsavedChanges,
	getReqPermission,
	getResolutionState,
	isIdle,
	isResolving,
	hasResolved,
	hasFailed,
	getResolutionError,
} = selectors;

const stateWith = ( overrides: Partial< State > ): State => ( {
	...initialState,
	...overrides,
} );

describe( 'settings selectors', () => {
	describe( 'getSettings', () => {
		it( 'returns the current settings', () => {
			const result = getSettings( initialState );
			expect( result ).toEqual( initialState.settings );
		} );

		it( 'reflects updated settings', () => {
			const updated: Settings = {
				permissions: {
					view_block_controls: 'read',
					edit_block_controls: 'read',
					edit_restrictions: 'manage_options',
					manage_settings: 'manage_options',
				},
			};
			const state = stateWith( { settings: updated } );
			expect( getSettings( state ) ).toEqual( updated );
		} );
	} );

	describe( 'getSetting', () => {
		it( 'returns a specific setting value', () => {
			const result = getSetting(
				initialState,
				'permissions',
				undefined
			);
			expect( result ).toEqual(
				initialState.settings.permissions
			);
		} );

		it( 'returns default value when setting is missing', () => {
			const emptyState = stateWith( {
				settings: {} as Settings,
			} );
			const result = getSetting(
				emptyState,
				'permissions',
				{ view_block_controls: 'fallback' } as unknown as Settings[ 'permissions' ]
			);
			expect( result ).toEqual( {
				view_block_controls: 'fallback',
			} );
		} );
	} );

	describe( 'getUnsavedChanges', () => {
		it( 'returns empty object when no changes', () => {
			expect( getUnsavedChanges( initialState ) ).toEqual( {} );
		} );

		it( 'returns staged changes', () => {
			const changes = {
				permissions: {
					view_block_controls: 'manage_options',
				},
			} as unknown as Partial< Settings >;
			const state = stateWith( { unsavedChanges: changes } );
			expect( getUnsavedChanges( state ) ).toEqual( changes );
		} );
	} );

	describe( 'hasUnsavedChanges', () => {
		it( 'returns false when no unsaved changes', () => {
			expect( hasUnsavedChanges( initialState ) ).toBe( false );
		} );

		it( 'returns true when changes exist', () => {
			const state = stateWith( {
				unsavedChanges: {
					permissions: { manage_settings: 'edit_posts' } as unknown as Settings[ 'permissions' ],
				},
			} );
			expect( hasUnsavedChanges( state ) ).toBe( true );
		} );
	} );

	describe( 'getReqPermission', () => {
		it( 'returns the permission for a known cap', () => {
			const result = getReqPermission(
				initialState,
				'view_block_controls'
			);
			expect( result ).toBe( 'edit_posts' );
		} );

		it( 'returns manage_options as default for unknown permissions', () => {
			const state = stateWith( {
				settings: {
					permissions: {
						view_block_controls: false,
						edit_block_controls: 'edit_posts',
						edit_restrictions: 'manage_options',
						manage_settings: 'manage_options',
					},
				},
			} );

			const result = getReqPermission( state, 'view_block_controls' );
			expect( result ).toBe( 'manage_options' );
		} );
	} );

	describe( 'resolution selectors', () => {
		it( 'getResolutionState returns idle for unknown', () => {
			const result = getResolutionState( initialState, 'unknown' );
			expect( result.status ).toBe( DispatchStatus.Idle );
		} );

		it( 'getResolutionState returns stored state', () => {
			const state = stateWith( {
				resolutionState: {
					fetch: { status: DispatchStatus.Success },
				},
			} );
			expect( getResolutionState( state, 'fetch' ).status ).toBe(
				DispatchStatus.Success
			);
		} );

		it( 'isIdle returns true for idle', () => {
			expect( isIdle( initialState, 'op' ) ).toBe( true );
		} );

		it( 'isResolving returns true during resolution', () => {
			const state = stateWith( {
				resolutionState: {
					op: { status: DispatchStatus.Resolving },
				},
			} );
			expect( isResolving( state, 'op' ) ).toBe( true );
		} );

		it( 'hasResolved returns true on success', () => {
			const state = stateWith( {
				resolutionState: {
					op: { status: DispatchStatus.Success },
				},
			} );
			expect( hasResolved( state, 'op' ) ).toBe( true );
		} );

		it( 'hasFailed returns true on error', () => {
			const state = stateWith( {
				resolutionState: {
					op: { status: DispatchStatus.Error },
				},
			} );
			expect( hasFailed( state, 'op' ) ).toBe( true );
		} );

		it( 'getResolutionError returns the error', () => {
			const state = stateWith( {
				resolutionState: {
					op: {
						status: DispatchStatus.Error,
						error: 'Failed',
					},
				},
			} );
			expect( getResolutionError( state, 'op' ) ).toBe( 'Failed' );
		} );

		it( 'getResolutionError returns undefined when no error', () => {
			expect(
				getResolutionError( initialState, 'nope' )
			).toBeUndefined();
		} );
	} );
} );
