import { createRegistry, type PopupMaker as BasePopupMaker } from './base';

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
} );

// Helper hook for components
export const useListOptions = () => ListOptionsRegistry.useItems();

export const registerListOption = ListOptionsRegistry.register;

export const getListOptions = () => ListOptionsRegistry.getItems();
