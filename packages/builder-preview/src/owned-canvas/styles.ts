const canvasStyles = `
	html.pum-builder-owned-canvas-root > body {
		background: transparent !important;
	}
	html.pum-builder-owned-canvas-root.pum-overlay-disabled,
	html.pum-builder-owned-canvas-root > body.pum-overlay-disabled {
		background: transparent !important;
	}
	html.pum-builder-owned-canvas {
		display: block !important;
		min-height: 100%;
		opacity: 1 !important;
		overflow: auto !important;
		visibility: visible !important;
	}
	html.pum-builder-owned-canvas.pum-overlay-disabled {
		background: transparent !important;
	}
	html.pum-builder-owned-canvas > body.pum-builder-canvas-area {
		display: block !important;
		height: auto;
		margin: 0 !important;
		min-height: 0 !important;
		opacity: 1 !important;
		position: absolute !important;
		visibility: visible !important;
	}
	.pum-builder-canvas-title,
	.pum-builder-canvas-close {
		pointer-events: none !important;
	}
	.pum-builder-canvas-close-anchor {
		display: none !important;
	}
	.pum-builder-canvas-area,
	.pum-builder-canvas-area *,
	.pum-builder-canvas-area *::before,
	.pum-builder-canvas-area *::after {
		box-sizing: border-box;
	}
	.pum-builder-canvas-area.pum-content:focus {
		outline: none;
	}
	.pum-builder-canvas-area.pum-content > :nth-child(1 of :not(.pum-builder-canvas-title, .pum-builder-canvas-close-anchor, .pum-builder-canvas-close)) {
		margin-top: 0;
	}
	.pum-builder-canvas-area.pum-content > :nth-last-child(1 of :not(.pum-builder-canvas-title, .pum-builder-canvas-close-anchor, .pum-builder-canvas-close)) {
		margin-bottom: 0;
	}
	.pum-builder-canvas-area.pum-scrollable > .pum-builder-canvas-title,
	.pum-builder-canvas-area.pum-scrollable > .pum-builder-canvas-close {
		translate: 0 var(--pum-builder-canvas-scroll-y, 0) !important;
	}
`;

export const copyPopupStyles = (
	sourceDocument: Document,
	targetDocument: Document,
	selectors: string[],
	onLoad: () => void
): HTMLElement[] => {
	const copies: HTMLElement[] = [];

	selectors.forEach( ( selector, selectorIndex ) => {
		sourceDocument
			.querySelectorAll( selector )
			.forEach( ( source, sourceIndex ) => {
				const sourceId = source.getAttribute( 'id' );
				const copyId = sourceId
					? `pum-builder-copy-${ sourceId }`
					: `pum-builder-copy-anonymous-${ selectorIndex }-${ sourceIndex }`;

				if ( targetDocument.getElementById( copyId ) ) {
					return;
				}

				const copy = source.cloneNode( true ) as HTMLElement;

				copy.id = copyId;

				if ( 'LINK' === copy.tagName ) {
					copy.addEventListener( 'load', onLoad );
				}
				targetDocument.head.append( copy );
				copies.push( copy );
			} );
	} );

	return copies;
};

export const attachCanvasStyles = (
	targetDocument: Document
): HTMLStyleElement | null => {
	if ( targetDocument.getElementById( 'pum-builder-owned-canvas' ) ) {
		return null;
	}

	const style = targetDocument.createElement( 'style' );
	style.id = 'pum-builder-owned-canvas';
	style.textContent = canvasStyles;
	targetDocument.head.append( style );

	return style;
};
