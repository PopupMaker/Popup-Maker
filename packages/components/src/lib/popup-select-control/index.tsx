import clsx from 'clsx';

import { __ } from '@popup-maker/i18n';
import { SelectControl } from '@wordpress/components';

import type { PopupSelectControlProps } from './types';
import { SelectControlProps } from '@wordpress/components/build-types/select-control/types';

const { popups } = window.popupMakerComponents;

export const PopupSelectControl = ( {
	onChange,
	value,
	label = __( 'Select Popup', 'popup-maker' ),
	emptyValueLabel = __( 'Choose a popup', 'popup-maker' ),
	hideLabelFromVision = false,
	multiple = false,
	required = false,
	options = [
		{
			value: '',
			label: emptyValueLabel,
		},
		...popups.map( ( { ID, post_title: title } ) => {
			return {
				value: `${ ID }`,
				label: title,
			};
		} ),
	],
	...props
}: PopupSelectControlProps & Pick< SelectControlProps, 'help' > ) => {
	return (
		<SelectControl
			label={ label }
			hideLabelFromVision={ hideLabelFromVision }
			className={ clsx(
				'pum-component-popup-select-control',
				multiple && 'pum-popup-select-control--multiple'
			) }
			options={ options }
			required={ required }
			{ ...{
				// Here for type safety.
				...( multiple
					? {
							multiple: true,
							value: value as string[],
							onChange: onChange as ( value: string[] ) => void,
					  }
					: {
							value: value as string,
							onChange: onChange as ( value: string ) => void,
					  } ),
				...props,
			} }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};

export default PopupSelectControl;
