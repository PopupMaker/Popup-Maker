import { useSyncExternalStore } from '@wordpress/element';

export declare namespace PopupMaker {
	export interface RegistryItem {
		/** Unique identifier for the item */
		id: string;
		/** Numeric priority for sorting (lower numbers come first) */
		priority?: number;
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
	const items: T[] = [];
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

		items.push( newItem );
		items.sort( ( a, b ) => a.priority! - b.priority! );

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
