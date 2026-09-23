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
	help,
	...fieldProps
}: WithOnChange< CheckboxFieldProps > ): JSX.Element => {
	const toggle = false;

	if ( ! toggle ) {
		return (
			<>
				<CheckboxControl
					{ ...fieldProps }
					label={ label }
					checked={ value }
					onChange={ onChange }
					__nextHasNoMarginBottom
				/>
				{ help && (
					<p className="components-base-control__help">{ help }</p>
				) }
			</>
		);
	}

	return (
		<BaseControl
			id={ fieldProps.id }
			label={ label }
			help={ help }
			__nextHasNoMarginBottom
		>
			<FormToggle
				checked={ value }
				onChange={ () => onChange( ! value ) }
				{ ...fieldProps }
			/>
		</BaseControl>
	);
};

export default CheckboxField;
