/**
 * Distributed omit of specific properties.
 */
export type DistributiveOmit< T, K extends keyof any > = T extends any
	? Omit< T, K >
	: never;

/**
 * Create a partial that still requires at least keys K...
 */
export type AtLeast< T, K extends keyof T > = Partial< T > & Pick< T, K >;

/**
 * Returns type with all props NonNullable.
 */
export type RequiredNotNull< T > = {
	[ P in keyof T ]: NonNullable< T[ P ] >;
};

/**
 * Distributed version of RequiredNotNull.
 * Ensure keys are non nullable (undefined | null).
 */
export type Ensure< T, K extends keyof T > = T &
	RequiredNotNull< Pick< T, K > >;
