import { BaseControl } from '@wordpress/components';

import type { DateFieldProps, WithOnChange } from '../types';

const DateField = ( {
	value,
	onChange,
	...fieldProps
}: WithOnChange< DateFieldProps > ): JSX.Element => {
	return (
		<>
			<BaseControl
				{ ...fieldProps }
				hideLabelFromVision={ true }
				__nextHasNoMarginBottom
			>
				<input
					type="date"
					value={ value }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
			</BaseControl>
		</>
	);
};

export default DateField;
