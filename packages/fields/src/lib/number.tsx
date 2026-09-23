import {
	// @ts-ignore
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

import type { NumberFieldProps, WithOnChange } from '../types';

export const parseNumberControlValue = ( value: string = '0' ): number =>
	Number.parseFloat( value );

const NumberField = ( {
	value,
	onChange,
	...fieldProps
}: WithOnChange< NumberFieldProps > ): JSX.Element => {
	return (
		<NumberControl
			{ ...fieldProps }
			value={ value }
			onChange={ ( newValue = '0' ) =>
				onChange( parseNumberControlValue( newValue ) )
			}
			// @ts-ignore
			__nextHasNoMarginBottom={ true }
			__next40pxDefaultSize={ true }
		/>
	);
};

export default NumberField;
