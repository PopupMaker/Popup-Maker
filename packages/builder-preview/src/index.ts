import './styles.scss';

import $ from 'jquery';

const PUM = window.PUM;
const $element = $( 'body.pum-builder-preview > .pum' ).first();
const popupId = parseInt(
	( $element.attr( 'id' ) || '' ).replace( 'pum-', '' ),
	10
);
const $popup = PUM && popupId ? PUM.getPopup( popupId ) : null;
const $container = $popup?.find( '.pum-container' );

if ( PUM && $popup?.length ) {
	const reposition = (): void => {
		if ( ! $popup.closest( 'body' ).length ) {
			return;
		}

		$popup.popmake( 'reposition' );
		$popup.find( '.pum-close' ).attr( {
			'aria-disabled': 'true',
			tabindex: '-1',
		} );
	};

	$popup.on( 'pumBeforeClose.pumBuilderPreview', () => {
		$popup.addClass( 'preventClose' );
	} );
	$popup.on( 'pumAfterOpen.pumBuilderPreview', reposition );

	if ( 'ResizeObserver' in window && $container?.length ) {
		new window.ResizeObserver( reposition ).observe( $container[ 0 ] );
	}

	if ( PUM.initialized ) {
		reposition();
	} else {
		$( document ).one( 'pumInitialized.pumBuilderPreview', reposition );
	}

	$( window ).on( 'resize.pumBuilderPreview', reposition );
}
