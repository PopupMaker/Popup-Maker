import { RangeControl } from '@wordpress/components';
import type { RangesliderFieldProps, WithOnChange } from '../types';

const RangeSliderField = ( {
	value,
	onChange,
	initialPosition = 0,
	...fieldProps
}: WithOnChange< RangesliderFieldProps > ) => {
	const { step } = fieldProps;

	return (
		<RangeControl
			// { ...fieldProps }
			value={ value ?? initialPosition }
			onChange={ ( newValue = 0 ) => onChange( newValue ) }
			withInputField={ true }
			__nextHasNoMarginBottom={ true }
			type={ step ? 'stepper' : undefined }
		/>
	);
};

export default RangeSliderField;
