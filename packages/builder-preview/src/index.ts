import './styles.scss';

import $ from 'jquery';

const PUM = window.PUM;

if ( PUM && 'function' === typeof PUM.getPopup ) {
	const $popupElement = $( 'body.pum-builder-preview > .pum' ).first();
	const popupId = parseInt(
		( $popupElement.attr( 'id' ) || '' ).replace( 'pum-', '' ),
		10
	);
	const $popup = popupId ? PUM.getPopup( popupId ) : null;
	const $container =
		$popup && $popup.length ? $popup.find( '.pum-container' ) : null;

	const constrainPopupToVisibleViewport = (): void => {
		const inset = 10;
		const bounds = {
			left: inset,
			top: $( 'body' ).hasClass( 'admin-bar' ) ? 42 : inset,
			right: window.innerWidth - inset,
			bottom: window.innerHeight - inset,
		};

		if ( ! $container || ! $container.length ) {
			return;
		}

		if ( window.frameElement && window.parent !== window ) {
			try {
				const frameRect = window.frameElement.getBoundingClientRect();
				bounds.left = Math.max( bounds.left, inset - frameRect.left );
				bounds.top = Math.max( bounds.top, inset - frameRect.top );
				bounds.right = Math.min(
					bounds.right,
					window.parent.innerWidth - frameRect.left - inset
				);
				bounds.bottom = Math.min(
					bounds.bottom,
					window.parent.innerHeight - frameRect.top - inset
				);
			} catch {
				// Keep the iframe's own viewport bounds across origins.
			}
		}

		if ( bounds.right <= bounds.left || bounds.bottom <= bounds.top ) {
			return;
		}

		const containerRect = $container[ 0 ].getBoundingClientRect();
		const offsetLeft =
			containerRect.width <= bounds.right - bounds.left
				? Math.max(
						bounds.left - containerRect.left,
						Math.min( 0, bounds.right - containerRect.right )
				  )
				: Math.max( 0, bounds.left - containerRect.left );
		const offsetTop =
			containerRect.height <= bounds.bottom - bounds.top
				? Math.max(
						bounds.top - containerRect.top,
						Math.min( 0, bounds.bottom - containerRect.bottom )
				  )
				: Math.max( 0, bounds.top - containerRect.top );

		$container.css( {
			left: ( parseFloat( $container.css( 'left' ) ) || 0 ) + offsetLeft,
			top: ( parseFloat( $container.css( 'top' ) ) || 0 ) + offsetTop,
		} );
	};

	const repositionPopup = (): void => {
		if (
			! $popup ||
			! $popup.length ||
			! $popup.closest( 'body' ).length
		) {
			return;
		}

		$popup.popmake( 'reposition' );
		$popup.find( '.pum-close' ).attr( {
			'aria-disabled': 'true',
			tabindex: '-1',
		} );

		$( document ).trigger( 'pumBuilderPreviewReposition', [
			$popup,
			popupId,
		] );
		constrainPopupToVisibleViewport();
	};

	if ( $popup && $popup.length ) {
		$popup.on( 'pumBeforeClose.pumBuilderPreview', () => {
			$popup.addClass( 'preventClose' );
		} );
		$popup.on( 'pumAfterOpen.pumBuilderPreview', repositionPopup );

		if ( 'ResizeObserver' in window && $container?.length ) {
			new window.ResizeObserver( repositionPopup ).observe(
				$container[ 0 ]
			);
		}
	}

	if ( PUM.initialized ) {
		repositionPopup();
	} else {
		$( document ).one(
			'pumInitialized.pumBuilderPreview',
			repositionPopup
		);
	}

	$( window ).on( 'resize.pumBuilderPreview', repositionPopup );
}
