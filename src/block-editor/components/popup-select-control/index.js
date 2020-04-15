//import Select from 'react-select/src/Select';
/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal vars.
 */
const { popups } = window.pum_block_editor_vars;

export default class PopupSelectControl extends Component {
	render() {
		const {
			onChangeInputValue,
			value,
			label = __( 'Select Popup', 'popup-maker' ),
			emptyValueLabel = __( 'Choose a popup', 'popup-maker' ),
			hideLabelFromVision = false,
			...props
		} = this.props;

		const options = [
			{
				value: '',
				label: emptyValueLabel,
			},
			...popups.map( ( popup ) => {
				return {
					value: `${ popup.ID }`,
					label: popup.post_title,
					//disabled: true
				};
			} ),
		];

		return (
			<div className="block-editor-popup-select-input">
				<SelectControl
					label={ label }
					hideLabelFromVision={ hideLabelFromVision }
					value={ value }
					onChange={ onChangeInputValue }
					options={ options }
					{ ...props }
				/>
			</div>
		);
	}
}
