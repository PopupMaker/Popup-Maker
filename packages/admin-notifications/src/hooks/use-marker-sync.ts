import { useEffect } from '@wordpress/element';

/**
 * Keep the server-rendered `[!]` markers + admin-bar dropdown count in
 * sync with the live panel item count. When everything is dismissed
 * in-panel, the markers disappear immediately instead of waiting for
 * the next page load.
 *
 * @param {boolean} hasLoaded Whether the store has completed its first fetch.
 * @param {number}  itemCount Current visible notification count.
 */
export const useMarkerSync = (
	hasLoaded: boolean,
	itemCount: number
): void => {
	useEffect( () => {
		if ( ! hasLoaded ) {
			return;
		}

		const markers = document.querySelectorAll< HTMLElement >(
			'.pum-notifications-marker, #wp-admin-bar-pum-notifications'
		);

		if ( itemCount === 0 ) {
			markers.forEach( ( el ) => {
				el.style.display = 'none';
			} );
			return;
		}

		markers.forEach( ( el ) => {
			el.style.display = '';
		} );

		const countEl = document.querySelector(
			'#wp-admin-bar-pum-notifications .pum-notifications-count'
		);
		if ( countEl ) {
			countEl.textContent = String( itemCount );
		}
	}, [ hasLoaded, itemCount ] );
};
