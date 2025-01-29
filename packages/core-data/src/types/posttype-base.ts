import type { Context, BaseEntityRecords } from '@wordpress/core-data';

/**
 * The ID of the editor.
 *
 * TODO REVIEW: This maybe should not allow undefined, let those who use this type handle it.
 */
export type EditorId = number | undefined;

/**
 * The query for records.
 */
export type GetRecordsHttpQuery = Record< string, any >;

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
