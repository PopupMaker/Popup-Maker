import React from 'react';
import clsx from 'clsx';

type FieldWrapperProps = {
	fieldId: string;
	error?: string | null;
	className?: string;
	children: React.ReactNode;
};

/**
 * Field wrapper component that handles error styling and display.
 *
 * @param {FieldWrapperProps} props           - The component props.
 * @param {string}            props.fieldId   - The ID of the field.
 * @param {string | null}     props.error     - The error message to display.
 * @param {string}            props.className - The class name to apply to the wrapper.
 * @param {React.ReactNode}   props.children  - The child elements to render.
 * @return {React.ReactNode}                  The rendered field wrapper.
 */
export const FieldWrapper: React.FC< FieldWrapperProps > = ( {
	fieldId,
	error,
	className,
	children,
} ) => {
	const hasError = error !== null && error !== undefined && error !== '';

	return (
		<div
			className={ clsx(
				'cta-field-wrapper',
				`cta-field-wrapper--${ fieldId }`,
				{
					'cta-field-wrapper--has-error': hasError,
				},
				className
			) }
		>
			{ children }
			{ hasError && <div className="cta-field-error">{ error }</div> }
		</div>
	);
};

export default FieldWrapper;
