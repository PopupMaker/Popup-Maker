const omit = < T extends object, K extends keyof T >(
	obj: T,
	...keys: K[]
): Omit< T, K > => {
	const r = { ...obj } as any;
	for ( const key of keys ) {
		delete r[ key ];
	}
	return r;
};

export default omit;
