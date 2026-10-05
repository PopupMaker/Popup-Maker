/**
 * Popup Editor Labels.
 *
 * The block editor's document bar and post-card panel fall back to core's
 * "No title" string, which refers to the native post_title field. Popup
 * Maker already renames that field to "Popup Name" everywhere else and
 * reserves "Popup Title" for the separate popup_title meta setting, so an
 * unrenamed "No title" reads as if it means that other setting. There's no
 * PHP-level label for this fallback string, so it's overridden here via the
 * i18n.gettext filter, scoped to the popup post type only.
 */

import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

interface EditorStore {
	getCurrentPostType: () => string;
}

addFilter(
	'i18n.gettext',
	'popup-maker/rename-no-title-to-no-name',
	( translation: string, text: string, domain?: string ) => {
		if ( text !== 'No title' || domain ) {
			return translation;
		}

		const editor = select( 'core/editor' ) as unknown as EditorStore;

		if ( editor.getCurrentPostType() !== 'popup' ) {
			return translation;
		}

		return __( 'No name', 'popup-maker' );
	}
);
