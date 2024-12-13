import {
	// @ts-ignore
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

import type { MeasureFieldProps, WithOnChange } from '../types';

const MeasureField = ( {
	value,
	onChange,
	units,
	...fieldProps
}: WithOnChange< MeasureFieldProps > ) => {
	const number = value ? parseInt( value ) : '';

	const [ state, setState ] = useState( {
		number,
		unit: value?.replace( `${ number }`, '' ) ?? '',
	} );

	useEffect( () => {
		onChange( `${ state.number }${ state.unit }` );
	}, [ state, onChange ] );

	const unitsArray = Object.entries( units ).map( ( [ v, l ] ) => ( {
		value: v,
		label: l,
	} ) );

	return (
		<UnitControl
			{ ...fieldProps }
			value={ value }
			onChange={ ( newNumber: string = '0' ) =>
				setState( { ...state, number: newNumber } )
			}
			disableUnits={ unitsArray.length === 0 }
			units={ unitsArray }
			onUnitChange={ ( newUnit: string = '' ) =>
				setState( { ...state, unit: newUnit } )
			}
			// @ts-ignore
			__nextHasNoMarginBottom={ true }
		/>
	);
};

export default MeasureField;
