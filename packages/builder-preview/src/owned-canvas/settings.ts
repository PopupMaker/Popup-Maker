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

export const isEnabled = ( value: boolean | string ): boolean =>
	true === value || '1' === value;

export const numericOffset = ( value: string ): number => {
	const offset = parseFloat( value );

	return Number.isNaN( offset ) ? 0 : offset;
};

export const selectorAttributeFilter = (
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

export const viewportRelativeLength = (
	value: string,
	viewportUnit = 'vw'
): string => {
	const percentage = value.trim().match( /^(\d+(?:\.\d+)?)%$/ );

	return percentage ? `${ percentage[ 1 ] }${ viewportUnit }` : value;
};

export const responsiveWidth = (
	size: string,
	viewportWidth: number
): string => {
	if ( viewportWidth < 1024 ) {
		return '95vw';
	}

	return responsiveWidths[ size ] || '95vw';
};
