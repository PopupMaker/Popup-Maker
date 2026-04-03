import { __ } from '@popup-maker/i18n';
import { clamp } from '@popup-maker/utils';
import { cleanForSlug } from '@wordpress/url';
import { Fragment, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import {
	Notice,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';

import { useFields, useAllFieldErrors } from '../../hooks';
import { TabErrorNotice } from '../../components';

import type { CallToAction } from '@popup-maker/core-data';
import type { BaseEditorTabProps } from '../../types';

export const name = 'general';

export const title = __( 'General', 'popup-maker' );

export const getCallToActionTypeOptions = () => {
	// Get all registered CTA types from the global data
	const { cta_types: registeredCtaTypes = {} } =
		window.popupMakerCtaEditor || {};

	// Convert registered CTA types to dropdown options.
	const registeredOptions = Object.values( registeredCtaTypes ).map(
		( ctaType: any ) => ( {
			value: ctaType.key,
			label: ctaType.label,
		} )
	);

	const callToActionTypeOptions: {
		value: Exclude< CallToAction[ 'settings' ][ 'type' ], undefined > | '';
		label: string;
		disabled?: boolean;
		[ key: string ]: any;
	}[] = applyFilters( 'popupMaker.callToActionEditor.typeOptions', [
		{
			value: '',
			label: __( 'Select a type', 'popup-maker' ),
		},
		// Include all registered CTA types (core + pro + locked previews).
		...registeredOptions,
	] ) as {
		value: Exclude< CallToAction[ 'settings' ][ 'type' ], undefined > | '';
		label: string;
		disabled?: boolean;
		[ key: string ]: any;
	}[];

	return callToActionTypeOptions;
};

export const Component = ( {
	callToAction,
	updateFields,
	updateSettings,
}: BaseEditorTabProps ) => {
	const { getTabFields } = useFields();
	const { clearAllErrors } = useAllFieldErrors();

	const { settings } = callToAction;
	const [ lockedType, setLockedType ] = useState< string | null >( null );

	const descriptionRowEst = ( callToAction.excerpt ?? '' ).length / 80;
	const descriptionRows = clamp( descriptionRowEst, 1, 5 );

	const { cta_types: registeredCtaTypes = {} } =
		window.popupMakerCtaEditor || {};
	const callToActionTypeOptions = getCallToActionTypeOptions();

	return (
		<div className="general-tab">
			<TabErrorNotice tabName="general" />

			<TextControl
				label={ __( 'Name', 'popup-maker' ) }
				// hideLabelFromVision={ true }
				placeholder={ __( 'Name…', 'popup-maker' ) }
				className="title-field"
				value={ callToAction.title ?? '' }
				onChange={ ( newTitle ) =>
					updateFields( {
						title: newTitle,
						slug: cleanForSlug( newTitle ),
					} )
				}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			<TextareaControl
				rows={ descriptionRows }
				// @ts-ignore
				scrolling={ descriptionRows > 5 ? 'auto' : 'no' }
				label={ __( 'Description', 'popup-maker' ) }
				// hideLabelFromVision={ true }
				placeholder={ __( 'Add description…', 'popup-maker' ) }
				className="description-field"
				value={ callToAction.excerpt ?? '' }
				onChange={ ( excerpt ) => updateFields( { excerpt } ) }
				__nextHasNoMarginBottom
			/>

			{ ( callToAction.title ?? '' ).length <= 0 && (
				<Notice
					status="warning"
					isDismissible={ false }
					className="title-field-notice"
				>
					{ __( 'Enter a label for this set.', 'popup-maker' ) }
				</Notice>
			) }

			{ /* Filtered fields with priority < 3 render before action type. */ }
			{ getTabFields( 'general' )
				.filter( ( field ) => field.priority < 3 )
				.map( ( field ) => (
					<Fragment key={ field.id }>{ field.component }</Fragment>
				) ) }

			{ callToActionTypeOptions.length > 1 && (
				<SelectControl
					label={ __( 'Action Type', 'popup-maker' ) }
					options={ callToActionTypeOptions }
					value={ settings.type ?? '' }
					onChange={ ( type ) => {
						const ctaType =
							registeredCtaTypes[ type as string ];

						// Intercept locked types — revert to link, show upsell.
						if ( ctaType?.pro_required ) {
							setLockedType( type );
							clearAllErrors();
							updateSettings( { type: 'link' } );
							return;
						}

						setLockedType( null );
						clearAllErrors();
						updateSettings( { type } );
					} }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ /* Upsell notice when a locked CTA type was attempted. */ }
			{ lockedType && registeredCtaTypes?.[ lockedType ] && (
				<Notice
					status="warning"
					isDismissible={ true }
					onDismiss={ () => setLockedType( null ) }
					className="pro-cta-type-notice"
				>
					<strong>
						{ registeredCtaTypes[ lockedType ]?.label }
					</strong>
					{ ' \u2014 ' }
					{ registeredCtaTypes[ lockedType ]?.pro_description ||
						'This action type requires Popup Maker Pro.' }{ ' ' }
					<a
						href={
							registeredCtaTypes[ lockedType ]?.upgrade_url ||
							'#'
						}
						target="_blank"
						rel="noopener noreferrer"
					>
						{ registeredCtaTypes[ lockedType ]?.pro_cta ||
							'Upgrade Now' }
						{ ' \u2192' }
					</a>
				</Notice>
			) }

			{ getTabFields( 'general' )
				.filter( ( field ) => field.priority >= 3 )
				.map( ( field ) => (
					<Fragment key={ field.id }>{ field.component }</Fragment>
				) ) }
		</div>
	);
};

export default Component;
