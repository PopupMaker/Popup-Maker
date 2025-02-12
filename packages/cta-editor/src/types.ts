import type { ClassValue } from 'clsx';
import type { EditableCta } from '@popup-maker/core-data';
import type { ComponentTab } from '@popup-maker/types';

export interface BaseEditorProps {
	/**
	 * The values to pass to the editor.
	 */
	values?: EditableCta | undefined;

	/**
	 * The function to update the values.
	 */
	onChange?: ( values: EditableCta ) => void;

	/**
	 * The tab to show in the editor.
	 */
	tab?: string;

	/**
	 * The function to set the tab.
	 */
	setTab?: ( tab: string ) => void;

	/**
	 * Tab filter.
	 */
	tabsFilter?: ( tabs: EditorTab[] ) => EditorTab[];

	/**
	 * The class name to apply to the editor.
	 */
	className?: ClassValue;

	/**
	 * The content to show before the tabs.
	 */
	beforeTabs?: JSX.Element;

	/**
	 * The content to show after the tabs.
	 */
	afterTabs?: JSX.Element;
}

type SettingField< T extends EditableCta > =
	T[ 'settings' ][ keyof T[ 'settings' ] ];

export interface BaseEditorTabProps< T extends EditableCta = EditableCta > {
	/**
	 * The CallToAction to edit.
	 */
	callToAction: T;

	/**
	 * Method to update the CallToAction values.
	 *
	 * @param values The values to update the CallToAction with.
	 */
	onChange: ( values: T ) => void;

	/**
	 * Method to update a specific CallToAction field.
	 *
	 * @param {Partial< T >} fields The fields to update.
	 */
	updateFields: ( fields: Partial< T > ) => void;

	/**
	 * Method to update the CallToAction settings.
	 *
	 * @param settings The settings to update the CallToAction with.
	 */
	updateSettings: ( settings: Partial< T[ 'settings' ] > ) => void;

	/**
	 * Method to update a specific CallToAction setting.
	 *
	 * @param {keyof T[ 'settings' ]} setting The setting to update.
	 * @param {SettingField<T>}       value   The value to update the setting with.
	 */
	updateSetting: (
		setting: keyof T[ 'settings' ],
		value: SettingField< T >
	) => void;
}

export type EditorTab = ComponentTab< BaseEditorTabProps >;
