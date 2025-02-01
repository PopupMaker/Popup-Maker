import { useSyncExternalStore } from '@wordpress/element';

export declare namespace PopupMaker {
	export interface RegistryItem {
		/** Unique identifier for the item */
		id: string;
		/** Numeric priority for sorting (lower numbers come first) */
		priority?: number;
		/** Group for sorting */
		group?: string;

		/**
		 * Conditional render check (can use hooks)
		 * @default () => true
		 */
		// shouldRender?: ( context?: any ) => boolean;
	}

	export type RegistryConfig = {
		/** Unique name for the registry */
		name: string;
	};
}

/**
 * Creates a type-safe registry with priority-based sorting
 * @param {PopupMaker.RegistryConfig<T>} config
 */
export function createRegistry< T extends PopupMaker.RegistryItem >(
	config: PopupMaker.RegistryConfig
) {
	const { name } = config;
	let items: T[] = [];
	const defaultPriority = 10;

	// Create a Set to track subscribers
	const subscribers = new Set< () => void >();

	/**
	 * Registers a new item with the registry
	 * @param {T} item
	 */
	const register = ( item: T ) => {
		const newItem = {
			...item,
			priority: item.priority ?? defaultPriority,
		} as T;

		// Using the unique id, group and priority, check if the item already exists.
		const existingItem = items.find(
			( { id, group, priority } ) =>
				id === newItem.id &&
				group === newItem.group &&
				priority === newItem.priority
		);

		console.log( existingItem );

		// If the item already exists, replace it.
		if ( existingItem ) {
			items = items.map( ( _item ) =>
				_item.id === newItem.id ? newItem : _item
			);
		} else {
			items.push( newItem );
		}

		// Sort items by group -> priority
		items.sort( ( a, b ) => {
			// Same group - sort by priority
			if ( a.group === b.group ) {
				return a.priority! - b.priority!;
			}

			// Handle null/undefined groups as "ungrouped" that should come last
			const aGroup = a.group ?? 'zzzz'; // 'zzzz' ensures null groups sort last
			const bGroup = b.group ?? 'zzzz';

			// First sort groups alphabetically
			const groupCompare = aGroup.localeCompare( bGroup );

			// If groups are different but have same sort order, then sort by priority
			return groupCompare !== 0
				? groupCompare
				: a.priority! - b.priority!;
		} );

		// Notify subscribers when items change
		emitChange();
	};

	/**
	 * Retrieves all items sorted by priority
	 */
	const getItems = () => [ ...items ];

	/**
	 * React hook version of getItems
	 */
	/**
	 * React hook to access registry items with automatic re-renders
	 * @return {T[]} Sorted registry items
	 */
	const useItems = (): T[] => {
		const subscribe = ( listener: () => void ) => {
			subscribers.add( listener );
			return () => subscribers.delete( listener );
		};

		const getSnapshot = () => items;

		return useSyncExternalStore(
			subscribe,
			getSnapshot,
			getSnapshot // Fallback for SSR
		);
	};

	/**
	 * Filter items with a custom predicate
	 * @param {Function} predicate
	 */
	const filter = ( predicate: ( item: T ) => boolean ) =>
		items.filter( predicate );

	/**
	 * Emits a change event to all subscribers
	 */
	const emitChange = () => {
		subscribers.forEach( ( listener ) => listener() );
	};

	const context = {
		name,
		register,
		getItems,
		useItems,
		filter,
		emitChange,
	};

	return context;
}
