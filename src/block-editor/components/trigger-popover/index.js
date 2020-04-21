/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { IconButton, Popover } from '@wordpress/components';

/**
 * Style Dependencies.
 * import './editor.scss';
 */
export default class TriggerPopover extends Component {
	constructor() {
		super( ...arguments );

		this.toggleSettingsVisibility = this.toggleSettingsVisibility.bind( this );

		this.state = {
			isSettingsExpanded: false,
		};
	}

	toggleSettingsVisibility() {
		this.setState( {
			isSettingsExpanded: ! this.state.isSettingsExpanded,
		} );
	}

	render() {
		const {
			additionalControls,
			children,
			renderSettings,
			position = 'bottom center',
			focusOnMount = 'firstElement',
			noticeUI,
			...popoverProps
		} = this.props;

		const {
			isSettingsExpanded,
		} = this.state;

		const showSettings = !! renderSettings && isSettingsExpanded;

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
							<IconButton
								className="editor-popup-trigger-popover__settings-toggle block-editor-popup-trigger-popover__settings-toggle"
								icon="arrow-down-alt2"
								label={ __( 'Trigger settings', 'popup-maker' ) }
								onClick={ this.toggleSettingsVisibility }
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
					<div
						className="block-editor-popup-trigger-popover__additional-controls"
					>
						{ additionalControls }
					</div>
				) }
			</Popover>
		);
	}
}
