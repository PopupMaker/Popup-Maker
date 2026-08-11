const ownedCanvas = window.pumBuilderOwnedCanvas;

if ( ownedCanvas?.popup_id ) {
	const display = ownedCanvas;
	let canvas: HTMLElement | null = null;
	let canvasDocument: Document | null = null;
	let canvasIframe: HTMLIFrameElement | null = null;
	let canvasWindow: Window | null = null;
	let canvasObserver: MutationObserver | null = null;
	let resizeObserver: ResizeObserver | null = null;

	const isEnabled = ( value: boolean | string ): boolean =>
		true === value || '1' === value;

	const pixelOffset = ( value: string ): string => {
		const offset = parseFloat( value );

		return Number.isNaN( offset ) ? '0px' : `${ offset }px`;
	};

	const addClasses = ( element: Element, classes: string ): void => {
		classes
			.split( /\s+/ )
			.filter( Boolean )
			.forEach( ( className ) => element.classList.add( className ) );
	};

	const copyPopupStyles = ( targetDocument: Document ): void => {
		( display.style_selectors || [] ).forEach( ( selector ) => {
			document.querySelectorAll( selector ).forEach( ( source ) => {
				const sourceId = source.getAttribute( 'id' );
				const copyId = sourceId ? `pum-builder-copy-${ sourceId }` : '';

				if ( copyId && targetDocument.getElementById( copyId ) ) {
					return;
				}

				const copy = source.cloneNode( true ) as HTMLElement;

				if ( copyId ) {
					copy.id = copyId;
				}

				if ( 'LINK' === copy.tagName ) {
					copy.addEventListener( 'load', adoptCanvas );
				}
				targetDocument.head.append( copy );
			} );
		} );
	};

	const attachCanvasStyles = ( targetDocument: Document ): void => {
		if ( targetDocument.getElementById( 'pum-builder-owned-canvas' ) ) {
			return;
		}

		const style = targetDocument.createElement( 'style' );
		style.id = 'pum-builder-owned-canvas';
		style.textContent = `
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

	function mirrorPopupGeometry(): void {
		if ( ! canvas || ! canvasWindow ) {
			return;
		}

		const isCustom = 'custom' === display.size;
		const isResponsive = ! isCustom && 'auto' !== display.size;
		const height =
			isCustom && ! isEnabled( display.custom_height_auto )
				? display.custom_height
				: 'auto';
		let vertical = 'center';
		let horizontal = 'center';
		let width = 'auto';
		let top = '50%';
		let bottom = 'auto';
		let left = '50%';
		let right = 'auto';

		if ( isCustom ) {
			width = display.custom_width;
		} else if ( isResponsive ) {
			width = '';
		}

		( display.location || 'center' ).split( ' ' ).forEach( ( part ) => {
			if ( 'top' === part || 'bottom' === part ) {
				vertical = part;
			} else if ( 'left' === part || 'right' === part ) {
				horizontal = part;
			}
		} );

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

		const properties: Record< string, string > = {
			position: isEnabled( display.position_fixed )
				? 'fixed'
				: 'absolute',
			width,
			height,
			'min-width': isResponsive
				? `min(${ display.responsive_min_width }, calc(100% - 20px))`
				: '0',
			'max-width': isResponsive
				? `min(${ display.responsive_max_width }, calc(100% - 20px))`
				: 'calc(100% - 20px)',
			'overflow-y': isEnabled( display.scrollable ) ? 'auto' : 'visible',
			margin: '0',
			top,
			bottom,
			left,
			right,
			transform: `translate(${
				'center' === horizontal ? '-50%' : '0'
			}, ${ 'center' === vertical ? '-50%' : '0' })`,
		};

		Object.entries( properties ).forEach( ( [ property, value ] ) => {
			if ( value ) {
				canvas?.style.setProperty( property, value, 'important' );
			} else {
				canvas?.style.removeProperty( property );
			}
		} );

		const bounds = canvas.getBoundingClientRect();
		const edgeGap = 10;
		const maxTop = canvasWindow.innerHeight - bounds.height - edgeGap;
		const maxLeft = canvasWindow.innerWidth - bounds.width - edgeGap;
		const isOversized =
			bounds.height + edgeGap * 2 > canvasWindow.innerHeight;
		const clampVertically =
			isOversized ||
			bounds.top < edgeGap ||
			bounds.bottom > canvasWindow.innerHeight - edgeGap;
		const clampHorizontally =
			bounds.left < edgeGap ||
			bounds.right > canvasWindow.innerWidth - edgeGap;

		if ( isOversized ) {
			canvas.style.setProperty( 'position', 'absolute', 'important' );
			canvasDocument?.documentElement.style.setProperty(
				'min-height',
				`${ Math.ceil( bounds.height + edgeGap ) }px`,
				'important'
			);
		} else {
			canvasDocument?.documentElement.style.removeProperty(
				'min-height'
			);
		}

		if ( clampVertically ) {
			canvas.style.setProperty(
				'top',
				`${ Math.max( edgeGap, Math.min( bounds.top, maxTop ) ) }px`,
				'important'
			);
			canvas.style.setProperty( 'bottom', 'auto', 'important' );
		}

		if ( clampHorizontally ) {
			canvas.style.setProperty(
				'left',
				`${ Math.max( edgeGap, Math.min( bounds.left, maxLeft ) ) }px`,
				'important'
			);
			canvas.style.setProperty( 'right', 'auto', 'important' );
		}

		if ( clampVertically || clampHorizontally ) {
			const transforms: string[] = [];

			if ( ! clampHorizontally && 'center' === horizontal ) {
				transforms.push( 'translateX(-50%)' );
			}

			if ( ! clampVertically && 'center' === vertical ) {
				transforms.push( 'translateY(-50%)' );
			}

			canvas.style.setProperty(
				'transform',
				transforms.join( ' ' ) || 'none',
				'important'
			);
		}
	}

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
		const targetCanvas = targetDocument?.querySelector< HTMLElement >(
			display.canvas_selector
		);

		if ( ! targetDocument || ! targetWindow || ! targetCanvas ) {
			return;
		}

		const documentChanged = canvasDocument !== targetDocument;
		const canvasChanged = canvas !== targetCanvas;

		if ( documentChanged ) {
			canvasWindow?.removeEventListener( 'resize', mirrorPopupGeometry );

			canvasDocument = targetDocument;
			canvasWindow = targetWindow;
			copyPopupStyles( targetDocument );
			attachCanvasStyles( targetDocument );
			targetWindow.addEventListener( 'resize', mirrorPopupGeometry );
		}

		if ( documentChanged || canvasChanged ) {
			canvasObserver?.disconnect();
			resizeObserver?.disconnect();
			resizeObserver = null;
			canvas = targetCanvas;

			canvasObserver = new MutationObserver( adoptCanvas );
			canvasObserver.observe( canvas, {
				childList: true,
			} );
		}

		addClasses( targetCanvas, display.container_classes );
		addClasses( targetCanvas, display.content_classes );
		targetCanvas.classList.add( 'pum-builder-canvas-area' );

		if ( iframe ) {
			const overlay = targetDocument.documentElement;
			addClasses( overlay, display.overlay_classes );
			overlay.classList.add( 'pum-builder-owned-canvas' );
			overlay.id = `pum-${ display.popup_id }`;
			targetCanvas.id = `popmake-${ display.popup_id }`;
		}

		attachTitle();
		attachCloseButton();
		mirrorPopupGeometry();

		if ( 'ResizeObserver' in window && ! resizeObserver ) {
			resizeObserver = new window.ResizeObserver( mirrorPopupGeometry );
			resizeObserver.observe( targetCanvas );
		}
	};

	const adoptChangedCanvas = (): void => {
		const iframe = display.iframe_selector
			? document.querySelector< HTMLIFrameElement >(
					display.iframe_selector
			  )
			: null;
		const targetDocument = iframe ? iframe.contentDocument : document;
		const targetCanvas = targetDocument?.querySelector< HTMLElement >(
			display.canvas_selector
		);

		if ( iframe !== canvasIframe || targetCanvas !== canvas ) {
			adoptCanvas();
		}
	};

	adoptCanvas();

	if ( 'MutationObserver' in window ) {
		new window.MutationObserver( adoptChangedCanvas ).observe(
			document.body,
			{
				childList: true,
				subtree: true,
			}
		);
	}

	window.addEventListener( 'resize', mirrorPopupGeometry );
}
