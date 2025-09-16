import clsx from 'clsx';
import { StringParam, useQueryParams } from '@popup-maker/use-query-params';

import { __ } from '@popup-maker/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useEffect, useMemo } from '@wordpress/element';
import { Popover } from '@wordpress/components';
import { AppLayout, AppHeader, AppContent } from '@popup-maker/layout';

import { CallToActionsView } from './components';
import { getGlobalVars } from './utils';

import type { TabComponent } from '@popup-maker/types';

const App = () => {
	const { permissions = { edit_ctas: false } } = getGlobalVars();
	const { edit_ctas: userCanEditCallToActions } = permissions;

	const [ { view = 'call-to-actions' }, setParams ] = useQueryParams( {
		tab: StringParam,
		view: StringParam,
	} );

	const views: TabComponent[] = useMemo( () => {
		let _views: TabComponent[] = [];

		if ( userCanEditCallToActions ) {
			_views.push( {
				name: 'call-to-actions',
				title: __( 'Call to Actions', 'popup-maker' ),
				className: 'call-to-actions',
				pageTitle: __( 'Popup Maker - Call to Actions', 'popup-maker' ),
				heading: __( 'Popup Maker - Call to Actions', 'popup-maker' ),
				comp: CallToActionsView,
			} );
		}

		/**
		 * Filter the list of views.
		 *
		 * @param {TabComponent[]} views List of views.
		 *
		 * @return {TabComponent[]} Filtered list of views.
		 */
		_views = applyFilters(
			'popupMaker.callToActionEditor.views',
			[ ..._views ],
			{
				view,
				setParams,
			}
		) as TabComponent[];

		return _views;
	}, [ view, setParams, userCanEditCallToActions ] );

	// Assign the current view from the list of views.
	const currentView = views.find( ( _view ) => _view.name === view );

	// Create a generic component from currentView.
	const ViewComponent = currentView?.comp ? currentView.comp : () => <></>;

	// Update page title with contextual info based on current view.
	useEffect( () => {
		document.title =
			views.find( ( obj ) => obj.name === view )?.pageTitle ??
			__( 'Popup Maker', 'popup-maker' );
	}, [ view, views ] );

	const { adminUrl } = getGlobalVars();

	return (
		<AppLayout
			className={ clsx( [
				'popup-maker-call-to-actions-page',
				`view-${ view }`,
			] ) }
		>
			<AppHeader
				tabs={ views }
				currentTab={ view ?? undefined }
				onTabChange={ ( tabName ) => setParams( { view: tabName } ) }
				adminUrl={ adminUrl }
			/>
			<AppContent>
				<ViewComponent />
			</AppContent>
			{ /*
			// @ts-ignore */ }
			<Popover.Slot />
		</AppLayout>
	);
};

export default App;
