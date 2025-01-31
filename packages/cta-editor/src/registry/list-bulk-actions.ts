import type {
	CallToActionStoreActions,
	CallToActionStoreSelectors,
	Notice,
} from '@popup-maker/core-data';
import { createRegistry, type PopupMaker as BasePopupMaker } from './base';
import type { IconType } from '@wordpress/components';
import type { useRegistry } from '@wordpress/data';

export type ListBulkActionContext = {
	/**
	 * Bulk selection.
	 */
	bulkSelection: number[];

	/**
	 * Set bulk selection.
	 */
	setBulkSelection: ( selection: number[] ) => void;

	/**
	 * Create notice.
	 */
	createNotice: (
		status?: Notice[ 'status' ],
		content?: Notice[ 'content' ],
		options?: Notice
	) => void;

	/**
	 * Get call to action.
	 */
	getCallToAction: CallToActionStoreSelectors[ 'getCallToAction' ];

	/**
	 * Update call to action.
	 */
	updateCallToAction: CallToActionStoreActions[ 'updateCallToAction' ];

	/**
	 * Delete call to action.
	 */
	deleteCallToAction: CallToActionStoreActions[ 'deleteCallToAction' ];

	/**
	 * Access to the data registry.
	 */
	registry: ReturnType< typeof useRegistry >;

	/**
	 * User can.
	 */
	// userCan: ( cap: string ) => boolean;
};

declare namespace PopupMaker {
	interface BaseListBulkAction extends BasePopupMaker.RegistryItem {
		id: string;
		priority?: number;
		separator?: 'before' | 'after' | 'both';
	}

	export interface SimpleListBulkAction extends BaseListBulkAction {
		label: string;
		icon: IconType | null | undefined;
		onClick: ( context: ListBulkActionContext ) => void;
		isDestructive?: boolean;
		shouldRender?: ( context: ListBulkActionContext ) => boolean;
	}

	export interface ComponentListBulkAction extends BaseListBulkAction {
		component?: React.FC< ListBulkActionContext >;
	}

	export type ListBulkAction = SimpleListBulkAction | ComponentListBulkAction;
}

export const isBulkActionComponent = (
	action: PopupMaker.ListBulkAction
): action is PopupMaker.ComponentListBulkAction => 'component' in action;

export const ListBulkActionsRegistry =
	createRegistry< PopupMaker.ListBulkAction >( {
		name: 'cta-editor/list-bulk-actions',
	} );

// Helper hook for components
export const useListBulkActions = () => ListBulkActionsRegistry.useItems();

export const registerListBulkAction = ListBulkActionsRegistry.register;

export const getListBulkActions = () => ListBulkActionsRegistry.getItems();
