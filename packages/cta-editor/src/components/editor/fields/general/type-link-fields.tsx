import { __ } from '@popup-maker/i18n';
import { addFilter } from '@wordpress/hooks';

import { FieldPanel, FieldRow, URLControl } from '@popup-maker/components';

import type { CallToAction } from '@popup-maker/core-data';

const LinkFields = ( {
	settings,
	updateSettings,
}: {
	settings: CallToAction[ 'settings' ];
	updateSettings: ( settings: Partial< CallToAction[ 'settings' ] > ) => void;
} ) => {
	return (
		<FieldPanel title={ __( 'URL', 'popup-maker' ) }>
			<FieldRow label={ __( 'Target URL', 'popup-maker' ) }>
				<URLControl
					value={ settings.url }
					onChange={ ( value ) =>
						updateSettings( {
							url: value.url,
						} )
					}
				/>
			</FieldRow>
		</FieldPanel>
	);
};

export const initTypeLinkFields = () => {
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
						id: 'linkFields',
						priority: 4,
						component: <LinkFields { ...componentProps } />,
					},
					...( fields?.general ?? [] ),
				],
			};
		}
	);
};

export default initTypeLinkFields;
