import type { FieldProps, OldFieldBase } from '@popup-maker/fields';
import type { CallToAction } from '@popup-maker/core-data';

export type FieldDefaults = Record< string, unknown >;

const isFieldDefinition = ( field: unknown ): field is FieldProps =>
	typeof field === 'object' &&
	field !== null &&
	'type' in field &&
	typeof field.type === 'string';

/**
 * Read defaults from both current field props and legacy PHP field definitions.
 *
 * @param field Field definition to inspect.
 */
export const getFieldDefault = ( field: FieldProps ): unknown => {
	if ( typeof field.default !== 'undefined' ) {
		return field.default;
	}

	return ( field as FieldProps & Pick< OldFieldBase, 'std' > ).std;
};

/** Normalize legacy checkbox defaults before the generic field sees them. */
export const normalizeFieldDefault = (
	defaultValue: unknown,
	field: FieldProps
): unknown => {
	if ( 'checkbox' !== field.type ) {
		return defaultValue;
	}

	if ( Array.isArray( defaultValue ) ) {
		return defaultValue.length === 1 && String( defaultValue[ 0 ] ) === '1';
	}

	if ( 'string' === typeof defaultValue ) {
		return (
			[ 'true', 'yes', '1' ].includes( defaultValue ) ||
			parseInt( defaultValue, 10 ) > 0
		);
	}

	if ( 'number' === typeof defaultValue ) {
		return defaultValue > 0;
	}

	return Boolean( defaultValue );
};

/**
 * Collect declared field defaults so dependency checks match the values that
 * controls display before a new CTA has persisted any settings.
 *
 * @param fields Fields grouped by editor tab.
 */
export const getFieldDefaults = (
	fields: Record< string, Record< string, unknown > >
): FieldDefaults =>
	Object.values( fields ).reduce< FieldDefaults >(
		( defaults, tabFields ) => {
			Object.entries( tabFields ).forEach( ( [ fieldId, field ] ) => {
				if ( ! isFieldDefinition( field ) ) {
					return;
				}

				const defaultValue = getFieldDefault( field );

				if ( typeof defaultValue !== 'undefined' ) {
					defaults[ fieldId ] = normalizeFieldDefault(
						defaultValue,
						field
					);
				}
			} );

			return defaults;
		},
		{}
	);

/**
 * Return defaults that have not yet been written to the editable CTA settings.
 *
 * @param settings      Current CTA settings.
 * @param fieldDefaults Declared defaults keyed by field ID.
 */
export const getMissingFieldDefaults = (
	settings: CallToAction[ 'settings' ],
	fieldDefaults: FieldDefaults
): Partial< CallToAction[ 'settings' ] > => {
	const missingDefaults: Partial< CallToAction[ 'settings' ] > = {};

	Object.entries( fieldDefaults ).forEach( ( [ fieldId, defaultValue ] ) => {
		if (
			settings[ fieldId ] === null ||
			typeof settings[ fieldId ] === 'undefined'
		) {
			( missingDefaults as Record< string, unknown > )[ fieldId ] =
				defaultValue;
		}
	} );

	return missingDefaults;
};

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
			( dependencyValue === null ||
				typeof dependencyValue === 'undefined' ) &&
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
