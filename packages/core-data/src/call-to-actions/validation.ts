import { __ } from '@wordpress/i18n';
import type { CallToAction } from './types';

/**
 * Checks of the set values are valid.
 *
 * @param {CallToAction} callToAction CallToAction to validate.
 *
 * @return {boolean} True when set values are valid.
 */
export const validateCallToAction = (
	callToAction: CallToAction< 'edit' >
):
	| boolean
	| {
			message: string;
			tabName?: string;
			field?: string;
			[ key: string ]: any;
	  } => {
	if ( ! callToAction ) {
		return false;
	}

	const title = callToAction.title.raw;

	if ( ! title?.length ) {
		return {
			message: __(
				'Please provide a name for this call to action.',
				'popup-paker'
			),
			tabName: 'general',
			field: 'title',
		};
	}

	return true;
};
