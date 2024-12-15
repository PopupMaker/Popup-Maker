import clsx from 'clsx';

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { PopupSelectControl } from '@popup-maker/components';

type Props = Pick< HTMLFormElement, 'onKeyDown' | 'onKeyPress' > & {
	className?: string;
	onChangeInputValue: ( value: string ) => void;
	value: string | number;
};

const PopupTriggerEditor = ( {
	className,
	onChangeInputValue,
	value,
	...props
}: Props ) => {
	return (
		<form
			className={ clsx(
				'block-editor-popup-trigger-popover__popup-editor',
				className
			) }
			{ ...props }
		>
			<div className="block-editor-popup-select-input">
				<PopupSelectControl
					emptyValueLabel={ __(
						'Which popup should open?',
						'popup-maker'
					) }
					hideLabelFromVision={ true }
					value={ value }
					onChange={ onChangeInputValue }
					required={ true }
					// postType="popup"
				/>
				<Button
					icon="editor-break"
					label={ __( 'Apply', 'popup-maker' ) }
					type="submit"
				/>
			</div>
		</form>
	);
};

export default PopupTriggerEditor;
