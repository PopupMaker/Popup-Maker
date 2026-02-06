const omit = < T extends object, K extends keyof T >(
	obj: T,
	...keys: K[]
): Omit< T, K > => {
	const r: any = {};
	let length = keys.length;

	while ( length-- ) {
		const key = keys[ length ];

		r[ key ] = obj[ key ];
	}

	return r;
};

export default omit;
