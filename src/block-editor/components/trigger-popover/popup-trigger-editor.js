/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { IconButton } from '@wordpress/components';
/**
 * Internal dependencies
 */
import PopupSelectControl from '../popup-select-control';

export default function PopupTriggerEditor( {
	className,
	onChangeInputValue,
	value,
	...props
} ) {
	return (
		<form
			className={ classnames(
				'block-editor-popup-trigger-popover__popup-editor',
				className,
			) }
			{ ...props }
		>
			<PopupSelectControl
				emptyValueLabel={ __( 'Which popup should open?', 'popup-maker' ) }
				hideLabelFromVision={ true }
				value={ value }
				onChange={ onChangeInputValue }
				required={ true }
				// postType="popup"
			/>
			<IconButton icon="editor-break" label={ __( 'Apply', 'popup-maker' ) } type="submit" />
		</form>
	);
}
