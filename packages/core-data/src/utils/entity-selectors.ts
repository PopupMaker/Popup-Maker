import { store as coreDataStore } from '@wordpress/core-data';
import { createRegistrySelector } from '@wordpress/data';

import type {
	Context,
	BaseEntityRecords as WPBaseEntityRecords,
} from '@wordpress/core-data';

export const createPostTypeSelectors = <
	T extends WPBaseEntityRecords.BaseEntity< Context >,
>(
	name: string
) => {
	const DEFAULT_QUERY = { per_page: -1 } as const;

	return {
		getAll: createRegistrySelector(
			( select ) => async ( _state: any ) =>
				select( coreDataStore ).getEntityRecords< T >(
					'postType',
					name,
					DEFAULT_QUERY
				)
		),

		getById: createRegistrySelector(
			( select ) => async ( _state: any, id: number ) =>
				select( coreDataStore ).getEntityRecord< T >(
					'postType',
					name,
					id
				)
		),

		getEdited: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).getEditedEntityRecord(
					'postType',
					name,
					id
				)
		),

		isSaving: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).isSavingEntityRecord(
					'postType',
					name,
					id
				)
		),

		isDeleting: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).isDeletingEntityRecord(
					'postType',
					name,
					id
				)
		),

		hasEdits: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).hasEditsForEntityRecord(
					'postType',
					name,
					id
				)
		),

		getLastSaveError: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).getLastEntitySaveError(
					'postType',
					name,
					id
				)
		),

		hasUndo: createRegistrySelector(
			( select ) => ( _state: any ) => select( coreDataStore ).hasUndo()
		),

		hasRedo: createRegistrySelector(
			( select ) => ( _state: any ) => select( coreDataStore ).hasRedo()
		),

		isAutosaving: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).isAutosavingEntityRecord(
					'postType',
					name,
					id
				)
		),

		getRawEntityRecord: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				select( coreDataStore ).getRawEntityRecord(
					'postType',
					name,
					id
				)
		),

		isFetchingEntity: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				// @ts-expect-error
				select( coreDataStore ).isResolving( 'getEntityRecord', [
					'postType',
					name,
					id,
				] )
		),

		isFetchingEntities: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) =>
					// @ts-expect-error
					select( coreDataStore ).isResolving( 'getEntityRecords', [
						'postType',
						name,
						query,
					] )
		),

		hasFetchedEntity: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				// @ts-expect-error
				select( coreDataStore ).hasFinishedResolution(
					'getEntityRecord',
					[ 'postType', name, id ]
				)
		),

		hasFetchedEntities: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) =>
					// @ts-expect-error
					select( coreDataStore ).hasFinishedResolution(
						'getEntityRecords',
						[ 'postType', name, query ]
					)
		),

		hasStartedEntityFetch: createRegistrySelector(
			( select ) => ( _state: any, id: number ) =>
				// @ts-expect-error
				select( coreDataStore ).hasStartedResolution(
					'getEntityRecord',
					[ 'postType', name, id ]
				)
		),

		hasStartedEntitiesFetch: createRegistrySelector(
			( select ) =>
				( _state: any, query = DEFAULT_QUERY ) =>
					// @ts-expect-error
					select( coreDataStore ).hasStartedResolution(
						'getEntityRecords',
						[ 'postType', name, query ]
					)
		),
	};
};

export default createPostTypeSelectors;
