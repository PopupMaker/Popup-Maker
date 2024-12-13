import classnames from 'classnames';

import { __, sprintf } from '@wordpress/i18n';

import {
	CheckboxField,
	ColorField,
	DateField,
	MeasureField,
	MulticheckField,
	NumberField,
	ObjectSelectField,
	RadioField,
	RangeSliderField,
	SelectField,
	TextAreaField,
	TextField,
	TokenSelectField,
} from './';

import type { FieldPropsWithOnChange } from '../types';
import { parseFieldProps } from './utils';

const FieldComponent = ( fieldProps: FieldPropsWithOnChange ): JSX.Element => {
	const { type } = fieldProps;

	switch ( type ) {
		case 'checkbox':
			return <CheckboxField { ...fieldProps } />;
		case 'color':
			return <ColorField { ...fieldProps } />;
		case 'date':
			return <DateField { ...fieldProps } />;
		case 'measure':
			return <MeasureField { ...fieldProps } />;
		case 'multicheck':
			return <MulticheckField { ...fieldProps } />;
		case 'select':
		case 'multiselect':
			return <SelectField { ...fieldProps } />;
		case 'objectselect':
		case 'postselect':
		case 'taxonomyselect':
		case 'userselect':
			return <ObjectSelectField { ...fieldProps } />;
		case 'radio':
			return <RadioField { ...fieldProps } />;
		case 'rangeslider':
			return <RangeSliderField { ...fieldProps } />;
		case 'number':
			return <NumberField { ...fieldProps } />;
		case 'email':
		case 'tel':
		case 'hidden':
		case 'text':
		case 'password':
			return <TextField { ...fieldProps } />;
		case 'textarea':
			return <TextAreaField { ...fieldProps } />;
		case 'tokenselect':
			return <TokenSelectField { ...fieldProps } />;
	}

	return (
		<>
			{ sprintf(
				/* translators: 1. type of field not found. */
				__( 'Field type `%s` not found', 'popup-maker' ),
				type
			) }
		</>
	);
};

const Field = ( props: FieldPropsWithOnChange ) => {
	const { type, className, onChange } = props;

	return (
		<div
			className={ classnames( [
				'pum-field',
				`pum-field--${ type }`,
				className,
			] ) }
		>
			{ /*
			// @ts-ignore */ }
			<FieldComponent
				onChange={ onChange }
				{ ...parseFieldProps( props ) }
			/>
		</div>
	);
};

export default Field;
