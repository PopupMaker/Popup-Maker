import { RadioControl } from '@wordpress/components';

import type { RadioFieldProps, WithOnChange } from '../types';

const RadioField = ( {
	value,
	onChange,
	...fieldProps
}: WithOnChange< RadioFieldProps > ) => {
	const options = fieldProps.options;

	return (
		<RadioControl
			{ ...fieldProps }
			selected={ value?.toString() }
			options={ options as { value: string; label: string }[] }
			onChange={ onChange }
			/* @ts-ignore - This exists on all controls, but is not fully typed. */
			__nextHasNoMarginBottom={ true }
		/>
	);
};

export default RadioField;
