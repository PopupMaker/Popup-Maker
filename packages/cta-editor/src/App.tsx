import clsx from 'clsx';
import { StringParam, useQueryParams } from 'use-query-params';

import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useEffect, useMemo } from '@wordpress/element';
import { Popover, SlotFillProvider } from '@wordpress/components';

import Header from './header';
import CallToActionsView from './call-to-actions-view';

import type { TabComponent } from './types';

const {
	permissions: { edit_ctas: userCanEditCallToActions },
} = window.popupMaker.globalVars;

const App = () => {
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
	}, [ view, setParams ] );

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

	return (
		<SlotFillProvider>
			<div
				className={ clsx( [
					'popup-maker-call-to-actions-page',
					`view-${ view }`,
				] ) }
			>
				<Header tabs={ views } />
				<div className="popup-maker-call-to-actions-page__content">
					<ViewComponent />
				</div>
				{ /*
			// @ts-ignore */ }
				<Popover.Slot />
			</div>
		</SlotFillProvider>
	);
};

export default App;
