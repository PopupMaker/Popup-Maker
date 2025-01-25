import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreDataStore } from '@wordpress/core-data';

import { storeHasNotices } from './notice-types';

import type {
	EntityRecord,
	Updatable,
} from '@wordpress/core-data/src/entity-types';

import type {
	ThunkContext,
	ThunkAction,
	StoreDescriptor,
} from './entity-types';

type ActionMap< T extends EntityRecord< 'edit' > > = ReturnType<
	typeof createBaseActions< T >
>;

type RemapKeys< T, M extends Partial< Record< keyof T, string > > > = {
	[ K in keyof T as K extends keyof M ? never : K ]: T[ K ];
} & {
	[ P in keyof M as NonNullable< M[ P ] > ]: P extends keyof T
		? T[ P ]
		: never;
};

const createBaseActions = <
	T extends EntityRecord< 'edit' > = EntityRecord< 'edit' >,
>(
	name: string
) => {
	const DEFAULT_QUERY = { per_page: -1, context: 'view' } as const;
	const NOTICE_CONTEXT = `${ name }-editor`;

	/**
	 * Shorthand type for editable string object fields like title, content, etc.
	 */
	type Editable = Partial< Updatable< T > >;

	/**
	 * Helper to handle notices with fallback to core notices
	 */
	const handleNotice = async (
		context: ThunkContext< StoreDescriptor >,
		options: {
			status: 'success' | 'error';
			content: string;
		}
	) => {
		if ( storeHasNotices( context ) ) {
			context.dispatch.createNotice( options.status, options.content, {
				// context: NOTICE_CONTEXT,
			} );
		} else {
			// Fallback to core notices
			await context.registry
				.dispatch( noticesStore )
				.createNotice( options.status, options.content, {
					context: NOTICE_CONTEXT,
				} );
		}
	};

	const create =
		(
			entity: Editable,
			validate?: ( entity: Editable ) => true | { message: string }
		): ThunkAction< T | boolean > =>
		async ( context ) => {
			const { registry } = context;

			try {
				if ( validate ) {
					const validation = validate( entity );
					if ( validation !== true ) {
						await handleNotice( context, {
							status: 'error',
							content: validation.message,
						} );
						return false;
					}
				}

				const result: boolean = await registry
					.dispatch( coreDataStore )
					.saveEntityRecord( 'postType', name, entity );

				if ( result ) {
					await handleNotice( context, {
						status: 'success',
						content: __( 'Entity created successfully' ),
					} );
				}

				return result;
			} catch ( error ) {
				await handleNotice( context, {
					status: 'error',
					content:
						error instanceof Error
							? error.message
							: __( 'Failed to create entity' ),
				} );
				throw error;
			}
		};

	const update =
		(
			entity: Editable,
			validate?: ( entity: Editable ) => true | { message: string }
		): ThunkAction< T | boolean > =>
		async ( context ) => {
			const { registry } = context;

			try {
				if ( validate ) {
					const validation = validate( entity );
					if ( validation !== true ) {
						await handleNotice( context, {
							status: 'error',
							content: validation.message,
						} );
						return false;
					}
				}

				const isUpdate = 'id' in entity && entity.id;

				const result: boolean = await registry
					.dispatch( coreDataStore )
					.saveEntityRecord( 'postType', name, entity );

				if ( result ) {
					await handleNotice( context, {
						status: 'success',
						content: isUpdate
							? __( 'Entity updated successfully' )
							: __( 'Entity created successfully' ),
					} );
				}

				return result;
			} catch ( error ) {
				await handleNotice( context, {
					status: 'error',
					content:
						error instanceof Error
							? error.message
							: __( 'Failed to save entity' ),
				} );
				throw error;
			}
		};

	const edit =
		( id: number, edits: Editable ): ThunkAction =>
		async ( context ) => {
			const { registry } = context;
			try {
				await registry
					.dispatch( coreDataStore )
					.editEntityRecord( 'postType', name, id, edits );
			} catch ( error ) {
				console.error( 'Edit failed:', error );
				await handleNotice( context, {
					status: 'error',
					content: __( 'Failed to edit entity' ),
				} );
			}
		};

	const save =
		(
			id: number,
			validate?: ( entity: Editable ) => true | { message: string }
		): ThunkAction< boolean > =>
		async ( context ) => {
			const { registry } = context;

			try {
				const entity = registry
					.select( coreDataStore )
					.getEditedEntityRecord( 'postType', name, id ) as
					| Editable
					| false;

				if ( entity && validate ) {
					const validation = validate( entity );
					if ( validation !== true ) {
						await handleNotice( context, {
							status: 'error',
							content: validation.message,
						} );

						return false;
					}
				}

				const result: boolean = await registry
					.dispatch( coreDataStore )
					.saveEntityRecord( 'postType', name, id );

				if ( result ) {
					await handleNotice( context, {
						status: 'success',
						content: __( 'Entity saved successfully' ),
					} );
				}

				return result;
			} catch ( error ) {
				console.error( 'Save failed:', error );
				await handleNotice( context, {
					status: 'error',
					content: __( 'Failed to save entity' ),
				} );
				throw error;
			}
		};

	const deleteEntity =
		( id: number, force = false ): ThunkAction =>
		async ( context ) => {
			const { registry } = context;
			try {
				await registry
					.dispatch( coreDataStore )
					.deleteEntityRecord( 'postType', name, id, { force } );

				await handleNotice( context, {
					status: 'success',
					content: __( 'Entity deleted successfully' ),
				} );
			} catch ( error ) {
				console.error( 'Delete failed:', error );
				await handleNotice( context, {
					status: 'error',
					content: __( 'Failed to delete entity' ),
				} );
				throw error;
			}
		};

	const invalidateList =
		( query?: any ): ThunkAction =>
		async ( context ) => {
			const { registry } = context;

			await registry
				.dispatch( coreDataStore )
				// @ts-expect-error
				.invalidateResolution( 'getEntityRecords', [
					'postType',
					name,
					query ?? DEFAULT_QUERY,
				] );
		};

	const resetEdits =
		( id: number ): ThunkAction =>
		async ( { registry } ) => {
			await registry
				.dispatch( coreDataStore )
				.editEntityRecord( 'postType', name, id, {} );
		};

	const undo =
		(): ThunkAction =>
		async ( { registry } ) =>
			await registry.dispatch( coreDataStore ).undo();

	const redo =
		(): ThunkAction =>
		async ( { registry } ) =>
			await registry.dispatch( coreDataStore ).redo();

	return {
		create,
		update,
		edit,
		save,
		delete: deleteEntity,
		invalidateList,
		resetEdits,
		undo,
		redo,
	};
};

export const createPostTypeActions = <
	T extends EntityRecord< 'edit' >,
	M extends Partial< Record< keyof ActionMap< T >, string > > = {},
>(
	name: string,
	mapping?: M
): RemapKeys< ActionMap< T >, M > => {
	const baseActions = createBaseActions< T >( name );

	if ( ! mapping ) {
		return baseActions as RemapKeys< ActionMap< T >, M >;
	}

	const remappedActions = { ...baseActions };

	for ( const [ key, newKey ] of Object.entries( mapping ) ) {
		if ( key in baseActions && newKey ) {
			Object.defineProperty(
				remappedActions,
				newKey,
				Object.getOwnPropertyDescriptor( baseActions, key ) || {}
			);
			delete remappedActions[ key as keyof typeof baseActions ];
		}
	}

	return remappedActions as RemapKeys< ActionMap< T >, M >;
};

export default createPostTypeActions;
