import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreDataStore, type Post } from '@wordpress/core-data';

import { storeHasNotices } from './notice-types';

import type { Updatable } from '@wordpress/core-data/src/entity-types';

import type {
	ThunkContext,
	ThunkAction,
	StoreDescriptor,
} from './entity-types';

import type { WPNotice } from './notice-types';

type ActionMap< T extends Post< 'edit' > > = ReturnType<
	typeof createBaseActions< T >
>;

type RemapKeys< T, M extends Partial< Record< keyof T, string > > > = {
	[ K in keyof T as K extends keyof M ? never : K ]: T[ K ];
} & {
	[ P in keyof M as NonNullable< M[ P ] > ]: P extends keyof T
		? T[ P ]
		: never;
};

const createBaseActions = < T extends Post< 'edit' > = Post< 'edit' > >(
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
			status: NonNullable< WPNotice[ 'status' ] >;
			content: string;
			id: string;
		} & Partial< Omit< WPNotice, 'status' | 'content' | 'id' > >
	) => {
		const noticeOptions = {
			isDismissible: true,
			...options,
		};

		if ( storeHasNotices( context ) ) {
			context.dispatch.createNotice(
				noticeOptions.status,
				noticeOptions.content,
				noticeOptions
			);
		} else {
			// Fallback to core notices
			await context.registry
				.dispatch( noticesStore )
				.createNotice( noticeOptions.status, noticeOptions.content, {
					context: NOTICE_CONTEXT,
					...noticeOptions,
				} );
		}
	};

	/**
	 * Create a new entity record. Values sent to the server immediately.
	 *
	 * @param {Editable} entity The entity to create.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<Editable | boolean>} The created entity or false if validation fails.
	 */
	const createRecord =
		(
			entity: Omit< Editable, 'id' >,
			validate?: (
				entity: Omit< Editable, 'id' >
			) => true | { message: string }
		): ThunkAction< Editable | false > =>
		async ( context ) => {
			const { registry } = context;

			try {
				if ( validate ) {
					const validation = validate( entity );
					if ( validation !== true ) {
						await handleNotice( context, {
							status: 'error',
							content: validation.message,
							id: `${ NOTICE_CONTEXT }-validation`,
						} );
						return false;
					}
				}

				const result: Editable = await registry
					.dispatch( coreDataStore )
					.saveEntityRecord( 'postType', name, entity );

				if ( result ) {
					await handleNotice( context, {
						status: 'success',
						content: __( 'Entity created successfully' ),
						id: `${ NOTICE_CONTEXT }-create-success`,
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
					id: `${ NOTICE_CONTEXT }-create-error`,
				} );
				throw error;
			}
		};

	/**
	 * Update an existing entity record. Values sent to the server immediately.
	 *
	 * @param {Editable} entity The entity to update.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<T | boolean>} The updated entity or false if validation fails.
	 */
	const updateRecord =
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
							id: `${ NOTICE_CONTEXT }-validation-${ Date.now() }`,
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
						id: `${ NOTICE_CONTEXT }-${
							isUpdate ? 'update' : 'create'
						}-success-${ Date.now() }`,
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
					id: `${ NOTICE_CONTEXT }-save-error-${ Date.now() }`,
				} );
				throw error;
			}
		};

	/**
	 * Delete an existing entity record.
	 *
	 * @param {number} id The entity ID.
	 * @param {boolean} force Whether to force the deletion.
	 * @returns {Promise<boolean>} Whether the deletion was successful.
	 */
	const deleteRecord =
		( id: number, force: boolean = false ): ThunkAction =>
		async ( context ) => {
			const { registry } = context;
			try {
				await registry
					.dispatch( coreDataStore )
					.deleteEntityRecord( 'postType', name, id, {
						force,
					} );

				await handleNotice( context, {
					status: 'success',
					content: __( 'Entity deleted successfully' ),
					id: `${ NOTICE_CONTEXT }-delete-success`,
				} );
			} catch ( error ) {
				console.error( 'Delete failed:', error );
				await handleNotice( context, {
					status: 'error',
					content: __( 'Failed to delete entity' ),
					id: `${ NOTICE_CONTEXT }-delete-error`,
				} );
				throw error;
			}
		};

	/**
	 * Edit an existing entity record. Values are not sent to the server until save.
	 *
	 * @param {number} id The entity ID.
	 * @param {Editable} edits The edits to apply.
	 * @returns {Promise<boolean>} Whether the edit was successful.
	 */
	const editRecord =
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
					id: `${ NOTICE_CONTEXT }-edit-error-${ Date.now() }`,
				} );
			}
		};

	/**
	 * Save an edited entity record.
	 *
	 * @param {number} id The entity ID.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<boolean>} Whether the save was successful.
	 */
	const saveRecord =
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
							id: `${ NOTICE_CONTEXT }-validation-${ Date.now() }`,
						} );

						return false;
					}
				}

				const result: Editable | false = await registry
					.dispatch( coreDataStore )
					.saveEditedEntityRecord( 'postType', name, id );

				if ( result ) {
					await handleNotice( context, {
						status: 'success',
						content: __( 'Entity saved successfully' ),
						id: `${ NOTICE_CONTEXT }-save-success`,
					} );
				}

				return !! result;
			} catch ( error ) {
				console.error( 'Save failed:', error );
				await handleNotice( context, {
					status: 'error',
					content: __( 'Failed to save entity' ),
					id: `${ NOTICE_CONTEXT }-save-error`,
				} );
				throw error;
			}
		};

	/**
	 * Undo the last action.
	 *
	 * @returns {Promise<void>}
	 */
	const undo =
		(): ThunkAction =>
		async ( { registry } ) =>
			await registry.dispatch( coreDataStore ).undo();

	/**
	 * Redo the last action.
	 *
	 * @returns {Promise<void>}
	 */
	const redo =
		(): ThunkAction =>
		async ( { registry } ) =>
			await registry.dispatch( coreDataStore ).redo();

	/**
	 * Reset the edits for an entity record.
	 *
	 * @param {number} _id The entity ID.
	 * @returns {Promise<void>}
	 */
	const resetRecordEdits =
		( _id?: number ): ThunkAction =>
		async ( { registry } ) => {
			const select = registry.select( coreDataStore );
			const dispatch = registry.dispatch( coreDataStore );

			// Keep undoing while there are edits to undo
			while ( select.hasUndo() ) {
				await dispatch.undo();
			}
		};

	/**
	 * Invalidate the list of entities.
	 *
	 * @param {any} query The query to invalidate.
	 * @returns {Promise<void>}
	 */
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

	return {
		createRecord,
		updateRecord,
		editRecord,
		saveRecord,
		deleteRecord,
		invalidateList,
		resetRecordEdits,
		undo,
		redo,
	};
};

export const createPostTypeActions = <
	T extends Post< 'edit' >,
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
