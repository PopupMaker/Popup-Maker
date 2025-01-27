import { __ } from '@popup-maker/i18n';

import type { CallToAction } from './types';
import type { Updatable } from '@wordpress/core-data';

/**
 * Checks of the set values are valid.
 *
 * @param {CallToAction} callToAction CallToAction to validate.
 *
 * @return {boolean} True when set values are valid.
 */
export const validateCallToAction = (
	callToAction: Partial< Updatable< CallToAction< 'edit' > > >
):
	| true
	| {
			message: string;
			tabName?: string;
			field?: string;
			[ key: string ]: any;
	  } => {
	if ( ! callToAction ) {
		return {
			message: __( 'Call to action not found', 'popup-maker' ),
		};
	}

	if ( callToAction.title && ! callToAction.title?.length ) {
		return {
			message: __(
				'Please provide a name for this call to action.',
				'popup-maker'
			),
			tabName: 'general',
			field: 'title',
		};
	}

	return true;
};
