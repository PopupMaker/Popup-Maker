import type { Updatable } from '@wordpress/core-data';
import type { BaseEntity } from '../types';
import type { CtaEditorId } from './types';

/**
 * Resolve the record key edits are stored under for a given editor id.
 *
 * Unsaved drafts (`'new'`) are keyed under `0` so the number-keyed edit
 * maps (editedEntities, editHistory, …) work unchanged.
 *
 * @param {CtaEditorId} editorId The editor id.
 * @return {number} The record key.
 */
export function editorRecordKey( editorId: CtaEditorId ): number {
	return typeof editorId === 'number' ? editorId : 0;
}

// Simple type guard for RenderedText fields
export function isRenderedText(
	value: any
): value is { raw: string; rendered: string } {
	return value && typeof value === 'object' && 'raw' in value;
}

/**
 * Convert an entity to an editable entity.
 *
 * @param {T} entity The entity to convert.
 * @return {Updatable<T>} The editable entity.
 */
export function editableEntity< T extends BaseEntity< 'edit' > >( {
	_links,
	...entity
}: T & { _links?: any } ): Updatable< T > {
	return Object.fromEntries(
		Object.entries( entity ).map( ( [ key, value ] ) => [
			key,
			isRenderedText( value ) ? value.raw : value,
		] )
	) as Updatable< T >;
}
