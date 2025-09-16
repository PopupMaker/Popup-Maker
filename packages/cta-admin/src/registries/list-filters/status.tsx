import clsx from 'clsx';
import { __ } from '@popup-maker/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { useMemo, useRef, useState } from '@wordpress/element';
import { Button, Icon, Popover, RadioControl } from '@wordpress/components';

import type { CallToActionStatuses } from '@popup-maker/core-data';
import type { ListFilterContext } from '../../registry';

export type ValidStatuses = CallToActionStatuses | 'trash' | 'all';
export type StatusFilterKey = 'status';
export type StatusFilterValue = ValidStatuses;

export const statusOptionLabels: Record< ValidStatuses, string > = {
	all: __( 'All', 'popup-maker' ),
	publish: __( 'Enabled', 'popup-maker' ),
	draft: __( 'Disabled', 'popup-maker' ),
	pending: __( 'Pending', 'popup-maker' ),
	trash: __( 'Trash', 'popup-maker' ),
	future: __( 'Future', 'popup-maker' ),
	private: __( 'Private', 'popup-maker' ),
};

export type StatusCounts = { all: number } & Partial<
	Record< Exclude< ValidStatuses, 'all' >, number >
>;

export const StatusFilter = ( {
	filters,
	setFilters,
	onClose,
	items,
	filteredItems,
}: ListFilterContext< StatusFilterKey, StatusFilterValue > ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const buttonRef = useRef< HTMLButtonElement >( null );

	// List of unique statuses from all items.
	const totalStatusCounts = useMemo(
		() =>
			items.reduce< StatusCounts >(
				( s, r ) => {
					s[ r.status ] = ( s[ r.status ] ?? 0 ) + 1;
					s.all++;
					return s;
				},
				{ all: 0 }
			),
		[ items ]
	);

	// List of unique statuses from filtered items.
	const activeStatusCounts = useMemo(
		() =>
			filteredItems.reduce< StatusCounts >(
				( s, r ) => {
					s[ r.status ] = ( s[ r.status ] ?? 0 ) + 1;
					s.all++;
					return s;
				},
				{ all: 0 }
			),
		[ filteredItems ]
	);

	const isStatusActive = ( status: ValidStatuses ): boolean =>
		Boolean( activeStatusCounts?.[ status ] );

	const currentStatus = filters?.status ?? 'all';

	return (
		<div
			className={ clsx( [
				'list-table-filter list-table-filter--status',
				isOpen ? 'is-active' : '',
			] ) }
		>
			<Button
				className="filter-button"
				onClick={ () => setIsOpen( ! isOpen ) }
				ref={ buttonRef }
			>
				<span className="filter-label">
					{ __( 'Status', 'popup-maker' ) }:
				</span>
				&nbsp;
				<span className="filter-selection">
					{ statusOptionLabels[ currentStatus ] }
				</span>
				<Icon
					className="filter-icon"
					icon={ isOpen ? chevronUp : chevronDown }
				/>
			</Button>
			{ isOpen && buttonRef.current && (
				<Popover
					className="list-table-filters__popover"
					anchor={ buttonRef.current }
					onClose={ () => {
						setIsOpen( false );
						onClose();
					} }
					position="bottom right"
					onFocusOutside={ () => {
						setIsOpen( false );
						onClose();
					} }
				>
					<RadioControl
						label={ __( 'Status', 'popup-maker' ) }
						hideLabelFromVision={ true }
						selected={ currentStatus }
						options={ Object.entries( statusOptionLabels )
							// Filter statuses with 0 items.
							.filter( ( [ value ] ) =>
								// If the current status has no items, show all statuses that have items.
								( totalStatusCounts[ currentStatus ] ?? 0 ) > 0
									? isStatusActive( value as ValidStatuses )
									: ( totalStatusCounts[
											value as ValidStatuses
									  ] ?? 0 ) > 0
							)
							// Map statuses to options.
							.map( ( [ value, label ] ) => ( {
								label: `${ label } (${
									activeStatusCounts[
										value as ValidStatuses
									] ?? 0
								})`,
								value,
							} ) ) }
						onChange={ ( status: string ) => {
							setFilters( {
								status: status as StatusFilterValue,
							} );
							setIsOpen( false );
							onClose();
						} }
					/>
				</Popover>
			) }
		</div>
	);
};

export default {
	id: 'status',
	priority: 10,
	group: 'core',
	render: StatusFilter,
};
