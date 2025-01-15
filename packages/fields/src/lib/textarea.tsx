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
			__nextHasNoMarginBottom
		/>
	);
};

export default TextAreaField;
