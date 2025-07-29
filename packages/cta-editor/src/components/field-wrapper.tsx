import React from 'react';
import clsx from 'clsx';
import { Panel, PanelBody } from '@wordpress/components';

type FieldWrapperProps = {
	fieldId: string;
	title: string;
	error?: string | null;
	className?: string;
	children: React.ReactNode;
};

/**
 * Field wrapper component that handles error styling and display.
 *
 * @param {FieldWrapperProps} props           - The component props.
 * @param {string}            props.fieldId   - The ID of the field.
 * @param {string}            props.title     - The title of the field.
 * @param {string | null}     props.error     - The error message to display.
 * @param {string}            props.className - The class name to apply to the wrapper.
 * @param {React.ReactNode}   props.children  - The child elements to render.
 * @return {React.ReactNode}                  The rendered field wrapper.
 */
export const FieldWrapper: React.FC< FieldWrapperProps > = ( {
	fieldId,
	title,
	error,
	className,
	children,
} ) => {
	const hasError = error !== null && error !== undefined && error !== '';

	return (
		<Panel
			header={ title }
			className={ clsx(
				'components-field-panel',
				'cta-field-wrapper',
				`cta-field-wrapper--${ fieldId }`,
				{
					'cta-field-wrapper--has-error': hasError,
				},
				className
			) }
		>
			<PanelBody opened>
				{ children }
				{ hasError && <div className="cta-field-error">{ error }</div> }
			</PanelBody>
		</Panel>
	);
};

export default FieldWrapper;
