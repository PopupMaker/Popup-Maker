import { SelectControl } from '@wordpress/components';

import { parseFieldOptions } from './utils';

import type {
	MultiselectFieldProps,
	OptGroups as OptGroupsProp,
	Options as OptsProp,
	SelectFieldProps,
	WithOnChange,
} from '../types';
/**
 * Options|OptGroups Type check handler.
 *
 * @param {OptsProp|OptGroupsProp} options Options to check for groups.
 * @return {boolean} True if optgroups found.
 */
export const hasOptGroups = (
	options: OptsProp | OptGroupsProp
): options is OptGroupsProp =>
	Object.entries( options ).reduce( ( hasGroups, [ _key, _value ] ) => {
		if ( true === hasGroups ) {
			return hasGroups;
		}

		return (
			typeof _key === 'string' &&
			! ( parseInt( _key ) >= 0 ) &&
			typeof _value === 'object'
		);
	}, false );

type OptionsProps = { options: OptsProp };

const Options = ( { options }: OptionsProps ) => (
	<>
		{ parseFieldOptions( options ).map( ( { label, value } ) => (
			<option key={ value } value={ value }>
				{ label }
			</option>
		) ) }
	</>
);

const OptGroups = ( { optGroups }: { optGroups: OptGroupsProp } ) => (
	<>
		{ Object.entries( optGroups ).map( ( [ label, options ] ) => (
			<optgroup key={ label } label={ label }>
				<Options options={ options } />
			</optgroup>
		) ) }
	</>
);

const SelectField = ( {
	value,
	onChange,
	...fieldProps
}:
	| WithOnChange< SelectFieldProps >
	| WithOnChange< MultiselectFieldProps > ) => {
	const { multiple = false } = fieldProps;

	const options = fieldProps.options ?? {};

	return (
		// @ts-ignore
		<SelectControl
			{ ...fieldProps }
			multiple={ multiple }
			value={
				// Correct older string typ values (here for sanity).
				multiple && typeof value === 'string'
					? value.split( ',' )
					: value
			}
			onChange={ onChange }
			/* @ts-ignore - This exists on all controls, but is not fully typed. */
			__nextHasNoMarginBottom={ true }
		>
			{ hasOptGroups( options ) ? (
				<OptGroups optGroups={ options } />
			) : (
				<Options options={ options } />
			) }
		</SelectControl>
	);
};

export default SelectField;
