import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreDataStore } from '@wordpress/core-data';

import { storeHasNotices } from './notice-types';
import type { BaseEntityRecords } from '@wordpress/core-data';
import type { ThunkAction, ThunkArgs } from '../types';

export const createPostTypeActions = <
	T extends
		BaseEntityRecords.BaseEntity< 'edit' > = BaseEntityRecords.BaseEntity< 'edit' >,
>(
	name: string
) => {
	const DEFAULT_QUERY = { per_page: -1, context: 'view' } as const;
	const NOTICE_CONTEXT = `${ name }-editor`;

	/**
	 * Helper to handle notices with fallback to core notices
	 */
	const handleNotice = async (
		context: ThunkArgs,
		options: {
			type: 'success' | 'error';
			message: string;
		}
	) => {
		if ( storeHasNotices( context ) ) {
			context.dispatch.createNotice( options.type, options.message, {
				context: NOTICE_CONTEXT,
			} );
		} else {
			// Fallback to core notices
			await context
				.dispatch( noticesStore )
				.createNotice( options.type, options.message, {
					context: NOTICE_CONTEXT,
				} );
		}
	};

	return {
		/**
		 * Create with validation support.
		 */
		create:
			(
				entity: Partial< T >,
				validate?: (
					entity: Partial< T >
				) => true | { message: string }
			): ThunkAction< T | undefined, S > =>
			async ( context ) => {
				try {
					if ( validate ) {
						const validation = validate( entity );
						if ( validation !== true ) {
							await handleNotice( context, {
								type: 'error',
								message: validation.message,
							} );
							return;
						}
					}

					const result = await context
						.dispatch( coreDataStore )
						.saveEntityRecord( 'postType', name, entity );

					if ( result ) {
						await handleNotice( context, {
							type: 'success',
							message: __( 'Entity created successfully' ),
						} );
					}

					return result;
				} catch ( error ) {
					await handleNotice( context, {
						type: 'error',
						message:
							error instanceof Error
								? error.message
								: __( 'Failed to create entity' ),
					} );
					throw error;
				}
			},

		/**
		 * Store unsaved edits.
		 */
		edit:
			( id: number, edits: Partial< T > ): ThunkAction< void, S > =>
			async ( context ) => {
				try {
					await context
						.dispatch( coreDataStore )
						.editEntityRecord( 'postType', name, id, edits );
				} catch ( error ) {
					console.error( 'Edit failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to edit entity' ),
					} );
				}
			},

		/**
		 * Save with validation support.
		 */
		save:
			(
				id: number,
				validate?: ( entity: T ) => true | { message: string }
			): ThunkAction< T | undefined > =>
			async ( context ) => {
				const { select, dispatch } = context;

				try {
					const entity = select(
						coreDataStore
					).getEditedEntityRecord(
						'postType',
						name,
						id
					) as unknown as T;

					// Run validation if provided
					if ( validate ) {
						const validation = validate( entity );
						if ( validation !== true ) {
							await handleNotice( context, {
								type: 'error',
								message: validation.message,
							} );
							return;
						}
					}

					const result = await dispatch(
						coreDataStore
					).saveEntityRecord( 'postType', name, id );

					if ( result ) {
						await handleNotice( context, {
							type: 'success',
							message: __( 'Entity saved successfully' ),
						} );
					}

					return result;
				} catch ( error ) {
					console.error( 'Save failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to save entity' ),
					} );
					throw error;
				}
			},

		/**
		 * Delete with force option.
		 */
		delete:
			( id: number, force = false ): ThunkAction< void > =>
			async ( context ) => {
				try {
					await context
						.dispatch( coreDataStore )
						.deleteEntityRecord( 'postType', name, id, { force } );

					await handleNotice( context, {
						type: 'success',
						message: __( 'Entity deleted successfully' ),
					} );
				} catch ( error ) {
					console.error( 'Delete failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to delete entity' ),
					} );
					throw error;
				}
			},

		/**
		 * Invalidate the entity list.
		 */
		invalidateList:
			( query?: any ): ThunkAction< void > =>
			async ( context ) => {
				await context
					.dispatch( coreDataStore )
					// @ts-expect-error
					.invalidateResolution( 'getEntityRecords', [
						'postType',
						name,
						query ?? DEFAULT_QUERY,
					] );
			},

		/**
		 * Reset edits.
		 */
		resetEdits:
			( id: number ): ThunkAction< void > =>
			async ( { dispatch } ) => {
				await dispatch( coreDataStore ).editEntityRecord(
					'postType',
					name,
					id,
					{}
				);
			},

		/**
		 * Undo/Redo.
		 */
		undo:
			(): ThunkAction< void > =>
			async ( { dispatch } ) =>
				await dispatch( coreDataStore ).undo(),
		redo:
			(): ThunkAction< void > =>
			async ( { dispatch } ) =>
				await dispatch( coreDataStore ).redo(),
	};
};

export default createPostTypeActions;
