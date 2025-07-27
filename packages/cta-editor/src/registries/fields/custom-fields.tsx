import { Field } from '@popup-maker/fields';
import { addFilter } from '@wordpress/hooks';
import { FieldPanel, URLControl } from '@popup-maker/components';

import type { FieldProps } from '@popup-maker/fields';
import type { CallToAction } from '@popup-maker/core-data';

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

			return Object.entries( extraFields ).reduce(
				( acc, [ tab, tabFields ] ) => {
					if ( ! acc[ tab ] ) {
						acc[ tab ] = [];
					}

					acc[ tab ] = [
						...acc[ tab ],
						...Object.entries( tabFields ).map(
							( [ fieldId, field ] ) => {
								if ( ! field || ! field.type ) {
									return null;
								}

								const shouldHide = () => {
									if ( ! field.dependencies ) {
										return false;
									}

									const dependencies = field.dependencies;

									return ! Object.entries(
										dependencies
									).every( ( [ key, value ] ) => {
										const dependencyValue = settings[ key ];

										if ( typeof value === 'string' ) {
											if (
												typeof dependencyValue ===
												'undefined'
											) {
												return value === '';
											}
											return value === dependencyValue;
										}

										if ( typeof value === 'boolean' ) {
											if (
												typeof dependencyValue ===
												'undefined'
											) {
												return value === false;
											}
											return value === dependencyValue;
										}

										return false;
									} );
								};

								return {
									...field,
									id: fieldId,
									priority: field?.priority ?? 0,
									component: (
										<div key={ fieldId }>
											{ ! shouldHide() &&
												( 'url' === field.type ? (
													<FieldPanel
														title={
															field.label ?? ''
														}
													>
														<URLControl
															key={ fieldId }
															{ ...field }
															value={
																settings[
																	fieldId
																]
															}
															onChange={ (
																value
															) =>
																updateSettings(
																	{
																		[ fieldId ]:
																			value.url,
																	}
																)
															}
														/>
													</FieldPanel>
												) : (
													<FieldPanel
														title={
															field.label ?? ''
														}
													>
														<Field
															key={ fieldId }
															{ ...field }
															value={
																settings[
																	fieldId
																]
															}
															onChange={ (
																value
															) =>
																updateSettings(
																	{
																		[ fieldId ]:
																			value,
																	}
																)
															}
														/>
													</FieldPanel>
												) ) }
										</div>
									),
								};
							}
						),
					]
						.filter( ( item ) => item !== null )
						.filter( Boolean );

					return acc;
				},
				{ ...fields }
			);
		}
	);
};

export default initCustomFields;
