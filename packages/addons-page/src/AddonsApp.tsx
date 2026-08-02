import React, { useMemo, useState } from 'react';
import { Notice, Popover, Spinner } from '@wordpress/components';
import { AppLayout, AppHeader, AppContent } from '@popup-maker/layout';
import { sprintf, _n, __ } from '@popup-maker/i18n';

import { getConfig } from './types';
import { getStatusKey, matchesFilter, matchesQuery } from './lib/status';
import { useAddons } from './hooks/useAddons';
import ActionNotice from './components/ActionNotice';
import AddonModal from './components/AddonModal';
import AddonSection from './components/AddonSection';
import PlanBanner from './components/PlanBanner';
import Toolbar from './components/Toolbar';

import type { Addon, FilterKey } from './types';

const AddonsApp = () => {
	const config = useMemo( getConfig, [] );
	const {
		addons,
		loading,
		loadError,
		busySlugs,
		notice,
		clearNotice,
		runAction,
	} = useAddons( config );

	const [ filter, setFilter ] = useState< FilterKey >( 'all' );
	const [ query, setQuery ] = useState( '' );
	const [ modalSlug, setModalSlug ] = useState< string | null >( null );

	const counts = useMemo( () => {
		const result: Record< FilterKey, number > = {
			all: addons.length,
			active: 0,
			installed: 0,
			locked: 0,
		};

		addons.forEach( ( addon ) => {
			const status = getStatusKey(
				addon,
				Boolean( busySlugs[ addon.slug ] )
			);

			if ( 'active' === status || 'network' === status ) {
				result.active++;
				result.installed++;
			} else if ( 'installed' === status ) {
				result.installed++;
			} else if ( 'locked' === status ) {
				result.locked++;
			}
		} );

		return result;
	}, [ addons, busySlugs ] );

	const visible = useMemo(
		() =>
			addons.filter(
				( addon ) =>
					matchesQuery( addon, query, config.categories ) &&
					matchesFilter(
						getStatusKey(
							addon,
							Boolean( busySlugs[ addon.slug ] )
						),
						filter
					)
			),
		[ addons, busySlugs, config.categories, filter, query ]
	);

	const proPlus = visible.filter( ( addon ) => addon.isProPlus );
	const extensions = visible.filter( ( addon ) => ! addon.isProPlus );

	const modalAddon =
		null !== modalSlug
			? addons.find( ( addon ) => addon.slug === modalSlug )
			: undefined;

	const renderSection = (
		items: Addon[],
		title: string,
		featured = false
	) => (
		<AddonSection
			key={ title }
			addons={ items }
			title={ title }
			featured={ featured }
			busySlugs={ busySlugs }
			config={ config }
			onAction={ runAction }
			onOpen={ ( addon ) => setModalSlug( addon.slug ) }
		/>
	);

	let catalogContent;

	if ( loading ) {
		catalogContent = (
			<p className="pum-addons__loading">
				<Spinner />
				{ __( 'Loading add-ons…', 'popup-maker' ) }
			</p>
		);
	} else if ( loadError ) {
		catalogContent = (
			<Notice status="error" isDismissible={ false }>
				{ loadError }
			</Notice>
		);
	} else {
		catalogContent = (
			<>
				{ 0 === addons.length ? (
					<p className="pum-addons__empty">
						{ __(
							'No add-ons are currently available.',
							'popup-maker'
						) }
					</p>
				) : (
					<>
						<Toolbar
							counts={ counts }
							filter={ filter }
							query={ query }
							onFilterChange={ setFilter }
							onQueryChange={ setQuery }
						/>
						{ 0 === visible.length ? (
							<p className="pum-addons__empty">
								{ query
									? sprintf(
											/* translators: %s: search query. */
											__(
												'No add-ons match “%s”.',
												'popup-maker'
											),
											query
									  )
									: __(
											'No add-ons match the selected filter.',
											'popup-maker'
									  ) }
							</p>
						) : (
							<>
								{ proPlus.length > 0 &&
									renderSection(
										proPlus,
										__( 'Pro+ Add-ons', 'popup-maker' ),
										true
									) }
								{ extensions.length > 0 &&
									renderSection(
										extensions,
										__( 'Add-ons', 'popup-maker' )
									) }
							</>
						) }
					</>
				) }
			</>
		);
	}

	const content = (
		<>
			<PlanBanner config={ config } />
			{ notice && (
				<ActionNotice
					notice={ notice }
					config={ config }
					onDismiss={ clearNotice }
				/>
			) }
			{ catalogContent }
		</>
	);

	return (
		<AppLayout className="pum-addons-app">
			<AppHeader navTabId="extend" />
			<AppContent>
				<div className="pum-addons">
					<header className="pum-addons__page-heading">
						<h1>
							{ __( 'Extend', 'popup-maker' ) }
							{ addons.length > 0 && (
								<span className="pum-addons__count">
									{ sprintf(
										/* translators: %d: number of add-ons. */
										_n(
											'%d add-on',
											'%d add-ons',
											addons.length,
											'popup-maker'
										),
										addons.length
									) }
								</span>
							) }
						</h1>
						<p>
							{ __(
								'Explore Popup Maker add-ons and manage extensions installed on this site.',
								'popup-maker'
							) }
						</p>
					</header>
					{ content }
				</div>
			</AppContent>
			{ modalAddon && (
				<AddonModal
					addon={ modalAddon }
					busy={ Boolean( busySlugs[ modalAddon.slug ] ) }
					config={ config }
					onAction={ runAction }
					onClose={ () => setModalSlug( null ) }
				/>
			) }
			{ /*
			// @ts-ignore */ }
			<Popover.Slot />
		</AppLayout>
	);
};

export default AddonsApp;
