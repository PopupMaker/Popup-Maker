import { useEffect, useMemo, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

import { STORE_NAME } from '../store';
import type { NotificationsSelectors } from '../store';
import type { Notification, NotificationCategory } from '../types';

type FilterKey = 'all' | NotificationCategory;

interface State {
	items: Notification[];
	isLoading: boolean;
	hasLoaded: boolean;
	counts: Record< FilterKey, number >;
	activeFilter: FilterKey;
	setActiveFilter: ( filter: FilterKey ) => void;
	visibleItems: Notification[];
}

/**
 * Orchestrates the notification list — fetches from the store, computes
 * per-category counts, and exposes the active-filter state.
 */
export const useNotifications = (): State => {
	const [ activeFilter, setActiveFilter ] = useState< FilterKey >( 'all' );

	const { fetchNotifications } = useDispatch( STORE_NAME );

	const { items, isLoading, hasLoaded } = useSelect( ( select ) => {
		const store = select( STORE_NAME ) as NotificationsSelectors;
		return {
			items: store.getNotifications(),
			isLoading: store.isLoading(),
			hasLoaded: store.hasLoaded(),
		};
	}, [] );

	// Fetch on mount so the count reflects server state even before open.
	useEffect( () => {
		fetchNotifications();
	}, [ fetchNotifications ] );

	const counts = useMemo< Record< FilterKey, number > >( () => {
		const base: Record< FilterKey, number > = {
			all: items.length,
			feature: 0,
			recommendation: 0,
			announcement: 0,
			offer: 0,
			warning: 0,
		};
		items.forEach( ( item ) => {
			base[ item.category ] = ( base[ item.category ] || 0 ) + 1;
		} );
		return base;
	}, [ items ] );

	const visibleItems = useMemo(
		() =>
			activeFilter === 'all'
				? items
				: items.filter( ( item ) => item.category === activeFilter ),
		[ items, activeFilter ]
	);

	return {
		items,
		isLoading,
		hasLoaded,
		counts,
		activeFilter,
		setActiveFilter,
		visibleItems,
	};
};
