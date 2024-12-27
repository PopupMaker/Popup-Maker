import './filters.scss';

import clsx from 'clsx';

import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { useMemo, useRef, useState } from '@wordpress/element';
import { Button, Icon, Popover, RadioControl } from '@wordpress/components';

import { useList } from '../context';

import type { CallToActionStatuses } from '@popup-maker/core-data';

const statusOptionLabels: Record< CallToActionStatuses, string > = {
	all: __( 'All', 'popup-maker' ),
	publish: __( 'Enabled', 'popup-maker' ),
	draft: __( 'Disabled', 'popup-maker' ),
	pending: __( 'Pending', 'popup-maker' ),
	trash: __( 'Trash', 'popup-maker' ),
};

const ListFilters = () => {
	const {
		filters = {},
		setFilters,
		bulkSelection = [],
		callToActions = [],
		filteredCallToActions = [],
	} = useList();

	const [ visibleFilterControl, setVisibleFilterControl ] =
		useState< string >( '' );

	const filterButtonRefs = useRef< Record< string, HTMLButtonElement > >(
		{}
	);

	// List of unique statuses from all items.
	const totalStatusCounts = useMemo(
		() =>
			callToActions.reduce< Record< CallToActionStatuses, number > >(
				( s, r ) => {
					s[ r.status ] = ( s[ r.status ] ?? 0 ) + 1;
					s.all++;
					return s;
				},
				{ all: 0 }
			),
		[ callToActions ]
	);

	// List of unique statuses from all items.
	const activeStatusCounts = useMemo(
		() =>
			filteredCallToActions.reduce<
				Record< CallToActionStatuses, number >
			>(
				( s, r ) => {
					s[ r.status ] = ( s[ r.status ] ?? 0 ) + 1;
					s.all++;
					return s;
				},
				{ all: 0 }
			),
		[ filteredCallToActions ]
	);

	/**
	 * Checks if Status button should be visible.
	 *
	 * @param {CallToActionStatuses} s Status to check
	 * @return {boolean} True if button should be available.
	 */
	const isStatusActive = ( s: CallToActionStatuses ): boolean =>
		activeStatusCounts?.[ s ] > 0;

	if ( bulkSelection.length > 0 ) {
		return null;
	}

	const FilterControl = ( { name, label, currentSelection, children } ) => {
		const visible = visibleFilterControl === name;

		return (
			<div
				className={ clsx( [
					`list-table-filter list-table-filter--${ name }`,
					visible ? 'is-active' : '',
				] ) }
			>
				<Button
					className="filter-button"
					onClick={ () =>
						setVisibleFilterControl( visible ? '' : name )
					}
					ref={ ( el ) => {
						filterButtonRefs.current[ name ] = el;
					} }
				>
					<span className="filter-label">{ label }:</span>&nbsp;
					<span className="filter-selection">
						{ currentSelection }
					</span>
					<Icon
						className="filter-icon"
						icon={ visible ? chevronUp : chevronDown }
					/>
				</Button>
				{ visible && (
					<Popover
						className="list-table-filters__popover"
						anchor={
							{
								getBoundingClientRect: () =>
									filterButtonRefs.current[
										name
									].getBoundingClientRect(),
							} as Element
						}
						onClose={ () => setVisibleFilterControl( '' ) }
						position="bottom right"
						onFocusOutside={ () => setVisibleFilterControl( '' ) }
					>
						{ children }
					</Popover>
				) }
			</div>
		);
	};

	return (
		<div className="list-table-filters">
			<FilterControl
				name="status"
				label={ __( 'Status', 'popup-maker' ) }
				currentSelection={ statusOptionLabels[ filters?.status ?? '' ] }
			>
				<RadioControl
					label={ __( 'Status', 'popup-maker' ) }
					hideLabelFromVision={ true }
					selected={ filters?.status ?? '' }
					options={ Object.entries( statusOptionLabels )
						// Filter statuses with 0 items.
						.filter( ( [ value ] ) =>
							// If the current status has no items, show all statuses that have items.
							totalStatusCounts[ filters?.status ?? '' ] > 0
								? isStatusActive( value )
								: totalStatusCounts[ value ] > 0
						)
						// Map statuses to options.
						.map( ( [ value, label ] ) => {
							return {
								label: `${ label } (${
									activeStatusCounts[ value ] ?? 0
								})`,
								value,
							};
						} ) }
					onChange={ ( s ) => {
						setFilters( {
							status: s,
						} );

						setVisibleFilterControl( '' );
					} }
				/>
			</FilterControl>
		</div>
	);
};

export default ListFilters;
