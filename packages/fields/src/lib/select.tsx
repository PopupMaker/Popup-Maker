import { SelectControl } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';

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
	optionDescriptions,
	...fieldProps
}:
	| WithOnChange< SelectFieldProps >
	| WithOnChange< MultiselectFieldProps > ): JSX.Element => {
	const { multiple = false } = fieldProps;

	const options = fieldProps.options ?? {};
	const selectedValue = Array.isArray( value )
		? ''
		: String( value ?? fieldProps.default ?? '' );
	const selectedDescription = optionDescriptions?.[ selectedValue ];
	let controlValue = value;

	if ( ! multiple ) {
		controlValue = selectedValue;
	} else if ( typeof value === 'string' ) {
		// Correct older string type values (here for sanity).
		controlValue = value.split( ',' );
	}

	return (
		<>
			{ /* @ts-ignore */ }
			<SelectControl
				{ ...fieldProps }
				multiple={ multiple }
				value={ controlValue }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			>
				{ hasOptGroups( options ) ? (
					<OptGroups optGroups={ options } />
				) : (
					<Options options={ options } />
				) }
			</SelectControl>
			{ selectedDescription && (
				<p
					className="components-base-control__help pum-field__selected-help"
					aria-live="polite"
				>
					<strong>{ __( 'Selected:', 'popup-maker' ) }</strong>{ ' ' }
					{ selectedDescription }
				</p>
			) }
		</>
	);
};

export default SelectField;
