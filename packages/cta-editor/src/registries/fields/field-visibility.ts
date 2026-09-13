import type { FieldProps, OldFieldBase } from '@popup-maker/fields';
import type { CallToAction } from '@popup-maker/core-data';

type FieldDefaults = Record< string, any >;

/**
 * Read defaults from both current field props and legacy PHP field definitions.
 *
 * @param field Field definition to inspect.
 */
export const getFieldDefault = ( field: FieldProps ): any => {
	if ( typeof field.default !== 'undefined' ) {
		return field.default;
	}

	return ( field as FieldProps & Pick< OldFieldBase, 'std' > ).std;
};

/**
 * Collect declared field defaults so dependency checks match the values that
 * controls display before a new CTA has persisted any settings.
 *
 * @param fields Fields grouped by editor tab.
 */
export const getFieldDefaults = (
	fields: Record< string, Record< string, FieldProps > >
): FieldDefaults =>
	Object.values( fields ).reduce< FieldDefaults >(
		( defaults, tabFields ) => {
			Object.entries( tabFields ).forEach( ( [ fieldId, field ] ) => {
				const defaultValue = getFieldDefault( field );

				if ( typeof defaultValue !== 'undefined' ) {
					defaults[ fieldId ] = defaultValue;
				}
			} );

			return defaults;
		},
		{}
	);

/**
 * Determine whether a field's dependencies are unmet.
 *
 * @param field         Field definition to evaluate.
 * @param settings      Current CTA settings.
 * @param fieldDefaults Declared defaults keyed by field ID.
 */
export const shouldHideField = (
	field: FieldProps,
	settings: CallToAction[ 'settings' ],
	fieldDefaults: FieldDefaults = {}
): boolean => {
	if ( ! field.dependencies ) {
		return false;
	}

	return ! Object.entries( field.dependencies ).every( ( [ key, value ] ) => {
		let dependencyValue = settings[ key ];

		if (
			typeof dependencyValue === 'undefined' &&
			Object.prototype.hasOwnProperty.call( fieldDefaults, key )
		) {
			dependencyValue = fieldDefaults[ key ];
		}

		if ( typeof dependencyValue === 'undefined' ) {
			if ( typeof value === 'string' ) {
				return value === '';
			}
			if ( typeof value === 'boolean' ) {
				return value === false;
			}
			if ( typeof value === 'number' ) {
				return value === 0;
			}
		}

		return value === dependencyValue;
	} );
};
