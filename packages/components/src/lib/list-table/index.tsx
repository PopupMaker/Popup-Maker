import './editor.scss';

import clsx from 'clsx';

import { __ } from '@popup-maker/i18n';
import { arrowDown, arrowUp } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';
import { Button, CheckboxControl, Icon } from '@wordpress/components';

import { useControlledState } from '../utils';
import type { TableItemBase, SortConfig } from './types';
import { SortDirection } from './types';

type Props< T extends TableItemBase > = {
	items: T[];
	columns: {
		[ key: string ]: React.ReactNode | ( () => React.ReactNode );
	};
	rowClasses?: ( item: T ) => clsx.ClassValue;
	renderCell: (
		col: string,
		item: T,
		rowIndex: number
	) => React.ReactNode | string | number;
	sortableColumns: string[];
	idCol?: string;
	noItemsText?: string;
	showBulkSelect?: boolean;
	className?: clsx.ClassValue;
	selectedItems?: number[];
	onSelectItems?: ( selectedItems: number[] ) => void;
	onSort?: ( orderby: string, order: SortDirection ) => void;
	initialSort?: SortConfig | null;
};

type CellProps = {
	heading?: boolean;
	children: React.ReactNode;
	[ key: string ]: any;
};

const TableCell = ( { heading = false, children, ...props }: CellProps ) => {
	return heading ? (
		<th { ...props }>{ children }</th>
	) : (
		<td { ...props }>{ children }</td>
	);
};

const ListTable = < T extends TableItemBase >( {
	items,
	columns,
	sortableColumns = [],
	idCol = 'id',
	rowClasses = ( item ) => [ `item-${ item.id }` ],
	renderCell = ( col, item ) => item[ col ],
	noItemsText = __( 'No items found.', 'popup-maker' ),
	showBulkSelect = true,
	className,
	selectedItems: incomingSelectedItems = [],
	onSelectItems = () => {},
	onSort,
	initialSort,
}: Props< T > ) => {
	const cols = { [ idCol ]: columns[ idCol ] ?? '', ...columns };
	const colCount = Object.keys( cols ).length;

	const [ selectedItems, setSelectedItems ] = useControlledState< number[] >(
		incomingSelectedItems,
		[],
		onSelectItems
	);

	useEffect( () => {
		onSelectItems( selectedItems );
	}, [ selectedItems, onSelectItems ] );

	const ColumnHeaders = ( { header = false } ) => (
		<>
			{ Object.entries( cols ).map( ( [ col, colLabel ] ) => {
				const isIdCol = col === idCol;
				const isBulkSelect = showBulkSelect && isIdCol;
				const isSortedBy = initialSort?.orderby === col;
				const isSortable = ! isBulkSelect
					? sortableColumns.indexOf( col ) >= 0
					: false;

				const cellProps = {
					key: col,
					heading: ! isBulkSelect,
					id: header && ! isBulkSelect ? col : undefined,
					scope: ! isBulkSelect ? 'col' : undefined,
					className: clsx( [
						`column-${ col }`,
						...( ! isBulkSelect && isSortable
							? [
									'sortable',
									initialSort?.order ?? SortDirection.ASC,
							  ]
							: [] ),
						isBulkSelect && 'check-column',
					] ),
				};

				const Label = () => (
					<>
						{ typeof colLabel === 'function' ? (
							colLabel()
						) : (
							<>
								<span>{ colLabel }</span>
								{ isSortedBy && (
									<Icon
										icon={
											initialSort?.order ===
											SortDirection.ASC
												? arrowUp
												: arrowDown
										}
										size={ 16 }
									/>
								) }
							</>
						) }
					</>
				);

				return (
					<TableCell { ...cellProps } key={ col }>
						{ isBulkSelect && (
							<CheckboxControl
								onChange={ ( checked ) =>
									setSelectedItems(
										! checked
											? []
											: items.map( ( item ) => item.id )
									)
								}
								checked={
									selectedItems.length > 0 &&
									selectedItems.length === items.length
								}
								// @ts-ignore
								indeterminate={
									selectedItems.length > 0 &&
									selectedItems.length < items.length
								}
								__nextHasNoMarginBottom
							/>
						) }

						{ ! isBulkSelect && isSortable && (
							<Button
								variant="link"
								onClick={ () => {
									if ( onSort ) {
										let newDirection: SortDirection;

										if ( initialSort?.orderby === col ) {
											newDirection =
												initialSort.order ===
												SortDirection.ASC
													? SortDirection.DESC
													: SortDirection.ASC;
										} else {
											newDirection = SortDirection.ASC;
										}

										onSort( col, newDirection );
									}
								} }
							>
								<Label />
							</Button>
						) }

						{ ! isBulkSelect && ! isSortable && <Label /> }
					</TableCell>
				);
			} ) }
		</>
	);

	return (
		<table
			className={ clsx( [
				className,
				'component-list-table',
				'list-table',
				items.length === 0 && 'no-items',
			] ) }
		>
			<thead>
				<tr>
					<ColumnHeaders header={ true } />
				</tr>
			</thead>
			<tbody>
				{ items.length ? (
					items.map( ( item, rowIndex ) => (
						<tr
							key={ item.id }
							className={ clsx( rowClasses( item ) ) }
						>
							{ Object.entries( cols ).map( ( [ col ] ) => {
								const isIdCol = col === idCol;

								return (
									<TableCell
										key={ col }
										heading={ isIdCol }
										className={ clsx( [
											`column-${ col }`,
											showBulkSelect &&
												isIdCol &&
												'check-column',
										] ) }
										scope={ isIdCol ? 'row' : undefined }
									>
										{ isIdCol ? (
											<CheckboxControl
												onChange={ ( checked ) => {
													const newSelectedItems =
														! checked
															? selectedItems.filter(
																	( id ) =>
																		id !==
																		item.id
															  )
															: [
																	...selectedItems,
																	item.id,
															  ];

													setSelectedItems(
														newSelectedItems
													);
												} }
												checked={
													selectedItems.indexOf(
														item.id
													) >= 0
												}
												__nextHasNoMarginBottom
											/>
										) : (
											renderCell( col, item, rowIndex )
										) }
									</TableCell>
								);
							} ) }
						</tr>
					) )
				) : (
					<tr>
						<td colSpan={ colCount }>{ noItemsText }</td>
					</tr>
				) }
			</tbody>
			<tfoot>
				<tr>
					<ColumnHeaders />
				</tr>
			</tfoot>
		</table>
	);
};

export default ListTable;

export * from './types';
