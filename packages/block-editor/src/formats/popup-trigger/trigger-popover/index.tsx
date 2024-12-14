import './editor.scss';

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Popover, Button } from '@wordpress/components';
import type { PopoverProps } from '@wordpress/components/build-types/popover/types';

type Props = PopoverProps & {
	additionalControls?: React.ReactNode;
	children: React.ReactNode;
	renderSettings?: () => React.ReactNode;
	position?: 'top center' | 'bottom center';
	focusOnMount?: 'firstElement' | boolean;
	noticeUI?: React.ReactNode;
};

const TriggerPopover = ( props: Props ) => {
	const {
		additionalControls,
		children,
		renderSettings,
		position = 'bottom center',
		focusOnMount = 'firstElement',
		noticeUI,
		...popoverProps
	} = props;

	const [ isSettingsExpanded, setSettingsExpanded ] =
		useState< boolean >( false );

	const showSettings = !! renderSettings && isSettingsExpanded;

	const toggleSettingsVisibility = () => {
		setSettingsExpanded( ! isSettingsExpanded );
	};

	return (
		<Popover
			className="editor-popup-trigger-popover block-editor-popup-trigger-popover"
			focusOnMount={ focusOnMount }
			position={ position }
			{ ...popoverProps }
		>
			<div className="block-editor-popup-trigger-popover__input-container">
				{ noticeUI }
				<div className="editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row">
					{ children }
					{ !! renderSettings && (
						<Button
							className="editor-popup-trigger-popover__settings-toggle block-editor-popup-trigger-popover__settings-toggle"
							icon="arrow-down-alt2"
							label={ __( 'Trigger settings', 'popup-maker' ) }
							onClick={ toggleSettingsVisibility }
							aria-expanded={ isSettingsExpanded }
						/>
					) }
				</div>
				{ showSettings && (
					<div className="editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row editor-popup-trigger-popover__settings block-editor-popup-trigger-popover__settings">
						{ renderSettings() }
					</div>
				) }
			</div>
			{ additionalControls && ! showSettings && (
				<div className="block-editor-popup-trigger-popover__additional-controls">
					{ additionalControls }
				</div>
			) }
		</Popover>
	);
};

export default TriggerPopover;

export { default as PopupTriggerEditor } from './popup-trigger-editor';
export { default as PopupTriggerViewer } from './popup-trigger-viewer';
