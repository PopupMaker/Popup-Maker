export type OptionLabel = string;
export type OptionValue = string | number;

export type StringArray = string[];
export type NumberArray = number[];
export type StringNumberArray = StringArray | NumberArray;
export type StringObject = { [ key: string ]: string };
export type BooleanObject = { [ key: string ]: boolean };

export interface Option {
	value: OptionValue;
	label: OptionLabel;
}

export type AcceptibleOptions =
	| string
	| Option[]
	| {
			[ key: OptionValue ]: OptionLabel;
	  }
	| OptionLabel[];

export type Options = Option[];

export type OptGroups = {
	[ key: string ]: Options;
};

/**
 * General ControlledInput prop type. Accepts value type as argument.
 */
export interface ControlledInputProps< T > {
	/** Controlled value */
	value: T;
	/** Callback used when the value changes */
	onChange: ( value: T ) => void;
	[ key: string ]: any;
}
