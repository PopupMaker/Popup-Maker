import clsx from 'clsx';
import { __ } from '@popup-maker/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { useMemo, useRef, useState } from '@wordpress/element';
import { Button, Icon, Popover, RadioControl } from '@wordpress/components';

import type { ListFilterContext } from '../../registry/list-filters';

export type TypeFilterKey = 'type';
export type TypeFilterValue = string;
export type TypeCounts = { all: number } & Record< string, number >;

export const TypeFilter = ( {
	filters,
	setFilters,
	onClose,
	items,
	filteredItems,
}: ListFilterContext< TypeFilterKey, TypeFilterValue > ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const buttonRef = useRef< HTMLButtonElement >( null );

	// Get all types from the call to actions
	const typeOptionLabels = useMemo( () => {
		const { cta_types: callToActions } = window.popupMakerCtaEditor;

		return Object.values( callToActions ).reduce(
			( acc, { key, label } ) => ( {
				...acc,
				[ key ]: label,
			} ),
			{ all: __( 'All', 'popup-maker' ) }
		);
	}, [] );

	// List of unique types from all items
	const totalTypeCounts = useMemo(
		() =>
			items.reduce< TypeCounts >(
				( counts, item ) => {
					const type = item.settings.type;
					counts[ type ] = ( counts[ type ] ?? 0 ) + 1;
					counts.all++;
					return counts;
				},
				{ all: 0 }
			),
		[ items ]
	);

	// List of unique types from filtered items
	const activeTypeCounts = useMemo(
		() =>
			filteredItems.reduce< TypeCounts >(
				( counts, item ) => {
					const type = item.settings.type;
					counts[ type ] = ( counts[ type ] ?? 0 ) + 1;
					counts.all++;
					return counts;
				},
				{ all: 0 }
			),
		[ filteredItems ]
	);

	const isTypeActive = ( type: string ) =>
		Boolean( activeTypeCounts?.[ type ] );

	return (
		<div
			className={ clsx( [
				'list-table-filter list-table-filter--type',
				isOpen ? 'is-active' : '',
			] ) }
		>
			<Button
				className="filter-button"
				onClick={ () => setIsOpen( ! isOpen ) }
				ref={ buttonRef }
			>
				<span className="filter-label">
					{ __( 'Type', 'popup-maker' ) }:
				</span>
				&nbsp;
				<span className="filter-selection">
					{ typeOptionLabels[ filters?.type ?? 'all' ] }
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
						label={ __( 'Type', 'popup-maker' ) }
						hideLabelFromVision={ true }
						selected={ filters?.type ?? 'all' }
						options={ Object.entries( typeOptionLabels )
							// Filter types with 0 items
							.filter( ( [ value ] ) =>
								( totalTypeCounts[ filters?.type ?? 'all' ] ??
									0 ) > 0
									? isTypeActive( value )
									: ( totalTypeCounts[ value ] ?? 0 ) > 0
							)
							// Map types to options
							.map( ( [ value, label ] ) => ( {
								label: `${ label } (${
									activeTypeCounts[ value ] ?? 0
								})`,
								value,
							} ) ) }
						onChange={ ( type: string ) => {
							setFilters( {
								type: type === 'all' ? undefined : type,
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
	id: 'type',
	group: 'core',
	render: TypeFilter,
};
