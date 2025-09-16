import { __ } from '@popup-maker/i18n';
import { Mark } from '@popup-maker/icons';
import { download } from '@wordpress/icons';
import { useRef, useState } from '@wordpress/element';
import { Button, Flex, Icon, Popover } from '@wordpress/components';

export const ExportBulkAction = () => {
	const [ showPopover, setShowPopover ] = useState( false );
	const btnRef = useRef< HTMLButtonElement | null >( null );

	return (
		<>
			{ showPopover && (
				<Popover
					anchor={ btnRef.current }
					onClose={ () => setShowPopover( false ) }
					// placement="bottom-start"
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

						<Button
							variant="primary"
							href="https://wppopupmaker.com/pricing/?utm_source=popup-maker&utm_medium=cta-editor&utm_campaign=export-cta"
							target="_blank"
						>
							{ __(
								'Learn more or upgrade today',
								'popup-maker'
							) }
						</Button>
					</Flex>
				</Popover>
			) }
			<Flex
				direction="row"
				justify="space-between"
				onMouseEnter={ () => setShowPopover( true ) }
				style={ {
					cursor: 'pointer',
					paddingRight: '10px',
					pointerEvents: 'all',
				} }
			>
				<Button
					icon={ download }
					text={ __( 'Export', 'popup-maker' ) }
					disabled={ true }
					showTooltip={ true }
					label={ __(
						'Export is available with Popup Maker Pro',
						'popup-maker'
					) }
					ref={ btnRef }
				/>
				<Icon icon={ Mark } size={ 14 } />
			</Flex>
		</>
	);
};

export default {
	id: 'export',
	group: 'export',
	priority: 5,
	render: ExportBulkAction,
};
