import {
	isEnabled,
	numericOffset,
	responsiveWidth,
	viewportRelativeLength,
} from './settings';
import {
	getCanvasTransformSignature,
	hasCanvasTransform,
	viewportToPosition,
} from './transforms';

type SetCanvasStyle = (
	property: string,
	value: string,
	priority?: string
) => void;

type GeometryOptions = {
	canvas: HTMLElement;
	display: BuilderOwnedCanvasSettings;
	restoreRootMinHeight: () => void;
	setCanvasStyle: SetCanvasStyle;
	setRootMinHeight: ( height: string ) => void;
	targetWindow: Window;
};

export const mirrorPopupGeometry = ( {
	canvas,
	display,
	restoreRootMinHeight,
	setCanvasStyle,
	setRootMinHeight,
	targetWindow,
}: GeometryOptions ): string => {
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
		width = responsiveWidth( display.size, targetWindow.innerWidth );
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
		! ( isResponsive && targetWindow.innerWidth <= 1024 )
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
		visualRight = Math.max( visualRight, closeBounds.right - bounds.left );
		visualBottom = Math.max(
			visualBottom,
			closeBounds.bottom - bounds.top
		);
		visualLeft = Math.min( visualLeft, closeBounds.left - bounds.left );
	}

	let viewportTop = ( targetWindow.innerHeight - bounds.height ) / 2;
	let viewportLeft = ( targetWindow.innerWidth - bounds.width ) / 2;

	if ( 'top' === vertical ) {
		viewportTop = numericOffset( display.position_top );
	} else if ( 'bottom' === vertical ) {
		viewportTop =
			targetWindow.innerHeight -
			bounds.height -
			numericOffset( display.position_bottom );
	}

	if ( 'left' === horizontal ) {
		viewportLeft = numericOffset( display.position_left );
	} else if ( 'right' === horizontal ) {
		viewportLeft =
			targetWindow.innerWidth -
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
	const maxTop = targetWindow.innerHeight - edgeGap - visualBottom;
	const minLeft = edgeGap - visualLeft;
	const maxLeft = targetWindow.innerWidth - edgeGap - visualRight;
	const isOversized =
		visualBottom - visualTop + topEdgeGap + edgeGap >
		targetWindow.innerHeight;

	viewportTop = Math.max( minTop, Math.min( viewportTop, maxTop ) );
	viewportLeft = Math.max( minLeft, Math.min( viewportLeft, maxLeft ) );

	const position = isOversized ? 'absolute' : requestedPosition;
	setCanvasStyle( 'position', position, 'important' );
	const coordinates = viewportToPosition(
		canvas,
		targetWindow,
		viewportTop,
		viewportLeft,
		position
	);
	const canvasStyle = targetWindow.getComputedStyle( canvas );
	const transformSignature = getCanvasTransformSignature(
		canvas,
		targetWindow
	);

	if ( hasCanvasTransform( canvasStyle ) ) {
		const positionedBounds = canvas.getBoundingClientRect();
		const transformOffset = viewportToPosition(
			canvas,
			targetWindow,
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
				targetWindow.scrollY + viewportTop + visualBottom + edgeGap
			) }px`
		);
	} else {
		restoreRootMinHeight();
	}

	return transformSignature;
};
