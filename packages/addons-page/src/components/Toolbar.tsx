import React from 'react';
import { SearchControl } from '@wordpress/components';
import { ControlledTabPanel } from '@popup-maker/components';
import { __ } from '@popup-maker/i18n';

import type { FilterKey } from '../types';

interface Props {
	counts: Record< FilterKey, number >;
	filter: FilterKey;
	query: string;
	onFilterChange: ( filter: FilterKey ) => void;
	onQueryChange: ( query: string ) => void;
}

const Toolbar = ( {
	counts,
	filter,
	query,
	onFilterChange,
	onQueryChange,
}: Props ) => {
	const tabs = (
		[
			{ name: 'all', label: __( 'All', 'popup-maker' ) },
			{ name: 'active', label: __( 'Active', 'popup-maker' ) },
			{
				name: 'installed',
				label: __( 'Installed', 'popup-maker' ),
			},
			{ name: 'locked', label: __( 'Premium', 'popup-maker' ) },
		] as const
	 ).map( ( tab ) => ( {
		name: tab.name,
		className: `pum-addons__tab pum-addons__tab--${ tab.name }`,
		title: (
			<>
				<span>{ tab.label }</span>
				<span className="pum-addons__tab-count">
					{ counts[ tab.name ] }
				</span>
			</>
		),
	} ) );

	return (
		<div className="pum-addons__toolbar">
			<ControlledTabPanel
				className="pum-addons__tabs"
				tabs={ tabs }
				selected={ filter }
				onSelect={ ( tabKey: string ) =>
					onFilterChange( tabKey as FilterKey )
				}
			/>
			<SearchControl
				className="pum-addons__search"
				label={ __( 'Search add-ons', 'popup-maker' ) }
				placeholder={ __( 'Search add-ons', 'popup-maker' ) }
				value={ query }
				onChange={ onQueryChange }
				__nextHasNoMarginBottom
			/>
		</div>
	);
};

export default Toolbar;
