import * as i18n from '@wordpress/i18n';

/**
 * Base namespace for Popup Maker i18n types
 */
export namespace PopupMaker {
	/**
	 * Base interface for text domains.
	 */
	export interface TextDomains {
		'popup-maker': never;
	}

	// Add this type alias for better type inference
	export type TextDomain = keyof TextDomains;
}

// Export the type separately to make it easier to extend
export type TextDomain = PopupMaker.TextDomain;

export const sprintf = (
	format: string,
	...args: Array< string | number >
) => {
	return i18n.sprintf( format, ...args );
};

export const __ = ( text: string, domain: TextDomain ) => {
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	return i18n.__( text, domain );
};

export const _x = ( text: string, context: string, domain: TextDomain ) => {
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	return i18n._x( text, context, domain );
};

export const _n = (
	single: string,
	plural: string,
	number: number,
	domain: TextDomain
) => {
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	return i18n._n( single, plural, number, domain );
};

export const _nx = (
	text: string,
	context: string,
	number: number,
	domain: TextDomain
) => {
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	return i18n._nx( text, context, number, domain );
};

export const isRTL = () => i18n.isRTL();
