import {
	BaseControl,
	CheckboxControl,
	FormToggle,
} from '@wordpress/components';

import { parseFieldOptions } from './utils';

import type { MulticheckFieldProps, WithOnChange } from '../types';

const MulticheckField = ( {
	value,
	onChange,
	...fieldProps
}: WithOnChange< MulticheckFieldProps > ) => {
	const toggle = false;

	const checked = value ?? [];

	const options = parseFieldOptions( fieldProps.options );

	const checkedOpts = value ?? [];

	/**
	 * Foreach option render a checkbox. value can be an array
	 * of keys, or an object with key: boolean pairs.
	 */

	const CheckBoxes = () => (
		<>
			{ options.map( ( { label: optLabel, value: optValue } ) => {
				const isChecked = checked.indexOf( optValue ) >= 0;

				const toggleOption = () =>
					onChange(
						! isChecked
							? [ ...checkedOpts, optValue ]
							: checkedOpts.filter( ( val ) => optValue !== val )
					);

				if ( ! toggle ) {
					return (
						<CheckboxControl
							key={ optValue }
							label={ optLabel }
							checked={ isChecked }
							onChange={ toggleOption }
						/>
					);
				}
				return (
					<BaseControl
						key={ optValue }
						id={ fieldProps.id + '-' + optValue.toString() }
						label={ optLabel }
					>
						<FormToggle
							checked={ isChecked }
							onChange={ toggleOption }
						/>
					</BaseControl>
				);
			} ) }
		</>
	);

	return <CheckBoxes />;
};

export default MulticheckField;
