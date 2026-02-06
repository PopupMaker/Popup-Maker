import { applyPatch } from 'fast-json-patch';
import { applyFilters } from '@wordpress/hooks';

import { DispatchStatus } from '../../constants';
import { defaultValues } from '../constants';

import type { State } from '../reducer';

// Mock dependencies before importing selectors.
jest.mock( 'fast-json-patch', () => ( {
	applyPatch: jest.fn( ( document, patch ) => ( {
		newDocument: patch.reduce(
			(
				doc: Record< string, unknown >,
				op: { path: string; value: unknown }
			) => {
				if ( op.path ) {
					const key = op.path.replace( '/', '' );
					return { ...doc, [ key ]: op.value };
				}
				return doc;
			},
			{ ...document }
		),
	} ) ),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn(
		( _hookName: string, value: unknown ) => value
	),
} ) );

jest.mock( '@wordpress/notices', () => ( {
	store: 'core/notices',
} ) );

jest.mock( '@wordpress/data', () => ( {
	createSelector: ( selector: Function ) => selector,
	createRegistrySelector: ( fn: Function ) =>
		fn( () => ( {
			getNotices: () => [],
		} ) ),
} ) );

// Now import selectors — mocks are in place.
import selectors from '../selectors';

// Helper to create a mock CTA record.
const mockCta = ( id: number, title = `CTA ${ id }` ) =>
	( {
		id,
		title,
		status: 'publish',
		settings: { type: 'link', url: '' },
	} ) as any;

// Helper to create a mock editable entity.
const mockEditable = ( id: number, title = `CTA ${ id }` ) =>
	( {
		id,
		title,
		status: 'draft',
		settings: { type: 'link', url: '' },
	} ) as any;

// Base empty state matching initialState shape.
const emptyState: State = {
	byId: {},
	allIds: [],
	queries: {},
	editorId: undefined,
	editedEntities: {},
	editHistory: {},
	editHistoryIndex: {},
	resolutionState: {},
	notices: {},
	errors: { global: null, byId: {} },
};

describe( 'CTA Selectors', () => {
	describe( 'Entity Selectors', () => {
		describe( 'getCallToActions', () => {
			it( 'returns empty array for empty state', () => {
				const result = selectors.getCallToActions( emptyState );
				expect( result ).toEqual( [] );
			} );

			it( 'returns all entities mapped from allIds', () => {
				const state: State = {
					...emptyState,
					byId: { 1: mockCta( 1 ), 2: mockCta( 2 ) },
					allIds: [ 1, 2 ],
				};

				const result = selectors.getCallToActions( state );
				expect( result ).toHaveLength( 2 );
				expect( result[ 0 ].id ).toBe( 1 );
				expect( result[ 1 ].id ).toBe( 2 );
			} );
		} );

		describe( 'getCallToAction', () => {
			it( 'returns undefined for missing id', () => {
				const result = selectors.getCallToAction( emptyState, 999 );
				expect( result ).toBeUndefined();
			} );

			it( 'returns the entity for existing id', () => {
				const cta = mockCta( 5 );
				const state: State = {
					...emptyState,
					byId: { 5: cta },
					allIds: [ 5 ],
				};

				const result = selectors.getCallToAction( state, 5 );
				expect( result ).toBe( cta );
			} );
		} );

		describe( 'getFetchError', () => {
			it( 'returns global error when no id provided', () => {
				const state: State = {
					...emptyState,
					errors: { global: 'Server error', byId: {} },
				};

				const result = selectors.getFetchError( state );
				expect( result ).toBe( 'Server error' );
			} );

			it( 'returns entity-specific error when id is a number', () => {
				const state: State = {
					...emptyState,
					errors: { global: null, byId: { 5: 'Not found' } },
				};

				const result = selectors.getFetchError( state, 5 );
				expect( result ).toBe( 'Not found' );
			} );

			it( 'returns undefined when entity has no error', () => {
				const result = selectors.getFetchError( emptyState, 123 );
				expect( result ).toBeUndefined();
			} );
		} );

		describe( 'getFiltered', () => {
			const state: State = {
				...emptyState,
				byId: {
					1: mockCta( 1, 'Alpha' ),
					2: mockCta( 2, 'Beta' ),
					3: mockCta( 3, 'Alpha Two' ),
				},
				allIds: [ 1, 2, 3 ],
			};

			it( 'filters entities by predicate', () => {
				const result = selectors.getFiltered(
					state,
					( cta ) => cta.title.startsWith( 'Alpha' )
				);

				expect( result ).toHaveLength( 2 );
				expect( result[ 0 ].id ).toBe( 1 );
				expect( result[ 1 ].id ).toBe( 3 );
			} );

			it( 'returns empty array when nothing matches', () => {
				const result = selectors.getFiltered(
					state,
					() => false
				);

				expect( result ).toEqual( [] );
			} );
		} );

		describe( 'getFilteredIds', () => {
			it( 'returns ids matching predicate', () => {
				const state: State = {
					...emptyState,
					byId: {
						1: { ...mockCta( 1 ), status: 'draft' },
						2: { ...mockCta( 2 ), status: 'publish' },
					},
					allIds: [ 1, 2 ],
				};

				const result = selectors.getFilteredIds(
					state,
					( cta ) => cta.status === 'publish'
				);

				expect( result ).toEqual( [ 2 ] );
			} );
		} );
	} );

	describe( 'Editor Selectors', () => {
		describe( 'getEditorId', () => {
			it( 'returns undefined when not set', () => {
				expect(
					selectors.getEditorId( emptyState )
				).toBeUndefined();
			} );

			it( 'returns the editor id', () => {
				const state = { ...emptyState, editorId: 10 };
				expect( selectors.getEditorId( state ) ).toBe( 10 );
			} );
		} );

		describe( 'isEditorActive', () => {
			it( 'returns false when editorId is undefined', () => {
				expect(
					selectors.isEditorActive( emptyState )
				).toBe( false );
			} );

			it( 'returns true when editorId is a positive number', () => {
				const state = { ...emptyState, editorId: 5 };
				expect( selectors.isEditorActive( state ) ).toBe( true );
			} );

			it( 'returns false when editorId is 0', () => {
				const state = { ...emptyState, editorId: 0 };
				expect( selectors.isEditorActive( state ) ).toBe( false );
			} );

			it( 'returns true when editorId is "new"', () => {
				const state = { ...emptyState, editorId: 'new' as any };
				expect( selectors.isEditorActive( state ) ).toBe( true );
			} );
		} );

		describe( 'hasEditedEntity', () => {
			it( 'returns false when no edited entity exists', () => {
				expect(
					selectors.hasEditedEntity( emptyState, 1 )
				).toBe( false );
			} );

			it( 'returns true when edited entity exists', () => {
				const state: State = {
					...emptyState,
					editedEntities: { 1: mockEditable( 1 ) },
				};
				expect( selectors.hasEditedEntity( state, 1 ) ).toBe( true );
			} );
		} );

		describe( 'getEditedEntity', () => {
			it( 'returns undefined for missing entity', () => {
				expect(
					selectors.getEditedEntity( emptyState, 1 )
				).toBeUndefined();
			} );

			it( 'returns the edited entity', () => {
				const entity = mockEditable( 1 );
				const state: State = {
					...emptyState,
					editedEntities: { 1: entity },
				};
				expect( selectors.getEditedEntity( state, 1 ) ).toBe(
					entity
				);
			} );
		} );

		describe( 'getEntityEditHistory', () => {
			it( 'returns undefined when no history', () => {
				expect(
					selectors.getEntityEditHistory( emptyState, 1 )
				).toBeUndefined();
			} );

			it( 'returns the edit history array', () => {
				const history = [
					[ { op: 'replace', path: '/title', value: 'X' } ],
				];
				const state: State = {
					...emptyState,
					editHistory: { 1: history } as any,
				};
				expect(
					selectors.getEntityEditHistory( state, 1 )
				).toBe( history );
			} );
		} );

		describe( 'getCurrentEditHistoryIndex', () => {
			it( 'returns undefined when not set', () => {
				expect(
					selectors.getCurrentEditHistoryIndex( emptyState, 1 )
				).toBeUndefined();
			} );

			it( 'returns current index', () => {
				const state: State = {
					...emptyState,
					editHistoryIndex: { 1: 2 },
				};
				expect(
					selectors.getCurrentEditHistoryIndex( state, 1 )
				).toBe( 2 );
			} );
		} );

		describe( 'hasEdits', () => {
			it( 'returns false when no history', () => {
				expect( selectors.hasEdits( emptyState, 1 ) ).toBeFalsy();
			} );

			it( 'returns false when history is empty array', () => {
				const state: State = {
					...emptyState,
					editHistory: { 1: [] },
				};
				expect( selectors.hasEdits( state, 1 ) ).toBe( false );
			} );

			it( 'returns true when history has entries', () => {
				const state: State = {
					...emptyState,
					editHistory: {
						1: [ [ { op: 'replace', path: '/title', value: 'X' } ] ],
					} as any,
				};
				expect( selectors.hasEdits( state, 1 ) ).toBe( true );
			} );
		} );

		describe( 'hasUndo', () => {
			it( 'returns false when no edit history index', () => {
				expect( selectors.hasUndo( emptyState, 1 ) ).toBe( false );
			} );

			it( 'returns false when index is -1', () => {
				const state: State = {
					...emptyState,
					editHistory: { 1: [ [] ] },
					editHistoryIndex: { 1: -1 },
				};
				expect( selectors.hasUndo( state, 1 ) ).toBe( false );
			} );

			it( 'returns true when index is 0 or above', () => {
				const state: State = {
					...emptyState,
					editHistory: { 1: [ [] ] },
					editHistoryIndex: { 1: 0 },
				};
				expect( selectors.hasUndo( state, 1 ) ).toBe( true );
			} );
		} );

		describe( 'hasRedo', () => {
			it( 'returns false when no edit history', () => {
				expect( selectors.hasRedo( emptyState, 1 ) ).toBe( false );
			} );

			it( 'returns false when at last index', () => {
				const state: State = {
					...emptyState,
					editHistory: { 1: [ [], [] ] },
					editHistoryIndex: { 1: 1 },
				};
				expect( selectors.hasRedo( state, 1 ) ).toBe( false );
			} );

			it( 'returns true when index is before last', () => {
				const state: State = {
					...emptyState,
					editHistory: { 1: [ [], [], [] ] },
					editHistoryIndex: { 1: 0 },
				};
				expect( selectors.hasRedo( state, 1 ) ).toBe( true );
			} );
		} );

		describe( 'getEditedCallToAction', () => {
			beforeEach( () => {
				( applyPatch as jest.Mock ).mockClear();
			} );

			it( 'returns undefined when no base entity', () => {
				expect(
					selectors.getEditedCallToAction( emptyState, 1 )
				).toBeUndefined();
			} );

			it( 'returns base entity when historyIndex is -1', () => {
				const entity = mockEditable( 1, 'Base' );
				const state: State = {
					...emptyState,
					editedEntities: { 1: entity },
					editHistoryIndex: { 1: -1 },
				};

				const result = selectors.getEditedCallToAction( state, 1 );
				expect( result ).toBe( entity );
				expect( applyPatch ).not.toHaveBeenCalled();
			} );

			it( 'returns base entity when no edit history', () => {
				const entity = mockEditable( 1, 'Base' );
				const state: State = {
					...emptyState,
					editedEntities: { 1: entity },
					editHistory: { 1: [] },
					editHistoryIndex: { 1: -1 },
				};

				const result = selectors.getEditedCallToAction( state, 1 );
				expect( result ).toBe( entity );
			} );

			it( 'applies patches up to historyIndex', () => {
				const entity = mockEditable( 1, 'Base' );
				const patches = [
					[ { op: 'replace', path: '/title', value: 'First' } ],
					[ { op: 'replace', path: '/title', value: 'Second' } ],
				];

				const state: State = {
					...emptyState,
					editedEntities: { 1: entity },
					editHistory: { 1: patches } as any,
					editHistoryIndex: { 1: 0 }, // Only apply first patch.
				};

				const result = selectors.getEditedCallToAction( state, 1 );

				// applyPatch should have been called once (one patch set at index 0).
				expect( applyPatch ).toHaveBeenCalledTimes( 1 );
				expect( result.title ).toBe( 'First' );
			} );
		} );

		describe( 'getCurrentEditorValues', () => {
			it( 'returns undefined when editorId is undefined', () => {
				expect(
					selectors.getCurrentEditorValues( emptyState )
				).toBeUndefined();
			} );

			it( 'returns edited entity for current editorId', () => {
				const entity = mockEditable( 3, 'Editor' );
				const state: State = {
					...emptyState,
					editorId: 3,
					editedEntities: { 3: entity },
					editHistoryIndex: { 3: -1 },
				};

				const result = selectors.getCurrentEditorValues( state );
				expect( result ).toBe( entity );
			} );
		} );

		describe( 'getDefaultValues', () => {
			it( 'returns default values with filter applied', () => {
				const result = selectors.getDefaultValues( emptyState );
				expect( applyFilters ).toHaveBeenCalledWith(
					'popupMaker.callToAction.defaultValues',
					defaultValues
				);
				expect( result ).toEqual( defaultValues );
			} );
		} );
	} );

	describe( 'Resolution Selectors', () => {
		describe( 'getResolutionState', () => {
			it( 'returns IDLE when no resolution state exists', () => {
				const result = selectors.getResolutionState( emptyState, 5 );
				expect( result ).toEqual( {
					status: DispatchStatus.Idle,
				} );
			} );

			it( 'returns existing resolution state', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: { status: DispatchStatus.Success },
					},
				};

				const result = selectors.getResolutionState( state, 5 );
				expect( result ).toEqual( {
					status: DispatchStatus.Success,
				} );
			} );
		} );

		describe( 'isIdle', () => {
			it( 'returns true for missing resolution', () => {
				expect( selectors.isIdle( emptyState, 5 ) ).toBe( true );
			} );

			it( 'returns false when resolving', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: { status: DispatchStatus.Resolving },
					},
				};
				expect( selectors.isIdle( state, 5 ) ).toBe( false );
			} );
		} );

		describe( 'isResolving', () => {
			it( 'returns false when idle', () => {
				expect( selectors.isResolving( emptyState, 5 ) ).toBe(
					false
				);
			} );

			it( 'returns true when resolving', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: { status: DispatchStatus.Resolving },
					},
				};
				expect( selectors.isResolving( state, 5 ) ).toBe( true );
			} );
		} );

		describe( 'hasResolved', () => {
			it( 'returns false when idle', () => {
				expect( selectors.hasResolved( emptyState, 5 ) ).toBe(
					false
				);
			} );

			it( 'returns true when success', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: { status: DispatchStatus.Success },
					},
				};
				expect( selectors.hasResolved( state, 5 ) ).toBe( true );
			} );
		} );

		describe( 'hasFailed', () => {
			it( 'returns false when idle', () => {
				expect( selectors.hasFailed( emptyState, 5 ) ).toBe( false );
			} );

			it( 'returns true when error', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: { status: DispatchStatus.Error },
					},
				};
				expect( selectors.hasFailed( state, 5 ) ).toBe( true );
			} );
		} );

		describe( 'getResolutionError', () => {
			it( 'returns undefined when no error', () => {
				expect(
					selectors.getResolutionError( emptyState, 5 )
				).toBeUndefined();
			} );

			it( 'returns the error message', () => {
				const state: State = {
					...emptyState,
					resolutionState: {
						5: {
							status: DispatchStatus.Error,
							error: 'Network failure',
						},
					},
				};
				expect(
					selectors.getResolutionError( state, 5 )
				).toBe( 'Network failure' );
			} );
		} );
	} );
} );
