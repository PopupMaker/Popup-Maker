import { TextareaControl } from '@wordpress/components';

import type { TextareaFieldProps, WithOnChange } from '../types';

const TextAreaField = ( {
	value,
	onChange,
	rows = 5,
	...fieldProps
}: WithOnChange< TextareaFieldProps > ) => {
	return (
		<TextareaControl
			{ ...fieldProps }
			value={ value ?? '' }
			onChange={ onChange }
			rows={ rows }
			/* @ts-ignore - This exists on all controls, but is not fully typed. */
			__nextHasNoMarginBottom={ true }
		/>
	);
};

export default TextAreaField;
