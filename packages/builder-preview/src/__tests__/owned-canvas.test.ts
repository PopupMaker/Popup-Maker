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

describe( 'builder-owned popup canvas', () => {
	let nativeInnerWidth: number;
	let nativeMutationObserver: typeof window.MutationObserver;
	let mutationCallbacks: MutationCallback[];

	beforeEach( () => {
		nativeInnerWidth = window.innerWidth;
		nativeMutationObserver = window.MutationObserver;
		mutationCallbacks = [];
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
	} );

	afterEach( () => {
		delete window.pumBuilderOwnedCanvas;
		document.head.innerHTML = '';
		document.body.innerHTML = '';
		document.documentElement.removeAttribute( 'style' );
		Object.defineProperty( window, 'innerWidth', {
			configurable: true,
			value: nativeInnerWidth,
		} );
		window.MutationObserver = nativeMutationObserver;
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
		iframe.contentDocument.body.getBoundingClientRect = jest
			.fn< () => DOMRect >()
			.mockReturnValue( {
				height: 300,
				top: 580,
			} as DOMRect );

		await import( '../owned-canvas' );

		const iframeDocument = iframe?.contentDocument;
		const canvas = iframeDocument?.body;

		expect( iframeDocument?.documentElement.id ).toBe( 'pum-12' );
		expect( iframeDocument?.documentElement.classList ).toContain(
			'pum-theme-4'
		);
		expect( canvas?.id ).toBe( 'popmake-12' );
		expect( canvas?.classList ).toContain( 'pum-container' );
		expect( canvas?.classList ).toContain( 'pum-content' );
		expect( canvas?.style.getPropertyValue( 'width' ) ).toBe( '420px' );
		expect( canvas?.style.getPropertyValue( 'height' ) ).toBe( '300px' );
		expect( canvas?.style.getPropertyValue( 'right' ) ).toBe( '30px' );
		expect( canvas?.style.getPropertyValue( 'bottom' ) ).toBe( '20px' );
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
			.mockReturnValue( {
				height: window.innerHeight + 100,
				top: -50,
			} as DOMRect );

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
		expect( canvas.style.getPropertyValue( 'transform' ) ).toBe(
			'translateX(-50%)'
		);
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
			.mockReturnValue( {
				bottom: 280,
				height: 260,
				left: -36,
				right: 284,
				top: 20,
				width: 320,
			} as DOMRect );

		await import( '../owned-canvas' );

		expect( canvas.style.getPropertyValue( 'left' ) ).toBe( '10px' );
		expect( canvas.style.getPropertyValue( 'right' ) ).toBe( 'auto' );
		expect( canvas.style.getPropertyValue( 'max-width' ) ).toBe(
			'calc(100% - 20px)'
		);
		expect(
			document.documentElement.style.getPropertyValue( 'min-height' )
		).toBe( '' );
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

		expect( replacementCanvas.classList ).toContain( 'pum-container' );
		expect( replacementCanvas.classList ).toContain( 'pum-content' );
		expect(
			replacementCanvas.querySelector( '.pum-builder-canvas-title' )
		).not.toBeNull();
		expect(
			replacementCanvas.querySelector( '.pum-builder-canvas-close' )
		).not.toBeNull();
	} );
} );
