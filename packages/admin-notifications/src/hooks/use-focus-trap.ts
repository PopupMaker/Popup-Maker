import { useEffect, useRef } from '@wordpress/element';

const FOCUSABLE_SELECTOR = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled]):not([type="hidden"])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ',' );

/**
 * Focus-trap the given container while `isOpen` is true.
 *
 * On open: records the previously focused element and moves focus into
 * the container (first focusable child when present, otherwise the
 * container itself). While open, Tab and Shift+Tab wrap inside the
 * container. On close, focus is restored to the previously focused
 * element if it's still in the document.
 *
 * @param {boolean}                         isOpen Whether the container is currently visible.
 * @param {React.RefObject<HTMLElement>}   ref    Ref to the container element.
 */
export const useFocusTrap = (
	isOpen: boolean,
	ref: React.RefObject< HTMLElement >
): void => {
	const previouslyFocused = useRef< HTMLElement | null >( null );

	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}

		previouslyFocused.current =
			document.activeElement instanceof HTMLElement
				? document.activeElement
				: null;

		const container = ref.current;
		// Track only the tabindex we synthesized, so cleanup doesn't strip
		// a caller-provided one.
		let addedTabIndex = false;
		if ( container ) {
			const focusables = container.querySelectorAll< HTMLElement >(
				FOCUSABLE_SELECTOR
			);
			const first = focusables[ 0 ];
			if ( first ) {
				first.focus();
			} else {
				if ( ! container.hasAttribute( 'tabindex' ) ) {
					container.setAttribute( 'tabindex', '-1' );
					addedTabIndex = true;
				}
				container.focus();
			}
		}

		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key !== 'Tab' || ! container ) {
				return;
			}
			const focusables = Array.from(
				container.querySelectorAll< HTMLElement >( FOCUSABLE_SELECTOR )
			).filter( ( el ) => ! el.hasAttribute( 'data-pum-trap-skip' ) );
			if ( focusables.length === 0 ) {
				e.preventDefault();
				return;
			}
			const first = focusables[ 0 ];
			const last = focusables[ focusables.length - 1 ];
			const active = document.activeElement as HTMLElement | null;

			if ( e.shiftKey && active === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && active === last ) {
				e.preventDefault();
				first.focus();
			}
		};

		document.addEventListener( 'keydown', onKey );

		return () => {
			document.removeEventListener( 'keydown', onKey );
			if ( container && addedTabIndex ) {
				container.removeAttribute( 'tabindex' );
			}
			const prev = previouslyFocused.current;
			if ( prev && document.contains( prev ) ) {
				prev.focus();
			}
			previouslyFocused.current = null;
		};
	}, [ isOpen, ref ] );
};
