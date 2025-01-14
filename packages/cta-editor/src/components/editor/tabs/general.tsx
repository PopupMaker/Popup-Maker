import {
	Notice,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { useInstanceId } from '@wordpress/compose';

import { clamp } from '@popup-maker/utils';
import { FieldPanel, FieldRow } from '@popup-maker/components';

import useFields from '../../hooks/use-fields';
import { callToActionTypeOptions } from '../../../options';

import type { CallToAction } from '@popup-maker/core-data';
import type { BaseEditorTabProps } from '../types';

const TypeField = ( {
	settings,
	updateSettings,
}: {
	settings: CallToAction[ 'settings' ];
	updateSettings: ( settings: Partial< CallToAction[ 'settings' ] > ) => void;
} ) => {
	const instanceId = useInstanceId( TypeField );

	return (
		<FieldPanel title={ __( 'Type', 'popup-maker' ) }>
			<FieldRow label={ __( 'Call to Action type', 'popup-maker' ) }>
				<SelectControl
					id={ `popup-maker-call-to-action-type-${ instanceId }` }
					label={ __( 'Type', 'popup-maker' ) }
					options={ callToActionTypeOptions }
					value={ settings.type ?? '' }
					onChange={ ( type ) => updateSettings( { type } ) }
				/>
			</FieldRow>
		</FieldPanel>
	);
};

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
		const componentProps = {
			settings,
			updateSettings,
		};

		return {
			...fields,
			general: [
				{
					id: 'type',
					priority: 3,
					component: <TypeField { ...componentProps } />,
				},
				...( fields?.general ?? [] ),
			],
		};
	}
);

export const name = 'general';

export const title = __( 'General', 'popup-maker' );

export const Component = ( {
	callToAction,
	updateFields,
}: BaseEditorTabProps ) => {
	const { getTabFields } = useFields();

	const descriptionRowEst = ( callToAction.description ?? '' ).length / 80;
	const descriptionRows = clamp( descriptionRowEst, 1, 5 );

	return (
		<div className="general-tab">
			<TextControl
				label={ __( 'Call to Action label', 'popup-maker' ) }
				hideLabelFromVision={ true }
				placeholder={ __( 'Name…', 'popup-maker' ) }
				className="title-field"
				value={ callToAction.title ?? '' }
				onChange={ ( title ) => updateFields( { title } ) }
			/>

			<TextareaControl
				rows={ descriptionRows }
				// @ts-ignore
				scrolling={ descriptionRows > 5 ? 'auto' : 'no' }
				label={ __( 'Call to Action description', 'popup-maker' ) }
				hideLabelFromVision={ true }
				placeholder={ __( 'Add description…', 'popup-maker' ) }
				className="description-field"
				value={ callToAction.description ?? '' }
				onChange={ ( description ) => updateFields( { description } ) }
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
