import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { useInstanceId } from '@wordpress/compose';
import { SelectControl } from '@wordpress/components';

import { FieldPanel, FieldRow } from '@popup-maker/components';

import { callToActionTypeOptions } from '../../../../options';

import type { CallToAction } from '@popup-maker/core-data';

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
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</FieldRow>
		</FieldPanel>
	);
};

export const initTypeField = () => {
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
};

export default initTypeField;
