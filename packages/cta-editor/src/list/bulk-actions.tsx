import './bulk-actions.scss';

import { CheckAll } from '@popup-maker/icons';
import { __, _n, sprintf } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';

import {
	Button,
	Dropdown,
	Flex,
	Icon,
	NavigableMenu,
} from '@wordpress/components';
import { Fragment, useRef } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';

import { useList } from '../context';
import {
	isBulkActionComponent,
	useListBulkActions,
	type ListBulkActionContext,
} from '../registry';

const ListBulkActions = () => {
	const registry = useRegistry();

	const {
		bulkSelection = [],
		setBulkSelection,
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

	const bulkActionsBtnRef = useRef< HTMLButtonElement >();

	const listBulkActions = useListBulkActions();

	const bulkActionsContext: ListBulkActionContext = {
		bulkSelection,
		setBulkSelection,
		// REVIEW: These can be accessed directly from the hooks.
		createNotice,
		registry,
		getCallToAction,
		updateCallToAction,
		deleteCallToAction,
	};

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	return (
		<>
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
						{ listBulkActions.map( ( action ) => {
							const { id } = action;

							if ( isBulkActionComponent( action ) ) {
								if ( typeof action?.component !== 'function' ) {
									return null;
								}

								// Rename the component to Component to avoid the warning.
								const Component = action.component;

								return (
									<Component
										key={ id }
										{ ...bulkActionsContext }
									/>
								);
							}

							const {
								icon,
								label,
								onClick,
								isDestructive,
								separator = 'none',
							} = action;

							const shouldRender = action?.shouldRender?.( {
								...bulkActionsContext,
							} );

							const sepBefore = [ 'before', 'both' ].includes(
								separator
							);

							const sepAfter = [ 'after', 'both' ].includes(
								separator
							);

							return (
								shouldRender && (
									<Fragment key={ id }>
										{ sepBefore && <hr /> }
										<Button
											key={ id }
											text={ label }
											icon={ icon }
											onClick={ async (
												event?: React.MouseEvent<
													| HTMLAnchorElement
													| HTMLButtonElement
												>
											) => {
												event?.preventDefault();
												onClick?.( {
													...bulkActionsContext,
												} );
											} }
											isDestructive={ isDestructive }
										/>
										{ sepAfter && <hr /> }
									</Fragment>
								)
							);
						} ) }
					</NavigableMenu>
				) }
			/>
		</>
	);
};

export default ListBulkActions;
