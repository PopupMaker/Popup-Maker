import { applyFilters } from '@wordpress/hooks';
import { createContext, useContext } from '@wordpress/element';

export interface BaseFieldDef {
	component: JSX.Element;
	id: string | number;
	priority: number;
	// Optional grouping of fields
	tab?: string;
	section?: string;
	panel?: string;
}

export interface BaseFieldValues {
	[ key: string ]: any;
}

export interface FieldDef extends BaseFieldDef {}
export interface FieldValues extends BaseFieldValues {}

export interface FieldsContextType<
	F extends FieldDef = BaseFieldDef,
	V extends FieldValues = BaseFieldValues,
	K extends keyof V = keyof V,
> {
	context: string;
	fields?: F[];
	values: Record< K, V[ K ] >;
	setValues: ( values: Record< K, V[ K ] > ) => void;
}

export const FieldsContext = createContext< FieldsContextType >( {
	context: '',
	fields: [],
	values: {} as Record< string, any >,
	setValues: () => {},
} );

export const FieldContextProvider = <
	T extends FieldsContextType = FieldsContextType,
>( {
	context,
	fields,
	values,
	setValues,
	children,
}: T & {
	children: React.ReactNode;
} ) => {
	return (
		<FieldsContext.Provider
			value={ { context, fields, values, setValues } }
		>
			{ children }
		</FieldsContext.Provider>
	);
};

type FieldFilters = {
	tab?: string;
	section?: string;
	panel?: string;
	visible?: boolean;
};

export const useFields = <
	F extends BaseFieldDef = FieldDef,
	V extends BaseFieldValues = FieldValues,
>(): FieldsContextType & {
	getFields: ( filters?: FieldFilters ) => F[];
	updateField: ( field: string, value: any ) => void;
	fieldIsVisible: ( field: string ) => boolean;
} => {
	const {
		context,
		fields = [],
		values,
		setValues,
	} = useContext( FieldsContext );

	const getFields = (
		filters: FieldFilters = {
			visible: true,
		}
	) => {
		const _fields = ( fields ?? [] )
			.filter( ( field ) => {
				if ( filters?.tab && field.tab !== filters.tab ) {
					return false;
				}

				if ( filters?.section && field.section !== filters.section ) {
					return false;
				}

				if ( filters?.panel && field.panel !== filters.panel ) {
					return false;
				}

				if ( filters?.visible && ! fieldIsVisible( field.id ) ) {
					return false;
				}

				return true;
			} )
			.sort( ( a, b ) => a.priority - b.priority )
			.map( ( field ) => {
				/**
				 * Allow external overrides via a filter with null default.
				 *
				 * @param {JSX.Element} component The current field component.
				 * @param {string}      id        The field name.
				 * @param {FieldDef}    field     The current tab name.
				 *
				 * @return {JSX.Element} The new field component.
				 */
				const component = applyFilters(
					`${ context }.renderField`,
					field.component,
					field.id,
					field
				) as JSX.Element;

				return {
					...field,
					component,
				} as F;
			} );

		/**
		 * Allow external overrides via a filter with null default.
		 *
		 * @param {F[]}          fields  The current fields.
		 * @param {FieldFilters} filters The current filters.
		 * @return {F[]} The new fields.
		 */
		return applyFilters(
			`${ context }.getFields`,
			_fields,
			filters
		) as F[];
	};

	const updateField = < K extends keyof V >( field: K, value: V[ K ] ) => {
		setValues( {
			...values,
			[ field ]: value,
		} );
	};

	const fieldIsVisible = ( field: keyof V ): boolean => {
		/**
		 * Allow external overrides via a filter with null default.
		 *
		 * @param {boolean|undefined} show   The current value of the field.
		 * @param {string}            field  The field name.
		 * @param {V}                 values The current values.
		 * @return {boolean|undefined} The new value of the field.
		 */

		const show = applyFilters(
			`${ context }.fieldIsVisible`,
			undefined,
			field,
			values // Values.
		) as boolean | undefined;

		// If the filter returned a value, use it.
		return show !== undefined ? show : true;
	};

	return {
		context,
		fields,
		values,
		setValues,
		getFields,
		updateField,
		fieldIsVisible,
	};
};

export default useFields;
