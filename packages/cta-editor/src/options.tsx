import { __ } from '@wordpress/i18n';

import type { CallToAction } from '@popup-maker/core-data';

export const callToActionTypeOptions: {
	value: CallToAction[ 'settings' ][ 'type' ];
	label: string;
	[ key: string ]: any;
}[] = [
	{
		value: 'link',
		label: __( 'Link', 'popup-maker' ),
	},
];
