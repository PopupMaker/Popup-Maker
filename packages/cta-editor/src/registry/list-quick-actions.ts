import type { BaseEntity } from '@popup-maker/core-data';
import { createRegistry, type PopupMaker as BasePopupMaker } from './base';

export type ListQuickActionContext<
	T extends BaseEntity< 'edit' > = BaseEntity< 'edit' >,
> = {
	values: T;
};

declare namespace PopupMaker {
	interface BaseListQuickAction<
		T extends BaseEntity< 'edit' > = BaseEntity< 'edit' >,
	> extends BasePopupMaker.RegistryItem {
		render: React.FC< ListQuickActionContext< T > >;
	}

	export type ListQuickAction<
		T extends BaseEntity< 'edit' > = BaseEntity< 'edit' >,
	> = BaseListQuickAction< T >;
}

export const ListQuickActionsRegistry =
	createRegistry< PopupMaker.ListQuickAction >( {
		name: 'cta-editor/list-quick-actions',
		groups: {
			general: { priority: 10 },
			trash: { priority: 20 },
		},
	} );

// Helper hook for components
export const useListQuickActions = () => ListQuickActionsRegistry.useItems();

export const registerListQuickAction = ListQuickActionsRegistry.register;

export const registerListQuickActionGroup =
	ListQuickActionsRegistry.registerGroup;

export const getListQuickActions = () => ListQuickActionsRegistry.getItems();
