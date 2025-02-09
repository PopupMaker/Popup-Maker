import { __ } from '@popup-maker/i18n';
import { Notice, TextareaControl, TextControl } from '@wordpress/components';
import { cleanForSlug } from '@wordpress/url';

import { clamp } from '@popup-maker/utils';

import useFields from '../../hooks/use-fields';

import type { BaseEditorTabProps } from '../types';

export const name = 'general';

export const title = __( 'General', 'popup-maker' );

export const Component = ( {
	callToAction,
	updateFields,
}: BaseEditorTabProps ) => {
	const { getTabFields } = useFields();

	const descriptionRowEst = ( callToAction.excerpt ?? '' ).length / 80;
	const descriptionRows = clamp( descriptionRowEst, 1, 5 );

	return (
		<div className="general-tab">
			<TextControl
				label={ __( 'Call to Action label', 'popup-maker' ) }
				hideLabelFromVision={ true }
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
				label={ __( 'Call to Action description', 'popup-maker' ) }
				hideLabelFromVision={ true }
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

			{ getTabFields( 'general' ).map( ( field ) => (
				<div key={ field.id }>{ field.component }</div>
			) ) }
		</div>
	);
};

export default Component;
