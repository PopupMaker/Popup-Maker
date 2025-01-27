import { store as coreDataStore } from '@wordpress/core-data';
import { createRegistrySelector } from '@wordpress/data';

import type { GetRecordsHttpQuery } from '../../types';

export const createBaseSelectors = ( name: string ) => {
	const DEFAULT_QUERY = { per_page: -1 } as const;

	return {
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

		savedSuccessfully: createRegistrySelector(
			( _select ) => ( _state: any, _id?: number ) => {
				return false;
			}
		),

		isLoading: createRegistrySelector(
			( _select ) => ( _state: any, _query?: GetRecordsHttpQuery ) => {
				return false;
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
