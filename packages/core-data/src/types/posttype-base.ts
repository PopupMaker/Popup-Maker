import type { Context, BaseEntityRecords } from '@wordpress/core-data';

export type EditorId = number | undefined;

/**
 * Declare a base post model with extra trash status.
 *
 * This is the base for all of our custom post types.
 */
declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface BaseEntity< C extends Context > extends Post< C > {}
	}
}

/**
 * Base entity for any custom post type.
 */
export interface BaseEntity< C extends Context = 'view' >
	extends BaseEntityRecords.BaseEntity< C > {}
