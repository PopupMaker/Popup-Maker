import React from 'react';
import { Field } from '@popup-maker/fields';
import { URLControl } from '@popup-maker/components';
import { callToActionStore, NOTICE_CONTEXT } from '@popup-maker/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
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
	const { removeNotice } = useDispatch( noticesStore );

	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);

	// Clear field error when value changes
	const handleChange = ( newValue: any ) => {
		// Clear the field-specific error notice when the field is updated
		if ( error ) {
			const errorNoticeId = `field-error-${
				ctaId || 'new'
			}-${ fieldId }`;
			removeNotice( errorNoticeId, NOTICE_CONTEXT );
		}
		onChange( newValue );
	};

	return (
		<FieldWrapper
			fieldId={ fieldId }
			title={ field.label ?? '' }
			error={ error }
		>
			{ field.type === 'url' ? (
				<URLControl
					{ ...field }
					value={ value }
					onChange={ ( urlValue ) => handleChange( urlValue.url ) }
				/>
			) : (
				<Field { ...field } value={ value } onChange={ handleChange } />
			) }
		</FieldWrapper>
	);
};

export default FieldWithError;
