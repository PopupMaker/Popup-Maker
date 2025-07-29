import { __ } from '@popup-maker/i18n';
import { addFilter } from '@wordpress/hooks';
import {
	// FieldPanel,
	//  FieldRow,
	URLControl,
} from '@popup-maker/components';

import { useFieldError } from '../../../hooks';
import type { CallToAction } from '@popup-maker/core-data';

// UNUSED, here for reference.

const LinkFields = ( {
	settings,
	updateSettings,
}: {
	settings: CallToAction[ 'settings' ];
	updateSettings: ( settings: Partial< CallToAction[ 'settings' ] > ) => void;
} ) => {
	const urlError = useFieldError( 'url' );

	if ( settings.type !== 'link' ) {
		return null;
	}

	return (
		// <FieldPanel title={ __( 'Link Settings', 'popup-maker' ) }>
		<URLControl
			label={ __( 'Target URL', 'popup-maker' ) }
			value={ settings.url }
			onChange={ ( value ) =>
				updateSettings( {
					url: value.url,
				} )
			}
			error={ urlError }
		/>
		// </FieldPanel>
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
