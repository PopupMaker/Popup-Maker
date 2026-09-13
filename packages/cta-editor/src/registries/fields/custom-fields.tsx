import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';

import { FieldWithError } from '../../components';

import type { FieldProps } from '@popup-maker/fields';
import type { CallToAction } from '@popup-maker/core-data';
import { getFieldDefaults, shouldHideField } from './field-visibility';

const { cta_types: callToActions } = window.popupMakerCtaEditor;

const getCtaFields = (
	key: string
): {
	[ tabName: string ]: {
		[ fieldId: string ]: FieldProps;
	};
} => {
	const fields =
		Object.values( callToActions ).find( ( cta ) => cta.key === key )
			?.fields ?? {};

	return fields;
};

export const initCustomFields = () => {
	// Initialize custom fields by adding them to the tab fields filter
	addFilter(
		'popupMaker.callToActionEditor.tabFields',
		'popup-maker',
		(
			fields: Record<
				string,
				{ id: string; priority: number; component: React.JSX.Element }[]
			>,
			settings: CallToAction[ 'settings' ],
			updateSettings: (
				settings: Partial< CallToAction[ 'settings' ] >
			) => void
		) => {
			const extraFields = getCtaFields( settings.type );

			if ( Object.keys( extraFields ).length === 0 ) {
				return fields;
			}

			const fieldDefaults = getFieldDefaults( extraFields );

			return Object.entries( extraFields ).reduce(
				( acc, [ tab, tabFields ] ) => {
					if ( ! acc[ tab ] ) {
						acc[ tab ] = [];
					}

					const entries = Object.entries( tabFields ).filter(
						( entry ): entry is [ string, FieldProps ] =>
							Boolean( entry[ 1 ]?.type )
					);
					const customFields = entries.map(
						( [ fieldId, field ] ) => {
							return {
								...field,
								id: fieldId,
								priority: field.priority ?? 0,
								component: (
									<Fragment key={ fieldId }>
										{ ! shouldHideField(
											field,
											settings,
											fieldDefaults
										) && (
											<FieldWithError
												fieldId={ fieldId }
												field={ field }
												value={ settings[ fieldId ] }
												onChange={ ( value ) =>
													updateSettings( {
														[ fieldId ]: value,
													} )
												}
											/>
										) }
									</Fragment>
								),
							};
						}
					);

					acc[ tab ] = [ ...acc[ tab ], ...customFields ];

					return acc;
				},
				{ ...fields }
			);
		}
	);
};

export default initCustomFields;
