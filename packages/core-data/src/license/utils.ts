export const apiPath = ( subpath = '' ) => {
	if ( subpath ) {
		subpath = `/${ subpath }`;
	}

	return `popup-maker/v2/license${ subpath }`;
};
