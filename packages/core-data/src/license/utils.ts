export const getResourcePath = ( subpath = '' ) => {
	if ( subpath ) {
		subpath = `/${ subpath }`;
	}

	return `popup-paker/v2/license${ subpath }`;
};
