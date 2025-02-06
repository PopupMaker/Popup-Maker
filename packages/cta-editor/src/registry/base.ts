import { useSyncExternalStore } from '@wordpress/element';

export declare namespace PopupMaker {
	export interface RegistryGroup {
		priority: number;
		label?: string;
	}

	export interface RegistryItem {
		/** Unique identifier for the item */
		id: string;
		/** Numeric priority for sorting (lower numbers come first) */
		priority?: number;
		/** Group for sorting */
		group?: string;
	}

	export type RegistryConfig = {
		/** Unique name for the registry */
		name: string;
		/**
		 * Group configuration with priorities (merged with core and global groups)
		 * Use numbers between 0-99 to insert around core groups (10-30)
		 */
		groups?: Record< string, RegistryGroup >;

		/**
		 * Default group for items that don't specify a group
		 */
		defaultGroup?: string;
	};
}

/**
 * Creates a type-safe registry with priority-based sorting
 * @param {PopupMaker.RegistryConfig<T>} config
 */
export function createRegistry< T extends PopupMaker.RegistryItem >(
	config: PopupMaker.RegistryConfig
) {
	const { name, groups = {}, defaultGroup = '' } = config;
	let groupConfig = { ...groups };
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
			group: item.group ?? defaultGroup,
			priority: item.priority ?? defaultPriority,
		} as T;

		// Using the unique id, group and priority, check if the item already exists.
		const existingItem = items.find(
			( { id, group } ) => id === newItem.id && group === newItem.group
		);

		// If the item already exists, replace it.
		if ( existingItem ) {
			items = items.map( ( _item ) =>
				_item.id === newItem.id ? newItem : _item
			);
		} else {
			items.push( newItem );
		}

		// New sorting logic using configured group priorities
		items.sort( sortComparator );

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

	const registerGroup = (
		groupName: string,
		groupOptions: PopupMaker.RegistryGroup
	) => {
		groupConfig = { ...groupConfig, [ groupName ]: groupOptions };
		// Re-sort existing items with new group configuration
		items.sort( sortComparator );
		emitChange();
	};

	const sortComparator = ( a: T, b: T ) => {
		if ( a.group === b.group ) {
			return a.priority! - b.priority!;
		}

		const getPriority = ( group?: string ) =>
			group ? groupConfig[ group ]?.priority ?? 50 : Infinity;

		const aPriority = getPriority( a.group );
		const bPriority = getPriority( b.group );

		return (
			aPriority - bPriority ||
			( a.group ?? 'zzzz' ).localeCompare( b.group ?? 'zzzz' )
		);
	};

	const context = {
		name,
		register,
		registerGroup,
		getItems,
		useItems,
		filter,
		emitChange,
	};

	return context;
}
