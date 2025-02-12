import { __ } from '@popup-maker/i18n';
import { Mark } from '@popup-maker/icons';
import { useRef, useState, useCallback } from '@wordpress/element';
import { Button, Flex, Icon, Popover } from '@wordpress/components';

export const ExportQuickAction = () => {
	const [ showPopover, setShowPopover ] = useState( false );
	const btnRef = useRef< HTMLButtonElement | null >( null );
	const timeoutRef = useRef< ReturnType< typeof setTimeout > | null >( null );

	const handleMouseEnter = useCallback( () => {
		if ( timeoutRef.current ) {
			clearTimeout( timeoutRef.current );
		}
		setShowPopover( true );
	}, [] );

	const handleMouseLeave = useCallback( () => {
		if ( timeoutRef.current ) {
			clearTimeout( timeoutRef.current );
		}
		timeoutRef.current = setTimeout( () => {
			setShowPopover( false );
		}, 200 );
	}, [] );

	return (
		<>
			{ showPopover && (
				<Popover
					anchor={ btnRef.current }
					onClose={ () => setShowPopover( false ) }
					placement="bottom-start"
				>
					<Flex
						direction="column"
						gap="16px"
						justify="center"
						align="center"
						className="pmp-popover-content"
						style={ { padding: '16px', minWidth: '350px' } }
					>
						<Icon icon={ Mark } size={ 28 } />

						<h3 style={ { margin: 0 } }>
							{ __(
								'Quick exports with Popup Maker Pro',
								'popup-maker'
							) }
						</h3>

						<p style={ { margin: 0 } }>
							{ __(
								'Popup Maker Pro gives you the power to import & export your call to actions to a JSON file in seconds.',
								'popup-maker'
							) }
						</p>
					</Flex>
				</Popover>
			) }
			<div
				onMouseEnter={ handleMouseEnter }
				onMouseLeave={ handleMouseLeave }
				style={ {
					display: 'inline-flex',
					cursor: 'pointer',
					pointerEvents: 'all',
					minWidth: 'fit-content',
				} }
			>
				<Button
					variant="link"
					text={ __( 'Export', 'popup-maker' ) }
					disabled={ true }
					showTooltip={ true }
					label={ __(
						'Export is available with Popup Maker Pro',
						'popup-maker'
					) }
					ref={ btnRef }
				/>
			</div>
		</>
	);
};

export default {
	id: 'export',
	group: 'general',
	priority: 5,
	render: ExportQuickAction,
};
