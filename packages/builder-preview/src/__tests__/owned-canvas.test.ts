const settings = (): BuilderOwnedCanvasSettings => ( {
	canvas_selector: 'body',
	iframe_selector: '#builder-canvas',
	popup_id: 12,
	overlay_classes: 'pum pum-overlay pum-theme-4',
	container_classes: 'pum-container size-custom',
	content_classes: 'pum-content',
	title_text: 'Popup title',
	title_classes: 'pum-title',
	size: 'custom',
	location: 'right bottom',
	custom_width: '420px',
	custom_height_auto: false,
	custom_height: '300px',
	responsive_min_width: '0%',
	responsive_max_width: '100%',
	position_top: '0',
	position_bottom: '20',
	position_left: '0',
	position_right: '30',
	position_fixed: false,
	scrollable: false,
	show_close: true,
	close_content: '&times;',
	close_classes: 'pum-close',
	close_label: 'Close',
	style_selectors: [
		'#popup-maker-site-inline-css',
		'#popup-maker-builder-preview-inline-css',
	],
} );

const rect = ( values: Partial< DOMRect > ): DOMRect => ( {
	bottom: 0,
	height: 0,
	left: 0,
	right: 0,
	top: 0,
	width: 0,
	x: 0,
	y: 0,
	toJSON: () => ( {} ),
	...values,
} );

const nextFrame = async (): Promise< void > =>
	new Promise( ( resolve ) =>
		window.requestAnimationFrame( () => resolve() )
	);

describe( 'builder-owned popup canvas', () => {
	let nativeInnerWidth: number;
	let nativeMutationObserver: typeof window.MutationObserver;
	let nativeResizeObserver: typeof window.ResizeObserver;
	let mutationCallbacks: MutationCallback[];
	let resizeCallbacks: ResizeObserverCallback[];

	beforeEach( () => {
		nativeInnerWidth = window.innerWidth;
		nativeMutationObserver = window.MutationObserver;
		nativeResizeObserver = window.ResizeObserver;
		mutationCallbacks = [];
		resizeCallbacks = [];
		window.MutationObserver = class implements MutationObserver {
			constructor( callback: MutationCallback ) {
				mutationCallbacks.push( callback );
			}

			disconnect(): void {}

			observe(): void {}

			takeRecords(): MutationRecord[] {
				return [];
			}
		};
		window.ResizeObserver = class implements ResizeObserver {
			constructor( callback: ResizeObserverCallback ) {
				resizeCallbacks.push( callback );
			}

			disconnect(): void {}

			observe(): void {}

			unobserve(): void {}
		};
	} );

	afterEach( () => {
		delete window.pumBuilderOwnedCanvas;
		document.head.innerHTML = '';
		document.body.innerHTML = '';
		document.body.className = '';
		document.body.id = '';
		document.documentElement.className = '';
		document.documentElement.id = '';
		document.documentElement.removeAttribute( 'style' );
		Object.defineProperty( window, 'innerWidth', {
			configurable: true,
			value: nativeInnerWidth,
		} );
		window.MutationObserver = nativeMutationObserver;
		window.ResizeObserver = nativeResizeObserver;
		jest.restoreAllMocks();
		jest.resetModules();
	} );

	it( 'projects the popup frame into a same-origin builder iframe', async () => {
		document.head.innerHTML =
			'<style id="popup-maker-site-inline-css">.pum-theme-4 { background: rgb(10, 20, 30); color: red; }</style>';
		document.body.innerHTML = '<iframe id="builder-canvas"></iframe>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			title_text: '<img src=x onerror=alert(1)>',
		};
		const iframe =
			document.querySelector< HTMLIFrameElement >( '#builder-canvas' );

		if ( ! iframe?.contentWindow ) {
			throw new Error( 'Iframe fixture was not created.' );
		}

		Object.defineProperty( iframe.contentWindow, 'innerHeight', {
			configurable: true,
			value: 900,
		} );
		Object.defineProperty( iframe.contentWindow, 'innerWidth', {
			configurable: true,
			value: 1000,
		} );
		iframe.contentDocument.documentElement.id = 'builder-document';
		iframe.contentDocument.body.id = 'builder-content';
		iframe.contentDocument.body.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 300,
					height: 300,
					left: 0,
					right: 420,
					top: 0,
					width: 420,
				} )
			);

		await import( '../owned-canvas' );

		const iframeDocument = iframe?.contentDocument;
		const canvas = iframeDocument?.body;

		expect( iframeDocument?.documentElement.id ).toBe( 'builder-document' );
		expect( iframeDocument?.documentElement.classList ).toContain(
			'pum-theme-4'
		);
		expect( iframeDocument?.documentElement.classList ).not.toContain(
			'pum-overlay'
		);
		expect( canvas?.id ).toBe( 'builder-content' );
		expect( canvas?.classList ).toContain( 'pum-container' );
		expect( canvas?.classList ).toContain( 'pum-content' );
		expect( canvas?.style.getPropertyValue( 'width' ) ).toBe( '420px' );
		expect( canvas?.style.getPropertyValue( 'height' ) ).toBe( '300px' );
		expect( canvas?.style.getPropertyValue( 'left' ) ).toBe( '550px' );
		expect( canvas?.style.getPropertyValue( 'top' ) ).toBe( '580px' );
		expect(
			iframeDocument?.getElementById(
				'pum-builder-copy-popup-maker-site-inline-css'
			)?.textContent
		).toContain( '.pum-theme-4' );
		expect(
			canvas?.firstElementChild?.classList.contains(
				'pum-builder-canvas-title'
			)
		).toBe( true );
		expect( canvas?.firstElementChild?.textContent ).toBe(
			'<img src=x onerror=alert(1)>'
		);
		expect( canvas?.firstElementChild?.querySelector( 'img' ) ).toBeNull();
		expect(
			canvas?.lastElementChild?.classList.contains(
				'pum-builder-canvas-close'
			)
		).toBe( true );
		expect(
			canvas?.lastElementChild?.previousElementSibling?.classList.contains(
				'pum-builder-canvas-close-anchor'
			)
		).toBe( true );
	} );

	it( 'keeps an oversized centered canvas reachable', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			location: 'center',
		};

		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Canvas fixture was not created.' );
		}

		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: window.innerHeight + 100,
					height: window.innerHeight + 100,
					left: 0,
					right: 420,
					top: 0,
					width: 420,
				} )
			);

		await import( '../owned-canvas' );

		expect(
			document.getElementById( 'pum-builder-owned-canvas' )
		).toBeNull();
		expect(
			document.querySelector( '[id^="pum-builder-copy-"]' )
		).toBeNull();
		expect( canvas.style.getPropertyValue( 'top' ) ).toBe( '10px' );
		expect( canvas.style.getPropertyValue( 'position' ) ).toBe(
			'absolute'
		);
		expect( canvas.style.getPropertyValue( 'transform' ) ).toBe( 'none' );
		expect( document.documentElement.classList ).toContain( 'pum-theme-4' );
		expect( document.body.classList ).toContain( 'pum-theme-4' );
		expect( document.documentElement.classList ).not.toContain(
			'pum-overlay'
		);
		expect( document.body.classList ).not.toContain( 'pum-overlay' );
		expect(
			document.documentElement.style.getPropertyValue( 'min-height' )
		).toBe( `${ Math.ceil( window.innerHeight + 120 ) }px` );
	} );

	it( 'keeps a right-positioned custom canvas inside a narrow viewport', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
		};

		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Canvas fixture was not created.' );
		}

		Object.defineProperty( window, 'innerWidth', {
			configurable: true,
			value: 320,
		} );
		document.documentElement.style.setProperty( 'min-height', '999px' );
		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 280,
					height: 260,
					left: -36,
					right: 284,
					top: 20,
					width: 320,
				} )
			);

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'left' ) ).toBe( '10px' );
		expect( canvas.style.getPropertyValue( 'right' ) ).toBe( 'auto' );
		expect( canvas.style.getPropertyValue( 'max-width' ) ).toBe(
			'calc(100vw - 20px)'
		);
		expect(
			document.documentElement.style.getPropertyValue( 'min-height' )
		).toBe( '999px' );
	} );

	it( 'uses viewport units for responsive limits in a nested canvas', async () => {
		document.body.innerHTML =
			'<section><main id="builder-canvas"></main></section>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			size: 'medium',
			responsive_min_width: '20%',
			responsive_max_width: '80%',
		};
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Responsive canvas fixture was not created.' );
		}

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'min-width' ) ).toBe(
			'min(20vw, calc(100vw - 20px))'
		);
		expect( canvas.style.getPropertyValue( 'max-width' ) ).toBe(
			'min(80vw, calc(100vw - 20px))'
		);
	} );

	it( 'adopts a replacement builder canvas in the same document', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
		};

		await import( '../owned-canvas' );

		const originalCanvas = document.querySelector( '#builder-canvas' );
		const replacementCanvas = document.createElement( 'main' );
		replacementCanvas.id = 'builder-canvas';
		originalCanvas?.replaceWith( replacementCanvas );

		mutationCallbacks.forEach( ( callback ) => {
			callback( [], {} as MutationObserver );
		} );
		await nextFrame();

		expect( replacementCanvas.classList ).toContain( 'pum-container' );
		expect( replacementCanvas.classList ).toContain( 'pum-content' );
		expect(
			replacementCanvas.querySelector( '.pum-builder-canvas-title' )
		).not.toBeNull();
		expect(
			replacementCanvas.querySelector( '.pum-builder-canvas-close' )
		).not.toBeNull();
	} );

	it( 'adopts a canvas inserted after its iframe is ready', async () => {
		document.body.innerHTML = '<iframe id="builder-canvas"></iframe>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			canvas_selector: '#late-canvas',
		};
		const iframe =
			document.querySelector< HTMLIFrameElement >( '#builder-canvas' );

		if ( ! iframe?.contentDocument ) {
			throw new Error( 'Iframe fixture was not created.' );
		}

		await import( '../owned-canvas' );

		const lateCanvas = iframe.contentDocument.createElement( 'main' );
		lateCanvas.id = 'late-canvas';
		lateCanvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 300,
					height: 300,
					left: 0,
					right: 420,
					top: 0,
					width: 420,
				} )
			);
		iframe.contentDocument.body.append( lateCanvas );

		mutationCallbacks.forEach( ( callback ) => {
			callback( [], {} as MutationObserver );
		} );
		await nextFrame();

		expect( lateCanvas.id ).toBe( 'late-canvas' );
		expect( lateCanvas.classList ).toContain( 'pum-container' );
		expect(
			lateCanvas.querySelector( '.pum-builder-canvas-title' )
		).not.toBeNull();
	} );

	it( 'positions a nested canvas in viewport coordinates', async () => {
		document.body.innerHTML =
			'<section id="canvas-parent"><main id="builder-canvas"></main></section>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			location: 'left top',
			position_left: '20',
			position_top: '10',
		};
		const parent =
			document.querySelector< HTMLElement >( '#canvas-parent' );
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! parent || ! canvas ) {
			throw new Error( 'Nested canvas fixture was not created.' );
		}

		parent.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue( rect( { left: 200, top: 100 } ) );
		Object.defineProperties( parent, {
			clientLeft: { configurable: true, value: 2 },
			clientTop: { configurable: true, value: 2 },
			scrollLeft: { configurable: true, value: 10 },
			scrollTop: { configurable: true, value: 20 },
		} );
		Object.defineProperty( canvas, 'offsetParent', {
			configurable: true,
			value: parent,
		} );
		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 200,
					height: 200,
					left: 0,
					right: 300,
					top: 0,
					width: 300,
				} )
			);

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'top' ) ).toBe( '-72px' );
		expect( canvas.style.getPropertyValue( 'left' ) ).toBe( '-172px' );
	} );

	it( 'keeps an outside close button inside the viewport', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			location: 'right top',
			position_right: '0',
			position_top: '0',
		};
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Canvas fixture was not created.' );
		}

		Object.defineProperty( window, 'innerWidth', {
			configurable: true,
			value: 320,
		} );
		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 200,
					height: 200,
					left: 0,
					right: 250,
					top: 0,
					width: 250,
				} )
			);
		jest.spyOn(
			HTMLElement.prototype,
			'getBoundingClientRect'
		).mockImplementation( function () {
			return this.classList.contains( 'pum-builder-canvas-close' )
				? rect( {
						bottom: 20,
						height: 40,
						left: 230,
						right: 280,
						top: -20,
						width: 50,
				  } )
				: rect( {} );
		} );

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'top' ) ).toBe( '30px' );
		expect( canvas.style.getPropertyValue( 'left' ) ).toBe( '30px' );
	} );

	it( 'keeps a top-positioned canvas below the admin bar', async () => {
		document.body.className = 'admin-bar';
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			location: 'left top',
			position_left: '10',
			position_top: '0',
			show_close: false,
		};
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Admin-bar canvas fixture was not created.' );
		}

		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 200,
					height: 200,
					right: 300,
					width: 300,
				} )
			);

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'top' ) ).toBe( '42px' );
	} );

	it( 'reinstalls iframe styles after an in-place head replacement', async () => {
		document.head.innerHTML =
			'<style id="popup-maker-site-inline-css">.pum-theme-4 { color: red; }</style>';
		document.body.innerHTML = '<iframe id="builder-canvas"></iframe>';
		window.pumBuilderOwnedCanvas = settings();
		const iframe =
			document.querySelector< HTMLIFrameElement >( '#builder-canvas' );

		if ( ! iframe?.contentDocument ) {
			throw new Error( 'Iframe fixture was not created.' );
		}

		await import( '../owned-canvas' );

		iframe.contentDocument.head.innerHTML = '';
		mutationCallbacks.forEach( ( callback ) => {
			callback( [], {} as MutationObserver );
		} );
		await nextFrame();

		expect(
			iframe.contentDocument.getElementById(
				'pum-builder-copy-popup-maker-site-inline-css'
			)
		).not.toBeNull();
		expect(
			iframe.contentDocument.getElementById( 'pum-builder-owned-canvas' )
		).not.toBeNull();
	} );

	it( 'keeps synthetic controls anchored while content scrolls', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
			scrollable: true,
		};
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Scrollable canvas fixture was not created.' );
		}

		await import( '../owned-canvas' );
		canvas.scrollTop = 80;
		canvas.dispatchEvent( new Event( 'scroll' ) );

		expect(
			canvas.style.getPropertyValue( '--pum-builder-canvas-scroll-y' )
		).toBe( '80px' );
	} );

	it( 'coalesces repeated resize observations into one frame', async () => {
		document.body.innerHTML = '<main id="builder-canvas"></main>';
		window.pumBuilderOwnedCanvas = {
			...settings(),
			iframe_selector: undefined,
			canvas_selector: '#builder-canvas',
		};
		const canvas =
			document.querySelector< HTMLElement >( '#builder-canvas' );

		if ( ! canvas ) {
			throw new Error( 'Canvas fixture was not created.' );
		}

		canvas.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue(
				rect( {
					bottom: 300,
					height: 300,
					left: 0,
					right: 420,
					top: 0,
					width: 420,
				} )
			);

		await import( '../owned-canvas' );
		expect( canvas.getBoundingClientRect ).toHaveBeenCalledTimes( 1 );

		resizeCallbacks[ 0 ]( [], {} as ResizeObserver );
		resizeCallbacks[ 0 ]( [], {} as ResizeObserver );
		resizeCallbacks[ 0 ]( [], {} as ResizeObserver );

		expect( canvas.getBoundingClientRect ).toHaveBeenCalledTimes( 1 );
		await nextFrame();
		expect( canvas.getBoundingClientRect ).toHaveBeenCalledTimes( 2 );
	} );
} );
