import './editor.scss';

import clsx from 'clsx';

import { useMemo } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { TabPanel } from '@wordpress/components';

import { noop } from '@popup-maker/utils';
import { defaultCtaValues } from '@popup-maker/core-data';
import { useControlledState } from '@popup-maker/components';

import type { BaseEditorProps, BaseEditorTabProps, EditorTab } from '../types';

/**
 * The base editor component.
 *
 * @param {BaseEditorProps} props The props to pass to the editor.
 *
 * @return {JSX.Element} The editor component.
 */
const BaseEditor = ( {
	afterTabs,
	beforeTabs,
	className,
	...props
}: BaseEditorProps ): JSX.Element => {
	/**
	 * Use the useControlledState hook to manage the tab and setTab function.
	 *
	 * This allows for the use of dataStore or hooks in parent component.
	 */
	const [ tab, setTab ] = useControlledState< string >(
		props.tab,
		'general',
		props.setTab
	);

	/**
	 * Use the useControlledState hook to manage the values and updateValues function.
	 *
	 * This allows for the use of dataStore or hooks in parent component.
	 */
	const [ values, onChange ] = useControlledState(
		props.values,
		defaultCtaValues,
		props.onChange ?? noop
	);

	/**
	 * Memoize the tab props.
	 */
	const tabProps: BaseEditorTabProps = useMemo( () => {
		return {
			callToAction: values,
			onChange,
			updateFields: ( fields ) => {
				onChange( {
					...values,
					...fields,
				} );
			},
			updateSettings: ( settings ) => {
				onChange( {
					...values,
					settings: {
						...values.settings,
						...settings,
					},
				} );
			},
			updateSetting: ( setting, value ) => {
				onChange( {
					...values,
					settings: {
						...values.settings,
						[ setting ]: value,
					},
				} );
			},
		};
	}, [ values, onChange ] );

	const { tabsFilter } = props;

	/**
	 * Memoize the tabs.
	 */
	const tabs: EditorTab[] = useMemo( () => {
		/**
		 * Define the tabs to show in the editor.
		 *
		 * @param {EditorTab[]} tabs Array of tab components.
		 *
		 * @return {EditorTab[]} Array of tab components.
		 */
		const _tabs = applyFilters(
			'popupMaker.callToActionEditor.tabs',
			[]
		) as EditorTab[];

		/**
		 * If a tabsFilter is provided, use it to filter the tabs.
		 */
		return tabsFilter ? tabsFilter( _tabs ) : _tabs;
	}, [ tabsFilter ] );

	return (
		<div className={ clsx( 'call-to-action-editor', className ) }>
			{ beforeTabs && (
				<div className="editor-tabs-before">{ beforeTabs }</div>
			) }

			<div className="editor-tabs-container">
				<TabPanel
					orientation="vertical"
					initialTabName={ tab ?? 'general' }
					onSelect={ setTab }
					// @ts-ignore This is a bug in the @types/wordpress__components package.
					tabs={ tabs }
					className="editor-tabs"
				>
					{ ( { Component } ) => <Component { ...tabProps } /> }
				</TabPanel>
			</div>

			{ afterTabs && (
				<div className="editor-tabs-after">{ afterTabs }</div>
			) }
		</div>
	);
};

export default BaseEditor;
