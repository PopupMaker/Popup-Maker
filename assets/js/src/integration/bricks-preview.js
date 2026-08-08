( function ( $ ) {
	'use strict';

	const display = window.pumBricksBuilderPreview || {};
	let $builderArea;

	if ( ! display.popup_id ) {
		return;
	}

	function pixelOffset( value ) {
		const offset = parseFloat( value );

		return isNaN( offset ) ? '0px' : offset + 'px';
	}

	function mirrorPopupGeometry() {
		if ( ! $builderArea || ! $builderArea.length ) {
			return;
		}

		const area = $builderArea[ 0 ],
			isCustom = 'custom' === display.size,
			isResponsive = ! isCustom && 'auto' !== display.size,
			isScrollable = $builderArea.hasClass( 'pum-scrollable' ),
			height =
				isCustom && ! display.custom_height_auto
					? display.custom_height
					: 'auto';
		let vertical = 'center',
			horizontal = 'center',
			width = 'auto',
			top = '50%',
			bottom = 'auto',
			left = '50%',
			right = 'auto';

		if ( isCustom ) {
			width = display.custom_width;
		} else if ( isResponsive ) {
			width = '';
		}

		( display.location || 'center' )
			.split( ' ' )
			.forEach( function ( part ) {
				if ( 'top' === part || 'bottom' === part ) {
					vertical = part;
				} else if ( 'left' === part || 'right' === part ) {
					horizontal = part;
				}
			} );

		$builderArea
			.parent()
			.css( { 'min-height': '100vh', position: 'relative' } );

		if ( 'bottom' === vertical ) {
			top = 'auto';
			bottom = pixelOffset( display.position_bottom );
		} else if ( 'top' === vertical ) {
			top = pixelOffset( display.position_top );
		}

		if ( 'right' === horizontal ) {
			left = 'auto';
			right = pixelOffset( display.position_right );
		} else if ( 'left' === horizontal ) {
			left = pixelOffset( display.position_left );
		}

		const properties = {
			position: display.position_fixed ? 'fixed' : 'absolute',
			width,
			height,
			'min-width': isResponsive ? display.responsive_min_width : '0',
			'max-width': isResponsive ? display.responsive_max_width : '100%',
			'overflow-y': isScrollable ? 'auto' : 'visible',
			margin: '0',
			top,
			bottom,
			left,
			right,
			transform:
				'translate(' +
				( 'center' === horizontal ? '-50%' : '0' ) +
				', ' +
				( 'center' === vertical ? '-50%' : '0' ) +
				')',
		};

		Object.keys( properties ).forEach( function ( property ) {
			area.style.setProperty(
				property,
				properties[ property ],
				'important'
			);
		} );
	}

	function attachTitle() {
		if ( ! $builderArea || ! $builderArea.length ) {
			return;
		}

		const $title = $builderArea
			.children( '.pum-builder-canvas-title' )
			.first();

		if ( ! display.title ) {
			$title.remove();

			return;
		}

		if ( ! $title.length ) {
			$( '<div></div>' )
				.addClass( display.title_classes + ' pum-builder-canvas-title' )
				.text( display.title )
				.prependTo( $builderArea );
		}
	}

	function attachCloseButton() {
		if ( ! $builderArea || ! $builderArea.length ) {
			return;
		}

		let $closeButton = $builderArea
			.children( '.pum-builder-canvas-close' )
			.first();
		let $closeAnchor = $builderArea
			.children( '.pum-builder-canvas-close-anchor' )
			.first();

		if ( ! display.show_close ) {
			$closeButton.remove();
			$closeAnchor.remove();

			return;
		}

		if ( ! $closeButton.length ) {
			$closeButton = $( '<button type="button"></button>' )
				.addClass( display.close_classes + ' pum-builder-canvas-close' )
				.attr( {
					'aria-disabled': 'true',
					tabindex: '-1',
					'aria-label': display.close_label,
				} )
				.html( display.close_content )
				.appendTo( $builderArea );
		}

		if ( ! $closeAnchor.length ) {
			$closeAnchor = $(
				'<div class="pum-content pum-builder-canvas-close-anchor" aria-hidden="true"></div>'
			);
		}

		if ( $closeButton.prev()[ 0 ] !== $closeAnchor[ 0 ] ) {
			$closeAnchor.insertBefore( $closeButton );
		}
	}

	function adoptBuilderArea() {
		$builderArea = $( '#brx-content' );

		if ( ! $builderArea.length ) {
			return;
		}

		$builderArea.addClass(
			display.container_classes + ' pum-builder-canvas-area'
		);

		mirrorPopupGeometry();
		attachTitle();
		attachCloseButton();
	}

	adoptBuilderArea();

	if ( 'MutationObserver' in window ) {
		new window.MutationObserver( adoptBuilderArea ).observe(
			document.body,
			{
				childList: true,
				subtree: true,
			}
		);
	}

	$( window ).on( 'resize.pumBricksBuilderPreview', mirrorPopupGeometry );
} )( window.jQuery );
