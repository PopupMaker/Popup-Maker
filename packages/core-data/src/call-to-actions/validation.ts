import { __ } from '@wordpress/i18n';
import type { CallToAction } from './types';
import { isApiFormat } from './types';

/**
 * Checks of the set values are valid.
 *
 * @param {CallToAction} callToAction CallToAction to validate.
 *
 * @return {boolean} True when set values are valid.
 */
export const validateCallToAction = (
	callToAction: CallToAction
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

	const title = isApiFormat( callToAction.title )
		? callToAction.title.raw
		: callToAction.title;

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

	if (
		! callToAction.settings?.conditions?.items?.length &&
		callToAction.status === 'publish'
	) {
		return {
			message: __(
				'Please provide at least one condition for this call to action before enabling it.',
				'popup-paker'
			),
			tabName: 'content',
		};
	}

	return true;
};
