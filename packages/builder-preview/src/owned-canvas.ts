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

	const selectorAttributeFilter = (
		...selectors: Array< string | undefined >
	): string[] => {
		const attributes = [ 'class', 'id' ];
		const pattern = /\[\s*([^\s~|^$*=\]]+)/g;

		selectors.forEach( ( selector ) => {
			if ( ! selector ) {
				return;
			}

			let match: RegExpExecArray | null;

			while ( ( match = pattern.exec( selector ) ) ) {
				attributes.push( match[ 1 ] );
			}
		} );

		return Array.from( new Set( attributes ) );
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
						ownedStyleElements.push( copy );
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
		targetDocument.head.append( style );
		ownedStyleElements.push( style );
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

	const fixedContainingBlock = (): HTMLElement | null => {
		if ( ! canvas || ! canvasWindow ) {
			return null;
		}

		let ancestor = canvas.parentElement;

		while ( ancestor ) {
			const style = canvasWindow.getComputedStyle( ancestor );
			const contain = style.getPropertyValue( 'contain' );
			const willChange = style.getPropertyValue( 'will-change' );
			const createsContainingBlock =
				[
					'transform',
					'translate',
					'scale',
					'rotate',
					'perspective',
					'filter',
					'backdrop-filter',
				].some( ( property ) => {
					const value = style.getPropertyValue( property );

					return '' !== value && 'none' !== value;
				} ) ||
				/(?:layout|paint|strict|content)/.test( contain ) ||
				/(?:transform|perspective|filter)/.test( willChange ) ||
				'auto' === style.getPropertyValue( 'content-visibility' );

			if ( createsContainingBlock ) {
				return ancestor;
			}

			ancestor = ancestor.parentElement;
		}

		return null;
	};

	type Matrix2D = {
		a: number;
		b: number;
		c: number;
		d: number;
	};

	const identityMatrix = (): Matrix2D => ( {
		a: 1,
		b: 0,
		c: 0,
		d: 1,
	} );

	const multiplyMatrices = (
		left: Matrix2D,
		right: Matrix2D
	): Matrix2D => ( {
		a: left.a * right.a + left.c * right.b,
		b: left.b * right.a + left.d * right.b,
		c: left.a * right.c + left.c * right.d,
		d: left.b * right.c + left.d * right.d,
	} );

	const transformMatrix = ( transform: string ): Matrix2D | null => {
		const match = transform.match( /^(matrix|matrix3d)\((.+)\)$/ );

		if ( ! match ) {
			return null;
		}

		const values = match[ 2 ].split( ',' ).map( Number );

		if ( values.some( ( value ) => Number.isNaN( value ) ) ) {
			return null;
		}

		if ( 'matrix' === match[ 1 ] && 6 === values.length ) {
			const [ a, b, c, d ] = values;

			return { a, b, c, d };
		}

		if ( 'matrix3d' === match[ 1 ] && 16 === values.length ) {
			const isPlanar = [ 2, 3, 6, 7, 8, 9, 11, 14 ].every(
				( index ) => 0 === values[ index ]
			);

			if ( ! isPlanar || 1 !== values[ 10 ] || 1 !== values[ 15 ] ) {
				return null;
			}

			return {
				a: values[ 0 ],
				b: values[ 1 ],
				c: values[ 4 ],
				d: values[ 5 ],
			};
		}

		return null;
	};

	const scaleValue = ( value: string ): number | null => {
		const scale = parseFloat( value );

		if ( Number.isNaN( scale ) ) {
			return null;
		}

		return value.endsWith( '%' ) ? scale / 100 : scale;
	};

	const scaleMatrix = ( scale: string ): Matrix2D | null => {
		if ( ! scale || 'none' === scale ) {
			return identityMatrix();
		}

		const values = scale.trim().split( /\s+/ ).map( scaleValue );

		if (
			values.some( ( value ) => null === value ) ||
			values.length < 1 ||
			values.length > 3 ||
			( 3 === values.length && 1 !== values[ 2 ] )
		) {
			return null;
		}

		return {
			...identityMatrix(),
			a: values[ 0 ] as number,
			d: ( values[ 1 ] ?? values[ 0 ] ) as number,
		};
	};

	const angleRadians = ( value: string ): number | null => {
		const angle = parseFloat( value );

		if ( Number.isNaN( angle ) ) {
			return null;
		}

		if ( value.endsWith( 'deg' ) ) {
			return ( angle * Math.PI ) / 180;
		}

		if ( value.endsWith( 'grad' ) ) {
			return ( angle * Math.PI ) / 200;
		}

		if ( value.endsWith( 'turn' ) ) {
			return angle * Math.PI * 2;
		}

		return value.endsWith( 'rad' ) || 0 === angle ? angle : null;
	};

	const rotateMatrix = ( rotate: string ): Matrix2D | null => {
		if ( ! rotate || 'none' === rotate ) {
			return identityMatrix();
		}

		const values = rotate.trim().split( /\s+/ );
		let direction = 1;
		let angleValue = '';

		if ( 1 === values.length ) {
			[ angleValue ] = values;
		} else if ( 2 === values.length && 'z' === values[ 0 ] ) {
			angleValue = values[ 1 ];
		} else if ( 4 === values.length ) {
			const axes = values.slice( 0, 3 ).map( Number );

			if (
				axes.some( ( axis ) => Number.isNaN( axis ) ) ||
				0 !== axes[ 0 ] ||
				0 !== axes[ 1 ] ||
				0 === axes[ 2 ]
			) {
				return null;
			}

			direction = Math.sign( axes[ 2 ] );
			angleValue = values[ 3 ];
		} else {
			return null;
		}

		const angle = angleRadians( angleValue );

		if ( null === angle ) {
			return null;
		}

		const cosine = Math.cos( angle * direction );
		const sine = Math.sin( angle * direction );

		return {
			...identityMatrix(),
			a: cosine,
			b: sine,
			c: -sine,
			d: cosine,
		};
	};

	const elementTransformMatrix = (
		style: CSSStyleDeclaration
	): Matrix2D | null => {
		const transform = style.getPropertyValue( 'transform' );
		const transformValue =
			! transform || 'none' === transform
				? identityMatrix()
				: transformMatrix( transform );
		const scale = scaleMatrix( style.getPropertyValue( 'scale' ) );
		const rotate = rotateMatrix( style.getPropertyValue( 'rotate' ) );

		if ( ! transformValue || ! scale || ! rotate ) {
			return null;
		}

		// Individual transforms are applied translate, rotate, scale, then
		// the transform list. Translation and origins affect only the affine
		// offset, which is recovered from the element's viewport bounds.
		return multiplyMatrices(
			rotate,
			multiplyMatrices( scale, transformValue )
		);
	};

	const transformedAncestorMatrix = (
		element: HTMLElement
	): Matrix2D | null => {
		if ( ! canvasWindow ) {
			return null;
		}

		let current: HTMLElement | null = element;
		let transformed = false;
		const matrices: Matrix2D[] = [];

		while ( current ) {
			const style = canvasWindow.getComputedStyle( current );
			const currentMatrix = elementTransformMatrix( style );

			if ( ! currentMatrix ) {
				return null;
			}

			if (
				[ 'transform', 'translate', 'scale', 'rotate' ].some(
					( property ) => {
						const value = style.getPropertyValue( property );

						return Boolean(
							value && 'none' !== value && 'normal' !== value
						);
					}
				)
			) {
				transformed = true;
			}

			matrices.push( currentMatrix );
			current = current.parentElement;
		}

		return transformed
			? matrices.reduce(
					( matrix, currentMatrix ) =>
						multiplyMatrices( currentMatrix, matrix ),
					identityMatrix()
			  )
			: null;
	};

	const transformedContainingBlockPosition = (
		element: HTMLElement,
		viewportTop: number,
		viewportLeft: number
	): { left: number; top: number } | null => {
		if ( ! canvasWindow ) {
			return null;
		}

		const matrix = transformedAncestorMatrix( element );
		const width = element.offsetWidth;
		const height = element.offsetHeight;

		if ( ! matrix || ! width || ! height ) {
			return null;
		}

		const determinant = matrix.a * matrix.d - matrix.b * matrix.c;

		if ( ! determinant ) {
			return null;
		}

		const transformPoint = ( left: number, top: number ) => ( {
			left: matrix.a * left + matrix.c * top,
			top: matrix.b * left + matrix.d * top,
		} );
		const corners = [
			transformPoint( 0, 0 ),
			transformPoint( width, 0 ),
			transformPoint( 0, height ),
			transformPoint( width, height ),
		];
		const minLeft = Math.min( ...corners.map( ( point ) => point.left ) );
		const minTop = Math.min( ...corners.map( ( point ) => point.top ) );
		const bounds = element.getBoundingClientRect();
		const relativeLeft = viewportLeft - ( bounds.left - minLeft );
		const relativeTop = viewportTop - ( bounds.top - minTop );

		return {
			left:
				( matrix.d * relativeLeft - matrix.c * relativeTop ) /
				determinant,
			top:
				( -matrix.b * relativeLeft + matrix.a * relativeTop ) /
				determinant,
		};
	};

	const viewportToPosition = (
		viewportTop: number,
		viewportLeft: number,
		position: string
	): { left: number; top: number } => {
		if ( ! canvas || ! canvasWindow ) {
			return { left: viewportLeft, top: viewportTop };
		}

		const offsetParent = (
			'fixed' === position
				? fixedContainingBlock() || canvas.offsetParent
				: canvas.offsetParent
		) as HTMLElement | null;

		if ( 'fixed' === position && ! offsetParent ) {
			return { left: viewportLeft, top: viewportTop };
		}

		if ( offsetParent ) {
			const transformedPosition = transformedContainingBlockPosition(
				offsetParent,
				viewportTop,
				viewportLeft
			);

			if ( transformedPosition ) {
				return {
					left:
						transformedPosition.left +
						offsetParent.scrollLeft -
						offsetParent.clientLeft,
					top:
						transformedPosition.top +
						offsetParent.scrollTop -
						offsetParent.clientTop,
				};
			}

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

	const getCanvasTransformSignature = (
		element: HTMLElement,
		targetWindow: Window
	): string => {
		const signatures: string[] = [];
		let current: HTMLElement | null = element;

		while ( current ) {
			const style = targetWindow.getComputedStyle( current );

			signatures.push(
				[
					'transform',
					'transform-origin',
					'translate',
					'scale',
					'rotate',
				]
					.map( ( property ) => style.getPropertyValue( property ) )
					.join( '|' )
			);
			current = current.parentElement;
		}

		return signatures.join( '||' );
	};

	const hasCanvasTransform = ( style: CSSStyleDeclaration ): boolean =>
		[ 'transform', 'translate', 'scale', 'rotate' ].some( ( property ) => {
			const value = style.getPropertyValue( property );

			return Boolean( value && 'none' !== value && 'normal' !== value );
		} );

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

		const requestedPosition =
			isEnabled( display.position_fixed ) &&
			! ( isResponsive && canvasWindow.innerWidth <= 1024 )
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
		};

		Object.entries( properties ).forEach( ( [ property, value ] ) => {
			if ( value ) {
				setCanvasStyle( property, value, 'important' );
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
		setCanvasStyle( 'position', position, 'important' );
		const coordinates = viewportToPosition(
			viewportTop,
			viewportLeft,
			position
		);
		const canvasStyle = canvasWindow.getComputedStyle( canvas );
		canvasTransformSignature = getCanvasTransformSignature(
			canvas,
			canvasWindow
		);

		if ( hasCanvasTransform( canvasStyle ) ) {
			const positionedBounds = canvas.getBoundingClientRect();
			const transformOffset = viewportToPosition(
				positionedBounds.top,
				positionedBounds.left,
				position
			);

			coordinates.top -= transformOffset.top;
			coordinates.left -= transformOffset.left;
		}

		setCanvasStyle(
			'top',
			`${ Math.round( coordinates.top * 100 ) / 100 }px`,
			'important'
		);
		setCanvasStyle( 'bottom', 'auto', 'important' );
		setCanvasStyle(
			'left',
			`${ Math.round( coordinates.left * 100 ) / 100 }px`,
			'important'
		);
		setCanvasStyle( 'right', 'auto', 'important' );

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

			if ( iframe ) {
				if ( 'MutationObserver' in window ) {
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
			}

			targetWindow.addEventListener( 'resize', scheduleGeometry );
		}

		if ( iframe ) {
			copyPopupStyles( targetDocument );
			attachCanvasStyles( targetDocument );
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
