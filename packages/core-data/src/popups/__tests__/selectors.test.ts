jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _, v ) => v ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	createSelector: ( selector: Function ) => selector,
	createRegistrySelector: ( fn: Function ) =>
		fn( ( storeName: string ) => ( {
			getNotices: () => [],
		} ) ),
	createReduxStore: jest.fn(),
} ) );

jest.mock( '@wordpress/notices', () => ( {
	store: 'core/notices',
} ) );

jest.mock( 'fast-json-patch', () => ( {
	applyPatch: jest.fn( ( doc, patches ) => {
		// Simple mock: apply replace ops shallowly.
		let result = { ...doc };
		for ( const patch of patches ) {
			if ( patch.op === 'replace' ) {
				const key = patch.path.replace( '/', '' );
				result = { ...result, [ key ]: patch.value };
			}
		}
		return { newDocument: result };
	} ),
} ) );

import {
	getPopups,
	getPopup,
	getFetchError,
	getFiltered,
	getFilteredIds,
	getEditorId,
	isEditorActive,
	getCurrentEditorValues,
	hasEditedEntity,
	getEditedEntity,
	getEntityEditHistory,
	getCurrentEditHistoryIndex,
	hasEdits,
	hasUndo,
	hasRedo,
	getEditedPopup,
	getDefaultValues,
	getNotices,
	getResolutionState,
	isIdle,
	isResolving,
	hasResolved,
	hasFailed,
	getResolutionError,
} from '../selectors';
import { DispatchStatus } from '../../constants';
import { initialState } from '../constants';

import type { State } from '../reducer';

const mockPopup = ( id: number, overrides = {} ) =>
	( {
		id,
		title: `Popup ${ id }`,
		status: 'draft',
		slug: `popup-${ id }`,
		...overrides,
	} ) as any;

const stateWith = ( overrides: Partial< State > ): State => ( {
	...initialState,
	...overrides,
} );

describe( 'popups selectors', () => {
	describe( 'entity selectors', () => {
		it( 'getPopups returns all popups in order', () => {
			const state = stateWith( {
				byId: {
					1: mockPopup( 1 ),
					2: mockPopup( 2 ),
				},
				allIds: [ 1, 2 ],
			} );

			const result = getPopups( state );
			expect( result ).toHaveLength( 2 );
			expect( result[ 0 ].id ).toBe( 1 );
		} );

		it( 'getPopups returns empty array for empty state', () => {
			expect( getPopups( initialState ) ).toEqual( [] );
		} );

		it( 'getPopup returns a specific popup', () => {
			const popup = mockPopup( 5 );
			const state = stateWith( {
				byId: { 5: popup },
				allIds: [ 5 ],
			} );

			expect( getPopup( state, 5 ) ).toEqual( popup );
		} );

		it( 'getPopup returns undefined for missing id', () => {
			expect( getPopup( initialState, 999 ) ).toBeUndefined();
		} );

		it( 'getFetchError returns global error when no id', () => {
			const state = stateWith( {
				errors: { global: 'Server down', byId: {} },
			} );
			expect( getFetchError( state ) ).toBe( 'Server down' );
		} );

		it( 'getFetchError returns per-id error', () => {
			const state = stateWith( {
				errors: { global: null, byId: { 42: 'Not found' } },
			} );
			expect( getFetchError( state, 42 ) ).toBe( 'Not found' );
		} );

		it( 'getFiltered filters popups by predicate', () => {
			const state = stateWith( {
				byId: {
					1: mockPopup( 1, { status: 'publish' } ),
					2: mockPopup( 2, { status: 'draft' } ),
					3: mockPopup( 3, { status: 'publish' } ),
				},
				allIds: [ 1, 2, 3 ],
			} );

			const published = getFiltered(
				state,
				( p ) => p.status === 'publish'
			);
			expect( published ).toHaveLength( 2 );
		} );

		it( 'getFilteredIds returns only matching ids', () => {
			const state = stateWith( {
				byId: {
					1: mockPopup( 1, { status: 'draft' } ),
					2: mockPopup( 2, { status: 'publish' } ),
				},
				allIds: [ 1, 2 ],
			} );

			const ids = getFilteredIds(
				state,
				( p ) => p.status === 'publish'
			);
			expect( ids ).toEqual( [ 2 ] );
		} );
	} );

	describe( 'editor selectors', () => {
		it( 'getEditorId returns the current editor id', () => {
			const state = stateWith( { editorId: 7 } );
			expect( getEditorId( state ) ).toBe( 7 );
		} );

		it( 'getEditorId returns undefined when no editor', () => {
			expect( getEditorId( initialState ) ).toBeUndefined();
		} );

		it( 'isEditorActive returns true for valid numeric id', () => {
			const state = stateWith( { editorId: 1 } );
			expect( isEditorActive( state ) ).toBe( true );
		} );

		it( 'isEditorActive returns true for "new" string id', () => {
			const state = stateWith( { editorId: 'new' as any } );
			expect( isEditorActive( state ) ).toBe( true );
		} );

		it( 'isEditorActive returns false for undefined', () => {
			expect( isEditorActive( initialState ) ).toBe( false );
		} );

		it( 'isEditorActive returns false for 0', () => {
			const state = stateWith( { editorId: 0 as any } );
			expect( isEditorActive( state ) ).toBe( false );
		} );

		it( 'hasEditedEntity returns true when entity exists', () => {
			const state = stateWith( {
				editedEntities: { 3: { id: 3 } as any },
			} );
			expect( hasEditedEntity( state, 3 ) ).toBe( true );
		} );

		it( 'hasEditedEntity returns false for missing entity', () => {
			expect( hasEditedEntity( initialState, 999 ) ).toBe( false );
		} );

		it( 'getEditedEntity returns the edited entity', () => {
			const entity = { id: 3, title: 'Edited' } as any;
			const state = stateWith( { editedEntities: { 3: entity } } );
			expect( getEditedEntity( state, 3 ) ).toEqual( entity );
		} );

		it( 'getEntityEditHistory returns history for entity', () => {
			const history = [
				[ { op: 'replace', path: '/title', value: 'A' } ],
			] as any;
			const state = stateWith( { editHistory: { 1: history } } );
			expect( getEntityEditHistory( state, 1 ) ).toEqual( history );
		} );

		it( 'getCurrentEditHistoryIndex returns index', () => {
			const state = stateWith( { editHistoryIndex: { 1: 3 } } );
			expect( getCurrentEditHistoryIndex( state, 1 ) ).toBe( 3 );
		} );
	} );

	describe( 'hasEdits / hasUndo / hasRedo', () => {
		it( 'hasEdits returns true when edit history exists', () => {
			const state = stateWith( {
				editHistory: { 1: [ [] ] as any },
			} );
			expect( hasEdits( state, 1 ) ).toBe( true );
		} );

		it( 'hasEdits returns false for empty history', () => {
			expect( hasEdits( initialState, 1 ) ).toBeFalsy();
		} );

		it( 'hasUndo returns true when index >= 0', () => {
			const state = stateWith( {
				editHistory: { 1: [ [] ] as any },
				editHistoryIndex: { 1: 0 },
			} );
			expect( hasUndo( state, 1 ) ).toBe( true );
		} );

		it( 'hasUndo returns false when index is -1', () => {
			const state = stateWith( {
				editHistory: { 1: [ [] ] as any },
				editHistoryIndex: { 1: -1 },
			} );
			expect( hasUndo( state, 1 ) ).toBe( false );
		} );

		it( 'hasUndo returns false when no history exists', () => {
			expect( hasUndo( initialState, 1 ) ).toBe( false );
		} );

		it( 'hasRedo returns true when index < length - 1', () => {
			const state = stateWith( {
				editHistory: { 1: [ [], [], [] ] as any },
				editHistoryIndex: { 1: 0 },
			} );
			expect( hasRedo( state, 1 ) ).toBe( true );
		} );

		it( 'hasRedo returns false when at end of history', () => {
			const state = stateWith( {
				editHistory: { 1: [ [], [] ] as any },
				editHistoryIndex: { 1: 1 },
			} );
			expect( hasRedo( state, 1 ) ).toBe( false );
		} );

		it( 'hasRedo returns false when no history', () => {
			expect( hasRedo( initialState, 1 ) ).toBe( false );
		} );
	} );

	describe( 'getEditedPopup', () => {
		it( 'returns undefined when no base entity', () => {
			expect( getEditedPopup( initialState, 1 ) ).toBeUndefined();
		} );

		it( 'returns base entity when history index is -1', () => {
			const entity = { id: 1, title: 'Base' } as any;
			const state = stateWith( {
				editedEntities: { 1: entity },
				editHistoryIndex: { 1: -1 },
			} );

			expect( getEditedPopup( state, 1 ) ).toEqual( entity );
		} );

		it( 'returns base entity when no edit history', () => {
			const entity = { id: 1, title: 'Base' } as any;
			const state = stateWith( {
				editedEntities: { 1: entity },
			} );

			expect( getEditedPopup( state, 1 ) ).toEqual( entity );
		} );

		it( 'applies patches through applyPatch', () => {
			const entity = { id: 1, title: 'Old' } as any;
			const state = stateWith( {
				editedEntities: { 1: entity },
				editHistory: {
					1: [
						[
							{
								op: 'replace',
								path: '/title',
								value: 'New',
							},
						],
					] as any,
				},
				editHistoryIndex: { 1: 0 },
			} );

			const result = getEditedPopup( state, 1 );
			expect( result ).toBeDefined();
			expect( result!.title ).toBe( 'New' );
		} );
	} );

	describe( 'getDefaultValues', () => {
		it( 'returns filtered default values', () => {
			const result = getDefaultValues( initialState );
			expect( result ).toBeDefined();
			expect( result.status ).toBe( 'draft' );
		} );
	} );

	describe( 'notice selectors', () => {
		it( 'getNotices returns notices from registry', () => {
			const notices = getNotices();
			expect( Array.isArray( notices ) ).toBe( true );
		} );
	} );

	describe( 'resolution state selectors', () => {
		it( 'getResolutionState returns idle for unknown id', () => {
			const result = getResolutionState( initialState, 'unknown' );
			expect( result.status ).toBe( DispatchStatus.Idle );
		} );

		it( 'getResolutionState returns stored state', () => {
			const state = stateWith( {
				resolutionState: {
					fetch: { status: DispatchStatus.Resolving },
				},
			} );
			expect( getResolutionState( state, 'fetch' ).status ).toBe(
				DispatchStatus.Resolving
			);
		} );

		it( 'isIdle returns true for idle state', () => {
			expect( isIdle( initialState, 'any' ) ).toBe( true );
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

		it( 'getResolutionError returns the error message', () => {
			const state = stateWith( {
				resolutionState: {
					op: {
						status: DispatchStatus.Error,
						error: 'Timeout',
					},
				},
			} );
			expect( getResolutionError( state, 'op' ) ).toBe( 'Timeout' );
		} );

		it( 'getResolutionError returns undefined when no error', () => {
			expect(
				getResolutionError( initialState, 'nonexistent' )
			).toBeUndefined();
		} );
	} );
} );
