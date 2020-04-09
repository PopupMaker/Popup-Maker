/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { removeFormat } from '@wordpress/rich-text';
import { Component } from '@wordpress/element';
import { withSpokenMessages } from '@wordpress/components';
import { RichTextShortcut, RichTextToolbarButton } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import LogoIcon from '../../icons/logo';
import InlinePopupTriggerUI from './inline';

const title = __( 'Popup Trigger', 'popup-maker' );

export const name = `popup-maker/popup-trigger`;
export const settings = {
	name,
	title,
	tagName: 'span',
	className: 'popup-trigger',
	attributes: {
		popupId: 'data-popup-id',
		doDefault: 'data-do-default',
	},
	edit: withSpokenMessages( class TriggerEdit extends Component {
		constructor() {
			super( ...arguments );

			this.addTrigger = this.addTrigger.bind( this );
			this.stopAddingTrigger = this.stopAddingTrigger.bind( this );
			this.onRemoveFormat = this.onRemoveFormat.bind( this );
			this.state = {
				addingTrigger: false,
			};
		}

		addTrigger() {
			this.setState( { addingTrigger: true } );
		}

		stopAddingTrigger() {
			this.setState( { addingTrigger: false } );
		}

		onRemoveFormat() {
			const { value, onChange, speak } = this.props;

			onChange( removeFormat( value, name ) );
			speak( __( 'Trigger removed.', 'popup-maker' ), 'assertive' );
		}

		render() {
			const { isActive, activeAttributes, value, onChange } = this.props;

			return (
				<>
					<RichTextShortcut
						type="primary"
						character="["
						onUse={ this.addTrigger }
					/>
					<RichTextShortcut
						type="primaryShift"
						character="["
						onUse={ this.onRemoveFormat }
					/>
					{ isActive && <RichTextToolbarButton
						icon={ LogoIcon }
						title={ __( 'Remove Trigger', 'popup-maker' ) }
						onClick={ this.onRemoveFormat }
						isActive={ isActive }
						shortcutType="primaryShift"
						shortcutCharacter="["
					/> }
					{ ! isActive && <RichTextToolbarButton
						icon={ LogoIcon }
						title={ title }
						onClick={ this.addTrigger }
						isActive={ isActive }
						shortcutType="primary"
						shortcutCharacter="["
					/> }
					<InlinePopupTriggerUI
						addingTrigger={ this.state.addingTrigger }
						stopAddingTrigger={ this.stopAddingTrigger }
						isActive={ isActive }
						activeAttributes={ activeAttributes }
						value={ value }
						onChange={ onChange }
					/>
				</>
			);
		}
	} ),
};
