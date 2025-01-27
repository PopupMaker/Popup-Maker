import type { Updatable } from '@wordpress/core-data';
import type { BaseEntity } from '../types';

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
 * @returns {Updatable<T>} The editable entity.
 */
export function editableEntity< T extends BaseEntity< 'edit' > >(
	entity: T
): Updatable< T > {
	return Object.fromEntries(
		Object.entries( entity ).map( ( [ key, value ] ) => [
			key,
			isRenderedText( value ) ? value.raw : value,
		] )
	) as Updatable< T >;
}
