/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { withSpokenMessages } from '@wordpress/components';

/**
 * Internal dependencies
 */
import LogoIcon from '../../icons/logo';

const title = __( 'Popup Call to Action', 'popup-maker' );
const description = __(
	'Insert a call to action to let users convert to a specific action.',
	'popup-maker'
);

export const name = `popup-maker/call-to-action`;
export const settings = {
	title,
	description,
	category: 'common',
	icon: LogoIcon,
	keywords: [
		__( 'cta', 'popup-maker' ),
		__( 'button', 'popup-maker' ),
		__( 'call to action', 'popup-maker' ),
	],
	styles: [
		{
			name: 'button-link',
			label: __( 'Button', 'popup-maker' ),
			isDefault: true,
		},
		{
			name: 'text-link',
			label: __( 'Text Link', 'popup-maker' ),
		},
	],
	attributes: {
		cta_type: {
			type: 'string',
		},
	},
};
