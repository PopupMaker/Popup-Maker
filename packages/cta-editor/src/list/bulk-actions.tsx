import './bulk-actions.scss';

import { ConfirmDialogue } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';
import { CheckAll } from '@popup-maker/icons';
import { saveFile } from '@popup-maker/utils';

import {
	Button,
	Dropdown,
	Flex,
	Icon,
	NavigableMenu,
} from '@wordpress/components';
import { __, _n, sprintf } from '@popup-maker/i18n';
import { useRef, useState } from '@wordpress/element';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import {
	cancelCircleFilled,
	chevronDown,
	chevronUp,
	download,
	link,
	linkOff,
	trash,
} from '@wordpress/icons';

import { useList } from '../context';
import { cleanCallToActionData } from './utils';
import { getGlobalVars } from '../utils';

const ListBulkActions = () => {
	const registry = useRegistry();
	const { version } = getGlobalVars();

	const {
		bulkSelection = [],
		setBulkSelection,
		callToActions = [],
		deleteCallToAction,
		updateCallToAction,
	} = useList();

	const { getCallToAction } = useSelect(
		( select ) => ( {
			getCallToAction: select( callToActionStore ).getCallToAction,
		} ),
		[]
	);

	const { createNotice } = useDispatch( callToActionStore );

	const [ confirmDialogue, setConfirmDialogue ] = useState< {
		message: string;
		callback: () => void;
		isDestructive?: boolean;
	} >();

	const clearConfirm = () => setConfirmDialogue( undefined );

	const bulkActionsBtnRef = useRef< HTMLButtonElement >();

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	return (
		<>
			<ConfirmDialogue { ...confirmDialogue } onClose={ clearConfirm } />
			<Dropdown
				className="list-table-bulk-actions"
				contentClassName="list-table-bulk-actions__popover"
				// @ts-ignore this is not typed in WP yet.
				placement="bottom left"
				focusOnMount="firstElement"
				popoverProps={ {
					noArrow: false,
					anchor: {
						getBoundingClientRect: () =>
							bulkActionsBtnRef?.current?.getBoundingClientRect(),
					} as Element,
				} }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Flex>
						<span className="selected-items">
							{ sprintf(
								// translators: 1. number of items.
								_n(
									'%d item selected',
									'%d items selected',
									bulkSelection.length,
									'popup-maker'
								),
								bulkSelection.length
							) }
						</span>
						<Button
							className="popover-toggle"
							ref={ ( ref: HTMLButtonElement ) => {
								bulkActionsBtnRef.current = ref;
							} }
							aria-label={ __( 'Bulk Actions', 'popup-maker' ) }
							variant="secondary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
							icon={ CheckAll }
							iconSize={ 20 }
						>
							{ __( 'Bulk Actions', 'popup-maker' ) }
							<Icon
								className="toggle-icon"
								icon={ isOpen ? chevronUp : chevronDown }
							/>
						</Button>
					</Flex>
				) }
				renderContent={ () => (
					<NavigableMenu orientation="vertical">
						<Button
							text={ __( 'Export Selected', 'popup-maker' ) }
							icon={ download }
							onClick={ () => {
								const exportData = {
									version,
									callToActions: callToActions
										.filter(
											( { id } ) =>
												bulkSelection.indexOf( id ) >= 0
										)
										.map( cleanCallToActionData ),
								};

								saveFile(
									JSON.stringify( exportData ),
									'popup-maker-call-to-actions.json',
									'text/json'
								);
							} }
						/>
						<hr />
						<Button
							text={ __( 'Enable', 'popup-maker' ) }
							icon={ link }
							onClick={ () => {
								// This will only rerender the components once.
								// @ts-ignore not yet typed in WP.
								registry.batch( () => {
									const count = bulkSelection.length;

									bulkSelection.forEach( ( id ) => {
										const callToAction =
											getCallToAction( id );

										if ( callToAction?.id === id ) {
											updateCallToAction( {
												id,
												status: 'publish',
											} );
										}
									} );
									setBulkSelection( [] );

									createNotice(
										'sucess',
										sprintf(
											// translators: 1. number of items
											_n(
												'%d call to action enabled.',
												'%d call to actions enabled.',
												count,
												'popup-maker'
											),
											count
										),
										{
											id: 'bulk-enable',
											type: 'success',
											closeDelay: 3000,
										}
									);
								} );
							} }
						/>
						<Button
							text={ __( 'Disable', 'popup-maker' ) }
							icon={ linkOff }
							onClick={ () => {
								// This will only rerender the components once.
								// @ts-ignore not yet typed in WP.
								registry.batch( () => {
									const count = bulkSelection.length;

									bulkSelection.forEach( ( id ) => {
										const callToAction =
											getCallToAction( id );

										if ( callToAction?.id === id ) {
											updateCallToAction( {
												id,
												status: 'draft',
											} );
										}
									} );
									setBulkSelection( [] );

									createNotice(
										'success',
										sprintf(
											// translators: 1. number of items
											_n(
												'%d call to action disabled.',
												'%d call to actions disabled.',
												count,
												'popup-maker'
											),
											count
										),
										{
											id: 'bulk-disable',
											closeDelay: 3000,
										}
									);
								} );
							} }
						/>

						<hr />
						<Button
							text={ __( 'Trash', 'popup-maker' ) }
							icon={ trash }
							onClick={ () => {
								setConfirmDialogue( {
									isDestructive: true,
									message: sprintf(
										// translators: 1. number of items
										__(
											'Are you sure you want to trash %d items?',
											'popup-maker'
										),
										bulkSelection.length
									),
									callback: () => {
										// This will only rerender the components once.
										// @ts-ignore not yet typed in WP.
										registry.batch( () => {
											const count = bulkSelection.length;

											bulkSelection.forEach( ( id ) =>
												deleteCallToAction( id )
											);
											setBulkSelection( [] );

											createNotice(
												'success',
												sprintf(
													// translators: 1. number of items
													_n(
														'%d call to action moved to trash.',
														'%d call to actions moved to trash.',
														count,
														'popup-maker'
													),
													count
												),
												{
													id: 'bulk-trash',
													closeDelay: 3000,
												}
											);
										} );
									},
								} );
							} }
						/>
						<Button
							text={ __( 'Delete Permanently', 'popup-maker' ) }
							icon={ cancelCircleFilled }
							isDestructive={ true }
							onClick={ () => {
								setConfirmDialogue( {
									isDestructive: true,
									message: sprintf(
										// translators: 1. call to action label.
										__(
											'Are you sure you want to premanently delete %d items?',
											'popup-maker'
										),
										bulkSelection.length
									),
									callback: () => {
										// This will only rerender the components once.
										// @ts-ignore not yet typed in WP.
										registry.batch( () => {
											const count = bulkSelection.length;

											bulkSelection.forEach( ( id ) =>
												deleteCallToAction( id, true )
											);
											setBulkSelection( [] );

											createNotice(
												'success',
												sprintf(
													// translators: 1. number of items
													_n(
														'%d call to action deleted.',
														'%d call to actions deleted.',
														count,
														'popup-maker'
													),
													count
												),
												{
													id: 'bulk-delete',
													closeDelay: 3000,
												}
											);
										} );
									},
								} );
							} }
						/>
					</NavigableMenu>
				) }
			/>
		</>
	);
};

export default ListBulkActions;
