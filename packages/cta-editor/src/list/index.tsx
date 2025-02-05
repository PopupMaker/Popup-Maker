import {
	Button,
	Icon,
	Spinner,
	TextControl,
	ToggleControl,
	Tooltip,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { info, search, trash } from '@wordpress/icons';

import { __ } from '@popup-maker/i18n';
import { noop } from '@popup-maker/utils';
import { FilterLines } from '@popup-maker/icons';
import { ConfirmDialogue, ListTable } from '@popup-maker/components';

import { ListConsumer, ListProvider } from '../context';
import { useEditor } from '../components';
import ListBulkActions from './bulk-actions';
import ListQuickActions from './quick-acions';
import ListFilters from './filters';
import ListOptions from './options';
import init from './init';

import type { CallToAction } from '@popup-maker/core-data';

const { cta_types: callToActions } = window.popupMakerCtaEditor;

// Initialize the registries.
init();

const List = () => {
	// Get the shared method for setting editor Id & query params.
	const { setEditorId } = useEditor();

	const [ showFilters, setShowFilters ] = useState< boolean >( false );

	const [ confirmDialogue, setConfirmDialogue ] = useState< {
		message: string;
		callback: () => void;
		isDestructive?: boolean;
	} >();

	const clearConfirm = () => setConfirmDialogue( undefined );

	const getCtaLabel = ( typeKey: string ) => {
		const ctaType = Object.values( callToActions ).find(
			( { key } ) => key === typeKey
		);

		return ctaType?.label ?? '';
	};

	return (
		<ListProvider>
			<ListConsumer>
				{ ( {
					isLoading,
					isDeleting,
					bulkSelection = [],
					setBulkSelection = noop,
					filteredCallToActions = [],
					updateCallToAction = noop,
					deleteCallToAction = noop,
					filters: { searchText = '' },
					setFilters,
					sortConfig,
					setSortConfig,
				} ) => (
					<>
						<ConfirmDialogue
							{ ...confirmDialogue }
							onClose={ clearConfirm }
						/>
						<div className="list-table-container">
							{ isLoading && (
								<div className="is-loading">
									<Spinner />
								</div>
							) }

							<div className="list-table-header">
								<div className="list-search">
									<Icon icon={ search } />
									<TextControl
										placeholder={ __(
											'Search Call to Actions…',
											'popup-maker'
										) }
										value={ searchText ?? '' }
										onChange={ ( value ) =>
											setFilters( {
												searchText:
													value !== ''
														? value
														: undefined,
											} )
										}
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
								</div>

								<ListBulkActions />

								{ bulkSelection.length === 0 && (
									<Button
										className="filters-toggle"
										variant="secondary"
										onClick={ () => {
											setShowFilters( ! showFilters );
										} }
										aria-expanded={ showFilters }
										icon={ FilterLines }
										iconSize={ 20 }
										text={
											! showFilters
												? __( 'Filters', 'popup-maker' )
												: __(
														'Hide Filters',
														'popup-maker'
												  )
										}
									/>
								) }

								<ListOptions />
							</div>

							{ showFilters && <ListFilters /> }

							<ListTable
								selectedItems={ bulkSelection }
								onSelectItems={ ( newSelection ) =>
									setBulkSelection( newSelection )
								}
								items={
									! isLoading ? filteredCallToActions : []
								}
								columns={ {
									enabled: () => (
										<Tooltip
											text={ __(
												'Enable or disable the call to action',
												'popup-maker'
											) }
											placement="top-end"
										>
											<span>
												<Icon icon={ info } />
											</span>
										</Tooltip>
									),
									title: __( 'Name', 'popup-maker' ),
									description: __(
										'Description',
										'popup-maker'
									),
									type: __( 'Type', 'popup-maker' ),
									status: __( 'Status', 'popup-maker' ),
								} }
								sortableColumns={ [ 'type', 'title' ] }
								onSort={ ( orderby, order ) => {
									setSortConfig( {
										orderby,
										order,
									} );
								} }
								initialSort={ sortConfig }
								rowClasses={ ( callToAction ) => {
									return [
										`call-to-action-${ callToAction.id }`,
										`status-${ callToAction.status }`,
									];
								} }
								renderCell={ (
									col:
										| string
										| keyof Pick<
												CallToAction< 'view' >,
												'id' | 'title' | 'excerpt'
										  >
										| keyof CallToAction[ 'settings' ],
									callToAction
								) => {
									const status = callToAction.status;
									const isTrash =
										status ===
										( 'trash' as typeof callToAction.status );
									const isPublish = status === 'publish';

									switch ( col ) {
										case 'enabled':
											return (
												<ToggleControl
													label={ '' }
													aria-label={ __(
														'Enable or disable the call to action',
														'popup-maker'
													) }
													checked={ isPublish }
													disabled={ isTrash }
													onChange={ ( checked ) => {
														updateCallToAction( {
															id: callToAction.id,
															status: checked
																? 'publish'
																: 'draft',
														} );
													} }
													__nextHasNoMarginBottom
												/>
											);

										case 'status':
											return isTrash ? (
												<Icon
													aria-label={ __(
														'In Trash',
														'popup-maker'
													) }
													icon={ trash }
													size={ 20 }
												/>
											) : (
												<span>
													{ isPublish
														? __(
																'Enabled',
																'popup-maker'
														  )
														: __(
																'Disabled',
																'popup-maker'
														  ) }
												</span>
											);

										case 'title': {
											return (
												<>
													<Button
														variant="link"
														onClick={ () =>
															setEditorId(
																callToAction.id
															)
														}
													>
														{
															callToAction.title
																.rendered
														}
													</Button>

													<ListQuickActions
														values={ callToAction }
													/>
												</>
											);
										}

										case 'type':
											return getCtaLabel(
												callToAction.settings.type
											);

										default:
											return (
												callToAction[ col ] ??
												callToAction.settings[ col ] ??
												''
											);
									}
								} }
							/>
						</div>
					</>
				) }
			</ListConsumer>
		</ListProvider>
	);
};

export default List;
