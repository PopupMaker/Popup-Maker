import type { EditableCta } from '@popup-maker/core-data';
import {
	createRegistry,
	type PopupMaker as BasePopupMaker,
} from '@popup-maker/registry';

export type EditorHeaderOptionsContext< T extends EditableCta = EditableCta > =
	{
		values: T;
		closeModal: () => void;
	};

declare namespace PopupMaker {
	interface BaseEditorHeaderOption< T extends EditableCta = EditableCta >
		extends BasePopupMaker.RegistryItem {
		render: React.FC< EditorHeaderOptionsContext< T > >;
	}

	export type EditorHeaderOption< T extends EditableCta = EditableCta > =
		BaseEditorHeaderOption< T >;
}

export const EditorHeaderOptionsRegistry =
	createRegistry< PopupMaker.EditorHeaderOption >( {
		name: 'cta-editor/editor-header-options',
		groups: {
			general: { priority: 10 },
		},
	} );

// Helper hook for components
export const useEditorHeaderOptions = () =>
	EditorHeaderOptionsRegistry.useItems();

export const registerEditorHeaderOption = EditorHeaderOptionsRegistry.register;

export const registerEditorHeaderOptionGroup =
	EditorHeaderOptionsRegistry.registerGroup;

export const getEditorHeaderOptions = () =>
	EditorHeaderOptionsRegistry.getItems();
