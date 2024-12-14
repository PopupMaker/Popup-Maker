import { name } from './index';

import type { TriggerFormat } from './types';

type TriggerFormatOptions = {
	popupId: number;
	doDefault: boolean;
};

/**
 * Generates the format object that will be applied to the trigger text.
 *
 * @param {TriggerFormatOptions} options The options.
 *
 * @return {TriggerFormat} The final format object.
 */
export const createTriggerFormat = ( {
	popupId = 0,
	doDefault = false,
}: TriggerFormatOptions ): TriggerFormat => ( {
	type: name,
	attributes: {
		class: `popmake-${ popupId } ${ doDefault ? 'pum-do-default' : '' }`,
		popupId: `${ popupId }`,
		doDefault: doDefault ? '1' : '0',
	},
} );
