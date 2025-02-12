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
import { Fragment, useRef, useState } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';

import { useList } from '../../../context';
import { useListBulkActions } from '../../../registry';

const ListBulkActions = () => {
	const lastGroup = useRef< string | undefined >( undefined );
	const btnRef = useRef< HTMLButtonElement >();
	const [ isOpen, setIsOpen ] = useState( false );
	const listBulkActions = useListBulkActions();

	const { bulkSelection = [] } = useList();

	const bulkActionsContext = {};

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	/**
	 * Separates new groups of options with a horizontal line.
	 * @param {Object} props
	 * @param {string} props.group
	 */
	const GroupSeparator = ( { group }: { group?: string } ) => {
		if ( ! group || group === lastGroup.current ) {
			return null;
		}

		const previousGroup = lastGroup.current;
		lastGroup.current = group;

		return previousGroup ? <hr /> : null;
	};

	const renderContent = ( { onClose }: { onClose: () => void } ) => {
		lastGroup.current = undefined;

		return (
			<NavigableMenu orientation="vertical">
				{ listBulkActions.map( ( { id, group, render: Component } ) => {
					return (
						<Fragment key={ id }>
							<GroupSeparator group={ group } />
							<Component
								{ ...bulkActionsContext }
								onClose={ onClose }
							/>
						</Fragment>
					);
				} ) }
			</NavigableMenu>
		);
	};

	return (
		<>
			<Dropdown
				className="list-table-bulk-actions"
				contentClassName="list-table-bulk-actions__popover"
				placement="bottom left"
				focusOnMount="firstElement"
				open={ isOpen }
				popoverProps={ {
					noArrow: false,
					anchor: {
						getBoundingClientRect: () => {
							return (
								btnRef.current?.getBoundingClientRect() ||
								new DOMRect()
							);
						},
					},
					onClose: () => {
						setIsOpen( false );
					},
					onFocusOutside: () => {
						setIsOpen( false );
					},
				} }
				renderToggle={ () => (
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
								btnRef.current = ref;
							} }
							aria-label={ __( 'Bulk Actions', 'popup-maker' ) }
							variant="secondary"
							onClick={ () => setIsOpen( ! isOpen ) }
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
				renderContent={ renderContent }
			/>
		</>
	);
};

export default ListBulkActions;
