import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { describe, beforeEach, it, expect } from '@jest/globals';

import {
	ListFiltersRegistry,
	registerListFilter,
	useListFilters,
} from '../list-filters';

describe( 'ListFiltersRegistry', () => {
	beforeEach( () => {
		// Clear all registered filters before each test
		ListFiltersRegistry.clear();
	} );

	it( 'registers a filter correctly', () => {
		const mockFilter = {
			id: 'test-filter',
			group: 'core',
			priority: 10,
			render: () => null,
		};

		registerListFilter( mockFilter );
		const filters = ListFiltersRegistry.getItems();

		expect( filters ).toHaveLength( 1 );
		expect( filters[ 0 ] ).toEqual( mockFilter );
	} );

	it( 'maintains filter order based on group priority', () => {
		const mockFilter1 = {
			id: 'test-filter-1',
			group: 'core',
			priority: 10,
			render: () => null,
		};

		const mockFilter2 = {
			id: 'test-filter-2',
			group: 'advanced',
			priority: 20,
			render: () => null,
		};

		registerListFilter( mockFilter2 );
		registerListFilter( mockFilter1 );

		const filters = ListFiltersRegistry.getItems();

		expect( filters ).toHaveLength( 2 );
		expect( filters[ 0 ].id ).toBe( 'test-filter-1' ); // core group comes first
		expect( filters[ 1 ].id ).toBe( 'test-filter-2' ); // advanced group comes second
	} );

	it( 'useListFilters hook returns registered filters', () => {
		const mockFilter = {
			id: 'test-filter',
			group: 'core',
			priority: 10,
			render: () => null,
		};

		registerListFilter( mockFilter );

		const { result } = renderHook( () => useListFilters() );

		expect( result.current ).toHaveLength( 1 );

		expect( result.current[ 0 ] ).toEqual( mockFilter );
	} );

	it( 'allows registering multiple filters', () => {
		const mockFilters = [
			{
				id: 'test-filter-1',
				group: 'core',
				priority: 10,
				render: () => null,
			},
			{
				id: 'test-filter-2',
				group: 'core',
				priority: 10,
				render: () => null,
			},
		];

		mockFilters.forEach( ( filter ) => registerListFilter( filter ) );
		const filters = ListFiltersRegistry.getItems();

		expect( filters ).toHaveLength( 2 );
		expect( filters ).toEqual( mockFilters );
	} );

	it( 'ensures duplicate filter IDs are overwritten', () => {
		const mockFilter = {
			id: 'test-filter',
			group: 'core',
			priority: 10,
			render: () => null,
		};

		registerListFilter( mockFilter );
		expect( () => registerListFilter( mockFilter ) ).not.toThrow();
		const filters = ListFiltersRegistry.getItems();
		expect( filters ).toHaveLength( 1 );
		expect( filters[ 0 ] ).toEqual( mockFilter );
	} );
} );
