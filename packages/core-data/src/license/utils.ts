export const apiPath = ( subpath = '' ) => {
	if ( subpath ) {
		subpath = `/${ subpath }`;
	}

	return `popup-paker/v2/license${ subpath }`;
};
