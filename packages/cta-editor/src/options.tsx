import { __ } from '@popup-maker/i18n';

import type { CallToAction } from '@popup-maker/core-data';

export const callToActionTypeOptions: {
	value: Exclude< CallToAction[ 'settings' ][ 'type' ], undefined > | '';
	label: string;
	[ key: string ]: any;
}[] = [
	{
		value: '',
		label: __( 'Select a type', 'popup-maker' ),
	},
	{
		value: 'link',
		label: __( 'Link', 'popup-maker' ),
	},
];
