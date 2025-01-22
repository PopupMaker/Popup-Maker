import type { Context, BaseEntityRecords } from '@wordpress/core-data';
import type {
	ContextualField,
	PostStatus,
} from '@wordpress/core-data/src/entity-types/helpers';

export type EditorId = 'new' | number | undefined;

/**
 * Fields in the core post object type that we don't care about or utilize.
 */
export type OmittedPostFields =
	| 'password'
	| 'featured_media'
	| 'comment_status'
	| 'ping_status'
	| 'format'
	| 'meta'
	| 'sticky'
	| 'template'
	| 'categories'
	| 'tags'
	| 'status';

/**
 * Declare a base post model with extra trash status.
 *
 * This is the base for all of our custom post types.
 */
declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface BaseEntity< C extends Context >
			extends Omit< Post< C >, OmittedPostFields > {
			status: ContextualField< PostStatus, 'view' | 'edit', C > | 'trash';
		}
	}
}

/**
 * Base entity for any custom post type.
 */
export interface BaseEntity< C extends Context = 'view' >
	extends BaseEntityRecords.BaseEntity< C > {}
