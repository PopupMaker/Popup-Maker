import { __ } from '@popup-maker/i18n';

import type { Popup } from './types';
import type { Updatable } from '@wordpress/core-data';

/**
 * Checks of the set values are valid.
 *
 * @param {Popup} popup Popup to validate.
 *
 * @return {boolean} True when set values are valid.
 */
export const validatePopup = (
	popup: Partial< Updatable< Popup< 'edit' > > >
):
	| true
	| {
			message: string;
			tabName?: string;
			field?: string;
			[ key: string ]: any;
	  } => {
	if ( ! popup ) {
		return {
			message: __( 'Popup not found', 'popup-maker' ),
		};
	}

	if ( popup.title && ! popup.title?.length ) {
		return {
			message: __(
				'Please provide a name for this popup.',
				'popup-maker'
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
				'popup-maker'
			),
			tabName: 'content',
		};
	}

	return true;
};
