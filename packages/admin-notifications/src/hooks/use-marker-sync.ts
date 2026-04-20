import { useEffect } from '@wordpress/element';
import { _n, sprintf } from '@popup-maker/i18n';

/**
 * Keep the server-rendered `[!]` markers + admin-bar dropdown count in
 * sync with the live panel item count. When everything is dismissed
 * in-panel, the markers disappear immediately instead of waiting for
 * the next page load. Also refreshes screen-reader labels so the
 * announced count stays accurate.
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

		const chips = document.querySelectorAll< HTMLElement >(
			'.pum-notifications-marker'
		);
		const subNode = document.getElementById(
			'wp-admin-bar-pum-notifications'
		);

		if ( itemCount === 0 ) {
			chips.forEach( ( el ) => {
				el.style.display = 'none';
			} );
			if ( subNode ) {
				subNode.style.display = 'none';
			}
			return;
		}

		const ariaLabel = sprintf(
			/* translators: %d: notification count. */
			_n(
				'%d Popup Maker notification',
				'%d Popup Maker notifications',
				itemCount,
				'popup-maker'
			),
			itemCount
		);

		chips.forEach( ( el ) => {
			el.style.display = '';
			el.dataset.count = String( itemCount );
			el.setAttribute( 'aria-label', ariaLabel );
		} );

		if ( subNode ) {
			subNode.style.display = '';
			const countEl = subNode.querySelector(
				'.pum-notifications-count'
			);
			if ( countEl ) {
				countEl.textContent = String( itemCount );
			}
		}
	}, [ hasLoaded, itemCount ] );
};
