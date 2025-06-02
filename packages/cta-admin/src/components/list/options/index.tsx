import './options.scss';

import { __ } from '@popup-maker/i18n';
import { Fragment, useRef, useState } from '@wordpress/element';
import { moreVertical } from '@wordpress/icons';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';

import { useListOptions } from '../../../registry';

const ListOptions = () => {
	const listOptionsBtnRef = useRef< HTMLButtonElement >();
	const lastGroup = useRef< string | undefined >( undefined );
	const [ isOpen, setIsOpen ] = useState( false );
	const listOptions = useListOptions();

	const listOptionsContext = {};

	if ( listOptions.length === 0 ) {
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
				{ listOptions.map( ( { id, group, render: Component } ) => {
					return (
						<Fragment key={ id }>
							<GroupSeparator group={ group } />
							<Component
								{ ...listOptionsContext }
								onClose={ onClose }
							/>
						</Fragment>
					);
				} ) }
			</NavigableMenu>
		);
	};

	return (
		<Dropdown
			className="list-table-options-menu"
			contentClassName="list-table-options-menu__popover"
			placement="bottom left"
			focusOnMount="firstElement"
			open={ isOpen }
			popoverProps={ {
				noArrow: false,
				anchor: {
					getBoundingClientRect: () => {
						return (
							listOptionsBtnRef.current?.getBoundingClientRect() ||
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
				<Button
					className="popover-toggle"
					aria-label={ __( 'Additional options', 'popup-maker' ) }
					icon={ moreVertical }
					onClick={ () => setIsOpen( ! isOpen ) }
					aria-expanded={ isOpen }
					ref={ ( ref: HTMLButtonElement ) => {
						listOptionsBtnRef.current = ref;
					} }
				/>
			) }
			renderContent={ renderContent }
		/>
	);
};

export default ListOptions;
