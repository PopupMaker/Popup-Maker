import type { CallToAction } from '@popup-maker/core-data';
import { createRegistry, type PopupMaker as BasePopupMaker } from './base';

export type ListFilterContext< K extends string = string, V = unknown > = {
	/** The current filters */
	filters: Partial< Record< K, V > >;
	/** Set filters callback */
	setFilters: ( filters: Partial< Record< K, V > > ) => void;
	/** Close filter dropdown */
	onClose: () => void;
	/** All items */
	items: CallToAction< 'edit' >[];
	/** Filtered items */
	filteredItems: CallToAction< 'edit' >[];
};

declare namespace PopupMaker {
	interface BaseListFilter< K extends string = string, V = unknown >
		extends BasePopupMaker.RegistryItem {
		render: React.FC< ListFilterContext< K, V > >;
	}

	export type ListFilter<
		K extends string = string,
		V = unknown,
	> = BaseListFilter< K, V >;
}

export const ListFiltersRegistry = createRegistry< PopupMaker.ListFilter >( {
	name: 'cta-editor/list-filters',
	groups: {
		core: { priority: 10, label: 'Core' },
		advanced: { priority: 20, label: 'Advanced' },
	},
	defaultGroup: 'core',
} );

// Helper hook for components
export const useListFilters = () => ListFiltersRegistry.useItems();

export const registerListFilter = ListFiltersRegistry.register;

export const registerListFilterGroup = ListFiltersRegistry.registerGroup;

export const getListFilters = () => ListFiltersRegistry.getItems();
