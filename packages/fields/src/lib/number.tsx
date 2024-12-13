import {
	// @ts-ignore
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

import type { NumberFieldProps, WithOnChange } from '../types';

const NumberField = ( {
	value,
	onChange,
	...fieldProps
}: WithOnChange< NumberFieldProps > ) => {
	return (
		<NumberControl
			{ ...fieldProps }
			value={ value }
			onChange={ ( newValue = '0' ) =>
				onChange( parseInt( newValue, 10 ) )
			}
			// @ts-ignore
			__nextHasNoMarginBottom={ true }
		/>
	);
};

export default NumberField;
