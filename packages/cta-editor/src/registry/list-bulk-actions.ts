import { createRegistry, type PopupMaker as BasePopupMaker } from './base';

export type ListBulkActionContext = {
	/**
	 * Close dropdown.
	 */
	onClose: () => void;
};

declare namespace PopupMaker {
	interface BaseListBulkAction extends BasePopupMaker.RegistryItem {
		render: React.FC< ListBulkActionContext >;
	}

	export type ListBulkAction = BaseListBulkAction;
}

export const ListBulkActionsRegistry =
	createRegistry< PopupMaker.ListBulkAction >( {
		name: 'cta-editor/list-bulk-actions',
	} );

// Helper hook for components
export const useListBulkActions = () => ListBulkActionsRegistry.useItems();

export const registerListBulkAction = ListBulkActionsRegistry.register;

export const getListBulkActions = () => ListBulkActionsRegistry.getItems();
