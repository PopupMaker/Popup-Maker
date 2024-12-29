import {
	Button,
	Icon,
	Spinner,
	TextControl,
	ToggleControl,
	Tooltip,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { info, search, trash } from '@wordpress/icons';

import { FilterLines } from '@popup-maker/icons';
import { noop } from '@popup-maker/utils';
import { ConfirmDialogue, ListTable } from '@popup-maker/components';

import { ListConsumer, ListProvider } from '../context';
import useEditor from '../hooks/use-editor';
import ListBulkActions from './bulk-actions';
import ListFilters from './filters';
import ListOptions from './options';

import type { CallToAction } from '@popup-maker/core-data';

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
											position="top right"
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
												CallToAction,
												'id' | 'title' | 'description'
										  >
										| keyof CallToAction[ 'settings' ],
									callToAction
								) => {
									const status = callToAction.status;
									const isTrash = status === 'trash';
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
															...callToAction,
															status: checked
																? 'publish'
																: 'draft',
														} );
													} }
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
														{ callToAction.title }
													</Button>

													<div className="item-actions">
														{ `${ __(
															'ID',
															'popup-maker'
														) }: ${
															callToAction.id
														}` }
														<Button
															text={ __(
																'Edit',
																'popup-maker'
															) }
															variant="link"
															onClick={ () =>
																setEditorId(
																	callToAction.id
																)
															}
														/>

														<Button
															text={
																isTrash
																	? __(
																			'Untrash',
																			'popup-maker'
																	  )
																	: __(
																			'Trash',
																			'popup-maker'
																	  )
															}
															variant="link"
															isDestructive={
																true
															}
															isBusy={
																!! isDeleting
															}
															onClick={ () =>
																isTrash
																	? updateCallToAction(
																			{
																				...callToAction,
																				status: 'draft',
																			}
																	  )
																	: deleteCallToAction(
																			callToAction.id
																	  )
															}
														/>

														{ isTrash && (
															<Button
																text={ __(
																	'Delete Permanently',
																	'popup-maker'
																) }
																variant="link"
																isDestructive={
																	true
																}
																isBusy={
																	!! isDeleting
																}
																onClick={ () =>
																	setConfirmDialogue(
																		{
																			message:
																				__(
																					'Are you sure you want to premanently delete this call to action?'
																				),
																			callback:
																				() => {
																					// This will only rerender the components once.
																					deleteCallToAction(
																						callToAction.id,
																						true
																					);
																				},
																			isDestructive:
																				true,
																		}
																	)
																}
															/>
														) }
													</div>
												</>
											);
										}

										case 'type':
											return callToAction.settings?.type;

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
