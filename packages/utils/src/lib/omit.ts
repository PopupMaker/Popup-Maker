const omit = < T extends object, K extends keyof T >(
	obj: T,
	...keys: K[]
): Omit< T, K > => {
	const result = { ...obj } as Record< string, unknown >;

	keys.forEach( ( key ) => {
		delete result[ key as string ];
	} );

	return result as Omit< T, K >;
};

export default omit;
