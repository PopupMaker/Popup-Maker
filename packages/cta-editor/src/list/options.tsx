import './options.scss';

import { __ } from '@popup-maker/i18n';
import { Fragment, useRef } from '@wordpress/element';
import { moreVertical } from '@wordpress/icons';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';

import { useListOptions } from '../registry';

const ListOptions = () => {
	const listOptionsBtnRef = useRef< HTMLButtonElement >();
	const lastGroup = useRef< string | undefined >( undefined );
	const listOptions = useListOptions();

	const listOptionsContext = {};

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
	console.log( listOptions );

	return (
		<Dropdown
			className="list-table-options-menu"
			contentClassName="list-table-options-menu__popover"
			placement="bottom left"
			focusOnMount="firstElement"
			popoverProps={ {
				noArrow: false,
				anchor: {
					getBoundingClientRect: () =>
						listOptionsBtnRef?.current?.getBoundingClientRect(),
				} as Element,
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="popover-toggle"
					ref={ ( ref: HTMLButtonElement ) => {
						listOptionsBtnRef.current = ref;
					} }
					aria-label={ __( 'Additional options', 'popup-maker' ) }
					onClick={ onToggle }
					aria-expanded={ isOpen }
					icon={ moreVertical }
				/>
			) }
			renderContent={ ( { onClose } ) => (
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
			) }
		/>
	);
};

export default ListOptions;
