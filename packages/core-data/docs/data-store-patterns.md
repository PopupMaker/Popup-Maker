# Understanding WordPress Data Store Patterns

## Introduction

When working with WordPress's data layer, particularly in modern JavaScript applications, understanding the different patterns for managing state, selectors, and actions is crucial. This guide will explore the core concepts and best practices for implementing efficient and maintainable data stores.

## Core Concepts

### 1. State Management Patterns

WordPress's data layer provides several patterns for managing state. Each serves a specific purpose:

-   **Raw State Access**: Direct state retrieval
-   **Derived State**: Computed values based on state
-   **Cross-Store State**: Data that combines multiple stores
-   **Async State**: Data that needs to be fetched

### 2. Selectors

Selectors are functions that retrieve and compute state. There are three main types:

#### Raw Selectors

```typescript
// Simple state access - use when you just need a direct value
const getCTAs = ( state ) => state.ctas.items;
const getLoading = ( state ) => state.ctas.isLoading;
```

#### Memoized Selectors (createSelector)

```typescript
// createSelector takes two arguments:
// 1. The selector function that computes the value
// 2. The dependencies function that returns an array of values to memoize on

// Example 1: Simple memoized selector
const getActiveCTAs = createSelector(
	// First arg: Main selector function
	( state ) => state.ctas.items.filter( ( cta ) => cta.status === 'active' ),
	// Second arg: Dependencies to memoize on
	( state ) => [ state.ctas.items ]
);

// Example 2: Selector with parameters
const getCTAById = createSelector(
	// First arg: Selector function with parameters
	( state, id: number ) => state.ctas.items.find( ( cta ) => cta.id === id ),
	// Second arg: Dependencies including both state and parameters
	( state, id: number ) => [ state.ctas.items, id ]
);

// Example 3: Using with createRegistrySelector
const getCallToActions = createRegistrySelector( ( select ) =>
	createSelector(
		// First arg: Selector function using registry
		( state ) =>
			select( coreDataStore ).getEntityRecords< CallToAction >(
				'postType',
				'pum_cta',
				{ per_page: -1 }
			),
		// Second arg: Dependencies to memoize on
		( records ) => records?.map( ( record ) => record.id ) ?? []
	)
);
```

Key Points:

1. The first argument is always the selector function that computes your value
2. The second argument is a function that returns an array of dependencies
3. The selector will only recompute when the dependencies change
4. Dependencies should include any values used in the computation
5. For registry selectors, consider what external store values should trigger recomputation

Common Patterns:

```typescript
// Simple state access with memoization
createSelector(
	( state ) => computeSomething( state.value ),
	( state ) => [ state.value ]
);

// With parameters
createSelector(
	( state, param ) => computeSomething( state.value, param ),
	( state, param ) => [ state.value, param ]
);

// With registry access
createRegistrySelector( ( select ) =>
	createSelector(
		( state ) => computeSomething( select( otherStore ).getValue() ),
		( state ) => [ select( otherStore ).getValue() ]
	)
);
```

#### Registry Selectors (createRegistrySelector)

```typescript
// Use when needing data from multiple stores
const getCTAsWithAuthors = createRegistrySelector( ( select ) =>
	createSelector(
		( state ) => state.ctas.items,
		( state ) => select( 'core' ).getUsers(),
		( ctas, users ) =>
			ctas.map( ( cta ) => ( {
				...cta,
				author: users.find( ( u ) => u.id === cta.authorId ),
			} ) )
	)
);
```

### Selector Utilities

`createSelector` returns a memoized function with additional utility methods:

```typescript
const getFilteredCTAs = createSelector(
	( state ) => state.ctas.filter( ( cta ) => cta.status === 'active' ),
	( state ) => [ state.ctas ]
);

// Clear memoization cache
getFilteredCTAs.clear();

// Get current dependencies for debugging
const deps = getFilteredCTAs.getDependants( state );
```

#### .clear()

-   Clears the memoization cache
-   Forces next call to recompute
-   Useful for:
    -   Testing (resetting between tests)
    -   Forcing fresh computation
    -   Handling edge cases where cache needs reset

#### .getDependants()

-   Returns the current dependency array
-   Takes same arguments as selector
-   Useful for:
    -   Debugging dependency tracking
    -   Verifying memoization behavior
    -   Understanding recomputation triggers

Example with Testing:

```typescript
describe( 'getFilteredCTAs', () => {
	beforeEach( () => {
		// Clear cache before each test
		getFilteredCTAs.clear();
	} );

	it( 'should track correct dependencies', () => {
		const state = { ctas: [] };
		const deps = getFilteredCTAs.getDependants( state );
		expect( deps ).toEqual( [ state.ctas ] );
	} );
} );
```

### 3. Actions and Resolvers

Actions handle state changes and side effects. There are two main patterns:

#### Simple Actions

```typescript
// Synchronous state updates
const setCTAs = ( ctas ) => ( {
	type: 'SET_CTAS',
	ctas,
} );
```

#### Thunks/Resolvers

```typescript
// Async operations and side effects
const fetchCTAs =
	() =>
	async ( { dispatch, select, resolveSelect } ) => {
		try {
			// You can use select to get current state
			const isLoading = select.getIsLoading();
			if ( isLoading ) return;

			dispatch.setLoading( true );

			// You can use resolveSelect to wait for async selectors
			const settings = await resolveSelect.getSettings();

			// Direct API calls without needing controls
			const response = await fetch( '/wp/v2/ctas' );
			const ctas = await response.json();

			// Dispatch multiple actions
			dispatch.setCTAs( ctas );
			dispatch.setLoading( false );
		} catch ( error ) {
			dispatch.setError( error );
			dispatch.setLoading( false );
		}
	};

// Resolvers can also use thunks
const getCTAs =
	() =>
	async ( { dispatch } ) => {
		try {
			const response = await fetch( '/wp/v2/ctas' );
			const ctas = await response.json();
			dispatch.receiveCTAs( ctas );
		} catch ( error ) {
			dispatch.setError( error );
		}
	};
```

### Store Definition with Thunks

```typescript
const store = createReduxStore( 'my-store', {
	reducer: ( state, action ) => {
		// ... reducer logic
	},
	actions: {
		// Thunks have access to { select, dispatch, resolveSelect }
		toggleFeature:
			( scope, featureName ) =>
			( { select, dispatch } ) => {
				const currentValue = select.isFeatureActive(
					scope,
					featureName
				);
				dispatch.setFeatureValue( scope, featureName, ! currentValue );
			},

		// Async thunks with error handling
		saveSettings:
			( settings ) =>
			async ( { dispatch } ) => {
				try {
					dispatch.setIsSaving( true );
					const response = await fetch( '/wp/v2/settings', {
						method: 'POST',
						body: JSON.stringify( settings ),
					} );
					const result = await response.json();
					dispatch.updateSettings( result );
				} catch ( error ) {
					dispatch.setError( error );
				} finally {
					dispatch.setIsSaving( false );
				}
			},
	},
	selectors: {
		getSettings: ( state ) => state.settings,
		// ... other selectors
	},
	resolvers: {
		// Resolvers can be thunks too
		getSettings:
			() =>
			async ( { dispatch } ) => {
				const response = await fetch( '/wp/v2/settings' );
				const settings = await response.json();
				dispatch.receiveSettings( settings );
			},
	},
} );
```

## Additional Thunk Features

### Registry Access

Thunks can access other stores through the registry:

```typescript
const myThunk =
	() =>
	( { registry } ) => {
		// Access other stores
		const coreSelect = registry.select( 'core' );
		const error = coreSelect.getLastEntitySaveError(
			'root',
			'menu',
			menuId
		);

		// Dispatch to other stores
		registry.dispatch( 'core' ).saveEntityRecord( /* ... */ );
	};
```

### Select vs ResolveSelect

-   `select`: Gets current state, doesn't wait for resolvers
-   `resolveSelect`: Returns a promise that resolves after resolvers complete

```typescript
const myThunk =
	() =>
	async ( { select, resolveSelect } ) => {
		// Immediate state access
		const currentCTAs = select.getCTAs();

		// Wait for resolver to complete
		const resolvedCTAs = await resolveSelect.getCTAs();
	};
```

## When to Use Each Pattern

### Raw Selectors

-   Direct state access
-   No computations needed
-   No dependencies on other state values

### createSelector

-   Expensive computations
-   Derived state based on multiple values
-   Need for memoization
-   Filtering or transforming data

### createRegistrySelector

-   Need data from multiple stores
-   Complex cross-store computations
-   Memoization needed for cross-store values

### Thunks/Resolvers

-   Async operations
-   API calls
-   Multiple sequential actions
-   Complex side effects

## Performance Considerations

### Memoization

`createSelector` and `createRegistrySelector` provide memoization similar to React's `useMemo`. They cache results based on dependency changes:

```typescript
// Only recomputes when ctas or status changes
const getFilteredCTAs = createSelector(
	( state ) => state.ctas.items,
	( state ) => state.filters.status,
	( ctas, status ) => ctas.filter( ( cta ) => cta.status === status )
);
```

### Dependencies

-   Keep dependency arrays minimal
-   Only include values that should trigger recomputation
-   Consider splitting complex selectors into smaller ones

## Best Practices

### 1. Post Type Data

For post type data, leverage the core store instead of maintaining custom state:

```typescript
// Instead of custom implementation with local state
const getEditorValues = createSelector(
	(state) => state.editor.values,
	(state) => [state.editor.values]
);

// Use core's entity editing system
const getEditorValues = createRegistrySelector((select) =>
	createSelector(
		(_state, id) => select(coreDataStore).getEditedEntityRecord(
			'postType',
			'pum_cta',
			id
		),
		(_state, id) => [id]
	)
);

// Check if entity has edits
const hasEdits = createRegistrySelector((select) =>
	createSelector(
		(_state, id) => select(coreDataStore).hasEditsForEntityRecord(
			'postType',
			'pum_cta',
			id
		),
		(_state, id) => [id]
	)
);
```

Core Entity Editing Benefits:
- Built-in undo/redo functionality
- Automatic dirty state tracking
- Proper handling of concurrent edits
- Integration with WordPress save system
- Optimistic updates
- Consistent editing experience

### 2. Custom State

For custom state (settings, analytics, etc.), keep it simple:

```typescript
interface SimpleState {
	items: Record< string, any >;
	isLoading: boolean;
	error?: Error;
}

// Simple selectors for simple state
const getItems = ( state: SimpleState ) => state.items;
const getIsLoading = ( state: SimpleState ) => state.isLoading;

// Add memoization only when needed
const getFilteredItems = createSelector(
	( state ) => state.items,
	( state ) => state.filters,
	( items, filters ) => applyFilters( items, filters )
);
```

### 3. Error Handling

Always handle errors in thunks/resolvers:

```typescript
const fetchData = () => async ( dispatch ) => {
	dispatch( setLoading( true ) );
	try {
		const data = await api.get( '/endpoint' );
		dispatch( setData( data ) );
	} catch ( error ) {
		dispatch( setError( error ) );
	} finally {
		dispatch( setLoading( false ) );
	}
};
```

### Entity Status Tracking

Instead of maintaining custom dispatch/loading states, use core's built-in status tracking:

```typescript
// Instead of custom status tracking
const getDispatchStatus = (state) => state.dispatchStatus;
const isDispatching = (state) => state.isDispatching;

// Use core's status selectors
const getEntityStatus = createRegistrySelector((select) =>
	createSelector(
		(_state, id) => ({
			isSaving: select(coreDataStore).isSavingEntityRecord('postType', 'pum_cta', id),
			isDeleting: select(coreDataStore).isDeletingEntityRecord('postType', 'pum_cta', id),
			saveError: select(coreDataStore).getLastEntitySaveError('postType', 'pum_cta', id),
			deleteError: select(coreDataStore).getLastEntityDeleteError('postType', 'pum_cta', id)
		}),
		(_state, id) => [id]
	)
);
```

Core Status Benefits:
- Automatic tracking of save/delete operations
- Built-in error handling
- Consistent status patterns across WordPress
- No need for custom reducers
- TypeScript support out of the box

Available Status Selectors:
- `isSavingEntityRecord`: Track save operations
- `isDeletingEntityRecord`: Track delete operations
- `getLastEntitySaveError`: Get save errors
- `getLastEntityDeleteError`: Get delete errors
- `hasEditsForEntityRecord`: Check for unsaved changes

### Reusable Entity Selectors

For consistent entity handling across different post types, create reusable selectors:

```typescript
// utils/entity-selectors.ts
export const createEntitySelectors = <T extends { id: number | string }>({ type, name }: EntityConfig) => ({
    getAll: createRegistrySelector((select) =>
        createSelector(
            (_state) => select(coreDataStore).getEntityRecords<T>(type, name),
            // Include core store deps to trigger updates
            (_state) => [
                select(coreDataStore).getEntityRecords(type, name),
                select(coreDataStore).hasFinishedResolution('getEntityRecords', [type, name])
            ]
        )
    ),
    // ... other entity selectors
});

// Usage in specific entity stores
const ctaSelectors = createEntitySelectors<CallToAction>({
    type: 'postType',
    name: 'pum_cta'
});

export const getCallToActions = ctaSelectors.getAll;
export const getCallToAction = ctaSelectors.getById;
```

Benefits:
- Consistent entity handling across post types
- Proper dependency tracking with core store
- Reusable TypeScript types
- Reduced code duplication
- Centralized entity selector logic

## Conclusion

Understanding these patterns helps build more efficient and maintainable WordPress applications:

1. Use raw selectors for simple state access
2. Use `createSelector` for memoized computations
3. Use `createRegistrySelector` for cross-store access
4. Use thunks/resolvers for async operations
5. Leverage core store for post type data
6. Keep custom state simple
7. Add complexity only when needed

Remember: The goal is to write maintainable, performant code. Start simple and add complexity only when necessary.
