import { mirrorPopupGeometry as updatePopupGeometry } from './owned-canvas/geometry';
import { isEnabled, selectorAttributeFilter } from './owned-canvas/settings';
import { attachCanvasStyles, copyPopupStyles } from './owned-canvas/styles';
import { getCanvasTransformSignature } from './owned-canvas/transforms';

const ownedCanvas = window.pumBuilderOwnedCanvas;

if ( ownedCanvas?.popup_id ) {
	const display = ownedCanvas;
	let canvas: HTMLElement | null = null;
	let canvasDocument: Document | null = null;
	let canvasIframe: HTMLIFrameElement | null = null;
	let canvasWindow: Window | null = null;
	let targetObserver: MutationObserver | null = null;
	let resizeObserver: ResizeObserver | null = null;
	let canvasTransformObserver: MutationObserver | null = null;
	let adoptionFrame: number | null = null;
	let geometryFrame: number | null = null;
	let rootElement: HTMLElement | null = null;
	let scrollCanvas: HTMLElement | null = null;
	let originalRootMinHeight = '';
	let originalRootMinHeightPriority = '';
	let installedRootMinHeight = '';
	let ownedRootClasses: Array< { className: string; element: Element } > = [];
	let ownedCanvasClasses: string[] = [];
	let syntheticCanvasElements: HTMLElement[] = [];
	let ownedStyleElements: HTMLElement[] = [];
	let canvasTransformSignature = '';
	let canvasSelectorAttributes: string[] = [];
	const ownedCanvasAttributes = new Map< string, string | null >();
	const canvasStyles = new Map<
		string,
		{
			installedPriority: string;
			installedValue: string;
			originalPriority: string;
			originalValue: string;
		}
	>();

	const rememberOwnedCanvasAttribute = (
		element: Element,
		attribute: string,
		previousValue: string | null
	): void => {
		if (
			element === canvas &&
			( ! ownedCanvasAttributes.has( attribute ) ||
				ownedCanvasAttributes.get( attribute ) === previousValue )
		) {
			ownedCanvasAttributes.set(
				attribute,
				element.getAttribute( attribute )
			);
		}
	};

	const addCanvasClasses = ( element: Element, classes: string ): void => {
		const previousClass = element.getAttribute( 'class' );

		classes
			.split( /\s+/ )
			.filter( Boolean )
			.forEach( ( className ) => {
				if ( ! element.classList.contains( className ) ) {
					element.classList.add( className );
					ownedCanvasClasses.push( className );
				}
			} );

		rememberOwnedCanvasAttribute( element, 'class', previousClass );
	};

	const setCanvasStyle = (
		property: string,
		value: string,
		priority = ''
	): void => {
		if ( ! canvas ) {
			return;
		}

		const previousStyle = canvas.getAttribute( 'style' );
		let state = canvasStyles.get( property );

		if ( ! state ) {
			state = {
				installedPriority: '',
				installedValue: '',
				originalPriority: canvas.style.getPropertyPriority( property ),
				originalValue: canvas.style.getPropertyValue( property ),
			};
			canvasStyles.set( property, state );
		}

		canvas.style.setProperty( property, value, priority );
		state.installedPriority = canvas.style.getPropertyPriority( property );
		state.installedValue = canvas.style.getPropertyValue( property );
		rememberOwnedCanvasAttribute( canvas, 'style', previousStyle );
	};

	const addRootClass = ( element: Element, className: string ): void => {
		if ( element.classList.contains( className ) ) {
			return;
		}

		element.classList.add( className );
		ownedRootClasses.push( { className, element } );
	};

	const clearOverlayIdentity = (): void => {
		ownedRootClasses.forEach( ( { className, element } ) => {
			element.classList.remove( className );
		} );
		ownedRootClasses = [];
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

		addRootClass(
			targetDocument.documentElement,
			'pum-builder-owned-canvas-root'
		);
		rootClasses
			.split( /\s+/ )
			.filter( Boolean )
			.forEach( ( className ) => {
				addRootClass( targetDocument.documentElement, className );
				addRootClass( targetDocument.body, className );
			} );
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
			syntheticCanvasElements.push( title );
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
			closeButton.style.setProperty(
				'pointer-events',
				'none',
				'important'
			);
			closeButton.tabIndex = -1;
			syntheticCanvasElements.push( closeButton );
		}

		if ( ! closeAnchor ) {
			closeAnchor = canvas.ownerDocument.createElement( 'div' );
			closeAnchor.className = `${ display.content_classes } pum-builder-canvas-close-anchor`;
			closeAnchor.setAttribute( 'aria-hidden', 'true' );
			syntheticCanvasElements.push( closeAnchor );
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

		setCanvasStyle(
			'--pum-builder-canvas-scroll-y',
			isEnabled( display.scrollable ) ? `${ canvas.scrollTop }px` : '0px'
		);
	};

	const restoreCanvas = (): void => {
		if ( ! canvas ) {
			return;
		}

		resizeObserver?.disconnect();
		resizeObserver = null;
		canvasTransformObserver?.disconnect();
		canvasTransformObserver = null;
		canvasTransformSignature = '';
		scrollCanvas?.removeEventListener( 'scroll', mirrorCanvasScroll );
		scrollCanvas = null;

		syntheticCanvasElements.forEach( ( element ) => element.remove() );
		syntheticCanvasElements = [];
		ownedCanvasClasses.forEach( ( className ) => {
			canvas?.classList.remove( className );
		} );
		ownedCanvasClasses = [];

		canvasStyles.forEach( ( state, property ) => {
			if (
				canvas?.style.getPropertyValue( property ) !==
					state.installedValue ||
				canvas.style.getPropertyPriority( property ) !==
					state.installedPriority
			) {
				return;
			}

			if ( state.originalValue ) {
				canvas.style.setProperty(
					property,
					state.originalValue,
					state.originalPriority
				);
			} else {
				canvas.style.removeProperty( property );
			}
		} );
		canvasStyles.clear();
		ownedCanvasAttributes.clear();
		canvasSelectorAttributes = [];
		canvas = null;
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

	const mirrorPopupGeometry = (): void => {
		if ( ! canvas || ! canvasWindow ) {
			return;
		}

		canvasTransformSignature = updatePopupGeometry( {
			canvas,
			display,
			restoreRootMinHeight,
			setCanvasStyle,
			setRootMinHeight,
			targetWindow: canvasWindow,
		} );
	};

	const scheduleGeometry = (): void => {
		if ( null !== geometryFrame || ! canvasWindow ) {
			return;
		}

		geometryFrame = canvasWindow.requestAnimationFrame( () => {
			geometryFrame = null;
			mirrorPopupGeometry();
		} );
	};

	const scheduleTransformGeometry = (): void => {
		if ( ! canvas || ! canvasWindow ) {
			return;
		}

		const signature = getCanvasTransformSignature( canvas, canvasWindow );

		if ( signature === canvasTransformSignature ) {
			return;
		}

		canvasTransformSignature = signature;
		scheduleGeometry();
	};

	const scheduleAdoptionFromMutations = (
		records: MutationRecord[]
	): void => {
		const onlyOwnedCanvasAttributes =
			Boolean( records.length && canvas ) &&
			records.every( ( record ) => {
				if (
					'attributes' !== record.type ||
					! record.attributeName ||
					record.target !== canvas
				) {
					return false;
				}

				return (
					ownedCanvasAttributes.has( record.attributeName ) &&
					ownedCanvasAttributes.get( record.attributeName ) ===
						( record.target as HTMLElement ).getAttribute(
							record.attributeName
						)
				);
			} );

		if ( ! onlyOwnedCanvasAttributes ) {
			scheduleAdoption();
		}
	};

	const currentCanvasRetainsIdentity = (
		targetDocument: Document
	): boolean =>
		Boolean(
			canvas &&
				canvas.ownerDocument === targetDocument &&
				canvas.isConnected &&
				canvasSelectorAttributes.every(
					( attribute ) =>
						ownedCanvasAttributes.get( attribute ) ===
						canvas?.getAttribute( attribute )
				)
		);

	const releaseCanvasDocument = (): void => {
		restoreCanvas();
		canvasWindow?.removeEventListener( 'resize', scheduleGeometry );
		if ( null !== geometryFrame && canvasWindow ) {
			canvasWindow.cancelAnimationFrame( geometryFrame );
			geometryFrame = null;
		}
		restoreRootMinHeight();
		clearOverlayIdentity();
		ownedStyleElements.forEach( ( element ) => element.remove() );
		ownedStyleElements = [];
		targetObserver?.disconnect();
		targetObserver = null;
		canvasDocument = null;
		canvasWindow = null;
		rootElement = null;
	};

	const adoptCanvas = (): void => {
		const iframe = display.iframe_selector
			? document.querySelector< HTMLIFrameElement >(
					display.iframe_selector
			  )
			: null;

		if ( display.iframe_selector && ! iframe ) {
			canvasIframe?.removeEventListener( 'load', adoptCanvas );
			canvasIframe = null;
			releaseCanvasDocument();

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
			releaseCanvasDocument();

			canvasDocument = targetDocument;
			canvasWindow = targetWindow;
			rootElement = targetDocument.documentElement;

			if ( iframe && 'MutationObserver' in window ) {
				targetObserver = new window.MutationObserver(
					scheduleAdoptionFromMutations
				);
				targetObserver.observe( targetDocument.documentElement, {
					attributes: true,
					attributeFilter: selectorAttributeFilter(
						display.canvas_selector
					),
					childList: true,
					subtree: true,
				} );
			}

			targetWindow.addEventListener( 'resize', scheduleGeometry );
		}

		if ( iframe ) {
			ownedStyleElements.push(
				...copyPopupStyles(
					document,
					targetDocument,
					display.style_selectors || [],
					adoptCanvas
				)
			);
			const attachedStyles = attachCanvasStyles( targetDocument );

			if ( attachedStyles ) {
				ownedStyleElements.push( attachedStyles );
			}
		}

		let targetCanvas = targetDocument.querySelector< HTMLElement >(
			display.canvas_selector
		);

		if (
			! targetCanvas &&
			currentCanvasRetainsIdentity( targetDocument )
		) {
			targetCanvas = canvas;
		}

		if ( ! targetCanvas ) {
			restoreRootMinHeight();
			clearOverlayIdentity();
			restoreCanvas();

			return;
		}

		const canvasChanged = canvas !== targetCanvas;

		if ( canvasChanged ) {
			restoreCanvas();
			canvas = targetCanvas;
			scrollCanvas = targetCanvas;
			scrollCanvas.addEventListener( 'scroll', mirrorCanvasScroll );
			canvasSelectorAttributes = selectorAttributeFilter(
				display.canvas_selector
			);
			if ( ! canvasSelectorAttributes.includes( 'style' ) ) {
				canvasSelectorAttributes.push( 'style' );
			}
		}

		canvasSelectorAttributes.forEach( ( attribute ) => {
			ownedCanvasAttributes.set(
				attribute,
				targetCanvas.getAttribute( attribute )
			);
		} );

		if ( 'MutationObserver' in window ) {
			if ( ! canvasTransformObserver ) {
				canvasTransformObserver = new window.MutationObserver(
					scheduleTransformGeometry
				);
			} else {
				canvasTransformObserver.disconnect();
			}

			canvasTransformSignature = getCanvasTransformSignature(
				targetCanvas,
				targetWindow
			);
			let transformTarget: HTMLElement | null = targetCanvas;

			while ( transformTarget ) {
				canvasTransformObserver.observe( transformTarget, {
					attributes: true,
					attributeFilter: [ 'style' ],
				} );
				transformTarget = transformTarget.parentElement;
			}
		}

		addCanvasClasses( targetCanvas, display.container_classes );
		addCanvasClasses( targetCanvas, display.content_classes );
		addCanvasClasses( targetCanvas, 'pum-builder-canvas-area' );

		addOverlayIdentity( targetDocument, display.overlay_classes );

		if ( iframe ) {
			addRootClass(
				targetDocument.documentElement,
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
		new window.MutationObserver( scheduleAdoptionFromMutations ).observe(
			document.body,
			{
				attributes: true,
				attributeFilter: selectorAttributeFilter(
					display.canvas_selector,
					display.iframe_selector
				),
				childList: true,
				subtree: true,
			}
		);
	}

	window.addEventListener( 'resize', scheduleGeometry );
}
