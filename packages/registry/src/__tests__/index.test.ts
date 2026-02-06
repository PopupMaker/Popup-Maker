jest.mock( '@wordpress/element', () => ( {
	useSyncExternalStore: jest.fn(
		( subscribe: any, getSnapshot: () => any ) => getSnapshot()
	),
} ) );

import { createRegistry } from '../index';
import type { PopupMaker } from '../index';

type TestItem = PopupMaker.RegistryItem & { label?: string };

describe( 'createRegistry', () => {
	let registry: ReturnType< typeof createRegistry< TestItem > >;

	beforeEach( () => {
		registry = createRegistry< TestItem >( { name: 'test-registry' } );
	} );

	describe( 'basic registration', () => {
		it( 'starts with an empty items list', () => {
			expect( registry.getItems() ).toEqual( [] );
		} );

		it( 'registers an item', () => {
			registry.register( { id: 'item-1', label: 'First' } );
			expect( registry.getItems() ).toHaveLength( 1 );
			expect( registry.getItems()[ 0 ].id ).toBe( 'item-1' );
		} );

		it( 'assigns default priority of 10', () => {
			registry.register( { id: 'item-1' } );
			expect( registry.getItems()[ 0 ].priority ).toBe( 10 );
		} );

		it( 'assigns default group of empty string', () => {
			registry.register( { id: 'item-1' } );
			expect( registry.getItems()[ 0 ].group ).toBe( '' );
		} );

		it( 'preserves custom priority', () => {
			registry.register( { id: 'item-1', priority: 5 } );
			expect( registry.getItems()[ 0 ].priority ).toBe( 5 );
		} );

		it( 'preserves custom group', () => {
			registry.register( { id: 'item-1', group: 'custom' } );
			expect( registry.getItems()[ 0 ].group ).toBe( 'custom' );
		} );
	} );

	describe( 'deduplication', () => {
		it( 'replaces existing item with same id and group', () => {
			registry.register( { id: 'item-1', label: 'Original' } );
			registry.register( { id: 'item-1', label: 'Updated' } );
			expect( registry.getItems() ).toHaveLength( 1 );
			expect( registry.getItems()[ 0 ].label ).toBe( 'Updated' );
		} );

		it( 'allows same id in different groups', () => {
			registry.register( { id: 'item-1', group: 'a' } );
			registry.register( { id: 'item-1', group: 'b' } );
			expect( registry.getItems() ).toHaveLength( 2 );
		} );
	} );

	describe( 'sorting by priority within same group', () => {
		it( 'sorts items by priority ascending', () => {
			registry.register( { id: 'low', priority: 20 } );
			registry.register( { id: 'high', priority: 1 } );
			registry.register( { id: 'mid', priority: 10 } );

			const items = registry.getItems();
			expect( items[ 0 ].id ).toBe( 'high' );
			expect( items[ 1 ].id ).toBe( 'mid' );
			expect( items[ 2 ].id ).toBe( 'low' );
		} );
	} );

	describe( 'sorting by group priority', () => {
		it( 'sorts groups by configured priority', () => {
			const grouped = createRegistry< TestItem >( {
				name: 'grouped',
				groups: {
					first: { priority: 1 },
					second: { priority: 2 },
				},
			} );

			grouped.register( { id: 'b', group: 'second' } );
			grouped.register( { id: 'a', group: 'first' } );

			const items = grouped.getItems();
			expect( items[ 0 ].id ).toBe( 'a' );
			expect( items[ 1 ].id ).toBe( 'b' );
		} );

		it( 'gives unconfigured groups default priority of 50', () => {
			const grouped = createRegistry< TestItem >( {
				name: 'grouped',
				groups: {
					high: { priority: 1 },
				},
			} );

			grouped.register( { id: 'unknown-group', group: 'unknown' } );
			grouped.register( { id: 'high-group', group: 'high' } );

			const items = grouped.getItems();
			expect( items[ 0 ].id ).toBe( 'high-group' );
			expect( items[ 1 ].id ).toBe( 'unknown-group' );
		} );

		it( 'uses localeCompare for groups with equal priority', () => {
			const grouped = createRegistry< TestItem >( {
				name: 'grouped',
				groups: {
					alpha: { priority: 10 },
					beta: { priority: 10 },
				},
			} );

			grouped.register( { id: 'b', group: 'beta' } );
			grouped.register( { id: 'a', group: 'alpha' } );

			const items = grouped.getItems();
			expect( items[ 0 ].id ).toBe( 'a' );
			expect( items[ 1 ].id ).toBe( 'b' );
		} );

		it( 'items in default empty-string group get Infinity priority', () => {
			const grouped = createRegistry< TestItem >( {
				name: 'grouped',
				groups: {
					named: { priority: 99 },
				},
			} );

			// Default group is '' (empty string) which is falsy,
			// so getPriority returns Infinity.
			grouped.register( { id: 'default-item' } );
			grouped.register( { id: 'named-item', group: 'named' } );

			const items = grouped.getItems();
			expect( items[ 0 ].id ).toBe( 'named-item' );
			expect( items[ 1 ].id ).toBe( 'default-item' );
		} );
	} );

	describe( 'filter', () => {
		it( 'filters items by predicate', () => {
			registry.register( { id: 'a', priority: 5 } );
			registry.register( { id: 'b', priority: 15 } );
			registry.register( { id: 'c', priority: 25 } );

			const filtered = registry.filter(
				( item ) => ( item.priority ?? 10 ) > 10
			);
			expect( filtered ).toHaveLength( 2 );
			expect( filtered[ 0 ].id ).toBe( 'b' );
			expect( filtered[ 1 ].id ).toBe( 'c' );
		} );

		it( 'returns empty array when no items match', () => {
			registry.register( { id: 'a' } );
			const filtered = registry.filter( () => false );
			expect( filtered ).toEqual( [] );
		} );
	} );

	describe( 'clear', () => {
		it( 'removes all items', () => {
			registry.register( { id: 'a' } );
			registry.register( { id: 'b' } );
			registry.clear();
			expect( registry.getItems() ).toEqual( [] );
		} );
	} );

	describe( 'registerGroup', () => {
		it( 'adds a new group config and re-sorts', () => {
			const grouped = createRegistry< TestItem >( {
				name: 'dynamic-groups',
			} );

			grouped.register( { id: 'late', group: 'late-group' } );
			grouped.register( { id: 'early', group: 'early-group' } );

			// Before registering group config, both have default priority 50.
			// After registering, early-group should come first.
			grouped.registerGroup( 'early-group', { priority: 1 } );
			grouped.registerGroup( 'late-group', { priority: 99 } );

			const items = grouped.getItems();
			expect( items[ 0 ].id ).toBe( 'early' );
			expect( items[ 1 ].id ).toBe( 'late' );
		} );
	} );

	describe( 'emitChange and subscribers', () => {
		it( 'notifies subscribers when items are registered', () => {
			const listener = jest.fn();

			// Access the subscribe mechanism via useItems mock.
			const { useSyncExternalStore } = require( '@wordpress/element' );
			useSyncExternalStore.mockImplementation(
				( subscribe: ( cb: () => void ) => () => void ) => {
					subscribe( listener );
					return [];
				}
			);

			registry.useItems();
			registry.register( { id: 'trigger' } );
			expect( listener ).toHaveBeenCalled();
		} );

		it( 'notifies subscribers on clear', () => {
			const listener = jest.fn();

			const { useSyncExternalStore } = require( '@wordpress/element' );
			useSyncExternalStore.mockImplementation(
				( subscribe: ( cb: () => void ) => () => void ) => {
					subscribe( listener );
					return [];
				}
			);

			registry.useItems();
			registry.clear();
			expect( listener ).toHaveBeenCalled();
		} );

		it( 'unsubscribes correctly', () => {
			const listener = jest.fn();

			const { useSyncExternalStore } = require( '@wordpress/element' );
			let unsubscribe: () => void;
			useSyncExternalStore.mockImplementation(
				( subscribe: ( cb: () => void ) => () => void ) => {
					unsubscribe = subscribe( listener );
					return [];
				}
			);

			registry.useItems();
			unsubscribe!();
			listener.mockClear();

			registry.register( { id: 'after-unsub' } );
			expect( listener ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'useItems', () => {
		it( 'returns current items via useSyncExternalStore', () => {
			const { useSyncExternalStore } = require( '@wordpress/element' );
			useSyncExternalStore.mockImplementation(
				( _sub: any, getSnapshot: () => any ) => getSnapshot()
			);

			registry.register( { id: 'hook-item', label: 'Hook' } );
			const items = registry.useItems();
			expect( items ).toHaveLength( 1 );
			expect( items[ 0 ].id ).toBe( 'hook-item' );
		} );
	} );

	describe( 'getItems returns a copy', () => {
		it( 'does not allow external mutation of internal items', () => {
			registry.register( { id: 'immutable' } );
			const items = registry.getItems();
			items.push( { id: 'hacked' } as TestItem );
			expect( registry.getItems() ).toHaveLength( 1 );
		} );
	} );

	describe( 'defaultGroup config', () => {
		it( 'uses configured defaultGroup for items without group', () => {
			const reg = createRegistry< TestItem >( {
				name: 'default-group-test',
				defaultGroup: 'general',
			} );

			reg.register( { id: 'no-group' } );
			expect( reg.getItems()[ 0 ].group ).toBe( 'general' );
		} );
	} );

	describe( 'registry name', () => {
		it( 'exposes the registry name', () => {
			expect( registry.name ).toBe( 'test-registry' );
		} );
	} );
} );
