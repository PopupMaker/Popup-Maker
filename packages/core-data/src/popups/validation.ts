import { __ } from '@wordpress/i18n';
import type { Popup } from './types';

/**
 * Checks of the set values are valid.
 *
 * @param {Popup} popup Popup to validate.
 *
 * @return {boolean} True when set values are valid.
 */
export const validatePopup = (
	popup: Popup
):
	| boolean
	| {
			message: string;
			tabName?: string;
			field?: string;
			[ key: string ]: any;
	  } => {
	if ( ! popup ) {
		return false;
	}

	const title =
		typeof popup.title === 'string' ? popup.title : popup.title?.raw;

	if ( ! title.length ) {
		return {
			message: __(
				'Please provide a name for this popup.',
				'popup-paker'
			),
			tabName: 'general',
			field: 'title',
		};
	}

	if (
		! popup.settings?.conditions?.items?.length &&
		popup.status === 'publish'
	) {
		return {
			message: __(
				'Please provide at least one condition for this popup before enabling it.',
				'popup-paker'
			),
			tabName: 'content',
		};
	}

	return true;
};
