jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _, v ) => v ),
} ) );

jest.mock( 'fast-json-patch', () => ( {
	applyPatch: jest.fn( ( doc, patch ) => ( { newDocument: doc } ) ),
} ) );

jest.mock( '@wordpress/notices', () => ( {
	store: 'core/notices',
} ) );

import { reducer } from '../reducer';
import { ACTION_TYPES, initialState } from '../constants';
import { DispatchStatus } from '../../constants';

import type { State, ReducerAction } from '../reducer';
import type { Popup, EditablePopup } from '../types';
import type { Operation } from 'fast-json-patch';

const mockPopup = ( id: number, overrides = {} ) => ( {
	id,
	title: `Popup ${ id }`,
	status: 'draft' as const,
	slug: `popup-${ id }`,
	content: '',
	...overrides,
} );

describe( 'popups reducer', () => {
	it( 'returns initial state for unknown action', () => {
		const result = reducer( undefined, { type: 'UNKNOWN' } as unknown as ReducerAction );
		expect( result ).toEqual( initialState );
	} );

	describe( 'RECEIVE_RECORD', () => {
		it( 'adds a new record to the store', () => {
			const record = mockPopup( 1 );
			const state = reducer( initialState, {
				type: ACTION_TYPES.RECEIVE_RECORD,
				payload: { record },
			} as unknown as ReducerAction );

			expect( state.byId[ 1 ] ).toEqual( record );
			expect( state.allIds ).toContain( 1 );
		} );

		it( 'does not duplicate IDs when receiving existing record', () => {
			const record = mockPopup( 1 );
			const stateWithRecord: State = {
				...initialState,
				byId: { 1: record as unknown as Popup< 'edit' > },
				allIds: [ 1 ],
			};

			const updatedRecord = mockPopup( 1, { title: 'Updated' } );
			const state = reducer( stateWithRecord, {
				type: ACTION_TYPES.RECEIVE_RECORD,
				payload: { record: updatedRecord },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 1 ] );
			expect( state.byId[ 1 ].title ).toBe( 'Updated' );
		} );
	} );

	describe( 'RECEIVE_RECORDS', () => {
		it( 'adds multiple records', () => {
			const records = [ mockPopup( 1 ), mockPopup( 2 ) ];
			const state = reducer( initialState, {
				type: ACTION_TYPES.RECEIVE_RECORDS,
				payload: { records },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 1, 2 ] );
			expect( state.byId[ 1 ] ).toEqual( records[ 0 ] );
			expect( state.byId[ 2 ] ).toEqual( records[ 1 ] );
		} );

		it( 'merges with existing records without duplicating IDs', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockPopup( 1 ) as unknown as Popup< 'edit' > },
				allIds: [ 1 ],
			};

			const records = [ mockPopup( 2 ), mockPopup( 3 ) ];
			const state = reducer( existing, {
				type: ACTION_TYPES.RECEIVE_RECORDS,
				payload: { records },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 1, 2, 3 ] );
		} );
	} );

	describe( 'RECEIVE_QUERY_RECORDS', () => {
		it( 'stores records and caches query results', () => {
			const records = [ mockPopup( 1 ), mockPopup( 2 ) ];
			const query = { status: 'publish', per_page: 10 };
			const state = reducer( initialState, {
				type: ACTION_TYPES.RECEIVE_QUERY_RECORDS,
				payload: { records, query },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 1, 2 ] );
			expect(
				state.queries?.[ JSON.stringify( query ) ]
			).toEqual( [ 1, 2 ] );
		} );

		it( 'does not update queries when no query provided', () => {
			const records = [ mockPopup( 5 ) ];
			const state = reducer( initialState, {
				type: ACTION_TYPES.RECEIVE_RECORDS,
				payload: { records },
			} as unknown as ReducerAction );

			expect( state.queries ).toEqual( initialState.queries );
		} );
	} );

	describe( 'RECEIVE_ERROR', () => {
		it( 'sets global error when no id provided', () => {
			// Use fresh state to avoid mutation from other tests.
			const freshState: State = {
				...initialState,
				errors: { global: null, byId: {} },
			};
			const state = reducer( freshState, {
				type: ACTION_TYPES.RECEIVE_ERROR,
				payload: { error: 'Server error' },
			} as unknown as ReducerAction );

			expect( state.errors.global ).toBe( 'Server error' );
		} );

		it( 'sets per-ID error when id provided', () => {
			// Use fresh state to avoid mutation leaking between tests.
			const freshState: State = {
				...initialState,
				errors: { global: null, byId: {} },
			};
			const state = reducer( freshState, {
				type: ACTION_TYPES.RECEIVE_ERROR,
				payload: { error: 'Not found', id: 42 },
			} as unknown as ReducerAction );

			expect( state.errors.byId[ 42 ] ).toBe( 'Not found' );
			expect( state.errors.global ).toBeNull();
		} );

		// BUG: The reducer mutates the previous state's errors object directly
		// via `prevErrors.global = error`. This violates Redux immutability
		// principles. See BUGS-FOUND-BY-TESTS.md #2.
		it( 'BUG: mutates previous errors state directly', () => {
			const baseState: State = {
				...initialState,
				errors: { global: null, byId: {} },
			};
			const errorRef = baseState.errors;

			reducer( baseState, {
				type: ACTION_TYPES.RECEIVE_ERROR,
				payload: { error: 'Server error' },
			} as unknown as ReducerAction );

			// The original state.errors object IS mutated (bug).
			expect( errorRef.global ).toBe( 'Server error' );
		} );
	} );

	describe( 'PURGE_RECORD', () => {
		// BUG: Same string/number key mismatch as PURGE_RECORDS.
		// allIds is purged correctly (number-to-number), but byId is not
		// (Object.entries string key vs number id). See BUGS-FOUND-BY-TESTS.md #1.

		it( 'removes record from allIds', () => {
			const existing: State = {
				...initialState,
				byId: {
					1: mockPopup( 1 ) as unknown as Popup< 'edit' >,
					2: mockPopup( 2 ) as unknown as Popup< 'edit' >,
				},
				allIds: [ 1, 2 ],
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.PURGE_RECORD,
				payload: { id: 1 },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 2 ] );
		} );

		it( 'BUG: does NOT remove byId entry (string/number key mismatch)', () => {
			const existing: State = {
				...initialState,
				byId: {
					1: mockPopup( 1 ) as unknown as Popup< 'edit' >,
					2: mockPopup( 2 ) as unknown as Popup< 'edit' >,
				},
				allIds: [ 1, 2 ],
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.PURGE_RECORD,
				payload: { id: 1 },
			} as unknown as ReducerAction );

			// byId[1] SHOULD be removed but isn't due to the bug.
			expect( state.byId[ 1 ] ).toBeDefined();
			expect( state.byId[ 2 ] ).toBeDefined();
		} );

		it( 'returns unchanged state when id is 0 and no ids provided', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockPopup( 1 ) as unknown as Popup< 'edit' > },
				allIds: [ 1 ],
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.PURGE_RECORD,
				payload: { id: 0 },
			} as unknown as ReducerAction );

			expect( state ).toEqual( existing );
		} );
	} );

	describe( 'PURGE_RECORDS', () => {
		// BUG: Object.entries() returns string keys but ids array has numbers.
		// ids.includes("1") !== ids.includes(1), so byId/editedEntities/editHistory/
		// editHistoryIndex entries are NEVER actually removed. Only allIds is purged
		// correctly (number-to-number comparison). See BUGS-FOUND-BY-TESTS.md #1.

		it( 'removes from allIds correctly', () => {
			const existing: State = {
				...initialState,
				byId: {
					1: mockPopup( 1 ) as unknown as Popup< 'edit' >,
					2: mockPopup( 2 ) as unknown as Popup< 'edit' >,
					3: mockPopup( 3 ) as unknown as Popup< 'edit' >,
				},
				allIds: [ 1, 2, 3 ],
				editedEntities: { 1: {} as unknown as EditablePopup, 2: {} as unknown as EditablePopup },
				editHistory: { 1: [], 2: [] },
				editHistoryIndex: { 1: -1, 2: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.PURGE_RECORDS,
				payload: { ids: [ 1, 2 ] },
			} as unknown as ReducerAction );

			expect( state.allIds ).toEqual( [ 3 ] );
		} );

		it( 'BUG: does NOT remove byId entries (string/number key mismatch)', () => {
			const existing: State = {
				...initialState,
				byId: {
					1: mockPopup( 1 ) as unknown as Popup< 'edit' >,
					2: mockPopup( 2 ) as unknown as Popup< 'edit' >,
					3: mockPopup( 3 ) as unknown as Popup< 'edit' >,
				},
				allIds: [ 1, 2, 3 ],
				editedEntities: { 1: {} as unknown as EditablePopup, 2: {} as unknown as EditablePopup },
				editHistory: { 1: [], 2: [] },
				editHistoryIndex: { 1: -1, 2: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.PURGE_RECORDS,
				payload: { ids: [ 1, 2 ] },
			} as unknown as ReducerAction );

			// These SHOULD be purged but aren't due to the bug.
			expect( Object.keys( state.byId ) ).toEqual( [ '1', '2', '3' ] );
			expect( Object.keys( state.editedEntities ) ).toEqual( [ '1', '2' ] );
			expect( Object.keys( state.editHistory ) ).toEqual( [ '1', '2' ] );
			expect( Object.keys( state.editHistoryIndex ) ).toEqual( [ '1', '2' ] );
		} );
	} );

	describe( 'EDITOR_CHANGE_ID', () => {
		it( 'sets the editor id', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.EDITOR_CHANGE_ID,
				payload: { editorId: 42 },
			} as unknown as ReducerAction );

			expect( state.editorId ).toBe( 42 );
		} );

		it( 'can set editor id to undefined', () => {
			const active: State = { ...initialState, editorId: 42 };
			const state = reducer( active, {
				type: ACTION_TYPES.EDITOR_CHANGE_ID,
				payload: { editorId: undefined },
			} as unknown as ReducerAction );

			expect( state.editorId ).toBeUndefined();
		} );
	} );

	describe( 'START_EDITING_RECORD', () => {
		it( 'stores the editable entity and sets editor id', () => {
			const editableEntity = { id: 5, title: 'Test' } as unknown as EditablePopup;
			const state = reducer( initialState, {
				type: ACTION_TYPES.START_EDITING_RECORD,
				payload: { id: 5, editableEntity, setEditorId: true },
			} as unknown as ReducerAction );

			expect( state.editedEntities[ 5 ] ).toEqual( editableEntity );
			expect( state.editorId ).toBe( 5 );
		} );

		it( 'does not change editor id when setEditorId is false', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.START_EDITING_RECORD,
				payload: {
					id: 5,
					editableEntity: { id: 5 } as unknown as EditablePopup,
					setEditorId: false,
				},
			} as unknown as ReducerAction );

			expect( state.editedEntities[ 5 ] ).toBeDefined();
			expect( state.editorId ).toBeUndefined();
		} );
	} );

	describe( 'EDIT_RECORD', () => {
		it( 'appends edits to history', () => {
			const edits = [ { op: 'replace', path: '/title', value: 'New' } ];
			const state = reducer( initialState, {
				type: ACTION_TYPES.EDIT_RECORD,
				payload: { id: 1, edits },
			} as unknown as ReducerAction );

			expect( state.editHistory[ 1 ] ).toEqual( [ edits ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 0 );
		} );

		it( 'clears future history when editing from mid-history', () => {
			const existing: State = {
				...initialState,
				editHistory: {
					1: [
						[ { op: 'replace', path: '/title', value: 'A' } ],
						[ { op: 'replace', path: '/title', value: 'B' } ],
						[ { op: 'replace', path: '/title', value: 'C' } ],
					] as unknown as Operation[][],
				},
				editHistoryIndex: { 1: 0 },
			};

			const newEdits = [ { op: 'replace', path: '/title', value: 'D' } ];
			const state = reducer( existing, {
				type: ACTION_TYPES.EDIT_RECORD,
				payload: { id: 1, edits: newEdits },
			} as unknown as ReducerAction );

			// Should keep only the first edit and add the new one.
			expect( state.editHistory[ 1 ] ).toHaveLength( 2 );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );
	} );

	describe( 'UNDO_EDIT_RECORD', () => {
		it( 'decrements the history index', () => {
			const existing: State = {
				...initialState,
				editHistoryIndex: { 1: 2 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} as unknown as ReducerAction );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'does not go below -1', () => {
			const existing: State = {
				...initialState,
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 5 },
			} as unknown as ReducerAction );

			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
		} );
	} );

	describe( 'REDO_EDIT_RECORD', () => {
		it( 'increments the history index', () => {
			const existing: State = {
				...initialState,
				editHistory: {
					1: [ [], [], [] ] as unknown as Operation[][],
				},
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.REDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} as unknown as ReducerAction );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'does not exceed max history length', () => {
			const existing: State = {
				...initialState,
				editHistory: {
					1: [ [], [] ] as unknown as Operation[][],
				},
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.REDO_EDIT_RECORD,
				payload: { id: 1, steps: 99 },
			} as unknown as ReducerAction );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'stays at current index when no history exists', () => {
			const existing: State = {
				...initialState,
				editHistoryIndex: { 1: -1 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.REDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} as unknown as ReducerAction );

			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
		} );
	} );

	describe( 'SAVE_EDITED_RECORD', () => {
		it( 'removes saved edits and resets index to -1', () => {
			const existing: State = {
				...initialState,
				editedEntities: {
					1: { id: 1, title: 'Old' } as unknown as EditablePopup,
				},
				editHistory: {
					1: [ [], [], [] ] as unknown as Operation[][],
				},
				editHistoryIndex: { 1: 1 },
			};

			const editedEntity = { id: 1, title: 'Saved' } as unknown as EditablePopup;
			const state = reducer( existing, {
				type: ACTION_TYPES.SAVE_EDITED_RECORD,
				payload: { id: 1, historyIndex: 1, editedEntity },
			} as unknown as ReducerAction );

			expect( state.editedEntities[ 1 ] ).toEqual( editedEntity );
			expect( state.editHistory[ 1 ] ).toHaveLength( 1 );
			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
		} );
	} );

	describe( 'RESET_EDIT_RECORD', () => {
		it( 'removes all edit data for the record', () => {
			const existing: State = {
				...initialState,
				editedEntities: {
					1: { id: 1 } as unknown as EditablePopup,
					2: { id: 2 } as unknown as EditablePopup,
				},
				editHistory: { 1: [], 2: [] },
				editHistoryIndex: { 1: 0, 2: 0 },
			};

			const state = reducer( existing, {
				type: ACTION_TYPES.RESET_EDIT_RECORD,
				payload: { id: 1 },
			} as unknown as ReducerAction );

			expect( state.editedEntities[ 1 ] ).toBeUndefined();
			expect( state.editHistory[ 1 ] ).toBeUndefined();
			expect( state.editHistoryIndex[ 1 ] ).toBeUndefined();
			// Record 2 is untouched.
			expect( state.editedEntities[ 2 ] ).toBeDefined();
		} );
	} );

	describe( 'CHANGE_ACTION_STATUS', () => {
		it( 'sets resolution status for an action', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'getPopup',
					status: DispatchStatus.Resolving,
					message: undefined,
				},
			} as unknown as ReducerAction );

			expect( state.resolutionState.getPopup ).toEqual( {
				status: DispatchStatus.Resolving,
				error: undefined,
			} );
		} );

		it( 'stores error message on failure', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'savePopup',
					status: DispatchStatus.Error,
					message: 'Failed to save',
				},
			} as unknown as ReducerAction );

			expect( state.resolutionState.savePopup ).toEqual( {
				status: DispatchStatus.Error,
				error: 'Failed to save',
			} );
		} );
	} );

	describe( 'INVALIDATE_RESOLUTION', () => {
		it( 'clears resolution for a specific operation/id', () => {
			const existing: State = {
				...initialState,
				resolutionState: {
					getPopup: {
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
				payload: { id: 1, operation: 'getPopup' },
			} as unknown as ReducerAction );

			// ID 1 should be invalidated (set to undefined).
			expect( state.resolutionState.getPopup?.[ 1 ] ).toBeUndefined();
			// ID 2 should remain intact.
			expect( state.resolutionState.getPopup?.[ 2 ] ).toEqual( {
				status: DispatchStatus.Success,
			} );
		} );
	} );
} );
