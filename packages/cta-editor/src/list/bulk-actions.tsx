import './bulk-actions.scss';

import { CheckAll } from '@popup-maker/icons';
import { __, _n, sprintf } from '@popup-maker/i18n';

import {
	Button,
	Dropdown,
	Flex,
	Icon,
	NavigableMenu,
} from '@wordpress/components';
import { Fragment, useRef } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';

import { useList } from '../context';
import { useListBulkActions } from '../registry';

const ListBulkActions = () => {
	const lastGroup = useRef< string | undefined >( undefined );
	const bulkActionsBtnRef = useRef< HTMLButtonElement >();
	const listBulkActions = useListBulkActions();

	const { bulkSelection = [] } = useList();

	const bulkActionsContext = {};

	/**
	 * Separates new groups of options with a horizontal line.
	 * @param {Object} props
	 * @param {string} props.group
	 */
	const GroupSeparator = ( { group }: { group?: string } ) => {
		// Store for comparison.
		const currentGroup = lastGroup.current;

		// Update ref before returning.
		lastGroup.current = group;

		if ( ! currentGroup || group !== currentGroup ) {
			return <hr />;
		}

		return null;
	};

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	return (
		<>
			<Dropdown
				className="list-table-bulk-actions"
				contentClassName="list-table-bulk-actions__popover"
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
				renderContent={ ( { onClose } ) => (
					<NavigableMenu orientation="vertical">
						{ listBulkActions.map(
							( { id, group, render: Component } ) => {
								return (
									<Fragment key={ id }>
										<GroupSeparator group={ group } />
										<Component
											{ ...bulkActionsContext }
											onClose={ onClose }
										/>
									</Fragment>
								);
							}
						) }
					</NavigableMenu>
				) }
			/>
		</>
	);
};

export default ListBulkActions;
