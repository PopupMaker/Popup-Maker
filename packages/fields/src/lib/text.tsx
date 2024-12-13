import { TextControl } from '@wordpress/components';

import type { HiddenFieldProps, TextFieldProps, WithOnChange } from '../types';

const TextField = ( {
	type,
	value,
	onChange,
	...fieldProps
}: WithOnChange< TextFieldProps > | WithOnChange< HiddenFieldProps > ) => {
	return (
		// @ts-ignore
		<TextControl
			{ ...fieldProps }
			type={ type !== 'hidden' ? type : undefined }
			value={ value ?? '' }
			onChange={ onChange }
			/* @ts-ignore - This exists on all controls, but is not fully typed. */
			__nextHasNoMarginBottom={ true }
		/>
	);
};

export default TextField;
