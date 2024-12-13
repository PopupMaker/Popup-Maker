import type {
	OptGroups,
	Option,
	OptionLabel,
	Options,
	OptionValue,
	StringObject,
} from './general';
import type { AtLeast } from './generics';

export interface OldFieldArgs {
	type: OldField[ 'type' ];
	allow_html?: boolean;
	as_array?: boolean;
	class?: string;
	classes?: string | string[];
	dependencies?: { [ key: string ]: string | boolean | number };
	desc?: string;
	desc_position?: string;
	dynamic_desc?: string;
	id?: string;
	id_prefix?: string;
	label?: string;
	max?: number;
	min?: number;
	multiple?: boolean;
	name?: string;
	post_type?: string;
	taxonomy?: string;
	options?: Options | OptGroups;
	placeholder?: string;
	required?: boolean;
	select2?: boolean;
	size?: string;
	std?: any;
	step?: number;
	unit?: string;
	units?: {};
	meta?: {};
}

export interface OldFieldBase {
	// Possible delineated union from newer field models.
	// v: 1;
	type: string;
	value?: any;
	id?: string;
	id_prefix?: string;
	//? Should this be optional?
	name?: string;
	label?: string;
	std?: any;
	desc?: string;
	dynamic_desc?: string;
	desc_position?: string;
	class?: string;
	classes?: string | string[];
	required?: boolean;
	meta?: {
		[ key: string ]: any;
	};
	dependencies?: { [ key: string ]: string | boolean | number };
}

export interface OldHiddenField extends OldFieldBase {
	type: 'hidden';
}

export interface OldTextField extends OldFieldBase {
	type: 'text' | 'email' | 'phone' | 'password';
	size?: string;
	placeholder?: string;
}

export interface OldNumberField extends Omit< OldTextField, 'type' > {
	type: 'number';
	min?: number;
	max?: number;
	step?: number;
}

export interface OldRangesliderField extends Omit< OldNumberField, 'type' > {
	type: 'rangeslider';
}

export interface OldMeasureField extends Omit< OldNumberField, 'type' > {
	type: 'measure';
	unit?: string;
	units?: StringObject;
}

export interface OldLicenseField extends Omit< OldTextField, 'type' > {
	type: 'license_key';
}

export interface OldColorField extends Omit< OldTextField, 'type' > {
	type: 'color';
}

export interface OldRadioField extends OldFieldBase {
	type: 'radio';
	options: Option[];
}

export interface OldMulticheckField extends Omit< OldRadioField, 'type' > {
	type: 'multicheck';
}

export type OldSelectOptions =
	| Option[]
	| {
			[ key: OptionValue ]: OptionLabel;
	  }
	| OptionLabel[];

export interface OldSelectField extends OldFieldBase {
	type: 'select';
	select2?: boolean;
	multiple?: boolean;
	as_array?: boolean;
	options: OldSelectOptions;
}

export interface OldSelect2Field extends Omit< OldSelectField, 'type' > {
	type: 'select2';
	select2: true;
}

export interface OldObjectSelectField extends Omit< OldSelect2Field, 'type' > {
	type: 'objectselect' | 'postselect' | 'taxonomyselect' | 'userselect';
	object_type?: 'post' | 'taxonomy' | 'user';
	post_type?: string;
	taxonomy?: string;
	user_roles?: string[];
}

export interface OldPostSelectField extends OldObjectSelectField {
	type: 'postselect';
	object_type?: 'post';
	post_type: string;
}

export interface OldTaxnomySelectField extends OldObjectSelectField {
	type: 'taxonomyselect';
	object_type?: 'taxonomy';
	taxonomy: string;
}

export interface OldUserSelectField extends OldObjectSelectField {
	type: 'userselect';
	object_type?: 'user';
	user_roles?: string[];
}

export interface OldCheckboxField extends OldFieldBase {
	type: 'checkbox';
}

export interface OldTextareaField extends OldFieldBase {
	type: 'textarea';
	allow_html?: boolean;
}

export type OldFieldProps =
	| OldHiddenField
	| OldTextField
	| OldNumberField
	| OldRangesliderField
	| OldMeasureField
	| OldLicenseField
	| OldColorField
	| OldRadioField
	| OldMulticheckField
	| OldSelectField
	| OldSelect2Field
	| OldObjectSelectField
	| OldPostSelectField
	| OldTaxnomySelectField
	| OldCheckboxField
	| OldTextareaField
	| OldUserSelectField;

/**
 * Union of FieldProps converted to partials that still require `type`.
 */
export type PartialOldFieldProps = AtLeast< OldFieldProps, 'type' >;

export type OldFieldMap = {
	checkbox: OldCheckboxField;
	color: OldColorField;
	email: OldTextField;
	hidden: OldHiddenField;
	license_key: OldLicenseField;
	measure: OldMeasureField;
	multicheck: OldMulticheckField;
	multiselect: OldSelectField;
	number: OldNumberField;
	objectselect: OldObjectSelectField;
	password: OldTextField;
	phone: OldTextField;
	postselect: OldPostSelectField;
	radio: OldRadioField;
	rangeslider: OldRangesliderField;
	select: OldSelectField;
	select2: OldSelect2Field;
	taxonomyselect: OldTaxnomySelectField;
	text: OldTextField;
	textarea: OldTextareaField;
	userselect: OldUserSelectField;
};

export type OldFieldValueMap = {
	checkbox: boolean | number | string;
	color: string;
	email: string;
	hidden: string;
	license_key: string;
	measure: string;
	multicheck: { [ key: string ]: boolean };
	multiselect: number[] | string[];
	number: number | string;
	objectselect: number | string | number[] | string[];
	password: string;
	phone: string;
	postselect: number | string | number[] | string[];
	radio: number | string;
	rangeslider: number;
	select: number | string;
	select2: number | string | number[] | string[];
	taxonomyselect: number | string | number[] | string[];
	text: string;
	textarea: string;
	userselect: number | string | number[] | string[];
};

// Catch all union of field types & values.
export type OldField = OldFieldMap[ keyof OldFieldMap ];
export type OldFieldValue = OldFieldValueMap[ keyof OldFieldValueMap ];
