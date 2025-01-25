import { store as coreDataStore } from '@wordpress/core-data';
import { createRegistrySelector } from '@wordpress/data';

import type { BaseEntityRecords } from '@wordpress/core-data';

type SelectorMap< T extends BaseEntityRecords.BaseEntity< 'edit' > > =
	ReturnType< typeof createBaseSelectors< T > >;

type RemapKeys< T, M extends Partial< Record< keyof T, string > > > = {
	[ K in keyof T as K extends keyof M ? never : K ]: T[ K ];
} & {
	[ P in keyof M as NonNullable< M[ P ] > ]: P extends keyof T
		? T[ P ]
		: never;
};

const createBaseSelectors = <
	T extends BaseEntityRecords.BaseEntity< 'edit' >,
>(
	name: string
) => {
	const DEFAULT_QUERY = { per_page: -1 } as const;

	return {
		getAll: createRegistrySelector( ( select ) => ( _state: any ) => {
			const records = select( coreDataStore ).getEntityRecords< T >(
				'postType',
				name,
				DEFAULT_QUERY
			);
			return records;
		} ),

		getById: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const record = select( coreDataStore ).getEntityRecord< T >(
					'postType',
					name,
					id
				);
				return record;
			}
		),

		getEdited: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const record = select( coreDataStore ).getEditedEntityRecord(
					'postType',
					name,
					id
				);
				return record;
			}
		),

		isSaving: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const saving = select( coreDataStore ).isSavingEntityRecord(
					'postType',
					name,
					id
				);
				return saving;
			}
		),

		isDeleting: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const deleting = select( coreDataStore ).isDeletingEntityRecord(
					'postType',
					name,
					id
				);
				return deleting;
			}
		),

		hasEdits: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const hasEdits = select(
					coreDataStore
				).hasEditsForEntityRecord( 'postType', name, id );
				return hasEdits;
			}
		),

		getLastSaveError: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const error = select( coreDataStore ).getLastEntitySaveError(
					'postType',
					name,
					id
				);
				return error;
			}
		),

		hasUndo: createRegistrySelector( ( select ) => ( _state: any ) => {
			const hasUndo = select( coreDataStore ).hasUndo();
			return hasUndo;
		} ),

		hasRedo: createRegistrySelector( ( select ) => ( _state: any ) => {
			const hasRedo = select( coreDataStore ).hasRedo();
			return hasRedo;
		} ),

		isAutosaving: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const autosaving = select(
					coreDataStore
				).isAutosavingEntityRecord( 'postType', name, id );
				return autosaving;
			}
		),

		getRawEntityRecord: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const record = select( coreDataStore ).getRawEntityRecord(
					'postType',
					name,
					id
				);
				return record;
			}
		),

		isFetchingEntity: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				// @ts-expect-error
				const fetching = select( coreDataStore ).isResolving(
					'getEntityRecord',
					[ 'postType', name, id ]
				) as boolean;

				return fetching;
			}
		),

		isFetchingEntities: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) => {
					// @ts-expect-error
					const fetching = select( coreDataStore ).isResolving(
						'getEntityRecords',
						[ 'postType', name, query ]
					) as boolean;

					return fetching;
				}
		),

		hasFetchedEntity: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const hasFetched = select( coreDataStore )
					// @ts-expect-error
					.hasFinishedResolution( 'getEntityRecord', [
						'postType',
						name,
						id,
					] ) as boolean;

				return hasFetched;
			}
		),

		hasFetchedEntities: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) => {
					const hasFetched = select( coreDataStore )
						// @ts-expect-error
						.hasFinishedResolution( 'getEntityRecords', [
							'postType',
							name,
							query,
						] ) as boolean;

					return hasFetched;
				}
		),

		hasStartedEntityFetch: createRegistrySelector(
			( select ) => ( _state: any, id: number ) => {
				const hasStarted = select( coreDataStore )
					// @ts-expect-error
					.hasStartedResolution( 'getEntityRecord', [
						'postType',
						name,
						id,
					] ) as boolean;

				return hasStarted;
			}
		),

		hasStartedEntitiesFetch: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) => {
					const hasStarted = select( coreDataStore )
						// @ts-expect-error
						.hasStartedResolution( 'getEntityRecords', [
							'postType',
							name,
							query,
						] ) as boolean;

					return hasStarted;
				}
		),
	};
};

export const createPostTypeSelectors = <
	T extends BaseEntityRecords.BaseEntity< 'edit' >,
	M extends Partial< Record< keyof SelectorMap< T >, string > > = {},
>(
	name: string,
	mapping?: M
): RemapKeys< SelectorMap< T >, M > => {
	const baseSelectors = createBaseSelectors< T >( name );

	if ( ! mapping ) {
		return baseSelectors as RemapKeys< SelectorMap< T >, M >;
	}

	const remappedSelectors = { ...baseSelectors };

	for ( const [ key, newKey ] of Object.entries( mapping ) ) {
		if ( key in baseSelectors && newKey ) {
			Object.defineProperty(
				remappedSelectors,
				newKey,
				Object.getOwnPropertyDescriptor( baseSelectors, key ) || {}
			);
			delete remappedSelectors[ key as keyof typeof baseSelectors ];
		}
	}

	return remappedSelectors as RemapKeys< SelectorMap< T >, M >;
};

export default createPostTypeSelectors;
