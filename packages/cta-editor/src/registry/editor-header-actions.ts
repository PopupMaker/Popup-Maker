import type { EditableCta } from '@popup-maker/core-data';
import { createRegistry, type PopupMaker as BasePopupMaker } from './base';

export type EditorHeaderActionContext< T extends EditableCta > = {
	values: T;
	closeModal: () => void;
};

declare namespace PopupMaker {
	interface BaseEditorHeaderAction< T extends EditableCta = EditableCta >
		extends BasePopupMaker.RegistryItem {
		render: React.FC< EditorHeaderActionContext< T > >;
	}

	export type EditorHeaderAction< T extends EditableCta = EditableCta > =
		BaseEditorHeaderAction< T >;
}

export const EditorHeaderActionsRegistry =
	createRegistry< PopupMaker.EditorHeaderAction >( {
		name: 'cta-editor/editor-header-actions',
		groups: {
			general: { priority: 10 },
		},
		defaultGroup: 'general',
	} );

// Helper hook for components
export const useEditorHeaderActions = () =>
	EditorHeaderActionsRegistry.useItems();

export const registerEditorHeaderAction = EditorHeaderActionsRegistry.register;

export const registerEditorHeaderActionGroup =
	EditorHeaderActionsRegistry.registerGroup;

export const getEditorHeaderActions = () =>
	EditorHeaderActionsRegistry.getItems();
