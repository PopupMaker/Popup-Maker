import {
	createRegistry,
	type PopupMaker as BasePopupMaker,
} from '@popup-maker/registry';

export type ListOptionContext = {
	onClose: () => void;
};

declare namespace PopupMaker {
	interface BaseListOption extends BasePopupMaker.RegistryItem {
		id: string;
		priority?: number;
		group?: string;
		render: React.FC< ListOptionContext >;
	}

	export type ListOption = BaseListOption;
}

export const ListOptionsRegistry = createRegistry< PopupMaker.ListOption >( {
	name: 'cta-editor/list-options',
	groups: {
		import: { priority: 10, label: 'Import' },
	},
} );

// Helper hook for components
export const useListOptions = () => ListOptionsRegistry.useItems();

export const registerListOption = ListOptionsRegistry.register;

export const registerListOptionGroup = ListOptionsRegistry.registerGroup;

export const getListOptions = () => ListOptionsRegistry.getItems();
