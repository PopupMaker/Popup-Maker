import React from 'react';
import { Field } from '@popup-maker/fields';
import { FieldPanel, URLControl } from '@popup-maker/components';
import { FieldWrapper } from './field-wrapper';
import { useFieldError } from '../hooks';

import type { FieldProps } from '@popup-maker/fields';

interface FieldWithErrorProps {
	fieldId: string;
	field: FieldProps;
	value: any;
	onChange: ( value: any ) => void;
}

/**
 * Custom field component that wraps fields with error handling.
 *
 * @param {FieldWithErrorProps}           props          - The component props.
 * @param {string}                        props.fieldId  - The ID of the field.
 * @param {FieldProps & { type: string }} props.field    - The field definition.
 * @param {any}                           props.value    - The current value of the field.
 * @param {(value: any) => void}          props.onChange - The function to call when the value changes.
 * @return {React.ReactNode} The rendered field component.
 */
export const FieldWithError: React.FC< FieldWithErrorProps > = ( {
	fieldId,
	field,
	value,
	onChange,
} ) => {
	const error = useFieldError( fieldId );

	return (
		<FieldWrapper fieldId={ fieldId } error={ error }>
			<FieldPanel title={ field.label ?? '' }>
				{ field.type === 'url' ? (
					<URLControl
						{ ...field }
						value={ value }
						onChange={ ( urlValue ) => onChange( urlValue.url ) }
					/>
				) : (
					<Field { ...field } value={ value } onChange={ onChange } />
				) }
			</FieldPanel>
		</FieldWrapper>
	);
};

export default FieldWithError;
