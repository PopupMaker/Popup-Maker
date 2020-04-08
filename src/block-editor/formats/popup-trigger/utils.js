/**
 * Internal dependencies
 */
import { name } from './index';

/**
 * Generates the format object that will be applied to the trigger text.
 *
 * @param {Object}  options
 * @param {number}  options.popupId       The popup ID.
 * @param {boolean} options.doDefault     Whether this trigger will act normally when clicked.
 *
 * @return {Object} The final format object.
 */
export function createTriggerFormat( { popupId = 0, doDefault = false } ) {
	const doDefaultClass = doDefault ? 'pum-do-default' : '';

	return {
		type: name,
		attributes: {
			class: `popmake-${ popupId } ${ doDefaultClass }`,
			popupId: `${ popupId }`,
			doDefault: doDefault ? '1' : '0',
		},
	};
}
