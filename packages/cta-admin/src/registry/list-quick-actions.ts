import type { CallToAction } from '@popup-maker/core-data';
import {
	createRegistry,
	type PopupMaker as BasePopupMaker,
} from '@popup-maker/registry';

export type ListQuickActionContext<
	T extends CallToAction< 'edit' > = CallToAction< 'edit' >,
> = {
	values: T;
};

declare namespace PopupMaker {
	interface BaseListQuickAction<
		T extends CallToAction< 'edit' > = CallToAction< 'edit' >,
	> extends BasePopupMaker.RegistryItem {
		render: React.FC< ListQuickActionContext< T > >;
	}

	export type ListQuickAction<
		T extends CallToAction< 'edit' > = CallToAction< 'edit' >,
	> = BaseListQuickAction< T >;
}

export const ListQuickActionsRegistry = createRegistry<
	PopupMaker.ListQuickAction< CallToAction< 'edit' > >
>( {
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
