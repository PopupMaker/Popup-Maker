import { useCallback, useEffect, useState } from '@wordpress/element';

import {
	OPEN_QUERY_PARAM,
	addOpenQueryParam,
	clearOpenQueryParam,
} from '../utils/open-state';

interface Options {
	initiallyOpen?: boolean;
}

interface PanelOpenState {
	isOpen: boolean;
	setIsOpen: ( next: boolean ) => void;
}

/**
 * Manage panel open/close state along with all the side effects
 * that keep it in sync with the browser:
 *
 *   - URL query param is written on open, stripped on close
 *   - back/forward button toggles panel via popstate
 *   - ESC closes when open
 *   - clicks on any admin-bar notifications trigger open in-place
 *
 * @param {Options} options               Hook options.
 * @param {boolean} options.initiallyOpen Whether the panel should start open.
 */
export const usePanelOpenState = ( {
	initiallyOpen = false,
}: Options = {} ): PanelOpenState => {
	const [ isOpen, setIsOpenState ] = useState< boolean >( initiallyOpen );

	const setIsOpen = useCallback( ( next: boolean ) => {
		setIsOpenState( next );
		if ( next ) {
			addOpenQueryParam();
		} else {
			clearOpenQueryParam();
		}
	}, [] );

	// Close on escape when open.
	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				setIsOpen( false );
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ isOpen, setIsOpen ] );

	// Intercept marker clicks so we open in-place instead of navigating.
	// On pages where the panel isn't mounted, the bootstrap script sets
	// the localStorage flag and lets WP 7.0 soft-nav handle the jump.
	useEffect( () => {
		const onClick = ( e: MouseEvent ) => {
			// Preserve native "open in new tab / window" behavior for
			// modifier + middle-button clicks — otherwise users who
			// expect to Cmd/Ctrl-click the marker lose that capability.
			if (
				e.defaultPrevented ||
				e.button !== 0 ||
				e.metaKey ||
				e.ctrlKey ||
				e.shiftKey ||
				e.altKey
			) {
				return;
			}

			const target = e.target as HTMLElement | null;
			if ( ! target ) {
				return;
			}

			const inTrigger =
				target.closest< HTMLElement >(
					'[data-pum-notifications-trigger]'
				) !== null;

			const inDropdownNode =
				target.closest( '#wp-admin-bar-pum-notifications' ) !== null;

			if ( inTrigger || inDropdownNode ) {
				e.preventDefault();
				setIsOpen( true );
			}
		};
		document.addEventListener( 'click', onClick );
		return () => document.removeEventListener( 'click', onClick );
	}, [ setIsOpen ] );

	/*
	 * Back/forward navigation — mirror the query-param state onto ours
	 * without re-pushing history. Also reconciles once on mount so a
	 * client-side route change that swapped the URL (WP 7.0 soft-nav)
	 * won't leave us desynced if the caller passed a stale
	 * `initiallyOpen`. `setIsOpenState` is React's stable setter so the
	 * effect effectively runs once.
	 */
	useEffect( () => {
		const syncFromUrl = () => {
			try {
				const url = new URL( window.location.href );
				const shouldOpen =
					url.searchParams.get( OPEN_QUERY_PARAM ) === 'open';
				setIsOpenState( shouldOpen );
			} catch {
				// Noop.
			}
		};
		syncFromUrl();
		window.addEventListener( 'popstate', syncFromUrl );
		return () =>
			window.removeEventListener( 'popstate', syncFromUrl );
	}, [] );

	return { isOpen, setIsOpen };
};
