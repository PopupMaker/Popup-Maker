import type { AnyConfig, ReduxStoreConfig } from '@wordpress/data/src/types';

/**
 * Helper type to wrap return type of a function in Promise
 */
export type PromiseReturnType< T > = T extends ( ...args: any[] ) => any
	? ( ...args: Parameters< T > ) => Promise< ReturnType< T > >
	: never;

/**
 * Helper type to wrap all methods' return types in Promise
 */
export type PromiseReturnMethods< T > = {
	[ K in keyof T ]: T[ K ] extends ( ...args: any[] ) => any
		? PromiseReturnType< T[ K ] >
		: T[ K ];
};

/**
 * Get the state type from a redux store config.
 */
export type StateOf< Config extends AnyConfig > =
	Config extends ReduxStoreConfig< infer State, any, any > ? State : never;

/**
 * Omit the first argument from a function.
 */
export type OmitFirstArg< F > = F extends (
	x: any,
	...args: infer P
) => infer R
	? ( ...args: P ) => R
	: never;

/**
 * Omit the first argument from an object's methods.
 */
export type OmitFirstArgs< O > = {
	[ K in keyof O ]: OmitFirstArg< O[ K ] >;
};

/**
 * Remove the return type from a function.
 */
export type RemoveReturnType< F > = F extends ( ...args: infer P ) => any
	? ( ...args: P ) => void
	: never;

/**
 * Remove the return type from an object's methods.
 */
export type RemoveReturnTypes< O > = {
	[ K in keyof O ]: RemoveReturnType< O[ K ] >;
};
