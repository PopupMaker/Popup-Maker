import {
	BaseControl,
	CheckboxControl,
	FormToggle,
} from '@wordpress/components';

import type { CheckboxFieldProps, WithOnChange } from '../types';

const CheckboxField = ( {
	value,
	onChange,
	label,
	...fieldProps
}: WithOnChange< CheckboxFieldProps > ) => {
	const toggle = false;

	if ( ! toggle ) {
		return (
			<CheckboxControl
				{ ...fieldProps }
				label={ label }
				checked={ value }
				onChange={ onChange }
			/>
		);
	}

	return (
		<BaseControl id={ fieldProps.id } label={ label }>
			<FormToggle
				checked={ value }
				onChange={ () => onChange( ! value ) }
				{ ...fieldProps }
			/>
		</BaseControl>
	);
};

export default CheckboxField;
