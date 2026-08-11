const ownedCanvas = window.pumBuilderOwnedCanvas;

if ( ownedCanvas?.popup_id ) {
	const display = ownedCanvas;
	let canvas: HTMLElement | null = null;
	let canvasDocument: Document | null = null;
	let canvasIframe: HTMLIFrameElement | null = null;
	let canvasWindow: Window | null = null;
	let targetObserver: MutationObserver | null = null;
	let resizeObserver: ResizeObserver | null = null;
	let adoptionFrame: number | null = null;
	let geometryFrame: number | null = null;
	let rootElement: HTMLElement | null = null;
	let scrollCanvas: HTMLElement | null = null;
	let originalRootMinHeight = '';
	let originalRootMinHeightPriority = '';
	let installedRootMinHeight = '';
	// Mirrors the responsive presets in the site stylesheet, using viewport
	// units because a builder canvas may have a narrower containing block.
	const responsiveWidths: Record< string, string > = {
		nano: '10vw',
		micro: '20vw',
		tiny: '30vw',
		small: '40vw',
		medium: '60vw',
		normal: '70vw',
		large: '80vw',
		xlarge: '95vw',
	};

	const isEnabled = ( value: boolean | string ): boolean =>
		true === value || '1' === value;

	const numericOffset = ( value: string ): number => {
		const offset = parseFloat( value );

		return Number.isNaN( offset ) ? 0 : offset;
	};

	const addClasses = ( element: Element, classes: string ): void => {
		classes
			.split( /\s+/ )
			.filter( Boolean )
			.forEach( ( className ) => element.classList.add( className ) );
	};

	const addOverlayIdentity = (
		targetDocument: Document,
		classes: string
	): void => {
		const rootClasses = classes
			.split( /\s+/ )
			.filter(
				( className ) =>
					className.startsWith( 'pum-theme-' ) ||
					'pum-overlay-disabled' === className
			)
			.join( ' ' );

		targetDocument.documentElement.classList.add(
			'pum-builder-owned-canvas-root'
		);
		addClasses( targetDocument.documentElement, rootClasses );
		addClasses( targetDocument.body, rootClasses );
	};

	const viewportRelativeLength = (
		value: string,
		viewportUnit = 'vw'
	): string => {
		const percentage = value.trim().match( /^(\d+(?:\.\d+)?)%$/ );

		return percentage ? `${ percentage[ 1 ] }${ viewportUnit }` : value;
	};

	const responsiveWidth = ( size: string, viewportWidth: number ): string => {
		if ( viewportWidth < 1024 ) {
			return '95vw';
		}

		return responsiveWidths[ size ] || '95vw';
	};

	const copyPopupStyles = ( targetDocument: Document ): void => {
		( display.style_selectors || [] ).forEach(
			( selector, selectorIndex ) => {
				document
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
							copy.addEventListener( 'load', adoptCanvas );
						}
						targetDocument.head.append( copy );
					} );
			}
		);
	};

	const attachCanvasStyles = ( targetDocument: Document ): void => {
		if ( targetDocument.getElementById( 'pum-builder-owned-canvas' ) ) {
			return;
		}

		const style = targetDocument.createElement( 'style' );
		style.id = 'pum-builder-owned-canvas';
		style.textContent = `
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
			.pum-builder-canvas-area.pum-scrollable > .pum-builder-canvas-title,
			.pum-builder-canvas-area.pum-scrollable > .pum-builder-canvas-close {
				translate: 0 var(--pum-builder-canvas-scroll-y, 0) !important;
			}
		`;
		targetDocument.head.append( style );
	};

	const attachTitle = (): void => {
		if ( ! canvas ) {
			return;
		}

		let title = canvas.querySelector< HTMLElement >(
			':scope > .pum-builder-canvas-title'
		);

		if ( ! display.title_text ) {
			title?.remove();

			return;
		}

		if ( ! title ) {
			title = canvas.ownerDocument.createElement( 'div' );
			title.className = `${ display.title_classes } pum-builder-canvas-title`;
			title.id = `pum_popup_title_${ display.popup_id }`;
			title.textContent = display.title_text;
		}

		if ( canvas.firstElementChild !== title ) {
			canvas.prepend( title );
		}
	};

	const attachCloseButton = (): void => {
		if ( ! canvas ) {
			return;
		}

		let closeButton = canvas.querySelector< HTMLButtonElement >(
			':scope > .pum-builder-canvas-close'
		);
		let closeAnchor = canvas.querySelector< HTMLElement >(
			':scope > .pum-builder-canvas-close-anchor'
		);

		if ( ! isEnabled( display.show_close ) ) {
			closeButton?.remove();
			closeAnchor?.remove();

			return;
		}

		if ( ! closeButton ) {
			closeButton = canvas.ownerDocument.createElement( 'button' );
			closeButton.type = 'button';
			closeButton.className = `${ display.close_classes } pum-builder-canvas-close`;
			// Escaped by pum_popup_close_text(); innerHTML preserves its optional icon.
			closeButton.innerHTML = display.close_content;
			closeButton.setAttribute( 'aria-disabled', 'true' );
			closeButton.setAttribute( 'aria-label', display.close_label );
			closeButton.tabIndex = -1;
		}

		if ( ! closeAnchor ) {
			closeAnchor = canvas.ownerDocument.createElement( 'div' );
			closeAnchor.className = `${ display.content_classes } pum-builder-canvas-close-anchor`;
			closeAnchor.setAttribute( 'aria-hidden', 'true' );
		}

		if ( closeButton.previousElementSibling !== closeAnchor ) {
			canvas.append( closeAnchor, closeButton );
		}
	};

	const restoreRootMinHeight = (): void => {
		if ( ! rootElement || ! installedRootMinHeight ) {
			return;
		}

		if (
			rootElement.style.getPropertyValue( 'min-height' ) ===
			installedRootMinHeight
		) {
			if ( originalRootMinHeight ) {
				rootElement.style.setProperty(
					'min-height',
					originalRootMinHeight,
					originalRootMinHeightPriority
				);
			} else {
				rootElement.style.removeProperty( 'min-height' );
			}
		}

		installedRootMinHeight = '';
	};

	const mirrorCanvasScroll = (): void => {
		if ( ! canvas ) {
			return;
		}

		canvas.style.setProperty(
			'--pum-builder-canvas-scroll-y',
			isEnabled( display.scrollable ) ? `${ canvas.scrollTop }px` : '0px'
		);
	};

	const setRootMinHeight = ( height: string ): void => {
		if ( ! rootElement ) {
			return;
		}

		if ( ! installedRootMinHeight ) {
			originalRootMinHeight =
				rootElement.style.getPropertyValue( 'min-height' );
			originalRootMinHeightPriority =
				rootElement.style.getPropertyPriority( 'min-height' );
		}

		installedRootMinHeight = height;
		rootElement.style.setProperty( 'min-height', height, 'important' );
	};

	const viewportToPosition = (
		viewportTop: number,
		viewportLeft: number,
		position: string
	): { left: number; top: number } => {
		if ( ! canvas || ! canvasWindow ) {
			return { left: viewportLeft, top: viewportTop };
		}

		const offsetParent = canvas.offsetParent;

		if ( 'fixed' === position && ! offsetParent ) {
			return { left: viewportLeft, top: viewportTop };
		}

		if ( offsetParent ) {
			const parentBounds = offsetParent.getBoundingClientRect();

			return {
				left:
					viewportLeft -
					parentBounds.left +
					offsetParent.scrollLeft -
					offsetParent.clientLeft,
				top:
					viewportTop -
					parentBounds.top +
					offsetParent.scrollTop -
					offsetParent.clientTop,
			};
		}

		return {
			left: viewportLeft + canvasWindow.scrollX,
			top: viewportTop + canvasWindow.scrollY,
		};
	};

	function mirrorPopupGeometry(): void {
		if ( ! canvas || ! canvasWindow ) {
			return;
		}

		const isCustom = 'custom' === display.size;
		const isResponsive = ! isCustom && 'auto' !== display.size;
		const height =
			isCustom && ! isEnabled( display.custom_height_auto )
				? viewportRelativeLength( display.custom_height, 'vh' )
				: 'auto';
		let vertical = 'center';
		let horizontal = 'center';
		let width = 'auto';

		if ( isCustom ) {
			width = viewportRelativeLength( display.custom_width );
		} else if ( isResponsive ) {
			width = responsiveWidth( display.size, canvasWindow.innerWidth );
		}

		( display.location || 'center' ).split( ' ' ).forEach( ( part ) => {
			if ( 'top' === part || 'bottom' === part ) {
				vertical = part;
			} else if ( 'left' === part || 'right' === part ) {
				horizontal = part;
			}
		} );

		const requestedPosition = isEnabled( display.position_fixed )
			? 'fixed'
			: 'absolute';
		const properties: Record< string, string > = {
			position: requestedPosition,
			width,
			height,
			'min-width': isResponsive
				? `min(${ viewportRelativeLength(
						display.responsive_min_width
				  ) }, calc(100vw - 20px))`
				: '0',
			'max-width': isResponsive
				? `min(${ viewportRelativeLength(
						display.responsive_max_width
				  ) }, calc(100vw - 20px))`
				: 'calc(100vw - 20px)',
			'overflow-y': isEnabled( display.scrollable ) ? 'auto' : 'visible',
			margin: '0',
			top: '0',
			bottom: 'auto',
			left: '0',
			right: 'auto',
			transform: 'none',
		};

		Object.entries( properties ).forEach( ( [ property, value ] ) => {
			if ( value ) {
				canvas?.style.setProperty( property, value, 'important' );
			} else {
				canvas?.style.removeProperty( property );
			}
		} );

		const bounds = canvas.getBoundingClientRect();
		const closeButton = canvas.querySelector< HTMLElement >(
			':scope > .pum-builder-canvas-close'
		);
		const closeBounds = closeButton?.getBoundingClientRect();
		let visualTop = 0;
		let visualRight = bounds.width;
		let visualBottom = bounds.height;
		let visualLeft = 0;

		if ( closeBounds && ( closeBounds.width || closeBounds.height ) ) {
			visualTop = Math.min( visualTop, closeBounds.top - bounds.top );
			visualRight = Math.max(
				visualRight,
				closeBounds.right - bounds.left
			);
			visualBottom = Math.max(
				visualBottom,
				closeBounds.bottom - bounds.top
			);
			visualLeft = Math.min( visualLeft, closeBounds.left - bounds.left );
		}

		let viewportTop = ( canvasWindow.innerHeight - bounds.height ) / 2;
		let viewportLeft = ( canvasWindow.innerWidth - bounds.width ) / 2;

		if ( 'top' === vertical ) {
			viewportTop = numericOffset( display.position_top );
		} else if ( 'bottom' === vertical ) {
			viewportTop =
				canvasWindow.innerHeight -
				bounds.height -
				numericOffset( display.position_bottom );
		}

		if ( 'left' === horizontal ) {
			viewportLeft = numericOffset( display.position_left );
		} else if ( 'right' === horizontal ) {
			viewportLeft =
				canvasWindow.innerWidth -
				bounds.width -
				numericOffset( display.position_right );
		}

		const edgeGap = 10;
		const topEdgeGap = canvas.ownerDocument.body.classList.contains(
			'admin-bar'
		)
			? 42
			: edgeGap;
		const minTop = topEdgeGap - visualTop;
		const maxTop = canvasWindow.innerHeight - edgeGap - visualBottom;
		const minLeft = edgeGap - visualLeft;
		const maxLeft = canvasWindow.innerWidth - edgeGap - visualRight;
		const isOversized =
			visualBottom - visualTop + topEdgeGap + edgeGap >
			canvasWindow.innerHeight;

		viewportTop = Math.max( minTop, Math.min( viewportTop, maxTop ) );
		viewportLeft = Math.max( minLeft, Math.min( viewportLeft, maxLeft ) );

		const position = isOversized ? 'absolute' : requestedPosition;
		canvas.style.setProperty( 'position', position, 'important' );
		const coordinates = viewportToPosition(
			viewportTop,
			viewportLeft,
			position
		);

		canvas.style.setProperty(
			'top',
			`${ Math.round( coordinates.top * 100 ) / 100 }px`,
			'important'
		);
		canvas.style.setProperty( 'bottom', 'auto', 'important' );
		canvas.style.setProperty(
			'left',
			`${ Math.round( coordinates.left * 100 ) / 100 }px`,
			'important'
		);
		canvas.style.setProperty( 'right', 'auto', 'important' );
		canvas.style.setProperty( 'transform', 'none', 'important' );

		if ( isOversized ) {
			setRootMinHeight(
				`${ Math.ceil(
					canvasWindow.scrollY + viewportTop + visualBottom + edgeGap
				) }px`
			);
		} else {
			restoreRootMinHeight();
		}
	}

	const scheduleGeometry = (): void => {
		if ( null !== geometryFrame || ! canvasWindow ) {
			return;
		}

		geometryFrame = canvasWindow.requestAnimationFrame( () => {
			geometryFrame = null;
			mirrorPopupGeometry();
		} );
	};

	const adoptCanvas = (): void => {
		const iframe = display.iframe_selector
			? document.querySelector< HTMLIFrameElement >(
					display.iframe_selector
			  )
			: null;

		if ( display.iframe_selector && ! iframe ) {
			return;
		}

		if ( canvasIframe !== iframe ) {
			canvasIframe?.removeEventListener( 'load', adoptCanvas );
			canvasIframe = iframe;
			canvasIframe?.addEventListener( 'load', adoptCanvas );
		}

		const targetDocument = iframe ? iframe.contentDocument : document;
		const targetWindow = iframe ? iframe.contentWindow : window;

		if ( ! targetDocument || ! targetWindow ) {
			return;
		}

		const documentChanged = canvasDocument !== targetDocument;

		if ( documentChanged ) {
			canvasWindow?.removeEventListener( 'resize', scheduleGeometry );
			if ( null !== geometryFrame && canvasWindow ) {
				canvasWindow.cancelAnimationFrame( geometryFrame );
				geometryFrame = null;
			}
			restoreRootMinHeight();
			targetObserver?.disconnect();
			targetObserver = null;

			canvasDocument = targetDocument;
			canvasWindow = targetWindow;
			rootElement = targetDocument.documentElement;

			if ( iframe ) {
				if ( 'MutationObserver' in window ) {
					targetObserver = new window.MutationObserver(
						scheduleAdoption
					);
					targetObserver.observe( targetDocument.documentElement, {
						childList: true,
						subtree: true,
					} );
				}
			}

			targetWindow.addEventListener( 'resize', scheduleGeometry );
		}

		if ( iframe ) {
			copyPopupStyles( targetDocument );
			attachCanvasStyles( targetDocument );
		}

		const targetCanvas = targetDocument.querySelector< HTMLElement >(
			display.canvas_selector
		);

		if ( ! targetCanvas ) {
			resizeObserver?.disconnect();
			resizeObserver = null;
			scrollCanvas?.removeEventListener( 'scroll', mirrorCanvasScroll );
			scrollCanvas = null;
			canvas = null;

			return;
		}

		const canvasChanged = canvas !== targetCanvas;

		if ( documentChanged || canvasChanged ) {
			resizeObserver?.disconnect();
			resizeObserver = null;
			scrollCanvas?.removeEventListener( 'scroll', mirrorCanvasScroll );
			canvas = targetCanvas;
			scrollCanvas = targetCanvas;
			scrollCanvas.addEventListener( 'scroll', mirrorCanvasScroll );
		}

		addClasses( targetCanvas, display.container_classes );
		addClasses( targetCanvas, display.content_classes );
		targetCanvas.classList.add( 'pum-builder-canvas-area' );

		addOverlayIdentity( targetDocument, display.overlay_classes );

		if ( iframe ) {
			targetDocument.documentElement.classList.add(
				'pum-builder-owned-canvas'
			);
		}

		attachTitle();
		attachCloseButton();
		mirrorCanvasScroll();
		mirrorPopupGeometry();

		if ( 'ResizeObserver' in window && ! resizeObserver ) {
			resizeObserver = new window.ResizeObserver( scheduleGeometry );
			resizeObserver.observe( targetCanvas );
		}
	};

	function scheduleAdoption(): void {
		if ( null !== adoptionFrame ) {
			return;
		}

		adoptionFrame = window.requestAnimationFrame( () => {
			adoptionFrame = null;
			adoptCanvas();
		} );
	}

	adoptCanvas();

	if ( 'MutationObserver' in window ) {
		new window.MutationObserver( scheduleAdoption ).observe(
			document.body,
			{
				childList: true,
				subtree: true,
			}
		);
	}

	window.addEventListener( 'resize', scheduleGeometry );
}
