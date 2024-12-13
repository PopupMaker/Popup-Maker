import {
	ColorIndicator,
	ColorPalette,
	ColorPicker,
} from '@wordpress/components';

import type { HexColorFieldProps, WithOnChange } from '../types';

const ColorField = ( {
	value = '',
	onChange,
	...fieldProps
}: WithOnChange< HexColorFieldProps > ) => {
	const colors = [
		{ name: 'red', color: '#f00' },
		{ name: 'white', color: '#fff' },
		{ name: 'blue', color: '#00f' },
	];

	return (
		<>
			<ColorIndicator colorValue={ value } />
			<ColorPicker
				{ ...fieldProps }
				color={ value }
				onChangeComplete={ ( color ) =>
					// @ts-ignore
					onChange( color?.hex ?? color )
				}
			/>
			<ColorPalette
				value={ value }
				onChange={ ( newValue ) => {
					onChange( newValue ?? '' );
				} }
				colors={ colors }
				clearable={ true }
			/>
		</>
	);
};

export default ColorField;
