import { __ } from '@popup-maker/i18n';
import { clamp } from '@popup-maker/utils';
import { CallToAction } from '@popup-maker/core-data';
import { cleanForSlug } from '@wordpress/url';
import { applyFilters } from '@wordpress/hooks';
import {
	Notice,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';

import { useFields } from '../../hooks';

import type { BaseEditorTabProps } from '../../types';

export const name = 'general';

export const title = __( 'General', 'popup-maker' );

export const getCallToActionTypeOptions = () => {
	// Get all registered CTA types from the global data
	const { cta_types: registeredCtaTypes = {} } =
		window.popupMakerCtaEditor || {};

	// Convert registered CTA types to dropdown options
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
		// Include all registered CTA types (core + pro)
		...registeredOptions,
		// {
		// 	value: 'openPopup',
		// 	label: __( 'Open Popup (Available in Pro)', 'popup-maker' ),
		// 	disabled: true,
		// },
		// {
		// 	value: 'addToCart',
		// 	label: __( 'Add to Cart (Available in Pro+)', 'popup-maker' ),
		// 	disabled: true,
		// },
		// {
		// 	value: 'applyDiscount',
		// 	label: __( 'Apply Discount (Available in Pro+)', 'popup-maker' ),
		// 	disabled: true,
		// },
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

	const { settings } = callToAction;

	const descriptionRowEst = ( callToAction.excerpt ?? '' ).length / 80;
	const descriptionRows = clamp( descriptionRowEst, 1, 5 );

	const callToActionTypeOptions = getCallToActionTypeOptions();

	return (
		<div className="general-tab">
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

			{ callToActionTypeOptions.length > 1 && (
				<SelectControl
					label={ __( 'Action Type', 'popup-maker' ) }
					options={ callToActionTypeOptions }
					value={ settings.type ?? '' }
					onChange={ ( type ) => updateSettings( { type } ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ getTabFields( 'general' ).map( ( field ) => (
				<div key={ field.id }>{ field.component }</div>
			) ) }
		</div>
	);
};

export default Component;
