import classnames from 'classnames';

import { __, sprintf } from '@popup-maker/i18n';

import {
	CheckboxField,
	ColorField,
	CustomSelectField,
	DateField,
	MeasureField,
	MulticheckField,
	HtmlField,
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
	const controlProps = { ...fieldProps };
	delete controlProps.heading;
	delete controlProps.dependencies;
	delete controlProps.priority;
	const { type } = controlProps;

	switch ( type ) {
		case 'checkbox':
			return <CheckboxField { ...controlProps } />;
		case 'color':
			return <ColorField { ...controlProps } />;
		case 'customselect':
			return <CustomSelectField { ...controlProps } />;
		case 'date':
			return <DateField { ...controlProps } />;
		case 'measure':
			return <MeasureField { ...controlProps } />;
		case 'multicheck':
			return <MulticheckField { ...controlProps } />;
		case 'select':
		case 'multiselect':
			return <SelectField { ...controlProps } />;
		case 'objectselect':
		case 'postselect':
		case 'taxonomyselect':
		case 'userselect':
			return <ObjectSelectField { ...controlProps } />;
		case 'radio':
			return <RadioField { ...controlProps } />;
		case 'rangeslider':
			return <RangeSliderField { ...controlProps } />;
		case 'number':
			return <NumberField { ...controlProps } />;
		case 'email':
		case 'tel':
		case 'hidden':
		case 'text':
		case 'password':
			return <TextField { ...controlProps } />;
		case 'textarea':
			return <TextAreaField { ...controlProps } />;
		case 'tokenselect':
			return <TokenSelectField { ...controlProps } />;
		case 'html':
			return <HtmlField { ...controlProps } />;
	}

	return (
		<>
			{ sprintf(
				/* translators: %s: field type that was not found. */
				__( 'Field type `%s` not found', 'popup-maker' ),
				type
			) }
		</>
	);
};

const Field = ( props: FieldPropsWithOnChange ): JSX.Element => {
	const { type, className, onChange } = props;

	return (
		<div
			className={ classnames( [
				'pum-field',
				`pum-field--${ type }`,
				className,
			] ) }
		>
			{ /* @ts-ignore */ }
			<FieldComponent
				onChange={ onChange }
				{ ...parseFieldProps( props ) }
			/>
		</div>
	);
};

export default Field;
