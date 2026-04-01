import { reducer } from '../reducer';
import { initialState, ACTION_TYPES } from '../constants';

import type { State, ReducerAction } from '../reducer';
import type { CallToAction, EditableCta } from '../types';
import type { Operation } from 'fast-json-patch';

const {
	RECEIVE_RECORD,
	RECEIVE_RECORDS,
	RECEIVE_QUERY_RECORDS,
	RECEIVE_ERROR,
	PURGE_RECORD,
	PURGE_RECORDS,
	EDITOR_CHANGE_ID,
	START_EDITING_RECORD,
	EDIT_RECORD,
	UNDO_EDIT_RECORD,
	REDO_EDIT_RECORD,
	SAVE_EDITED_RECORD,
	RESET_EDIT_RECORD,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

// Helper to create a mock CTA record.
const mockCta = ( id: number, title = `CTA ${ id }` ) =>
	( {
		id,
		title,
		status: 'publish',
		settings: { type: 'link', url: '' },
	} ) as unknown as CallToAction< 'edit' >;

// Helper to create a mock editable entity.
const mockEditable = ( id: number, title = `CTA ${ id }` ) =>
	( {
		id,
		title,
		status: 'draft',
		settings: { type: 'link', url: '' },
	} ) as unknown as EditableCta;

describe( 'CTA Reducer', () => {
	it( 'returns initial state for unknown action', () => {
		const state = reducer( undefined, { type: 'UNKNOWN' } as unknown as ReducerAction );
		expect( state ).toEqual( initialState );
	} );

	it( 'returns existing state for unknown action', () => {
		const existing = { ...initialState, editorId: 42 };
		const state = reducer( existing, { type: 'NONSENSE' } as unknown as ReducerAction );
		expect( state ).toBe( existing );
	} );

	describe( 'RECEIVE_RECORD', () => {
		it( 'adds a single record to empty state', () => {
			const record = mockCta( 1 );
			const state = reducer( initialState, {
				type: RECEIVE_RECORD,
				payload: { record },
			} );

			expect( state.byId[ 1 ] ).toBe( record );
			expect( state.allIds ).toEqual( [ 1 ] );
		} );

		it( 'deduplicates allIds for existing record', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockCta( 1, 'Old' ) },
				allIds: [ 1 ],
			};

			const updated = mockCta( 1, 'Updated' );
			const state = reducer( existing, {
				type: RECEIVE_RECORD,
				payload: { record: updated },
			} );

			expect( state.allIds ).toEqual( [ 1 ] );
			expect( state.byId[ 1 ].title ).toBe( 'Updated' );
		} );

		it( 'appends new id to existing allIds', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockCta( 1 ) },
				allIds: [ 1 ],
			};

			const state = reducer( existing, {
				type: RECEIVE_RECORD,
				payload: { record: mockCta( 2 ) },
			} );

			expect( state.allIds ).toEqual( [ 1, 2 ] );
		} );
	} );

	describe( 'RECEIVE_RECORDS', () => {
		it( 'adds multiple records to empty state', () => {
			const records = [ mockCta( 1 ), mockCta( 2 ) ];
			const state = reducer( initialState, {
				type: RECEIVE_RECORDS,
				payload: { records },
			} );

			expect( state.allIds ).toEqual( [ 1, 2 ] );
			expect( state.byId[ 1 ] ).toBe( records[ 0 ] );
			expect( state.byId[ 2 ] ).toBe( records[ 1 ] );
		} );

		it( 'deduplicates allIds when merging with existing', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockCta( 1 ) },
				allIds: [ 1 ],
			};

			const records = [ mockCta( 1, 'Updated' ), mockCta( 3 ) ];
			const state = reducer( existing, {
				type: RECEIVE_RECORDS,
				payload: { records },
			} );

			expect( state.allIds ).toEqual( [ 1, 3 ] );
			expect( state.byId[ 1 ].title ).toBe( 'Updated' );
		} );

		it( 'does not set queries when no query provided', () => {
			const state = reducer( initialState, {
				type: RECEIVE_RECORDS,
				payload: { records: [ mockCta( 1 ) ] },
			} );

			expect( state.queries ).toEqual( {} );
		} );
	} );

	describe( 'RECEIVE_QUERY_RECORDS', () => {
		it( 'stores query mapping alongside records', () => {
			const query = { status: 'publish', per_page: 10 };
			const records = [ mockCta( 5 ), mockCta( 6 ) ];

			const state = reducer( initialState, {
				type: RECEIVE_QUERY_RECORDS,
				payload: { records, query },
			} );

			expect( state.allIds ).toEqual( [ 5, 6 ] );
			expect( state.queries?.[ JSON.stringify( query ) ] ).toEqual( [
				5, 6,
			] );
		} );

		it( 'preserves existing queries', () => {
			const q1 = { status: 'draft' };
			const existing: State = {
				...initialState,
				queries: { [ JSON.stringify( q1 ) ]: [ 1 ] },
			};

			const q2 = { status: 'publish' };
			const state = reducer( existing, {
				type: RECEIVE_QUERY_RECORDS,
				payload: {
					records: [ mockCta( 2 ) ],
					query: q2,
				},
			} );

			expect( state.queries?.[ JSON.stringify( q1 ) ] ).toEqual( [ 1 ] );
			expect( state.queries?.[ JSON.stringify( q2 ) ] ).toEqual( [ 2 ] );
		} );
	} );

	describe( 'RECEIVE_ERROR', () => {
		it( 'sets a global error when no id provided', () => {
			const freshState = {
				...initialState,
				errors: { global: null, byId: {} },
			};
			const state = reducer( freshState, {
				type: RECEIVE_ERROR,
				payload: { error: 'Server error' },
			} );

			expect( state.errors.global ).toBe( 'Server error' );
			expect( state.errors.byId ).toEqual( {} );
		} );

		it( 'sets an entity-specific error when id provided', () => {
			const freshState = {
				...initialState,
				errors: { global: null, byId: {} },
			};
			const state = reducer( freshState, {
				type: RECEIVE_ERROR,
				payload: { error: 'Not found', id: 42 },
			} );

			expect( state.errors.byId[ 42 ] ).toBe( 'Not found' );
			// The global error preserves whatever the previous state had.
			expect( state.errors.global ).toBeNull();
		} );
	} );

	describe( 'PURGE_RECORD', () => {
		// BUG: Same string/number key mismatch as PURGE_RECORDS.
		// allIds is purged correctly (number-to-number), but byId is not
		// (Object.entries string key vs number id). See BUGS-FOUND-BY-TESTS.md #1.

		const stateWithEdits: State = {
			...initialState,
			byId: { 1: mockCta( 1 ), 2: mockCta( 2 ) },
			allIds: [ 1, 2 ],
			editedEntities: { 1: mockEditable( 1 ) },
			editHistory: { 1: [ [ { op: 'replace', path: '/title', value: 'X' } as Operation ] ] },
			editHistoryIndex: { 1: 0 },
		};

		it( 'removes entity from allIds', () => {
			const state = reducer( stateWithEdits, {
				type: PURGE_RECORD,
				payload: { id: 1 },
			} );

			expect( state.allIds ).toEqual( [ 2 ] );
		} );

		it( 'BUG: does NOT remove byId entry (string/number key mismatch)', () => {
			const state = reducer( stateWithEdits, {
				type: PURGE_RECORD,
				payload: { id: 1 },
			} );

			// byId[1] SHOULD be removed but isn't due to the bug.
			expect( state.byId[ 1 ] ).toBeDefined();
			expect( state.byId[ 2 ] ).toBeDefined();
		} );

		it( 'returns state unchanged for empty ids', () => {
			const state = reducer( stateWithEdits, {
				type: PURGE_RECORD,
				payload: { id: null },
			} as unknown as ReducerAction );

			expect( state ).toBe( stateWithEdits );
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
				byId: { 1: mockCta( 1 ), 2: mockCta( 2 ), 3: mockCta( 3 ) },
				allIds: [ 1, 2, 3 ],
				editedEntities: { 1: mockEditable( 1 ), 2: mockEditable( 2 ) },
				editHistory: { 1: [], 2: [] },
				editHistoryIndex: { 1: -1, 2: -1 },
			};

			const state = reducer( existing, {
				type: PURGE_RECORDS,
				payload: { ids: [ 1, 2 ] },
			} );

			expect( state.allIds ).toEqual( [ 3 ] );
		} );

		it( 'BUG: does NOT remove byId entries (string/number key mismatch)', () => {
			const existing: State = {
				...initialState,
				byId: { 1: mockCta( 1 ), 2: mockCta( 2 ), 3: mockCta( 3 ) },
				allIds: [ 1, 2, 3 ],
				editedEntities: { 1: mockEditable( 1 ), 2: mockEditable( 2 ) },
				editHistory: { 1: [], 2: [] },
				editHistoryIndex: { 1: -1, 2: -1 },
			};

			const state = reducer( existing, {
				type: PURGE_RECORDS,
				payload: { ids: [ 1, 2 ] },
			} );

			// These SHOULD be purged but aren't due to the bug.
			expect( Object.keys( state.byId ) ).toEqual( [ '1', '2', '3' ] );
			expect( Object.keys( state.editedEntities ) ).toEqual( [ '1', '2' ] );
			expect( Object.keys( state.editHistory ) ).toEqual( [ '1', '2' ] );
			expect( Object.keys( state.editHistoryIndex ) ).toEqual( [ '1', '2' ] );
		} );

		it( 'returns state unchanged for empty ids array', () => {
			const state = reducer( initialState, {
				type: PURGE_RECORDS,
				payload: { ids: [] },
			} );

			expect( state ).toBe( initialState );
		} );
	} );

	describe( 'EDITOR_CHANGE_ID', () => {
		it( 'sets editorId', () => {
			const state = reducer( initialState, {
				type: EDITOR_CHANGE_ID,
				payload: { editorId: 5 },
			} );

			expect( state.editorId ).toBe( 5 );
		} );

		it( 'clears editorId to undefined', () => {
			const existing = { ...initialState, editorId: 5 };
			const state = reducer( existing, {
				type: EDITOR_CHANGE_ID,
				payload: { editorId: undefined },
			} );

			expect( state.editorId ).toBeUndefined();
		} );
	} );

	describe( 'START_EDITING_RECORD', () => {
		it( 'adds editable entity without setting editorId', () => {
			const entity = mockEditable( 1 );
			const state = reducer( initialState, {
				type: START_EDITING_RECORD,
				payload: { id: 1, editableEntity: entity, setEditorId: false },
			} );

			expect( state.editedEntities[ 1 ] ).toBe( entity );
			expect( state.editorId ).toBeUndefined();
		} );

		it( 'adds editable entity and sets editorId when flag is true', () => {
			const entity = mockEditable( 1 );
			const state = reducer( initialState, {
				type: START_EDITING_RECORD,
				payload: { id: 1, editableEntity: entity, setEditorId: true },
			} );

			expect( state.editedEntities[ 1 ] ).toBe( entity );
			expect( state.editorId ).toBe( 1 );
		} );
	} );

	describe( 'EDIT_RECORD', () => {
		const patchA: Operation[] = [
			{ op: 'replace', path: '/title', value: 'A' },
		];
		const patchB: Operation[] = [
			{ op: 'replace', path: '/title', value: 'B' },
		];
		const patchC: Operation[] = [
			{ op: 'replace', path: '/title', value: 'C' },
		];

		it( 'appends edit to empty history', () => {
			const state = reducer( initialState, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchA },
			} );

			expect( state.editHistory[ 1 ] ).toEqual( [ patchA ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 0 );
		} );

		it( 'appends edit to existing history at the end', () => {
			const existing: State = {
				...initialState,
				editHistory: { 1: [ patchA ] },
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchB },
			} );

			expect( state.editHistory[ 1 ] ).toEqual( [ patchA, patchB ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'clears future history when editing after undo (branching)', () => {
			const existing: State = {
				...initialState,
				editHistory: { 1: [ patchA, patchB, patchC ] },
				editHistoryIndex: { 1: 0 }, // Undone to first edit.
			};

			const patchD: Operation[] = [
				{ op: 'replace', path: '/title', value: 'D' },
			];

			const state = reducer( existing, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchD },
			} );

			// Should keep only patchA, then add patchD.
			expect( state.editHistory[ 1 ] ).toEqual( [ patchA, patchD ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );
	} );

	describe( 'UNDO_EDIT_RECORD', () => {
		it( 'decrements index by step amount', () => {
			const existing: State = {
				...initialState,
				editHistoryIndex: { 1: 2 },
			};

			const state = reducer( existing, {
				type: UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'clamps at -1 minimum', () => {
			const existing: State = {
				...initialState,
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 5 },
			} );

			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
		} );

		it( 'handles missing history index (defaults to -1)', () => {
			const state = reducer( initialState, {
				type: UNDO_EDIT_RECORD,
				payload: { id: 99, steps: 1 },
			} );

			// -1 - 1 = -2, clamped to -1.
			expect( state.editHistoryIndex[ 99 ] ).toBe( -1 );
		} );
	} );

	describe( 'REDO_EDIT_RECORD', () => {
		it( 'increments index by step amount', () => {
			const existing: State = {
				...initialState,
				editHistory: { 1: [ [], [], [] ] },
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: REDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'clamps at max index (history length - 1)', () => {
			const existing: State = {
				...initialState,
				editHistory: { 1: [ [], [] ] },
				editHistoryIndex: { 1: 0 },
			};

			const state = reducer( existing, {
				type: REDO_EDIT_RECORD,
				payload: { id: 1, steps: 10 },
			} );

			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );
		} );

		it( 'does not change index when no history exists', () => {
			const state = reducer( initialState, {
				type: REDO_EDIT_RECORD,
				payload: { id: 99, steps: 1 },
			} );

			// No history → maxIndex = -1, currentIndex = -1, stays -1.
			expect( state.editHistoryIndex[ 99 ] ).toBe( -1 );
		} );
	} );

	describe( 'SAVE_EDITED_RECORD', () => {
		it( 'preserves edits after historyIndex and resets index', () => {
			const patchA: Operation[] = [
				{ op: 'replace', path: '/title', value: 'A' },
			];
			const patchB: Operation[] = [
				{ op: 'replace', path: '/title', value: 'B' },
			];
			const patchC: Operation[] = [
				{ op: 'replace', path: '/title', value: 'C' },
			];

			const existing: State = {
				...initialState,
				editHistory: { 1: [ patchA, patchB, patchC ] },
				editHistoryIndex: { 1: 1 }, // Currently at patchB.
				editedEntities: { 1: mockEditable( 1 ) },
			};

			const savedEntity = mockEditable( 1, 'Saved' );
			const state = reducer( existing, {
				type: SAVE_EDITED_RECORD,
				payload: {
					id: 1,
					historyIndex: 1,
					editedEntity: savedEntity,
				},
			} );

			// Should keep patchC (index 2, which is after historyIndex 1).
			expect( state.editHistory[ 1 ] ).toEqual( [ patchC ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
			expect( state.editedEntities[ 1 ] ).toBe( savedEntity );
		} );

		it( 'clears all history when saved at last index', () => {
			const existing: State = {
				...initialState,
				editHistory: { 1: [ [] ] },
				editHistoryIndex: { 1: 0 },
				editedEntities: { 1: mockEditable( 1 ) },
			};

			const state = reducer( existing, {
				type: SAVE_EDITED_RECORD,
				payload: {
					id: 1,
					historyIndex: 0,
					editedEntity: mockEditable( 1, 'Saved' ),
				},
			} );

			expect( state.editHistory[ 1 ] ).toEqual( [] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );
		} );
	} );

	describe( 'RESET_EDIT_RECORD', () => {
		it( 'removes all edit data for a specific entity', () => {
			const existing: State = {
				...initialState,
				editedEntities: {
					1: mockEditable( 1 ),
					2: mockEditable( 2 ),
				},
				editHistory: { 1: [ [] ], 2: [ [] ] },
				editHistoryIndex: { 1: 0, 2: 0 },
			};

			const state = reducer( existing, {
				type: RESET_EDIT_RECORD,
				payload: { id: 1 },
			} );

			expect( state.editedEntities[ 1 ] ).toBeUndefined();
			expect( state.editHistory[ 1 ] ).toBeUndefined();
			expect( state.editHistoryIndex[ 1 ] ).toBeUndefined();
			// Entity 2 should be untouched.
			expect( state.editedEntities[ 2 ] ).toBeDefined();
			expect( state.editHistory[ 2 ] ).toBeDefined();
			expect( state.editHistoryIndex[ 2 ] ).toBeDefined();
		} );
	} );

	describe( 'CHANGE_ACTION_STATUS', () => {
		it( 'sets resolution state with status and message', () => {
			const state = reducer( initialState, {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'getCallToAction(5)',
					status: 'RESOLVING',
					message: undefined,
				},
			} );

			expect( state.resolutionState[ 'getCallToAction(5)' ] ).toEqual( {
				status: 'RESOLVING',
				error: undefined,
			} );
		} );

		it( 'sets error message on failure', () => {
			const state = reducer( initialState, {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: 'getCallToAction(5)',
					status: 'ERROR',
					message: 'Not found',
				},
			} );

			expect( state.resolutionState[ 'getCallToAction(5)' ] ).toEqual( {
				status: 'ERROR',
				error: 'Not found',
			} );
		} );
	} );

	describe( 'INVALIDATE_RESOLUTION', () => {
		it( 'sets resolution for operation+id to undefined', () => {
			const existing: State = {
				...initialState,
				resolutionState: {
					getCallToAction: {
						5: { status: 'SUCCESS' },
						6: { status: 'SUCCESS' },
					},
				},
			};

			const state = reducer( existing, {
				type: INVALIDATE_RESOLUTION,
				payload: { id: 5, operation: 'getCallToAction' },
			} );

			// ID 5 should be invalidated.
			expect(
				state.resolutionState[ 'getCallToAction' ]?.[ 5 ]
			).toBeUndefined();
			// ID 6 should remain.
			expect(
				state.resolutionState[ 'getCallToAction' ]?.[ 6 ]
			).toEqual( { status: 'SUCCESS' } );
		} );
	} );

	describe( 'Undo/Redo integration', () => {
		it( 'full undo/redo/branch cycle', () => {
			const patchA: Operation[] = [
				{ op: 'replace', path: '/title', value: 'A' },
			];
			const patchB: Operation[] = [
				{ op: 'replace', path: '/title', value: 'B' },
			];

			// Add two edits.
			let state = reducer( initialState, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchA },
			} );
			state = reducer( state, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchB },
			} );

			expect( state.editHistory[ 1 ] ).toEqual( [ patchA, patchB ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );

			// Undo once.
			state = reducer( state, {
				type: UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 0 );

			// Redo once.
			state = reducer( state, {
				type: REDO_EDIT_RECORD,
				payload: { id: 1, steps: 1 },
			} );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 1 );

			// Undo twice, then add new edit → branches.
			state = reducer( state, {
				type: UNDO_EDIT_RECORD,
				payload: { id: 1, steps: 2 },
			} );
			expect( state.editHistoryIndex[ 1 ] ).toBe( -1 );

			const patchC: Operation[] = [
				{ op: 'replace', path: '/title', value: 'C' },
			];
			state = reducer( state, {
				type: EDIT_RECORD,
				payload: { id: 1, edits: patchC },
			} );

			// Should have cleared all previous history and only have patchC.
			expect( state.editHistory[ 1 ] ).toEqual( [ patchC ] );
			expect( state.editHistoryIndex[ 1 ] ).toBe( 0 );
		} );
	} );
} );
