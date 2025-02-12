import { debounce } from 'lodash';

import { __ } from '@popup-maker/i18n';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';
import { Fragment, useRef, useState } from '@wordpress/element';
import { moreHorizontal, moreVertical } from '@wordpress/icons';

import { useEditorHeaderOptions } from '../../registry';

import type { EditableCta } from '@popup-maker/core-data';
import type { EditorHeaderOptionsContext } from '../../registry';

const EditorHeaderOptions = ( {
	values,
	closeModal,
}: {
	values: EditableCta;
	closeModal: () => void;
} ) => {
	const lastGroup = useRef< string | undefined >( undefined );
	const btnRef = useRef< HTMLButtonElement >();
	const clickOffTimerRef = useRef< NodeJS.Timeout | null >( null );
	const [ isOpen, setIsOpen ] = useState( false );

	const toggleOpen = debounce( () => {
		setIsOpen( ! isOpen );
	}, 100 );

	const headerOptions = useEditorHeaderOptions();

	const headerOptionsContext: EditorHeaderOptionsContext< EditableCta > = {
		values,
		closeModal,
	};

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

	const renderContent = () => {
		lastGroup.current = undefined;

		return (
			<NavigableMenu orientation="vertical">
				{ headerOptions.map( ( { id, group, render: Component } ) => {
					return (
						<Fragment key={ id }>
							<GroupSeparator group={ group } />
							<Component { ...headerOptionsContext } />
						</Fragment>
					);
				} ) }
			</NavigableMenu>
		);
	};

	return (
		<>
			<Dropdown
				className="editor-header-options"
				contentClassName="editor-header-options__popover"
				focusOnMount="firstElement"
				open={ isOpen }
				popoverProps={ {
					noArrow: false,
					placement: 'bottom',
					anchor: {
						getBoundingClientRect: () => {
							return (
								btnRef.current?.getBoundingClientRect() ||
								new DOMRect()
							);
						},
					},
					onClose: () => {
						toggleOpen();
					},
					onFocusOutside: () => {
						toggleOpen();
						clickOffTimerRef.current = setTimeout( () => {
							clearTimeout(
								clickOffTimerRef.current ?? undefined
							);

							clickOffTimerRef.current = null;
						}, 300 );
					},
				} }
				renderToggle={ () => (
					<Button
						className="popover-toggle"
						ref={ ( ref: HTMLButtonElement ) => {
							btnRef.current = ref;
						} }
						aria-label={ __( 'Options', 'popup-maker' ) }
						variant="link"
						onClick={ () => {
							if ( ! isOpen && ! clickOffTimerRef.current ) {
								setIsOpen( true );
							}
						} }
						icon={ isOpen ? moreVertical : moreHorizontal }
						iconSize={ 20 }
					/>
				) }
				renderContent={ renderContent }
			/>
		</>
	);
};

export default EditorHeaderOptions;
