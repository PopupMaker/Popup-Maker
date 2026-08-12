export type Position = {
	left: number;
	top: number;
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

const multiplyMatrices = ( left: Matrix2D, right: Matrix2D ): Matrix2D => ( {
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
	element: HTMLElement,
	targetWindow: Window
): Matrix2D | null => {
	let current: HTMLElement | null = element;
	let transformed = false;
	const matrices: Matrix2D[] = [];

	while ( current ) {
		const style = targetWindow.getComputedStyle( current );
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
	targetWindow: Window,
	viewportTop: number,
	viewportLeft: number
): Position | null => {
	const matrix = transformedAncestorMatrix( element, targetWindow );
	const width = element.offsetWidth;
	const height = element.offsetHeight;

	if ( ! matrix || ! width || ! height ) {
		return null;
	}

	const determinant = matrix.a * matrix.d - matrix.b * matrix.c;

	if ( ! determinant ) {
		return null;
	}

	const transformPoint = ( left: number, top: number ): Position => ( {
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
			( matrix.d * relativeLeft - matrix.c * relativeTop ) / determinant,
		top:
			( -matrix.b * relativeLeft + matrix.a * relativeTop ) / determinant,
	};
};

const fixedContainingBlock = (
	canvas: HTMLElement,
	targetWindow: Window
): HTMLElement | null => {
	let ancestor = canvas.parentElement;

	while ( ancestor ) {
		const style = targetWindow.getComputedStyle( ancestor );
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

export const viewportToPosition = (
	canvas: HTMLElement,
	targetWindow: Window,
	viewportTop: number,
	viewportLeft: number,
	position: string
): Position => {
	const offsetParent = (
		'fixed' === position
			? fixedContainingBlock( canvas, targetWindow ) ||
			  canvas.offsetParent
			: canvas.offsetParent
	) as HTMLElement | null;

	if ( 'fixed' === position && ! offsetParent ) {
		return { left: viewportLeft, top: viewportTop };
	}

	if ( offsetParent ) {
		const transformedPosition = transformedContainingBlockPosition(
			offsetParent,
			targetWindow,
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
		left: viewportLeft + targetWindow.scrollX,
		top: viewportTop + targetWindow.scrollY,
	};
};

export const getCanvasTransformSignature = (
	element: HTMLElement,
	targetWindow: Window
): string => {
	const signatures: string[] = [];
	let current: HTMLElement | null = element;

	while ( current ) {
		const style = targetWindow.getComputedStyle( current );

		signatures.push(
			[ 'transform', 'transform-origin', 'translate', 'scale', 'rotate' ]
				.map( ( property ) => style.getPropertyValue( property ) )
				.join( '|' )
		);
		current = current.parentElement;
	}

	return signatures.join( '||' );
};

export const hasCanvasTransform = ( style: CSSStyleDeclaration ): boolean =>
	[ 'transform', 'translate', 'scale', 'rotate' ].some( ( property ) => {
		const value = style.getPropertyValue( property );

		return Boolean( value && 'none' !== value && 'normal' !== value );
	} );
