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
 * NOTE: The editor now conditionally renders tabs based on the number of available tabs.
 * - If there's only one tab, it renders the content directly without tab navigation.
 * - If there are multiple tabs, it renders the full TabPanel interface.
 * This provides a cleaner UX and is forward-compatible.
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
	const [ activeTab, setActiveTab ] = useControlledState< string >(
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

	/**
	 * Determine if we should show tabs or render content directly.
	 */
	const shouldShowTabs = tabs.length > 1;

	/**
	 * Get the component to render when not showing tabs.
	 */
	const getSingleTabComponent = () => {
		if ( tabs.length === 0 ) {
			return null;
		}

		// Find the active tab or use the first available tab
		const targetTab =
			tabs.find( ( tab ) => tab.name === activeTab ) || tabs[ 0 ];
		return targetTab?.Component;
	};

	return (
		<div className={ clsx( 'call-to-action-editor', className ) }>
			{ beforeTabs && (
				<div className="editor-tabs-before">{ beforeTabs }</div>
			) }

			<div className="editor-tabs-container">
				{ shouldShowTabs ? (
					<TabPanel
						orientation="vertical"
						initialTabName={ activeTab ?? 'general' }
						onSelect={ setActiveTab }
						// @ts-ignore This is a bug in the @types/wordpress__components package.
						tabs={ tabs }
						className="editor-tabs"
					>
						{ ( { Component } ) => <Component { ...tabProps } /> }
					</TabPanel>
				) : (
					<div className="editor-tab-content">
						{ ( () => {
							const Component = getSingleTabComponent();
							return Component ? (
								<Component { ...tabProps } />
							) : (
								<div className="no-content-available">
									No content available
								</div>
							);
						} )() }
					</div>
				) }
			</div>

			{ afterTabs && (
				<div className="editor-tabs-after">{ afterTabs }</div>
			) }
		</div>
	);
};

export default BaseEditor;
